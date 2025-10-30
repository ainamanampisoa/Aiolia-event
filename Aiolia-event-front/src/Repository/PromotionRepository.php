<?php

namespace App\Repository;

use App\Entity\Promotion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Promotion>
 */
class PromotionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Promotion::class);
    }

    public function findByCode(string $code): ?Promotion
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.code = :code')
            ->setParameter('code', strtoupper($code))
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Promotion[] Returns an array of active Promotion objects for an event
     */
    public function findActiveByEvent(int $eventId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.event = :event')
            ->andWhere('p.isActive = :active')
            ->andWhere('p.validFrom <= :now')
            ->andWhere('p.validUntil >= :now')
            ->setParameter('event', $eventId)
            ->setParameter('active', true)
            ->setParameter('now', new \DateTime())
            ->orderBy('p.discountPercentage', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Promotion[] Returns an array of Promotion objects for an event
     */
    public function findByEvent(int $eventId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.event = :event')
            ->setParameter('event', $eventId)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find valid promotion by code and event
     */
    public function findValidPromotionByCodeAndEvent(string $code, int $eventId): ?Promotion
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.code = :code')
            ->andWhere('p.event = :event')
            ->andWhere('p.isActive = :active')
            ->andWhere('p.validFrom <= :now')
            ->andWhere('p.validUntil >= :now')
            ->andWhere('(p.usageLimit = 0 OR p.usedCount < p.usageLimit)')
            ->setParameter('code', strtoupper($code))
            ->setParameter('event', $eventId)
            ->setParameter('active', true)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getOneOrNullResult();
    }
}

