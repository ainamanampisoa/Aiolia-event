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

    /**
     * Récupère tous les codes promotionnels d'un organisateur
     */
    public function getByOrganisateur(OrganizerProfile $organisateur): array
    {
        return $this->repository->findByOrganisateur($organisateur);
    }

    /**
     * Récupère les codes promotionnels d'un organisateur avec pagination et filtres de date
     *
     * @param OrganizerProfile $organisateur
     * @param int $page Numéro de la page (commence à 1)
     * @param int $perPage Nombre d'éléments par page
     * @param \DateTimeImmutable|null $dateDebut Date de début (peut être null)
     * @param \DateTimeImmutable|null $dateFin Date de fin (peut être null)
     * @return array ['items' => array, 'total' => int, 'pages' => int, 'current_page' => int, 'per_page' => int]
     */
    public function getByOrganisateurPaginated(
        OrganizerProfile $organisateur,
        int $page = 1,
        int $perPage = 4,
        ?\DateTimeImmutable $dateDebut = null,
        ?\DateTimeImmutable $dateFin = null
    ): array {
        return $this->repository->findByOrganisateurPaginated($organisateur, $page, $perPage, $dateDebut, $dateFin);
    }

    /**
     * Récupère les codes promotionnels actifs d'un organisateur
     */
    public function getActiveByOrganisateur(OrganizerProfile $organisateur): array
    {
        return $this->repository->findActiveByOrganisateur($organisateur);
    }

    /**
     * Compte le nombre d'utilisations d'un code promotionnel
     */
    public function countUtilisations(CodePromotionnel $codePromotionnel): int
    {
        return $this->repository->countUtilisations($codePromotionnel);
    }

    /**
     * Compte le nombre d'utilisations d'un code promotionnel par un utilisateur
     */
    public function countUtilisationsByUser(CodePromotionnel $codePromotionnel, User $user): int
    {
        return $this->repository->countUtilisationsByUser($codePromotionnel, $user);
    }

    /**
     * Calcule le montant total des réductions accordées pour un code promotionnel
     */
    public function getTotalRemise(CodePromotionnel $codePromotionnel): float
    {
        return $this->repository->getTotalRemise($codePromotionnel);
    }

    /**
     * Récupère les codes promotionnels qui expirent bientôt
     */
    public function getExpiringSoon(OrganizerProfile $organisateur, int $days = 7): array
    {
        return $this->repository->findExpiringSoon($organisateur, $days);
    }
}

