<?php

namespace App\Service\Admin;

use App\Entity\SubscriptionInvoice;
use App\Repository\Admin\SubscriptionInvoiceRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service pour la génération automatique des factures d'abonnement
 * Utilise la fonction SQL generate_monthly_subscription_invoices() pour optimiser les performances
 */
class SubscriptionInvoiceGenerationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SubscriptionInvoiceRepository $invoiceRepository,
        private ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Génère automatiquement les factures mensuelles pour tous les abonnements actifs
     * Utilise la fonction SQL generate_monthly_subscription_invoices() pour une génération optimisée
     * 
     * @param \DateTimeInterface|null $targetMonth Le mois pour lequel générer les factures (1er du mois). Si null, utilise le mois courant
     * @return array{created: int, skipped: int, errors: array, details: array}
     */
    public function generateMonthlyInvoices(?\DateTimeInterface $targetMonth = null): array
    {
        $results = [
            'created' => 0,
            'skipped' => 0,
            'errors' => [],
            'details' => [],
        ];

        try {
            // Utiliser le mois courant si non spécifié
            if ($targetMonth === null) {
                $now = new \DateTime();
                $targetMonth = \DateTimeImmutable::createFromFormat('Y-m-d', $now->format('Y-m-01'));
            } else {
                // S'assurer que c'est le premier jour du mois
                $year = (int) $targetMonth->format('Y');
                $month = (int) $targetMonth->format('m');
                $targetMonth = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', sprintf('%d-%02d-01 00:00:00', $year, $month));
            }
            
            $targetMonthDate = $targetMonth->format('Y-m-d');

            $connection = $this->entityManager->getConnection();

            // Appeler la fonction SQL pour générer les factures
            $sql = "SELECT * FROM aiolia.generate_monthly_subscription_invoices(:target_month::DATE)";
            
            $stmt = $connection->prepare($sql);
            $stmt->bindValue('target_month', $targetMonthDate, \PDO::PARAM_STR);
            $result = $stmt->executeQuery();
            $rows = $result->fetchAllAssociative();

            // Traiter les résultats
            foreach ($rows as $row) {
                $action = $row['action'] ?? 'unknown';
                
                switch ($action) {
                    case 'created':
                    case 'created_pause':
                    case 'created_prepaid':
                        $results['created']++;
                        break;
                    case 'skipped':
                    case 'skipped_pause':
                    case 'deferred':
                        $results['skipped']++;
                        break;
                }

                $results['details'][] = [
                    'subscription_id' => $row['subscription_id'],
                    'invoice_id' => $row['invoice_id'],
                    'invoice_number' => $row['invoice_number'],
                    'status' => $row['status'],
                    'amount' => (float) $row['amount'],
                    'action' => $action,
                ];

                if ($this->logger && in_array($action, ['created', 'created_pause', 'created_prepaid'])) {
                    $this->logger->info('Facture d\'abonnement générée via fonction SQL', [
                        'subscription_id' => $row['subscription_id'],
                        'invoice_id' => $row['invoice_id'],
                        'invoice_number' => $row['invoice_number'],
                        'status' => $row['status'],
                        'amount' => $row['amount'],
                        'action' => $action,
                        'target_month' => $targetMonthDate,
                    ]);
                }
            }

            if ($this->logger) {
                $this->logger->info('Génération de factures mensuelles terminée', [
                    'created' => $results['created'],
                    'skipped' => $results['skipped'],
                    'target_month' => $targetMonthDate,
                ]);
            }

        } catch (\Exception $e) {
            $errorMsg = 'Erreur lors de la génération des factures mensuelles: ' . $e->getMessage();
            $results['errors'][] = $errorMsg;
            
            if ($this->logger) {
                $this->logger->error($errorMsg, [
                    'exception' => $e,
                    'target_month' => $targetMonthDate ?? null,
                ]);
            }
        }

        return $results;
    }

    /**
     * Génère les factures pour un mois spécifique
     * 
     * @param int $year Année
     * @param int $month Mois (1-12)
     * @return array{created: int, skipped: int, errors: array, details: array}
     */
    public function generateInvoicesForMonth(int $year, int $month): array
    {
        $targetMonth = new \DateTimeImmutable(sprintf('%d-%02d-01', $year, $month));
        return $this->generateMonthlyInvoices($targetMonth);
    }
}
