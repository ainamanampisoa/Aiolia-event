<?php

namespace App\Service\Admin;

use App\Repository\Admin\StatisticsRepository;

class StatisticsService
{
    public function __construct(
        private StatisticsRepository $statisticsRepository
    ) {
    }

    /**
     * Récupère toutes les statistiques pour la page
     * @param int $month 0 = tous les mois, 1-12 = mois spécifique
     * @param int $year Année (défaut: 2025)
     */
    public function getStatistics(int $month = 0, int $year = 2025): array
    {
        // Pour les widgets, si month = 0, utiliser le mois actuel (ou novembre 2025 par défaut pour avoir des données)
        if ($month > 0 && $month <= 12) {
            $referenceDate = new \DateTimeImmutable(sprintf('%d-%02d-01', $year, $month));
        } else {
            // Si "tous les mois" est sélectionné, utiliser novembre 2025 par défaut (dernier mois avec données)
            // ou le mois actuel si on est dans l'année sélectionnée
            $now = new \DateTimeImmutable();
            if ($now->format('Y') == $year && $now->format('m') <= 11) {
                $referenceDate = $now;
            } else {
                // Utiliser novembre 2025 par défaut pour avoir des données
                $referenceDate = new \DateTimeImmutable(sprintf('%d-11-01', $year));
            }
        }
        
        // Widgets
        $activeOrganizers = $this->statisticsRepository->countActiveOrganizersForMonth($referenceDate);
        $newOrganizers = $this->statisticsRepository->countNewOrganizersForMonth($referenceDate);
        $mostUsedSubscription = $this->statisticsRepository->findMostUsedSubscription($month, $year);
        
        // Graphiques : respecter les filtres mois/année
        $newOrganizersChart = $this->statisticsRepository->getNewOrganizersByPeriod($month, $year);
        $revenueForecast = $this->statisticsRepository->calculateRevenueForecastForPeriod($month, $year);
        $subscriptionDistribution = $this->statisticsRepository->getSubscriptionDistribution($month, $year);
        $topPayers = $this->statisticsRepository->getTopPayers(10, $month, $year);
        
        // Calculer le CA total pour le widget (TTC) - seulement pour la période filtrée
        $totalRevenue = array_sum(array_column($revenueForecast, 'revenue_ttc'));

        return [
            'widgets' => [
                'active_organizers' => $activeOrganizers ?? 0,
                'new_organizers' => $newOrganizers ?? 0,
                'most_used_subscription' => $mostUsedSubscription,
                'revenue_forecast' => $totalRevenue ?? 0,
            ],
            'charts' => [
                'new_organizers_by_month' => $newOrganizersChart,
                'subscription_distribution' => $subscriptionDistribution,
                'revenue_forecast' => $revenueForecast,
                'top_payers' => $topPayers,
            ],
        ];
    }
}

