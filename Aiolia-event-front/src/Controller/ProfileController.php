<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProfileController extends AbstractController
{
    public function __construct(
        private readonly Connection $connection,
        private readonly TranslatorInterface $translator
    ) {
    }

    #[Route('/profile', name: 'profile_index')]
    public function index(Request $request): Response
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $sessionUser = $session->get('user');
        $isAuthenticated = is_array($sessionUser) && isset($sessionUser['id']);

        if (!$isAuthenticated) {
            return $this->redirectToRoute('login');
        }

        $userId = (int) $sessionUser['id'];

        // Récupérer les informations utilisateur
        $userInfo = $this->fetchUserInfo($userId);
        
        // Récupérer les statistiques (inclure le panier en session)
        $sessionCartItems = $session->get('cart_items', []);
        $stats = $this->fetchUserStats($userId, $sessionCartItems);
        
        // Récupérer les activités récentes (inclure le panier en session)
        $recentActivities = $this->fetchRecentActivities($userId, $sessionCartItems);

        return $this->render('profile/index.html.twig', [
            'user' => $userInfo,
            'stats' => $stats,
            'activities' => $recentActivities,
            'isAuthenticated' => $isAuthenticated,
        ]);
    }

    #[Route('/profile/history', name: 'profile_history')]
    public function history(Request $request): Response
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $sessionUser = $session->get('user');
        $isAuthenticated = is_array($sessionUser) && isset($sessionUser['id']);

        if (!$isAuthenticated) {
            return $this->redirectToRoute('login');
        }

        $userId = (int) $sessionUser['id'];
        
        // Récupérer les commandes de l'utilisateur
        $orders = $this->fetchUserOrders($userId);
        
        // Calculer les statistiques
        $stats = $this->calculatePurchaseStats($userId, $orders);

        return $this->render('profile/history.html.twig', [
            'orders' => $orders,
            'stats' => $stats,
        ]);
    }

    #[Route('/profile/wallet', name: 'profile_wallet')]
    public function wallet(): Response
    {
        return $this->render('profile/wallet.html.twig');
    }

    #[Route('/profile/favorites', name: 'profile_favorites')]
    public function favorites(Request $request): Response
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $sessionUser = $session->get('user');
        $isAuthenticated = is_array($sessionUser) && isset($sessionUser['id']);

        if (!$isAuthenticated) {
            return $this->redirectToRoute('login');
        }

        $userId = (int) $sessionUser['id'];
        $favoriteEvents = $this->fetchUserFavoriteEvents($userId);

        return $this->render('profile/favorites.html.twig', [
            'favorites' => $favoriteEvents,
            'isAuthenticated' => $isAuthenticated,
        ]);
    }

    #[Route('/profile/search-history', name: 'profile_search_history')]
    public function searchHistory(Request $request): Response
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $sessionUser = $session->get('user');
        $isAuthenticated = is_array($sessionUser) && isset($sessionUser['id']);

        if (!$isAuthenticated) {
            return $this->redirectToRoute('login');
        }

        $userId = (int) $sessionUser['id'];

        // Récupérer les paramètres de filtrage et tri
        $searchQuery = trim((string) $request->query->get('q', ''));
        $sortBy = $request->query->get('sort', 'newest'); // newest, oldest, custom
        $dateFrom = trim((string) $request->query->get('date_from', ''));
        $dateTo = trim((string) $request->query->get('date_to', ''));

        // Récupérer l'historique de recherche
        $searchHistory = $this->fetchUserSearchHistory($userId, $searchQuery, $sortBy, $dateFrom, $dateTo);

        // Compter le nombre de résultats pour chaque recherche
        $searchHistoryWithResults = $this->countResultsForSearches($searchHistory);

        return $this->render('profile/search_history.html.twig', [
            'searches' => $searchHistoryWithResults,
            'isAuthenticated' => $isAuthenticated,
            'currentSearchQuery' => $searchQuery,
            'currentSort' => $sortBy,
            'currentDateFrom' => $dateFrom,
            'currentDateTo' => $dateTo,
        ]);
    }

    #[Route('/profile/search-history/{id}/delete', name: 'profile_search_history_delete', methods: ['DELETE'])]
    public function deleteSearchHistoryItem(int $id, Request $request): JsonResponse
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $sessionUser = $session->get('user');
        if (!is_array($sessionUser) || !isset($sessionUser['id'])) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Vous devez être connecté'
            ], 401);
        }

        $userId = (int) $sessionUser['id'];

        // Vérifier que l'élément appartient à l'utilisateur
        $exists = $this->connection->executeQuery(
            'SELECT id FROM aiolia.user_search_history WHERE id = :id AND user_id = :userId',
            ['id' => $id, 'userId' => $userId]
        )->fetchOne();

        if (!$exists) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Élément introuvable'
            ], 404);
        }

        // Supprimer l'élément
        $this->connection->executeStatement(
            'DELETE FROM aiolia.user_search_history WHERE id = :id AND user_id = :userId',
            ['id' => $id, 'userId' => $userId]
        );

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Recherche supprimée de l\'historique'
        ]);
    }

    #[Route('/profile/search-history/clear', name: 'profile_search_history_clear', methods: ['DELETE'])]
    public function clearSearchHistory(Request $request): JsonResponse
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $sessionUser = $session->get('user');
        if (!is_array($sessionUser) || !isset($sessionUser['id'])) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Vous devez être connecté'
            ], 401);
        }

        $userId = (int) $sessionUser['id'];

        // Supprimer tout l'historique de l'utilisateur
        $this->connection->executeStatement(
            'DELETE FROM aiolia.user_search_history WHERE user_id = :userId',
            ['userId' => $userId]
        );

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Historique de recherche effacé'
        ]);
    }

    /**
     * Récupère l'historique de recherche de l'utilisateur avec filtres et tri
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchUserSearchHistory(int $userId, string $searchQuery = '', string $sortBy = 'newest', string $dateFrom = '', string $dateTo = ''): array
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
     * Compte le nombre de résultats pour chaque recherche en utilisant la méthode searchEvents d'EventController
     * Note: On utilise une approche simplifiée en comptant directement depuis la base de données
     *
     * @param array<int, array<string, mixed>> $searches
     * @return array<int, array<string, mixed>>
     */
    private function countResultsForSearches(array $searches): array
    {
        // Pour chaque recherche, on va compter les résultats en utilisant une requête similaire à searchEvents
        foreach ($searches as &$search) {
            $query = $search['query'];
            $filters = $search['filters'] ?? [];

            $category = $filters['category'] ?? '';
            $city = $filters['city'] ?? '';
            $priceMin = $filters['price_min'] ?? null;
            $priceMax = $filters['price_max'] ?? null;
            $dateFrom = $filters['date_from'] ?? '';
            $dateTo = $filters['date_to'] ?? '';

            // Construire une requête SQL simplifiée pour compter les résultats
            $count = $this->countSearchResults($query, $category, $city, $priceMin, $priceMax, $dateFrom, $dateTo);
            $search['results'] = $count;
        }
        unset($search);

        return $searches;
    }

    /**
     * Compte le nombre de résultats pour une recherche donnée
     */
    private function countSearchResults(string $query = '', string $category = '', string $city = '', ?float $priceMin = null, ?float $priceMax = null, string $dateFrom = '', string $dateTo = ''): int
    {
        $exactQuery = $query;
        $startQuery = $query . '%';
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
            $count = (int) $this->connection->executeQuery($sql, $params, $types)->fetchOne();
            return $count;
        } catch (\Exception $e) {
            return 0;
        }
    }

    #[Route('/profile/calendar', name: 'profile_calendar')]
    public function calendar(): Response
    {
        return $this->render('profile/calendar.html.twig');
    }

    #[Route('/profile/stats', name: 'profile_stats')]
    public function stats(Request $request): Response
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $sessionUser = $session->get('user');
        $isAuthenticated = is_array($sessionUser) && isset($sessionUser['id']);

        if (!$isAuthenticated) {
            return $this->redirectToRoute('login');
        }

        $userId = (int) $sessionUser['id'];
        
        // Récupérer les statistiques
        $stats = $this->fetchUserStatistics($userId);
        
        // Récupérer les dépenses mensuelles
        $monthlyExpenses = $this->fetchMonthlyExpenses($userId);
        
        // Calculer la valeur maximale pour le graphique
        $maxExpense = 0;
        if (!empty($monthlyExpenses)) {
            $maxExpense = max(array_column($monthlyExpenses, 'total_raw'));
        }
        
        // Récupérer la répartition par type d'événement
        $eventTypeDistribution = $this->fetchEventTypeDistribution($userId);

        return $this->render('profile/stats.html.twig', [
            'stats' => $stats,
            'monthlyExpenses' => $monthlyExpenses,
            'maxExpense' => $maxExpense,
            'eventTypeDistribution' => $eventTypeDistribution,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchUserFavoriteEvents(int $userId): array
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
            // Logger pour débogage
            error_log('Nombre de favoris récupérés: ' . count($rows) . ' pour userId: ' . $userId);
        } catch (\Exception $e) {
            // En cas d'erreur, logger et retourner un tableau vide
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

    #[Route('/profile/settings', name: 'profile_settings')]
    public function settings(Request $request): Response
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $sessionUser = $session->get('user');
        $isAuthenticated = is_array($sessionUser) && isset($sessionUser['id']);

        if (!$isAuthenticated) {
            return $this->redirectToRoute('login');
        }

        $userId = (int) $sessionUser['id'];

        // Récupérer les informations utilisateur
        $userInfo = $this->fetchUserInfo($userId);
        
        // Récupérer les préférences utilisateur
        $preferences = $this->fetchUserPreferences($userId);

        return $this->render('profile/settings.html.twig', [
            'user' => $userInfo,
            'preferences' => $preferences,
            'isAuthenticated' => $isAuthenticated,
        ]);
    }

    #[Route('/profile/settings/update', name: 'profile_settings_update', methods: ['POST'])]
    public function updateSettings(Request $request): JsonResponse
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $sessionUser = $session->get('user');
        if (!is_array($sessionUser) || !isset($sessionUser['id'])) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Vous devez être connecté'
            ], 401);
        }

        $userId = (int) $sessionUser['id'];
        $data = json_decode($request->getContent(), true);

        try {
            // Mettre à jour les informations personnelles
            if (isset($data['personal_info'])) {
                $personalInfo = $data['personal_info'];
                $updateFields = [];
                $params = ['userId' => $userId];

                if (isset($personalInfo['first_name'])) {
                    $updateFields[] = 'first_name = :first_name';
                    $params['first_name'] = $personalInfo['first_name'];
                }
                if (isset($personalInfo['last_name'])) {
                    $updateFields[] = 'last_name = :last_name';
                    $params['last_name'] = $personalInfo['last_name'];
                }
                if (isset($personalInfo['phone'])) {
                    $updateFields[] = 'phone = :phone';
                    $params['phone'] = $personalInfo['phone'];
                }
                if (isset($personalInfo['language_code'])) {
                    $updateFields[] = 'language_code = :language_code';
                    $params['language_code'] = $personalInfo['language_code'];
                }

                if (!empty($updateFields)) {
                    $sql = 'UPDATE aiolia.users SET ' . implode(', ', $updateFields) . ' WHERE id = :userId';
                    $this->connection->executeStatement($sql, $params);
                }
            }

            // Mettre à jour les préférences
            if (isset($data['preferences'])) {
                foreach ($data['preferences'] as $key => $value) {
                    $this->connection->executeStatement(
                        'INSERT INTO aiolia.user_preferences (user_id, preference_key, preference_value, updated_at)
                         VALUES (:userId, :key, :value::jsonb, NOW())
                         ON CONFLICT (user_id, preference_key)
                         DO UPDATE SET preference_value = :value::jsonb, updated_at = NOW()',
                        [
                            'userId' => $userId,
                            'key' => $key,
                            'value' => json_encode($value, JSON_THROW_ON_ERROR),
                        ]
                    );
                }
            }

            return new JsonResponse([
                'status' => 'success',
                'message' => $this->translator->trans('settings.update_success')
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupère les informations utilisateur
     *
     * @return array<string, mixed>
     */
    private function fetchUserInfo(int $userId): array
    {
        $sql = <<<SQL
            SELECT 
                id,
                email,
                first_name,
                last_name,
                phone,
                language_code,
                timezone,
                avatar_url,
                password_hash,
                created_at
            FROM aiolia.users
            WHERE id = :userId
        SQL;

        $row = $this->connection->executeQuery($sql, ['userId' => $userId])->fetchAssociative();

        if (false === $row) {
            return [];
        }

        // Formater le nom complet
        $fullName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));

        // Formater la date de création
        $createdAt = isset($row['created_at']) ? new \DateTimeImmutable($row['created_at']) : null;
        $createdAtFormatted = $createdAt ? $createdAt->format('d M Y') : '';

        // Récupérer la date de dernière modification du mot de passe (si disponible)
        // Note: On ne peut pas vraiment connaître la date de modification du mot de passe
        // sans un champ dédié, donc on utilisera la date de création comme approximation
        $passwordLastModified = $createdAtFormatted;

        return [
            'id' => (int) $row['id'],
            'email' => $row['email'],
            'first_name' => $row['first_name'] ?? '',
            'last_name' => $row['last_name'] ?? '',
            'full_name' => $fullName,
            'phone' => $row['phone'] ?? '',
            'language_code' => $row['language_code'] ?? 'fr-FR',
            'timezone' => $row['timezone'] ?? 'Indian/Antananarivo',
            'avatar_url' => $row['avatar_url'],
            'password_last_modified' => $passwordLastModified,
            'created_at' => $createdAt,
        ];
    }

    /**
     * Récupère les préférences utilisateur
     *
     * @return array<string, mixed>
     */
    private function fetchUserPreferences(int $userId): array
    {
        $sql = <<<SQL
            SELECT preference_key, preference_value
            FROM aiolia.user_preferences
            WHERE user_id = :userId
        SQL;

        $rows = $this->connection->executeQuery($sql, ['userId' => $userId])->fetchAllAssociative();

        $preferences = [
            'notifications' => [
                'ticket_alerts' => true,
                'event_reminders' => true,
                'newsletters' => false,
            ],
            'security' => [
                'two_factor_enabled' => false,
            ],
            'appearance' => [
                'theme' => 'light',
            ],
        ];

        foreach ($rows as $row) {
            $key = $row['preference_key'];
            $value = json_decode($row['preference_value'], true);
            
            if ($key === 'notifications') {
                $preferences['notifications'] = array_merge($preferences['notifications'], $value ?? []);
            } elseif ($key === 'security') {
                $preferences['security'] = array_merge($preferences['security'], $value ?? []);
            } elseif ($key === 'appearance') {
                $preferences['appearance'] = array_merge($preferences['appearance'], $value ?? []);
            } else {
                $preferences[$key] = $value;
            }
        }

        return $preferences;
    }

    #[Route('/profile/financial-history', name: 'profile_financial')]
    public function financialHistory(Request $request): Response
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $sessionUser = $session->get('user');
        $isAuthenticated = is_array($sessionUser) && isset($sessionUser['id']);

        if (!$isAuthenticated) {
            return $this->redirectToRoute('login');
        }

        $userId = (int) $sessionUser['id'];
        
        // Récupérer l'historique financier
        $financialData = $this->fetchFinancialHistory($userId);
        
        // Récupérer les dépenses mensuelles
        $monthly = $this->fetchMonthlyFinancialData($userId);
        
        // Récupérer la répartition des méthodes de paiement
        $paymentMethods = $this->fetchPaymentMethodDistribution($userId);

        return $this->render('profile/financial.html.twig', [
            'financialData' => $financialData,
            'monthly' => $monthly,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    #[Route('/profile/ticket-chance', name: 'profile_ticket_chance')]
    public function ticketChance(): Response
    {
        return $this->render('profile/ticket_chance.html.twig');
    }

    /**
     * Récupère les statistiques de l'utilisateur
     *
     * @param array<string, mixed> $sessionCartItems
     * @return array<string, mixed>
     */
    private function fetchUserStats(int $userId, array $sessionCartItems = []): array
    {
        // Compter les billets actifs (tickets valides pour des événements futurs)
        $activeTickets = (int) $this->connection->executeQuery(
            'SELECT COUNT(DISTINCT t.id)
             FROM aiolia.tickets t
             INNER JOIN aiolia.orders o ON o.id = t.order_id
             INNER JOIN aiolia.events e ON e.id = t.event_id
             WHERE o.user_id = :userId
               AND t.status = \'valid\'
               AND e.starts_at > NOW()',
            ['userId' => $userId]
        )->fetchOne();

        // Compter les événements favoris
        $wishlistId = $this->connection->executeQuery(
            'SELECT id FROM aiolia.wishlists WHERE user_id = :userId AND is_default = TRUE LIMIT 1',
            ['userId' => $userId]
        )->fetchOne();
        
        $favoriteEvents = 0;
        if ($wishlistId) {
            $favoriteEvents = (int) $this->connection->executeQuery(
                'SELECT COUNT(*) FROM aiolia.wishlist_items WHERE wishlist_id = :wishlistId',
                ['wishlistId' => $wishlistId]
            )->fetchOne();
        }

        // Compter les items dans le panier actif (DB)
        $dbCartItems = (int) $this->connection->executeQuery(
            'SELECT COUNT(DISTINCT ci.event_id)
             FROM aiolia.cart_items ci
             INNER JOIN aiolia.carts c ON c.id = ci.cart_id
             WHERE c.user_id = :userId
               AND c.status = \'active\'',
            ['userId' => $userId]
        )->fetchOne();
        
        // Compter les items dans le panier en session
        $sessionCartCount = count($sessionCartItems);
        
        // Prendre le maximum entre DB et session (pour éviter les doublons, on prend le plus grand)
        $cartCount = max($dbCartItems, $sessionCartCount);

        // Récupérer les points fidélité
        $points = (int) $this->connection->executeQuery(
            'SELECT points_balance FROM aiolia.wallets WHERE user_id = :userId LIMIT 1',
            ['userId' => $userId]
        )->fetchOne() ?: 0;

        return [
            'active_tickets' => $activeTickets,
            'favorite_events' => $favoriteEvents,
            'cart_items' => $cartCount,
            'loyalty_points' => $points,
        ];
    }

    /**
     * Récupère les activités récentes de l'utilisateur
     *
     * @param array<string, mixed> $sessionCartItems
     * @return array<int, array<string, mixed>>
     */
    private function fetchRecentActivities(int $userId, array $sessionCartItems = []): array
    {
        $activities = [];

        // 1. Billets confirmés récents (derniers 30 jours)
        $recentTickets = $this->connection->executeQuery(
            'SELECT 
                o.id AS order_id,
                o.order_number,
                o.created_at,
                e.id AS event_id,
                e.title AS event_title,
                COUNT(t.id) AS ticket_count
             FROM aiolia.orders o
             INNER JOIN aiolia.tickets t ON t.order_id = o.id
             INNER JOIN aiolia.events e ON e.id = t.event_id
             WHERE o.user_id = :userId
               AND o.status = \'paid\'
               AND o.created_at >= NOW() - INTERVAL \'30 days\'
             GROUP BY o.id, o.order_number, o.created_at, e.id, e.title
             ORDER BY o.created_at DESC
             LIMIT 5',
            ['userId' => $userId]
        )->fetchAllAssociative();

        foreach ($recentTickets as $ticket) {
            $createdAt = new \DateTimeImmutable($ticket['created_at']);
            $activities[] = [
                'type' => 'ticket',
                'icon' => 'fas fa-ticket-alt',
                'title' => $ticket['ticket_count'] . ' billet(s) confirmé(s) pour <strong>' . $ticket['event_title'] . '</strong>',
                'meta' => $createdAt->format('d M Y') . ' · Paiement réussi · Ref. #' . $ticket['order_number'],
                'date' => $createdAt,
                'event_id' => (int) $ticket['event_id'],
            ];
        }

        // 2. Favoris récents (derniers 30 jours)
        $wishlistId = $this->connection->executeQuery(
            'SELECT id FROM aiolia.wishlists WHERE user_id = :userId AND is_default = TRUE LIMIT 1',
            ['userId' => $userId]
        )->fetchOne();
        
        if ($wishlistId) {
            $recentFavorites = $this->connection->executeQuery(
                'SELECT 
                    wi.added_at,
                    e.id AS event_id,
                    e.title AS event_title
                 FROM aiolia.wishlist_items wi
                 INNER JOIN aiolia.events e ON e.id = wi.event_id
                 WHERE wi.wishlist_id = :wishlistId
                   AND wi.added_at >= NOW() - INTERVAL \'30 days\'
                 ORDER BY wi.added_at DESC
                 LIMIT 5',
                ['wishlistId' => $wishlistId]
            )->fetchAllAssociative();

            foreach ($recentFavorites as $favorite) {
                $addedAt = new \DateTimeImmutable($favorite['added_at']);
                $activities[] = [
                    'type' => 'favorite',
                    'icon' => 'fas fa-heart',
                    'title' => 'Nouvel événement favori : <strong>' . $favorite['event_title'] . '</strong>',
                    'meta' => $addedAt->format('d M Y') . ' · Favoris',
                    'date' => $addedAt,
                    'event_id' => (int) $favorite['event_id'],
                ];
            }
        }

        // 3. Panier en attente (DB)
        $pendingCart = $this->connection->executeQuery(
            'SELECT 
                c.id,
                c.created_at,
                e.id AS event_id,
                e.title AS event_title,
                e.starts_at
             FROM aiolia.carts c
             INNER JOIN aiolia.cart_items ci ON ci.cart_id = c.id
             INNER JOIN aiolia.events e ON e.id = ci.event_id
             WHERE c.user_id = :userId
               AND c.status = \'active\'
             ORDER BY c.created_at DESC
             LIMIT 1',
            ['userId' => $userId]
        )->fetchAssociative();

        if ($pendingCart) {
            $createdAt = new \DateTimeImmutable($pendingCart['created_at']);
            $startsAt = new \DateTimeImmutable($pendingCart['starts_at']);
            $hoursRemaining = (int) (($startsAt->getTimestamp() - time()) / 3600);
            
            $activities[] = [
                'type' => 'cart',
                'icon' => 'fas fa-clock',
                'title' => 'Panier en attente pour <strong>' . $pendingCart['event_title'] . '</strong>',
                'meta' => $createdAt->format('d M Y') . ' · Expire dans ' . max(0, $hoursRemaining) . ' heure(s)',
                'date' => $createdAt,
                'event_id' => (int) $pendingCart['event_id'],
            ];
        }

        // 4. Panier en session (si pas déjà dans DB)
        if (!empty($sessionCartItems) && !$pendingCart) {
            // Récupérer les événements du panier en session
            $eventIds = array_unique(array_map(fn($item) => $item['eventId'] ?? null, $sessionCartItems));
            $eventIds = array_filter($eventIds);
            
            if (!empty($eventIds)) {
                $placeholders = implode(',', array_fill(0, count($eventIds), '?'));
                $sessionEvents = $this->connection->executeQuery(
                    "SELECT id, title, starts_at FROM aiolia.events WHERE id IN ($placeholders) ORDER BY starts_at ASC LIMIT 1",
                    $eventIds
                )->fetchAssociative();
                
                if ($sessionEvents) {
                    // Trouver la date d'ajout la plus ancienne dans le panier
                    $oldestAddedAt = null;
                    foreach ($sessionCartItems as $item) {
                        if (isset($item['added_at'])) {
                            $addedAt = new \DateTimeImmutable($item['added_at']);
                            if (!$oldestAddedAt || $addedAt < $oldestAddedAt) {
                                $oldestAddedAt = $addedAt;
                            }
                        }
                    }
                    
                    if (!$oldestAddedAt) {
                        $oldestAddedAt = new \DateTimeImmutable();
                    }
                    
                    $startsAt = new \DateTimeImmutable($sessionEvents['starts_at']);
                    $hoursRemaining = (int) (($startsAt->getTimestamp() - time()) / 3600);
                    
                    $activities[] = [
                        'type' => 'cart',
                        'icon' => 'fas fa-clock',
                        'title' => 'Panier en attente pour <strong>' . $sessionEvents['title'] . '</strong>',
                        'meta' => $oldestAddedAt->format('d M Y') . ' · Expire dans ' . max(0, $hoursRemaining) . ' heure(s)',
                        'date' => $oldestAddedAt,
                        'event_id' => (int) $sessionEvents['id'],
                    ];
                }
            }
        }

        // Trier toutes les activités par date (plus récentes en premier)
        usort($activities, function($a, $b) {
            return $b['date'] <=> $a['date'];
        });

        // Limiter à 10 activités
        return array_slice($activities, 0, 10);
    }

    /**
     * Récupère les commandes de l'utilisateur avec leurs détails.
     */
    private function fetchUserOrders(int $userId): array
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
                SUM(oi.quantity) as total_tickets,
                STRING_AGG(DISTINCT e.title, ', ') as event_titles,
                STRING_AGG(DISTINCT e.starts_at::text, ', ') as event_dates
            FROM aiolia.orders o
            LEFT JOIN aiolia.order_items oi ON oi.order_id = o.id
            LEFT JOIN aiolia.ticket_types tt ON tt.id = oi.ticket_type_id
            LEFT JOIN aiolia.events e ON e.id = tt.event_id
            WHERE o.user_id = :user_id
            GROUP BY o.id, o.status, o.total_amount, o.discount_amount, o.currency, 
                     o.promotion_code, o.created_at, o.updated_at, o.notes
            ORDER BY o.created_at DESC
        SQL;

        $rows = $this->connection->executeQuery($sql, ['user_id' => $userId])->fetchAllAssociative();

        return array_map(function (array $row): array {
            $status = $row['status'];
            $statusLabels = [
                'pending' => 'En attente',
                'awaiting_payment' => 'En attente de paiement',
                'paid' => 'Payée',
                'cancelled' => 'Annulée',
                'refunded' => 'Remboursée',
                'failed' => 'Échouée',
            ];

            $paymentMethodLabels = [
                'mvola' => 'M-Vola',
                'orange-money' => 'Orange Money',
                'airtel-money' => 'Airtel Money',
                'orange' => 'Orange Money',
                'airtel' => 'Airtel Money',
                'telma' => 'Telma',
                'bank_transfer' => 'Virement bancaire',
            ];

            // Extraire le payment_method depuis le champ notes (JSON)
            $paymentMethod = null;
            if (!empty($row['notes'])) {
                $notes = json_decode($row['notes'], true);
                if (is_array($notes) && isset($notes['payment_method'])) {
                    $paymentMethod = $notes['payment_method'];
                }
            }

            // Utiliser la date de création de la commande (date du paiement) au lieu de la date de l'événement
            $paymentDate = !empty($row['created_at']) ? new \DateTimeImmutable($row['created_at']) : null;

            return [
                'id' => (int) $row['id'],
                'code' => 'CMD-' . str_pad((string) $row['id'], 6, '0', STR_PAD_LEFT),
                'title' => $row['event_titles'] ?? 'Événement',
                'date' => $paymentDate ? $paymentDate->format('d F Y') : '',
                'hour' => $paymentDate ? $paymentDate->format('H:i') : '',
                'status' => $statusLabels[$status] ?? ucfirst($status),
                'status_key' => $status,
                'amount' => number_format((float) $row['total_amount'], 0, ',', ' ') . ' MGA',
                'amount_raw' => (float) $row['total_amount'],
                'method' => $paymentMethod ? ($paymentMethodLabels[$paymentMethod] ?? ucfirst(str_replace('-', ' ', $paymentMethod))) : 'Non spécifié',
                'tickets' => (int) ($row['total_tickets'] ?? 0),
                'items_count' => (int) ($row['items_count'] ?? 0),
                'created_at' => new \DateTimeImmutable($row['created_at']),
            ];
        }, $rows);
    }

    /**
     * Calcule les statistiques d'achat de l'utilisateur.
     */
    private function calculatePurchaseStats(int $userId, array $orders): array
    {
        $confirmedOrders = array_filter($orders, fn($o) => $o['status_key'] === 'paid');
        $refundedOrders = array_filter($orders, fn($o) => $o['status_key'] === 'refunded');
        
        $totalSpent = array_sum(array_column($confirmedOrders, 'amount_raw'));
        $totalRefunded = array_sum(array_column($refundedOrders, 'amount_raw'));
        $totalTickets = array_sum(array_column($confirmedOrders, 'tickets'));
        
        // Compter les billets VIP (approximation basée sur le montant)
        $vipTickets = 0;
        foreach ($confirmedOrders as $order) {
            if ($order['amount_raw'] > 200000) { // Si montant élevé, probablement VIP
                $vipTickets += (int) ($order['tickets'] * 0.3); // Estimation 30% VIP
            }
        }

        return [
            'total_spent' => number_format($totalSpent, 0, ',', ' ') . ' MGA',
            'total_spent_raw' => $totalSpent,
            'confirmed_orders' => count($confirmedOrders),
            'total_tickets' => $totalTickets,
            'vip_tickets' => $vipTickets,
            'refunded_count' => count($refundedOrders),
            'refunded_amount' => number_format($totalRefunded, 0, ',', ' ') . ' MGA',
            'average_cart' => count($confirmedOrders) > 0 
                ? number_format($totalSpent / count($confirmedOrders), 0, ',', ' ') . ' MGA'
                : '0 MGA',
        ];
    }

    /**
     * Récupère les statistiques personnelles de l'utilisateur.
     */
    private function fetchUserStatistics(int $userId): array
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
     * Récupère les dépenses mensuelles de l'utilisateur.
     */
    private function fetchMonthlyExpenses(int $userId): array
    {
        $sql = <<<SQL
            SELECT 
                TO_CHAR(o.created_at, 'Month YYYY') as month_name,
                TO_CHAR(o.created_at, 'YYYY-MM') as month_key,
                SUM(o.total_amount) as total
            FROM aiolia.orders o
            WHERE o.user_id = :user_id 
              AND o.status = 'paid'
              AND o.created_at >= NOW() - INTERVAL '6 months'
            GROUP BY TO_CHAR(o.created_at, 'Month YYYY'), TO_CHAR(o.created_at, 'YYYY-MM')
            ORDER BY month_key DESC
            LIMIT 6
        SQL;

        $rows = $this->connection->executeQuery($sql, ['user_id' => $userId])->fetchAllAssociative();

        return array_map(function (array $row): array {
            return [
                'month' => trim($row['month_name']),
                'total' => number_format((float) $row['total'], 0, ',', ' ') . ' MGA',
                'total_raw' => (float) $row['total'],
            ];
        }, $rows);
    }

    /**
     * Récupère la répartition par type d'événement.
     */
    private function fetchEventTypeDistribution(int $userId): array
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
            GROUP BY ec.label
            ORDER BY total_amount DESC
        SQL;

        $rows = $this->connection->executeQuery($sql, ['user_id' => $userId])->fetchAllAssociative();

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
     * Récupère l'historique financier détaillé.
     */
    private function fetchFinancialHistory(int $userId): array
    {
        // Récupérer le total des dépenses de l'année en cours
        $sql = <<<SQL
            SELECT 
                SUM(CASE WHEN o.status = 'paid' THEN o.total_amount ELSE 0 END) as total_spent,
                SUM(CASE WHEN o.status = 'refunded' THEN o.total_amount ELSE 0 END) as total_refunded,
                COUNT(CASE WHEN o.status = 'refunded' THEN 1 END) as refund_count
            FROM aiolia.orders o
            WHERE o.user_id = :user_id
              AND EXTRACT(YEAR FROM o.created_at) = EXTRACT(YEAR FROM NOW())
        SQL;

        $row = $this->connection->executeQuery($sql, ['user_id' => $userId])->fetchAssociative();

        // Récupérer le solde du wallet
        $walletSql = <<<SQL
            SELECT balance, points_balance
            FROM aiolia.wallets
            WHERE user_id = :user_id
            LIMIT 1
        SQL;

        $walletRow = $this->connection->executeQuery($walletSql, ['user_id' => $userId])->fetchAssociative();

        return [
            'total_spent' => number_format((float) ($row['total_spent'] ?? 0), 0, ',', ' ') . ' MGA',
            'total_refunded' => number_format((float) ($row['total_refunded'] ?? 0), 0, ',', ' ') . ' MGA',
            'refund_count' => (int) ($row['refund_count'] ?? 0),
            'wallet_balance' => number_format((float) ($walletRow['balance'] ?? 0), 0, ',', ' ') . ' MGA',
            'wallet_points' => (int) ($walletRow['points_balance'] ?? 0),
        ];
    }

    /**
     * Récupère les données financières mensuelles.
     */
    private function fetchMonthlyFinancialData(int $userId): array
    {
        $sql = <<<SQL
            SELECT 
                TO_CHAR(o.created_at, 'Month') as month_name,
                TO_CHAR(o.created_at, 'YYYY-MM') as month_key,
                SUM(o.total_amount) as total
            FROM aiolia.orders o
            WHERE o.user_id = :user_id 
              AND o.status = 'paid'
              AND o.created_at >= NOW() - INTERVAL '6 months'
            GROUP BY TO_CHAR(o.created_at, 'Month'), TO_CHAR(o.created_at, 'YYYY-MM')
            ORDER BY month_key DESC
            LIMIT 6
        SQL;

        $rows = $this->connection->executeQuery($sql, ['user_id' => $userId])->fetchAllAssociative();

        return array_map(function (array $row): array {
            return [
                'month' => trim($row['month_name']),
                'total' => number_format((float) $row['total'], 0, ',', ' ') . ' MGA',
            ];
        }, $rows);
    }

    /**
     * Récupère la répartition des méthodes de paiement.
     */
    private function fetchPaymentMethodDistribution(int $userId): array
    {
        $sql = <<<SQL
            SELECT 
                tp.provider,
                COUNT(*) as count,
                SUM(tp.amount) as total_amount
            FROM aiolia.ticket_payments tp
            JOIN aiolia.ticket_invoices ti ON ti.id = tp.invoice_id
            JOIN aiolia.orders o ON o.id = ti.order_id
            WHERE o.user_id = :user_id
              AND tp.status = 'paid'
            GROUP BY tp.provider
        SQL;

        $rows = $this->connection->executeQuery($sql, ['user_id' => $userId])->fetchAllAssociative();

        $total = array_sum(array_column($rows, 'total_amount'));
        $providerLabels = [
            'orange' => 'Orange Money',
            'airtel' => 'Airtel Money',
            'telma' => 'M-Vola',
        ];

        $distribution = [];
        foreach ($rows as $row) {
            $provider = $row['provider'];
            $label = $providerLabels[$provider] ?? 'Autres';
            $amount = (float) $row['total_amount'];
            $percentage = $total > 0 ? round($amount / $total * 100) : 0;

            if (!isset($distribution[$label])) {
                $distribution[$label] = [
                    'label' => $label,
                    'percentage' => 0,
                    'count' => 0,
                ];
            }

            $distribution[$label]['percentage'] += $percentage;
            $distribution[$label]['count'] += (int) $row['count'];
        }

        return array_values($distribution);
    }
}
