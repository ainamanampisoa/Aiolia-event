<?php

namespace App\Service\Organisateur;

use App\Entity\Event;
use App\Entity\TypeBillet;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use App\Repository\Organisateur\TypeBilletRepository;

class TicketManagementService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TypeBilletService $typeBilletService,
        private TypeBilletRepository $typeBilletRepository,
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
        // Récupérer les alertes de stock via le repository
        $alertesData = $this->typeBilletRepository->findStockAlertsForOrganizer($user);
        
        $alertes = [];
        $alertesTotal = [];
        $eventsIds = [];
        $categories = [];
        $segments = [];
        $capaciteParCategorie = [];
        
        // Traiter les données des alertes
        foreach ($alertesData as $data) {
            $typeBillet = $this->entityManager->getRepository(TypeBillet::class)->find($data['type_billet_id']);
            
            if (!$typeBillet) {
                continue;
            }
            
            $inventaire = $typeBillet->getInventaire();
            $evenement = $typeBillet->getEvenement();
            
            if (!$inventaire || !$evenement) {
                continue;
            }
            
            // Récupérer les valeurs directement des données SQL
            $quantiteTotale = (int) $data['quantite_totale'];
            $quantiteVendue = (int) $data['quantite_vendue'];
            $quantiteReservee = (int) $data['quantite_reservee'];
            
            // CORRECTION : Calcul correct du stock restant
            $quantiteRestante = max(0, $quantiteTotale - $quantiteVendue - $quantiteReservee);
            
            // Pourcentage restant
            $pourcentage = $quantiteTotale > 0
                ? ($quantiteRestante / $quantiteTotale) * 100
                : 0;
            
            // Déterminer le niveau d'alerte - CORRECTION IMPORTANTE
            $niveau = 'attention';
            if ($quantiteRestante === 0) {
                $niveau = 'critique'; // Stock épuisé
            } elseif ($pourcentage <= 5) {
                $niveau = 'critique'; // ≤ 5% de stock
            } elseif ($pourcentage <= 10) {
                $niveau = 'attention'; // ≤ 10% de stock
            } else {
                // Si > 10%, ce n'est pas une alerte (ne devrait pas arriver avec la requête)
                continue;
            }
            
            $categorieNom = $data['categorie_nom'] ?? null;
            $segmentNom = $data['segment_nom'] ?? null;
            
            // Collecter les informations pour les filtres
            if ($categorieNom && !isset($categories[$categorieNom])) {
                $categories[$categorieNom] = $categorieNom;
            }
            
            if ($segmentNom && $segmentNom !== 'tous' && !isset($segments[$segmentNom])) {
                $segments[$segmentNom] = $segmentNom;
            }
            
            // Capacité par catégorie
            $eventId = $evenement->getId();
            if (!in_array($eventId, $eventsIds)) {
                $eventsIds[] = $eventId;
            }
            
            $categorieId = $typeBillet->getConfigurationCategorie() ? 
                $typeBillet->getConfigurationCategorie()->getId() : 'sans_categorie';
                
            if (!isset($capaciteParCategorie[$eventId])) {
                $capaciteParCategorie[$eventId] = [];
            }
            if (!isset($capaciteParCategorie[$eventId][$categorieId])) {
                $capaciteParCategorie[$eventId][$categorieId] = 0;
            }
            $capaciteParCategorie[$eventId][$categorieId] += $quantiteTotale;
            
            // Créer l'alerte avec toutes les données nécessaires
            $alerte = [
                'typeBillet' => $typeBillet,
                'inventaire' => $inventaire,
                'quantiteRestante' => $quantiteRestante,
                'pourcentage' => round($pourcentage, 2),
                'niveau' => $niveau,
                'categorieNom' => $categorieNom,
                'segmentNom' => $segmentNom,
                'listeAttenteCount' => (int) $data['liste_attente_count'],
                'evenementTitre' => $data['evenement_titre'] ?? '',
                'quantiteTotale' => $quantiteTotale,
                'quantiteVendue' => $quantiteVendue,
                'quantiteReservee' => $quantiteReservee,
            ];
            
            $alertesTotal[] = $alerte;
            
            // Appliquer les filtres
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
        
        // Récupérer toutes les catégories et segments pour les options de filtre
        $allCategories = $this->typeBilletRepository->findCategoriesForOrganizer($user);
        $allSegments = $this->typeBilletRepository->findSegmentsForOrganizer($user);
        
        foreach ($allCategories as $catData) {
            $catNom = $catData['nom'];
            if ($catNom && !isset($categories[$catNom])) {
                $categories[$catNom] = $catNom;
            }
        }
        
        foreach ($allSegments as $segData) {
            $segNom = $segData['nom'];
            if ($segNom && $segNom !== 'tous' && !isset($segments[$segNom])) {
                $segments[$segNom] = $segNom;
            }
        }
        
        // Séparer les alertes par niveau pour les compteurs
        $alertesCritiques = array_filter($alertesTotal, fn($a) => $a['niveau'] === 'critique');
        $alertesAttention = array_filter($alertesTotal, fn($a) => $a['niveau'] === 'attention');
        
        return [
            'alertes' => $alertes, // Alertes filtrées
            'alertesTotal' => $alertesTotal, // Toutes les alertes (pour les compteurs)
            'alertesCritiques' => array_values($alertesCritiques),
            'alertesAttention' => array_values($alertesAttention),
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

