<?php

namespace App\Repository;

use App\Entity\TicketCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TicketCategory>
 */
class TicketCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TicketCategory::class);
    }

    /**
     * @return TicketCategory[] Returns an array of TicketCategory objects for an event
     */
    public function findByEvent(int $eventId): array
    {
        return $this->createQueryBuilder('tc')
            ->andWhere('tc.event = :event')
            ->andWhere('tc.isActive = :active')
            ->setParameter('event', $eventId)
            ->setParameter('active', true)
            ->orderBy('tc.price', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return TicketCategory[] Returns an array of available TicketCategory objects for an event
     */
    public function findAvailableByEvent(int $eventId): array
    {
        return $this->createQueryBuilder('tc')
            ->andWhere('tc.event = :event')
            ->andWhere('tc.isActive = :active')
            ->andWhere('tc.soldQuantity < tc.quantity')
            ->setParameter('event', $eventId)
            ->setParameter('active', true)
            ->orderBy('tc.price', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

