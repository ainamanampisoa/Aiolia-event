<?php

namespace App\Service\Organisateur;

use App\Entity\Event;
use App\Entity\User;
use App\Repository\Organisateur\SalesHistoryRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;

class SalesHistoryService
{
    public function __construct(private SalesHistoryRepository $repository)
    {
    }

    public function getPaginated(User $organizer, int $page = 1, int $limit = 10, ?Event $event = null, array $filters = []): array
    {
        return $this->repository->findByOrganizerPaginated($organizer, $page, $limit, $event, $filters);
    }
}


