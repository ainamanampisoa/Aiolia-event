<?php

namespace App\Service\Organisateur;

use App\Entity\Venue;
use App\Repository\Organisateur\VenueRepository;

class VenueService
{
    public function __construct(
        private VenueRepository $repository
    ) {
    }

    
    public function getAllActive(): array
    {
        return $this->repository->findAllActive();
    }

    
    public function getAll(): array
    {
        return $this->repository->findAll();
    }

    
    public function getById(string $id): ?Venue
    {
        return $this->repository->getById($id);
    }
}


