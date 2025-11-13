<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Enum\Role as UserRoleEnum;
use App\Repository\UserRepository;
use App\Repository\AuditLogRepository;
use App\Repository\EventRepository;
use App\Service\AuditLogService;
use App\Service\UserNotificationService;
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
        private UserNotificationService $notificationService
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
        
        // Pagination : 5 utilisateurs par page
        $itemsPerPage = 5;
        $page = max(1, (int) $request->query->get('page', 1));
        $offset = ($page - 1) * $itemsPerPage;

        // Construction de la requête pour compter le total (avec filtres)
        $countQb = $this->userRepository->createQueryBuilder('u')
            ->select('COUNT(DISTINCT u.id)');

        // Construction de la requête pour récupérer les utilisateurs (avec filtres)
        $qb = $this->userRepository->createQueryBuilder('u');

        // Recherche par nom (prénom + nom), email ou téléphone
        if ($search) {
            $searchTerm = '%' . trim($search) . '%';
            $searchCondition = '
                u.firstName LIKE :search OR 
                u.lastName LIKE :search OR 
                u.email LIKE :search OR 
                u.phone LIKE :search
            ';
            $qb->andWhere($searchCondition)
               ->setParameter('search', $searchTerm);
            $countQb->andWhere($searchCondition)
                    ->setParameter('search', $searchTerm);
        }

        // Filtre par rôle
        if ($role && in_array($role, UserRoleEnum::all(), true)) {
            $qb->andWhere('u.role = :role')
               ->setParameter('role', $role);

            $countQb->andWhere('u.role = :role')
                    ->setParameter('role', $role);
        }

        // Filtre par statut
        if ($status && in_array($status, ['active', 'pending_validation', 'rejected'], true)) {
            $databaseStatus = User::accountStatusToDatabaseStatus($status);
            $qb->andWhere('u.status = :status')
               ->setParameter('status', $databaseStatus);
            $countQb->andWhere('u.status = :status')
                    ->setParameter('status', $databaseStatus);
        }

        // Tri par défaut : date de création décroissante (plus récents en premier)
        $validSortFields = ['created_at' => 'createdAt', 'email' => 'email', 'first_name' => 'firstName', 'last_name' => 'lastName'];
        $sortField = $validSortFields[$sort] ?? 'createdAt';
        $qb->orderBy('u.' . $sortField, strtoupper($order) === 'ASC' ? 'ASC' : 'DESC');

        // Compter le total d'utilisateurs avec les filtres appliqués
        $totalUsersFiltered = (int) $countQb->getQuery()->getSingleScalarResult();
        $totalPages = (int) ceil($totalUsersFiltered / $itemsPerPage);

        // Appliquer la pagination : récupérer seulement 5 utilisateurs par page
        $qb->setFirstResult($offset)
           ->setMaxResults($itemsPerPage);

        // Récupérer les utilisateurs de la page courante depuis la base de données
        $users = $qb->getQuery()->getResult();

        // Calculer les statistiques depuis la base de données
        $statsQb = $this->userRepository->createQueryBuilder('u');
        
        // Total des utilisateurs
        $totalUsers = (int) $statsQb->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Utilisateurs en attente
        $pendingUsers = (int) $this->userRepository->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.status = :status')
            ->setParameter('status', User::STATUS_PENDING)
            ->getQuery()
            ->getSingleScalarResult();

        // Utilisateurs actifs
        $activeUsers = (int) $this->userRepository->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.status = :status')
            ->setParameter('status', User::STATUS_ACTIVE)
            ->getQuery()
            ->getSingleScalarResult();

        // Organisateurs
        $organizers = (int) $this->userRepository->createQueryBuilder('u')
            ->select('COUNT(DISTINCT u.id)')
            ->where('u.role = :role')
            ->setParameter('role', UserRoleEnum::ORGANIZER)
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('admin/users/list.html.twig', [
            'users' => $users,
            'search' => $search,
            'currentRole' => $role,
            'currentStatus' => $status,
            'currentSort' => $sort,
            'currentOrder' => $order,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalUsersFiltered' => $totalUsersFiltered,
            'itemsPerPage' => $itemsPerPage,
            'stats' => [
                'total' => $totalUsers,
                'pending' => $pendingUsers,
                'active' => $activeUsers,
                'organizers' => $organizers,
            ],
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

        // Récupérer les événements créés par l'organisateur (si organisateur)
        $events = [];
        if ($user->getRole() === 'organizer') {
            $events = $this->eventRepository->findByOrganizer($user);
        }

        // Compter les statistiques de l'utilisateur
        $eventsCount = count($events);
        $publishedEventsCount = count(array_filter($events, fn($e) => $e->getStatus() === 'published'));

        return $this->render('admin/users/show.html.twig', [
            'user' => $user,
            'auditLogs' => $auditLogs,
            'events' => $events,
            'eventsCount' => $eventsCount,
            'publishedEventsCount' => $publishedEventsCount,
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

        $oldStatus = $user->getAccountStatus();
        $newStatus = $user->getAccountStatus() === 'pending_validation' ? 'active' : 'pending_validation';
        $user->setAccountStatus($newStatus);
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
            $user->getFullName(),
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

        // TODO: Récupérer les paiements réels depuis la base de données
        // Pour l'instant, on retourne une liste vide
        $payments = [];

        return $this->render('admin/users/payments.html.twig', [
            'user' => $user,
            'payments' => $payments,
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

        return $this->render('admin/users/info.html.twig', [
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

        // Pagination : 5 événements par page
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = 5;
        $offset = ($page - 1) * $perPage;

        // Récupérer tous les événements de l'organisateur
        $allEvents = $this->eventRepository->findByOrganizer($user);
        $totalEvents = count($allEvents);
        $totalPages = ceil($totalEvents / $perPage);

        // Paginer les événements
        $events = array_slice($allEvents, $offset, $perPage);

        // Statistiques
        $eventsCount = $totalEvents;
        $publishedEventsCount = count(array_filter($allEvents, fn($e) => $e->getStatus() === 'published'));

        return $this->render('admin/users/events.html.twig', [
            'user' => $user,
            'events' => $events,
            'eventsCount' => $eventsCount,
            'publishedEventsCount' => $publishedEventsCount,
            'currentPage' => $page,
            'totalPages' => $totalPages,
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

