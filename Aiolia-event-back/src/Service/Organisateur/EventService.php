<?php

namespace App\Service\Organisateur;

use App\Entity\Event;
use App\Entity\EventCategory;
use App\Entity\EventType;
use App\Entity\EspaceLieu;
use App\Entity\OrganizerProfile;
use App\Entity\User;
use App\Entity\Venue;
use App\Repository\Organisateur\EventRepository;
use App\Service\Organisateur\EventTypeService;
use App\Service\Organisateur\EspaceLieuService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class EventService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventRepository $eventRepository,
        private EventTypeService $eventTypeService,
        private EspaceLieuService $espaceLieuService,
        private SluggerInterface $slugger
    ) {
    }

    /**
     * Récupère tous les événements
     */
    public function getAll(): array
    {
        return $this->eventRepository->getAll();
    }

    /**
     * Récupère un événement par son ID
     */
    public function getById(string $id): ?Event
    {
        return $this->eventRepository->getById($id);
    }

    /**
     * Crée un nouvel événement
     */
    public function create(array $data, ?OrganizerProfile $organizerProfile = null): Event
    {
        $event = new Event();

        if ($organizerProfile !== null) {
            $event->setProfilOrganisateur($organizerProfile);
        }

        $this->updateEventFromData($event, $data);

        return $this->eventRepository->create($event);
    }

    /**
     * Met à jour un événement
     */
    public function update(Event $event, array $data): Event
    {
        $this->updateEventFromData($event, $data);

        return $this->eventRepository->update($event);
    }

    /**
     * Supprime un événement
     */
    public function delete(Event $event): void
    {
        $this->eventRepository->delete($event);
    }

    /**
     * Publie un événement
     */
    public function publishEvent(Event $event): Event
    {
        $event->setStatut(Event::STATUS_PUBLISHED);
        return $this->eventRepository->update($event);
    }

    /**
     * Annule un événement
     */
    public function cancelEvent(Event $event): Event
    {
        $event->setStatut(Event::STATUS_CANCELLED);
        return $this->eventRepository->update($event);
    }

    /**
     * Archive un événement
     */
    public function archiveEvent(Event $event): Event
    {
        $event->setStatut(Event::STATUS_ARCHIVED);
        return $this->eventRepository->update($event);
    }

    /**
     * Duplique un événement
     */
    public function duplicateEvent(Event $originalEvent, ?OrganizerProfile $organizerProfile = null): Event
    {
        $newEvent = new Event();
        
        if ($organizerProfile !== null) {
            $newEvent->setProfilOrganisateur($organizerProfile);
        }

        $newEvent->setCategoriePrincipale($originalEvent->getCategoriePrincipale());
        $newEvent->setTypeEvenement($originalEvent->getTypeEvenement());
        $newEvent->setLieu($originalEvent->getLieu());
        $newEvent->setEspacePrincipal($originalEvent->getEspacePrincipal());
        $newEvent->setTitre($originalEvent->getTitre() . ' (Copie)');
        $newEvent->setSlug($this->generateUniqueSlug($originalEvent->getTitre() . ' Copie'));
        $newEvent->setDescription($originalEvent->getDescription());
        $newEvent->setSousTitre($originalEvent->getSousTitre());
        $newEvent->setResume($originalEvent->getResume());
        $newEvent->setFuseauHoraire($originalEvent->getFuseauHoraire());
        $newEvent->setCapacite($originalEvent->getCapacite());
        $newEvent->setStatut(Event::STATUS_DRAFT);
        $newEvent->setVisibilite($originalEvent->getVisibilite());
        $newEvent->setFormatEvenement($originalEvent->getFormatEvenement());

        return $this->eventRepository->create($newEvent);
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
        $organizerProfile = $event->getProfilOrganisateur();
        if ($organizerProfile === null) {
            return in_array('ROLE_ADMIN', $user->getRoles());
        }

        return $organizerProfile->getUtilisateur() === $user || in_array('ROLE_ADMIN', $user->getRoles());
    }

    /**
     * Vérifie si un utilisateur peut supprimer un événement
     */
    public function canDelete(Event $event, User $user): bool
    {
        return $this->canEdit($event, $user);
    }

    /**
     * Met à jour un événement depuis un tableau de données
     */
    private function updateEventFromData(Event $event, array $data): void
    {
        if (isset($data['titre'])) {
            $event->setTitre($data['titre']);
            if (!$event->getSlug()) {
                $event->setSlug($this->generateUniqueSlug($data['titre']));
            }
        }

        if (isset($data['profilOrganisateur']) && $data['profilOrganisateur'] instanceof OrganizerProfile) {
            $event->setProfilOrganisateur($data['profilOrganisateur']);
        } elseif (isset($data['profilOrganisateurId'])) {
            // Si on passe juste l'ID, on doit charger l'entité
            $organizerProfile = $this->entityManager->getRepository(OrganizerProfile::class)->find($data['profilOrganisateurId']);
            if ($organizerProfile) {
                $event->setProfilOrganisateur($organizerProfile);
            }
        }

        if (isset($data['categoriePrincipale']) && $data['categoriePrincipale'] instanceof EventCategory) {
            $event->setCategoriePrincipale($data['categoriePrincipale']);
        } elseif (isset($data['categoriePrincipaleId'])) {
            $category = $this->entityManager->getRepository(EventCategory::class)->find($data['categoriePrincipaleId']);
            if ($category) {
                $event->setCategoriePrincipale($category);
            }
        }

        if (isset($data['typeEvenement']) && $data['typeEvenement'] instanceof EventType) {
            $event->setTypeEvenement($data['typeEvenement']);
        } elseif (isset($data['typeEvenementId'])) {
            $type = $this->eventTypeService->getById($data['typeEvenementId']);
            if ($type) {
                $event->setTypeEvenement($type);
            }
        }

        if (isset($data['lieu']) && $data['lieu'] instanceof Venue) {
            $event->setLieu($data['lieu']);
        } elseif (isset($data['lieuId'])) {
            $venue = $this->entityManager->getRepository(Venue::class)->find($data['lieuId']);
            if ($venue) {
                $event->setLieu($venue);
            }
        }

        if (isset($data['espacePrincipal']) && $data['espacePrincipal'] instanceof EspaceLieu) {
            $event->setEspacePrincipal($data['espacePrincipal']);
        } elseif (isset($data['espacePrincipalId'])) {
            $espace = $this->espaceLieuService->getById($data['espacePrincipalId']);
            if ($espace) {
                $event->setEspacePrincipal($espace);
            }
        }

        if (isset($data['slug'])) {
            $event->setSlug($data['slug']);
        }

        if (isset($data['sousTitre'])) {
            $event->setSousTitre($data['sousTitre']);
        }

        if (isset($data['resume'])) {
            $event->setResume($data['resume']);
        }

        if (isset($data['description'])) {
            $event->setDescription($data['description']);
        }

        if (isset($data['urlImageCouverture'])) {
            $event->setUrlImageCouverture($data['urlImageCouverture']);
        }

        if (isset($data['statut'])) {
            $event->setStatut($data['statut']);
        }

        if (isset($data['visibilite'])) {
            $event->setVisibilite($data['visibilite']);
        }

        if (isset($data['formatEvenement'])) {
            $event->setFormatEvenement($data['formatEvenement']);
        }

        if (isset($data['capacite'])) {
            $event->setCapacite($data['capacite']);
        }

        if (isset($data['fuseauHoraire'])) {
            $event->setFuseauHoraire($data['fuseauHoraire']);
        }

        if (isset($data['localisationOverride'])) {
            $event->setLocalisationOverride($data['localisationOverride']);
        }

        if (isset($data['urlLive'])) {
            $event->setUrlLive($data['urlLive']);
        }

        if (isset($data['plateformeStreaming'])) {
            $event->setPlateformeStreaming($data['plateformeStreaming']);
        }

        if (isset($data['commenceLe'])) {
            $event->setCommenceLe($data['commenceLe']);
        }

        if (isset($data['seTermineLe'])) {
            $event->setSeTermineLe($data['seTermineLe']);
        }

        if (isset($data['ventesCommencentLe'])) {
            $event->setVentesCommencentLe($data['ventesCommencentLe']);
        }

        if (isset($data['ventesSeTerminentLe'])) {
            $event->setVentesSeTerminentLe($data['ventesSeTerminentLe']);
        }

        if (isset($data['restrictionAge'])) {
            $event->setRestrictionAge($data['restrictionAge']);
        }

        if (isset($data['codeLangue'])) {
            $event->setCodeLangue($data['codeLangue']);
        }

        if (isset($data['estEnVedette'])) {
            $event->setEstEnVedette($data['estEnVedette']);
        }

        if (isset($data['estMisEnAvant'])) {
            $event->setEstMisEnAvant($data['estMisEnAvant']);
        }

        if (isset($data['urlYoutube'])) {
            $event->setUrlYoutube($data['urlYoutube']);
        }

        if (isset($data['nomLieuTexte'])) {
            $event->setNomLieuTexte($data['nomLieuTexte']);
        }

        if (isset($data['adresseComplete'])) {
            $event->setAdresseComplete($data['adresseComplete']);
        }

        if (isset($data['tarifUnique'])) {
            $event->setTarifUnique($data['tarifUnique']);
        }

        if (isset($data['codeQr'])) {
            $event->setCodeQr($data['codeQr']);
        }

        if (isset($data['checksumQr'])) {
            $event->setChecksumQr($data['checksumQr']);
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

