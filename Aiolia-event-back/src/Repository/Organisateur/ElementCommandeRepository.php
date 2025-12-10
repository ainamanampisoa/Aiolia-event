<?php

namespace App\Repository\Organisateur;

use App\Entity\Commande;
use App\Entity\ElementCommande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class ElementCommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ElementCommande::class);
    }

    
    public function getAll(): array
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.creeLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    
    public function getById(string $id): ?ElementCommande
    {
        return $this->find($id);
    }

    
    public function findByCommande(Commande $commande): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.commande = :commande')
            ->setParameter('commande', $commande)
            ->orderBy('e.creeLe', 'ASC')
            ->getQuery()
            ->getResult();
    }

    
    public function create(ElementCommande $element): ElementCommande
    {
        $this->getEntityManager()->persist($element);
        $this->getEntityManager()->flush();

        return $element;
    }

    
    public function update(ElementCommande $element): ElementCommande
    {
        $this->getEntityManager()->flush();

        return $element;
    }

    
    public function delete(ElementCommande $element): void
    {
        $this->getEntityManager()->remove($element);
        $this->getEntityManager()->flush();
    }
}

