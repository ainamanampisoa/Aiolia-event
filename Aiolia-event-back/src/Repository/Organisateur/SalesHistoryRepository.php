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
            // Si un événement spécifique est sélectionné, filtrer par cet événement
            $where[] = 'e.id = :eventId';
            $params['eventId'] = $event->getId();
        } else {
            // Sinon, filtrer par les événements publiés en cours et à venir
            // Un événement est "en cours ou à venir" s'il n'est pas encore terminé
            // C'est-à-dire : se_termine_le IS NULL OU se_termine_le >= maintenant
            // Cela inclut :
            // - Les événements en cours (commencés mais pas terminés)
            // - Les événements à venir (pas encore commencés)
            // - Les événements sans date de fin (se_termine_le IS NULL)
            $now = new \DateTimeImmutable('now', new \DateTimeZone('Indian/Antananarivo'));
            $where[] = 'e.statut = :statutPublished';
            // Événements qui ne sont pas encore terminés (en cours ou à venir)
            $where[] = '(e.se_termine_le IS NULL OR e.se_termine_le >= :nowDate)';
            $params['statutPublished'] = Event::STATUS_PUBLISHED;
            $params['nowDate'] = $now->format('Y-m-d H:i:s');
        }

        if (!empty($filters['statut'])) {
            $where[] = 'v.billet_statut = :statut';
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
            FROM (
                -- Billets avec factures (ventes) - récupérer aussi les billets associés
                SELECT
                    v.facture_id,
                    v.numero_facture,
                    v.facture_date,
                    v.commande_id,
                    v.client_id,
                    v.client_email,
                    v.devise,
                    v.statut_facture,
                    v.montant_facture_total,
                    v.montant_facture_ttc,
                    v.montant_facture_ht,
                    v.montant_facture_tva,
                    v.montant_remise,
                    v.code_promo,
                    v.type_billet_id,
                    v.type_billet_nom,
                    v.prix_normal,
                    v.quantite,
                    v.montant_ligne_totale,
                    v.evenement_id,
                    v.evenement_titre,
                    b.id AS billet_id,
                    COALESCE(b.statut, 'valid') AS billet_statut,
                    b.code_qr AS billet_code_qr
                FROM aiolia.v_ticket_sales_history v
                INNER JOIN aiolia.types_billets tb ON tb.id = v.type_billet_id
                INNER JOIN aiolia.evenements e ON e.id = v.evenement_id
                LEFT JOIN aiolia.elements_commandes ec ON ec.id_commande = v.commande_id AND ec.id_type_billet = v.type_billet_id
                LEFT JOIN aiolia.billets b ON b.id_element_commande = ec.id AND b.id_type_billet = v.type_billet_id
                LEFT JOIN aiolia.profils_organisateurs op ON op.id = e.id_profil_organisateur
                LEFT JOIN aiolia.organisateurs_evenements oe ON oe.id_evenement = e.id
                LEFT JOIN aiolia.profils_organisateurs op2 ON op2.id = oe.id_profil_organisateur
                WHERE (op.id_utilisateur = :organizerId OR op2.id_utilisateur = :organizerId)
                
                UNION ALL
                
                -- Billets disponibles (dispo) sans facture - avec QR code
                SELECT
                    NULL AS facture_id,
                    'DISPO-' || LPAD(b.id::text, 8, '0') AS numero_facture,
                    b.emis_le AS facture_date,
                    NULL AS commande_id,
                    NULL AS client_id,
                    NULL AS client_email,
                    tb.devise AS devise,
                    'dispo' AS statut_facture,
                    0::numeric AS montant_facture_total,
                    0::numeric AS montant_facture_ttc,
                    0::numeric AS montant_facture_ht,
                    0::numeric AS montant_facture_tva,
                    0::numeric AS montant_remise,
                    NULL AS code_promo,
                    tb.id AS type_billet_id,
                    tb.nom AS type_billet_nom,
                    tb.prix_de_base::numeric AS prix_normal,
                    1 AS quantite,
                    tb.prix_de_base::numeric AS montant_ligne_totale,
                    e.id AS evenement_id,
                    e.titre AS evenement_titre,
                    b.id AS billet_id,
                    b.statut AS billet_statut,
                    b.code_qr AS billet_code_qr
                FROM aiolia.billets b
                INNER JOIN aiolia.types_billets tb ON tb.id = b.id_type_billet
                INNER JOIN aiolia.evenements e ON e.id = tb.id_evenement
                LEFT JOIN aiolia.profils_organisateurs op ON op.id = e.id_profil_organisateur
                LEFT JOIN aiolia.organisateurs_evenements oe ON oe.id_evenement = e.id
                LEFT JOIN aiolia.profils_organisateurs op2 ON op2.id = oe.id_profil_organisateur
                WHERE b.statut = 'dispo'
                    AND b.id_utilisateur_proprietaire IS NULL
                    AND b.id_element_commande IS NULL
                    AND b.code_qr IS NOT NULL
                    AND (op.id_utilisateur = :organizerId OR op2.id_utilisateur = :organizerId)
            ) v
            INNER JOIN aiolia.types_billets tb ON tb.id = v.type_billet_id
            INNER JOIN aiolia.evenements e ON e.id = v.evenement_id
            LEFT JOIN aiolia.configuration_categories_billets cc ON cc.id = tb.id_configuration_categorie
            LEFT JOIN aiolia.configuration_segments_billets cs ON cs.id = tb.id_configuration_segment
            WHERE 1=1
            $whereSql
        ";

        $sqlCount = "SELECT COUNT(*) AS cnt " . $sqlBase;
        $total = (int) $conn->fetchOne($sqlCount, $params);

        $sqlSelect = "
            SELECT
                v.*,
                v.billet_id,
                v.billet_statut,
                v.billet_code_qr,
                cc.nom AS categorie_nom,
                cs.nom AS segment_nom
            " . $sqlBase . "
            ORDER BY 
                CASE WHEN v.statut_facture = 'dispo' THEN 0 ELSE 1 END,
                v.facture_date DESC NULLS LAST,
                v.numero_facture DESC
            LIMIT :limit OFFSET :offset
        ";

        $rows = $conn->fetchAllAssociative($sqlSelect, $params);

        return [
            'rows' => $rows,
            'total' => $total,
        ];
    }
}


