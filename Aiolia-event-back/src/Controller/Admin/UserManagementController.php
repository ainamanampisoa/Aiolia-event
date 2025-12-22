<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\OrganizerProfile;
use App\Enum\Role as UserRoleEnum;
use App\Repository\UserRepository;
use App\Repository\AuditLogRepository;
use App\Repository\Organisateur\EventRepository;
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
                u.prenom LIKE :search OR 
                u.nom LIKE :search OR 
                u.email LIKE :search OR
                u.telephone LIKE :search
            ';
            $qb->andWhere($searchCondition)
               ->setParameter('search', $searchTerm);
            $countQb->andWhere($searchCondition)
                    ->setParameter('search', $searchTerm);
        }

        // Filtre par rôle
        if ($role && in_array($role, UserRoleEnum::all(), true)) {
            // Si un rôle est sélectionné, filtrer par ce rôle
            $qb->andWhere('u.role = :role')
               ->setParameter('role', $role);

            $countQb->andWhere('u.role = :role')
                    ->setParameter('role', $role);
        } else {
            // Par défaut (sans filtre de rôle), exclure les users
            // Afficher uniquement les admins et organisateurs
            $qb->andWhere('u.role IN (:allowedRoles)')
               ->setParameter('allowedRoles', [UserRoleEnum::ADMIN, UserRoleEnum::ORGANIZER]);

            $countQb->andWhere('u.role IN (:allowedRoles)')
                    ->setParameter('allowedRoles', [UserRoleEnum::ADMIN, UserRoleEnum::ORGANIZER]);
        }

        // Filtre par statut
        if ($status === 'paused') {
            // Utiliser SQL natif pour filtrer les organisateurs en pause
            $conn = $this->entityManager->getConnection();
            $sql = "
                SELECT DISTINCT po.id_utilisateur
                FROM aiolia.abonnements_organisateurs os
                INNER JOIN aiolia.profils_organisateurs po ON po.id = os.id_profil_organisateur
                WHERE os.annule_le IS NULL
                    AND os.statut = 'paused'
            ";
            $pausedUserIds = $conn->fetchFirstColumn($sql);
            
            if (empty($pausedUserIds)) {
                // Aucun utilisateur en pause, retourner une liste vide
                $qb->andWhere('1 = 0');
                $countQb->andWhere('1 = 0');
            } else {
                $qb->andWhere('u.role = :pausedRole')
                   ->setParameter('pausedRole', UserRoleEnum::ORGANIZER)
                   ->andWhere('u.id IN (:pausedUserIds)')
                   ->setParameter('pausedUserIds', $pausedUserIds);

                $countQb->andWhere('u.role = :pausedRole')
                        ->setParameter('pausedRole', UserRoleEnum::ORGANIZER)
                        ->andWhere('u.id IN (:pausedUserIds)')
                        ->setParameter('pausedUserIds', $pausedUserIds);
            }
        } elseif ($status && in_array($status, ['active', 'pending_validation', 'rejected'], true)) {
            if ($status === 'pending_validation') {
                // Pour les organisateurs non validés, filtrer par statut_verification du profil organisateur
                $qb->leftJoin('App\Entity\OrganizerProfile', 'op', 'WITH', 'op.utilisateur = u.id')
                   ->andWhere('u.role = :organizerRole')
                   ->andWhere('op.statutVerification = :verificationStatus')
                   ->setParameter('organizerRole', UserRoleEnum::ORGANIZER)
                   ->setParameter('verificationStatus', 'pending');
                
                $countQb->leftJoin('App\Entity\OrganizerProfile', 'op', 'WITH', 'op.utilisateur = u.id')
                        ->andWhere('u.role = :organizerRole')
                        ->andWhere('op.statutVerification = :verificationStatus')
                        ->setParameter('organizerRole', UserRoleEnum::ORGANIZER)
                        ->setParameter('verificationStatus', 'pending');
            } elseif ($status === 'rejected') {
                // Pour les organisateurs rejetés, filtrer par statut_verification = 'rejected'
                $qb->leftJoin('App\Entity\OrganizerProfile', 'op', 'WITH', 'op.utilisateur = u.id')
                   ->andWhere('u.role = :organizerRole')
                   ->andWhere('op.statutVerification = :verificationStatus')
                   ->setParameter('organizerRole', UserRoleEnum::ORGANIZER)
                   ->setParameter('verificationStatus', 'rejected');
                
                $countQb->leftJoin('App\Entity\OrganizerProfile', 'op', 'WITH', 'op.utilisateur = u.id')
                        ->andWhere('u.role = :organizerRole')
                        ->andWhere('op.statutVerification = :verificationStatus')
                        ->setParameter('organizerRole', UserRoleEnum::ORGANIZER)
                        ->setParameter('verificationStatus', 'rejected');
            } else {
                // Pour 'active', utiliser le statut du compte utilisateur
                $databaseStatus = User::accountStatusToDatabaseStatus($status);
                $qb->andWhere('u.statut = :statut')
                   ->setParameter('statut', $databaseStatus);
                $countQb->andWhere('u.statut = :statut')
                        ->setParameter('statut', $databaseStatus);
            }
        }

        // Tri par défaut : date de création décroissante (plus récents en premier)
        $validSortFields = ['created_at' => 'creeLe', 'email' => 'email', 'first_name' => 'prenom', 'last_name' => 'nom'];
        $sortField = $validSortFields[$sort] ?? 'creeLe';
        $qb->orderBy('u.' . $sortField, strtoupper($order) === 'ASC' ? 'ASC' : 'DESC');

        // Compter le total d'utilisateurs avec les filtres appliqués
        $totalUsersFiltered = (int) $countQb->getQuery()->getSingleScalarResult();
        $totalPages = (int) ceil($totalUsersFiltered / $itemsPerPage);

        // Appliquer la pagination : récupérer seulement 5 utilisateurs par page
        $qb->setFirstResult($offset)
           ->setMaxResults($itemsPerPage);

        // Récupérer les utilisateurs de la page courante depuis la base de données
        $users = $qb->getQuery()->getResult();

        // Récupérer le statut d'abonnement et de vérification des organisateurs affichés
        $organizerIds = array_values(array_filter(
            array_map(static function ($user) {
                return $user instanceof User && $user->getRole() === UserRoleEnum::ORGANIZER ? $user->getId() : null;
            }, $users),
            static fn($id) => $id !== null
        ));

        $subscriptionStatuses = $this->userRepository->getOrganizerSubscriptionStatuses($organizerIds);
        
        // Récupérer les statuts de vérification des organisateurs
        $verificationStatuses = [];
        if (!empty($organizerIds)) {
            $conn = $this->entityManager->getConnection();
            $sql = "
                SELECT id_utilisateur, statut_verification
                FROM aiolia.profils_organisateurs
                WHERE id_utilisateur IN (:userIds)
            ";
            $results = $conn->fetchAllAssociative($sql, ['userIds' => $organizerIds], ['userIds' => \Doctrine\DBAL\ArrayParameterType::INTEGER]);
            foreach ($results as $row) {
                $verificationStatuses[$row['id_utilisateur']] = $row['statut_verification'];
            }
        }

        // Calculer les statistiques depuis la base de données
        $statsQb = $this->userRepository->createQueryBuilder('u');
        
        // Total des utilisateurs
        $totalUsers = (int) $statsQb->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Utilisateurs en attente (organisateurs avec statut_verification = 'pending')
        $pendingUsers = (int) $this->userRepository->createQueryBuilder('u')
            ->select('COUNT(DISTINCT u.id)')
            ->leftJoin('App\Entity\OrganizerProfile', 'op', 'WITH', 'op.utilisateur = u.id')
            ->where('u.role = :organizerRole')
            ->andWhere('op.statutVerification = :verificationStatus')
            ->setParameter('organizerRole', UserRoleEnum::ORGANIZER)
            ->setParameter('verificationStatus', 'pending')
            ->getQuery()
            ->getSingleScalarResult();

        // Organisateurs actifs uniquement (exclure ceux en attente de validation)
        $activeOrganizers = (int) $this->userRepository->createQueryBuilder('u')
            ->select('COUNT(DISTINCT u.id)')
            ->leftJoin('App\Entity\OrganizerProfile', 'op', 'WITH', 'op.utilisateur = u.id')
            ->where('u.role = :role')
            ->andWhere('u.statut = :statut')
            ->andWhere('(op.statutVerification IS NULL OR op.statutVerification != :pendingStatus)')
            ->setParameter('role', UserRoleEnum::ORGANIZER)
            ->setParameter('statut', User::STATUS_ACTIVE)
            ->setParameter('pendingStatus', 'pending')
            ->getQuery()
            ->getSingleScalarResult();

        // Total organisateurs (tous les statuts)
        $totalOrganizers = (int) $this->userRepository->createQueryBuilder('u')
            ->select('COUNT(DISTINCT u.id)')
            ->where('u.role = :role')
            ->setParameter('role', UserRoleEnum::ORGANIZER)
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('@Admin/users/list.html.twig', [
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
                'activeOrganizers' => $activeOrganizers,
                'totalOrganizers' => $totalOrganizers,
            ],
            'subscriptionStatuses' => $subscriptionStatuses,
            'verificationStatuses' => $verificationStatuses,
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
        $subscriptionInfo = null;
        if ($user->getRole() === 'organizer') {
            $conn = $this->entityManager->getConnection();
            $sql = "
                SELECT 
                    os.id,
                    os.statut,
                    os.mois_prepayes_restants,
                    os.mis_en_pause_le,
                    os.repris_le,
                    sp.niveau,
                    sp.nom,
                    sp.code,
                    sp.periode_facturation
                FROM aiolia.abonnements_organisateurs os
                INNER JOIN aiolia.profils_organisateurs po ON po.id = os.id_profil_organisateur
                INNER JOIN aiolia.plans_abonnements sp ON sp.id = os.id_plan
                WHERE po.id_utilisateur = :userId
                    AND os.annule_le IS NULL
                ORDER BY os.cree_le DESC
                LIMIT 1
            ";
            $result = $conn->fetchAssociative($sql, ['userId' => $user->getId()]);
            if ($result) {
                $subscriptionInfo = $result;
            }
        }

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

        $conn = $this->entityManager->getConnection();
        
        // Récupérer l'abonnement actif de l'organisateur
        $subscriptionInfo = null;
        $nextPaymentDate = null;
        $prepaidMonths = 0;
        $isPaused = false;
        $pauseMonth = null;
        
        if ($user->getRole() === 'organizer') {
            $sql = "
                SELECT 
                    os.id,
                    os.statut,
                    os.mois_prepayes_restants,
                    os.mis_en_pause_le,
                    os.repris_le,
                    sp.niveau,
                    sp.nom,
                    sp.code,
                    sp.periode_facturation
                FROM aiolia.abonnements_organisateurs os
                INNER JOIN aiolia.profils_organisateurs po ON po.id = os.id_profil_organisateur
                INNER JOIN aiolia.plans_abonnements sp ON sp.id = os.id_plan
                WHERE po.id_utilisateur = :userId
                    AND os.annule_le IS NULL
                ORDER BY os.cree_le DESC
                LIMIT 1
            ";
            $result = $conn->fetchAssociative($sql, ['userId' => $user->getId()]);
            if ($result) {
                $subscriptionInfo = $result;
                $prepaidMonths = (int) ($result['mois_prepayes_restants'] ?? 0);
                
                // Vérifier si en pause
                $isPaused = $result['statut'] === 'paused' || 
                           ($result['mis_en_pause_le'] !== null && 
                            ($result['repris_le'] === null || new \DateTime($result['repris_le']) > new \DateTime()));
                
                if ($isPaused && $result['mis_en_pause_le']) {
                    $pauseDate = new \DateTime($result['mis_en_pause_le']);
                    $monthNames = [
                        1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
                        5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
                        9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
                    ];
                    $pauseMonth = $monthNames[(int)$pauseDate->format('n')] ?? $pauseDate->format('F');
                }
                
                // Calculer le prochain paiement (première facture non payée à venir)
                $sqlNextPayment = "
                    SELECT 
                        (mois_facturation + INTERVAL '1 month')::date as next_payment
                    FROM aiolia.factures_abonnements
                    WHERE id_abonnement = :subscriptionId
                        AND statut IN ('issued', 'draft', 'pending')
                        AND mois_facturation >= DATE_TRUNC('month', CURRENT_DATE)
                    ORDER BY mois_facturation ASC
                    LIMIT 1
                ";
                $nextPaymentResult = $conn->fetchOne($sqlNextPayment, ['subscriptionId' => $result['id']]);
                if ($nextPaymentResult) {
                    $nextPaymentDate = new \DateTime($nextPaymentResult);
                } else {
                    // Si aucune facture en attente, calculer à partir du mois suivant
                    $nextPaymentDate = new \DateTime('first day of next month');
                }
            }
        }
        
        // Récupérer l'historique des paiements (factures payées)
        $sqlPayments = "
            SELECT 
                fa.id,
                fa.numero_facture,
                fa.montant_total,
                fa.devise,
                fa.mois_facturation,
                fa.statut,
                fa.payee_le,
                fa.emise_le,
                fa.echeance_le,
                COALESCE(sp.niveau, 'basic') as niveau,
                COALESCE(sp.nom, 'Plan inconnu') as plan_nom
            FROM aiolia.factures_abonnements fa
            INNER JOIN aiolia.abonnements_organisateurs os ON os.id = fa.id_abonnement
            INNER JOIN aiolia.profils_organisateurs po ON po.id = os.id_profil_organisateur
            LEFT JOIN aiolia.plans_abonnements sp ON sp.id = os.id_plan
            WHERE po.id_utilisateur = :userId
            ORDER BY fa.mois_facturation DESC, fa.cree_le DESC
            LIMIT 50
        ";
        $payments = $conn->fetchAllAssociative($sqlPayments, ['userId' => $user->getId()]);

        return $this->render('@Admin/users/payments.html.twig', [
            'user' => $user,
            'payments' => $payments,
            'subscriptionInfo' => $subscriptionInfo,
            'nextPaymentDate' => $nextPaymentDate,
            'prepaidMonths' => $prepaidMonths,
            'isPaused' => $isPaused,
            'pauseMonth' => $pauseMonth,
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

        // Paramètres de filtrage et recherche
        $search = $request->query->get('search', '');
        $status = $request->query->get('status', '');
        $sort = $request->query->get('sort', 'date_desc'); // date_desc, date_asc, title_asc, title_desc

        // Pagination : 10 événements par page
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        // Récupérer tous les événements de l'organisateur
        $allEvents = $this->eventRepository->findByOrganizer($user);

        // Appliquer les filtres
        $filteredEvents = $allEvents;
        
        if ($search) {
            $searchLower = mb_strtolower($search);
            $filteredEvents = array_filter($filteredEvents, function($event) use ($searchLower) {
                return str_contains(mb_strtolower($event->getTitre()), $searchLower);
            });
        }

        if ($status && in_array($status, ['published', 'draft', 'cancelled', 'archived'], true)) {
            $filteredEvents = array_filter($filteredEvents, fn($e) => $e->getStatut() === $status);
        }

        // Réindexer le tableau après filtrage
        $filteredEvents = array_values($filteredEvents);

        // Appliquer le tri
        usort($filteredEvents, function($a, $b) use ($sort) {
            switch ($sort) {
                case 'date_asc':
                    $dateA = $a->getCommenceLe();
                    $dateB = $b->getCommenceLe();
                    if (!$dateA && !$dateB) return 0;
                    if (!$dateA) return 1;
                    if (!$dateB) return -1;
                    return $dateA <=> $dateB;
                case 'date_desc':
                    $dateA = $a->getCommenceLe();
                    $dateB = $b->getCommenceLe();
                    if (!$dateA && !$dateB) return 0;
                    if (!$dateA) return 1;
                    if (!$dateB) return -1;
                    return $dateB <=> $dateA;
                case 'title_asc':
                    return strcasecmp($a->getTitre(), $b->getTitre());
                case 'title_desc':
                    return strcasecmp($b->getTitre(), $a->getTitre());
                default:
                    return 0;
            }
        });

        $totalEvents = count($filteredEvents);
        $totalPages = ceil($totalEvents / $perPage);

        // Paginer les événements
        $events = array_slice($filteredEvents, $offset, $perPage);

        // Statistiques
        $eventsCount = count($allEvents);
        $publishedEventsCount = count(array_filter($allEvents, fn($e) => $e->getStatut() === 'published'));
        $draftEventsCount = count(array_filter($allEvents, fn($e) => $e->getStatut() === 'draft'));
        $cancelledEventsCount = count(array_filter($allEvents, fn($e) => $e->getStatut() === 'cancelled'));
        $archivedEventsCount = count(array_filter($allEvents, fn($e) => $e->getStatut() === 'archived'));

        return $this->render('@Admin/users/events.html.twig', [
            'user' => $user,
            'events' => $events,
            'eventsCount' => $eventsCount,
            'publishedEventsCount' => $publishedEventsCount,
            'draftEventsCount' => $draftEventsCount,
            'cancelledEventsCount' => $cancelledEventsCount,
            'archivedEventsCount' => $archivedEventsCount,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalEventsFiltered' => $totalEvents,
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

