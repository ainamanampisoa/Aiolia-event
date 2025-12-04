<?php

namespace App\Service\Organisateur;

use App\Entity\Event;
use App\Entity\EspaceLieu;
use App\Entity\SessionEvenement;
use App\Repository\Organisateur\SessionEvenementRepository;

class SessionEvenementService
{
    public function __construct(
        private SessionEvenementRepository $repository
    ) {
    }

    /**
     * Récupère toutes les sessions d'événements
     */
    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    /**
     * Récupère une session d'événement par son ID
     */
    public function getById(string $id): ?SessionEvenement
    {
        return $this->repository->getById($id);
    }

    /**
     * Crée une nouvelle session d'événement
     */
    public function create(array $data, Event $evenement): SessionEvenement
    {
        $session = new SessionEvenement();
        $session->setEvenement($evenement);

        if (isset($data['espaceLieu']) && $data['espaceLieu'] instanceof EspaceLieu) {
            $session->setEspaceLieu($data['espaceLieu']);
        }

        if (isset($data['titre'])) {
            $session->setTitre($data['titre']);
        }

        if (isset($data['description'])) {
            $session->setDescription($data['description']);
        }

        if (isset($data['commenceLe'])) {
            $session->setCommenceLe($data['commenceLe']);
        }

        if (isset($data['seTermineLe'])) {
            $session->setSeTermineLe($data['seTermineLe']);
        }

        if (isset($data['capacite'])) {
            $session->setCapacite($data['capacite']);
        }

        if (isset($data['localisationOverride'])) {
            $session->setLocalisationOverride($data['localisationOverride']);
        }

        if (isset($data['urlLive'])) {
            $session->setUrlLive($data['urlLive']);
        }

        return $this->repository->create($session);
    }

    /**
     * Met à jour une session d'événement
     */
    public function update(SessionEvenement $session, array $data): SessionEvenement
    {
        if (isset($data['espaceLieu']) && $data['espaceLieu'] instanceof EspaceLieu) {
            $session->setEspaceLieu($data['espaceLieu']);
        }

        if (isset($data['titre'])) {
            $session->setTitre($data['titre']);
        }

        if (isset($data['description'])) {
            $session->setDescription($data['description']);
        }

        if (isset($data['commenceLe'])) {
            $session->setCommenceLe($data['commenceLe']);
        }

        if (isset($data['seTermineLe'])) {
            $session->setSeTermineLe($data['seTermineLe']);
        }

        if (isset($data['capacite'])) {
            $session->setCapacite($data['capacite']);
        }

        if (isset($data['localisationOverride'])) {
            $session->setLocalisationOverride($data['localisationOverride']);
        }

        if (isset($data['urlLive'])) {
            $session->setUrlLive($data['urlLive']);
        }

        return $this->repository->update($session);
    }

    /**
     * Supprime une session d'événement
     */
    public function delete(SessionEvenement $session): void
    {
        $this->repository->delete($session);
    }
}

