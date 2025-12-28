<?php

namespace App\Service\Admin;

use App\Entity\SubscriptionInvoice;
use App\Entity\TicketInvoice;
use App\Repository\Admin\SubscriptionInvoiceRepository;
use App\Repository\Organisateur\TicketInvoiceRepository;
use Doctrine\ORM\EntityManagerInterface;

class BillingService
{
    public function __construct(
        private SubscriptionInvoiceRepository $subscriptionInvoiceRepository,
        private TicketInvoiceRepository $ticketInvoiceRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Récupère les factures avec filtres, tri et pagination
     */
    public function getInvoicesWithFilters(
        ?string $status = null,
        ?string $search = null,
        ?int $month = null,
        ?int $year = null,
        int $page = 1,
        int $perPage = 7
    ): array {
        $month = $this->validateMonth($month);
        $year = $this->validateYear($year);

        $fetchLimit = 10000;
        $monthFilter = $month > 0 ? $month : null;
        $yearFilter = $year > 0 ? $year : null;

        // Récupérer uniquement les factures d'abonnements
        $subscriptionInvoices = $this->subscriptionInvoiceRepository->findAllWithFilters(
            $status,
            $search,
            $monthFilter,
            $yearFilter,
            $fetchLimit,
            0
        );

        $allInvoices = $subscriptionInvoices;
        
        // Filtrer les factures d'abonnement pour exclure les mois avant le premier mois disponible
        $allInvoices = $this->filterInvoicesByFirstAvailableMonth($allInvoices, $month, $year);
        
        // Trier par date de facturation décroissante
        $allInvoices = $this->sortInvoices($allInvoices);
        
        // Recalculer le total après filtrage
        $totalInvoices = count($allInvoices);
        
        // Pagination
        $allInvoices = array_slice($allInvoices, ($page - 1) * $perPage, $perPage);

        // Identifier les types de factures et récupérer les informations
        $invoiceInfo = $this->processInvoicesInfo($allInvoices);

        return [
            'invoices' => $allInvoices,
            'invoiceInfo' => $invoiceInfo,
            'totalInvoices' => $totalInvoices,
        ];
    }

    /**
     * Calcule les statistiques des factures
     */
    public function calculateStats(?string $status, ?string $search, ?int $month, ?int $year): array
    {
        return [
            'total' => $this->countByStatus(null, $search, $month, $year),
            'paid' => $this->countByStatus('paid', $search, $month, $year),
            'pending' => $this->countByStatus('issued', $search, $month, $year)
                + $this->countByStatus('draft', $search, $month, $year)
                + $this->countByStatus('overdue', $search, $month, $year),
            'cancelled' => $this->countByStatus('void', $search, $month, $year) 
                + $this->countByStatus('refunded', $search, $month, $year),
        ];
    }

    /**
     * Récupère les factures précédentes d'un organisateur
     */
    public function getPreviousInvoices(SubscriptionInvoice $currentInvoice, $organizer): array
    {
        return $this->subscriptionInvoiceRepository->createQueryBuilder('si')
            ->where('si.customer = :customer')
            ->andWhere('si.id != :currentId')
            ->andWhere('si.createdAt < :currentDate')
            ->setParameter('customer', $organizer)
            ->setParameter('currentId', $currentInvoice->getId())
            ->setParameter('currentDate', $currentInvoice->getCreatedAt())
            ->orderBy('si.createdAt', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    /**
     * Enrichit les factures avec les informations de plan
     */
    public function enrichInvoicesWithPlanInfo(array $invoices): array
    {
        $result = [];
        foreach ($invoices as $invoice) {
            $planInfo = $this->subscriptionInvoiceRepository->getPlanInfoForInvoice($invoice);
            $result[] = [
                'invoice' => $invoice,
                'planInfo' => $planInfo,
                'planTier' => $planInfo['niveau'] ?? null,
            ];
        }
        return $result;
    }

    /**
     * Vérifie si le plan a changé entre la facture actuelle et les précédentes
     */
    public function checkPlanChange(?array $currentPlanInfo, array $previousInvoices): bool
    {
        if (empty($previousInvoices) || empty($currentPlanInfo)) {
            return false;
        }

        $previousPlanInfo = $previousInvoices[0]['planInfo'] ?? null;
        if (!$previousPlanInfo) {
            return false;
        }

        return ($previousPlanInfo['niveau'] ?? null) !== ($currentPlanInfo['niveau'] ?? null)
            || ($previousPlanInfo['periode_facturation'] ?? null) !== ($currentPlanInfo['periode_facturation'] ?? null);
    }

    /**
     * Valide le mois
     */
    private function validateMonth(int $month): int
    {
        return ($month >= 1 && $month <= 12) ? $month : 0;
    }

    /**
     * Valide l'année
     */
    private function validateYear(int $year): int
    {
        if ($year === 0) {
            return 0;
        }
        return ($year >= 2020 && $year <= 2100) ? $year : 0;
    }

    /**
     * Filtre les factures pour exclure celles avant le premier mois disponible
     */
    private function filterInvoicesByFirstAvailableMonth(array $invoices, int $month, int $year): array
    {
        if ($year === 0) {
            return $invoices;
        }

        // Obtenir le premier mois disponible pour les factures d'abonnements
        $firstAvailableMonth = $this->subscriptionInvoiceRepository->getFirstAvailableMonthForYear($year);
        
        // Forcer le premier mois à juin 2025 si c'est l'année 2025
        if ($year === 2025 && $firstAvailableMonth < 6) {
            $firstAvailableMonth = 6;
        }
        
        if ($firstAvailableMonth <= 1 && $year !== 2025) {
            return $invoices;
        }

        if ($month > 0 && $month < $firstAvailableMonth) {
            return [];
        }

        return array_filter($invoices, function($invoice) use ($firstAvailableMonth, $year, $month) {
            if (!($invoice instanceof SubscriptionInvoice)) {
                return false;
            }
            
            $invoiceYear = null;
            $invoiceMonth = null;
            
            $billingMonth = $invoice->getBillingMonth();
            if ($billingMonth instanceof \DateTimeInterface) {
                $invoiceYear = (int) $billingMonth->format('Y');
                $invoiceMonth = (int) $billingMonth->format('n');
            }
            
            // Exclure toutes les factures de mai 2025
            if ($invoiceYear === 2025 && $invoiceMonth === 5) {
                return false;
            }
            
            if ($invoiceYear === $year && $invoiceMonth !== null && $invoiceMonth < $firstAvailableMonth) {
                return false;
            }
            
            if ($year === 2025 && $firstAvailableMonth >= 6 && $invoiceYear === 2025 && $invoiceMonth !== null && $invoiceMonth < 6) {
                return false;
            }
            
            if ($month > 0 && $invoiceMonth !== $month) {
                return false;
            }
            
            return true;
        });
    }

    /**
     * Trie les factures par date de facturation décroissante
     */
    private function sortInvoices(array $invoices): array
    {
        usort($invoices, function($a, $b) {
            if ($a instanceof SubscriptionInvoice && $b instanceof SubscriptionInvoice) {
                $dateA = $a->getBillingMonth();
                $dateB = $b->getBillingMonth();
                
                if (!$dateA) $dateA = $a->getIssuedAt();
                if (!$dateB) $dateB = $b->getIssuedAt();
                
                if (!$dateA) $dateA = $a->getCreatedAt();
                if (!$dateB) $dateB = $b->getCreatedAt();
            } else {
                $dateA = $a->getIssuedAt();
                $dateB = $b->getIssuedAt();
                
                if (!$dateA) $dateA = $a->getCreatedAt();
                if (!$dateB) $dateB = $b->getCreatedAt();
            }
            
            return $dateB <=> $dateA;
        });
        
        return $invoices;
    }

    /**
     * Traite les informations des factures (types, dates, plans)
     */
    private function processInvoicesInfo(array $invoices): array
    {
        $invoiceIds = array_map(fn($inv) => $inv->getId(), $invoices);
        $connection = $this->entityManager->getConnection();

        $subscriptionInvoiceIds = [];
        if (!empty($invoiceIds)) {
            $sql = "
                SELECT DISTINCT id_facture 
                FROM aiolia.transactions_paiement_mobile 
                WHERE id_facture IN (:invoice_ids)
            ";
            $results = $connection->fetchAllAssociative(
                $sql,
                ['invoice_ids' => $invoiceIds],
                ['invoice_ids' => \Doctrine\DBAL\ArrayParameterType::INTEGER]
            );
            foreach ($results as $row) {
                $subscriptionInvoiceIds[(string) $row['id_facture']] = true;
            }
        }

        $types = [];
        $dates = [];
        $subscriptionInvoices = [];

        foreach ($invoices as $invoice) {
            $invoiceId = (string) $invoice->getId();
            
            if ($invoice instanceof SubscriptionInvoice || isset($subscriptionInvoiceIds[$invoiceId])) {
                $types[$invoiceId] = 'subscription';
                if ($invoice instanceof SubscriptionInvoice) {
                    $subscriptionInvoices[] = $invoice;
                    $billingMonth = $invoice->getBillingMonth();
                    if ($billingMonth instanceof \DateTimeInterface) {
                        $normalizedDate = \DateTimeImmutable::createFromInterface($billingMonth)
                            ->modify('first day of this month')
                            ->setTime(0, 0, 0);
                        $dates[$invoiceId] = $normalizedDate;
                    } else {
                        $dates[$invoiceId] = $invoice->getIssuedAt();
                    }
                } else {
                    $dates[$invoiceId] = $invoice->getIssuedAt();
                }
            } else {
                $types[$invoiceId] = 'ticket';
                $dates[$invoiceId] = $invoice->getIssuedAt();
            }
        }

        $planInfos = [];
        if (!empty($subscriptionInvoices)) {
            $planInfos = $this->subscriptionInvoiceRepository->getPlanInfosForInvoices($subscriptionInvoices);
            
            foreach ($subscriptionInvoices as $invoice) {
                $invoiceId = (string) $invoice->getId();
                if (!isset($planInfos[$invoiceId]) || 
                    !is_array($planInfos[$invoiceId]) || 
                    !isset($planInfos[$invoiceId]['niveau']) ||
                    $planInfos[$invoiceId]['niveau'] === null ||
                    $planInfos[$invoiceId]['niveau'] === '') {
                    $planInfo = $this->subscriptionInvoiceRepository->getPlanInfoForInvoice($invoice);
                    if ($planInfo && is_array($planInfo) && isset($planInfo['niveau']) && 
                        $planInfo['niveau'] !== null && $planInfo['niveau'] !== '') {
                        $planInfos[$invoiceId] = $planInfo;
                    }
                }
            }
        }

        return [
            'types' => $types,
            'dates' => $dates,
            'planInfos' => $planInfos
        ];
    }

    /**
     * Compte les factures par statut
     */
    private function countByStatus(?string $status, ?string $search, ?int $month, ?int $year): int
    {
        return $this->subscriptionInvoiceRepository->countWithFilters($status, $search, $month, $year);
    }
}

