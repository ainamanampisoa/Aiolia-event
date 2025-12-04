<?php

namespace App\Service\Organisateur;

use App\Entity\Commande;
use App\Entity\Panier;
use App\Entity\User;
use App\Repository\Organisateur\CommandeRepository;

class CommandeService
{
    public function __construct(
        private CommandeRepository $repository
    ) {
    }

    /**
     * Récupère toutes les commandes
     */
    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    /**
     * Récupère une commande par son ID
     */
    public function getById(string $id): ?Commande
    {
        return $this->repository->getById($id);
    }

    /**
     * Récupère toutes les commandes d'un utilisateur
     */
    public function getByUser(User $user): array
    {
        return $this->repository->findByUser($user);
    }

    /**
     * Crée une nouvelle commande
     */
    public function create(array $data, User $utilisateur, ?Panier $panier = null): Commande
    {
        $commande = new Commande();
        $commande->setUtilisateur($utilisateur);

        if ($panier !== null) {
            $commande->setPanier($panier);
        }

        if (isset($data['statut'])) {
            $commande->setStatut($data['statut']);
        }

        if (isset($data['montantTotal'])) {
            $commande->setMontantTotal((string) $data['montantTotal']);
        }

        if (isset($data['montantRemise'])) {
            $commande->setMontantRemise((string) $data['montantRemise']);
        }

        if (isset($data['devise'])) {
            $commande->setDevise($data['devise']);
        }

        if (isset($data['codePromotion'])) {
            $commande->setCodePromotion($data['codePromotion']);
        }

        if (isset($data['paiementDueLe'])) {
            $commande->setPaiementDueLe($data['paiementDueLe']);
        }

        if (isset($data['notes'])) {
            $commande->setNotes($data['notes']);
        }

        return $this->repository->create($commande);
    }

    /**
     * Met à jour une commande
     */
    public function update(Commande $commande, array $data): Commande
    {
        if (isset($data['statut'])) {
            $commande->setStatut($data['statut']);
        }

        if (isset($data['montantTotal'])) {
            $commande->setMontantTotal((string) $data['montantTotal']);
        }

        if (isset($data['montantRemise'])) {
            $commande->setMontantRemise((string) $data['montantRemise']);
        }

        if (isset($data['devise'])) {
            $commande->setDevise($data['devise']);
        }

        if (isset($data['codePromotion'])) {
            $commande->setCodePromotion($data['codePromotion']);
        }

        if (isset($data['paiementDueLe'])) {
            $commande->setPaiementDueLe($data['paiementDueLe']);
        }

        if (isset($data['notes'])) {
            $commande->setNotes($data['notes']);
        }

        return $this->repository->update($commande);
    }

    /**
     * Supprime une commande
     */
    public function delete(Commande $commande): void
    {
        $this->repository->delete($commande);
    }
}

