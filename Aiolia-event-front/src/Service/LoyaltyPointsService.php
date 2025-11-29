<?php

namespace App\Service;

use App\Repository\WalletRepository;
use App\Repository\WalletTransactionRepository;

class LoyaltyPointsService
{
    private const POINTS_PER_100_MGA = 1; // 1 point pour 100 MGA
    private const POINTS_TO_MGA_RATE = 10; // 100 points = 1000 MGA (10 MGA par point)
    private const MAX_POINTS_USAGE_PERCENT = 50; // Max 50% du montant avec points

    // Seuils des niveaux de fidélité
    private const TIER_BRONZE = 0;
    private const TIER_SILVER = 500;
    private const TIER_GOLD = 2000;
    private const TIER_PLATINUM = 5000;
    private const TIER_DIAMOND = 10000;

    public function __construct(
        private readonly WalletRepository $walletRepository,
        private readonly WalletTransactionRepository $transactionRepository
    ) {
    }

    /**
     * Calcule les points à attribuer pour un achat.
     */
    public function calculatePointsForPurchase(float $amount): int
    {
        return (int) floor($amount / 100) * self::POINTS_PER_100_MGA;
    }

    /**
     * Attribue des points à un utilisateur.
     */
    public function awardPoints(
        int $userId,
        int $points,
        string $reason,
        ?int $orderId = null
    ): void {
        if ($points <= 0) {
            return;
        }

        $wallet = $this->walletRepository->findWalletByUserId($userId);
        if (!$wallet) {
            // Créer le wallet s'il n'existe pas
            $walletId = $this->walletRepository->createWallet($userId);
            $wallet = $this->walletRepository->findWalletByUserId($userId);
        }

        if (!$wallet) {
            throw new \RuntimeException('Impossible de récupérer le wallet');
        }

        $walletId = $wallet['id'];
        $newPoints = $wallet['points_balance'] + $points;

        // Créer la transaction
        $this->transactionRepository->createTransaction([
            'wallet_id' => $walletId,
            'transaction_type' => 'points_credit',
            'status' => 'completed',
            'amount' => 0,
            'points_delta' => $points,
            'description' => $reason,
            'related_entity' => 'points_award',
            'related_id' => $orderId,
        ]);

        // Mettre à jour les points
        $this->walletRepository->updateBalance($walletId, $wallet['balance'], $newPoints);
    }

    /**
     * Déduit des points d'un utilisateur.
     */
    public function deductPoints(
        int $userId,
        int $points,
        string $reason
    ): bool {
        if ($points <= 0) {
            return false;
        }

        $wallet = $this->walletRepository->findWalletByUserId($userId);
        if (!$wallet) {
            return false;
        }

        if ($wallet['points_balance'] < $points) {
            return false;
        }

        $newPoints = $wallet['points_balance'] - $points;

        // Créer la transaction
        $this->transactionRepository->createTransaction([
            'wallet_id' => $wallet['id'],
            'transaction_type' => 'points_debit',
            'status' => 'completed',
            'amount' => 0,
            'points_delta' => -$points,
            'description' => $reason,
            'related_entity' => 'points_usage',
        ]);

        // Mettre à jour les points
        $this->walletRepository->updateBalance($wallet['id'], $wallet['balance'], $newPoints);

        return true;
    }

    /**
     * Récupère les points actuels d'un utilisateur.
     */
    public function getCurrentPoints(int $userId): int
    {
        $balance = $this->walletRepository->getBalance($userId);
        return $balance['points'];
    }

    /**
     * Détermine le niveau de fidélité de l'utilisateur.
     */
    public function getLoyaltyTier(int $userId): string
    {
        $points = $this->getCurrentPoints($userId);

        if ($points >= self::TIER_DIAMOND) {
            return 'diamond';
        } elseif ($points >= self::TIER_PLATINUM) {
            return 'platinum';
        } elseif ($points >= self::TIER_GOLD) {
            return 'gold';
        } elseif ($points >= self::TIER_SILVER) {
            return 'silver';
        }

        return 'bronze';
    }

    /**
     * Convertit des points en réduction en MGA.
     */
    public function convertPointsToDiscount(int $points): float
    {
        return $points * self::POINTS_TO_MGA_RATE;
    }

    /**
     * Convertit une réduction en MGA en points nécessaires.
     */
    public function convertDiscountToPoints(float $discountAmount): int
    {
        return (int) ceil($discountAmount / self::POINTS_TO_MGA_RATE);
    }

    /**
     * Vérifie si l'utilisateur peut utiliser un certain nombre de points.
     */
    public function canUsePoints(int $userId, int $points): bool
    {
        $currentPoints = $this->getCurrentPoints($userId);
        return $currentPoints >= $points;
    }

    /**
     * Calcule le montant maximum d'utilisation de points pour un achat.
     */
    public function calculateMaxPointsUsage(float $totalAmount): int
    {
        $maxDiscount = $totalAmount * (self::MAX_POINTS_USAGE_PERCENT / 100);
        return $this->convertDiscountToPoints($maxDiscount);
    }

    /**
     * Récupère les informations complètes du niveau de fidélité.
     */
    public function getLoyaltyTierInfo(int $userId): array
    {
        $points = $this->getCurrentPoints($userId);
        $tier = $this->getLoyaltyTier($userId);

        $tiers = [
            'bronze' => [
                'name' => 'Bronze',
                'min_points' => self::TIER_BRONZE,
                'max_points' => self::TIER_SILVER - 1,
                'discount_percent' => 0,
            ],
            'silver' => [
                'name' => 'Argent',
                'min_points' => self::TIER_SILVER,
                'max_points' => self::TIER_GOLD - 1,
                'discount_percent' => 5,
            ],
            'gold' => [
                'name' => 'Or',
                'min_points' => self::TIER_GOLD,
                'max_points' => self::TIER_PLATINUM - 1,
                'discount_percent' => 10,
            ],
            'platinum' => [
                'name' => 'Platine',
                'min_points' => self::TIER_PLATINUM,
                'max_points' => self::TIER_DIAMOND - 1,
                'discount_percent' => 15,
            ],
            'diamond' => [
                'name' => 'Diamant',
                'min_points' => self::TIER_DIAMOND,
                'max_points' => PHP_INT_MAX,
                'discount_percent' => 20,
            ],
        ];

        $currentTier = $tiers[$tier];
        $nextTier = null;
        $tierKeys = array_keys($tiers);
        $currentIndex = array_search($tier, $tierKeys, true);

        if ($currentIndex !== false && isset($tierKeys[$currentIndex + 1])) {
            $nextTierKey = $tierKeys[$currentIndex + 1];
            $nextTier = $tiers[$nextTierKey];
            $pointsToNextTier = $nextTier['min_points'] - $points;
        }

        return [
            'current_tier' => $tier,
            'current_tier_name' => $currentTier['name'],
            'points' => $points,
            'tier_info' => $currentTier,
            'next_tier' => $nextTier,
            'points_to_next_tier' => $pointsToNextTier ?? null,
            'all_tiers' => $tiers,
        ];
    }
}

