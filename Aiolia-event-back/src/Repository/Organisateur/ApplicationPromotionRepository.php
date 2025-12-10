<?php

namespace App\Repository\Organisateur;

use App\Entity\ApplicationPromotion;
use App\Entity\CodePromotionnel;
use App\Entity\Commande;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class ApplicationPromotionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApplicationPromotion::class);
    }

    
    public function getAll(): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.appliqueLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    
    public function getById(string $id): ?ApplicationPromotion
    {
        return $this->find($id);
    }

    
    public function findByPromotion(CodePromotionnel $promotion): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.promotion = :promotion')
            ->setParameter('promotion', $promotion)
            ->orderBy('a.appliqueLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    
    public function findByCommande(Commande $commande): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.commande = :commande')
            ->setParameter('commande', $commande)
            ->orderBy('a.appliqueLe', 'ASC')
            ->getQuery()
            ->getResult();
    }

    
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.utilisateur = :user')
            ->setParameter('user', $user)
            ->orderBy('a.appliqueLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    
    public function isPromotionAppliedToCommande(CodePromotionnel $promotion, Commande $commande): bool
    {
        $result = $this->createQueryBuilder('a')
            ->andWhere('a.promotion = :promotion')
            ->andWhere('a.commande = :commande')
            ->setParameter('promotion', $promotion)
            ->setParameter('commande', $commande)
            ->getQuery()
            ->getOneOrNullResult();

        return $result !== null;
    }

    
    public function create(ApplicationPromotion $application): ApplicationPromotion
    {
        $this->getEntityManager()->persist($application);
        $this->getEntityManager()->flush();

        return $application;
    }

    
    public function update(ApplicationPromotion $application): ApplicationPromotion
    {
        $this->getEntityManager()->flush();

        return $application;
    }

    
    public function delete(ApplicationPromotion $application): void
    {
        $this->getEntityManager()->remove($application);
        $this->getEntityManager()->flush();
    }
}

