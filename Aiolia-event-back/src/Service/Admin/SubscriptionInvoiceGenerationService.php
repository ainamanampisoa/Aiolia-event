<?php

namespace App\Service\Admin;

use App\Entity\SubscriptionInvoice;
use App\Entity\User;
use App\Repository\Admin\OrganizerSubscriptionRepository;
use App\Repository\Admin\SubscriptionInvoiceRepository;
use App\Service\Organisateur\InvoiceNumberService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class SubscriptionInvoiceGenerationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private InvoiceNumberService $invoiceNumberService,
        private SubscriptionInvoiceRepository $invoiceRepository,
        private OrganizerSubscriptionRepository $subscriptionRepository,
        private ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Génère automatiquement les factures du 1er du mois pour tous les organisateurs avec abonnements actifs
     * Règle : Les factures sont créées le 1er du mois si elles n'existent pas encore
     * 
     * Pour les abonnements mensuels : crée la facture du mois courant
     * Pour les abonnements annuels : crée toutes les factures de l'année (12 factures)
     * 
     * @param \DateTimeInterface $targetMonth Le mois pour lequel générer les factures (1er du mois)
     * @return array{created: int, skipped: int, errors: array}
     */
    public function generateMonthlyInvoices(\DateTimeInterface $targetMonth): array
    {
        $results = [
            'created' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        try {
            // Récupérer tous les organisateurs avec abonnements actifs via le repository
            $subscriptions = $this->subscriptionRepository->findActiveSubscriptionsForInvoiceGeneration();

            $year = (int) $targetMonth->format('Y');
            $month = (int) $targetMonth->format('m');
            // Le 1er du mois pour l'émission de la facture
            $firstDayOfMonth = new \DateTimeImmutable(sprintf('%d-%02d-01 00:00:00', $year, $month));
            // Date d'échéance : 10ème jour du mois (date limite de paiement)
            $dueDate = new \DateTimeImmutable(sprintf('%d-%02d-10 23:59:59', $year, $month));

            foreach ($subscriptions as $subscription) {
                try {
                    $billingPeriod = $subscription['billing_period'] ?? 'monthly';
                    
                    // Récupérer l'utilisateur (organisateur)
                    $user = $this->entityManager->getRepository(User::class)->find($subscription['user_id']);
                    if (!$user) {
                        $results['errors'][] = sprintf(
                            'Utilisateur introuvable pour l\'abonnement %s',
                            $subscription['subscription_id']
                        );
                        continue;
                    }

                    // Calculer les montants
                    $subtotal = (float) $subscription['price'];
                    $vatRate = (float) ($subscription['vat_rate'] ?? 20);
                    $taxAmount = $subtotal * ($vatRate / 100);
                    $totalAmount = $subtotal + $taxAmount;

                    if ($billingPeriod === 'yearly') {
                        // Pour les abonnements annuels : créer toutes les factures de l'année (12 factures)
                        $this->generateYearlyInvoices(
                            $subscription,
                            $user,
                            $year,
                            $subtotal,
                            $taxAmount,
                            $totalAmount,
                            $vatRate,
                            $results
                        );
                    } else {
                        // Pour les abonnements mensuels : créer uniquement la facture du mois courant
                        // Vérifier si une facture existe déjà pour ce mois et cet abonnement via le repository
                        $existingInvoice = $this->invoiceRepository->findInvoiceForMonth(
                            (string) $subscription['subscription_id'],
                            $firstDayOfMonth
                        );

                        if ($existingInvoice) {
                            $results['skipped']++;
                            continue;
                        }

                        // Créer la facture mensuelle le 1er du mois
                        $invoice = new SubscriptionInvoice();
                        $invoice->setInvoiceNumber($this->invoiceNumberService->generateSubscriptionInvoiceNumber());
                        $invoice->setSubscriptionId((string) $subscription['subscription_id']);
                        $invoice->setCustomer($user);
                        $invoice->setCurrency($subscription['currency'] ?? 'MGA');
                        $invoice->setSubtotalAmount((string) $subtotal);
                        $invoice->setTaxAmount((string) $taxAmount);
                        $invoice->setTotalAmount((string) $totalAmount);
                        $invoice->setStatus(SubscriptionInvoice::STATUS_ISSUED); // Émise le 1er du mois
                        $invoice->setIssuedAt($firstDayOfMonth);
                        $invoice->setDueAt($dueDate); // Échéance le 10ème jour
                        $invoice->setMetadata([
                            'month' => $month,
                            'year' => $year,
                            'billing_period' => 'monthly',
                            'auto_generated' => true,
                        ]);

                        $this->entityManager->persist($invoice);
                        $results['created']++;

                        if ($this->logger) {
                            $this->logger->info('Facture d\'abonnement mensuelle générée le 1er du mois', [
                                'invoice_number' => $invoice->getInvoiceNumber(),
                                'subscription_id' => $subscription['subscription_id'],
                                'customer_email' => $user->getEmail(),
                                'month' => $month,
                                'year' => $year,
                                'issued_at' => $firstDayOfMonth->format('Y-m-d'),
                                'due_at' => $dueDate->format('Y-m-d'),
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    $errorMsg = sprintf(
                        'Erreur lors de la génération de la facture pour l\'abonnement %s: %s',
                        $subscription['subscription_id'],
                        $e->getMessage()
                    );
                    $results['errors'][] = $errorMsg;

                    if ($this->logger) {
                        $this->logger->error($errorMsg, [
                            'subscription_id' => $subscription['subscription_id'],
                            'exception' => $e,
                        ]);
                    }
                }
            }

            $this->entityManager->flush();
        } catch (\Exception $e) {
            $results['errors'][] = 'Erreur générale lors de la génération des factures: ' . $e->getMessage();
            if ($this->logger) {
                $this->logger->error('Erreur générale lors de la génération des factures mensuelles', [
                    'exception' => $e,
                ]);
            }
        }

        return $results;
    }

    /**
     * Génère toutes les factures de l'année pour un abonnement annuel (12 factures)
     * 
     * @param array $subscription Données de l'abonnement
     * @param User $user L'utilisateur organisateur
     * @param int $year L'année pour laquelle générer les factures
     * @param float $subtotal Montant HT
     * @param float $taxAmount Montant de la TVA
     * @param float $totalAmount Montant TTC
     * @param float $vatRate Taux de TVA
     * @param array &$results Tableau de résultats (modifié par référence)
     */
    private function generateYearlyInvoices(
        array $subscription,
        User $user,
        int $year,
        float $subtotal,
        float $taxAmount,
        float $totalAmount,
        float $vatRate,
        array &$results
    ): void {
        // Pour un abonnement annuel, créer 12 factures (une par mois)
        // Le prix annuel est divisé par 12 pour chaque mois
        $monthlySubtotal = $subtotal / 12;
        $monthlyTaxAmount = $taxAmount / 12;
        $monthlyTotalAmount = $totalAmount / 12;

        for ($month = 1; $month <= 12; $month++) {
            try {
                $firstDayOfMonth = new \DateTimeImmutable(sprintf('%d-%02d-01 00:00:00', $year, $month));
                $dueDate = new \DateTimeImmutable(sprintf('%d-%02d-10 23:59:59', $year, $month));

                // Vérifier si une facture existe déjà pour ce mois et cet abonnement via le repository
                $existingInvoice = $this->invoiceRepository->findInvoiceForMonth(
                    (string) $subscription['subscription_id'],
                    $firstDayOfMonth
                );

                if ($existingInvoice) {
                    $results['skipped']++;
                    continue;
                }

                // Créer la facture mensuelle pour l'abonnement annuel
                $invoice = new SubscriptionInvoice();
                $invoice->setInvoiceNumber($this->invoiceNumberService->generateSubscriptionInvoiceNumber());
                $invoice->setSubscriptionId((string) $subscription['subscription_id']);
                $invoice->setCustomer($user);
                $invoice->setCurrency($subscription['currency'] ?? 'MGA');
                $invoice->setSubtotalAmount((string) $monthlySubtotal);
                $invoice->setTaxAmount((string) $monthlyTaxAmount);
                $invoice->setTotalAmount((string) $monthlyTotalAmount);
                $invoice->setStatus(SubscriptionInvoice::STATUS_DRAFT);
                $invoice->setIssuedAt($firstDayOfMonth);
                $invoice->setDueAt($dueDate);
                $invoice->setMetadata([
                    'month' => $month,
                    'year' => $year,
                    'billing_period' => 'yearly',
                    'yearly_subscription' => true,
                    'total_yearly_amount' => $totalAmount,
                    'auto_generated' => true,
                ]);

                $this->entityManager->persist($invoice);
                $results['created']++;

                if ($this->logger) {
                    $this->logger->info('Facture d\'abonnement annuel générée (mois)', [
                        'invoice_number' => $invoice->getInvoiceNumber(),
                        'subscription_id' => $subscription['subscription_id'],
                        'customer_email' => $user->getEmail(),
                        'month' => $month,
                        'year' => $year,
                    ]);
                }
            } catch (\Exception $e) {
                $errorMsg = sprintf(
                    'Erreur lors de la génération de la facture annuelle (mois %d) pour l\'abonnement %s: %s',
                    $month,
                    $subscription['subscription_id'],
                    $e->getMessage()
                );
                $results['errors'][] = $errorMsg;

                if ($this->logger) {
                    $this->logger->error($errorMsg, [
                        'subscription_id' => $subscription['subscription_id'],
                        'month' => $month,
                        'exception' => $e,
                    ]);
                }
            }
        }
    }

    /**
     * Met à jour le statut des factures non payées en "overdue" (retard) et met en pause les organisateurs
     * Règle : Si une facture n'est pas payée dans les 10 jours (après échéance),
     *         le statut devient "overdue" et l'organisateur est automatiquement mis en pause
     * 
     * Cette méthode doit être appelée après le 10ème jour du mois
     * 
     * @param \DateTimeInterface $currentDate La date actuelle
     * @return array{updated: int, paused: int, errors: array}
     */
    public function markOverdueInvoices(\DateTimeInterface $currentDate): array
    {
        $results = [
            'updated' => 0,
            'paused' => 0,
            'errors' => [],
        ];

        try {
            // Récupérer toutes les factures en retard via le repository
            $overdueInvoices = $this->invoiceRepository->findOverdueInvoices($currentDate);

            foreach ($overdueInvoices as $invoice) {
                try {
                    // Marquer la facture comme en retard
                    $invoice->setStatus(SubscriptionInvoice::STATUS_OVERDUE);
                    $this->entityManager->persist($invoice);
                    $results['updated']++;

                    // Mettre en pause l'organisateur automatiquement
                    $subscriptionId = (int) $invoice->getSubscriptionId();
                    $subscription = $this->subscriptionRepository->findSubscription($subscriptionId);
                    
                    if ($subscription && $subscription['statut'] === 'active') {
                        // Mettre en pause l'abonnement
                        $this->subscriptionRepository->pauseSubscription(
                            $subscriptionId,
                            $currentDate,
                            [
                                'auto_paused_reason' => 'invoice_overdue',
                                'auto_paused_at' => $currentDate->format('Y-m-d H:i:s'),
                                'overdue_invoice_id' => $invoice->getId(),
                                'overdue_invoice_number' => $invoice->getInvoiceNumber(),
                                'overdue_invoice_month' => $invoice->getIssuedAt()->format('Y-m'),
                            ]
                        );
                        $results['paused']++;

                        if ($this->logger) {
                            $this->logger->info('Organisateur mis en pause automatiquement pour facture en retard', [
                                'subscription_id' => $subscriptionId,
                                'invoice_number' => $invoice->getInvoiceNumber(),
                                'invoice_id' => $invoice->getId(),
                            ]);
                        }
                    }

                    if ($this->logger) {
                        $daysOverdue = $this->calculateDaysOverdue($invoice, $currentDate);
                        $this->logger->info('Facture marquée comme en retard et organisateur mis en pause', [
                            'invoice_number' => $invoice->getInvoiceNumber(),
                            'invoice_id' => $invoice->getId(),
                            'subscription_id' => $subscriptionId,
                            'issued_at' => $invoice->getIssuedAt()->format('Y-m-d'),
                            'due_at' => $invoice->getDueAt()?->format('Y-m-d'),
                            'days_overdue' => $daysOverdue,
                        ]);
                    }
                } catch (\Exception $e) {
                    $errorMsg = sprintf(
                        'Erreur lors de la mise à jour de la facture %s: %s',
                        $invoice->getInvoiceNumber(),
                        $e->getMessage()
                    );
                    $results['errors'][] = $errorMsg;

                    if ($this->logger) {
                        $this->logger->error($errorMsg, [
                            'invoice_id' => $invoice->getId(),
                            'exception' => $e,
                        ]);
                    }
                }
            }

            $this->entityManager->flush();
        } catch (\Exception $e) {
            $results['errors'][] = 'Erreur générale lors de la mise à jour des factures en retard: ' . $e->getMessage();
            if ($this->logger) {
                $this->logger->error('Erreur générale lors de la mise à jour des factures en retard', [
                    'exception' => $e,
                ]);
            }
        }

        return $results;
    }

    /**
     * Calcule le nombre de jours de retard d'une facture
     */
    public function calculateDaysOverdue(SubscriptionInvoice $invoice, ?\DateTimeInterface $currentDate = null): ?int
    {
        if ($invoice->getStatus() !== SubscriptionInvoice::STATUS_OVERDUE && !$invoice->isPaid()) {
            // Si la facture n'est pas encore en retard, vérifier si elle devrait l'être
            $dueDate = $invoice->getDueAt();
            if (!$dueDate) {
                return null;
            }

            $now = $currentDate ?? new \DateTimeImmutable();
            if ($now > $dueDate) {
                $diff = $now->diff($dueDate);
                return (int) $diff->days;
            }

            return null;
        }

        if ($invoice->getStatus() !== SubscriptionInvoice::STATUS_OVERDUE) {
            return null;
        }

        $dueDate = $invoice->getDueAt();
        if (!$dueDate) {
            return null;
        }

        $now = $currentDate ?? new \DateTimeImmutable();
        $diff = $now->diff($dueDate);
        return (int) $diff->days;
    }
}

