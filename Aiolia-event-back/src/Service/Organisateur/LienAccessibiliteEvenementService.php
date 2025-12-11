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

    
    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    
    public function getById(Event $evenement, TypeAccessibilite $typeAccessibilite): ?LienAccessibiliteEvenement
    {
        return $this->repository->getById($evenement, $typeAccessibilite);
    }

    /**
     * Vérifie si un lien existe déjà pour un événement et un type d'accessibilité.
     */
    public function exists(Event $evenement, TypeAccessibilite $typeAccessibilite): bool
    {
        return $this->repository->getById($evenement, $typeAccessibilite) !== null;
    }

    
    public function getByEvenement(Event $evenement): array
    {
        return $this->repository->findByEvenement($evenement);
    }

    
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

    
    public function update(LienAccessibiliteEvenement $lien, array $data): LienAccessibiliteEvenement
    {
        if (isset($data['description'])) {
            $lien->setDescription($data['description']);
        }

        return $this->repository->update($lien);
    }

    
    public function delete(LienAccessibiliteEvenement $lien): void
    {
        $this->repository->delete($lien);
    }

    /**
     * Supprime tous les liens d'accessibilité pour un événement.
     */
    public function deleteAllForEvent(Event $evenement): void
    {
        $liens = $this->getByEvenement($evenement);
        foreach ($liens as $lien) {
            $this->delete($lien);
        }
    }
}

