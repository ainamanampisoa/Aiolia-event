<?php

namespace App\Repository\Organisateur;

use App\Entity\CodePromotionnel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CodePromotionnel>
 */
class CodePromotionnelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CodePromotionnel::class);
    }

    /**
     * Récupère tous les codes promotionnels
     */
    public function getAll(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.creeLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère un code promotionnel par son ID
     */
    public function getById(string $id): ?CodePromotionnel
    {
        return $this->find($id);
    }

    /**
     * Récupère un code promotionnel par son code
     */
    public function findByCode(string $code): ?CodePromotionnel
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère tous les codes promotionnels actifs
     */
    public function findActive(): array
    {
        $now = new \DateTimeImmutable();
        
        return $this->createQueryBuilder('c')
            ->andWhere('(c.commenceLe IS NULL OR c.commenceLe <= :now)')
            ->andWhere('(c.seTermineLe IS NULL OR c.seTermineLe >= :now)')
            ->setParameter('now', $now)
            ->orderBy('c.creeLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Crée un nouveau code promotionnel
     */
    public function create(CodePromotionnel $codePromotionnel): CodePromotionnel
    {
        $this->getEntityManager()->persist($codePromotionnel);
        $this->getEntityManager()->flush();

        return $codePromotionnel;
    }

    /**
     * Met à jour un code promotionnel
     */
    public function update(CodePromotionnel $codePromotionnel): CodePromotionnel
    {
        $this->getEntityManager()->flush();

        return $codePromotionnel;
    }

    /**
     * Supprime un code promotionnel
     */
    public function delete(CodePromotionnel $codePromotionnel): void
    {
        $this->getEntityManager()->remove($codePromotionnel);
        $this->getEntityManager()->flush();
    }
}

