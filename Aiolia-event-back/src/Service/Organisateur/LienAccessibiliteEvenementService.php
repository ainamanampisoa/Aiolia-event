<?php

namespace App\Service\Organisateur;

use App\Entity\Event;
use App\Entity\LienAccessibiliteEvenement;
use App\Entity\TypeAccessibilite;
use App\Repository\Organisateur\LienAccessibiliteEvenementRepository;

class LienAccessibiliteEvenementService
{
    public function __construct(
        private LienAccessibiliteEvenementRepository $repository
    ) {
    }

    /**
     * Récupère tous les liens accessibilité-événements
     */
    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    /**
     * Récupère un lien accessibilité-événement par son ID (composite)
     */
    public function getById(Event $evenement, TypeAccessibilite $typeAccessibilite): ?LienAccessibiliteEvenement
    {
        return $this->repository->getById($evenement, $typeAccessibilite);
    }

    /**
     * Récupère tous les liens d'un événement
     */
    public function getByEvenement(Event $evenement): array
    {
        return $this->repository->findByEvenement($evenement);
    }

    /**
     * Crée un nouveau lien accessibilité-événement
     */
    public function create(array $data, Event $evenement, TypeAccessibilite $typeAccessibilite): LienAccessibiliteEvenement
    {
        $lien = new LienAccessibiliteEvenement();
        $lien->setEvenement($evenement);
        $lien->setTypeAccessibilite($typeAccessibilite);

        if (isset($data['description'])) {
            $lien->setDescription($data['description']);
        }

        return $this->repository->create($lien);
    }

    /**
     * Met à jour un lien accessibilité-événement
     */
    public function update(LienAccessibiliteEvenement $lien, array $data): LienAccessibiliteEvenement
    {
        if (isset($data['description'])) {
            $lien->setDescription($data['description']);
        }

        return $this->repository->update($lien);
    }

    /**
     * Supprime un lien accessibilité-événement
     */
    public function delete(LienAccessibiliteEvenement $lien): void
    {
        $this->repository->delete($lien);
    }
}

