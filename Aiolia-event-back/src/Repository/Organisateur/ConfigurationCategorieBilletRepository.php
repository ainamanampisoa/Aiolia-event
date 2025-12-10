<?php

namespace App\Repository\Organisateur;

use App\Entity\ConfigurationCategorieBillet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class ConfigurationCategorieBilletRepository extends ServiceEntityRepository
{
    private const NOT_DELETED_CONDITION = 'c.supprimeLe IS NULL';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConfigurationCategorieBillet::class);
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

    
    public function getById(string $id): ?ConfigurationCategorieBillet
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.id = :id')
            ->andWhere(self::NOT_DELETED_CONDITION)
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    
    public function findByNom(string $nom): ?ConfigurationCategorieBillet
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.nom = :nom')
            ->andWhere(self::NOT_DELETED_CONDITION)
            ->setParameter('nom', $nom)
            ->getQuery()
            ->getOneOrNullResult();
    }

    
    public function create(ConfigurationCategorieBillet $categorie): ConfigurationCategorieBillet
    {
        $this->getEntityManager()->persist($categorie);
        $this->getEntityManager()->flush();

        return $categorie;
    }

    
    public function update(ConfigurationCategorieBillet $categorie): ConfigurationCategorieBillet
    {
        $this->getEntityManager()->flush();

        return $categorie;
    }

    
    public function delete(ConfigurationCategorieBillet $categorie): void
    {
        $this->getEntityManager()->remove($categorie);
        $this->getEntityManager()->flush();
    }

    
    public function softDelete(ConfigurationCategorieBillet $categorie): void
    {
        $categorie->softDelete();
        $this->getEntityManager()->flush();
    }

    
    public function restore(ConfigurationCategorieBillet $categorie): void
    {
        $categorie->restore();
        $this->getEntityManager()->flush();
    }
}

