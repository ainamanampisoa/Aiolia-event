<?php

namespace App\Service\Organisateur;

use App\Entity\ModePaiement;
use App\Repository\Organisateur\ModePaiementRepository;

class ModePaiementService
{
    public function __construct(
        private ModePaiementRepository $repository
    ) {
    }

    /**
     * Récupère tous les modes de paiement actifs, triés par ordre d'affichage.
     */
    public function getAllActive(): array
    {
        return $this->repository->getAllActive();
    }

    /**
     * Récupère un mode de paiement par son code.
     */
    public function getByCode(string $code): ?ModePaiement
    {
        return $this->repository->findByCode($code);
    }

    /**
     * Récupère un mode de paiement par son ID.
     */
    public function getById(string $id): ?ModePaiement
    {
        return $this->repository->find($id);
    }
}

