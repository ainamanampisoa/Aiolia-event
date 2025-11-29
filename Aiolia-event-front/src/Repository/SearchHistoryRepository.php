<?php

namespace App\Repository;

use Doctrine\DBAL\Connection;

class SearchHistoryRepository
{
    public function __construct(
        private readonly Connection $connection
    ) {
    }

    /**
     * Sauvegarde une recherche dans l'historique.
     */
    public function saveSearch(int $userId, string $keywords, array $filters = []): void
    {
        $this->connection->executeStatement(
            'INSERT INTO aiolia.user_search_history (user_id, keywords, filters, searched_at)
             VALUES (:userId, :keywords, :filters::jsonb, NOW())
             ON CONFLICT DO NOTHING',
            [
                'userId' => $userId,
                'keywords' => $keywords,
                'filters' => json_encode($filters, JSON_THROW_ON_ERROR),
            ]
        );
    }

    /**
     * Récupère l'historique de recherche de l'utilisateur avec filtres et tri.
     */
    public function findUserSearchHistory(int $userId, string $searchQuery = '', string $sortBy = 'newest', string $dateFrom = '', string $dateTo = ''): array
    {
        $sql = 'SELECT id, keywords, filters, searched_at FROM aiolia.user_search_history WHERE user_id = :userId';
        $params = ['userId' => $userId];
        $where = [];

        // Filtre par mots-clés
        if (!empty($searchQuery)) {
            $where[] = 'keywords ILIKE :search_query';
            $params['search_query'] = '%' . $searchQuery . '%';
        }

        // Filtre par période
        if (!empty($dateFrom)) {
            $where[] = 'searched_at >= :date_from::timestamptz';
            $params['date_from'] = $dateFrom;
        }
        if (!empty($dateTo)) {
            $where[] = 'searched_at <= :date_to::timestamptz';
            $params['date_to'] = $dateTo . ' 23:59:59';
        }

        if (!empty($where)) {
            $sql .= ' AND ' . implode(' AND ', $where);
        }

        // Tri
        switch ($sortBy) {
            case 'oldest':
                $sql .= ' ORDER BY searched_at ASC';
                break;
            case 'newest':
            default:
                $sql .= ' ORDER BY searched_at DESC';
                break;
        }

        $rows = $this->connection->executeQuery($sql, $params)->fetchAllAssociative();

        return array_map(static function (array $row): array {
            $searchedAt = isset($row['searched_at']) ? new \DateTimeImmutable($row['searched_at']) : new \DateTimeImmutable();
            $filters = [];
            if (!empty($row['filters'])) {
                $decoded = json_decode($row['filters'], true);
                if (is_array($decoded)) {
                    $filters = $decoded;
                }
            }

            // Formater la date
            $days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
            $months = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
            $day = $searchedAt->format('d');
            $month = $months[(int) $searchedAt->format('n') - 1];
            $year = $searchedAt->format('Y');
            $time = $searchedAt->format('H:i');
            $dateFormatted = $day . ' ' . $month . ' ' . $year . ' · ' . $time;

            return [
                'id' => (int) $row['id'],
                'query' => $row['keywords'],
                'date' => $dateFormatted,
                'filters' => $filters,
                'searched_at' => $searchedAt,
            ];
        }, $rows);
    }

    /**
     * Supprime un élément de l'historique de recherche.
     */
    public function deleteSearchHistoryItem(int $id, int $userId): void
    {
        $this->connection->executeStatement(
            'DELETE FROM aiolia.user_search_history WHERE id = :id AND user_id = :userId',
            ['id' => $id, 'userId' => $userId]
        );
    }

    /**
     * Efface tout l'historique de recherche d'un utilisateur.
     */
    public function clearUserSearchHistory(int $userId): void
    {
        $this->connection->executeStatement(
            'DELETE FROM aiolia.user_search_history WHERE user_id = :userId',
            ['userId' => $userId]
        );
    }

    /**
     * Vérifie si un élément de l'historique appartient à l'utilisateur.
     */
    public function searchHistoryItemExists(int $id, int $userId): bool
    {
        $result = $this->connection->executeQuery(
            'SELECT id FROM aiolia.user_search_history WHERE id = :id AND user_id = :userId',
            ['id' => $id, 'userId' => $userId]
        )->fetchOne();

        return (bool) $result;
    }
}

