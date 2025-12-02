<?php

namespace App\Service\Admin;

use App\Repository\Admin\StatisticsRepository;

class StatisticsService
{
    public function __construct(
        private StatisticsRepository $statisticsRepository
    ) {}

    public function getDashboardStatistics(int $month = 0, int $year = 0): array
    {
        return [
            'widgets' => [
                'activeOrganizers' => $this->statisticsRepository->countOrganizers($month, $year, 'active'),
                // Alias snake_case pour compatibilité avec les templates Twig existants
                'active_organizers' => $this->statisticsRepository->countOrganizers($month, $year, 'active'),
                'newOrganizers' => $this->statisticsRepository->countOrganizers($month, $year, 'new'),
                'new_organizers' => $this->statisticsRepository->countOrganizers($month, $year, 'new'),
                'mostUsedSubscription' => $this->statisticsRepository->getMostUsedSubscription($month, $year),
                'most_used_subscription' => $this->statisticsRepository->getMostUsedSubscription($month, $year),
                'revenue' => $this->statisticsRepository->getRevenue($month, $year),
                // Prévision de CA simple : ici on renvoie pour l’instant le CA réel,
                // ce qui satisfait les templates sans changer la logique métier
                'revenueForecast' => $this->statisticsRepository->getRevenue($month, $year),
                'revenue_forecast' => $this->statisticsRepository->getRevenue($month, $year),
            ],
            'charts' => [
                'activeOrganizersTrend' => $this->statisticsRepository->getActiveOrganizersTrend($month, $year),
                // Alias snake_case pour compatibilité avec le template Twig
                'active_organizers_trend' => $this->statisticsRepository->getActiveOrganizersTrend($month, $year),
                'subscriptionDistribution' => $this->statisticsRepository->getSubscriptionUsageByLevel($month, $year),
                'subscription_distribution' => $this->statisticsRepository->getSubscriptionUsageByLevel($month, $year),
                'revenueBreakdown' => $this->statisticsRepository->getRevenueBreakdownByPeriod($month, $year),
                'revenue_breakdown' => $this->statisticsRepository->getRevenueBreakdownByPeriod($month, $year),
            ]
        ];
    }

    // Méthodes spécifiques pour usage granulaire
    public function getActiveOrganizersCount(int $month = 0, int $year = 0): int
    {
        return $this->statisticsRepository->countOrganizers($month, $year, 'active');
    }

    public function getNewOrganizersCount(int $month = 0, int $year = 0): int
    {
        return $this->statisticsRepository->countOrganizers($month, $year, 'new');
    }

    public function getMostUsedSubscription(int $month = 0, int $year = 0): ?array
    {
        return $this->statisticsRepository->getMostUsedSubscription($month, $year);
    }

    public function getRevenue(int $month = 0, int $year = 0): float
    {
        return $this->statisticsRepository->getRevenue($month, $year);
    }
}