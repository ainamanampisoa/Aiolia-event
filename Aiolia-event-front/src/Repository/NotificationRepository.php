<?php

namespace App\Repository;

use Doctrine\DBAL\Connection;

class NotificationRepository
{
    public function __construct(
        private readonly Connection $connection
    ) {
    }

    /**
     * Marque une notification comme lue.
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        $exists = $this->connection->executeQuery(
            'SELECT id FROM aiolia.notifications WHERE id = :id AND user_id = :userId',
            ['id' => $notificationId, 'userId' => $userId]
        )->fetchOne();

        if (!$exists) {
            return false;
        }

        $this->connection->executeStatement(
            'UPDATE aiolia.notifications SET status = CAST(\'read\' AS aiolia.notification_status_enum), read_at = NOW() WHERE id = :id',
            ['id' => $notificationId],
            ['id' => \PDO::PARAM_INT]
        );

        return true;
    }

    /**
     * Marque toutes les notifications comme lues pour un utilisateur.
     */
    public function markAllAsRead(int $userId): void
    {
        // Utiliser une requête qui fonctionne avec les types ENUM PostgreSQL
        // On compare le statut en le convertissant en texte pour éviter les problèmes avec les ENUM
        $this->connection->executeStatement(
            'UPDATE aiolia.notifications 
             SET status = CAST(\'read\' AS aiolia.notification_status_enum), read_at = NOW() 
             WHERE user_id = :userId 
               AND (CAST(status AS TEXT) != \'read\' OR read_at IS NULL)',
            ['userId' => $userId],
            ['userId' => \PDO::PARAM_INT]
        );
    }

    /**
     * Supprime une notification.
     */
    public function deleteNotification(int $notificationId, int $userId): bool
    {
        $exists = $this->connection->executeQuery(
            'SELECT id FROM aiolia.notifications WHERE id = :id AND user_id = :userId',
            ['id' => $notificationId, 'userId' => $userId]
        )->fetchOne();

        if (!$exists) {
            return false;
        }

        $this->connection->executeStatement(
            'DELETE FROM aiolia.notifications WHERE id = :id',
            ['id' => $notificationId]
        );

        return true;
    }

    /**
     * Compte les notifications non lues d'un utilisateur.
     */
    public function countUnreadNotifications(int $userId): int
    {
        return (int) $this->connection->executeQuery(
            'SELECT COUNT(*) FROM aiolia.notifications WHERE user_id = :userId AND status != \'read\'',
            ['userId' => $userId]
        )->fetchOne();
    }

    /**
     * Récupère les notifications d'un utilisateur.
     */
    public function findUserNotifications(int $userId, int $limit = 20, int $offset = 0): array
    {
        $sql = <<<SQL
            SELECT 
                n.id,
                -- Statut de lecture dérivé du statut ou de la date de lecture
                (n.status = 'read' OR n.read_at IS NOT NULL) AS is_read,
                n.created_at,
                n.read_at,
                -- Le payload JSON contient les données métier de la notif
                n.payload AS metadata,
                nt.code AS template_code,
                nt.subject
            FROM aiolia.notifications n
            LEFT JOIN aiolia.notification_templates nt ON nt.id = n.template_id
            WHERE n.user_id = :userId
            ORDER BY n.created_at DESC
            LIMIT :limit OFFSET :offset
        SQL;

        return $this->connection->executeQuery(
            $sql,
            [
                'userId' => $userId,
                'limit' => $limit,
                'offset' => $offset,
            ],
            [
                'userId' => \PDO::PARAM_INT,
                'limit' => \PDO::PARAM_INT,
                'offset' => \PDO::PARAM_INT,
            ]
        )->fetchAllAssociative();
    }

    /**
     * Vérifie si une notification de rappel existe déjà pour cet utilisateur et cet événement
     */
    public function reminderNotificationExists(int $userId, int $eventId, int $hoursBefore): bool
    {
        $sql = <<<SQL
            SELECT COUNT(*) > 0
            FROM aiolia.notifications n
            WHERE n.user_id = :user_id
              AND n.payload->>'event_id' = :event_id
              AND n.payload->>'hours_before' = :hours_before
              AND n.payload->>'type' = 'reminder'
              AND n.created_at > NOW() - INTERVAL '1 day'
        SQL;

        return (bool) $this->connection->executeQuery($sql, [
            'user_id' => $userId,
            'event_id' => (string) $eventId,
            'hours_before' => (string) $hoursBefore,
        ])->fetchOne();
    }
}

