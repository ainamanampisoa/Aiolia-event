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

    
    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    
    public function getById(string $id): ?Langue
    {
        return $this->repository->getById($id);
    }

    
    public function getByCode(string $code): ?Langue
    {
        return $this->repository->findByCode($code);
    }

    
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

    
    public function delete(Langue $langue): void
    {
        $this->repository->delete($langue);
    }
}

