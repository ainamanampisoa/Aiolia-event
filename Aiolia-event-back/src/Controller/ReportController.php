<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\StatisticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/reports')]
class ReportController extends AbstractController
{
    public function __construct(
        private StatisticsService $statisticsService,
        private UserRepository $userRepository
    ) {
    }

    #[Route('', name: 'app_reports_index')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        return $this->render('admin/reports/index.html.twig');
    }

    #[Route('/rapports', name: 'app_reports_rapports')]
    public function rapports(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $month = $request->query->get('month');
        $year = $request->query->get('year');
        $dateFrom = $request->query->get('date_from');
        $dateTo = $request->query->get('date_to');
        $plan = $request->query->get('plan');
        $organizerId = $request->query->get('organizer') ? (int) $request->query->get('organizer') : null;
        
        $dateFromObj = null;
        $dateToObj = null;
        
        // Priorité : Si mois ET année sont fournis, utiliser ces filtres
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
        // Si seulement l'année est fournie
        elseif ($year && !$month) {
            try {
                $dateFromObj = new \DateTime(sprintf('%d-01-01 00:00:00', $year), new \DateTimeZone('UTC'));
                $dateToObj = new \DateTime(sprintf('%d-12-31 23:59:59', $year), new \DateTimeZone('UTC'));
            } catch (\Exception $e) {
                $dateFromObj = null;
                $dateToObj = null;
            }
        }
        // Sinon, utiliser les dates personnalisées
        else {
            if ($dateFrom) {
                try {
                    $dateFromObj = new \DateTime($dateFrom . ' 00:00:00', new \DateTimeZone('UTC'));
                } catch (\Exception $e) {
                    $dateFromObj = null;
                }
            }
            
            if ($dateTo) {
                try {
                    $dateToObj = new \DateTime($dateTo . ' 23:59:59', new \DateTimeZone('UTC'));
                } catch (\Exception $e) {
                    $dateToObj = null;
                }
            }
        }

        // Mapper le plan du formulaire vers le niveau en base de données
        $planFilter = null;
        if ($plan) {
            $planMapping = [
                'Basic' => 'basic',
                'Pro' => 'pro',
                'Entreprise' => 'enterprise'
            ];
            $planFilter = $planMapping[$plan] ?? null;
        }

        // Récupérer la liste des organisateurs
        $organizers = $this->userRepository->createQueryBuilder('u')
            ->where("u.role = 'organizer'")
            ->andWhere('u.statut = :statut')
            ->setParameter('statut', \App\Entity\User::STATUS_ACTIVE)
            ->orderBy('u.prenom', 'ASC')
            ->addOrderBy('u.nom', 'ASC')
            ->getQuery()
            ->getResult();

        $stats = $this->statisticsService->getAllStatistics($dateFromObj, $dateToObj, $planFilter, $organizerId);

        return $this->render('admin/reports/rapports.html.twig', [
            'user' => $this->getUser(),
            'stats' => $stats,
            'currentMonth' => $month,
            'currentYear' => $year,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'plan' => $plan,
            'currentOrganizer' => $organizerId,
            'organizers' => $organizers,
        ]);
    }

    #[Route('/statistiques', name: 'app_reports_statistiques')]
    public function statistiques(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $dateDebut = $request->query->get('date_debut');
        $dateFin = $request->query->get('date_fin');
        $month = $request->query->get('month');
        $year = $request->query->get('year');
        $plan = $request->query->get('plan');
        
        $dateFromObj = null;
        $dateToObj = null;
        
        // Priorité : Si mois ET année sont fournis, utiliser ces filtres
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
        // Si seulement l'année est fournie
        elseif ($year && !$month) {
            try {
                $dateFromObj = new \DateTime(sprintf('%d-01-01 00:00:00', $year), new \DateTimeZone('UTC'));
                $dateToObj = new \DateTime(sprintf('%d-12-31 23:59:59', $year), new \DateTimeZone('UTC'));
            } catch (\Exception $e) {
                $dateFromObj = null;
                $dateToObj = null;
            }
        }
        // Sinon, utiliser les dates personnalisées
        else {
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
        }

        // Mapper le plan du formulaire vers le niveau en base de données
        $planFilter = null;
        if ($plan) {
            $planMapping = [
                'Basic' => 'basic',
                'Pro' => 'pro',
                'Entreprise' => 'enterprise'
            ];
            $planFilter = $planMapping[$plan] ?? null;
        }

        $stats = $this->statisticsService->getAllStatistics($dateFromObj, $dateToObj, $planFilter);

        return $this->render('admin/reports/statistiques.html.twig', [
            'user' => $this->getUser(),
            'stats' => $stats,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'month' => $month,
            'year' => $year,
            'plan' => $plan,
        ]);
    }

    #[Route('/ventes', name: 'app_reports_ventes')]
    public function ventes(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        return $this->render('admin/reports/ventes.html.twig', [
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

