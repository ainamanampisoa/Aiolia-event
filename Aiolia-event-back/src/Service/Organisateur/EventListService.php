<?php

namespace App\Service\Organisateur;

use App\Entity\Event;
use DateTime;
use DateTimeZone;
use Symfony\Component\HttpFoundation\Request;

class EventListService
{
    private const TIMEZONE = 'Indian/Antananarivo';

    public function __construct(
        private EventService $eventService,
        private TypeBilletService $typeBilletService,
        private EventStatsService $eventStatsService
    ) {
    }


    public function getOrganizerEvents(array $criteria): array
    {
        return $this->eventService->searchMultiCriteriaWithPagination($criteria);
    }


    public function calculateGlobalStats(string $organizerId): array
    {

        $allEventsCriteria = [
            'idOrganisateur' => $organizerId,
            'lieuId' => null,
            'dateDebut' => null,
            'dateFin' => null,
            'typeEvenementId' => null,
            'statut' => null,
            'prixMin' => null,
            'prixMax' => null,
            'triPrix' => null,
            'page' => 1,
            'limit' => 10000,
        ];

        $allEventsResult = $this->eventService->searchMultiCriteriaWithPagination($allEventsCriteria);
        $allEvents = $allEventsResult['items'];

        $totalEvents = count($allEvents);
        $now = new DateTime('now', new DateTimeZone(self::TIMEZONE));
        $nowDate = (clone $now)->setTime(0, 0, 0);
        
        $upcomingEvents = 0;
        $ongoingEvents = 0;
        
        foreach ($allEvents as $event) {
            if (!$event->getCommenceLe()) {
                continue;
            }
            
            $commenceLe = $event->getCommenceLe();
            $seTermineLe = $event->getSeTermineLe();
            $statut = $event->getStatut();
            
            if ($commenceLe->getTimezone()->getName() !== self::TIMEZONE) {
                $commenceLe = clone $commenceLe;
                $commenceLe->setTimezone(new DateTimeZone(self::TIMEZONE));
            }
            if ($seTermineLe && $seTermineLe->getTimezone()->getName() !== self::TIMEZONE) {
                $seTermineLe = clone $seTermineLe;
                $seTermineLe->setTimezone(new DateTimeZone(self::TIMEZONE));
            }
            
            $commenceLeDate = (clone $commenceLe)->setTime(0, 0, 0);
            
            $hasStarted = $commenceLeDate <= $nowDate;
            
            if ($seTermineLe !== null) {
                $seTermineLeDate = (clone $seTermineLe)->setTime(0, 0, 0);
                $hasNotEnded = $seTermineLeDate >= $nowDate;
            } else {
                $hasNotEnded = true;
            }
            
            if ($statut === Event::STATUS_PUBLISHED && $hasStarted && $hasNotEnded) {
                $ongoingEvents++;
            }
            
            if ($statut === Event::STATUS_PUBLISHED && !$hasStarted) {
                $upcomingEvents++;
            }
        }

        return [
            'total' => $totalEvents,
            'upcoming' => $upcomingEvents,
            'ongoing' => $ongoingEvents,
            'currentDate' => $now,
        ];
    }


    public function calculateEventsStats(array $events, array $viewsByEvent, array $favoritesByEvent, array $participantsByEvent, int $maxUsers): array
    {
        $eventsStats = [];

        foreach ($events as $event) {
            $ticketTypes = $this->typeBilletService->getByEvenement($event);
            $totalTickets = 0;
            $soldTickets = 0;
            $minPrice = null;
            $adultTicketTypes = [];

            foreach ($ticketTypes as $ticketType) {
                $inventaire = $ticketType->getInventaire();
                if ($inventaire) {
                    $totalTickets += $inventaire->getQuantiteTotale() ?? 0;
                    $soldTickets += $inventaire->getQuantiteVendue() ?? 0;
                }

                $price = (float) $ticketType->getPrixDeBase();
                if ($minPrice === null || $price < $minPrice) {
                    $minPrice = $price;
                }


                $segment = $ticketType->getConfigurationSegment();
                if ($segment && in_array($segment->getNom(), ['adulte', 'tous'], true)) {
                    $adultTicketTypes[] = [
                        'nom' => $ticketType->getNom(),
                        'prix' => $price,
                        'devise' => $ticketType->getDevise(),
                    ];
                }
            }


            usort($adultTicketTypes, function($a, $b) {
                return $a['prix'] <=> $b['prix'];
            });

            $eventId = $event->getId();


            $participantsCount = (int) ($participantsByEvent[$eventId] ?? 0);

            $viewsCount = (int) ($viewsByEvent[$eventId] ?? 0);



            if ($viewsCount < $participantsCount) {
                $viewsCount = $participantsCount;
            }
            if ($maxUsers > 0 && $viewsCount > $maxUsers) {
                $viewsCount = $maxUsers;
            }

            $favoritesCount = (int) ($favoritesByEvent[$eventId] ?? 0);

            $eventsStats[$event->getId()] = [
                'totalTickets' => $totalTickets,
                'soldTickets' => $soldTickets,
                'participantsCount' => $participantsCount,
                'minPrice' => $minPrice,
                'adultTicketTypes' => $adultTicketTypes,
                'viewsCount' => $viewsCount,
                'favoritesCount' => $favoritesCount,
            ];
        }

        return $eventsStats;
    }


    public function getDisplayStats(array $eventIds): array
    {
        $viewsByEvent = $this->eventStatsService->getViewsCountByEventIds($eventIds);
        $favoritesByEvent = $this->eventStatsService->getFavoritesCountByEventIds($eventIds);
        $participantsByEvent = $this->eventStatsService->getParticipantsCountByEventIds($eventIds);
        $maxUsers = $this->eventStatsService->getMaxUserCount();

        return [
            'viewsByEvent' => $viewsByEvent,
            'favoritesByEvent' => $favoritesByEvent,
            'participantsByEvent' => $participantsByEvent,
            'maxUsers' => $maxUsers,
        ];
    }


    public function buildCriteriaFromRequest(Request $request, string $organizerId): array
    {
        $criteria = [
            'idOrganisateur' => $organizerId,
            'lieuId' => $request->query->get('lieuId'),
            'dateDebut' => $request->query->get('dateDebut') ? new DateTime($request->query->get('dateDebut')) : null,
            'dateFin' => $request->query->get('dateFin') ? new DateTime($request->query->get('dateFin')) : null,
            'typeEvenementId' => $request->query->get('typeEvenementId'),
            'statut' => $request->query->get('statut'),
            'prixMin' => $request->query->get('prixMin') ? (float) $request->query->get('prixMin') : null,
            'prixMax' => $request->query->get('prixMax') ? (float) $request->query->get('prixMax') : null,
            'triPrix' => $request->query->get('triPrix'),
            'page' => $request->query->getInt('page', 1),
            'limit' => $request->query->getInt('limit', 6),
        ];

        foreach ($criteria as $key => $value) {
            if ($value === '' || $value === '0') {
                $criteria[$key] = null;
            }
        }

        return $criteria;
    }


    public function getCalendarData(string $organizerId, int $year, int $month): array
    {
        $allEventsCriteria = [
            'idOrganisateur' => $organizerId,
            'nomLieu' => null,
            'dateDebut' => null,
            'dateFin' => null,
            'typeEvenementId' => null,
            'statut' => null,
            'prixMin' => null,
            'prixMax' => null,
            'triPrix' => null,
            'page' => 1,
            'limit' => 10000,
        ];
        $allEventsResult = $this->eventService->searchMultiCriteriaWithPagination($allEventsCriteria);
        $allEvents = $allEventsResult['items'];


        $eventsByDate = [];
        foreach ($allEvents as $event) {
            if ($event->getCommenceLe()) {
                $dateKey = $event->getCommenceLe()->format('Y-m-d');
                $eventsByDate[$dateKey][] = $event;
            }
        }

        $firstDay = new DateTime(sprintf('%d-%02d-01', $year, $month));
        $lastDay = new DateTime($firstDay->format('Y-m-t'));
        $daysInMonth = (int) $lastDay->format('d');
        $startingDayOfWeek = (int) $firstDay->format('w');
        $adjustedStartingDay = $startingDayOfWeek == 0 ? 6 : $startingDayOfWeek - 1;

        $monthNames = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];

        return [
            'events' => $allEvents,
            'eventsByDate' => $eventsByDate,
            'currentYear' => $year,
            'currentMonth' => $month,
            'monthName' => $monthNames[$month],
            'daysInMonth' => $daysInMonth,
            'startingDayOfWeek' => $adjustedStartingDay,
        ];
    }
}

