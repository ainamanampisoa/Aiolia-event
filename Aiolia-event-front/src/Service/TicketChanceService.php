<?php

namespace App\Service;

use App\Repository\OrderRepository;
use Doctrine\DBAL\Connection;

class TicketChanceService
{
    // Configuration
    private const MINIMUM_PURCHASE_THRESHOLD = 100_000.0; // 100 000 MGA pour débloquer
    private const BONUS_PURCHASE_THRESHOLD = 50_000.0;    // 50 000 MGA pour tirage bonus
    private const MAX_PLAYS_PER_DAY = 2;                  // Maximum 2 tirages par jour
    private const FREE_PLAY_INTERVAL_DAYS = 7;           // 1 tirage gratuit par semaine

    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly Connection $connection
    ) {
    }

    /**
     * Vérifie si un utilisateur peut jouer au ticket chance.
     */
    public function canUserPlay(int $userId): array
    {
        $totalSpent = $this->calculateTotalSpent($userId);
        $canPlay = $totalSpent >= self::MINIMUM_PURCHASE_THRESHOLD;
        $remaining = max(0, self::MINIMUM_PURCHASE_THRESHOLD - $totalSpent);
        $progress = $totalSpent > 0 ? min(($totalSpent / self::MINIMUM_PURCHASE_THRESHOLD) * 100, 100) : 0;

        // Vérifier les tirages disponibles
        $playsInfo = $this->getRemainingPlays($userId);

        return [
            'can_play' => $canPlay && $playsInfo['remaining'] > 0,
            'is_unlocked' => $canPlay,
            'total_spent' => min($totalSpent, self::MINIMUM_PURCHASE_THRESHOLD),
            'threshold' => self::MINIMUM_PURCHASE_THRESHOLD,
            'remaining' => $remaining,
            'progress' => $progress,
            'plays_remaining' => $playsInfo['remaining'],
            'plays_today' => $playsInfo['today'],
            'next_free_play' => $playsInfo['next_free'],
        ];
    }

    /**
     * Calcule le total des achats d'un utilisateur.
     */
    private function calculateTotalSpent(int $userId): float
    {
        $sql = <<<SQL
            SELECT COALESCE(SUM(o.total_amount), 0) as total_spent
            FROM aiolia.orders o
            WHERE o.user_id = :user_id
              AND o.status = 'paid'
        SQL;

        $result = $this->connection->executeQuery($sql, ['user_id' => $userId]);
        $row = $result->fetchAssociative();

        return (float) ($row['total_spent'] ?? 0);
    }

    /**
     * Calcule les tirages restants pour un utilisateur.
     */
    public function getRemainingPlays(int $userId): array
    {
        // Tirages effectués aujourd'hui
        $todayPlays = $this->getPlaysToday($userId);

        // Dernier tirage gratuit utilisé
        $lastFreePlay = $this->getLastFreePlayDate($userId);
        $canUseFreePlay = $lastFreePlay === null ||
            (new \DateTime($lastFreePlay))->diff(new \DateTime())->days >= self::FREE_PLAY_INTERVAL_DAYS;

        // Tirages bonus disponibles (non utilisés)
        $bonusPlays = $this->getUnusedBonusPlays($userId);

        // Calcul des tirages restants
        $maxToday = self::MAX_PLAYS_PER_DAY;
        $remainingToday = max(0, $maxToday - $todayPlays);

        // Total disponible = tirage gratuit (si disponible) + bonus (si disponibles)
        $totalAvailable = 0;
        if ($canUseFreePlay) {
            $totalAvailable++;
        }
        $totalAvailable += $bonusPlays;

        // Limité par le max journalier
        $remaining = min($totalAvailable, $remainingToday);

        // Prochaine date de tirage gratuit
        $nextFreePlay = null;
        if (!$canUseFreePlay && $lastFreePlay) {
            $nextDate = (new \DateTime($lastFreePlay))->modify('+' . self::FREE_PLAY_INTERVAL_DAYS . ' days');
            $nextFreePlay = $nextDate->format('Y-m-d');
        }

        return [
            'remaining' => $remaining,
            'today' => $todayPlays,
            'max_today' => $maxToday,
            'free_available' => $canUseFreePlay,
            'bonus_available' => $bonusPlays,
            'next_free' => $nextFreePlay,
        ];
    }

    /**
     * Nombre de tirages effectués aujourd'hui.
     */
    private function getPlaysToday(int $userId): int
    {
        $sql = <<<SQL
            SELECT COUNT(*) as count
            FROM aiolia.ticket_chance_entries
            WHERE user_id = :user_id
              AND DATE(created_at) = CURRENT_DATE
        SQL;

        $result = $this->connection->executeQuery($sql, ['user_id' => $userId]);
        $row = $result->fetchAssociative();

        return (int) ($row['count'] ?? 0);
    }

    /**
     * Date du dernier tirage gratuit.
     */
    private function getLastFreePlayDate(int $userId): ?string
    {
        $sql = <<<SQL
            SELECT created_at
            FROM aiolia.ticket_chance_entries
            WHERE user_id = :user_id
              AND play_type = 'free'
            ORDER BY created_at DESC
            LIMIT 1
        SQL;

        $result = $this->connection->executeQuery($sql, ['user_id' => $userId]);
        $row = $result->fetchAssociative();

        return $row['created_at'] ?? null;
    }

    /**
     * Nombre de tirages bonus non utilisés.
     */
    private function getUnusedBonusPlays(int $userId): int
    {
        // Compter les commandes >= 50 000 MGA qui n'ont pas encore donné de bonus
        $sql = <<<SQL
            SELECT COUNT(*) as count
            FROM aiolia.orders o
            WHERE o.user_id = :user_id
              AND o.status = 'paid'
              AND o.total_amount >= :threshold
              AND NOT EXISTS (
                  SELECT 1 FROM aiolia.ticket_chance_entries tce
                  WHERE tce.user_id = o.user_id
                    AND tce.play_type = 'bonus'
                    AND tce.order_id = o.id
              )
        SQL;

        $result = $this->connection->executeQuery($sql, [
            'user_id' => $userId,
            'threshold' => self::BONUS_PURCHASE_THRESHOLD,
        ]);
        $row = $result->fetchAssociative();

        return (int) ($row['count'] ?? 0);
    }

    /**
     * Récupère le seuil minimum configuré.
     */
    public function getMinimumThreshold(): float
    {
        return self::MINIMUM_PURCHASE_THRESHOLD;
    }

    /**
     * Récupère la liste des prix possibles avec leurs probabilités.
     * Sans les points fidélité - uniquement réductions, bons, billets.
     */
    public function getAvailablePrizes(): array
    {
        return [
            [
                'id' => 1,
                'code' => 'DISCOUNT_50',
                'label' => '-50%',
                'type' => 'percent',
                'value' => 50.0,
                'color' => '#FF4757',
                'probability' => 0.03, // 3% - Très rare
                'icon' => 'fa-fire',
                'validity_days' => 14,
            ],
            [
                'id' => 2,
                'code' => 'DISCOUNT_20',
                'label' => '-20%',
                'type' => 'percent',
                'value' => 20.0,
                'color' => '#FF6B6B',
                'probability' => 0.15, // 15%
                'icon' => 'fa-percent',
                'validity_days' => 30,
            ],
            [
                'id' => 3,
                'code' => 'DISCOUNT_10',
                'label' => '-10%',
                'type' => 'percent',
                'value' => 10.0,
                'color' => '#50C878',
                'probability' => 0.30, // 30% - Commun
                'icon' => 'fa-percent',
                'validity_days' => 30,
            ],
            [
                'id' => 4,
                'code' => 'DISCOUNT_10000',
                'label' => '10 000 MGA',
                'type' => 'amount',
                'value' => 10000.0,
                'color' => '#FFD700',
                'probability' => 0.12, // 12%
                'icon' => 'fa-money-bill-wave',
                'validity_days' => 60,
            ],
            [
                'id' => 5,
                'code' => 'DISCOUNT_5000',
                'label' => '5 000 MGA',
                'type' => 'amount',
                'value' => 5000.0,
                'color' => '#2ED573',
                'probability' => 0.20, // 20%
                'icon' => 'fa-money-bill-wave',
                'validity_days' => 60,
            ],
            [
                'id' => 6,
                'code' => 'FREE_TICKET',
                'label' => 'Billet Gratuit',
                'type' => 'free_ticket',
                'value' => 1.0,
                'color' => '#4A90E2',
                'probability' => 0.02, // 2% - Très rare
                'icon' => 'fa-ticket-alt',
                'validity_days' => 60,
            ],
            [
                'id' => 7,
                'code' => 'UPGRADE_VIP',
                'label' => 'Upgrade VIP',
                'type' => 'upgrade',
                'value' => 1.0,
                'color' => '#9B59B6',
                'probability' => 0.03, // 3% - Rare
                'icon' => 'fa-arrow-up',
                'validity_days' => 30,
            ],
            [
                'id' => 8,
                'code' => 'EXTRA_PLAY',
                'label' => 'Rejouez !',
                'type' => 'extra_play',
                'value' => 1.0,
                'color' => '#E056FD',
                'probability' => 0.15, // 15%
                'icon' => 'fa-redo',
                'validity_days' => 7,
            ],
        ];
    }

    /**
     * Sélectionne un prix aléatoire basé sur les probabilités.
     */
    public function selectRandomPrize(): array
    {
        $prizes = $this->getAvailablePrizes();
        $random = mt_rand() / mt_getrandmax();
        $cumulative = 0.0;

        foreach ($prizes as $prize) {
            $cumulative += $prize['probability'];
            if ($random <= $cumulative) {
                return $prize;
            }
        }

        // Fallback: retourner le prix le plus commun (-10%)
        return $prizes[2];
    }

    /**
     * Effectue un tirage et enregistre le résultat.
     */
    public function play(int $userId): array
    {
        // Vérifier l'éligibilité de base (seuil d'achat atteint)
        $totalSpent = $this->calculateTotalSpent($userId);
        if ($totalSpent < self::MINIMUM_PURCHASE_THRESHOLD) {
            throw new \RuntimeException('Vous devez atteindre ' . number_format(self::MINIMUM_PURCHASE_THRESHOLD, 0, ',', ' ') . ' MGA d\'achats pour jouer.');
        }

        // Sélectionner le prix
        $prize = $this->selectRandomPrize();

        // Enregistrer l'entrée
        $entryId = $this->recordEntrySimple($userId, $prize);

        // Appliquer le prix (générer le code promo, etc.)
        $promoCode = $this->applyPrize($userId, $prize, $entryId);

        return [
            'success' => true,
            'prize' => $prize,
            'entry_id' => $entryId,
            'play_type' => 'free',
            'promo_code' => $promoCode,
        ];
    }

    /**
     * Enregistre une entrée de tirage (version simple compatible).
     */
    private function recordEntrySimple(int $userId, array $prize): int
    {
        // Convertir le type de prix en type compatible avec promotion_type_enum ('percent' ou 'amount')
        $dbPrizeType = match ($prize['type']) {
            'percent' => 'percent',
            'amount', 'free_ticket', 'upgrade', 'extra_play' => 'amount',
            default => 'amount',
        };

        // Vérifier si les nouvelles colonnes existent
        $hasNewColumns = $this->checkNewColumnsExist();

        if ($hasNewColumns) {
            $sql = <<<SQL
                INSERT INTO aiolia.ticket_chance_entries 
                (user_id, prize_type, prize_value, prize_code, play_type, status, created_at)
                VALUES (:user_id, :prize_type::aiolia.promotion_type_enum, :prize_value, :prize_code, 'free', 'won', NOW())
                RETURNING id
            SQL;

            $result = $this->connection->executeQuery($sql, [
                'user_id' => $userId,
                'prize_type' => $dbPrizeType,
                'prize_value' => $prize['value'],
                'prize_code' => $prize['code'],
            ]);
        } else {
            // Version compatible avec l'ancien schéma
            $sql = <<<SQL
                INSERT INTO aiolia.ticket_chance_entries 
                (user_id, prize_type, prize_value, status, created_at)
                VALUES (:user_id, :prize_type::aiolia.promotion_type_enum, :prize_value, 'won', NOW())
                RETURNING id
            SQL;

            $result = $this->connection->executeQuery($sql, [
                'user_id' => $userId,
                'prize_type' => $dbPrizeType,
                'prize_value' => $prize['value'],
            ]);
        }

        $row = $result->fetchAssociative();
        return (int) $row['id'];
    }

    /**
     * Récupère le prochain order_id éligible pour un tirage bonus.
     */
    private function getNextBonusOrderId(int $userId): ?int
    {
        $sql = <<<SQL
            SELECT o.id
            FROM aiolia.orders o
            WHERE o.user_id = :user_id
              AND o.status = 'paid'
              AND o.total_amount >= :threshold
              AND NOT EXISTS (
                  SELECT 1 FROM aiolia.ticket_chance_entries tce
                  WHERE tce.order_id = o.id
              )
            ORDER BY o.created_at ASC
            LIMIT 1
        SQL;

        $result = $this->connection->executeQuery($sql, [
            'user_id' => $userId,
            'threshold' => self::BONUS_PURCHASE_THRESHOLD,
        ]);
        $row = $result->fetchAssociative();

        return $row['id'] ?? null;
    }

    /**
     * Enregistre une entrée de tirage.
     */
    private function recordEntry(int $userId, array $prize, string $playType, ?int $orderId): int
    {
        $sql = <<<SQL
            INSERT INTO aiolia.ticket_chance_entries 
            (user_id, prize_type, prize_value, prize_code, play_type, order_id, status, created_at)
            VALUES (:user_id, :prize_type, :prize_value, :prize_code, :play_type, :order_id, 'won', NOW())
            RETURNING id
        SQL;

        $result = $this->connection->executeQuery($sql, [
            'user_id' => $userId,
            'prize_type' => $prize['type'],
            'prize_value' => $prize['value'],
            'prize_code' => $prize['code'],
            'play_type' => $playType,
            'order_id' => $orderId,
        ]);

        $row = $result->fetchAssociative();
        return (int) $row['id'];
    }

    /**
     * Applique le prix gagné (création de code promo, crédit, etc.).
     */
    private function applyPrize(int $userId, array $prize, int $entryId): ?string
    {
        $validityDays = $prize['validity_days'] ?? 30;
        $expiresAt = (new \DateTime())->modify("+{$validityDays} days")->format('Y-m-d H:i:s');

        switch ($prize['type']) {
            case 'percent':
            case 'amount':
                // Créer un code promo pour l'utilisateur
                return $this->createPromoCode($userId, $prize, $entryId, $expiresAt);

            case 'free_ticket':
                // Créer un bon pour billet gratuit
                return $this->createFreeTicketVoucher($userId, $entryId, $expiresAt);

            case 'upgrade':
                // Créer un bon pour upgrade VIP
                return $this->createUpgradeVoucher($userId, $entryId, $expiresAt);

            case 'extra_play':
                // Ajouter un tirage bonus immédiat
                $this->addExtraPlay($userId, $entryId);
                return null;
        }

        return null;
    }

    /**
     * Crée un code promo pour une réduction.
     */
    private function createPromoCode(int $userId, array $prize, int $entryId, string $expiresAt): string
    {
        $code = 'CHANCE-' . strtoupper(substr(md5($entryId . $userId . time()), 0, 6));
        $promotionType = $prize['type'] === 'percent' ? 'percent' : 'amount';

        $sql = <<<SQL
            INSERT INTO aiolia.promotion_codes 
            (code, promotion_type, value, max_usage_total, max_usage_per_user, 
             starts_at, ends_at, created_at)
            VALUES (:code, :promotion_type::aiolia.promotion_type_enum, :value, 1, 1, 
                    NOW(), :expires_at, NOW())
        SQL;

        $this->connection->executeQuery($sql, [
            'code' => $code,
            'promotion_type' => $promotionType,
            'value' => $prize['value'],
            'expires_at' => $expiresAt,
        ]);

        // Mettre à jour l'entrée avec le code promo
        $this->connection->executeQuery(
            'UPDATE aiolia.ticket_chance_entries SET promo_code = :code WHERE id = :id',
            ['code' => $code, 'id' => $entryId]
        );

        return $code;
    }

    /**
     * Crée un bon pour billet gratuit.
     */
    private function createFreeTicketVoucher(int $userId, int $entryId, string $expiresAt): string
    {
        $code = 'CHANCE-FREE-' . strtoupper(substr(md5($entryId . $userId . time()), 0, 4));

        $sql = <<<SQL
            INSERT INTO aiolia.promotion_codes 
            (code, promotion_type, value, max_usage_total, max_usage_per_user, 
             starts_at, ends_at, created_at, metadata)
            VALUES (:code, 'amount'::aiolia.promotion_type_enum, 999999, 1, 1, 
                    NOW(), :expires_at, NOW(),
                    '{"type": "free_ticket", "max_ticket_price": 100000}'::jsonb)
        SQL;

        $this->connection->executeQuery($sql, [
            'code' => $code,
            'expires_at' => $expiresAt,
        ]);

        $this->connection->executeQuery(
            'UPDATE aiolia.ticket_chance_entries SET promo_code = :code WHERE id = :id',
            ['code' => $code, 'id' => $entryId]
        );

        return $code;
    }

    /**
     * Crée un bon pour upgrade VIP.
     */
    private function createUpgradeVoucher(int $userId, int $entryId, string $expiresAt): string
    {
        $code = 'CHANCE-VIP-' . strtoupper(substr(md5($entryId . $userId . time()), 0, 4));

        $sql = <<<SQL
            INSERT INTO aiolia.promotion_codes 
            (code, promotion_type, value, max_usage_total, max_usage_per_user, 
             starts_at, ends_at, created_at, metadata)
            VALUES (:code, 'amount'::aiolia.promotion_type_enum, 999999, 1, 1, 
                    NOW(), :expires_at, NOW(),
                    '{"type": "upgrade_vip"}'::jsonb)
        SQL;

        $this->connection->executeQuery($sql, [
            'code' => $code,
            'expires_at' => $expiresAt,
        ]);

        $this->connection->executeQuery(
            'UPDATE aiolia.ticket_chance_entries SET promo_code = :code WHERE id = :id',
            ['code' => $code, 'id' => $entryId]
        );

        return $code;
    }

    /**
     * Ajoute un tirage bonus (pour le prix "Rejouez").
     */
    private function addExtraPlay(int $userId, int $entryId): void
    {
        // Pour "Rejouez", on marque simplement l'entrée avec un flag
        // Le prochain tirage sera considéré comme bonus gratuit
        $this->connection->executeQuery(
            'UPDATE aiolia.ticket_chance_entries SET metadata = \'{"grants_extra_play": true}\'::jsonb WHERE id = :id',
            ['id' => $entryId]
        );
    }

    /**
     * Récupère l'historique des tirages d'un utilisateur.
     */
    public function getUserHistory(int $userId, int $limit = 10): array
    {
        try {
            // Vérifier si les nouvelles colonnes existent
            $hasNewColumns = $this->checkNewColumnsExist();

            if ($hasNewColumns) {
                $sql = <<<SQL
                    SELECT 
                        id,
                        prize_type as type,
                        prize_value as value,
                        prize_code as code,
                        promo_code,
                        status,
                        created_at as date,
                        claimed_at
                    FROM aiolia.ticket_chance_entries
                    WHERE user_id = :user_id
                    ORDER BY created_at DESC
                    LIMIT :limit
                SQL;
            } else {
                // Requête compatible avec l'ancien schéma
                $sql = <<<SQL
                    SELECT 
                        id,
                        prize_type as type,
                        prize_value as value,
                        NULL as code,
                        NULL as promo_code,
                        status,
                        created_at as date,
                        claimed_at
                    FROM aiolia.ticket_chance_entries
                    WHERE user_id = :user_id
                    ORDER BY created_at DESC
                    LIMIT :limit
                SQL;
            }

            $result = $this->connection->executeQuery($sql, [
                'user_id' => $userId,
                'limit' => $limit,
            ]);

            $history = [];
            $prizes = $this->getAvailablePrizes();
            $prizesByCode = [];
            $prizesByType = [];
            foreach ($prizes as $p) {
                $prizesByCode[$p['code']] = $p;
                $prizesByType[$p['type']] = $p;
            }

            while ($row = $result->fetchAssociative()) {
                // Trouver le prix correspondant
                $prizeInfo = null;
                if (!empty($row['code'])) {
                    $prizeInfo = $prizesByCode[$row['code']] ?? null;
                }
                if (!$prizeInfo && !empty($row['type'])) {
                    $prizeInfo = $prizesByType[$row['type']] ?? null;
                }

                // Construire le label du prix
                $prizeLabel = $prizeInfo['label'] ?? $this->buildPrizeLabel($row['type'], $row['value']);

                $history[] = [
                    'id' => $row['id'],
                    'prize' => $prizeLabel,
                    'type' => $row['type'],
                    'value' => $row['value'],
                    'promo_code' => $row['promo_code'] ?? null,
                    'status' => $row['status'],
                    'date' => new \DateTime($row['date']),
                    'claimed_at' => $row['claimed_at'] ? new \DateTime($row['claimed_at']) : null,
                    'color' => $prizeInfo['color'] ?? '#4A90E2',
                    'icon' => $prizeInfo['icon'] ?? 'fa-gift',
                ];
            }

            return $history;

        } catch (\Exception $e) {
            // En cas d'erreur, retourner un tableau vide
            return [];
        }
    }

    /**
     * Vérifie si les nouvelles colonnes existent.
     */
    private function checkNewColumnsExist(): bool
    {
        try {
            $sql = <<<SQL
                SELECT COUNT(*) as cnt
                FROM information_schema.columns 
                WHERE table_schema = 'aiolia' 
                AND table_name = 'ticket_chance_entries' 
                AND column_name = 'prize_code'
            SQL;

            $result = $this->connection->executeQuery($sql);
            $row = $result->fetchAssociative();

            return (int) ($row['cnt'] ?? 0) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Construit un label pour un prix basé sur son type et sa valeur.
     */
    private function buildPrizeLabel(string $type, float $value): string
    {
        return match ($type) {
            'percent' => '-' . (int) $value . '%',
            'amount' => number_format($value, 0, ',', ' ') . ' MGA',
            'free_ticket' => 'Billet Gratuit',
            'upgrade' => 'Upgrade VIP',
            'extra_play' => 'Rejouez !',
            default => 'Récompense',
        };
    }
}
