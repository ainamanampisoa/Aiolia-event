<?php

namespace App\Controller\Organisateur;

use App\Entity\Event;
use App\Entity\EventCategory;
use App\Form\EventType;
use App\Repository\Organisateur\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/events')]
#[IsGranted('ROLE_ORGANIZER')]
class EventController extends AbstractController
{
    /**
     * Liste tous les événements
     */
    #[Route('', name: 'app_event_index', methods: ['GET'])]
    public function index(EventRepository $eventRepository): Response
    {
        // Si l'utilisateur est connecté, afficher ses événements
        if ($this->getUser()) {
            $events = $eventRepository->findByOrganizer($this->getUser());
        } else {
            $events = $eventRepository->findUpcomingEvents();
        }

        return $this->render('event/index.html.twig', [
            'events' => $events,
        ]);
    }

    /**
     * Affiche un événement
     */
    #[Route('/{id}', name: 'app_event_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Event $event, EventRepository $eventRepository): Response
    {
        // TODO: Implémenter l'incrémentation du compteur de vues si nécessaire
        // Pour l'instant, on ne fait rien car la méthode n'existe pas dans l'entité

        // Récupérer les statistiques
        $statistics = $eventRepository->getEventStatistics($event);

        return $this->render('event/show.html.twig', [
            'event' => $event,
            'statistics' => $statistics,
        ]);
    }

    /**
     * Crée un nouvel événement
     */
    #[Route('/new', name: 'app_event_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $event = new Event();
        $event->setOrganizer($this->getUser());
        
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Générer le slug
            $slug = $slugger->slug($event->getTitle())->lower();
            $event->setSlug($slug);

            $entityManager->persist($event);
            $entityManager->flush();

            $this->addFlash('success', 'L\'événement a été créé avec succès.');

            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        return $this->render('Organisateur/event/new.html.twig', [
            'event' => $event,
            'form' => $form,
        ]);
    }

    /**
     * Modifie un événement
     */
    #[Route('/{id}/edit', name: 'app_event_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(
        Request $request,
        Event $event,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        // Vérifier que l'utilisateur est l'organisateur
        if ($event->getOrganizer() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas les droits pour modifier cet événement.');
        }

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Régénérer le slug si le titre a changé
            $slug = $slugger->slug($event->getTitle())->lower();
            $event->setSlug($slug);

            $entityManager->flush();

            $this->addFlash('success', 'L\'événement a été modifié avec succès.');

            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        return $this->render('event/edit.html.twig', [
            'event' => $event,
            'form' => $form,
        ]);
    }

    /**
     * Supprime un événement
     */
    #[Route('/{id}/delete', name: 'app_event_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(
        Request $request,
        Event $event,
        EntityManagerInterface $entityManager
    ): Response {
        // Vérifier que l'utilisateur est l'organisateur
        if ($event->getOrganizer() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas les droits pour supprimer cet événement.');
        }

        // Vérifier le token CSRF
        if ($this->isCsrfTokenValid('delete'.$event->getId(), $request->request->get('_token'))) {
            $entityManager->remove($event);
            $entityManager->flush();

            $this->addFlash('success', 'L\'événement a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_event_index');
    }

    /**
     * Publie un événement
     */
    #[Route('/{id}/publish', name: 'app_event_publish', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function publish(
        Request $request,
        Event $event,
        EntityManagerInterface $entityManager
    ): Response {
        // Vérifier que l'utilisateur est l'organisateur
        if ($event->getOrganizer() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas les droits pour publier cet événement.');
        }

        // Vérifier le token CSRF
        if ($this->isCsrfTokenValid('publish'.$event->getId(), $request->request->get('_token'))) {
            $event->setStatus(Event::STATUS_PUBLISHED);
            $entityManager->flush();

            $this->addFlash('success', 'L\'événement a été publié avec succès.');
        }

        return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
    }

    /**
     * Duplique un événement
     */
    #[Route('/{id}/duplicate', name: 'app_event_duplicate', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function duplicate(
        Request $request,
        Event $event,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // Vérifier le token CSRF
        if ($this->isCsrfTokenValid('duplicate'.$event->getId(), $request->request->get('_token'))) {
            $newEvent = new Event();
            $newEvent->setOrganizer($this->getUser());
            $newEvent->setPrimaryCategory($event->getPrimaryCategory());
            $newEvent->setTitle($event->getTitle() . ' (Copie)');
            $newEvent->setSlug($slugger->slug($newEvent->getTitle())->lower() . '-' . uniqid());
            $newEvent->setDescription($event->getDescription());
            $newEvent->setSubtitle($event->getSubtitle());
            $newEvent->setSummary($event->getSummary());
            $newEvent->setTimezone($event->getTimezone());
            $newEvent->setCapacity($event->getCapacity());
            $newEvent->setStatus(Event::STATUS_DRAFT);

            $entityManager->persist($newEvent);
            $entityManager->flush();

            $this->addFlash('success', 'L\'événement a été dupliqué avec succès.');

            return $this->redirectToRoute('app_event_edit', ['id' => $newEvent->getId()]);
        }

        return $this->redirectToRoute('app_event_index');
    }

    /**
     * Recherche d'événements
     */
    #[Route('/search', name: 'app_event_search', methods: ['GET'])]
    public function search(Request $request, EventRepository $eventRepository): Response
    {
        $query = $request->query->get('q', '');
        $filters = [
            'category' => $request->query->get('category'),
            'startDate' => $request->query->get('startDate'),
            'endDate' => $request->query->get('endDate'),
            'location' => $request->query->get('location'),
        ];

        $events = $eventRepository->searchEvents($query, $filters);

        return $this->render('event/search.html.twig', [
            'events' => $events,
            'query' => $query,
            'filters' => $filters,
        ]);
    }

    /**
     * Liste des événements par catégorie
     */
    #[Route('/category/{slug}', name: 'app_event_by_category', methods: ['GET'])]
    public function byCategory(
        string $slug,
        EventRepository $eventRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $categoryRepository = $entityManager->getRepository(EventCategory::class);
        $category = $categoryRepository->findBySlug($slug);

        if (!$category) {
            throw $this->createNotFoundException('Catégorie non trouvée');
        }

        $events = $eventRepository->findByCategory($category);

        return $this->render('event/by_category.html.twig', [
            'category' => $category,
            'events' => $events,
        ]);
    }
}

