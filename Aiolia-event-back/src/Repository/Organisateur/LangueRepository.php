<?php

namespace App\Repository\Organisateur;

use App\Entity\Langue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Langue>
 */
class LangueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Langue::class);
    }

    /**
     * Récupère toutes les langues
     */
    public function getAll(): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.estActif = :actif')
            ->setParameter('actif', true)
            ->orderBy('l.libelle', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère une langue par son ID
     */
    public function getById(string $id): ?Langue
    {
        return $this->find($id);
    }

    /**
     * Récupère une langue par son code
     */
    public function findByCode(string $code): ?Langue
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Crée une nouvelle langue
     */
    public function create(Langue $langue): Langue
    {
        $this->getEntityManager()->persist($langue);
        $this->getEntityManager()->flush();

        return $langue;
    }

    /**
     * Met à jour une langue
     */
    public function update(Langue $langue): Langue
    {
        $this->getEntityManager()->flush();

        return $langue;
    }

    /**
     * Supprime une langue
     */
    public function delete(Langue $langue): void
    {
        $this->getEntityManager()->remove($langue);
        $this->getEntityManager()->flush();
    }
}

