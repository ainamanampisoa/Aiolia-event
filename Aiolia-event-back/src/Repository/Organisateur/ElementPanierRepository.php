<?php

namespace App\Repository\Organisateur;

use App\Entity\ElementPanier;
use App\Entity\Panier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ElementPanier>
 */
class ElementPanierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ElementPanier::class);
    }

    /**
     * Récupère tous les éléments de paniers
     */
    public function getAll(): array
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.creeLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère un élément de panier par son ID
     */
    public function getById(string $id): ?ElementPanier
    {
        return $this->find($id);
    }

    /**
     * Récupère tous les éléments d'un panier
     */
    public function findByPanier(Panier $panier): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.panier = :panier')
            ->setParameter('panier', $panier)
            ->orderBy('e.creeLe', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Crée un nouvel élément de panier
     */
    public function create(ElementPanier $element): ElementPanier
    {
        $this->getEntityManager()->persist($element);
        $this->getEntityManager()->flush();

        return $element;
    }

    /**
     * Met à jour un élément de panier
     */
    public function update(ElementPanier $element): ElementPanier
    {
        $this->getEntityManager()->flush();

        return $element;
    }

    /**
     * Supprime un élément de panier
     */
    public function delete(ElementPanier $element): void
    {
        $this->getEntityManager()->remove($element);
        $this->getEntityManager()->flush();
    }
}

