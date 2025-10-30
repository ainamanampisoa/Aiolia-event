<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EventController extends AbstractController
{
    #[Route('/events', name: 'events')]
    public function listEvents(Request $request): Response
    {
        // TODO: Récupérer les événements depuis la base de données avec filtres
        $events = [
            [
                'id' => 1,
                'title' => 'Music on Sunday',
                'category' => ['name' => 'Soirée live'],
                'startDate' => new \DateTime('2025-07-20 20:00:00'),
                'location' => 'Analakely au Café de la Gare',
                'minPrice' => 50000,
                'maxPrice' => 150000,
                'image' => null,
            ],
            [
                'id' => 2,
                'title' => 'Concert Jazz',
                'category' => ['name' => 'Concert'],
                'startDate' => new \DateTime('2025-08-15 19:30:00'),
                'location' => 'Théâtre Municipal',
                'minPrice' => 30000,
                'maxPrice' => 80000,
                'image' => null,
            ]
        ];

        $categories = [
            ['id' => 1, 'name' => 'Concert'],
            ['id' => 2, 'name' => 'Sport'],
            ['id' => 3, 'name' => 'Conférence'],
            ['id' => 4, 'name' => 'Théâtre'],
            ['id' => 5, 'name' => 'Festival'],
            ['id' => 6, 'name' => 'Exposition'],
            ['id' => 7, 'name' => 'Formation'],
            ['id' => 8, 'name' => 'Autre']
        ];

        $locations = ['Antananarivo', 'Toamasina', 'Antsirabe', 'Mahajanga', 'Fianarantsoa'];

        return $this->render('event/list.html.twig', [
            'events' => $events,
            'categories' => $categories,
            'locations' => $locations,
        ]);
    }

    #[Route('/events/{id}', name: 'event_details')]
    public function showEvent(int $id): Response
    {
        // TODO: Récupérer l'événement depuis la base de données
        $event = [
            'id' => $id,
            'title' => 'Music on Sunday',
            'description' => '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p><p>Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>',
            'category' => ['name' => 'Soirée live'],
            'startDate' => new \DateTime('2025-07-20 20:00:00'),
            'location' => 'Analakely au Café de la Gare',
            'minPrice' => 50000,
            'maxPrice' => 150000,
            'image' => null,
            'organizer' => ['firstName' => 'Jean', 'lastName' => 'Dupont'],
            'ticketCategories' => [
                [
                    'id' => 1, 
                    'name' => 'Offre VIP', 
                    'price' => 150000, 
                    'description' => 'Accès VIP avec boissons incluses',
                    'availableTickets' => 50
                ],
                [
                    'id' => 2, 
                    'name' => 'Standard', 
                    'price' => 50000, 
                    'description' => 'Accès standard',
                    'availableTickets' => 200
                ]
            ],
            'totalTicketsSold' => 75,
            'maxCapacity' => 250
        ];

        return $this->render('event/details.html.twig', [
            'event' => $event,
        ]);
    }

    #[Route('/api/events', name: 'api_events_list', methods: ['GET'])]
    public function listEventsApi(Request $request): JsonResponse
    {
        // TODO: Récupérer la liste des événements avec filtres
        return new JsonResponse([
            'message' => 'Liste des événements - À implémenter',
            'status' => 'success',
            'data' => []
        ]);
    }

    #[Route('/api/events/{id}', name: 'api_events_show', methods: ['GET'])]
    public function showEventApi(int $id): JsonResponse
    {
        // TODO: Récupérer les détails d'un événement
        return new JsonResponse([
            'message' => "Détails de l'événement {$id} - À implémenter",
            'status' => 'success',
            'data' => []
        ]);
    }

    #[Route('/api/events/search', name: 'api_events_search', methods: ['GET'])]
    public function searchEvents(Request $request): JsonResponse
    {
        // TODO: Rechercher des événements par critères
        $query = $request->query->get('q', '');
        $category = $request->query->get('category', '');
        $city = $request->query->get('city', '');
        $dateFrom = $request->query->get('date_from', '');
        $dateTo = $request->query->get('date_to', '');

        return new JsonResponse([
            'message' => 'Recherche d\'événements - À implémenter',
            'status' => 'success',
            'filters' => [
                'query' => $query,
                'category' => $category,
                'city' => $city,
                'date_from' => $dateFrom,
                'date_to' => $dateTo
            ],
            'data' => []
        ]);
    }

    #[Route('/api/events/{id}/tickets', name: 'api_events_tickets', methods: ['GET'])]
    public function getEventTickets(int $id): JsonResponse
    {
        // TODO: Récupérer les catégories de billets disponibles pour un événement
        return new JsonResponse([
            'message' => "Catégories de billets pour l'événement {$id} - À implémenter",
            'status' => 'success',
            'data' => []
        ]);
    }

    #[Route('/api/events/{id}/favorite', name: 'api_events_favorite', methods: ['POST'])]
    public function addToFavorites(int $id): JsonResponse
    {
        // TODO: Ajouter un événement aux favoris
        return new JsonResponse([
            'message' => "Ajout aux favoris de l'événement {$id} - À implémenter",
            'status' => 'success'
        ]);
    }

    #[Route('/api/events/{id}/favorite', name: 'api_events_unfavorite', methods: ['DELETE'])]
    public function removeFromFavorites(int $id): JsonResponse
    {
        // TODO: Retirer un événement des favoris
        return new JsonResponse([
            'message' => "Suppression des favoris de l'événement {$id} - À implémenter",
            'status' => 'success'
        ]);
    }

    #[Route('/api/events/categories', name: 'api_events_categories', methods: ['GET'])]
    public function getCategories(): JsonResponse
    {
        // TODO: Récupérer la liste des catégories d'événements
        return new JsonResponse([
            'message' => 'Liste des catégories d\'événements - À implémenter',
            'status' => 'success',
            'data' => [
                'Concert',
                'Sport',
                'Conférence',
                'Théâtre',
                'Festival',
                'Exposition',
                'Formation',
                'Autre'
            ]
        ]);
    }
}
