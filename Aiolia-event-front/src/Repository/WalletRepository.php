<?php

namespace App\Repository;

use Doctrine\DBAL\Connection;

class WalletRepository
{
    public function __construct(
        private readonly Connection $connection
    ) {
    }

    /**
     * Récupère le wallet d'un utilisateur.
     */
    public function findWalletByUserId(int $userId): ?array
    {
        $sql = <<<SQL
            SELECT 
                id,
                user_id,
                currency,
                balance,
                points_balance,
                updated_at,
                created_at
            FROM aiolia.wallets
            WHERE user_id = :userId
            LIMIT 1
        SQL;

        $row = $this->connection->executeQuery($sql, ['userId' => $userId])->fetchAssociative();

        if (false === $row) {
            return null;
        }

        return [
            'id' => (int) $row['id'],
            'user_id' => (int) $row['user_id'],
            'currency' => $row['currency'] ?? 'MGA',
            'balance' => (float) $row['balance'],
            'points_balance' => (int) $row['points_balance'],
            'updated_at' => isset($row['updated_at']) ? new \DateTimeImmutable($row['updated_at']) : null,
            'created_at' => isset($row['created_at']) ? new \DateTimeImmutable($row['created_at']) : null,
        ];
    }

    /**
     * Crée un wallet pour un utilisateur.
     */
    public function createWallet(int $userId, string $currency = 'MGA'): int
    {
        $result = $this->connection->executeQuery(
            'INSERT INTO aiolia.wallets (user_id, currency, balance, points_balance, created_at, updated_at)
             VALUES (:userId, :currency, 0, 0, NOW(), NOW())
             RETURNING id',
            [
                'userId' => $userId,
                'currency' => $currency,
            ]
        );

        return (int) $result->fetchOne();
    }

    /**
     * Met à jour le solde et les points d'un wallet.
     */
    public function updateBalance(int $walletId, float $balance, int $points = null): void
    {
        $params = [
            'walletId' => $walletId,
            'balance' => $balance,
        ];

        $sql = 'UPDATE aiolia.wallets SET balance = :balance, updated_at = NOW()';
        
        if (null !== $points) {
            $sql .= ', points_balance = :points';
            $params['points'] = $points;
        }
        
        $sql .= ' WHERE id = :walletId';

        $this->connection->executeStatement($sql, $params);
    }

    /**
     * Récupère le solde et les points d'un utilisateur.
     */
    public function getBalance(int $userId): array
    {
        $wallet = $this->findWalletByUserId($userId);

        if (!$wallet) {
            return [
                'balance' => 0.0,
                'points' => 0,
                'currency' => 'MGA',
            ];
        }

        return [
            'balance' => $wallet['balance'],
            'points' => $wallet['points_balance'],
            'currency' => $wallet['currency'],
        ];
    }

    /**
     * Vérifie si un wallet existe pour un utilisateur.
     */
    public function walletExists(int $userId): bool
    {
        $count = $this->connection->executeQuery(
            'SELECT COUNT(*) FROM aiolia.wallets WHERE user_id = :userId',
            ['userId' => $userId]
        )->fetchOne();

        return (int) $count > 0;
    }
}

