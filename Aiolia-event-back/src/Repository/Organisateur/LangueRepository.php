<?php

namespace App\Repository\Organisateur;

use App\Entity\Langue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class LangueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Langue::class);
    }

    
    public function getAll(): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.estActif = :actif')
            ->setParameter('actif', true)
            ->orderBy('l.libelle', 'ASC')
            ->getQuery()
            ->getResult();
    }

    
    public function getById(string $id): ?Langue
    {
        return $this->find($id);
    }

    
    public function findByCode(string $code): ?Langue
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }

    
    public function create(Langue $langue): Langue
    {
        $this->getEntityManager()->persist($langue);
        $this->getEntityManager()->flush();

        return $langue;
    }

    
    public function update(Langue $langue): Langue
    {
        $this->getEntityManager()->flush();

        return $langue;
    }

    
    public function delete(Langue $langue): void
    {
        $this->getEntityManager()->remove($langue);
        $this->getEntityManager()->flush();
    }
}

