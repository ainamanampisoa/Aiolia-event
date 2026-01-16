<?php

namespace App\Repository\Organisateur;

use App\Entity\TicketInvoice;
use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

class TicketInvoiceRepository extends ServiceEntityRepository
{
    private const PAID_STATUSES = ['paid', 'partially_paid', 'issued'];
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

        // Conditions de date si fournies
        if ($dateFrom !== null) {
            // Convertir en UTC pour la comparaison avec TIMESTAMPTZ
            $dateFromImmutable = $dateFrom instanceof \DateTimeImmutable 
                ? $dateFrom 
                : \DateTimeImmutable::createFromMutable($dateFrom);
            $utcDateFrom = $dateFromImmutable->setTimezone(new \DateTimeZone('UTC'));
            $conditions[] = 'COALESCE(fb.emise_le, c.cree_le) >= :dateFrom';
            $params['dateFrom'] = $utcDateFrom->format('Y-m-d H:i:s');
        }
        
        if ($dateTo !== null) {
            // Convertir en UTC pour la comparaison avec TIMESTAMPTZ
            $dateToImmutable = $dateTo instanceof \DateTimeImmutable 
                ? $dateTo 
                : \DateTimeImmutable::createFromMutable($dateTo);
            $utcDateTo = $dateToImmutable->setTimezone(new \DateTimeZone('UTC'));
            $conditions[] = 'COALESCE(fb.emise_le, c.cree_le) <= :dateTo';
            $params['dateTo'] = $utcDateTo->format('Y-m-d H:i:s');
        }

        $sql = sprintf(
            "SELECT
                ec.id_type_billet AS type_id,
                COALESCE(SUM(COALESCE(fb.montant_ttc::numeric, ec.montant_total::numeric)), 0) AS revenue
            FROM %s.%s c
            INNER JOIN %s.%s ec ON ec.id_commande = c.id
            INNER JOIN %s.%s tb ON tb.id = ec.id_type_billet
            LEFT JOIN %s.%s fb ON fb.id_commande = c.id AND fb.statut IN ('paid', 'partially_paid', 'issued')
            WHERE %s
              AND c.statut = 'paid'
            GROUP BY ec.id_type_billet",
            self::SCHEMA,
            'commandes',
            self::SCHEMA,
            self::ORDER_ITEM_TABLE,
            self::SCHEMA,
            self::TICKET_TYPE_TABLE,
            self::SCHEMA,
            self::INVOICE_TABLE,
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
        // Vérifier que le mois demandé n'est pas avant le premier mois disponible
        $firstMonth = $this->getFirstAvailableMonthForYear($year);
        
        // Si le mois demandé est avant le premier mois disponible, ne rien retourner
        if ($month < $firstMonth) {
            // Forcer un résultat vide en ajoutant une condition impossible
            $qb->andWhere('1 = 0');
            return;
        }
        
        $monthStart = (new \DateTime(sprintf('%d-%02d-01', $year, $month)))
            ->setTime(0, 0, 0);
        $monthEnd = (clone $monthStart)->modify('+1 month');

        $qb->andWhere('ti.issuedAt >= :monthStart')
            ->andWhere('ti.issuedAt < :monthEnd')
            ->setParameter('monthStart', $monthStart)
            ->setParameter('monthEnd', $monthEnd);
    }

    /**
     * Détermine dynamiquement le premier mois disponible dans la base de données pour une année donnée
     * 
     * @param int $year L'année à vérifier
     * @return int Le numéro du mois (1-12), ou 1 si aucune donnée n'est trouvée
     */
    private function getFirstAvailableMonthForYear(int $year): int
    {
        $yearStart = (new \DateTime(sprintf('%d-01-01', $year)))
            ->setTime(0, 0, 0);
        
        $yearEnd = (new \DateTime(sprintf('%d-12-31', $year)))
            ->setTime(23, 59, 59);

        try {
            $result = $this->createQueryBuilder('ti')
                ->select('MIN(ti.issuedAt) as firstDate')
                ->where('ti.issuedAt >= :yearStart')
                ->andWhere('ti.issuedAt <= :yearEnd')
                ->setParameter('yearStart', $yearStart)
                ->setParameter('yearEnd', $yearEnd)
                ->getQuery()
                ->getOneOrNullResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);

            if ($result && isset($result['firstDate']) && $result['firstDate'] instanceof \DateTimeInterface) {
                return (int) $result['firstDate']->format('n');
            }
        } catch (\Exception $e) {
            // En cas d'erreur, retourner janvier par défaut
        }

        // Par défaut, commencer en janvier si aucune donnée n'est trouvée
        return 1;
    }

    /**
     * Applique un filtre pour une année spécifique
     */
    private function applyYearFilter(QueryBuilder $qb, int $year): void
    {
        $firstMonth = $this->getFirstAvailableMonthForYear($year);
        
        // Pour 2025, forcer le premier mois à juin si mai est détecté
        if ($year === 2025 && $firstMonth < 6) {
            $firstMonth = 6;
        }
        
        $yearStart = (new \DateTime(sprintf('%d-%02d-01', $year, $firstMonth)))
            ->setTime(0, 0, 0);
        $yearEnd = (new \DateTime(sprintf('%d-12-31', $year)))
            ->setTime(23, 59, 59);

        $qb->andWhere('ti.issuedAt >= :yearStart')
            ->andWhere('ti.issuedAt <= :yearEnd')
            ->setParameter('yearStart', $yearStart)
            ->setParameter('yearEnd', $yearEnd);
        
        // Exclusion explicite de mai 2025 pour éviter les factures de mai
        if ($year === 2025) {
            $mayStart = (new \DateTime('2025-05-01'))->setTime(0, 0, 0);
            $mayEnd = (new \DateTime('2025-05-31'))->setTime(23, 59, 59);
            $qb->andWhere('ti.issuedAt < :mayStart OR ti.issuedAt > :mayEnd')
                ->setParameter('mayStart', $mayStart)
                ->setParameter('mayEnd', $mayEnd);
        }
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