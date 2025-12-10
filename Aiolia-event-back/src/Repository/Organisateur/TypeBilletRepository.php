<?php

namespace App\Repository\Organisateur;

use App\Entity\Event;
use App\Entity\TypeBillet;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;


class TypeBilletRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TypeBillet::class);
    }

    
    public function getAll(): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.creeLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    
    public function getById(string $id): ?TypeBillet
    {
        return $this->find($id);
    }

    
    public function findByEvenement(Event $evenement): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.evenement = :evenement')
            ->setParameter('evenement', $evenement)
            ->orderBy('t.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    
    public function create(TypeBillet $typeBillet): TypeBillet
    {
        $this->getEntityManager()->persist($typeBillet);
        $this->getEntityManager()->flush();

        return $typeBillet;
    }

    
    public function update(TypeBillet $typeBillet): TypeBillet
    {
        $this->getEntityManager()->flush();

        return $typeBillet;
    }

    
    public function delete(TypeBillet $typeBillet): void
    {
        $this->getEntityManager()->remove($typeBillet);
        $this->getEntityManager()->flush();
    }

    
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

