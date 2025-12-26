<?php

namespace App\Repository\Organisateur;

use App\Entity\CodePromotionnel;
use App\Entity\OrganizerProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class CodePromotionnelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CodePromotionnel::class);
    }

    
    public function getAll(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.dateSuppression IS NULL')
            ->orderBy('c.creeLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    
    public function getById(string $id): ?CodePromotionnel
    {
        return $this->find($id);
    }

    
    public function findByCode(string $code): ?CodePromotionnel
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.code = :code')
            ->andWhere('c.dateSuppression IS NULL')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }

    
    public function findActive(): array
    {
        $now = new \DateTimeImmutable();
        
        return $this->createQueryBuilder('c')
            ->andWhere('c.dateSuppression IS NULL')
            ->andWhere('(c.commenceLe IS NULL OR c.commenceLe <= :now)')
            ->andWhere('(c.seTermineLe IS NULL OR c.seTermineLe >= :now)')
            ->setParameter('now', $now)
            ->orderBy('c.creeLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    
    public function create(CodePromotionnel $codePromotionnel): CodePromotionnel
    {
        $this->getEntityManager()->persist($codePromotionnel);
        $this->getEntityManager()->flush();

        return $codePromotionnel;
    }

    
    public function update(CodePromotionnel $codePromotionnel): CodePromotionnel
    {
        $this->getEntityManager()->flush();

        return $codePromotionnel;
    }

    
    public function delete(CodePromotionnel $codePromotionnel): void
    {
        $codePromotionnel->setDateSuppression(new \DateTimeImmutable());
        $this->getEntityManager()->flush();
    }

    
    public function findByOrganisateur(OrganizerProfile $organisateur): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.profilOrganisateur = :organisateur')
            ->andWhere('c.dateSuppression IS NULL')
            ->setParameter('organisateur', $organisateur)
            ->orderBy('c.creeLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    
    public function findByOrganisateurPaginated(
        OrganizerProfile $organisateur,
        int $page = 1,
        int $perPage = 4,
        ?\DateTimeImmutable $dateDebut = null,
        ?\DateTimeImmutable $dateFin = null
    ): array {
        $offset = ($page - 1) * $perPage;
        
        
        $totalQueryBuilder = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.profilOrganisateur = :organisateur')
            ->andWhere('c.dateSuppression IS NULL')
            ->setParameter('organisateur', $organisateur);
        
        
        if ($dateDebut !== null) {
            
            $totalQueryBuilder->andWhere('(c.commenceLe >= :dateDebut OR c.commenceLe IS NULL)')
                ->setParameter('dateDebut', $dateDebut);
        }
        
        if ($dateFin !== null) {
            
            $totalQueryBuilder->andWhere('(c.seTermineLe <= :dateFin OR c.seTermineLe IS NULL)')
                ->setParameter('dateFin', $dateFin);
        }
        
        $total = (int) $totalQueryBuilder->getQuery()->getSingleScalarResult();
        
        
        $itemsQueryBuilder = $this->createQueryBuilder('c')
            ->andWhere('c.profilOrganisateur = :organisateur')
            ->andWhere('c.dateSuppression IS NULL')
            ->setParameter('organisateur', $organisateur);
        
        
        if ($dateDebut !== null) {
            
            $itemsQueryBuilder->andWhere('(c.commenceLe >= :dateDebut OR c.commenceLe IS NULL)')
                ->setParameter('dateDebut', $dateDebut);
        }
        
        if ($dateFin !== null) {
            
            $itemsQueryBuilder->andWhere('(c.seTermineLe <= :dateFin OR c.seTermineLe IS NULL)')
                ->setParameter('dateFin', $dateFin);
        }
        
        $items = $itemsQueryBuilder
            ->orderBy('c.creeLe', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
        
        $pages = (int) ceil($total / $perPage);
        
        return [
            'items' => $items,
            'total' => $total,
            'pages' => $pages,
            'current_page' => $page,
            'per_page' => $perPage,
        ];
    }

    
    public function findActiveByOrganisateur(OrganizerProfile $organisateur): array
    {
        $now = new \DateTimeImmutable();
        
        return $this->createQueryBuilder('c')
            ->andWhere('c.profilOrganisateur = :organisateur')
            ->andWhere('c.dateSuppression IS NULL')
            ->andWhere('(c.commenceLe IS NULL OR c.commenceLe <= :now)')
            ->andWhere('(c.seTermineLe IS NULL OR c.seTermineLe >= :now)')
            ->setParameter('organisateur', $organisateur)
            ->setParameter('now', $now)
            ->orderBy('c.creeLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    
    public function countUtilisations(CodePromotionnel $codePromotionnel): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        
        return (int) $qb->select('COUNT(a.id)')
            ->from('App\Entity\ApplicationPromotion', 'a')
            ->where('a.promotion = :promotion')
            ->setParameter('promotion', $codePromotionnel)
            ->getQuery()
            ->getSingleScalarResult();
    }

    
    public function countUtilisationsByUser(CodePromotionnel $codePromotionnel, $userId): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        
        return (int) $qb->select('COUNT(a.id)')
            ->from('App\Entity\ApplicationPromotion', 'a')
            ->where('a.promotion = :promotion')
            ->andWhere('a.utilisateur = :user')
            ->setParameter('promotion', $codePromotionnel)
            ->setParameter('user', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    
    public function getTotalRemise(CodePromotionnel $codePromotionnel): float
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        
        $result = $qb->select('SUM(a.montantRemise)')
            ->from('App\Entity\ApplicationPromotion', 'a')
            ->where('a.promotion = :promotion')
            ->setParameter('promotion', $codePromotionnel)
            ->getQuery()
            ->getSingleScalarResult();
        
        return (float) ($result ?? 0);
    }

    
    public function findExpiringSoon(OrganizerProfile $organisateur, int $days = 7): array
    {
        $now = new \DateTimeImmutable();
        $limit = $now->modify("+{$days} days");
        
        return $this->createQueryBuilder('c')
            ->andWhere('c.profilOrganisateur = :organisateur')
            ->andWhere('c.dateSuppression IS NULL')
            ->andWhere('c.seTermineLe IS NOT NULL')
            ->andWhere('c.seTermineLe >= :now')
            ->andWhere('c.seTermineLe <= :limit')
            ->setParameter('organisateur', $organisateur)
            ->setParameter('now', $now)
            ->setParameter('limit', $limit)
            ->orderBy('c.seTermineLe', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

