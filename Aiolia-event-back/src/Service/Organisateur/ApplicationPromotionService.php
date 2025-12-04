<?php

namespace App\Service\Organisateur;

use App\Entity\ApplicationPromotion;
use App\Entity\CodePromotionnel;
use App\Entity\Commande;
use App\Entity\User;
use App\Repository\Organisateur\ApplicationPromotionRepository;

class ApplicationPromotionService
{
    public function __construct(
        private ApplicationPromotionRepository $repository
    ) {
    }

    /**
     * Récupère toutes les applications de promotions
     */
    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    /**
     * Récupère une application de promotion par son ID
     */
    public function getById(string $id): ?ApplicationPromotion
    {
        return $this->repository->getById($id);
    }

    /**
     * Récupère toutes les applications d'un code promotionnel
     */
    public function getByPromotion(CodePromotionnel $promotion): array
    {
        return $this->repository->findByPromotion($promotion);
    }

    /**
     * Récupère toutes les applications d'une commande
     */
    public function getByCommande(Commande $commande): array
    {
        return $this->repository->findByCommande($commande);
    }

    /**
     * Récupère toutes les applications d'un utilisateur
     */
    public function getByUser(User $user): array
    {
        return $this->repository->findByUser($user);
    }

    /**
     * Vérifie si une promotion a déjà été appliquée à une commande
     */
    public function isPromotionAppliedToCommande(CodePromotionnel $promotion, Commande $commande): bool
    {
        return $this->repository->isPromotionAppliedToCommande($promotion, $commande);
    }

    /**
     * Crée une nouvelle application de promotion
     */
    public function create(array $data, CodePromotionnel $promotion, Commande $commande, User $utilisateur): ApplicationPromotion
    {
        $application = new ApplicationPromotion();
        $application->setPromotion($promotion);
        $application->setCommande($commande);
        $application->setUtilisateur($utilisateur);

        if (isset($data['montantRemise'])) {
            $application->setMontantRemise((string) $data['montantRemise']);
        }

        return $this->repository->create($application);
    }

    /**
     * Met à jour une application de promotion
     */
    public function update(ApplicationPromotion $application, array $data): ApplicationPromotion
    {
        if (isset($data['montantRemise'])) {
            $application->setMontantRemise((string) $data['montantRemise']);
        }

        return $this->repository->update($application);
    }

    /**
     * Supprime une application de promotion
     */
    public function delete(ApplicationPromotion $application): void
    {
        $this->repository->delete($application);
    }
}

