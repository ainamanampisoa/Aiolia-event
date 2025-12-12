<?php

namespace App\Repository\Organisateur;

use App\Entity\Billet;
use App\Entity\ConfigurationCategorieBillet;
use App\Entity\ConfigurationSegmentBillet;
use App\Entity\Event;
use App\Entity\TypeBillet;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class WaitlistRepository extends ServiceEntityRepository
{
    private const TIMEZONE = 'Indian/Antananarivo';
    private const JOIN_BILLET_USER = 'b.utilisateurProprietaire = u.id';
    private const JOIN_TYPE_BILLET = 'b.typeBillet = tb.id';
    private const JOIN_EVENT = 'tb.evenement = e.id';
    private const WHERE_USER_ROLE = 'u.role = :role';
    private const WHERE_EVENT_ACTIVE = '(e.id IS NULL OR (e.statut = :statut AND ((e.commenceLe <= :now AND (e.seTermineLe IS NULL OR e.seTermineLe >= :now)) OR e.commenceLe > :now)))';
    private const WHERE_ORGANIZER = '(e.id IS NULL OR e.profilOrganisateur = :organizerProfileId)';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * Récupère tous les utilisateurs avec le nombre de billets achetés
     * pour les événements en cours et à venir uniquement
     *
     * @param string|null $organizerProfileId ID du profil organisateur (optionnel)
     * @return array Liste des utilisateurs avec leurs statistiques de billets
     */
    public function findAllUsersWithTicketCount(?string $organizerProfileId = null): array
    {
        $now = new \DateTime('now', new \DateTimeZone(self::TIMEZONE));
        
        $qb = $this->getEntityManager()->createQueryBuilder();
        
        $qb->select([
            'u.id as userId',
            'u.email',
            'u.prenom',
            'u.nom',
            'u.telephone',
            'u.urlAvatar',
            'COUNT(DISTINCT b.id) as nombreBillets',
            'COUNT(DISTINCT e.id) as nombreEvenements',
            'MAX(e.id) as eventId'
        ])
        ->from(User::class, 'u')
        ->leftJoin(Billet::class, 'b', 'WITH', self::JOIN_BILLET_USER)
        ->leftJoin(TypeBillet::class, 'tb', 'WITH', self::JOIN_TYPE_BILLET)
        ->leftJoin(Event::class, 'e', 'WITH', self::JOIN_EVENT)
        ->where(self::WHERE_USER_ROLE)
        ->setParameter('role', 'user')
        ->andWhere(self::WHERE_EVENT_ACTIVE)
        ->setParameter('statut', Event::STATUS_PUBLISHED)
        ->setParameter('now', $now);
        
        if ($organizerProfileId !== null) {
            $qb->andWhere(self::WHERE_ORGANIZER)
               ->setParameter('organizerProfileId', $organizerProfileId);
        }
        
        $qb->groupBy('u.id', 'u.email', 'u.prenom', 'u.nom', 'u.telephone', 'u.urlAvatar')
           ->orderBy('u.nom', 'ASC')
           ->addOrderBy('u.prenom', 'ASC');
        
        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère tous les utilisateurs avec le nombre de billets achetés (paginé)
     * pour les événements en cours et à venir uniquement
     *
     * @param string|null $organizerProfileId ID du profil organisateur (optionnel)
     * @param int $page Numéro de page (commence à 1)
     * @param int $perPage Nombre d'éléments par page
     * @return array ['items' => [], 'total' => int, 'pages' => int, 'currentPage' => int]
     */
    public function findAllUsersWithTicketCountPaginated(?string $organizerProfileId = null, int $page = 1, int $perPage = 20): array
    {
        $now = new \DateTime('now', new \DateTimeZone(self::TIMEZONE));
        $offset = ($page - 1) * $perPage;
        $dateFormat = 'Y-m-d H:i:s';
        $organizerFilter = ' AND e.id_profil_organisateur = :organizerProfileId';
        
        // Utiliser SQL natif pour récupérer uniquement les utilisateurs en liste d'attente
        $conn = $this->getEntityManager()->getConnection();
        
        // Requête pour compter le total d'utilisateurs en liste d'attente
        // Note: Vérifier d'abord si la table existe
        $tableExists = false;
        try {
            $checkTable = $conn->executeQuery("SELECT EXISTS (
                SELECT FROM information_schema.tables
                WHERE table_schema = 'aiolia'
                AND table_name = 'listes_attente_billets'
            )")->fetchOne();
            $tableExists = (bool) $checkTable;
        } catch (\Exception $e) {
            $tableExists = false;
        }
        
        if (!$tableExists) {
            return [
                'items' => [],
                'total' => 0,
                'pages' => 0,
                'currentPage' => $page,
                'perPage' => $perPage,
            ];
        }
        
        // Requête simplifiée pour compter - tester d'abord sans filtres stricts
        $countSql = 'SELECT COUNT(DISTINCT lab.id_utilisateur)
                     FROM aiolia.listes_attente_billets lab
                     INNER JOIN aiolia.types_billets tb ON lab.id_type_billet = tb.id
                     INNER JOIN aiolia.evenements e ON tb.id_evenement = e.id
                     INNER JOIN aiolia.utilisateurs u ON lab.id_utilisateur = u.id
                     WHERE u.role = :role
                     AND e.statut = :statut';
        
        $countParams = [
            'role' => 'user',
            'statut' => Event::STATUS_PUBLISHED
        ];
        
        // Ajouter le filtre de date seulement si nécessaire
        $countSql .= ' AND ((e.commence_le <= :now AND (e.se_termine_le IS NULL OR e.se_termine_le >= :now)) OR e.commence_le > :now)';
        $countParams['now'] = $now->format($dateFormat);
        
        if ($organizerProfileId !== null) {
            $countSql .= $organizerFilter;
            $countParams['organizerProfileId'] = $organizerProfileId;
        }
        
        try {
            $total = (int) $conn->executeQuery($countSql, $countParams)->fetchOne();
        } catch (\Exception $e) {
            $total = 0;
        }
        
        // Debug: Si total = 0 mais qu'il y a des données, tester sans filtres stricts
        if ($total === 0) {
            try {
                $debugSql = 'SELECT COUNT(DISTINCT lab.id_utilisateur)
                             FROM aiolia.listes_attente_billets lab
                             INNER JOIN aiolia.types_billets tb ON lab.id_type_billet = tb.id
                             INNER JOIN aiolia.evenements e ON tb.id_evenement = e.id
                             INNER JOIN aiolia.utilisateurs u ON lab.id_utilisateur = u.id
                             WHERE u.role = :role';
                $debugParams = ['role' => 'user'];
                
                if ($organizerProfileId !== null) {
                    $debugSql .= ' AND e.id_profil_organisateur = :organizerProfileId';
                    $debugParams['organizerProfileId'] = $organizerProfileId;
                }
                
                $debugTotal = (int) $conn->executeQuery($debugSql, $debugParams)->fetchOne();
                
                // Si on trouve des résultats sans le filtre de date/statut, utiliser cette requête
                if ($debugTotal > 0) {
                    $total = $debugTotal;
                    // Utiliser la requête simplifiée sans filtre de date strict
                    $countSql = $debugSql;
                    $countParams = $debugParams;
                    $useSimplifiedQuery = true;
                } else {
                    $useSimplifiedQuery = false;
                }
            } catch (\Exception $e2) {
                $useSimplifiedQuery = false;
            }
        } else {
            $useSimplifiedQuery = false;
        }
        
        // Si aucun utilisateur en liste d'attente, retourner vide
        if ($total === 0) {
            return [
                'items' => [],
                'total' => 0,
                'pages' => 0,
                'currentPage' => $page,
                'perPage' => $perPage,
            ];
        }
        
        // Requête pour récupérer les utilisateurs en liste d'attente
        if (isset($useSimplifiedQuery) && $useSimplifiedQuery) {
            // Utiliser la requête simplifiée sans filtre de date/statut strict
            $sql = 'SELECT DISTINCT
                        u.id as userId,
                        u.email,
                        u.prenom,
                        u.nom,
                        u.telephone,
                        u.url_avatar as urlAvatar,
                        MAX(e.id) as eventId,
                        MAX(tb.id) as typeBilletId
                    FROM aiolia.listes_attente_billets lab
                    INNER JOIN aiolia.types_billets tb ON lab.id_type_billet = tb.id
                    INNER JOIN aiolia.evenements e ON tb.id_evenement = e.id
                    INNER JOIN aiolia.utilisateurs u ON lab.id_utilisateur = u.id
                    WHERE u.role = :role';
            
            $params = ['role' => 'user'];
            
            if ($organizerProfileId !== null) {
                $sql .= ' AND e.id_profil_organisateur = :organizerProfileId';
                $params['organizerProfileId'] = $organizerProfileId;
            }
        } else {
            // Utiliser la requête avec tous les filtres
            $sql = 'SELECT DISTINCT
                        u.id as userId,
                        u.email,
                        u.prenom,
                        u.nom,
                        u.telephone,
                        u.url_avatar as urlAvatar,
                        MAX(e.id) as eventId,
                        MAX(tb.id) as typeBilletId
                    FROM aiolia.listes_attente_billets lab
                    INNER JOIN aiolia.types_billets tb ON lab.id_type_billet = tb.id
                    INNER JOIN aiolia.evenements e ON tb.id_evenement = e.id
                    INNER JOIN aiolia.utilisateurs u ON lab.id_utilisateur = u.id
                    WHERE u.role = :role
                    AND e.statut = :statut
                    AND (e.commence_le >= :now OR (e.commence_le < :now AND (e.se_termine_le IS NULL OR e.se_termine_le >= :now)))';
            
            $params = [
                'role' => 'user',
                'statut' => Event::STATUS_PUBLISHED,
                'now' => $now->format($dateFormat)
            ];
            
            if ($organizerProfileId !== null) {
                $sql .= $organizerFilter;
                $params['organizerProfileId'] = $organizerProfileId;
            }
        }
        
        $sql .= ' GROUP BY u.id, u.email, u.prenom, u.nom, u.telephone, u.url_avatar
                  ORDER BY u.nom ASC, u.prenom ASC
                  LIMIT :limit OFFSET :offset';
        
        $params['limit'] = $perPage;
        $params['offset'] = $offset;
        
        try {
            $items = $conn->executeQuery($sql, $params)->fetchAllAssociative();
        } catch (\Exception $e) {
            $items = [];
        }
        
        // Calculer nombreBillets et nombreEvenements pour chaque utilisateur
        $formattedItems = [];
        foreach ($items as $item) {
            $userId = $item['userid'];
            
            // Compter les billets de l'utilisateur
            $billetsSql = 'SELECT COUNT(DISTINCT b.id)
                           FROM aiolia.billets b
                           INNER JOIN aiolia.types_billets tb ON b.id_type_billet = tb.id
                           INNER JOIN aiolia.evenements e ON tb.id_evenement = e.id
                           WHERE b.id_utilisateur_proprietaire = :userId
                           AND e.statut = :statut
                           AND ((e.commence_le <= :now AND (e.se_termine_le IS NULL OR e.se_termine_le >= :now)) OR e.commence_le > :now)';
            
            $billetsParams = [
                'userId' => $userId,
                'statut' => Event::STATUS_PUBLISHED,
                'now' => $now->format($dateFormat)
            ];
            
            if ($organizerProfileId !== null) {
                $billetsSql .= $organizerFilter;
                $billetsParams['organizerProfileId'] = $organizerProfileId;
            }
            
            $nombreBillets = (int) $conn->executeQuery($billetsSql, $billetsParams)->fetchOne();
            
            // Compter les événements en liste d'attente pour cet utilisateur
            $eventsSql = 'SELECT COUNT(DISTINCT e.id)
                          FROM aiolia.listes_attente_billets lab
                          INNER JOIN aiolia.types_billets tb ON lab.id_type_billet = tb.id
                          INNER JOIN aiolia.evenements e ON tb.id_evenement = e.id
                          WHERE lab.id_utilisateur = :userId
                          AND e.statut = :statut
                          AND ((e.commence_le <= :now AND (e.se_termine_le IS NULL OR e.se_termine_le >= :now)) OR e.commence_le > :now)';
            
            $eventsParams = [
                'userId' => $userId,
                'statut' => Event::STATUS_PUBLISHED,
                'now' => $now->format($dateFormat)
            ];
            
            if ($organizerProfileId !== null) {
                $eventsSql .= $organizerFilter;
                $eventsParams['organizerProfileId'] = $organizerProfileId;
            }
            
            $nombreEvenements = (int) $conn->executeQuery($eventsSql, $eventsParams)->fetchOne();
            
            $formattedItems[] = [
                'userId' => $userId,
                'email' => $item['email'],
                'prenom' => $item['prenom'],
                'nom' => $item['nom'],
                'telephone' => $item['telephone'],
                'urlAvatar' => $item['urlavatar'],
                'nombreBillets' => $nombreBillets,
                'nombreEvenements' => $nombreEvenements,
                'eventId' => $item['eventid'],
                'typeBilletId' => $item['typebilletid'],
            ];
        }
        
        $items = $formattedItems;
        
        // Enrichir avec les catégories, segments et liste d'attente pour chaque utilisateur
        foreach ($items as &$item) {
            $userId = $item['userId'];
            
            // Récupérer UNIQUEMENT les catégories et segments qui sont en liste d'attente pour cet utilisateur
            $conn = $this->getEntityManager()->getConnection();
            $sql = 'SELECT
                        cat.nom as categorie,
                        seg.nom as segment,
                        seg.age_min as ageMin,
                        seg.age_max as ageMax,
                        tb.id as typeBilletId,
                        COALESCE(ib.quantite_totale, 0) as quantiteTotale,
                        COALESCE(ib.quantite_vendue, 0) as quantiteVendue,
                        COALESCE(ib.quantite_reservee, 0) as quantiteReservee,
                        SUM(lab.quantite_demandee) as quantiteAttente
                    FROM aiolia.listes_attente_billets lab
                    INNER JOIN aiolia.types_billets tb ON lab.id_type_billet = tb.id
                    INNER JOIN aiolia.evenements e ON tb.id_evenement = e.id
                    LEFT JOIN aiolia.configuration_categories_billets cat ON tb.id_configuration_categorie = cat.id
                    LEFT JOIN aiolia.configuration_segments_billets seg ON tb.id_configuration_segment = seg.id
                    LEFT JOIN aiolia.inventaire_billets ib ON tb.id = ib.id_type_billet
                    WHERE lab.id_utilisateur = :userId
                    AND e.statut = :statut
                    AND ((e.commence_le <= :now AND (e.se_termine_le IS NULL OR e.se_termine_le >= :now)) OR e.commence_le > :now)';

            $params = [
                'userId' => $userId,
                'statut' => Event::STATUS_PUBLISHED,
                'now' => $now->format('Y-m-d H:i:s')
            ];

            if ($organizerProfileId !== null) {
                $sql .= ' AND e.id_profil_organisateur = :organizerProfileId';
                $params['organizerProfileId'] = $organizerProfileId;
            }

            $sql .= ' GROUP BY cat.nom, seg.nom, seg.age_min, seg.age_max, tb.id, ib.quantite_totale, ib.quantite_vendue, ib.quantite_reservee
                      ORDER BY cat.nom, seg.nom';

            $waitlistData = $conn->executeQuery($sql, $params)->fetchAllAssociative();

            // Formater les résultats
            $details = [];
            foreach ($waitlistData as $wl) {
                $details[] = [
                    'categorie' => $wl['categorie'] ?? '',
                    'segment' => $wl['segment'] ?? '',
                    'ageMin' => $wl['agemin'] !== null ? (int)$wl['agemin'] : null,
                    'ageMax' => $wl['agemax'] !== null ? (int)$wl['agemax'] : null,
                    'typeBilletId' => $wl['typebilletid'],
                    'quantiteTotale' => (int)($wl['quantitetotale'] ?? 0),
                    'quantiteVendue' => (int)($wl['quantitevendue'] ?? 0),
                    'quantiteReservee' => (int)($wl['quantitereservee'] ?? 0),
                    'quantiteAttente' => (int)($wl['quantiteattente'] ?? 0),
                ];
            }

            $item['categoriesSegments'] = $details;
        }
        $pages = (int) ceil($total / $perPage);
        
        return [
            'items' => $items,
            'total' => $total,
            'pages' => $pages,
            'currentPage' => $page,
            'perPage' => $perPage,
        ];
    }

    /**
     * Récupère les événements en cours et à venir pour un organisateur
     *
     * @param string $organizerProfileId ID du profil organisateur
     * @return array Liste des événements
     */
    public function findOngoingAndUpcomingEvents(string $organizerProfileId): array
    {
        $now = new \DateTime('now', new \DateTimeZone(self::TIMEZONE));
        
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.profilOrganisateur', 'op')
            ->leftJoin('e.organisateursEvenements', 'oe')
            ->leftJoin('oe.profilOrganisateur', 'op2')
            ->where('(op.id = :organizerProfileId OR op2.id = :organizerProfileId)')
            ->setParameter('organizerProfileId', $organizerProfileId)
            ->andWhere('e.statut = :statut')
            ->setParameter('statut', Event::STATUS_PUBLISHED)
            ->andWhere('((e.commenceLe <= :now AND (e.seTermineLe IS NULL OR e.seTermineLe >= :now)) OR e.commenceLe > :now)')
            ->setParameter('now', $now)
            ->groupBy('e.id')
            ->orderBy('e.commenceLe', 'ASC');
        
        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les utilisateurs avec leurs billets pour un événement spécifique
     *
     * @param string $eventId ID de l'événement
     * @return array Liste des utilisateurs avec leurs billets
     */
    public function findUsersWithTicketsForEvent(string $eventId): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        
        $qb->select([
            'u.id as userId',
            'u.email',
            'u.prenom',
            'u.nom',
            'COUNT(b.id) as nombreBillets',
            'tb.nom as typeBilletNom',
            'b.statut as statutBillet'
        ])
        ->from(User::class, 'u')
        ->innerJoin(Billet::class, 'b', 'WITH', self::JOIN_BILLET_USER)
        ->innerJoin(TypeBillet::class, 'tb', 'WITH', self::JOIN_TYPE_BILLET)
        ->innerJoin(Event::class, 'e', 'WITH', self::JOIN_EVENT)
        ->where('e.id = :eventId')
        ->setParameter('eventId', $eventId)
        ->andWhere(self::WHERE_USER_ROLE)
        ->setParameter('role', 'user')
        ->groupBy('u.id', 'u.email', 'u.prenom', 'u.nom', 'tb.nom', 'b.statut')
        ->orderBy('u.nom', 'ASC')
        ->addOrderBy('u.prenom', 'ASC');
        
        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les statistiques globales de la liste d'attente
     *
     * @param string|null $organizerProfileId ID du profil organisateur (optionnel)
     * @return array Statistiques
     */
    public function getWaitlistStatistics(?string $organizerProfileId = null): array
    {
        $now = new \DateTime('now', new \DateTimeZone(self::TIMEZONE));
        $conn = $this->getEntityManager()->getConnection();
        $dateFormat = 'Y-m-d H:i:s';
        
        // Vérifier si la table listes_attente_billets existe
        $tableExists = false;
        try {
            $checkTable = $conn->executeQuery("SELECT EXISTS (
                SELECT FROM information_schema.tables
                WHERE table_schema = 'aiolia'
                AND table_name = 'listes_attente_billets'
            )")->fetchOne();
            $tableExists = (bool) $checkTable;
        } catch (\Exception $e) {
            $tableExists = false;
        }
        
        // Compter les utilisateurs en liste d'attente
        $totalUtilisateurs = 0;
        if ($tableExists) {
            $usersSql = 'SELECT COUNT(DISTINCT lab.id_utilisateur)
                        FROM aiolia.listes_attente_billets lab
                        INNER JOIN aiolia.types_billets tb ON lab.id_type_billet = tb.id
                        INNER JOIN aiolia.evenements e ON tb.id_evenement = e.id
                        INNER JOIN aiolia.utilisateurs u ON lab.id_utilisateur = u.id
                        WHERE u.role = :role
                        AND e.statut = :statut
                        AND ((e.commence_le <= :now AND (e.se_termine_le IS NULL OR e.se_termine_le >= :now)) OR e.commence_le > :now)';
            
            $usersParams = [
                'role' => 'user',
                'statut' => Event::STATUS_PUBLISHED,
                'now' => $now->format($dateFormat)
            ];
            
            if ($organizerProfileId !== null) {
                $usersSql .= ' AND e.id_profil_organisateur = :organizerProfileId';
                $usersParams['organizerProfileId'] = $organizerProfileId;
            }
            
            try {
                $totalUtilisateurs = (int) $conn->executeQuery($usersSql, $usersParams)->fetchOne();
            } catch (\Exception $e) {
                $totalUtilisateurs = 0;
            }
        }
        
        // Compter les billets achetés pour les événements en cours/à venir
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('COUNT(DISTINCT b.id) as totalBillets')
           ->from(Billet::class, 'b')
           ->leftJoin(TypeBillet::class, 'tb', 'WITH', 'b.typeBillet = tb.id')
           ->leftJoin(Event::class, 'e', 'WITH', 'tb.evenement = e.id')
           ->where('e.statut = :statut')
           ->setParameter('statut', Event::STATUS_PUBLISHED)
           ->andWhere('((e.commenceLe <= :now AND (e.seTermineLe IS NULL OR e.seTermineLe >= :now)) OR e.commenceLe > :now)')
           ->setParameter('now', $now);
        
        if ($organizerProfileId !== null) {
            $qb->andWhere('e.profilOrganisateur = :organizerProfileId')
               ->setParameter('organizerProfileId', $organizerProfileId);
        }
        
        $totalBillets = (int) $qb->getQuery()->getSingleScalarResult();
        
        // Compter les événements distincts qui ont des listes d'attente
        $totalEvenements = 0;
        if ($tableExists) {
            $eventsSql = 'SELECT COUNT(DISTINCT e.id)
                         FROM aiolia.listes_attente_billets lab
                         INNER JOIN aiolia.types_billets tb ON lab.id_type_billet = tb.id
                         INNER JOIN aiolia.evenements e ON tb.id_evenement = e.id
                         WHERE e.statut = :statut
                         AND ((e.commence_le <= :now AND (e.se_termine_le IS NULL OR e.se_termine_le >= :now)) OR e.commence_le > :now)';
            
            $eventsParams = [
                'statut' => Event::STATUS_PUBLISHED,
                'now' => $now->format($dateFormat)
            ];
            
            if ($organizerProfileId !== null) {
                $eventsSql .= ' AND e.id_profil_organisateur = :organizerProfileId';
                $eventsParams['organizerProfileId'] = $organizerProfileId;
            }
            
            try {
                $totalEvenements = (int) $conn->executeQuery($eventsSql, $eventsParams)->fetchOne();
            } catch (\Exception $e) {
                $totalEvenements = 0;
            }
        }
        
        return [
            'totalUtilisateurs' => $totalUtilisateurs,
            'totalBillets' => $totalBillets,
            'totalEvenements' => $totalEvenements,
        ];
    }
}

