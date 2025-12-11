<?php

namespace App\Service\Organisateur;

use App\Entity\CodePromotionnel;
use App\Entity\OrganizerProfile;
use App\Entity\User;
use App\Repository\Organisateur\CodePromotionnelRepository;

class CodePromotionnelService
{
    public function __construct(
        private CodePromotionnelRepository $repository
    ) {
    }

    
    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    
    public function getById(string $id): ?CodePromotionnel
    {
        return $this->repository->getById($id);
    }

    
    public function getByCode(string $code): ?CodePromotionnel
    {
        return $this->repository->findByCode($code);
    }

    
    public function getActive(): array
    {
        return $this->repository->findActive();
    }

    
    public function create(array $data, ?OrganizerProfile $profilOrganisateur = null): CodePromotionnel
    {
        $codePromotionnel = new CodePromotionnel();

        if ($profilOrganisateur !== null) {
            $codePromotionnel->setProfilOrganisateur($profilOrganisateur);
        }

        if (isset($data['code'])) {
            $codePromotionnel->setCode($data['code']);
        }

        if (isset($data['typePromotion'])) {
            $codePromotionnel->setTypePromotion($data['typePromotion']);
        }

        if (isset($data['valeur'])) {
            $codePromotionnel->setValeur((string) $data['valeur']);
        }

        if (isset($data['utilisationMaximaleTotale'])) {
            $codePromotionnel->setUtilisationMaximaleTotale($data['utilisationMaximaleTotale']);
        }

        if (isset($data['utilisationMaximaleParUtilisateur'])) {
            $codePromotionnel->setUtilisationMaximaleParUtilisateur($data['utilisationMaximaleParUtilisateur']);
        }

        if (isset($data['commenceLe'])) {
            $codePromotionnel->setCommenceLe($data['commenceLe']);
        }

        if (isset($data['seTermineLe'])) {
            $codePromotionnel->setSeTermineLe($data['seTermineLe']);
        }

        if (isset($data['metadonnees'])) {
            $codePromotionnel->setMetadonnees($data['metadonnees']);
        }

        return $this->repository->create($codePromotionnel);
    }

    
    public function update(CodePromotionnel $codePromotionnel, array $data): CodePromotionnel
    {
        if (isset($data['code'])) {
            $codePromotionnel->setCode($data['code']);
        }

        if (isset($data['typePromotion'])) {
            $codePromotionnel->setTypePromotion($data['typePromotion']);
        }

        if (isset($data['valeur'])) {
            $codePromotionnel->setValeur((string) $data['valeur']);
        }

        if (isset($data['utilisationMaximaleTotale'])) {
            $codePromotionnel->setUtilisationMaximaleTotale($data['utilisationMaximaleTotale']);
        }

        if (isset($data['utilisationMaximaleParUtilisateur'])) {
            $codePromotionnel->setUtilisationMaximaleParUtilisateur($data['utilisationMaximaleParUtilisateur']);
        }

        if (isset($data['commenceLe'])) {
            $codePromotionnel->setCommenceLe($data['commenceLe']);
        }

        if (isset($data['seTermineLe'])) {
            $codePromotionnel->setSeTermineLe($data['seTermineLe']);
        }

        if (isset($data['metadonnees'])) {
            $codePromotionnel->setMetadonnees($data['metadonnees']);
        }

        return $this->repository->update($codePromotionnel);
    }

    
    public function delete(CodePromotionnel $codePromotionnel): void
    {
        $this->repository->delete($codePromotionnel);
    }

    
    public function getByOrganisateur(OrganizerProfile $organisateur): array
    {
        return $this->repository->findByOrganisateur($organisateur);
    }

    
    public function getByOrganisateurPaginated(
        OrganizerProfile $organisateur,
        int $page = 1,
        int $perPage = 4,
        ?\DateTimeImmutable $dateDebut = null,
        ?\DateTimeImmutable $dateFin = null
    ): array {
        return $this->repository->findByOrganisateurPaginated($organisateur, $page, $perPage, $dateDebut, $dateFin);
    }

    
    public function getActiveByOrganisateur(OrganizerProfile $organisateur): array
    {
        return $this->repository->findActiveByOrganisateur($organisateur);
    }

    
    public function countUtilisations(CodePromotionnel $codePromotionnel): int
    {
        return $this->repository->countUtilisations($codePromotionnel);
    }

    
    public function countUtilisationsByUser(CodePromotionnel $codePromotionnel, User $user): int
    {
        return $this->repository->countUtilisationsByUser($codePromotionnel, $user);
    }

    
    public function getTotalRemise(CodePromotionnel $codePromotionnel): float
    {
        return $this->repository->getTotalRemise($codePromotionnel);
    }

    
    public function getExpiringSoon(OrganizerProfile $organisateur, int $days = 7): array
    {
        return $this->repository->findExpiringSoon($organisateur, $days);
    }
}

