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
        // Récupérer TOUS les types de billets de l'organisateur
        $allTypesBillets = $this->typeBilletRepository->findByOrganizer($user);
        
        $alertes = [];
        $alertesTotal = [];
        $eventsIds = [];
        $categories = [];
        $segments = [];
        $capaciteParCategorie = [];
        
        foreach ($allTypesBillets as $typeBillet) {
            $evenement = $typeBillet->getEvenement();
            
            if (!$evenement) {
                continue;
            }
            
            // Vérifier si l'événement est encore actif ou à venir
            if (!$this->isEventActiveOrUpcoming($evenement)) {
                continue;
            }
            
            // Utiliser la même logique que categories.html.twig via getSalesStatsByTypeBillet
            $stats = $this->billetService->getSalesStatsByTypeBillet($typeBillet);
            
            $quantiteTotale = $stats['stockTotal'];
            $quantiteVendue = $stats['vendus'];
            $quantiteRestante = $stats['disponibles'];
            
            // Pourcentage restant
            $pourcentage = $quantiteTotale > 0
                ? ($quantiteRestante / $quantiteTotale) * 100
                : 0;
            
            // **AJUSTEMENT :** Seuils plus stricts pour éviter trop d'alertes
            // Critique si ≤ 1 billet OU ≤ 5%
            // Attention si ≤ 3 billets OU ≤ 15%
            
            $niveau = null;
            if ($quantiteRestante === 0) {
                $niveau = 'critique'; // Stock épuisé
            } elseif ($quantiteTotale > 0 && $pourcentage <= 5) {
                $niveau = 'critique'; // ≤ 5% de stock
            } elseif ($quantiteRestante <= 1) {
                $niveau = 'critique'; // 1 billet ou moins
            } elseif ($quantiteTotale > 0 && $pourcentage <= 15) {
                $niveau = 'attention'; // ≤ 15% de stock
            } elseif ($quantiteRestante <= 3) {
                $niveau = 'attention'; // ≤ 3 billets
            }
            
            // Si pas d'alerte, passer au suivant
            if (!$niveau) {
                continue;
            }
            
            $categorieNom = $typeBillet->getConfigurationCategorie() ? 
                $typeBillet->getConfigurationCategorie()->getNom() : null;
            $segmentNom = $typeBillet->getConfigurationSegment() ? 
                $typeBillet->getConfigurationSegment()->getNom() : null;
            
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
            // Utiliser quantiteTotale de getSalesStatsByTypeBillet (basé sur les billets réels)
            $capaciteParCategorie[$eventId][$categorieId] += $quantiteTotale;
            
            // **DEBUG :** Afficher les calculs
            error_log(sprintf(
                "CALCUL: Événement: %s, Type: %s, Total: %d, Vendu: %d, Disponible: %d, Restant: %d, %%: %.1f, Niveau: %s",
                $evenement->getTitre(),
                $typeBillet->getNom(),
                $quantiteTotale,
                $quantiteVendue,
                $quantiteRestante,
                $quantiteRestante,
                $pourcentage,
                $niveau ?: 'aucun'
            ));
            
            // Créer l'alerte
            $alerte = [
                'typeBillet' => $typeBillet,
                'inventaire' => $typeBillet->getInventaire(), // Garder pour compatibilité template
                'quantiteRestante' => $quantiteRestante,
                'pourcentage' => round($pourcentage, 2),
                'niveau' => $niveau,
                'categorieNom' => $categorieNom,
                'segmentNom' => $segmentNom,
                'evenementTitre' => $evenement->getTitre(),
                'quantiteTotale' => $quantiteTotale,
                'quantiteVendue' => $quantiteVendue,
                'quantiteReservee' => 0, // Non utilisé avec la nouvelle logique
                'isDataCorrected' => true,
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
        
        // **FILTRE SUPPLEMENTAIRE :** Limiter aux événements vraiment critiques
        // Garder seulement les 10 événements les plus critiques
        
        // Trier par criticité
        usort($alertesTotal, function($a, $b) {
            // D'abord par niveau
            $levelOrder = ['critique' => 1, 'attention' => 2];
            $levelA = $levelOrder[$a['niveau']] ?? 3;
            $levelB = $levelOrder[$b['niveau']] ?? 3;
            
            if ($levelA !== $levelB) {
                return $levelA <=> $levelB;
            }
            
            // Ensuite par pourcentage
            if ($a['pourcentage'] !== $b['pourcentage']) {
                return $a['pourcentage'] <=> $b['pourcentage'];
            }
            
            // Enfin par quantité restante
            return $a['quantiteRestante'] <=> $b['quantiteRestante'];
        });
        
        // Limiter le nombre total d'alertes (éviter 92 alertes)
        $alertesTotal = array_slice($alertesTotal, 0, 20); // Max 20 alertes
        
        // Re-filtrer les alertes paginées selon les filtres
        $alertesFiltrees = [];
        foreach ($alertesTotal as $alerte) {
            if ($niveauFilter && $alerte['niveau'] !== $niveauFilter) {
                continue;
            }
            if ($categorieFilter && $alerte['categorieNom'] !== $categorieFilter) {
                continue;
            }
            if ($segmentFilter && $alerte['segmentNom'] !== $segmentFilter) {
                continue;
            }
            $alertesFiltrees[] = $alerte;
        }
        
        // Séparer les alertes par niveau
        $alertesCritiques = array_filter($alertesTotal, fn($a) => $a['niveau'] === 'critique');
        $alertesAttention = array_filter($alertesTotal, fn($a) => $a['niveau'] === 'attention');
        
        error_log(sprintf(
            "=== RÉSULTATS FINAUX ===
            Types analysés: %d
            Alertes totales: %d
            Critiques: %d
            Attention: %d
            =======================",
            count($allTypesBillets),
            count($alertesTotal),
            count($alertesCritiques),
            count($alertesAttention)
        ));
        
        return [
            'alertes' => $alertesFiltrees, // Alertes filtrées
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