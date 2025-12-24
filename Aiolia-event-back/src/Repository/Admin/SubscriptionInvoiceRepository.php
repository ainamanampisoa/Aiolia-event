<?php

namespace App\Repository\Admin;

use App\Entity\SubscriptionInvoice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\ArrayParameterType;

/**
 * @extends ServiceEntityRepository<SubscriptionInvoice>
 */
class SubscriptionInvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubscriptionInvoice::class);
    }

    public function findAllWithFilters(
        ?string $status = null, 
        ?string $search = null, 
        ?int $month = null, 
        ?int $year = null, 
        int $limit = 50, 
        int $offset = 0
    ): array {
        $qb = $this->createQueryBuilder('si')
            ->leftJoin('si.customer', 'c')
            ->addSelect('c');

        $this->applyFilters($qb, $status, $search, $month, $year);

        return $qb->orderBy('si.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countWithFilters(
        ?string $status = null, 
        ?string $search = null, 
        ?int $month = null, 
        ?int $year = null
    ): int {
        $qb = $this->createQueryBuilder('si')
            ->select('COUNT(si.id)');

        $this->applyFilters($qb, $status, $search, $month, $year);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function applyFilters($qb, ?string $status, ?string $search, ?int $month, ?int $year): void
    {
        if ($status) {
            $qb->andWhere('si.status = :status')
                ->setParameter('status', $status);
        }

        if ($search) {
            if ($qb->getDQLPart('join') === null || !$this->hasCustomerJoin($qb)) {
                $qb->leftJoin('si.customer', 'c');
            }
            $this->applySearchFilter($qb, $search, 'si', 'c');
        }

        $this->applyDateFilter($qb, $month, $year);
    }

    private function hasCustomerJoin($qb): bool
    {
        $joins = $qb->getDQLPart('join');
        foreach ($joins as $joinAlias => $join) {
            foreach ($join as $joinItem) {
                if (strpos((string) $joinItem, 'si.customer') !== false) {
                    return true;
                }
            }
        }
        return false;
    }

    private function applyDateFilter($qb, ?int $month, ?int $year): void
    {
        if ($year === null) {
            return;
        }

        if ($month !== null && $month > 0) {
            $this->applyMonthYearFilter($qb, $month, $year);
        } else {
            $this->applyYearFilter($qb, $year);
        }
    }

    private function applyMonthYearFilter($qb, int $month, int $year): void
    {
        $monthStart = (new \DateTime())
            ->setDate($year, $month, 1)
            ->setTime(0, 0, 0);
        
        $monthEnd = (clone $monthStart)
            ->modify('last day of this month')
            ->setTime(23, 59, 59);

        $qb->andWhere('si.billingMonth BETWEEN :monthStart AND :monthEnd')
            ->setParameter('monthStart', $monthStart)
            ->setParameter('monthEnd', $monthEnd);
    }

    private function applyYearFilter($qb, int $year): void
    {
        // Pour 2025, exclure mai car les données commencent en juin 2025
        if ($year === 2025) {
            $yearStart = (new \DateTime())
                ->setDate($year, 6, 1) // Commencer en juin 2025
                ->setTime(0, 0, 0);
        } else {
            $yearStart = (new \DateTime())
                ->setDate($year, 1, 1)
                ->setTime(0, 0, 0);
        }
        
        $yearEnd = (clone $yearStart)
            ->setDate($year, 12, 31)
            ->setTime(23, 59, 59);

        $qb->andWhere('si.billingMonth BETWEEN :yearStart AND :yearEnd')
            ->setParameter('yearStart', $yearStart)
            ->setParameter('yearEnd', $yearEnd);
    }

    public function findByInvoiceNumber(string $invoiceNumber): ?SubscriptionInvoice
    {
        return $this->createQueryBuilder('si')
            ->where('si.invoiceNumber = :number')
            ->setParameter('number', $invoiceNumber)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getPlanTierForInvoice(SubscriptionInvoice $invoice): ?string
    {
        $connection = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT sp.niveau
            FROM aiolia.factures_abonnements fi
            INNER JOIN aiolia.abonnements_organisateurs os ON os.id = fi.id_abonnement
            INNER JOIN aiolia.plans_abonnements sp ON sp.id = os.id_plan
            WHERE fi.id = :invoice_id
        ";

        $tier = $connection->fetchOne($sql, ['invoice_id' => $invoice->getId()]);

        return $tier !== false ? $tier : null;
    }

    public function getPlanTiersForInvoices(array $invoices): array
    {
        if (empty($invoices)) {
            return [];
        }

        $invoiceIds = array_map(fn($invoice) => $invoice->getId(), $invoices);
        
        $connection = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT fi.id as invoice_id, sp.niveau
            FROM aiolia.factures_abonnements fi
            INNER JOIN aiolia.abonnements_organisateurs os ON os.id = fi.id_abonnement
            INNER JOIN aiolia.plans_abonnements sp ON sp.id = os.id_plan
            WHERE fi.id IN (:invoice_ids)
        ";

        $results = $connection->fetchAllKeyValue(
            $sql,
            ['invoice_ids' => $invoiceIds],
            ['invoice_ids' => ArrayParameterType::INTEGER]
        );

        $tiers = [];
        foreach ($invoiceIds as $id) {
            $tiers[$id] = $results[$id] ?? null;
        }

        return $tiers;
    }

    public function getPlanInfoForInvoice(SubscriptionInvoice $invoice): ?array
    {
        $connection = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT sp.niveau, sp.periode_facturation, sp.nom, sp.code
            FROM aiolia.factures_abonnements fi
            INNER JOIN aiolia.abonnements_organisateurs os ON os.id = fi.id_abonnement
            INNER JOIN aiolia.plans_abonnements sp ON sp.id = os.id_plan
            WHERE fi.id = :invoice_id
        ";

        $result = $connection->fetchAssociative($sql, ['invoice_id' => $invoice->getId()]);

        if (!$result) {
            $sql = "
                SELECT DISTINCT sp.niveau, sp.periode_facturation, sp.nom, sp.code
                FROM aiolia.factures_abonnements fi
                INNER JOIN aiolia.transactions_paiement_mobile tpm ON tpm.id_facture = fi.id
                INNER JOIN aiolia.abonnements_organisateurs os ON os.id = fi.id_abonnement
                INNER JOIN aiolia.plans_abonnements sp ON sp.id = os.id_plan
                WHERE fi.id = :invoice_id
            ";

            $result = $connection->fetchAssociative($sql, ['invoice_id' => $invoice->getId()]);
        }

        return $result ?: null;
    }

    public function getPlanInfosForInvoices(array $invoices): array
    {
        if (empty($invoices)) {
            return [];
        }

        $invoiceIds = array_map(fn($invoice) => $invoice->getId(), $invoices);
        $connection = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT 
                fi.id as invoice_id, 
                sp.niveau, 
                sp.periode_facturation, 
                sp.nom, 
                sp.code
            FROM aiolia.factures_abonnements fi
            INNER JOIN aiolia.abonnements_organisateurs os ON os.id = fi.id_abonnement
            INNER JOIN aiolia.plans_abonnements sp ON sp.id = os.id_plan
            WHERE fi.id IN (:invoice_ids)
        ";

        $results = $connection->fetchAllAssociative(
            $sql,
            ['invoice_ids' => $invoiceIds],
            ['invoice_ids' => ArrayParameterType::INTEGER]
        );

        $planInfos = [];
        foreach ($results as $row) {
            // Convertir l'ID en chaîne pour correspondre au format utilisé dans le contrôleur
            $invoiceId = (string) $row['invoice_id'];
            // Vérifier que niveau n'est pas null et n'est pas une chaîne vide avant d'ajouter
            $niveau = $row['niveau'] ?? null;
            if ($niveau !== null && $niveau !== '') {
                $planInfos[$invoiceId] = [
                    'niveau' => $niveau,
                    'periode_facturation' => $row['periode_facturation'] ?? null,
                    'nom' => $row['nom'] ?? null,
                    'code' => $row['code'] ?? null,
                ];
            }
        }

        return $planInfos;
    }

    public function getInvoiceItemsForInvoice(SubscriptionInvoice $invoice): array
    {
        return [];
    }

    public function getPaymentMethodForInvoice(SubscriptionInvoice $invoice): ?string
    {
        return $invoice->getPaymentMethod();
    }

    public function findOverdueInvoices(\DateTimeInterface $currentDate): array
    {
        $qb = $this->createQueryBuilder('si')
            ->where('si.status IN (:statuses)')
            ->andWhere('si.paidAt IS NULL')
            ->andWhere('si.dueAt < :currentDate')
            ->setParameter('statuses', [SubscriptionInvoice::STATUS_DRAFT, SubscriptionInvoice::STATUS_ISSUED])
            ->setParameter('currentDate', $currentDate)
            ->orderBy('si.dueAt', 'ASC');

        return $qb->getQuery()->getResult();
    }

    private function applySearchFilter($qb, string $search, string $invoiceAlias, string $customerAlias): void
    {
        $searchTrimmed = trim($search);
        $pattern = '%' . $searchTrimmed . '%';

        if (preg_match('/^\d+$/', $searchTrimmed)) {
            $qb->andWhere("$invoiceAlias.invoiceNumber LIKE :search")
                ->setParameter('search', $pattern);
            return;
        }

        if (str_contains($searchTrimmed, '@')) {
            $qb->andWhere("$customerAlias.email LIKE :search")
                ->setParameter('search', $pattern);
            return;
        }

        $words = preg_split('/\s+/', $searchTrimmed);

        if (count($words) > 1) {
            $qb->andWhere("$customerAlias.prenom LIKE :firstWord AND $customerAlias.nom LIKE :lastWord")
                ->setParameter('firstWord', '%' . $words[0] . '%')
                ->setParameter('lastWord', '%' . end($words) . '%');
        } else {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like("$customerAlias.prenom", ':search'),
                    $qb->expr()->like("$customerAlias.nom", ':search')
                )
            )->setParameter('search', $pattern);
        }
    }

    public function findInvoiceForMonth(string $subscriptionId, \DateTimeInterface $billingMonth): ?SubscriptionInvoice
    {
        $monthStart = \DateTimeImmutable::createFromInterface($billingMonth)
            ->modify('first day of this month')
            ->setTime(0, 0, 0);
        
        $monthEnd = $monthStart->modify('+1 month');

        return $this->createQueryBuilder('si')
            ->where('si.subscriptionId = :subscriptionId')
            ->andWhere('si.issuedAt >= :monthStart')
            ->andWhere('si.issuedAt < :monthEnd')
            ->setParameter('subscriptionId', $subscriptionId)
            ->setParameter('monthStart', $monthStart)
            ->setParameter('monthEnd', $monthEnd)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Calcule le montant total des factures d'abonnement payées par un utilisateur (organisateur)
     * Inclut uniquement les factures payées ou partiellement payées
     * Note: Pour l'organisateur, ce sont des dépenses, pas des revenus
     * 
     * @param \App\Entity\User $user L'utilisateur (organisateur)
     * @param \DateTimeInterface|null $dateFrom Date de début (optionnel)
     * @param \DateTimeInterface|null $dateTo Date de fin (optionnel)
     * @return float Le montant total des factures d'abonnement payées
     */
    public function getSubscriptionRevenueByUser(\App\Entity\User $user, ?\DateTimeInterface $dateFrom = null, ?\DateTimeInterface $dateTo = null): float
    {
        $qb = $this->createQueryBuilder('si')
            ->select('COALESCE(SUM(si.totalAmount), 0)')
            ->where('si.customer = :user')
            ->andWhere('si.status IN (:paidStatuses)')
            ->setParameter('user', $user)
            ->setParameter('paidStatuses', [SubscriptionInvoice::STATUS_PAID, SubscriptionInvoice::STATUS_PARTIALLY_PAID]);

        if ($dateFrom !== null) {
            $qb->andWhere('si.billingMonth >= :dateFrom')
                ->setParameter('dateFrom', $dateFrom);
        }

        if ($dateTo !== null) {
            $qb->andWhere('si.billingMonth <= :dateTo')
                ->setParameter('dateTo', $dateTo);
        }

        $result = $qb->getQuery()->getSingleScalarResult();
        return (float) $result;
    }
}