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

    
    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    
    public function getById(string $id): ?ApplicationPromotion
    {
        return $this->repository->getById($id);
    }

    
    public function getByPromotion(CodePromotionnel $promotion): array
    {
        return $this->repository->findByPromotion($promotion);
    }

    
    public function getByCommande(Commande $commande): array
    {
        return $this->repository->findByCommande($commande);
    }

    
    public function getByUser(User $user): array
    {
        return $this->repository->findByUser($user);
    }

    
    public function isPromotionAppliedToCommande(CodePromotionnel $promotion, Commande $commande): bool
    {
        return $this->repository->isPromotionAppliedToCommande($promotion, $commande);
    }

    
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

    
    public function update(ApplicationPromotion $application, array $data): ApplicationPromotion
    {
        if (isset($data['montantRemise'])) {
            $application->setMontantRemise((string) $data['montantRemise']);
        }

        return $this->repository->update($application);
    }

    
    public function delete(ApplicationPromotion $application): void
    {
        $this->repository->delete($application);
    }
}

