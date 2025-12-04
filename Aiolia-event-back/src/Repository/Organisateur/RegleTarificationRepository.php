<?php

namespace App\Repository\Organisateur;

use App\Entity\RegleTarification;
use App\Entity\TypeBillet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RegleTarification>
 */
class RegleTarificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RegleTarification::class);
    }

    /**
     * Récupère toutes les règles de tarification
     */
    public function getAll(): array
    {
        return $this->createQueryBuilder('r')
            ->orderBy('r.creeLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère une règle de tarification par son ID
     */
    public function getById(string $id): ?RegleTarification
    {
        return $this->find($id);
    }

    /**
     * Récupère toutes les règles de tarification d'un type de billet
     */
    public function findByTypeBillet(TypeBillet $typeBillet): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.typeBillet = :typeBillet')
            ->setParameter('typeBillet', $typeBillet)
            ->orderBy('r.creeLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère toutes les règles de tarification actives d'un type de billet
     */
    public function findActiveByTypeBillet(TypeBillet $typeBillet): array
    {
        $now = new \DateTimeImmutable();
        
        return $this->createQueryBuilder('r')
            ->andWhere('r.typeBillet = :typeBillet')
            ->andWhere('(r.commenceLe IS NULL OR r.commenceLe <= :now)')
            ->andWhere('(r.seTermineLe IS NULL OR r.seTermineLe >= :now)')
            ->setParameter('typeBillet', $typeBillet)
            ->setParameter('now', $now)
            ->orderBy('r.creeLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère toutes les règles de tarification actives
     */
    public function findActive(): array
    {
        $now = new \DateTimeImmutable();
        
        return $this->createQueryBuilder('r')
            ->andWhere('(r.commenceLe IS NULL OR r.commenceLe <= :now)')
            ->andWhere('(r.seTermineLe IS NULL OR r.seTermineLe >= :now)')
            ->setParameter('now', $now)
            ->orderBy('r.creeLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Crée une nouvelle règle de tarification
     */
    public function create(RegleTarification $regle): RegleTarification
    {
        $this->getEntityManager()->persist($regle);
        $this->getEntityManager()->flush();

        return $regle;
    }

    /**
     * Met à jour une règle de tarification
     */
    public function update(RegleTarification $regle): RegleTarification
    {
        $this->getEntityManager()->flush();

        return $regle;
    }

    /**
     * Supprime une règle de tarification
     */
    public function delete(RegleTarification $regle): void
    {
        $this->getEntityManager()->remove($regle);
        $this->getEntityManager()->flush();
    }
}

