<?php

namespace App\Service\Organisateur;

use App\Entity\Panier;
use App\Entity\User;
use App\Repository\Organisateur\PanierRepository;

class PanierService
{
    public function __construct(
        private PanierRepository $repository
    ) {
    }

    
    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    
    public function getById(string $id): ?Panier
    {
        return $this->repository->getById($id);
    }

    
    public function getActiveByUser(User $user): ?Panier
    {
        return $this->repository->findActiveByUser($user);
    }

    
    public function getByJetonSession(string $jetonSession): ?Panier
    {
        return $this->repository->findByJetonSession($jetonSession);
    }

    
    public function create(array $data, ?User $utilisateur = null): Panier
    {
        $panier = new Panier();

        if ($utilisateur !== null) {
            $panier->setUtilisateur($utilisateur);
        }

        if (isset($data['statut'])) {
            $panier->setStatut($data['statut']);
        }

        if (isset($data['jetonSession'])) {
            $panier->setJetonSession($data['jetonSession']);
        }

        if (isset($data['devise'])) {
            $panier->setDevise($data['devise']);
        }

        if (isset($data['montantTotal'])) {
            $panier->setMontantTotal((string) $data['montantTotal']);
        }

        if (isset($data['expireLe'])) {
            $panier->setExpireLe($data['expireLe']);
        }

        return $this->repository->create($panier);
    }

    
    public function update(Panier $panier, array $data): Panier
    {
        if (isset($data['statut'])) {
            $panier->setStatut($data['statut']);
        }

        if (isset($data['jetonSession'])) {
            $panier->setJetonSession($data['jetonSession']);
        }

        if (isset($data['devise'])) {
            $panier->setDevise($data['devise']);
        }

        if (isset($data['montantTotal'])) {
            $panier->setMontantTotal((string) $data['montantTotal']);
        }

        if (isset($data['expireLe'])) {
            $panier->setExpireLe($data['expireLe']);
        }

        return $this->repository->update($panier);
    }

    
    public function delete(Panier $panier): void
    {
        $this->repository->delete($panier);
    }
}

