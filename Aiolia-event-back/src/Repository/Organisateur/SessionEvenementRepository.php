<?php

namespace App\Repository\Organisateur;

use App\Entity\SessionEvenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class SessionEvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SessionEvenement::class);
    }

    
    public function getAll(): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.commenceLe', 'ASC')
            ->getQuery()
            ->getResult();
    }

    
    public function getById(string $id): ?SessionEvenement
    {
        return $this->find($id);
    }

    
    public function create(SessionEvenement $session): SessionEvenement
    {
        $this->getEntityManager()->persist($session);
        $this->getEntityManager()->flush();

        return $session;
    }

    
    public function update(SessionEvenement $session): SessionEvenement
    {
        $this->getEntityManager()->flush();

        return $session;
    }

    
    public function delete(SessionEvenement $session): void
    {
        $this->getEntityManager()->remove($session);
        $this->getEntityManager()->flush();
    }
}

