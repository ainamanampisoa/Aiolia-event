<?php

namespace App\Repository\Organisateur;

use App\Entity\Event;
use App\Entity\User;
use App\Entity\TicketInvoice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Accès à la vue aiolia.v_ticket_sales_history pour l'historique des ventes.
 */
class SalesHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        // On rattache le repository à l'entité TicketInvoice pour satisfaire ServiceEntityRepository
        parent::__construct($registry, TicketInvoice::class);
    }

    /**
     * Retourne un paginator sur l'historique des ventes (vue v_ticket_sales_history)
     *
     * @param User         $organizer
     * @param int          $page
     * @param int          $limit
     * @param Event|null   $event
     * @param array<string,mixed> $filters [statut, categorie, segment]
     */
    public function findByOrganizerPaginated(User $organizer, int $page = 1, int $limit = 10, ?Event $event = null, array $filters = []): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $offset = ($page - 1) * $limit;

        $params = [
            'organizerId' => $organizer->getId(),
            'limit' => $limit,
            'offset' => $offset,
        ];

        $where = [];

        if ($event) {
            $where[] = 'e.id = :eventId';
            $params['eventId'] = $event->getId();
        }

        if (!empty($filters['statut'])) {
            $where[] = 'b.statut = :statut';
            $params['statut'] = $filters['statut'];
        }

        if (!empty($filters['categorie'])) {
            $where[] = 'cc.nom = :categorie';
            $params['categorie'] = $filters['categorie'];
        }

        if (!empty($filters['segment'])) {
            $where[] = 'cs.nom = :segment';
            $params['segment'] = $filters['segment'];
        }

        $whereSql = count($where) ? (' AND ' . implode(' AND ', $where)) : '';

        $sqlBase = "
            FROM aiolia.v_ticket_sales_history v
            INNER JOIN aiolia.types_billets tb ON tb.id = v.type_billet_id
            INNER JOIN aiolia.evenements e ON e.id = v.evenement_id
            LEFT JOIN aiolia.elements_commandes ec ON ec.id_commande = v.commande_id AND ec.id_type_billet = v.type_billet_id
            LEFT JOIN aiolia.billets b ON b.id_element_commande = ec.id AND b.id_type_billet = v.type_billet_id
            LEFT JOIN aiolia.configuration_categories_billets cc ON cc.id = tb.id_configuration_categorie
            LEFT JOIN aiolia.configuration_segments_billets cs ON cs.id = tb.id_configuration_segment
            LEFT JOIN aiolia.profils_organisateurs op ON op.id = e.id_profil_organisateur
            LEFT JOIN aiolia.organisateurs_evenements oe ON oe.id_evenement = e.id
            LEFT JOIN aiolia.profils_organisateurs op2 ON op2.id = oe.id_profil_organisateur
            WHERE (op.id_utilisateur = :organizerId OR op2.id_utilisateur = :organizerId)
            $whereSql
        ";

        $sqlCount = "SELECT COUNT(*) AS cnt " . $sqlBase;
        $total = (int) $conn->fetchOne($sqlCount, $params);

        $sqlSelect = "
            SELECT
                v.*,
                b.id AS billet_id,
                b.statut AS billet_statut,
                cc.nom AS categorie_nom,
                cs.nom AS segment_nom
            " . $sqlBase . "
            ORDER BY v.numero_facture DESC
            LIMIT :limit OFFSET :offset
        ";

        $rows = $conn->fetchAllAssociative($sqlSelect, $params);

        return [
            'rows' => $rows,
            'total' => $total,
        ];
    }
}


