<?php

namespace App\Service\Organisateur;

use App\Entity\Event;
use App\Entity\EventCategory;
use App\Entity\EventType;
use App\Entity\EspaceLieu;
use App\Entity\OrganisateurEvenement;
use App\Entity\OrganizerProfile;
use App\Entity\User;
use App\Entity\Venue;
use App\Repository\Organisateur\EventRepository;
use App\Repository\Organisateur\BilletRepository;
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
        private SluggerInterface $slugger,
        private BilletRepository $billetRepository
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
     * Recherche multicritères d'événements
     *
     * @param array $criteria Critères de recherche :
     *   - 'idOrganisateur' (string) : ID du profil organisateur (obligatoire)
     *   - 'nomLieu' (string|null) : Nom du lieu
     *   - 'dateDebut' (\DateTimeInterface|null) : Date de début
     *   - 'dateFin' (\DateTimeInterface|null) : Date de fin
     *   - 'typeEvenementId' (string|null) : ID du type d'événement
     *   - 'prixMin' (float|null) : Prix minimum
     *   - 'prixMax' (float|null) : Prix maximum
     *   - 'triPrix' (string|null) : 'asc' pour croissant, 'desc' pour décroissant
     *   - 'limit' (int|null) : Limite de résultats
     *   - 'offset' (int|null) : Offset pour la pagination
     * @return array Tableau d'événements
     * @throws \InvalidArgumentException Si idOrganisateur n'est pas fourni
     */
    public function searchMultiCriteria(array $criteria): array
    {
        $idOrganisateur = $criteria['idOrganisateur'] ?? null;
        if ($idOrganisateur === null || $idOrganisateur === '') {
            throw new \InvalidArgumentException('Le paramètre idOrganisateur est obligatoire');
        }

        $nomLieu = $criteria['nomLieu'] ?? null;
        $dateDebut = $criteria['dateDebut'] ?? null;
        $dateFin = $criteria['dateFin'] ?? null;
        $typeEvenementId = $criteria['typeEvenementId'] ?? null;
        $statut = $criteria['statut'] ?? null;
        $prixMin = $criteria['prixMin'] ?? null;
        $prixMax = $criteria['prixMax'] ?? null;
        $triPrix = $criteria['triPrix'] ?? null;
        $limit = $criteria['limit'] ?? null;
        $offset = $criteria['offset'] ?? null;

        // Normaliser les valeurs null/0 pour les prix
        if ($prixMin !== null && ($prixMin === 0 || $prixMin === '0')) {
            $prixMin = null;
        }
        if ($prixMax !== null && ($prixMax === 0 || $prixMax === '0')) {
            $prixMax = null;
        }

        // Normaliser le type d'événement
        if ($typeEvenementId !== null && ($typeEvenementId === '0' || $typeEvenementId === '')) {
            $typeEvenementId = null;
        }

        // Normaliser le statut
        if ($statut !== null && $statut === '') {
            $statut = null;
        }

        // Convertir les dates si elles sont des strings
        if ($dateDebut !== null && is_string($dateDebut)) {
            try {
                $dateDebut = new \DateTime($dateDebut);
            } catch (\Exception $e) {
                $dateDebut = null;
            }
        }

        if ($dateFin !== null && is_string($dateFin)) {
            try {
                $dateFin = new \DateTime($dateFin);
            } catch (\Exception $e) {
                $dateFin = null;
            }
        }

        return $this->eventRepository->searchMultiCriteria(
            $idOrganisateur,
            $nomLieu,
            $dateDebut,
            $dateFin,
            $typeEvenementId,
            $statut,
            $prixMin,
            $prixMax,
            $triPrix,
            $limit,
            $offset
        );
    }

    /**
     * Compte les résultats d'une recherche multicritères
     *
     * @param array $criteria Critères de recherche (même format que searchMultiCriteria)
     * @return int Nombre de résultats
     * @throws \InvalidArgumentException Si idOrganisateur n'est pas fourni
     */
    public function countSearchMultiCriteria(array $criteria): int
    {
        $idOrganisateur = $criteria['idOrganisateur'] ?? null;
        if ($idOrganisateur === null || $idOrganisateur === '') {
            throw new \InvalidArgumentException('Le paramètre idOrganisateur est obligatoire');
        }

        $nomLieu = $criteria['nomLieu'] ?? null;
        $dateDebut = $criteria['dateDebut'] ?? null;
        $dateFin = $criteria['dateFin'] ?? null;
        $typeEvenementId = $criteria['typeEvenementId'] ?? null;
        $statut = $criteria['statut'] ?? null;
        $prixMin = $criteria['prixMin'] ?? null;
        $prixMax = $criteria['prixMax'] ?? null;

        // Normaliser les valeurs null/0 pour les prix
        if ($prixMin !== null && ($prixMin === 0 || $prixMin === '0')) {
            $prixMin = null;
        }
        if ($prixMax !== null && ($prixMax === 0 || $prixMax === '0')) {
            $prixMax = null;
        }

        // Normaliser le type d'événement
        if ($typeEvenementId !== null && ($typeEvenementId === '0' || $typeEvenementId === '')) {
            $typeEvenementId = null;
        }

        // Normaliser le statut
        if ($statut !== null && $statut === '') {
            $statut = null;
        }

        // Convertir les dates si elles sont des strings
        if ($dateDebut !== null && is_string($dateDebut)) {
            try {
                $dateDebut = new \DateTime($dateDebut);
            } catch (\Exception $e) {
                $dateDebut = null;
            }
        }

        if ($dateFin !== null && is_string($dateFin)) {
            try {
                $dateFin = new \DateTime($dateFin);
            } catch (\Exception $e) {
                $dateFin = null;
            }
        }

        return $this->eventRepository->countSearchMultiCriteria(
            $idOrganisateur,
            $nomLieu,
            $dateDebut,
            $dateFin,
            $typeEvenementId,
            $prixMin,
            $prixMax,
            $statut
        );
    }

    /**
     * Recherche multicritères avec pagination complète
     *
     * @param array $criteria Critères de recherche :
     *   - 'idOrganisateur' (string) : ID du profil organisateur (obligatoire)
     *   - 'nomLieu' (string|null) : Nom du lieu
     *   - 'dateDebut' (\DateTimeInterface|null) : Date de début
     *   - 'dateFin' (\DateTimeInterface|null) : Date de fin
     *   - 'typeEvenementId' (string|null) : ID du type d'événement
     *   - 'prixMin' (float|null) : Prix minimum
     *   - 'prixMax' (float|null) : Prix maximum
     *   - 'triPrix' (string|null) : 'asc' pour croissant, 'desc' pour décroissant
     *   - 'page' (int) : Numéro de page (commence à 1, défaut: 1)
     *   - 'limit' (int) : Nombre d'éléments par page (défaut: 20)
     * @return array ['items' => Event[], 'pagination' => array]
     * @throws \InvalidArgumentException Si idOrganisateur n'est pas fourni
     */
    public function searchMultiCriteriaWithPagination(array $criteria): array
    {
        $idOrganisateur = $criteria['idOrganisateur'] ?? null;
        if ($idOrganisateur === null || $idOrganisateur === '') {
            throw new \InvalidArgumentException('Le paramètre idOrganisateur est obligatoire');
        }

        $nomLieu = $criteria['nomLieu'] ?? null;
        $dateDebut = $criteria['dateDebut'] ?? null;
        $dateFin = $criteria['dateFin'] ?? null;
        $typeEvenementId = $criteria['typeEvenementId'] ?? null;
        $statut = $criteria['statut'] ?? null;
        $prixMin = $criteria['prixMin'] ?? null;
        $prixMax = $criteria['prixMax'] ?? null;
        $triPrix = $criteria['triPrix'] ?? null;
        $page = max(1, (int) ($criteria['page'] ?? 1));
        $limit = max(1, (int) ($criteria['limit'] ?? 20));

        // Normaliser les valeurs null/0 pour les prix
        if ($prixMin !== null && ($prixMin === 0 || $prixMin === '0')) {
            $prixMin = null;
        }
        if ($prixMax !== null && ($prixMax === 0 || $prixMax === '0')) {
            $prixMax = null;
        }

        // Normaliser le type d'événement
        if ($typeEvenementId !== null && ($typeEvenementId === '0' || $typeEvenementId === '')) {
            $typeEvenementId = null;
        }

        // Normaliser le statut
        if ($statut !== null && $statut === '') {
            $statut = null;
        }

        // Convertir les dates si elles sont des strings
        if ($dateDebut !== null && is_string($dateDebut)) {
            try {
                $dateDebut = new \DateTime($dateDebut);
            } catch (\Exception $e) {
                $dateDebut = null;
            }
        }

        if ($dateFin !== null && is_string($dateFin)) {
            try {
                $dateFin = new \DateTime($dateFin);
            } catch (\Exception $e) {
                $dateFin = null;
            }
        }

        return $this->eventRepository->searchMultiCriteriaWithPagination(
            $idOrganisateur,
            $nomLieu,
            $dateDebut,
            $dateFin,
            $typeEvenementId,
            $prixMin,
            $prixMax,
            $triPrix,
            $page,
            $limit,
            $statut
        );
    }

    /**
     * Récupère les statistiques d'un événement
     */
    public function getEventStatistics(Event $event): array
    {
        $baseStats = $this->eventRepository->getEventStatistics($event);
        $salesEvolution = $this->billetRepository->getSalesEvolutionByEvent($event);

        return array_merge($baseStats, [
            'salesEvolution' => $salesEvolution,
        ]);
    }

    /**
     * Vérifie si un utilisateur peut modifier un événement
     * Vérifie si l'utilisateur est l'organisateur principal, un co-organisateur, ou un admin
     */
    public function canEdit(Event $event, User $user): bool
    {
        // Les admins peuvent toujours modifier
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // Vérifier si l'utilisateur est l'organisateur principal
        $organizerProfile = $event->getProfilOrganisateur();
        if ($organizerProfile !== null && $organizerProfile->getUtilisateur() === $user) {
            return true;
        }

        // Vérifier si l'utilisateur est un co-organisateur avec les permissions appropriées
        foreach ($event->getOrganisateursEvenements() as $organisateurEvenement) {
            $profil = $organisateurEvenement->getProfilOrganisateur();
            if ($profil && $profil->getUtilisateur() === $user) {
                $role = $organisateurEvenement->getRole();
                // Les créateurs et co-organisateurs peuvent modifier
                if (in_array($role, [
                    OrganisateurEvenement::ROLE_CREATEUR,
                    OrganisateurEvenement::ROLE_CO_ORGANISATEUR
                ])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Vérifie si un utilisateur peut supprimer un événement
     * Seuls l'organisateur principal (créateur) ou un admin peuvent supprimer
     */
    public function canDelete(Event $event, User $user): bool
    {
        // Les admins peuvent toujours supprimer
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // Seul l'organisateur principal peut supprimer
        $organizerProfile = $event->getProfilOrganisateur();
        if ($organizerProfile !== null && $organizerProfile->getUtilisateur() === $user) {
            return true;
        }

        return false;
    }

    /**
     * Ajoute un organisateur à un événement
     */
    public function addOrganisateurToEvent(
        Event $event,
        OrganizerProfile $organisateur,
        string $role = OrganisateurEvenement::ROLE_CO_ORGANISATEUR,
        ?OrganizerProfile $ajoutePar = null
    ): Event {
        $event->addOrganisateur($organisateur, $role, $ajoutePar);
        return $this->eventRepository->update($event);
    }

    /**
     * Retire un organisateur d'un événement
     */
    public function removeOrganisateurFromEvent(Event $event, OrganizerProfile $organisateur): Event
    {
        $event->removeOrganisateur($organisateur);
        return $this->eventRepository->update($event);
    }

    /**
     * Met à jour le rôle d'un organisateur dans un événement
     */
    public function updateOrganisateurRole(
        Event $event,
        OrganizerProfile $organisateur,
        string $role
    ): Event {
        foreach ($event->getOrganisateursEvenements() as $organisateurEvenement) {
            if ($organisateurEvenement->getProfilOrganisateur() === $organisateur) {
                $organisateurEvenement->setRole($role);
                break;
            }
        }
        return $this->eventRepository->update($event);
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

