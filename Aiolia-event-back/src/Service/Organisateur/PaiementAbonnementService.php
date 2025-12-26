<?php

namespace App\Service\Organisateur;

use App\Repository\Organisateur\PaiementAbonnementRepository;
use App\Entity\SubscriptionPlan;

class PaiementAbonnementService
{
    public function __construct(
        private PaiementAbonnementRepository $paiementAbonnementRepository
    ) {
    }

    /**
     * Récupère tous les plans d'abonnement actifs
     *
     * @return SubscriptionPlan[]
     */
    public function getAllPlans(): array
    {
        return $this->paiementAbonnementRepository->findAllActive();
    }

    /**
     * Récupère les plans d'abonnement par niveau
     *
     * @param string $niveau Le niveau (basic, pro, enterprise)
     * @return SubscriptionPlan[]
     */
    public function getPlansByNiveau(string $niveau): array
    {
        return $this->paiementAbonnementRepository->findByNiveau($niveau);
    }

    /**
     * Récupère tous les niveaux distincts disponibles
     *
     * @return string[]
     */
    public function getAvailableNiveaux(): array
    {
        return $this->paiementAbonnementRepository->findDistinctNiveaux();
    }

    /**
     * Organise les plans par niveau
     *
     * @return array<string, SubscriptionPlan[]>
     */
    public function getPlansGroupedByNiveau(): array
    {
        $plans = $this->getAllPlans();
        $grouped = [];

        foreach ($plans as $plan) {
            $niveau = $plan->getNiveau();
            if (!isset($grouped[$niveau])) {
                $grouped[$niveau] = [];
            }
            $grouped[$niveau][] = $plan;
        }

        return $grouped;
    }
}
