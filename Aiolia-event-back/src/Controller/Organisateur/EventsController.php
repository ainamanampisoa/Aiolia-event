<?php

namespace App\Controller\Organisateur;

use App\Entity\Event;
use App\Form\EventType;
use App\Repository\Organisateur\EventRepository;
use App\Repository\Organisateur\OrganizerProfileRepository;
use App\Service\Organisateur\EventService;
use App\Service\Organisateur\EventTypeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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

        // Récupérer les types d'événements pour le select
        $eventTypes = $eventTypeService->getAll();

        // Utilise le nouveau template unifié pour la liste des événements
        return $this->render('Organisateur/events/index.html.twig', [
            'events' => $events,
            'pagination' => $pagination,
            'criteria' => $criteria,
            'eventTypes' => $eventTypes,
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
}


