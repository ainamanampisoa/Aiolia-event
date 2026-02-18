<?php

namespace App\Controller\Organisateur;

use App\Entity\Event;
use App\Repository\Organisateur\EventRepository;
use App\Service\Organisateur\BilletService;
use App\Service\Organisateur\SalesHistoryService;
use App\Service\TicketStatusService;
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
        private EventRepository $eventRepository,
        private SalesHistoryService $salesHistoryService,
        private TicketStatusService $ticketStatusService
    ) {
    }

    
    #[Route('', name: 'organisateur_tickets_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        
        $user = $this->getUser();

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 10;
        $eventId = $request->query->get('eventId');
        $filters = [
            'statut' => $request->query->get('statut'),
            'categorie' => $request->query->get('categorie'),
            'segment' => $request->query->get('segment'),
        ];

        $filters = array_map(static function ($value) {
            if (is_string($value)) {
                $value = trim($value);
            }

            return $value === '' ? null : $value;
        }, $filters);
        
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

        // Historique de ventes via vue v_ticket_sales_history
        $history = $this->salesHistoryService->getPaginated($user, $page, $limit, $event, $filters);
        $rows = $history['rows'] ?? [];
        $totalItems = $history['total'] ?? 0;
        $totalPages = (int) ceil($totalItems / $limit);

        // Stats globales sans filtres (pour les widgets)
        $stats = $this->billetService->getStatsByOrganizer($user, $event);

        // Filtres disponibles depuis la base de données (toutes les valeurs possibles)
        $availableFilters = $this->billetService->getFilterOptionsByOrganizer($user, $event);
        
        // Inclure le statut 'dispo' pour afficher les billets disponibles sans facture
        // Ces billets sont maintenant inclus dans l'affichage avec leur QR code

        return $this->render('Organisateur/ticket/index.html.twig', [
            'ventes' => $rows,
            'stats' => $stats,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'limit' => $limit,
            'event' => $event,
            'filters' => $filters,
            'availableFilters' => $availableFilters,
            'statusLabels' => $this->ticketStatusService->getStatusLabels(),
        ]);
    }
}

