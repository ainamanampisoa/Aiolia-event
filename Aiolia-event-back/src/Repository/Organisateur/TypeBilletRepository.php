<?php

namespace App\Repository\Organisateur;

use App\Entity\Event;
use App\Entity\TypeBillet;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TypeBillet>
 */
class TypeBilletRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TypeBillet::class);
    }

    /**
     * Récupère tous les types de billets
     */
    public function getAll(): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.creeLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère un type de billet par son ID
     */
    public function getById(string $id): ?TypeBillet
    {
        return $this->find($id);
    }

    /**
     * Récupère tous les types de billets d'un événement
     */
    public function findByEvenement(Event $evenement): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.evenement = :evenement')
            ->setParameter('evenement', $evenement)
            ->orderBy('t.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Crée un nouveau type de billet
     */
    public function create(TypeBillet $typeBillet): TypeBillet
    {
        $this->getEntityManager()->persist($typeBillet);
        $this->getEntityManager()->flush();

        return $typeBillet;
    }

    /**
     * Met à jour un type de billet
     */
    public function update(TypeBillet $typeBillet): TypeBillet
    {
        $this->getEntityManager()->flush();

        return $typeBillet;
    }

    /**
     * Supprime un type de billet
     */
    public function delete(TypeBillet $typeBillet): void
    {
        $this->getEntityManager()->remove($typeBillet);
        $this->getEntityManager()->flush();
    }

    /**
     * Récupère tous les types de billets pour un organisateur (via ses événements)
     */
    public function findByOrganizer(User $organizer): array
    {
        return $this->createQueryBuilder('t')
            ->innerJoin('t.evenement', 'e')
            ->leftJoin('e.profilOrganisateur', 'op')
            ->leftJoin('App\Entity\OrganisateurEvenement', 'oe', 'WITH', 'oe.evenement = e')
            ->leftJoin('oe.profilOrganisateur', 'op2')
            ->where('op.utilisateur = :organizer OR op2.utilisateur = :organizer')
            ->setParameter('organizer', $organizer)
            ->orderBy('t.creeLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les types de billets pour un organisateur avec pagination
     *
     * @param User $organizer
     * @param int $page Numéro de page (commence à 1)
     * @param int $limit Nombre d'éléments par page
     * @param string|null $categorieFilter ID de la catégorie pour filtrer (optionnel)
     * @param string|null $segmentFilter ID du segment pour filtrer (optionnel)
     * @return Paginator
     */
    public function findByOrganizerPaginated(User $organizer, int $page = 1, int $limit = 6, ?string $categorieFilter = null, ?string $segmentFilter = null): Paginator
    {
        $qb = $this->createQueryBuilder('t')
            ->innerJoin('t.evenement', 'e')
            ->leftJoin('e.profilOrganisateur', 'op')
            ->leftJoin('App\Entity\OrganisateurEvenement', 'oe', 'WITH', 'oe.evenement = e')
            ->leftJoin('oe.profilOrganisateur', 'op2')
            ->where('op.utilisateur = :organizer OR op2.utilisateur = :organizer')
            ->setParameter('organizer', $organizer);

        if ($categorieFilter) {
            $qb->andWhere('t.configurationCategorie = :categorie')
               ->setParameter('categorie', $categorieFilter);
        }

        if ($segmentFilter) {
            $qb->andWhere('t.configurationSegment = :segment')
               ->setParameter('segment', $segmentFilter);
        }

        $qb->orderBy('t.creeLe', 'DESC')
           ->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        return new Paginator($qb, true);
    }
}

