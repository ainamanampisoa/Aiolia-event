<?php

namespace App\Controller\Api;

use App\DTO\EventFilterDTO;
use App\Entity\Event;
use App\Repository\EventRepository;
use App\Service\EventService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/events', name: 'api_event_')]
class EventApiController extends AbstractController
{
    public function __construct(
        private EventRepository $eventRepository,
        private EventService $eventService
    ) {
    }

    /**
     * Liste des événements (API)
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $filters = EventFilterDTO::fromArray($request->query->all());
        
        $events = $this->eventRepository->searchEvents(
            $filters->getQuery() ?? '',
            $filters->toArray()
        );

        return $this->json([
            'success' => true,
            'data' => array_map(fn($event) => $this->serializeEvent($event), $events),
            'total' => count($events),
        ]);
    }

    /**
     * Détails d'un événement (API)
     */
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Event $event): JsonResponse
    {
        // Incrémenter les vues
        $event->incrementViewsCount();
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'data' => $this->serializeEvent($event, true),
        ]);
    }

    /**
     * Événements à venir (API)
     */
    #[Route('/upcoming', name: 'upcoming', methods: ['GET'])]
    public function upcoming(Request $request): JsonResponse
    {
        $limit = $request->query->getInt('limit', 10);
        $events = $this->eventService->getUpcomingEvents($limit);

        return $this->json([
            'success' => true,
            'data' => array_map(fn($event) => $this->serializeEvent($event), $events),
        ]);
    }

    /**
     * Événements en vedette (API)
     */
    #[Route('/featured', name: 'featured', methods: ['GET'])]
    public function featured(Request $request): JsonResponse
    {
        $limit = $request->query->getInt('limit', 6);
        $events = $this->eventService->getFeaturedEvents($limit);

        return $this->json([
            'success' => true,
            'data' => array_map(fn($event) => $this->serializeEvent($event), $events),
        ]);
    }

    /**
     * Recherche d'événements (API)
     */
    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $filters = EventFilterDTO::fromArray($request->query->all());

        $events = $this->eventService->searchEvents($query, $filters->toArray());

        return $this->json([
            'success' => true,
            'data' => array_map(fn($event) => $this->serializeEvent($event), $events),
            'query' => $query,
        ]);
    }

    /**
     * Statistiques d'un événement (API)
     */
    #[Route('/{id}/statistics', name: 'statistics', methods: ['GET'])]
    public function statistics(Event $event): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // Vérifier les permissions
        if (!$this->eventService->canEdit($event, $this->getUser())) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $statistics = $this->eventService->getEventStatistics($event);

        return $this->json([
            'success' => true,
            'data' => $statistics,
        ]);
    }

    /**
     * Sérialise un événement pour l'API
     */
    private function serializeEvent(Event $event, bool $detailed = false): array
    {
        $data = [
            'id' => $event->getId(),
            'title' => $event->getTitle(),
            'slug' => $event->getSlug(),
            'shortDescription' => $event->getShortDescription(),
            'location' => $event->getLocation(),
            'startDate' => $event->getStartDate()?->format('c'),
            'endDate' => $event->getEndDate()?->format('c'),
            'status' => $event->getStatus(),
            'isFeatured' => $event->isFeatured(),
            'viewsCount' => $event->getViewsCount(),
            'category' => [
                'id' => $event->getCategory()?->getId(),
                'name' => $event->getCategory()?->getName(),
                'slug' => $event->getCategory()?->getSlug(),
            ],
            'organizer' => [
                'id' => $event->getOrganizer()?->getId(),
                'name' => $event->getOrganizer()?->getFullName(),
            ],
        ];

        if ($detailed) {
            $data['description'] = $event->getDescription();
            $data['address'] = $event->getAddress();
            $data['latitude'] = $event->getLatitude();
            $data['longitude'] = $event->getLongitude();
            $data['timezone'] = $event->getTimezone();
            $data['totalCapacity'] = $event->getTotalCapacity();
            $data['createdAt'] = $event->getCreatedAt()?->format('c');
            $data['updatedAt'] = $event->getUpdatedAt()?->format('c');
            $data['publishedAt'] = $event->getPublishedAt()?->format('c');
        }

        return $data;
    }
}

