<?php

namespace App\Service\Admin;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service pour nettoyer les factures existantes en "overdue"
 * Utilise la fonction SQL cleanup_overdue_invoices() pour annuler les factures en retard
 */
class SubscriptionInvoiceCleanupService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Nettoie les factures existantes en "overdue" depuis plus de 10 jours
     * Annule ces factures (statut "void") et met en pause les abonnements associés
     * 
     * @return array{voided: int, paused: int, errors: array, details: array}
     */
    public function cleanupOverdueInvoices(): array
    {
        $results = [
            'voided' => 0,
            'paused' => 0,
            'errors' => [],
            'details' => [],
        ];

        try {
            $connection = $this->entityManager->getConnection();

            // Appeler la fonction SQL pour nettoyer les factures en retard
            $sql = "SELECT * FROM aiolia.cleanup_overdue_invoices()";
            $result = $connection->executeQuery($sql);
            $rows = $result->fetchAllAssociative();

            foreach ($rows as $row) {
                $results['voided']++;
                
                if ($row['subscription_new_status'] === 'paused') {
                    $results['paused']++;
                }

                $results['details'][] = [
                    'invoice_id' => $row['invoice_id'],
                    'invoice_number' => $row['invoice_number'],
                    'old_status' => $row['old_status'],
                    'new_status' => $row['new_status'],
                    'subscription_id' => $row['subscription_id'],
                    'subscription_new_status' => $row['subscription_new_status'],
                    'days_overdue' => (int) $row['days_overdue'],
                ];

                if ($this->logger) {
                    $this->logger->info('Facture en retard annulée et abonnement mis en pause via fonction SQL', [
                        'invoice_id' => $row['invoice_id'],
                        'invoice_number' => $row['invoice_number'],
                        'old_status' => $row['old_status'],
                        'new_status' => $row['new_status'],
                        'subscription_id' => $row['subscription_id'],
                        'subscription_new_status' => $row['subscription_new_status'],
                        'days_overdue' => $row['days_overdue'],
                    ]);
                }
            }

            if ($this->logger) {
                $this->logger->info('Nettoyage des factures en retard terminé', [
                    'voided' => $results['voided'],
                    'paused' => $results['paused'],
                ]);
            }

        } catch (\Exception $e) {
            $errorMsg = 'Erreur lors du nettoyage des factures en retard: ' . $e->getMessage();
            $results['errors'][] = $errorMsg;
            
            if ($this->logger) {
                $this->logger->error($errorMsg, [
                    'exception' => $e,
                ]);
            }
        }

        return $results;
    }
}

