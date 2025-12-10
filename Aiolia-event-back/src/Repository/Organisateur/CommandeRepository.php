<?php

namespace App\Repository\Organisateur;

use App\Entity\Commande;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class CommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }

    
    public function getAll(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.creeLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    
    public function getById(string $id): ?Commande
    {
        return $this->find($id);
    }

    
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.utilisateur = :user')
            ->setParameter('user', $user)
            ->orderBy('c.creeLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    
    public function create(Commande $commande): Commande
    {
        $this->getEntityManager()->persist($commande);
        $this->getEntityManager()->flush();

        return $commande;
    }

    
    public function update(Commande $commande): Commande
    {
        $this->getEntityManager()->flush();

        return $commande;
    }

    
    public function delete(Commande $commande): void
    {
        $this->getEntityManager()->remove($commande);
        $this->getEntityManager()->flush();
    }
}

