<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
{
    private Connection $connection;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
        $this->connection = $this->getEntityManager()->getConnection();
    }

    /**
     * @return Event[] Returns an array of Event objects
     */
    public function findPublishedEvents(): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.isPublished = :published')
            ->andWhere('e.isActive = :active')
            ->setParameter('published', true)
            ->setParameter('active', true)
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Event[] Returns an array of upcoming Event objects
     */
    public function findUpcomingEvents(): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.isPublished = :published')
            ->andWhere('e.isActive = :active')
            ->andWhere('e.startDate > :now')
            ->setParameter('published', true)
            ->setParameter('active', true)
            ->setParameter('now', new \DateTime())
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Event[] Returns an array of Event objects by category
     */
    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.category = :category')
            ->andWhere('e.isPublished = :published')
            ->andWhere('e.isActive = :active')
            ->setParameter('category', $category)
            ->setParameter('published', true)
            ->setParameter('active', true)
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Event[] Returns an array of Event objects by city
     */
    public function findByCity(string $city): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.city = :city')
            ->andWhere('e.isPublished = :published')
            ->andWhere('e.isActive = :active')
            ->setParameter('city', $city)
            ->setParameter('published', true)
            ->setParameter('active', true)
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Event[] Returns an array of Event objects by organizer
     */
    public function findByOrganizer(int $organizerId): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organizer = :organizer')
            ->setParameter('organizer', $organizerId)
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search events by title or description
     * @return Event[] Returns an array of Event objects
     */
    public function searchEvents(string $searchTerm): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.isPublished = :published')
            ->andWhere('e.isActive = :active')
            ->andWhere('(e.title LIKE :search OR e.description LIKE :search OR e.shortDescription LIKE :search)')
            ->setParameter('published', true)
            ->setParameter('active', true)
            ->setParameter('search', '%' . $searchTerm . '%')
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find events with available tickets
     * @return Event[] Returns an array of Event objects
     */
    public function findEventsWithAvailableTickets(): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.isPublished = :published')
            ->andWhere('e.isActive = :active')
            ->andWhere('e.currentBookings < e.maxCapacity')
            ->setParameter('published', true)
            ->setParameter('active', true)
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find events by date range
     * @return Event[] Returns an array of Event objects
     */
    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.isPublished = :published')
            ->andWhere('e.isActive = :active')
            ->andWhere('e.startDate >= :startDate')
            ->andWhere('e.startDate <= :endDate')
            ->setParameter('published', true)
            ->setParameter('active', true)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les événements à venir pour la page d'accueil (avec toutes les données nécessaires).
     */
    public function findUpcomingEventsForHome(int $limit = 6): array
    {
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
                v.latitude,
                v.longitude,
                e.starts_at,
                e.ends_at,
                COALESCE(primary_cat.label, cat.label) AS category_label,
                COALESCE(media.url, e.cover_image_url) AS image_url,
                pricing.min_price,
                pricing.max_price
            FROM aiolia.events e
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
            WHERE e.status = 'published'
              AND (e.starts_at IS NULL OR e.starts_at >= NOW() - INTERVAL '1 day')
            ORDER BY e.starts_at ASC NULLS LAST
            LIMIT :limit
        SQL;

        $rows = $this->connection->executeQuery($sql, ['limit' => $limit])->fetchAllAssociative();

        return array_map(static function (array $row): array {
            $startsAt = isset($row['starts_at']) ? new \DateTimeImmutable($row['starts_at']) : null;
            $endsAt = isset($row['ends_at']) ? new \DateTimeImmutable($row['ends_at']) : null;

            return [
                'id' => (int) $row['id'],
                'slug' => $row['slug'],
                'title' => $row['title'],
                'subtitle' => $row['subtitle'],
                'summary' => $row['summary'],
                'venue_name' => $row['venue_name'],
                'venue_address' => $row['venue_address'],
                'city' => $row['city'],
                'region' => $row['region'],
                'country_code' => $row['country_code'],
                'category_label' => $row['category_label'],
                'image_url' => $row['image_url'],
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'min_price' => null !== $row['min_price'] ? (float) $row['min_price'] : null,
                'max_price' => null !== $row['max_price'] ? (float) $row['max_price'] : null,
                'latitude' => isset($row['latitude']) ? (float) $row['latitude'] : null,
                'longitude' => isset($row['longitude']) ? (float) $row['longitude'] : null,
            ];
        }, $rows);
    }

    /**
     * Récupère les statistiques principales pour la page d'accueil.
     */
    public function findHeadlineStats(): array
    {
        $sql = <<<SQL
            SELECT
                (SELECT COUNT(*) FROM aiolia.events) AS total_events,
                (SELECT COALESCE(SUM(sold_quantity), 0) FROM aiolia.ticket_inventory) AS tickets_sold,
                (SELECT COUNT(*) FROM aiolia.organizer_profiles) AS organizers
        SQL;

        $row = $this->connection->executeQuery($sql)->fetchAssociative() ?: [];

        return [
            'total_events' => (int) ($row['total_events'] ?? 0),
            'tickets_sold' => (int) ($row['tickets_sold'] ?? 0),
            'organizers' => (int) ($row['organizers'] ?? 0),
        ];
    }

    /**
     * Vérifie si un événement existe.
     */
    public function eventExists(int $eventId): bool
    {
        $result = $this->connection->executeQuery(
            'SELECT id FROM aiolia.events WHERE id = :id',
            ['id' => $eventId]
        )->fetchOne();

        return (bool) $result;
    }

    /**
     * Récupère les informations de base d'un événement par ID.
     */
    public function findEventById(int $eventId): ?array
    {
        $sql = <<<SQL
            SELECT 
                id,
                organizer_id,
                title,
                status,
                starts_at
            FROM aiolia.events
            WHERE id = :id
            LIMIT 1
        SQL;

        $result = $this->connection->executeQuery($sql, ['id' => $eventId])->fetchAssociative();
        return $result ?: null;
    }

    /**
     * Récupère les événements avec recherche et filtres complexes.
     */
    public function searchEventsWithFilters(string $query = '', array $filters = []): array
    {
        $category = $filters['category'] ?? '';
        $city = $filters['city'] ?? '';
        $priceMin = $filters['price_min'] ?? null;
        $priceMax = $filters['price_max'] ?? null;
        $dateFrom = $filters['date_from'] ?? '';
        $dateTo = $filters['date_to'] ?? '';
        $sortBy = $filters['sort_by'] ?? 'date';
        $sortOrder = $filters['sort_order'] ?? 'asc';

        $exactQuery = $query;
        $startQuery = $query . '%';
        $containsQuery = '%' . $query . '%';
        
        $sql = <<<SQL
            SELECT DISTINCT
                e.id,
                e.slug,
                e.title,
                e.subtitle,
                e.summary,
                e.description,
                COALESCE(e.location_override->>'venue_name', v.name) AS venue_name,
                COALESCE(e.location_override->>'address', NULLIF(CONCAT_WS(', ', v.address_line1, v.address_line2), '')) AS venue_address,
                COALESCE(e.location_override->>'city', v.city) AS city,
                COALESCE(e.location_override->>'region', v.region) AS region,
                COALESCE(e.location_override->>'country', v.country_code) AS country_code,
                v.latitude,
                v.longitude,
                e.starts_at,
                e.ends_at,
                COALESCE(primary_cat.label, cat.label) AS category_label,
                COALESCE(primary_cat.slug, cat.slug) AS category_slug,
                COALESCE(media.url, e.cover_image_url) AS image_url,
                pricing.min_price,
                pricing.max_price,
                CASE
                    WHEN :exact_query = '' THEN 0
                    WHEN e.title ILIKE :exact_query THEN 100
                    WHEN e.title ILIKE :start_query THEN 80
                    WHEN e.title ILIKE :contains_query THEN 60
                    WHEN e.summary ILIKE :contains_query THEN 40
                    WHEN e.description ILIKE :contains_query THEN 20
                    WHEN tag.label ILIKE :contains_query THEN 30
                    ELSE 0
                END AS relevance_score
            FROM aiolia.events e
            LEFT JOIN aiolia.venues v ON v.id = e.venue_id
            LEFT JOIN aiolia.event_categories primary_cat ON primary_cat.id = e.primary_category_id
            LEFT JOIN LATERAL (
                SELECT c.label, c.slug
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
            LEFT JOIN aiolia.event_tag_links etl ON etl.event_id = e.id
            LEFT JOIN aiolia.event_tags tag ON tag.id = etl.tag_id
        SQL;

        $where = ["e.status = 'published'", "e.visibility = 'public'"];
        $params = [
            'exact_query' => $exactQuery,
            'start_query' => $startQuery,
            'contains_query' => $containsQuery,
        ];
        $types = [
            'exact_query' => \PDO::PARAM_STR,
            'start_query' => \PDO::PARAM_STR,
            'contains_query' => \PDO::PARAM_STR,
        ];

        if (!empty($query)) {
            $where[] = <<<SQL
                (
                    e.title ILIKE :contains_query
                    OR e.subtitle ILIKE :contains_query
                    OR e.summary ILIKE :contains_query
                    OR e.description ILIKE :contains_query
                    OR tag.label ILIKE :contains_query
                )
            SQL;
        }

        if (!empty($category)) {
            $where[] = <<<SQL
                (
                    primary_cat.slug = :category
                    OR EXISTS (
                        SELECT 1
                        FROM aiolia.event_category_links cl
                        JOIN aiolia.event_categories c ON c.id = cl.category_id
                        WHERE cl.event_id = e.id
                          AND c.slug = :category
                    )
                )
            SQL;
            $params['category'] = $category;
            $types['category'] = \PDO::PARAM_STR;
        }

        if (!empty($city)) {
            $where[] = "(COALESCE(e.location_override->>'city', v.city) = :city)";
            $params['city'] = $city;
            $types['city'] = \PDO::PARAM_STR;
        }

        if (null !== $priceMin || null !== $priceMax) {
            if (null !== $priceMin && null !== $priceMax) {
                $where[] = "(pricing.min_price BETWEEN :price_min AND :price_max OR pricing.max_price BETWEEN :price_min AND :price_max)";
                $params['price_min'] = $priceMin;
                $params['price_max'] = $priceMax;
                $types['price_min'] = \PDO::PARAM_STR;
                $types['price_max'] = \PDO::PARAM_STR;
            } elseif (null !== $priceMin) {
                $where[] = "pricing.max_price >= :price_min";
                $params['price_min'] = $priceMin;
                $types['price_min'] = \PDO::PARAM_STR;
            } elseif (null !== $priceMax) {
                $where[] = "pricing.min_price <= :price_max";
                $params['price_max'] = $priceMax;
                $types['price_max'] = \PDO::PARAM_STR;
            }
        }

        if (!empty($dateFrom)) {
            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $dateFrom, $matches)) {
                $dateFrom = $matches[3] . '-' . $matches[2] . '-' . $matches[1];
            }
            if (strlen($dateFrom) === 10) {
                $dateFrom .= ' 00:00:00';
            }
            $where[] = "e.starts_at >= :date_from::timestamptz";
            $params['date_from'] = $dateFrom;
            $types['date_from'] = \PDO::PARAM_STR;
        }
        if (!empty($dateTo)) {
            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $dateTo, $matches)) {
                $dateTo = $matches[3] . '-' . $matches[2] . '-' . $matches[1];
            }
            if (strlen($dateTo) === 10) {
                $dateTo .= ' 23:59:59';
            }
            $where[] = "e.ends_at <= :date_to::timestamptz";
            $params['date_to'] = $dateTo;
            $types['date_to'] = \PDO::PARAM_STR;
        }

        $sql .= ' WHERE ' . implode(' AND ', $where);

        $orderBy = [];
        if (!empty($query)) {
            $orderBy[] = "relevance_score DESC";
        }
        switch ($sortBy) {
            case 'price_asc':
                $orderBy[] = "pricing.min_price ASC NULLS LAST";
                break;
            case 'price_desc':
                $orderBy[] = "pricing.max_price DESC NULLS LAST";
                break;
            case 'popularity':
                $orderBy[] = "e.starts_at ASC NULLS LAST";
                break;
            case 'date':
            default:
                $orderBy[] = "e.starts_at " . strtoupper($sortOrder) . " NULLS LAST";
                break;
        }
        $orderBy[] = "e.title ASC";
        $sql .= ' ORDER BY ' . implode(', ', $orderBy);

        try {
            $rows = $this->connection->executeQuery($sql, $params, $types)->fetchAllAssociative();

            return array_map(static function (array $row): array {
                $startsAt = isset($row['starts_at']) ? new \DateTimeImmutable($row['starts_at']) : null;
                $endsAt = isset($row['ends_at']) ? new \DateTimeImmutable($row['ends_at']) : null;

                return [
                    'id' => (int) $row['id'],
                    'slug' => $row['slug'],
                    'title' => $row['title'],
                    'subtitle' => $row['subtitle'],
                    'summary' => $row['summary'],
                    'description' => $row['description'] ?? null,
                    'venue_name' => $row['venue_name'],
                    'venue_address' => $row['venue_address'],
                    'city' => $row['city'],
                    'region' => $row['region'],
                    'country_code' => $row['country_code'],
                    'category_label' => $row['category_label'] ?? 'Événement',
                    'category_slug' => $row['category_slug'] ?? null,
                    'image_url' => $row['image_url'],
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'min_price' => null !== $row['min_price'] ? (float) $row['min_price'] : null,
                    'max_price' => null !== $row['max_price'] ? (float) $row['max_price'] : null,
                    'latitude' => isset($row['latitude']) ? (float) $row['latitude'] : null,
                    'longitude' => isset($row['longitude']) ? (float) $row['longitude'] : null,
                    'relevance_score' => isset($row['relevance_score']) ? (int) $row['relevance_score'] : 0,
                ];
            }, $rows);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Récupère tous les événements publiés avec leurs détails complets.
     */
    public function findAllPublishedEvents(): array
    {
        $sql = <<<SQL
            SELECT
                e.id,
                e.slug,
                e.title,
                e.subtitle,
                e.summary,
                COALESCE(e.location_override->>'venue_name', v.name) AS venue_name,
                COALESCE(e.location_override->>'address', NULLIF(CONCAT_WS(', ', v.address_line1, v.address_line2), '')) AS venue_address,
                COALESCE(e.location_override->>'city', v.city) AS city,
                COALESCE(e.location_override->>'region', v.region) AS region,
                COALESCE(e.location_override->>'country', v.country_code) AS country_code,
                v.latitude,
                v.longitude,
                e.starts_at,
                e.ends_at,
                COALESCE(primary_cat.label, cat.label) AS category_label,
                COALESCE(media.url, e.cover_image_url) AS image_url,
                pricing.min_price,
                pricing.max_price
            FROM aiolia.events e
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
            WHERE e.status = 'published'
              AND e.visibility = 'public'
            ORDER BY e.starts_at ASC NULLS LAST, e.title ASC
        SQL;

        $rows = $this->connection->executeQuery($sql)->fetchAllAssociative();

        return array_map(static function (array $row): array {
            $startsAt = isset($row['starts_at']) ? new \DateTimeImmutable($row['starts_at']) : null;
            $endsAt = isset($row['ends_at']) ? new \DateTimeImmutable($row['ends_at']) : null;

            return [
                'id' => (int) $row['id'],
                'slug' => $row['slug'],
                'title' => $row['title'],
                'subtitle' => $row['subtitle'],
                'summary' => $row['summary'],
                'venue_name' => $row['venue_name'],
                'venue_address' => $row['venue_address'],
                'city' => $row['city'],
                'region' => $row['region'],
                'country_code' => $row['country_code'],
                'category_label' => $row['category_label'] ?? 'Événement',
                'image_url' => $row['image_url'],
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'min_price' => null !== $row['min_price'] ? (float) $row['min_price'] : null,
                'max_price' => null !== $row['max_price'] ? (float) $row['max_price'] : null,
                'latitude' => isset($row['latitude']) ? (float) $row['latitude'] : null,
                'longitude' => isset($row['longitude']) ? (float) $row['longitude'] : null,
            ];
        }, $rows);
    }

    /**
     * Récupère les catégories d'événements.
     */
    public function findAllCategories(): array
    {
        $sql = <<<SQL
            SELECT slug, label
            FROM aiolia.event_categories
            ORDER BY display_order ASC, label ASC
        SQL;

        $rows = $this->connection->executeQuery($sql)->fetchAllAssociative();

        return array_map(static fn (array $row): array => [
            'slug' => $row['slug'],
            'label' => $row['label'],
        ], $rows);
    }

    /**
     * Récupère toutes les villes distinctes où se déroulent des événements.
     */
    public function findAllCities(): array
    {
        $sql = <<<SQL
            SELECT DISTINCT COALESCE(e.location_override->>'city', v.city) AS city
            FROM aiolia.events e
            LEFT JOIN aiolia.venues v ON v.id = e.venue_id
            WHERE COALESCE(e.location_override->>'city', v.city) IS NOT NULL
              AND COALESCE(e.location_override->>'city', v.city) <> ''
            ORDER BY city ASC
        SQL;

        $rows = $this->connection->executeQuery($sql)->fetchFirstColumn();

        return array_map(static fn ($city) => (string) $city, $rows);
    }

    /**
     * Récupère les bornes de prix min/max.
     */
    public function findPriceBounds(): array
    {
        $sql = <<<SQL
            SELECT
                COALESCE(MIN(tt.base_price), 0) AS min_price,
                COALESCE(MAX(tt.base_price), 0) AS max_price
            FROM aiolia.ticket_types tt
        SQL;

        $row = $this->connection->executeQuery($sql)->fetchAssociative() ?: [];

        return [
            'min' => (float) ($row['min_price'] ?? 0),
            'max' => (float) ($row['max_price'] ?? 0),
        ];
    }

    /**
     * Récupère les détails complets d'un événement par ID.
     */
    public function findEventDetailsById(int $id): ?array
    {
        $sql = <<<SQL
            SELECT
                e.id,
                e.slug,
                e.title,
                e.subtitle,
                e.summary,
                e.description,
                e.event_format,
                e.timezone,
                e.starts_at,
                e.ends_at,
                e.sales_starts_at,
                e.sales_ends_at,
                e.capacity,
                e.language_code,
                e.location_override,
                e.created_at,
                COALESCE(primary_cat.label, cat.label) AS category_label,
                COALESCE(primary_cat.slug, cat.slug) AS category_slug,
                COALESCE(media.url, e.cover_image_url) AS image_url,
                media.alt_text AS image_alt,
                pricing.min_price,
                pricing.max_price,
                v.name AS venue_name_fallback,
                v.address_line1,
                v.address_line2,
                v.city AS venue_city,
                v.region AS venue_region,
                v.country_code AS venue_country,
                v.latitude,
                v.longitude,
                v.capacity AS venue_capacity,
                vs.name AS space_name
            FROM aiolia.events e
            LEFT JOIN aiolia.venues v ON v.id = e.venue_id
            LEFT JOIN aiolia.venue_spaces vs ON vs.id = e.main_space_id
            LEFT JOIN aiolia.event_categories primary_cat ON primary_cat.id = e.primary_category_id
            LEFT JOIN LATERAL (
                SELECT c.slug, c.label
                FROM aiolia.event_category_links cl
                JOIN aiolia.event_categories c ON c.id = cl.category_id
                WHERE cl.event_id = e.id
                ORDER BY c.display_order ASC, c.label ASC
                LIMIT 1
            ) AS cat ON TRUE
            LEFT JOIN LATERAL (
                SELECT m.url, m.alt_text
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
            WHERE e.id = :id
            LIMIT 1
        SQL;

        $row = $this->connection->executeQuery($sql, ['id' => $id])->fetchAssociative();

        if (false === $row) {
            return null;
        }

        $startsAt = isset($row['starts_at']) ? new \DateTimeImmutable($row['starts_at']) : null;
        $endsAt = isset($row['ends_at']) ? new \DateTimeImmutable($row['ends_at']) : null;
        $salesStartsAt = isset($row['sales_starts_at']) ? new \DateTimeImmutable($row['sales_starts_at']) : null;
        $salesEndsAt = isset($row['sales_ends_at']) ? new \DateTimeImmutable($row['sales_ends_at']) : null;
        $createdAt = isset($row['created_at']) ? new \DateTimeImmutable($row['created_at']) : null;

        $override = [];
        if (!empty($row['location_override'])) {
            $decoded = json_decode($row['location_override'], true);
            if (is_array($decoded)) {
                $override = $decoded;
            }
        }

        $venueName = $override['venue_name'] ?? $row['venue_name_fallback'];
        $venueAddress = $override['address'] ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([$row['address_line1'], $row['address_line2']]))));
        if ('' === $venueAddress) {
            $venueAddress = null;
        }
        $city = $override['city'] ?? $row['venue_city'];
        $region = $override['region'] ?? $row['venue_region'];
        $countryCode = $override['country'] ?? $row['venue_country'];
        $latitude = isset($override['latitude'])
            ? (float) $override['latitude']
            : (isset($row['latitude']) ? (float) $row['latitude'] : null);
        $longitude = isset($override['longitude'])
            ? (float) $override['longitude']
            : (isset($row['longitude']) ? (float) $row['longitude'] : null);

        return [
            'id' => (int) $row['id'],
            'slug' => $row['slug'],
            'title' => $row['title'],
            'subtitle' => $row['subtitle'],
            'summary' => $row['summary'],
            'description' => $row['description'] ?? $row['summary'],
            'event_format' => $row['event_format'],
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'sales_starts_at' => $salesStartsAt,
            'sales_ends_at' => $salesEndsAt,
            'capacity' => isset($row['capacity']) ? (int) $row['capacity'] : null,
            'language_code' => $row['language_code'],
            'timezone' => $row['timezone'],
            'created_at' => $createdAt,
            'category_label' => $row['category_label'] ?? 'Évènement',
            'category_slug' => $row['category_slug'],
            'image_url' => $row['image_url'],
            'image_alt' => $row['image_alt'] ?? $row['title'],
            'min_price' => null !== $row['min_price'] ? (float) $row['min_price'] : null,
            'max_price' => null !== $row['max_price'] ? (float) $row['max_price'] : null,
            'venue_name' => $venueName,
            'venue_address' => $venueAddress,
            'city' => $city,
            'region' => $region,
            'country_code' => $countryCode,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'space_name' => $row['space_name'],
            'venue_capacity' => isset($row['venue_capacity']) ? (int) $row['venue_capacity'] : null,
            'venue_raw' => [
                'address_line1' => $row['address_line1'],
                'address_line2' => $row['address_line2'],
            ],
        ];
    }

    /**
     * Récupère les types de billets pour un événement.
     */
    public function findTicketTypesByEventId(int $eventId): array
    {
        $sql = <<<SQL
            SELECT
                tt.id,
                tt.name,
                tt.description,
                tt.currency,
                tt.base_price,
                tt.service_fee,
                tt.vat_rate,
                tt.age_category,
                tt.min_per_order,
                tt.max_per_order,
                tt.metadata,
                inv.total_quantity,
                inv.reserved_quantity,
                inv.sold_quantity
            FROM aiolia.ticket_types tt
            LEFT JOIN aiolia.ticket_inventory inv ON inv.ticket_type_id = tt.id
            WHERE tt.event_id = :event_id
            ORDER BY 
                CASE tt.age_category 
                    WHEN 'adult' THEN 1 
                    WHEN 'child' THEN 2 
                    WHEN 'all' THEN 3 
                    ELSE 4 
                END,
                tt.base_price ASC NULLS LAST, 
                tt.name ASC
        SQL;

        $rows = $this->connection->executeQuery($sql, ['event_id' => $eventId])->fetchAllAssociative();

        return array_map(static function (array $row): array {
            $total = isset($row['total_quantity']) ? (int) $row['total_quantity'] : null;
            $sold = isset($row['sold_quantity']) ? (int) $row['sold_quantity'] : 0;
            $reserved = isset($row['reserved_quantity']) ? (int) $row['reserved_quantity'] : 0;
            $available = null;
            if (null !== $total) {
                $available = max($total - $sold - $reserved, 0);
            }

            $metadata = null;
            if (!empty($row['metadata'])) {
                $decoded = json_decode($row['metadata'], true);
                if (is_array($decoded)) {
                    $metadata = $decoded;
                }
            }

            return [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'description' => $row['description'],
                'currency' => $row['currency'],
                'base_price' => (float) $row['base_price'],
                'service_fee' => isset($row['service_fee']) ? (float) $row['service_fee'] : null,
                'vat_rate' => isset($row['vat_rate']) ? (float) $row['vat_rate'] : null,
                'age_category' => $row['age_category'] ?? 'all',
                'metadata' => $metadata,
                'min_per_order' => isset($row['min_per_order']) ? (int) $row['min_per_order'] : null,
                'max_per_order' => isset($row['max_per_order']) ? (int) $row['max_per_order'] : null,
                'available' => $available,
                'total_quantity' => $total,
                'sold_quantity' => $sold,
                'reserved_quantity' => $reserved,
                'is_available' => null === $available || $available > 0,
            ];
        }, $rows);
    }

    /**
     * Récupère les tags d'un événement.
     */
    public function findEventTags(int $eventId): array
    {
        $sql = <<<SQL
            SELECT t.label
            FROM aiolia.event_tag_links tl
            JOIN aiolia.event_tags t ON t.id = tl.tag_id
            WHERE tl.event_id = :event_id
            ORDER BY t.label ASC
        SQL;

        return $this->connection
            ->executeQuery($sql, ['event_id' => $eventId])
            ->fetchFirstColumn();
    }

    /**
     * Récupère des événements similaires basés sur la catégorie.
     */
    public function findSimilarEvents(?string $categorySlug, int $excludeId): array
    {
        $sql = <<<SQL
            SELECT
                e.id,
                e.slug,
                e.title,
                COALESCE(e.location_override->>'venue_name', v.name) AS venue_name,
                COALESCE(e.location_override->>'city', v.city) AS city,
                e.starts_at,
                COALESCE(primary_cat.label, cat.label) AS category_label,
                COALESCE(media.url, e.cover_image_url) AS image_url
            FROM aiolia.events e
            LEFT JOIN aiolia.venues v ON v.id = e.venue_id
            LEFT JOIN aiolia.event_categories primary_cat ON primary_cat.id = e.primary_category_id
            LEFT JOIN LATERAL (
                SELECT c.slug, c.label
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
            WHERE e.status = 'published'
              AND e.visibility = 'public'
              AND e.id <> :exclude_id
        SQL;

        $parameters = ['exclude_id' => $excludeId];
        $types = ['exclude_id' => \PDO::PARAM_INT];

        $whereClause = '';
        if (null !== $categorySlug && '' !== $categorySlug) {
            $whereClause = ' AND COALESCE(primary_cat.slug, cat.slug) = :category_slug';
            $parameters['category_slug'] = $categorySlug;
            $types['category_slug'] = \PDO::PARAM_STR;
        }

        $sql .= $whereClause . ' ORDER BY e.starts_at ASC NULLS LAST, e.created_at DESC LIMIT 4';

        $rows = $this->connection->executeQuery($sql, $parameters, $types)->fetchAllAssociative();

        return array_map(static function (array $row): array {
            $startsAt = isset($row['starts_at']) ? new \DateTimeImmutable($row['starts_at']) : null;

            return [
                'id' => (int) $row['id'],
                'slug' => $row['slug'],
                'title' => $row['title'],
                'category_label' => $row['category_label'] ?? 'Évènement',
                'venue_name' => $row['venue_name'],
                'city' => $row['city'],
                'starts_at' => $startsAt,
                'image_url' => $row['image_url'],
            ];
        }, $rows);
    }

    /**
     * Récupère les événements à venir de l'utilisateur (depuis ses tickets/commandes).
     */
    public function findUpcomingEventsForUser(int $userId): array
    {
        $sql = <<<SQL
            SELECT DISTINCT
                e.id,
                e.slug,
                e.title,
                e.subtitle,
                e.summary,
                COALESCE(e.location_override->>'venue_name', v.name) AS venue_name,
                COALESCE(e.location_override->>'address', NULLIF(CONCAT_WS(', ', v.address_line1, v.address_line2), '')) AS venue_address,
                COALESCE(e.location_override->>'city', v.city) AS city,
                COALESCE(e.location_override->>'region', v.region) AS region,
                e.starts_at,
                e.ends_at,
                COALESCE(primary_cat.label, cat.label) AS category_label,
                COALESCE(media.url, e.cover_image_url) AS image_url,
                COUNT(DISTINCT t.id) AS ticket_count,
                MAX(o.status) AS order_status
            FROM aiolia.events e
            INNER JOIN aiolia.ticket_types tt ON tt.event_id = e.id
            INNER JOIN aiolia.order_items oi ON oi.ticket_type_id = tt.id
            INNER JOIN aiolia.orders o ON o.id = oi.order_id
            LEFT JOIN aiolia.tickets t ON t.order_item_id = oi.id 
                AND (t.owner_user_id = :user_id OR (t.owner_user_id IS NULL AND o.user_id = :user_id))
                AND (t.status IS NULL OR t.status != 'cancelled')
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
            WHERE o.user_id = :user_id
              AND e.starts_at >= NOW()
              AND o.status = 'paid'
            GROUP BY e.id, e.slug, e.title, e.subtitle, e.summary, e.location_override, 
                     v.name, v.address_line1, v.address_line2, v.city, v.region,
                     e.starts_at, e.ends_at, primary_cat.label, cat.label, 
                     media.url, e.cover_image_url
            ORDER BY e.starts_at ASC
        SQL;

        try {
            $rows = $this->connection->executeQuery($sql, ['user_id' => $userId])->fetchAllAssociative();

            return array_map(static function (array $row): array {
                $startsAt = isset($row['starts_at']) ? new \DateTimeImmutable($row['starts_at']) : null;
                $endsAt = isset($row['ends_at']) ? new \DateTimeImmutable($row['ends_at']) : null;

                return [
                    'id' => (int) $row['id'],
                    'slug' => $row['slug'],
                    'title' => $row['title'],
                    'subtitle' => $row['subtitle'],
                    'summary' => $row['summary'],
                    'venue_name' => $row['venue_name'],
                    'venue_address' => $row['venue_address'],
                    'city' => $row['city'],
                    'region' => $row['region'],
                    'category_label' => $row['category_label'] ?? 'Événement',
                    'image_url' => $row['image_url'],
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'ticket_count' => (int) ($row['ticket_count'] ?? 0),
                    'order_status' => $row['order_status'],
                ];
            }, $rows);
        } catch (\Exception $e) {
            error_log('Error fetching upcoming events for user: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Compte le nombre de résultats pour une recherche avec filtres.
     */
    public function countSearchResults(string $query = '', string $category = '', string $city = '', ?float $priceMin = null, ?float $priceMax = null, string $dateFrom = '', string $dateTo = ''): int
    {
        $containsQuery = '%' . $query . '%';

        $sql = <<<SQL
            SELECT COUNT(DISTINCT e.id)
            FROM aiolia.events e
            LEFT JOIN aiolia.venues v ON v.id = e.venue_id
            LEFT JOIN aiolia.event_categories primary_cat ON primary_cat.id = e.primary_category_id
            LEFT JOIN LATERAL (
                SELECT c.slug, c.label
                FROM aiolia.event_category_links cl
                JOIN aiolia.event_categories c ON c.id = cl.category_id
                WHERE cl.event_id = e.id
                ORDER BY c.display_order ASC, c.label ASC
                LIMIT 1
            ) AS cat ON TRUE
            LEFT JOIN LATERAL (
                SELECT
                    MIN(tt.base_price) AS min_price,
                    MAX(tt.base_price) AS max_price
                FROM aiolia.ticket_types tt
                WHERE tt.event_id = e.id
            ) AS pricing ON TRUE
            LEFT JOIN aiolia.event_tag_links etl ON etl.event_id = e.id
            LEFT JOIN aiolia.event_tags tag ON tag.id = etl.tag_id
        SQL;

        $where = ["e.status = 'published'", "e.visibility = 'public'"];
        $params = [];
        $types = [];

        // Recherche textuelle
        if (!empty($query)) {
            $where[] = <<<SQL
                (
                    e.title ILIKE :contains_query
                    OR e.subtitle ILIKE :contains_query
                    OR e.summary ILIKE :contains_query
                    OR e.description ILIKE :contains_query
                    OR tag.label ILIKE :contains_query
                )
            SQL;
            $params['contains_query'] = $containsQuery;
            $types['contains_query'] = \PDO::PARAM_STR;
        }

        // Filtre par catégorie
        if (!empty($category)) {
            $where[] = <<<SQL
                (
                    primary_cat.slug = :category
                    OR EXISTS (
                        SELECT 1
                        FROM aiolia.event_category_links cl
                        JOIN aiolia.event_categories c ON c.id = cl.category_id
                        WHERE cl.event_id = e.id
                          AND c.slug = :category
                    )
                )
            SQL;
            $params['category'] = $category;
            $types['category'] = \PDO::PARAM_STR;
        }

        // Filtre par ville
        if (!empty($city)) {
            $where[] = "(COALESCE(e.location_override->>'city', v.city) = :city)";
            $params['city'] = $city;
            $types['city'] = \PDO::PARAM_STR;
        }

        // Filtre par prix
        if (null !== $priceMin || null !== $priceMax) {
            if (null !== $priceMin && null !== $priceMax) {
                $where[] = "(pricing.min_price BETWEEN :price_min AND :price_max OR pricing.max_price BETWEEN :price_min AND :price_max)";
                $params['price_min'] = $priceMin;
                $params['price_max'] = $priceMax;
                $types['price_min'] = \PDO::PARAM_STR;
                $types['price_max'] = \PDO::PARAM_STR;
            } elseif (null !== $priceMin) {
                $where[] = "pricing.max_price >= :price_min";
                $params['price_min'] = $priceMin;
                $types['price_min'] = \PDO::PARAM_STR;
            } elseif (null !== $priceMax) {
                $where[] = "pricing.min_price <= :price_max";
                $params['price_max'] = $priceMax;
                $types['price_max'] = \PDO::PARAM_STR;
            }
        }

        // Filtre par date
        if (!empty($dateFrom)) {
            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $dateFrom, $matches)) {
                $dateFrom = $matches[3] . '-' . $matches[2] . '-' . $matches[1];
            }
            if (strlen($dateFrom) === 10) {
                $dateFrom .= ' 00:00:00';
            }
            $where[] = "e.starts_at >= :date_from::timestamptz";
            $params['date_from'] = $dateFrom;
            $types['date_from'] = \PDO::PARAM_STR;
        }
        if (!empty($dateTo)) {
            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $dateTo, $matches)) {
                $dateTo = $matches[3] . '-' . $matches[2] . '-' . $matches[1];
            }
            if (strlen($dateTo) === 10) {
                $dateTo .= ' 23:59:59';
            }
            $where[] = "e.ends_at <= :date_to::timestamptz";
            $params['date_to'] = $dateTo;
            $types['date_to'] = \PDO::PARAM_STR;
        }

        $sql .= ' WHERE ' . implode(' AND ', $where);

        try {
            return (int) $this->connection->executeQuery($sql, $params, $types)->fetchOne();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Trouve les événements qui commencent dans X heures
     *
     * @return array<int, array<string, mixed>>
     */
    public function findEventsStartingIn(int $hours): array
    {
        $sql = <<<SQL
            SELECT 
                e.id,
                e.title,
                e.slug,
                e.starts_at,
                e.ends_at,
                e.cover_image_url,
                COALESCE(v.name, e.location_override->>'venue_name') AS venue_name,
                COALESCE(v.address_line1, e.location_override->>'address') AS address,
                COALESCE(v.city, e.location_override->>'city') AS city
            FROM aiolia.events e
            LEFT JOIN aiolia.venues v ON v.id = e.venue_id
            WHERE e.status = 'published'
              AND e.starts_at >= NOW() + make_interval(hours => :hours)
              AND e.starts_at < NOW() + make_interval(hours => :hours) + INTERVAL '1 hour'
            ORDER BY e.starts_at ASC
        SQL;

        return $this->connection->executeQuery($sql, ['hours' => $hours])->fetchAllAssociative();
    }
}

