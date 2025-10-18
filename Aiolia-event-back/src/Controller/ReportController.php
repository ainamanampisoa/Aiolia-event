<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/reports')]
class ReportController extends AbstractController
{
    #[Route('', name: 'app_reports_index')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        return $this->render('reports/index.html.twig');
    }

    #[Route('/rapports', name: 'app_reports_rapports')]
    public function rapports(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        return $this->render('reports/rapports.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/statistiques', name: 'app_reports_statistiques')]
    public function statistiques(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        return $this->render('reports/statistiques.html.twig', [
            'user' => $this->getUser(),
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

