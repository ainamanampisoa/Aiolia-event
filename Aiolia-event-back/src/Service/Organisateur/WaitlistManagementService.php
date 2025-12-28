<?php

namespace App\Service\Organisateur;

use App\Entity\Event;
use App\Entity\TypeBillet;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class WaitlistManagementService
{
    public function __construct(
        private InventaireBilletService $inventaireBilletService,
        private TypeBilletService $typeBilletService,
        private WaitlistEmailService $waitlistEmailService,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Met à jour la quantité totale d'un type de billet
     */
    public function updateQuantity(TypeBillet $typeBillet, int $quantiteTotale): array
    {
        if ($quantiteTotale < 0) {
            return [
                'success' => false,
                'message' => 'Quantité invalide',
                'errors' => ['quantiteTotale' => 'La quantité doit être un nombre positif']
            ];
        }

        $inventaire = $this->inventaireBilletService->getByTypeBillet($typeBillet);
        
        if (!$inventaire) {
            $inventaire = $this->inventaireBilletService->create([
                'quantiteTotale' => $quantiteTotale,
                'quantiteVendue' => 0,
                'quantiteReservee' => 0
            ], $typeBillet);
        } else {
            if ($quantiteTotale < $inventaire->getQuantiteVendue()) {
                return [
                    'success' => false,
                    'message' => 'Quantité invalide',
                    'errors' => ['quantiteTotale' => 'La quantité totale ne peut pas être inférieure à la quantité vendue (' . $inventaire->getQuantiteVendue() . ')']
                ];
            }

            $this->inventaireBilletService->update($inventaire, [
                'quantiteTotale' => $quantiteTotale
            ]);
        }

        return [
            'success' => true,
            'message' => 'Quantité mise à jour avec succès',
            'data' => [
                'quantiteTotale' => $inventaire->getQuantiteTotale(),
                'quantiteVendue' => $inventaire->getQuantiteVendue(),
                'quantiteDisponible' => $inventaire->getQuantiteDisponible()
            ]
        ];
    }

    /**
     * Traite une demande de liste d'attente (acceptation ou rejet)
     */
    public function processWaitlistRequest(
        User $waitlistUser,
        TypeBillet $typeBillet,
        string $action,
        ?int $quantiteTotale = null,
        ?float $prixDeBase = null
    ): array {
        if (!in_array($action, ['accept', 'reject'])) {
            return [
                'success' => false,
                'message' => 'Action invalide. Doit être "accept" ou "reject"'
            ];
        }

        $event = $typeBillet->getEvenement();
        if (!$event) {
            return [
                'success' => false,
                'message' => 'Événement non trouvé'
            ];
        }

        if ($action === 'accept') {
            return $this->acceptWaitlistRequest($waitlistUser, $event, $typeBillet, $quantiteTotale, $prixDeBase);
        } else {
            return $this->rejectWaitlistRequest($waitlistUser, $event, $typeBillet);
        }
    }

    /**
     * Accepte une demande de liste d'attente
     */
    private function acceptWaitlistRequest(
        User $waitlistUser,
        Event $event,
        TypeBillet $typeBillet,
        ?int $quantiteTotale,
        ?float $prixDeBase
    ): array {
        // Validation
        if ($quantiteTotale === null || $quantiteTotale < 0) {
            return [
                'success' => false,
                'message' => 'Quantité invalide',
                'errors' => ['quantiteTotale' => 'La quantité doit être un nombre positif']
            ];
        }

        if ($prixDeBase === null || $prixDeBase < 0) {
            return [
                'success' => false,
                'message' => 'Prix invalide',
                'errors' => ['prixDeBase' => 'Le prix doit être un nombre positif']
            ];
        }

        $inventaire = $this->inventaireBilletService->getByTypeBillet($typeBillet);
        $quantiteVendue = $inventaire ? $inventaire->getQuantiteVendue() : 0;

        if ($quantiteTotale < $quantiteVendue) {
            return [
                'success' => false,
                'message' => 'Quantité invalide',
                'errors' => ['quantiteTotale' => 'La quantité totale ne peut pas être inférieure à la quantité vendue (' . $quantiteVendue . ')']
            ];
        }

        // Vérifier la capacité de l'événement
        $quantiteDisponible = $quantiteTotale - $quantiteVendue;
        $quantiteDemandee = $this->getWaitlistQuantity($waitlistUser->getId(), $typeBillet->getId());
        
        if ($quantiteDisponible < $quantiteDemandee) {
            return [
                'success' => false,
                'message' => 'Capacité insuffisante',
                'errors' => ['quantiteTotale' => 'La quantité disponible (' . $quantiteDisponible . ') est insuffisante pour satisfaire la demande (' . $quantiteDemandee . ')']
            ];
        }

        // Mettre à jour le prix et la quantité
        $this->typeBilletService->update($typeBillet, [
            'prixDeBase' => $prixDeBase
        ]);

        if (!$inventaire) {
            $inventaire = $this->inventaireBilletService->create([
                'quantiteTotale' => $quantiteTotale,
                'quantiteVendue' => 0,
                'quantiteReservee' => 0
            ], $typeBillet);
        } else {
            $this->inventaireBilletService->update($inventaire, [
                'quantiteTotale' => $quantiteTotale
            ]);
        }

        // Supprimer l'entrée de la liste d'attente
        $this->removeWaitlistEntry($waitlistUser->getId(), $typeBillet->getId());

        // Envoyer l'email de confirmation
        $this->waitlistEmailService->sendAcceptanceEmail($waitlistUser, $event, $typeBillet, $quantiteDemandee);

        return [
            'success' => true,
            'message' => 'Liste d\'attente acceptée avec succès. Un email de confirmation a été envoyé à l\'utilisateur.'
        ];
    }

    /**
     * Rejette une demande de liste d'attente
     */
    private function rejectWaitlistRequest(User $waitlistUser, Event $event, TypeBillet $typeBillet): array
    {
        // Supprimer l'entrée de la liste d'attente
        $this->removeWaitlistEntry($waitlistUser->getId(), $typeBillet->getId());

        // Envoyer l'email de rejet
        $this->waitlistEmailService->sendRejectionEmail($waitlistUser, $event, $typeBillet);

        return [
            'success' => true,
            'message' => 'Liste d\'attente rejetée. Un email de notification a été envoyé à l\'utilisateur.'
        ];
    }

    /**
     * Récupère la quantité demandée en liste d'attente
     */
    private function getWaitlistQuantity(string $userId, string $typeBilletId): int
    {
        $conn = $this->entityManager->getConnection();
        $waitlistSql = 'SELECT SUM(quantite_demandee) as quantite_demandee
                      FROM aiolia.listes_attente_billets
                      WHERE id_utilisateur = :userId
                      AND id_type_billet = :typeBilletId
                      AND statut = :statut';
        
        $waitlistResult = $conn->executeQuery($waitlistSql, [
            'userId' => $userId,
            'typeBilletId' => $typeBilletId,
            'statut' => 'pending'
        ])->fetchAssociative();
        
        return (int)($waitlistResult['quantite_demandee'] ?? 0);
    }

    /**
     * Supprime une entrée de la liste d'attente
     */
    private function removeWaitlistEntry(string $userId, string $typeBilletId): void
    {
        $conn = $this->entityManager->getConnection();
        $deleteSql = 'DELETE FROM aiolia.listes_attente_billets
                     WHERE id_utilisateur = :userId
                     AND id_type_billet = :typeBilletId';
        
        $conn->executeStatement($deleteSql, [
            'userId' => $userId,
            'typeBilletId' => $typeBilletId
        ]);
    }
}

