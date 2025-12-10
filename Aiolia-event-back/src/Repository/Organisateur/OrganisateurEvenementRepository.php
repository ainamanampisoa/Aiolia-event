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
}

