<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\AuditLogRepository;
use App\Service\AuditLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class UserManagementController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private AuditLogRepository $auditLogRepository,
        private AuditLogService $auditLogService
    ) {
    }

    /**
     * Liste des utilisateurs avec recherche multi-critères
     */
    #[Route('', name: 'admin_users_list')]
    public function list(Request $request): Response
    {
        $search = $request->query->get('search', '');
        $role = $request->query->get('role', '');
        $status = $request->query->get('status', '');
        $sort = $request->query->get('sort', 'created_at');
        $order = $request->query->get('order', 'DESC');

        // Construction de la requête
        $qb = $this->userRepository->createQueryBuilder('u');

        // Recherche par nom/email
        if ($search) {
            $qb->andWhere('u.firstName LIKE :search OR u.lastName LIKE :search OR u.email LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Filtre par rôle
        if ($role && in_array($role, ['user', 'organizer', 'co_organizer', 'admin'])) {
            $qb->andWhere('u.role = :role')
               ->setParameter('role', $role);
        }

        // Filtre par statut
        if ($status && in_array($status, ['active', 'pending_validation', 'rejected', 'suspended'])) {
            $qb->andWhere('u.accountStatus = :status')
               ->setParameter('status', $status);
        }

        // Tri
        $validSortFields = ['created_at' => 'createdAt', 'email' => 'email', 'first_name' => 'firstName', 'last_name' => 'lastName'];
        $sortField = $validSortFields[$sort] ?? 'createdAt';
        $qb->orderBy('u.' . $sortField, strtoupper($order) === 'ASC' ? 'ASC' : 'DESC');

        $users = $qb->getQuery()->getResult();

        return $this->render('admin/users/list.html.twig', [
            'users' => $users,
            'search' => $search,
            'currentRole' => $role,
            'currentStatus' => $status,
            'currentSort' => $sort,
            'currentOrder' => $order,
        ]);
    }

    /**
     * Autocomplete pour la recherche de noms
     */
    #[Route('/autocomplete', name: 'admin_users_autocomplete')]
    public function autocomplete(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');

        if (strlen($query) < 2) {
            return $this->json([]);
        }

        $users = $this->userRepository->createQueryBuilder('u')
            ->where('u.firstName LIKE :query OR u.lastName LIKE :query OR u.email LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $results = [];
        foreach ($users as $user) {
            $results[] = [
                'id' => $user->getId(),
                'text' => sprintf('%s (%s)', $user->getFullName(), $user->getEmail()),
                'email' => $user->getEmail(),
                'role' => $user->getRole(),
            ];
        }

        return $this->json($results);
    }

    /**
     * Détails d'un utilisateur
     */
    #[Route('/{id}', name: 'admin_users_show', requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        // Récupérer l'historique de l'utilisateur
        $auditLogs = $this->auditLogRepository->findByEntity('User', $id);

        return $this->render('admin/users/show.html.twig', [
            'user' => $user,
            'auditLogs' => $auditLogs,
        ]);
    }

    /**
     * Modifier le rôle d'un utilisateur
     */
    #[Route('/{id}/change-role', name: 'admin_users_change_role', methods: ['POST'])]
    public function changeRole(int $id, Request $request): Response
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            $this->addFlash('error', 'Utilisateur non trouvé');
            return $this->redirectToRoute('admin_users_list');
        }

        $newRole = $request->request->get('role');
        if (!in_array($newRole, ['user', 'organizer', 'co_organizer', 'admin'])) {
            $this->addFlash('error', 'Rôle invalide');
            return $this->redirectToRoute('admin_users_show', ['id' => $id]);
        }

        $oldRole = $user->getRole();
        $user->setRole($newRole);
        $this->entityManager->flush();

        // Logger l'action
        $this->auditLogService->log(
            AuditLogService::ACTION_ROLE_CHANGED,
            'User',
            $user->getId(),
            [
                'old_role' => $oldRole,
                'new_role' => $newRole,
            ],
            $this->getUser()
        );

        $this->addFlash('success', sprintf(
            'Rôle de %s modifié : %s → %s',
            $user->getFullName(),
            $this->getRoleLabel($oldRole),
            $this->getRoleLabel($newRole)
        ));

        return $this->redirectToRoute('admin_users_show', ['id' => $id]);
    }

    /**
     * Suspendre/Activer un utilisateur
     */
    #[Route('/{id}/toggle-status', name: 'admin_users_toggle_status', methods: ['POST'])]
    public function toggleStatus(int $id): Response
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            $this->addFlash('error', 'Utilisateur non trouvé');
            return $this->redirectToRoute('admin_users_list');
        }

        $newStatus = $user->getAccountStatus() === 'suspended' ? 'active' : 'suspended';
        $user->setAccountStatus($newStatus);
        $this->entityManager->flush();

        // Logger l'action
        $this->auditLogService->log(
            AuditLogService::ACTION_USER_UPDATED,
            'User',
            $user->getId(),
            [
                'action' => $newStatus === 'suspended' ? 'suspended' : 'activated',
            ],
            $this->getUser()
        );

        $this->addFlash('success', sprintf(
            'Compte de %s %s',
            $user->getFullName(),
            $newStatus === 'suspended' ? 'suspendu' : 'activé'
        ));

        return $this->redirectToRoute('admin_users_show', ['id' => $id]);
    }

    /**
     * Supprimer un utilisateur
     */
    #[Route('/{id}/delete', name: 'admin_users_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            $this->addFlash('error', 'Utilisateur non trouvé');
            return $this->redirectToRoute('admin_users_list');
        }

        // Ne pas permettre la suppression de son propre compte
        if ($user->getId() === $this->getUser()->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte');
            return $this->redirectToRoute('admin_users_show', ['id' => $id]);
        }

        $userName = $user->getFullName();
        $userId = $user->getId();

        // Logger l'action avant la suppression
        $this->auditLogService->log(
            AuditLogService::ACTION_USER_DELETED,
            'User',
            $userId,
            [
                'email' => $user->getEmail(),
                'name' => $userName,
                'role' => $user->getRole(),
            ],
            $this->getUser()
        );

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('Utilisateur %s supprimé', $userName));

        return $this->redirectToRoute('admin_users_list');
    }

    /**
     * Historique global des actions
     */
    #[Route('/audit/history', name: 'admin_audit_history')]
    public function auditHistory(Request $request): Response
    {
        $action = $request->query->get('action');
        $userId = $request->query->get('user_id');
        $startDate = $request->query->get('start_date') 
            ? new \DateTime($request->query->get('start_date')) 
            : null;
        $endDate = $request->query->get('end_date') 
            ? new \DateTime($request->query->get('end_date')) 
            : null;

        $auditLogs = $this->auditLogRepository->findWithFilters(
            $action,
            $userId,
            $startDate,
            $endDate
        );

        $actionStats = $this->auditLogRepository->getActionStatistics();

        return $this->render('admin/users/audit_history.html.twig', [
            'auditLogs' => $auditLogs,
            'actionStats' => $actionStats,
            'currentAction' => $action,
            'currentUserId' => $userId,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
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

