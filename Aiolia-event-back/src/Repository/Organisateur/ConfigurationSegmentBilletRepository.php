<?php

namespace App\Repository\Organisateur;

use App\Entity\ConfigurationSegmentBillet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ConfigurationSegmentBillet>
 */
class ConfigurationSegmentBilletRepository extends ServiceEntityRepository
{
    private const NOT_DELETED_CONDITION = 'c.supprimeLe IS NULL';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConfigurationSegmentBillet::class);
    }

    /**
     * Récupère tous les segments de billets (non supprimés)
     */
    public function getAll(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere(self::NOT_DELETED_CONDITION)
            ->orderBy('c.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les segments de billets actifs (non supprimés)
     */
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

    /**
     * Récupère un segment de billet par son ID
     */
    public function getById(string $id): ?ConfigurationSegmentBillet
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.id = :id')
            ->andWhere(self::NOT_DELETED_CONDITION)
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère un segment de billet par son nom
     */
    public function findByNom(string $nom): ?ConfigurationSegmentBillet
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.nom = :nom')
            ->andWhere(self::NOT_DELETED_CONDITION)
            ->setParameter('nom', $nom)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Crée un nouveau segment de billet
     */
    public function create(ConfigurationSegmentBillet $segment): ConfigurationSegmentBillet
    {
        $this->getEntityManager()->persist($segment);
        $this->getEntityManager()->flush();

        return $segment;
    }

    /**
     * Met à jour un segment de billet
     */
    public function update(ConfigurationSegmentBillet $segment): ConfigurationSegmentBillet
    {
        $this->getEntityManager()->flush();

        return $segment;
    }

    /**
     * Supprime définitivement un segment de billet
     */
    public function delete(ConfigurationSegmentBillet $segment): void
    {
        $this->getEntityManager()->remove($segment);
        $this->getEntityManager()->flush();
    }

    /**
     * Suppression logique (soft delete) d'un segment de billet
     */
    public function softDelete(ConfigurationSegmentBillet $segment): void
    {
        $segment->softDelete();
        $this->getEntityManager()->flush();
    }

    /**
     * Restaure un segment de billet supprimé logiquement
     */
    public function restore(ConfigurationSegmentBillet $segment): void
    {
        $segment->restore();
        $this->getEntityManager()->flush();
    }
}

