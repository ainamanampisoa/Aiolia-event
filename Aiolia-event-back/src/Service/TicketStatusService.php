<?php

namespace App\Service;

use App\Repository\TicketStatusRepository;

class TicketStatusService
{
    public function __construct(
        private TicketStatusRepository $repository
    ) {
    }

    /**
     * @return string[]
     */
    public function getStatuses(): array
    {
        return $this->repository->getAll();
    }

    /**
     * Libellés prêts pour l'affichage (optionnel).
     *
     * @return array<string, string>
     */
    public function getStatusLabels(): array
    {
        return [
            'dispo' => 'Disponible',
            'valid' => 'En attente',
            'used' => 'Utilisé',
            'refunded' => 'Remboursé',
            'transferred' => 'Transféré',
        ];
    }
}


