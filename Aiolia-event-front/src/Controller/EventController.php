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

        // Récupérer les paramètres de recherche et filtres (tous optionnels)
        $query = trim((string) $request->query->get('q', ''));
        $category = trim((string) $request->query->get('category', ''));
        $city = trim((string) $request->query->get('city', ''));
        $priceMin = $request->query->get('price_min');
        $priceMax = $request->query->get('price_max');
        $dateFrom = trim((string) $request->query->get('date_from', ''));
        $dateTo = trim((string) $request->query->get('date_to', ''));
        $sortBy = $request->query->get('sort_by', 'date'); // date, price_asc, price_desc, popularity
        $sortOrder = $request->query->get('sort_order', 'asc');

        // Normaliser les valeurs vides en null
        $priceMin = !empty($priceMin) && $priceMin !== '0' ? (float) $priceMin : null;
        $priceMax = !empty($priceMax) && $priceMax !== '0' ? (float) $priceMax : null;
        $dateFrom = !empty($dateFrom) ? $dateFrom : '';
        $dateTo = !empty($dateTo) ? $dateTo : '';

        // Si on a au moins un critère de recherche ou filtre, utiliser la méthode de recherche
        // Sinon, afficher tous les événements avec le tri par défaut
        $hasFilters = !empty($query) || !empty($category) || !empty($city) || 
                     null !== $priceMin || null !== $priceMax || !empty($dateFrom) || !empty($dateTo);

        if ($hasFilters) {
            // Utiliser la méthode de recherche avec les filtres (même partiels)
            $events = $this->searchEvents($query, [
                'category' => $category,
                'city' => $city,
                'price_min' => $priceMin,
                'price_max' => $priceMax,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
            ]);
            
            // Sauvegarder l'historique de recherche si utilisateur connecté et qu'il y a une requête textuelle
            if ($isAuthenticated && !empty($query)) {
                $this->saveSearchHistory((int) $sessionUser['id'], $query, [
                    'category' => $category,
                    'city' => $city,
                    'price_min' => $priceMin,
                    'price_max' => $priceMax,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                ]);
            }
        } else {
            // Aucun filtre : afficher tous les événements avec tri par défaut
            $events = $this->fetchEvents();
            
            // Appliquer le tri même sans filtres
            if ($sortBy !== 'date' || $sortOrder !== 'asc') {
                // Trier les résultats en mémoire selon les critères sélectionnés
                usort($events, function($a, $b) use ($sortBy, $sortOrder) {
                    $direction = $sortOrder === 'desc' ? -1 : 1;
                    
                    switch ($sortBy) {
                        case 'price_asc':
                            $aPrice = $a['min_price'] ?? 0;
                            $bPrice = $b['min_price'] ?? 0;
                            return $direction * ($aPrice <=> $bPrice);
                        case 'price_desc':
                            $aPrice = $a['max_price'] ?? 0;
                            $bPrice = $b['max_price'] ?? 0;
                            return -$direction * ($aPrice <=> $bPrice);
                        case 'date':
                        default:
                            $aDate = $a['starts_at']?->getTimestamp() ?? 0;
                            $bDate = $b['starts_at']?->getTimestamp() ?? 0;
                            return $direction * ($aDate <=> $bDate);
                    }
                });
            }
        }

        // Charger les favoris de l'utilisateur si connecté
        $favoriteEventIds = [];
        if ($isAuthenticated && isset($sessionUser['id'])) {
            $favoriteEventIds = $this->fetchUserFavoriteEventIds((int) $sessionUser['id']);
            
            // Ajouter la propriété isFavorite à chaque événement
            foreach ($events as &$event) {
                $event['isFavorite'] = in_array($event['id'], $favoriteEventIds, true);
            }
            unset($event);
        }

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
            'searchQuery' => $query,
            'filters' => [
                'category' => $category,
                'city' => $city,
                'price_min' => $priceMin,
                'price_max' => $priceMax,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
            ],
        ]);
    }

    #[Route('/events/{id}/favorite', name: 'api_events_favorite', methods: ['POST'])]
    public function addToFavorites(int $id, Request $request): JsonResponse
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $sessionUser = $session->get('user');
        if (!is_array($sessionUser) || !isset($sessionUser['id'])) {
            $this->logger->warning('Tentative d\'ajout aux favoris sans authentification', ['event_id' => $id]);
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Vous devez être connecté pour ajouter aux favoris'
            ], 401);
        }

        $userId = (int) $sessionUser['id'];
        $this->logger->debug('Ajout aux favoris', ['event_id' => $id, 'user_id' => $userId]);

        try {
            // Vérifier si l'événement existe
            $eventExists = $this->connection->executeQuery(
                'SELECT id FROM aiolia.events WHERE id = :id',
                ['id' => $id]
            )->fetchOne();

            if (!$eventExists) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Événement introuvable'
                ], 404);
            }

            // Récupérer ou créer la wishlist par défaut
            $wishlistId = $this->connection->executeQuery(
                'SELECT id FROM aiolia.wishlists WHERE user_id = :userId AND is_default = TRUE LIMIT 1',
                ['userId' => $userId]
            )->fetchOne();

            if (!$wishlistId) {
                // Créer la wishlist par défaut
                $this->connection->executeStatement(
                    'INSERT INTO aiolia.wishlists (user_id, title, is_default, created_at) VALUES (:userId, :title, TRUE, NOW())',
                    ['userId' => $userId, 'title' => 'Favoris']
                );
                // Récupérer l'ID de la wishlist créée
                $wishlistId = $this->connection->executeQuery(
                    'SELECT id FROM aiolia.wishlists WHERE user_id = :userId AND is_default = TRUE LIMIT 1',
                    ['userId' => $userId]
                )->fetchOne();
            }

            // Vérifier si l'événement est déjà dans les favoris
            $exists = $this->connection->executeQuery(
                'SELECT 1 FROM aiolia.wishlist_items WHERE wishlist_id = :wishlistId AND event_id = :eventId',
                ['wishlistId' => $wishlistId, 'eventId' => $id]
            )->fetchOne();

            if ($exists) {
                return new JsonResponse([
                    'status' => 'success',
                    'message' => 'Événement déjà dans les favoris'
                ]);
            }

            // Ajouter l'événement aux favoris
            $this->connection->executeStatement(
                'INSERT INTO aiolia.wishlist_items (wishlist_id, event_id, added_at) VALUES (:wishlistId, :eventId, NOW())',
                ['wishlistId' => $wishlistId, 'eventId' => $id]
            );

            $this->logger->info('Événement ajouté aux favoris', ['event_id' => $id, 'user_id' => $userId, 'wishlist_id' => $wishlistId]);

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Événement ajouté aux favoris'
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'ajout aux favoris', [
                'event_id' => $id,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return new JsonResponse([
                'status' => 'error',
                'message' => 'Erreur lors de l\'ajout aux favoris'
            ], 500);
        }
    }

    #[Route('/events/{id}/favorite', name: 'api_events_unfavorite', methods: ['DELETE'])]
    public function removeFromFavorites(int $id, Request $request): JsonResponse
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $sessionUser = $session->get('user');
        if (!is_array($sessionUser) || !isset($sessionUser['id'])) {
            $this->logger->warning('Tentative de retrait des favoris sans authentification', ['event_id' => $id]);
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Vous devez être connecté pour retirer des favoris'
            ], 401);
        }

        $userId = (int) $sessionUser['id'];
        $this->logger->debug('Retrait des favoris', ['event_id' => $id, 'user_id' => $userId]);

        try {
            // Récupérer la wishlist par défaut
            $wishlistId = $this->connection->executeQuery(
                'SELECT id FROM aiolia.wishlists WHERE user_id = :userId AND is_default = TRUE LIMIT 1',
                ['userId' => $userId]
            )->fetchOne();

            if (!$wishlistId) {
                return new JsonResponse([
                    'status' => 'success',
                    'message' => 'Événement retiré des favoris'
                ]);
            }

            // Retirer l'événement des favoris
            $deleted = $this->connection->executeStatement(
                'DELETE FROM aiolia.wishlist_items WHERE wishlist_id = :wishlistId AND event_id = :eventId',
                ['wishlistId' => $wishlistId, 'eventId' => $id]
            );

            $this->logger->info('Événement retiré des favoris', ['event_id' => $id, 'user_id' => $userId, 'wishlist_id' => $wishlistId, 'deleted' => $deleted]);

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Événement retiré des favoris'
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du retrait des favoris', [
                'event_id' => $id,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return new JsonResponse([
                'status' => 'error',
                'message' => 'Erreur lors du retrait des favoris'
            ], 500);
        }
    }

    #[Route('/events/{id}', name: 'event_details', methods: ['GET'])]
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

        $rawTicketTypes = $this->fetchTicketTypes($id);
        $ticketTypes = array_values(array_filter($rawTicketTypes, static function (array $ticket): bool {
            if (!array_key_exists('is_available', $ticket)) {
                return true;
            }

            return true === $ticket['is_available'];
        }));
        
        // Grouper les types de billets par nom pour gérer VIP/Gold/Silver avec prix adultes/enfants
        $groupedTicketTypes = $this->groupTicketTypesByName($ticketTypes);
        
        // Détecter si l'événement a des billets adultes ET enfants disponibles (pour activer les deux inputs)
        $hasAnyAdultTickets = false;
        $hasAnyChildTickets = false;
        $hasOnlyAllCategory = true; // Vérifier si tous les billets sont de type 'all' (pas de distinction adulte/enfant)
        
        foreach ($ticketTypes as $ticket) {
            $ageCategory = $ticket['age_category'] ?? 'all';
            
            if ($ageCategory === 'adult' || $ageCategory === 'all') {
                $hasAnyAdultTickets = true;
            }
            if ($ageCategory === 'child' || $ageCategory === 'all') {
                $hasAnyChildTickets = true;
            }
            
            // Si on trouve un billet qui n'est pas de type 'all', alors on n'a pas seulement des billets génériques
            if ($ageCategory !== 'all') {
                $hasOnlyAllCategory = false;
            }
        }
        
        // Si on a des billets avec 'adult' OU 'child' séparés, ce n'est pas seulement 'all'
        if ($hasAnyAdultTickets && $hasAnyChildTickets) {
            // Vérifier si on a des billets séparés (adult/child) en plus de 'all'
            $hasSeparateAdultChild = false;
            foreach ($ticketTypes as $ticket) {
                $ageCategory = $ticket['age_category'] ?? 'all';
                if ($ageCategory === 'adult' || $ageCategory === 'child') {
                    $hasSeparateAdultChild = true;
                    $hasOnlyAllCategory = false;
                    break;
                }
            }
        }
        
        // Créer un mapping pour trouver les IDs adultes/enfants pour chaque type groupé
        // Cela permet de gérer les cas où "Billet Adulte" et "Billet Enfant" sont séparés
        // Le mapping contient toutes les infos nécessaires (id, base_price, available, currency)
        $eventTicketMapping = [
            'adult_ticket_ids' => [],
            'child_ticket_ids' => [],
            'all_ticket_ids' => []
        ];
        
        foreach ($ticketTypes as $ticket) {
            $ticketData = [
                'id' => $ticket['id'],
                'base_price' => $ticket['base_price'],
                'available' => $ticket['available'],
                'currency' => $ticket['currency'] ?? 'MGA',
                'name' => $ticket['name']
            ];
            
            if ($ticket['age_category'] === 'adult') {
                $eventTicketMapping['adult_ticket_ids'][(string)$ticket['id']] = $ticketData;
            } elseif ($ticket['age_category'] === 'child') {
                $eventTicketMapping['child_ticket_ids'][(string)$ticket['id']] = $ticketData;
            } elseif ($ticket['age_category'] === 'all') {
                $eventTicketMapping['all_ticket_ids'][(string)$ticket['id']] = $ticketData;
            }
        }
        
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
        $event['ticket_types_grouped'] = $groupedTicketTypes;
        $event['ticket_types_all'] = $rawTicketTypes;
        $event['has_adult_tickets'] = $hasAnyAdultTickets;
        $event['has_child_tickets'] = $hasAnyChildTickets;
        $event['has_only_all_category'] = $hasOnlyAllCategory; // Indique si on n'a que des billets sans distinction adulte/enfant
        $event['ticket_mapping'] = $eventTicketMapping;
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
    public function searchEventsApi(Request $request): JsonResponse
    {
        $query = trim((string) $request->query->get('q', ''));
        $category = trim((string) $request->query->get('category', ''));
        $city = trim((string) $request->query->get('city', ''));
        $priceMin = $request->query->get('price_min');
        $priceMax = $request->query->get('price_max');
        $dateFrom = $request->query->get('date_from', '');
        $dateTo = $request->query->get('date_to', '');
        $sortBy = $request->query->get('sort_by', 'date');
        $sortOrder = $request->query->get('sort_order', 'asc');

        $events = $this->searchEvents($query, [
            'category' => $category,
            'city' => $city,
            'price_min' => $priceMin ? (float) $priceMin : null,
            'price_max' => $priceMax ? (float) $priceMax : null,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder,
        ]);

        return new JsonResponse([
            'status' => 'success',
            'count' => count($events),
            'data' => $events,
            'filters' => [
                'query' => $query,
                'category' => $category,
                'city' => $city,
                'price_min' => $priceMin,
                'price_max' => $priceMax,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    /**
     * Recherche d'événements avec filtres
     *
     * @param string $query
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    private function searchEvents(string $query = '', array $filters = []): array
    {
        $category = $filters['category'] ?? '';
        $city = $filters['city'] ?? '';
        $priceMin = $filters['price_min'] ?? null;
        $priceMax = $filters['price_max'] ?? null;
        $dateFrom = $filters['date_from'] ?? '';
        $dateTo = $filters['date_to'] ?? '';
        $sortBy = $filters['sort_by'] ?? 'date';
        $sortOrder = $filters['sort_order'] ?? 'asc';

        // Construire la requête SQL avec filtres
        // Définir les paramètres de recherche même si vides pour éviter les erreurs SQL
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
                -- Score de pertinence pour la recherche textuelle (0 si pas de requête)
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

        // Recherche textuelle - ajouter condition WHERE seulement si requête non vide
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

        // Filtre par catégorie - vérifier primary_category_id ET event_category_links
        if (!empty($category)) {
            // Utiliser EXISTS pour vérifier si l'événement appartient à la catégorie via primary_category_id OU event_category_links
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

        // Filtre par date - conversion du format dd/mm/yyyy vers YYYY-MM-DD si nécessaire
        if (!empty($dateFrom)) {
            // Si la date est au format dd/mm/yyyy, la convertir
            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $dateFrom, $matches)) {
                $dateFrom = $matches[3] . '-' . $matches[2] . '-' . $matches[1];
            }
            // Ajouter l'heure à minuit si seulement la date est fournie
            if (strlen($dateFrom) === 10) {
                $dateFrom .= ' 00:00:00';
            }
            $where[] = "e.starts_at >= :date_from::timestamptz";
            $params['date_from'] = $dateFrom;
            $types['date_from'] = \PDO::PARAM_STR;
        }
        if (!empty($dateTo)) {
            // Si la date est au format dd/mm/yyyy, la convertir
            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $dateTo, $matches)) {
                $dateTo = $matches[3] . '-' . $matches[2] . '-' . $matches[1];
            }
            // Ajouter l'heure à 23:59:59 si seulement la date est fournie
            if (strlen($dateTo) === 10) {
                $dateTo .= ' 23:59:59';
            }
            $where[] = "e.ends_at <= :date_to::timestamptz";
            $params['date_to'] = $dateTo;
            $types['date_to'] = \PDO::PARAM_STR;
        }

        $sql .= ' WHERE ' . implode(' AND ', $where);

        // Tri
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
                // TODO: Ajouter un compteur de vues ou de billets vendus
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
            // Log pour debug
            $this->logger->debug('Recherche d\'événements', [
                'query' => $query,
                'filters' => $filters,
                'sql' => $sql,
                'where' => $where,
                'params' => $params,
            ]);

            $rows = $this->connection->executeQuery($sql, $params, $types)->fetchAllAssociative();

            $this->logger->debug('Résultats de recherche', [
                'count' => count($rows),
                'first_row' => $rows[0] ?? null,
            ]);

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
            $this->logger->error('Erreur lors de la recherche d\'événements', [
                'error' => $e->getMessage(),
                'query' => $query,
                'filters' => $filters,
            ]);
            return [];
        }
    }

    /**
     * Sauvegarde l'historique de recherche
     */
    private function saveSearchHistory(int $userId, string $keywords, array $filters = []): void
    {
        try {
            $this->connection->insert('aiolia.user_search_history', [
                'user_id' => $userId,
                'keywords' => $keywords,
                'filters' => json_encode($filters, JSON_THROW_ON_ERROR),
                'searched_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la sauvegarde de l\'historique de recherche', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'keywords' => $keywords,
            ]);
        }
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
     * Groupe les types de billets par nom pour gérer les types VIP/Gold/Silver
     * avec des prix différents pour adultes et enfants.
     *
     * @param array<int, array<string, mixed>> $ticketTypes
     * @return array<string, array<string, mixed>>
     */
    private function groupTicketTypesByName(array $ticketTypes): array
    {
        if (empty($ticketTypes)) {
            return [];
        }

        // Détecter si les billets ont des types spécifiques (VIP, Gold, Silver, etc.)
        // ou s'ils sont génériques (Billet Adulte, Billet Enfant, Standard, etc.)
        $hasSpecificTypes = false;
        $genericNames = ['Billet Adulte', 'Billet Enfant', 'Billet', 'Standard', 'General', 'Général', 'Adulte', 'Enfant'];
        
        foreach ($ticketTypes as $ticket) {
            $name = $ticket['name'];
            // Si le nom n'est pas dans la liste des noms génériques, c'est un type spécifique
            if (!in_array($name, $genericNames, true)) {
                $hasSpecificTypes = true;
                break;
            }
        }

        // Si pas de types spécifiques, créer un seul groupe combiné
        if (!$hasSpecificTypes) {
            $grouped = [];
            $firstTicket = $ticketTypes[0];
            
            // Créer un groupe unique avec un nom générique
            $groupName = 'Billet';
            $grouped[$groupName] = [
                'name' => $groupName,
                'description' => $firstTicket['description'] ?? '',
                'currency' => $firstTicket['currency'] ?? 'MGA',
                'adult' => null,
                'child' => null,
                'all' => null,
            ];

            // Assigner tous les billets selon leur catégorie d'âge
            foreach ($ticketTypes as $ticket) {
                $ageCategory = $ticket['age_category'] ?? 'all';
                
                if ($ageCategory === 'adult') {
                    $grouped[$groupName]['adult'] = $ticket;
                } elseif ($ageCategory === 'child') {
                    $grouped[$groupName]['child'] = $ticket;
                } elseif ($ageCategory === 'all') {
                    $grouped[$groupName]['all'] = $ticket;
                }
            }

            return $grouped;
        }

        // Sinon, grouper normalement par nom
        $grouped = [];

        foreach ($ticketTypes as $ticket) {
            $name = $ticket['name'];
            
            if (!isset($grouped[$name])) {
                $grouped[$name] = [
                    'name' => $name,
                    'description' => $ticket['description'],
                    'currency' => $ticket['currency'],
                    'adult' => null,
                    'child' => null,
                    'all' => null, // Pour les types avec age_category='all'
                ];
            }

            // Assigner selon la catégorie d'âge
            $ageCategory = $ticket['age_category'] ?? 'all';
            
            if ($ageCategory === 'adult') {
                $grouped[$name]['adult'] = $ticket;
            } elseif ($ageCategory === 'child') {
                $grouped[$name]['child'] = $ticket;
            } elseif ($ageCategory === 'all') {
                $grouped[$name]['all'] = $ticket;
            }
        }

        return $grouped;
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
     * @return int[]
     */
    private function fetchUserFavoriteEventIds(int $userId): array
    {
        // Vérifier si une wishlist par défaut existe, sinon retourner un tableau vide
        $checkSql = <<<SQL
            SELECT id FROM aiolia.wishlists
            WHERE user_id = :userId AND is_default = TRUE
            LIMIT 1
        SQL;
        
        $wishlistId = $this->connection->executeQuery($checkSql, ['userId' => $userId])->fetchOne();
        
        if (!$wishlistId) {
            return [];
        }
        
        $sql = <<<SQL
            SELECT event_id
            FROM aiolia.wishlist_items
            WHERE wishlist_id = :wishlistId
        SQL;

        return $this->connection->executeQuery($sql, ['wishlistId' => $wishlistId])->fetchFirstColumn();
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
}
