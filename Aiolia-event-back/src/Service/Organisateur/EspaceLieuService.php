<?php

namespace App\Service\Organisateur;

use App\Entity\EspaceLieu;
use App\Entity\Venue;
use App\Repository\Organisateur\EspaceLieuRepository;

class EspaceLieuService
{
    public function __construct(
        private EspaceLieuRepository $repository
    ) {
    }

    /**
     * Récupère tous les espaces de lieux
     */
    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    /**
     * Récupère un espace de lieu par son ID
     */
    public function getById(string $id): ?EspaceLieu
    {
        return $this->repository->getById($id);
    }

    /**
     * Récupère tous les espaces d'un lieu
     */
    public function getByLieu(Venue $lieu): array
    {
        return $this->repository->findByLieu($lieu);
    }

    /**
     * Récupère l'espace par défaut d'un lieu
     */
    public function getDefaultByLieu(Venue $lieu): ?EspaceLieu
    {
        return $this->repository->findDefaultByLieu($lieu);
    }

    /**
     * Crée un nouvel espace de lieu
     */
    public function create(array $data, Venue $lieu): EspaceLieu
    {
        $espaceLieu = new EspaceLieu();
        $espaceLieu->setLieu($lieu);

        if (isset($data['nom'])) {
            $espaceLieu->setNom($data['nom']);
        }

        if (isset($data['description'])) {
            $espaceLieu->setDescription($data['description']);
        }

        if (isset($data['capacite'])) {
            $espaceLieu->setCapacite($data['capacite']);
        }

        if (isset($data['plan'])) {
            $espaceLieu->setPlan($data['plan']);
        }

        if (isset($data['equipements'])) {
            $espaceLieu->setEquipements($data['equipements']);
        }

        if (isset($data['estParDefaut'])) {
            $espaceLieu->setEstParDefaut($data['estParDefaut']);
        }

        return $this->repository->create($espaceLieu);
    }

    /**
     * Met à jour un espace de lieu
     */
    public function update(EspaceLieu $espaceLieu, array $data): EspaceLieu
    {
        if (isset($data['nom'])) {
            $espaceLieu->setNom($data['nom']);
        }

        if (isset($data['description'])) {
            $espaceLieu->setDescription($data['description']);
        }

        if (isset($data['capacite'])) {
            $espaceLieu->setCapacite($data['capacite']);
        }

        if (isset($data['plan'])) {
            $espaceLieu->setPlan($data['plan']);
        }

        if (isset($data['equipements'])) {
            $espaceLieu->setEquipements($data['equipements']);
        }

        if (isset($data['estParDefaut'])) {
            $espaceLieu->setEstParDefaut($data['estParDefaut']);
        }

        return $this->repository->update($espaceLieu);
    }

    /**
     * Supprime un espace de lieu
     */
    public function delete(EspaceLieu $espaceLieu): void
    {
        $this->repository->delete($espaceLieu);
    }
}

