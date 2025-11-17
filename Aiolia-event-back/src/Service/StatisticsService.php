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
        ];
    }

    /**
     * Récupère les compteurs globaux
     * 
     * @param \DateTimeInterface|null $dateFrom Date de début du filtre
     * @param \DateTimeInterface|null $dateTo Date de fin du filtre
     * @return array ['organizers' => int, 'paid_invoices' => int, 'active_subscriptions' => int]
     */
    public function getCounts(?\DateTimeInterface $dateFrom = null, ?\DateTimeInterface $dateTo = null): array
    {
        return [
            'organizers' => $this->statisticsRepository->countOrganizers(),
            'paid_invoices' => $this->statisticsRepository->countPaidInvoices($dateFrom, $dateTo),
            'active_subscriptions' => $this->statisticsRepository->countActiveSubscriptions(),
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
     *     'timeseries' => ['labels' => [], 'values' => []]
     * ]
     */
    public function getSubscriptionsStatistics(?\DateTimeInterface $dateFrom = null, ?\DateTimeInterface $dateTo = null): array
    {
        $evolution = $this->statisticsRepository->getSubscriptionsEvolution($dateFrom, $dateTo);
        
        return [
            'timeseries' => [
                'labels' => $evolution['labels'],
                'values' => $evolution['values'],
            ],
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
}

