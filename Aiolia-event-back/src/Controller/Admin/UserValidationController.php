<?php

namespace App\Controller\Admin;

use App\Entity\UserValidationRequest;
use App\Repository\UserValidationRequestRepository;
use App\Service\AuditLogService;
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
        private AuditLogService $auditLogService
    ) {
    }

    /**
     * Liste des demandes de validation en attente
     */
    #[Route('/pending', name: 'admin_validation_pending')]
    public function pending(): Response
    {
        $pendingRequests = $this->validationRequestRepository->findPendingRequests();

        return $this->render('admin/validation/pending.html.twig', [
            'requests' => $pendingRequests,
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
        $oldRole = $user->getRole();
        $user->setRole($requestedRole);
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
            'co_organizer' => 'Co-organisateur',
            'admin' => 'Administrateur',
            default => 'Utilisateur',
        };
    }
}

