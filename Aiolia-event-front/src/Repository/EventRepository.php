<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * @return Event[] Returns an array of Event objects
     */
    public function findPublishedEvents(): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.isPublished = :published')
            ->andWhere('e.isActive = :active')
            ->setParameter('published', true)
            ->setParameter('active', true)
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Event[] Returns an array of upcoming Event objects
     */
    public function findUpcomingEvents(): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.isPublished = :published')
            ->andWhere('e.isActive = :active')
            ->andWhere('e.startDate > :now')
            ->setParameter('published', true)
            ->setParameter('active', true)
            ->setParameter('now', new \DateTime())
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Event[] Returns an array of Event objects by category
     */
    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.category = :category')
            ->andWhere('e.isPublished = :published')
            ->andWhere('e.isActive = :active')
            ->setParameter('category', $category)
            ->setParameter('published', true)
            ->setParameter('active', true)
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Event[] Returns an array of Event objects by city
     */
    public function findByCity(string $city): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.city = :city')
            ->andWhere('e.isPublished = :published')
            ->andWhere('e.isActive = :active')
            ->setParameter('city', $city)
            ->setParameter('published', true)
            ->setParameter('active', true)
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Event[] Returns an array of Event objects by organizer
     */
    public function findByOrganizer(int $organizerId): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organizer = :organizer')
            ->setParameter('organizer', $organizerId)
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search events by title or description
     * @return Event[] Returns an array of Event objects
     */
    public function searchEvents(string $searchTerm): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.isPublished = :published')
            ->andWhere('e.isActive = :active')
            ->andWhere('(e.title LIKE :search OR e.description LIKE :search OR e.shortDescription LIKE :search)')
            ->setParameter('published', true)
            ->setParameter('active', true)
            ->setParameter('search', '%' . $searchTerm . '%')
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find events with available tickets
     * @return Event[] Returns an array of Event objects
     */
    public function findEventsWithAvailableTickets(): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.isPublished = :published')
            ->andWhere('e.isActive = :active')
            ->andWhere('e.currentBookings < e.maxCapacity')
            ->setParameter('published', true)
            ->setParameter('active', true)
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find events by date range
     * @return Event[] Returns an array of Event objects
     */
    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.isPublished = :published')
            ->andWhere('e.isActive = :active')
            ->andWhere('e.startDate >= :startDate')
            ->andWhere('e.startDate <= :endDate')
            ->setParameter('published', true)
            ->setParameter('active', true)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

