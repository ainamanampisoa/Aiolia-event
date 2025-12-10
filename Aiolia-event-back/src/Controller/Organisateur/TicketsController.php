<?php

namespace App\Controller\Organisateur;

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
        private BilletService $billetService
    ) {
    }

    
    #[Route('', name: 'organisateur_tickets_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        
        $user = $this->getUser();

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 10;

        $paginator = $this->billetService->getByOrganizerPaginated($user, $page, $limit);
        $stats = $this->billetService->getStatsByOrganizer($user);

        $totalItems = $paginator->count();
        $totalPages = (int) ceil($totalItems / $limit);

        return $this->render('Organisateur/ticket/index.html.twig', [
            'billets' => $paginator,
            'stats' => $stats,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'limit' => $limit,
        ]);
    }
}

