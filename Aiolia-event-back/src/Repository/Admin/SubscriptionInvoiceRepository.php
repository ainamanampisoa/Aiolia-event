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
            $this->applySearchFilter($qb, $search, 'si', 'c');
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
            $qb->leftJoin('si.customer', 'c');
            $this->applySearchFilter($qb, $search, 'si', 'c');
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
     * Récupère le niveau (tier) du plan d'abonnement pour une facture
     * Utilise la vue vw_subscription_invoice_items pour optimiser les performances
     * Retourne null si le niveau n'est pas trouvé
     */
    public function getPlanTierForInvoice(SubscriptionInvoice $invoice): ?string
    {
        $connection = $this->getEntityManager()->getConnection();
        
        $sql = "
            SELECT DISTINCT plan_code
            FROM aiolia.vw_subscription_invoice_items
            WHERE invoice_id = :invoice_id
            LIMIT 1
        ";
        
        // Récupérer le plan_code et déduire le niveau depuis la vue
        $result = $connection->fetchOne($sql, ['invoice_id' => $invoice->getId()]);
        
        if ($result === false) {
            // Fallback sur la requête directe si la vue ne retourne rien
            $sql = "
                SELECT sp.niveau
                FROM aiolia.factures_abonnements si
                INNER JOIN aiolia.abonnements_organisateurs os ON os.id = si.id_abonnement
                INNER JOIN aiolia.plans_abonnements sp ON sp.id = os.id_plan
                WHERE si.id = :invoice_id
            ";
            $result = $connection->fetchOne($sql, ['invoice_id' => $invoice->getId()]);
        }
        
        return $result !== false ? $result : null;
    }

    /**
     * Récupère les niveaux (tiers) des plans d'abonnement pour plusieurs factures
     * Utilise la vue vw_subscription_invoice_items pour optimiser
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
            SELECT DISTINCT invoice_id, plan_code
            FROM aiolia.vw_subscription_invoice_items
            WHERE invoice_id IN ($placeholders)
        ";
        
        $results = $connection->fetchAllKeyValue($sql, $invoiceIds);
        
        // Si certains résultats manquent, utiliser le fallback
        if (count($results) < count($invoiceIds)) {
            $missingIds = array_diff($invoiceIds, array_keys($results));
            if (!empty($missingIds)) {
                $missingPlaceholders = implode(',', array_fill(0, count($missingIds), '?'));
                $fallbackSql = "
                    SELECT si.id, sp.niveau
                    FROM aiolia.factures_abonnements si
                    INNER JOIN aiolia.abonnements_organisateurs os ON os.id = si.id_abonnement
                    INNER JOIN aiolia.plans_abonnements sp ON sp.id = os.id_plan
                    WHERE si.id IN ($missingPlaceholders)
                ";
                $fallbackResults = $connection->fetchAllKeyValue($fallbackSql, array_values($missingIds));
                $results = array_merge($results, $fallbackResults);
            }
        }
        
        return $results;
    }

    /**
     * Récupère les informations complètes du plan pour une facture
     * Utilise la vue vw_subscription_invoice_items pour optimiser
     * Retourne un array avec 'niveau', 'periode_facturation', 'nom', 'code' ou null
     */
    public function getPlanInfoForInvoice(SubscriptionInvoice $invoice): ?array
    {
        $connection = $this->getEntityManager()->getConnection();
        
        // Utiliser la vue pour récupérer les infos du plan
        $sql = "
            SELECT DISTINCT
                plan_code,
                plan_name
            FROM aiolia.vw_subscription_invoice_items
            WHERE invoice_id = :invoice_id
            LIMIT 1
        ";
        
        $result = $connection->fetchAssociative($sql, ['invoice_id' => $invoice->getId()]);
        
        if ($result) {
            // Récupérer les infos complètes depuis la table plans_abonnements
            $planSql = "
                SELECT
                    niveau,
                    periode_facturation,
                    nom,
                    code
                FROM aiolia.plans_abonnements
                WHERE code = :plan_code
            ";
            $planInfo = $connection->fetchAssociative($planSql, ['plan_code' => $result['plan_code']]);

            return $planInfo ?: null;
        }

        // Fallback sur la requête directe
        $sql = "
            SELECT
                sp.niveau,
                sp.periode_facturation,
                sp.nom,
                sp.code
            FROM aiolia.factures_abonnements si
            INNER JOIN aiolia.abonnements_organisateurs os ON os.id = si.id_abonnement
            INNER JOIN aiolia.plans_abonnements sp ON sp.id = os.id_plan
            WHERE si.id = :invoice_id
        ";

        $result = $connection->fetchAssociative($sql, ['invoice_id' => $invoice->getId()]);

        return $result ?: null;
    }

    /**
     * Récupère les informations complètes des plans pour plusieurs factures
     * Utilise la vue vw_subscription_invoice_items pour optimiser
     * Retourne un array avec invoice_id => ['niveau' => ..., 'periode_facturation' => ..., 'nom' => ..., 'code' => ...]
     */
    public function getPlanInfosForInvoices(array $invoices): array
    {
        if (empty($invoices)) {
            return [];
        }
        
        $invoiceIds = array_map(fn($invoice) => $invoice->getId(), $invoices);
        $connection = $this->getEntityManager()->getConnection();
        
        $placeholders = implode(',', array_fill(0, count($invoiceIds), '?'));
        
        // Essayer d'abord avec la vue pour optimiser
        $invoicePlanCodes = [];
        try {
            $sql = "
                SELECT DISTINCT invoice_id, plan_code
                FROM aiolia.vw_subscription_invoice_items
                WHERE invoice_id IN ($placeholders)
            ";
            $invoicePlanCodes = $connection->fetchAllKeyValue($sql, $invoiceIds);
        } catch (\Exception $e) {
            // Si la vue n'existe pas ou échoue, on continue avec le fallback
        }
        
        // Fallback direct sur les tables si la vue ne retourne pas toutes les factures
        $missingInvoiceIds = array_diff($invoiceIds, array_keys($invoicePlanCodes));
        if (!empty($missingInvoiceIds)) {
            $missingPlaceholders = implode(',', array_fill(0, count($missingInvoiceIds), '?'));
            $fallbackSql = "
                SELECT 
                    fa.id AS invoice_id,
                    sp.code AS plan_code
                FROM aiolia.factures_abonnements fa
                INNER JOIN aiolia.abonnements_organisateurs ao ON ao.id = fa.id_abonnement
                INNER JOIN aiolia.plans_abonnements sp ON sp.id = ao.id_plan
                WHERE fa.id IN ($missingPlaceholders)
            ";
            $fallbackResults = $connection->fetchAllAssociative($fallbackSql, array_values($missingInvoiceIds));
            foreach ($fallbackResults as $row) {
                $invoicePlanCodes[$row['invoice_id']] = $row['plan_code'];
            }
        }
        
        if (empty($invoicePlanCodes)) {
            return [];
        }
        
        // Récupérer les infos complètes des plans
        $planCodes = array_unique(array_values($invoicePlanCodes));
        $planPlaceholders = implode(',', array_fill(0, count($planCodes), '?'));
        $planSql = "
            SELECT 
                code,
                niveau,
                periode_facturation,
                nom
            FROM aiolia.plans_abonnements
            WHERE code IN ($planPlaceholders)
        ";
        
        $plansInfo = $connection->fetchAllAssociative($planSql, $planCodes);
        $plansByCode = [];
        foreach ($plansInfo as $plan) {
            $plansByCode[$plan['code']] = [
                'niveau' => $plan['niveau'],
                'periode_facturation' => $plan['periode_facturation'],
                'nom' => $plan['nom'],
                'code' => $plan['code'],
            ];
        }
        
        // Mapper les factures aux infos de plans - TOUTES les factures doivent avoir un plan
        $planInfos = [];
        foreach ($invoicePlanCodes as $invoiceId => $planCode) {
            if (isset($plansByCode[$planCode])) {
                $planInfos[$invoiceId] = $plansByCode[$planCode];
            } else {
                // Si le plan n'est pas trouvé, on récupère directement depuis la base
                $directSql = "
                    SELECT 
                        sp.niveau,
                        sp.periode_facturation,
                        sp.nom,
                        sp.code
                    FROM aiolia.factures_abonnements fa
                    INNER JOIN aiolia.abonnements_organisateurs ao ON ao.id = fa.id_abonnement
                    INNER JOIN aiolia.plans_abonnements sp ON sp.id = ao.id_plan
                    WHERE fa.id = :invoice_id
                ";
                $directResult = $connection->fetchAssociative($directSql, ['invoice_id' => $invoiceId]);
                if ($directResult) {
                    $planInfos[$invoiceId] = [
                        'niveau' => $directResult['niveau'],
                        'periode_facturation' => $directResult['periode_facturation'],
                        'nom' => $directResult['nom'],
                        'code' => $directResult['code'],
                    ];
                }
            }
        }
        
        return $planInfos;
    }

    /**
     * Retourne la liste détaillée des lignes d'une facture d'abonnement
     */
    public function getInvoiceItemsForInvoice(SubscriptionInvoice $invoice): array
    {
        $connection = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT 
                item_id,
                plan_code,
                plan_name,
                description,
                quantite,
                prix_unitaire,
                montant_total
            FROM aiolia.vw_subscription_invoice_items
            WHERE invoice_id = :invoice_id
            ORDER BY item_id ASC
        ";

        return $connection->fetchAllAssociative($sql, [
            'invoice_id' => $invoice->getId(),
        ]);
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

    /**
     * Récupère le mode de paiement pour une facture
     * Utilise la vue vw_subscription_payments_detailed pour optimiser
     * Retourne null si aucun paiement n'est trouvé
     */
    public function getPaymentMethodForInvoice(SubscriptionInvoice $invoice): ?string
    {
        $connection = $this->getEntityManager()->getConnection();
        
        // Utiliser la vue pour récupérer le fournisseur de paiement
        $sql = "
            SELECT fournisseur
            FROM aiolia.vw_subscription_payments_detailed
            WHERE numero_facture = (
                SELECT numero_facture 
                FROM aiolia.factures_abonnements 
                WHERE id = :invoice_id
            )
            ORDER BY paye_le DESC NULLS LAST, modifie_le DESC
            LIMIT 1
        ";
        
        $result = $connection->fetchOne($sql, ['invoice_id' => $invoice->getId()]);
        
        if ($result === false) {
            // Fallback sur la requête directe
            $sql = "
                SELECT pa.fournisseur
                FROM aiolia.paiements_abonnements pa
                WHERE pa.id_facture = :invoice_id
                ORDER BY pa.paye_le DESC NULLS LAST, pa.cree_le DESC
                LIMIT 1
            ";
            $result = $connection->fetchOne($sql, ['invoice_id' => $invoice->getId()]);
        }
        
        return $result !== false ? $result : null;
    }

    /**
     * Récupère les factures en retard en utilisant la vue vw_subscription_invoices_overdue
     * 
     * @return SubscriptionInvoice[]
     */
    public function findOverdueInvoicesUsingView(): array
    {
        $connection = $this->getEntityManager()->getConnection();
        
        $sql = "
            SELECT id
            FROM aiolia.vw_subscription_invoices_overdue
        ";
        
        $invoiceIds = $connection->fetchFirstColumn($sql);
        
        if (empty($invoiceIds)) {
            return [];
        }
        
        return $this->createQueryBuilder('si')
            ->where('si.id IN (:ids)')
            ->setParameter('ids', $invoiceIds)
            ->getQuery()
            ->getResult();
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

    /**
     * Trouve une facture existante pour un abonnement et un mois donné
     * 
     * @param string $subscriptionId ID de l'abonnement
     * @param \DateTimeInterface $billingMonth Mois de facturation (1er du mois)
     * @return SubscriptionInvoice|null
     */
    public function findInvoiceForMonth(string $subscriptionId, \DateTimeInterface $billingMonth): ?SubscriptionInvoice
    {
        // Créer une instance DateTime pour pouvoir utiliser modify()
        $billingDate = $billingMonth instanceof \DateTime ? clone $billingMonth : new \DateTime($billingMonth->format('Y-m-d H:i:s'));
        $monthStart = (clone $billingDate)->modify('first day of this month')->setTime(0, 0, 0);
        $monthEnd = (clone $monthStart)->modify('+1 month');

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
     * Trouve toutes les factures en retard (non payées après l'échéance)
     * 
     * @param \DateTimeInterface $currentDate Date actuelle
     * @return SubscriptionInvoice[]
     */
    public function findOverdueInvoices(\DateTimeInterface $currentDate): array
    {
        // Calculer le 10ème jour du mois courant (date limite de paiement)
        $currentMonth = (int) $currentDate->format('n');
        $currentYear = (int) $currentDate->format('Y');
        $paymentDeadline = new \DateTimeImmutable(sprintf('%d-%d-10 23:59:59', $currentYear, $currentMonth));
        
        // Récupérer les factures du mois courant non payées et après l'échéance
        $firstDayOfMonth = new \DateTimeImmutable(sprintf('%d-%d-01 00:00:00', $currentYear, $currentMonth));
        $lastDayOfMonth = new \DateTimeImmutable(sprintf('%d-%d-%d 23:59:59', $currentYear, $currentMonth, (int) $firstDayOfMonth->format('t')));

        return $this->createQueryBuilder('si')
            ->where('si.status IN (:statuses)')
            ->andWhere('si.issuedAt >= :monthStart')
            ->andWhere('si.issuedAt <= :monthEnd')
            ->andWhere('si.paidAt IS NULL')
            ->andWhere(':currentDate > si.dueAt')
            ->setParameter('statuses', [SubscriptionInvoice::STATUS_DRAFT, SubscriptionInvoice::STATUS_ISSUED])
            ->setParameter('monthStart', $firstDayOfMonth)
            ->setParameter('monthEnd', $lastDayOfMonth)
            ->setParameter('currentDate', $currentDate)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve une facture existante pour un mois donné (par ID d'abonnement et mois)
     * 
     * @param int $subscriptionId ID de l'abonnement
     * @param string $billingMonth Mois de facturation au format 'Y-m-01'
     * @return SubscriptionInvoice|null
     */
    public function findInvoiceBySubscriptionAndMonth(int $subscriptionId, string $billingMonth): ?SubscriptionInvoice
    {
        $monthStart = new \DateTimeImmutable($billingMonth);
        $monthEnd = $monthStart->modify('+1 month');

        return $this->createQueryBuilder('si')
            ->where('si.subscriptionId = :subscriptionId')
            ->andWhere('si.issuedAt >= :monthStart')
            ->andWhere('si.issuedAt < :monthEnd')
            ->setParameter('subscriptionId', (string) $subscriptionId)
            ->setParameter('monthStart', $monthStart)
            ->setParameter('monthEnd', $monthEnd)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

