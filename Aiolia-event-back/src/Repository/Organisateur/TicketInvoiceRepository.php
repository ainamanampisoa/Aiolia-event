<?php

namespace App\Repository\Organisateur;

use App\Entity\TicketInvoice;
use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

class TicketInvoiceRepository extends ServiceEntityRepository
{
    private const PAID_STATUSES = ['paid', 'partially_paid'];
    private const SCHEMA = 'aiolia';
    private const INVOICE_TABLE = 'factures_billets';
    private const ORDER_ITEM_TABLE = 'elements_commandes';
    private const TICKET_TYPE_TABLE = 'types_billets';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TicketInvoice::class);
    }

    /**
     * Revenus facturés par type de billet pour un événement (somme montant_total).
     * Inclut uniquement les factures payées ou partiellement payées.
     *
     * @return array<string, float> [typeBilletId => revenue]
     */
    public function getRevenueByEvent(Event $event): array
    {
        return $this->fetchRevenueData($event);
    }

    /**
     * Chiffre d'affaires par type de billet pour un événement,
     * limité à une période (issuedAt entre $dateFrom et $dateTo)
     * et aux factures payées / partiellement payées.
     *
     * @return array<string, float>
     */
    public function getRevenueByEventAndPeriod(Event $event, \DateTimeInterface $dateFrom, \DateTimeInterface $dateTo): array
    {
        return $this->fetchRevenueData($event, $dateFrom, $dateTo);
    }

    /**
     * Méthode générique pour récupérer les données de revenus
     */
    private function fetchRevenueData(Event $event, ?\DateTimeInterface $dateFrom = null, ?\DateTimeInterface $dateTo = null): array
    {
        $params = ['eventId' => $event->getId()];
        $conditions = ['tb.id_evenement = :eventId'];
        
        // Condition de statut
        $placeholders = [];
        foreach (self::PAID_STATUSES as $index => $status) {
            $paramName = 'status' . $index;
            $placeholders[] = ':' . $paramName;
            $params[$paramName] = $status;
        }
        $conditions[] = 'fb.statut IN (' . implode(', ', $placeholders) . ')';

        // Conditions de date si fournies
        if ($dateFrom !== null) {
            // Convertir en UTC pour la comparaison avec TIMESTAMPTZ
            $dateFromImmutable = $dateFrom instanceof \DateTimeImmutable 
                ? $dateFrom 
                : \DateTimeImmutable::createFromMutable($dateFrom);
            $utcDateFrom = $dateFromImmutable->setTimezone(new \DateTimeZone('UTC'));
            $conditions[] = 'fb.emise_le >= :dateFrom';
            $params['dateFrom'] = $utcDateFrom->format('Y-m-d H:i:s');
        }
        
        if ($dateTo !== null) {
            // Convertir en UTC pour la comparaison avec TIMESTAMPTZ
            $dateToImmutable = $dateTo instanceof \DateTimeImmutable 
                ? $dateTo 
                : \DateTimeImmutable::createFromMutable($dateTo);
            $utcDateTo = $dateToImmutable->setTimezone(new \DateTimeZone('UTC'));
            $conditions[] = 'fb.emise_le <= :dateTo';
            $params['dateTo'] = $utcDateTo->format('Y-m-d H:i:s');
        }

        $sql = sprintf(
            "SELECT
                ec.id_type_billet AS type_id,
                SUM(fb.montant_total::numeric) AS revenue
            FROM %s.%s fb
            INNER JOIN %s.%s ec ON ec.id_commande = fb.id_commande
            INNER JOIN %s.%s tb ON tb.id = ec.id_type_billet
            WHERE %s
            GROUP BY ec.id_type_billet",
            self::SCHEMA,
            self::INVOICE_TABLE,
            self::SCHEMA,
            self::ORDER_ITEM_TABLE,
            self::SCHEMA,
            self::TICKET_TYPE_TABLE,
            implode(' AND ', $conditions)
        );

        $conn = $this->getEntityManager()->getConnection();
        $rows = $conn->fetchAllAssociative($sql, $params);

        $revenues = [];
        foreach ($rows as $row) {
            $revenues[(string) $row['type_id']] = (float) $row['revenue'];
        }

        return $revenues;
    }

    /**
     * Récupère les factures avec filtres
     */
    public function findAllWithFilters(
        ?string $status = null,
        ?string $search = null,
        ?int $month = null,
        ?int $year = null,
        int $limit = 50,
        int $offset = 0
    ): array {
        $qb = $this->createQueryBuilderWithFilters($status, $search, $month, $year)
            ->leftJoin('ti.customer', 'c')
            ->addSelect('c');

        return $qb->orderBy('ti.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les factures avec filtres
     */
    public function countWithFilters(
        ?string $status = null,
        ?string $search = null,
        ?int $month = null,
        ?int $year = null
    ): int {
        $qb = $this->createQueryBuilderWithFilters($status, $search, $month, $year)
            ->select('COUNT(ti.id)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Crée un QueryBuilder avec les filtres appliqués
     */
    private function createQueryBuilderWithFilters(
        ?string $status,
        ?string $search,
        ?int $month,
        ?int $year
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('ti');

        if ($status) {
            $qb->andWhere('ti.status = :status')
                ->setParameter('status', $status);
        }

        if ($search) {
            $qb->leftJoin('ti.customer', 'c');
            $this->applySearchFilter($qb, $search);
        }

        $this->applyDateFilters($qb, $month, $year);

        return $qb;
    }

    /**
     * Applique les filtres de date
     */
    private function applyDateFilters(QueryBuilder $qb, ?int $month, ?int $year): void
    {
        if ($month !== null && $month > 0 && $year !== null) {
            $this->applyMonthYearFilter($qb, $month, $year);
        } elseif ($year !== null) {
            $this->applyYearFilter($qb, $year);
        }
    }

    /**
     * Applique un filtre pour un mois et année spécifiques
     */
    private function applyMonthYearFilter(QueryBuilder $qb, int $month, int $year): void
    {
        $monthStart = (new \DateTime(sprintf('%d-%02d-01', $year, $month)))
            ->setTime(0, 0, 0);
        $monthEnd = (clone $monthStart)->modify('+1 month');

        $qb->andWhere('ti.issuedAt >= :monthStart')
            ->andWhere('ti.issuedAt < :monthEnd')
            ->setParameter('monthStart', $monthStart)
            ->setParameter('monthEnd', $monthEnd);
    }

    /**
     * Applique un filtre pour une année spécifique
     */
    private function applyYearFilter(QueryBuilder $qb, int $year): void
    {
        $yearStart = (new \DateTime(sprintf('%d-01-01', $year)))
            ->setTime(0, 0, 0);
        $yearEnd = (clone $yearStart)->modify('+1 year');

        $qb->andWhere('ti.issuedAt >= :yearStart')
            ->andWhere('ti.issuedAt < :yearEnd')
            ->setParameter('yearStart', $yearStart)
            ->setParameter('yearEnd', $yearEnd);
    }

    /**
     * Trouve une facture par son numéro
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
     * Applique le filtre de recherche
     */
    private function applySearchFilter(QueryBuilder $qb, string $search): void
    {
        $searchTrimmed = trim($search);
        
        // Recherche par numéro de facture (uniquement chiffres)
        if (preg_match('/^\d+$/', $searchTrimmed)) {
            $qb->andWhere($qb->expr()->like('ti.invoiceNumber', ':search'))
                ->setParameter('search', '%' . $searchTrimmed . '%');
            return;
        }

        // Recherche par email
        if (str_contains($searchTrimmed, '@')) {
            $qb->andWhere($qb->expr()->like('c.email', ':search'))
                ->setParameter('search', '%' . $searchTrimmed . '%');
            return;
        }

        // Recherche par nom/prénom
        $this->applyNameSearch($qb, $searchTrimmed);
    }

    /**
     * Applique la recherche par nom/prénom
     */
    private function applyNameSearch(QueryBuilder $qb, string $search): void
    {
        $words = preg_split('/\s+/', $search);
        
        if (count($words) > 1) {
            // Recherche avec plusieurs mots : premier mot = prénom, dernier mot = nom
            $qb->andWhere(
                $qb->expr()->andX(
                    $qb->expr()->like('c.prenom', ':firstWord'),
                    $qb->expr()->like('c.nom', ':lastWord')
                )
            )
            ->setParameter('firstWord', '%' . $words[0] . '%')
            ->setParameter('lastWord', '%' . end($words) . '%');
        } else {
            // Recherche avec un seul mot : cherche dans prénom OU nom
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('c.prenom', ':search'),
                    $qb->expr()->like('c.nom', ':search')
                )
            )
            ->setParameter('search', '%' . $search . '%');
        }
    }
}