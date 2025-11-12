<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Enum\Role as UserRoleEnum;
use App\Repository\UserRepository;
use App\Service\AuditLogService;
use App\Service\UserNotificationService;
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
     * Liste des demandes de validation en attente
     */
    #[Route('/pending', name: 'admin_validation_pending')]
    public function pending(Request $request): Response
    {
        $pendingAccounts = array_values(array_filter(
            $this->userRepository->findAccountsPendingValidation(),
            static fn(User $user): bool => in_array($user->getRole(), [UserRoleEnum::ORGANIZER, UserRoleEnum::USER], true)
        ));

        $perPage = 5;
        $totalAccounts = count($pendingAccounts);
        $totalPages = max(1, (int) ceil($totalAccounts / $perPage));
        $page = max(1, (int) $request->query->get('page', 1));
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $offset = ($page - 1) * $perPage;
        $paginatedPendingAccounts = array_slice($pendingAccounts, $offset, $perPage);

        $pendingOrganizers = array_filter($pendingAccounts, static fn($user) => $user->getRole() === 'organizer');
        $pendingUsers = array_filter($pendingAccounts, static fn($user) => $user->getRole() === 'user');

        return $this->render('admin/validation/pending.html.twig', [
            'pendingAccounts' => $pendingAccounts,
            'paginatedPendingAccounts' => $paginatedPendingAccounts,
            'pendingStats' => [
                'total' => count($pendingAccounts),
                'organizer' => count($pendingOrganizers),
                'user' => count($pendingUsers),
            ],
            'currentPage' => $page,
            'totalPages' => $totalPages,
        ]);
    }


    /**
     * Approuver une demande
     */
    #[Route('/{id}/approve', name: 'admin_validation_approve', methods: ['POST'])]
    public function approve(
        int $id,
        Request $request
    ): Response {
        $user = $this->userRepository->find($id);

        if (!$user instanceof User) {
            $this->addFlash('error', 'Utilisateur introuvable');
            return $this->redirectToRoute('admin_validation_pending');
        }

        if ($user->getStatus() !== User::STATUS_PENDING) {
            $this->addFlash('error', 'Ce compte a déjà été traité');
            return $this->redirectToRoute('admin_validation_pending');
        }

        $targetRole = $request->request->get('target_role', $user->getRole());
        $targetRole = UserRoleEnum::normalize($targetRole);
        if (!UserRoleEnum::isValid($targetRole)) {
            $targetRole = $user->getRole();
        }

        $comment = $request->request->get('comment');

        $oldRole = $user->getRole();
        $oldStatus = $user->getAccountStatus();
        $user->setRole($targetRole);
        $user->setAccountStatus('active');

        $this->entityManager->flush();

        // Logger l'action
        $this->auditLogService->log(
            AuditLogService::ACTION_USER_VALIDATED,
            'User',
            $user->getId(),
            [
                'old_role' => $oldRole,
                'new_role' => $user->getRole(),
                'old_status' => $oldStatus,
                'new_status' => $user->getAccountStatus(),
                'comment' => $comment,
            ],
            $this->getUser()
        );

        // Envoyer une notification par email
        $this->notificationService->sendValidationApprovedNotification(
            $user,
            $user->getRole(),
            $comment
        );

        $this->addFlash('success', sprintf(
            'Demande approuvée : %s est maintenant %s',
            $user->getFullName(),
            $this->getRoleLabel($user->getRole())
        ));

        return $this->redirectToRoute('admin_validation_pending');
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
            return $this->redirectToRoute('admin_validation_pending');
        }

        if ($user->getStatus() !== User::STATUS_PENDING) {
            $this->addFlash('error', 'Ce compte a déjà été traité');
            return $this->redirectToRoute('admin_validation_pending');
        }

        $comment = $request->request->get('comment');
        $oldStatus = $user->getAccountStatus();

        // Mettre à jour le statut du compte
        $user->setAccountStatus('rejected');

        $this->entityManager->flush();

        // Logger l'action
        $this->auditLogService->log(
            AuditLogService::ACTION_USER_REJECTED,
            'User',
            $user->getId(),
            [
                'requested_role' => $user->getRole(),
                'old_status' => $oldStatus,
                'new_status' => $user->getAccountStatus(),
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
            $user->getFullName()
        ));

        return $this->redirectToRoute('admin_validation_pending');
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

