<?php

namespace App\Service\Organisateur;

use App\Entity\ConfigurationCategorieBillet;
use App\Repository\Organisateur\ConfigurationCategorieBilletRepository;

class ConfigurationCategorieBilletService
{
    public function __construct(
        private ConfigurationCategorieBilletRepository $repository
    ) {
    }

    /**
     * Récupère toutes les catégories de billets
     */
    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    /**
     * Récupère toutes les catégories de billets actives
     */
    public function getAllActive(): array
    {
        return $this->repository->getAllActive();
    }

    /**
     * Récupère une catégorie de billet par son ID
     */
    public function getById(string $id): ?ConfigurationCategorieBillet
    {
        return $this->repository->getById($id);
    }

    /**
     * Récupère une catégorie de billet par son nom
     */
    public function getByNom(string $nom): ?ConfigurationCategorieBillet
    {
        return $this->repository->findByNom($nom);
    }

    /**
     * Crée une nouvelle catégorie de billet
     */
    public function create(array $data): ConfigurationCategorieBillet
    {
        $categorie = new ConfigurationCategorieBillet();

        if (isset($data['nom'])) {
            $categorie->setNom($data['nom']);
        }

        if (isset($data['description'])) {
            $categorie->setDescription($data['description']);
        }

        if (isset($data['pourcentagePrix'])) {
            $categorie->setPourcentagePrix((string) $data['pourcentagePrix']);
        }

        if (isset($data['estActif'])) {
            $categorie->setEstActif($data['estActif']);
        }

        if (isset($data['metadonnees'])) {
            $categorie->setMetadonnees($data['metadonnees']);
        }

        return $this->repository->create($categorie);
    }

    /**
     * Met à jour une catégorie de billet
     */
    public function update(ConfigurationCategorieBillet $categorie, array $data): ConfigurationCategorieBillet
    {
        if (isset($data['nom'])) {
            $categorie->setNom($data['nom']);
        }

        if (isset($data['description'])) {
            $categorie->setDescription($data['description']);
        }

        if (isset($data['pourcentagePrix'])) {
            $categorie->setPourcentagePrix((string) $data['pourcentagePrix']);
        }

        if (isset($data['estActif'])) {
            $categorie->setEstActif($data['estActif']);
        }

        if (isset($data['metadonnees'])) {
            $categorie->setMetadonnees($data['metadonnees']);
        }

        return $this->repository->update($categorie);
    }

    /**
     * Supprime définitivement une catégorie de billet
     */
    public function delete(ConfigurationCategorieBillet $categorie): void
    {
        $this->repository->delete($categorie);
    }

    /**
     * Suppression logique (soft delete) d'une catégorie de billet
     */
    public function softDelete(ConfigurationCategorieBillet $categorie): void
    {
        $this->repository->softDelete($categorie);
    }

    /**
     * Restaure une catégorie de billet supprimée logiquement
     */
    public function restore(ConfigurationCategorieBillet $categorie): void
    {
        $this->repository->restore($categorie);
    }

    /**
     * Active une catégorie de billet
     */
    public function activate(ConfigurationCategorieBillet $categorie): ConfigurationCategorieBillet
    {
        $categorie->setEstActif(true);
        return $this->repository->update($categorie);
    }

    /**
     * Désactive une catégorie de billet
     */
    public function deactivate(ConfigurationCategorieBillet $categorie): ConfigurationCategorieBillet
    {
        $categorie->setEstActif(false);
        return $this->repository->update($categorie);
    }

    /**
     * Vérifie si un nom de catégorie existe déjà
     */
    public function nomExists(string $nom, ?string $excludeId = null): bool
    {
        $existing = $this->repository->findByNom($nom);
        
        if ($existing === null) {
            return false;
        }

        if ($excludeId !== null && $existing->getId() === $excludeId) {
            return false;
        }

        return true;
    }
}

