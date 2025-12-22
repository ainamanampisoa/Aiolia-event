<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\OrganizerProfile;
use App\Enum\Role as UserRoleEnum;
use App\Repository\UserRepository;
use App\Repository\Organisateur\OrganizerProfileRepository;
use App\Service\AuditLogService;
use App\Service\Organisateur\UserNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/validation')]
#[IsGranted('ROLE_ADMIN')]
class UserValidationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private OrganizerProfileRepository $organizerProfileRepository,
        private AuditLogService $auditLogService,
        private UserNotificationService $notificationService
    ) {
    }

    /**
     * Approuver une demande
     */
    #[Route('/{id}/approve', name: 'admin_validation_approve', methods: ['POST'])]
    public function approve(int $id, Request $request): Response
    {
        $user = $this->userRepository->find($id);

        if (!$user instanceof User) {
            $this->addFlash('error', 'Utilisateur introuvable');
            return $this->redirectToRoute('admin_users_list');
        }

        // Nouveau système : la "pending validation" peut venir soit du compte,
        // soit du profil organisateur (statut_verification = pending).
        $organizerProfile = null;
        if ($user->getRole() === UserRoleEnum::ORGANIZER) {
            $organizerProfile = $this->organizerProfileRepository->findByUser($user);
        }

        $isPendingByAccountStatus = $user->getStatutCompte() === 'pending_validation';
        $isPendingByProfileStatus = $organizerProfile instanceof OrganizerProfile
            && $organizerProfile->getStatutVerification() === OrganizerProfile::STATUS_PENDING;

        if (!$isPendingByAccountStatus && !$isPendingByProfileStatus) {
            $this->addFlash('error', 'Ce compte a déjà été traité');
            return $this->redirectToRoute('admin_users_list');
        }

        $targetRole = $request->request->get('target_role', $user->getRole());
        $targetRole = UserRoleEnum::normalize($targetRole);

        if (!UserRoleEnum::isValid($targetRole)) {
            $targetRole = $user->getRole();
        }

        $comment = $request->request->get('comment');

        // Sauvegarde des anciennes valeurs
        $oldRole   = $user->getRole();
        $oldStatus = $user->getStatutCompte();
        $oldProfileStatus = $organizerProfile instanceof OrganizerProfile
            ? $organizerProfile->getStatutVerification()
            : null;

        // ⚠️ Ne pas encore valider en base, seulement mettre en mémoire
        $user->setRole($targetRole);
        $user->setStatutCompte('active');
        if ($organizerProfile instanceof OrganizerProfile) {
            $organizerProfile->setStatutVerification(OrganizerProfile::STATUS_VERIFIED);
        }

        // Envoyer l'email AVANT flush() - le statut ne change QUE si l'email est envoyé avec succès
        try {
            $emailResult = $this->notificationService->sendValidationApprovedNotificationWithDetails(
                $user,
                $user->getRole(),
                $comment
            );

            // Vérifier explicitement le résultat
            if (!isset($emailResult['success']) || $emailResult['success'] !== true) {
                throw new \RuntimeException($emailResult['error'] ?? 'Échec de l\'envoi de l\'email');
            }
        } catch (\Throwable $e) {
            // 🔄 Rollback des valeurs non validées
            $user->setRole($oldRole);
            $user->setStatutCompte($oldStatus);
            if ($organizerProfile instanceof OrganizerProfile && $oldProfileStatus !== null) {
                $organizerProfile->setStatutVerification($oldProfileStatus);
            }

            // Stocker les détails de l'erreur pour l'afficher dans la console JavaScript
            $errorDetails = [
                'message' => $e->getMessage(),
                'details' => $e->getTraceAsString(),
                'user' => $user->getNomComplet(),
            ];

            $this->addFlash('error', sprintf(
                'Échec de l\'envoi de l\'email pour %s. Aucune modification enregistrée.',
                $user->getNomComplet()
            ));

            // Stocker les détails de l'erreur dans un flash message spécial pour JavaScript
            $this->addFlash('email_error_details', json_encode($errorDetails, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            return $this->redirectToRoute('admin_users_list');
        }

        // L'email est OK → on valide en base
        $this->entityManager->flush();

        // Log
        $this->auditLogService->log(
            AuditLogService::ACTION_USER_VALIDATED,
            'User',
            $user->getId(),
            [
                'old_role' => $oldRole,
                'new_role' => $user->getRole(),
                'old_status' => $oldStatus,
                'new_status' => $user->getStatutCompte(),
                'comment' => $comment,
            ],
            $this->getUser()
        );

        $this->addFlash('success', sprintf(
            'Demande approuvée : %s est maintenant %s',
            $user->getNomComplet(),
            $this->getRoleLabel($user->getRole())
        ));

        return $this->redirectToRoute('admin_users_list');
    }

    /**
     * Rejeter une demande
     */
    #[Route('/{id}/reject', name: 'admin_validation_reject', methods: ['POST'])]
    public function reject(
        int $id,
        Request $request
    ): Response {
        $user = $this->userRepository->find($id);

        if (!$user instanceof User) {
            $this->addFlash('error', 'Utilisateur introuvable');
            return $this->redirectToRoute('admin_users_list');
        }

        if ($user->getStatutCompte() !== 'pending_validation') {
            // Nouveau système : vérifier le profil organisateur si le compte n'est pas en pending_validation
            $organizerProfile = null;
            if ($user->getRole() === UserRoleEnum::ORGANIZER) {
                $organizerProfile = $this->organizerProfileRepository->findByUser($user);
            }

            $isPendingByProfileStatus = $organizerProfile instanceof OrganizerProfile
                && $organizerProfile->getStatutVerification() === OrganizerProfile::STATUS_PENDING;

            if (!$isPendingByProfileStatus) {
                $this->addFlash('error', 'Ce compte a déjà été traité');
                return $this->redirectToRoute('admin_users_list');
            }
        }

        $comment = $request->request->get('comment');
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

        // Logger l'action
        $this->auditLogService->log(
            AuditLogService::ACTION_USER_REJECTED,
            'User',
            $user->getId(),
            [
                'requested_role' => $user->getRole(),
                'old_status' => $oldStatus,
                'new_status' => $user->getStatutCompte(),
                'reason' => $comment,
            ],
            $this->getUser()
        );

        // Envoyer une notification par email
        $this->notificationService->sendValidationRejectedNotification(
            $user,
            $user->getRole(),
            $comment
        );

        $this->addFlash('success', sprintf(
            'Demande rejetée : %s',
            $user->getNomComplet()
        ));

        // Après rejet, rediriger vers la liste globale des utilisateurs
        return $this->redirectToRoute('admin_users_list');
    }

    private function getRoleLabel(string $role): string
    {
        return match($role) {
            'organizer' => 'Organisateur',
            'admin' => 'Administrateur',
            default => 'Utilisateur',
        };
    }
}

