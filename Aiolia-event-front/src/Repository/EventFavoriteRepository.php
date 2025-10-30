<?php

namespace App\Repository;

use App\Entity\EventFavorite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EventFavorite>
 */
class EventFavoriteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventFavorite::class);
    }

    /**
     * @return EventFavorite[] Returns an array of EventFavorite objects for a user
     */
    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('ef')
            ->join('ef.event', 'e')
            ->andWhere('ef.user = :user')
            ->andWhere('e.isPublished = :published')
            ->andWhere('e.isActive = :active')
            ->setParameter('user', $userId)
            ->setParameter('published', true)
            ->setParameter('active', true)
            ->orderBy('ef.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByUserAndEvent(int $userId, int $eventId): ?EventFavorite
    {
        return $this->createQueryBuilder('ef')
            ->andWhere('ef.user = :user')
            ->andWhere('ef.event = :event')
            ->setParameter('user', $userId)
            ->setParameter('event', $eventId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Count favorites for an event
     */
    public function countFavoritesForEvent(int $eventId): int
    {
        return $this->createQueryBuilder('ef')
            ->select('COUNT(ef.id)')
            ->andWhere('ef.event = :event')
            ->setParameter('event', $eventId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}

