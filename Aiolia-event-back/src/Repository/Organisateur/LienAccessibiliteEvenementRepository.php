<?php

namespace App\Repository\Organisateur;

use App\Entity\Event;
use App\Entity\LienAccessibiliteEvenement;
use App\Entity\TypeAccessibilite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class LienAccessibiliteEvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LienAccessibiliteEvenement::class);
    }

    
    public function getAll(): array
    {
        return $this->createQueryBuilder('l')
            ->orderBy('l.creeLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    
    public function getById(Event $evenement, TypeAccessibilite $typeAccessibilite): ?LienAccessibiliteEvenement
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.evenement = :evenement')
            ->andWhere('l.typeAccessibilite = :typeAccessibilite')
            ->setParameter('evenement', $evenement)
            ->setParameter('typeAccessibilite', $typeAccessibilite)
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

    
    public function create(LienAccessibiliteEvenement $lien): LienAccessibiliteEvenement
    {
        $this->getEntityManager()->persist($lien);
        $this->getEntityManager()->flush();

        return $lien;
    }

    
    public function update(LienAccessibiliteEvenement $lien): LienAccessibiliteEvenement
    {
        $this->getEntityManager()->flush();

        return $lien;
    }

    
    public function delete(LienAccessibiliteEvenement $lien): void
    {
        $this->getEntityManager()->remove($lien);
        $this->getEntityManager()->flush();
    }
}

