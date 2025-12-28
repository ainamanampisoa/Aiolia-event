<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Service\Admin\UserValidationService;
use App\Service\AuditLogService;
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
        private AuditLogService $auditLogService,
        private UserValidationService $userValidationService
    ) {
    }

    /**
     * Approuver une demande
     */
    #[Route('/{id}/approve', name: 'admin_validation_approve', methods: ['POST'])]
    public function approve(int $id, Request $request): Response
    {
        $targetRole = $request->request->get('target_role');
        $comment = $request->request->get('comment');

        $result = $this->userValidationService->approveUser($id, $targetRole, $comment);

        if (!$result['success']) {
            if (isset($result['exception'])) {
                $errorDetails = [
                    'message' => $result['exception']->getMessage(),
                    'details' => $result['exception']->getTraceAsString(),
                    'user' => $result['exception']->getMessage(),
                ];
                $this->addFlash('email_error_details', json_encode($errorDetails, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            }
            $this->addFlash('error', $result['error']);
            return $this->redirectToRoute('admin_users_list');
        }

        $user = $result['user'];

        // Log
        $this->auditLogService->log(
            AuditLogService::ACTION_USER_VALIDATED,
            'User',
            $user->getId(),
            [
                'old_role' => $result['oldRole'],
                'new_role' => $user->getRole(),
                'old_status' => $result['oldStatus'],
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
    public function reject(int $id, Request $request): Response
    {
        $comment = $request->request->get('comment');

        $result = $this->userValidationService->rejectUser($id, $comment);

        if (!$result['success']) {
            $this->addFlash('error', $result['error']);
            return $this->redirectToRoute('admin_users_list');
        }

        $user = $result['user'];

        // Logger l'action
        $this->auditLogService->log(
            AuditLogService::ACTION_USER_REJECTED,
            'User',
            $user->getId(),
            [
                'requested_role' => $user->getRole(),
                'old_status' => $result['oldStatus'],
                'new_status' => $user->getStatutCompte(),
                'reason' => $comment,
            ],
            $this->getUser()
        );

        $this->addFlash('success', sprintf(
            'Demande rejetée : %s',
            $user->getNomComplet()
        ));

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

