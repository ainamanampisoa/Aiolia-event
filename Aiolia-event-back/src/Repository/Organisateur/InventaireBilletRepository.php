<?php

namespace App\Repository\Organisateur;

use App\Entity\InventaireBillet;
use App\Entity\TypeBillet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class InventaireBilletRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InventaireBillet::class);
    }

    
    public function getAll(): array
    {
        return $this->createQueryBuilder('i')
            ->orderBy('i.modifieLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    
    public function getById(string $id): ?InventaireBillet
    {
        return $this->find($id);
    }

    
    public function findByTypeBillet(TypeBillet $typeBillet): ?InventaireBillet
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.typeBillet = :typeBillet')
            ->setParameter('typeBillet', $typeBillet)
            ->getQuery()
            ->getOneOrNullResult();
    }

    
    public function create(InventaireBillet $inventaire): InventaireBillet
    {
        $this->getEntityManager()->persist($inventaire);
        $this->getEntityManager()->flush();

        return $inventaire;
    }

    
    public function update(InventaireBillet $inventaire): InventaireBillet
    {
        $this->getEntityManager()->flush();

        return $inventaire;
    }

    
    public function delete(InventaireBillet $inventaire): void
    {
        $this->getEntityManager()->remove($inventaire);
        $this->getEntityManager()->flush();
    }
}

