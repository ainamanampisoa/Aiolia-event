<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig');
    }

    #[Route('/api/health', name: 'api_health', methods: ['GET'])]
    public function healthCheck(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'healthy',
            'message' => 'API Aiolia Event fonctionne correctement',
            'timestamp' => new \DateTime(),
            'version' => '1.0.0'
        ]);
    }

    #[Route('/api/stats', name: 'api_stats', methods: ['GET'])]
    public function getStats(): JsonResponse
    {
        // TODO: Récupérer les statistiques globales de la plateforme
        return new JsonResponse([
            'message' => 'Statistiques de la plateforme - À implémenter',
            'status' => 'success',
            'data' => [
                'total_events' => 0,
                'total_users' => 0,
                'total_tickets_sold' => 0,
                'categories' => []
            ]
        ]);
    }
}

