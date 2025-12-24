<?php

namespace App\Service\Organisateur;

use App\Entity\Event;
use App\Entity\OrganizerProfile;
use App\Entity\User;
use App\Repository\Admin\SubscriptionInvoiceRepository;
use App\Repository\Organisateur\EventRepository;
use App\Repository\Organisateur\TicketInvoiceRepository;
use DateTimeImmutable;
use DateTimeZone;

class ReportsService
{
    private const TIMEZONE = 'Indian/Antananarivo';

    public function __construct(
        private readonly EventRepository $eventRepository,
        private readonly TicketInvoiceRepository $ticketInvoiceRepository,
        private readonly SubscriptionInvoiceRepository $subscriptionInvoiceRepository,
        private readonly EventStatsService $eventStatsService,
    ) {
    }

    /**
     * Récupère les données de rapports pour un organisateur
     * 
     * @param OrganizerProfile $organizerProfile
     * @param User $user
     * @param \DateTimeImmutable|null $dateFrom
     * @param \DateTimeImmutable|null $dateTo
     * @return array
     */
    public function getReportsData(
        OrganizerProfile $organizerProfile,
        User $user,
        ?\DateTimeImmutable $dateFrom = null,
        ?\DateTimeImmutable $dateTo = null
    ): array {
        // Récupération des événements publiés (filtrés par date si applicable)
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
            Event::STATUS_PUBLISHED
        );

        // Compter les événements publiés dans la période sélectionnée
        $publishedEventsCount = count(array_filter($events, fn(Event $event) => $event->getStatut() === Event::STATUS_PUBLISHED));

        $eventIds = array_map(
            static fn (Event $event) => (int) $event->getId(),
            $events
        );

        // Statistiques par événement
        $viewsByEvent = $this->eventStatsService->getViewsCountByEventIds($eventIds);
        $participantsByEvent = $this->eventStatsService->getParticipantsCountByEventIds($eventIds);
        $ticketOccupancy = $this->eventStatsService->getTicketOccupancyRateByEventIds($eventIds);

        // Préparation des données de rapports
        $reports = [];
        foreach ($events as $event) {
            $id = (int) $event->getId();
            
            // Chiffre d'affaires par événement
            $revenue = $this->calculateEventRevenue($event, $dateFrom, $dateTo);

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

        // Calcul des totaux
        $totals = $this->calculateTotals($reports);

        // Calcul des dépenses d'abonnement
        $subscriptionExpenses = $this->subscriptionInvoiceRepository->getSubscriptionRevenueByUser(
            $user,
            $dateFrom,
            $dateTo
        );

        // Revenu net
        $netRevenue = $totals['revenue'] - $subscriptionExpenses;

        // Top 3 des événements les plus réussis
        $top3Events = $this->getTop3Events($reports);

        return [
            'events' => $events,
            'reports' => $reports,
            'totals' => $totals,
            'top3Events' => $top3Events,
            'publishedEventsCount' => $publishedEventsCount,
            'subscriptionExpenses' => $subscriptionExpenses,
            'netRevenue' => $netRevenue,
        ];
    }

    /**
     * Récupère les données de statistiques pour les graphiques
     * 
     * @param OrganizerProfile $organizerProfile
     * @param \DateTimeImmutable|null $dateFrom
     * @param \DateTimeImmutable|null $dateTo
     * @return array
     */
    public function getStatisticsData(
        OrganizerProfile $organizerProfile,
        ?\DateTimeImmutable $dateFrom = null,
        ?\DateTimeImmutable $dateTo = null
    ): array {
        // Récupération des événements publiés (filtrés par date si applicable)
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
            Event::STATUS_PUBLISHED
        );

        $eventIds = array_map(
            static fn (Event $event) => (int) $event->getId(),
            $events
        );

        // Statistiques d'engagement par événement
        $viewsByEvent = $this->eventStatsService->getViewsCountByEventIds($eventIds);
        $participantsByEvent = $this->eventStatsService->getParticipantsCountByEventIds($eventIds);

        // Préparation des données pour les graphiques
        $eventLabels = [];
        $viewsSeries = [];
        $participantsSeries = [];
        $revenueSeries = [];

        foreach ($events as $event) {
            $id = (int) $event->getId();
            $eventLabels[] = $event->getTitre();
            $viewsSeries[] = $viewsByEvent[$id] ?? 0;
            $participantsSeries[] = $participantsByEvent[$id] ?? 0;
            $revenueSeries[] = $this->calculateEventRevenue($event, $dateFrom, $dateTo);
        }

        // Statistiques pour les graphiques
        $salesEvolution = $this->eventStatsService->getSalesEvolutionOverTime($eventIds, $dateFrom, $dateTo);
        $salesDistribution = $this->eventStatsService->getSalesDistributionByTicketType($eventIds, $dateFrom, $dateTo);
        $salesComparison = $this->eventStatsService->getSalesComparisonByTicketType($eventIds, $dateFrom, $dateTo);
        $ticketOccupancy = $this->eventStatsService->getTicketOccupancyRateByEventIds($eventIds);

        // Widgets
        $totalPublishedEvents = count(array_filter($events, fn(Event $event) => $event->getStatut() === Event::STATUS_PUBLISHED));
        $totalParticipants = array_sum($participantsSeries);
        $totalRevenue = array_sum($revenueSeries);

        return [
            'widgets' => [
                'published_events' => $totalPublishedEvents,
                'total_revenue' => $totalRevenue,
                'total_participants' => $totalParticipants,
            ],
            'charts' => [
                'sales_evolution' => $salesEvolution,
                'sales_distribution' => $salesDistribution,
                'sales_comparison' => $salesComparison,
                'ticket_occupancy' => $ticketOccupancy,
            ],
        ];
    }

    /**
     * Calcule le revenu d'un événement
     * 
     * @param Event $event
     * @param \DateTimeImmutable|null $dateFrom
     * @param \DateTimeImmutable|null $dateTo
     * @return float
     */
    private function calculateEventRevenue(
        Event $event,
        ?\DateTimeImmutable $dateFrom = null,
        ?\DateTimeImmutable $dateTo = null
    ): float {
        if ($dateFrom && $dateTo) {
            $revenuesByType = $this->ticketInvoiceRepository->getRevenueByEventAndPeriod($event, $dateFrom, $dateTo);
        } else {
            $revenuesByType = $this->ticketInvoiceRepository->getRevenueByEvent($event);
        }
        
        return array_sum($revenuesByType);
    }

    /**
     * Calcule les totaux à partir des rapports
     * 
     * @param array $reports
     * @return array
     */
    private function calculateTotals(array $reports): array
    {
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

        return $totals;
    }

    /**
     * Récupère le Top 3 des événements les plus réussis
     * 
     * @param array $reports
     * @return array
     */
    private function getTop3Events(array $reports): array
    {
        $topEvents = $reports;
        usort($topEvents, function ($a, $b) {
            return $b['revenue'] <=> $a['revenue'];
        });
        
        return array_slice($topEvents, 0, 3);
    }

    /**
     * Normalise les dates de filtre depuis les paramètres de requête
     * 
     * @param string|null $dateFromParam
     * @param string|null $dateToParam
     * @return array{dateFrom: \DateTimeImmutable|null, dateTo: \DateTimeImmutable|null}
     */
    public function normalizeDateFilters(?string $dateFromParam, ?string $dateToParam): array
    {
        $timezone = new DateTimeZone(self::TIMEZONE);
        $dateFrom = null;
        $dateTo = null;

        // Normaliser les chaînes vides en null
        $dateFromParam = ($dateFromParam === null || $dateFromParam === '') ? null : trim($dateFromParam);
        $dateToParam = ($dateToParam === null || $dateToParam === '') ? null : trim($dateToParam);

        // Traiter la date de début
        if ($dateFromParam !== null && $dateFromParam !== '') {
            try {
                // Créer la date dans le fuseau horaire local, puis la convertir en UTC pour les requêtes SQL
                $localDate = new DateTimeImmutable($dateFromParam, $timezone);
                $dateFrom = $localDate->setTime(0, 0, 0);
            } catch (\Exception) {
                $dateFrom = null;
            }
        }

        // Traiter la date de fin
        if ($dateToParam !== null && $dateToParam !== '') {
            try {
                // Créer la date dans le fuseau horaire local, puis la convertir en UTC pour les requêtes SQL
                $localDate = new DateTimeImmutable($dateToParam, $timezone);
                $dateTo = $localDate->setTime(23, 59, 59);
            } catch (\Exception) {
                $dateTo = null;
            }
        }

        // Inverser si dateFrom > dateTo
        if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        return [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ];
    }

    /**
     * Génère le label de période
     * 
     * @param \DateTimeImmutable|null $dateFrom
     * @param \DateTimeImmutable|null $dateTo
     * @return string
     */
    public function getPeriodLabel(?\DateTimeImmutable $dateFrom, ?\DateTimeImmutable $dateTo): string
    {
        if ($dateFrom && $dateTo) {
            return sprintf('%s - %s', $dateFrom->format('d/m/Y'), $dateTo->format('d/m/Y'));
        }
        
        return 'Toutes les périodes';
    }
}

