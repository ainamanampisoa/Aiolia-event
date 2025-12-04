<?php

namespace App\Repository\Organisateur;

use App\Entity\Event;
use App\Entity\User;
use App\Entity\EventCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * Récupère tous les événements
     */
    public function getAll(): array
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.creeLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère un événement par son ID
     */
    public function getById(string $id): ?Event
    {
        return $this->find($id);
    }

    /**
     * Crée un nouvel événement
     */
    public function create(Event $event): Event
    {
        $this->getEntityManager()->persist($event);
        $this->getEntityManager()->flush();

        return $event;
    }

    /**
     * Met à jour un événement
     */
    public function update(Event $event): Event
    {
        $this->getEntityManager()->flush();

        return $event;
    }

    /**
     * Supprime un événement
     */
    public function delete(Event $event): void
    {
        $this->getEntityManager()->remove($event);
        $this->getEntityManager()->flush();
    }

    /**
     * Récupère tous les événements publiés à venir
     */
    public function findUpcomingEvents(int $limit = 0): array
    {
        $qb = $this->createQueryBuilder('e')
            ->andWhere('e.status = :status')
            ->andWhere('e.startDate > :now')
            ->setParameter('status', 'published')
            ->setParameter('now', new \DateTime())
            ->orderBy('e.startDate', 'ASC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les événements en vedette
     */
    public function findFeaturedEvents(int $limit = 6): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.status = :status')
            ->andWhere('e.isFeatured = :featured')
            ->andWhere('e.startDate > :now')
            ->setParameter('status', 'published')
            ->setParameter('featured', true)
            ->setParameter('now', new \DateTime())
            ->orderBy('e.startDate', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche d'événements par mot-clé
     */
    public function searchEvents(string $query, array $filters = []): array
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.category', 'c')
            ->andWhere('e.status = :status')
            ->setParameter('status', 'published');

        // Recherche textuelle
        if (!empty($query)) {
            $qb->andWhere('e.title LIKE :query OR e.description LIKE :query OR e.location LIKE :query')
                ->setParameter('query', '%' . $query . '%');
        }

        // Filtre par catégorie
        if (!empty($filters['category'])) {
            $qb->andWhere('c.id = :category')
                ->setParameter('category', $filters['category']);
        }

        // Filtre par date
        if (!empty($filters['startDate'])) {
            $qb->andWhere('e.startDate >= :startDate')
                ->setParameter('startDate', $filters['startDate']);
        }

        if (!empty($filters['endDate'])) {
            $qb->andWhere('e.endDate <= :endDate')
                ->setParameter('endDate', $filters['endDate']);
        }

        // Filtre par localisation
        if (!empty($filters['location'])) {
            $qb->andWhere('e.location LIKE :location')
                ->setParameter('location', '%' . $filters['location'] . '%');
        }

        return $qb->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les événements d'un organisateur
     * Inclut les événements où l'organisateur est l'organisateur principal OU un co-organisateur
     */
    public function findByOrganizer(User $organizer, ?string $status = null, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.profilOrganisateur', 'op')
            ->leftJoin('e.organisateursEvenements', 'oe')
            ->leftJoin('oe.profilOrganisateur', 'op2')
            ->leftJoin('op.utilisateur', 'u1')
            ->leftJoin('op2.utilisateur', 'u2')
            ->where('u1.id = :userId OR u2.id = :userId')
            ->setParameter('userId', $organizer->getId())
            ->groupBy('e.id')
            ->orderBy('e.creeLe', 'DESC');

        if ($status !== null) {
            $qb->andWhere('e.statut = :status')
                ->setParameter('status', $status);
        }

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte les événements d'un organisateur
     * Inclut les événements où l'organisateur est l'organisateur principal OU un co-organisateur
     */
    public function countByOrganizer(User $organizer, ?string $status = null): int
    {
        $qb = $this->createQueryBuilder('e')
            ->select('COUNT(DISTINCT e.id)')
            ->leftJoin('e.profilOrganisateur', 'op')
            ->leftJoin('e.organisateursEvenements', 'oe')
            ->leftJoin('oe.profilOrganisateur', 'op2')
            ->leftJoin('op.utilisateur', 'u1')
            ->leftJoin('op2.utilisateur', 'u2')
            ->where('u1.id = :userId OR u2.id = :userId')
            ->setParameter('userId', $organizer->getId());

        if ($status !== null) {
            $qb->andWhere('e.statut = :status')
                ->setParameter('status', $status);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Récupère les événements par catégorie
     */
    public function findByCategory(EventCategory $category, int $limit = 0): array
    {
        $qb = $this->createQueryBuilder('e')
            ->andWhere('e.category = :category')
            ->andWhere('e.status = :status')
            ->andWhere('e.startDate > :now')
            ->setParameter('category', $category)
            ->setParameter('status', 'published')
            ->setParameter('now', new \DateTime())
            ->orderBy('e.startDate', 'ASC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte les événements par statut
     */
    public function countByStatus(string $status): int
    {
        return $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les statistiques d'un événement
     */
    public function getEventStatistics(Event $event): array
    {
        // Cette méthode sera complétée plus tard avec les statistiques réelles
        return [
            'tickets_sold' => 0,
            'revenue' => 0,
            'views' => 0,
        ];
    }

    /**
     * Recherche multicritères d'événements
     *
     * @param string $idOrganisateur ID du profil organisateur (obligatoire)
     * @param string|null $nomLieu Nom du lieu (peut être null)
     * @param \DateTimeInterface|null $dateDebut Date de début (peut être null)
     * @param \DateTimeInterface|null $dateFin Date de fin (peut être null)
     * @param string|null $typeEvenementId ID du type d'événement (peut être null)
     * @param float|null $prixMin Prix minimum (peut être null ou 0)
     * @param float|null $prixMax Prix maximum (peut être null ou 0)
     * @param string|null $triPrix 'asc' pour croissant, 'desc' pour décroissant, null pour pas de tri par prix
     * @param int|null $limit Limite de résultats
     * @param int|null $offset Offset pour la pagination
     * @param string|null $statut Statut de l'événement (draft, published, cancelled, archived)
     * @return array
     */
    public function searchMultiCriteria(
        string $idOrganisateur,
        ?string $nomLieu = null,
        ?\DateTimeInterface $dateDebut = null,
        ?\DateTimeInterface $dateFin = null,
        ?string $typeEvenementId = null,
        ?float $prixMin = null,
        ?float $prixMax = null,
        ?string $triPrix = null,
        ?int $limit = null,
        ?int $offset = null,
        ?string $statut = null
    ): array {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.lieu', 'lieu')
            ->leftJoin('e.typeEvenement', 'type')
            ->leftJoin('e.profilOrganisateur', 'profil')
            ->leftJoin('e.organisateursEvenements', 'oe')
            ->leftJoin('oe.profilOrganisateur', 'profil2')
            ->leftJoin('App\Entity\TypeBillet', 'tb', 'WITH', 'tb.evenement = e')
            ->andWhere('profil.id = :idOrganisateur OR profil2.id = :idOrganisateur')
            ->setParameter('idOrganisateur', $idOrganisateur)
            ->groupBy('e.id');

        // Filtre par nom de lieu
        if ($nomLieu !== null && $nomLieu !== '') {
            $qb->andWhere('LOWER(lieu.nom) LIKE LOWER(:nomLieu)')
                ->setParameter('nomLieu', '%' . $nomLieu . '%');
        }

        // Filtre par date de début
        if ($dateDebut !== null) {
            $qb->andWhere('e.commenceLe >= :dateDebut')
                ->setParameter('dateDebut', $dateDebut);
        }

        // Filtre par date de fin
        if ($dateFin !== null) {
            $qb->andWhere('e.seTermineLe <= :dateFin')
                ->setParameter('dateFin', $dateFin);
        }

        // Filtre par type d'événement
        if ($typeEvenementId !== null && $typeEvenementId !== '0') {
            $qb->andWhere('type.id = :typeEvenementId')
                ->setParameter('typeEvenementId', $typeEvenementId);
        }

        // Filtre par prix minimum
        if ($prixMin !== null && $prixMin > 0) {
            $qb->andHaving('MIN(tb.prixDeBase) >= :prixMin')
                ->setParameter('prixMin', $prixMin);
        }

        // Filtre par prix maximum
        if ($prixMax !== null && $prixMax > 0) {
            $qb->andHaving('MIN(tb.prixDeBase) <= :prixMax')
                ->setParameter('prixMax', $prixMax);
        }

        // Filtre par statut
        if ($statut !== null && $statut !== '') {
            $qb->andWhere('e.statut = :statut')
                ->setParameter('statut', $statut);
        }

        // Tri par prix
        if ($triPrix === 'asc') {
            $qb->orderBy('MIN(tb.prixDeBase)', 'ASC');
        } elseif ($triPrix === 'desc') {
            $qb->orderBy('MIN(tb.prixDeBase)', 'DESC');
        } else {
            // Tri par défaut par date de début
            $qb->orderBy('e.commenceLe', 'ASC');
        }

        // Limite et offset
        if ($limit !== null && $limit > 0) {
            $qb->setMaxResults($limit);
        }

        if ($offset !== null && $offset > 0) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte les résultats d'une recherche multicritères
     *
     * @param string $idOrganisateur ID du profil organisateur (obligatoire)
     * @param string|null $nomLieu Nom du lieu (peut être null)
     * @param \DateTimeInterface|null $dateDebut Date de début (peut être null)
     * @param \DateTimeInterface|null $dateFin Date de fin (peut être null)
     * @param string|null $typeEvenementId ID du type d'événement (peut être null)
     * @param float|null $prixMin Prix minimum (peut être null ou 0)
     * @param float|null $prixMax Prix maximum (peut être null ou 0)
     * @param string|null $statut Statut de l'événement (draft, published, cancelled, archived)
     * @return int
     */
    public function countSearchMultiCriteria(
        string $idOrganisateur,
        ?string $nomLieu = null,
        ?\DateTimeInterface $dateDebut = null,
        ?\DateTimeInterface $dateFin = null,
        ?string $typeEvenementId = null,
        ?float $prixMin = null,
        ?float $prixMax = null,
        ?string $statut = null
    ): int {
        $qb = $this->createQueryBuilder('e')
            ->select('COUNT(DISTINCT e.id)')
            ->leftJoin('e.lieu', 'lieu')
            ->leftJoin('e.typeEvenement', 'type')
            ->leftJoin('e.profilOrganisateur', 'profil')
            ->leftJoin('e.organisateursEvenements', 'oe')
            ->leftJoin('oe.profilOrganisateur', 'profil2')
            ->leftJoin('App\Entity\TypeBillet', 'tb', 'WITH', 'tb.evenement = e')
            ->andWhere('profil.id = :idOrganisateur OR profil2.id = :idOrganisateur')
            ->setParameter('idOrganisateur', $idOrganisateur);

        // Filtre par nom de lieu
        if ($nomLieu !== null && $nomLieu !== '') {
            $qb->andWhere('LOWER(lieu.nom) LIKE LOWER(:nomLieu)')
                ->setParameter('nomLieu', '%' . $nomLieu . '%');
        }

        // Filtre par date de début
        if ($dateDebut !== null) {
            $qb->andWhere('e.commenceLe >= :dateDebut')
                ->setParameter('dateDebut', $dateDebut);
        }

        // Filtre par date de fin
        if ($dateFin !== null) {
            $qb->andWhere('e.seTermineLe <= :dateFin')
                ->setParameter('dateFin', $dateFin);
        }

        // Filtre par type d'événement
        if ($typeEvenementId !== null && $typeEvenementId !== '0') {
            $qb->andWhere('type.id = :typeEvenementId')
                ->setParameter('typeEvenementId', $typeEvenementId);
        }

        // Filtre par prix minimum
        if ($prixMin !== null && $prixMin > 0) {
            $qb->andHaving('MIN(tb.prixDeBase) >= :prixMin')
                ->setParameter('prixMin', $prixMin);
        }

        // Filtre par prix maximum
        if ($prixMax !== null && $prixMax > 0) {
            $qb->andHaving('MIN(tb.prixDeBase) <= :prixMax')
                ->setParameter('prixMax', $prixMax);
        }

        // Filtre par statut
        if ($statut !== null && $statut !== '') {
            $qb->andWhere('e.statut = :statut')
                ->setParameter('statut', $statut);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Recherche multicritères avec pagination complète
     *
     * @param string $idOrganisateur ID du profil organisateur (obligatoire)
     * @param string|null $nomLieu Nom du lieu (peut être null)
     * @param \DateTimeInterface|null $dateDebut Date de début (peut être null)
     * @param \DateTimeInterface|null $dateFin Date de fin (peut être null)
     * @param string|null $typeEvenementId ID du type d'événement (peut être null)
     * @param float|null $prixMin Prix minimum (peut être null ou 0)
     * @param float|null $prixMax Prix maximum (peut être null ou 0)
     * @param string|null $triPrix 'asc' pour croissant, 'desc' pour décroissant, null pour pas de tri par prix
     * @param int $page Numéro de page (commence à 1)
     * @param int $limit Nombre d'éléments par page
     * @param string|null $statut Statut de l'événement (draft, published, cancelled, archived)
     * @return array ['items' => Event[], 'pagination' => ['total' => int, 'page' => int, 'limit' => int, 'totalPages' => int, 'hasNext' => bool, 'hasPrev' => bool]]
     */
    public function searchMultiCriteriaWithPagination(
        string $idOrganisateur,
        ?string $nomLieu = null,
        ?\DateTimeInterface $dateDebut = null,
        ?\DateTimeInterface $dateFin = null,
        ?string $typeEvenementId = null,
        ?float $prixMin = null,
        ?float $prixMax = null,
        ?string $triPrix = null,
        int $page = 1,
        int $limit = 20,
        ?string $statut = null
    ): array {
        // Calculer l'offset
        $offset = ($page - 1) * $limit;

        // Récupérer les résultats
        $items = $this->searchMultiCriteria(
            $idOrganisateur,
            $nomLieu,
            $dateDebut,
            $dateFin,
            $typeEvenementId,
            $prixMin,
            $prixMax,
            $triPrix,
            $limit,
            $offset,
            $statut
        );

        // Compter le total
        $total = $this->countSearchMultiCriteria(
            $idOrganisateur,
            $nomLieu,
            $dateDebut,
            $dateFin,
            $typeEvenementId,
            $prixMin,
            $prixMax,
            $statut
        );

        // Calculer les informations de pagination
        $totalPages = (int) ceil($total / $limit);
        $hasNext = $page < $totalPages;
        $hasPrev = $page > 1;

        return [
            'items' => $items,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'totalPages' => $totalPages,
                'hasNext' => $hasNext,
                'hasPrev' => $hasPrev,
                'offset' => $offset,
                'startItem' => $total > 0 ? $offset + 1 : 0,
                'endItem' => min($offset + $limit, $total),
            ],
        ];
    }
}

