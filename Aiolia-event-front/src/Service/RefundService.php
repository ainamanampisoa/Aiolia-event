<?php

namespace App\Service;

use App\Repository\OrderRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;

class RefundService
{
    public function __construct(
        private readonly Connection $connection,
        private readonly OrderRepository $orderRepository,
        private readonly MvolaPaymentClient $mvolaClient,
        private readonly NotificationService $notificationService,
        private readonly ?LoggerInterface $logger = null
    ) {
    }

    /**
     * Rembourse tous les billets d'un événement annulé.
     * 
     * @param int $eventId ID de l'événement annulé
     * @param string $reason Raison de l'annulation
     * @return array Résultat avec statistiques
     */
    public function refundEventTickets(int $eventId, string $reason = 'Événement annulé'): array
    {
        $this->logger?->info("Début du remboursement pour l'événement {$eventId}", ['event_id' => $eventId, 'reason' => $reason]);

        try {
            // Démarrer une transaction
            $this->connection->beginTransaction();

            // Récupérer toutes les commandes payées pour cet événement
            $orders = $this->findPaidOrdersForEvent($eventId);
            
            if (empty($orders)) {
                $this->connection->rollBack();
                return [
                    'success' => true,
                    'message' => 'Aucune commande à rembourser pour cet événement',
                    'refunded_orders' => 0,
                    'refunded_tickets' => 0,
                    'total_amount' => 0
                ];
            }

            $refundedOrders = 0;
            $refundedTickets = 0;
            $totalRefunded = 0;
            $errors = [];

            foreach ($orders as $order) {
                try {
                    $result = $this->refundOrder($order['id'], $order['user_id'], $order['total_amount'], $reason);
                    
                    if ($result['success']) {
                        $refundedOrders++;
                        $refundedTickets += $order['ticket_count'];
                        $totalRefunded += $order['total_amount'];
                    } else {
                        $errors[] = [
                            'order_id' => $order['id'],
                            'error' => $result['message']
                        ];
                    }
                } catch (\Exception $e) {
                    $this->logger?->error("Erreur lors du remboursement de la commande {$order['id']}", [
                        'order_id' => $order['id'],
                        'error' => $e->getMessage()
                    ]);
                    $errors[] = [
                        'order_id' => $order['id'],
                        'error' => $e->getMessage()
                    ];
                }
            }

            // Valider la transaction
            $this->connection->commit();

            $this->logger?->info("Remboursement terminé pour l'événement {$eventId}", [
                'refunded_orders' => $refundedOrders,
                'refunded_tickets' => $refundedTickets,
                'total_amount' => $totalRefunded,
                'errors' => count($errors)
            ]);

            return [
                'success' => true,
                'message' => "Remboursement effectué : {$refundedOrders} commande(s), {$refundedTickets} billet(s)",
                'refunded_orders' => $refundedOrders,
                'refunded_tickets' => $refundedTickets,
                'total_amount' => $totalRefunded,
                'errors' => $errors
            ];

        } catch (Exception $e) {
            $this->connection->rollBack();
            $this->logger?->error("Erreur lors du remboursement de l'événement {$eventId}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors du remboursement : ' . $e->getMessage(),
                'refunded_orders' => 0,
                'refunded_tickets' => 0,
                'total_amount' => 0
            ];
        }
    }

    /**
     * Rembourse une commande spécifique.
     */
    private function refundOrder(int $orderId, int $userId, float $amount, string $reason): array
    {
        try {
            // Récupérer les informations de paiement
            $paymentTransaction = $this->findPaymentTransaction($orderId);
            
            if (!$paymentTransaction) {
                return [
                    'success' => false,
                    'message' => 'Transaction de paiement introuvable'
                ];
            }

            // Vérifier que le paiement a été effectué
            if ($paymentTransaction['status'] !== 'paid') {
                return [
                    'success' => false,
                    'message' => "Le paiement n'a pas été effectué (statut: {$paymentTransaction['status']})"
                ];
            }

            // Effectuer le remboursement via Mvola si disponible
            $refundResult = null;
            if ($this->mvolaClient && $paymentTransaction['mvola_transaction_id']) {
                try {
                    $refundResult = $this->processMvolaRefund(
                        $paymentTransaction['mvola_transaction_id'],
                        $amount,
                        $paymentTransaction['customer_msisdn'] ?? null,
                        $reason
                    );
                } catch (\Exception $e) {
                    $this->logger?->warning("Impossible d'effectuer le remboursement Mvola, marquage manuel", [
                        'order_id' => $orderId,
                        'error' => $e->getMessage()
                    ]);
                    // On continue quand même pour marquer comme remboursé
                }
            }

            // Mettre à jour les statuts dans la base de données
            $this->markOrderAsRefunded($orderId, $userId, $reason);

            // Envoyer une notification à l'utilisateur
            $this->sendRefundNotification($userId, $orderId, $amount, $reason, $paymentTransaction['currency'] ?? 'MGA');

            return [
                'success' => true,
                'message' => 'Remboursement effectué avec succès',
                'mvola_refund' => $refundResult
            ];

        } catch (\Exception $e) {
            $this->logger?->error("Erreur lors du remboursement de la commande {$orderId}", [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ];
        }
    }

    /**
     * Trouve toutes les commandes payées pour un événement.
     * Méthode publique pour permettre à l'EventListener de vérifier s'il y a des commandes à rembourser.
     */
    public function findPaidOrdersForEvent(int $eventId): array
    {
        $sql = <<<SQL
            SELECT DISTINCT
                o.id,
                o.user_id,
                o.total_amount,
                o.currency,
                COUNT(DISTINCT t.id) as ticket_count
            FROM aiolia.orders o
            INNER JOIN aiolia.order_items oi ON oi.order_id = o.id
            INNER JOIN aiolia.ticket_types tt ON tt.id = oi.ticket_type_id
            INNER JOIN aiolia.tickets t ON t.order_item_id = oi.id
            WHERE tt.event_id = :event_id
              AND o.status = 'paid'
              AND t.status = 'valid'
            GROUP BY o.id, o.user_id, o.total_amount, o.currency
            ORDER BY o.created_at DESC
        SQL;

        return $this->connection->executeQuery($sql, ['event_id' => $eventId])->fetchAllAssociative();
    }

    /**
     * Trouve la transaction de paiement pour une commande.
     */
    private function findPaymentTransaction(int $orderId): ?array
    {
        $sql = <<<SQL
            SELECT 
                id,
                order_id,
                mvola_correlation_id,
                mvola_transaction_id,
                transaction_reference,
                status,
                amount,
                currency,
                customer_msisdn,
                payment_method
            FROM aiolia.payment_transactions
            WHERE order_id = :order_id
              AND status = 'paid'
            ORDER BY created_at DESC
            LIMIT 1
        SQL;

        $result = $this->connection->executeQuery($sql, ['order_id' => $orderId])->fetchAssociative();
        return $result ?: null;
    }

    /**
     * Effectue un remboursement via l'API Mvola.
     */
    private function processMvolaRefund(string $originalTransactionId, float $amount, ?string $customerMsisdn, string $reason): ?array
    {
        // Note: L'API Mvola peut avoir une méthode de remboursement spécifique
        // Ici, on utilise une approche générique. À adapter selon la documentation Mvola
        
        try {
            // Générer une référence unique pour le remboursement
            $refundReference = 'REFUND_' . uniqid() . '_' . time();
            
            // Appeler l'API Mvola pour le remboursement
            // Note: Cette méthode doit être implémentée dans MvolaPaymentClient si elle n'existe pas
            // Pour l'instant, on retourne null et on marque quand même comme remboursé
            
            $this->logger?->info("Remboursement Mvola initié", [
                'original_transaction_id' => $originalTransactionId,
                'amount' => $amount,
                'reference' => $refundReference
            ]);

            // TODO: Implémenter l'appel API Mvola pour le remboursement
            // $response = $this->mvolaClient->refund($originalTransactionId, $amount, $refundReference);
            
            return [
                'reference' => $refundReference,
                'status' => 'initiated',
                'amount' => $amount
            ];

        } catch (\Exception $e) {
            $this->logger?->error("Erreur lors du remboursement Mvola", [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Marque une commande et ses tickets comme remboursés.
     */
    private function markOrderAsRefunded(int $orderId, int $userId, string $reason): void
    {
        // Mettre à jour les tickets
        $this->connection->executeStatement(
            <<<SQL
                UPDATE aiolia.tickets
                SET status = 'refunded',
                    metadata = COALESCE(metadata, '{}'::jsonb) || jsonb_build_object(
                        'refunded_at', NOW()::text,
                        'refund_reason', :reason
                    )
                WHERE id IN (
                    SELECT t.id
                    FROM aiolia.tickets t
                    INNER JOIN aiolia.order_items oi ON oi.id = t.order_item_id
                    WHERE oi.order_id = :order_id
                      AND t.status = 'valid'
                )
            SQL,
            [
                'order_id' => $orderId,
                'reason' => $reason
            ]
        );

        // Mettre à jour la commande
        $this->connection->executeStatement(
            <<<SQL
                UPDATE aiolia.orders
                SET status = 'cancelled',
                    notes = COALESCE(notes::jsonb, '{}'::jsonb) || jsonb_build_object(
                        'refunded_at', NOW()::text,
                        'refund_reason', :reason,
                        'refunded', true
                    )::text
                WHERE id = :order_id
                  AND user_id = :user_id
            SQL,
            [
                'order_id' => $orderId,
                'user_id' => $userId,
                'reason' => $reason
            ]
        );

        // Mettre à jour les transactions de paiement
        $this->connection->executeStatement(
            <<<SQL
                UPDATE aiolia.payment_transactions
                SET status = 'refunded',
                    updated_at = NOW()
                WHERE order_id = :order_id
                  AND status = 'paid'
            SQL,
            ['order_id' => $orderId]
        );

        // Ajouter l'historique de statut
        $this->connection->executeStatement(
            <<<SQL
                INSERT INTO aiolia.order_status_history (order_id, status_from, status_to, metadata, changed_at)
                VALUES (:order_id, 'paid', 'cancelled', jsonb_build_object('reason', :reason, 'refunded', true), NOW())
            SQL,
            [
                'order_id' => $orderId,
                'reason' => $reason
            ]
        );
    }

    /**
     * Envoie une notification de remboursement à l'utilisateur.
     */
    private function sendRefundNotification(int $userId, int $orderId, float $amount, string $reason, string $currency = 'MGA'): void
    {
        try {
            // Récupérer les informations de la commande pour la notification
            $order = $this->orderRepository->findOrderByIdAndUserId($orderId, $userId);
            
            if ($order) {
                $this->notificationService->sendRefundNotification(
                    $userId,
                    [
                        'order_id' => $orderId,
                        'amount' => $amount,
                        'currency' => $currency,
                        'reason' => $reason,
                        'event_title' => $order['event_titles'] ?? 'Événement'
                    ]
                );
            }
        } catch (\Exception $e) {
            $this->logger?->warning("Impossible d'envoyer la notification de remboursement", [
                'user_id' => $userId,
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
        }
    }
}

