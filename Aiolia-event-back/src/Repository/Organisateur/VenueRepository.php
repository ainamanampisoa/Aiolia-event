<?php

namespace App\Repository\Organisateur;

use App\Entity\Venue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class VenueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Venue::class);
    }

    
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('v')
            ->where('v.estActif = :actif')
            ->setParameter('actif', true)
            ->orderBy('v.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    
    public function findAll(): array
    {
        return $this->createQueryBuilder('v')
            ->orderBy('v.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    
    public function getById(string $id): ?Venue
    {
        return $this->find($id);
    }
}


