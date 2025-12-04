<?php

namespace App\Service\Organisateur;

use App\Entity\TypeAccessibilite;
use App\Repository\Organisateur\TypeAccessibiliteRepository;

class TypeAccessibiliteService
{
    public function __construct(
        private TypeAccessibiliteRepository $repository
    ) {
    }

    /**
     * Récupère tous les types d'accessibilité
     */
    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    /**
     * Récupère un type d'accessibilité par son ID
     */
    public function getById(string $id): ?TypeAccessibilite
    {
        return $this->repository->getById($id);
    }

    /**
     * Récupère un type d'accessibilité par son code
     */
    public function getByCode(string $code): ?TypeAccessibilite
    {
        return $this->repository->findByCode($code);
    }

    /**
     * Crée un nouveau type d'accessibilité
     */
    public function create(array $data): TypeAccessibilite
    {
        $typeAccessibilite = new TypeAccessibilite();

        if (isset($data['code'])) {
            $typeAccessibilite->setCode($data['code']);
        }

        if (isset($data['libelle'])) {
            $typeAccessibilite->setLibelle($data['libelle']);
        }

        if (isset($data['estActif'])) {
            $typeAccessibilite->setEstActif($data['estActif']);
        }

        return $this->repository->create($typeAccessibilite);
    }

    /**
     * Met à jour un type d'accessibilité
     */
    public function update(TypeAccessibilite $typeAccessibilite, array $data): TypeAccessibilite
    {
        if (isset($data['code'])) {
            $typeAccessibilite->setCode($data['code']);
        }

        if (isset($data['libelle'])) {
            $typeAccessibilite->setLibelle($data['libelle']);
        }

        if (isset($data['estActif'])) {
            $typeAccessibilite->setEstActif($data['estActif']);
        }

        return $this->repository->update($typeAccessibilite);
    }

    /**
     * Supprime un type d'accessibilité
     */
    public function delete(TypeAccessibilite $typeAccessibilite): void
    {
        $this->repository->delete($typeAccessibilite);
    }
}

