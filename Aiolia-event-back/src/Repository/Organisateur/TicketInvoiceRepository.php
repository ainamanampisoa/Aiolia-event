<?php

namespace App\Repository\Organisateur;

use App\Entity\TicketInvoice;
use App\Entity\Event;
use Doctrine\DBAL\Exception;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class TicketInvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TicketInvoice::class);
    }

    /**
     * Revenus facturés par type de billet pour un événement (somme montant_total).
     * Inclut uniquement les factures payées ou partiellement payées.
     *
     * @return array<string,float> [typeBilletId => revenue]
     */
    public function getRevenueByEvent(Event $event): array
    {
        $sql = "
            SELECT
                ec.id_type_billet AS type_id,
                SUM(fb.montant_total::numeric) AS revenue
            FROM aiolia.factures_billets fb
            INNER JOIN aiolia.elements_commandes ec ON ec.id_commande = fb.id_commande
            INNER JOIN aiolia.types_billets tb ON tb.id = ec.id_type_billet
            WHERE tb.id_evenement = :eventId
              AND fb.statut IN ('paid', 'partially_paid')
            GROUP BY ec.id_type_billet
        ";

        $conn = $this->getEntityManager()->getConnection();
        $rows = $conn->fetchAllAssociative($sql, ['eventId' => $event->getId()]);

        $revenues = [];
        foreach ($rows as $row) {
            $revenues[(string) $row['type_id']] = (float) $row['revenue'];
        }

        return $revenues;
    }

    /**
     * Chiffre d'affaires par type de billet pour un événement,
     * limité à une période (issuedAt entre $dateFrom et $dateTo)
     * et aux factures payées / partiellement payées.
     *
     * @return array<string,float>
     */
    public function getRevenueByEventAndPeriod(Event $event, \DateTimeInterface $dateFrom, \DateTimeInterface $dateTo): array
    {
        $sql = "
            SELECT
                ec.id_type_billet AS type_id,
                SUM(fb.montant_total::numeric) AS revenue
            FROM aiolia.factures_billets fb
            INNER JOIN aiolia.elements_commandes ec ON ec.id_commande = fb.id_commande
            INNER JOIN aiolia.types_billets tb ON tb.id = ec.id_type_billet
            WHERE tb.id_evenement = :eventId
              AND fb.statut IN ('paid', 'partially_paid')
              AND fb.emise_le >= :dateFrom
              AND fb.emise_le <= :dateTo
            GROUP BY ec.id_type_billet
        ";

        $conn = $this->getEntityManager()->getConnection();
        $rows = $conn->fetchAllAssociative($sql, [
            'eventId' => $event->getId(),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);

        $revenues = [];
        foreach ($rows as $row) {
            $revenues[(string) $row['type_id']] = (float) $row['revenue'];
        }

        return $revenues;
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

