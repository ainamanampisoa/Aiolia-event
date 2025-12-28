<?php

namespace App\Service\Organisateur;

use App\Entity\Event;
use App\Entity\TypeBillet;
use App\Entity\User;
use Doctrine\ORM\Tools\Pagination\Paginator;

class TicketManagementService
{
    public function __construct(
        private TypeBilletService $typeBilletService,
        private InventaireBilletService $inventaireBilletService,
        private HistoriquePrixBilletService $historiquePrixBilletService,
        private BilletService $billetService
    ) {
    }

    /**
     * Calcule les alertes de stock pour un organisateur
     */
    public function calculateStockAlerts(
        User $user,
        ?string $niveauFilter = null,
        ?string $categorieFilter = null,
        ?string $segmentFilter = null
    ): array {
        $typesBillets = $this->typeBilletService->getByOrganizer($user);
        $timezone = new \DateTimeZone('Indian/Antananarivo');
        $now = new \DateTime('now', $timezone);
        
        $alertesTotal = [];
        foreach ($typesBillets as $typeBillet) {
            $evenement = $typeBillet->getEvenement();
            
            // Filtrer uniquement les événements en cours et à venir
            if ($evenement && $evenement->getCommenceLe()) {
                $commenceLe = clone $evenement->getCommenceLe();
                if ($commenceLe->getTimezone()->getName() !== $timezone->getName()) {
                    $commenceLe->setTimezone($timezone);
                }
                
                $seTermineLe = $evenement->getSeTermineLe();
                if ($seTermineLe) {
                    $seTermineLe = clone $seTermineLe;
                    if ($seTermineLe->getTimezone()->getName() !== $timezone->getName()) {
                        $seTermineLe->setTimezone($timezone);
                    }
                }
                
                $isOngoing = $commenceLe <= $now && ($seTermineLe === null || $seTermineLe >= $now);
                $isUpcoming = $commenceLe > $now;
                
                if (!$isOngoing && !$isUpcoming) {
                    continue;
                }
            }
            
            $inventaire = $typeBillet->getInventaire();
            if ($inventaire) {
                $quantiteRestante = $inventaire->getQuantiteTotale() - $inventaire->getQuantiteVendue() - $inventaire->getQuantiteReservee();
                $pourcentage = $inventaire->getQuantiteTotale() > 0
                    ? ($quantiteRestante / $inventaire->getQuantiteTotale()) * 100
                    : 0;

                if ($pourcentage <= 10) {
                    $niveau = $pourcentage <= 5 ? 'critique' : 'attention';
                    
                    $alertesTotal[] = [
                        'typeBillet' => $typeBillet,
                        'inventaire' => $inventaire,
                        'quantiteRestante' => $quantiteRestante,
                        'pourcentage' => $pourcentage,
                        'niveau' => $niveau,
                    ];
                }
            }
        }

        // Filtrer les alertes
        $alertes = [];
        foreach ($alertesTotal as $alerte) {
            $typeBillet = $alerte['typeBillet'];
            $niveau = $alerte['niveau'];
            $categorieNom = $typeBillet->getConfigurationCategorie() ? $typeBillet->getConfigurationCategorie()->getNom() : null;
            $segmentNom = $typeBillet->getConfigurationSegment() ? $typeBillet->getConfigurationSegment()->getNom() : null;
            
            if ($niveauFilter && $niveau !== $niveauFilter) {
                continue;
            }
            if ($categorieFilter && $categorieNom !== $categorieFilter) {
                continue;
            }
            if ($segmentFilter && $segmentNom !== $segmentFilter) {
                continue;
            }
            
            $alertes[] = $alerte;
        }

        // Calculer les statistiques et capacités
        $alertesCritiquesTotal = array_filter($alertesTotal, fn($a) => $a['niveau'] === 'critique');
        $alertesAttentionTotal = array_filter($alertesTotal, fn($a) => $a['niveau'] === 'attention');

        $categories = [];
        $segments = [];
        $eventsIds = [];
        $capaciteParCategorie = [];
        
        foreach ($typesBillets as $tb) {
            $evenement = $tb->getEvenement();
            
            if ($evenement && $evenement->getCommenceLe() && $evenement->getStatut() === Event::STATUS_PUBLISHED) {
                $commenceLe = clone $evenement->getCommenceLe();
                if ($commenceLe->getTimezone()->getName() !== $timezone->getName()) {
                    $commenceLe->setTimezone($timezone);
                }
                
                $seTermineLe = $evenement->getSeTermineLe();
                if ($seTermineLe) {
                    $seTermineLe = clone $seTermineLe;
                    if ($seTermineLe->getTimezone()->getName() !== $timezone->getName()) {
                        $seTermineLe->setTimezone($timezone);
                    }
                }
                
                $isOngoing = $commenceLe <= $now && ($seTermineLe === null || $seTermineLe >= $now);
                $isUpcoming = $commenceLe > $now;
                
                if ($isOngoing || $isUpcoming) {
                    $eventId = $evenement->getId();
                    if (!in_array($eventId, $eventsIds)) {
                        $eventsIds[] = $eventId;
                    }
                    
                    $categorieId = $tb->getConfigurationCategorie() ? $tb->getConfigurationCategorie()->getId() : 'sans_categorie';
                    $inventaire = $tb->getInventaire();
                    $quantiteTotale = $inventaire ? $inventaire->getQuantiteTotale() : 0;
                    
                    if (!isset($capaciteParCategorie[$eventId])) {
                        $capaciteParCategorie[$eventId] = [];
                    }
                    if (!isset($capaciteParCategorie[$eventId][$categorieId])) {
                        $capaciteParCategorie[$eventId][$categorieId] = 0;
                    }
                    $capaciteParCategorie[$eventId][$categorieId] += $quantiteTotale;
                }
            }
            
            if ($tb->getConfigurationCategorie() && !isset($categories[$tb->getConfigurationCategorie()->getNom()])) {
                $categories[$tb->getConfigurationCategorie()->getNom()] = $tb->getConfigurationCategorie()->getNom();
            }
            if ($tb->getConfigurationSegment()) {
                $segmentNom = $tb->getConfigurationSegment()->getNom();
                if ($segmentNom !== 'tous' && !isset($segments[$segmentNom])) {
                    $segments[$segmentNom] = $segmentNom;
                }
            }
        }

        return [
            'alertes' => $alertes,
            'alertesTotal' => $alertesTotal,
            'alertesCritiques' => array_values($alertesCritiquesTotal),
            'alertesAttention' => array_values($alertesAttentionTotal),
            'categories' => $categories,
            'segments' => $segments,
            'eventsCount' => count($eventsIds),
            'capaciteParCategorie' => $capaciteParCategorie,
        ];
    }

    /**
     * Récupère l'historique des prix groupé par type de billet
     */
    public function getGroupedPriceHistory(
        User $organizer,
        int $page = 1,
        int $limit = 5,
        ?string $categorieFilter = null,
        ?string $segmentFilter = null
    ): array {
        $paginator = $this->historiquePrixBilletService->getByOrganizerPaginated(
            $organizer,
            $page,
            $limit,
            $categorieFilter,
            $segmentFilter
        );
        
        $totalItems = $paginator->count();
        $totalPages = max(1, (int) ceil($totalItems / $limit));

        $groupedByType = [];
        $itemsOnCurrentPage = 0;
        foreach ($paginator as $hist) {
            $itemsOnCurrentPage++;
            $typeId = $hist->getTypeBillet()->getId();
            if (!isset($groupedByType[$typeId])) {
                $groupedByType[$typeId] = [
                    'typeBillet' => $hist->getTypeBillet(),
                    'historique' => [],
                ];
            }
            $groupedByType[$typeId]['historique'][] = $hist;
        }

        $typesBillets = $this->typeBilletService->getByOrganizer($organizer);
        $categories = [];
        $segments = [];
        foreach ($typesBillets as $tb) {
            if ($tb->getConfigurationCategorie() && !isset($categories[$tb->getConfigurationCategorie()->getNom()])) {
                $categories[$tb->getConfigurationCategorie()->getNom()] = $tb->getConfigurationCategorie()->getNom();
            }
            if ($tb->getConfigurationSegment()) {
                $segmentNom = $tb->getConfigurationSegment()->getNom();
                if ($segmentNom !== 'tous' && !isset($segments[$segmentNom])) {
                    $segments[$segmentNom] = $segmentNom;
                }
            }
        }

        return [
            'historiques' => $groupedByType,
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalItems' => $totalItems,
                'limit' => $limit,
                'categorieFilter' => $categorieFilter,
                'segmentFilter' => $segmentFilter,
                'itemsOnCurrentPage' => $itemsOnCurrentPage,
            ],
            'categories' => $categories,
            'segments' => $segments,
        ];
    }

    /**
     * Met à jour le prix d'un type de billet
     */
    public function updatePrice(
        TypeBillet $typeBillet,
        float $nouveauPrix,
        ?User $user = null,
        ?string $raison = null,
        ?array $metadonnees = null
    ): array {
        $nouveauPrixString = is_numeric($nouveauPrix) ? number_format((float)$nouveauPrix, 2, '.', '') : (string)$nouveauPrix;
        $ancienPrix = $typeBillet->getPrixDeBase();
        
        $this->typeBilletService->update($typeBillet, [
            'prixDeBase' => $nouveauPrixString,
        ]);
        
        $this->historiquePrixBilletService->enregistrerChangement(
            $typeBillet,
            $ancienPrix,
            $nouveauPrixString,
            $user,
            $raison,
            $metadonnees
        );
        
        return [
            'success' => true,
            'nouveauPrix' => number_format((float)$nouveauPrix, 2, ',', ' '),
            'devise' => $typeBillet->getDevise(),
        ];
    }

    /**
     * Met à jour un type de billet (prix et quantité)
     */
    public function updateTypeBillet(
        TypeBillet $typeBillet,
        float $prixDeBase,
        int $quantiteTotale,
        User $user
    ): array {
        // Validation
        if ($prixDeBase < 0) {
            return [
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => ['prixDeBase' => 'Le prix doit être un nombre positif']
            ];
        }

        $inventaire = $typeBillet->getInventaire();
        $quantiteVendue = $inventaire ? $inventaire->getQuantiteVendue() : 0;
        
        if ($quantiteTotale < $quantiteVendue) {
            return [
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => ['quantiteTotale' => "La quantité ne peut pas être inférieure à {$quantiteVendue} (quantité déjà vendue)"]
            ];
        }

        // Mettre à jour le prix
        $nouveauPrix = number_format((float)$prixDeBase, 2, '.', '');
        $ancienPrix = $typeBillet->getPrixDeBase();
        
        $this->typeBilletService->update($typeBillet, [
            'prixDeBase' => $nouveauPrix,
        ]);

        // Enregistrer l'historique si le prix a changé
        if ($ancienPrix !== $nouveauPrix) {
            $this->historiquePrixBilletService->enregistrerChangement(
                $typeBillet,
                $ancienPrix,
                $nouveauPrix,
                $user,
                'Modification via interface catégories',
                ['action' => 'modification_prix_quantite', 'type_billet_id' => $typeBillet->getId()]
            );
        }

        // Mettre à jour la quantité dans l'inventaire
        if ($inventaire) {
            $this->inventaireBilletService->update($inventaire, [
                'quantiteTotale' => $quantiteTotale,
            ]);
        }

        return [
            'success' => true,
            'message' => 'Type de billet modifié avec succès',
        ];
    }

    /**
     * Vérifie si un événement est actif ou à venir
     */
    public function isEventActiveOrUpcoming(Event $event): bool
    {
        $now = new \DateTime();
        $isEventActive = $event->getCommenceLe() && $event->getSeTermineLe()
            && $now >= $event->getCommenceLe() && $now <= $event->getSeTermineLe();
        $isEventUpcoming = $event->getCommenceLe() && $now < $event->getCommenceLe();

        return $isEventActive || $isEventUpcoming;
    }
}

