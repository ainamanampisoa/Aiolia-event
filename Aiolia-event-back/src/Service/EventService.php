<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\EventMedia;
use App\Entity\User;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class EventService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventRepository $eventRepository,
        private SluggerInterface $slugger
    ) {
    }

    /**
     * Crée un nouvel événement
     */
    public function createEvent(array $data, User $organizer): Event
    {
        $event = new Event();
        $event->setOrganizer($organizer);
        
        $this->updateEventFromData($event, $data);

        $this->entityManager->persist($event);
        $this->entityManager->flush();

        return $event;
    }

    /**
     * Met à jour un événement
     */
    public function updateEvent(Event $event, array $data): Event
    {
        $this->updateEventFromData($event, $data);
        
        $this->entityManager->flush();

        return $event;
    }

    /**
     * Supprime un événement
     */
    public function deleteEvent(Event $event): void
    {
        $this->entityManager->remove($event);
        $this->entityManager->flush();
    }

    /**
     * Publie un événement
     */
    public function publishEvent(Event $event): Event
    {
        $event->publish();
        $this->entityManager->flush();

        return $event;
    }

    /**
     * Annule un événement
     */
    public function cancelEvent(Event $event, string $reason = null): Event
    {
        $event->setStatus('cancelled');
        $this->entityManager->flush();

        // TODO: Envoyer des notifications aux participants

        return $event;
    }

    /**
     * Duplique un événement
     */
    public function duplicateEvent(Event $originalEvent, User $organizer): Event
    {
        $newEvent = new Event();
        $newEvent->setOrganizer($organizer);
        $newEvent->setCategory($originalEvent->getCategory());
        $newEvent->setTitle($originalEvent->getTitle() . ' (Copie)');
        $newEvent->setSlug($this->generateUniqueSlug($originalEvent->getTitle() . ' Copie'));
        $newEvent->setDescription($originalEvent->getDescription());
        $newEvent->setShortDescription($originalEvent->getShortDescription());
        $newEvent->setLocation($originalEvent->getLocation());
        $newEvent->setAddress($originalEvent->getAddress());
        $newEvent->setLatitude($originalEvent->getLatitude());
        $newEvent->setLongitude($originalEvent->getLongitude());
        $newEvent->setTimezone($originalEvent->getTimezone());
        $newEvent->setTotalCapacity($originalEvent->getTotalCapacity());
        $newEvent->setStatus('draft');

        $this->entityManager->persist($newEvent);
        $this->entityManager->flush();

        return $newEvent;
    }

    /**
     * Récupère les événements à venir
     */
    public function getUpcomingEvents(int $limit = null): array
    {
        return $this->eventRepository->findUpcomingEvents($limit);
    }

    /**
     * Récupère les événements en vedette
     */
    public function getFeaturedEvents(int $limit = 6): array
    {
        return $this->eventRepository->findFeaturedEvents($limit);
    }

    /**
     * Recherche des événements
     */
    public function searchEvents(string $query, array $filters = []): array
    {
        return $this->eventRepository->searchEvents($query, $filters);
    }

    /**
     * Récupère les statistiques d'un événement
     */
    public function getEventStatistics(Event $event): array
    {
        return $this->eventRepository->getEventStatistics($event);
    }

    /**
     * Vérifie si un utilisateur peut modifier un événement
     */
    public function canEdit(Event $event, User $user): bool
    {
        return $event->getOrganizer() === $user || in_array('ROLE_ADMIN', $user->getRoles());
    }

    /**
     * Vérifie si un utilisateur peut supprimer un événement
     */
    public function canDelete(Event $event, User $user): bool
    {
        return $event->getOrganizer() === $user || in_array('ROLE_ADMIN', $user->getRoles());
    }

    /**
     * Met à jour un événement depuis un tableau de données
     */
    private function updateEventFromData(Event $event, array $data): void
    {
        if (isset($data['title'])) {
            $event->setTitle($data['title']);
            if (!$event->getSlug()) {
                $event->setSlug($this->generateUniqueSlug($data['title']));
            }
        }

        if (isset($data['category'])) {
            $event->setCategory($data['category']);
        }

        if (isset($data['description'])) {
            $event->setDescription($data['description']);
        }

        if (isset($data['shortDescription'])) {
            $event->setShortDescription($data['shortDescription']);
        }

        if (isset($data['location'])) {
            $event->setLocation($data['location']);
        }

        if (isset($data['address'])) {
            $event->setAddress($data['address']);
        }

        if (isset($data['latitude'])) {
            $event->setLatitude($data['latitude']);
        }

        if (isset($data['longitude'])) {
            $event->setLongitude($data['longitude']);
        }

        if (isset($data['startDate'])) {
            $event->setStartDate($data['startDate']);
        }

        if (isset($data['endDate'])) {
            $event->setEndDate($data['endDate']);
        }

        if (isset($data['timezone'])) {
            $event->setTimezone($data['timezone']);
        }

        if (isset($data['totalCapacity'])) {
            $event->setTotalCapacity($data['totalCapacity']);
        }

        if (isset($data['status'])) {
            $event->setStatus($data['status']);
        }

        if (isset($data['isFeatured'])) {
            $event->setIsFeatured($data['isFeatured']);
        }
    }

    /**
     * Génère un slug unique
     */
    private function generateUniqueSlug(string $title): string
    {
        $slug = $this->slugger->slug($title)->lower();
        $originalSlug = $slug;
        $counter = 1;

        // Vérifier si le slug existe déjà
        while ($this->eventRepository->findOneBy(['slug' => $slug])) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}

