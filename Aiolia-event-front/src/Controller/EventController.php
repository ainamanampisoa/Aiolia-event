<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EventController extends AbstractController
{
    public function __construct(
        private readonly Connection $connection,
        private readonly LoggerInterface $logger
    ) {
    }

    #[Route('/events', name: 'events')]
    public function listEvents(Request $request): Response
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }
        $sessionUser = $session->get('user');
        $isAuthenticated = is_array($sessionUser) && isset($sessionUser['id']);
        $this->logger->debug('EventController session check', [
            'session_started' => $session->isStarted(),
            'session_keys' => array_keys($session->all()),
            'session_user' => $sessionUser,
            'is_authenticated_flag' => $isAuthenticated,
        ]);

        $events = $this->fetchEvents();
        $groupedEvents = $this->groupEventsByCategory($events);
        $categories = $this->fetchCategories();
        $locations = $this->fetchLocations();
        $priceBounds = $this->fetchPriceBounds();

        return $this->render('event/list.html.twig', [
            'groupedEvents' => $groupedEvents,
            'categories' => $categories,
            'locations' => $locations,
            'price_bounds' => $priceBounds,
            'isAuthenticated' => $isAuthenticated,
            'sessionUser' => $sessionUser,
        ]);
    }

    #[Route('/events/{id}', name: 'event_details')]
    public function showEvent(int $id, Request $request): Response
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $sessionUser = $session->get('user');
        $isAuthenticated = is_array($sessionUser) && isset($sessionUser['id']);

        if (!$isAuthenticated) {
            $this->addFlash('error', 'Veuillez vous connecter pour consulter le détail d’un évènement.');

            return $this->redirectToRoute('login');
        }

        $event = $this->fetchEventDetails($id);

        if (null === $event) {
            throw $this->createNotFoundException('Évènement introuvable.');
        }

        $ticketTypes = $this->fetchTicketTypes($id);
        $tags = $this->fetchEventTags($id);

        $priceMin = null;
        $priceMax = null;
        if (!empty($ticketTypes)) {
            $prices = array_column($ticketTypes, 'base_price');
            $priceMin = (float) min($prices);
            $priceMax = (float) max($prices);
        } elseif (null !== $event['min_price']) {
            $priceMin = $event['min_price'];
            $priceMax = $event['max_price'];
        }

        $event['ticket_types'] = $ticketTypes;
        $event['tags'] = $tags;
        $event['price_min'] = $priceMin;
        $event['price_max'] = $priceMax;

        $similarEvents = $this->fetchSimilarEvents($event['category_slug'], $event['id']);

        return $this->render('event/details.html.twig', [
            'event' => $event,
            'similar_events' => $similarEvents,
            'sessionUser' => $sessionUser,
        ]);
    }

    #[Route('/event-detail', name: 'event_detail_static')]
    public function showEventDetailStatic(): Response
    {
        // Rend la page de détails statique (copie fidèle du HTML)
        return $this->render('event/details.html.twig');
    }

    #[Route('/api/events', name: 'api_events_list', methods: ['GET'])]
    public function listEventsApi(Request $request): JsonResponse
    {
        // TODO: Récupérer la liste des événements avec filtres
        return new JsonResponse([
            'message' => 'Liste des événements - À implémenter',
            'status' => 'success',
            'data' => []
        ]);
    }

    #[Route('/api/events/{id}', name: 'api_events_show', methods: ['GET'])]
    public function showEventApi(int $id): JsonResponse
    {
        // TODO: Récupérer les détails d'un événement
        return new JsonResponse([
            'message' => "Détails de l'événement {$id} - À implémenter",
            'status' => 'success',
            'data' => []
        ]);
    }

    #[Route('/api/events/search', name: 'api_events_search', methods: ['GET'])]
    public function searchEvents(Request $request): JsonResponse
    {
        // TODO: Rechercher des événements par critères
        $query = $request->query->get('q', '');
        $category = $request->query->get('category', '');
        $city = $request->query->get('city', '');
        $dateFrom = $request->query->get('date_from', '');
        $dateTo = $request->query->get('date_to', '');

        return new JsonResponse([
            'message' => 'Recherche d\'événements - À implémenter',
            'status' => 'success',
            'filters' => [
                'query' => $query,
                'category' => $category,
                'city' => $city,
                'date_from' => $dateFrom,
                'date_to' => $dateTo
            ],
            'data' => []
        ]);
    }

    #[Route('/api/events/{id}/tickets', name: 'api_events_tickets', methods: ['GET'])]
    public function getEventTickets(int $id): JsonResponse
    {
        // TODO: Récupérer les catégories de billets disponibles pour un événement
        return new JsonResponse([
            'message' => "Catégories de billets pour l'événement {$id} - À implémenter",
            'status' => 'success',
            'data' => []
        ]);
    }

    #[Route('/api/events/{id}/favorite', name: 'api_events_favorite', methods: ['POST'])]
    public function addToFavorites(int $id): JsonResponse
    {
        // TODO: Ajouter un événement aux favoris
        return new JsonResponse([
            'message' => "Ajout aux favoris de l'événement {$id} - À implémenter",
            'status' => 'success'
        ]);
    }

    #[Route('/api/events/{id}/favorite', name: 'api_events_unfavorite', methods: ['DELETE'])]
    public function removeFromFavorites(int $id): JsonResponse
    {
        // TODO: Retirer un événement des favoris
        return new JsonResponse([
            'message' => "Suppression des favoris de l'événement {$id} - À implémenter",
            'status' => 'success'
        ]);
    }

    #[Route('/api/events/categories', name: 'api_events_categories', methods: ['GET'])]
    public function getCategories(): JsonResponse
    {
        // TODO: Récupérer la liste des catégories d'événements
        return new JsonResponse([
            'message' => 'Liste des catégories d\'événements - À implémenter',
            'status' => 'success',
            'data' => [
                'Concert',
                'Sport',
                'Conférence',
                'Théâtre',
                'Festival',
                'Exposition',
                'Formation',
                'Autre'
            ]
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchEvents(): array
    {
        $sql = <<<SQL
            SELECT
                e.id,
                e.slug,
                e.title,
                e.subtitle,
                e.summary,
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
                'country_code' => $row['country_code'],
                'category_label' => $row['category_label'] ?? 'Événement',
                'image_url' => $row['image_url'],
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'min_price' => null !== $row['min_price'] ? (float) $row['min_price'] : null,
                'max_price' => null !== $row['max_price'] ? (float) $row['max_price'] : null,
            ];
        }, $rows);
    }

    /**
     * @param array<int, array<string, mixed>> $events
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function groupEventsByCategory(array $events): array
    {
        $grouped = [];

        foreach ($events as $event) {
            $label = $event['category_label'] ?? 'Autres';
            $grouped[$label][] = $event;
        }

        ksort($grouped, SORT_NATURAL | SORT_FLAG_CASE);

        return $grouped;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function fetchCategories(): array
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
     * @return string[]
     */
    private function fetchLocations(): array
    {
        $sql = <<<SQL
            SELECT DISTINCT city
            FROM aiolia.events
            WHERE city IS NOT NULL AND city <> ''
            ORDER BY city ASC
        SQL;

        $rows = $this->connection->executeQuery($sql)->fetchFirstColumn();

        return array_map(static fn ($city) => (string) $city, $rows);
    }

    /**
     * @return array{min: float, max: float}
     */
    private function fetchPriceBounds(): array
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
     * @return array<string, mixed>|null
     */
    private function fetchEventDetails(int $id): ?array
    {
        $sql = <<<SQL
            SELECT
                e.id,
                e.slug,
                e.title,
                e.subtitle,
                e.summary,
                e.description,
                e.venue_name,
                e.venue_address,
                e.city,
                e.region,
                e.country_code,
                e.starts_at,
                e.ends_at,
                e.capacity,
                e.language_code,
                e.latitude,
                e.longitude,
                e.timezone,
                e.created_at,
                cat.label AS category_label,
                cat.slug AS category_slug,
                media.url AS image_url,
                media.alt_text AS image_alt,
                pricing.min_price,
                pricing.max_price
            FROM aiolia.events e
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

        return [
            'id' => (int) $row['id'],
            'slug' => $row['slug'],
            'title' => $row['title'],
            'subtitle' => $row['subtitle'],
            'summary' => $row['summary'],
            'description' => $row['description'] ?? $row['summary'],
            'venue_name' => $row['venue_name'],
            'venue_address' => $row['venue_address'],
            'city' => $row['city'],
            'region' => $row['region'],
            'country_code' => $row['country_code'],
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'capacity' => isset($row['capacity']) ? (int) $row['capacity'] : null,
            'language_code' => $row['language_code'],
            'latitude' => isset($row['latitude']) ? (float) $row['latitude'] : null,
            'longitude' => isset($row['longitude']) ? (float) $row['longitude'] : null,
            'timezone' => $row['timezone'],
            'created_at' => new \DateTimeImmutable($row['created_at']),
            'category_label' => $row['category_label'] ?? 'Évènement',
            'category_slug' => $row['category_slug'],
            'image_url' => $row['image_url'],
            'image_alt' => $row['image_alt'] ?? $row['title'],
            'min_price' => null !== $row['min_price'] ? (float) $row['min_price'] : null,
            'max_price' => null !== $row['max_price'] ? (float) $row['max_price'] : null,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchTicketTypes(int $eventId): array
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
                tt.min_per_order,
                tt.max_per_order,
                inv.total_quantity,
                inv.reserved_quantity,
                inv.sold_quantity
            FROM aiolia.ticket_types tt
            LEFT JOIN aiolia.ticket_inventory inv ON inv.ticket_type_id = tt.id
            WHERE tt.event_id = :event_id
            ORDER BY tt.base_price ASC NULLS LAST, tt.name ASC
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

            return [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'description' => $row['description'],
                'currency' => $row['currency'],
                'base_price' => (float) $row['base_price'],
                'service_fee' => isset($row['service_fee']) ? (float) $row['service_fee'] : null,
                'vat_rate' => isset($row['vat_rate']) ? (float) $row['vat_rate'] : null,
                'min_per_order' => isset($row['min_per_order']) ? (int) $row['min_per_order'] : null,
                'max_per_order' => isset($row['max_per_order']) ? (int) $row['max_per_order'] : null,
                'available' => $available,
                'total_quantity' => $total,
                'sold_quantity' => $sold,
                'reserved_quantity' => $reserved,
            ];
        }, $rows);
    }

    /**
     * @return array<int, string>
     */
    private function fetchEventTags(int $eventId): array
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
     * @return array<int, array<string, mixed>>
     */
    private function fetchSimilarEvents(?string $categorySlug, int $excludeId): array
    {
        $sql = <<<SQL
            SELECT
                e.id,
                e.slug,
                e.title,
                e.venue_name,
                e.city,
                e.starts_at,
                cat.label AS category_label,
                media.url AS image_url
            FROM aiolia.events e
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
            $whereClause = ' AND cat.slug = :category_slug';
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
}
