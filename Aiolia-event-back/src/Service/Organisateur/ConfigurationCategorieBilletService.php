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

    
    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    
    public function getAllActive(): array
    {
        return $this->repository->getAllActive();
    }

    
    public function getById(string $id): ?ConfigurationCategorieBillet
    {
        return $this->repository->getById($id);
    }

    
    public function getByNom(string $nom): ?ConfigurationCategorieBillet
    {
        return $this->repository->findByNom($nom);
    }

    
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

    
    public function delete(ConfigurationCategorieBillet $categorie): void
    {
        $this->repository->delete($categorie);
    }

    
    public function softDelete(ConfigurationCategorieBillet $categorie): void
    {
        $this->repository->softDelete($categorie);
    }

    
    public function restore(ConfigurationCategorieBillet $categorie): void
    {
        $this->repository->restore($categorie);
    }

    
    public function activate(ConfigurationCategorieBillet $categorie): ConfigurationCategorieBillet
    {
        $categorie->setEstActif(true);
        return $this->repository->update($categorie);
    }

    
    public function deactivate(ConfigurationCategorieBillet $categorie): ConfigurationCategorieBillet
    {
        $categorie->setEstActif(false);
        return $this->repository->update($categorie);
    }

    
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

