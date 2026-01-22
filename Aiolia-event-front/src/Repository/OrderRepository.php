<?php

namespace App\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

class OrderRepository
{
    public function __construct(
        private readonly Connection $connection
    ) {
    }

    /**
     * Récupère les détails d'une commande pour un utilisateur.
     */
    public function findOrderByIdAndUserId(int $orderId, int $userId): ?array
    {
        $sql = <<<SQL
            SELECT 
                o.id,
                o.status,
                o.total_amount,
                o.discount_amount,
                o.currency,
                o.promotion_code,
                o.created_at,
                o.notes,
                COUNT(DISTINCT oi.id) as items_count,
                COALESCE(SUM(oi.quantity), 0) as total_tickets,
                STRING_AGG(DISTINCT e.title, ', ') as event_titles
            FROM aiolia.orders o
            LEFT JOIN aiolia.order_items oi ON oi.order_id = o.id
            LEFT JOIN aiolia.ticket_types tt ON tt.id = oi.ticket_type_id
            LEFT JOIN aiolia.events e ON e.id = tt.event_id
            WHERE o.id = :order_id AND o.user_id = :user_id
            GROUP BY o.id, o.status, o.total_amount, o.discount_amount, o.currency,
                     o.promotion_code, o.created_at, o.notes
        SQL;

        $result = $this->connection->executeQuery($sql, [
            'order_id' => $orderId,
            'user_id' => $userId,
        ])->fetchAssociative();

        return $result ?: null;
    }

    /**
     * Récupère les commandes d'un utilisateur avec filtres.
     */
    public function findUserOrders(int $userId, string $searchQuery = '', string $statusFilter = 'all', string $dateRangeFilter = 'all', string $categoryFilter = 'all', ?int $limit = null, ?int $offset = null): array
    {
        $sql = <<<SQL
            SELECT 
                o.id,
                o.status,
                o.total_amount,
                o.discount_amount,
                o.currency,
                o.promotion_code,
                o.created_at,
                o.updated_at,
                o.notes,
                COUNT(DISTINCT oi.id) as items_count,
                COALESCE(SUM(oi.quantity), 0) as total_tickets,
                COALESCE(STRING_AGG(DISTINCT e.title, ', '), '') as event_titles,
                COALESCE(STRING_AGG(DISTINCT e.starts_at::text, ', '), '') as event_dates
            FROM aiolia.orders o
            LEFT JOIN aiolia.order_items oi ON oi.order_id = o.id
            LEFT JOIN aiolia.ticket_types tt ON tt.id = oi.ticket_type_id
            LEFT JOIN aiolia.events e ON e.id = tt.event_id
            WHERE o.user_id = :user_id
        SQL;

        $params = ['user_id' => $userId];

        // Appliquer le filtre de statut
        if ($statusFilter !== 'all') {
            $sql .= ' AND o.status = :status';
            $params['status'] = $statusFilter;
        }

        // Appliquer le filtre de période
        if ($dateRangeFilter !== 'all') {
            $now = new \DateTimeImmutable();
            switch ($dateRangeFilter) {
                case 'today':
                    $startDate = $now->setTime(0, 0, 0);
                    $sql .= ' AND o.created_at >= :date_start';
                    $params['date_start'] = $startDate->format('Y-m-d H:i:s');
                    break;
                case 'week':
                    $startDate = $now->modify('-7 days')->setTime(0, 0, 0);
                    $sql .= ' AND o.created_at >= :date_start';
                    $params['date_start'] = $startDate->format('Y-m-d H:i:s');
                    break;
                case 'month':
                    $startDate = $now->modify('first day of this month')->setTime(0, 0, 0);
                    $sql .= ' AND o.created_at >= :date_start';
                    $params['date_start'] = $startDate->format('Y-m-d H:i:s');
                    break;
                case '3months':
                    $startDate = $now->modify('-3 months')->setTime(0, 0, 0);
                    $sql .= ' AND o.created_at >= :date_start';
                    $params['date_start'] = $startDate->format('Y-m-d H:i:s');
                    break;
                case 'year':
                    $startDate = $now->modify('first day of January')->setTime(0, 0, 0);
                    $sql .= ' AND o.created_at >= :date_start';
                    $params['date_start'] = $startDate->format('Y-m-d H:i:s');
                    break;
            }
        }

        // Appliquer le filtre de catégorie
        if ($categoryFilter !== 'all') {
            $sql .= " AND EXISTS (
                SELECT 1 FROM aiolia.order_items oi2
                INNER JOIN aiolia.ticket_types tt2 ON tt2.id = oi2.ticket_type_id
                INNER JOIN aiolia.events e2 ON e2.id = tt2.event_id
                INNER JOIN aiolia.event_categories ec ON ec.id = e2.primary_category_id
                WHERE oi2.order_id = o.id
                AND ec.slug = :category
            )";
            $params['category'] = $categoryFilter;
        }

        // Appliquer la recherche
        if (!empty($searchQuery)) {
            $sql .= ' AND (
                e.title ILIKE :search 
                OR CAST(o.id AS TEXT) ILIKE :search
                OR o.notes::text ILIKE :search
            )';
            $params['search'] = '%' . $searchQuery . '%';
        }

        $sql .= <<<SQL
            GROUP BY o.id, o.status, o.total_amount, o.discount_amount, o.currency, 
                     o.promotion_code, o.created_at, o.updated_at, o.notes
            ORDER BY o.created_at DESC
        SQL;

        // Ajouter la pagination si nécessaire
        if ($limit !== null) {
            $sql .= ' LIMIT :limit';
            $params['limit'] = $limit;
        }
        if ($offset !== null) {
            $sql .= ' OFFSET :offset';
            $params['offset'] = $offset;
        }

        return $this->connection->executeQuery($sql, $params)->fetchAllAssociative();
    }

    /**
     * Compte le nombre total de commandes d'un utilisateur avec filtres (pour la pagination).
     */
    public function countUserOrders(int $userId, string $searchQuery = '', string $statusFilter = 'all', string $dateRangeFilter = 'all', string $categoryFilter = 'all'): int
    {
        $sql = <<<SQL
            SELECT COUNT(DISTINCT o.id) as total
            FROM aiolia.orders o
            LEFT JOIN aiolia.order_items oi ON oi.order_id = o.id
            LEFT JOIN aiolia.ticket_types tt ON tt.id = oi.ticket_type_id
            LEFT JOIN aiolia.events e ON e.id = tt.event_id
            WHERE o.user_id = :user_id
        SQL;

        $params = ['user_id' => $userId];

        // Appliquer le filtre de statut
        if ($statusFilter !== 'all') {
            $sql .= ' AND o.status = :status';
            $params['status'] = $statusFilter;
        }

        // Appliquer le filtre de période
        if ($dateRangeFilter !== 'all') {
            $now = new \DateTimeImmutable();
            switch ($dateRangeFilter) {
                case 'today':
                    $startDate = $now->setTime(0, 0, 0);
                    $sql .= ' AND o.created_at >= :date_start';
                    $params['date_start'] = $startDate->format('Y-m-d H:i:s');
                    break;
                case 'week':
                    $startDate = $now->modify('-7 days')->setTime(0, 0, 0);
                    $sql .= ' AND o.created_at >= :date_start';
                    $params['date_start'] = $startDate->format('Y-m-d H:i:s');
                    break;
                case 'month':
                    $startDate = $now->modify('first day of this month')->setTime(0, 0, 0);
                    $sql .= ' AND o.created_at >= :date_start';
                    $params['date_start'] = $startDate->format('Y-m-d H:i:s');
                    break;
                case '3months':
                    $startDate = $now->modify('-3 months')->setTime(0, 0, 0);
                    $sql .= ' AND o.created_at >= :date_start';
                    $params['date_start'] = $startDate->format('Y-m-d H:i:s');
                    break;
                case 'year':
                    $startDate = $now->modify('first day of January')->setTime(0, 0, 0);
                    $sql .= ' AND o.created_at >= :date_start';
                    $params['date_start'] = $startDate->format('Y-m-d H:i:s');
                    break;
            }
        }

        // Appliquer le filtre de catégorie
        if ($categoryFilter !== 'all') {
            $sql .= " AND EXISTS (
                SELECT 1 FROM aiolia.order_items oi2
                INNER JOIN aiolia.ticket_types tt2 ON tt2.id = oi2.ticket_type_id
                INNER JOIN aiolia.events e2 ON e2.id = tt2.event_id
                INNER JOIN aiolia.event_categories ec ON ec.id = e2.primary_category_id
                WHERE oi2.order_id = o.id
                AND ec.slug = :category
            )";
            $params['category'] = $categoryFilter;
        }

        // Appliquer la recherche
        if (!empty($searchQuery)) {
            $sql .= ' AND (
                e.title ILIKE :search 
                OR CAST(o.id AS TEXT) ILIKE :search
                OR o.notes::text ILIKE :search
            )';
            $params['search'] = '%' . $searchQuery . '%';
        }

        $result = $this->connection->executeQuery($sql, $params)->fetchAssociative();
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Récupère les statuts disponibles pour un utilisateur.
     */
    public function findAvailableStatuses(int $userId): array
    {
        // Récupérer tous les statuts possibles depuis l'ENUM order_status_enum
        $sqlEnum = <<<SQL
            SELECT unnest(enum_range(NULL::aiolia.order_status_enum))::text as status
            ORDER BY status
        SQL;

        $enumStatuses = $this->connection->executeQuery($sqlEnum)->fetchAllAssociative();

        // Récupérer le nombre de commandes par statut pour cet utilisateur
        $sqlCounts = <<<SQL
            SELECT o.status, COUNT(*) as count
            FROM aiolia.orders o
            WHERE o.user_id = :user_id
            GROUP BY o.status
        SQL;

        $counts = $this->connection->executeQuery($sqlCounts, ['user_id' => $userId])->fetchAllAssociative();

        // Créer un tableau associatif pour les compteurs
        $countMap = [];
        foreach ($counts as $countRow) {
            $countMap[$countRow['status']] = (int) $countRow['count'];
        }

        $statusLabels = [
            'pending' => 'En attente',
            'paid' => 'Payée',
            'cancelled' => 'Annulée',
            'failed' => 'Échouée',
        ];

        $excludedStatuses = ['awaiting_payment', 'refunded'];

        $availableStatuses = [];
        foreach ($enumStatuses as $enumRow) {
            $status = $enumRow['status'];

            if (in_array($status, $excludedStatuses, true)) {
                continue;
            }

            $count = $countMap[$status] ?? 0;

            $availableStatuses[] = [
                'key' => $status,
                'label' => $statusLabels[$status] ?? ucfirst($status),
                'count' => $count,
            ];
        }

        return $availableStatuses;
    }

    /**
     * Récupère les catégories disponibles pour un utilisateur (basées sur ses commandes).
     */
    public function findAvailableCategories(int $userId): array
    {
        $sql = <<<SQL
            SELECT DISTINCT
                ec.slug,
                ec.label,
                COUNT(DISTINCT o.id) as order_count
            FROM aiolia.orders o
            INNER JOIN aiolia.order_items oi ON oi.order_id = o.id
            INNER JOIN aiolia.ticket_types tt ON tt.id = oi.ticket_type_id
            INNER JOIN aiolia.events e ON e.id = tt.event_id
            INNER JOIN aiolia.event_categories ec ON ec.id = e.primary_category_id
            WHERE o.user_id = :user_id
            GROUP BY ec.slug, ec.label
            ORDER BY order_count DESC, ec.label ASC
        SQL;

        $rows = $this->connection->executeQuery($sql, ['user_id' => $userId])->fetchAllAssociative();

        return array_map(function (array $row): array {
            return [
                'slug' => $row['slug'],
                'label' => $row['label'],
                'count' => (int) $row['order_count'],
            ];
        }, $rows);
    }

    /**
     * Récupère les dépenses mensuelles d'un utilisateur.
     */
    public function findMonthlyExpenses(int $userId, ?\DateTimeImmutable $dateFrom = null): array
    {
        $sql = <<<SQL
            SELECT 
                TO_CHAR(o.created_at, 'Month YYYY') as month_name,
                TO_CHAR(o.created_at, 'YYYY-MM') as month_key,
                SUM(o.total_amount) as total
            FROM aiolia.orders o
            WHERE o.user_id = :user_id 
              AND (o.status = 'paid' OR o.status = 'pending')
        SQL;

        $params = ['user_id' => $userId];

        if ($dateFrom !== null) {
            $sql .= ' AND o.created_at >= :date_from';
            $params['date_from'] = $dateFrom->format('Y-m-d H:i:s');
        }
        // Si dateFrom est null (période = 'all'), on récupère toutes les données sans limite de temps

        // Déterminer la limite selon la période
        $limit = 12; // Par défaut, 12 mois pour l'affichage
        if ($dateFrom === null) {
            // Pour "Toutes périodes", on peut afficher plus de mois (par exemple 24)
            $limit = 24;
        } else {
            // Pour les périodes spécifiques (30, 90, 365 jours), limiter à 12 mois max
            $limit = 12;
        }

        $sql .= <<<SQL
            GROUP BY TO_CHAR(o.created_at, 'Month YYYY'), TO_CHAR(o.created_at, 'YYYY-MM')
            ORDER BY month_key DESC
            LIMIT {$limit}
        SQL;

        $rows = $this->connection->executeQuery($sql, $params)->fetchAllAssociative();

        return array_map(function (array $row): array {
            return [
                'month' => trim($row['month_name']),
                'total' => number_format((float) $row['total'], 0, ',', ' ') . ' MGA',
                'total_raw' => (float) $row['total'],
            ];
        }, $rows);
    }

    /**
     * Récupère les données de dépenses par mois pour le graphique.
     */
    /**
     * Récupère les données de dépenses par mois pour le graphique.
     * 
     * @param int $userId ID de l'utilisateur
     * @param int $months Nombre de mois à récupérer (6, 12, ou 24)
     * @return array Tableau avec 'labels' (mois) et 'data' (montants)
     */
    public function findSpendingChartData(int $userId, int $months = 6): array
    {
        // Sanitize months value
        if ($months < 1)
            $months = 6;
        if ($months > 60)
            $months = 60;

        $now = new \DateTimeImmutable('now');
        $startDate = $now->modify('first day of this month')->modify("-" . ($months - 1) . " months")->setTime(0, 0, 0);
        $endDate = $now->modify('last day of this month')->setTime(23, 59, 59);

        $sql = <<<SQL
            SELECT 
                TO_CHAR(o.created_at, 'YYYY-MM') as month_key,
                SUM(o.total_amount) as total_amount
            FROM aiolia.orders o
            WHERE o.user_id = :user_id
            AND (o.status = 'paid' OR o.status = 'pending')
            AND o.created_at >= :start_date
            AND o.created_at <= :end_date
            GROUP BY month_key
            ORDER BY month_key ASC
        SQL;

        $rows = $this->connection->executeQuery($sql, [
            'user_id' => $userId,
            'start_date' => $startDate->format('Y-m-d H:i:s'),
            'end_date' => $endDate->format('Y-m-d H:i:s'),
        ])->fetchAllAssociative();

        $monthlyData = [];
        foreach ($rows as $row) {
            $monthlyData[$row['month_key']] = (float) $row['total_amount'];
        }

        $labels = [];
        $data = [];
        $monthNames = [
            1 => 'Jan',
            2 => 'Fév',
            3 => 'Mar',
            4 => 'Avr',
            5 => 'Mai',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Aoû',
            9 => 'Sep',
            10 => 'Oct',
            11 => 'Nov',
            12 => 'Déc'
        ];

        for ($i = 0; $i < $months; $i++) {
            $currentMonth = $startDate->modify("+$i months");
            $monthKey = $currentMonth->format('Y-m');
            $monthNum = (int) $currentMonth->format('n');
            $year = $currentMonth->format('Y');

            $labels[] = $monthNames[$monthNum] . ' ' . $year;
            $data[] = $monthlyData[$monthKey] ?? 0;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Récupère la comparaison des dépenses entre l'année en cours et l'année précédente.
     */
    public function findYearComparison(int $userId): array
    {
        $currentYear = (int) date('Y');
        $previousYear = $currentYear - 1;

        $sql = <<<SQL
            SELECT 
                TO_CHAR(o.created_at, 'MM') as month_num,
                TO_CHAR(o.created_at, 'Month') as month_name,
                EXTRACT(YEAR FROM o.created_at) as year,
                SUM(o.total_amount) as total_amount
            FROM aiolia.orders o
            WHERE o.user_id = :user_id 
              AND o.status = 'paid'
              AND EXTRACT(YEAR FROM o.created_at) IN ({$currentYear}, {$previousYear})
            GROUP BY TO_CHAR(o.created_at, 'MM'), TO_CHAR(o.created_at, 'Month'), EXTRACT(YEAR FROM o.created_at)
            ORDER BY month_num, year
        SQL;

        $rows = $this->connection->executeQuery($sql, [
            'user_id' => $userId,
        ])->fetchAllAssociative();

        // Organiser les données par mois
        $monthlyData = [];
        $monthNames = [
            '01' => 'Janvier',
            '02' => 'Février',
            '03' => 'Mars',
            '04' => 'Avril',
            '05' => 'Mai',
            '06' => 'Juin',
            '07' => 'Juillet',
            '08' => 'Août',
            '09' => 'Septembre',
            '10' => 'Octobre',
            '11' => 'Novembre',
            '12' => 'Décembre'
        ];

        $monthNamesShort = [
            '01' => 'Jan',
            '02' => 'Fév',
            '03' => 'Mar',
            '04' => 'Avr',
            '05' => 'Mai',
            '06' => 'Juin',
            '07' => 'Juil',
            '08' => 'Aoû',
            '09' => 'Sep',
            '10' => 'Oct',
            '11' => 'Nov',
            '12' => 'Déc'
        ];

        foreach ($rows as $row) {
            $monthNum = $row['month_num'];
            $year = (int) $row['year'];
            $amount = (float) $row['total_amount'];

            if (!isset($monthlyData[$monthNum])) {
                $monthlyData[$monthNum] = [
                    'month' => trim($row['month_name']),
                    'month_short' => $monthNamesShort[$monthNum] ?? substr(trim($row['month_name']), 0, 3),
                    'current_year' => 0,
                    'previous_year' => 0,
                ];
            }

            if ($year === $currentYear) {
                $monthlyData[$monthNum]['current_year'] = $amount;
            } elseif ($year === $previousYear) {
                $monthlyData[$monthNum]['previous_year'] = $amount;
            }
        }

        // Convertir en tableau indexé et formater
        $comparison = [];
        $maxValue = 0;

        foreach ($monthlyData as $monthNum => $data) {
            if ($data['current_year'] > $maxValue) {
                $maxValue = $data['current_year'];
            }
            if ($data['previous_year'] > $maxValue) {
                $maxValue = $data['previous_year'];
            }

            $comparison[] = [
                'month' => $data['month'],
                'month_short' => $data['month_short'],
                'current_year' => $data['current_year'],
                'previous_year' => $data['previous_year'],
                'current_year_formatted' => number_format($data['current_year'], 0, ',', ' ') . ' MGA',
                'previous_year_formatted' => number_format($data['previous_year'], 0, ',', ' ') . ' MGA',
                'current_year_label' => (string) $currentYear,
                'previous_year_label' => (string) $previousYear,
                'growth' => $data['previous_year'] > 0
                    ? round((($data['current_year'] - $data['previous_year']) / $data['previous_year']) * 100, 1)
                    : ($data['current_year'] > 0 ? 100 : 0),
            ];
        }

        return [
            'data' => $comparison,
            'max_value' => $maxValue,
        ];
    }

    /**
     * Récupère l'historique financier détaillé.
     */
    public function findFinancialHistory(
        int $userId,
        ?int $year = null,
        int $month = 0,
        string $period = 'year',
        string $paymentMethodFilter = 'all',
        ?string $minAmount = null,
        ?string $maxAmount = null,
        string $categoryFilter = 'all'
    ): array {
        if ($year === null) {
            $year = (int) date('Y');
        }

        // Construire les conditions WHERE selon la période
        // Inclure 'paid' ET 'pending' (car en sandbox Mvola, les paiements réussis retournent 'pending')
        $whereConditions = ['o.user_id = :user_id', "(o.status = 'paid' OR o.status = 'pending')"];
        $params = ['user_id' => $userId];

        if ($period === 'year') {
            $whereConditions[] = 'EXTRACT(YEAR FROM o.created_at) = :year';
            $params['year'] = $year;
        } elseif ($period === 'month') {
            $whereConditions[] = 'EXTRACT(YEAR FROM o.created_at) = :year';
            $params['year'] = $year;
            if ($month > 0) {
                $whereConditions[] = 'EXTRACT(MONTH FROM o.created_at) = :month';
                $params['month'] = $month;
            }
        }
        // Pour 'all', pas de filtre de date

        // Filtre par méthode de paiement
        if ($paymentMethodFilter !== 'all') {
            $whereConditions[] = "o.notes::json->>'payment_method' = :payment_method";
            $params['payment_method'] = $paymentMethodFilter;
        }

        // Filtre par montant minimum
        if ($minAmount !== null && $minAmount !== '') {
            $whereConditions[] = 'o.total_amount >= :min_amount';
            $params['min_amount'] = (float) $minAmount;
        }

        // Filtre par montant maximum
        if ($maxAmount !== null && $maxAmount !== '') {
            $whereConditions[] = 'o.total_amount <= :max_amount';
            $params['max_amount'] = (float) $maxAmount;
        }

        // Filtre par catégorie d'événement (nécessite une jointure avec order_items, ticket_types, events et event_categories)
        if ($categoryFilter !== 'all') {
            $whereConditions[] = "EXISTS (
                SELECT 1 FROM aiolia.order_items oi
                INNER JOIN aiolia.ticket_types tt ON tt.id = oi.ticket_type_id
                INNER JOIN aiolia.events e ON e.id = tt.event_id
                INNER JOIN aiolia.event_categories ec ON ec.id = e.primary_category_id
                WHERE oi.order_id = o.id
                AND ec.slug = :category
            )";
            $params['category'] = $categoryFilter;
        }

        $whereClause = implode(' AND ', $whereConditions);

        // Récupérer le total des dépenses selon les filtres
        $sql = <<<SQL
            SELECT 
                SUM(o.total_amount) as total_spent,
                COUNT(*) as total_orders,
                SUM(CASE WHEN EXTRACT(MONTH FROM o.created_at) = EXTRACT(MONTH FROM NOW()) 
                    AND EXTRACT(YEAR FROM o.created_at) = EXTRACT(YEAR FROM NOW())
                    THEN o.total_amount ELSE 0 END) as monthly_spent
            FROM aiolia.orders o
            WHERE {$whereClause}
        SQL;

        $row = $this->connection->executeQuery($sql, $params)->fetchAssociative();

        // Récupérer les remboursements (commandes annulées ou tickets remboursés via order_items)
        $refundWhereConditions = ['o.user_id = :user_id', "o.status = 'cancelled'"];
        $refundParams = ['user_id' => $userId];

        if ($period === 'year') {
            $refundWhereConditions[] = 'EXTRACT(YEAR FROM o.created_at) = :refund_year';
            $refundParams['refund_year'] = $year;
        } elseif ($period === 'month') {
            $refundWhereConditions[] = 'EXTRACT(YEAR FROM o.created_at) = :refund_year';
            $refundParams['refund_year'] = $year;
            if ($month > 0) {
                $refundWhereConditions[] = 'EXTRACT(MONTH FROM o.created_at) = :refund_month';
                $refundParams['refund_month'] = $month;
            }
        }

        $refundWhereClause = implode(' AND ', $refundWhereConditions);

        $refundSql = <<<SQL
            SELECT 
                COALESCE(SUM(o.total_amount), 0) as total_refunded,
                COUNT(DISTINCT o.id) as refund_count
            FROM aiolia.orders o
            WHERE {$refundWhereClause}
        SQL;

        $refundRow = $this->connection->executeQuery($refundSql, $refundParams)->fetchAssociative();

        // Si pas de commandes annulées, chercher via les tickets remboursés
        if ((float) ($refundRow['total_refunded'] ?? 0) == 0) {
            $ticketRefundSql = <<<SQL
                SELECT 
                    COALESCE(SUM(oi.total_amount), 0) as total_refunded,
                    COUNT(DISTINCT t.ticket_type_id) as refund_count
                FROM aiolia.tickets t
                INNER JOIN aiolia.order_items oi ON t.order_item_id = oi.id
                WHERE t.owner_user_id = :user_id
                  AND t.status = 'refunded'
            SQL;

            $ticketRefundRow = $this->connection->executeQuery($ticketRefundSql, ['user_id' => $userId])->fetchAssociative();
            if ((float) ($ticketRefundRow['total_refunded'] ?? 0) > 0) {
                $refundRow = $ticketRefundRow;
            }
        }

        // Récupérer le solde du wallet
        $walletSql = <<<SQL
            SELECT balance, points_balance
            FROM aiolia.wallets
            WHERE user_id = :user_id
            LIMIT 1
        SQL;

        $walletRow = $this->connection->executeQuery($walletSql, ['user_id' => $userId])->fetchAssociative();

        // Calculer la moyenne par commande
        $totalOrders = (int) ($row['total_orders'] ?? 0);
        $totalSpent = (float) ($row['total_spent'] ?? 0);
        $averageOrder = $totalOrders > 0 ? $totalSpent / $totalOrders : 0;

        // Récupérer les dépenses de l'année précédente pour comparaison (seulement si période = année)
        $previousYearSpent = 0;
        $yearOverYearChange = 0;

        if ($period === 'year') {
            $previousYearSql = <<<SQL
                SELECT 
                    SUM(CASE WHEN o.status = 'paid' THEN o.total_amount ELSE 0 END) as total_spent_previous
                FROM aiolia.orders o
                WHERE o.user_id = :user_id
                  AND EXTRACT(YEAR FROM o.created_at) = :previous_year
            SQL;

            $previousRow = $this->connection->executeQuery($previousYearSql, [
                'user_id' => $userId,
                'previous_year' => $year - 1
            ])->fetchAssociative();
            $previousYearSpent = (float) ($previousRow['total_spent_previous'] ?? 0);
            $yearOverYearChange = $previousYearSpent > 0
                ? (($totalSpent - $previousYearSpent) / $previousYearSpent) * 100
                : ($totalSpent > 0 ? 100 : 0);
        }

        return [
            'total_spent' => number_format($totalSpent, 0, ',', ' ') . ' MGA',
            'total_refunded' => number_format((float) ($refundRow['total_refunded'] ?? 0), 0, ',', ' ') . ' MGA',
            'refund_count' => (int) ($refundRow['refund_count'] ?? 0),
            'wallet_balance' => number_format((float) ($walletRow['balance'] ?? 0), 0, ',', ' ') . ' MGA',
            'wallet_points' => (int) ($walletRow['points_balance'] ?? 0),
            'total_orders' => $totalOrders,
            'average_order' => number_format($averageOrder, 0, ',', ' ') . ' MGA',
            'monthly_spent' => number_format((float) ($row['monthly_spent'] ?? 0), 0, ',', ' ') . ' MGA',
            'year_over_year_change' => round($yearOverYearChange, 1),
        ];
    }

    /**
     * Récupère les données financières mensuelles.
     */
    public function findMonthlyFinancialData(int $userId, ?int $year = null, int $month = 0, string $period = 'year', string $monthlyRange = 'last_6'): array
    {
        if ($year === null) {
            $year = (int) date('Y');
        }

        // Inclure 'paid' ET 'pending' (car en sandbox Mvola, les paiements réussis retournent 'pending')
        $whereConditions = ['o.user_id = :user_id', "(o.status = 'paid' OR o.status = 'pending')"];
        $params = ['user_id' => $userId];

        // Déterminer les dates de début et de fin pour la génération des mois
        $startDate = null;
        $endDate = null;
        $limit = 12;

        if ($period === 'year') {
            if ($monthlyRange === 'first_6') {
                $startDate = new \DateTimeImmutable("$year-01-01 00:00:00");
                $endDate = new \DateTimeImmutable("$year-06-30 23:59:59");
                $limit = 6;
            } elseif ($monthlyRange === 'last_6') {
                $startDate = new \DateTimeImmutable("$year-07-01 00:00:00");
                $endDate = new \DateTimeImmutable("$year-12-31 23:59:59");
                $limit = 6;
            } else {
                $startDate = new \DateTimeImmutable("$year-01-01 00:00:00");
                $endDate = new \DateTimeImmutable("$year-12-31 23:59:59");
                $limit = 12;
            }
            $whereConditions[] = 'o.created_at BETWEEN :start_date AND :end_date';
            $params['start_date'] = $startDate->format('Y-m-d H:i:s');
            $params['end_date'] = $endDate->format('Y-m-d H:i:s');
        } elseif ($period === 'month' && $month > 0) {
            $startDate = new \DateTimeImmutable("$year-$month-01 00:00:00");
            $endDate = $startDate->modify('last day of this month')->setTime(23, 59, 59);
            $limit = 1;
            $whereConditions[] = 'o.created_at BETWEEN :start_date AND :end_date';
            $params['start_date'] = $startDate->format('Y-m-d H:i:s');
            $params['end_date'] = $endDate->format('Y-m-d H:i:s');
        } else {
            // Pour 'all' ou mois = 0, on prend les 12 derniers mois glissants
            $endDate = new \DateTimeImmutable();
            $startDate = $endDate->modify('-11 months')->modify('first day of this month')->setTime(0, 0, 0);
            $limit = 12;
            $whereConditions[] = 'o.created_at >= :start_date';
            $params['start_date'] = $startDate->format('Y-m-d H:i:s');
        }

        $whereClause = implode(' AND ', $whereConditions);

        $sql = <<<SQL
            SELECT 
                TO_CHAR(o.created_at, 'YYYY-MM') as month_key,
                SUM(o.total_amount) as total
            FROM aiolia.orders o
            WHERE {$whereClause}
            GROUP BY month_key
            ORDER BY month_key ASC
        SQL;

        $rows = $this->connection->executeQuery($sql, $params)->fetchAllAssociative();

        // Indexer les résultats par month_key
        $indexedData = [];
        foreach ($rows as $row) {
            $indexedData[$row['month_key']] = (float) $row['total'];
        }

        $monthNames = [
            1 => 'Janvier',
            2 => 'Février',
            3 => 'Mars',
            4 => 'Avril',
            5 => 'Mai',
            6 => 'Juin',
            7 => 'Juillet',
            8 => 'Août',
            9 => 'Septembre',
            10 => 'Octobre',
            11 => 'Novembre',
            12 => 'Décembre'
        ];

        // Générer la séquence complète des mois
        $result = [];
        $currentDate = $startDate;
        for ($i = 0; $i < $limit; $i++) {
            $monthKey = $currentDate->format('Y-m');
            $monthNum = (int) $currentDate->format('n');
            $totalAmount = $indexedData[$monthKey] ?? 0.0;

            $result[] = [
                'month' => $monthNames[$monthNum] ?? 'Mois ' . $monthNum,
                'month_number' => $monthNum,
                'month_key' => $monthKey,
                'total' => number_format($totalAmount, 0, ',', ' ') . ' MGA',
                'total_raw' => $totalAmount,
            ];

            $currentDate = $currentDate->modify('+1 month');
        }

        return $result;
    }

    /**
     * Supprime une commande de l'historique d'un utilisateur.
     * Vérifie que la commande appartient bien à l'utilisateur avant de la supprimer.
     * 
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteOrderFromHistory(int $orderId, int $userId): array
    {
        // Vérifier que la commande appartient à l'utilisateur
        $order = $this->findOrderByIdAndUserId($orderId, $userId);

        if (!$order) {
            return [
                'success' => false,
                'message' => 'Commande introuvable ou vous n\'avez pas la permission de la supprimer'
            ];
        }

        try {
            // Démarrer une transaction
            $this->connection->beginTransaction();

            // Supprimer l'historique des statuts de commande
            $this->connection->executeStatement(
                'DELETE FROM aiolia.order_status_history WHERE order_id = :order_id',
                ['order_id' => $orderId]
            );

            // Supprimer les order_items liés (les tickets seront mis à NULL automatiquement grâce à ON DELETE SET NULL)
            $this->connection->executeStatement(
                'DELETE FROM aiolia.order_items WHERE order_id = :order_id',
                ['order_id' => $orderId]
            );

            // Supprimer la commande
            $this->connection->executeStatement(
                'DELETE FROM aiolia.orders WHERE id = :order_id AND user_id = :user_id',
                [
                    'order_id' => $orderId,
                    'user_id' => $userId
                ]
            );

            // Valider la transaction
            $this->connection->commit();

            return [
                'success' => true,
                'message' => 'Commande supprimée avec succès'
            ];
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            // Retourner un message d'erreur détaillé
            $errorMessage = $e->getMessage();

            // Messages d'erreur plus conviviaux
            if (strpos($errorMessage, 'foreign key') !== false) {
                return [
                    'success' => false,
                    'message' => 'Impossible de supprimer cette commande car elle est liée à d\'autres données (tickets, paiements, etc.)'
                ];
            }

            return [
                'success' => false,
                'message' => 'Erreur lors de la suppression : ' . $errorMessage
            ];
        }
    }
}

