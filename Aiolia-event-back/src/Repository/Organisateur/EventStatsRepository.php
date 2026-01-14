<?php

namespace App\Repository\Organisateur;

use Doctrine\DBAL\Connection;
use DateTimeImmutable;

class EventStatsRepository
{
    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * Nombre de vues par événement (toutes périodes)
     */
    public function getViewsCountByEventIds(array $eventIds): array
    {
        if (empty($eventIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($eventIds), '?'));
        $sql = "
            SELECT id_evenement AS event_id, COUNT(*) AS total
            FROM aiolia.vues_evenements
            WHERE id_evenement IN ({$placeholders})
            GROUP BY id_evenement
        ";

        $rows = $this->connection->fetchAllAssociative($sql, $eventIds);

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['event_id']] = (int) $row['total'];
        }

        return $result;
    }

    /**
     * Nombre de favoris par événement (toutes périodes)
     */
    public function getFavoritesCountByEventIds(array $eventIds): array
    {
        if (empty($eventIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($eventIds), '?'));
        $sql = "
            SELECT id_evenement AS event_id, COUNT(DISTINCT id_liste_souhaits) AS total
            FROM aiolia.elements_listes_souhaits
            WHERE id_evenement IN ({$placeholders})
            GROUP BY id_evenement
        ";

        $rows = $this->connection->fetchAllAssociative($sql, $eventIds);

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['event_id']] = (int) $row['total'];
        }

        return $result;
    }

    /**
     * Nombre total d'utilisateurs finaux (front)
     */
    public function getMaxUserCount(): int
    {
        $sql = "SELECT COUNT(*) AS total FROM aiolia.utilisateurs WHERE role = 'user'";
        $row = $this->connection->fetchAssociative($sql);

        return (int) ($row['total'] ?? 0);
    }

    /**
     * Nombre de participants (billets attribués) par événement
     */
    public function getParticipantsCountByEventIds(array $eventIds): array
    {
        if (empty($eventIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($eventIds), '?'));
        $sql = "
            SELECT
                tb.id_evenement AS event_id,
                COUNT(DISTINCT b.id_utilisateur_proprietaire) AS total
            FROM aiolia.billets b
            INNER JOIN aiolia.types_billets tb ON b.id_type_billet = tb.id
            WHERE tb.id_evenement IN ({$placeholders})
                AND b.id_utilisateur_proprietaire IS NOT NULL
            GROUP BY tb.id_evenement
        ";

        $rows = $this->connection->fetchAllAssociative($sql, $eventIds);

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['event_id']] = (int) $row['total'];
        }

        return $result;
    }

    /**
     * Dépense d'abonnement TTC totale pour un profil organisateur
     *
     * Somme de toutes les factures d'abonnements payées (ou partiellement payées)
     * liées à ce profil, toutes périodes confondues.
     */
    public function getCurrentMonthSubscriptionExpenseForOrganizer(int $organizerProfileId): float
    {
        $sql = "
            SELECT COALESCE(SUM(fa.montant_ttc), 0) AS total
            FROM aiolia.factures_abonnements fa
            INNER JOIN aiolia.abonnements_organisateurs ao ON ao.id = fa.id_abonnement
            WHERE ao.id_profil_organisateur = :organizerProfileId
              AND fa.statut IN ('paid', 'partially_paid')
        ";

        $row = $this->connection->fetchAssociative($sql, [
            'organizerProfileId' => $organizerProfileId,
        ]);

        return (float) ($row['total'] ?? 0.0);
    }

    /**
     * Évolution des ventes dans le temps (groupées par date d'émission de facture)
     * pour les événements d'un organisateur
     *
     * @param array $eventIds IDs des événements
     * @param \DateTimeInterface|null $dateFrom Date de début (optionnel)
     * @param \DateTimeInterface|null $dateTo Date de fin (optionnel)
     * @return array [['date' => 'YYYY-MM-DD', 'count' => int, 'revenue' => float], ...]
     */
    public function getSalesEvolutionOverTime(array $eventIds, ?\DateTimeInterface $dateFrom = null, ?\DateTimeInterface $dateTo = null): array
    {
        if (empty($eventIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($eventIds), '?'));
        $params = $eventIds;
        $conditions = ["tb.id_evenement IN ({$placeholders})"];

        if ($dateFrom) {
            // Convertir en UTC pour la comparaison avec TIMESTAMPTZ
            $dateFromImmutable = $dateFrom instanceof \DateTimeImmutable 
                ? $dateFrom 
                : \DateTimeImmutable::createFromMutable($dateFrom);
            $utcDateFrom = $dateFromImmutable->setTimezone(new \DateTimeZone('UTC'));
            $conditions[] = "fb.emise_le >= ?";
            $params[] = $utcDateFrom->format('Y-m-d H:i:s');
        }

        if ($dateTo) {
            // Convertir en UTC pour la comparaison avec TIMESTAMPTZ
            $dateToImmutable = $dateTo instanceof \DateTimeImmutable 
                ? $dateTo 
                : \DateTimeImmutable::createFromMutable($dateTo);
            $utcDateTo = $dateToImmutable->setTimezone(new \DateTimeZone('UTC'));
            $conditions[] = "fb.emise_le <= ?";
            $params[] = $utcDateTo->format('Y-m-d H:i:s');
        }

        $whereClause = implode(' AND ', $conditions);

        $sql = "
            SELECT
                DATE(fb.emise_le) AS sale_date,
                COUNT(DISTINCT fb.id) AS ticket_count,
                COALESCE(SUM(fb.montant_ttc::numeric), 0) AS revenue
            FROM aiolia.factures_billets fb
            INNER JOIN aiolia.elements_commandes ec ON ec.id_commande = fb.id_commande
            INNER JOIN aiolia.types_billets tb ON tb.id = ec.id_type_billet
            WHERE {$whereClause}
              AND fb.statut IN ('paid', 'partially_paid')
            GROUP BY DATE(fb.emise_le)
            ORDER BY sale_date ASC
        ";

        $rows = $this->connection->fetchAllAssociative($sql, $params);

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'date' => $row['sale_date'],
                'count' => (int) $row['ticket_count'],
                'revenue' => (float) $row['revenue'],
            ];
        }

        return $result;
    }

    /**
     * Répartition des ventes par type de billet (catégorie)
     * pour les événements d'un organisateur
     *
     * @param array $eventIds IDs des événements
     * @param \DateTimeInterface|null $dateFrom Date de début (optionnel)
     * @param \DateTimeInterface|null $dateTo Date de fin (optionnel)
     * @return array [['category' => string, 'count' => int, 'revenue' => float], ...]
     */
    public function getSalesDistributionByTicketType(array $eventIds, ?\DateTimeInterface $dateFrom = null, ?\DateTimeInterface $dateTo = null): array
    {
        if (empty($eventIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($eventIds), '?'));
        $params = $eventIds;
        $conditions = ["tb.id_evenement IN ({$placeholders})"];

        if ($dateFrom) {
            // Convertir en UTC pour la comparaison avec TIMESTAMPTZ
            $dateFromImmutable = $dateFrom instanceof \DateTimeImmutable 
                ? $dateFrom 
                : \DateTimeImmutable::createFromMutable($dateFrom);
            $utcDateFrom = $dateFromImmutable->setTimezone(new \DateTimeZone('UTC'));
            $conditions[] = "fb.emise_le >= ?";
            $params[] = $utcDateFrom->format('Y-m-d H:i:s');
        }

        if ($dateTo) {
            // Convertir en UTC pour la comparaison avec TIMESTAMPTZ
            $dateToImmutable = $dateTo instanceof \DateTimeImmutable 
                ? $dateTo 
                : \DateTimeImmutable::createFromMutable($dateTo);
            $utcDateTo = $dateToImmutable->setTimezone(new \DateTimeZone('UTC'));
            $conditions[] = "fb.emise_le <= ?";
            $params[] = $utcDateTo->format('Y-m-d H:i:s');
        }

        $whereClause = implode(' AND ', $conditions);

        $sql = "
            SELECT
                COALESCE(ccb.nom::text, 'Non défini') AS category,
                COUNT(DISTINCT fb.id) AS ticket_count,
                COALESCE(SUM(fb.montant_ttc::numeric), 0) AS revenue
            FROM aiolia.factures_billets fb
            INNER JOIN aiolia.elements_commandes ec ON ec.id_commande = fb.id_commande
            INNER JOIN aiolia.types_billets tb ON tb.id = ec.id_type_billet
            LEFT JOIN aiolia.configuration_categories_billets ccb ON ccb.id = tb.id_configuration_categorie
            WHERE {$whereClause}
              AND fb.statut IN ('paid', 'partially_paid')
            GROUP BY ccb.nom
            ORDER BY revenue DESC
        ";

        $rows = $this->connection->fetchAllAssociative($sql, $params);

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'category' => $row['category'],
                'count' => (int) $row['ticket_count'],
                'revenue' => (float) $row['revenue'],
            ];
        }

        return $result;
    }

    /**
     * Comparaison des ventes par type de billet (nom du type)
     * pour les événements d'un organisateur
     *
     * @param array $eventIds IDs des événements
     * @param \DateTimeInterface|null $dateFrom Date de début (optionnel)
     * @param \DateTimeInterface|null $dateTo Date de fin (optionnel)
     * @return array [['ticket_type' => string, 'count' => int, 'revenue' => float], ...]
     */
    public function getSalesComparisonByTicketType(array $eventIds, ?\DateTimeInterface $dateFrom = null, ?\DateTimeInterface $dateTo = null): array
    {
        if (empty($eventIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($eventIds), '?'));
        $params = $eventIds;
        $conditions = ["tb.id_evenement IN ({$placeholders})"];

        if ($dateFrom) {
            // Convertir en UTC pour la comparaison avec TIMESTAMPTZ
            $dateFromImmutable = $dateFrom instanceof \DateTimeImmutable 
                ? $dateFrom 
                : \DateTimeImmutable::createFromMutable($dateFrom);
            $utcDateFrom = $dateFromImmutable->setTimezone(new \DateTimeZone('UTC'));
            $conditions[] = "fb.emise_le >= ?";
            $params[] = $utcDateFrom->format('Y-m-d H:i:s');
        }

        if ($dateTo) {
            // Convertir en UTC pour la comparaison avec TIMESTAMPTZ
            $dateToImmutable = $dateTo instanceof \DateTimeImmutable 
                ? $dateTo 
                : \DateTimeImmutable::createFromMutable($dateTo);
            $utcDateTo = $dateToImmutable->setTimezone(new \DateTimeZone('UTC'));
            $conditions[] = "fb.emise_le <= ?";
            $params[] = $utcDateTo->format('Y-m-d H:i:s');
        }

        $whereClause = implode(' AND ', $conditions);

        $sql = "
            SELECT
                tb.nom AS ticket_type,
                COUNT(DISTINCT fb.id) AS ticket_count,
                COALESCE(SUM(fb.montant_ttc::numeric), 0) AS revenue
            FROM aiolia.factures_billets fb
            INNER JOIN aiolia.elements_commandes ec ON ec.id_commande = fb.id_commande
            INNER JOIN aiolia.types_billets tb ON tb.id = ec.id_type_billet
            WHERE {$whereClause}
              AND fb.statut IN ('paid', 'partially_paid')
            GROUP BY tb.nom, tb.id
            ORDER BY revenue DESC
            LIMIT 10
        ";

        $rows = $this->connection->fetchAllAssociative($sql, $params);

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'ticket_type' => $row['ticket_type'] ?: 'Non défini',
                'count' => (int) $row['ticket_count'],
                'revenue' => (float) $row['revenue'],
            ];
        }

        return $result;
    }

    /**
     * Taux de remplissage (occupation) par événement
     * Retourne pour chaque événement : capacité totale, billets vendus, et taux de remplissage
     * Utilise la table billets pour être cohérent avec la page de gestion des billets
     *
     * @param array $eventIds IDs des événements
     * @return array [['event_id' => int, 'event_title' => string, 'total_capacity' => int, 'sold_tickets' => int, 'occupation_rate' => float], ...]
     */
    public function getTicketOccupancyRateByEventIds(array $eventIds): array
    {
        if (empty($eventIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($eventIds), '?'));
        
        // Utiliser la table billets au lieu de inventaire_billets pour être cohérent avec la page de gestion
        // total_capacity = tous les billets créés (tous statuts)
        // sold_tickets = billets avec elementCommande et statut valid/used (même logique que BilletRepository)
        $sql = "
            SELECT
                e.id AS event_id,
                e.titre AS event_title,
                COUNT(b.id) AS total_capacity,
                COUNT(CASE 
                    WHEN b.id_element_commande IS NOT NULL 
                    AND b.statut IN ('valid', 'used') 
                    THEN 1 
                END) AS sold_tickets,
                CASE 
                    WHEN COUNT(b.id) > 0 
                    THEN ROUND((COUNT(CASE 
                        WHEN b.id_element_commande IS NOT NULL 
                        AND b.statut IN ('valid', 'used') 
                        THEN 1 
                    END)::numeric / COUNT(b.id)::numeric) * 100, 2)
                    ELSE 0
                END AS occupation_rate
            FROM aiolia.evenements e
            LEFT JOIN aiolia.types_billets tb ON tb.id_evenement = e.id
            LEFT JOIN aiolia.billets b ON b.id_type_billet = tb.id
            WHERE e.id IN ({$placeholders})
            GROUP BY e.id, e.titre
            ORDER BY occupation_rate DESC, sold_tickets DESC
        ";

        $rows = $this->connection->fetchAllAssociative($sql, $eventIds);

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'event_id' => (int) $row['event_id'],
                'event_title' => $row['event_title'],
                'total_capacity' => (int) $row['total_capacity'],
                'sold_tickets' => (int) $row['sold_tickets'],
                'occupation_rate' => (float) $row['occupation_rate'],
            ];
        }

        return $result;
    }

}

