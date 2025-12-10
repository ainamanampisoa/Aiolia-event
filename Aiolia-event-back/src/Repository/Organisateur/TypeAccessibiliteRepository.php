<?php

namespace App\Repository\Organisateur;

use App\Entity\TypeAccessibilite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class TypeAccessibiliteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TypeAccessibilite::class);
    }

    
    public function getAll(): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.estActif = :actif')
            ->setParameter('actif', true)
            ->orderBy('t.ordreAffichage', 'ASC')
            ->addOrderBy('t.libelle', 'ASC')
            ->getQuery()
            ->getResult();
    }

    
    public function getById(string $id): ?TypeAccessibilite
    {
        return $this->find($id);
    }

    
    public function findByCode(string $code): ?TypeAccessibilite
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }

    
    public function create(TypeAccessibilite $typeAccessibilite): TypeAccessibilite
    {
        $this->getEntityManager()->persist($typeAccessibilite);
        $this->getEntityManager()->flush();

        return $typeAccessibilite;
    }

    
    public function update(TypeAccessibilite $typeAccessibilite): TypeAccessibilite
    {
        $this->getEntityManager()->flush();

        return $typeAccessibilite;
    }

    
    public function delete(TypeAccessibilite $typeAccessibilite): void
    {
        $this->getEntityManager()->remove($typeAccessibilite);
        $this->getEntityManager()->flush();
    }
}

