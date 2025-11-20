<?php

namespace App\Service\Admin;

use App\Entity\SubscriptionInvoice;
use App\Entity\User;
use App\Repository\Admin\SubscriptionInvoiceRepository;
use App\Service\Organisateur\InvoiceNumberService;
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
     * Cette méthode doit être appelée pendant les 7 derniers jours du mois
     * 
     * Pour les abonnements mensuels : crée la facture du mois suivant
     * Pour les abonnements annuels : crée toutes les factures de l'année (12 factures)
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
            // Récupérer tous les organisateurs avec abonnements actifs (mensuels et annuels)
            // Requête SQL directe car nous n'avons pas d'entité OrganizerSubscription
            $sql = "
                SELECT 
                    os.id as subscription_id,
                    os.organizer_profile_id,
                    op.user_id,
                    sp.price,
                    sp.vat_rate,
                    sp.currency,
                    sp.billing_period
                FROM aiolia.organizer_subscriptions os
                INNER JOIN aiolia.organizer_profiles op ON op.id = os.organizer_profile_id
                INNER JOIN aiolia.subscription_plans sp ON sp.id = os.plan_id
                WHERE os.status = 'active'
                    AND sp.billing_period IN ('monthly', 'yearly')
                    AND sp.is_active = true
                    AND os.cancelled_at IS NULL
                    AND (os.cancel_at_period_end = false OR os.current_period_end > :now)
            ";

            $subscriptions = $connection->fetchAllAssociative($sql, [
                'now' => new \DateTimeImmutable(),
            ]);

            $year = (int) $targetMonth->format('Y');
            $month = (int) $targetMonth->format('m');
            $billingPeriod = $subscriptions[0]['billing_period'] ?? 'monthly';

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
                        // Pour les abonnements mensuels : créer uniquement la facture du mois suivant
                        $firstDayOfMonth = new \DateTimeImmutable(sprintf('%d-%02d-01 00:00:00', $year, $month));
                        // Date d'échéance : 10ème jour du mois (date limite de paiement)
                        $dueDate = new \DateTimeImmutable(sprintf('%d-%02d-10 23:59:59', $year, $month));

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

                        // Créer la facture mensuelle
                        $invoice = new SubscriptionInvoice();
                        $invoice->setInvoiceNumber($this->invoiceNumberService->generateSubscriptionInvoiceNumber());
                        $invoice->setSubscriptionId((string) $subscription['subscription_id']);
                        $invoice->setCustomer($user);
                        $invoice->setCurrency($subscription['currency'] ?? 'MGA');
                        $invoice->setSubtotalAmount((string) $subtotal);
                        $invoice->setTaxAmount((string) $taxAmount);
                        $invoice->setTotalAmount((string) $totalAmount);
                        $invoice->setStatus(SubscriptionInvoice::STATUS_DRAFT);
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
     * Met à jour le statut des factures non payées en "overdue" (retard)
     * Cette méthode doit être appelée entre le 10ème et 15ème jour du mois
     * Marque les factures non payées en retard à partir du 10ème jour du mois (date limite de paiement)
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
            $currentMonth = (int) $currentDate->format('n');
            $currentYear = (int) $currentDate->format('Y');
            $dayOfMonth = (int) $currentDate->format('d');
            
            // Calculer le 10ème jour du mois (date limite de paiement)
            $paymentDeadline = new \DateTimeImmutable(sprintf('%d-%d-10 23:59:59', $currentYear, $currentMonth));
            
            // Récupérer toutes les factures du mois courant en "issued" ou "draft" non payées
            // qui sont après le 10ème jour (date limite)
            $firstDayOfMonth = new \DateTimeImmutable(sprintf('%d-%d-01 00:00:00', $currentYear, $currentMonth));
            $lastDayOfMonth = new \DateTimeImmutable(sprintf('%d-%d-%d 23:59:59', $currentYear, $currentMonth, (int) $firstDayOfMonth->format('t')));
            
            $overdueInvoices = $this->entityManager
                ->getRepository(SubscriptionInvoice::class)
                ->createQueryBuilder('si')
                ->where('si.status IN (:statuses)')
                ->andWhere('si.issuedAt >= :monthStart')
                ->andWhere('si.issuedAt <= :monthEnd')
                ->andWhere('si.issuedAt <= :paymentDeadline')
                ->andWhere('si.paidAt IS NULL')
                ->setParameter('statuses', [SubscriptionInvoice::STATUS_DRAFT, SubscriptionInvoice::STATUS_ISSUED])
                ->setParameter('monthStart', $firstDayOfMonth)
                ->setParameter('monthEnd', $lastDayOfMonth)
                ->setParameter('paymentDeadline', $paymentDeadline)
                ->getQuery()
                ->getResult();
            
            // Filtrer pour ne garder que celles qui sont après le 10ème jour
            $overdueInvoices = array_filter($overdueInvoices, function($invoice) use ($currentDate, $paymentDeadline) {
                return $currentDate > $paymentDeadline && $invoice->getStatus() !== SubscriptionInvoice::STATUS_PAID;
            });

            foreach ($overdueInvoices as $invoice) {
                try {
                    $invoice->setStatus(SubscriptionInvoice::STATUS_OVERDUE);
                    $this->entityManager->persist($invoice);
                    $results['updated']++;

                    if ($this->logger) {
                        $daysOverdue = $invoice->getDaysOverdue($currentDate);
                        $this->logger->info('Facture marquée comme en retard', [
                            'invoice_number' => $invoice->getInvoiceNumber(),
                            'invoice_id' => $invoice->getId(),
                            'issued_at' => $invoice->getIssuedAt()->format('Y-m-d'),
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

