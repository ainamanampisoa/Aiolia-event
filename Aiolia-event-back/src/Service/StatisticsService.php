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
     * @return array Structure complète des statistiques
     */
    public function getAllStatistics(?\DateTimeInterface $dateFrom = null, ?\DateTimeInterface $dateTo = null): array
    {
        return [
            'counts' => $this->getCounts($dateFrom, $dateTo),
            'organizers' => $this->getOrganizersStatistics($dateFrom, $dateTo),
            'subscriptions' => $this->getSubscriptionsStatistics($dateFrom, $dateTo),
            'tax' => $this->getTaxStatistics(0.20, 0.05, $dateFrom, $dateTo),
            'fiscal' => $this->getFiscalStatistics($dateFrom, $dateTo),
        ];
    }

    /**
     * Récupère les compteurs globaux
     * 
     * @param \DateTimeInterface|null $dateFrom Date de début du filtre
     * @param \DateTimeInterface|null $dateTo Date de fin du filtre
     * @return array ['organizers' => int, 'paid_invoices' => int, 'active_subscriptions' => int, 'subscription_revenue_total' => float]
     */
    public function getCounts(?\DateTimeInterface $dateFrom = null, ?\DateTimeInterface $dateTo = null): array
    {
        return [
            'organizers' => $this->statisticsRepository->countOrganizers(),
            'paid_invoices' => $this->statisticsRepository->countPaidInvoices($dateFrom, $dateTo),
            'active_subscriptions' => $this->statisticsRepository->countActiveSubscriptions(),
            'subscription_revenue_total' => $this->statisticsRepository->getSubscriptionRevenueTotal($dateFrom, $dateTo),
        ];
    }

    /**
     * Récupère les statistiques des organisateurs
     * 
     * @param \DateTimeInterface|null $dateFrom Date de début du filtre
     * @param \DateTimeInterface|null $dateTo Date de fin du filtre
     * @return array [
     *     'subscription_revenue_total' => float,
     *     'plans' => ['labels' => [], 'revenue_values' => []],
     *     'top_payers_labels' => [],
     *     'top_payers_values' => []
     * ]
     */
    public function getOrganizersStatistics(?\DateTimeInterface $dateFrom = null, ?\DateTimeInterface $dateTo = null): array
    {
        $revenueByPlan = $this->statisticsRepository->getRevenueByPlan($dateFrom, $dateTo);
        $topPayers = $this->statisticsRepository->getTopPayers(10, 30, $dateFrom, $dateTo);
        
        return [
            'subscription_revenue_total' => $this->statisticsRepository->getSubscriptionRevenueTotal($dateFrom, $dateTo),
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
     * @return array [
     *     'timeseries' => ['labels' => [], 'values' => []],
     *     'revenue_by_plan_by_month' => ['labels' => [], 'plans' => []]
     * ]
     */
    public function getSubscriptionsStatistics(?\DateTimeInterface $dateFrom = null, ?\DateTimeInterface $dateTo = null): array
    {
        $evolution = $this->statisticsRepository->getSubscriptionsEvolution($dateFrom, $dateTo);
        $revenueByPlanByMonth = $this->statisticsRepository->getRevenueByPlanByMonth($dateFrom, $dateTo);
        
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
    public function getTaxStatistics(float $vatRate = 0.20, float $commissionRate = 0.05, ?\DateTimeInterface $dateFrom = null, ?\DateTimeInterface $dateTo = null): array
    {
        return $this->statisticsRepository->getTaxStatistics($vatRate, $commissionRate, $dateFrom, $dateTo);
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
     * @return array [
     *     'by_month' => ['labels' => [], 'ht_values' => [], 'tva_values' => [], 'ttc_values' => []],
     *     'top_vat_contributors' => ['labels' => [], 'vat_values' => []]
     * ]
     */
    public function getFiscalStatistics(?\DateTimeInterface $dateFrom = null, ?\DateTimeInterface $dateTo = null): array
    {
        $fiscalByMonth = $this->statisticsRepository->getFiscalStatisticsByMonth($dateFrom, $dateTo);
        $topVatContributors = $this->statisticsRepository->getTopVatContributors(10, $dateFrom, $dateTo);
        
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
        
        // Calculer HT et TVA pour les top contributeurs
        $topVatValues = [];
        foreach ($topVatContributors['ttc_values'] as $ttc) {
            $ht = $ttc / 1.2;
            $tva = $ttc - $ht;
            $topVatValues[] = $tva; // On affiche la TVA pour le top contributeurs
        }
        
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
        ];
    }
}

