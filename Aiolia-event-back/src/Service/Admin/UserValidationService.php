<?php

namespace App\Service\Admin;

use App\Entity\User;
use App\Entity\OrganizerProfile;
use App\Enum\Role as UserRoleEnum;
use App\Repository\UserRepository;
use App\Repository\Organisateur\OrganizerProfileRepository;
use App\Service\Organisateur\UserNotificationService;
use Doctrine\ORM\EntityManagerInterface;

class UserValidationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private OrganizerProfileRepository $organizerProfileRepository,
        private UserNotificationService $notificationService
    ) {
    }

    /**
     * Approuve une demande d'utilisateur
     */
    public function approveUser(
        int $userId,
        ?string $targetRole = null,
        ?string $comment = null
    ): array {
        $user = $this->userRepository->find($userId);

        if (!$user instanceof User) {
            return [
                'success' => false,
                'error' => 'Utilisateur introuvable',
            ];
        }

        // Vérifier le statut de validation
        $organizerProfile = null;
        if ($user->getRole() === UserRoleEnum::ORGANIZER) {
            $organizerProfile = $this->organizerProfileRepository->findByUser($user);
        }

        $isPendingByAccountStatus = $user->getStatutCompte() === 'pending_validation';
        $isPendingByProfileStatus = $organizerProfile instanceof OrganizerProfile
            && $organizerProfile->getStatutVerification() === OrganizerProfile::STATUS_PENDING;

        if (!$isPendingByAccountStatus && !$isPendingByProfileStatus) {
            return [
                'success' => false,
                'error' => 'Ce compte a déjà été traité',
            ];
        }

        $targetRole = $targetRole ? UserRoleEnum::normalize($targetRole) : $user->getRole();
        if (!UserRoleEnum::isValid($targetRole)) {
            $targetRole = $user->getRole();
        }

        // Sauvegarde des anciennes valeurs
        $oldRole = $user->getRole();
        $oldStatus = $user->getStatutCompte();
        $oldProfileStatus = $organizerProfile instanceof OrganizerProfile
            ? $organizerProfile->getStatutVerification()
            : null;

        // Mettre à jour les valeurs en mémoire
        $user->setRole($targetRole);
        $user->setStatutCompte('active');
        if ($organizerProfile instanceof OrganizerProfile) {
            $organizerProfile->setStatutVerification(OrganizerProfile::STATUS_VERIFIED);
        }

        // Envoyer l'email AVANT flush()
        try {
            $emailResult = $this->notificationService->sendValidationApprovedNotificationWithDetails(
                $user,
                $user->getRole(),
                $comment
            );

            if (!isset($emailResult['success']) || $emailResult['success'] !== true) {
                throw new \RuntimeException($emailResult['error'] ?? 'Échec de l\'envoi de l\'email');
            }
        } catch (\Throwable $e) {
            // Rollback des valeurs
            $user->setRole($oldRole);
            $user->setStatutCompte($oldStatus);
            if ($organizerProfile instanceof OrganizerProfile && $oldProfileStatus !== null) {
                $organizerProfile->setStatutVerification($oldProfileStatus);
            }

            return [
                'success' => false,
                'error' => sprintf(
                    'Échec de l\'envoi de l\'email pour %s. Aucune modification enregistrée.',
                    $user->getNomComplet()
                ),
                'exception' => $e,
            ];
        }

        // L'email est OK → on valide en base
        $this->entityManager->flush();

        return [
            'success' => true,
            'user' => $user,
            'oldRole' => $oldRole,
            'oldStatus' => $oldStatus,
        ];
    }

    /**
     * Rejette une demande d'utilisateur
     */
    public function rejectUser(int $userId, ?string $comment = null): array
    {
        $user = $this->userRepository->find($userId);

        if (!$user instanceof User) {
            return [
                'success' => false,
                'error' => 'Utilisateur introuvable',
            ];
        }

        if ($user->getStatutCompte() !== 'pending_validation') {
            // Vérifier le profil organisateur si le compte n'est pas en pending_validation
            $organizerProfile = null;
            if ($user->getRole() === UserRoleEnum::ORGANIZER) {
                $organizerProfile = $this->organizerProfileRepository->findByUser($user);
            }

            $isPendingByProfileStatus = $organizerProfile instanceof OrganizerProfile
                && $organizerProfile->getStatutVerification() === OrganizerProfile::STATUS_PENDING;

            if (!$isPendingByProfileStatus) {
                return [
                    'success' => false,
                    'error' => 'Ce compte a déjà été traité',
                ];
            }
        }

        $oldStatus = $user->getStatutCompte();

        // Mettre à jour le statut du compte
        $user->setStatutCompte('rejected');

        // Mettre à jour le statut de vérification de l'organisateur (si applicable)
        if ($user->getRole() === UserRoleEnum::ORGANIZER) {
            $organizerProfile = $this->organizerProfileRepository->findByUser($user);
            if ($organizerProfile instanceof OrganizerProfile) {
                $organizerProfile->setStatutVerification(OrganizerProfile::STATUS_REJECTED);
            }
        }

        $this->entityManager->flush();

        // Envoyer une notification par email
        $this->notificationService->sendValidationRejectedNotification(
            $user,
            $user->getRole(),
            $comment
        );

        return [
            'success' => true,
            'user' => $user,
            'oldStatus' => $oldStatus,
        ];
    }

    /**
     * Vérifie si un utilisateur est en attente de validation
     */
    public function isUserPendingValidation(User $user): bool
    {
        $isPendingByAccountStatus = $user->getStatutCompte() === 'pending_validation';
        
        if ($isPendingByAccountStatus) {
            return true;
        }

        if ($user->getRole() === UserRoleEnum::ORGANIZER) {
            $organizerProfile = $this->organizerProfileRepository->findByUser($user);
            if ($organizerProfile instanceof OrganizerProfile) {
                return $organizerProfile->getStatutVerification() === OrganizerProfile::STATUS_PENDING;
            }
        }

        return false;
    }
}

