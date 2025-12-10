<?php

namespace App\Repository\Organisateur;

use App\Entity\ConfigurationSegmentBillet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class ConfigurationSegmentBilletRepository extends ServiceEntityRepository
{
    private const NOT_DELETED_CONDITION = 'c.supprimeLe IS NULL';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConfigurationSegmentBillet::class);
    }

    
    public function getAll(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere(self::NOT_DELETED_CONDITION)
            ->orderBy('c.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    
    public function getAllActive(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.estActif = :actif')
            ->andWhere(self::NOT_DELETED_CONDITION)
            ->setParameter('actif', true)
            ->orderBy('c.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    
    public function getById(string $id): ?ConfigurationSegmentBillet
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.id = :id')
            ->andWhere(self::NOT_DELETED_CONDITION)
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    
    public function findByNom(string $nom): ?ConfigurationSegmentBillet
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.nom = :nom')
            ->andWhere(self::NOT_DELETED_CONDITION)
            ->setParameter('nom', $nom)
            ->getQuery()
            ->getOneOrNullResult();
    }

    
    public function create(ConfigurationSegmentBillet $segment): ConfigurationSegmentBillet
    {
        $this->getEntityManager()->persist($segment);
        $this->getEntityManager()->flush();

        return $segment;
    }

    
    public function update(ConfigurationSegmentBillet $segment): ConfigurationSegmentBillet
    {
        $this->getEntityManager()->flush();

        return $segment;
    }

    
    public function delete(ConfigurationSegmentBillet $segment): void
    {
        $this->getEntityManager()->remove($segment);
        $this->getEntityManager()->flush();
    }

    
    public function softDelete(ConfigurationSegmentBillet $segment): void
    {
        $segment->softDelete();
        $this->getEntityManager()->flush();
    }

    
    public function restore(ConfigurationSegmentBillet $segment): void
    {
        $segment->restore();
        $this->getEntityManager()->flush();
    }
}

