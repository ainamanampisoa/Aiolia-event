<?php

namespace App\Repository\Organisateur;

use App\Entity\Event;
use App\Entity\TypeBillet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
}

