<?php

namespace App\Service\Organisateur;

use App\Entity\Langue;
use App\Repository\Organisateur\LangueRepository;

class LangueService
{
    public function __construct(
        private LangueRepository $repository
    ) {
    }

    /**
     * Récupère toutes les langues
     */
    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    /**
     * Récupère une langue par son ID
     */
    public function getById(string $id): ?Langue
    {
        return $this->repository->getById($id);
    }

    /**
     * Récupère une langue par son code
     */
    public function getByCode(string $code): ?Langue
    {
        return $this->repository->findByCode($code);
    }

    /**
     * Crée une nouvelle langue
     */
    public function create(array $data): Langue
    {
        $langue = new Langue();

        if (isset($data['code'])) {
            $langue->setCode($data['code']);
        }

        if (isset($data['libelle'])) {
            $langue->setLibelle($data['libelle']);
        }

        if (isset($data['estActif'])) {
            $langue->setEstActif($data['estActif']);
        }

        return $this->repository->create($langue);
    }

    /**
     * Met à jour une langue
     */
    public function update(Langue $langue, array $data): Langue
    {
        if (isset($data['code'])) {
            $langue->setCode($data['code']);
        }

        if (isset($data['libelle'])) {
            $langue->setLibelle($data['libelle']);
        }

        if (isset($data['estActif'])) {
            $langue->setEstActif($data['estActif']);
        }

        return $this->repository->update($langue);
    }

    /**
     * Supprime une langue
     */
    public function delete(Langue $langue): void
    {
        $this->repository->delete($langue);
    }
}

