<?php

namespace App\Repository\Admin;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class StatisticsRepository
{
    private Connection $connection;

    public function __construct(ManagerRegistry $registry)
    {
        $this->connection = $registry->getConnection();
    }

    /**
     * Compte les organisateurs actifs pour un mois donné
     */
    public function countActiveOrganizersForMonth(\DateTimeInterface $date): int
    {
        $dateObj = $date instanceof \DateTime ? clone $date : new \DateTime($date->format('Y-m-d H:i:s'));
        $monthStart = (clone $dateObj)->modify('first day of this month')->setTime(0, 0, 0);
        $monthEnd = (clone $dateObj)->modify('last day of this month')->setTime(23, 59, 59);

        $sql = "
            SELECT COUNT(DISTINCT po.id) as count
            FROM aiolia.profils_organisateurs po
            INNER JOIN aiolia.utilisateurs u ON u.id = po.id_utilisateur
            INNER JOIN aiolia.abonnements_organisateurs ao ON ao.id_profil_organisateur = po.id
            WHERE u.statut = 1
                AND u.role = 'organizer'
                AND ao.commence_le <= :monthEnd
                AND (ao.annule_le IS NULL OR ao.annule_le >= :monthStart)
                AND (
                    -- Pas en pause pendant ce mois
                    (ao.mis_en_pause_le IS NULL OR ao.mis_en_pause_le > :monthEnd)
                    OR
                    -- En pause mais repris avant ou pendant le mois
                    (ao.mis_en_pause_le IS NOT NULL AND ao.repris_le IS NOT NULL AND ao.repris_le <= :monthEnd)
                )
        ";

        $result = $this->connection->executeQuery($sql, [
            'monthStart' => $monthStart->format('Y-m-d H:i:s'),
            'monthEnd' => $monthEnd->format('Y-m-d H:i:s'),
        ]);

        $count = $result->fetchOne();
        return $count !== false && $count !== null ? (int) $count : 0;
    }

    /**
     * Compte les nouveaux organisateurs pour un mois donné
     */
    public function countNewOrganizersForMonth(\DateTimeInterface $date): int
    {
        $dateObj = $date instanceof \DateTime ? clone $date : new \DateTime($date->format('Y-m-d H:i:s'));
        $monthStart = (clone $dateObj)->modify('first day of this month')->setTime(0, 0, 0);
        $monthEnd = (clone $dateObj)->modify('last day of this month')->setTime(23, 59, 59);

        $sql = "
            SELECT COUNT(DISTINCT po.id) as count
            FROM aiolia.profils_organisateurs po
            INNER JOIN aiolia.utilisateurs u ON u.id = po.id_utilisateur
            WHERE u.role = 'organizer'
                AND u.statut = 1
                AND po.cree_le >= :monthStart
                AND po.cree_le <= :monthEnd
        ";

        $result = $this->connection->executeQuery($sql, [
            'monthStart' => $monthStart->format('Y-m-d H:i:s'),
            'monthEnd' => $monthEnd->format('Y-m-d H:i:s'),
        ]);

        $count = $result->fetchOne();
        return $count !== false && $count !== null ? (int) $count : 0;
    }

    /**
     * Trouve l'abonnement le plus utilisé
     * @param int $month 0 = tous les mois, 1-12 = mois spécifique
     * @param int $year Année
     */
    public function findMostUsedSubscription(int $month = 0, int $year = 2025): ?array
    {
        // Pour l'abonnement le plus utilisé, on cherche les abonnements actifs pendant la période
        if ($month > 0 && $month <= 12) {
            $periodStart = sprintf('%d-%02d-01', $year, $month);
            $periodEnd = (new \DateTime($periodStart))->modify('last day of this month')->format('Y-m-d');
        } else {
            $periodStart = sprintf('%d-01-01', $year);
            $periodEnd = sprintf('%d-12-31', $year);
        }

        $sql = "
            SELECT 
                sp.niveau as plan_level,
                sp.periode_facturation as billing_period,
                COUNT(DISTINCT ao.id) as count,
                sp.nom as plan_name
            FROM aiolia.abonnements_organisateurs ao
            INNER JOIN aiolia.plans_abonnements sp ON sp.id = ao.id_plan
            INNER JOIN aiolia.profils_organisateurs po ON po.id = ao.id_profil_organisateur
            INNER JOIN aiolia.utilisateurs u ON u.id = po.id_utilisateur
            WHERE u.statut = 1
                AND ao.commence_le <= :periodEnd
                AND (ao.annule_le IS NULL OR ao.annule_le >= :periodStart)
                AND (
                    -- Pas en pause pendant la période
                    (ao.mis_en_pause_le IS NULL OR ao.mis_en_pause_le > :periodEnd)
                    OR
                    -- En pause mais repris avant ou pendant la période
                    (ao.mis_en_pause_le IS NOT NULL AND ao.repris_le IS NOT NULL AND ao.repris_le <= :periodEnd)
                )
        ";

        $params = [
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
        ];

        $sql .= "
            GROUP BY sp.niveau, sp.periode_facturation, sp.nom
            ORDER BY count DESC
            LIMIT 1
        ";

        $result = $this->connection->executeQuery($sql, $params);
        $row = $result->fetchAssociative();

        return $row ?: null;
    }

    /**
     * Calcule la prévision du chiffre d'affaires pour les 6 prochains mois avec détail HT/TTC/TVA
     */
    public function calculateRevenueForecast(\DateTimeInterface $startDate, int $months = 6): array
    {
        $forecast = [];
        $currentDate = $startDate instanceof \DateTime ? clone $startDate : new \DateTime($startDate->format('Y-m-d H:i:s'));

        for ($i = 0; $i < $months; $i++) {
            $monthStart = (clone $currentDate)->modify('first day of this month')->setTime(0, 0, 0);
            $monthEnd = (clone $currentDate)->modify('last day of this month')->setTime(23, 59, 59);

            $sql = "
                SELECT 
                    COALESCE(SUM(
                        CASE 
                            WHEN sp.periode_facturation = 'yearly' THEN sp.prix / 12
                            WHEN sp.periode_facturation = 'quarterly' THEN sp.prix / 3
                            ELSE sp.prix
                        END
                    ), 0) as forecast_revenue_ht,
                    COALESCE(SUM(
                        CASE 
                            WHEN sp.periode_facturation = 'yearly' THEN (sp.prix / 12) * (sp.taux_tva / 100)
                            WHEN sp.periode_facturation = 'quarterly' THEN (sp.prix / 3) * (sp.taux_tva / 100)
                            ELSE sp.prix * (sp.taux_tva / 100)
                        END
                    ), 0) as forecast_revenue_tva,
                    COALESCE(SUM(
                        CASE 
                            WHEN sp.periode_facturation = 'yearly' THEN (sp.prix / 12) * (1 + sp.taux_tva / 100)
                            WHEN sp.periode_facturation = 'quarterly' THEN (sp.prix / 3) * (1 + sp.taux_tva / 100)
                            ELSE sp.prix * (1 + sp.taux_tva / 100)
                        END
                    ), 0) as forecast_revenue_ttc
                FROM aiolia.abonnements_organisateurs ao
                INNER JOIN aiolia.plans_abonnements sp ON sp.id = ao.id_plan
                INNER JOIN aiolia.profils_organisateurs po ON po.id = ao.id_profil_organisateur
                INNER JOIN aiolia.utilisateurs u ON u.id = po.id_utilisateur
                WHERE u.statut = 1
                    AND ao.statut = 'active'
                    AND ao.commence_le <= :monthEnd
                    AND (ao.annule_le IS NULL OR ao.annule_le >= :monthStart)
                    AND (ao.mis_en_pause_le IS NULL OR ao.mis_en_pause_le >= :monthEnd OR ao.repris_le <= :monthStart)
            ";

            $result = $this->connection->executeQuery($sql, [
                'monthStart' => $monthStart->format('Y-m-d H:i:s'),
                'monthEnd' => $monthEnd->format('Y-m-d H:i:s'),
            ]);

            $row = $result->fetchAssociative();

            $forecast[] = [
                'month' => $monthStart->format('Y-m'),
                'month_label' => $monthStart->format('M Y'),
                'revenue' => (float) ($row['forecast_revenue_ttc'] ?? 0),
                'revenue_ht' => (float) ($row['forecast_revenue_ht'] ?? 0),
                'revenue_tva' => (float) ($row['forecast_revenue_tva'] ?? 0),
                'revenue_ttc' => (float) ($row['forecast_revenue_ttc'] ?? 0),
            ];

            $currentDate->modify('+1 month');
        }

        return $forecast;
    }

    /**
     * Calcule la prévision du chiffre d'affaires selon la période filtrée
     * @param int $month 0 = tous les mois, 1-12 = mois spécifique
     * @param int $year Année
     */
    public function calculateRevenueForecastForPeriod(int $month = 0, int $year = 2025): array
    {
        if ($month > 0 && $month <= 12) {
            // Un seul mois
            $monthStart = new \DateTime(sprintf('%d-%02d-01', $year, $month));
            $monthEnd = (clone $monthStart)->modify('last day of this month')->setTime(23, 59, 59);

            $sql = "
                SELECT 
                    COALESCE(SUM(
                        CASE 
                            WHEN sp.periode_facturation = 'yearly' THEN sp.prix / 12
                            WHEN sp.periode_facturation = 'quarterly' THEN sp.prix / 3
                            ELSE sp.prix
                        END
                    ), 0) as forecast_revenue_ht,
                    COALESCE(SUM(
                        CASE 
                            WHEN sp.periode_facturation = 'yearly' THEN (sp.prix / 12) * (sp.taux_tva / 100)
                            WHEN sp.periode_facturation = 'quarterly' THEN (sp.prix / 3) * (sp.taux_tva / 100)
                            ELSE sp.prix * (sp.taux_tva / 100)
                        END
                    ), 0) as forecast_revenue_tva,
                    COALESCE(SUM(
                        CASE 
                            WHEN sp.periode_facturation = 'yearly' THEN (sp.prix / 12) * (1 + sp.taux_tva / 100)
                            WHEN sp.periode_facturation = 'quarterly' THEN (sp.prix / 3) * (1 + sp.taux_tva / 100)
                            ELSE sp.prix * (1 + sp.taux_tva / 100)
                        END
                    ), 0) as forecast_revenue_ttc
                FROM aiolia.abonnements_organisateurs ao
                INNER JOIN aiolia.plans_abonnements sp ON sp.id = ao.id_plan
                INNER JOIN aiolia.profils_organisateurs po ON po.id = ao.id_profil_organisateur
                INNER JOIN aiolia.utilisateurs u ON u.id = po.id_utilisateur
                WHERE u.statut = 1
                    AND ao.commence_le <= :monthEnd
                    AND (ao.annule_le IS NULL OR ao.annule_le >= :monthStart)
                    AND (
                        -- Pas en pause pendant ce mois
                        (ao.mis_en_pause_le IS NULL OR ao.mis_en_pause_le > :monthEnd)
                        OR
                        -- En pause mais repris avant ou pendant le mois
                        (ao.mis_en_pause_le IS NOT NULL AND ao.repris_le IS NOT NULL AND ao.repris_le <= :monthEnd)
                    )
            ";

            $result = $this->connection->executeQuery($sql, [
                'monthStart' => $monthStart->format('Y-m-d H:i:s'),
                'monthEnd' => $monthEnd->format('Y-m-d H:i:s'),
            ]);

            $row = $result->fetchAssociative();

            return [[
                'month' => $monthStart->format('Y-m'),
                'month_label' => $monthStart->format('M Y'),
                'revenue' => (float) ($row['forecast_revenue_ttc'] ?? 0),
                'revenue_ht' => (float) ($row['forecast_revenue_ht'] ?? 0),
                'revenue_tva' => (float) ($row['forecast_revenue_tva'] ?? 0),
                'revenue_ttc' => (float) ($row['forecast_revenue_ttc'] ?? 0),
            ]];
        } else {
            // Toute l'année
            return $this->calculateRevenueForecastForYear($year);
        }
    }

    /**
     * Calcule la prévision du chiffre d'affaires pour toute l'année (janvier à décembre) avec détail HT/TTC/TVA
     */
    public function calculateRevenueForecastForYear(int $year = 2025): array
    {
        $forecast = [];

        for ($month = 1; $month <= 12; $month++) {
            $monthStart = new \DateTime(sprintf('%d-%02d-01', $year, $month));
            $monthEnd = (clone $monthStart)->modify('last day of this month')->setTime(23, 59, 59);

            $sql = "
                SELECT 
                    COALESCE(SUM(
                        CASE 
                            WHEN sp.periode_facturation = 'yearly' THEN sp.prix / 12
                            WHEN sp.periode_facturation = 'quarterly' THEN sp.prix / 3
                            ELSE sp.prix
                        END
                    ), 0) as forecast_revenue_ht,
                    COALESCE(SUM(
                        CASE 
                            WHEN sp.periode_facturation = 'yearly' THEN (sp.prix / 12) * (sp.taux_tva / 100)
                            WHEN sp.periode_facturation = 'quarterly' THEN (sp.prix / 3) * (sp.taux_tva / 100)
                            ELSE sp.prix * (sp.taux_tva / 100)
                        END
                    ), 0) as forecast_revenue_tva,
                    COALESCE(SUM(
                        CASE 
                            WHEN sp.periode_facturation = 'yearly' THEN (sp.prix / 12) * (1 + sp.taux_tva / 100)
                            WHEN sp.periode_facturation = 'quarterly' THEN (sp.prix / 3) * (1 + sp.taux_tva / 100)
                            ELSE sp.prix * (1 + sp.taux_tva / 100)
                        END
                    ), 0) as forecast_revenue_ttc
                FROM aiolia.abonnements_organisateurs ao
                INNER JOIN aiolia.plans_abonnements sp ON sp.id = ao.id_plan
                INNER JOIN aiolia.profils_organisateurs po ON po.id = ao.id_profil_organisateur
                INNER JOIN aiolia.utilisateurs u ON u.id = po.id_utilisateur
                WHERE u.statut = 1
                    AND ao.commence_le <= :monthEnd
                    AND (ao.annule_le IS NULL OR ao.annule_le >= :monthStart)
                    AND (
                        -- Pas en pause pendant ce mois
                        (ao.mis_en_pause_le IS NULL OR ao.mis_en_pause_le > :monthEnd)
                        OR
                        -- En pause mais repris avant ou pendant le mois
                        (ao.mis_en_pause_le IS NOT NULL AND ao.repris_le IS NOT NULL AND ao.repris_le <= :monthEnd)
                    )
            ";

            $result = $this->connection->executeQuery($sql, [
                'monthStart' => $monthStart->format('Y-m-d H:i:s'),
                'monthEnd' => $monthEnd->format('Y-m-d H:i:s'),
            ]);

            $row = $result->fetchAssociative();

            $forecast[] = [
                'month' => $monthStart->format('Y-m'),
                'month_label' => $monthStart->format('M Y'),
                'revenue' => (float) ($row['forecast_revenue_ttc'] ?? 0),
                'revenue_ht' => (float) ($row['forecast_revenue_ht'] ?? 0),
                'revenue_tva' => (float) ($row['forecast_revenue_tva'] ?? 0),
                'revenue_ttc' => (float) ($row['forecast_revenue_ttc'] ?? 0),
            ];
        }

        return $forecast;
    }

    /**
     * Récupère les nouveaux organisateurs par mois pour les 6 derniers mois
     */
    public function getNewOrganizersByMonth(\DateTimeInterface $startDate, int $months = 6): array
    {
        $data = [];
        $currentDate = $startDate instanceof \DateTime ? clone $startDate : new \DateTime($startDate->format('Y-m-d H:i:s'));

        for ($i = 0; $i < $months; $i++) {
            $monthStart = (clone $currentDate)->modify('first day of this month')->setTime(0, 0, 0);
            $monthEnd = (clone $currentDate)->modify('last day of this month')->setTime(23, 59, 59);

            $count = $this->countNewOrganizersForMonth($currentDate);

            $data[] = [
                'month' => $monthStart->format('Y-m'),
                'month_label' => $monthStart->format('M Y'),
                'count' => $count,
            ];

            $currentDate->modify('-1 month');
        }

        return array_reverse($data);
    }

    /**
     * Récupère les nouveaux organisateurs par mois pour toute l'année (janvier à décembre)
     */
    public function getNewOrganizersByYear(int $year = 2025): array
    {
        $data = [];
        $startDate = new \DateTime(sprintf('%d-01-01', $year));

        for ($month = 1; $month <= 12; $month++) {
            $monthDate = new \DateTime(sprintf('%d-%02d-01', $year, $month));
            $count = $this->countNewOrganizersForMonth($monthDate);

            $data[] = [
                'month' => $monthDate->format('Y-m'),
                'month_label' => $monthDate->format('M Y'),
                'count' => $count,
            ];
        }

        return $data;
    }

    /**
     * Récupère les nouveaux organisateurs selon la période filtrée
     * @param int $month 0 = tous les mois, 1-12 = mois spécifique
     * @param int $year Année
     */
    public function getNewOrganizersByPeriod(int $month = 0, int $year = 2025): array
    {
        if ($month > 0 && $month <= 12) {
            // Un seul mois
            $monthDate = new \DateTime(sprintf('%d-%02d-01', $year, $month));
            $count = $this->countNewOrganizersForMonth($monthDate);
            
            return [[
                'month' => $monthDate->format('Y-m'),
                'month_label' => $monthDate->format('M Y'),
                'count' => $count,
            ]];
        } else {
            // Toute l'année
            return $this->getNewOrganizersByYear($year);
        }
    }

    /**
     * Récupère la répartition des abonnements par niveau (Basic/Pro/Enterprise)
     * @param int $month 0 = tous les mois, 1-12 = mois spécifique
     * @param int $year Année
     */
    public function getSubscriptionDistribution(int $month = 0, int $year = 2025): array
    {
        // Pour la répartition, on cherche les abonnements actifs pendant la période
        if ($month > 0 && $month <= 12) {
            $periodStart = sprintf('%d-%02d-01', $year, $month);
            $periodEnd = (new \DateTime($periodStart))->modify('last day of this month')->format('Y-m-d 23:59:59');
        } else {
            // Pour "Tous les mois", on cherche les abonnements actifs à n'importe quel moment de l'année
            $periodStart = sprintf('%d-01-01', $year);
            $periodEnd = sprintf('%d-12-31 23:59:59', $year);
        }

        $sql = "
            SELECT 
                sp.niveau as plan_level,
                COUNT(DISTINCT ao.id) as count
            FROM aiolia.abonnements_organisateurs ao
            INNER JOIN aiolia.plans_abonnements sp ON sp.id = ao.id_plan
            INNER JOIN aiolia.profils_organisateurs po ON po.id = ao.id_profil_organisateur
            INNER JOIN aiolia.utilisateurs u ON u.id = po.id_utilisateur
            WHERE u.statut = 1
                -- Abonnement commencé avant ou pendant la période
                AND ao.commence_le <= :periodEnd
                -- Abonnement non annulé ou annulé après le début de la période
                AND (ao.annule_le IS NULL OR ao.annule_le >= :periodStart)
                -- Abonnement actif pendant au moins une partie de la période
                AND (
                    -- Cas 1 : Jamais en pause (actif pendant toute la période si commencé avant)
                    (ao.mis_en_pause_le IS NULL)
                    OR
                    -- Cas 2 : En pause mais repris pendant ou avant la fin de la période
                    (ao.mis_en_pause_le IS NOT NULL AND ao.repris_le IS NOT NULL AND ao.repris_le <= :periodEnd)
                    OR
                    -- Cas 3 : Commencé avant la période et mis en pause après le début (actif au début de la période)
                    (ao.commence_le < :periodStart AND ao.mis_en_pause_le IS NOT NULL AND ao.mis_en_pause_le > :periodStart)
                    OR
                    -- Cas 4 : Commencé pendant la période et pas encore en pause à la fin
                    (ao.commence_le >= :periodStart AND (ao.mis_en_pause_le IS NULL OR ao.mis_en_pause_le > :periodEnd))
                )
        ";

        $params = [
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
        ];

        $sql .= "
            GROUP BY sp.niveau
            ORDER BY sp.niveau
        ";

        $result = $this->connection->executeQuery($sql, $params);
        $rows = $result->fetchAllAssociative();

        $distribution = [
            'basic' => 0,
            'pro' => 0,
            'enterprise' => 0,
        ];

        foreach ($rows as $row) {
            $level = strtolower($row['plan_level']);
            if (isset($distribution[$level])) {
                $distribution[$level] = (int) $row['count'];
            }
        }

        return $distribution;
    }

    /**
     * Récupère le top des payeurs (organisateurs avec le plus de revenus)
     * @param int $limit Nombre de résultats
     * @param int $month 0 = tous les mois, 1-12 = mois spécifique
     * @param int $year Année
     */
    public function getTopPayers(int $limit = 10, int $month = 0, int $year = 2025): array
    {
        // Pour les top payeurs, on cherche les factures payées pendant la période
        if ($month > 0 && $month <= 12) {
            $periodStart = sprintf('%d-%02d-01', $year, $month);
            $periodEnd = (new \DateTime($periodStart))->modify('last day of this month')->format('Y-m-d 23:59:59');
        } else {
            $periodStart = sprintf('%d-01-01', $year);
            $periodEnd = sprintf('%d-12-31 23:59:59', $year);
        }

        $sql = "
            SELECT 
                po.id as organizer_id,
                po.nom_affichage as organizer_name,
                u.email,
                COALESCE(SUM(fa.montant_total), 0) as total_paid
            FROM aiolia.profils_organisateurs po
            INNER JOIN aiolia.utilisateurs u ON u.id = po.id_utilisateur
            LEFT JOIN aiolia.abonnements_organisateurs ao ON ao.id_profil_organisateur = po.id
            LEFT JOIN aiolia.factures_abonnements fa ON fa.id_abonnement = ao.id
            WHERE u.statut = 1
                AND u.role = 'organizer'
                AND fa.statut = 'paid'
                AND fa.payee_le >= :periodStart
                AND fa.payee_le <= :periodEnd
        ";

        $params = [
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
        ];

        $sql .= "
            GROUP BY po.id, po.nom_affichage, u.email
            ORDER BY total_paid DESC
            LIMIT :limit
        ";

        $params['limit'] = $limit;

        $result = $this->connection->executeQuery($sql, $params, ['limit' => ParameterType::INTEGER]);
        return $result->fetchAllAssociative();
    }
}

