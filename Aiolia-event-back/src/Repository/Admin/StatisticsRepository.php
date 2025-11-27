<?php

namespace App\Repository\Admin;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;

class StatisticsRepository
{
    private Connection $connection;

    private const DATE_FORMAT = '%04d-%02d-01';
    private const YEAR_START_FORMAT = '%04d-01-01';
    private const YEAR_END_FORMAT = '%04d-12-31';
    private const ORGANIZER_PERIOD_FIELD = "COALESCE(os.debut_periode_courante, os.commence_le)";

    public function __construct(ManagerRegistry $registry)
    {
        $this->connection = $registry->getConnection();
    }

    /**
     * Arrondit les bornes temporelles à un mois complet.
     *
     * @return array{start: string|null, end: string|null}
     */
    private function resolvePeriod(int $mois, int $annee): array
    {
        if ($mois === 0 && $annee === 0) {
            return ['start' => null, 'end' => null];
        }

        $annee = $annee > 0 ? $annee : (int) date('Y');

        if ($mois > 0) {
            $start = sprintf(self::DATE_FORMAT, $annee, $mois);
            $end   = date('Y-m-t', strtotime($start));
        } else {
            $start = sprintf(self::YEAR_START_FORMAT, $annee);
            $end   = sprintf(self::YEAR_END_FORMAT, $annee);
        }

        return ['start' => $start, 'end' => $end];
    }

    private function buildDateCondition(?string $start, ?string $end, string $field): string
    {
        if (!$start || !$end) {
            return '';
        }

        return " AND ($field BETWEEN :date_start AND :date_end)";
    }

    private function countOrganisateurs(int $mois, int $annee, string $type): int
    {
        $period = $this->resolvePeriod($mois, $annee);
        $start = $period['start'];
        $end   = $period['end'];

        if ($type === 'active') {
            $sql = "
                SELECT COUNT(DISTINCT os.id_profil_organisateur)
                FROM aiolia.abonnements_organisateurs os
                WHERE os.statut = 'active'
            ";
            $sql .= $this->buildDateCondition($start, $end, self::ORGANIZER_PERIOD_FIELD);
        } else {
            $sql = "
                SELECT COUNT(*)
                FROM aiolia.profils_organisateurs po
                INNER JOIN aiolia.utilisateurs u ON u.id = po.id_utilisateur
                WHERE po.statut_verification = 'verified'
                  AND u.role = 'organizer'
                  AND u.statut = 1
            ";
            $sql .= $this->buildDateCondition($start, $end, "po.cree_le");
        }

        $params = [];
        if ($start && $end) {
            $params['date_start'] = $start;
            $params['date_end']   = $end;
        }

        return (int) $this->connection->fetchOne($sql, $params);
    }

    public function organisateurActifs(int $mois, int $annee): int
    {
        return $this->countOrganisateurs($mois, $annee, 'active');
    }

    public function newsOrganisateur(int $mois, int $annee): int
    {
        return $this->countOrganisateurs($mois, $annee, 'new');
    }

    /* ========================================================================== */
    /*     ABONNEMENT LE PLUS UTILISÉ                                             */
    /* ========================================================================== */
    public function abonnemnentPLusActifs(int $mois, int $annee): ?array
    {
        $period = $this->resolvePeriod($mois, $annee);
        $start = $period['start'];
        $end   = $period['end'];

        $sql = "
            SELECT sp.nom, COUNT(DISTINCT os.id_profil_organisateur) AS count
            FROM aiolia.abonnements_organisateurs os
            INNER JOIN aiolia.plans_abonnements sp ON sp.id = os.id_plan
            WHERE os.statut = 'active'
        ";

        $sql .= $this->buildDateCondition($start, $end, self::ORGANIZER_PERIOD_FIELD);

        $sql .= " GROUP BY sp.nom ORDER BY count DESC LIMIT 1";

        $params = [];
        if ($start && $end) {
            $params['date_start'] = $start;
            $params['date_end']   = $end;
        }

        $result = $this->connection->fetchAssociative($sql, $params);

        return $result ? [
            'nom'   => $result['nom'],
            'count' => (int)$result['count'],
        ] : null;
    }

    /* ========================================================================== */
    /*     CHIFFRE D'AFFAIRE                                                      */
    /* ========================================================================== */
    public function chiffreAffaireCA(int $mois, int $annee): float
    {
        $period = $this->resolvePeriod($mois, $annee);
        $start = $period['start'];
        $end   = $period['end'];

        $sql = "
            SELECT COALESCE(SUM(montant_total), 0)
            FROM aiolia.factures_abonnements
            WHERE statut IN ('paid', 'partially_paid')
        ";

        $sql .= $this->buildDateCondition($start, $end, "mois_facturation");

        $params = [];
        if ($start && $end) {
            $params['date_start'] = $start;
            $params['date_end']   = $end;
        }

        return (float) $this->connection->fetchOne($sql, $params);
    }

    /* ========================================================================== */
    /*     TENDANCE ORGANISATEURS ACTIFS                                          */
    /* ========================================================================== */
    public function getActiveOrganizersTrend(int $mois, int $annee): array
    {
        $period = $this->resolvePeriod($mois, $annee);
        $start = $period['start'] ?? date('Y-01-01');
        $end   = $period['end'] ?? date('Y-12-31');

        $sql = "
            WITH periods AS (
                SELECT date_trunc('month', dd)::date AS period_start
                FROM generate_series(:start::date, :end::date, '1 month') dd
            )
            SELECT
                TO_CHAR(periods.period_start, 'Mon YYYY') AS month_label,
                COUNT(DISTINCT os.id_profil_organisateur) AS count
            FROM periods
            LEFT JOIN aiolia.abonnements_organisateurs os
                ON os.statut = 'active'
               AND COALESCE(os.debut_periode_courante, os.commence_le)
                   BETWEEN periods.period_start AND periods.period_start + INTERVAL '1 month - 1 day'
            GROUP BY periods.period_start
            ORDER BY periods.period_start
        ";

        return $this->connection->fetchAllAssociative($sql, [
            'start' => $start,
            'end'   => $end,
        ]);
    }

    /* ========================================================================== */
    /*     DISTRIBUTION DES NIVEAUX                                               */
    /* ========================================================================== */
    public function getSubscriptionUsageByLevel(int $mois, int $annee): array
    {
        $period = $this->resolvePeriod($mois, $annee);
        $start = $period['start'];
        $end   = $period['end'];

        $sql = "
            SELECT sp.niveau, COUNT(DISTINCT os.id_profil_organisateur) AS count
            FROM aiolia.abonnements_organisateurs os
            INNER JOIN aiolia.plans_abonnements sp ON sp.id = os.id_plan
            WHERE os.statut = 'active'
        ";

        $sql .= $this->buildDateCondition($start, $end, self::ORGANIZER_PERIOD_FIELD);

        $sql .= " GROUP BY sp.niveau";

        $params = [];
        if ($start && $end) {
            $params['date_start'] = $start;
            $params['date_end']   = $end;
        }

        $rows = $this->connection->fetchAllAssociative($sql, $params);

        $distribution = [
            'basic' => 0,
            'pro' => 0,
            'enterprise' => 0,
        ];

        foreach ($rows as $row) {
            $niveau = $row['niveau'] ?? null;
            if ($niveau !== null && array_key_exists($niveau, $distribution)) {
                $distribution[$niveau] = (int) $row['count'];
            }
        }

        return $distribution;
    }

    /* ========================================================================== */
    /*     DÉTAIL CA                                                               */
    /* ========================================================================== */
    public function getRevenueBreakdownByPeriod(int $mois, int $annee): array
    {
        $period = $this->resolvePeriod($mois, $annee);
        $start = $period['start'];
        $end   = $period['end'];

        $sql = "
            SELECT
                DATE_TRUNC('month', mois_facturation) AS period_start,
                TO_CHAR(DATE_TRUNC('month', mois_facturation), 'Mon YYYY') AS month_label,
                SUM(montant_ht)  AS revenue_ht,
                SUM(montant_tva) AS revenue_tva,
                SUM(montant_ttc) AS revenue_ttc
            FROM aiolia.factures_abonnements
            WHERE statut IN ('paid', 'partially_paid')
        ";

        $sql .= $this->buildDateCondition($start, $end, "mois_facturation");

        $sql .= " GROUP BY period_start ORDER BY period_start";

        $params = [];
        if ($start && $end) {
            $params['date_start'] = $start;
            $params['date_end']   = $end;
        }

        return $this->connection->fetchAllAssociative($sql, $params);
    }
}
