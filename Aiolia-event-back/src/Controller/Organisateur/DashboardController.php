<?php

namespace App\Controller\Organisateur;

use App\Entity\Event;
use App\Entity\OrganizerProfile; // ou ProfilOrganisateur
use App\Repository\Organisateur\EventRepository;
use App\Repository\Organisateur\OrganisateurEvenementRepository;
use App\Repository\Organisateur\OrganizerProfileRepository;
use App\Repository\Organisateur\TicketInvoiceRepository;
use App\Service\Organisateur\EventStatsService;
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
        private readonly EventRepository $eventRepository,
        private readonly OrganizerProfileRepository $organizerProfileRepository,
        private readonly TicketInvoiceRepository $ticketInvoiceRepository,
        private readonly EventStatsService $statsService,
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

        // Filtre entre deux dates (sur la période des événements)
        $timezone = new \DateTimeZone('Indian/Antananarivo');
        $dateFromParam = $request->query->get('date_from');
        $dateToParam = $request->query->get('date_to');
        $hasDateFilter = $dateFromParam || $dateToParam;

        $dateFrom = null;
        $dateTo = null;

        if ($hasDateFilter) {
            try {
                $dateFrom = $dateFromParam
                    ? new \DateTimeImmutable($dateFromParam, $timezone)
                    : null;
            } catch (\Exception) {
                $dateFrom = null;
            }

            try {
                $dateTo = $dateToParam
                    ? (new \DateTimeImmutable($dateToParam, $timezone))->setTime(23, 59, 59)
                    : null;
            } catch (\Exception) {
                $dateTo = null;
            }

            if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
                [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
            }
        }

        // Récupération des événements de l'organisateur
        // - si aucune date fournie : tous les événements
        // - si dates fournies : filtre sur commenceLe / seTermineLe
        $events = $this->eventRepository->searchMultiCriteria(
            $organizerProfile->getId(),
            null,
            $dateFrom,
            $dateTo,
            null,
            null,
            null,
            null,
            null,
            null,
            null
        );

        $eventIds = array_map(
            static fn (Event $event) => (int) $event->getId(),
            $events
        );

        // Statistiques d’engagement par événement
        $viewsByEvent = $this->statsService->getViewsCountByEventIds($eventIds);
        $participantsByEvent = $this->statsService->getParticipantsCountByEventIds($eventIds);

        // Préparation des données pour les graphiques : labels = titres des événements
        $eventLabels = [];
        $viewsSeries = [];
        $participantsSeries = [];
        $revenueSeries = [];

        foreach ($events as $event) {
            $id = (int) $event->getId();
            $label = $event->getTitre();

            $eventLabels[] = $label;
            $viewsSeries[] = $viewsByEvent[$id] ?? 0;
            $participantsSeries[] = $participantsByEvent[$id] ?? 0;

            // Chiffre d'affaires par événement :
            // - avec filtre : factures sur la période sélectionnée
            // - sans filtre : tout l'historique de ventes de billets
            if ($dateFrom && $dateTo) {
                $revenuesByType = $this->ticketInvoiceRepository->getRevenueByEventAndPeriod($event, $dateFrom, $dateTo);
            } else {
                $revenuesByType = $this->ticketInvoiceRepository->getRevenueByEvent($event);
            }
            $eventRevenue = array_sum($revenuesByType);
            $revenueSeries[] = $eventRevenue;
        }

        // Nouvelles statistiques pour les graphiques
        $salesEvolution = $this->statsService->getSalesEvolutionOverTime($eventIds, $dateFrom, $dateTo);
        $salesDistribution = $this->statsService->getSalesDistributionByTicketType($eventIds, $dateFrom, $dateTo);
        $salesComparison = $this->statsService->getSalesComparisonByTicketType($eventIds, $dateFrom, $dateTo);
        $ticketOccupancy = $this->statsService->getTicketOccupancyRateByEventIds($eventIds);

        // Widgets (toutes périodes par défaut, ou filtrées si dates fournies)
        $totalPublishedEvents = $this->eventRepository->countByOrganizer($user, Event::STATUS_PUBLISHED);
        $totalParticipants = array_sum($participantsSeries);
        $totalRevenue = array_sum($revenueSeries);
        $currentMonthSubscriptionExpense = $this->statsService->getCurrentMonthSubscriptionExpense($organizerProfile);

        $statistics = [
            'widgets' => [
                'published_events' => $totalPublishedEvents,
                'total_revenue' => $totalRevenue,
                'total_participants' => $totalParticipants,
                'current_month_subscription_expense' => $currentMonthSubscriptionExpense,
            ],
            'charts' => [
                // Graphique 1 : Évolution des ventes dans le temps
                'sales_evolution' => $salesEvolution,
                // Graphique 2 : Répartition des ventes par type (catégorie)
                'sales_distribution' => $salesDistribution,
                // Graphique 3 : Comparaison des ventes par type de billet
                'sales_comparison' => $salesComparison,
                // Graphique 4 : Taux de remplissage (occupation) par événement
                'ticket_occupancy' => $ticketOccupancy,
            ],
        ];

        if ($dateFrom && $dateTo) {
            $periodLabel = sprintf('%s - %s', $dateFrom->format('d/m/Y'), $dateTo->format('d/m/Y'));
        } else {
            $periodLabel = 'Toutes les périodes';
        }

        return $this->render('Organisateur/dashboard/statistiques.html.twig', [
            'statistics' => $statistics,
            'periodLabel' => $periodLabel,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

}
