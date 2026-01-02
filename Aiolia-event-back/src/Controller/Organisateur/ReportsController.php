<?php

namespace App\Controller\Organisateur;

use App\Repository\Organisateur\OrganizerProfileRepository;
use App\Service\Organisateur\ReportsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/organisateur/reports')]
#[IsGranted('ROLE_ORGANIZER')]
class ReportsController extends AbstractController
{
    public function __construct(
        private readonly OrganizerProfileRepository $organizerProfileRepository,
        private readonly ReportsService $reportsService,
    ) {
    }

    // Ajoutez cette méthode dans ReportsController

// Version originale avec filtres
    #[Route('/export/csv', name: 'organisateur_reports_export_csv', methods: ['GET'])]
    public function exportCsv(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $organizerProfile = $this->organizerProfileRepository->findByUser($user);
        if (!$organizerProfile) {
            throw $this->createAccessDeniedException('Profil organisateur introuvable pour cet utilisateur.');
        }

        // Normalisation des dates de filtre
        $dateFromParam = $request->query->get('date_from');
        $dateToParam = $request->query->get('date_to');
        
        $dateFromParam = ($dateFromParam === null || $dateFromParam === '') ? null : trim($dateFromParam);
        $dateToParam = ($dateToParam === null || $dateToParam === '') ? null : trim($dateToParam);
        
        $dateFilters = $this->reportsService->normalizeDateFilters(
            $dateFromParam,
            $dateToParam
        );
        $dateFrom = $dateFilters['dateFrom'];
        $dateTo = $dateFilters['dateTo'];

        // Récupération des données via le service
        $reportsData = $this->reportsService->getReportsData(
            $organizerProfile,
            $user,
            $dateFrom,
            $dateTo
        );

        // Création du CSV
        $csvData = "Rapport des événements - " . date('d/m/Y H:i:s') . "\n";
        
        // Période
        $periodLabel = $this->reportsService->getPeriodLabel($dateFrom, $dateTo);
        $csvData .= "Période: $periodLabel\n\n";
        
        // En-têtes
        $csvData .= "Événement;Date début;Date fin;Lieu;CA (Ar);Vues;Participants;Billets vendus;Capacité;Taux remplissage;Statut\n";
        
        // Données
        foreach ($reportsData['reports'] as $report) {
            $event = $report['event'];
            
            // Lieu
            $lieu = '';
            if ($event->getLieu()) {
                $lieu = $event->getLieu()->getNom();
                if ($event->getLieu()->getVille()) {
                    $lieu .= ' - ' . $event->getLieu()->getVille();
                }
            } elseif ($event->getNomLieuTexte()) {
                $lieu = $event->getNomLieuTexte();
            }
            
            // Dates
            $dateDebut = $event->getCommenceLe() ? $event->getCommenceLe()->format('d/m/Y H:i') : '';
            $dateFin = $event->getSeTermineLe() ? $event->getSeTermineLe()->format('d/m/Y H:i') : '';
            
            // Taux de remplissage
            $tauxRemplissage = number_format($report['occupancy_rate'], 1, ',', ' ') . '%';
            
            // Statut
            $statut = match($event->getStatut()) {
                'published' => 'Publié',
                'draft' => 'Brouillon',
                default => $event->getStatut(),
            };
            
            $csvData .= sprintf(
                '%s;%s;%s;%s;%s;%s;%s;%s;%s;%s;%s',
                str_replace(';', ',', $event->getTitre()), // Échapper les points-virgules
                $dateDebut,
                $dateFin,
                str_replace(';', ',', $lieu),
                number_format($report['revenue'], 0, ',', ' '),
                number_format($report['views'], 0, ',', ' '),
                number_format($report['participants'], 0, ',', ' '),
                number_format($report['sold_tickets'], 0, ',', ' '),
                number_format($report['total_capacity'], 0, ',', ' '),
                $tauxRemplissage,
                $statut
            ) . "\n";
        }
        
        // Totaux
        $csvData .= "\n";
        $csvData .= "TOTAUX;;;;" . 
            number_format($reportsData['totals']['revenue'], 0, ',', ' ') . ";" .
            number_format($reportsData['totals']['views'], 0, ',', ' ') . ";" .
            number_format($reportsData['totals']['participants'], 0, ',', ' ') . ";" .
            number_format($reportsData['totals']['sold_tickets'], 0, ',', ' ') . ";" .
            number_format($reportsData['totals']['total_capacity'], 0, ',', ' ') . ";" .
            number_format($reportsData['totals']['occupancy_rate'], 1, ',', ' ') . "%;\n";
        
        // Nom du fichier
        $filename = 'rapport-evenements-' . date('Y-m-d-His') . '.csv';
        
        // Réponse
        $response = new Response($csvData);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        
        return $response;
    }

    // NOUVELLE MÉTHODE POUR TOUTES LES DONNÉES SANS FILTRE
    #[Route('/export/csv/all', name: 'organisateur_reports_export_csv_all', methods: ['GET'])]
    public function exportCsvAll(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $organizerProfile = $this->organizerProfileRepository->findByUser($user);
        if (!$organizerProfile) {
            throw $this->createAccessDeniedException('Profil organisateur introuvable pour cet utilisateur.');
        }

        // Récupération de TOUTES les données SANS filtre de période (passer null pour les dates)
        $reportsData = $this->reportsService->getReportsData(
            $organizerProfile,
            $user,
            null, // Pas de date de début
            null  // Pas de date de fin
        );

        // Création du CSV
        $csvData = "Rapport complet des événements - " . date('d/m/Y H:i:s') . "\n";
        $csvData .= "Période: Toutes les données\n\n";
        
        // En-têtes
        $csvData .= "Événement;Date début;Date fin;Lieu;CA (Ar);Vues;Participants;Billets vendus;Capacité;Taux remplissage;Statut\n";
        
        // Données
        foreach ($reportsData['reports'] as $report) {
            $event = $report['event'];
            
            // Lieu
            $lieu = '';
            if ($event->getLieu()) {
                $lieu = $event->getLieu()->getNom();
                if ($event->getLieu()->getVille()) {
                    $lieu .= ' - ' . $event->getLieu()->getVille();
                }
            } elseif ($event->getNomLieuTexte()) {
                $lieu = $event->getNomLieuTexte();
            }
            
            // Dates
            $dateDebut = $event->getCommenceLe() ? $event->getCommenceLe()->format('d/m/Y H:i') : '';
            $dateFin = $event->getSeTermineLe() ? $event->getSeTermineLe()->format('d/m/Y H:i') : '';
            
            // Taux de remplissage
            $tauxRemplissage = number_format($report['occupancy_rate'], 1, ',', ' ') . '%';
            
            // Statut
            $statut = match($event->getStatut()) {
                'published' => 'Publié',
                'draft' => 'Brouillon',
                default => $event->getStatut(),
            };
            
            $csvData .= sprintf(
                '%s;%s;%s;%s;%s;%s;%s;%s;%s;%s;%s',
                str_replace(';', ',', $event->getTitre()),
                $dateDebut,
                $dateFin,
                str_replace(';', ',', $lieu),
                number_format($report['revenue'], 0, ',', ' '),
                number_format($report['views'], 0, ',', ' '),
                number_format($report['participants'], 0, ',', ' '),
                number_format($report['sold_tickets'], 0, ',', ' '),
                number_format($report['total_capacity'], 0, ',', ' '),
                $tauxRemplissage,
                $statut
            ) . "\n";
        }
        
        // Totaux
        $csvData .= "\n";
        $csvData .= "TOTAUX;;;;" . 
            number_format($reportsData['totals']['revenue'], 0, ',', ' ') . ";" .
            number_format($reportsData['totals']['views'], 0, ',', ' ') . ";" .
            number_format($reportsData['totals']['participants'], 0, ',', ' ') . ";" .
            number_format($reportsData['totals']['sold_tickets'], 0, ',', ' ') . ";" .
            number_format($reportsData['totals']['total_capacity'], 0, ',', ' ') . ";" .
            number_format($reportsData['totals']['occupancy_rate'], 1, ',', ' ') . "%;\n";
        
        // Ajout des métriques supplémentaires si elles existent
        if (isset($reportsData['subscriptionExpenses'])) {
            $csvData .= "\nDÉPENSES D'ABONNEMENT: " . 
                number_format($reportsData['subscriptionExpenses'], 0, ',', ' ') . " Ar\n";
        }
        
        if (isset($reportsData['netRevenue'])) {
            $csvData .= "CA NET: " . 
                number_format($reportsData['netRevenue'], 0, ',', ' ') . " Ar\n";
        }
        
        if (isset($reportsData['publishedEventsCount'])) {
            $csvData .= "ÉVÉNEMENTS PUBLIÉS: " . $reportsData['publishedEventsCount'] . "\n";
        }
        
        // Nom du fichier
        $filename = 'rapport-complet-evenements-' . date('Y-m-d-His') . '.csv';
        
        // Réponse
        $response = new Response($csvData);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        
        return $response;
    }

    #[Route('/export/pdf', name: 'organisateur_reports_export_pdf', methods: ['GET'])]
    public function exportPdf(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $organizerProfile = $this->organizerProfileRepository->findByUser($user);
        if (!$organizerProfile) {
            throw $this->createAccessDeniedException('Profil organisateur introuvable pour cet utilisateur.');
        }

        // Normalisation des dates de filtre
        $dateFromParam = $request->query->get('date_from');
        $dateToParam = $request->query->get('date_to');
        
        $dateFromParam = ($dateFromParam === null || $dateFromParam === '') ? null : trim($dateFromParam);
        $dateToParam = ($dateToParam === null || $dateToParam === '') ? null : trim($dateToParam);
        
        $dateFilters = $this->reportsService->normalizeDateFilters(
            $dateFromParam,
            $dateToParam
        );
        $dateFrom = $dateFilters['dateFrom'];
        $dateTo = $dateFilters['dateTo'];

        // Récupération des données via le service
        $reportsData = $this->reportsService->getReportsData(
            $organizerProfile,
            $user,
            $dateFrom,
            $dateTo
        );

        $periodLabel = $this->reportsService->getPeriodLabel($dateFrom, $dateTo);

        // Configuration de Dompdf
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($options);

        // HTML pour le PDF
        // Dans la méthode exportPdf, modifiez le tableau de données :
        $html = $this->renderView('Organisateur/reports/pdf.html.twig', [
            'reports' => $reportsData['reports'],
            'totals' => $reportsData['totals'],
            'top3Events' => $reportsData['top3Events'],
            'periodLabel' => $periodLabel,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'subscriptionExpenses' => $reportsData['subscriptionExpenses'] ?? 0,
            'netRevenue' => $reportsData['netRevenue'] ?? 0,
            'publishedEventsCount' => $reportsData['publishedEventsCount'] ?? 0,
            'generatedAt' => new \DateTime(),
            'organizerProfile' => $organizerProfile,
            'user' => $user, 
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        // Nom du fichier
        $filename = 'rapport-evenements-' . date('Y-m-d-His') . '.pdf';

        // Retourner le PDF
        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }


    #[Route('', name: 'organisateur_reports_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $organizerProfile = $this->organizerProfileRepository->findByUser($user);
        if (!$organizerProfile) {
            throw $this->createAccessDeniedException('Profil organisateur introuvable pour cet utilisateur.');
        }

        // Normalisation des dates de filtre via le service
        // Récupérer les paramètres et normaliser les chaînes vides en null
        $dateFromParam = $request->query->get('date_from');
        $dateToParam = $request->query->get('date_to');
        
        $dateFromParam = ($dateFromParam === null || $dateFromParam === '') ? null : trim($dateFromParam);
        $dateToParam = ($dateToParam === null || $dateToParam === '') ? null : trim($dateToParam);
        
        $dateFilters = $this->reportsService->normalizeDateFilters(
            $dateFromParam,
            $dateToParam
        );
        $dateFrom = $dateFilters['dateFrom'];
        $dateTo = $dateFilters['dateTo'];

        // Récupération des données via le service
        $reportsData = $this->reportsService->getReportsData(
            $organizerProfile,
            $user,
            $dateFrom,
            $dateTo
        );

        // Pagination
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = 7;
        $totalItems = count($reportsData['reports']);
        $totalPages = max(1, (int) ceil($totalItems / $perPage));
        $page = min($page, $totalPages);

        $offset = ($page - 1) * $perPage;
        $paginatedReports = array_slice($reportsData['reports'], $offset, $perPage);

        $pagination = [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $totalItems,
            'pages' => $totalPages,
        ];

        $periodLabel = $this->reportsService->getPeriodLabel($dateFrom, $dateTo);

        return $this->render('Organisateur/reports/index.html.twig', [
            'reports' => $paginatedReports,
            'totals' => $reportsData['totals'],
            'top3Events' => $reportsData['top3Events'],
            'pagination' => $pagination,
            'periodLabel' => $periodLabel,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'subscriptionExpenses' => $reportsData['subscriptionExpenses'],
            'netRevenue' => $reportsData['netRevenue'],
            'publishedEventsCount' => $reportsData['publishedEventsCount'],
        ]);
    }
}

