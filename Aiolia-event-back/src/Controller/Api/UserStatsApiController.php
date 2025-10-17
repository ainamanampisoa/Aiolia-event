<?php

namespace App\Controller\Api;

use App\Service\UserStatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * API pour récupérer les statistiques utilisateur (calculées dynamiquement)
 */
#[Route('/api/user/stats', name: 'api_user_stats_')]
class UserStatsApiController extends AbstractController
{
    public function __construct(
        private UserStatsService $userStatsService
    ) {
    }

    /**
     * Récupère toutes les statistiques de l'utilisateur connecté
     */
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $stats = $this->userStatsService->getUserStatistics($this->getUser());

        return $this->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Récupère le résumé complet pour le dashboard
     */
    #[Route('/dashboard', name: 'dashboard', methods: ['GET'])]
    public function dashboard(): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $summary = $this->userStatsService->getDashboardSummary($this->getUser());

        return $this->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Récupère les événements à venir de l'utilisateur
     */
    #[Route('/upcoming-events', name: 'upcoming_events', methods: ['GET'])]
    public function upcomingEvents(): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $events = $this->userStatsService->getUpcomingEvents($this->getUser());

        return $this->json([
            'success' => true,
            'data' => $events,
            'total' => count($events),
        ]);
    }

    /**
     * Récupère l'historique des commandes
     */
    #[Route('/orders-history', name: 'orders_history', methods: ['GET'])]
    public function ordersHistory(): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $orders = $this->userStatsService->getOrdersHistory($this->getUser(), 20);

        return $this->json([
            'success' => true,
            'data' => $orders,
            'total' => count($orders),
        ]);
    }

    /**
     * Récupère les points de fidélité
     */
    #[Route('/loyalty', name: 'loyalty', methods: ['GET'])]
    public function loyalty(): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $points = $this->userStatsService->getLoyaltyPoints($this->getUser());
        $tier = $this->userStatsService->getLoyaltyTier($this->getUser());

        return $this->json([
            'success' => true,
            'data' => [
                'points' => $points,
                'tier' => $tier,
                'tiers' => [
                    ['name' => 'bronze', 'min_points' => 0],
                    ['name' => 'silver', 'min_points' => 500],
                    ['name' => 'gold', 'min_points' => 2000],
                    ['name' => 'platinum', 'min_points' => 5000],
                    ['name' => 'diamond', 'min_points' => 10000],
                ],
            ],
        ]);
    }
}


