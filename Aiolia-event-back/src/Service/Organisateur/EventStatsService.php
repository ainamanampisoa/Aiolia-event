<?php

namespace App\Service\Organisateur;

use App\Repository\Organisateur\EventStatsRepository;

class EventStatsService
{
    public function __construct(private readonly EventStatsRepository $statsRepository)
    {
    }

    
    public function getViewsCountByEventIds(array $eventIds): array
    {
        return $this->statsRepository->getViewsCountByEventIds($eventIds);
    }

    
    public function getFavoritesCountByEventIds(array $eventIds): array
    {
        return $this->statsRepository->getFavoritesCountByEventIds($eventIds);
    }

    public function getMaxUserCount(): int
    {
        return $this->statsRepository->getMaxUserCount();
    }

    
    public function getParticipantsCountByEventIds(array $eventIds): array
    {
        return $this->statsRepository->getParticipantsCountByEventIds($eventIds);
    }
}

