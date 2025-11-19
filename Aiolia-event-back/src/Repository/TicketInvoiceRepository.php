<?php

namespace App\Repository;

use App\Entity\TicketInvoice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TicketInvoice>
 */
class TicketInvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TicketInvoice::class);
    }

    /**
     * Trouve toutes les factures avec filtres optionnels
     */
    public function findAllWithFilters(?string $status = null, ?string $search = null, ?\DateTime $dateFrom = null, ?\DateTime $dateTo = null, int $limit = 50, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('ti')
            ->leftJoin('ti.customer', 'c')
            ->addSelect('c');

        if ($status) {
            $qb->andWhere('ti.status = :status')
                ->setParameter('status', $status);
        }

        if ($search) {
            $this->applySearchFilter($qb, $search, 'ti', 'c');
        }

        if ($dateFrom) {
            $dateFromStart = clone $dateFrom;
            $dateFromStart->setTime(0, 0, 0);
            $qb->andWhere('ti.issuedAt >= :dateFrom')
                ->setParameter('dateFrom', $dateFromStart);
        }

        if ($dateTo) {
            $dateToEnd = clone $dateTo;
            $dateToEnd->setTime(23, 59, 59);
            $qb->andWhere('ti.issuedAt <= :dateTo')
                ->setParameter('dateTo', $dateToEnd);
        }

        return $qb->orderBy('ti.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le total de factures avec filtres
     */
    public function countWithFilters(?string $status = null, ?string $search = null, ?\DateTime $dateFrom = null, ?\DateTime $dateTo = null): int
    {
        $qb = $this->createQueryBuilder('ti')
            ->select('COUNT(ti.id)');

        if ($status) {
            $qb->andWhere('ti.status = :status')
                ->setParameter('status', $status);
        }

        if ($search) {
            $qb->leftJoin('ti.customer', 'c');
            $this->applySearchFilter($qb, $search, 'ti', 'c');
        }

        if ($dateFrom) {
            $dateFromStart = clone $dateFrom;
            $dateFromStart->setTime(0, 0, 0);
            $qb->andWhere('ti.issuedAt >= :dateFrom')
                ->setParameter('dateFrom', $dateFromStart);
        }

        if ($dateTo) {
            $dateToEnd = clone $dateTo;
            $dateToEnd->setTime(23, 59, 59);
            $qb->andWhere('ti.issuedAt <= :dateTo')
                ->setParameter('dateTo', $dateToEnd);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Trouve une facture par numéro
     */
    public function findByInvoiceNumber(string $invoiceNumber): ?TicketInvoice
    {
        return $this->createQueryBuilder('ti')
            ->where('ti.invoiceNumber = :number')
            ->setParameter('number', $invoiceNumber)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Applique un filtre de recherche intelligent selon le type de valeur
     * - Si uniquement des chiffres : recherche dans invoiceNumber
     * - Si contient @ : recherche dans email
     * - Sinon : recherche dans prenom, nom et nom complet (prenom + nom)
     */
    private function applySearchFilter($qb, string $search, string $invoiceAlias, string $customerAlias): void
    {
        $searchTrimmed = trim($search);
        $searchPattern = '%' . $searchTrimmed . '%';

        // Vérifier si c'est uniquement des chiffres (numéro de facture)
        if (preg_match('/^\d+$/', $searchTrimmed)) {
            $qb->andWhere($qb->expr()->like($invoiceAlias . '.invoiceNumber', ':search'))
                ->setParameter('search', $searchPattern);
            return;
        }

        // Vérifier si c'est un email (contient @)
        if (strpos($searchTrimmed, '@') !== false) {
            $qb->andWhere($qb->expr()->like($customerAlias . '.email', ':search'))
                ->setParameter('search', $searchPattern);
            return;
        }

        // Sinon, rechercher dans prenom, nom et nom complet
        // Si la recherche contient un espace, diviser en mots et chercher dans prenom ET nom
        $words = preg_split('/\s+/', $searchTrimmed);
        
        if (count($words) > 1) {
            // Recherche multi-mots : chercher le premier mot dans prenom et le dernier dans nom
            $firstWord = '%' . $words[0] . '%';
            $lastWord = '%' . end($words) . '%';
            
            $qb->andWhere(
                $qb->expr()->andX(
                    $qb->expr()->like($customerAlias . '.prenom', ':firstWord'),
                    $qb->expr()->like($customerAlias . '.nom', ':lastWord')
                )
            )
                ->setParameter('firstWord', $firstWord)
                ->setParameter('lastWord', $lastWord);
        } else {
            // Recherche simple : chercher dans prenom OU nom
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like($customerAlias . '.prenom', ':search'),
                    $qb->expr()->like($customerAlias . '.nom', ':search')
                )
            )
                ->setParameter('search', $searchPattern);
        }
    }
}

