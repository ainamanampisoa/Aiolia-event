<?php

namespace App\Repository\Organisateur;

use App\Entity\CodePromotionnel;
use App\Entity\OrganizerProfile;
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

    /**
     * Récupère tous les codes promotionnels d'un organisateur
     */
    public function findByOrganisateur(OrganizerProfile $organisateur): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.profilOrganisateur = :organisateur')
            ->setParameter('organisateur', $organisateur)
            ->orderBy('c.creeLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les codes promotionnels d'un organisateur avec pagination et filtres de date
     *
     * @param OrganizerProfile $organisateur
     * @param int $page Numéro de la page (commence à 1)
     * @param int $perPage Nombre d'éléments par page
     * @param \DateTimeImmutable|null $dateDebut Date de début (peut être null)
     * @param \DateTimeImmutable|null $dateFin Date de fin (peut être null)
     * @return array ['items' => array, 'total' => int, 'pages' => int]
     */
    public function findByOrganisateurPaginated(
        OrganizerProfile $organisateur,
        int $page = 1,
        int $perPage = 4,
        ?\DateTimeImmutable $dateDebut = null,
        ?\DateTimeImmutable $dateFin = null
    ): array {
        $offset = ($page - 1) * $perPage;
        
        // Construction de la requête de base pour le comptage
        $totalQueryBuilder = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.profilOrganisateur = :organisateur')
            ->setParameter('organisateur', $organisateur);
        
        // Ajout des filtres de date si fournis
        if ($dateDebut !== null) {
            // Inclure les promotions qui commencent à partir de la date OU qui n'ont pas de date de début
            $totalQueryBuilder->andWhere('(c.commenceLe >= :dateDebut OR c.commenceLe IS NULL)')
                ->setParameter('dateDebut', $dateDebut);
        }
        
        if ($dateFin !== null) {
            // Inclure les promotions qui se terminent avant ou à la date OU qui n'ont pas de date de fin
            $totalQueryBuilder->andWhere('(c.seTermineLe <= :dateFin OR c.seTermineLe IS NULL)')
                ->setParameter('dateFin', $dateFin);
        }
        
        $total = (int) $totalQueryBuilder->getQuery()->getSingleScalarResult();
        
        // Construction de la requête pour récupérer les résultats paginés
        $itemsQueryBuilder = $this->createQueryBuilder('c')
            ->andWhere('c.profilOrganisateur = :organisateur')
            ->setParameter('organisateur', $organisateur);
        
        // Ajout des mêmes filtres de date
        if ($dateDebut !== null) {
            // Inclure les promotions qui commencent à partir de la date OU qui n'ont pas de date de début
            $itemsQueryBuilder->andWhere('(c.commenceLe >= :dateDebut OR c.commenceLe IS NULL)')
                ->setParameter('dateDebut', $dateDebut);
        }
        
        if ($dateFin !== null) {
            // Inclure les promotions qui se terminent avant ou à la date OU qui n'ont pas de date de fin
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

    /**
     * Récupère les codes promotionnels actifs d'un organisateur
     */
    public function findActiveByOrganisateur(OrganizerProfile $organisateur): array
    {
        $now = new \DateTimeImmutable();
        
        return $this->createQueryBuilder('c')
            ->andWhere('c.profilOrganisateur = :organisateur')
            ->andWhere('(c.commenceLe IS NULL OR c.commenceLe <= :now)')
            ->andWhere('(c.seTermineLe IS NULL OR c.seTermineLe >= :now)')
            ->setParameter('organisateur', $organisateur)
            ->setParameter('now', $now)
            ->orderBy('c.creeLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre d'utilisations d'un code promotionnel
     */
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

    /**
     * Compte le nombre d'utilisations d'un code promotionnel par un utilisateur
     */
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

    /**
     * Calcule le montant total des réductions accordées pour un code promotionnel
     */
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

    /**
     * Récupère les codes promotionnels qui expirent bientôt (dans les 7 prochains jours)
     */
    public function findExpiringSoon(OrganizerProfile $organisateur, int $days = 7): array
    {
        $now = new \DateTimeImmutable();
        $limit = $now->modify("+{$days} days");
        
        return $this->createQueryBuilder('c')
            ->andWhere('c.profilOrganisateur = :organisateur')
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

