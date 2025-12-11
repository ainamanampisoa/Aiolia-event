<?php

namespace App\Repository\Organisateur;

use App\Entity\ElementPanier;
use App\Entity\Panier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class ElementPanierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ElementPanier::class);
    }

    
    public function getAll(): array
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.creeLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    
    public function getById(string $id): ?ElementPanier
    {
        return $this->find($id);
    }

    
    public function findByPanier(Panier $panier): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.panier = :panier')
            ->setParameter('panier', $panier)
            ->orderBy('e.creeLe', 'ASC')
            ->getQuery()
            ->getResult();
    }

    
    public function create(ElementPanier $element): ElementPanier
    {
        $this->getEntityManager()->persist($element);
        $this->getEntityManager()->flush();

        return $element;
    }

    
    public function update(ElementPanier $element): ElementPanier
    {
        $this->getEntityManager()->flush();

        return $element;
    }

    
    public function delete(ElementPanier $element): void
    {
        $this->getEntityManager()->remove($element);
        $this->getEntityManager()->flush();
    }
}

