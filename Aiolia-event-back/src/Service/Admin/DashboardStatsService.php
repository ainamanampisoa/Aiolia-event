<?php

namespace App\Service\Admin;

use App\Entity\User;
use App\Enum\Role as UserRoleEnum;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;

class DashboardStatsService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function getDashboardData(int $month, int $year): array
    {
        $periodStart = $this->createPeriodStart($month, $year);
        $periodEnd = $periodStart->modify('first day of next month');

        $summary = [
            'active_organizers' => $this->countActiveOrganizers(),
            'new_organizers' => $this->countNewOrganizers($periodStart, $periodEnd),
            'top_subscription' => $this->getMostPopularSubscription($periodStart, $periodEnd),
            'global_activity_rate' => $this->formatPercentage($this->computeActivityRate()),
        ];

        return [
            'summary' => $summary,
            'charts' => [
                'new_organizers' => $this->buildNewOrganizersSeries($periodStart, $periodEnd),
                'subscriptions' => $this->buildSubscriptionsHistogram($periodStart, $periodEnd),
                'activity_rate' => $this->buildActivityRateTrend($periodStart, $periodEnd),
            ],
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


