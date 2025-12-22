<?php

namespace App\Repository\Admin;

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
            $this->applySearchFilter($qb, $search, 'si', 'c');
        }

        if ($dateFrom) {
            $dateFromStart = (clone $dateFrom)->setTime(0, 0, 0);
            $qb->andWhere('si.issuedAt >= :dateFrom')
                ->setParameter('dateFrom', $dateFromStart);
        }

        if ($dateTo) {
            $dateToEnd = (clone $dateTo)->setTime(23, 59, 59);
            $qb->andWhere('si.issuedAt <= :dateTo')
                ->setParameter('dateTo', $dateToEnd);
        }

        return $qb->orderBy('si.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countWithFilters(?string $status = null, ?string $search = null, ?\DateTime $dateFrom = null, ?\DateTime $dateTo = null): int
    {
        $qb = $this->createQueryBuilder('si')
            ->select('COUNT(si.id)');

        if ($status) {
            $qb->andWhere('si.status = :status')
                ->setParameter('status', $status);
        }

        if ($search) {
            $qb->leftJoin('si.customer', 'c');
            $this->applySearchFilter($qb, $search, 'si', 'c');
        }

        if ($dateFrom) {
            $dateFromStart = (clone $dateFrom)->setTime(0, 0, 0);
            $qb->andWhere('si.issuedAt >= :dateFrom')
                ->setParameter('dateFrom', $dateFromStart);
        }

        if ($dateTo) {
            $dateToEnd = (clone $dateTo)->setTime(23, 59, 59);
            $qb->andWhere('si.issuedAt <= :dateTo')
                ->setParameter('dateTo', $dateToEnd);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function findByInvoiceNumber(string $invoiceNumber): ?SubscriptionInvoice
    {
        return $this->createQueryBuilder('si')
            ->where('si.invoiceNumber = :number')
            ->setParameter('number', $invoiceNumber)
            ->getQuery()
            ->getOneOrNullResult();
    }

    // Récupérer le niveau de plan (tier) pour une facture d'abonnement
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

    // Récupérer les tiers de plans pour plusieurs factures
    public function getPlanTiersForInvoices(array $invoices): array
    {
        $tiers = [];
        foreach ($invoices as $invoice) {
            $tier = $this->getPlanTierForInvoice($invoice);
            if ($tier !== null) {
                $tiers[$invoice->getId()] = $tier;
            }
        }
        return $tiers;
    }

    // Récupérer les infos complètes du plan pour une facture
    // Utilise aussi les transactions pour récupérer les informations si nécessaire
    public function getPlanInfoForInvoice(SubscriptionInvoice $invoice): ?array
    {
        $connection = $this->getEntityManager()->getConnection();

        // Requête principale via les abonnements
        $sql = "
            SELECT 
                sp.niveau,
                sp.periode_facturation,
                sp.nom,
                sp.code
            FROM aiolia.factures_abonnements fi
            INNER JOIN aiolia.abonnements_organisateurs os ON os.id = fi.id_abonnement
            INNER JOIN aiolia.plans_abonnements sp ON sp.id = os.id_plan
            WHERE fi.id = :invoice_id
        ";

        $result = $connection->fetchAssociative($sql, ['invoice_id' => $invoice->getId()]);

        // Si pas de résultat, essayer via les transactions
        if (!$result) {
            $sql2 = "
                SELECT DISTINCT
                    sp.niveau,
                    sp.periode_facturation,
                    sp.nom,
                    sp.code
                FROM aiolia.factures_abonnements fi
                INNER JOIN aiolia.transactions_paiement_mobile tpm ON tpm.id_facture = fi.id
                INNER JOIN aiolia.abonnements_organisateurs os ON os.id = fi.id_abonnement
                INNER JOIN aiolia.plans_abonnements sp ON sp.id = os.id_plan
                WHERE fi.id = :invoice_id
            ";

            $result = $connection->fetchAssociative($sql2, ['invoice_id' => $invoice->getId()]);
        }

        return $result ?: null;
    }

    // Même principe pour plusieurs factures (optimisé avec une seule requête)
    // Utilise aussi les transactions pour identifier les factures d'abonnement
    public function getPlanInfosForInvoices(array $invoices): array
    {
        if (empty($invoices)) {
            return [];
        }

        $invoiceIds = array_map(function($invoice) {
            return $invoice->getId();
        }, $invoices);

        $connection = $this->getEntityManager()->getConnection();

        // Requête améliorée qui utilise aussi les transactions pour identifier les factures d'abonnement
        // et récupère les informations de plan via les abonnements
        $sql = "
            SELECT DISTINCT
                fi.id as invoice_id,
                sp.niveau,
                sp.periode_facturation,
                sp.nom,
                sp.code
            FROM aiolia.factures_abonnements fi
            LEFT JOIN aiolia.abonnements_organisateurs os ON os.id = fi.id_abonnement
            LEFT JOIN aiolia.plans_abonnements sp ON sp.id = os.id_plan
            LEFT JOIN aiolia.transactions_paiement_mobile tpm ON tpm.id_facture = fi.id
            WHERE fi.id IN (:invoice_ids)
        ";

        $results = $connection->fetchAllAssociative(
            $sql,
            ['invoice_ids' => $invoiceIds],
            ['invoice_ids' => \Doctrine\DBAL\ArrayParameterType::INTEGER]
        );

        $result = [];
        foreach ($results as $row) {
            // Normaliser l'ID en chaîne pour correspondre aux IDs Doctrine
            $invoiceId = (string) $row['invoice_id'];
            
            // Si on a des informations de plan, les utiliser
            if ($row['niveau'] !== null) {
                $result[$invoiceId] = [
                    'niveau' => $row['niveau'],
                    'periode_facturation' => $row['periode_facturation'],
                    'nom' => $row['nom'],
                    'code' => $row['code'],
                ];
            } else {
                // Si pas d'info de plan mais qu'on a une transaction, c'est quand même une facture d'abonnement
                // On peut essayer de récupérer les infos via la transaction
                $result[$invoiceId] = null;
            }
        }

        // Pour les factures sans plan trouvé, essayer de récupérer via les transactions
        $missingInvoiceIds = [];
        foreach ($invoiceIds as $id) {
            $idStr = (string) $id;
            if (!isset($result[$idStr]) || $result[$idStr] === null) {
                $missingInvoiceIds[] = $id;
            }
        }

        if (!empty($missingInvoiceIds)) {
            // Récupérer les infos via les transactions et les abonnements
            $sql2 = "
                SELECT DISTINCT
                    fi.id as invoice_id,
                    sp.niveau,
                    sp.periode_facturation,
                    sp.nom,
                    sp.code
                FROM aiolia.factures_abonnements fi
                INNER JOIN aiolia.transactions_paiement_mobile tpm ON tpm.id_facture = fi.id
                INNER JOIN aiolia.abonnements_organisateurs os ON os.id = fi.id_abonnement
                INNER JOIN aiolia.plans_abonnements sp ON sp.id = os.id_plan
                WHERE fi.id IN (:invoice_ids)
            ";

            $results2 = $connection->fetchAllAssociative(
                $sql2,
                ['invoice_ids' => $missingInvoiceIds],
                ['invoice_ids' => \Doctrine\DBAL\ArrayParameterType::INTEGER]
            );

            foreach ($results2 as $row) {
                $invoiceId = (string) $row['invoice_id'];
                if ($row['niveau'] !== null) {
                    $result[$invoiceId] = [
                        'niveau' => $row['niveau'],
                        'periode_facturation' => $row['periode_facturation'],
                        'nom' => $row['nom'],
                        'code' => $row['code'],
                    ];
                }
            }
        }

        return $result;
    }

    // Récupérer les lignes d’une facture via relations Doctrine
    public function getInvoiceItemsForInvoice(SubscriptionInvoice $invoice): array
    {
        // L'entité SubscriptionInvoice actuelle ne possède pas de relation vers des lignes de facture.
        // On retourne donc un tableau vide pour éviter les erreurs, en attendant une éventuelle
        // implémentation d'entités de lignes de facture.
        return [];
    }

    // Mode de paiement depuis l'entité SubscriptionInvoice
    public function getPaymentMethodForInvoice(SubscriptionInvoice $invoice): ?string
    {
        // L'entité SubscriptionInvoice expose déjà le mode de paiement via getPaymentMethod()
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

        if (strpos($searchTrimmed, '@') !== false) {
            $qb->leftJoin("$invoiceAlias.customer", $customerAlias)
                ->andWhere("$customerAlias.email LIKE :search")
                ->setParameter('search', $pattern);
            return;
        }

        $words = preg_split('/\s+/', $searchTrimmed);

        if (count($words) > 1) {
            $qb->leftJoin("$invoiceAlias.customer", $customerAlias)
                ->andWhere("$customerAlias.prenom LIKE :firstWord")
                ->andWhere("$customerAlias.nom LIKE :lastWord")
                ->setParameter('firstWord', '%' . $words[0] . '%')
                ->setParameter('lastWord', '%' . end($words) . '%');
        } else {
            $qb->leftJoin("$invoiceAlias.customer", $customerAlias)
                ->andWhere($qb->expr()->orX(
                    $qb->expr()->like("$customerAlias.prenom", ':search'),
                    $qb->expr()->like("$customerAlias.nom", ':search')
                ))
                ->setParameter('search', $pattern);
        }
    }

    public function findInvoiceForMonth(string $subscriptionId, \DateTimeInterface $billingMonth): ?SubscriptionInvoice
    {
        // Normalisation du mois de facturation : on crée des objets DateTimeImmutable
        $monthStart = \DateTimeImmutable::createFromInterface($billingMonth)
            ->modify('first day of this month')
            ->setTime(0, 0, 0);
        $monthEnd = $monthStart
            ->modify('+1 month');

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
}
