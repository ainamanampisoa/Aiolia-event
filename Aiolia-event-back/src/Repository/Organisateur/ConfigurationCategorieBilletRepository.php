<?php

namespace App\Repository\Organisateur;

use App\Entity\ConfigurationCategorieBillet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ConfigurationCategorieBillet>
 */
class ConfigurationCategorieBilletRepository extends ServiceEntityRepository
{
    private const NOT_DELETED_CONDITION = 'c.supprimeLe IS NULL';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConfigurationCategorieBillet::class);
    }

    /**
     * Récupère toutes les catégories de billets (non supprimées)
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
     * Récupère toutes les catégories de billets actives (non supprimées)
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
     * Récupère une catégorie de billet par son ID
     */
    public function getById(string $id): ?ConfigurationCategorieBillet
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.id = :id')
            ->andWhere(self::NOT_DELETED_CONDITION)
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère une catégorie de billet par son nom
     */
    public function findByNom(string $nom): ?ConfigurationCategorieBillet
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.nom = :nom')
            ->andWhere(self::NOT_DELETED_CONDITION)
            ->setParameter('nom', $nom)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Crée une nouvelle catégorie de billet
     */
    public function create(ConfigurationCategorieBillet $categorie): ConfigurationCategorieBillet
    {
        $this->getEntityManager()->persist($categorie);
        $this->getEntityManager()->flush();

        return $categorie;
    }

    /**
     * Met à jour une catégorie de billet
     */
    public function update(ConfigurationCategorieBillet $categorie): ConfigurationCategorieBillet
    {
        $this->getEntityManager()->flush();

        return $categorie;
    }

    /**
     * Supprime définitivement une catégorie de billet
     */
    public function delete(ConfigurationCategorieBillet $categorie): void
    {
        $this->getEntityManager()->remove($categorie);
        $this->getEntityManager()->flush();
    }

    /**
     * Suppression logique (soft delete) d'une catégorie de billet
     */
    public function softDelete(ConfigurationCategorieBillet $categorie): void
    {
        $categorie->softDelete();
        $this->getEntityManager()->flush();
    }

    /**
     * Restaure une catégorie de billet supprimée logiquement
     */
    public function restore(ConfigurationCategorieBillet $categorie): void
    {
        $categorie->restore();
        $this->getEntityManager()->flush();
    }
}

