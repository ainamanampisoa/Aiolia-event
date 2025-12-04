<?php

namespace App\Repository\Organisateur;

use App\Entity\OrganisateurEvenement;
use App\Entity\Event;
use App\Entity\OrganizerProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrganisateurEvenement>
 */
class OrganisateurEvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrganisateurEvenement::class);
    }

    /**
     * Récupère tous les organisateurs d'un événement
     */
    public function findByEvent(Event $event): array
    {
        return $this->createQueryBuilder('oe')
            ->andWhere('oe.evenement = :event')
            ->setParameter('event', $event)
            ->orderBy('oe.creeLe', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les événements d'un organisateur
     */
    public function findByOrganizer(OrganizerProfile $organizer): array
    {
        return $this->createQueryBuilder('oe')
            ->andWhere('oe.profilOrganisateur = :organizer')
            ->setParameter('organizer', $organizer)
            ->orderBy('oe.creeLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère un lien organisateur-événement spécifique
     */
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

    /**
     * Crée un nouveau lien organisateur-événement
     */
    public function create(OrganisateurEvenement $organisateurEvenement): OrganisateurEvenement
    {
        $this->getEntityManager()->persist($organisateurEvenement);
        $this->getEntityManager()->flush();

        return $organisateurEvenement;
    }

    /**
     * Met à jour un lien organisateur-événement
     */
    public function update(OrganisateurEvenement $organisateurEvenement): OrganisateurEvenement
    {
        $this->getEntityManager()->flush();

        return $organisateurEvenement;
    }

    /**
     * Supprime un lien organisateur-événement
     */
    public function delete(OrganisateurEvenement $organisateurEvenement): void
    {
        $this->getEntityManager()->remove($organisateurEvenement);
        $this->getEntityManager()->flush();
    }
}

