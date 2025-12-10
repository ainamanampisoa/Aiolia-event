<?php

namespace App\Service\Organisateur;

use App\Entity\EventType;
use App\Repository\Organisateur\EventTypeRepository;

class EventTypeService
{
    public function __construct(
        private EventTypeRepository $repository
    ) {
    }

    
    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    
    public function getById(string $id): ?EventType
    {
        return $this->repository->getById($id);
    }

    
    public function getBySlug(string $slug): ?EventType
    {
        return $this->repository->findBySlug($slug);
    }

    
    public function create(array $data): EventType
    {
        $eventType = new EventType();

        if (isset($data['slug'])) {
            $eventType->setSlug($data['slug']);
        }

        if (isset($data['label'])) {
            $eventType->setLabel($data['label']);
        }

        if (isset($data['description'])) {
            $eventType->setDescription($data['description']);
        }

        return $this->repository->create($eventType);
    }

    
    public function update(EventType $eventType, array $data): EventType
    {
        if (isset($data['slug'])) {
            $eventType->setSlug($data['slug']);
        }

        if (isset($data['label'])) {
            $eventType->setLabel($data['label']);
        }

        if (isset($data['description'])) {
            $eventType->setDescription($data['description']);
        }

        return $this->repository->update($eventType);
    }

    
    public function delete(EventType $eventType): void
    {
        $this->repository->delete($eventType);
    }
}

