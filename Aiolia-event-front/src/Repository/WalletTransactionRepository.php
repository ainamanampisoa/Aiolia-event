<?php

namespace App\Repository;

use Doctrine\DBAL\Connection;

class WalletTransactionRepository
{
    public function __construct(
        private readonly Connection $connection
    ) {
    }

    /**
     * Crée une transaction.
     */
    public function createTransaction(array $data): int
    {
        $sql = <<<SQL
            INSERT INTO aiolia.wallet_transactions (
                wallet_id,
                transaction_type,
                status,
                amount,
                points_delta,
                description,
                related_entity,
                related_id,
                created_at
            )
            VALUES (
                :wallet_id,
                :transaction_type,
                :status,
                :amount,
                :points_delta,
                :description,
                :related_entity,
                :related_id,
                NOW()
            )
            RETURNING id
        SQL;

        $result = $this->connection->executeQuery($sql, [
            'wallet_id' => $data['wallet_id'],
            'transaction_type' => $data['transaction_type'],
            'status' => $data['status'] ?? 'pending',
            'amount' => $data['amount'] ?? 0,
            'points_delta' => $data['points_delta'] ?? 0,
            'description' => $data['description'] ?? null,
            'related_entity' => $data['related_entity'] ?? null,
            'related_id' => $data['related_id'] ?? null,
        ]);

        return (int) $result->fetchOne();
    }

    /**
     * Récupère les transactions d'un utilisateur.
     */
    public function findUserTransactions(int $userId, ?string $type = null, int $limit = 50): array
    {
        $sql = <<<SQL
            SELECT 
                wt.id,
                wt.transaction_type,
                wt.status,
                wt.amount,
                wt.points_delta,
                wt.description,
                wt.related_entity,
                wt.related_id,
                wt.created_at
            FROM aiolia.wallet_transactions wt
            INNER JOIN aiolia.wallets w ON w.id = wt.wallet_id
            WHERE w.user_id = :userId
        SQL;

        $params = ['userId' => $userId];

        if (null !== $type) {
            $sql .= ' AND wt.transaction_type = :type';
            $params['type'] = $type;
        }

        $sql .= ' ORDER BY wt.created_at DESC LIMIT ' . (int) $limit;

        $rows = $this->connection->executeQuery($sql, $params)->fetchAllAssociative();

        return array_map(function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'type' => $row['transaction_type'],
                'status' => $row['status'],
                'amount' => (float) $row['amount'],
                'points_delta' => (int) $row['points_delta'],
                'description' => $row['description'],
                'related_entity' => $row['related_entity'],
                'related_id' => $row['related_id'] ? (int) $row['related_id'] : null,
                'created_at' => isset($row['created_at']) ? new \DateTimeImmutable($row['created_at']) : null,
            ];
        }, $rows);
    }

    /**
     * Récupère une transaction par son ID.
     */
    public function findTransactionById(int $transactionId): ?array
    {
        $sql = <<<SQL
            SELECT 
                id,
                wallet_id,
                transaction_type,
                status,
                amount,
                points_delta,
                description,
                related_entity,
                related_id,
                created_at
            FROM aiolia.wallet_transactions
            WHERE id = :id
            LIMIT 1
        SQL;

        $row = $this->connection->executeQuery($sql, ['id' => $transactionId])->fetchAssociative();

        if (false === $row) {
            return null;
        }

        return [
            'id' => (int) $row['id'],
            'wallet_id' => (int) $row['wallet_id'],
            'transaction_type' => $row['transaction_type'],
            'status' => $row['status'],
            'amount' => (float) $row['amount'],
            'points_delta' => (int) $row['points_delta'],
            'description' => $row['description'],
            'related_entity' => $row['related_entity'],
            'related_id' => $row['related_id'] ? (int) $row['related_id'] : null,
            'created_at' => isset($row['created_at']) ? new \DateTimeImmutable($row['created_at']) : null,
        ];
    }

    /**
     * Met à jour le statut d'une transaction.
     */
    public function updateTransactionStatus(int $transactionId, string $status): void
    {
        $this->connection->executeStatement(
            'UPDATE aiolia.wallet_transactions SET status = :status WHERE id = :id',
            [
                'id' => $transactionId,
                'status' => $status,
            ]
        );
    }

    /**
     * Récupère l'historique des transactions avec filtres.
     */
    public function getTransactionHistory(
        int $userId,
        ?\DateTime $startDate = null,
        ?\DateTime $endDate = null,
        ?string $type = null,
        int $limit = 100
    ): array {
        $sql = <<<SQL
            SELECT 
                wt.id,
                wt.transaction_type,
                wt.status,
                wt.amount,
                wt.points_delta,
                wt.description,
                wt.related_entity,
                wt.related_id,
                wt.created_at
            FROM aiolia.wallet_transactions wt
            INNER JOIN aiolia.wallets w ON w.id = wt.wallet_id
            WHERE w.user_id = :userId
        SQL;

        $params = ['userId' => $userId];

        if (null !== $startDate) {
            $sql .= ' AND wt.created_at >= :start_date';
            $params['start_date'] = $startDate->format('Y-m-d H:i:s');
        }

        if (null !== $endDate) {
            $sql .= ' AND wt.created_at <= :end_date';
            $params['end_date'] = $endDate->format('Y-m-d H:i:s');
        }

        if (null !== $type) {
            $sql .= ' AND wt.transaction_type = :type';
            $params['type'] = $type;
        }

        $sql .= ' ORDER BY wt.created_at DESC LIMIT ' . (int) $limit;

        $rows = $this->connection->executeQuery($sql, $params)->fetchAllAssociative();

        return array_map(function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'type' => $row['transaction_type'],
                'status' => $row['status'],
                'amount' => (float) $row['amount'],
                'points_delta' => (int) $row['points_delta'],
                'description' => $row['description'],
                'related_entity' => $row['related_entity'],
                'related_id' => $row['related_id'] ? (int) $row['related_id'] : null,
                'created_at' => isset($row['created_at']) ? new \DateTimeImmutable($row['created_at']) : null,
            ];
        }, $rows);
    }
}

