<?php

namespace App\Repository;

use App\Entity\EventCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EventCategory>
 */
class EventCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventCategory::class);
    }

    /**
     * Récupère toutes les catégories actives triées par ordre d'affichage
     */
    public function findActiveCategories(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('c.displayOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche une catégorie par slug
     */
    public function findBySlug(string $slug): ?EventCategory
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

