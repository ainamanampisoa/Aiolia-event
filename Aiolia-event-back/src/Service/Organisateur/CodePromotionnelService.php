<?php

namespace App\Service\Organisateur;

use App\Entity\CodePromotionnel;
use App\Entity\OrganizerProfile;
use App\Repository\Organisateur\CodePromotionnelRepository;

class CodePromotionnelService
{
    public function __construct(
        private CodePromotionnelRepository $repository
    ) {
    }

    /**
     * Récupère tous les codes promotionnels
     */
    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    /**
     * Récupère un code promotionnel par son ID
     */
    public function getById(string $id): ?CodePromotionnel
    {
        return $this->repository->getById($id);
    }

    /**
     * Récupère un code promotionnel par son code
     */
    public function getByCode(string $code): ?CodePromotionnel
    {
        return $this->repository->findByCode($code);
    }

    /**
     * Récupère tous les codes promotionnels actifs
     */
    public function getActive(): array
    {
        return $this->repository->findActive();
    }

    /**
     * Crée un nouveau code promotionnel
     */
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

    /**
     * Met à jour un code promotionnel
     */
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

    /**
     * Supprime un code promotionnel
     */
    public function delete(CodePromotionnel $codePromotionnel): void
    {
        $this->repository->delete($codePromotionnel);
    }
}

