<?php

namespace App\Service;

use App\Repository\StatisticsRepository;

/**
 * Service pour calculer les statistiques de la plateforme
 * 
 * Ce service utilise StatisticsRepository pour récupérer les données
 * et applique les formules de calcul métier.
 */
class StatisticsService
{
    public function __construct(
        private StatisticsRepository $statisticsRepository
    ) {
    }

    /**
     * Récupère toutes les statistiques pour la page de statistiques
     * 
     * @param \DateTimeInterface|null $dateFrom Date de début du filtre
     * @param \DateTimeInterface|null $dateTo Date de fin du filtre
     * @param string|null $plan Filtre par plan (Basic, Pro, Entreprise)
     * @return array Structure complète des statistiques
     */
    public function getAllStatistics(?\DateTimeInterface $dateFrom = null, ?\DateTimeInterface $dateTo = null, ?string $plan = null, ?int $organizerId = null): array
    {
        return [
            'counts' => $this->getCounts($dateFrom, $dateTo, $plan),
            'organizers' => $this->getOrganizersStatistics($dateFrom, $dateTo, $plan),
            'subscriptions' => $this->getSubscriptionsStatistics($dateFrom, $dateTo, $plan),
            'tax' => $this->getTaxStatistics(0.20, 0.05, $dateFrom, $dateTo, $plan),
            'fiscal' => $this->getFiscalStatistics($dateFrom, $dateTo, $plan),
            'unpaid' => $this->getUnpaidStatistics($dateFrom, $dateTo, $plan, $organizerId),
            'payment_methods' => $this->getPaymentMethodsStatistics($dateFrom, $dateTo, $plan, $organizerId),
        ];
    }

    /**
     * Récupère les compteurs globaux
     * 
     * @param \DateTimeInterface|null $dateFrom Date de début du filtre
     * @param \DateTimeInterface|null $dateTo Date de fin du filtre
     * @param string|null $plan Filtre par plan
     * @return array ['organizers' => int, 'paid_invoices' => int, 'active_subscriptions' => int, 'subscription_revenue_total' => float]
     */
    public function getCounts(?\DateTimeInterface $dateFrom = null, ?\DateTimeInterface $dateTo = null, ?string $plan = null): array
    {
        return [
            'organizers' => $this->statisticsRepository->countOrganizers(),
            'paid_invoices' => $this->statisticsRepository->countPaidInvoices($dateFrom, $dateTo, $plan),
            'active_subscriptions' => $this->statisticsRepository->countActiveSubscriptions(),
            'subscription_revenue_total' => $this->statisticsRepository->getSubscriptionRevenueTotal($dateFrom, $dateTo, $plan),
        ];
    }

    /**
     * Récupère les statistiques des organisateurs
     * 
     * @param \DateTimeInterface|null $dateFrom Date de début du filtre
     * @param \DateTimeInterface|null $dateTo Date de fin du filtre
     * @param string|null $plan Filtre par plan
     * @return array [
     *     'subscription_revenue_total' => float,
     *     'plans' => ['labels' => [], 'revenue_values' => []],
     *     'top_payers_labels' => [],
     *     'top_payers_values' => []
     * ]
     */
    public function getOrganizersStatistics(?\DateTimeInterface $dateFrom = null, ?\DateTimeInterface $dateTo = null, ?string $plan = null): array
    {
        $revenueByPlan = $this->statisticsRepository->getRevenueByPlan($dateFrom, $dateTo, $plan);
        $topPayers = $this->statisticsRepository->getTopPayers(10, 30, $dateFrom, $dateTo, $plan);
        
        return [
            'subscription_revenue_total' => $this->statisticsRepository->getSubscriptionRevenueTotal($dateFrom, $dateTo, $plan),
            'plans' => [
                'labels' => $revenueByPlan['labels'],
                'revenue_values' => $revenueByPlan['revenue_values'],
            ],
            'top_payers_labels' => $topPayers['labels'],
            'top_payers_values' => $topPayers['values'],
        ];
    }

    /**
     * Récupère les statistiques des abonnements
     * 
     * @param \DateTimeInterface|null $dateFrom Date de début du filtre
     * @param \DateTimeInterface|null $dateTo Date de fin du filtre
     * @param string|null $plan Filtre par plan
     * @return array [
     *     'timeseries' => ['labels' => [], 'values' => []],
     *     'revenue_by_plan_by_month' => ['labels' => [], 'plans' => []]
     * ]
     */
    public function getSubscriptionsStatistics(?\DateTimeInterface $dateFrom = null, ?\DateTimeInterface $dateTo = null, ?string $plan = null): array
    {
        $evolution = $this->statisticsRepository->getSubscriptionsEvolution($dateFrom, $dateTo, $plan);
        $revenueByPlanByMonth = $this->statisticsRepository->getRevenueByPlanByMonth($dateFrom, $dateTo, $plan);
        
        return [
            'timeseries' => [
                'labels' => $evolution['labels'],
                'values' => $evolution['values'],
            ],
            'revenue_by_plan_by_month' => $revenueByPlanByMonth,
        ];
    }

    /**
     * Récupère les statistiques fiscales
     * 
     * Formules appliquées :
     * - Revenus bruts = Somme de toutes les factures payées (total_amount)
     * - TVA = Revenus bruts × taux_TVA (par défaut 20% = 0.20)
     * - Commissions plateforme = Revenus bruts × taux_commission (par défaut 5% = 0.05)
     * - Revenus nets = Revenus bruts - TVA - Commissions plateforme
     * 
     * @param float $vatRate Taux de TVA (par défaut 0.20 = 20%)
     * @param float $commissionRate Taux de commission plateforme (par défaut 0.05 = 5%)
     * @param \DateTimeInterface|null $dateFrom Date de début du filtre
     * @param \DateTimeInterface|null $dateTo Date de fin du filtre
     * @return array [
     *     'gross_revenue' => float,
     *     'vat' => float,
     *     'platform_commission' => float,
     *     'net_revenue' => float,
     *     'vat_rate' => float,
     *     'commission_rate' => float
     * ]
     */
    public function getTaxStatistics(float $vatRate = 0.20, float $commissionRate = 0.05, ?\DateTimeInterface $dateFrom = null, ?\DateTimeInterface $dateTo = null, ?string $plan = null): array
    {
        return $this->statisticsRepository->getTaxStatistics($vatRate, $commissionRate, $dateFrom, $dateTo, $plan);
    }

    /**
     * Formate un montant en devise MGA avec séparateurs de milliers
     * 
     * @param float $amount Montant à formater
     * @return string Montant formaté (ex: "150 000 MGA")
     */
    public function formatCurrency(float $amount, string $currency = 'MGA'): string
    {
        return number_format($amount, 0, ',', ' ') . ' ' . $currency;
    }

    /**
     * Formate un montant en euros avec séparateurs de milliers
     * 
     * @param float $amount Montant à formater
     * @return string Montant formaté (ex: "1 234,56 €")
     */
    public function formatCurrencyEuro(float $amount): string
    {
        return number_format($amount, 2, ',', ' ') . ' €';
    }

    /**
     * Récupère les statistiques fiscales (HT, TVA, TTC par mois)
     * 
     * Formules appliquées dans le métier :
     * - HT = TTC / 1,2
     * - TVA = TTC - HT
     * - TTC = HT × 1,2 (utilisé pour la validation)
     * 
     * @param \DateTimeInterface|null $dateFrom Date de début du filtre
     * @param \DateTimeInterface|null $dateTo Date de fin du filtre
     * @param string|null $plan Filtre par plan
     * @return array [
     *     'by_month' => ['labels' => [], 'ht_values' => [], 'tva_values' => [], 'ttc_values' => []],
     *     'top_vat_contributors' => ['labels' => [], 'vat_values' => []],
     *     'totals' => ['ht_total' => float, 'tva_total' => float, 'ttc_total' => float],
     *     'organizers_who_paid' => int
     * ]
     */
    public function getFiscalStatistics(?\DateTimeInterface $dateFrom = null, ?\DateTimeInterface $dateTo = null, ?string $plan = null): array
    {
        $fiscalByMonth = $this->statisticsRepository->getFiscalStatisticsByMonth($dateFrom, $dateTo, $plan);
        $topVatContributors = $this->statisticsRepository->getTopVatContributors(5, $dateFrom, $dateTo, $plan);
        
        // Calculer HT et TVA à partir de TTC
        // HT = TTC / 1,2
        // TVA = TTC - HT
        $htValues = [];
        $tvaValues = [];
        
        foreach ($fiscalByMonth['ttc_values'] as $ttc) {
            $ht = $ttc / 1.2;
            $tva = $ttc - $ht;
            $htValues[] = $ht;
            $tvaValues[] = $tva;
        }
        
        // Calculer les totaux
        $htTotal = array_sum($htValues);
        $tvaTotal = array_sum($tvaValues);
        $ttcTotal = array_sum($fiscalByMonth['ttc_values']);
        
        // Calculer HT et TVA pour les top contributeurs
        $topVatValues = [];
        foreach ($topVatContributors['ttc_values'] as $ttc) {
            $ht = $ttc / 1.2;
            $tva = $ttc - $ht;
            $topVatValues[] = $tva; // On affiche la TVA pour le top contributeurs
        }
        
        // Compter les organisateurs ayant payé
        $organizersWhoPaid = $this->statisticsRepository->countOrganizersWhoPaid($dateFrom, $dateTo, $plan);
        
        return [
            'by_month' => [
                'labels' => $fiscalByMonth['labels'],
                'ht_values' => $htValues,
                'tva_values' => $tvaValues,
                'ttc_values' => $fiscalByMonth['ttc_values'],
            ],
            'top_vat_contributors' => [
                'labels' => $topVatContributors['labels'],
                'vat_values' => $topVatValues,
            ],
            'totals' => [
                'ht_total' => $htTotal,
                'tva_total' => $tvaTotal,
                'ttc_total' => $ttcTotal,
            ],
            'organizers_who_paid' => $organizersWhoPaid,
        ];
    }

    /**
     * Récupère les statistiques des factures impayées
     * 
     * @param \DateTimeInterface|null $dateFrom Date de début du filtre
     * @param \DateTimeInterface|null $dateTo Date de fin du filtre
     * @param string|null $plan Filtre par plan
     * @param int|null $organizerId Filtre par organisateur
     * @return array
     */
    public function getUnpaidStatistics(?\DateTimeInterface $dateFrom = null, ?\DateTimeInterface $dateTo = null, ?string $plan = null, ?int $organizerId = null): array
    {
        return $this->statisticsRepository->getUnpaidStatistics($dateFrom, $dateTo, $plan, $organizerId);
    }

    /**
     * Récupère les statistiques par méthode de paiement
     * 
     * @param \DateTimeInterface|null $dateFrom Date de début du filtre
     * @param \DateTimeInterface|null $dateTo Date de fin du filtre
     * @param string|null $plan Filtre par plan
     * @param int|null $organizerId Filtre par organisateur
     * @return array
     */
    public function getPaymentMethodsStatistics(?\DateTimeInterface $dateFrom = null, ?\DateTimeInterface $dateTo = null, ?string $plan = null, ?int $organizerId = null): array
    {
        return $this->statisticsRepository->getPaymentMethodsStatistics($dateFrom, $dateTo, $plan, $organizerId);
    }
}

