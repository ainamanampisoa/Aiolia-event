<?php

namespace App\Service\Admin;

use App\Repository\Admin\StatisticsRepository;

class StatisticsService
{
    public function __construct(
        private StatisticsRepository $statisticsRepository
    ) {
    }

    public function getStatistics(int $month = 0, int $year = 2025): array
    {
        $widgets = [
            'active_organizers' => $this->statisticsRepository->organisateurActifs($month, $year),
            'new_organizers' => $this->statisticsRepository->newsOrganisateur($month, $year),
            'most_used_subscription' => $this->statisticsRepository->abonnemnentPLusActifs($month, $year),
            'revenue_forecast' => $this->statisticsRepository->chiffreAffaireCA($month, $year),
        ];

        $charts = [
            'active_organizers_trend' => $this->statisticsRepository->getActiveOrganizersTrend($month, $year),
            'subscription_distribution' => $this->statisticsRepository->getSubscriptionUsageByLevel($month, $year),
            'revenue_breakdown' => $this->statisticsRepository->getRevenueBreakdownByPeriod($month, $year),
        ];

        return [
            'widgets' => $widgets,
            'charts' => $charts,
        ];
    }
}

