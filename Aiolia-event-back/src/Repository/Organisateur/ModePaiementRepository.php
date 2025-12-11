<?php

namespace App\Repository\Organisateur;

use App\Entity\ModePaiement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ModePaiementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ModePaiement::class);
    }

    /**
     * Récupère tous les modes de paiement actifs, triés par ordre d'affichage.
     */
    public function getAllActive(): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.estActif = :actif')
            ->setParameter('actif', true)
            ->orderBy('m.ordreAffichage', 'ASC')
            ->addOrderBy('m.libelle', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère un mode de paiement par son code.
     */
    public function findByCode(string $code): ?ModePaiement
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

