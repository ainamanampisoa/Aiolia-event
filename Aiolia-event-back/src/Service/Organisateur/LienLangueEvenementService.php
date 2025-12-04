<?php

namespace App\Service\Organisateur;

use App\Entity\Event;
use App\Entity\Langue;
use App\Entity\LienLangueEvenement;
use App\Repository\Organisateur\LienLangueEvenementRepository;

class LienLangueEvenementService
{
    public function __construct(
        private LienLangueEvenementRepository $repository
    ) {
    }

    /**
     * Récupère tous les liens langues-événements
     */
    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    /**
     * Récupère un lien langue-événement par son ID (composite)
     */
    public function getById(Event $evenement, Langue $langue): ?LienLangueEvenement
    {
        return $this->repository->getById($evenement, $langue);
    }

    /**
     * Récupère tous les liens d'un événement
     */
    public function getByEvenement(Event $evenement): array
    {
        return $this->repository->findByEvenement($evenement);
    }

    /**
     * Crée un nouveau lien langue-événement
     */
    public function create(Event $evenement, Langue $langue): LienLangueEvenement
    {
        $lien = new LienLangueEvenement();
        $lien->setEvenement($evenement);
        $lien->setLangue($langue);

        return $this->repository->create($lien);
    }

    /**
     * Supprime un lien langue-événement
     */
    public function delete(LienLangueEvenement $lien): void
    {
        $this->repository->delete($lien);
    }
}

