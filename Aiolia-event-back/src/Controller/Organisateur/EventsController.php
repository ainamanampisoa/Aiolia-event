<?php

namespace App\Controller\Organisateur;

use App\Entity\Event;
use App\Form\EventType;
use App\Repository\Organisateur\EventRepository;
use App\Repository\Organisateur\OrganizerProfileRepository;
use App\Service\Organisateur\EventService;
use App\Service\Organisateur\TypeBilletService;
use App\Service\Organisateur\EventTypeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/organisateur/events')]
#[IsGranted('ROLE_ORGANIZER')]
class EventsController extends AbstractController
{
    // Le repository peut être injecté directement dans les méthodes si nécessaire

    /**
     * Liste des événements de l'organisateur avec recherche multicritères et pagination
     */
    #[Route('', name: 'organisateur_events_index', methods: ['GET'])]
    public function index(
        EventService $eventService,
        EventTypeService $eventTypeService,
        TypeBilletService $typeBilletService,
        OrganizerProfileRepository $organizerProfileRepository,
        Request $request
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        // Récupérer le profil organisateur de l'utilisateur
        $organizerProfile = $organizerProfileRepository->findOneBy(['utilisateur' => $user]);
        if (!$organizerProfile) {
            throw $this->createAccessDeniedException('Profil organisateur non trouvé.');
        }

        // Récupérer les critères de recherche depuis la requête
        $criteria = [
            'idOrganisateur' => $organizerProfile->getId(),
            'nomLieu' => $request->query->get('nomLieu'),
            'dateDebut' => $request->query->get('dateDebut') ? new \DateTime($request->query->get('dateDebut')) : null,
            'dateFin' => $request->query->get('dateFin') ? new \DateTime($request->query->get('dateFin')) : null,
            'typeEvenementId' => $request->query->get('typeEvenementId'),
            'statut' => $request->query->get('statut'),
            'prixMin' => $request->query->get('prixMin') ? (float) $request->query->get('prixMin') : null,
            'prixMax' => $request->query->get('prixMax') ? (float) $request->query->get('prixMax') : null,
            'triPrix' => $request->query->get('triPrix'),
            'page' => $request->query->getInt('page', 1),
            'limit' => $request->query->getInt('limit', 6),
        ];

        // Normaliser les valeurs vides
        foreach ($criteria as $key => $value) {
            if ($value === '' || $value === '0') {
                $criteria[$key] = null;
            }
        }

        // Effectuer la recherche avec pagination
        $result = $eventService->searchMultiCriteriaWithPagination($criteria);
        $events = $result['items'];
        $pagination = $result['pagination'];

        // Calculer les statistiques globales (tous les événements de l'organisateur, pas seulement ceux filtrés)
        $allEventsCriteria = [
            'idOrganisateur' => $organizerProfile->getId(),
            'nomLieu' => null,
            'dateDebut' => null,
            'dateFin' => null,
            'typeEvenementId' => null,
            'statut' => null,
            'prixMin' => null,
            'prixMax' => null,
            'triPrix' => null,
            'page' => 1,
            'limit' => 10000, // Valeur élevée pour obtenir tous les événements
        ];
        $allEventsResult = $eventService->searchMultiCriteriaWithPagination($allEventsCriteria);
        $allEvents = $allEventsResult['items'];
        
        // Calculer les totaux globaux
        $totalEvents = count($allEvents);
        // Utiliser le fuseau horaire par défaut (Indian/Antananarivo)
        $now = new \DateTime('now', new \DateTimeZone('Indian/Antananarivo'));
        $nowTimestamp = $now->getTimestamp();
        $upcomingEvents = 0;
        $ongoingEvents = 0;
        
        foreach ($allEvents as $event) {
            if (!$event->getCommenceLe()) {
                continue;
            }
            
            $commenceLe = $event->getCommenceLe();
            $seTermineLe = $event->getSeTermineLe();
            $statut = $event->getStatut();
            
            // Convertir les dates en timestamps pour une comparaison fiable
            $commenceLeTimestamp = $commenceLe->getTimestamp();
            $seTermineLeTimestamp = $seTermineLe ? $seTermineLe->getTimestamp() : null;
            
            // Vérifier si l'événement a commencé
            $hasStarted = $commenceLeTimestamp <= $nowTimestamp;
            // Vérifier si l'événement est terminé
            $isFinished = $seTermineLeTimestamp !== null && $seTermineLeTimestamp < $nowTimestamp;
            
            // Un événement est "en cours" si :
            // - Il est publié ou archivé
            // - Il a commencé
            // - ET il n'est pas encore terminé
            if (in_array($statut, ['published', 'archived']) && $hasStarted && !$isFinished) {
                $ongoingEvents++;
            }
            
            // Un événement est "à venir" si :
            // - Il est publié
            // - Il n'a pas encore commencé
            if ($statut === 'published' && !$hasStarted) {
                $upcomingEvents++;
            }
        }
        
        $globalStats = [
            'total' => $totalEvents,
            'upcoming' => $upcomingEvents,
            'ongoing' => $ongoingEvents,
        ];

        // Calculer les statistiques pour chaque événement
        $eventsStats = [];
        foreach ($events as $event) {
            $ticketTypes = $typeBilletService->getByEvenement($event);
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
                
                // Filtrer les billets pour adultes (segment = 'adulte' ou 'tous')
                $segment = $ticketType->getConfigurationSegment();
                if ($segment && in_array($segment->getNom(), ['adulte', 'tous'])) {
                    $adultTicketTypes[] = [
                        'nom' => $ticketType->getNom(),
                        'prix' => $price,
                        'devise' => $ticketType->getDevise(),
                    ];
                }
            }
            
            // Trier les prix par ordre croissant
            usort($adultTicketTypes, function($a, $b) {
                return $a['prix'] <=> $b['prix'];
            });
            
            $eventsStats[$event->getId()] = [
                'totalTickets' => $totalTickets,
                'soldTickets' => $soldTickets,
                'minPrice' => $minPrice,
                'adultTicketTypes' => $adultTicketTypes,
            ];
        }

        // Récupérer les types d'événements pour le select
        $eventTypes = $eventTypeService->getAll();

        // Utilise le nouveau template unifié pour la liste des événements
        return $this->render('Organisateur/events/index.html.twig', [
            'events' => $events,
            'pagination' => $pagination,
            'criteria' => $criteria,
            'eventTypes' => $eventTypes,
            'eventsStats' => $eventsStats,
            'globalStats' => $globalStats,
        ]);
    }

    /**
     * Affiche le formulaire de création d'un événement
     */
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

        // Récupérer le profil organisateur de l'utilisateur
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

    /**
     * Vue calendrier des événements
     */
    #[Route('/calendar', name: 'organisateur_events_calendar', methods: ['GET'])]
    public function calendar(
        EventService $eventService,
        OrganizerProfileRepository $organizerProfileRepository,
        Request $request
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        // Récupérer le profil organisateur de l'utilisateur
        $organizerProfile = $organizerProfileRepository->findOneBy(['utilisateur' => $user]);
        if (!$organizerProfile) {
            throw $this->createAccessDeniedException('Profil organisateur non trouvé.');
        }

        // Récupérer le mois et l'année depuis la requête (par défaut: mois et année actuels)
        $year = $request->query->getInt('year', (int) date('Y'));
        $month = $request->query->getInt('month', (int) date('m'));
        
        // Valider le mois et l'année
        $month = max(1, min(12, $month));
        $year = max(2020, min(2100, $year));

        // Récupérer tous les événements de l'organisateur
        $allEventsCriteria = [
            'idOrganisateur' => $organizerProfile->getId(),
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
        $allEventsResult = $eventService->searchMultiCriteriaWithPagination($allEventsCriteria);
        $allEvents = $allEventsResult['items'];

        // Organiser les événements par date
        $eventsByDate = [];
        foreach ($allEvents as $event) {
            if ($event->getCommenceLe()) {
                $dateKey = $event->getCommenceLe()->format('Y-m-d');
                if (!isset($eventsByDate[$dateKey])) {
                    $eventsByDate[$dateKey] = [];
                }
                $eventsByDate[$dateKey][] = $event;
            }
        }

        // Calculer les informations du calendrier
        $firstDay = new \DateTime(sprintf('%d-%02d-01', $year, $month));
        $lastDay = new \DateTime($firstDay->format('Y-m-t'));
        $daysInMonth = (int) $lastDay->format('d');
        $startingDayOfWeek = (int) $firstDay->format('w'); // 0 = dimanche, 6 = samedi
        $adjustedStartingDay = $startingDayOfWeek == 0 ? 6 : $startingDayOfWeek - 1; // Ajuster pour lundi = 0
        
        $monthNames = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];

        return $this->render('Organisateur/events/calendar.html.twig', [
            'events' => $allEvents,
            'eventsByDate' => $eventsByDate,
            'currentYear' => $year,
            'currentMonth' => $month,
            'monthName' => $monthNames[$month],
            'daysInMonth' => $daysInMonth,
            'startingDayOfWeek' => $adjustedStartingDay,
        ]);
    }

    /**
     * Vue d'ensemble d'un événement pour l'organisateur
     */
    #[Route('/{id}', name: 'organisateur_events_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(
        Event $event,
        OrganizerProfileRepository $organizerProfileRepository
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        // Vérifier que l'événement appartient bien à l'organisateur connecté
        $organizerProfile = $organizerProfileRepository->findOneBy(['utilisateur' => $user]);
        if (!$organizerProfile || $event->getProfilOrganisateur()?->getId() !== $organizerProfile->getId()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cet événement.');
        }

        return $this->render('Organisateur/events/show.html.twig', [
            'event' => $event,
        ]);
    }

    /**
     * Statistiques d'un événement
     */
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

        // Vérifier que l'événement appartient bien à l'organisateur connecté
        $organizerProfile = $organizerProfileRepository->findOneBy(['utilisateur' => $user]);
        if (!$organizerProfile || $event->getProfilOrganisateur()?->getId() !== $organizerProfile->getId()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cet événement.');
        }

        // Types de billets associés à l'événement
        $ticketTypes = $typeBilletService->getByEvenement($event);

        // Statistiques pour le graphique d'évolution des ventes
        $statistics = $eventService->getEventStatistics($event);

        return $this->render('Organisateur/events/statistics.html.twig', [
            'event' => $event,
            'ticketTypes' => $ticketTypes,
            'statistics' => $statistics,
        ]);
    }

    /**
     * Informations détaillées d'un événement
     */
    #[Route('/{id}/information', name: 'organisateur_events_information', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function information(
        Event $event,
        OrganizerProfileRepository $organizerProfileRepository
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        // Vérifier que l'événement appartient bien à l'organisateur connecté
        $organizerProfile = $organizerProfileRepository->findOneBy(['utilisateur' => $user]);
        if (!$organizerProfile || $event->getProfilOrganisateur()?->getId() !== $organizerProfile->getId()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cet événement.');
        }

        // Organisateur principal + co-organisateurs
        $primaryOrganizer = $event->getProfilOrganisateur();
        $coOrganizers = $event->getOrganisateurs();

        return $this->render('Organisateur/events/information.html.twig', [
            'event' => $event,
            'primaryOrganizer' => $primaryOrganizer,
            'coOrganizers' => $coOrganizers,
        ]);
    }

    /**
     * Actions rapides pour un événement (redirige vers Informations)
     */
    #[Route('/{id}/actions', name: 'organisateur_events_actions', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function actions(
        Event $event,
        OrganizerProfileRepository $organizerProfileRepository
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        // Vérifier que l'événement appartient bien à l'organisateur connecté
        $organizerProfile = $organizerProfileRepository->findOneBy(['utilisateur' => $user]);
        if (!$organizerProfile || $event->getProfilOrganisateur()?->getId() !== $organizerProfile->getId()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cet événement.');
        }

        // Rediriger vers la page Informations où se trouvent maintenant les actions
        return $this->redirectToRoute('organisateur_events_information', ['id' => $event->getId()]);
    }

    /**
     * Export PDF d'un événement (statistiques + informations)
     */
    #[Route('/{id}/export-pdf', name: 'organisateur_events_export_pdf', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function exportPdf(
        Event $event,
        OrganizerProfileRepository $organizerProfileRepository,
        TypeBilletService $typeBilletService,
        EventService $eventService
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        // Vérifier que l'événement appartient bien à l'organisateur connecté
        $organizerProfile = $organizerProfileRepository->findOneBy(['utilisateur' => $user]);
        if (!$organizerProfile || $event->getProfilOrganisateur()?->getId() !== $organizerProfile->getId()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cet événement.');
        }

        try {
            // Récupérer les données nécessaires
            $primaryOrganizer = $event->getProfilOrganisateur();
            $coOrganizers = $event->getOrganisateurs();
            $ticketTypes = $typeBilletService->getByEvenement($event);
            $statistics = $eventService->getEventStatistics($event);

            // Générer le HTML pour le PDF
            $html = $this->renderView('Organisateur/events/pdf_export.html.twig', [
                'event' => $event,
                'primaryOrganizer' => $primaryOrganizer,
                'coOrganizers' => $coOrganizers,
                'ticketTypes' => $ticketTypes,
                'statistics' => $statistics,
            ]);
            
            // Nettoyer le HTML pour Dompdf
            $html = $this->cleanHtmlForPdf($html);
            
            // Post-traitement : convertir les SVG en images base64 pour une meilleure compatibilité
            $html = $this->convertSvgToBase64Images($html);

            // Configuration de Dompdf
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', false);
            $options->set('defaultFont', 'DejaVu Sans');
            $options->set('isFontSubsettingEnabled', true);
            $options->set('isPhpEnabled', false);
            $options->set('debugKeepTemp', false);
            $options->set('enableCssFloat', true);
            $options->set('chroot', realpath(__DIR__ . '/../../public'));
            
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'portrait');
            
            // Rendre le PDF
            $dompdf->render();
            
            // Vérifier qu'un PDF a bien été généré
            $output = $dompdf->output();
            
            if (empty($output) || strlen($output) < 100) {
                throw new \RuntimeException('La génération du PDF a échoué. Le fichier généré est vide ou invalide.');
            }
            
            // Vérifier que c'est bien un PDF (début par %PDF)
            if (substr($output, 0, 4) !== '%PDF') {
                throw new \RuntimeException('La génération du PDF a échoué. Le contenu généré n\'est pas un PDF valide.');
            }
            
            // Nom du fichier (nettoyer le slug pour éviter les caractères spéciaux)
            $slug = $event->getSlug() ?: 'evenement-' . $event->getId();
            $slug = preg_replace('/[^a-z0-9-]/', '-', strtolower($slug));
            $filename = 'evenement-' . $slug . '-' . date('Y-m-d') . '.pdf';
            
            // Créer la réponse avec le PDF
            $response = new Response($output);
            $response->headers->set('Content-Type', 'application/pdf');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
            $response->headers->set('Content-Length', (string) strlen($output));
            $response->headers->set('Cache-Control', 'private, max-age=0, must-revalidate');
            $response->headers->set('Pragma', 'public');
            
            return $response;
            
        } catch (\Exception $e) {
            // En cas d'erreur, retourner une réponse d'erreur
            throw new \RuntimeException('Erreur lors de la génération du PDF : ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Nettoie le HTML pour une meilleure compatibilité avec Dompdf
     */
    private function cleanHtmlForPdf(string $html): string
    {
        // Enlever les scripts
        $html = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/is', '', $html);
        
        // Enlever les styles inline problématiques
        $html = preg_replace('/style="[^"]*transition[^"]*"/i', '', $html);
        $html = preg_replace('/style="[^"]*hover[^"]*"/i', '', $html);
        
        // Simplifier les classes CSS complexes
        $html = preg_replace('/class="[^"]*hover[^"]*"/i', '', $html);
        
        return $html;
    }

    /**
     * Convertit les balises SVG en images base64 pour une meilleure compatibilité avec DomPDF
     */
    private function convertSvgToBase64Images(string $html): string
    {
        // Pattern pour trouver les balises SVG complètes (plus robuste)
        $pattern = '/<svg[^>]*>.*?<\/svg>/is';
        
        return preg_replace_callback($pattern, function ($matches) {
            try {
                $svgContent = $matches[0];
                
                // Nettoyer le SVG pour Dompdf
                // Enlever les attributs problématiques
                $svgContent = preg_replace('/xmlns:xlink="[^"]*"/i', '', $svgContent);
                $svgContent = preg_replace('/xlink:href="[^"]*"/i', '', $svgContent);
                $svgContent = preg_replace('/version="[^"]*"/i', '', $svgContent);
                
                // Nettoyer les espaces multiples mais garder la structure
                $svgContent = preg_replace('/\s+/', ' ', $svgContent);
                $svgContent = str_replace(['> <', '> <'], ['><', '><'], $svgContent);
                $svgContent = trim($svgContent);
                
                // Vérifier que le SVG est valide
                if (empty($svgContent) || !str_contains($svgContent, '<svg')) {
                    return ''; // Retourner vide si invalide
                }
                
                // Encoder en base64
                $base64 = base64_encode($svgContent);
                
                // Retourner une balise img avec le SVG encodé
                return '<img src="data:image/svg+xml;base64,' . $base64 . '" style="max-width: 100%; height: auto;" alt="Graphique" />';
            } catch (\Exception $e) {
                // En cas d'erreur, retourner un placeholder
                return '<div style="width: 100%; height: 200px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #666;">Graphique non disponible</div>';
            }
        }, $html);
    }
}


