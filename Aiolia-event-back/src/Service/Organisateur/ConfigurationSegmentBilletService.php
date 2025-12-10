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

    
    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    
    public function getAllActive(): array
    {
        return $this->repository->getAllActive();
    }

    
    public function getById(string $id): ?ConfigurationSegmentBillet
    {
        return $this->repository->getById($id);
    }

    
    public function getByNom(string $nom): ?ConfigurationSegmentBillet
    {
        return $this->repository->findByNom($nom);
    }

    
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

    
    public function delete(ConfigurationSegmentBillet $segment): void
    {
        $this->repository->delete($segment);
    }

    
    public function softDelete(ConfigurationSegmentBillet $segment): void
    {
        $this->repository->softDelete($segment);
    }

    
    public function restore(ConfigurationSegmentBillet $segment): void
    {
        $this->repository->restore($segment);
    }

    
    public function activate(ConfigurationSegmentBillet $segment): ConfigurationSegmentBillet
    {
        $segment->setEstActif(true);
        return $this->repository->update($segment);
    }

    
    public function deactivate(ConfigurationSegmentBillet $segment): ConfigurationSegmentBillet
    {
        $segment->setEstActif(false);
        return $this->repository->update($segment);
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

