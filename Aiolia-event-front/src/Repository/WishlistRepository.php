<?php

namespace App\Repository;

use Doctrine\DBAL\Connection;

class WishlistRepository
{
    public function __construct(
        private readonly Connection $connection
    ) {
    }

    /**
     * Récupère ou crée la wishlist par défaut d'un utilisateur.
     */
    public function getOrCreateDefaultWishlist(int $userId): int
    {
        $wishlistId = $this->connection->executeQuery(
            'SELECT id FROM aiolia.wishlists WHERE user_id = :userId AND is_default = TRUE LIMIT 1',
            ['userId' => $userId]
        )->fetchOne();

        if (!$wishlistId) {
            // Utiliser executeQuery pour récupérer l'ID retourné par RETURNING
            $result = $this->connection->executeQuery(
                'INSERT INTO aiolia.wishlists (user_id, is_default, created_at)
                 VALUES (:userId, TRUE, NOW())
                 RETURNING id',
                ['userId' => $userId]
            );
            $wishlistId = $result->fetchOne();
        }

        return (int) $wishlistId;
    }

    /**
     * Vérifie si un événement est dans les favoris d'un utilisateur.
     */
    public function isEventInWishlist(int $userId, int $eventId): bool
    {
        $wishlistId = $this->connection->executeQuery(
            'SELECT id FROM aiolia.wishlists WHERE user_id = :userId AND is_default = TRUE LIMIT 1',
            ['userId' => $userId]
        )->fetchOne();

        if (!$wishlistId) {
            return false;
        }

        $exists = $this->connection->executeQuery(
            'SELECT 1 FROM aiolia.wishlist_items WHERE wishlist_id = :wishlistId AND event_id = :eventId LIMIT 1',
            ['wishlistId' => $wishlistId, 'eventId' => $eventId]
        )->fetchOne();

        return (bool) $exists;
    }

    /**
     * Ajoute un événement aux favoris.
     */
    public function addEventToWishlist(int $userId, int $eventId): void
    {
        try {
            $wishlistId = $this->getOrCreateDefaultWishlist($userId);

            // Vérifier si l'événement n'est pas déjà dans la wishlist
            $exists = $this->connection->executeQuery(
                'SELECT 1 FROM aiolia.wishlist_items WHERE wishlist_id = :wishlistId AND event_id = :eventId LIMIT 1',
                ['wishlistId' => $wishlistId, 'eventId' => $eventId]
            )->fetchOne();

            if (!$exists) {
                $this->connection->executeStatement(
                    'INSERT INTO aiolia.wishlist_items (wishlist_id, event_id, added_at)
                     VALUES (:wishlistId, :eventId, NOW())
                     ON CONFLICT (wishlist_id, event_id) DO NOTHING',
                    ['wishlistId' => $wishlistId, 'eventId' => $eventId]
                );
            }
        } catch (\Exception $e) {
            error_log('Erreur lors de l\'ajout aux favoris: ' . $e->getMessage());
            throw new \RuntimeException('Erreur lors de l\'ajout aux favoris: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Retire un événement des favoris.
     */
    public function removeEventFromWishlist(int $userId, int $eventId): void
    {
        try {
            $wishlistId = $this->connection->executeQuery(
                'SELECT id FROM aiolia.wishlists WHERE user_id = :userId AND is_default = TRUE LIMIT 1',
                ['userId' => $userId]
            )->fetchOne();

            if ($wishlistId) {
                $this->connection->executeStatement(
                    'DELETE FROM aiolia.wishlist_items WHERE wishlist_id = :wishlistId AND event_id = :eventId',
                    ['wishlistId' => $wishlistId, 'eventId' => $eventId]
                );
            }
        } catch (\Exception $e) {
            error_log('Erreur lors du retrait des favoris: ' . $e->getMessage());
            throw new \RuntimeException('Erreur lors du retrait des favoris: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Récupère les IDs des événements favoris d'un utilisateur.
     */
    public function findUserFavoriteEventIds(int $userId): array
    {
        $wishlistId = $this->connection->executeQuery(
            'SELECT id FROM aiolia.wishlists WHERE user_id = :userId AND is_default = TRUE LIMIT 1',
            ['userId' => $userId]
        )->fetchOne();

        if (!$wishlistId) {
            return [];
        }

        return $this->connection->executeQuery(
            'SELECT event_id FROM aiolia.wishlist_items WHERE wishlist_id = :wishlistId',
            ['wishlistId' => $wishlistId]
        )->fetchFirstColumn();
    }

    /**
     * Récupère les événements favoris d'un utilisateur avec leurs détails.
     */
    public function findUserFavoriteEvents(int $userId): array
    {
        // D'abord, récupérer tous les IDs d'événements favoris
        $wishlistId = $this->connection->executeQuery(
            'SELECT id FROM aiolia.wishlists WHERE user_id = :userId AND is_default = TRUE LIMIT 1',
            ['userId' => $userId]
        )->fetchOne();
        
        if (!$wishlistId) {
            return [];
        }
        
        $eventIds = $this->connection->executeQuery(
            'SELECT event_id FROM aiolia.wishlist_items WHERE wishlist_id = :wishlistId ORDER BY added_at DESC',
            ['wishlistId' => $wishlistId]
        )->fetchFirstColumn();
        
        if (empty($eventIds)) {
            return [];
        }
        
        // Ensuite, récupérer les détails de tous les événements en une seule requête
        $placeholders = implode(',', array_fill(0, count($eventIds), '?'));
        $sql = <<<SQL
            SELECT
                e.id,
                e.slug,
                e.title,
                COALESCE(e.subtitle, '') AS subtitle,
                COALESCE(e.summary, '') AS summary,
                COALESCE(e.location_override->>'venue_name', v.name) AS venue_name,
                COALESCE(e.location_override->>'address', NULLIF(CONCAT_WS(', ', v.address_line1, v.address_line2), '')) AS venue_address,
                COALESCE(e.location_override->>'city', v.city) AS city,
                COALESCE(e.location_override->>'region', v.region) AS region,
                COALESCE(e.location_override->>'country', v.country_code) AS country_code,
                e.starts_at,
                e.ends_at,
                COALESCE(primary_cat.label, cat.label) AS category_label,
                COALESCE(media.url, e.cover_image_url) AS image_url,
                pricing.min_price,
                pricing.max_price,
                wi.added_at
            FROM aiolia.events e
            INNER JOIN aiolia.wishlist_items wi ON wi.event_id = e.id AND wi.wishlist_id = :wishlistId
            LEFT JOIN aiolia.venues v ON v.id = e.venue_id
            LEFT JOIN aiolia.event_categories primary_cat ON primary_cat.id = e.primary_category_id
            LEFT JOIN LATERAL (
                SELECT c.label
                FROM aiolia.event_category_links cl
                JOIN aiolia.event_categories c ON c.id = cl.category_id
                WHERE cl.event_id = e.id
                ORDER BY c.display_order ASC, c.label ASC
                LIMIT 1
            ) AS cat ON TRUE
            LEFT JOIN LATERAL (
                SELECT m.url
                FROM aiolia.event_media m
                WHERE m.event_id = e.id
                  AND m.is_public IS TRUE
                ORDER BY m.display_order ASC, m.id ASC
                LIMIT 1
            ) AS media ON TRUE
            LEFT JOIN LATERAL (
                SELECT
                    MIN(tt.base_price) AS min_price,
                    MAX(tt.base_price) AS max_price
                FROM aiolia.ticket_types tt
                WHERE tt.event_id = e.id
            ) AS pricing ON TRUE
            WHERE e.id IN ($placeholders)
              AND e.status = 'published'
            ORDER BY wi.added_at DESC
        SQL;
        
        $params = array_merge(['wishlistId' => $wishlistId], $eventIds);

        try {
            $rows = $this->connection->executeQuery($sql, $params)->fetchAllAssociative();
        } catch (\Exception $e) {
            error_log('Erreur lors de la récupération des favoris: ' . $e->getMessage());
            return [];
        }

        return array_map(static function (array $row): array {
            $startsAt = isset($row['starts_at']) ? new \DateTimeImmutable($row['starts_at']) : null;
            $endsAt = isset($row['ends_at']) ? new \DateTimeImmutable($row['ends_at']) : null;
            $addedAt = isset($row['added_at']) ? new \DateTimeImmutable($row['added_at']) : null;

            // Déterminer le statut de vente
            $status = 'Bientôt disponible';
            if ($startsAt && $startsAt > new \DateTime()) {
                $status = 'Ouvert à la vente';
            } elseif ($startsAt && $startsAt <= new \DateTime()) {
                $status = 'En cours';
            }

            // Formater le prix
            $price = 'Tarifs non communiqués';
            if (null !== $row['min_price']) {
                if (null !== $row['max_price'] && $row['max_price'] > $row['min_price']) {
                    $price = 'Dès ' . number_format($row['min_price'], 0, '.', ' ') . ' MGA';
                } else {
                    $price = 'Dès ' . number_format($row['min_price'], 0, '.', ' ') . ' MGA';
                }
            }

            // Formater la date
            $dateFormatted = 'Date à confirmer';
            if ($startsAt) {
                $days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
                $months = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
                $dayName = $days[(int) $startsAt->format('w')];
                $day = $startsAt->format('d');
                $month = $months[(int) $startsAt->format('n') - 1];
                $time = $startsAt->format('H\\hi');
                $dateFormatted = $dayName . ' ' . $day . ' ' . $month . ' · ' . $time;
            }

            // Construire la localisation
            $locationParts = [];
            if ($row['venue_name']) {
                $locationParts[] = $row['venue_name'];
            }
            if ($row['city']) {
                $locationParts[] = $row['city'];
            }
            $location = !empty($locationParts) ? implode(', ', $locationParts) : 'Lieu à confirmer';

            // Badges (catégories)
            $badges = [];
            if ($row['category_label']) {
                $badges[] = $row['category_label'];
            }

            return [
                'id' => (int) $row['id'],
                'slug' => $row['slug'],
                'title' => $row['title'],
                'date' => $dateFormatted,
                'location' => $location,
                'image' => $row['image_url'] ?: 'vente-ticket/images/img1.png',
                'status' => $status,
                'badges' => $badges,
                'price' => $price,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ];
        }, $rows);
    }
}

