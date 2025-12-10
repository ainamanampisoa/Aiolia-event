<?php

namespace App\Controller\Organisateur;

use App\Entity\Event;
use App\Form\EventType;
use App\Repository\Organisateur\EventRepository;
use App\Repository\Organisateur\OrganizerProfileRepository;
use App\Service\Organisateur\EventExportService;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Service\Organisateur\EventService;
use App\Service\Organisateur\TypeBilletService;
use App\Service\Organisateur\EventTypeService;
use App\Service\Organisateur\EventStatsService;
use App\Service\Organisateur\VenueService;
use App\Service\Organisateur\EventListService;
use App\Service\Organisateur\EventPdfExportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/organisateur/events')]
#[IsGranted('ROLE_ORGANIZER')]
class EventsController extends AbstractController
{
    


    #[Route('', name: 'organisateur_events_index', methods: ['GET'])]
    public function index(
        EventListService $eventListService,
        EventTypeService $eventTypeService,
        OrganizerProfileRepository $organizerProfileRepository,
        VenueService $venueService,
        Request $request
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        
        $organizerProfile = $organizerProfileRepository->findOneBy(['utilisateur' => $user]);
        if (!$organizerProfile) {
            throw $this->createAccessDeniedException('Profil organisateur non trouvé.');
        }

        
        $criteria = $eventListService->buildCriteriaFromRequest($request, $organizerProfile->getId());

        
        $result = $eventListService->getOrganizerEvents($criteria);
        $events = $result['items'];
        $pagination = $result['pagination'];
        
        
        $eventIds = array_map(static fn(Event $event) => $event->getId(), $events);
        $displayStats = $eventListService->getDisplayStats($eventIds);
        
        
        $globalStatsData = $eventListService->calculateGlobalStats($organizerProfile->getId());
        $globalStats = [
            'total' => $globalStatsData['total'],
            'upcoming' => $globalStatsData['upcoming'],
            'ongoing' => $globalStatsData['ongoing'],
        ];
        $currentDate = $globalStatsData['currentDate'];

        
        $eventsStats = $eventListService->calculateEventsStats(
            $events,
            $displayStats['viewsByEvent'],
            $displayStats['favoritesByEvent'],
            $displayStats['participantsByEvent'],
            $displayStats['maxUsers']
        );

        
        $eventTypes = $eventTypeService->getAll();

        
        $venues = $venueService->getAllActive();

        
        $eventStatuses = [
            Event::STATUS_DRAFT => 'Brouillon',
            Event::STATUS_PUBLISHED => 'Publié',
            Event::STATUS_CANCELLED => 'Annulé',
            Event::STATUS_ARCHIVED => 'Archivé',
            'live' => 'En cours (LIVE)',
            'upcoming' => 'À venir',
        ];

        return $this->render('Organisateur/events/index.html.twig', [
            'events' => $events,
            'pagination' => $pagination,
            'criteria' => $criteria,
            'eventTypes' => $eventTypes,
            'venues' => $venues,
            'eventStatuses' => $eventStatuses,
            'eventsStats' => $eventsStats,
            'globalStats' => $globalStats,
            'currentDate' => $currentDate,
        ]);
    }

    
    #[Route('/new', name: 'app_event_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EventService $eventService,
        EventRepository $eventRepository,
        OrganizerProfileRepository $organizerProfileRepository
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        
        $organizerProfile = $organizerProfileRepository->findOneBy(['utilisateur' => $user]);
        if (!$organizerProfile) {
            throw $this->createAccessDeniedException('Profil organisateur non trouvé.');
        }

        $event = new Event();
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $event->setProfilOrganisateur($organizerProfile);
            $eventRepository->create($event);

            return $this->redirectToRoute('organisateur_events_index');
        }

        return $this->render('Organisateur/events/new.html.twig', [
            'event' => $event,
            'form' => $form,
        ]);
    }

    
    #[Route('/calendar', name: 'organisateur_events_calendar', methods: ['GET'])]
    public function calendar(
        EventListService $eventListService,
        OrganizerProfileRepository $organizerProfileRepository,
        Request $request
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        
        $organizerProfile = $organizerProfileRepository->findOneBy(['utilisateur' => $user]);
        if (!$organizerProfile) {
            throw $this->createAccessDeniedException('Profil organisateur non trouvé.');
        }

        
        $year = $request->query->getInt('year', (int) date('Y'));
        $month = $request->query->getInt('month', (int) date('m'));
        
        
        $month = max(1, min(12, $month));
        $year = max(2020, min(2100, $year));

        $calendarData = $eventListService->getCalendarData($organizerProfile->getId(), $year, $month);

        return $this->render('Organisateur/events/calendar.html.twig', $calendarData);
    }

    
    #[Route('/{id}', name: 'organisateur_events_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(
        Event $event,
        OrganizerProfileRepository $organizerProfileRepository
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        
        $organizerProfile = $organizerProfileRepository->findOneBy(['utilisateur' => $user]);
        if (!$organizerProfile || $event->getProfilOrganisateur()?->getId() !== $organizerProfile->getId()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cet événement.');
        }

        return $this->render('Organisateur/events/show.html.twig', [
            'event' => $event,
        ]);
    }

    
    #[Route('/{id}/statistics', name: 'organisateur_events_statistics', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function statistics(
        Event $event,
        OrganizerProfileRepository $organizerProfileRepository,
        TypeBilletService $typeBilletService,
        EventService $eventService
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        
        $organizerProfile = $organizerProfileRepository->findOneBy(['utilisateur' => $user]);
        if (!$organizerProfile || $event->getProfilOrganisateur()?->getId() !== $organizerProfile->getId()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cet événement.');
        }

        
        $ticketTypes = $typeBilletService->getByEvenement($event);

        
        $statistics = $eventService->getEventStatistics($event);

        return $this->render('Organisateur/events/statistics.html.twig', [
            'event' => $event,
            'ticketTypes' => $ticketTypes,
            'statistics' => $statistics,
        ]);
    }

    
    #[Route('/{id}/information', name: 'organisateur_events_information', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function information(
        Event $event,
        OrganizerProfileRepository $organizerProfileRepository
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        
        $organizerProfile = $organizerProfileRepository->findOneBy(['utilisateur' => $user]);
        if (!$organizerProfile || $event->getProfilOrganisateur()?->getId() !== $organizerProfile->getId()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cet événement.');
        }

        
        $primaryOrganizer = $event->getProfilOrganisateur();
        $coOrganizers = $event->getOrganisateurs();

        return $this->render('Organisateur/events/information.html.twig', [
            'event' => $event,
            'primaryOrganizer' => $primaryOrganizer,
            'coOrganizers' => $coOrganizers,
        ]);
    }

    
    #[Route('/{id}/actions', name: 'organisateur_events_actions', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function actions(
        Event $event,
        OrganizerProfileRepository $organizerProfileRepository
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        
        $organizerProfile = $organizerProfileRepository->findOneBy(['utilisateur' => $user]);
        if (!$organizerProfile || $event->getProfilOrganisateur()?->getId() !== $organizerProfile->getId()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cet événement.');
        }

        
        return $this->redirectToRoute('organisateur_events_information', ['id' => $event->getId()]);
    }

    
    #[Route('/{id}/export-pdf', name: 'organisateur_events_export_pdf', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function exportPdf(
        Event $event,
        OrganizerProfileRepository $organizerProfileRepository,
        TypeBilletService $typeBilletService,
        EventService $eventService,
        EventPdfExportService $eventPdfExportService
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        
        $organizerProfile = $organizerProfileRepository->findOneBy(['utilisateur' => $user]);
        if (!$organizerProfile || $event->getProfilOrganisateur()?->getId() !== $organizerProfile->getId()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cet événement.');
        }

            
            $primaryOrganizer = $event->getProfilOrganisateur();
            $coOrganizers = $event->getOrganisateurs();
            $ticketTypes = $typeBilletService->getByEvenement($event);
            $statistics = $eventService->getEventStatistics($event);

        
        return $eventPdfExportService->generatePdf(
            $event,
            $primaryOrganizer,
            $coOrganizers,
            $ticketTypes,
            $statistics
        );
    }

    
    #[Route('/{id}/export-csv', name: 'organisateur_events_export_csv', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function exportCsv(
        Event $event,
        OrganizerProfileRepository $organizerProfileRepository,
        EventExportService $eventExportService
    ): StreamedResponse {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        
        $organizerProfile = $organizerProfileRepository->findOneBy(['utilisateur' => $user]);
        if (!$organizerProfile || $event->getProfilOrganisateur()?->getId() !== $organizerProfile->getId()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cet événement.');
        }

        
        $filename = $eventExportService->generateCsvFilename($event);

        
        $response = new StreamedResponse();
        $response->setCallback(function () use ($event, $eventExportService) {
            $handle = fopen('php://output', 'w');
            
            
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            
            
            $eventExportService->generateParticipantsCsv($event, function (array $row) use ($handle) {
                fputcsv($handle, $row, ';');
            });
            
            fclose($handle);
        });
        
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'private, max-age=0, must-revalidate');
        
        return $response;
    }
}


