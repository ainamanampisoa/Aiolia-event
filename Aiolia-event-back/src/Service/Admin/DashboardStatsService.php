<?php

namespace App\Service\Admin;

use App\Entity\User;
use App\Enum\Role as UserRoleEnum;
use App\Repository\Admin\StatisticsRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;

class DashboardStatsService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RevenueForecastService $revenueForecastService,
        private StatisticsRepository $statisticsRepository
    ) {
    }

    public function getDashboardData(int $month, int $year): array
    {
        $periodStart = $this->createPeriodStart($month, $year);
        $periodEnd = $periodStart->modify('first day of next month');

        // Calculer les revenus du mois en cours
        $currentMonthRevenue = $this->getCurrentMonthRevenue($periodStart, $periodEnd);
        
        // Calculer les revenus du mois précédent pour la variation
        $previousMonthStart = $periodStart->modify('-1 month');
        $previousMonthEnd = $periodStart;
        $previousMonthRevenue = $this->getCurrentMonthRevenue($previousMonthStart, $previousMonthEnd);
        
        // Calculer la variation
        $variation = $previousMonthRevenue > 0 
            ? (($currentMonthRevenue - $previousMonthRevenue) / $previousMonthRevenue) * 100 
            : 0;
        
        // Obtenir la prévision
        $forecast = $this->revenueForecastService->getNextMonthForecast(new \DateTime());

        $summary = [
            'active_organizers' => $this->countActiveOrganizers(),
            'new_organizers' => $this->countNewOrganizers($periodStart, $periodEnd),
            'top_subscription' => $this->getMostPopularSubscription($periodStart, $periodEnd),
            'global_activity_rate' => $this->formatPercentage($this->computeActivityRate()),
            'current_month_revenue' => $currentMonthRevenue,
            'revenue_variation' => $variation,
            'revenue_forecast' => $forecast,
        ];

        // Construire les données de revenus avec prévision pour le graphique
        $revenueChart = $this->buildRevenueChartWithForecast($periodStart);
        
        // Récupérer le top 5 des contributeurs fiscaux (12 derniers mois)
        $topVatContributors = $this->buildTopVatContributors($periodStart);
        
        // Calculer les statistiques fiscales du mois
        $fiscalStats = $this->getFiscalStatsForMonth($periodStart, $periodEnd);

        return [
            'summary' => $summary,
            'fiscal' => $fiscalStats,
            'charts' => [
                'new_organizers' => $this->buildNewOrganizersSeries($periodStart, $periodEnd),
                'subscriptions' => $this->buildSubscriptionsHistogram($periodStart, $periodEnd),
                'activity_rate' => $this->buildActivityRateTrend($periodStart, $periodEnd),
                'revenue_forecast' => $revenueChart,
                'top_vat_contributors' => $topVatContributors,
            ],
        ];
    }
    
    /**
     * Construit les données du top 5 des contributeurs fiscaux (TVA)
     */
    private function buildTopVatContributors(\DateTimeImmutable $periodStart): array
    {
        // Récupérer les top 5 contributeurs pour les 12 derniers mois
        $dateFrom = $periodStart->modify('-11 months');
        $dateTo = $periodStart->modify('+1 month');
        
        $topContributors = $this->statisticsRepository->getTopVatContributors(5, $dateFrom, $dateTo);
        
        // Calculer la TVA à partir du TTC (TVA = TTC - HT, où HT = TTC / 1.2)
        $labels = [];
        $vatValues = [];
        
        foreach ($topContributors['labels'] as $index => $label) {
            $ttc = $topContributors['ttc_values'][$index] ?? 0;
            $ht = $ttc / 1.2;
            $vat = $ttc - $ht;
            
            $labels[] = $label;
            $vatValues[] = $vat;
        }
        
        return [
            'label' => 'Top 5 Contributeurs TVA',
            'labels' => $labels,
            'vat_values' => $vatValues,
        ];
    }
    
    /**
     * Récupère les statistiques fiscales (HT, TVA, TTC) pour le mois
     */
    private function getFiscalStatsForMonth(\DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        $sql = <<<SQL
            SELECT 
                COALESCE(SUM(si.montant_total::numeric), 0) AS ttc_total
            FROM aiolia.factures_abonnements si
            WHERE si.statut = 'paid'
                AND si.payee_le >= :start
                AND si.payee_le < :end
        SQL;

        $result = $this->getConnection()->executeQuery(
            $sql,
            [
                'start' => $start->format('Y-m-d H:i:s'),
                'end' => $end->format('Y-m-d H:i:s'),
            ],
            [
                'start' => ParameterType::STRING,
                'end' => ParameterType::STRING,
            ]
        )->fetchAssociative();

        $ttc = (float) ($result['ttc_total'] ?? 0);
        $ht = $ttc / 1.2;
        $tva = $ttc - $ht;

        return [
            'ht' => $ht,
            'tva' => $tva,
            'ttc' => $ttc,
        ];
    }

    /**
     * Récupère les revenus TTC du mois donné
     */
    private function getCurrentMonthRevenue(\DateTimeImmutable $start, \DateTimeImmutable $end): float
    {
        $sql = <<<SQL
            SELECT COALESCE(SUM(si.montant_total::numeric), 0) AS total
            FROM aiolia.factures_abonnements si
            WHERE si.statut = 'paid'
                AND si.payee_le >= :start
                AND si.payee_le < :end
        SQL;

        $result = $this->getConnection()->executeQuery(
            $sql,
            [
                'start' => $start->format('Y-m-d H:i:s'),
                'end' => $end->format('Y-m-d H:i:s'),
            ],
            [
                'start' => ParameterType::STRING,
                'end' => ParameterType::STRING,
            ]
        )->fetchAssociative();

        return (float) ($result['total'] ?? 0);
    }

    /**
     * Construit les données de graphique de revenus avec prévision
     */
    private function buildRevenueChartWithForecast(\DateTimeImmutable $periodStart): array
    {
        // Récupérer les revenus des 6 derniers mois
        $startDate = $periodStart->modify('-5 months');
        $endDate = $periodStart->modify('+1 month');
        
        $sql = <<<SQL
            SELECT 
                DATE_TRUNC('month', si.payee_le) AS month,
                COALESCE(SUM(si.montant_total::numeric), 0) AS total
            FROM aiolia.factures_abonnements si
            WHERE si.statut = 'paid'
                AND si.payee_le >= :start
                AND si.payee_le < :end
            GROUP BY DATE_TRUNC('month', si.payee_le)
            ORDER BY month ASC
        SQL;

        $rows = $this->getConnection()->executeQuery(
            $sql,
            [
                'start' => $startDate->format('Y-m-d H:i:s'),
                'end' => $endDate->format('Y-m-d H:i:s'),
            ],
            [
                'start' => ParameterType::STRING,
                'end' => ParameterType::STRING,
            ]
        )->fetchAllAssociative();

        $labels = [];
        $values = [];
        $forecastValues = [];

        // Créer un map des revenus par mois
        $revenueMap = [];
        foreach ($rows as $row) {
            $monthKey = (new \DateTimeImmutable($row['month']))->format('Y-m');
            $revenueMap[$monthKey] = (float) $row['total'];
        }

        // Construire les 6 derniers mois + 1 mois de prévision
        $current = clone $startDate;
        $forecast = $this->revenueForecastService->getRevenueForecast(1, new \DateTime());
        $nextMonthForecast = $forecast['forecast'] ?? 0;

        for ($i = 0; $i < 7; $i++) {
            $monthKey = $current->format('Y-m');
            $labels[] = ucfirst($current->format('M'));
            
            if ($i < 6) {
                // Données réelles
                $values[] = $revenueMap[$monthKey] ?? 0;
                $forecastValues[] = null; // Pas de prévision pour les mois passés
            } else {
                // Mois suivant avec prévision
                $values[] = null; // Pas encore de données réelles
                $forecastValues[] = $nextMonthForecast;
            }
            
            $current = $current->modify('+1 month');
        }

        return [
            'label' => 'Revenus TTC',
            'labels' => $labels,
            'values' => $values,
            'forecast_values' => $forecastValues,
        ];
    }

    private function countActiveOrganizers(): int
    {
        $qb = $this->entityManager->getRepository(User::class)->createQueryBuilder('u');

        return (int) $qb
            ->select('COUNT(u.id)')
            ->where('u.role = :role')
            ->andWhere('u.statut = :status')
            ->setParameter('role', UserRoleEnum::ORGANIZER)
            ->setParameter('status', User::STATUS_ACTIVE)
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function countNewOrganizers(\DateTimeImmutable $start, \DateTimeImmutable $end): int
    {
        $qb = $this->entityManager->getRepository(User::class)->createQueryBuilder('u');

        return (int) $qb
            ->select('COUNT(u.id)')
            ->where('u.role = :role')
            ->andWhere('u.creeLe >= :start')
            ->andWhere('u.creeLe < :end')
            ->setParameter('role', UserRoleEnum::ORGANIZER)
            ->setParameter('start', $start, Types::DATETIME_IMMUTABLE)
            ->setParameter('end', $end, Types::DATETIME_IMMUTABLE)
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function getMostPopularSubscription(\DateTimeImmutable $start, \DateTimeImmutable $end): string
    {
        $sql = <<<SQL
            SELECT pa.nom AS label, COUNT(*) AS total
            FROM aiolia.abonnements_organisateurs ao
            INNER JOIN aiolia.plans_abonnements pa ON pa.id = ao.id_plan
            INNER JOIN aiolia.profils_organisateurs po ON po.id = ao.id_profil_organisateur
            INNER JOIN aiolia.utilisateurs u ON u.id = po.id_utilisateur
            WHERE ao.annule_le IS NULL
                AND u.role = :role
                AND ao.cree_le >= :start
                AND ao.cree_le < :end
            GROUP BY pa.nom
            ORDER BY total DESC
            LIMIT 1
        SQL;

        $result = $this->getConnection()->executeQuery(
            $sql,
            [
                'role' => UserRoleEnum::ORGANIZER,
                'start' => $start->format('Y-m-d H:i:s'),
                'end' => $end->format('Y-m-d H:i:s'),
            ],
            [
                'role' => ParameterType::STRING,
                'start' => ParameterType::STRING,
                'end' => ParameterType::STRING,
            ]
        )->fetchAssociative();

        return $result['label'] ?? 'Aucun abonnement';
    }

    private function computeActivityRate(): float
    {
        $repo = $this->entityManager->getRepository(User::class);

        $total = (int) $repo->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.role = :role')
            ->setParameter('role', UserRoleEnum::ORGANIZER)
            ->getQuery()
            ->getSingleScalarResult();

        if ($total === 0) {
            return 0.0;
        }

        return ($this->countActiveOrganizers() / $total) * 100;
    }

    private function buildNewOrganizersSeries(\DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        $sql = <<<SQL
            SELECT DATE_TRUNC('day', u.cree_le) AS bucket, COUNT(*) AS total
            FROM aiolia.utilisateurs u
            WHERE u.role = :role
                AND u.cree_le >= :start
                AND u.cree_le < :end
            GROUP BY bucket
            ORDER BY bucket ASC
        SQL;

        $rows = $this->getConnection()->executeQuery(
            $sql,
            [
                'role' => UserRoleEnum::ORGANIZER,
                'start' => $start->format('Y-m-d H:i:s'),
                'end' => $end->format('Y-m-d H:i:s'),
            ],
            [
                'role' => ParameterType::STRING,
                'start' => ParameterType::STRING,
                'end' => ParameterType::STRING,
            ]
        )->fetchAllAssociative();

        $labels = [];
        $values = [];

        foreach ($rows as $row) {
            $day = new \DateTimeImmutable($row['bucket']);
            $labels[] = $day->format('d/m');
            $values[] = (int) $row['total'];
        }

        if ($labels === []) {
            $labels[] = $start->format('d/m');
            $values[] = 0;
        }

        return [
            'label' => 'Nouveaux organisateurs',
            'labels' => $labels,
            'values' => $values,
        ];
    }

    private function buildSubscriptionsHistogram(\DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        $sql = <<<SQL
            SELECT pa.nom AS label, COUNT(*) AS total
            FROM aiolia.abonnements_organisateurs ao
            INNER JOIN aiolia.plans_abonnements pa ON pa.id = ao.id_plan
            INNER JOIN aiolia.profils_organisateurs po ON po.id = ao.id_profil_organisateur
            INNER JOIN aiolia.utilisateurs u ON u.id = po.id_utilisateur
            WHERE ao.cree_le >= :start
                AND ao.cree_le < :end
                AND u.role = :role
            GROUP BY pa.nom
            ORDER BY total DESC
        SQL;

        $rows = $this->getConnection()->executeQuery(
            $sql,
            [
                'start' => $start->format('Y-m-d H:i:s'),
                'end' => $end->format('Y-m-d H:i:s'),
                'role' => UserRoleEnum::ORGANIZER,
            ],
            [
                'start' => ParameterType::STRING,
                'end' => ParameterType::STRING,
                'role' => ParameterType::STRING,
            ]
        )->fetchAllAssociative();

        $labels = [];
        $values = [];

        foreach ($rows as $row) {
            $labels[] = $row['label'];
            $values[] = (int) $row['total'];
        }

        if ($labels === []) {
            $labels = ['Plan Basic', 'Plan Pro', 'Entreprise'];
            $values = [0, 0, 0];
        }

        return [
            'label' => 'Répartition abonnements',
            'labels' => $labels,
            'values' => $values,
        ];
    }

    private function buildActivityRateTrend(\DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        $trendStart = $start->modify('-5 months')->setTime(0, 0);
        $trendEnd = $end;

        $sql = <<<SQL
            SELECT DATE_TRUNC('month', u.cree_le) AS bucket,
                   SUM(CASE WHEN u.statut = :active THEN 1 ELSE 0 END) AS active_total,
                   COUNT(*) AS total
            FROM aiolia.utilisateurs u
            WHERE u.role = :role
                AND u.cree_le >= :start
                AND u.cree_le < :end
            GROUP BY bucket
            ORDER BY bucket ASC
        SQL;

        $rows = $this->getConnection()->executeQuery(
            $sql,
            [
                'role' => UserRoleEnum::ORGANIZER,
                'active' => User::STATUS_ACTIVE,
                'start' => $trendStart->format('Y-m-d H:i:s'),
                'end' => $trendEnd->format('Y-m-d H:i:s'),
            ],
            [
                'role' => ParameterType::STRING,
                'active' => ParameterType::INTEGER,
                'start' => ParameterType::STRING,
                'end' => ParameterType::STRING,
            ]
        )->fetchAllAssociative();

        $map = [];
        foreach ($rows as $row) {
            $bucket = (new \DateTimeImmutable($row['bucket']))->format('Y-m');
            $total = (int) $row['total'];
            $active = (int) $row['active_total'];
            $map[$bucket] = $total > 0 ? round(($active / $total) * 100, 1) : 0.0;
        }

        $labels = [];
        $values = [];
        $cursor = $trendStart;

        while ($cursor < $trendEnd) {
            $key = $cursor->format('Y-m');
            $labels[] = ucfirst($cursor->format('M'));
            $values[] = $map[$key] ?? 0;
            $cursor = $cursor->modify('first day of next month');
        }

        if ($labels === []) {
            $labels = [$start->format('M')];
            $values = [0];
        }

        return [
            'label' => 'Taux d’activité',
            'labels' => $labels,
            'values' => $values,
        ];
    }

    private function createPeriodStart(int $month, int $year): \DateTimeImmutable
    {
        $month = max(1, min(12, $month));
        $year = max(1970, $year);

        return (new \DateTimeImmutable())
            ->setDate($year, $month, 1)
            ->setTime(0, 0);
    }

    private function formatPercentage(float $value): string
    {
        return number_format($value, 1, ',', ' ') . ' %';
    }

    private function getConnection(): Connection
    {
        return $this->entityManager->getConnection();
    }
}


