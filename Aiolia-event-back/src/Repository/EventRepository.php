<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\User;
use App\Entity\EventCategory;
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
     * Récupère tous les événements publiés à venir
     */
    public function findUpcomingEvents(int $limit = 0): array
    {
        $qb = $this->createQueryBuilder('e')
            ->andWhere('e.status = :status')
            ->andWhere('e.startDate > :now')
            ->setParameter('status', 'published')
            ->setParameter('now', new \DateTime())
            ->orderBy('e.startDate', 'ASC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les événements en vedette
     */
    public function findFeaturedEvents(int $limit = 6): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.status = :status')
            ->andWhere('e.isFeatured = :featured')
            ->andWhere('e.startDate > :now')
            ->setParameter('status', 'published')
            ->setParameter('featured', true)
            ->setParameter('now', new \DateTime())
            ->orderBy('e.startDate', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche d'événements par mot-clé
     */
    public function searchEvents(string $query, array $filters = []): array
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.category', 'c')
            ->andWhere('e.status = :status')
            ->setParameter('status', 'published');

        // Recherche textuelle
        if (!empty($query)) {
            $qb->andWhere('e.title LIKE :query OR e.description LIKE :query OR e.location LIKE :query')
                ->setParameter('query', '%' . $query . '%');
        }

        // Filtre par catégorie
        if (!empty($filters['category'])) {
            $qb->andWhere('c.id = :category')
                ->setParameter('category', $filters['category']);
        }

        // Filtre par date
        if (!empty($filters['startDate'])) {
            $qb->andWhere('e.startDate >= :startDate')
                ->setParameter('startDate', $filters['startDate']);
        }

        if (!empty($filters['endDate'])) {
            $qb->andWhere('e.endDate <= :endDate')
                ->setParameter('endDate', $filters['endDate']);
        }

        // Filtre par localisation
        if (!empty($filters['location'])) {
            $qb->andWhere('e.location LIKE :location')
                ->setParameter('location', '%' . $filters['location'] . '%');
        }

        return $qb->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les événements d'un organisateur
     */
    public function findByOrganizer(User $organizer): array
    {
        // Utilisation d'une requête SQL native car organizer_profile_id référence organizer_profiles
        // qui a un user_id référençant users
        $conn = $this->getEntityManager()->getConnection();
        $sql = '
            SELECT e.id FROM aiolia.events e
            INNER JOIN aiolia.organizer_profiles op ON e.organizer_profile_id = op.id
            WHERE op.user_id = :userId
            ORDER BY e.created_at DESC
        ';
        
        $result = $conn->executeQuery($sql, ['userId' => $organizer->getId()]);
        $eventIds = $result->fetchFirstColumn();
        
        // Convertir les IDs en entités Event et charger l'organizer
        $events = [];
        foreach ($eventIds as $eventId) {
            $event = $this->find($eventId);
            if ($event) {
                // Définir l'organizer dans l'entité pour éviter les chargements supplémentaires
                $event->setOrganizer($organizer);
                $events[] = $event;
            }
        }
        
        return $events;
    }

    /**
     * Récupère les événements par catégorie
     */
    public function findByCategory(EventCategory $category, int $limit = 0): array
    {
        $qb = $this->createQueryBuilder('e')
            ->andWhere('e.category = :category')
            ->andWhere('e.status = :status')
            ->andWhere('e.startDate > :now')
            ->setParameter('category', $category)
            ->setParameter('status', 'published')
            ->setParameter('now', new \DateTime())
            ->orderBy('e.startDate', 'ASC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte les événements par statut
     */
    public function countByStatus(string $status): int
    {
        return $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les statistiques d'un événement
     */
    public function getEventStatistics(Event $event): array
    {
        // Cette méthode sera complétée plus tard avec les statistiques réelles
        return [
            'tickets_sold' => 0,
            'revenue' => 0,
            'views' => 0,
        ];
    }
}

