<?php

namespace App\Repository\Organisateur;

use App\Entity\Event;
use App\Entity\Langue;
use App\Entity\LienLangueEvenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class LienLangueEvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LienLangueEvenement::class);
    }

    
    public function getAll(): array
    {
        return $this->createQueryBuilder('l')
            ->orderBy('l.creeLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    
    public function getById(Event $evenement, Langue $langue): ?LienLangueEvenement
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.evenement = :evenement')
            ->andWhere('l.langue = :langue')
            ->setParameter('evenement', $evenement)
            ->setParameter('langue', $langue)
            ->getQuery()
            ->getOneOrNullResult();
    }

    
    public function findByEvenement(Event $evenement): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.evenement = :evenement')
            ->setParameter('evenement', $evenement)
            ->orderBy('l.creeLe', 'ASC')
            ->getQuery()
            ->getResult();
    }

    
    public function create(LienLangueEvenement $lien): LienLangueEvenement
    {
        $this->getEntityManager()->persist($lien);
        $this->getEntityManager()->flush();

        return $lien;
    }

    
    public function update(LienLangueEvenement $lien): LienLangueEvenement
    {
        $this->getEntityManager()->flush();

        return $lien;
    }

    
    public function delete(LienLangueEvenement $lien): void
    {
        $this->getEntityManager()->remove($lien);
        $this->getEntityManager()->flush();
    }
}

