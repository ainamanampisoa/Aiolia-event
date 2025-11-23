<?php

namespace App\Service\Admin;

use App\Repository\Admin\StatisticsRepository;

/**
 * Service pour calculer les prévisions de revenus
 */
class RevenueForecastService
{
    public function __construct(
        private StatisticsRepository $statisticsRepository
    ) {
    }

    /**
     * Calcule la prévision de revenus pour les N prochains mois
     * 
     * @param int $months Nombre de mois à prévoir (défaut: 3)
     * @param \DateTimeInterface|null $baseDate Date de base pour le calcul (défaut: aujourd'hui)
     * @return array [
     *     'forecast' => float, // Prévision totale
     *     'monthly_forecast' => array, // Prévisions par mois
     *     'trend' => float, // Tendance (%)
     *     'confidence' => float // Niveau de confiance (0-1)
     * ]
     */
    public function getRevenueForecast(int $months = 3, ?\DateTimeInterface $baseDate = null): array
    {
        if ($baseDate === null) {
            $baseDate = new \DateTime();
        }

        // Récupérer les revenus des 6 derniers mois pour calculer la tendance
        $endDate = new \DateTime($baseDate->format('Y-m-d H:i:s'));
        $startDate = new \DateTime($baseDate->format('Y-m-d H:i:s'));
        $startDate->modify('-6 months');

        $fiscalStats = $this->statisticsRepository->getFiscalStatisticsByMonth($startDate, $endDate);
        
        $revenues = $fiscalStats['ttc_values'] ?? [];
        
        if (empty($revenues) || count($revenues) < 3) {
            // Pas assez de données pour une prévision fiable
            return [
                'forecast' => 0,
                'monthly_forecast' => [],
                'trend' => 0,
                'confidence' => 0,
            ];
        }

        // Calculer la moyenne mobile sur 3 mois
        $last3Months = array_slice($revenues, -3);
        $average3Months = array_sum($last3Months) / count($last3Months);

        // Calculer la tendance (moyenne des 3 derniers vs moyenne des 3 précédents)
        if (count($revenues) >= 6) {
            $previous3Months = array_slice($revenues, -6, 3);
            $averagePrevious3 = array_sum($previous3Months) / count($previous3Months);
            
            if ($averagePrevious3 > 0) {
                $trend = (($average3Months - $averagePrevious3) / $averagePrevious3) * 100;
            } else {
                $trend = 0;
            }
        } else {
            $trend = 0;
        }

        // Calculer la prévision mensuelle
        $monthlyForecast = [];
        $currentMonth = new \DateTime($baseDate->format('Y-m-d H:i:s'));
        $currentMonth->modify('first day of this month');
        
        for ($i = 1; $i <= $months; $i++) {
            $forecastMonth = new \DateTime($currentMonth->format('Y-m-d H:i:s'));
            $forecastMonth->modify("+{$i} month");
            
            // Prévision basée sur la moyenne mobile avec ajustement de tendance
            // On applique la tendance de manière décroissante (la tendance s'estompe avec le temps)
            $trendFactor = 1 + ($trend / 100) * (1 - ($i - 1) * 0.1);
            $forecast = $average3Months * max(0.5, $trendFactor); // Minimum 50% de la moyenne
            
            $monthlyForecast[] = [
                'month' => $forecastMonth->format('Y-m'),
                'month_label' => $this->formatMonthLabel($forecastMonth),
                'forecast' => max(0, $forecast),
            ];
        }

        // Prévision totale
        $totalForecast = array_sum(array_column($monthlyForecast, 'forecast'));

        // Calculer le niveau de confiance basé sur la variance des données
        $variance = $this->calculateVariance($revenues);
        $mean = array_sum($revenues) / count($revenues);
        $coefficientOfVariation = $mean > 0 ? sqrt($variance) / $mean : 1;
        
        // Plus la variance est faible, plus la confiance est élevée
        $confidence = max(0, min(1, 1 - ($coefficientOfVariation * 0.5)));

        return [
            'forecast' => $totalForecast,
            'monthly_forecast' => $monthlyForecast,
            'trend' => $trend,
            'confidence' => $confidence,
            'average_3_months' => $average3Months,
        ];
    }

    /**
     * Calcule la prévision pour le mois suivant uniquement
     * 
     * @param \DateTimeInterface|null $baseDate Date de base
     * @return float Prévision pour le mois suivant
     */
    public function getNextMonthForecast(?\DateTimeInterface $baseDate = null): float
    {
        $forecast = $this->getRevenueForecast(1, $baseDate);
        return $forecast['forecast'] ?? 0;
    }

    /**
     * Calcule la variation en pourcentage entre deux périodes
     * 
     * @param \DateTimeInterface $period1Start
     * @param \DateTimeInterface $period1End
     * @param \DateTimeInterface $period2Start
     * @param \DateTimeInterface $period2End
     * @return float Variation en pourcentage
     */
    public function calculatePeriodVariation(
        \DateTimeInterface $period1Start,
        \DateTimeInterface $period1End,
        \DateTimeInterface $period2Start,
        \DateTimeInterface $period2End
    ): float {
        $period1Revenue = $this->statisticsRepository->getSubscriptionRevenueTotal($period1Start, $period1End);
        $period2Revenue = $this->statisticsRepository->getSubscriptionRevenueTotal($period2Start, $period2End);

        if ($period1Revenue == 0) {
            return $period2Revenue > 0 ? 100 : 0;
        }

        return (($period2Revenue - $period1Revenue) / $period1Revenue) * 100;
    }

    /**
     * Calcule la variance d'un tableau de valeurs
     */
    private function calculateVariance(array $values): float
    {
        if (empty($values)) {
            return 0;
        }

        $mean = array_sum($values) / count($values);
        $variance = 0;

        foreach ($values as $value) {
            $variance += pow($value - $mean, 2);
        }

        return $variance / count($values);
    }

    /**
     * Formate un label de mois en français
     */
    private function formatMonthLabel(\DateTimeInterface $date): string
    {
        $monthNames = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];

        $monthNum = (int) $date->format('n');
        $year = $date->format('Y');

        return $monthNames[$monthNum] . ' ' . $year;
    }
}

