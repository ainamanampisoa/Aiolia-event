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
            'UPDATE aiolia.notifications SET is_read = TRUE, read_at = NOW() WHERE id = :id',
            ['id' => $notificationId]
        );

        return true;
    }

    /**
     * Marque toutes les notifications comme lues pour un utilisateur.
     */
    public function markAllAsRead(int $userId): void
    {
        $this->connection->executeStatement(
            'UPDATE aiolia.notifications SET is_read = TRUE, read_at = NOW() WHERE user_id = :userId AND is_read = FALSE',
            ['userId' => $userId]
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
            'SELECT COUNT(*) FROM aiolia.notifications WHERE user_id = :userId AND is_read = FALSE',
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
                n.type,
                n.title,
                n.message,
                n.is_read,
                n.created_at,
                n.read_at,
                n.metadata,
                nt.code AS template_code,
                nt.subject
            FROM aiolia.notifications n
            LEFT JOIN aiolia.notification_templates nt ON nt.id = n.template_id
            WHERE n.user_id = :userId
            ORDER BY n.created_at DESC
            LIMIT :limit OFFSET :offset
        SQL;

        return $this->connection->executeQuery($sql, [
            'userId' => $userId,
            'limit' => $limit,
            'offset' => $offset,
        ])->fetchAllAssociative();
    }
}

