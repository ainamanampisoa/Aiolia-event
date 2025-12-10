<?php

namespace App\Repository\Organisateur;

use App\Entity\EspaceLieu;
use App\Entity\Venue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class EspaceLieuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EspaceLieu::class);
    }

    
    public function getAll(): array
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    
    public function getById(string $id): ?EspaceLieu
    {
        return $this->find($id);
    }

    
    public function findByLieu(Venue $lieu): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.lieu = :lieu')
            ->setParameter('lieu', $lieu)
            ->orderBy('e.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    
    public function findDefaultByLieu(Venue $lieu): ?EspaceLieu
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.lieu = :lieu')
            ->andWhere('e.estParDefaut = :defaut')
            ->setParameter('lieu', $lieu)
            ->setParameter('defaut', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    
    public function create(EspaceLieu $espaceLieu): EspaceLieu
    {
        $this->getEntityManager()->persist($espaceLieu);
        $this->getEntityManager()->flush();

        return $espaceLieu;
    }

    
    public function update(EspaceLieu $espaceLieu): EspaceLieu
    {
        $this->getEntityManager()->flush();

        return $espaceLieu;
    }

    
    public function delete(EspaceLieu $espaceLieu): void
    {
        $this->getEntityManager()->remove($espaceLieu);
        $this->getEntityManager()->flush();
    }
}

