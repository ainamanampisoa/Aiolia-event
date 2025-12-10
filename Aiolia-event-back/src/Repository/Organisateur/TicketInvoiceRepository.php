<?php

namespace App\Repository\Organisateur;

use App\Entity\TicketInvoice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class TicketInvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TicketInvoice::class);
    }

    
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

    
    public function findByInvoiceNumber(string $invoiceNumber): ?TicketInvoice
    {
        return $this->createQueryBuilder('ti')
            ->where('ti.invoiceNumber = :number')
            ->setParameter('number', $invoiceNumber)
            ->getQuery()
            ->getOneOrNullResult();
    }

    
    private function applySearchFilter($qb, string $search, string $invoiceAlias, string $customerAlias): void
    {
        $searchTrimmed = trim($search);
        $searchPattern = '%' . $searchTrimmed . '%';

        
        if (preg_match('/^\d+$/', $searchTrimmed)) {
            $qb->andWhere($qb->expr()->like($invoiceAlias . '.invoiceNumber', ':search'))
                ->setParameter('search', $searchPattern);
            return;
        }

        
        if (strpos($searchTrimmed, '@') !== false) {
            $qb->andWhere($qb->expr()->like($customerAlias . '.email', ':search'))
                ->setParameter('search', $searchPattern);
            return;
        }

        
        
        $words = preg_split('/\s+/', $searchTrimmed);
        
        if (count($words) > 1) {
            
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

