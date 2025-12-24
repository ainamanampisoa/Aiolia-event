<?php

namespace App\Controller\Organisateur;

use App\Repository\Organisateur\OrganizerProfileRepository;
use App\Service\Organisateur\ReportsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/organisateur/reports')]
#[IsGranted('ROLE_ORGANIZER')]
class ReportsController extends AbstractController
{
    public function __construct(
        private readonly OrganizerProfileRepository $organizerProfileRepository,
        private readonly ReportsService $reportsService,
    ) {
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

