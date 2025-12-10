<?php

namespace App\Repository\Organisateur;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

class EventStatsRepository
{
    public function __construct(private readonly Connection $connection)
    {
    }

    
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

    
    public function getMaxUserCount(): int
    {
        $sql = "SELECT COUNT(*) AS total FROM aiolia.utilisateurs WHERE role = 'user'";
        $row = $this->connection->fetchAssociative($sql);

        return (int) ($row['total'] ?? 0);
    }

    
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
}

