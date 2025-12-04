<?php

namespace App\Repository\Organisateur;

use App\Entity\InventaireBillet;
use App\Entity\TypeBillet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<InventaireBillet>
 */
class InventaireBilletRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InventaireBillet::class);
    }

    /**
     * Récupère tous les inventaires de billets
     */
    public function getAll(): array
    {
        return $this->createQueryBuilder('i')
            ->orderBy('i.modifieLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère un inventaire de billet par son ID (TypeBillet)
     */
    public function getById(string $id): ?InventaireBillet
    {
        return $this->find($id);
    }

    /**
     * Récupère l'inventaire d'un type de billet
     */
    public function findByTypeBillet(TypeBillet $typeBillet): ?InventaireBillet
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.typeBillet = :typeBillet')
            ->setParameter('typeBillet', $typeBillet)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Crée un nouvel inventaire de billet
     */
    public function create(InventaireBillet $inventaire): InventaireBillet
    {
        $this->getEntityManager()->persist($inventaire);
        $this->getEntityManager()->flush();

        return $inventaire;
    }

    /**
     * Met à jour un inventaire de billet
     */
    public function update(InventaireBillet $inventaire): InventaireBillet
    {
        $this->getEntityManager()->flush();

        return $inventaire;
    }

    /**
     * Supprime un inventaire de billet
     */
    public function delete(InventaireBillet $inventaire): void
    {
        $this->getEntityManager()->remove($inventaire);
        $this->getEntityManager()->flush();
    }
}

