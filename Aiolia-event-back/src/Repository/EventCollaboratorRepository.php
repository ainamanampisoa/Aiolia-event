<?php

namespace App\Repository;

use App\Entity\EventCollaborator;
use App\Entity\Event;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EventCollaborator>
 */
class EventCollaboratorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventCollaborator::class);
    }

    /**
     * Récupère tous les collaborateurs d'un événement
     */
    public function findByEvent(Event $event, bool $activeOnly = true): array
    {
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.event = :event')
            ->setParameter('event', $event);

        if ($activeOnly) {
            $qb->andWhere('c.isActive = :active')
                ->setParameter('active', true);
        }

        return $qb->orderBy('c.invitedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les événements d'un collaborateur
     */
    public function findEventsByCollaborator(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :user')
            ->andWhere('c.isActive = :active')
            ->setParameter('user', $user)
            ->setParameter('active', true)
            ->orderBy('c.invitedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si un utilisateur est collaborateur d'un événement
     */
    public function isCollaborator(Event $event, User $user): bool
    {
        $result = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.event = :event')
            ->andWhere('c.user = :user')
            ->andWhere('c.isActive = :active')
            ->setParameter('event', $event)
            ->setParameter('user', $user)
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();

        return $result > 0;
    }

    /**
     * Récupère un collaborateur spécifique
     */
    public function findCollaborator(Event $event, User $user): ?EventCollaborator
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.event = :event')
            ->andWhere('c.user = :user')
            ->setParameter('event', $event)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

