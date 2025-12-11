<?php

namespace App\Repository\Organisateur;

use App\Entity\EventType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class EventTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventType::class);
    }

    
    public function getAll(): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.label', 'ASC')
            ->getQuery()
            ->getResult();
    }

    
    public function getById(string $id): ?EventType
    {
        return $this->find($id);
    }

    
    public function findBySlug(string $slug): ?EventType
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    
    public function create(EventType $eventType): EventType
    {
        $this->getEntityManager()->persist($eventType);
        $this->getEntityManager()->flush();

        return $eventType;
    }

    
    public function update(EventType $eventType): EventType
    {
        $this->getEntityManager()->flush();

        return $eventType;
    }

    
    public function delete(EventType $eventType): void
    {
        $this->getEntityManager()->remove($eventType);
        $this->getEntityManager()->flush();
    }
}

