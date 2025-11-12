<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    public function __construct(
        private readonly Connection $connection
    ) {
    }

    #[Route('/', name: 'home')]
    public function index(): Response
    {
        $events = $this->fetchUpcomingEvents();
        $stats = $this->fetchHeadlineStats();

        return $this->render('home/index.html.twig', [
            'events' => $events,
            'stats' => $stats,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchUpcomingEvents(): array
    {
        $sql = <<<SQL
            SELECT
                e.id,
                e.slug,
                e.title,
                COALESCE(e.subtitle, '') AS subtitle,
                COALESCE(e.summary, '') AS summary,
                e.venue_name,
                e.venue_address,
                e.city,
                e.country_code,
                e.starts_at,
                e.ends_at,
                cat.label AS category_label,
                media.url AS image_url,
                pricing.min_price,
                pricing.max_price
            FROM aiolia.events e
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
            LIMIT 6
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
                'country_code' => $row['country_code'],
                'category_label' => $row['category_label'],
                'image_url' => $row['image_url'],
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'min_price' => null !== $row['min_price'] ? (float) $row['min_price'] : null,
                'max_price' => null !== $row['max_price'] ? (float) $row['max_price'] : null,
            ];
        }, $rows);
    }

    /**
     * @return array<string, float|int>
     */
    private function fetchHeadlineStats(): array
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
}
