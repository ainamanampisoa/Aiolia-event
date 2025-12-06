<?php

namespace App\Repository\Organisateur;

use App\Entity\HistoriquePrixBillet;
use App\Entity\TypeBillet;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HistoriquePrixBillet>
 */
class HistoriquePrixBilletRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HistoriquePrixBillet::class);
    }

    /**
     * Récupère tous les historiques de prix
     */
    public function getAll(): array
    {
        return $this->createQueryBuilder('h')
            ->orderBy('h.modifieLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère un historique par son ID
     */
    public function getById(string $id): ?HistoriquePrixBillet
    {
        return $this->find($id);
    }

    /**
     * Récupère l'historique des prix pour un type de billet
     */
    public function findByTypeBillet(TypeBillet $typeBillet): array
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.typeBillet = :typeBillet')
            ->setParameter('typeBillet', $typeBillet)
            ->orderBy('h.modifieLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère l'historique des prix pour un événement
     */
    public function findByEvenement(int $evenementId): array
    {
        return $this->createQueryBuilder('h')
            ->innerJoin('h.typeBillet', 'tb')
            ->andWhere('tb.evenement = :evenementId')
            ->setParameter('evenementId', $evenementId)
            ->orderBy('h.modifieLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Crée un nouvel historique de prix
     */
    public function create(HistoriquePrixBillet $historique): HistoriquePrixBillet
    {
        $this->getEntityManager()->persist($historique);
        $this->getEntityManager()->flush();

        return $historique;
    }

    /**
     * Supprime un historique
     */
    public function delete(HistoriquePrixBillet $historique): void
    {
        $this->getEntityManager()->remove($historique);
        $this->getEntityManager()->flush();
    }

    /**
     * Récupère l'historique des prix paginé pour un type de billet
     *
     * @param TypeBillet $typeBillet
     * @param int $page Numéro de page (commence à 1)
     * @param int $limit Nombre d'éléments par page
     * @return Paginator
     */
    public function findByTypeBilletPaginated(TypeBillet $typeBillet, int $page = 1, int $limit = 5): Paginator
    {
        $query = $this->createQueryBuilder('h')
            ->andWhere('h.typeBillet = :typeBillet')
            ->setParameter('typeBillet', $typeBillet)
            ->orderBy('h.modifieLe', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery();

        return new Paginator($query, true);
    }

    /**
     * Récupère l'historique des prix paginé pour un organisateur
     *
     * @param User $organizer
     * @param int $page Numéro de page (commence à 1)
     * @param int $limit Nombre d'éléments par page
     * @param string|null $categorieFilter Filtre par nom de catégorie (ex: 'vip')
     * @param string|null $segmentFilter Filtre par nom de segment (ex: 'enfant')
     * @return Paginator
     */
    public function findByOrganizerPaginated(User $organizer, int $page = 1, int $limit = 5, ?string $categorieFilter = null, ?string $segmentFilter = null): Paginator
    {
        $qb = $this->createQueryBuilder('h')
            ->innerJoin('h.typeBillet', 'tb')
            ->innerJoin('tb.evenement', 'e')
            ->leftJoin('e.profilOrganisateur', 'op')
            ->leftJoin('App\Entity\OrganisateurEvenement', 'oe', 'WITH', 'oe.evenement = e')
            ->leftJoin('oe.profilOrganisateur', 'op2')
            ->where('op.utilisateur = :organizer OR op2.utilisateur = :organizer')
            ->setParameter('organizer', $organizer);

        if ($categorieFilter) {
            $qb->leftJoin('tb.configurationCategorie', 'cc')
               ->andWhere('cc.nom = :categorieFilter')
               ->setParameter('categorieFilter', $categorieFilter);
        }

        if ($segmentFilter) {
            $qb->leftJoin('tb.configurationSegment', 'cs')
               ->andWhere('cs.nom = :segmentFilter')
               ->setParameter('segmentFilter', $segmentFilter);
        }

        $qb->orderBy('h.modifieLe', 'DESC')
           ->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        return new Paginator($qb, true);
    }
}
