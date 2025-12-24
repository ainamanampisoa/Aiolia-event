<?php

namespace App\EventListener;

use App\Service\RefundService;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

/**
 * Écoute les changements de statut des événements et déclenche le remboursement
 * automatique quand un événement est annulé.
 */
class EventCancellationListener
{
    public function __construct(
        private readonly Connection $connection,
        private readonly RefundService $refundService,
        private readonly ?LoggerInterface $logger = null
    ) {
    }

    /**
     * Vérifie périodiquement les événements annulés qui n'ont pas encore été remboursés.
     * Cette méthode peut être appelée par une commande Symfony ou un cron job.
     */
    public function processCancelledEvents(): void
    {
        // Trouver les événements annulés récemment qui n'ont pas encore été traités
        $sql = <<<SQL
            SELECT DISTINCT
                e.id,
                e.title,
                e.status,
                e.updated_at
            FROM aiolia.events e
            WHERE e.status = 'cancelled'
              AND e.updated_at >= NOW() - INTERVAL '24 hours'
              AND NOT EXISTS (
                  SELECT 1
                  FROM aiolia.tickets t
                  INNER JOIN aiolia.order_items oi ON oi.id = t.order_item_id
                  INNER JOIN aiolia.ticket_types tt ON tt.id = oi.ticket_type_id
                  WHERE tt.event_id = e.id
                    AND t.status = 'refunded'
                    AND t.metadata::jsonb->>'refunded_at' IS NOT NULL
              )
            ORDER BY e.updated_at DESC
        SQL;

        $events = $this->connection->executeQuery($sql)->fetchAllAssociative();

        foreach ($events as $event) {
            try {
                $this->logger?->info("Traitement du remboursement pour l'événement annulé", [
                    'event_id' => $event['id'],
                    'event_title' => $event['title']
                ]);

                // Vérifier s'il y a des commandes à rembourser
                $orders = $this->refundService->findPaidOrdersForEvent((int) $event['id']);
                
                if (!empty($orders)) {
                    // Effectuer le remboursement
                    $reason = 'Événement annulé - Remboursement automatique';
                    $result = $this->refundService->refundEventTickets((int) $event['id'], $reason);
                    
                    $this->logger?->info("Remboursement effectué pour l'événement", [
                        'event_id' => $event['id'],
                        'refunded_orders' => $result['refunded_orders'] ?? 0,
                        'refunded_tickets' => $result['refunded_tickets'] ?? 0
                    ]);
                }
            } catch (\Exception $e) {
                $this->logger?->error("Erreur lors du traitement du remboursement", [
                    'event_id' => $event['id'],
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}

