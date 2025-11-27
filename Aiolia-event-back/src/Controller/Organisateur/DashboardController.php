<?php

namespace App\Controller\Organisateur;

use App\Repository\Organisateur\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/organisateur/dashboard')]
#[IsGranted('ROLE_ORGANIZER')]
class DashboardController extends AbstractController
{
    public function __construct(
        private EventRepository $eventRepository
    ) {
    }

    /**
     * Page principale des statistiques (première page après connexion)
     */
    #[Route('/statistiques', name: 'organisateur_dashboard_statistiques', methods: ['GET'])]
    public function statistiques(): Response
    {
        $user = $this->getUser();
        
        // Récupérer les statistiques de l'organisateur
        $totalEvents = $this->eventRepository->count(['organizer' => $user]);
        $publishedEvents = $this->eventRepository->count(['organizer' => $user, 'status' => 'published']);
        $draftEvents = $this->eventRepository->count(['organizer' => $user, 'status' => 'draft']);
        
        // Récupérer les événements récents
        $recentEvents = $this->eventRepository->findBy(
            ['organizer' => $user],
            ['createdAt' => 'DESC'],
            5
        );
        
        // TODO: Ajouter d'autres statistiques (ventes, revenus, etc.)
        
        return $this->render('Organisateur/dashboard/statistiques.html.twig', [
            'totalEvents' => $totalEvents,
            'publishedEvents' => $publishedEvents,
            'draftEvents' => $draftEvents,
            'recentEvents' => $recentEvents,
        ]);
    }
}


