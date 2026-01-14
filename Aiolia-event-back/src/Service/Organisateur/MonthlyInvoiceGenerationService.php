<?php

namespace App\Service\Organisateur;

use App\Entity\SubscriptionInvoice;
use App\Entity\User;
use App\Entity\SubscriptionPlan;
use App\Entity\OrganizerSubscription;
use App\Repository\Admin\OrganizerSubscriptionRepository;
use App\Repository\Admin\SubscriptionInvoiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service pour générer automatiquement les factures mensuelles des abonnements
 */
class MonthlyInvoiceGenerationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private OrganizerSubscriptionRepository $subscriptionRepository,
        private SubscriptionInvoiceRepository $invoiceRepository,
        private InvoiceNumberService $invoiceNumberService,
        private SubscriptionNotificationService $notificationService,
        private ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Génère les factures mensuelles pour tous les abonnements actifs dont la date de renouvellement est aujourd'hui
     * 
     * @param \DateTimeInterface|null $billingMonth Mois de facturation (par défaut: mois courant)
     * @return array{created: int, skipped: int, errors: array}
     */
    public function generateMonthlyInvoices(?\DateTimeInterface $billingMonth = null): array
    {
        $billingMonth = $billingMonth ?? new \DateTimeImmutable('first day of this month');
        $billingMonthFormatted = $billingMonth->format('Y-m-01');
        
        $results = [
            'created' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        try {
            // Récupérer tous les abonnements actifs dont la date de renouvellement est aujourd'hui (1er du mois)
            $subscriptions = $this->findSubscriptionsDueForRenewal($billingMonth);

            if ($this->logger) {
                $this->logger->info('Génération des factures mensuelles', [
                    'billing_month' => $billingMonthFormatted,
                    'subscriptions_count' => count($subscriptions),
                ]);
            }

            foreach ($subscriptions as $subscriptionData) {
                try {
                    $subscriptionId = (int) $subscriptionData['subscription_id'];
                    $userId = (int) $subscriptionData['user_id'];
                    $planId = (int) $subscriptionData['plan_id'];

                    // Vérification 1 : Vérifier via le repository des abonnements
                    $existingInvoice = $this->subscriptionRepository->findInvoiceForMonth(
                        $subscriptionId,
                        $billingMonthFormatted
                    );

                    if ($existingInvoice) {
                        $results['skipped']++;
                        if ($this->logger) {
                            $this->logger->info('Facture déjà existante (vérification 1)', [
                                'subscription_id' => $subscriptionId,
                                'billing_month' => $billingMonthFormatted,
                                'existing_invoice_id' => $existingInvoice['id'],
                                'existing_status' => $existingInvoice['statut'],
                            ]);
                        }
                        continue;
                    }

                    // Vérification 2 : Vérifier directement via le repository des factures (double vérification)
                    $existingInvoiceEntity = $this->invoiceRepository->createQueryBuilder('si')
                        ->where('si.subscriptionId = :subscriptionId')
                        ->andWhere('si.billingMonth = :billingMonth')
                        ->setParameter('subscriptionId', (string) $subscriptionId)
                        ->setParameter('billingMonth', $billingMonthFormatted)
                        ->getQuery()
                        ->getOneOrNullResult();

                    if ($existingInvoiceEntity) {
                        $results['skipped']++;
                        if ($this->logger) {
                            $this->logger->warning('Facture déjà existante (vérification 2 - doublon détecté)', [
                                'subscription_id' => $subscriptionId,
                                'billing_month' => $billingMonthFormatted,
                                'existing_invoice_id' => $existingInvoiceEntity->getId(),
                                'existing_invoice_number' => $existingInvoiceEntity->getInvoiceNumber(),
                                'existing_status' => $existingInvoiceEntity->getStatus(),
                            ]);
                        }
                        continue;
                    }

                    // Récupérer les entités
                    $user = $this->entityManager->getRepository(User::class)->find($userId);
                    $plan = $this->entityManager->getRepository(SubscriptionPlan::class)->find($planId);
                    $subscription = $this->entityManager->getRepository(OrganizerSubscription::class)->find($subscriptionId);

                    if (!$user || !$plan || !$subscription) {
                        $results['errors'][] = "Entités non trouvées pour l'abonnement {$subscriptionId}";
                        continue;
                    }

                    // Calculer les montants
                    $price = (float) $subscriptionData['price'];
                    $vatRate = (float) $subscriptionData['vat_rate'];
                    $subtotal = $price / (1 + ($vatRate / 100));
                    $taxAmount = $price - $subtotal;

                    // Générer le numéro de facture
                    $invoiceNumber = $this->invoiceNumberService->generateSubscriptionInvoiceNumber();

                    // Vérification 3 : Vérifier que le numéro de facture n'existe pas déjà (sécurité supplémentaire)
                    $existingInvoiceByNumber = $this->invoiceRepository->findOneBy([
                        'invoiceNumber' => $invoiceNumber
                    ]);

                    if ($existingInvoiceByNumber) {
                        $results['errors'][] = "Le numéro de facture {$invoiceNumber} existe déjà pour l'abonnement {$subscriptionId}";
                        if ($this->logger) {
                            $this->logger->error('Numéro de facture en doublon détecté', [
                                'subscription_id' => $subscriptionId,
                                'invoice_number' => $invoiceNumber,
                                'existing_invoice_id' => $existingInvoiceByNumber->getId(),
                            ]);
                        }
                        continue;
                    }

                    // Créer la facture
                    $invoice = $this->createInvoice(
                        $subscription,
                        $user,
                        $plan,
                        $price,
                        $subtotal,
                        $taxAmount,
                        $invoiceNumber,
                        $billingMonth
                    );

                    // Vérification 4 : Vérification finale avant persistance (pour éviter les race conditions)
                    // Re-vérifier une dernière fois juste avant de persister
                    $finalCheck = $this->invoiceRepository->createQueryBuilder('si')
                        ->where('si.subscriptionId = :subscriptionId')
                        ->andWhere('si.billingMonth = :billingMonth')
                        ->setParameter('subscriptionId', (string) $subscriptionId)
                        ->setParameter('billingMonth', $billingMonthFormatted)
                        ->getQuery()
                        ->getOneOrNullResult();

                    if ($finalCheck) {
                        $results['skipped']++;
                        if ($this->logger) {
                            $this->logger->warning('Facture créée entre-temps (race condition évitée)', [
                                'subscription_id' => $subscriptionId,
                                'billing_month' => $billingMonthFormatted,
                                'existing_invoice_id' => $finalCheck->getId(),
                                'existing_invoice_number' => $finalCheck->getInvoiceNumber(),
                            ]);
                        }
                        continue;
                    }

                    // Persister la facture
                    try {
                        $this->entityManager->persist($invoice);
                        $this->entityManager->flush();
                    } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
                        // Si une contrainte unique est violée (doublon au niveau base de données)
                        $results['skipped']++;
                        if ($this->logger) {
                            $this->logger->warning('Contrainte unique violée - facture en doublon détectée au niveau base de données', [
                                'subscription_id' => $subscriptionId,
                                'billing_month' => $billingMonthFormatted,
                                'invoice_number' => $invoiceNumber,
                                'error' => $e->getMessage(),
                            ]);
                        }
                        $this->entityManager->clear(); // Nettoyer l'entity manager
                        continue;
                    }

                    // Mettre à jour la date de renouvellement au mois suivant
                    $nextRenewalDate = clone $billingMonth;
                    $nextRenewalDate->modify('+1 month');
                    $subscription->setRenouvellementLe($nextRenewalDate);
                    $this->entityManager->flush();

                    // Envoyer la notification
                    $this->notificationService->sendInvoiceGenerated($invoice);

                    $results['created']++;

                    if ($this->logger) {
                        $this->logger->info('Facture mensuelle créée', [
                            'invoice_number' => $invoiceNumber,
                            'subscription_id' => $subscriptionId,
                            'billing_month' => $billingMonthFormatted,
                        ]);
                    }
                } catch (\Exception $e) {
                    $errorMsg = "Erreur pour l'abonnement {$subscriptionData['subscription_id']}: " . $e->getMessage();
                    $results['errors'][] = $errorMsg;
                    
                    if ($this->logger) {
                        $this->logger->error('Erreur lors de la génération de facture', [
                            'subscription_id' => $subscriptionData['subscription_id'],
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            $results['errors'][] = "Erreur générale: " . $e->getMessage();
            if ($this->logger) {
                $this->logger->error('Erreur lors de la génération des factures mensuelles', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Trouve les abonnements dont la date de renouvellement correspond au mois donné
     */
    private function findSubscriptionsDueForRenewal(\DateTimeInterface $billingMonth): array
    {
        $connection = $this->entityManager->getConnection();
        
        // Récupérer les abonnements actifs dont la date de renouvellement est le 1er du mois
        $sql = "
            SELECT 
                os.id as subscription_id,
                os.id_profil_organisateur as organizer_profile_id,
                op.id_utilisateur as user_id,
                sp.id as plan_id,
                sp.prix as price,
                sp.taux_tva as vat_rate,
                sp.devise as currency,
                sp.periode_facturation as billing_period,
                os.statut as subscription_status,
                os.renouvellement_le,
                os.metadonnees
            FROM aiolia.abonnements_organisateurs os
            INNER JOIN aiolia.profils_organisateurs op ON op.id = os.id_profil_organisateur
            INNER JOIN aiolia.plans_abonnements sp ON sp.id = os.id_plan
            WHERE os.statut IN ('active', 'paused')
                AND sp.est_actif = true
                AND os.annule_le IS NULL
                AND DATE_TRUNC('month', os.renouvellement_le) = DATE_TRUNC('month', :billing_month::date)
        ";
        
        return $connection->fetchAllAssociative($sql, [
            'billing_month' => $billingMonth->format('Y-m-01'),
        ]);
    }

    /**
     * Crée une facture d'abonnement
     */
    private function createInvoice(
        OrganizerSubscription $subscription,
        User $customer,
        SubscriptionPlan $plan,
        float $totalAmount,
        float $subtotal,
        float $taxAmount,
        string $invoiceNumber,
        \DateTimeInterface $billingMonth
    ): SubscriptionInvoice {
        $invoice = new SubscriptionInvoice();
        $invoice->setInvoiceNumber($invoiceNumber);
        $invoice->setSubscriptionId((string) $subscription->getId());
        $invoice->setCustomer($customer);
        $invoice->setCurrency($plan->getDevise());
        $invoice->setSubtotalAmount((string) round($subtotal, 2));
        $invoice->setTaxAmount((string) round($taxAmount, 2));
        $invoice->setTotalAmount((string) round($totalAmount, 2));
        $invoice->setAmountHt((string) round($subtotal, 2));
        $invoice->setAmountTva((string) round($taxAmount, 2));
        $invoice->setAmountTtc((string) round($totalAmount, 2));
        
        // Mois de facturation
        $billingMonthDate = \DateTime::createFromImmutable($billingMonth);
        $billingMonthDate->modify('first day of this month');
        $invoice->setBillingMonth($billingMonthDate);
        
        // Date d'échéance = 10ème jour du mois de facturation
        $dueDate = clone $billingMonthDate;
        $dueDate->modify('+9 days')->setTime(23, 59, 59);
        $invoice->setDueAt($dueDate);
        
        // Date d'émission = aujourd'hui
        $invoice->setIssuedAt(new \DateTime());
        $invoice->setStatus(SubscriptionInvoice::STATUS_ISSUED);
        $invoice->setIsPrepaid(false);
        $invoice->setIsPauseMonth($subscription->getStatut() === OrganizerSubscription::STATUS_PAUSED);

        // Métadonnées
        $metadata = [
            'plan_code' => $plan->getCode(),
            'plan_niveau' => $plan->getNiveau(),
            'plan_periode' => $plan->getPeriodeFacturation(),
            'generated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'auto_generated' => true,
        ];
        $invoice->setMetadata($metadata);

        return $invoice;
    }
}

