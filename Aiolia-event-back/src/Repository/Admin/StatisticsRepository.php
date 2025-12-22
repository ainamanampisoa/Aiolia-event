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
     * Résout les bornes temporelles pour une période (mois/année)
     */
    private function resolvePeriod(int $month, int $year): array
    {
        if ($month === 0 && $year === 0) {
            return ['start' => null, 'end' => null];
        }

        $year = $year > 0 ? $year : (int) date('Y');

        if ($month > 0) {
            $start = sprintf('%04d-%02d-01', $year, $month);
            $end = date('Y-m-t', strtotime($start));
        } else {
            $start = sprintf('%04d-01-01', $year);
            $end = sprintf('%04d-12-31', $year);
        }

        return ['start' => $start, 'end' => $end];
    }

    /**
     * Compte les organisateurs selon le type (actifs/nouveaux)
     */
    public function countOrganizers(int $month, int $year, string $type): int
    {
        $period = $this->resolvePeriod($month, $year);
        $start = $period['start'];
        $end = $period['end'];

        if ($type === 'active') {
            return $this->countActiveOrganizers($start, $end);
        }

        return $this->countNewOrganizers($start, $end);
    }

    private function countActiveOrganizers(?string $start, ?string $end): int
    {
        $qb = $this->createQueryBuilder('os')
            // On compte les organisateurs uniques, pas le nombre de lignes d'abonnements
            ->select('COUNT(DISTINCT os.organizerProfile)')
            ->join('os.organizerProfile', 'op')
            ->where('os.statut = :active')
            ->andWhere('op.statutVerification = :verified')
            ->setParameter('active', SubscriptionStatus::ACTIVE)
            ->setParameter('verified', OrganizerProfile::STATUS_VERIFIED);

        // On filtre par période en utilisant les champs de dates réels de l'entité OrganizerSubscription
        $this->applyPeriodFilter($qb, $start, $end, 'COALESCE(os.debutPeriodeCourante, os.commenceLe)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function countNewOrganizers(?string $start, ?string $end): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(po.id)')
            ->from(OrganizerProfile::class, 'po')
            ->join('po.utilisateur', 'u')
            ->where('po.statutVerification = :verified')
            ->andWhere('u.role = :organizer')
            ->andWhere('u.statut = :active')
            ->setParameter('verified', 'verified')
            ->setParameter('organizer', 'organizer')
            ->setParameter('active', 1);

        // Filtre sur la date de création réelle de l'organisateur
        $this->applyPeriodFilter($qb, $start, $end, 'po.creeLe');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Abonnement le plus utilisé
     */
    public function getMostUsedSubscription(int $month, int $year): ?array
    {
        $period = $this->resolvePeriod($month, $year);
        $start = $period['start'];
        $end = $period['end'];

        $qb = $this->createQueryBuilder('os')
            ->select('p.nom, COUNT(DISTINCT os.organizerProfile) as count')
            ->join('os.plan', 'p')
            ->join('os.organizerProfile', 'op')
            ->where('os.statut = :active')
            ->andWhere('op.statutVerification = :verified')
            ->groupBy('p.nom')
            ->orderBy('count', 'DESC')
            ->setMaxResults(1)
            ->setParameter('active', SubscriptionStatus::ACTIVE)
            ->setParameter('verified', OrganizerProfile::STATUS_VERIFIED);

        $this->applyPeriodFilter($qb, $start, $end, 'COALESCE(os.debutPeriodeCourante, os.commenceLe)');

        $result = $qb->getQuery()->getOneOrNullResult();

        return $result ? [
            'nom' => $result['nom'],
            'count' => (int) $result['count']
        ] : null;
    }

    /**
     * Chiffre d'affaires
     */
    public function getRevenue(int $month, int $year): float
    {
        $period = $this->resolvePeriod($month, $year);
        $start = $period['start'];
        $end = $period['end'];

        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('COALESCE(SUM(i.totalAmount), 0)')
            ->from(SubscriptionInvoice::class, 'i')
            ->where('i.status IN (:paidStatuses)')
            ->setParameter('paidStatuses', ['paid', 'partially_paid']);

        $this->applyPeriodFilter($qb, $start, $end, 'i.billingMonth');

        return (float) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Tendance organisateurs actifs
     */
    public function getActiveOrganizersTrend(int $month, int $year): array
    {
        $period = $this->resolvePeriod($month, $year);
        $start = $period['start'] ?? date('Y-01-01');
        $end = $period['end'] ?? date('Y-12-31');

        // Simulation des mois avec Doctrine (sans generate_series natif)
        $months = $this->generateMonthlyPeriods($start, $end);
        $trends = [];

        foreach ($months as $monthStart) {
            $monthEnd = (clone $monthStart)->modify('last day of this month');
            
            $count = $this->countActiveOrganizersInPeriod($monthStart, $monthEnd);
            
            $trends[] = [
                'month_label' => $monthStart->format('M Y'),
                'count' => $count
            ];
        }

        return $trends;
    }

    /**
     * Distribution par niveau d'abonnement
     */
    public function getSubscriptionUsageByLevel(int $month, int $year): array
    {
        $period = $this->resolvePeriod($month, $year);
        $start = $period['start'];
        $end = $period['end'];

        $qb = $this->createQueryBuilder('os')
            ->select('p.niveau AS level, COUNT(DISTINCT os.organizerProfile) as count')
            ->join('os.plan', 'p')
            ->join('os.organizerProfile', 'op')
            ->where('os.statut = :active')
            ->andWhere('op.statutVerification = :verified')
            ->groupBy('p.niveau')
            ->setParameter('active', OrganizerSubscription::STATUS_ACTIVE)
            ->setParameter('verified', OrganizerProfile::STATUS_VERIFIED);

        $this->applyPeriodFilter($qb, $start, $end, 'COALESCE(os.debutPeriodeCourante, os.commenceLe)');

        $rows = $qb->getQuery()->getResult();

        $distribution = [
            'basic' => 0,
            'pro' => 0,
            'enterprise' => 0,
        ];

        foreach ($rows as $row) {
            if (isset($distribution[$row['level']])) {
                $distribution[$row['level']] = (int) $row['count'];
            }
        }

        return $distribution;
    }

    /**
     * Détail CA par période
     */
    public function getRevenueBreakdownByPeriod(int $month, int $year): array
    {
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

        $this->applyPeriodFilter($qb, $start, $end, 'i.billingMonth');

        $rows = $qb->getQuery()->getResult();

        // On reconstruit period_start et month_label en PHP pour éviter d'utiliser des fonctions de date en DQL
        $results = [];
        foreach ($rows as $row) {
            /** @var \DateTimeInterface $billingMonth */
            $billingMonth = $row['billingMonth'];

            // Normalise au premier jour du mois
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

    private function applyPeriodFilter($qb, ?string $start, ?string $end, string $field): void
    {
        if ($start && $end) {
            $qb->andWhere("$field BETWEEN :date_start AND :date_end")
            ->setParameter('date_start', $start)
            ->setParameter('date_end', $end);
        }
    }

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

    private function countActiveOrganizersInPeriod(\DateTimeInterface $start, \DateTimeInterface $end): int
    {
        $qb = $this->createQueryBuilder('os')
            ->select('COUNT(DISTINCT os.organizerProfile)')
            ->join('os.organizerProfile', 'op')
            ->where('os.statut = :active')
            ->andWhere('op.statutVerification = :verified')
            ->andWhere('COALESCE(os.debutPeriodeCourante, os.commenceLe) BETWEEN :start AND :end')
            ->setParameter('active', SubscriptionStatus::ACTIVE)
            ->setParameter('verified', OrganizerProfile::STATUS_VERIFIED)
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
