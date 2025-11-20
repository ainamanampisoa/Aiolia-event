<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProfileController extends AbstractController
{
    public function __construct(
        private readonly Connection $connection
    ) {
    }

    #[Route('/profile', name: 'profile_index')]
    public function index(): Response
    {
        return $this->render('profile/index.html.twig');
    }

    #[Route('/profile/history', name: 'profile_history')]
    public function history(): Response
    {
        return $this->render('profile/history.html.twig');
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
    public function searchHistory(): Response
    {
        return $this->render('profile/search_history.html.twig');
    }

    #[Route('/profile/calendar', name: 'profile_calendar')]
    public function calendar(): Response
    {
        return $this->render('profile/calendar.html.twig');
    }

    #[Route('/profile/stats', name: 'profile_stats')]
    public function stats(): Response
    {
        return $this->render('profile/stats.html.twig');
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
    public function settings(): Response
    {
        return $this->render('profile/settings.html.twig');
    }

    #[Route('/profile/financial-history', name: 'profile_financial')]
    public function financialHistory(): Response
    {
        return $this->render('profile/financial.html.twig');
    }

    #[Route('/profile/ticket-chance', name: 'profile_ticket_chance')]
    public function ticketChance(): Response
    {
        return $this->render('profile/ticket_chance.html.twig');
    }
}
