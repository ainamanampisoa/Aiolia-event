<?php

namespace App\Repository;

use App\Entity\SubscriptionInvoice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SubscriptionInvoice>
 */
class SubscriptionInvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubscriptionInvoice::class);
    }

    /**
     * Trouve toutes les factures avec filtres optionnels
     */
    public function findAllWithFilters(?string $status = null, ?string $search = null, ?\DateTime $dateFrom = null, ?\DateTime $dateTo = null, int $limit = 50, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('si')
            ->leftJoin('si.customer', 'c')
            ->addSelect('c');

        if ($status) {
            $qb->andWhere('si.status = :status')
                ->setParameter('status', $status);
        }

        if ($search) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('si.invoiceNumber', ':search'),
                    $qb->expr()->like('c.email', ':search'),
                    $qb->expr()->like('c.firstName', ':search'),
                    $qb->expr()->like('c.lastName', ':search')
                )
            )
                ->setParameter('search', '%' . $search . '%');
        }

        if ($dateFrom) {
            $dateFromStart = clone $dateFrom;
            $dateFromStart->setTime(0, 0, 0);
            $qb->andWhere('si.issuedAt >= :dateFrom')
                ->setParameter('dateFrom', $dateFromStart);
        }

        if ($dateTo) {
            $dateToEnd = clone $dateTo;
            $dateToEnd->setTime(23, 59, 59);
            $qb->andWhere('si.issuedAt <= :dateTo')
                ->setParameter('dateTo', $dateToEnd);
        }

        return $qb->orderBy('si.createdAt', 'DESC')
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
        $qb = $this->createQueryBuilder('si')
            ->select('COUNT(si.id)');

        if ($status) {
            $qb->andWhere('si.status = :status')
                ->setParameter('status', $status);
        }

        if ($search) {
            $qb->leftJoin('si.customer', 'c')
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->like('si.invoiceNumber', ':search'),
                        $qb->expr()->like('c.email', ':search'),
                        $qb->expr()->like('c.firstName', ':search'),
                        $qb->expr()->like('c.lastName', ':search')
                    )
                )
                ->setParameter('search', '%' . $search . '%');
        }

        if ($dateFrom) {
            $dateFromStart = clone $dateFrom;
            $dateFromStart->setTime(0, 0, 0);
            $qb->andWhere('si.issuedAt >= :dateFrom')
                ->setParameter('dateFrom', $dateFromStart);
        }

        if ($dateTo) {
            $dateToEnd = clone $dateTo;
            $dateToEnd->setTime(23, 59, 59);
            $qb->andWhere('si.issuedAt <= :dateTo')
                ->setParameter('dateTo', $dateToEnd);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Trouve une facture par numéro
     */
    public function findByInvoiceNumber(string $invoiceNumber): ?SubscriptionInvoice
    {
        return $this->createQueryBuilder('si')
            ->where('si.invoiceNumber = :number')
            ->setParameter('number', $invoiceNumber)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère le tier du plan d'abonnement pour une facture d'abonnement
     * Retourne null si le tier n'est pas trouvé
     */
    public function getPlanTierForInvoice(SubscriptionInvoice $invoice): ?string
    {
        $connection = $this->getEntityManager()->getConnection();
        
        $sql = "
            SELECT sp.tier
            FROM aiolia.subscription_invoices si
            INNER JOIN aiolia.organizer_subscriptions os ON os.id = si.subscription_id
            INNER JOIN aiolia.subscription_plans sp ON sp.id = os.plan_id
            WHERE si.id = :invoice_id
        ";
        
        $result = $connection->fetchOne($sql, ['invoice_id' => $invoice->getId()]);
        
        return $result !== false ? $result : null;
    }

    /**
     * Récupère les tiers des plans d'abonnement pour plusieurs factures
     * Retourne un array avec invoice_id => plan_tier
     */
    public function getPlanTiersForInvoices(array $invoices): array
    {
        if (empty($invoices)) {
            return [];
        }
        
        $invoiceIds = array_map(fn($invoice) => $invoice->getId(), $invoices);
        $connection = $this->getEntityManager()->getConnection();
        
        $placeholders = implode(',', array_fill(0, count($invoiceIds), '?'));
        $sql = "
            SELECT si.id, sp.tier
            FROM aiolia.subscription_invoices si
            INNER JOIN aiolia.organizer_subscriptions os ON os.id = si.subscription_id
            INNER JOIN aiolia.subscription_plans sp ON sp.id = os.plan_id
            WHERE si.id IN ($placeholders)
        ";
        
        $results = $connection->fetchAllKeyValue($sql, $invoiceIds);
        
        return $results;
    }

    /**
     * @deprecated Utiliser getPlanTierForInvoice() à la place
     */
    public function getOrganizerTypeForInvoice(SubscriptionInvoice $invoice): ?string
    {
        return $this->getPlanTierForInvoice($invoice);
    }

    /**
     * @deprecated Utiliser getPlanTiersForInvoices() à la place
     */
    public function getOrganizerTypesForInvoices(array $invoices): array
    {
        return $this->getPlanTiersForInvoices($invoices);
    }
}

