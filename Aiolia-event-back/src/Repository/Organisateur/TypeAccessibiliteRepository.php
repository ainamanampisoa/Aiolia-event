<?php

namespace App\Repository\Organisateur;

use App\Entity\TypeAccessibilite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TypeAccessibilite>
 */
class TypeAccessibiliteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TypeAccessibilite::class);
    }

    /**
     * Récupère tous les types d'accessibilité
     */
    public function getAll(): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.estActif = :actif')
            ->setParameter('actif', true)
            ->orderBy('t.libelle', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère un type d'accessibilité par son ID
     */
    public function getById(string $id): ?TypeAccessibilite
    {
        return $this->find($id);
    }

    /**
     * Récupère un type d'accessibilité par son code
     */
    public function findByCode(string $code): ?TypeAccessibilite
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Crée un nouveau type d'accessibilité
     */
    public function create(TypeAccessibilite $typeAccessibilite): TypeAccessibilite
    {
        $this->getEntityManager()->persist($typeAccessibilite);
        $this->getEntityManager()->flush();

        return $typeAccessibilite;
    }

    /**
     * Met à jour un type d'accessibilité
     */
    public function update(TypeAccessibilite $typeAccessibilite): TypeAccessibilite
    {
        $this->getEntityManager()->flush();

        return $typeAccessibilite;
    }

    /**
     * Supprime un type d'accessibilité
     */
    public function delete(TypeAccessibilite $typeAccessibilite): void
    {
        $this->getEntityManager()->remove($typeAccessibilite);
        $this->getEntityManager()->flush();
    }
}

