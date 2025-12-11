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

    
    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    
    public function getById(Event $evenement, Langue $langue): ?LienLangueEvenement
    {
        return $this->repository->getById($evenement, $langue);
    }

    
    public function getByEvenement(Event $evenement): array
    {
        return $this->repository->findByEvenement($evenement);
    }

    
    public function create(Event $evenement, Langue $langue): LienLangueEvenement
    {
        $lien = new LienLangueEvenement();
        $lien->setEvenement($evenement);
        $lien->setLangue($langue);

        return $this->repository->create($lien);
    }

    
    public function delete(LienLangueEvenement $lien): void
    {
        $this->repository->delete($lien);
    }
}

