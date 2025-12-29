<?php

namespace App\Controller;

use App\Repository\EventRepository;
use App\Repository\SearchHistoryRepository;
use App\Repository\WishlistRepository;
use App\Service\ActivityService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EventController extends AbstractController
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly EventRepository $eventRepository,
        private readonly WishlistRepository $wishlistRepository,
        private readonly SearchHistoryRepository $searchHistoryRepository,
        private readonly ActivityService $activityService
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
            $events = $this->eventRepository->searchEventsWithFilters($query, [
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
                $this->searchHistoryRepository->saveSearch((int) $sessionUser['id'], $query, [
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
            $events = $this->eventRepository->findAllPublishedEvents();
            
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
            $favoriteEventIds = $this->wishlistRepository->findUserFavoriteEventIds((int) $sessionUser['id']);
            
            // Ajouter la propriété isFavorite à chaque événement
            foreach ($events as &$event) {
                $event['isFavorite'] = in_array($event['id'], $favoriteEventIds, true);
            }
            unset($event);
        }

        $groupedEvents = $this->groupEventsByCategory($events);
        $categories = $this->eventRepository->findAllCategories();
        $locations = $this->eventRepository->findAllCities();
        $priceBounds = $this->eventRepository->findPriceBounds();

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
            if (!$this->eventRepository->eventExists($id)) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Événement introuvable'
                ], 404);
            }

            // Vérifier si l'événement est déjà dans les favoris
            if ($this->wishlistRepository->isEventInWishlist($userId, $id)) {
                return new JsonResponse([
                    'status' => 'success',
                    'message' => 'Événement déjà dans les favoris'
                ]);
            }

            // Ajouter l'événement aux favoris
            $this->wishlistRepository->addEventToWishlist($userId, $id);

            $this->logger->info('Événement ajouté aux favoris', ['event_id' => $id, 'user_id' => $userId]);

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
            // Retirer l'événement des favoris
            $this->wishlistRepository->removeEventFromWishlist($userId, $id);

            // Logger l'activité de suppression
            $this->activityService->logFavoriteRemoval($userId, $id);

            $this->logger->info('Événement retiré des favoris', ['event_id' => $id, 'user_id' => $userId]);

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

        $event = $this->eventRepository->findEventDetailsById($id);

        if (null === $event) {
            throw $this->createNotFoundException('Évènement introuvable.');
        }

        // Accessibilité de l'événement
        $eventAccessibility = $this->eventRepository->findEventAccessibility($id);

        $rawTicketTypes = $this->eventRepository->findTicketTypesByEventId($id);
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
        
        $tags = $this->eventRepository->findEventTags($id);

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
        $event['accessibility'] = $eventAccessibility;

        $similarEvents = $this->eventRepository->findSimilarEvents($event['category_slug'], $event['id']);

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

}
