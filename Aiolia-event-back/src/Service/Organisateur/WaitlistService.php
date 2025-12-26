<?php

namespace App\Service\Organisateur;

use App\Entity\Event;
use App\Repository\Organisateur\WaitlistRepository;

class WaitlistService
{
    public function __construct(
        private WaitlistRepository $waitlistRepository
    ) {
    }

    /**
     * Récupère tous les utilisateurs avec le nombre de billets
     * pour les événements en cours et à venir
     *
     * @param string|null $organizerProfileId ID du profil organisateur (optionnel)
     * @return array Liste des utilisateurs avec leurs statistiques
     */
    public function getAllUsersWithTicketCount(?string $organizerProfileId = null): array
    {
        return $this->waitlistRepository->findAllUsersWithTicketCount($organizerProfileId);
    }

    /**
     * Récupère tous les utilisateurs avec le nombre de billets (paginé)
     * pour les événements en cours et à venir
     *
     * @param string|null $organizerProfileId ID du profil organisateur (optionnel)
     * @param int $page Numéro de page
     * @param int $perPage Nombre d'éléments par page
     * @return array Données paginées
     */
    public function getAllUsersWithTicketCountPaginated(?string $organizerProfileId = null, int $page = 1, int $perPage = 20): array
    {
        return $this->waitlistRepository->findAllUsersWithTicketCountPaginated($organizerProfileId, $page, $perPage);
    }

    /**
     * Récupère les événements en cours et à venir pour un organisateur
     *
     * @param string $organizerProfileId ID du profil organisateur
     * @return array Liste des événements
     */
    public function getOngoingAndUpcomingEvents(string $organizerProfileId): array
    {
        return $this->waitlistRepository->findOngoingAndUpcomingEvents($organizerProfileId);
    }

    /**
     * Récupère les utilisateurs avec leurs billets pour un événement spécifique
     *
     * @param string $eventId ID de l'événement
     * @return array Liste des utilisateurs avec leurs billets
     */
    public function getUsersWithTicketsForEvent(string $eventId): array
    {
        return $this->waitlistRepository->findUsersWithTicketsForEvent($eventId);
    }

    /**
     * Récupère les statistiques globales de la liste d'attente
     *
     * @param string|null $organizerProfileId ID du profil organisateur (optionnel)
     * @return array Statistiques
     */
    public function getWaitlistStatistics(?string $organizerProfileId = null): array
    {
        return $this->waitlistRepository->getWaitlistStatistics($organizerProfileId);
    }

    /**
     * Formate les données pour l'affichage dans la vue
     *
     * @param string|null $organizerProfileId ID du profil organisateur (optionnel)
     * @return array Données formatées
     */
    public function getFormattedWaitlistData(?string $organizerProfileId = null): array
    {
        $users = $this->getAllUsersWithTicketCount($organizerProfileId);
        $events = $organizerProfileId ? $this->getOngoingAndUpcomingEvents($organizerProfileId) : [];
        $statistics = $this->getWaitlistStatistics($organizerProfileId);
        
        return [
            'users' => $users,
            'events' => $events,
            'statistics' => $statistics,
        ];
    }

    /**
     * Formate les données paginées pour l'affichage dans la vue
     *
     * @param string|null $organizerProfileId ID du profil organisateur (optionnel)
     * @param int $page Numéro de page
     * @param int $perPage Nombre d'éléments par page
     * @return array Données formatées avec pagination
     */
    public function getFormattedWaitlistDataPaginated(?string $organizerProfileId = null, int $page = 1, int $perPage = 20): array
    {
        $paginationData = $this->getAllUsersWithTicketCountPaginated($organizerProfileId, $page, $perPage);
        $statistics = $this->getWaitlistStatistics($organizerProfileId);
        
        return [
            'users' => $paginationData['items'],
            'pagination' => [
                'total' => $paginationData['total'],
                'pages' => $paginationData['pages'],
                'currentPage' => $paginationData['currentPage'],
                'perPage' => $paginationData['perPage'],
            ],
            'statistics' => $statistics,
        ];
    }

    /**
     * Formate les données paginées groupées par événement pour l'affichage dans la vue
     *
     * @param string|null $organizerProfileId ID du profil organisateur (optionnel)
     * @param int $page Numéro de page
     * @param int $perPage Nombre d'éléments par page
     * @return array Données formatées avec pagination
     */
    public function getFormattedWaitlistDataByEventPaginated(?string $organizerProfileId = null, int $page = 1, int $perPage = 20): array
    {
        $paginationData = $this->waitlistRepository->findEventsWithWaitlistPaginated($organizerProfileId, $page, $perPage);
        $statistics = $this->getWaitlistStatistics($organizerProfileId);
        
        return [
            'events' => $paginationData['items'],
            'pagination' => [
                'total' => $paginationData['total'],
                'pages' => $paginationData['pages'],
                'currentPage' => $paginationData['currentPage'],
                'perPage' => $paginationData['perPage'],
            ],
            'statistics' => $statistics,
        ];
    }
}

