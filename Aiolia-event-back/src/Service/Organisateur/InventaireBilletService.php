<?php

namespace App\Service\Organisateur;

use App\Entity\InventaireBillet;
use App\Entity\TypeBillet;
use App\Repository\Organisateur\InventaireBilletRepository;

class InventaireBilletService
{
    public function __construct(
        private InventaireBilletRepository $repository
    ) {
    }

    /**
     * Récupère tous les inventaires de billets
     */
    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    /**
     * Récupère un inventaire de billet par son ID
     */
    public function getById(string $id): ?InventaireBillet
    {
        return $this->repository->getById($id);
    }

    /**
     * Récupère l'inventaire d'un type de billet
     */
    public function getByTypeBillet(TypeBillet $typeBillet): ?InventaireBillet
    {
        return $this->repository->findByTypeBillet($typeBillet);
    }

    /**
     * Crée un nouvel inventaire de billet
     */
    public function create(array $data, TypeBillet $typeBillet): InventaireBillet
    {
        $inventaire = new InventaireBillet();
        $inventaire->setTypeBillet($typeBillet);

        if (isset($data['quantiteTotale'])) {
            $inventaire->setQuantiteTotale($data['quantiteTotale']);
        }

        if (isset($data['quantiteReservee'])) {
            $inventaire->setQuantiteReservee($data['quantiteReservee']);
        }

        if (isset($data['quantiteVendue'])) {
            $inventaire->setQuantiteVendue($data['quantiteVendue']);
        }

        return $this->repository->create($inventaire);
    }

    /**
     * Met à jour un inventaire de billet
     */
    public function update(InventaireBillet $inventaire, array $data): InventaireBillet
    {
        if (isset($data['quantiteTotale'])) {
            $inventaire->setQuantiteTotale($data['quantiteTotale']);
        }

        if (isset($data['quantiteReservee'])) {
            $inventaire->setQuantiteReservee($data['quantiteReservee']);
        }

        if (isset($data['quantiteVendue'])) {
            $inventaire->setQuantiteVendue($data['quantiteVendue']);
        }

        return $this->repository->update($inventaire);
    }

    /**
     * Supprime un inventaire de billet
     */
    public function delete(InventaireBillet $inventaire): void
    {
        $this->repository->delete($inventaire);
    }
}

