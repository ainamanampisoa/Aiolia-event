<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\OrganizerProfile;
use App\Enum\Role as UserRoleEnum;
use App\Repository\UserRepository;
use App\Repository\AuditLogRepository;
use App\Repository\Organisateur\EventRepository;
use App\Service\Admin\UserManagementService;
use App\Service\AuditLogService;
use App\Service\Organisateur\UserNotificationService;
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
        private EventRepository $eventRepository,
        private AuditLogService $auditLogService,
        private UserNotificationService $notificationService,
        private UserManagementService $userManagementService
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
        $page = max(1, (int) $request->query->get('page', 1));
        $itemsPerPage = 5;

        $result = $this->userManagementService->getUsersWithFilters(
            $search,
            $role,
            $status,
            $sort,
            $order,
            $page,
            $itemsPerPage
        );

        $stats = $this->userManagementService->calculateUserStats();

        return $this->render('@Admin/users/list.html.twig', [
            'users' => $result['users'],
            'search' => $search,
            'currentRole' => $role,
            'currentStatus' => $status,
            'currentSort' => $sort,
            'currentOrder' => $order,
            'currentPage' => $page,
            'totalPages' => $result['totalPages'],
            'totalUsersFiltered' => $result['totalUsersFiltered'],
            'itemsPerPage' => $itemsPerPage,
            'stats' => $stats,
            'subscriptionStatuses' => $result['subscriptionStatuses'],
            'verificationStatuses' => $result['verificationStatuses'],
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
            ->where('u.prenom LIKE :query OR u.nom LIKE :query OR u.email LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $results = [];
        foreach ($users as $user) {
            $results[] = [
                'id' => $user->getId(),
                'text' => sprintf('%s (%s)', $user->getNomComplet(), $user->getEmail()),
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

        // Récupérer les événements créés par l'organisateur (si organisateur)
        $events = [];
        if ($user->getRole() === 'organizer') {
            $events = $this->eventRepository->findByOrganizer($user);
        }

        // Compter les statistiques de l'utilisateur
        $eventsCount = count($events);
        $publishedEventsCount = count(array_filter($events, fn($e) => $e->getStatut() === 'published'));

        // Récupérer les informations d'abonnement si c'est un organisateur
        $subscriptionInfo = $this->userManagementService->getSubscriptionInfo($user);

        return $this->render('@Admin/users/show.html.twig', [
            'user' => $user,
            'auditLogs' => $auditLogs,
            'events' => $events,
            'eventsCount' => $eventsCount,
            'publishedEventsCount' => $publishedEventsCount,
            'subscriptionInfo' => $subscriptionInfo,
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
        if (!in_array($newRole, UserRoleEnum::all(), true)) {
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

        // Envoyer une notification par email
        $this->notificationService->sendRoleChangeNotification($user, $oldRole, $newRole);

        $this->addFlash('success', sprintf(
            'Rôle de %s modifié : %s → %s',
            $user->getNomComplet(),
            $this->getRoleLabel($oldRole),
            $this->getRoleLabel($newRole)
        ));

        return $this->redirectToRoute('admin_users_show', ['id' => $id]);
    }

    /**
     * Suspendre/Activer un utilisateur
     */
    #[Route('/{id}/toggle-status', name: 'admin_users_toggle_status', methods: ['POST'])]
    public function toggleStatus(int $id, Request $request): Response
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            $this->addFlash('error', 'Utilisateur non trouvé');
            return $this->redirectToRoute('admin_users_list');
        }

        // Validation CSRF
        if (!$this->isCsrfTokenValid('toggle_status_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide');
            return $this->redirectToRoute('admin_users_list');
        }

        $oldStatus = $user->getStatutCompte();
        $newStatus = $user->getStatutCompte() === 'pending_validation' ? 'active' : 'pending_validation';
        $user->setStatutCompte($newStatus);
        $this->entityManager->flush();

        // Logger l'action
        $this->auditLogService->log(
            AuditLogService::ACTION_USER_UPDATED,
            'User',
            $user->getId(),
            [
                'action' => $newStatus === 'pending_validation' ? 'disabled' : 'activated',
            ],
            $this->getUser()
        );

        // Envoyer une notification par email
        $this->notificationService->sendStatusChangeNotification($user, $oldStatus, $newStatus);

        $this->addFlash('success', sprintf(
            'Compte de %s %s',
            $user->getNomComplet(),
            $newStatus === 'pending_validation' ? 'désactivé' : 'activé'
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
        $currentUser = $this->getUser();
        if ($currentUser instanceof User && $user->getId() === $currentUser->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte');
            return $this->redirectToRoute('admin_users_show', ['id' => $id]);
        }

        $userName = $user->getNomComplet();
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

        return $this->render('@Admin/users/audit_history.html.twig', [
            'auditLogs' => $auditLogs,
            'actionStats' => $actionStats,
            'currentAction' => $action,
            'currentUserId' => $userId,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    /**
     * Historique des paiements d'un utilisateur
     */
    #[Route('/{id}/payments', name: 'admin_users_payments', requirements: ['id' => '\d+'])]
    public function payments(int $id): Response
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        $paymentInfo = $this->userManagementService->getPaymentInfo($user);

        return $this->render('@Admin/users/payments.html.twig', [
            'user' => $user,
            'payments' => $paymentInfo['payments'],
            'subscriptionInfo' => $paymentInfo['subscriptionInfo'],
            'nextPaymentDate' => $paymentInfo['nextPaymentDate'],
            'prepaidMonths' => $paymentInfo['prepaidMonths'],
            'isPaused' => $paymentInfo['isPaused'],
            'pauseMonth' => $paymentInfo['pauseMonth'],
        ]);
    }

    /**
     * Informations supplémentaires d'un utilisateur
     */
    #[Route('/{id}/info', name: 'admin_users_info', requirements: ['id' => '\d+'])]
    public function info(int $id): Response
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        return $this->render('@Admin/users/info.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * Événements créés par un organisateur avec pagination
     */
    #[Route('/{id}/events', name: 'admin_users_events', requirements: ['id' => '\d+'])]
    public function events(int $id, Request $request): Response
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        if ($user->getRole() !== UserRoleEnum::ORGANIZER) {
            $this->addFlash('warning', 'Cet utilisateur n\'est pas un organisateur');
            return $this->redirectToRoute('admin_users_show', ['id' => $id]);
        }

        $search = $request->query->get('search', '');
        $status = $request->query->get('status', '');
        $sort = $request->query->get('sort', 'date_desc');
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = 10;

        $result = $this->userManagementService->getOrganizerEvents(
            $user,
            $search,
            $status,
            $sort,
            $page,
            $perPage
        );

        return $this->render('@Admin/users/events.html.twig', [
            'user' => $user,
            'events' => $result['events'],
            'eventsCount' => $result['eventsCount'],
            'publishedEventsCount' => $result['publishedEventsCount'],
            'draftEventsCount' => $result['draftEventsCount'],
            'cancelledEventsCount' => $result['cancelledEventsCount'],
            'archivedEventsCount' => $result['archivedEventsCount'],
            'currentPage' => $page,
            'totalPages' => $result['totalPages'],
            'totalEventsFiltered' => $result['totalEventsFiltered'],
            'search' => $search,
            'status' => $status,
            'sort' => $sort,
        ]);
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

