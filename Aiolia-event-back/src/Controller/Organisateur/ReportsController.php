<?php

namespace App\Controller\Organisateur;

use App\Entity\Event;
use App\Repository\Organisateur\EventRepository;
use App\Repository\Organisateur\OrganizerProfileRepository;
use App\Repository\Organisateur\TicketInvoiceRepository;
use App\Service\Organisateur\EventStatsService;
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
        private readonly EventRepository $eventRepository,
        private readonly OrganizerProfileRepository $organizerProfileRepository,
        private readonly TicketInvoiceRepository $ticketInvoiceRepository,
        private readonly EventStatsService $statsService,
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

        // Récupération de tous les événements de l'organisateur
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

        // Statistiques par événement
        $viewsByEvent = $this->statsService->getViewsCountByEventIds($eventIds);
        $participantsByEvent = $this->statsService->getParticipantsCountByEventIds($eventIds);
        $ticketOccupancy = $this->statsService->getTicketOccupancyRateByEventIds($eventIds);

        // Préparation des données de rapports
        $reports = [];
        foreach ($events as $event) {
            $id = (int) $event->getId();
            
            // Chiffre d'affaires par événement
            if ($dateFrom && $dateTo) {
                $revenuesByType = $this->ticketInvoiceRepository->getRevenueByEventAndPeriod($event, $dateFrom, $dateTo);
            } else {
                $revenuesByType = $this->ticketInvoiceRepository->getRevenueByEvent($event);
            }
            $revenue = array_sum($revenuesByType);

            // Trouver les données d'occupation pour cet événement
            $occupancyData = null;
            foreach ($ticketOccupancy as $occ) {
                if ($occ['event_id'] === $id) {
                    $occupancyData = $occ;
                    break;
                }
            }

            $reports[] = [
                'event' => $event,
                'revenue' => $revenue,
                'views' => $viewsByEvent[$id] ?? 0,
                'participants' => $participantsByEvent[$id] ?? 0,
                'occupancy_rate' => $occupancyData ? $occupancyData['occupation_rate'] : 0,
                'sold_tickets' => $occupancyData ? $occupancyData['sold_tickets'] : 0,
                'total_capacity' => $occupancyData ? $occupancyData['total_capacity'] : 0,
            ];
        }

        // Trier par date de début (plus récent en premier)
        usort($reports, function ($a, $b) {
            $dateA = $a['event']->getCommenceLe();
            $dateB = $b['event']->getCommenceLe();
            if (!$dateA && !$dateB) {
                return 0;
            }
            if (!$dateA) {
                return 1;
            }
            if (!$dateB) {
                return -1;
            }
            return $dateB <=> $dateA;
        });

        // Calcul des totaux (sur tous les événements, pas seulement la page courante)
        $totals = [
            'revenue' => 0,
            'views' => 0,
            'participants' => 0,
            'sold_tickets' => 0,
            'total_capacity' => 0,
        ];

        foreach ($reports as $report) {
            $totals['revenue'] += $report['revenue'];
            $totals['views'] += $report['views'];
            $totals['participants'] += $report['participants'];
            $totals['sold_tickets'] += $report['sold_tickets'];
            $totals['total_capacity'] += $report['total_capacity'];
        }

        $totals['occupancy_rate'] = $totals['total_capacity'] > 0
            ? ($totals['sold_tickets'] / $totals['total_capacity'] * 100)
            : 0;

        // Les 3 meilleurs événements (par chiffre d'affaires)
        $topEvents = $reports;
        usort($topEvents, function ($a, $b) {
            return $b['revenue'] <=> $a['revenue'];
        });
        $top3Events = array_slice($topEvents, 0, 3);

        // Pagination
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = 7;
        $totalItems = count($reports);
        $totalPages = max(1, (int) ceil($totalItems / $perPage));
        $page = min($page, $totalPages);

        $offset = ($page - 1) * $perPage;
        $paginatedReports = array_slice($reports, $offset, $perPage);

        $pagination = [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $totalItems,
            'pages' => $totalPages,
        ];

        if ($dateFrom && $dateTo) {
            $periodLabel = sprintf('%s - %s', $dateFrom->format('d/m/Y'), $dateTo->format('d/m/Y'));
        } else {
            $periodLabel = 'Toutes les périodes';
        }

        return $this->render('Organisateur/reports/index.html.twig', [
            'reports' => $paginatedReports,
            'totals' => $totals,
            'top3Events' => $top3Events,
            'pagination' => $pagination,
            'periodLabel' => $periodLabel,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }
}

