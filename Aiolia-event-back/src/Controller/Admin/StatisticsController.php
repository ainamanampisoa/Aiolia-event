<?php

namespace App\Controller\Admin;

use App\Service\Admin\StatisticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/reports/statistiques', name: 'app_reports_statistiques')]
#[IsGranted('ROLE_ADMIN')]
class StatisticsController extends AbstractController
{
    public function __construct(
        private StatisticsService $statisticsService
    ) {
    }

    #[Route('', name: '', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // Par défaut : tous les mois, toutes les années
        $month = $request->query->getInt('month', 0); // 0 = tous les mois
        $year = $request->query->getInt('year', 0); // 0 = toutes les années

        // Validation
        if ($month < 0 || $month > 12) {
            $month = 0;
        }
        // Accepter 0 pour "Toutes les années"
        if ($year !== 0 && ($year < 2020 || $year > 2100)) {
            $year = 0;
        }

        $statistics = $this->statisticsService->getDashboardStatistics($month, $year);
;

        // Générer les titres des graphiques selon les filtres
        $monthNames = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];

        if ($year === 0) {
            // Toutes les années
            if ($month > 0 && $month <= 12) {
                $periodLabel = $monthNames[$month] . ' (Toutes années)';
            } else {
                $periodLabel = 'Toutes périodes';
            }
        } else {
            // Année spécifique
            if ($month > 0 && $month <= 12) {
                $periodLabel = $monthNames[$month] . ' ' . $year;
            } else {
                $periodLabel = 'Jan-Déc ' . $year;
            }
        }

        return $this->render('Admin/reports/statistiques.html.twig', [
            'statistics' => $statistics,
            'selectedMonth' => $month,
            'selectedYear' => $year,
            'periodLabel' => $periodLabel,
        ]);
    }
}

