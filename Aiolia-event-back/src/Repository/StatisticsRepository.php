<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Repository pour les statistiques de la plateforme
 */
class StatisticsRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Récupère l'EntityManager
     */
    private function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    /**
     * Compte le nombre total d'organisateurs (utilisateurs avec rôle 'organizer')
     */
    public function countOrganizers(): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        
        return (int) $qb->select('COUNT(u.id)')
            ->from(User::class, 'u')
            ->where("u.role = 'organizer'")
            ->andWhere('u.statut = :statut')
            ->setParameter('statut', User::STATUS_ACTIVE, Types::SMALLINT)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte le nombre total d'utilisateurs actifs
     */
    public function countUsers(): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        
        return (int) $qb->select('COUNT(u.id)')
            ->from(User::class, 'u')
            ->where('u.statut = :statut')
            ->setParameter('statut', User::STATUS_ACTIVE, Types::SMALLINT)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte le nombre d'abonnements actifs
     * Un abonnement est actif si son statut est 'active'
     */
    public function countActiveSubscriptions(): int
    {
        $conn = $this->getEntityManager()->getConnection();
        
        $sql = "SELECT COUNT(*) 
                FROM aiolia.abonnements_organisateurs 
                WHERE statut = 'active'";
        
        return (int) $conn->fetchOne($sql);
    }

    /**
     * Compte le nombre de factures payées
     */
    public function countPaidInvoices(?\DateTimeInterface $dateFrom = null, ?\DateTimeInterface $dateTo = null): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        
        $qb->select('COUNT(si.id)')
            ->from('App\Entity\SubscriptionInvoice', 'si')
            ->where("si.status = 'paid'");
        
        if ($dateFrom) {
            $qb->andWhere('si.paidAt >= :dateFrom')
                ->setParameter('dateFrom', $dateFrom);
        }
        
        if ($dateTo) {
            $qb->andWhere('si.paidAt <= :dateTo')
                ->setParameter('dateTo', $dateTo);
        }
        
        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Calcule le total des revenus d'abonnements (factures payées uniquement)
     */
    public function getSubscriptionRevenueTotal(?\DateTimeInterface $dateFrom = null, ?\DateTimeInterface $dateTo = null): float
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        
        $qb->select('COALESCE(SUM(si.totalAmount), 0)')
            ->from('App\Entity\SubscriptionInvoice', 'si')
            ->where("si.status = 'paid'");
        
        if ($dateFrom) {
            $qb->andWhere('si.paidAt >= :dateFrom')
                ->setParameter('dateFrom', $dateFrom);
        }
        
        if ($dateTo) {
            $qb->andWhere('si.paidAt <= :dateTo')
                ->setParameter('dateTo', $dateTo);
        }
        
        $result = $qb->getQuery()->getSingleScalarResult();
        
        return (float) $result;
    }

    /**
     * Récupère l'évolution des abonnements actifs par mois
     * Retourne un tableau avec labels (mois) et values (nombre d'abonnements)
     */
    public function getSubscriptionsEvolution(?\DateTimeInterface $dateFrom = null, ?\DateTimeInterface $dateTo = null): array
    {
        $conn = $this->getEntityManager()->getConnection();
        
        // Si pas de filtre, utiliser les 12 derniers mois
        if (!$dateFrom || !$dateTo) {
            $dateTo = new \DateTime();
            $dateFrom = clone $dateTo;
            $dateFrom->modify('-11 months');
        }
        
        // S'assurer que les dates sont au début du mois
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
                COALESCE(COUNT(DISTINCT os.id), 0) AS count
            FROM month_series ms
            LEFT JOIN aiolia.abonnements_organisateurs os 
                ON os.statut = 'active' 
                AND date_trunc('month', os.commence_le) <= ms.month_start
                AND (os.annule_le IS NULL OR date_trunc('month', os.annule_le) >= ms.month_start)
            GROUP BY ms.month_start
            ORDER BY ms.month_start ASC
        ";
        
        $result = $conn->executeQuery($sql, 
            [
                'startDate' => $startMonth->format('Y-m-d'),
                'endDate' => $endMonth->format('Y-m-d')
            ], 
            [
                'startDate' => Types::STRING,
                'endDate' => Types::STRING
            ]
        );
        
        $labels = [];
        $values = [];
        
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
            $values[] = (int) $row['count'];
        }
        
        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    /**
     * Récupère les revenus par plan d'abonnement par mois
     * Retourne un tableau avec labels (mois), et pour chaque plan (Basic, Pro, Entreprise) les revenus mensuels
     */
    public function getRevenueByPlanByMonth(?\DateTimeInterface $dateFrom = null, ?\DateTimeInterface $dateTo = null): array
    {
        $conn = $this->getEntityManager()->getConnection();
        
        // Si pas de filtre, utiliser les 12 derniers mois
        if (!$dateFrom || !$dateTo) {
            $dateTo = new \DateTime();
            $dateFrom = clone $dateTo;
            $dateFrom->modify('-11 months');
        }
        
        // S'assurer que les dates sont au début du mois
        $startMonth = new \DateTime($dateFrom->format('Y-m-01'));
        $endMonth = new \DateTime($dateTo->format('Y-m-01'));
        
        $sql = "
            WITH month_series AS (
                SELECT generate_series(
                    date_trunc('month', :startDate::date),
                    date_trunc('month', :endDate::date),
                    '1 month'::interval
                )::date AS month_start
            ),
            all_combinations AS (
                SELECT ms.month_start, sp.id AS plan_id, sp.nom AS plan_name, sp.ordre_affichage
                FROM month_series ms
                CROSS JOIN aiolia.plans_abonnements sp
            )
            SELECT 
                ac.month_start,
                TO_CHAR(ac.month_start, 'TMMonth YYYY') AS month_label,
                ac.plan_name,
                COALESCE(SUM(si.montant_total::numeric), 0) AS revenue
            FROM all_combinations ac
            LEFT JOIN aiolia.abonnements_organisateurs os ON os.id_plan = ac.plan_id
            LEFT JOIN aiolia.factures_abonnements si 
                ON si.id_abonnement = os.id 
                AND si.statut = 'paid'
                AND date_trunc('month', si.payee_le) = ac.month_start
            GROUP BY ac.month_start, ac.plan_id, ac.plan_name, ac.ordre_affichage
            ORDER BY ac.month_start ASC, ac.ordre_affichage ASC
        ";
        
        $result = $conn->executeQuery($sql, 
            [
                'startDate' => $startMonth->format('Y-m-d'),
                'endDate' => $endMonth->format('Y-m-d')
            ], 
            [
                'startDate' => Types::STRING,
                'endDate' => Types::STRING
            ]
        );
        
        $monthNames = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
        
        // D'abord, récupérer tous les mois uniques et tous les plans
        $allMonths = [];
        $allPlans = [];
        $dataByPlan = [];
        
        // Parcourir les résultats pour construire les structures
        while ($row = $result->fetchAssociative()) {
            $date = new \DateTime($row['month_start']);
            $monthNum = (int) $date->format('n');
            $year = $date->format('Y');
            $monthLabel = $monthNames[$monthNum] . ' ' . $year;
            $planName = $row['plan_name'];
            $revenue = (float) $row['revenue'];
            
            // Ajouter le mois s'il n'existe pas
            if (!in_array($monthLabel, $allMonths)) {
                $allMonths[] = $monthLabel;
            }
            
            // Ajouter le plan s'il n'existe pas
            if (!in_array($planName, $allPlans)) {
                $allPlans[] = $planName;
            }
            
            // Initialiser le plan s'il n'existe pas
            if (!isset($dataByPlan[$planName])) {
                $dataByPlan[$planName] = [];
            }
            
            // Stocker le revenu pour ce mois et ce plan
            $dataByPlan[$planName][$monthLabel] = $revenue;
        }
        
        // Si aucun mois n'a été trouvé, créer la série de mois
        if (empty($allMonths)) {
            $current = clone $startMonth;
            while ($current <= $endMonth) {
                $monthNum = (int) $current->format('n');
                $year = $current->format('Y');
                $allMonths[] = $monthNames[$monthNum] . ' ' . $year;
                $current->modify('+1 month');
            }
        }
        
        // Si aucun plan n'a été trouvé, récupérer tous les plans depuis la base
        if (empty($allPlans)) {
            $plansQuery = $conn->executeQuery("SELECT nom FROM aiolia.plans_abonnements ORDER BY ordre_affichage ASC");
            while ($planRow = $plansQuery->fetchAssociative()) {
                $allPlans[] = $planRow['nom'];
            }
        }
        
        // Trier les mois
        sort($allMonths);
        $labels = $allMonths;
        
        // S'assurer que tous les plans ont des valeurs pour tous les mois
        foreach ($allPlans as $planName) {
            if (!isset($dataByPlan[$planName])) {
                $dataByPlan[$planName] = [];
            }
            $values = [];
            foreach ($labels as $monthLabel) {
                $values[] = $dataByPlan[$planName][$monthLabel] ?? 0;
            }
            $dataByPlan[$planName] = $values;
        }
        
        return [
            'labels' => $labels,
            'plans' => $dataByPlan,
        ];
    }

    /**
     * Récupère les revenus par plan d'abonnement (Basic, Pro, Enterprise)
     * Retourne un tableau avec labels (noms des plans) et revenue_values (montants)
     */
    public function getRevenueByPlan(?\DateTimeInterface $dateFrom = null, ?\DateTimeInterface $dateTo = null): array
    {
        $conn = $this->getEntityManager()->getConnection();
        
        $sql = "
            SELECT 
                sp.niveau,
                sp.nom,
                COALESCE(SUM(si.montant_total::numeric), 0) AS revenue
            FROM aiolia.plans_abonnements sp
            LEFT JOIN aiolia.abonnements_organisateurs os ON os.id_plan = sp.id
            LEFT JOIN aiolia.factures_abonnements si 
                ON si.id_abonnement = os.id 
                AND si.statut = 'paid'
        ";
        
        $params = [];
        $types = [];
        $conditions = [];
        
        if ($dateFrom) {
            $conditions[] = "si.payee_le >= :dateFrom";
            $params['dateFrom'] = $dateFrom;
            $types['dateFrom'] = Types::DATETIMETZ_MUTABLE;
        }
        
        if ($dateTo) {
            $conditions[] = "si.payee_le <= :dateTo";
            $params['dateTo'] = $dateTo;
            $types['dateTo'] = Types::DATETIMETZ_MUTABLE;
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $sql .= "
            GROUP BY sp.id, sp.niveau, sp.nom
            ORDER BY sp.ordre_affichage ASC
        ";
        
        $result = empty($params) 
            ? $conn->executeQuery($sql)
            : $conn->executeQuery($sql, $params, $types);
        
        $labels = [];
        $revenueValues = [];
        
        while ($row = $result->fetchAssociative()) {
            $labels[] = $row['nom'];
            $revenueValues[] = (float) $row['revenue'];
        }
        
        return [
            'labels' => $labels,
            'revenue_values' => $revenueValues,
        ];
    }

    /**
     * Récupère les top payeurs (organisateurs ayant payé le plus)
     * Retourne un tableau avec labels (noms des organisateurs) et values (montants totaux)
     */
    public function getTopPayers(int $limit = 10, int $days = 30, ?\DateTimeInterface $dateFrom = null, ?\DateTimeInterface $dateTo = null): array
    {
        $conn = $this->getEntityManager()->getConnection();
        
        $sql = "
            SELECT 
                CONCAT(u.prenom, ' ', COALESCE(u.nom, '')) AS organizer_name,
                COALESCE(SUM(si.montant_total::numeric), 0) AS total_paid
            FROM aiolia.factures_abonnements si
            INNER JOIN aiolia.utilisateurs u ON u.id = si.id_client
            WHERE si.statut = 'paid'
        ";
        
        $params = [];
        $types = [];
        
        if ($dateFrom) {
            // Utiliser le premier jour du mois de début
            $sql .= " AND si.payee_le >= :dateFrom";
            $params['dateFrom'] = $dateFrom;
            $types['dateFrom'] = Types::DATETIMETZ_MUTABLE;
        } elseif (!$dateTo) {
            // Si pas de filtre de date, utiliser les 12 derniers mois
            $sql .= " AND si.payee_le >= date_trunc('month', CURRENT_DATE) - INTERVAL '11 months'";
        }
        
        if ($dateTo) {
            // Utiliser le dernier jour du mois de fin
            $sql .= " AND si.payee_le <= :dateTo";
            $params['dateTo'] = $dateTo;
            $types['dateTo'] = Types::DATETIMETZ_MUTABLE;
        }
        
        $sql .= "
            GROUP BY u.id, u.prenom, u.nom
            ORDER BY total_paid DESC
            LIMIT :limit
        ";
        
        $params['limit'] = $limit;
        $types['limit'] = Types::INTEGER;
        
        $result = $conn->executeQuery($sql, $params, $types);
        
        $labels = [];
        $values = [];
        
        while ($row = $result->fetchAssociative()) {
            $labels[] = $row['organizer_name'];
            $values[] = (float) $row['total_paid'];
        }
        
        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    /**
     * Calcule les statistiques fiscales (revenus bruts, TVA, commissions, revenus nets)
     * 
     * Formules :
     * - Revenus bruts = Somme de toutes les factures payées (total_amount)
     * - TVA = Revenus bruts * (taux_tva / 100) où taux_tva est généralement 20% (0.20)
     * - Commissions plateforme = Revenus bruts * (taux_commission / 100) où taux_commission est généralement 5% (0.05)
     * - Revenus nets = Revenus bruts - TVA - Commissions plateforme
     */
    public function getTaxStatistics(float $vatRate = 0.20, float $commissionRate = 0.05, ?\DateTimeInterface $dateFrom = null, ?\DateTimeInterface $dateTo = null): array
    {
        $conn = $this->getEntityManager()->getConnection();
        
        // Revenus bruts = somme des montant_total des factures payées
        $sql = "
            SELECT COALESCE(SUM(montant_total::numeric), 0) AS gross_revenue
            FROM aiolia.factures_abonnements
            WHERE statut = 'paid'
        ";
        
        $params = [];
        $types = [];
        
        if ($dateFrom) {
            $sql .= " AND payee_le >= :dateFrom";
            $params['dateFrom'] = $dateFrom;
            $types['dateFrom'] = Types::DATETIMETZ_MUTABLE;
        }
        
        if ($dateTo) {
            $sql .= " AND payee_le <= :dateTo";
            $params['dateTo'] = $dateTo;
            $types['dateTo'] = Types::DATETIMETZ_MUTABLE;
        }
        
        $grossRevenue = empty($params)
            ? (float) $conn->fetchOne($sql)
            : (float) $conn->fetchOne($sql, $params, $types);
        
        // TVA = Revenus bruts * taux TVA
        $vat = $grossRevenue * $vatRate;
        
        // Commissions plateforme = Revenus bruts * taux commission
        $platformCommission = $grossRevenue * $commissionRate;
        
        // Revenus nets = Revenus bruts - TVA - Commissions
        $netRevenue = $grossRevenue - $vat - $platformCommission;
        
        return [
            'gross_revenue' => $grossRevenue,
            'vat' => $vat,
            'platform_commission' => $platformCommission,
            'net_revenue' => $netRevenue,
            'vat_rate' => $vatRate,
            'commission_rate' => $commissionRate,
        ];
    }

    /**
     * Récupère les revenus HT, TVA et TTC par mois
     * Les calculs sont faits dans le métier (service) à partir des montants TTC
     * 
     * @param \DateTimeInterface|null $dateFrom Date de début du filtre
     * @param \DateTimeInterface|null $dateTo Date de fin du filtre
     * @return array ['labels' => [], 'ht_values' => [], 'tva_values' => [], 'ttc_values' => []]
     */
    public function getFiscalStatisticsByMonth(?\DateTimeInterface $dateFrom = null, ?\DateTimeInterface $dateTo = null): array
    {
        $conn = $this->getEntityManager()->getConnection();
        
        // Si pas de filtre, utiliser les 12 derniers mois
        if (!$dateFrom || !$dateTo) {
            $dateTo = new \DateTime();
            $dateFrom = clone $dateTo;
            $dateFrom->modify('-11 months');
        }
        
        // S'assurer que les dates sont au début du mois
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
                COALESCE(SUM(si.montant_total::numeric), 0) AS ttc_total
            FROM month_series ms
            LEFT JOIN aiolia.factures_abonnements si 
                ON si.statut = 'paid'
                AND date_trunc('month', si.payee_le) = ms.month_start
            GROUP BY ms.month_start
            ORDER BY ms.month_start ASC
        ";
        
        $result = $conn->executeQuery($sql, 
            [
                'startDate' => $startMonth->format('Y-m-d'),
                'endDate' => $endMonth->format('Y-m-d')
            ], 
            [
                'startDate' => Types::STRING,
                'endDate' => Types::STRING
            ]
        );
        
        $labels = [];
        $ttcValues = [];
        
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
            $ttcValues[] = (float) $row['ttc_total'];
        }
        
        // Si aucun mois n'a été trouvé, créer la série de mois
        if (empty($labels)) {
            $current = clone $startMonth;
            while ($current <= $endMonth) {
                $monthNum = (int) $current->format('n');
                $year = $current->format('Y');
                $labels[] = $monthNames[$monthNum] . ' ' . $year;
                $ttcValues[] = 0;
                $current->modify('+1 month');
            }
        }
        
        return [
            'labels' => $labels,
            'ttc_values' => $ttcValues,
        ];
    }

    /**
     * Récupère le top des organisateurs contributeurs à la TVA
     * 
     * @param int $limit Nombre d'organisateurs à retourner
     * @param \DateTimeInterface|null $dateFrom Date de début du filtre
     * @param \DateTimeInterface|null $dateTo Date de fin du filtre
     * @return array ['labels' => [], 'vat_values' => []]
     */
    public function getTopVatContributors(int $limit = 10, ?\DateTimeInterface $dateFrom = null, ?\DateTimeInterface $dateTo = null): array
    {
        $conn = $this->getEntityManager()->getConnection();
        
        $sql = "
            SELECT 
                CONCAT(u.prenom, ' ', COALESCE(u.nom, '')) AS organizer_name,
                COALESCE(SUM(si.montant_total::numeric), 0) AS ttc_total
            FROM aiolia.factures_abonnements si
            INNER JOIN aiolia.utilisateurs u ON u.id = si.id_client
            WHERE si.statut = 'paid'
        ";
        
        $params = [];
        $types = [];
        
        if ($dateFrom) {
            $sql .= " AND si.payee_le >= :dateFrom";
            $params['dateFrom'] = $dateFrom;
            $types['dateFrom'] = Types::DATETIMETZ_MUTABLE;
        } elseif (!$dateTo) {
            // Si pas de filtre de date, utiliser les 12 derniers mois
            $sql .= " AND si.payee_le >= date_trunc('month', CURRENT_DATE) - INTERVAL '11 months'";
        }
        
        if ($dateTo) {
            $sql .= " AND si.payee_le <= :dateTo";
            $params['dateTo'] = $dateTo;
            $types['dateTo'] = Types::DATETIMETZ_MUTABLE;
        }
        
        $sql .= "
            GROUP BY u.id, u.prenom, u.nom
            ORDER BY ttc_total DESC
            LIMIT :limit
        ";
        
        $params['limit'] = $limit;
        $types['limit'] = Types::INTEGER;
        
        $result = $conn->executeQuery($sql, $params, $types);
        
        $labels = [];
        $ttcValues = [];
        
        while ($row = $result->fetchAssociative()) {
            $labels[] = $row['organizer_name'];
            $ttcValues[] = (float) $row['ttc_total'];
        }
        
        return [
            'labels' => $labels,
            'ttc_values' => $ttcValues,
        ];
    }
}

