<?php

namespace App\Repository\Organisateur;

use App\Entity\Commande;
use App\Entity\ElementCommande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ElementCommande>
 */
class ElementCommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ElementCommande::class);
    }

    /**
     * Récupère tous les éléments de commandes
     */
    public function getAll(): array
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.creeLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère un élément de commande par son ID
     */
    public function getById(string $id): ?ElementCommande
    {
        return $this->find($id);
    }

    /**
     * Récupère tous les éléments d'une commande
     */
    public function findByCommande(Commande $commande): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.commande = :commande')
            ->setParameter('commande', $commande)
            ->orderBy('e.creeLe', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Crée un nouvel élément de commande
     */
    public function create(ElementCommande $element): ElementCommande
    {
        $this->getEntityManager()->persist($element);
        $this->getEntityManager()->flush();

        return $element;
    }

    /**
     * Met à jour un élément de commande
     */
    public function update(ElementCommande $element): ElementCommande
    {
        $this->getEntityManager()->flush();

        return $element;
    }

    /**
     * Supprime un élément de commande
     */
    public function delete(ElementCommande $element): void
    {
        $this->getEntityManager()->remove($element);
        $this->getEntityManager()->flush();
    }
}

