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
use App\Entity\ConfigurationCategorieBillet;
use App\Entity\ConfigurationSegmentBillet;
use App\Repository\Organisateur\EventRepository;
use App\Repository\Organisateur\BilletRepository;
use App\Service\Organisateur\EventTypeService;
use App\Service\Organisateur\EspaceLieuService;
use App\Service\Organisateur\TypeBilletService;
use App\Service\Organisateur\InventaireBilletService;
use App\Service\Organisateur\ConfigurationCategorieBilletService;
use App\Service\Organisateur\ConfigurationSegmentBilletService;
use App\Service\Organisateur\MediaService;
use App\Repository\Organisateur\TicketInvoiceRepository;
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
        private BilletRepository $billetRepository,
        private TypeBilletService $typeBilletService,
        private InventaireBilletService $inventaireBilletService,
        private ConfigurationCategorieBilletService $categorieBilletService,
        private ConfigurationSegmentBilletService $segmentBilletService,
        private MediaService $mediaService,
        private TicketInvoiceRepository $ticketInvoiceRepository
    ) {
    }

    
    public function getAll(): array
    {
        return $this->eventRepository->getAll();
    }

    
    public function getById(string $id): ?Event
    {
        return $this->eventRepository->getById($id);
    }

    public function getActiveEventsByOrganisateur(
        string $idOrganisateur,
        ?int $limit = null,
        ?int $offset = null
    ): array {
        return $this->eventRepository->findActiveEventsByOrganisateur(
            $idOrganisateur,
            $limit,
            $offset
        );
    }

    
    public function create(array $data, ?OrganizerProfile $organizerProfile = null): Event
    {
        $event = new Event();

        if ($organizerProfile !== null) {
            $event->setProfilOrganisateur($organizerProfile);
        }

        $this->updateEventFromData($event, $data);

        return $this->eventRepository->create($event);
    }

    
    public function update(Event $event, array $data): Event
    {
        $this->updateEventFromData($event, $data);

        return $this->eventRepository->update($event);
    }

    /**
     * Persiste un événement déjà hydraté (issu d'un formulaire Symfony).
     */
    public function saveFromForm(Event $event): Event
    {
        if ($event->getTitre()) {
            $event->setSlug($this->generateUniqueSlug($event->getTitre()));
        }

        return $this->eventRepository->create($event);
    }

    /**
     * Crée les billets pour un événement à partir des données du formulaire.
     *
     * @param Event $event L'événement auquel associer les billets
     * @param array $ticketsData Données des billets depuis le formulaire :
     *   - ticket_categorie[] : IDs des catégories
     *   - ticket_segment[] : IDs des segments
     *   - ticket_price[] : Prix des billets
     *   - ticket_quantity[] : Quantités disponibles
     * @return array Liste des TypeBillet créés
     */
    public function createTicketsForEvent(Event $event, array $ticketsData): array
    {
        $createdTickets = [];
        
        $categories = $ticketsData['ticket_categorie'] ?? [];
        $segments = $ticketsData['ticket_segment'] ?? [];
        $prices = $ticketsData['ticket_price'] ?? [];
        $quantities = $ticketsData['ticket_quantity'] ?? [];
        
        // Récupérer les paramètres de vente globaux
        $salesStart = $this->parseDateTime(
            $ticketsData['ticket_sales_start_date'] ?? null,
            $ticketsData['ticket_sales_start_time'] ?? null
        );
        $salesEnd = $this->parseDateTime(
            $ticketsData['ticket_sales_end_date'] ?? null,
            $ticketsData['ticket_sales_end_time'] ?? null
        );
        $minPerOrder = isset($ticketsData['ticket_min_per_order']) && $ticketsData['ticket_min_per_order'] !== ''
            ? (int) $ticketsData['ticket_min_per_order']
            : 1;
        $maxPerOrder = isset($ticketsData['ticket_max_per_order']) && $ticketsData['ticket_max_per_order'] !== ''
            ? (int) $ticketsData['ticket_max_per_order']
            : null;
        
        // Récupérer toutes les catégories et segments actifs (sauf "tous")
        $allCategories = $this->categorieBilletService->getAllActive();
        $allSegments = $this->segmentBilletService->getAllActive();
        
        // Filtrer pour exclure "tous"
        $validCategories = array_filter($allCategories, fn($cat) => $cat->getNom() !== 'tous');
        $validSegments = array_filter($allSegments, fn($seg) => $seg->getNom() !== 'tous');
        
        // Vérifier que tous les tableaux ont la même longueur
        $count = max(
            count($categories),
            count($segments),
            count($prices),
            count($quantities)
        );
        
        for ($i = 0; $i < $count; $i++) {
            // Récupérer les entités catégorie et segment
            $categorieId = $categories[$i] ?? null;
            $segmentId = $segments[$i] ?? null;
            $price = $prices[$i] ?? null;
            $quantity = $quantities[$i] ?? null;
            
            // Valider les données - ignorer si vide ou "tous" (qui n'est pas un ID valide)
            if (!$categorieId || !$segmentId || $price === null || $quantity === null) {
                continue;
            }
            
            // Vérifier si "tous" est sélectionné (valeur littérale "tous", pas un ID)
            $isTousCategorie = ($categorieId === 'tous' || strtolower($categorieId) === 'tous');
            $isTousSegment = ($segmentId === 'tous' || strtolower($segmentId) === 'tous');
            
            // Si "tous" est sélectionné, on utilise toutes les catégories/segments valides
            if ($isTousCategorie && $isTousSegment) {
                // Créer un billet pour chaque combinaison catégorie x segment
                foreach ($validCategories as $catToUse) {
                    foreach ($validSegments as $segToUse) {
                        $this->createTicketType($event, $catToUse, $segToUse, $price, $quantity, $minPerOrder, $maxPerOrder, $salesStart, $salesEnd, $createdTickets);
                    }
                }
                continue;
            } elseif ($isTousCategorie) {
                // Récupérer le segment
                $segment = $this->entityManager->getRepository(ConfigurationSegmentBillet::class)->find($segmentId);
                if (!$segment) {
                    continue;
                }
                // Créer un billet pour chaque catégorie avec ce segment
                foreach ($validCategories as $catToUse) {
                    $this->createTicketType($event, $catToUse, $segment, $price, $quantity, $minPerOrder, $maxPerOrder, $salesStart, $salesEnd, $createdTickets);
                }
                continue;
            } elseif ($isTousSegment) {
                // Récupérer la catégorie
                $categorie = $this->entityManager->getRepository(ConfigurationCategorieBillet::class)->find($categorieId);
                if (!$categorie) {
                    continue;
                }
                // Créer un billet pour chaque segment avec cette catégorie
                foreach ($validSegments as $segToUse) {
                    $this->createTicketType($event, $categorie, $segToUse, $price, $quantity, $minPerOrder, $maxPerOrder, $salesStart, $salesEnd, $createdTickets);
                }
                continue;
            }
            
            // Si ni catégorie ni segment n'est "tous", récupérer les entités normalement
            $categorie = $this->entityManager->getRepository(ConfigurationCategorieBillet::class)->find($categorieId);
            $segment = $this->entityManager->getRepository(ConfigurationSegmentBillet::class)->find($segmentId);
            
            if (!$categorie || !$segment) {
                continue;
            }
            
            // Créer le billet pour cette catégorie et ce segment spécifiques
            $this->createTicketType($event, $categorie, $segment, $price, $quantity, $minPerOrder, $maxPerOrder, $salesStart, $salesEnd, $createdTickets);
        }
        
        return $createdTickets;
    }

    /**
     * Met à jour les billets existants pour un événement
     */
    public function updateTicketsForEvent(Event $event, array $ticketsData): array
    {
        $ticketIds = $ticketsData['ticket_id'] ?? [];
        $categories = $ticketsData['ticket_categorie'] ?? [];
        $segments = $ticketsData['ticket_segment'] ?? [];
        $prices = $ticketsData['ticket_price'] ?? [];
        $quantities = $ticketsData['ticket_quantity'] ?? [];
        
        $salesStartDate = $ticketsData['ticket_sales_start_date'] ?? null;
        $salesStartTime = $ticketsData['ticket_sales_start_time'] ?? null;
        $salesEndDate = $ticketsData['ticket_sales_end_date'] ?? null;
        $salesEndTime = $ticketsData['ticket_sales_end_time'] ?? null;
        $minPerOrder = (int) ($ticketsData['ticket_min_per_order'] ?? 1);
        $maxPerOrder = $ticketsData['ticket_max_per_order'] ? (int) $ticketsData['ticket_max_per_order'] : null;
        
        $salesStart = null;
        if ($salesStartDate && $salesStartTime) {
            $salesStart = $this->parseDateTime($salesStartDate, $salesStartTime);
        }
        
        $salesEnd = null;
        if ($salesEndDate && $salesEndTime) {
            $salesEnd = $this->parseDateTime($salesEndDate, $salesEndTime);
        }
        
        $updatedTickets = [];
        $count = count($categories);
        
        for ($i = 0; $i < $count; $i++) {
            $ticketId = $ticketIds[$i] ?? null;
            $categorieId = $categories[$i] ?? null;
            $segmentId = $segments[$i] ?? null;
            $price = $prices[$i] ?? null;
            $quantity = $quantities[$i] ?? null;
            
            if (!$categorieId || !$segmentId || $price === null || $quantity === null) {
                continue;
            }
            
            // Si c'est un billet existant, le mettre à jour
            if ($ticketId) {
                $typeBillet = $this->typeBilletService->getById($ticketId);
                if ($typeBillet && $typeBillet->getEvenement()->getId() === $event->getId()) {
                    $inventaire = $typeBillet->getInventaire();
                    $quantiteVendue = $inventaire ? $inventaire->getQuantiteVendue() : 0;
                    
                    // Valider que la nouvelle quantité n'est pas inférieure à la quantité vendue
                    if ((int) $quantity < $quantiteVendue) {
                        throw new \InvalidArgumentException(
                            sprintf(
                                'La quantité du billet "%s" ne peut pas être inférieure à %d (billets déjà vendus).',
                                $typeBillet->getNom(),
                                $quantiteVendue
                            )
                        );
                    }
                    
                    // Mettre à jour le billet
                    $ticketData = [
                        'prixDeBase' => (float) $price,
                        'minimumParCommande' => $minPerOrder,
                        'maximumParCommande' => $maxPerOrder,
                        'ventesCommencentLe' => $salesStart,
                        'ventesSeTerminentLe' => $salesEnd,
                    ];
                    
                    $this->typeBilletService->update($typeBillet, $ticketData);
                    
                    // Mettre à jour l'inventaire
                    if ($inventaire) {
                        $inventaireData = [
                            'quantiteTotale' => (int) $quantity,
                        ];
                        $this->inventaireBilletService->update($inventaire, $inventaireData);
                    }
                    
                    $updatedTickets[] = $typeBillet;
                    continue;
                }
            }
            
            // Sinon, créer un nouveau billet (logique existante)
            $isTousCategorie = ($categorieId === 'tous' || strtolower($categorieId) === 'tous');
            $isTousSegment = ($segmentId === 'tous' || strtolower($segmentId) === 'tous');
            
            $validCategories = $this->entityManager
                ->getRepository(ConfigurationCategorieBillet::class)
                ->findBy(['estActif' => true]);
            $validSegments = $this->entityManager
                ->getRepository(ConfigurationSegmentBillet::class)
                ->findBy(['estActif' => true]);
            
            if ($isTousCategorie && $isTousSegment) {
                foreach ($validCategories as $catToUse) {
                    foreach ($validSegments as $segToUse) {
                        $this->createTicketType($event, $catToUse, $segToUse, $price, $quantity, $minPerOrder, $maxPerOrder, $salesStart, $salesEnd, $updatedTickets);
                    }
                }
            } elseif ($isTousCategorie) {
                $segment = $this->entityManager->getRepository(ConfigurationSegmentBillet::class)->find($segmentId);
                if ($segment) {
                    foreach ($validCategories as $catToUse) {
                        $this->createTicketType($event, $catToUse, $segment, $price, $quantity, $minPerOrder, $maxPerOrder, $salesStart, $salesEnd, $updatedTickets);
                    }
                }
            } elseif ($isTousSegment) {
                $categorie = $this->entityManager->getRepository(ConfigurationCategorieBillet::class)->find($categorieId);
                if ($categorie) {
                    foreach ($validSegments as $segToUse) {
                        $this->createTicketType($event, $categorie, $segToUse, $price, $quantity, $minPerOrder, $maxPerOrder, $salesStart, $salesEnd, $updatedTickets);
                    }
                }
            } else {
                $categorie = $this->entityManager->getRepository(ConfigurationCategorieBillet::class)->find($categorieId);
                $segment = $this->entityManager->getRepository(ConfigurationSegmentBillet::class)->find($segmentId);
                if ($categorie && $segment) {
                    $this->createTicketType($event, $categorie, $segment, $price, $quantity, $minPerOrder, $maxPerOrder, $salesStart, $salesEnd, $updatedTickets);
                }
            }
        }
        
        return $updatedTickets;
    }

    /**
     * Helper method pour créer un type de billet avec inventaire
     */
    private function createTicketType(
        Event $event,
        $categorie,
        $segment,
        $price,
        $quantity,
        int $minPerOrder,
        ?int $maxPerOrder,
        ?\DateTimeInterface $salesStart,
        ?\DateTimeInterface $salesEnd,
        array &$createdTickets
    ): void {
        // Générer le nom du billet (catégorie + segment)
        $nom = ucfirst($categorie->getNom()) . ' - ' . ucfirst($segment->getNom());
        
        // Créer le TypeBillet avec les paramètres de vente
        $ticketData = [
            'configurationCategorie' => $categorie,
            'configurationSegment' => $segment,
            'nom' => $nom,
            'prixDeBase' => (float) $price,
            'devise' => 'MGA',
            'fraisService' => 0,
            'tauxTva' => 0,
            'minimumParCommande' => $minPerOrder,
            'maximumParCommande' => $maxPerOrder,
            'ventesCommencentLe' => $salesStart,
            'ventesSeTerminentLe' => $salesEnd,
        ];
        
        $typeBillet = $this->typeBilletService->create($ticketData, $event);
        
        // Créer l'inventaire avec la quantité
        $inventaireData = [
            'quantiteTotale' => (int) $quantity,
            'quantiteReservee' => 0,
            'quantiteVendue' => 0,
        ];
        
        $this->inventaireBilletService->create($inventaireData, $typeBillet);
        
        $createdTickets[] = $typeBillet;
    }

    /**
     * Parse une date et une heure séparées en un objet DateTime.
     *
     * @param string|null $date La date au format jj/mm/aaaa
     * @param string|null $time L'heure au format hh:mm
     * @return \DateTimeInterface|null
     */
    public function parseDateTime(?string $date, ?string $time): ?\DateTimeInterface
    {
        if (!$date || !$time) {
            return null;
        }

        try {
            // Parser la date jj/mm/aaaa
            $dateParts = explode('/', $date);
            if (count($dateParts) !== 3) {
                return null;
            }
            $day = (int) $dateParts[0];
            $month = (int) $dateParts[1];
            $year = (int) $dateParts[2];

            // Parser l'heure hh:mm
            $timeParts = explode(':', $time);
            if (count($timeParts) !== 2) {
                return null;
            }
            $hour = (int) $timeParts[0];
            $minute = (int) $timeParts[1];

            return new \DateTimeImmutable(sprintf(
                '%04d-%02d-%02d %02d:%02d:00',
                $year,
                $month,
                $day,
                $hour,
                $minute
            ));
        } catch (\Exception $e) {
            return null;
        }
    }

    
    public function delete(Event $event): void
    {
        // Supprimer tous les types de billets associés à l'événement (et leurs inventaires en cascade)
        $typeBillets = $this->typeBilletService->getByEvenement($event);
        foreach ($typeBillets as $typeBillet) {
            $this->typeBilletService->delete($typeBillet);
        }
        
        // Supprimer tous les médias associés à l'événement avant de supprimer l'événement
        $eventMedias = $this->mediaService->getEventMedias($event);
        foreach ($eventMedias as $media) {
            $this->mediaService->deleteMedia($media);
        }
        
        // Supprimer l'événement
        $this->eventRepository->delete($event);
    }

    
    public function publishEvent(Event $event): Event
    {
        $event->setStatut(Event::STATUS_PUBLISHED);
        return $this->eventRepository->update($event);
    }

    
    public function cancelEvent(Event $event): Event
    {
        $event->setStatut(Event::STATUS_CANCELLED);
        return $this->eventRepository->update($event);
    }

    
    public function archiveEvent(Event $event): Event
    {
        $event->setStatut(Event::STATUS_ARCHIVED);
        return $this->eventRepository->update($event);
    }

    
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

    
    public function getUpcomingEvents(int $limit = 0): array
    {
        return $this->eventRepository->findUpcomingEvents($limit);
    }

    
    public function getFeaturedEvents(int $limit = 6): array
    {
        return $this->eventRepository->findFeaturedEvents($limit);
    }

    
    public function searchEvents(string $query, array $filters = []): array
    {
        return $this->eventRepository->searchEvents($query, $filters);
    }

    
    public function searchMultiCriteria(array $criteria): array
    {
        $idOrganisateur = $criteria['idOrganisateur'] ?? null;
        if ($idOrganisateur === null || $idOrganisateur === '') {
            throw new \InvalidArgumentException('Le paramètre idOrganisateur est obligatoire');
        }

        $lieuId = $criteria['lieuId'] ?? $criteria['nomLieu'] ?? null; 
        $dateDebut = $criteria['dateDebut'] ?? null;
        $dateFin = $criteria['dateFin'] ?? null;
        $typeEvenementId = $criteria['typeEvenementId'] ?? null;
        $statut = $criteria['statut'] ?? null;
        $prixMin = $criteria['prixMin'] ?? null;
        $prixMax = $criteria['prixMax'] ?? null;
        $triPrix = $criteria['triPrix'] ?? null;
        $limit = $criteria['limit'] ?? null;
        $offset = $criteria['offset'] ?? null;

        
        if ($prixMin !== null && ($prixMin === 0 || $prixMin === '0')) {
            $prixMin = null;
        }
        if ($prixMax !== null && ($prixMax === 0 || $prixMax === '0')) {
            $prixMax = null;
        }

        
        if ($typeEvenementId !== null && ($typeEvenementId === '0' || $typeEvenementId === '')) {
            $typeEvenementId = null;
        }

        
        if ($statut !== null && $statut === '') {
            $statut = null;
        }

        
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
            $lieuId,
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

    
    public function countSearchMultiCriteria(array $criteria): int
    {
        $idOrganisateur = $criteria['idOrganisateur'] ?? null;
        if ($idOrganisateur === null || $idOrganisateur === '') {
            throw new \InvalidArgumentException('Le paramètre idOrganisateur est obligatoire');
        }

        $lieuId = $criteria['lieuId'] ?? $criteria['nomLieu'] ?? null; 
        $dateDebut = $criteria['dateDebut'] ?? null;
        $dateFin = $criteria['dateFin'] ?? null;
        $typeEvenementId = $criteria['typeEvenementId'] ?? null;
        $statut = $criteria['statut'] ?? null;
        $prixMin = $criteria['prixMin'] ?? null;
        $prixMax = $criteria['prixMax'] ?? null;

        
        if ($prixMin !== null && ($prixMin === 0 || $prixMin === '0')) {
            $prixMin = null;
        }
        if ($prixMax !== null && ($prixMax === 0 || $prixMax === '0')) {
            $prixMax = null;
        }

        
        if ($typeEvenementId !== null && ($typeEvenementId === '0' || $typeEvenementId === '')) {
            $typeEvenementId = null;
        }

        
        if ($statut !== null && $statut === '') {
            $statut = null;
        }

        
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
            $lieuId,
            $dateDebut,
            $dateFin,
            $typeEvenementId,
            $prixMin,
            $prixMax,
            $statut
        );
    }

    
    public function searchMultiCriteriaWithPagination(array $criteria): array
    {
        $idOrganisateur = $criteria['idOrganisateur'] ?? null;
        if ($idOrganisateur === null || $idOrganisateur === '') {
            throw new \InvalidArgumentException('Le paramètre idOrganisateur est obligatoire');
        }

        $lieuId = $criteria['lieuId'] ?? $criteria['nomLieu'] ?? null; 
        $dateDebut = $criteria['dateDebut'] ?? null;
        $dateFin = $criteria['dateFin'] ?? null;
        $typeEvenementId = $criteria['typeEvenementId'] ?? null;
        $statut = $criteria['statut'] ?? null;
        $prixMin = $criteria['prixMin'] ?? null;
        $prixMax = $criteria['prixMax'] ?? null;
        $triPrix = $criteria['triPrix'] ?? null;
        $page = max(1, (int) ($criteria['page'] ?? 1));
        $limit = max(1, (int) ($criteria['limit'] ?? 20));

        
        if ($prixMin !== null && ($prixMin === 0 || $prixMin === '0')) {
            $prixMin = null;
        }
        if ($prixMax !== null && ($prixMax === 0 || $prixMax === '0')) {
            $prixMax = null;
        }

        
        if ($typeEvenementId !== null && ($typeEvenementId === '0' || $typeEvenementId === '')) {
            $typeEvenementId = null;
        }

        
        if ($statut !== null && $statut === '') {
            $statut = null;
        }

        
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
            $lieuId,
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

    
    public function getEventStatistics(Event $event): array
    {
        $baseStats = $this->eventRepository->getEventStatistics($event);
        $salesEvolution = $this->billetRepository->getSalesEvolutionByEvent($event);
        $revenuesByType = $this->ticketInvoiceRepository->getRevenueByEvent($event);
        $totalRevenue = array_sum($revenuesByType);

        return array_merge($baseStats, [
            'salesEvolution' => $salesEvolution,
            'revenuesByType' => $revenuesByType,
            'totalRevenue' => $totalRevenue,
        ]);
    }

    
    public function canEdit(Event $event, User $user): bool
    {
        
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        
        $organizerProfile = $event->getProfilOrganisateur();
        if ($organizerProfile !== null && $organizerProfile->getUtilisateur() === $user) {
            return true;
        }

        
        foreach ($event->getOrganisateursEvenements() as $organisateurEvenement) {
            $profil = $organisateurEvenement->getProfilOrganisateur();
            if ($profil && $profil->getUtilisateur() === $user) {
                $role = $organisateurEvenement->getRole();
                
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

    
    public function canDelete(Event $event, User $user): bool
    {
        
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        
        $organizerProfile = $event->getProfilOrganisateur();
        if ($organizerProfile !== null && $organizerProfile->getUtilisateur() === $user) {
            return true;
        }

        return false;
    }

    
    public function addOrganisateurToEvent(
        Event $event,
        OrganizerProfile $organisateur,
        string $role = OrganisateurEvenement::ROLE_CO_ORGANISATEUR,
        ?OrganizerProfile $ajoutePar = null
    ): Event {
        $event->addOrganisateur($organisateur, $role, $ajoutePar);
        return $this->eventRepository->update($event);
    }

    
    public function removeOrganisateurFromEvent(Event $event, OrganizerProfile $organisateur): Event
    {
        $event->removeOrganisateur($organisateur);
        return $this->eventRepository->update($event);
    }

    
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

    
    private function generateUniqueSlug(string $title): string
    {
        $slug = $this->slugger->slug($title)->lower();
        $originalSlug = $slug;
        $counter = 1;

        
        while ($this->eventRepository->findOneBy(['slug' => $slug])) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}

