<?php

namespace App\Repository\Organisateur;

use App\Entity\EventMedia;
use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class EventMediaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventMedia::class);
    }

    
    public function findByEvent(Event $event): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.event = :event')
            ->setParameter('event', $event)
            ->orderBy('m.displayOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    
    public function findPrimaryImage(Event $event): ?EventMedia
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.event = :event')
            ->andWhere('m.mediaType = :type')
            ->andWhere('m.isPrimary = :primary')
            ->setParameter('event', $event)
            ->setParameter('type', 'image')
            ->setParameter('primary', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    
    public function findByEventAndType(Event $event, string $type): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.event = :event')
            ->andWhere('m.mediaType = :type')
            ->setParameter('event', $event)
            ->setParameter('type', $type)
            ->orderBy('m.displayOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

