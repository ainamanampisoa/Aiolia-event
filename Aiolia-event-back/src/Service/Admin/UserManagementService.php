<?php

namespace App\Service\Admin;

use App\Entity\User;
use App\Entity\OrganizerProfile;
use App\Enum\Role as UserRoleEnum;
use App\Repository\UserRepository;
use App\Repository\Organisateur\EventRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserManagementService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private EventRepository $eventRepository
    ) {
    }

    /**
     * Récupère les utilisateurs avec filtres, tri et pagination
     */
    public function getUsersWithFilters(
        string $search = '',
        string $role = '',
        string $status = '',
        string $sort = 'created_at',
        string $order = 'DESC',
        int $page = 1,
        int $itemsPerPage = 5
    ): array {
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
            $qb->andWhere('u.role = :role')
               ->setParameter('role', $role);
            $countQb->andWhere('u.role = :role')
                    ->setParameter('role', $role);
        } else {
            // Par défaut (sans filtre de rôle), exclure les users
            $qb->andWhere('u.role IN (:allowedRoles)')
               ->setParameter('allowedRoles', [UserRoleEnum::ADMIN, UserRoleEnum::ORGANIZER]);
            $countQb->andWhere('u.role IN (:allowedRoles)')
                    ->setParameter('allowedRoles', [UserRoleEnum::ADMIN, UserRoleEnum::ORGANIZER]);
        }

        // Filtre par statut
        $this->applyStatusFilter($qb, $countQb, $status);

        // Tri par défaut : date de création décroissante
        $validSortFields = ['created_at' => 'creeLe', 'email' => 'email', 'first_name' => 'prenom', 'last_name' => 'nom'];
        $sortField = $validSortFields[$sort] ?? 'creeLe';
        $qb->orderBy('u.' . $sortField, strtoupper($order) === 'ASC' ? 'ASC' : 'DESC');

        // Compter le total d'utilisateurs avec les filtres appliqués
        $totalUsersFiltered = (int) $countQb->getQuery()->getSingleScalarResult();
        $totalPages = (int) ceil($totalUsersFiltered / $itemsPerPage);

        // Appliquer la pagination
        $qb->setFirstResult($offset)
           ->setMaxResults($itemsPerPage);

        // Récupérer les utilisateurs de la page courante
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
        $verificationStatuses = $this->getVerificationStatuses($organizerIds);

        return [
            'users' => $users,
            'totalUsersFiltered' => $totalUsersFiltered,
            'totalPages' => $totalPages,
            'subscriptionStatuses' => $subscriptionStatuses,
            'verificationStatuses' => $verificationStatuses,
        ];
    }

    /**
     * Calcule les statistiques des utilisateurs
     */
    public function calculateUserStats(): array
    {
        // Total des utilisateurs
        $totalUsers = (int) $this->userRepository->createQueryBuilder('u')
            ->select('COUNT(u.id)')
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

        return [
            'total' => $totalUsers,
            'pending' => $pendingUsers,
            'activeOrganizers' => $activeOrganizers,
            'totalOrganizers' => $totalOrganizers,
        ];
    }

    /**
     * Récupère les informations d'abonnement d'un organisateur
     */
    public function getSubscriptionInfo(User $user): ?array
    {
        if ($user->getRole() !== 'organizer') {
            return null;
        }

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
        
        return $result ?: null;
    }

    /**
     * Récupère les informations de paiement d'un organisateur
     */
    public function getPaymentInfo(User $user): array
    {
        $subscriptionInfo = null;
        $nextPaymentDate = null;
        $prepaidMonths = 0;
        $isPaused = false;
        $pauseMonth = null;
        
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
                $prepaidMonths = (int) ($result['mois_prepayes_restants'] ?? 0);
                
                $isPaused = $result['statut'] === 'paused' || 
                           ($result['mis_en_pause_le'] !== null && 
                            ($result['repris_le'] === null || new \DateTime($result['repris_le']) > new \DateTime()));
                
                if (!$isPaused) {
                    $sqlOverdueInvoice = "
                        SELECT COUNT(*) as count
                        FROM aiolia.factures_abonnements
                        WHERE id_abonnement = :subscriptionId
                            AND statut = 'overdue'
                            AND payee_le IS NULL
                        LIMIT 1
                    ";
                    $overdueCount = (int) $conn->fetchOne($sqlOverdueInvoice, ['subscriptionId' => $result['id']]);
                    if ($overdueCount > 0) {
                        $isPaused = true;
                        if ($result['mis_en_pause_le'] === null) {
                            $sqlOverdueDate = "
                                SELECT mois_facturation
                                FROM aiolia.factures_abonnements
                                WHERE id_abonnement = :subscriptionId
                                    AND statut = 'overdue'
                                    AND payee_le IS NULL
                                ORDER BY mois_facturation ASC
                                LIMIT 1
                            ";
                            $overdueDateResult = $conn->fetchOne($sqlOverdueDate, ['subscriptionId' => $result['id']]);
                            if ($overdueDateResult) {
                                $result['mis_en_pause_le'] = $overdueDateResult;
                            }
                        }
                    }
                }
                
                if ($isPaused && $result['mis_en_pause_le']) {
                    $pauseDate = new \DateTime($result['mis_en_pause_le']);
                    $monthNames = [
                        1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
                        5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
                        9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
                    ];
                    $pauseMonth = $monthNames[(int)$pauseDate->format('n')] ?? $pauseDate->format('F');
                }
                
                $nextPaymentDate = $this->calculateNextPaymentDate($conn, $result['id']);
            }
        }
        
        // Récupérer l'historique des paiements
        $payments = $this->getPaymentHistory($user);
        
        return [
            'subscriptionInfo' => $subscriptionInfo,
            'nextPaymentDate' => $nextPaymentDate,
            'prepaidMonths' => $prepaidMonths,
            'isPaused' => $isPaused,
            'pauseMonth' => $pauseMonth,
            'payments' => $payments,
        ];
    }

    /**
     * Récupère les événements d'un organisateur avec filtres et pagination
     */
    public function getOrganizerEvents(
        User $user,
        string $search = '',
        string $status = '',
        string $sort = 'date_desc',
        int $page = 1,
        int $perPage = 10
    ): array {
        $allEvents = $this->eventRepository->findByOrganizer($user);
        
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
        $offset = ($page - 1) * $perPage;
        $events = array_slice($filteredEvents, $offset, $perPage);

        // Statistiques
        $eventsCount = count($allEvents);
        $publishedEventsCount = count(array_filter($allEvents, fn($e) => $e->getStatut() === 'published'));
        $draftEventsCount = count(array_filter($allEvents, fn($e) => $e->getStatut() === 'draft'));
        $cancelledEventsCount = count(array_filter($allEvents, fn($e) => $e->getStatut() === 'cancelled'));
        $archivedEventsCount = count(array_filter($allEvents, fn($e) => $e->getStatut() === 'archived'));

        return [
            'events' => $events,
            'eventsCount' => $eventsCount,
            'publishedEventsCount' => $publishedEventsCount,
            'draftEventsCount' => $draftEventsCount,
            'cancelledEventsCount' => $cancelledEventsCount,
            'archivedEventsCount' => $archivedEventsCount,
            'totalEventsFiltered' => $totalEvents,
            'totalPages' => $totalPages,
        ];
    }

    /**
     * Applique le filtre de statut
     */
    private function applyStatusFilter($qb, $countQb, ?string $status): void
    {
        if ($status === 'paused') {
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
                $databaseStatus = User::accountStatusToDatabaseStatus($status);
                $qb->andWhere('u.statut = :statut')
                   ->setParameter('statut', $databaseStatus);
                $countQb->andWhere('u.statut = :statut')
                        ->setParameter('statut', $databaseStatus);
            }
        }
    }

    /**
     * Récupère les statuts de vérification des organisateurs
     */
    private function getVerificationStatuses(array $organizerIds): array
    {
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
        return $verificationStatuses;
    }

    /**
     * Calcule la date du prochain paiement
     */
    private function calculateNextPaymentDate($connection, int $subscriptionId): ?\DateTime
    {
        $sqlNextPayment = "
            SELECT mois_facturation
            FROM aiolia.factures_abonnements
            WHERE id_abonnement = :subscriptionId
                AND statut IN ('issued', 'draft', 'pending')
                AND mois_facturation >= DATE_TRUNC('month', CURRENT_DATE)
            ORDER BY mois_facturation ASC
            LIMIT 1
        ";
        $nextPaymentResult = $connection->fetchOne($sqlNextPayment, ['subscriptionId' => $subscriptionId]);
        
        if ($nextPaymentResult) {
            $nextPaymentDate = new \DateTime($nextPaymentResult);
            $nextPaymentDate->modify('first day of this month');
            return $nextPaymentDate;
        }
        
        // Si aucune facture en attente, calculer à partir de la dernière facture payée
        $sqlLastPaid = "
            SELECT mois_facturation
            FROM aiolia.factures_abonnements
            WHERE id_abonnement = :subscriptionId
                AND statut = 'paid'
            ORDER BY mois_facturation DESC
            LIMIT 1
        ";
        $lastPaidResult = $connection->fetchOne($sqlLastPaid, ['subscriptionId' => $subscriptionId]);
        
        if ($lastPaidResult) {
            $lastPaidDate = new \DateTime($lastPaidResult);
            $nextPaymentDate = clone $lastPaidDate;
            $nextPaymentDate->modify('+1 month');
            $nextPaymentDate->modify('first day of this month');
            return $nextPaymentDate;
        }
        
        // Si aucune facture payée, utiliser le mois suivant
        return new \DateTime('first day of next month');
    }

    /**
     * Récupère l'historique des paiements
     */
    private function getPaymentHistory(User $user): array
    {
        $conn = $this->entityManager->getConnection();
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
            ORDER BY 
                COALESCE(fa.payee_le, fa.emise_le, fa.mois_facturation) DESC,
                fa.mois_facturation DESC,
                fa.cree_le DESC
            LIMIT 50
        ";
        return $conn->fetchAllAssociative($sqlPayments, ['userId' => $user->getId()]);
    }
}

