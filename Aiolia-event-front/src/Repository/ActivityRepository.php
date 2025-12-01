<?php

namespace App\Repository;

use Doctrine\DBAL\Connection;

class ActivityRepository
{
    public function __construct(
        private readonly Connection $connection
    ) {
    }

    /**
     * Récupère les activités récentes d'un utilisateur.
     */
    public function findRecentActivities(int $userId, array $sessionCartItems = []): array
    {
        $activities = [];

        // 1. Billets confirmés récents (derniers 30 jours)
        // Relation: orders -> order_items -> tickets -> ticket_types -> events
        $recentTickets = $this->connection->executeQuery(
            'SELECT 
                o.id AS order_id,
                o.id AS order_number,
                o.created_at,
                e.id AS event_id,
                e.title AS event_title,
                COUNT(DISTINCT t.id) AS ticket_count
             FROM aiolia.orders o
             INNER JOIN aiolia.order_items oi ON oi.order_id = o.id
             INNER JOIN aiolia.tickets t ON t.order_item_id = oi.id
             INNER JOIN aiolia.ticket_types tt ON tt.id = t.ticket_type_id
             INNER JOIN aiolia.events e ON e.id = tt.event_id
             WHERE o.user_id = :userId
               AND o.status = \'paid\'
               AND o.created_at >= NOW() - INTERVAL \'30 days\'
             GROUP BY o.id, o.created_at, e.id, e.title
             ORDER BY o.created_at DESC
             LIMIT 5',
            ['userId' => $userId]
        )->fetchAllAssociative();

        foreach ($recentTickets as $ticket) {
            $createdAt = new \DateTimeImmutable($ticket['created_at']);
            $orderNumber = 'CMD-' . str_pad((string) $ticket['order_id'], 6, '0', STR_PAD_LEFT);
            $activities[] = [
                'type' => 'ticket',
                'icon' => 'fas fa-ticket-alt',
                'title' => $ticket['ticket_count'] . ' billet(s) confirmé(s) pour <strong>' . $ticket['event_title'] . '</strong>',
                'meta' => $createdAt->format('d M Y') . ' · Paiement réussi · Ref. #' . $orderNumber,
                'date' => $createdAt,
                'event_id' => (int) $ticket['event_id'],
            ];
        }

        // 2. Favoris récents (derniers 30 jours)
        $wishlistId = $this->connection->executeQuery(
            'SELECT id FROM aiolia.wishlists WHERE user_id = :userId AND is_default = TRUE LIMIT 1',
            ['userId' => $userId]
        )->fetchOne();
        
        if ($wishlistId) {
            $recentFavorites = $this->connection->executeQuery(
                'SELECT 
                    wi.added_at,
                    e.id AS event_id,
                    e.title AS event_title
                 FROM aiolia.wishlist_items wi
                 INNER JOIN aiolia.events e ON e.id = wi.event_id
                 WHERE wi.wishlist_id = :wishlistId
                   AND wi.added_at >= NOW() - INTERVAL \'30 days\'
                 ORDER BY wi.added_at DESC
                 LIMIT 5',
                ['wishlistId' => $wishlistId]
            )->fetchAllAssociative();

            foreach ($recentFavorites as $favorite) {
                $addedAt = new \DateTimeImmutable($favorite['added_at']);
                $activities[] = [
                    'type' => 'favorite',
                    'icon' => 'fas fa-heart',
                    'title' => 'Nouvel événement favori : <strong>' . $favorite['event_title'] . '</strong>',
                    'meta' => $addedAt->format('d M Y') . ' · Favoris',
                    'date' => $addedAt,
                    'event_id' => (int) $favorite['event_id'],
                ];
            }
        }

        // 3. Panier en attente (DB)
        $pendingCart = $this->connection->executeQuery(
            'SELECT 
                c.id,
                c.created_at,
                e.id AS event_id,
                e.title AS event_title,
                e.starts_at
             FROM aiolia.carts c
             INNER JOIN aiolia.cart_items ci ON ci.cart_id = c.id
             INNER JOIN aiolia.events e ON e.id = ci.event_id
             WHERE c.user_id = :userId
               AND c.status = \'active\'
             ORDER BY c.created_at DESC
             LIMIT 1',
            ['userId' => $userId]
        )->fetchAssociative();

        if ($pendingCart) {
            $createdAt = new \DateTimeImmutable($pendingCart['created_at']);
            $startsAt = new \DateTimeImmutable($pendingCart['starts_at']);
            $hoursRemaining = (int) (($startsAt->getTimestamp() - time()) / 3600);
            
            $activities[] = [
                'type' => 'cart',
                'icon' => 'fas fa-clock',
                'title' => 'Panier en attente pour <strong>' . $pendingCart['event_title'] . '</strong>',
                'meta' => $createdAt->format('d M Y') . ' · Expire dans ' . max(0, $hoursRemaining) . ' heure(s)',
                'date' => $createdAt,
                'event_id' => (int) $pendingCart['event_id'],
            ];
        }

        // 4. Panier en session (si pas déjà dans DB)
        if (!empty($sessionCartItems) && !$pendingCart) {
            // Récupérer les événements du panier en session
            $eventIds = array_unique(array_map(fn($item) => $item['eventId'] ?? null, $sessionCartItems));
            $eventIds = array_filter($eventIds);
            
            if (!empty($eventIds)) {
                $placeholders = implode(',', array_fill(0, count($eventIds), '?'));
                $sessionEvents = $this->connection->executeQuery(
                    "SELECT id, title, starts_at FROM aiolia.events WHERE id IN ($placeholders) ORDER BY starts_at ASC LIMIT 1",
                    $eventIds
                )->fetchAssociative();
                
                if ($sessionEvents) {
                    // Trouver la date d'ajout la plus ancienne du panier en session
                    $oldestAddedAt = null;
                    foreach ($sessionCartItems as $item) {
                        if (isset($item['addedAt'])) {
                            $addedAt = new \DateTimeImmutable($item['addedAt']);
                            if ($oldestAddedAt === null || $addedAt < $oldestAddedAt) {
                                $oldestAddedAt = $addedAt;
                            }
                        }
                    }
                    
                    if ($oldestAddedAt === null) {
                        $oldestAddedAt = new \DateTimeImmutable();
                    }
                    
                    $startsAt = new \DateTimeImmutable($sessionEvents['starts_at']);
                    $hoursRemaining = (int) (($startsAt->getTimestamp() - time()) / 3600);
                    
                    $activities[] = [
                        'type' => 'cart',
                        'icon' => 'fas fa-clock',
                        'title' => 'Panier en attente pour <strong>' . $sessionEvents['title'] . '</strong>',
                        'meta' => $oldestAddedAt->format('d M Y') . ' · Expire dans ' . max(0, $hoursRemaining) . ' heure(s)',
                        'date' => $oldestAddedAt,
                        'event_id' => (int) $sessionEvents['id'],
                    ];
                }
            }
        }

        // Trier toutes les activités par date (plus récentes en premier)
        usort($activities, function($a, $b) {
            return $b['date'] <=> $a['date'];
        });

        // Limiter à 10 activités
        return array_slice($activities, 0, 10);
    }
}

