<?php

namespace App\Controller;

use App\Repository\EventRepository;
use App\Repository\WishlistRepository;
use App\Service\CacheService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    public function __construct(
        private readonly EventRepository $eventRepository,
        private readonly WishlistRepository $wishlistRepository,
        private readonly CacheService $cacheService
    ) {
    }

    #[Route('/', name: 'home')]
    public function index(Request $request): Response
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $sessionUser = $session->get('user');
        $isAuthenticated = is_array($sessionUser) && isset($sessionUser['id']);

        // Vérifier si l'utilisateur vient de se connecter
        $justLoggedIn = $session->has('just_logged_in') && $session->get('just_logged_in');
        
        // Retirer le flag après l'avoir lu (pour qu'il ne persiste pas sur les autres pages)
        if ($justLoggedIn) {
            $session->remove('just_logged_in');
        }

        // Récupérer les événements avec cache Redis (1 heure de cache)
        $events = $this->cacheService->getCachedUpcomingEvents(
            fn() => $this->eventRepository->findUpcomingEventsForHome(6),
            6,
            3600 // 1 heure
        );
        
        // Récupérer les statistiques avec cache Redis (30 minutes de cache)
        $stats = $this->cacheService->getCachedStats(
            'home',
            'headline',
            fn() => $this->eventRepository->findHeadlineStats(),
            1800 // 30 minutes
        );

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

        return $this->render('home/index.html.twig', [
            'events' => $events,
            'stats' => $stats,
            'isAuthenticated' => $isAuthenticated,
            'sessionUser' => $sessionUser,
            'just_logged_in' => $justLoggedIn,
        ]);
    }

}
