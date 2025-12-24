<?php

namespace App\Controller;

use App\Repository\EventRepository;
use App\Service\RefundService;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class OrganizerController extends AbstractController
{
    public function __construct(
        private readonly EventRepository $eventRepository,
        private readonly RefundService $refundService,
        private readonly Connection $connection
    ) {
    }
    #[Route('/api/organizer/events', name: 'api_organizer_events', methods: ['GET'])]
    public function listOrganizerEvents(): JsonResponse
    {
        // TODO: Récupérer les événements de l'organisateur connecté
        return new JsonResponse([
            'message' => 'Liste des événements de l\'organisateur - À implémenter',
            'status' => 'success',
            'data' => []
        ]);
    }

    #[Route('/api/organizer/events', name: 'api_organizer_create_event', methods: ['POST'])]
    public function createEvent(Request $request): JsonResponse
    {
        // TODO: Créer un nouvel événement
        return new JsonResponse([
            'message' => 'Création d\'événement - À implémenter',
            'status' => 'success'
        ]);
    }

    #[Route('/api/organizer/events/{id}', name: 'api_organizer_update_event', methods: ['PUT'])]
    public function updateEvent(int $id, Request $request): JsonResponse
    {
        // TODO: Mettre à jour un événement
        return new JsonResponse([
            'message' => "Mise à jour de l'événement {$id} - À implémenter",
            'status' => 'success'
        ]);
    }

    #[Route('/api/organizer/events/{id}', name: 'api_organizer_delete_event', methods: ['DELETE'])]
    public function deleteEvent(int $id): JsonResponse
    {
        // TODO: Supprimer un événement
        return new JsonResponse([
            'message' => "Suppression de l'événement {$id} - À implémenter",
            'status' => 'success'
        ]);
    }

    #[Route('/api/organizer/events/{id}/tickets', name: 'api_organizer_event_tickets', methods: ['GET'])]
    public function getEventTickets(int $id): JsonResponse
    {
        // TODO: Récupérer les statistiques des billets pour un événement
        return new JsonResponse([
            'message' => "Statistiques des billets pour l'événement {$id} - À implémenter",
            'status' => 'success',
            'data' => []
        ]);
    }

    #[Route('/api/organizer/events/{id}/promotions', name: 'api_organizer_event_promotions', methods: ['GET'])]
    public function getEventPromotions(int $id): JsonResponse
    {
        // TODO: Récupérer les promotions d'un événement
        return new JsonResponse([
            'message' => "Promotions de l'événement {$id} - À implémenter",
            'status' => 'success',
            'data' => []
        ]);
    }

    #[Route('/api/organizer/events/{id}/promotions', name: 'api_organizer_create_promotion', methods: ['POST'])]
    public function createPromotion(int $id, Request $request): JsonResponse
    {
        // TODO: Créer une promotion pour un événement
        return new JsonResponse([
            'message' => "Création de promotion pour l'événement {$id} - À implémenter",
            'status' => 'success'
        ]);
    }

    #[Route('/api/organizer/dashboard', name: 'api_organizer_dashboard', methods: ['GET'])]
    public function getDashboard(): JsonResponse
    {
        // TODO: Récupérer les données du tableau de bord organisateur
        return new JsonResponse([
            'message' => 'Tableau de bord organisateur - À implémenter',
            'status' => 'success',
            'data' => [
                'total_events' => 0,
                'total_tickets_sold' => 0,
                'total_revenue' => 0,
                'upcoming_events' => []
            ]
        ]);
    }

    #[Route('/api/organizer/reports/{eventId}', name: 'api_organizer_event_report', methods: ['GET'])]
    public function getEventReport(int $eventId): JsonResponse
    {
        // TODO: Générer un rapport pour un événement
        return new JsonResponse([
            'message' => "Rapport de l'événement {$eventId} - À implémenter",
            'status' => 'success',
            'data' => []
        ]);
    }

    #[Route('/api/organizer/reports/{eventId}/export', name: 'api_organizer_export_report', methods: ['GET'])]
    public function exportEventReport(int $eventId): JsonResponse
    {
        // TODO: Exporter un rapport en CSV/PDF
        return new JsonResponse([
            'message' => "Export du rapport de l'événement {$eventId} - À implémenter",
            'status' => 'success'
        ]);
    }

    /**
     * Route ADMIN uniquement : Annule un événement (le remboursement se déclenche automatiquement)
     */
    #[Route('/api/admin/events/{id}/cancel', name: 'api_admin_cancel_event', methods: ['POST'])]
    public function cancelEvent(int $id, Request $request): JsonResponse
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $sessionUser = $session->get('user');
        $isAuthenticated = is_array($sessionUser) && isset($sessionUser['id']);

        if (!$isAuthenticated) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        // Vérifier que l'utilisateur est un admin
        $isAdmin = in_array('ROLE_ADMIN', $sessionUser['roles'] ?? [], true);

        if (!$isAdmin) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Accès réservé aux administrateurs'
            ], 403);
        }

        // Récupérer l'événement
        $event = $this->eventRepository->findEventById($id);
        
        if (!$event) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Événement introuvable'
            ], 404);
        }

        // Vérifier que l'événement n'est pas déjà annulé
        if (($event['status'] ?? '') === 'cancelled') {
            return new JsonResponse([
                'success' => false,
                'message' => 'Cet événement est déjà annulé'
            ], 400);
        }

        // Récupérer la raison de l'annulation
        $data = json_decode($request->getContent(), true);
        $reason = $data['reason'] ?? 'Événement annulé pour cause d\'urgence';

        try {
            // Marquer l'événement comme annulé
            // Le remboursement sera déclenché automatiquement par l'EventListener
            $this->connection->executeStatement(
                <<<SQL
                    UPDATE aiolia.events
                    SET status = 'cancelled',
                        updated_at = NOW()
                    WHERE id = :event_id
                SQL,
                ['event_id' => $id]
            );

            // Déclencher le remboursement automatique
            $refundResult = $this->refundService->refundEventTickets($id, $reason);

            return new JsonResponse([
                'success' => true,
                'message' => 'Événement annulé. Remboursements en cours...',
                'refund_summary' => [
                    'refunded_orders' => $refundResult['refunded_orders'],
                    'refunded_tickets' => $refundResult['refunded_tickets'],
                    'total_amount' => $refundResult['total_amount'],
                    'errors' => $refundResult['errors'] ?? []
                ]
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation : ' . $e->getMessage()
            ], 500);
        }
    }
}

