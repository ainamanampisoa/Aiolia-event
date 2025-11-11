<?php

namespace App\Controller\Admin;

use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use App\Repository\UserValidationRequestRepository;
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
        private UserValidationRequestRepository $validationRequestRepository,
        private UserRepository $userRepository,
        private RoleRepository $roleRepository,
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
        $pendingRequests = $this->validationRequestRepository->findPendingRequests();

        $pendingAccounts = array_values(array_filter(
            $this->userRepository->findAccountsPendingValidation(),
            static fn($user) => in_array($user->getRole(), ['organizer', 'user'], true)
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

        $pendingRequestUserIds = array_map(static fn($request) => $request->getUser()->getId(), $pendingRequests);

        $pendingAccountsWithoutRequest = array_values(array_filter(
            $pendingAccounts,
            static fn($user) => !in_array($user->getId(), $pendingRequestUserIds, true)
        ));

        $requestsByUserId = [];
        foreach ($pendingRequests as $request) {
            $requestsByUserId[$request->getUser()->getId()] = $request;
        }

        return $this->render('admin/validation/pending.html.twig', [
            'requests' => $pendingRequests,
            'pendingAccounts' => $pendingAccounts,
            'paginatedPendingAccounts' => $paginatedPendingAccounts,
            'pendingAccountsWithoutRequest' => $pendingAccountsWithoutRequest,
            'pendingStats' => [
                'total' => count($pendingAccounts),
                'organizer' => count($pendingOrganizers),
                'user' => count($pendingUsers),
            ],
            'requestsByUserId' => $requestsByUserId,
            'pendingRequestUserIds' => $pendingRequestUserIds,
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
        $validationRequest = $this->validationRequestRepository->find($id);

        if (!$validationRequest) {
            $this->addFlash('error', 'Demande non trouvée');
            return $this->redirectToRoute('admin_validation_pending');
        }

        if ($validationRequest->getStatus() !== 'pending') {
            $this->addFlash('error', 'Cette demande a déjà été traitée');
            return $this->redirectToRoute('admin_validation_pending');
        }

        $user = $validationRequest->getUser();
        $requestedRole = $validationRequest->getRequestedRole();

        // Mettre à jour le rôle de l'utilisateur
        $roleEntity = $this->roleRepository->findOneByCode($requestedRole);

        if (!$roleEntity) {
            $this->addFlash('error', sprintf('Rôle demandé invalide : %s', $requestedRole));
            return $this->redirectToRoute('admin_validation_pending');
        }

        $oldRole = $user->getRole();
        $user->setRole($roleEntity);
        $user->setAccountStatus('active');

        // Mettre à jour la demande
        $validationRequest->setStatus('approved');
        $validationRequest->setValidatedBy($this->getUser());
        $validationRequest->setValidatedAt(new \DateTime());
        $validationRequest->setAdminComment($request->request->get('comment'));

        $this->entityManager->flush();

        // Logger l'action
        $this->auditLogService->log(
            AuditLogService::ACTION_USER_VALIDATED,
            'User',
            $user->getId(),
            [
                'old_role' => $oldRole,
                'new_role' => $requestedRole,
                'validation_request_id' => $validationRequest->getId(),
            ],
            $this->getUser()
        );

        // Envoyer une notification par email
        $this->notificationService->sendValidationApprovedNotification(
            $user,
            $requestedRole,
            $request->request->get('comment')
        );

        $this->addFlash('success', sprintf(
            'Demande approuvée : %s est maintenant %s',
            $user->getFullName(),
            $this->getRoleLabel($requestedRole)
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
        $validationRequest = $this->validationRequestRepository->find($id);

        if (!$validationRequest) {
            $this->addFlash('error', 'Demande non trouvée');
            return $this->redirectToRoute('admin_validation_pending');
        }

        if ($validationRequest->getStatus() !== 'pending') {
            $this->addFlash('error', 'Cette demande a déjà été traitée');
            return $this->redirectToRoute('admin_validation_pending');
        }

        $user = $validationRequest->getUser();

        // Mettre à jour le statut du compte
        $user->setAccountStatus('rejected');

        // Mettre à jour la demande
        $validationRequest->setStatus('rejected');
        $validationRequest->setValidatedBy($this->getUser());
        $validationRequest->setValidatedAt(new \DateTime());
        $validationRequest->setAdminComment($request->request->get('comment'));

        $this->entityManager->flush();

        // Logger l'action
        $this->auditLogService->log(
            AuditLogService::ACTION_USER_REJECTED,
            'User',
            $user->getId(),
            [
                'requested_role' => $validationRequest->getRequestedRole(),
                'validation_request_id' => $validationRequest->getId(),
                'reason' => $request->request->get('comment'),
            ],
            $this->getUser()
        );

        // Envoyer une notification par email
        $this->notificationService->sendValidationRejectedNotification(
            $user,
            $validationRequest->getRequestedRole(),
            $request->request->get('comment')
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

