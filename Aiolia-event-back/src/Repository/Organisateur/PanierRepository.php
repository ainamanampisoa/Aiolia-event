<?php

namespace App\Repository\Organisateur;

use App\Entity\Panier;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Panier>
 */
class PanierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Panier::class);
    }

    /**
     * Récupère tous les paniers
     */
    public function getAll(): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.creeLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère un panier par son ID
     */
    public function getById(string $id): ?Panier
    {
        return $this->find($id);
    }

    /**
     * Récupère le panier actif d'un utilisateur
     */
    public function findActiveByUser(User $user): ?Panier
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.utilisateur = :user')
            ->andWhere('p.statut = :statut')
            ->setParameter('user', $user)
            ->setParameter('statut', Panier::STATUT_ACTIVE)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère un panier par jeton de session
     */
    public function findByJetonSession(string $jetonSession): ?Panier
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.jetonSession = :jeton')
            ->setParameter('jeton', $jetonSession)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Crée un nouveau panier
     */
    public function create(Panier $panier): Panier
    {
        $this->getEntityManager()->persist($panier);
        $this->getEntityManager()->flush();

        return $panier;
    }

    /**
     * Met à jour un panier
     */
    public function update(Panier $panier): Panier
    {
        $this->getEntityManager()->flush();

        return $panier;
    }

    /**
     * Supprime un panier
     */
    public function delete(Panier $panier): void
    {
        $this->getEntityManager()->remove($panier);
        $this->getEntityManager()->flush();
    }
}

