<?php

namespace App\Repository\Organisateur;

use App\Entity\SessionEvenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SessionEvenement>
 */
class SessionEvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SessionEvenement::class);
    }

    /**
     * Récupère toutes les sessions d'événements
     */
    public function getAll(): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.commenceLe', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère une session d'événement par son ID
     */
    public function getById(string $id): ?SessionEvenement
    {
        return $this->find($id);
    }

    /**
     * Crée une nouvelle session d'événement
     */
    public function create(SessionEvenement $session): SessionEvenement
    {
        $this->getEntityManager()->persist($session);
        $this->getEntityManager()->flush();

        return $session;
    }

    /**
     * Met à jour une session d'événement
     */
    public function update(SessionEvenement $session): SessionEvenement
    {
        $this->getEntityManager()->flush();

        return $session;
    }

    /**
     * Supprime une session d'événement
     */
    public function delete(SessionEvenement $session): void
    {
        $this->getEntityManager()->remove($session);
        $this->getEntityManager()->flush();
    }
}

