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
        // Par défaut : janvier-décembre 2025
        $month = $request->query->getInt('month', 0); // 0 = tous les mois
        $year = $request->query->getInt('year', 2025);

        // Validation
        if ($month < 0 || $month > 12) {
            $month = 0;
        }
        if ($year < 2020 || $year > 2100) {
            $year = 2025;
        }

        $statistics = $this->statisticsService->getStatistics($month, $year);

        // Générer les titres des graphiques selon les filtres
        $monthNames = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];

        if ($month > 0 && $month <= 12) {
            $periodLabel = $monthNames[$month] . ' ' . $year;
        } else {
            $periodLabel = 'Jan-Déc ' . $year;
        }

        return $this->render('Admin/reports/statistiques.html.twig', [
            'statistics' => $statistics,
            'selectedMonth' => $month,
            'selectedYear' => $year,
            'periodLabel' => $periodLabel,
        ]);
    }
}

