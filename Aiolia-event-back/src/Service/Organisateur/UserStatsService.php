<?php

namespace App\Service\Organisateur;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;


class UserStatsService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    
    public function getUserStatistics(User $user): array
    {
        return [
            'profile' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'nom_complet' => $user->getNomComplet(),
                'prenom' => $user->getPrenom(),
                'nom' => $user->getNom(),
                'telephone' => $user->getTelephone(),
                'url_photo' => $user->getUrlPhoto(),
                'role' => $user->getRole(),
                'email_verifie' => $user->estEmailVerifie(),
                'est_actif' => $user->estActif(),
                'cree_le' => $user->getCreeLe()?->format('Y-m-d H:i:s'),
                'derniere_connexion_le' => $user->getDerniereConnexionLe()?->format('Y-m-d H:i:s'),
            ],
            'events' => $this->getEventsStats($user),
            'tickets' => $this->getTicketsStats($user),
            'orders' => $this->getOrdersStats($user),
            'loyalty' => [
                'points' => $this->getLoyaltyPoints($user),
                'tier' => $this->getLoyaltyTier($user),
            ],
        ];
    }

    
    public function getDashboardSummary(User $user): array
    {
        $stats = $this->getUserStatistics($user);
        
        return [
            'user' => $stats['profile'],
            'summary' => [
                'events_created' => $stats['events']['created_count'],
                'events_collaborated' => $stats['events']['collaborated_count'],
                'tickets_purchased' => $stats['tickets']['purchased_count'],
                'upcoming_events' => $stats['events']['upcoming_count'],
                'loyalty_points' => $stats['loyalty']['points'],
                'loyalty_tier' => $stats['loyalty']['tier'],
            ],
        ];
    }

    
    public function getUpcomingEvents(User $user): array
    {
        
        
        return [];
    }

    
    public function getOrdersHistory(User $user, int $limit = 20): array
    {
        
        
        return [];
    }

    
    public function getLoyaltyPoints(User $user): int
    {
        
        
        return 0;
    }

    
    public function getLoyaltyTier(User $user): string
    {
        $points = $this->getLoyaltyPoints($user);
        
        if ($points >= 10000) {
            return 'diamond';
        } elseif ($points >= 5000) {
            return 'platinum';
        } elseif ($points >= 2000) {
            return 'gold';
        } elseif ($points >= 500) {
            return 'silver';
        }
        
        return 'bronze';
    }

    
    private function getEventsStats(User $user): array
    {
        
        return [
            'created_count' => 0,
            'collaborated_count' => 0,
            'upcoming_count' => 0,
            'past_count' => 0,
            'draft_count' => 0,
        ];
    }

    
    private function getTicketsStats(User $user): array
    {
        
        return [
            'purchased_count' => 0,
            'used_count' => 0,
            'valid_count' => 0,
            'total_spent' => 0,
        ];
    }

    
    private function getOrdersStats(User $user): array
    {
        
        return [
            'total_count' => 0,
            'completed_count' => 0,
            'pending_count' => 0,
            'total_amount' => 0,
        ];
    }
}

