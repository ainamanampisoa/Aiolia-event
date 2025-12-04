<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Enum\Role as UserRoleEnum;
use App\Repository\UserRepository;
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

        if ($user->getStatutCompte() !== 'pending_validation') {
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

        // ⚠️ Ne pas encore valider en base, seulement mettre en mémoire
        $user->setRole($targetRole);
        $user->setStatutCompte('active');

        // Envoyer l'email AVANT flush()
        $emailSent = $this->notificationService->sendValidationApprovedNotification(
            $user,
            $user->getRole(),
            $comment
        );

        if (!$emailSent) {

            // 🔄 Rollback des valeurs non validées
            $user->setRole($oldRole);
            $user->setStatutCompte($oldStatus);

            $this->addFlash('error', sprintf(
                'Échec de l\'envoi de l\'email pour %s. Aucune modification enregistrée.',
                $user->getNomComplet()
            ));

            return $this->redirectToRoute('admin_users_list');
        }

        // L’email est OK → on valide en base
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

        if ($user->getStatus() !== User::STATUS_PENDING) {
            $this->addFlash('error', 'Ce compte a déjà été traité');
            return $this->redirectToRoute('admin_users_list');
        }

        $comment = $request->request->get('comment');
        $oldStatus = $user->getStatutCompte();

        // Mettre à jour le statut du compte
        $user->setStatutCompte('rejected');

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

