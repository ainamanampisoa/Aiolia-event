<?php

namespace App\Service\Organisateur;

use App\Entity\Event;
use App\Entity\EventMedia;
use App\Entity\User;
use App\Repository\Organisateur\EventRepository;
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
        $event->setStatus(Event::STATUS_PUBLISHED);
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
        $newEvent->setPrimaryCategory($originalEvent->getPrimaryCategory());
        $newEvent->setTitle($originalEvent->getTitle() . ' (Copie)');
        $newEvent->setSlug($this->generateUniqueSlug($originalEvent->getTitle() . ' Copie'));
        $newEvent->setDescription($originalEvent->getDescription());
        $newEvent->setSubtitle($originalEvent->getSubtitle());
        $newEvent->setSummary($originalEvent->getSummary());
        $newEvent->setTimezone($originalEvent->getTimezone());
        $newEvent->setCapacity($originalEvent->getCapacity());
        $newEvent->setStatus(Event::STATUS_DRAFT);

        $this->entityManager->persist($newEvent);
        $this->entityManager->flush();

        return $newEvent;
    }

    /**
     * Récupère les événements à venir
     */
    public function getUpcomingEvents(int $limit = 0): array
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
            $event->setPrimaryCategory($data['category']);
        }

        if (isset($data['description'])) {
            $event->setDescription($data['description']);
        }

        if (isset($data['summary'])) {
            $event->setSummary($data['summary']);
        }

        if (isset($data['subtitle'])) {
            $event->setSubtitle($data['subtitle']);
        }

        if (isset($data['shortDescription'])) {
            $event->setSubtitle($data['shortDescription']);
        }

        if (isset($data['timezone'])) {
            $event->setTimezone($data['timezone']);
        }

        if (isset($data['startDate']) || isset($data['startsAt'])) {
            $event->setStartsAt($data['startsAt'] ?? $data['startDate']);
        }

        if (isset($data['endDate']) || isset($data['endsAt'])) {
            $event->setEndsAt($data['endsAt'] ?? $data['endDate']);
        }

        if (isset($data['capacity']) || isset($data['totalCapacity'])) {
            $event->setCapacity($data['capacity'] ?? $data['totalCapacity']);
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

