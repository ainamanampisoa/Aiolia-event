<?php

namespace App\Repository\Admin;

use App\Entity\OrganizerSubscription;
use App\Entity\OrganizerProfile;
use App\Entity\User;
use App\Entity\SubscriptionInvoice;
use App\Enum\SubscriptionStatus;
use App\Enum\Role;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrganizerSubscription>
 */
class StatisticsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrganizerSubscription::class);
    }


    /**
     * Compte les organisateurs selon le type (actifs/nouveaux)
     */
    public function countOrganizers(int $month, int $year, string $type): int
    {
        $period = $this->resolvePeriod($month, $year);
        
        if ($type === 'active') {
            return $this->countActiveOrganizers($period['end']);
        }

        return $this->countNewOrganizers($period['start'], $period['end']);
    }

    /**
     * Compte les organisateurs actifs à une date donnée
     * Un organisateur est actif s'il a un profil vérifié et un abonnement actif valide à cette date
     */
    private function countActiveOrganizers(?string $endDate): int
    {
        $connection = $this->getEntityManager()->getConnection();
        $referenceDate = $endDate ? new \DateTime($endDate) : new \DateTime();
        $referenceDate->setTime(23, 59, 59);
        $dateStr = $referenceDate->format('Y-m-d H:i:s');
        
        // Un organisateur est actif si :
        // 1. Son profil est vérifié
        // 2. Il a au moins un abonnement avec statut = 'active'
        // 3. L'abonnement a commencé avant ou à la date de référence
        // 4. L'abonnement n'a pas été annulé avant ou à la date de référence
        // On prend le dernier abonnement créé (le plus récent)
        $sql = "
            WITH latest_subscriptions AS (
                SELECT 
                    os.id,
                    os.id_profil_organisateur,
                    os.statut,
                    os.debut_periode_courante,
                    os.commence_le,
                    os.annule_le,
                    ROW_NUMBER() OVER (
                        PARTITION BY os.id_profil_organisateur 
                        ORDER BY os.cree_le DESC, os.id DESC
                    ) as rn
                FROM aiolia.abonnements_organisateurs os
            )
            SELECT COUNT(DISTINCT op.id)
            FROM aiolia.profils_organisateurs op
            INNER JOIN latest_subscriptions os ON os.id_profil_organisateur = op.id AND os.rn = 1
            WHERE op.statut_verification = :verified
                AND os.statut = :active
                AND COALESCE(os.debut_periode_courante, os.commence_le) <= :referenceDate
                AND (os.annule_le IS NULL OR os.annule_le > :referenceDate)
        ";
        
        $result = $connection->fetchOne($sql, [
            'active' => SubscriptionStatus::ACTIVE,
            'verified' => OrganizerProfile::STATUS_VERIFIED,
            'referenceDate' => $dateStr
        ]);
        
        return (int) ($result ?? 0);
    }

    /**
     * Compte les nouveaux organisateurs
     * Calcule la différence entre le nombre d'organisateurs actifs du mois actuel et du mois précédent
     * Pour "Tous les mois", retourne 0 car on ne peut pas calculer de différence
     */
    private function countNewOrganizers(?string $start, ?string $end): int
    {
        if (!$start || !$end) {
            return 0;
        }

        $currentMonthStart = new \DateTime($start);
        $currentMonthEnd = new \DateTime($end);
        
        // Si la période couvre plus d'un mois (année complète), retourner 0
        // car on ne peut pas calculer de différence significative
        $startMonth = (int) $currentMonthStart->format('n');
        $endMonth = (int) $currentMonthEnd->format('n');
        $startYear = (int) $currentMonthStart->format('Y');
        $endYear = (int) $currentMonthEnd->format('Y');
        
        if ($startMonth !== $endMonth || $startYear !== $endYear) {
            // Période de plusieurs mois : retourner 0
            return 0;
        }

        // Compter les organisateurs actifs à la fin du mois actuel
        $currentMonthEnd->setTime(23, 59, 59);
        $currentCount = $this->countActiveOrganizers($currentMonthEnd->format('Y-m-d H:i:s'));

        // Calculer le mois précédent : dernier jour du mois précédent
        $previousMonthEnd = (clone $currentMonthStart)->modify('first day of this month')->modify('-1 day')->setTime(23, 59, 59);
        
        // Compter les organisateurs actifs à la fin du mois précédent
        $previousCount = $this->countActiveOrganizers($previousMonthEnd->format('Y-m-d H:i:s'));

        // La différence est le nombre de nouveaux organisateurs
        return max(0, $currentCount - $previousCount);
    }

    /**
     * Abonnement le plus utilisé dans une période
     * Utilise exactement la même logique que getSubscriptionUsageByLevel pour trouver le niveau le plus utilisé
     * et retourne le nombre d'organisateurs actifs de ce niveau (pour correspondre au graphique de répartition)
     */
    public function getMostUsedSubscription(int $month, int $year): ?array
    {
        $period = $this->resolvePeriod($month, $year);
        $referenceDate = $period['end'] ? new \DateTime($period['end']) : new \DateTime();
        $referenceDate->setTime(23, 59, 59);
        $dateStr = $referenceDate->format('Y-m-d H:i:s');

        $connection = $this->getEntityManager()->getConnection();
        
        // Utiliser exactement la même requête que getSubscriptionUsageByLevel
        $sql = "
            WITH latest_subscriptions AS (
                SELECT 
                    os.id,
                    os.id_profil_organisateur,
                    os.id_plan,
                    os.statut,
                    os.debut_periode_courante,
                    os.commence_le,
                    os.annule_le,
                    ROW_NUMBER() OVER (
                        PARTITION BY os.id_profil_organisateur 
                        ORDER BY os.cree_le DESC, os.id DESC
                    ) as rn
                FROM aiolia.abonnements_organisateurs os
            )
            SELECT p.niveau AS level, COUNT(DISTINCT op.id) as organizer_count
            FROM aiolia.profils_organisateurs op
            INNER JOIN latest_subscriptions os ON os.id_profil_organisateur = op.id AND os.rn = 1
            INNER JOIN aiolia.plans_abonnements p ON p.id = os.id_plan
            WHERE op.statut_verification = :verified
                AND os.statut = :active
                AND COALESCE(os.debut_periode_courante, os.commence_le) <= :referenceDate
                AND (os.annule_le IS NULL OR os.annule_le > :referenceDate)
            GROUP BY p.niveau
            ORDER BY organizer_count DESC
            LIMIT 1
        ";
        
        $result = $connection->fetchAssociative($sql, [
            'active' => SubscriptionStatus::ACTIVE,
            'verified' => OrganizerProfile::STATUS_VERIFIED,
            'referenceDate' => $dateStr
        ]);

        if (!$result) {
            return null;
        }

        $level = $result['level'];
        $organizerCount = (int) $result['organizer_count'];

        // Convertir le niveau en nom d'affichage (première lettre en majuscule)
        $levelName = ucfirst($level);

        // Retourner le nombre d'organisateurs actifs du niveau le plus utilisé
        // pour correspondre exactement au graphique de répartition
        return [
            'nom' => $levelName,
            'count' => $organizerCount
        ];
    }

    /**
     * Chiffre d'affaires pour une période
     */
    public function getRevenue(int $month, int $year): float
    {
        if ($month > 0 && $year === 0) {
            $connection = $this->getEntityManager()->getConnection();
            $sql = "
                SELECT COALESCE(SUM(montant_total), 0)
                FROM aiolia.factures_abonnements
                WHERE statut IN ('paid', 'partially_paid')
                    AND EXTRACT(MONTH FROM mois_facturation) = :month
            ";
            return (float) $connection->fetchOne($sql, ['month' => $month]);
        }

        $period = $this->resolvePeriod($month, $year);
        $start = $period['start'];
        $end = $period['end'];

        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('COALESCE(SUM(i.totalAmount), 0)')
            ->from(SubscriptionInvoice::class, 'i')
            ->where('i.status IN (:paidStatuses)')
            ->setParameter('paidStatuses', ['paid', 'partially_paid']);

        if ($start !== null && $end !== null) {
            $qb->andWhere('i.billingMonth BETWEEN :date_start AND :date_end')
            ->setParameter('date_start', new \DateTime($start))
            ->setParameter('date_end', new \DateTime($end));
        }

        return (float) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Récupère le CA total (toutes périodes) - À supprimer car intégré dans getRevenue()
     */
    private function getTotalRevenue(): float
    {
        // Cette méthode n'est plus nécessaire
        return $this->getRevenue(0, 0);
    }

    /**
     * Résout la période en dates de début/fin
     */
    private function resolvePeriod(int $month, int $year): array
    {
        // Cas 1: Toutes les années et tous les mois
        if ($year === 0 && $month === 0) {
            return [
                'start' => null, // Pas de filtre de date
                'end' => null
            ];
        }
        
        // Cas 2: Année spécifique, tous les mois
        if ($year > 0 && $month === 0) {
            return [
                'start' => $year . '-01-01',
                'end' => $year . '-12-31'
            ];
        }
        
        // Cas 3: Mois et année spécifiques
        if ($year > 0 && $month > 0) {
            $start = new \DateTime($year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01');
            $end = clone $start;
            $end->modify('last day of this month');
            
            return [
                'start' => $start->format('Y-m-d'),
                'end' => $end->format('Y-m-d')
            ];
        }
        
        // Cas 4: Mois spécifique, année actuelle (si année = 0)
        if ($month > 0 && $year === 0) {
            $now = new \DateTime();
            $year = (int) $now->format('Y');
            $start = new \DateTime($year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01');
            $end = clone $start;
            $end->modify('last day of this month');
            
            return [
                'start' => $start->format('Y-m-d'),
                'end' => $end->format('Y-m-d')
            ];
        }
        
        // Cas par défaut: année spécifique sans mois (si année > 0 mais mois = 0 déjà traité)
        if ($year > 0) {
            return [
                'start' => $year . '-01-01',
                'end' => $year . '-12-31'
            ];
        }
        
        // Cas par défaut: date actuelle
        $now = new \DateTime();
        return [
            'start' => $now->format('Y-m-01'),
            'end' => $now->format('Y-m-t')
        ];
    }

    /**
     * Tendance organisateurs actifs par mois
     */
    public function getActiveOrganizersTrend(int $month, int $year): array
    {
        $period = $this->resolvePeriod($month, $year);
        
        // Si toutes les années sont sélectionnées, utiliser une plage large pour couvrir toutes les données
        if ($year === 0) {
            // Récupérer la première et dernière date de facturation disponible
            $connection = $this->getEntityManager()->getConnection();
            $firstDate = $connection->fetchOne(
                "SELECT MIN(mois_facturation) FROM aiolia.factures_abonnements WHERE mois_facturation IS NOT NULL"
            );
            $lastDate = $connection->fetchOne(
                "SELECT MAX(mois_facturation) FROM aiolia.factures_abonnements WHERE mois_facturation IS NOT NULL"
            );
            
            if ($firstDate && $lastDate) {
                $start = (new \DateTime($firstDate))->modify('first day of this month')->format('Y-m-01');
                $end = (new \DateTime($lastDate))->modify('last day of this month')->format('Y-m-t');
            } else {
                // Fallback : utiliser une plage large
                $start = '2020-01-01';
                $end = date('Y-12-31');
            }
        } else {
            $start = $period['start'] ?? date('Y-01-01');
            $end = $period['end'] ?? date('Y-12-31');
        }

        $months = $this->generateMonthlyPeriods($start, $end);
        $trends = [];

        foreach ($months as $monthStart) {
            $monthEnd = (clone $monthStart)->modify('last day of this month')->setTime(23, 59, 59);
            // Utiliser exactement la même méthode que countActiveOrganizers
            $count = $this->countActiveOrganizers($monthEnd->format('Y-m-d H:i:s'));
            
            $trends[] = [
                'month_label' => $monthStart->format('M Y'),
                'count' => $count
            ];
        }

        return $trends;
    }

    /**
     * Distribution par niveau d'abonnement à une date donnée
     */
    public function getSubscriptionUsageByLevel(int $month, int $year): array
    {
        $period = $this->resolvePeriod($month, $year);
        $referenceDate = $period['end'] ? new \DateTime($period['end']) : new \DateTime();
        $referenceDate->setTime(23, 59, 59);
        $dateStr = $referenceDate->format('Y-m-d H:i:s');

        $connection = $this->getEntityManager()->getConnection();
        
        $sql = "
            WITH latest_subscriptions AS (
                SELECT 
                    os.id,
                    os.id_profil_organisateur,
                    os.id_plan,
                    os.statut,
                    os.debut_periode_courante,
                    os.commence_le,
                    os.annule_le,
                    ROW_NUMBER() OVER (
                        PARTITION BY os.id_profil_organisateur 
                        ORDER BY os.cree_le DESC, os.id DESC
                    ) as rn
                FROM aiolia.abonnements_organisateurs os
            )
            SELECT p.niveau AS level, COUNT(DISTINCT op.id) as count
            FROM aiolia.profils_organisateurs op
            INNER JOIN latest_subscriptions os ON os.id_profil_organisateur = op.id AND os.rn = 1
            INNER JOIN aiolia.plans_abonnements p ON p.id = os.id_plan
            WHERE op.statut_verification = :verified
                AND os.statut = :active
                AND COALESCE(os.debut_periode_courante, os.commence_le) <= :referenceDate
                AND (os.annule_le IS NULL OR os.annule_le > :referenceDate)
            GROUP BY p.niveau
        ";
        
        $rows = $connection->fetchAllAssociative($sql, [
            'active' => SubscriptionStatus::ACTIVE,
            'verified' => OrganizerProfile::STATUS_VERIFIED,
            'referenceDate' => $dateStr
        ]);

        $distribution = [
            'basic' => 0,
            'pro' => 0,
            'enterprise' => 0,
        ];

        foreach ($rows as $row) {
            $level = $row['level'] ?? null;
            if ($level && isset($distribution[$level])) {
                $distribution[$level] = (int) $row['count'];
            }
        }

        return $distribution;
    }

    /**
     * Détail CA par période
     */
    public function getRevenueBreakdownByPeriod(int $month, int $year): array
    {
        if ($month > 0 && $year === 0) {
            return $this->getRevenueBreakdownForMonthAllYears($month);
        }

        $period = $this->resolvePeriod($month, $year);
        $start = $period['start'];
        $end = $period['end'];

        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select(
                'i.billingMonth AS billingMonth',
                'SUM(i.amountHt) as revenue_ht',
                'SUM(i.amountTva) as revenue_tva',
                'SUM(i.amountTtc) as revenue_ttc'
            )
            ->from(SubscriptionInvoice::class, 'i')
            ->where('i.status IN (:paidStatuses)')
            ->groupBy('i.billingMonth')
            ->orderBy('i.billingMonth', 'ASC')
            ->setParameter('paidStatuses', ['paid', 'partially_paid']);

        if ($start && $end) {
            $qb->andWhere('i.billingMonth BETWEEN :date_start AND :date_end')
                ->setParameter('date_start', new \DateTime($start))
                ->setParameter('date_end', new \DateTime($end));
        }

        $rows = $qb->getQuery()->getResult();

        $results = [];
        foreach ($rows as $row) {
            /** @var \DateTimeInterface $billingMonth */
            $billingMonth = $row['billingMonth'];

            $periodStart = $billingMonth->format('Y-m-01');
            $date = \DateTimeImmutable::createFromFormat('Y-m-d', $periodStart);

            $results[] = [
                'period_start' => $periodStart,
                'month_label' => $date ? $date->format('M Y') : $billingMonth->format('m/Y'),
                'revenue_ht' => $row['revenue_ht'],
                'revenue_tva' => $row['revenue_tva'],
                'revenue_ttc' => $row['revenue_ttc'],
            ];
        }

        return $results;
    }

    /**
     * Agrège le CA d'un mois spécifique sur toutes les années
     */
    private function getRevenueBreakdownForMonthAllYears(int $month): array
    {
        $connection = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT
                SUM(montant_ht) as revenue_ht,
                SUM(montant_tva_detail) as revenue_tva,
                SUM(montant_ttc) as revenue_ttc
            FROM aiolia.factures_abonnements
            WHERE statut IN ('paid', 'partially_paid')
                AND EXTRACT(MONTH FROM mois_facturation) = :month
        ";

        $row = $connection->fetchAssociative($sql, ['month' => $month]);

        if (!$row || ((float) ($row['revenue_ht'] ?? 0) === 0.0
            && (float) ($row['revenue_tva'] ?? 0) === 0.0
            && (float) ($row['revenue_ttc'] ?? 0) === 0.0)) {
            return [];
        }

        $monthNames = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
        ];

        return [[
            'period_start' => date('Y') . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01',
            'month_label' => $monthNames[$month] ?? 'Unknown',
            'revenue_ht' => $row['revenue_ht'] ?? 0,
            'revenue_tva' => $row['revenue_tva'] ?? 0,
            'revenue_ttc' => $row['revenue_ttc'] ?? 0,
        ]];
    }

    /**
     * Génère une liste de périodes mensuelles entre deux dates
     */
    private function generateMonthlyPeriods(string $start, string $end): array
    {
        $periods = [];
        $current = new \DateTime($start);
        $endDate = new \DateTime($end);

        while ($current <= $endDate) {
            $periods[] = clone $current;
            $current->modify('first day of next month');
        }

        return $periods;
    }
}
