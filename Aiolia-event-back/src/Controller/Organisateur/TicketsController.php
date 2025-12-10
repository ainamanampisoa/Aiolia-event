<?php

namespace App\Controller\Organisateur;

use App\Entity\Event;
use App\Repository\Organisateur\EventRepository;
use App\Service\Organisateur\BilletService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/organisateur/tickets')]
#[IsGranted('ROLE_ORGANIZER')]
class TicketsController extends AbstractController
{
    public function __construct(
        private BilletService $billetService,
        private EventRepository $eventRepository
    ) {
    }

    
    #[Route('', name: 'organisateur_tickets_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        
        $user = $this->getUser();

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 10;
        $eventId = $request->query->get('eventId');
        
        $event = null;
        if ($eventId) {
            $event = $this->eventRepository->getById($eventId);
            // Vérifier que l'utilisateur a accès à cet événement
            if ($event) {
                $organizerProfile = $event->getProfilOrganisateur();
                if ($organizerProfile && $organizerProfile->getUtilisateur() !== $user) {
                    $event = null; // Pas d'accès, ignorer le filtrage
                }
            }
        }

        $paginator = $this->billetService->getByOrganizerPaginated($user, $page, $limit, $event);
        $stats = $this->billetService->getStatsByOrganizer($user, $event);

        $totalItems = $paginator->count();
        $totalPages = (int) ceil($totalItems / $limit);

        return $this->render('Organisateur/ticket/index.html.twig', [
            'billets' => $paginator,
            'stats' => $stats,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'limit' => $limit,
            'event' => $event,
        ]);
    }
}

