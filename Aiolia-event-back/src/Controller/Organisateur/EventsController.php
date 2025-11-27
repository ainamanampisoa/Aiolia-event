<?php

namespace App\Controller\Organisateur;

use App\Repository\Organisateur\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/organisateur/events')]
#[IsGranted('ROLE_ORGANIZER')]
class EventsController extends AbstractController
{
    public function __construct(
        private EventRepository $eventRepository
    ) {
    }

    /**
     * Liste des événements de l'organisateur
     */
    #[Route('', name: 'organisateur_events_index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        $events = $this->eventRepository->findByOrganizer($user);
        
        return $this->render('Organisateur/events/index.html.twig', [
            'events' => $events,
        ]);
    }
}


