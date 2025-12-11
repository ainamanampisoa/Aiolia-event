<?php

namespace App\Service;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class NotificationService
{
    public function __construct(
        private readonly Connection $connection,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Crée une notification pour un utilisateur
     *
     * @param int $userId ID de l'utilisateur
     * @param string $channel Canal de notification (email, sms, web_push)
     * @param string $type Type de notification (ticket, offer, reminder, payment, etc.)
     * @param array<string, mixed> $payload Données de la notification
     * @param string|null $templateCode Code du template de notification (optionnel)
     * @param \DateTimeImmutable|null $scheduledAt Date de programmation (optionnel)
     * @return int|null ID de la notification créée ou null en cas d'erreur
     */
    public function createNotification(
        int $userId,
        string $channel,
        string $type,
        array $payload,
        ?string $templateCode = null,
        ?\DateTimeImmutable $scheduledAt = null
    ): ?int {
        try {
            // Récupérer le template si un code est fourni
            $templateId = null;
            if ($templateCode) {
                $templateId = $this->connection->executeQuery(
                    'SELECT id FROM aiolia.notification_templates WHERE code = :code LIMIT 1',
                    ['code' => $templateCode]
                )->fetchOne();
            }

            // Ajouter le type dans le payload
            $payload['type'] = $type;

            // Insérer la notification
            $this->connection->insert('aiolia.notifications', [
                'user_id' => $userId,
                'template_id' => $templateId ?: null,
                'channel' => $channel,
                'status' => 'pending',
                'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
                'scheduled_at' => $scheduledAt?->format('Y-m-d H:i:s'),
                'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]);

            $notificationId = (int) $this->connection->lastInsertId();

            $this->logger->info('Notification créée', [
                'notification_id' => $notificationId,
                'user_id' => $userId,
                'type' => $type,
                'channel' => $channel,
            ]);

            return $notificationId;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la création de la notification', [
                'user_id' => $userId,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Crée une notification de confirmation de paiement
     */
    public function createPaymentConfirmationNotification(
        int $userId,
        int $orderId,
        string $eventName,
        float $amount,
        string $currency = 'MGA'
    ): ?int {
        return $this->createNotification(
            $userId,
            'web_push',
            'payment',
            [
                'order_id' => $orderId,
                'event_name' => $eventName,
                'amount' => $amount,
                'currency' => $currency,
                'message' => "Votre paiement de {$amount} {$currency} pour {$eventName} est confirmé.",
            ],
            'payment_confirmation'
        );
    }

    /**
     * Crée une notification de billets disponibles
     */
    public function createTicketAvailableNotification(
        int $userId,
        int $eventId,
        string $eventName,
        int $ticketCount
    ): ?int {
        return $this->createNotification(
            $userId,
            'web_push',
            'ticket',
            [
                'event_id' => $eventId,
                'event_name' => $eventName,
                'ticket_count' => $ticketCount,
                'message' => "Vos {$ticketCount} billet(s) pour {$eventName} sont maintenant disponibles.",
            ],
            'ticket_available'
        );
    }

    /**
     * Crée une notification de rappel d'événement
     */
    public function createEventReminderNotification(
        int $userId,
        int $eventId,
        string $eventName,
        \DateTimeImmutable $eventDate,
        int $hoursBefore = 24
    ): ?int {
        // Ne pas programmer la notification, l'envoyer immédiatement
        // car le service EventReminderService est appelé au bon moment

        return $this->createNotification(
            $userId,
            'web_push',
            'reminder',
            [
                'event_id' => $eventId,
                'event_name' => $eventName,
                'event_date' => $eventDate->format('Y-m-d H:i:s'),
                'hours_before' => $hoursBefore,
                'message' => $hoursBefore === 24 
                    ? "Rappel : {$eventName} demain à " . $eventDate->format('H:i')
                    : "Rappel : {$eventName} dans {$hoursBefore} heure(s) à " . $eventDate->format('H:i'),
            ],
            'event_reminder'
        );
    }

    /**
     * Crée une notification d'offre spéciale
     */
    public function createSpecialOfferNotification(
        int $userId,
        int $eventId,
        string $eventName,
        string $promoCode,
        float $discount,
        string $discountType = 'percent'
    ): ?int {
        $discountText = $discountType === 'percent' 
            ? "{$discount}% de réduction"
            : "{$discount} {$discountType} de réduction";

        return $this->createNotification(
            $userId,
            'web_push',
            'offer',
            [
                'event_id' => $eventId,
                'event_name' => $eventName,
                'promo_code' => $promoCode,
                'discount' => $discount,
                'discount_type' => $discountType,
                'message' => "Nouvelle offre : {$discountText} sur {$eventName} avec le code {$promoCode}.",
            ],
            'special_offer'
        );
    }

    /**
     * Crée une notification de panier expirant
     */
    public function createCartExpiringNotification(
        int $userId,
        array $cartItems,
        \DateTimeImmutable $expiresAt
    ): ?int {
        $eventNames = array_map(fn($item) => $item['event_name'] ?? 'événement', $cartItems);
        $eventsText = implode(', ', array_slice($eventNames, 0, 2));
        if (count($eventNames) > 2) {
            $eventsText .= ' et ' . (count($eventNames) - 2) . ' autre(s)';
        }

        return $this->createNotification(
            $userId,
            'web_push',
            'reminder',
            [
                'cart_items' => $cartItems,
                'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
                'message' => "Votre panier pour {$eventsText} expire bientôt. Validez votre commande pour garantir vos places.",
            ],
            'cart_expiring'
        );
    }

    /**
     * Crée une notification de stock bas
     */
    public function createLowStockNotification(
        int $userId,
        int $eventId,
        string $eventName,
        int $remainingTickets
    ): ?int {
        return $this->createNotification(
            $userId,
            'web_push',
            'reminder',
            [
                'event_id' => $eventId,
                'event_name' => $eventName,
                'remaining_tickets' => $remainingTickets,
                'message' => "Attention : Il ne reste que {$remainingTickets} billet(s) pour {$eventName}.",
            ],
            'low_stock'
        );
    }
}

