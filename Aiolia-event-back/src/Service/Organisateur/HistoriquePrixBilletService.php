<?php

namespace App\Service\Organisateur;

use App\Entity\HistoriquePrixBillet;
use App\Entity\TypeBillet;
use App\Entity\User;
use App\Repository\Organisateur\HistoriquePrixBilletRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;

class HistoriquePrixBilletService
{
    public function __construct(
        private HistoriquePrixBilletRepository $repository
    ) {
    }

    /**
     * Récupère tous les historiques de prix
     */
    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    /**
     * Récupère un historique par son ID
     */
    public function getById(string $id): ?HistoriquePrixBillet
    {
        return $this->repository->getById($id);
    }

    /**
     * Récupère l'historique des prix pour un type de billet
     */
    public function getByTypeBillet(TypeBillet $typeBillet): array
    {
        return $this->repository->findByTypeBillet($typeBillet);
    }

    /**
     * Récupère l'historique des prix pour un événement
     */
    public function getByEvenement(int $evenementId): array
    {
        return $this->repository->findByEvenement($evenementId);
    }

    /**
     * Enregistre un changement de prix
     */
    public function enregistrerChangement(
        TypeBillet $typeBillet,
        ?string $prixPrecedent,
        ?string $nouveauPrix,
        ?User $modifiePar = null,
        ?string $raison = null,
        ?array $metadonnees = null
    ): HistoriquePrixBillet {
        $historique = new HistoriquePrixBillet();
        $historique->setTypeBillet($typeBillet);
        $historique->setPrixPrecedent($prixPrecedent);
        $historique->setNouveauPrix($nouveauPrix);
        $historique->setModifiePar($modifiePar);
        $historique->setRaison($raison);
        $historique->setMetadonnees($metadonnees);

        return $this->repository->create($historique);
    }

    /**
     * Récupère l'historique paginé pour un type de billet
     */
    public function getByTypeBilletPaginated(TypeBillet $typeBillet, int $page = 1, int $limit = 5): Paginator
    {
        return $this->repository->findByTypeBilletPaginated($typeBillet, $page, $limit);
    }

    /**
     * Récupère l'historique paginé pour un organisateur
     */
    public function getByOrganizerPaginated(User $organizer, int $page = 1, int $limit = 5, ?string $categorieFilter = null, ?string $segmentFilter = null): Paginator
    {
        return $this->repository->findByOrganizerPaginated($organizer, $page, $limit, $categorieFilter, $segmentFilter);
    }
}
