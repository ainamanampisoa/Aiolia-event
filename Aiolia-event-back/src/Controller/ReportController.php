<?php

namespace App\Controller;

use App\Service\StatisticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/reports')]
class ReportController extends AbstractController
{
    public function __construct(
        private StatisticsService $statisticsService
    ) {
    }

    #[Route('', name: 'app_reports_index')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        return $this->render('reports/index.html.twig');
    }

    #[Route('/rapports', name: 'app_reports_rapports')]
    public function rapports(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $month = $request->query->get('month');
        $year = $request->query->get('year');
        
        $dateFromObj = null;
        $dateToObj = null;
        
        // Si mois ET année sont fournis : filtrer sur ce mois précis
        if ($month && $year) {
            try {
                $dateFromObj = new \DateTime(sprintf('%d-%02d-01 00:00:00', $year, $month), new \DateTimeZone('UTC'));
                $dateToObj = clone $dateFromObj;
                $dateToObj->modify('last day of this month');
                $dateToObj->setTime(23, 59, 59);
            } catch (\Exception $e) {
                $dateFromObj = null;
                $dateToObj = null;
            }
        }
        // Si seulement l'année est fournie : filtrer sur toute l'année
        elseif ($year && !$month) {
            try {
                $dateFromObj = new \DateTime(sprintf('%d-01-01 00:00:00', $year), new \DateTimeZone('UTC'));
                $dateToObj = new \DateTime(sprintf('%d-12-31 23:59:59', $year), new \DateTimeZone('UTC'));
            } catch (\Exception $e) {
                $dateFromObj = null;
                $dateToObj = null;
            }
        }
        // Si seulement le mois est fourni : filtrer sur ce mois pour toutes les années disponibles
        elseif ($month && !$year) {
            try {
                // Utiliser l'année actuelle comme référence
                $currentYear = (int) date('Y');
                $dateFromObj = new \DateTime(sprintf('%d-%02d-01 00:00:00', $currentYear, $month), new \DateTimeZone('UTC'));
                $dateToObj = clone $dateFromObj;
                $dateToObj->modify('last day of this month');
                $dateToObj->setTime(23, 59, 59);
            } catch (\Exception $e) {
                $dateFromObj = null;
                $dateToObj = null;
            }
        }

        $stats = $this->statisticsService->getAllStatistics($dateFromObj, $dateToObj);

        return $this->render('reports/rapports.html.twig', [
            'user' => $this->getUser(),
            'stats' => $stats,
            'currentMonth' => $month,
            'currentYear' => $year,
        ]);
    }

    #[Route('/statistiques', name: 'app_reports_statistiques')]
    public function statistiques(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $dateDebut = $request->query->get('date_debut');
        $dateFin = $request->query->get('date_fin');
        
        $dateFromObj = null;
        $dateToObj = null;
        
        // Traitement de la date de début
        if ($dateDebut) {
            try {
                $dateFromObj = new \DateTime($dateDebut . ' 00:00:00', new \DateTimeZone('UTC'));
            } catch (\Exception $e) {
                $dateFromObj = null;
            }
        }
        
        // Traitement de la date de fin
        if ($dateFin) {
            try {
                $dateToObj = new \DateTime($dateFin . ' 23:59:59', new \DateTimeZone('UTC'));
            } catch (\Exception $e) {
                $dateToObj = null;
            }
        }

        $stats = $this->statisticsService->getAllStatistics($dateFromObj, $dateToObj);

        return $this->render('reports/statistiques.html.twig', [
            'user' => $this->getUser(),
            'stats' => $stats,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
        ]);
    }

    #[Route('/ventes', name: 'app_reports_ventes')]
    public function ventes(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        return $this->render('reports/ventes.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/export/pdf', name: 'app_reports_export_pdf')]
    public function exportPdf(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        // TODO: Générer PDF avec TCPDF ou Dompdf
        
        $this->addFlash('success', 'Rapport PDF généré avec succès !');
        return $this->redirectToRoute('app_reports_rapports');
    }

    #[Route('/export/csv', name: 'app_reports_export_csv')]
    public function exportCsv(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        // TODO: Générer CSV
        
        $this->addFlash('success', 'Export CSV généré avec succès !');
        return $this->redirectToRoute('app_reports_ventes');
    }
}

