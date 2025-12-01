<?php

namespace App\Repository;

use Doctrine\DBAL\Connection;

class UserStatsRepository
{
    public function __construct(
        private readonly Connection $connection
    ) {
    }

    /**
     * Récupère les statistiques personnelles de l'utilisateur.
     */
    public function findUserStatistics(int $userId, ?\DateTimeImmutable $dateFrom = null): ?array
    {
        $sql = <<<SQL
            SELECT 
                COUNT(DISTINCT t.id) as total_tickets,
                COUNT(DISTINCT o.id) as total_orders,
                COUNT(DISTINCT e.id) as unique_events,
                SUM(CASE WHEN o.status = 'paid' THEN o.total_amount ELSE 0 END) as total_spent,
                AVG(CASE WHEN o.status = 'paid' THEN o.total_amount ELSE NULL END) as avg_cart
            FROM aiolia.orders o
            LEFT JOIN aiolia.order_items oi ON oi.order_id = o.id
            LEFT JOIN aiolia.tickets t ON t.order_item_id = oi.id
            LEFT JOIN aiolia.ticket_types tt ON tt.id = oi.ticket_type_id
            LEFT JOIN aiolia.events e ON e.id = tt.event_id
            WHERE o.user_id = :user_id
        SQL;
        
        $params = ['user_id' => $userId];
        
        if ($dateFrom !== null) {
            $sql .= ' AND o.created_at >= :date_from';
            $params['date_from'] = $dateFrom->format('Y-m-d H:i:s');
        }

        $row = $this->connection->executeQuery($sql, $params)->fetchAssociative();

        if (!$row) {
            return null;
        }

        $totalSpent = (float) ($row['total_spent'] ?? 0);
        $avgCart = (float) ($row['avg_cart'] ?? 0);

        return [
            'total_tickets' => (int) ($row['total_tickets'] ?? 0),
            'total_spent' => number_format($totalSpent, 0, ',', ' ') . ' MGA',
            'total_spent_raw' => $totalSpent,
            'unique_events' => (int) ($row['unique_events'] ?? 0),
            'avg_cart' => number_format($avgCart, 0, ',', ' ') . ' MGA',
            'total_orders' => (int) ($row['total_orders'] ?? 0),
        ];
    }

    /**
     * Récupère la répartition par type d'événement.
     */
    public function findEventTypeDistribution(int $userId, ?\DateTimeImmutable $dateFrom = null): array
    {
        $sql = <<<SQL
            SELECT 
                COALESCE(ec.label, 'Autres') as category,
                COUNT(DISTINCT o.id) as order_count,
                SUM(o.total_amount) as total_amount
            FROM aiolia.orders o
            JOIN aiolia.order_items oi ON oi.order_id = o.id
            JOIN aiolia.ticket_types tt ON tt.id = oi.ticket_type_id
            JOIN aiolia.events e ON e.id = tt.event_id
            LEFT JOIN aiolia.event_categories ec ON ec.id = e.primary_category_id
            WHERE o.user_id = :user_id 
              AND o.status = 'paid'
        SQL;
        
        $params = ['user_id' => $userId];
        
        if ($dateFrom !== null) {
            $sql .= ' AND o.created_at >= :date_from';
            $params['date_from'] = $dateFrom->format('Y-m-d H:i:s');
        }
        
        $sql .= <<<SQL
            GROUP BY ec.label
            ORDER BY total_amount DESC
        SQL;

        $rows = $this->connection->executeQuery($sql, $params)->fetchAllAssociative();

        $total = array_sum(array_column($rows, 'total_amount'));

        return array_map(function (array $row) use ($total): array {
            $percentage = $total > 0 ? round((float) $row['total_amount'] / $total * 100) : 0;
            return [
                'category' => $row['category'],
                'percentage' => $percentage,
                'order_count' => (int) $row['order_count'],
            ];
        }, $rows);
    }

    /**
     * Récupère le Top N des événements achetés par l'utilisateur.
     */
    public function findTopPurchasedEvents(int $userId, int $limit = 5, ?\DateTimeImmutable $dateFrom = null): array
    {
        $limit = max(1, min(100, (int) $limit));
        
        $sql = <<<SQL
            SELECT 
                e.id,
                e.title,
                e.slug,
                COALESCE(ec.label, 'Autres') as category,
                COUNT(DISTINCT o.id) as purchase_count,
                SUM(oi.quantity) as total_tickets,
                SUM(o.total_amount) as total_spent,
                MIN(o.created_at) as first_purchase,
                MAX(o.created_at) as last_purchase
            FROM aiolia.orders o
            JOIN aiolia.order_items oi ON oi.order_id = o.id
            JOIN aiolia.ticket_types tt ON tt.id = oi.ticket_type_id
            JOIN aiolia.events e ON e.id = tt.event_id
            LEFT JOIN aiolia.event_categories ec ON ec.id = e.primary_category_id
            WHERE o.user_id = :user_id 
              AND o.status = 'paid'
        SQL;
        
        $params = ['user_id' => $userId];
        
        if ($dateFrom !== null) {
            $sql .= ' AND o.created_at >= :date_from';
            $params['date_from'] = $dateFrom->format('Y-m-d H:i:s');
        }
        
        $sql .= <<<SQL
            GROUP BY e.id, e.title, e.slug, ec.label
            ORDER BY total_spent DESC, purchase_count DESC
            LIMIT {$limit}
        SQL;

        $rows = $this->connection->executeQuery($sql, $params)->fetchAllAssociative();

        return array_map(function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'title' => $row['title'],
                'slug' => $row['slug'],
                'category' => $row['category'],
                'purchase_count' => (int) $row['purchase_count'],
                'total_tickets' => (int) $row['total_tickets'],
                'total_spent' => number_format((float) $row['total_spent'], 0, ',', ' ') . ' MGA',
                'total_spent_raw' => (float) $row['total_spent'],
                'first_purchase' => isset($row['first_purchase']) ? new \DateTimeImmutable($row['first_purchase']) : null,
                'last_purchase' => isset($row['last_purchase']) ? new \DateTimeImmutable($row['last_purchase']) : null,
            ];
        }, $rows);
    }

    /**
     * Récupère le mois le plus actif de l'utilisateur.
     */
    public function findMostActiveMonth(int $userId, ?\DateTimeImmutable $dateFrom = null): ?array
    {
        $sql = <<<SQL
            SELECT 
                TO_CHAR(o.created_at, 'Month YYYY') as month_name,
                COUNT(*) as order_count,
                SUM(o.total_amount) as total_amount
            FROM aiolia.orders o
            WHERE o.user_id = :user_id 
              AND o.status = 'paid'
        SQL;
        
        $params = ['user_id' => $userId];
        
        if ($dateFrom !== null) {
            $sql .= ' AND o.created_at >= :date_from';
            $params['date_from'] = $dateFrom->format('Y-m-d H:i:s');
        }
        
        $sql .= <<<SQL
            GROUP BY TO_CHAR(o.created_at, 'Month YYYY')
            ORDER BY order_count DESC, total_amount DESC
            LIMIT 1
        SQL;
        
        $row = $this->connection->executeQuery($sql, $params)->fetchAssociative();
        
        if ($row) {
            return [
                'month' => trim($row['month_name']),
                'count' => (int) $row['order_count'],
                'total' => number_format((float) $row['total_amount'], 0, ',', ' ') . ' MGA'
            ];
        }
        
        return null;
    }

    /**
     * Calcule le total économisé avec les codes promo.
     */
    public function calculateTotalSavedWithPromos(int $userId, ?\DateTimeImmutable $dateFrom = null): float
    {
        $sql = <<<SQL
            SELECT SUM(COALESCE(o.discount_amount, 0)) as total_saved
            FROM aiolia.orders o
            WHERE o.user_id = :user_id 
              AND o.status = 'paid'
              AND o.discount_amount > 0
        SQL;
        
        $params = ['user_id' => $userId];
        
        if ($dateFrom !== null) {
            $sql .= ' AND o.created_at >= :date_from';
            $params['date_from'] = $dateFrom->format('Y-m-d H:i:s');
        }
        
        $result = $this->connection->executeQuery($sql, $params)->fetchAssociative();
        
        return (float) ($result['total_saved'] ?? 0);
    }

    /**
     * Compte le nombre de types d'événements différents.
     */
    public function countEventTypes(int $userId, ?\DateTimeImmutable $dateFrom = null): int
    {
        $sql = <<<SQL
            SELECT COUNT(DISTINCT COALESCE(ec.label, 'Autres')) as types_count
            FROM aiolia.orders o
            JOIN aiolia.order_items oi ON oi.order_id = o.id
            JOIN aiolia.ticket_types tt ON tt.id = oi.ticket_type_id
            JOIN aiolia.events e ON e.id = tt.event_id
            LEFT JOIN aiolia.event_categories ec ON ec.id = e.primary_category_id
            WHERE o.user_id = :user_id 
              AND o.status = 'paid'
        SQL;
        
        $params = ['user_id' => $userId];
        
        if ($dateFrom !== null) {
            $sql .= ' AND o.created_at >= :date_from';
            $params['date_from'] = $dateFrom->format('Y-m-d H:i:s');
        }
        
        $result = $this->connection->executeQuery($sql, $params)->fetchAssociative();
        
        return (int) ($result['types_count'] ?? 0);
    }

    /**
     * Récupère la catégorie préférée de l'utilisateur.
     */
    public function findFavoriteCategory(int $userId, ?\DateTimeImmutable $dateFrom = null): ?array
    {
        $sql = <<<SQL
            SELECT 
                COALESCE(ec.label, 'Autres') as category,
                SUM(oi.quantity) as ticket_count
            FROM aiolia.orders o
            JOIN aiolia.order_items oi ON oi.order_id = o.id
            JOIN aiolia.ticket_types tt ON tt.id = oi.ticket_type_id
            JOIN aiolia.events e ON e.id = tt.event_id
            LEFT JOIN aiolia.event_categories ec ON ec.id = e.primary_category_id
            WHERE o.user_id = :user_id 
              AND o.status = 'paid'
        SQL;
        
        $params = ['user_id' => $userId];
        
        if ($dateFrom !== null) {
            $sql .= ' AND o.created_at >= :date_from';
            $params['date_from'] = $dateFrom->format('Y-m-d H:i:s');
        }
        
        $sql .= <<<SQL
            GROUP BY ec.label
            ORDER BY ticket_count DESC
            LIMIT 1
        SQL;
        
        $row = $this->connection->executeQuery($sql, $params)->fetchAssociative();
        
        if ($row) {
            return [
                'category' => $row['category'],
                'count' => (int) $row['ticket_count']
            ];
        }
        
        return null;
    }

    /**
     * Récupère les catégories recommandées basées sur l'historique.
     */
    public function findRecommendedCategories(int $userId): array
    {
        $sql = <<<SQL
            SELECT DISTINCT
                COALESCE(ec.label, 'Autres') as category,
                ec.slug as category_slug
            FROM aiolia.event_categories ec
            WHERE ec.label IN (
                SELECT DISTINCT COALESCE(ec2.label, 'Autres')
                FROM aiolia.orders o
                JOIN aiolia.order_items oi ON oi.order_id = o.id
                JOIN aiolia.ticket_types tt ON tt.id = oi.ticket_type_id
                JOIN aiolia.events e ON e.id = tt.event_id
                LEFT JOIN aiolia.event_categories ec2 ON ec2.id = e.primary_category_id
                WHERE o.user_id = :user_id 
                  AND o.status = 'paid'
            )
            LIMIT 5
        SQL;
        
        $rows = $this->connection->executeQuery($sql, ['user_id' => $userId])->fetchAllAssociative();
        
        return array_map(fn($row) => [
            'category' => $row['category'],
            'slug' => $row['category_slug'] ?? null
        ], $rows);
    }

    /**
     * Récupère les statistiques de l'utilisateur pour le dashboard.
     */
    public function findUserStats(int $userId, array $sessionCartItems = []): array
    {
        // Compter les billets actifs (tickets valides pour des événements futurs)
        // Relation: tickets -> ticket_types -> events -> starts_at
        // Utiliser owner_user_id pour vérifier la propriété du ticket
        try {
            $activeTicketsResult = $this->connection->executeQuery(
                'SELECT COUNT(DISTINCT t.id) as count
                 FROM aiolia.tickets t
                 INNER JOIN aiolia.ticket_types tt ON tt.id = t.ticket_type_id
                 INNER JOIN aiolia.events e ON e.id = tt.event_id
                 WHERE t.owner_user_id = :userId
                   AND t.status = \'valid\'
                   AND e.starts_at > NOW()',
                ['userId' => $userId]
            )->fetchOne();
            $activeTickets = (int) ($activeTicketsResult ?? 0);
        } catch (\Exception $e) {
            $activeTickets = 0;
        }

        // Compter les événements favoris
        try {
            $wishlistResult = $this->connection->executeQuery(
                'SELECT id FROM aiolia.wishlists WHERE user_id = :userId AND is_default = TRUE LIMIT 1',
                ['userId' => $userId]
            )->fetchAssociative();
            
            $favoriteEvents = 0;
            if ($wishlistResult && isset($wishlistResult['id'])) {
                $wishlistId = (int) $wishlistResult['id'];
                $favoriteResult = $this->connection->executeQuery(
                    'SELECT COUNT(*) as count FROM aiolia.wishlist_items WHERE wishlist_id = :wishlistId',
                    ['wishlistId' => $wishlistId]
                )->fetchOne();
                $favoriteEvents = (int) ($favoriteResult ?? 0);
            }
        } catch (\Exception $e) {
            $favoriteEvents = 0;
        }

        // Compter les items dans le panier actif (DB) - somme des quantités
        try {
            $dbCartResult = $this->connection->executeQuery(
                'SELECT COALESCE(SUM(ci.quantity + COALESCE(ci.adult_quantity, 0) + COALESCE(ci.child_quantity, 0)), 0) as total
                 FROM aiolia.cart_items ci
                 INNER JOIN aiolia.carts c ON c.id = ci.cart_id
                 WHERE c.user_id = :userId
                   AND c.status = \'active\'',
                ['userId' => $userId]
            )->fetchOne();
            $dbCartItems = (int) ($dbCartResult ?? 0);
        } catch (\Exception $e) {
            $dbCartItems = 0;
        }
        
        // Compter les items dans le panier en session (somme des quantités)
        // Les items sont stockés comme un tableau associatif: ['cart_key' => ['eventId' => ..., 'adultQuantity' => ..., etc.]]
        $sessionCartCount = 0;
        if (is_array($sessionCartItems) && !empty($sessionCartItems)) {
            foreach ($sessionCartItems as $cartKey => $item) {
                if (is_array($item)) {
                    // Compter les quantités adultes et enfants
                    $adultQty = (int) ($item['adultQuantity'] ?? 0);
                    $childQty = (int) ($item['childQuantity'] ?? 0);
                    
                    // Si on a des quantités adultes/enfants, les utiliser
                    if ($adultQty > 0 || $childQty > 0) {
                        $sessionCartCount += $adultQty + $childQty;
                    } else {
                        // Sinon, utiliser quantity (ou 1 par défaut si c'est un item simple)
                        $qty = (int) ($item['quantity'] ?? 1);
                        $sessionCartCount += $qty;
                    }
                }
            }
        }
        
        // Debug temporaire
        error_log('UserStatsRepository - DB cart items: ' . $dbCartItems);
        error_log('UserStatsRepository - Session cart count: ' . $sessionCartCount);
        error_log('UserStatsRepository - Session cart items structure: ' . json_encode($sessionCartItems));
        
        // Prendre le maximum entre DB et session
        $cartCount = max($dbCartItems, $sessionCartCount);

        // Récupérer les points fidélité (créer le wallet s'il n'existe pas)
        try {
            $pointsResult = $this->connection->executeQuery(
                'SELECT points_balance FROM aiolia.wallets WHERE user_id = :userId LIMIT 1',
                ['userId' => $userId]
            )->fetchAssociative();
            
            $points = 0;
            if ($pointsResult && isset($pointsResult['points_balance'])) {
                $points = (int) $pointsResult['points_balance'];
            } else {
                // Si le wallet n'existe pas, créer un wallet avec 0 points
                try {
                    $this->connection->insert('aiolia.wallets', [
                        'user_id' => $userId,
                        'currency' => 'MGA',
                        'balance' => 0,
                        'points_balance' => 0,
                    ]);
                } catch (\Exception $e) {
                    // Le wallet existe peut-être déjà, ignorer l'erreur
                }
            }
        } catch (\Exception $e) {
            $points = 0;
        }

        // S'assurer que toutes les valeurs sont des entiers
        return [
            'active_tickets' => (int) $activeTickets,
            'favorite_events' => (int) $favoriteEvents,
            'cart_items' => (int) $cartCount,
            'loyalty_points' => (int) $points,
        ];
    }

    /**
     * Compte le nombre d'événements à venir pour lesquels l'utilisateur a des billets payés.
     */
    public function countUpcomingEvents(int $userId): int
    {
        $sql = <<<SQL
            SELECT COUNT(DISTINCT e.id) as upcoming_count
            FROM aiolia.orders o
            INNER JOIN aiolia.order_items oi ON oi.order_id = o.id
            INNER JOIN aiolia.ticket_types tt ON tt.id = oi.ticket_type_id
            INNER JOIN aiolia.events e ON e.id = tt.event_id
            WHERE o.user_id = :user_id
            AND o.status = 'paid'
            AND e.starts_at > NOW()
        SQL;

        $result = $this->connection->executeQuery($sql, ['user_id' => $userId])->fetchAssociative();
        
        return (int) ($result['upcoming_count'] ?? 0);
    }

    /**
     * Récupère les statistiques personnelles de l'utilisateur pour la page stats.
     */
    public function findUserStatisticsForStats(int $userId): array
    {
        $sql = <<<SQL
            SELECT 
                COUNT(DISTINCT t.id) as total_tickets,
                COUNT(DISTINCT o.id) as total_orders,
                COUNT(DISTINCT e.id) as unique_events,
                SUM(CASE WHEN o.status = 'paid' THEN o.total_amount ELSE 0 END) as total_spent,
                AVG(CASE WHEN o.status = 'paid' THEN o.total_amount ELSE NULL END) as avg_cart
            FROM aiolia.orders o
            LEFT JOIN aiolia.order_items oi ON oi.order_id = o.id
            LEFT JOIN aiolia.tickets t ON t.order_item_id = oi.id
            LEFT JOIN aiolia.ticket_types tt ON tt.id = oi.ticket_type_id
            LEFT JOIN aiolia.events e ON e.id = tt.event_id
            WHERE o.user_id = :user_id
        SQL;

        $row = $this->connection->executeQuery($sql, ['user_id' => $userId])->fetchAssociative();

        if (!$row) {
            return [
                'total_tickets' => 0,
                'total_spent' => 0,
                'unique_events' => 0,
                'avg_cart' => 0,
            ];
        }

        $totalSpent = (float) ($row['total_spent'] ?? 0);
        $avgCart = (float) ($row['avg_cart'] ?? 0);

        return [
            'total_tickets' => (int) ($row['total_tickets'] ?? 0),
            'total_spent' => number_format($totalSpent, 0, ',', ' ') . ' MGA',
            'total_spent_raw' => $totalSpent,
            'unique_events' => (int) ($row['unique_events'] ?? 0),
            'avg_cart' => number_format($avgCart, 0, ',', ' ') . ' MGA',
            'total_orders' => (int) ($row['total_orders'] ?? 0),
        ];
    }

    /**
     * Récupère la répartition des méthodes de paiement.
     */
    public function findPaymentMethodDistribution(int $userId): array
    {
        $sql = <<<SQL
            SELECT 
                o.notes,
                o.total_amount,
                COUNT(*) as order_count
            FROM aiolia.orders o
            WHERE o.user_id = :user_id
              AND o.status = 'paid'
              AND o.notes IS NOT NULL
            GROUP BY o.notes, o.total_amount
        SQL;

        $rows = $this->connection->executeQuery($sql, ['user_id' => $userId])->fetchAllAssociative();

        $providerLabels = [
            'mvola' => 'M-Vola',
            'orange-money' => 'Orange Money',
            'orange' => 'Orange Money',
            'airtel-money' => 'Airtel Money',
            'airtel' => 'Airtel Money',
            'telma' => 'Telma',
            'bank_transfer' => 'Virement bancaire',
        ];

        $distribution = [];
        $totalCount = 0;

        foreach ($rows as $row) {
            $notes = json_decode($row['notes'], true);
            $paymentMethod = null;
            
            if (is_array($notes) && isset($notes['payment_method'])) {
                $paymentMethod = $notes['payment_method'];
            }
            
            if (!$paymentMethod) {
                $paymentMethod = 'other';
            }
            
            $label = $providerLabels[$paymentMethod] ?? 'Autres';
            $count = (int) $row['order_count'];
            
            if (!isset($distribution[$label])) {
                $distribution[$label] = [
                    'label' => $label,
                    'count' => 0,
                ];
            }
            
            $distribution[$label]['count'] += $count;
            $totalCount += $count;
        }

        // Calculer les pourcentages
        $result = [];
        foreach ($distribution as $label => $data) {
            $percentage = $totalCount > 0 ? round(($data['count'] / $totalCount) * 100) : 0;
            $result[] = [
                'label' => $label,
                'count' => $data['count'],
                'percentage' => $percentage,
            ];
        }

        // Trier par nombre de transactions décroissant
        usort($result, fn($a, $b) => $b['count'] <=> $a['count']);

        return [
            'methods' => $result,
            'total_count' => $totalCount,
        ];
    }
}

