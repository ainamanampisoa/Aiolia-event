<?php

namespace App\Service\Organisateur;

use App\Entity\ConfigurationCategorieBillet;
use App\Entity\ConfigurationSegmentBillet;
use App\Entity\Event;
use App\Entity\SessionEvenement;
use App\Entity\TypeBillet;
use App\Entity\User;
use App\Repository\Organisateur\TypeBilletRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;

class TypeBilletService
{
    public function __construct(
        private TypeBilletRepository $repository
    ) {
    }

    
    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    
    public function getById(string $id): ?TypeBillet
    {
        return $this->repository->getById($id);
    }

    
    public function getByEvenement(Event $evenement): array
    {
        return $this->repository->findByEvenement($evenement);
    }

    
    public function create(array $data, Event $evenement): TypeBillet
    {
        $typeBillet = new TypeBillet();
        $typeBillet->setEvenement($evenement);

        if (isset($data['session']) && $data['session'] instanceof SessionEvenement) {
            $typeBillet->setSession($data['session']);
        }

        if (isset($data['configurationCategorie']) && $data['configurationCategorie'] instanceof ConfigurationCategorieBillet) {
            $typeBillet->setConfigurationCategorie($data['configurationCategorie']);
        }

        if (isset($data['configurationSegment']) && $data['configurationSegment'] instanceof ConfigurationSegmentBillet) {
            $typeBillet->setConfigurationSegment($data['configurationSegment']);
        }

        if (isset($data['nom'])) {
            $typeBillet->setNom($data['nom']);
        }

        if (isset($data['description'])) {
            $typeBillet->setDescription($data['description']);
        }

        if (isset($data['devise'])) {
            $typeBillet->setDevise($data['devise']);
        }

        if (isset($data['prixDeBase'])) {
            $typeBillet->setPrixDeBase((string) $data['prixDeBase']);
        }

        if (isset($data['fraisService'])) {
            $typeBillet->setFraisService((string) $data['fraisService']);
        }

        if (isset($data['tauxTva'])) {
            $typeBillet->setTauxTva((string) $data['tauxTva']);
        }

        if (isset($data['ventesCommencentLe'])) {
            $typeBillet->setVentesCommencentLe($data['ventesCommencentLe']);
        }

        if (isset($data['ventesSeTerminentLe'])) {
            $typeBillet->setVentesSeTerminentLe($data['ventesSeTerminentLe']);
        }

        if (isset($data['minimumParCommande'])) {
            $typeBillet->setMinimumParCommande($data['minimumParCommande']);
        }

        if (isset($data['maximumParCommande'])) {
            $typeBillet->setMaximumParCommande($data['maximumParCommande']);
        }

        if (isset($data['metadonnees'])) {
            $typeBillet->setMetadonnees($data['metadonnees']);
        }

        return $this->repository->create($typeBillet);
    }

    
    public function update(TypeBillet $typeBillet, array $data): TypeBillet
    {
        if (isset($data['session']) && $data['session'] instanceof SessionEvenement) {
            $typeBillet->setSession($data['session']);
        }

        if (isset($data['configurationCategorie']) && $data['configurationCategorie'] instanceof ConfigurationCategorieBillet) {
            $typeBillet->setConfigurationCategorie($data['configurationCategorie']);
        }

        if (isset($data['configurationSegment']) && $data['configurationSegment'] instanceof ConfigurationSegmentBillet) {
            $typeBillet->setConfigurationSegment($data['configurationSegment']);
        }

        if (isset($data['nom'])) {
            $typeBillet->setNom($data['nom']);
        }

        if (isset($data['description'])) {
            $typeBillet->setDescription($data['description']);
        }

        if (isset($data['devise'])) {
            $typeBillet->setDevise($data['devise']);
        }

        if (isset($data['prixDeBase'])) {
            $typeBillet->setPrixDeBase((string) $data['prixDeBase']);
        }

        if (isset($data['fraisService'])) {
            $typeBillet->setFraisService((string) $data['fraisService']);
        }

        if (isset($data['tauxTva'])) {
            $typeBillet->setTauxTva((string) $data['tauxTva']);
        }

        if (isset($data['ventesCommencentLe'])) {
            $typeBillet->setVentesCommencentLe($data['ventesCommencentLe']);
        }

        if (isset($data['ventesSeTerminentLe'])) {
            $typeBillet->setVentesSeTerminentLe($data['ventesSeTerminentLe']);
        }

        if (isset($data['minimumParCommande'])) {
            $typeBillet->setMinimumParCommande($data['minimumParCommande']);
        }

        if (isset($data['maximumParCommande'])) {
            $typeBillet->setMaximumParCommande($data['maximumParCommande']);
        }

        if (isset($data['metadonnees'])) {
            $typeBillet->setMetadonnees($data['metadonnees']);
        }

        return $this->repository->update($typeBillet);
    }

    
    public function delete(TypeBillet $typeBillet): void
    {
        $this->repository->delete($typeBillet);
    }

    
    public function getByOrganizer(User $organizer): array
    {
        return $this->repository->findByOrganizer($organizer);
    }

    
    public function getByOrganizerPaginated(User $organizer, int $page = 1, int $limit = 6, ?string $categorieFilter = null, ?string $segmentFilter = null, ?\App\Entity\Event $event = null): Paginator
    {
        return $this->repository->findByOrganizerPaginated($organizer, $page, $limit, $categorieFilter, $segmentFilter, $event);
    }
}

