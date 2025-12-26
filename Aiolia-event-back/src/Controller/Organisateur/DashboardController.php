<?php

namespace App\Controller\Organisateur;

use App\Repository\Organisateur\OrganizerProfileRepository;
use App\Service\Organisateur\ReportsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/organisateur/dashboard')]
#[IsGranted('ROLE_ORGANIZER')]
class DashboardController extends AbstractController
{
    public function __construct(
        private readonly OrganizerProfileRepository $organizerProfileRepository,
        private readonly ReportsService $reportsService,
    ) {
    }

    #[Route('/statistiques', name: 'app_organisateur_dashboard_statistiques', methods: ['GET'])]
    #[Route('/statistiques/', name: 'app_organisateur_dashboard_statistiques_slash', methods: ['GET'])]
    public function statistiques(Request $request): Response
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
        
        $dateFromParam = ($dateFromParam === null || $dateFromParam === '') ? null : $dateFromParam;
        $dateToParam = ($dateToParam === null || $dateToParam === '') ? null : $dateToParam;
        
        $dateFilters = $this->reportsService->normalizeDateFilters(
            $dateFromParam,
            $dateToParam
        );
        $dateFrom = $dateFilters['dateFrom'];
        $dateTo = $dateFilters['dateTo'];

        // Récupération des données de statistiques via le service
        $statisticsData = $this->reportsService->getStatisticsData(
            $organizerProfile,
            $dateFrom,
            $dateTo
        );

        // Ajouter les dépenses d'abonnement
        $reportsData = $this->reportsService->getReportsData(
            $organizerProfile,
            $user,
            $dateFrom,
            $dateTo
        );
        
        $statisticsData['widgets']['current_month_subscription_expense'] = $reportsData['subscriptionExpenses'];

        $periodLabel = $this->reportsService->getPeriodLabel($dateFrom, $dateTo);

        return $this->render('Organisateur/dashboard/statistiques.html.twig', [
            'statistics' => $statisticsData,
            'periodLabel' => $periodLabel,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }
}
