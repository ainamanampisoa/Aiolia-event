<?php

namespace App\Controller;

use App\Repository\NotificationRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NotificationController extends AbstractController
{
    public function __construct(
        private readonly Connection $connection,
        private readonly NotificationRepository $notificationRepository
    ) {
    }

    #[Route('/notifications', name: 'notifications')]
    public function index(Request $request): Response
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $sessionUser = $session->get('user');
        $isAuthenticated = is_array($sessionUser) && isset($sessionUser['id']);

        if (!$isAuthenticated) {
            return $this->redirectToRoute('login');
        }

        $userId = (int) $sessionUser['id'];

        // Récupérer les notifications
        $notifications = $this->fetchUserNotifications($userId);
        
        // Compter les notifications non lues
        $unreadCount = count(array_filter($notifications, fn($n) => !$n['read']));

        return $this->render('notifications/index.html.twig', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'isAuthenticated' => $isAuthenticated,
        ]);
    }

    #[Route('/api/notifications', name: 'api_notifications_list', methods: ['GET'])]
    public function listNotifications(Request $request): JsonResponse
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $sessionUser = $session->get('user');
        if (!is_array($sessionUser) || !isset($sessionUser['id'])) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Vous devez être connecté'
            ], 401);
        }

        $userId = (int) $sessionUser['id'];
        $filter = $request->query->get('filter', 'all'); // all, unread, read

        $notifications = $this->fetchUserNotifications($userId, $filter);

        return new JsonResponse([
            'status' => 'success',
            'notifications' => $notifications,
            'count' => count($notifications),
        ]);
    }

    #[Route('/api/notifications/{id}/read', name: 'api_notifications_mark_read', methods: ['POST'])]
    public function markAsRead(int $id, Request $request): JsonResponse
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $sessionUser = $session->get('user');
        if (!is_array($sessionUser) || !isset($sessionUser['id'])) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Vous devez être connecté'
            ], 401);
        }

        $userId = (int) $sessionUser['id'];

        // Marquer comme lu
        $success = $this->notificationRepository->markAsRead($id, $userId);

        if (!$success) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Notification introuvable'
            ], 404);
        }

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Notification marquée comme lue'
        ]);
    }

    #[Route('/api/notifications/read-all', name: 'api_notifications_mark_all_read', methods: ['POST'])]
    public function markAllAsRead(Request $request): JsonResponse
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $sessionUser = $session->get('user');
        if (!is_array($sessionUser) || !isset($sessionUser['id'])) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Vous devez être connecté'
            ], 401);
        }

        $userId = (int) $sessionUser['id'];

        // Marquer toutes les notifications comme lues
        $this->notificationRepository->markAllAsRead($userId);

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Toutes les notifications ont été marquées comme lues'
        ]);
    }

    #[Route('/api/notifications/{id}/delete', name: 'api_notifications_delete', methods: ['DELETE'])]
    public function deleteNotification(int $id, Request $request): JsonResponse
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $sessionUser = $session->get('user');
        if (!is_array($sessionUser) || !isset($sessionUser['id'])) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Vous devez être connecté'
            ], 401);
        }

        $userId = (int) $sessionUser['id'];

        // Supprimer la notification
        $success = $this->notificationRepository->deleteNotification($id, $userId);

        if (!$success) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Notification introuvable'
            ], 404);
        }

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Notification supprimée'
        ]);
    }

    #[Route('/api/notifications/count', name: 'api_notifications_count', methods: ['GET'])]
    public function getUnreadCount(Request $request): JsonResponse
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $sessionUser = $session->get('user');
        if (!is_array($sessionUser) || !isset($sessionUser['id'])) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Vous devez être connecté'
            ], 401);
        }

        $userId = (int) $sessionUser['id'];

        $count = $this->notificationRepository->countUnreadNotifications($userId);

        return new JsonResponse([
            'status' => 'success',
            'count' => $count,
        ]);
    }

    /**
     * Récupère les notifications de l'utilisateur
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchUserNotifications(int $userId, string $filter = 'all'): array
    {
        // Récupérer les notifications depuis le repository
        $rows = $this->notificationRepository->findUserNotifications($userId, 100, 0);
        
        // Appliquer le filtre
        if ($filter === 'unread') {
            $rows = array_filter($rows, fn($row) => !$row['is_read']);
        } elseif ($filter === 'read') {
            $rows = array_filter($rows, fn($row) => $row['is_read']);
        }

        return array_map(function (array $row): array {
            $payload = [];
            if (!empty($row['metadata'])) {
                $decoded = json_decode($row['metadata'], true);
                if (is_array($decoded)) {
                    $payload = $decoded;
                }
            }

            $createdAt = isset($row['created_at']) ? new \DateTimeImmutable($row['created_at']) : new \DateTimeImmutable();
            $readAt = isset($row['read_at']) ? new \DateTimeImmutable($row['read_at']) : null;

            // Déterminer le type de notification basé sur le code du template ou le payload
            $type = $this->determineNotificationType($row['template_code'] ?? '', $payload);
            
            // Générer le titre et la description
            $title = $this->generateNotificationTitle($type, $payload, $row['subject'] ?? '');
            $description = $this->generateNotificationDescription($type, $payload);

            // Formater les dates
            $timeAgo = $this->formatTimeAgo($createdAt);
            $dateFormatted = $createdAt->format('d M Y') . ' · ' . $createdAt->format('H:i');

            return [
                'id' => (int) $row['id'],
                'title' => $title,
                'description' => $description,
                'type' => $type,
                'read' => $row['is_read'] ?? false,
                'time' => $timeAgo,
                'date' => $dateFormatted,
                'created_at' => $createdAt,
                'read_at' => $readAt,
            ];
        }, $rows);
    }

    /**
     * Détermine le type de notification
     */
    private function determineNotificationType(string $templateCode, array $payload): string
    {
        // Basé sur le code du template
        if (str_contains($templateCode, 'ticket') || str_contains($templateCode, 'billet')) {
            return 'ticket';
        }
        if (str_contains($templateCode, 'offer') || str_contains($templateCode, 'promo') || str_contains($templateCode, 'offre')) {
            return 'offer';
        }
        if (str_contains($templateCode, 'reminder') || str_contains($templateCode, 'rappel')) {
            return 'reminder';
        }
        if (str_contains($templateCode, 'payment') || str_contains($templateCode, 'paiement')) {
            return 'payment';
        }

        // Basé sur le payload
        if (isset($payload['type'])) {
            return $payload['type'];
        }

        return 'info';
    }

    /**
     * Génère le titre de la notification
     */
    private function generateNotificationTitle(string $type, array $payload, string $subject): string
    {
        if (!empty($subject)) {
            return $subject;
        }

        $eventName = $payload['event_name'] ?? $payload['event_title'] ?? 'cet événement';
        
        return match ($type) {
            'ticket' => "Vos billets pour <span>{$eventName}</span> sont disponibles",
            'offer' => "Nouvelle offre exclusive sur <span>{$eventName}</span>",
            'reminder' => "Rappel : Validation requise pour <span>{$eventName}</span>",
            'payment' => "Confirmation : paiement reçu pour <span>{$eventName}</span>",
            default => "Notification concernant <span>{$eventName}</span>",
        };
    }

    /**
     * Génère la description de la notification
     */
    private function generateNotificationDescription(string $type, array $payload): string
    {
        if (isset($payload['message']) || isset($payload['description'])) {
            return $payload['message'] ?? $payload['description'] ?? '';
        }

        return match ($type) {
            'ticket' => 'Téléchargez vos billets et partagez-les avec vos invités avant l\'évènement.',
            'offer' => 'Profitez de cette offre spéciale pour réserver vos places.',
            'reminder' => 'N\'oubliez pas de valider votre réservation.',
            'payment' => 'Votre paiement est confirmé. Nous vous enverrons un rappel le jour de l\'évènement.',
            default => 'Nouvelle notification disponible.',
        };
    }

    /**
     * Formate le temps écoulé en français
     */
    private function formatTimeAgo(\DateTimeImmutable $date): string
    {
        $now = new \DateTimeImmutable();
        $diff = $now->diff($date);

        if ($diff->days > 7) {
            return 'Il y a ' . $diff->days . ' jours';
        }
        if ($diff->days > 0) {
            return 'Il y a ' . $diff->days . ' jour' . ($diff->days > 1 ? 's' : '');
        }
        if ($diff->h > 0) {
            return 'Il y a ' . $diff->h . ' heure' . ($diff->h > 1 ? 's' : '');
        }
        if ($diff->i > 0) {
            return 'Il y a ' . $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
        }

        return 'À l\'instant';
    }
}
