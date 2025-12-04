<?php

namespace App\Service\Organisateur;

use App\Entity\ConfigurationSegmentBillet;
use App\Repository\Organisateur\ConfigurationSegmentBilletRepository;

class ConfigurationSegmentBilletService
{
    public function __construct(
        private ConfigurationSegmentBilletRepository $repository
    ) {
    }

    /**
     * Récupère tous les segments de billets
     */
    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    /**
     * Récupère tous les segments de billets actifs
     */
    public function getAllActive(): array
    {
        return $this->repository->getAllActive();
    }

    /**
     * Récupère un segment de billet par son ID
     */
    public function getById(string $id): ?ConfigurationSegmentBillet
    {
        return $this->repository->getById($id);
    }

    /**
     * Récupère un segment de billet par son nom
     */
    public function getByNom(string $nom): ?ConfigurationSegmentBillet
    {
        return $this->repository->findByNom($nom);
    }

    /**
     * Crée un nouveau segment de billet
     */
    public function create(array $data): ConfigurationSegmentBillet
    {
        $segment = new ConfigurationSegmentBillet();

        if (isset($data['nom'])) {
            $segment->setNom($data['nom']);
        }

        if (isset($data['ageMin'])) {
            $segment->setAgeMin($data['ageMin']);
        }

        if (isset($data['ageMax'])) {
            $segment->setAgeMax($data['ageMax']);
        }

        if (isset($data['pourcentagePrix'])) {
            $segment->setPourcentagePrix((string) $data['pourcentagePrix']);
        }

        if (isset($data['estActif'])) {
            $segment->setEstActif($data['estActif']);
        }

        if (isset($data['metadonnees'])) {
            $segment->setMetadonnees($data['metadonnees']);
        }

        return $this->repository->create($segment);
    }

    /**
     * Met à jour un segment de billet
     */
    public function update(ConfigurationSegmentBillet $segment, array $data): ConfigurationSegmentBillet
    {
        if (isset($data['nom'])) {
            $segment->setNom($data['nom']);
        }

        if (isset($data['ageMin'])) {
            $segment->setAgeMin($data['ageMin']);
        }

        if (isset($data['ageMax'])) {
            $segment->setAgeMax($data['ageMax']);
        }

        if (isset($data['pourcentagePrix'])) {
            $segment->setPourcentagePrix((string) $data['pourcentagePrix']);
        }

        if (isset($data['estActif'])) {
            $segment->setEstActif($data['estActif']);
        }

        if (isset($data['metadonnees'])) {
            $segment->setMetadonnees($data['metadonnees']);
        }

        return $this->repository->update($segment);
    }

    /**
     * Supprime définitivement un segment de billet
     */
    public function delete(ConfigurationSegmentBillet $segment): void
    {
        $this->repository->delete($segment);
    }

    /**
     * Suppression logique (soft delete) d'un segment de billet
     */
    public function softDelete(ConfigurationSegmentBillet $segment): void
    {
        $this->repository->softDelete($segment);
    }

    /**
     * Restaure un segment de billet supprimé logiquement
     */
    public function restore(ConfigurationSegmentBillet $segment): void
    {
        $this->repository->restore($segment);
    }

    /**
     * Active un segment de billet
     */
    public function activate(ConfigurationSegmentBillet $segment): ConfigurationSegmentBillet
    {
        $segment->setEstActif(true);
        return $this->repository->update($segment);
    }

    /**
     * Désactive un segment de billet
     */
    public function deactivate(ConfigurationSegmentBillet $segment): ConfigurationSegmentBillet
    {
        $segment->setEstActif(false);
        return $this->repository->update($segment);
    }

    /**
     * Vérifie si un nom de segment existe déjà
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

