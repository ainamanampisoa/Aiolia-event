<?php

namespace App\Repository\Organisateur;

use App\Entity\EventType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EventType>
 */
class EventTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventType::class);
    }

    /**
     * Récupère tous les types d'événements
     */
    public function getAll(): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.label', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère un type d'événement par son ID
     */
    public function getById(string $id): ?EventType
    {
        return $this->find($id);
    }

    /**
     * Récupère un type d'événement par son slug
     */
    public function findBySlug(string $slug): ?EventType
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Crée un nouveau type d'événement
     */
    public function create(EventType $eventType): EventType
    {
        $this->getEntityManager()->persist($eventType);
        $this->getEntityManager()->flush();

        return $eventType;
    }

    /**
     * Met à jour un type d'événement
     */
    public function update(EventType $eventType): EventType
    {
        $this->getEntityManager()->flush();

        return $eventType;
    }

    /**
     * Supprime un type d'événement
     */
    public function delete(EventType $eventType): void
    {
        $this->getEntityManager()->remove($eventType);
        $this->getEntityManager()->flush();
    }
}

