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
    public function showEvent(int $id): Response
    {
        // TODO: Récupérer l'événement depuis la base de données
        $event = [
            'id' => $id,
            'title' => 'Music on Sunday',
            'description' => '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p><p>Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>',
            'category' => ['name' => 'Soirée live'],
            'startDate' => new \DateTime('2025-07-20 20:00:00'),
            'location' => 'Analakely au Café de la Gare',
            'minPrice' => 50000,
            'maxPrice' => 150000,
            'image' => null,
            'organizer' => ['firstName' => 'Jean', 'lastName' => 'Dupont'],
            'ticketCategories' => [
                [
                    'id' => 1, 
                    'name' => 'Offre VIP', 
                    'price' => 150000, 
                    'description' => 'Accès VIP avec boissons incluses',
                    'availableTickets' => 50
                ],
                [
                    'id' => 2, 
                    'name' => 'Standard', 
                    'price' => 50000, 
                    'description' => 'Accès standard',
                    'availableTickets' => 200
                ]
            ],
            'totalTicketsSold' => 75,
            'maxCapacity' => 250
        ];

        return $this->render('event/details.html.twig', [
            'event' => $event,
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
}
