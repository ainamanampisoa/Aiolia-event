<?php

namespace App\Controller\Organisateur;

use App\Entity\Event;
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

    
    #[Route('/statistiques', name: 'organisateur_dashboard_statistiques', methods: ['GET'])]
    public function statistiques(): Response
    {
        $user = $this->getUser();
        
        
        $totalEvents = $this->eventRepository->countByOrganizer($user);
        $publishedEvents = $this->eventRepository->countByOrganizer($user, Event::STATUS_PUBLISHED);
        $draftEvents = $this->eventRepository->countByOrganizer($user, Event::STATUS_DRAFT);
        
        
        $recentEvents = $this->eventRepository->findByOrganizer($user, null, 5);
        
        
        
        return $this->render('Organisateur/dashboard/statistiques.html.twig', [
            'totalEvents' => $totalEvents,
            'publishedEvents' => $publishedEvents,
            'draftEvents' => $draftEvents,
            'recentEvents' => $recentEvents,
        ]);
    }
}


