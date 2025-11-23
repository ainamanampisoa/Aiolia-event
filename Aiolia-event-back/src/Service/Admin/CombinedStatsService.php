<?php

namespace App\Service\Admin;

use App\Repository\Admin\StatisticsRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service pour combiner les données de statistiques et rapports
 */
class CombinedStatsService
{
    public function __construct(
        private StatisticsService $statisticsService,
        private StatisticsRepository $statisticsRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Récupère toutes les données combinées pour l'interface Statistiques/Rapports
     * 
     * @param \DateTimeInterface|null $dateFrom
     * @param \DateTimeInterface|null $dateTo
     * @param string|null $plan
     * @param int|null $organizerId
     * @return array
     */
    public function getCombinedStats(
        ?\DateTimeInterface $dateFrom = null,
        ?\DateTimeInterface $dateTo = null,
        ?string $plan = null,
        ?int $organizerId = null
    ): array {
        // Récupérer toutes les statistiques de base
        $stats = $this->statisticsService->getAllStatistics($dateFrom, $dateTo, $plan, $organizerId);

        // Ajouter les données spécifiques pour les widgets combinés
        return [
            'counts' => $stats['counts'],
            'organizers' => $stats['organizers'],
            'subscriptions' => $stats['subscriptions'],
            'tax' => $stats['tax'],
            'fiscal' => $stats['fiscal'],
            'unpaid' => $stats['unpaid'],
            'payment_methods' => $stats['payment_methods'],
            
            // Widget 1: Revenus Fiscaux Consolidés
            'fiscal_consolidated' => [
                'by_month' => $stats['fiscal']['by_month'],
                'totals' => $stats['fiscal']['totals'],
                'variation' => $this->calculatePeriodVariation($dateFrom, $dateTo),
            ],
            
            // Widget 2: Répartition des Abonnements
            'subscriptions_distribution' => [
                'by_plan' => $stats['organizers']['plans'],
                'total_revenue' => $stats['organizers']['subscription_revenue_total'],
                'counts_by_plan' => $this->getSubscriptionCountsByPlan($dateFrom, $dateTo, $plan),
            ],
            
            // Widget 3: Top 5 Contributeurs Fiscaux
            'top_contributors' => [
                'vat_contributors' => $stats['fiscal']['top_vat_contributors'],
                'top_payers' => $this->statisticsRepository->getTopPayers(5, 30, $dateFrom, $dateTo, $plan),
            ],
            
            // Widget 4: Analyse des Impayés
            'unpaid_analysis' => [
                'summary' => [
                    'overdue_count' => $stats['unpaid']['overdue_count'],
                    'overdue_amount' => $stats['unpaid']['overdue_amount'],
                    'pending_count' => $stats['unpaid']['pending_count'],
                    'pending_amount' => $stats['unpaid']['pending_amount'],
                ],
                'invoices' => $stats['unpaid']['invoices'],
                'by_month' => $this->getUnpaidByMonth($dateFrom, $dateTo, $plan, $organizerId),
            ],
        ];
    }

    /**
     * Calcule la variation en pourcentage par rapport à la période précédente
     */
    private function calculatePeriodVariation(?\DateTimeInterface $dateFrom, ?\DateTimeInterface $dateTo): ?float
    {
        if (!$dateFrom || !$dateTo) {
            return null;
        }

        $periodDuration = $dateFrom->diff($dateTo)->days;
        
        // Calculer la période précédente de même durée
        $prevPeriodEnd = new \DateTime($dateFrom->format('Y-m-d H:i:s'));
        $prevPeriodEnd->modify('-1 day');
        $prevPeriodStart = new \DateTime($prevPeriodEnd->format('Y-m-d H:i:s'));
        $prevPeriodStart->modify("-{$periodDuration} days");

        $currentRevenue = $this->statisticsRepository->getSubscriptionRevenueTotal($dateFrom, $dateTo);
        $previousRevenue = $this->statisticsRepository->getSubscriptionRevenueTotal($prevPeriodStart, $prevPeriodEnd);

        if ($previousRevenue == 0) {
            return $currentRevenue > 0 ? 100 : 0;
        }

        return (($currentRevenue - $previousRevenue) / $previousRevenue) * 100;
    }

    /**
     * Récupère le nombre d'abonnements par plan
     */
    private function getSubscriptionCountsByPlan(?\DateTimeInterface $dateFrom = null, ?\DateTimeInterface $dateTo = null, ?string $plan = null): array
    {
        $conn = $this->entityManager->getConnection();
        
        $sql = "
            SELECT 
                sp.nom AS plan_name,
                COUNT(DISTINCT os.id) AS count
            FROM aiolia.plans_abonnements sp
            LEFT JOIN aiolia.abonnements_organisateurs os ON os.id_plan = sp.id
            WHERE os.statut = 'active' OR os.id IS NULL
        ";
        
        $params = [];
        $types = [];
        
        if ($plan) {
            $sql .= " AND (sp.niveau = :plan OR os.id IS NULL)";
            $params['plan'] = $plan;
            $types['plan'] = \Doctrine\DBAL\Types\Types::STRING;
        }
        
        $sql .= " GROUP BY sp.id, sp.nom, sp.ordre_affichage ORDER BY sp.ordre_affichage ASC";
        
        $result = empty($params)
            ? $conn->executeQuery($sql)
            : $conn->executeQuery($sql, $params, $types);
        
        $counts = [];
        while ($row = $result->fetchAssociative()) {
            $counts[$row['plan_name']] = (int) $row['count'];
        }
        
        return $counts;
    }

    /**
     * Récupère les données d'impayés par mois pour le graphique
     */
    private function getUnpaidByMonth(?\DateTimeInterface $dateFrom = null, ?\DateTimeInterface $dateTo = null, ?string $plan = null, ?int $organizerId = null): array
    {
        $conn = $this->entityManager->getConnection();
        
        // Si pas de filtre, utiliser les 12 derniers mois
        if (!$dateFrom || !$dateTo) {
            $dateTo = new \DateTime();
            $dateFrom = clone $dateTo;
            $dateFrom->modify('-11 months');
        }
        
        $startMonth = new \DateTime($dateFrom->format('Y-m-01'));
        $endMonth = new \DateTime($dateTo->format('Y-m-01'));
        
        $sql = "
            WITH month_series AS (
                SELECT generate_series(
                    date_trunc('month', :startDate::date),
                    date_trunc('month', :endDate::date),
                    '1 month'::interval
                )::date AS month_start
            )
            SELECT 
                ms.month_start,
                TO_CHAR(ms.month_start, 'TMMonth YYYY') AS month_label,
                COUNT(CASE WHEN si.statut = 'overdue' THEN 1 END) AS overdue_count,
                COALESCE(SUM(CASE WHEN si.statut = 'overdue' THEN si.montant_total::numeric ELSE 0 END), 0) AS overdue_amount
            FROM month_series ms
            LEFT JOIN aiolia.factures_abonnements si 
                ON si.statut = 'overdue'
                AND date_trunc('month', si.echeance_le) = ms.month_start
                AND si.echeance_le >= :dateFromFilter::timestamp
                AND si.echeance_le <= :dateToFilter::timestamp
        ";
        
        $params = [
            'startDate' => $startMonth->format('Y-m-d'),
            'endDate' => $endMonth->format('Y-m-d'),
            'dateFromFilter' => $dateFrom->format('Y-m-d H:i:s'),
            'dateToFilter' => $dateTo->format('Y-m-d H:i:s'),
        ];
        $types = [
            'startDate' => \Doctrine\DBAL\Types\Types::STRING,
            'endDate' => \Doctrine\DBAL\Types\Types::STRING,
            'dateFromFilter' => \Doctrine\DBAL\Types\Types::STRING,
            'dateToFilter' => \Doctrine\DBAL\Types\Types::STRING,
        ];
        
        if ($plan) {
            $sql .= "
                LEFT JOIN aiolia.abonnements_organisateurs os ON os.id = si.id_abonnement
                LEFT JOIN aiolia.plans_abonnements sp ON sp.id = os.id_plan
                WHERE (sp.niveau = :plan OR si.id IS NULL)
            ";
            $params['plan'] = $plan;
            $types['plan'] = \Doctrine\DBAL\Types\Types::STRING;
        }
        
        if ($organizerId) {
            $sql .= " AND si.id_client = :organizerId";
            $params['organizerId'] = $organizerId;
            $types['organizerId'] = \Doctrine\DBAL\Types\Types::INTEGER;
        }
        
        $sql .= " GROUP BY ms.month_start ORDER BY ms.month_start ASC";
        
        $result = $conn->executeQuery($sql, $params, $types);
        
        $labels = [];
        $overdueCounts = [];
        $overdueAmounts = [];
        
        $monthNames = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
        
        while ($row = $result->fetchAssociative()) {
            $date = new \DateTime($row['month_start']);
            $monthNum = (int) $date->format('n');
            $year = $date->format('Y');
            $labels[] = $monthNames[$monthNum] . ' ' . $year;
            $overdueCounts[] = (int) $row['overdue_count'];
            $overdueAmounts[] = (float) $row['overdue_amount'];
        }
        
        // Si aucun mois n'a été trouvé, créer la série
        if (empty($labels)) {
            $current = clone $startMonth;
            while ($current <= $endMonth) {
                $monthNum = (int) $current->format('n');
                $year = $current->format('Y');
                $labels[] = $monthNames[$monthNum] . ' ' . $year;
                $overdueCounts[] = 0;
                $overdueAmounts[] = 0;
                $current->modify('+1 month');
            }
        }
        
        return [
            'labels' => $labels,
            'overdue_counts' => $overdueCounts,
            'overdue_amounts' => $overdueAmounts,
        ];
    }
}

