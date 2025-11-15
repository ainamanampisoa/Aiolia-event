<?php

namespace App\Service;

use App\Entity\SubscriptionInvoice;
use App\Entity\User;
use App\Repository\SubscriptionInvoiceRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class SubscriptionInvoiceGenerationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private InvoiceNumberService $invoiceNumberService,
        private ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Génère automatiquement les factures du mois suivant pour tous les organisateurs avec abonnements actifs
     * Cette méthode doit être appelée pendant les 5 derniers jours du mois
     * 
     * @param \DateTimeInterface $targetMonth Le mois pour lequel générer les factures (le mois suivant)
     * @return array{created: int, skipped: int, errors: array}
     */
    public function generateMonthlyInvoices(\DateTimeInterface $targetMonth): array
    {
        $results = [
            'created' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        $connection = $this->entityManager->getConnection();

        try {
            // Récupérer tous les organisateurs avec abonnements actifs
            // Requête SQL directe car nous n'avons pas d'entité OrganizerSubscription
            $sql = "
                SELECT 
                    os.id as subscription_id,
                    os.organizer_id as organizer_profile_id,
                    op.user_id,
                    sp.price,
                    sp.vat_rate,
                    sp.currency
                FROM aiolia.organizer_subscriptions os
                INNER JOIN aiolia.organizer_profiles op ON op.id = os.organizer_id
                INNER JOIN aiolia.subscription_plans sp ON sp.id = os.plan_id
                WHERE os.status = 'active'
                    AND sp.billing_period = 'monthly'
                    AND sp.is_active = true
                    AND os.cancelled_at IS NULL
                    AND (os.cancel_at_period_end = false OR os.current_period_end > :now)
            ";

            $subscriptions = $connection->fetchAllAssociative($sql, [
                'now' => new \DateTimeImmutable(),
            ]);

            $year = (int) $targetMonth->format('Y');
            $month = (int) $targetMonth->format('m');
            $firstDayOfMonth = new \DateTimeImmutable(sprintf('%d-%02d-01', $year, $month));
            $dueDate = $firstDayOfMonth->modify('+15 days'); // Date d'échéance : 15 jours après le début du mois

            foreach ($subscriptions as $subscription) {
                try {
                    // Vérifier si une facture existe déjà pour ce mois et cette abonnement
                    $existingInvoice = $this->entityManager
                        ->getRepository(SubscriptionInvoice::class)
                        ->createQueryBuilder('si')
                        ->where('si.subscriptionId = :subscriptionId')
                        ->andWhere('si.issuedAt >= :monthStart')
                        ->andWhere('si.issuedAt < :monthEnd')
                        ->setParameter('subscriptionId', (string) $subscription['subscription_id'])
                        ->setParameter('monthStart', $firstDayOfMonth)
                        ->setParameter('monthEnd', $firstDayOfMonth->modify('+1 month'))
                        ->getQuery()
                        ->getOneOrNullResult();

                    if ($existingInvoice) {
                        $results['skipped']++;
                        continue;
                    }

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

                    // Créer la facture
                    $invoice = new SubscriptionInvoice();
                    $invoice->setInvoiceNumber($this->invoiceNumberService->generateSubscriptionInvoiceNumber());
                    $invoice->setSubscriptionId((string) $subscription['subscription_id']);
                    $invoice->setCustomer($user);
                    $invoice->setCurrency($subscription['currency'] ?? 'MGA');
                    $invoice->setSubtotalAmount((string) $subtotal);
                    $invoice->setTaxAmount((string) $taxAmount);
                    $invoice->setTotalAmount((string) $totalAmount);
                    $invoice->setStatus(SubscriptionInvoice::STATUS_DRAFT); // Statut draft par défaut
                    $invoice->setIssuedAt($firstDayOfMonth);
                    $invoice->setDueAt($dueDate);
                    $invoice->setMetadata([
                        'month' => $month,
                        'year' => $year,
                        'billing_period' => 'monthly',
                        'auto_generated' => true,
                    ]);

                    $this->entityManager->persist($invoice);
                    $results['created']++;

                    if ($this->logger) {
                        $this->logger->info('Facture d\'abonnement mensuelle générée', [
                            'invoice_number' => $invoice->getInvoiceNumber(),
                            'subscription_id' => $subscription['subscription_id'],
                            'customer_email' => $user->getEmail(),
                            'month' => $month,
                            'year' => $year,
                        ]);
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
     * Met à jour le statut des factures non payées en "overdue" (retard)
     * Cette méthode doit être appelée entre le 10ème et 15ème jour du mois
     * 
     * @param \DateTimeInterface $currentDate La date actuelle
     * @return array{updated: int, errors: array}
     */
    public function markOverdueInvoices(\DateTimeInterface $currentDate): array
    {
        $results = [
            'updated' => 0,
            'errors' => [],
        ];

        try {
            // Récupérer toutes les factures en "issued" ou "draft" dont la date d'échéance est passée
            $overdueInvoices = $this->entityManager
                ->getRepository(SubscriptionInvoice::class)
                ->createQueryBuilder('si')
                ->where('si.status IN (:statuses)')
                ->andWhere('si.dueAt < :now')
                ->setParameter('statuses', [SubscriptionInvoice::STATUS_DRAFT, SubscriptionInvoice::STATUS_ISSUED])
                ->setParameter('now', $currentDate)
                ->getQuery()
                ->getResult();

            foreach ($overdueInvoices as $invoice) {
                try {
                    $invoice->setStatus(SubscriptionInvoice::STATUS_OVERDUE);
                    $this->entityManager->persist($invoice);
                    $results['updated']++;

                    if ($this->logger) {
                        $this->logger->info('Facture marquée comme en retard', [
                            'invoice_number' => $invoice->getInvoiceNumber(),
                            'invoice_id' => $invoice->getId(),
                            'due_date' => $invoice->getDueAt()?->format('Y-m-d'),
                            'days_overdue' => $this->calculateDaysOverdue($invoice, $currentDate),
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

