<?php

namespace App\Repository;

class TicketStatusRepository
{
    /**
     * Retourne la liste complète des statuts disponibles.
     *
     * @return string[]
     */
    public function getAll(): array
    {
        return [
            'dispo',
            'valid',
            'used',
            'refunded',
            'transferred',
        ];
    }
}



