<?php

namespace App\Service;

use App\Repository\EventRepository;
use App\Repository\NotificationRepository;
use App\Repository\TicketRepository;
use App\Repository\UserRepository;
use App\Service\Notification\UserMailer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EventReminderService
{
    public function __construct(
        private readonly EventRepository $eventRepository,
        private readonly TicketRepository $ticketRepository,
        private readonly UserRepository $userRepository,
        private readonly NotificationService $notificationService,
        private readonly UserMailer $userMailer,
        private readonly NotificationRepository $notificationRepository,
        private readonly UrlGeneratorInterface $router,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Envoie les rappels pour les événements qui ont lieu dans les prochaines heures
     *
     * @param array<int> $hoursBefore Liste des heures avant l'événement (ex: [24, 2])
     * @return array<string, int> Statistiques des rappels envoyés
     */
    public function sendReminders(array $hoursBefore = [24, 2]): array
    {
        $stats = [
            'events_processed' => 0,
            'users_notified' => 0,
            'emails_sent' => 0,
            'push_notifications_sent' => 0,
            'errors' => 0,
        ];

        foreach ($hoursBefore as $hours) {
            $this->logger->info("Envoi des rappels pour les événements dans {$hours} heures");
            
            $events = $this->eventRepository->findEventsStartingIn($hours);
            $stats['events_processed'] += count($events);

            foreach ($events as $event) {
                try {
                    $users = $this->ticketRepository->findUsersWithTicketsForEvent($event['id']);
                    
                    foreach ($users as $user) {
                        // Vérifier si l'utilisateur a activé les rappels d'événements
                        if (!$this->userRepository->hasEventRemindersEnabled($user['id'])) {
                            continue;
                        }

                        // Vérifier si une notification pour ce rappel n'existe pas déjà
                        if ($this->notificationRepository->reminderNotificationExists($user['id'], $event['id'], $hours)) {
                            continue;
                        }

                        // Envoyer les notifications
                        $result = $this->sendReminderToUser($user, $event, $hours);
                        
                        $stats['users_notified']++;
                        if ($result['email_sent']) {
                            $stats['emails_sent']++;
                        }
                        if ($result['push_sent']) {
                            $stats['push_notifications_sent']++;
                        }
                    }
                } catch (\Exception $e) {
                    $this->logger->error("Erreur lors de l'envoi des rappels pour l'événement {$event['id']}", [
                        'event_id' => $event['id'],
                        'error' => $e->getMessage(),
                    ]);
                    $stats['errors']++;
                }
            }
        }

        return $stats;
    }

    /**
     * Envoie un rappel à un utilisateur pour un événement
     *
     * @return array{email_sent: bool, push_sent: bool}
     */
    private function sendReminderToUser(array $user, array $event, int $hoursBefore): array
    {
        $eventDate = new \DateTimeImmutable($event['starts_at']);
        // Générer l'URL de l'événement
        $eventUrl = $this->router->generate('event_details', ['id' => $event['id']], UrlGeneratorInterface::ABSOLUTE_URL);

        $result = ['email_sent' => false, 'push_sent' => false];

        // Créer la notification web push
        try {
            $notificationId = $this->notificationService->createEventReminderNotification(
                (int) $user['id'],
                (int) $event['id'],
                $event['title'],
                $eventDate,
                $hoursBefore
            );
            $result['push_sent'] = $notificationId !== null;
            
            // Marquer la notification comme envoyée (via le repository si nécessaire)
            // Note: Le statut sera mis à jour automatiquement lors de l'envoi
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de la création de la notification push", [
                'user_id' => $user['id'],
                'event_id' => $event['id'],
                'error' => $e->getMessage(),
            ]);
        }

        // Créer aussi une notification email dans la base
        try {
            $this->notificationService->createNotification(
                (int) $user['id'],
                'email',
                'reminder',
                [
                    'event_id' => (int) $event['id'],
                    'event_name' => $event['title'],
                    'event_date' => $eventDate->format('Y-m-d H:i:s'),
                    'hours_before' => $hoursBefore,
                    'message' => "Rappel : {$event['title']} dans {$hoursBefore} heure(s).",
                ],
                'event_reminder'
            );
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de la création de la notification email", [
                'user_id' => $user['id'],
                'event_id' => $event['id'],
                'error' => $e->getMessage(),
            ]);
        }

        // Envoyer l'email de rappel
        try {
            $this->userMailer->sendEventReminder(
                $user,
                $event,
                $hoursBefore,
                $eventUrl
            );
            $result['email_sent'] = true;
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de l'envoi de l'email de rappel", [
                'user_id' => $user['id'],
                'event_id' => $event['id'],
                'error' => $e->getMessage(),
            ]);
        }

        return $result;
    }
}

