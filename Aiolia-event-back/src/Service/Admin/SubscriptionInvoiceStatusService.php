<?php

namespace App\Service\Admin;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service pour la gestion des statuts des factures d'abonnement
 * Utilise les fonctions SQL pour optimiser les performances :
 * - update_overdue_invoices_status() : Met à jour les statuts des factures en retard
 * - auto_pause_unpaid_subscriptions() : Met en pause automatiquement les abonnements non payés
 */
class SubscriptionInvoiceStatusService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Met à jour le statut des factures non payées en "overdue" (retard)
     * Utilise la fonction SQL update_overdue_invoices_status() pour optimiser
     * 
     * @return array{updated: int, errors: array, details: array}
     */
    public function updateOverdueInvoicesStatus(): array
    {
        $results = [
            'updated' => 0,
            'errors' => [],
            'details' => [],
        ];

        try {
            $connection = $this->entityManager->getConnection();

            // Appeler la fonction SQL pour mettre à jour les statuts
            $sql = "SELECT * FROM aiolia.update_overdue_invoices_status()";
            $result = $connection->executeQuery($sql);
            $rows = $result->fetchAllAssociative();

            foreach ($rows as $row) {
                $results['updated']++;
                $results['details'][] = [
                    'invoice_id' => $row['invoice_id'],
                    'invoice_number' => $row['invoice_number'],
                    'old_status' => $row['old_status'],
                    'new_status' => $row['new_status'],
                    'days_overdue' => (int) $row['days_overdue'],
                ];

                if ($this->logger) {
                    $this->logger->info('Facture marquée comme en retard via fonction SQL', [
                        'invoice_id' => $row['invoice_id'],
                        'invoice_number' => $row['invoice_number'],
                        'old_status' => $row['old_status'],
                        'new_status' => $row['new_status'],
                        'days_overdue' => $row['days_overdue'],
                    ]);
                }
            }

            if ($this->logger) {
                $this->logger->info('Mise à jour des statuts de factures en retard terminée', [
                    'updated' => $results['updated'],
                ]);
            }

        } catch (\Exception $e) {
            $errorMsg = 'Erreur lors de la mise à jour des statuts de factures en retard: ' . $e->getMessage();
            $results['errors'][] = $errorMsg;
            
            if ($this->logger) {
                $this->logger->error($errorMsg, [
                    'exception' => $e,
                ]);
            }
        }

        return $results;
    }

    /**
     * Met automatiquement en pause les abonnements dont la facture n'a pas été payée
     * Utilise la fonction SQL auto_pause_unpaid_subscriptions() pour optimiser
     * Règle : Si l'organisateur ne paie pas son abonnement du mois courant avant le 11ème jour, 
     *         son compte est automatiquement mis en pause
     * 
     * @return array{paused: int, errors: array, details: array}
     */
    public function autoPauseUnpaidSubscriptions(): array
    {
        $results = [
            'paused' => 0,
            'errors' => [],
            'details' => [],
        ];

        try {
            $connection = $this->entityManager->getConnection();

            // Appeler la fonction SQL pour mettre en pause automatiquement
            $sql = "SELECT * FROM aiolia.auto_pause_unpaid_subscriptions()";
            $result = $connection->executeQuery($sql);
            $rows = $result->fetchAllAssociative();

            foreach ($rows as $row) {
                $results['paused']++;
                $results['details'][] = [
                    'subscription_id' => $row['subscription_id'],
                    'organizer_profile_id' => $row['organizer_profile_id'],
                    'organizer_user_id' => $row['organizer_user_id'],
                    'invoice_id' => $row['invoice_id'],
                    'invoice_number' => $row['invoice_number'],
                    'invoice_old_status' => $row['invoice_old_status'] ?? $row['old_status'] ?? null,
                    'invoice_new_status' => $row['invoice_new_status'] ?? 'void',
                    'subscription_old_status' => $row['subscription_old_status'] ?? null,
                    'subscription_new_status' => $row['subscription_new_status'] ?? $row['new_status'] ?? 'paused',
                    'paused_at' => $row['paused_at'],
                ];

                if ($this->logger) {
                    $this->logger->info('Abonnement mis en pause et facture annulée automatiquement via fonction SQL', [
                        'subscription_id' => $row['subscription_id'],
                        'organizer_profile_id' => $row['organizer_profile_id'],
                        'invoice_id' => $row['invoice_id'],
                        'invoice_number' => $row['invoice_number'],
                        'invoice_old_status' => $row['invoice_old_status'] ?? $row['old_status'] ?? null,
                        'invoice_new_status' => $row['invoice_new_status'] ?? 'void',
                        'subscription_old_status' => $row['subscription_old_status'] ?? null,
                        'subscription_new_status' => $row['subscription_new_status'] ?? $row['new_status'] ?? 'paused',
                        'paused_at' => $row['paused_at'],
                    ]);
                }
            }

            if ($this->logger) {
                $this->logger->info('Mise en pause automatique des abonnements non payés terminée', [
                    'paused' => $results['paused'],
                ]);
            }

        } catch (\Exception $e) {
            $errorMsg = 'Erreur lors de la mise en pause automatique des abonnements: ' . $e->getMessage();
            $results['errors'][] = $errorMsg;
            
            if ($this->logger) {
                $this->logger->error($errorMsg, [
                    'exception' => $e,
                ]);
            }
        }

        return $results;
    }

    /**
     * Exécute toutes les mises à jour de statut (overdue + pause automatique)
     * 
     * @return array{overdue: array, paused: array}
     */
    public function updateAllStatuses(): array
    {
        return [
            'overdue' => $this->updateOverdueInvoicesStatus(),
            'paused' => $this->autoPauseUnpaidSubscriptions(),
        ];
    }
}

