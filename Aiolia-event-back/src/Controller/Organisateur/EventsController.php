<?php

namespace App\Controller\Organisateur;

use App\Entity\Event;
use App\Form\EventType;
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
use App\Service\Organisateur\ConfigurationCategorieBilletService;
use App\Service\Organisateur\ConfigurationSegmentBilletService;
use App\Service\Organisateur\MediaService;
use App\Repository\Organisateur\EventCategoryRepository;
use App\Service\Organisateur\ModePaiementService;
use App\Service\Organisateur\TypeAccessibiliteService;
use App\Service\Organisateur\LienAccessibiliteEvenementService;
use Doctrine\ORM\EntityManagerInterface;
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
        MediaService $mediaService,
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

        // Récupérer les médias pour chaque événement
        $eventsMedias = [];
        foreach ($events as $event) {
            $eventsMedias[$event->getId()] = $mediaService->getEventMedias($event, 'image');
        }

        return $this->render('Organisateur/events/index.html.twig', [
            'events' => $events,
            'pagination' => $pagination,
            'criteria' => $criteria,
            'eventTypes' => $eventTypes,
            'venues' => $venues,
            'eventStatuses' => $eventStatuses,
            'eventsMedias' => $eventsMedias,
            'eventsStats' => $eventsStats,
            'globalStats' => $globalStats,
            'currentDate' => $currentDate,
        ]);
    }

    
    #[Route('/new', name: 'app_event_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EventService $eventService,
        OrganizerProfileRepository $organizerProfileRepository,
        VenueService $venueService,
        ConfigurationCategorieBilletService $categorieBilletService,
        ConfigurationSegmentBilletService $segmentBilletService,
        MediaService $mediaService,
        EventTypeService $eventTypeService,
        EventCategoryRepository $eventCategoryRepository,
        ModePaiementService $modePaiementService,
        TypeAccessibiliteService $typeAccessibiliteService,
        LienAccessibiliteEvenementService $lienAccessibiliteService,
        EntityManagerInterface $entityManager
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

        if ($form->isSubmitted()) {
            // Gérer les dates séparées (date + heure) depuis les champs HTML personnalisés
            $dateStart = $request->request->get('date_start');
            $dateEnd = $request->request->get('date_end');
            
            // Valider et définir la date de début (obligatoire)
            if (!$dateStart) {
                $form->get('commenceLe')->addError(new \Symfony\Component\Form\FormError('La date de début est obligatoire'));
            } else {
                try {
                    $event->setCommenceLe(new \DateTime($dateStart));
                } catch (\Exception $e) {
                    $form->get('commenceLe')->addError(new \Symfony\Component\Form\FormError('Format de date invalide'));
                }
            }
            
            // Définir la date de fin (optionnelle)
            if ($dateEnd) {
                try {
                    $event->setSeTermineLe(new \DateTime($dateEnd));
                } catch (\Exception $e) {
                    // Ignorer l'erreur si la date de fin est invalide
                }
            }
            
            // Valider le formulaire après avoir défini les dates
            if ($form->isValid()) {
                // Gérer le lieu (venue)
                $venueId = $request->request->get('venue_id');
                if ($venueId) {
                    $venue = $venueService->getById($venueId);
                    if ($venue) {
                        $event->setLieu($venue);
                    }
                }
                
            $event->setProfilOrganisateur($organizerProfile);
                
                // Définir les dates de vente sur l'événement si elles sont fournies
                $salesStartDate = $request->request->get('ticket_sales_start_date');
                $salesStartTime = $request->request->get('ticket_sales_start_time');
                $salesEndDate = $request->request->get('ticket_sales_end_date');
                $salesEndTime = $request->request->get('ticket_sales_end_time');
                
                if ($salesStartDate && $salesStartTime) {
                    $salesStart = $eventService->parseDateTime($salesStartDate, $salesStartTime);
                    if ($salesStart) {
                        $event->setVentesCommencentLe($salesStart);
                    }
                }
                
                if ($salesEndDate && $salesEndTime) {
                    $salesEnd = $eventService->parseDateTime($salesEndDate, $salesEndTime);
                    if ($salesEnd) {
                        $event->setVentesSeTerminentLe($salesEnd);
                    }
                }
                
                $savedEvent = $eventService->saveFromForm($event);
                
                // Gérer l'accessibilité
                // Les valeurs du formulaire correspondent directement aux codes de TypeAccessibilite
                $accessibilityTypes = $request->request->all('accessibility') ?? [];
                if (!empty($accessibilityTypes)) {
                    foreach ($accessibilityTypes as $accessibilityCode) {
                        // Récupérer le type d'accessibilité via le service
                        $typeAccessibilite = $typeAccessibiliteService->getByCode($accessibilityCode);
                        
                        // Vérifier si le type existe et si le lien n'existe pas déjà pour éviter la collision d'identité
                        if ($typeAccessibilite && !$lienAccessibiliteService->exists($savedEvent, $typeAccessibilite)) {
                            $lienAccessibiliteService->create([], $savedEvent, $typeAccessibilite);
                        }
                    }
                }
                
                // Gérer les modes de paiement
                // Note: Les modes de paiement sont associés aux factures lors de la création de commandes,
                // pas directement aux événements. Cette section peut être étendue si nécessaire pour
                // stocker les modes de paiement acceptés par événement.
                // Les modes de paiement seront utilisés lors de la création des factures
                
                // Gérer l'upload de l'image principale
                $mainImage = $request->files->get('image');
                if ($mainImage) {
                    try {
                        $mediaService->uploadEventMedia(
                            $savedEvent,
                            $mainImage,
                            'image',
                            true, // isPrimary
                            0 // displayOrder
                        );
                    } catch (\Exception $e) {
                        $this->addFlash('error', 'Erreur lors de l\'upload de l\'image principale: ' . $e->getMessage());
                    }
                }
                
                // Gérer l'upload des images supplémentaires (max 5)
                $galleryImages = $request->files->get('gallery', []);
                if (!empty($galleryImages)) {
                    $displayOrder = 1;
                    foreach ($galleryImages as $galleryImage) {
                        if ($galleryImage && $displayOrder <= 5) {
                            try {
                                $mediaService->uploadEventMedia(
                                    $savedEvent,
                                    $galleryImage,
                                    'image',
                                    false, // isPrimary
                                    $displayOrder
                                );
                                $displayOrder++;
                            } catch (\Exception $e) {
                                $this->addFlash('error', 'Erreur lors de l\'upload d\'une image supplémentaire: ' . $e->getMessage());
                            }
                        }
                    }
                }
                
                // Gérer les billets depuis les données du formulaire
                $ticketsData = [
                    'ticket_categorie' => $request->request->all('ticket_categorie') ?? [],
                    'ticket_segment' => $request->request->all('ticket_segment') ?? [],
                    'ticket_price' => $request->request->all('ticket_price') ?? [],
                    'ticket_quantity' => $request->request->all('ticket_quantity') ?? [],
                    // Paramètres de vente globaux
                    'ticket_sales_start_date' => $request->request->get('ticket_sales_start_date'),
                    'ticket_sales_start_time' => $request->request->get('ticket_sales_start_time'),
                    'ticket_sales_end_date' => $request->request->get('ticket_sales_end_date'),
                    'ticket_sales_end_time' => $request->request->get('ticket_sales_end_time'),
                    'ticket_min_per_order' => $request->request->get('ticket_min_per_order'),
                    'ticket_max_per_order' => $request->request->get('ticket_max_per_order'),
                ];
                
                // Créer les billets uniquement si des données sont présentes
                if (!empty($ticketsData['ticket_categorie']) && !empty($ticketsData['ticket_segment'])) {
                    $eventService->createTicketsForEvent($savedEvent, $ticketsData);
                }

            return $this->redirectToRoute('organisateur_events_index');
            }
        }

        return $this->render('Organisateur/events/new.html.twig', [
            'event' => $event,
            'form' => $form,
            'venues' => $venueService->getAllActive(),
            'categoriesBillets' => $categorieBilletService->getAllActive(),
            'segmentsBillets' => $segmentBilletService->getAllActive(),
            'eventCategories' => $eventCategoryRepository->findActiveCategories(),
            'eventTypes' => $eventTypeService->getAll(),
            'modesPaiement' => $modePaiementService->getAllActive(),
            'typesAccessibilite' => $typeAccessibiliteService->getAll(),
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
        OrganizerProfileRepository $organizerProfileRepository,
        MediaService $mediaService
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        
        $organizerProfile = $organizerProfileRepository->findOneBy(['utilisateur' => $user]);
        if (!$organizerProfile || $event->getProfilOrganisateur()?->getId() !== $organizerProfile->getId()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cet événement.');
        }

        // Récupérer les médias de l'événement (images uniquement, triées par ordre d'affichage)
        $eventMedias = $mediaService->getEventMedias($event, 'image');

        return $this->render('Organisateur/events/show.html.twig', [
            'event' => $event,
            'eventMedias' => $eventMedias,
        ]);
    }

    
    #[Route('/{id}/statistics', name: 'organisateur_events_statistics', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function statistics(
        Event $event,
        OrganizerProfileRepository $organizerProfileRepository,
        TypeBilletService $typeBilletService,
        EventService $eventService,
        MediaService $mediaService
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

        // Récupérer les médias de l'événement (images uniquement, triées par ordre d'affichage)
        $eventMedias = $mediaService->getEventMedias($event, 'image');

        return $this->render('Organisateur/events/statistics.html.twig', [
            'event' => $event,
            'ticketTypes' => $ticketTypes,
            'statistics' => $statistics,
            'eventMedias' => $eventMedias,
        ]);
    }

    
    #[Route('/{id}/information', name: 'organisateur_events_information', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function information(
        Event $event,
        OrganizerProfileRepository $organizerProfileRepository,
        MediaService $mediaService
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

        // Récupérer les médias de l'événement (images uniquement, triées par ordre d'affichage)
        $eventMedias = $mediaService->getEventMedias($event, 'image');

        return $this->render('Organisateur/events/information.html.twig', [
            'event' => $event,
            'primaryOrganizer' => $primaryOrganizer,
            'coOrganizers' => $coOrganizers,
            'eventMedias' => $eventMedias,
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

    /**
     * Publie un événement (change le statut de 'draft' à 'published').
     */
    #[Route('/{id}/publish', name: 'organisateur_events_publish', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function publish(
        Event $event,
        Request $request,
        OrganizerProfileRepository $organizerProfileRepository,
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

        // Vérifier le token CSRF
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('publish_event_' . $event->getId(), $token)) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('organisateur_events_information', ['id' => $event->getId()]);
        }

        // Vérifier que l'événement est en brouillon
        if ($event->getStatut() !== Event::STATUS_DRAFT) {
            $this->addFlash('error', 'Seuls les événements en brouillon peuvent être publiés.');
            return $this->redirectToRoute('organisateur_events_information', ['id' => $event->getId()]);
        }

        $eventService->publishEvent($event);
        $this->addFlash('success', 'L\'événement a été publié avec succès.');

        return $this->redirectToRoute('organisateur_events_information', ['id' => $event->getId()]);
    }

    /**
     * Supprime un événement (uniquement si c'est un brouillon).
     */
    #[Route('/{id}/delete', name: 'organisateur_events_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        Event $event,
        Request $request,
        OrganizerProfileRepository $organizerProfileRepository,
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

        // Vérifier le token CSRF
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_event_' . $event->getId(), $token)) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('organisateur_events_index');
        }

        // Vérifier que l'événement est en brouillon
        if ($event->getStatut() !== Event::STATUS_DRAFT) {
            $this->addFlash('error', 'Seuls les événements en brouillon peuvent être supprimés.');
            return $this->redirectToRoute('organisateur_events_index');
        }

        $eventService->delete($event);
        $this->addFlash('success', 'L\'événement a été supprimé avec succès.');

        return $this->redirectToRoute('organisateur_events_index');
    }
}


