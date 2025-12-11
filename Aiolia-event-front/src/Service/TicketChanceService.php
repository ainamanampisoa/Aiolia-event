<?php

namespace App\Service;

use App\Repository\OrderRepository;
use Doctrine\DBAL\Connection;

class TicketChanceService
{
    // Seuil minimum d'achat pour pouvoir jouer (configurable)
    private const MINIMUM_PURCHASE_THRESHOLD = 100_000.0; // 100 000 MGA

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
        // Calculer le total des achats de l'utilisateur
        $totalSpent = $this->calculateTotalSpent($userId);
        
        $canPlay = $totalSpent >= self::MINIMUM_PURCHASE_THRESHOLD;
        $remaining = max(0, self::MINIMUM_PURCHASE_THRESHOLD - $totalSpent);
        $progress = $totalSpent > 0 ? min(($totalSpent / self::MINIMUM_PURCHASE_THRESHOLD) * 100, 100) : 0;

        return [
            'can_play' => $canPlay,
            'total_spent' => $totalSpent,
            'threshold' => self::MINIMUM_PURCHASE_THRESHOLD,
            'remaining' => $remaining,
            'progress' => $progress,
        ];
    }

    /**
     * Calcule le total des achats d'un utilisateur.
     */
    private function calculateTotalSpent(int $userId): float
    {
        // Récupérer directement le total depuis la base de données
        $sql = <<<SQL
            SELECT COALESCE(SUM(o.total_amount), 0) as total_spent
            FROM aiolia.orders o
            WHERE o.user_id = :user_id
              AND (o.status = 'paid' OR o.status = 'pending')
        SQL;
        
        $result = $this->connection->executeQuery($sql, ['user_id' => $userId]);
        $row = $result->fetchAssociative();
        
        return (float) ($row['total_spent'] ?? 0);
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
     * 
     * @return array Liste des prix avec id, label, type, value, color, probability
     */
    public function getAvailablePrizes(): array
    {
        return [
            [
                'id' => 1,
                'label' => 'Réduction -20%',
                'type' => 'percent',
                'value' => -20.0,
                'color' => '#FF6B6B',
                'probability' => 0.15, // 15% de chance
                'icon' => 'fa-percent',
            ],
            [
                'id' => 2,
                'label' => 'Billet gratuit',
                'type' => 'free_ticket',
                'value' => 1.0,
                'color' => '#4A90E2',
                'probability' => 0.10, // 10% de chance
                'icon' => 'fa-ticket-alt',
            ],
            [
                'id' => 3,
                'label' => '+150 points fidélité',
                'type' => 'loyalty_points',
                'value' => 150.0,
                'color' => '#FFA500',
                'probability' => 0.20, // 20% de chance
                'icon' => 'fa-coins',
            ],
            [
                'id' => 4,
                'label' => 'Réduction -10%',
                'type' => 'percent',
                'value' => -10.0,
                'color' => '#50C878',
                'probability' => 0.25, // 25% de chance
                'icon' => 'fa-percent',
            ],
            [
                'id' => 5,
                'label' => '+50 points fidélité',
                'type' => 'loyalty_points',
                'value' => 50.0,
                'color' => '#9B59B6',
                'probability' => 0.30, // 30% de chance
                'icon' => 'fa-star',
            ],
        ];
    }

    /**
     * Sélectionne un prix aléatoire basé sur les probabilités.
     * 
     * @return array Le prix sélectionné
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

        // Fallback: retourner le dernier prix
        return end($prizes);
    }
}

