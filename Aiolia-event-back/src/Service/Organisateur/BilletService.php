<?php

namespace App\Service\Organisateur;

use App\Entity\Billet;
use App\Entity\ElementCommande;
use App\Entity\TypeBillet;
use App\Entity\User;
use App\Repository\Organisateur\BilletRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;

class BilletService
{
    public function __construct(
        private BilletRepository $repository
    ) {
    }

    
    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    
    public function getById(string $id): ?Billet
    {
        return $this->repository->getById($id);
    }

    
    public function getByCodeQr(string $codeQr): ?Billet
    {
        return $this->repository->findByCodeQr($codeQr);
    }

    
    public function getByUser(User $user): array
    {
        return $this->repository->findByUser($user);
    }

    
    public function getByTypeBillet(TypeBillet $typeBillet): array
    {
        return $this->repository->findByTypeBillet($typeBillet);
    }

    
    public function getByElementCommande(ElementCommande $elementCommande): array
    {
        return $this->repository->findByElementCommande($elementCommande);
    }

    
    public function create(array $data, TypeBillet $typeBillet, ?ElementCommande $elementCommande = null, ?User $utilisateurProprietaire = null): Billet
    {
        $billet = new Billet();
        $billet->setTypeBillet($typeBillet);

        if ($elementCommande !== null) {
            $billet->setElementCommande($elementCommande);
        }

        if ($utilisateurProprietaire !== null) {
            $billet->setUtilisateurProprietaire($utilisateurProprietaire);
        }

        if (isset($data['statut'])) {
            $billet->setStatut($data['statut']);
        }

        if (isset($data['codeQr'])) {
            $billet->setCodeQr($data['codeQr']);
        }

        if (isset($data['checksumQr'])) {
            $billet->setChecksumQr($data['checksumQr']);
        }

        if (isset($data['metadonnees'])) {
            $billet->setMetadonnees($data['metadonnees']);
        }

        return $this->repository->create($billet);
    }

    
    public function update(Billet $billet, array $data): Billet
    {
        if (isset($data['statut'])) {
            $billet->setStatut($data['statut']);
        }

        if (isset($data['codeQr'])) {
            $billet->setCodeQr($data['codeQr']);
        }

        if (isset($data['checksumQr'])) {
            $billet->setChecksumQr($data['checksumQr']);
        }

        if (isset($data['metadonnees'])) {
            $billet->setMetadonnees($data['metadonnees']);
        }

        if (isset($data['utilisateurProprietaire']) && $data['utilisateurProprietaire'] instanceof User) {
            $billet->setUtilisateurProprietaire($data['utilisateurProprietaire']);
        }

        return $this->repository->update($billet);
    }

    
    public function delete(Billet $billet): void
    {
        $this->repository->delete($billet);
    }

    
    public function getByOrganizer(User $organizer): array
    {
        return $this->repository->findByOrganizer($organizer);
    }

    
    public function getByOrganizerPaginated(User $organizer, int $page = 1, int $limit = 10): Paginator
    {
        return $this->repository->findByOrganizerPaginated($organizer, $page, $limit);
    }

    
    public function getStatsByOrganizer(User $organizer): array
    {
        return $this->repository->getStatsByOrganizer($organizer);
    }
}

