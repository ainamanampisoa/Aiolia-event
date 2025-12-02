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

        // 3. Ajouts récents au panier (DB) - chaque événement ajouté récemment
        $recentCartAdditions = [];
        try {
            $cartAdditionsRaw = $this->connection->executeQuery(
                'SELECT 
                    ci.created_at,
                    e.id AS event_id,
                    e.title AS event_title,
                    e.starts_at,
                    ci.quantity,
                    ci.adult_quantity,
                    ci.child_quantity
                 FROM aiolia.carts c
                 INNER JOIN aiolia.cart_items ci ON ci.cart_id = c.id
                 INNER JOIN aiolia.ticket_types tt ON tt.id = ci.ticket_type_id
                 INNER JOIN aiolia.events e ON e.id = tt.event_id
                 WHERE c.user_id = :userId
                   AND c.status = \'active\'
                   AND ci.created_at >= NOW() - INTERVAL \'7 days\'
                 ORDER BY ci.created_at DESC',
                ['userId' => $userId]
            )->fetchAllAssociative();

            // Grouper par event_id pour éviter les doublons
            $groupedAdditions = [];
            foreach ($cartAdditionsRaw as $addition) {
                $eventId = (int) $addition['event_id'];
                // Garder seulement le plus récent ajout pour chaque événement
                if (!isset($groupedAdditions[$eventId]) || 
                    strtotime($addition['created_at']) > strtotime($groupedAdditions[$eventId]['created_at'])) {
                    $groupedAdditions[$eventId] = $addition;
                }
            }

            foreach ($groupedAdditions as $addition) {
                $createdAt = new \DateTimeImmutable($addition['created_at']);
                $totalQty = ($addition['quantity'] ?? 0) + ($addition['adult_quantity'] ?? 0) + ($addition['child_quantity'] ?? 0);
                $qtyText = $totalQty > 0 ? ' (' . $totalQty . ' billet' . ($totalQty > 1 ? 's' : '') . ')' : '';
                
                $recentCartAdditions[] = [
                    'type' => 'cart_add',
                    'icon' => 'fas fa-shopping-cart',
                    'title' => 'Ajouté au panier : <strong>' . $addition['event_title'] . '</strong>' . $qtyText,
                    'meta' => $createdAt->format('d M Y à H:i') . ' · Panier',
                    'date' => $createdAt,
                    'event_id' => (int) $addition['event_id'],
                ];
            }
            $activities = array_merge($activities, $recentCartAdditions);
        } catch (\Exception $e) {
            // Ignorer les erreurs
        }

        // 3b. Panier en attente (DB) - résumé global
        $pendingCart = $this->connection->executeQuery(
            'SELECT 
                c.id,
                c.created_at,
                e.id AS event_id,
                e.title AS event_title,
                e.starts_at
             FROM aiolia.carts c
             INNER JOIN aiolia.cart_items ci ON ci.cart_id = c.id
             INNER JOIN aiolia.ticket_types tt ON tt.id = ci.ticket_type_id
             INNER JOIN aiolia.events e ON e.id = tt.event_id
             WHERE c.user_id = :userId
               AND c.status = \'active\'
             ORDER BY c.created_at DESC
             LIMIT 1',
            ['userId' => $userId]
        )->fetchAssociative();

        if ($pendingCart && empty($recentCartAdditions ?? [])) {
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

        // 4. Ajouts récents au panier en session
        if (!empty($sessionCartItems)) {
            $eventIds = array_unique(array_map(fn($item) => $item['eventId'] ?? null, $sessionCartItems));
            $eventIds = array_filter($eventIds);
            // Réindexer le tableau pour avoir des indices séquentiels (0, 1, 2...)
            $eventIds = array_values($eventIds);
            
            if (!empty($eventIds)) {
                $placeholders = implode(',', array_fill(0, count($eventIds), '?'));
                $sessionEvents = $this->connection->executeQuery(
                    "SELECT id, title, starts_at FROM aiolia.events WHERE id IN ($placeholders)",
                    $eventIds
                )->fetchAllAssociative();
                
                // Créer un index par event_id pour accès rapide
                $eventsById = [];
                foreach ($sessionEvents as $event) {
                    $eventsById[(int) $event['id']] = $event;
                }
                
                // Parcourir les items du panier en session pour créer des activités d'ajout
                foreach ($sessionCartItems as $cartKey => $item) {
                    $eventId = $item['eventId'] ?? null;
                    if (!$eventId || !isset($eventsById[$eventId])) {
                        continue;
                    }
                    
                    $event = $eventsById[$eventId];
                    $addedAt = isset($item['addedAt']) 
                        ? new \DateTimeImmutable($item['addedAt'])
                        : new \DateTimeImmutable();
                    
                    // Vérifier si c'est un ajout récent (derniers 7 jours)
                    $daysDiff = (time() - $addedAt->getTimestamp()) / 86400;
                    if ($daysDiff <= 7) {
                        $adultQty = (int) ($item['adultQuantity'] ?? 0);
                        $childQty = (int) ($item['childQuantity'] ?? 0);
                        $totalQty = $adultQty + $childQty;
                        $qtyText = $totalQty > 0 ? ' (' . $totalQty . ' billet' . ($totalQty > 1 ? 's' : '') . ')' : '';
                        
                        $activities[] = [
                            'type' => 'cart_add',
                            'icon' => 'fas fa-shopping-cart',
                            'title' => 'Ajouté au panier : <strong>' . $event['title'] . '</strong>' . $qtyText,
                            'meta' => $addedAt->format('d M Y à H:i') . ' · Panier',
                            'date' => $addedAt,
                            'event_id' => (int) $eventId,
                        ];
                    }
                }
            }
        }
        
        // 4b. Panier en session (résumé si pas d'ajouts récents déjà trackés et pas de panier DB)
        // Vérifier si on a déjà des activités cart_add dans le panier
        $hasCartAddActivities = false;
        foreach ($activities as $activity) {
            if (isset($activity['type']) && $activity['type'] === 'cart_add') {
                $hasCartAddActivities = true;
                break;
            }
        }
        
        if (!empty($sessionCartItems) && !$pendingCart && !$hasCartAddActivities) {
            $eventIds = array_unique(array_map(fn($item) => $item['eventId'] ?? null, $sessionCartItems));
            $eventIds = array_filter($eventIds);
            // Réindexer le tableau pour avoir des indices séquentiels
            $eventIds = array_values($eventIds);
            
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

        // 5. Transactions wallet récentes (recharge, transfert)
        try {
            $walletId = $this->connection->executeQuery(
                'SELECT id FROM aiolia.wallets WHERE user_id = :userId LIMIT 1',
                ['userId' => $userId]
            )->fetchOne();
            
            if ($walletId) {
                $recentTransactions = $this->connection->executeQuery(
                    'SELECT 
                        wt.id,
                        wt.transaction_type,
                        wt.status,
                        wt.amount,
                        wt.points_delta,
                        wt.description,
                        wt.created_at
                     FROM aiolia.wallet_transactions wt
                     WHERE wt.wallet_id = :walletId
                       AND wt.created_at >= NOW() - INTERVAL \'30 days\'
                       AND wt.transaction_type IN (\'credit\', \'debit\', \'transfer_in\', \'transfer_out\')
                     ORDER BY wt.created_at DESC
                     LIMIT 5',
                    ['walletId' => $walletId]
                )->fetchAllAssociative();

                foreach ($recentTransactions as $transaction) {
                    $createdAt = new \DateTimeImmutable($transaction['created_at']);
                    $amount = (float) $transaction['amount'];
                    $type = $transaction['transaction_type'];
                    $status = $transaction['status'];
                    
                    $typeLabels = [
                        'credit' => 'Rechargement',
                        'debit' => 'Débit',
                        'transfer_in' => 'Reçu',
                        'transfer_out' => 'Envoyé',
                    ];
                    
                    $typeLabel = $typeLabels[$type] ?? ucfirst($type);
                    $statusLabel = $status === 'completed' ? 'Confirmé' : 'En attente';
                    
                    $activities[] = [
                        'type' => 'wallet',
                        'icon' => 'fas fa-wallet',
                        'title' => $typeLabel . ' wallet : <strong>' . number_format($amount, 0, ',', ' ') . ' MGA</strong>',
                        'meta' => $createdAt->format('d M Y') . ' · ' . $statusLabel . ($transaction['description'] ? ' · ' . $transaction['description'] : ''),
                        'date' => $createdAt,
                    ];
                }
            }
        } catch (\Exception $e) {
            // Ignorer les erreurs de wallet
        }

        // 6. Recherches récentes
        try {
            $recentSearches = $this->connection->executeQuery(
                'SELECT 
                    ush.id,
                    ush.keywords,
                    ush.filters,
                    ush.searched_at
                 FROM aiolia.user_search_history ush
                 WHERE ush.user_id = :userId
                   AND ush.searched_at >= NOW() - INTERVAL \'7 days\'
                 ORDER BY ush.searched_at DESC
                 LIMIT 5',
                ['userId' => $userId]
            )->fetchAllAssociative();

            foreach ($recentSearches as $search) {
                $searchedAt = new \DateTimeImmutable($search['searched_at']);
                $keywords = $search['keywords'] ?? '';
                $filters = is_string($search['filters']) 
                    ? json_decode($search['filters'], true) 
                    : ($search['filters'] ?? []);
                
                $searchText = !empty($keywords) ? $keywords : 'Recherche';
                if (!empty($filters) && is_array($filters)) {
                    $filtersParts = [];
                    if (!empty($filters['category'])) $filtersParts[] = $filters['category'];
                    if (!empty($filters['city'])) $filtersParts[] = $filters['city'];
                    if (!empty($filtersParts)) {
                        $searchText .= ' (' . implode(', ', $filtersParts) . ')';
                    }
                }
                
                $activities[] = [
                    'type' => 'search',
                    'icon' => 'fas fa-search',
                    'title' => 'Recherche : <strong>' . htmlspecialchars($searchText) . '</strong>',
                    'meta' => $searchedAt->format('d M Y') . ' · Recherche d\'événements',
                    'date' => $searchedAt,
                ];
            }
        } catch (\Exception $e) {
            // Ignorer les erreurs de recherche
        }

        // 7. Activités de profil (basées sur audit_logs si disponible)
        // Note: Les modifications de profil seront trackées via audit_logs ou une table dédiée
        // Pour l'instant, on les récupère depuis les logs d'audit
        try {
            $profileActivities = $this->connection->executeQuery(
                'SELECT 
                    al.created_at,
                    al.action,
                    al.changes
                 FROM aiolia.audit_logs al
                 WHERE al.actor_user_id = :userId
                   AND al.scope = \'profile\'
                   AND al.action IN (\'profile_updated\', \'avatar_uploaded\', \'settings_updated\')
                   AND al.created_at >= NOW() - INTERVAL \'30 days\'
                 ORDER BY al.created_at DESC
                 LIMIT 3',
                ['userId' => $userId]
            )->fetchAllAssociative();

            foreach ($profileActivities as $activity) {
                $createdAt = new \DateTimeImmutable($activity['created_at']);
                $action = $activity['action'];
                $changes = is_string($activity['changes']) 
                    ? json_decode($activity['changes'], true) 
                    : ($activity['changes'] ?? []);
                
                $titles = [
                    'profile_updated' => 'Profil modifié',
                    'avatar_uploaded' => 'Photo de profil mise à jour',
                    'settings_updated' => 'Paramètres modifiés',
                ];
                
                $icons = [
                    'profile_updated' => 'fas fa-user-edit',
                    'avatar_uploaded' => 'fas fa-user-circle',
                    'settings_updated' => 'fas fa-cog',
                ];
                
                $activities[] = [
                    'type' => 'profile',
                    'icon' => $icons[$action] ?? 'fas fa-user',
                    'title' => $titles[$action] ?? 'Action sur le profil',
                    'meta' => $createdAt->format('d M Y') . ' · Paramètres du compte',
                    'date' => $createdAt,
                ];
            }
        } catch (\Exception $e) {
            // Si la table audit_logs n'existe pas ou n'a pas les bonnes colonnes, ignorer
        }

        // 8. Suppressions de panier (via audit_logs)
        try {
            $cartRemovals = $this->connection->executeQuery(
                'SELECT 
                    al.created_at,
                    al.entity_id,
                    e.title AS event_title
                 FROM aiolia.audit_logs al
                 LEFT JOIN aiolia.events e ON e.id = al.entity_id
                 WHERE al.actor_user_id = :userId
                   AND al.scope = \'cart\'
                   AND al.action = \'cart_item_removed\'
                   AND al.created_at >= NOW() - INTERVAL \'7 days\'
                 ORDER BY al.created_at DESC
                 LIMIT 5',
                ['userId' => $userId]
            )->fetchAllAssociative();

            foreach ($cartRemovals as $removal) {
                if (!empty($removal['event_title'])) {
                    $createdAt = new \DateTimeImmutable($removal['created_at']);
                    $activities[] = [
                        'type' => 'cart_remove',
                        'icon' => 'fas fa-trash-alt',
                        'title' => 'Retiré du panier : <strong>' . $removal['event_title'] . '</strong>',
                        'meta' => $createdAt->format('d M Y à H:i') . ' · Panier',
                        'date' => $createdAt,
                        'event_id' => (int) $removal['entity_id'],
                    ];
                }
            }
        } catch (\Exception $e) {
            // Ignorer les erreurs
        }

        // 9. Suppressions de favoris (via audit_logs)
        try {
            $favoriteRemovals = $this->connection->executeQuery(
                'SELECT 
                    al.created_at,
                    al.entity_id,
                    e.title AS event_title
                 FROM aiolia.audit_logs al
                 LEFT JOIN aiolia.events e ON e.id = al.entity_id
                 WHERE al.actor_user_id = :userId
                   AND al.scope = \'favorites\'
                   AND al.action = \'favorite_removed\'
                   AND al.created_at >= NOW() - INTERVAL \'7 days\'
                 ORDER BY al.created_at DESC
                 LIMIT 5',
                ['userId' => $userId]
            )->fetchAllAssociative();

            foreach ($favoriteRemovals as $removal) {
                if (!empty($removal['event_title'])) {
                    $createdAt = new \DateTimeImmutable($removal['created_at']);
                    $activities[] = [
                        'type' => 'favorite_remove',
                        'icon' => 'fas fa-heart-broken',
                        'title' => 'Retiré des favoris : <strong>' . $removal['event_title'] . '</strong>',
                        'meta' => $createdAt->format('d M Y à H:i') . ' · Favoris',
                        'date' => $createdAt,
                        'event_id' => (int) $removal['entity_id'],
                    ];
                }
            }
        } catch (\Exception $e) {
            // Ignorer les erreurs
        }

        // Trier toutes les activités par date (plus récentes en premier)
        usort($activities, function($a, $b) {
            return $b['date'] <=> $a['date'];
        });

        // Limiter à 50 activités pour la page complète (sera limité à 5 dans le template)
        return $activities;
    }
}

