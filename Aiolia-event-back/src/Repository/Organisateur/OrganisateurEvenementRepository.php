<?php

namespace App\Repository\Organisateur;

use App\Entity\OrganisateurEvenement;
use App\Entity\Event;
use App\Entity\OrganizerProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class OrganisateurEvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrganisateurEvenement::class);
    }

    
    public function findByEvent(Event $event): array
    {
        return $this->createQueryBuilder('oe')
            ->andWhere('oe.evenement = :event')
            ->setParameter('event', $event)
            ->orderBy('oe.creeLe', 'ASC')
            ->getQuery()
            ->getResult();
    }

    
    public function findByOrganizer(OrganizerProfile $organizer): array
    {
        return $this->createQueryBuilder('oe')
            ->andWhere('oe.profilOrganisateur = :organizer')
            ->setParameter('organizer', $organizer)
            ->orderBy('oe.creeLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    
    public function findOneByEventAndOrganizer(Event $event, OrganizerProfile $organizer): ?OrganisateurEvenement
    {
        return $this->createQueryBuilder('oe')
            ->andWhere('oe.evenement = :event')
            ->andWhere('oe.profilOrganisateur = :organizer')
            ->setParameter('event', $event)
            ->setParameter('organizer', $organizer)
            ->getQuery()
            ->getOneOrNullResult();
    }

    
    public function create(OrganisateurEvenement $organisateurEvenement): OrganisateurEvenement
    {
        $this->getEntityManager()->persist($organisateurEvenement);
        $this->getEntityManager()->flush();

        return $organisateurEvenement;
    }

    
    public function update(OrganisateurEvenement $organisateurEvenement): OrganisateurEvenement
    {
        $this->getEntityManager()->flush();

        return $organisateurEvenement;
    }

    
    public function delete(OrganisateurEvenement $organisateurEvenement): void
    {
        $this->getEntityManager()->remove($organisateurEvenement);
        $this->getEntityManager()->flush();
    }
  /**
     * Trouve tous les OrganisateurEvenement pour un utilisateur via son profil organisateur
     */
    public function findEventsByUserId(int $userId): array
    {
        return $this->createQueryBuilder('oe')
            ->innerJoin('oe.profilOrganisateur', 'po')
            ->innerJoin('po.utilisateur', 'u')
            ->where('u.id = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();
    }

    /**
     * Événements récents par utilisateur
     */
    public function findRecentEventsByUserId(int $userId, int $limit = 5): array
    {
        $qb = $this->createQueryBuilder('oe')
            ->select('oe, e')
            ->innerJoin('oe.profilOrganisateur', 'po')
            ->innerJoin('po.utilisateur', 'u')
            ->innerJoin('oe.evenement', 'e')
            ->where('u.id = :userId')
            ->orderBy('e.creeLe', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('userId', $userId);

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte les événements publiés par utilisateur
     */
    public function countPublishedEventsByUserId(int $userId): int
    {
        return $this->createQueryBuilder('oe')
            ->select('COUNT(DISTINCT oe.evenement)')
            ->innerJoin('oe.profilOrganisateur', 'po')
            ->innerJoin('po.utilisateur', 'u')
            ->innerJoin('oe.evenement', 'e')
            ->where('u.id = :userId')
            ->andWhere('e.statut = :statut')
            ->setParameter('userId', $userId)
            ->setParameter('statut', 'published')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les brouillons par utilisateur
     */
    public function countDraftEventsByUserId(int $userId): int
    {
        return $this->createQueryBuilder('oe')
            ->select('COUNT(DISTINCT oe.evenement)')
            ->innerJoin('oe.profilOrganisateur', 'po')
            ->innerJoin('po.utilisateur', 'u')
            ->innerJoin('oe.evenement', 'e')
            ->where('u.id = :userId')
            ->andWhere('e.statut = :statut')
            ->setParameter('userId', $userId)
            ->setParameter('statut', 'draft')
            ->getQuery()
            ->getSingleScalarResult();
    }
}

