<?php

namespace App\Repository\Organisateur;

use App\Entity\SubscriptionPlan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PaiementAbonnementRepository extends ServiceEntityRepository
{
    private const ACTIVE_CONDITION = 'p.estActif = :actif';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubscriptionPlan::class);
    }

    /**
     * Récupère tous les plans d'abonnement actifs
     *
     * @return SubscriptionPlan[]
     */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('p')
            ->where(self::ACTIVE_CONDITION)
            ->setParameter('actif', true)
            ->orderBy('p.ordreAffichage', 'ASC')
            ->addOrderBy('p.niveau', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les plans d'abonnement par niveau
     *
     * @param string $niveau Le niveau (basic, pro, enterprise)
     * @return SubscriptionPlan[]
     */
    public function findByNiveau(string $niveau): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.niveau = :niveau')
            ->andWhere(self::ACTIVE_CONDITION)
            ->setParameter('niveau', $niveau)
            ->setParameter('actif', true)
            ->orderBy('p.ordreAffichage', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les niveaux distincts disponibles
     *
     * @return string[]
     */
    public function findDistinctNiveaux(): array
    {
        $result = $this->createQueryBuilder('p')
            ->select('DISTINCT p.niveau')
            ->where(self::ACTIVE_CONDITION)
            ->setParameter('actif', true)
            ->orderBy('p.niveau', 'ASC')
            ->getQuery()
            ->getResult();

        return array_column($result, 'niveau');
    }

    /**
     * Trouve un plan par niveau et période de facturation
     *
     * @param string $niveau Le niveau (basic, pro, enterprise)
     * @param string $periode La période (monthly, quarterly, yearly)
     * @return SubscriptionPlan|null
     */
    public function findByNiveauAndPeriode(string $niveau, string $periode): ?SubscriptionPlan
    {
        return $this->createQueryBuilder('p')
            ->where('p.niveau = :niveau')
            ->andWhere('p.periodeFacturation = :periode')
            ->andWhere(self::ACTIVE_CONDITION)
            ->setParameter('niveau', $niveau)
            ->setParameter('periode', $periode)
            ->setParameter('actif', true)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
