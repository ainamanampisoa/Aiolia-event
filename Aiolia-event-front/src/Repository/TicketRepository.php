<?php

namespace App\Repository;

use App\Entity\Ticket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ticket>
 */
class TicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ticket::class);
    }

    /**
     * @return Ticket[] Returns an array of Ticket objects for a user
     */
    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.user = :user')
            ->setParameter('user', $userId)
            ->orderBy('t.purchaseDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Ticket[] Returns an array of upcoming Ticket objects for a user
     */
    public function findUpcomingTicketsByUser(int $userId): array
    {
        return $this->createQueryBuilder('t')
            ->join('t.event', 'e')
            ->andWhere('t.user = :user')
            ->andWhere('e.startDate > :now')
            ->andWhere('t.status = :status')
            ->setParameter('user', $userId)
            ->setParameter('now', new \DateTime())
            ->setParameter('status', 'purchased')
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Ticket[] Returns an array of past Ticket objects for a user
     */
    public function findPastTicketsByUser(int $userId): array
    {
        return $this->createQueryBuilder('t')
            ->join('t.event', 'e')
            ->andWhere('t.user = :user')
            ->andWhere('e.endDate < :now')
            ->setParameter('user', $userId)
            ->setParameter('now', new \DateTime())
            ->orderBy('e.endDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Ticket[] Returns an array of Ticket objects for an event
     */
    public function findByEvent(int $eventId): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.event = :event')
            ->setParameter('event', $eventId)
            ->orderBy('t.purchaseDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Ticket[] Returns an array of Ticket objects by status
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.status = :status')
            ->setParameter('status', $status)
            ->orderBy('t.purchaseDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByQrCode(string $qrCode): ?Ticket
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.qrCode = :qrCode')
            ->setParameter('qrCode', $qrCode)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Count tickets sold for an event
     */
    public function countTicketsSoldForEvent(int $eventId): int
    {
        return $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.event = :event')
            ->andWhere('t.status != :cancelled')
            ->setParameter('event', $eventId)
            ->setParameter('cancelled', 'cancelled')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get total revenue for an event
     */
    public function getTotalRevenueForEvent(int $eventId): float
    {
        $result = $this->createQueryBuilder('t')
            ->select('SUM(t.price)')
            ->andWhere('t.event = :event')
            ->andWhere('t.status != :cancelled')
            ->setParameter('event', $eventId)
            ->setParameter('cancelled', 'cancelled')
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }
}

