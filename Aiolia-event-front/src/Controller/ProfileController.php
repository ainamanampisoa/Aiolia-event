<?php

namespace App\Controller;

use App\Repository\ActivityRepository;
use App\Repository\EventRepository;
use App\Repository\OrderRepository;
use App\Repository\SearchHistoryRepository;
use App\Repository\UserRepository;
use App\Repository\UserStatsRepository;
use App\Repository\WalletTransactionRepository;
use App\Repository\WishlistRepository;
use App\Service\LoyaltyPointsService;
use App\Service\WalletService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProfileController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly OrderRepository $orderRepository,
        private readonly UserStatsRepository $userStatsRepository,
        private readonly WishlistRepository $wishlistRepository,
        private readonly SearchHistoryRepository $searchHistoryRepository,
        private readonly EventRepository $eventRepository,
        private readonly ActivityRepository $activityRepository,
        private readonly UserRepository $userRepository,
        private readonly WalletService $walletService,
        private readonly LoyaltyPointsService $loyaltyPointsService,
        private readonly WalletTransactionRepository $walletTransactionRepository
    ) {
    }

    #[Route('/profile', name: 'profile_index')]
    public function index(Request $request): Response
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $sessionUser = $session->get('user');
        $isAuthenticated = is_array($sessionUser) && isset($sessionUser['id']);

        if (!$isAuthenticated) {
            return $this->redirectToRoute('login');
        }

        $userId = (int) $sessionUser['id'];

        // Récupérer les informations utilisateur
        $userInfo = $this->userRepository->findUserInfo($userId);
        
        // Récupérer les statistiques (inclure le panier en session)
        $sessionCartItems = $session->get('cart_items', []);
        $stats = $this->fetchUserStats($userId, $sessionCartItems);
        
        // Récupérer les activités récentes (inclure le panier en session)
        $recentActivities = $this->activityRepository->findRecentActivities($userId, $sessionCartItems);

        return $this->render('profile/index.html.twig', [
            'user' => $userInfo,
            'stats' => $stats,
            'activities' => $recentActivities,
            'isAuthenticated' => $isAuthenticated,
        ]);
    }

    #[Route('/profile/history/invoice/{id}', name: 'profile_history_invoice', requirements: ['id' => '\d+'])]
    public function downloadInvoice(int $id, Request $request): Response
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $sessionUser = $session->get('user');
        $isAuthenticated = is_array($sessionUser) && isset($sessionUser['id']);

        if (!$isAuthenticated) {
            return $this->redirectToRoute('login');
        }

        $userId = (int) $sessionUser['id'];

        // Récupérer les détails de la commande
        $order = $this->orderRepository->findOrderByIdAndUserId($id, $userId);

        if (!$order) {
            $this->addFlash('error', 'Commande introuvable.');
            return $this->redirectToRoute('profile_history');
        }

        // Extraire le payment_method depuis le champ notes
        $paymentMethod = null;
        if (!empty($order['notes'])) {
            $notes = json_decode($order['notes'], true);
            if (is_array($notes) && isset($notes['payment_method'])) {
                $paymentMethod = $notes['payment_method'];
            }
        }

        $paymentMethodLabels = [
            'mvola' => 'M-Vola',
            'orange-money' => 'Orange Money',
            'airtel-money' => 'Airtel Money',
        ];

        // Récupérer les informations utilisateur complètes
        $userInfo = $this->userRepository->findUserInfo($userId);

        // Générer le contenu HTML de la facture
        $html = $this->renderView('profile/invoice.html.twig', [
            'order' => [
                'id' => $order['id'],
                'code' => 'CMD-' . str_pad((string) $order['id'], 6, '0', STR_PAD_LEFT),
                'date' => new \DateTimeImmutable($order['created_at']),
                'total_amount' => (float) $order['total_amount'],
                'discount_amount' => (float) ($order['discount_amount'] ?? 0),
                'currency' => $order['currency'] ?? 'MGA',
                'promotion_code' => $order['promotion_code'],
                'event_titles' => $order['event_titles'],
                'total_tickets' => (int) ($order['total_tickets'] ?? 0),
                'payment_method' => $paymentMethod ? ($paymentMethodLabels[$paymentMethod] ?? ucfirst(str_replace('-', ' ', $paymentMethod))) : 'Non spécifié',
            ],
            'user' => $userInfo,
        ]);

        // Configuration de Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Générer le nom du fichier
        $orderCode = 'CMD-' . str_pad((string) $order['id'], 6, '0', STR_PAD_LEFT);
        $filename = 'Facture_' . $orderCode . '_' . date('Y-m-d') . '.pdf';

        // Retourner le PDF en téléchargement
        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    #[Route('/profile/history/export', name: 'profile_history_export')]
    public function exportHistory(Request $request): Response
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $sessionUser = $session->get('user');
        $isAuthenticated = is_array($sessionUser) && isset($sessionUser['id']);

        if (!$isAuthenticated) {
            return $this->redirectToRoute('login');
        }

        $userId = (int) $sessionUser['id'];
        
        // Récupérer les paramètres de recherche et de filtre (sans pagination pour exporter tout)
        $searchQuery = $request->query->get('search', '');
        $statusFilter = $request->query->get('status', 'all');
        $paymentMethodFilter = $request->query->get('payment_method', 'all');
        
        try {
            // Récupérer toutes les commandes avec les filtres (sans pagination)
            $orders = $this->fetchUserOrders($userId, $searchQuery, $statusFilter, $paymentMethodFilter);

            // Générer le CSV
            $csvLines = [];
            // En-tête
            $csvLines[] = "Numéro de commande,Statut,Événement,Date,Heure,Billets,Montant,Méthode de paiement";
            
            if (empty($orders)) {
                // Si aucune commande, ajouter une ligne pour indiquer qu'il n'y a pas de données
                $csvLines[] = "Aucune commande trouvée,,,,,,";
            } else {
                foreach ($orders as $order) {
                    $code = $order['code'] ?? '';
                    $status = $order['status'] ?? '';
                    $title = $order['title'] ?? '';
                    
                    // Formater la date et l'heure pour le CSV
                    $date = '';
                    $hour = '';
                    
                    if (isset($order['created_at'])) {
                        $dateObj = null;
                        
                        if ($order['created_at'] instanceof \DateTimeImmutable) {
                            $dateObj = $order['created_at'];
                        } elseif (is_string($order['created_at'])) {
                            // Si c'est une chaîne, essayer de la parser
                            try {
                                $dateObj = new \DateTimeImmutable($order['created_at']);
                            } catch (\Exception $e) {
                                // En cas d'erreur de parsing, on laisse date et hour vides
                            }
                        }
                        
                        // Si on a réussi à obtenir un objet DateTime, formater date et heure
                        if ($dateObj instanceof \DateTimeImmutable) {
                            $date = $dateObj->format('Y-m-d');
                            $hour = $dateObj->format('H:i');
                        }
                    }
                    
                    // Si l'heure n'a toujours pas été récupérée mais qu'on a un champ hour séparé
                    if (empty($hour) && isset($order['hour']) && !empty($order['hour'])) {
                        $hour = $order['hour'];
                    }
                    
                    $tickets = $order['tickets'] ?? 0;
                    // Utiliser le montant brut pour le CSV
                    $amountRaw = $order['amount_raw'] ?? 0;
                    $amount = number_format($amountRaw, 0, '.', '');
                    $method = $order['method'] ?? '';
                    
                    // Échapper les guillemets et les virgules
                    $csvLines[] = sprintf(
                        '"%s","%s","%s","%s","%s","%s","%s","%s"',
                        str_replace('"', '""', $code),
                        str_replace('"', '""', $status),
                        str_replace('"', '""', $title),
                        str_replace('"', '""', $date),
                        str_replace('"', '""', $hour),
                        str_replace('"', '""', (string)$tickets),
                        str_replace('"', '""', $amount),
                        str_replace('"', '""', $method)
                    );
                }
            }

            // Joindre toutes les lignes
            $csvContent = implode("\n", $csvLines);

            // Générer le nom du fichier
            $filename = 'historique_achats_' . date('Y-m-d_His') . '.csv';

            // Créer la réponse avec les bons en-têtes
            $response = new Response($csvContent);
            $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
            $response->headers->set('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
            $response->headers->set('Pragma', 'public');
            $response->headers->set('Expires', '0');

            return $response;
        } catch (\Exception $e) {
            // En cas d'erreur, retourner un message d'erreur
            return new Response('Erreur lors de l\'export: ' . $e->getMessage(), 500);
        }
    }

    #[Route('/profile/history', name: 'profile_history')]
    public function history(Request $request): Response
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $sessionUser = $session->get('user');
        $isAuthenticated = is_array($sessionUser) && isset($sessionUser['id']);

        if (!$isAuthenticated) {
            return $this->redirectToRoute('login');
        }

        $userId = (int) $sessionUser['id'];
        
        // Récupérer les paramètres de recherche et de filtre
        $searchQuery = $request->query->get('search', '');
        $statusFilter = $request->query->get('status', 'all');
        $paymentMethodFilter = $request->query->get('payment_method', 'all');
        
        // Récupérer les commandes de l'utilisateur avec filtres (pour l'affichage)
        $orders = $this->fetchUserOrders($userId, $searchQuery, $statusFilter, $paymentMethodFilter);
        
        // Récupérer toutes les commandes pour les statistiques (sans filtres)
        $allOrders = $this->fetchUserOrders($userId, '', 'all');
        
        // Récupérer les statuts disponibles depuis la base de données
        $availableStatuses = $this->orderRepository->findAvailableStatuses($userId);
        
        // Calculer les statistiques avec toutes les commandes
        $stats = $this->calculatePurchaseStats($userId, $allOrders);

        // Paramètres de pagination
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = 20; // Nombre d'éléments par page (2 pour tester la pagination)
        $totalResults = count($orders);
        $totalPages = max(1, (int) ceil($totalResults / $perPage));
        $startResult = $totalResults > 0 ? (($page - 1) * $perPage) + 1 : 0;
        $endResult = min($page * $perPage, $totalResults);
        
        // Paginer les résultats
        $paginatedOrders = array_slice($orders, ($page - 1) * $perPage, $perPage);

        // Récupérer les données pour le graphique (12 mois par défaut)
        $chartPeriod = max(6, min(24, (int) $request->query->get('chart_period', 12)));
        $chartData = $this->fetchSpendingChartData($userId, $chartPeriod);

        return $this->render('profile/history.html.twig', [
            'orders' => $paginatedOrders,
            'stats' => $stats,
            'availableStatuses' => $availableStatuses,
            'currentStatusFilter' => $statusFilter,
            'currentPaymentMethodFilter' => $paymentMethodFilter,
            'searchQuery' => $searchQuery,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'totalResults' => $totalResults,
            'startResult' => $startResult,
            'endResult' => $endResult,
            'perPage' => $perPage,
            'chartData' => $chartData,
            'chartPeriod' => $chartPeriod,
        ]);
    }

    #[Route('/profile/wallet', name: 'profile_wallet')]
    public function wallet(Request $request): Response
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $sessionUser = $session->get('user');
        $isAuthenticated = is_array($sessionUser) && isset($sessionUser['id']);

        if (!$isAuthenticated) {
            return $this->redirectToRoute('login');
        }

        $userId = (int) $sessionUser['id'];

        // Récupérer le solde et les points
        $balance = $this->walletService->getWalletBalance($userId);
        $loyaltyInfo = $this->loyaltyPointsService->getLoyaltyTierInfo($userId);

        // Récupérer les transactions
        $transactions = $this->walletTransactionRepository->findUserTransactions($userId, null, 50);

        // Formater les transactions pour le template
        $formattedTransactions = array_map(function (array $transaction) {
            $date = $transaction['created_at'] ? $transaction['created_at']->format('d M Y') : '';
            
            $typeLabel = match($transaction['type']) {
                'credit' => 'Crédit',
                'debit' => 'Débit',
                'points_credit' => 'Points crédités',
                'points_debit' => 'Points utilisés',
                default => ucfirst($transaction['type']),
            };

            $statusLabel = match($transaction['status']) {
                'completed' => 'Confirmée',
                'pending' => 'En attente',
                'cancelled' => 'Annulée',
                'failed' => 'Échouée',
                default => ucfirst($transaction['status']),
            };

            $amount = '';
            if ($transaction['type'] === 'credit' || $transaction['type'] === 'points_credit') {
                $amount = '+';
            } elseif ($transaction['type'] === 'debit' || $transaction['type'] === 'points_debit') {
                $amount = '-';
            }

            if ($transaction['type'] === 'points_credit' || $transaction['type'] === 'points_debit') {
                $amount .= abs($transaction['points_delta']) . ' pts';
            } else {
                $amount .= number_format(abs($transaction['amount']), 0, ',', ' ') . ' MGA';
            }

            return [
                'date' => $date,
                'label' => $transaction['description'] ?? 'Transaction',
                'type' => $typeLabel,
                'amount' => $amount,
                'status' => $statusLabel,
                'details' => $transaction['description'] ?? '',
            ];
        }, $transactions);

        // Calculer la limite mensuelle de recharge (exemple : 1M MGA)
        $monthlyLimit = 1_000_000.0;
        $monthlyRecharge = 0.0; // TODO: Calculer le total des recharges du mois
        $monthlyProgress = $monthlyRecharge > 0 ? min(($monthlyRecharge / $monthlyLimit) * 100, 100) : 0;

        return $this->render('profile/wallet.html.twig', [
            'balance' => number_format($balance['balance'], 0, ',', ' ') . ' ' . $balance['currency'],
            'points' => number_format($balance['points'], 0, ',', ' ') . ' pts',
            'loyalty_tier' => $loyaltyInfo['current_tier_name'],
            'transactions' => $formattedTransactions,
            'monthly_limit' => number_format($monthlyLimit, 0, ',', ' ') . ' MGA',
            'monthly_progress' => $monthlyProgress,
        ]);
    }

    #[Route('/api/wallet/recharge', name: 'api_wallet_recharge', methods: ['POST'])]
    public function rechargeWallet(Request $request): JsonResponse
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $sessionUser = $session->get('user');
        if (!is_array($sessionUser) || !isset($sessionUser['id'])) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Vous devez être connecté pour recharger votre wallet'
            ], 401);
        }

        $userId = (int) $sessionUser['id'];
        $data = json_decode($request->getContent(), true);

        try {
            $amount = (float) ($data['amount'] ?? 0);
            $paymentMethod = (string) ($data['payment_method'] ?? 'mobile_money');
            $reference = $data['reference'] ?? null;

            if ($amount <= 0) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Le montant doit être supérieur à 0'
                ], 400);
            }

            $transactionId = $this->walletService->rechargeWallet($userId, $amount, $paymentMethod, $reference);
            $balance = $this->walletService->getWalletBalance($userId);

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Wallet rechargé avec succès',
                'data' => [
                    'transaction_id' => $transactionId,
                    'new_balance' => $balance['balance'],
                    'balance_formatted' => number_format($balance['balance'], 0, ',', ' ') . ' ' . $balance['currency'],
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/api/wallet/transfer', name: 'api_wallet_transfer', methods: ['POST'])]
    public function transferWallet(Request $request): JsonResponse
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $sessionUser = $session->get('user');
        if (!is_array($sessionUser) || !isset($sessionUser['id'])) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Vous devez être connecté'
            ], 401);
        }

        $fromUserId = (int) $sessionUser['id'];
        $data = json_decode($request->getContent(), true);

        try {
            $toUserId = (int) ($data['to_user_id'] ?? 0);
            $amount = (float) ($data['amount'] ?? 0);
            $description = (string) ($data['description'] ?? 'Transfert');

            if ($toUserId <= 0) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Utilisateur destinataire invalide'
                ], 400);
            }

            if ($amount <= 0) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Le montant doit être supérieur à 0'
                ], 400);
            }

            if ($fromUserId === $toUserId) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Vous ne pouvez pas transférer vers votre propre wallet'
                ], 400);
            }

            $result = $this->walletService->transferToWallet($fromUserId, $toUserId, $amount, $description);
            $balance = $this->walletService->getWalletBalance($fromUserId);

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Transfert effectué avec succès',
                'data' => [
                    'debit_transaction_id' => $result['debit_transaction_id'],
                    'credit_transaction_id' => $result['credit_transaction_id'],
                    'new_balance' => $balance['balance'],
                    'balance_formatted' => number_format($balance['balance'], 0, ',', ' ') . ' ' . $balance['currency'],
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/api/wallet/transactions', name: 'api_wallet_transactions', methods: ['GET'])]
    public function getTransactions(Request $request): JsonResponse
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $sessionUser = $session->get('user');
        if (!is_array($sessionUser) || !isset($sessionUser['id'])) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Vous devez être connecté'
            ], 401);
        }

        $userId = (int) $sessionUser['id'];
        $type = $request->query->get('type'); // 'credit', 'debit', 'points_credit', 'points_debit'
        $limit = (int) ($request->query->get('limit', 50));

        $transactions = $this->walletTransactionRepository->findUserTransactions($userId, $type, $limit);

        return new JsonResponse([
            'status' => 'success',
            'data' => $transactions,
            'total' => count($transactions),
        ]);
    }

    #[Route('/profile/favorites', name: 'profile_favorites')]
    public function favorites(Request $request): Response
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $sessionUser = $session->get('user');
        $isAuthenticated = is_array($sessionUser) && isset($sessionUser['id']);

        if (!$isAuthenticated) {
            return $this->redirectToRoute('login');
        }

        $userId = (int) $sessionUser['id'];
        $favoriteEvents = $this->fetchUserFavoriteEvents($userId);

        return $this->render('profile/favorites.html.twig', [
            'favorites' => $favoriteEvents,
            'isAuthenticated' => $isAuthenticated,
        ]);
    }

    #[Route('/profile/search-history', name: 'profile_search_history')]
    public function searchHistory(Request $request): Response
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $sessionUser = $session->get('user');
        $isAuthenticated = is_array($sessionUser) && isset($sessionUser['id']);

        if (!$isAuthenticated) {
            return $this->redirectToRoute('login');
        }

        $userId = (int) $sessionUser['id'];

        // Récupérer les paramètres de filtrage et tri
        $searchQuery = trim((string) $request->query->get('q', ''));
        $sortBy = $request->query->get('sort', 'newest'); // newest, oldest, custom
        $dateFrom = trim((string) $request->query->get('date_from', ''));
        $dateTo = trim((string) $request->query->get('date_to', ''));

        // Récupérer l'historique de recherche
        $searchHistory = $this->fetchUserSearchHistory($userId, $searchQuery, $sortBy, $dateFrom, $dateTo);

        // Compter le nombre de résultats pour chaque recherche
        $searchHistoryWithResults = $this->countResultsForSearches($searchHistory);

        return $this->render('profile/search_history.html.twig', [
            'searches' => $searchHistoryWithResults,
            'isAuthenticated' => $isAuthenticated,
            'currentSearchQuery' => $searchQuery,
            'currentSort' => $sortBy,
            'currentDateFrom' => $dateFrom,
            'currentDateTo' => $dateTo,
        ]);
    }

    #[Route('/profile/search-history/{id}/delete', name: 'profile_search_history_delete', methods: ['DELETE'])]
    public function deleteSearchHistoryItem(int $id, Request $request): JsonResponse
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $sessionUser = $session->get('user');
        if (!is_array($sessionUser) || !isset($sessionUser['id'])) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Vous devez être connecté'
            ], 401);
        }

        $userId = (int) $sessionUser['id'];

        // Vérifier que l'élément existe avant de le supprimer
        if (!$this->searchHistoryRepository->searchHistoryItemExists($id, $userId)) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Élément introuvable'
            ], 404);
        }

        // Supprimer l'élément
        $this->searchHistoryRepository->deleteSearchHistoryItem($id, $userId);

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Recherche supprimée de l\'historique'
        ]);
    }

    #[Route('/profile/search-history/clear', name: 'profile_search_history_clear', methods: ['DELETE'])]
    public function clearSearchHistory(Request $request): JsonResponse
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $sessionUser = $session->get('user');
        if (!is_array($sessionUser) || !isset($sessionUser['id'])) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Vous devez être connecté'
            ], 401);
        }

        $userId = (int) $sessionUser['id'];

        // Supprimer tout l'historique de l'utilisateur
        $this->searchHistoryRepository->clearUserSearchHistory($userId);

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Historique de recherche effacé'
        ]);
    }

    /**
     * Récupère l'historique de recherche de l'utilisateur avec filtres et tri
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchUserSearchHistory(int $userId, string $searchQuery = '', string $sortBy = 'newest', string $dateFrom = '', string $dateTo = ''): array
    {
        return $this->searchHistoryRepository->findUserSearchHistory($userId, $searchQuery, $sortBy, $dateFrom, $dateTo);
    }

    /**
     * Compte le nombre de résultats pour chaque recherche en utilisant la méthode searchEvents d'EventController
     * Note: On utilise une approche simplifiée en comptant directement depuis la base de données
     *
     * @param array<int, array<string, mixed>> $searches
     * @return array<int, array<string, mixed>>
     */
    private function countResultsForSearches(array $searches): array
    {
        // Pour chaque recherche, on va compter les résultats en utilisant une requête similaire à searchEvents
        foreach ($searches as &$search) {
            $query = $search['query'];
            $filters = $search['filters'] ?? [];

            $category = $filters['category'] ?? '';
            $city = $filters['city'] ?? '';
            $priceMin = $filters['price_min'] ?? null;
            $priceMax = $filters['price_max'] ?? null;
            $dateFrom = $filters['date_from'] ?? '';
            $dateTo = $filters['date_to'] ?? '';

            // Compter les résultats de recherche
            $count = $this->eventRepository->countSearchResults($query, $category, $city, $priceMin, $priceMax, $dateFrom, $dateTo);
            $search['results'] = $count;
        }
        unset($search);

        return $searches;
    }

    /**
     * Compte le nombre de résultats pour une recherche donnée
     */

    #[Route('/profile/calendar', name: 'profile_calendar')]
    public function calendar(): Response
    {
        return $this->render('profile/calendar.html.twig');
    }

    #[Route('/profile/stats', name: 'profile_stats')]
    public function stats(Request $request): Response
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $sessionUser = $session->get('user');
        $isAuthenticated = is_array($sessionUser) && isset($sessionUser['id']);

        if (!$isAuthenticated) {
            return $this->redirectToRoute('login');
        }

        $userId = (int) $sessionUser['id'];
        
        // Récupérer la période de filtrage (défaut: toutes les données)
        $period = $request->query->get('period', 'all'); // all, 30, 90, 365
        $dateFrom = null;
        
        if ($period !== 'all') {
            $days = (int) $period;
            $dateFrom = (new \DateTimeImmutable())->modify("-{$days} days");
        }
        
        // Récupérer les statistiques avec filtre de période
        $stats = $this->fetchUserStatistics($userId, $dateFrom);
        
        // Récupérer les dépenses mensuelles avec filtre de période
        $monthlyExpenses = $this->fetchMonthlyExpenses($userId, $dateFrom);
        
        // Calculer la valeur maximale pour le graphique
        $maxExpense = 0;
        if (!empty($monthlyExpenses)) {
            $maxExpense = max(array_column($monthlyExpenses, 'total_raw'));
        }
        
        // Récupérer la répartition par type d'événement avec filtre de période
        $eventTypeDistribution = $this->fetchEventTypeDistribution($userId, $dateFrom);
        
        // Récupérer le Top 5 des événements achetés avec filtre de période
        $topEvents = $this->fetchTopPurchasedEvents($userId, 5, $dateFrom);
        
        // Récupérer les insights dynamiques
        $insights = $this->fetchStatsInsights($userId, $dateFrom);
        
        // Récupérer la comparaison annuelle (seulement si période = all)
        $yearComparison = $period === 'all' ? $this->fetchYearComparison($userId) : [];

        return $this->render('profile/stats.html.twig', [
            'stats' => $stats,
            'monthlyExpenses' => $monthlyExpenses,
            'maxExpense' => $maxExpense,
            'eventTypeDistribution' => $eventTypeDistribution,
            'topEvents' => $topEvents,
            'insights' => $insights,
            'currentPeriod' => $period,
            'yearComparison' => $yearComparison,
        ]);
    }
    
    #[Route('/profile/stats/export', name: 'profile_stats_export')]
    public function exportStats(Request $request): Response
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $sessionUser = $session->get('user');
        $isAuthenticated = is_array($sessionUser) && isset($sessionUser['id']);

        if (!$isAuthenticated) {
            return $this->redirectToRoute('login');
        }

        $userId = (int) $sessionUser['id'];
        
        // Récupérer la période de filtrage
        $period = $request->query->get('period', 'all');
        $dateFrom = null;
        
        if ($period !== 'all') {
            $days = (int) $period;
            $dateFrom = (new \DateTimeImmutable())->modify("-{$days} days");
        }
        
        // Récupérer toutes les données
        $stats = $this->fetchUserStatistics($userId, $dateFrom);
        $eventTypeDistribution = $this->fetchEventTypeDistribution($userId, $dateFrom);
        $topEvents = $this->fetchTopPurchasedEvents($userId, 10, $dateFrom);
        $monthlyExpenses = $this->fetchMonthlyExpenses($userId, $dateFrom);
        
        // Générer le CSV
        $csvLines = [];
        $csvLines[] = "Statistiques personnelles - " . date('d/m/Y H:i');
        $csvLines[] = "";
        
        // Statistiques générales
        $csvLines[] = "Statistiques générales";
        $csvLines[] = "Total billets," . ($stats['total_tickets'] ?? 0);
        $csvLines[] = "Total dépensé," . ($stats['total_spent'] ?? '0 MGA');
        $csvLines[] = "Événements uniques," . ($stats['unique_events'] ?? 0);
        $csvLines[] = "Commandes totales," . ($stats['total_orders'] ?? 0);
        $csvLines[] = "Panier moyen," . ($stats['avg_cart'] ?? '0 MGA');
        $csvLines[] = "";
        
        // Répartition par catégorie
        $csvLines[] = "Répartition par catégorie";
        $csvLines[] = "Catégorie,Pourcentage,Commandes";
        foreach ($eventTypeDistribution as $dist) {
            $csvLines[] = sprintf(
                '"%s",%s,%s',
                str_replace('"', '""', $dist['category']),
                $dist['percentage'],
                $dist['order_count']
            );
        }
        $csvLines[] = "";
        
        // Top événements
        $csvLines[] = "Top événements achetés";
        $csvLines[] = "Rang,Titre,Catégorie,Billets,Achats,Montant total";
        foreach ($topEvents as $index => $event) {
            $csvLines[] = sprintf(
                '%s,"%s","%s",%s,%s,"%s"',
                $index + 1,
                str_replace('"', '""', $event['title']),
                str_replace('"', '""', $event['category']),
                $event['total_tickets'],
                $event['purchase_count'],
                str_replace('"', '""', $event['total_spent'])
            );
        }
        $csvLines[] = "";
        
        // Dépenses mensuelles
        $csvLines[] = "Dépenses mensuelles";
        $csvLines[] = "Mois,Montant";
        foreach ($monthlyExpenses as $expense) {
            $csvLines[] = sprintf(
                '"%s","%s"',
                str_replace('"', '""', $expense['month']),
                str_replace('"', '""', $expense['total'])
            );
        }
        
        $csvContent = implode("\n", $csvLines);
        
        $periodLabel = $period === 'all' ? 'toutes_periodes' : $period . '_jours';
        $filename = 'statistiques_' . $periodLabel . '_' . date('Y-m-d_His') . '.csv';
        
        $response = new Response($csvContent);
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Expires', '0');
        
        return $response;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchUserFavoriteEvents(int $userId): array
    {
        return $this->wishlistRepository->findUserFavoriteEvents($userId);
    }

    #[Route('/profile/settings', name: 'profile_settings')]
    public function settings(Request $request): Response
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $sessionUser = $session->get('user');
        $isAuthenticated = is_array($sessionUser) && isset($sessionUser['id']);

        if (!$isAuthenticated) {
            return $this->redirectToRoute('login');
        }

        $userId = (int) $sessionUser['id'];

        // Récupérer les informations utilisateur
        $userInfo = $this->userRepository->findUserInfo($userId);
        
        // Récupérer les préférences utilisateur
        $preferences = $this->userRepository->findUserPreferences($userId);

        return $this->render('profile/settings.html.twig', [
            'user' => $userInfo,
            'preferences' => $preferences,
            'isAuthenticated' => $isAuthenticated,
        ]);
    }

    #[Route('/profile/settings/update', name: 'profile_settings_update', methods: ['POST'])]
    public function updateSettings(Request $request): JsonResponse
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $sessionUser = $session->get('user');
        if (!is_array($sessionUser) || !isset($sessionUser['id'])) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Vous devez être connecté'
            ], 401);
        }

        $userId = (int) $sessionUser['id'];
        $data = json_decode($request->getContent(), true);

        try {
            // Mettre à jour les informations personnelles
            if (isset($data['personal_info'])) {
                $personalInfo = $data['personal_info'];
                $updateFields = [];
                $params = ['userId' => $userId];

                if (isset($personalInfo['first_name'])) {
                    $updateFields[] = 'first_name = :first_name';
                    $params['first_name'] = $personalInfo['first_name'];
                }
                if (isset($personalInfo['last_name'])) {
                    $updateFields[] = 'last_name = :last_name';
                    $params['last_name'] = $personalInfo['last_name'];
                }
                if (isset($personalInfo['phone'])) {
                    $updateFields[] = 'phone = :phone';
                    $params['phone'] = $personalInfo['phone'];
                }
                if (isset($personalInfo['language_code'])) {
                    $updateFields[] = 'language_code = :language_code';
                    $params['language_code'] = $personalInfo['language_code'];
                }

                if (!empty($updateFields)) {
                    $this->userRepository->updateUser($userId, $updateFields, $params);
                }
            }

            // Mettre à jour les préférences
            if (isset($data['preferences'])) {
                foreach ($data['preferences'] as $key => $value) {
                    $this->userRepository->updateUserPreference($userId, $key, $value);
                }
            }

            return new JsonResponse([
                'status' => 'success',
                'message' => $this->translator->trans('settings.update_success')
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
            ], 500);
        }
    }


    #[Route('/profile/financial-history', name: 'profile_financial')]
    public function financialHistory(Request $request): Response
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $sessionUser = $session->get('user');
        $isAuthenticated = is_array($sessionUser) && isset($sessionUser['id']);

        if (!$isAuthenticated) {
            return $this->redirectToRoute('login');
        }

        $userId = (int) $sessionUser['id'];
        
        // Récupérer l'historique financier
        $financialData = $this->orderRepository->findFinancialHistory($userId);
        
        // Récupérer les dépenses mensuelles
        $monthly = $this->orderRepository->findMonthlyFinancialData($userId);
        
        // Récupérer la répartition des méthodes de paiement
        $paymentMethods = $this->userStatsRepository->findPaymentMethodDistribution($userId);

        return $this->render('profile/financial.html.twig', [
            'financialData' => $financialData,
            'monthly' => $monthly,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    #[Route('/profile/ticket-chance', name: 'profile_ticket_chance')]
    public function ticketChance(): Response
    {
        return $this->render('profile/ticket_chance.html.twig');
    }

    /**
     * Récupère les statistiques de l'utilisateur
     *
     * @param array<string, mixed> $sessionCartItems
     * @return array<string, mixed>
     */
    private function fetchUserStats(int $userId, array $sessionCartItems = []): array
    {
        return $this->userStatsRepository->findUserStats($userId, $sessionCartItems);
    }


    /**
     * Récupère les commandes de l'utilisateur avec leurs détails.
     */
    private function fetchUserOrders(int $userId, string $searchQuery = '', string $statusFilter = 'all', string $paymentMethodFilter = 'all'): array
    {
        $rows = $this->orderRepository->findUserOrders($userId, $searchQuery, $statusFilter, $paymentMethodFilter);

        return array_map(function (array $row): array {
            $status = $row['status'];
            $statusLabels = [
                'pending' => 'En attente',
                'paid' => 'Payée',
                'cancelled' => 'Annulée',
                'failed' => 'Échouée',
            ];

            $paymentMethodLabels = [
                'mvola' => 'M-Vola',
                'orange-money' => 'Orange Money',
                'airtel-money' => 'Airtel Money',
                'orange' => 'Orange Money',
                'airtel' => 'Airtel Money',
                'telma' => 'Telma',
                'bank_transfer' => 'Virement bancaire',
            ];

            // Extraire le payment_method depuis le champ notes (JSON)
            $paymentMethod = null;
            if (!empty($row['notes'])) {
                $notes = json_decode($row['notes'], true);
                if (is_array($notes) && isset($notes['payment_method'])) {
                    $paymentMethod = $notes['payment_method'];
                }
            }

            // Utiliser la date de création de la commande (date du paiement) au lieu de la date de l'événement
            $paymentDate = !empty($row['created_at']) ? new \DateTimeImmutable($row['created_at']) : null;

            return [
                'id' => (int) $row['id'],
                'code' => 'CMD-' . str_pad((string) $row['id'], 6, '0', STR_PAD_LEFT),
                'title' => $row['event_titles'] ?? 'Événement',
                'date' => $paymentDate ? $paymentDate->format('d F Y') : '',
                'hour' => $paymentDate ? $paymentDate->format('H:i') : '',
                'status' => $statusLabels[$status] ?? ucfirst($status),
                'status_key' => $status,
                'amount' => number_format((float) $row['total_amount'], 0, ',', ' ') . ' MGA',
                'amount_raw' => (float) $row['total_amount'],
                'method' => $paymentMethod ? ($paymentMethodLabels[$paymentMethod] ?? ucfirst(str_replace('-', ' ', $paymentMethod))) : 'Non spécifié',
                'tickets' => (int) ($row['total_tickets'] ?? 0),
                'items_count' => (int) ($row['items_count'] ?? 0),
                'created_at' => new \DateTimeImmutable($row['created_at']),
            ];
        }, $rows);
    }


    /**
     * Calcule les statistiques d'achat de l'utilisateur.
     */
    private function calculatePurchaseStats(int $userId, array $orders): array
    {
        $confirmedOrders = array_filter($orders, fn($o) => $o['status_key'] === 'paid');
        
        $totalSpent = array_sum(array_column($confirmedOrders, 'amount_raw'));
        $totalTickets = array_sum(array_column($confirmedOrders, 'tickets'));
        
        // Compter les billets VIP (approximation basée sur le montant)
        $vipTickets = 0;
        foreach ($confirmedOrders as $order) {
            if ($order['amount_raw'] > 200000) { // Si montant élevé, probablement VIP
                $vipTickets += (int) ($order['tickets'] * 0.3); // Estimation 30% VIP
            }
        }

        // Compter les événements à venir pour lesquels l'utilisateur a des billets payés
        $upcomingEventsCount = $this->userStatsRepository->countUpcomingEvents($userId);

        return [
            'total_spent' => number_format($totalSpent, 0, ',', ' ') . ' MGA',
            'total_spent_raw' => $totalSpent,
            'confirmed_orders' => count($confirmedOrders),
            'total_tickets' => $totalTickets,
            'vip_tickets' => $vipTickets,
            'upcoming_events' => $upcomingEventsCount,
            'average_cart' => count($confirmedOrders) > 0 
                ? number_format($totalSpent / count($confirmedOrders), 0, ',', ' ') . ' MGA'
                : '0 MGA',
        ];
    }


    /**
     * Récupère les données de dépenses par mois pour le graphique.
     * 
     * @param int $userId ID de l'utilisateur
     * @param int $months Nombre de mois à récupérer (6, 12, ou 24)
     * @return array Tableau avec 'labels' (mois) et 'data' (montants)
     */
    private function fetchSpendingChartData(int $userId, int $months = 12): array
    {
        return $this->orderRepository->findSpendingChartData($userId, $months);
    }

    /**
     * Récupère les statistiques personnelles de l'utilisateur.
     */
    private function fetchUserStatistics(int $userId, ?\DateTimeImmutable $dateFrom = null): array
    {
        $result = $this->userStatsRepository->findUserStatistics($userId, $dateFrom);
        return $result ?: [
            'total_tickets' => 0,
            'total_spent' => '0 MGA',
            'total_spent_raw' => 0,
            'unique_events' => 0,
            'avg_cart' => '0 MGA',
            'total_orders' => 0,
        ];
    }

    /**
     * Récupère les dépenses mensuelles de l'utilisateur.
     */
    private function fetchMonthlyExpenses(int $userId, ?\DateTimeImmutable $dateFrom = null): array
    {
        return $this->orderRepository->findMonthlyExpenses($userId, $dateFrom);
    }

    /**
     * Récupère la répartition par type d'événement.
     */
    private function fetchEventTypeDistribution(int $userId, ?\DateTimeImmutable $dateFrom = null): array
    {
        return $this->userStatsRepository->findEventTypeDistribution($userId, $dateFrom);
    }

    /**
     * Récupère le Top N des événements achetés par l'utilisateur.
     * 
     * @param int $userId ID de l'utilisateur
     * @param int $limit Nombre d'événements à retourner (défaut: 5)
     * @param \DateTimeImmutable|null $dateFrom Date de début pour filtrer (optionnel)
     * @return array Tableau des événements triés par montant total dépensé
     */
    private function fetchTopPurchasedEvents(int $userId, int $limit = 5, ?\DateTimeImmutable $dateFrom = null): array
    {
        return $this->userStatsRepository->findTopPurchasedEvents($userId, $limit, $dateFrom);
    }


    /**
     * Récupère la répartition des méthodes de paiement.
     */
    private function fetchPaymentMethodDistribution(int $userId): array
    {
        return $this->userStatsRepository->findPaymentMethodDistribution($userId);
    }

    /**
     * Récupère les insights dynamiques basés sur les statistiques de l'utilisateur.
     */
    private function fetchStatsInsights(int $userId, ?\DateTimeImmutable $dateFrom = null): array
    {
        $insights = [
            'moments' => [],
            'suggestions' => [],
        ];
        
        // Récupérer le mois le plus actif
        $mostActiveMonth = $this->getMostActiveMonth($userId, $dateFrom);
        if ($mostActiveMonth) {
            $insights['moments'][] = [
                'icon' => 'fas fa-calendar-star',
                'text' => "Votre mois le plus actif était <strong>{$mostActiveMonth['month']}</strong> avec {$mostActiveMonth['total']} d'achats"
            ];
        }
        
        // Récupérer le total économisé avec les codes promo
        $totalSaved = $this->getTotalSavedWithPromos($userId, $dateFrom);
        if ($totalSaved > 0) {
            $insights['moments'][] = [
                'icon' => 'fas fa-tag',
                'text' => "Vous avez économisé <strong>" . number_format($totalSaved, 0, ',', ' ') . " MGA</strong> grâce aux codes promo"
            ];
        }
        
        // Récupérer le nombre de types d'événements différents
        $eventTypesCount = $this->getEventTypesCount($userId, $dateFrom);
        if ($eventTypesCount > 0) {
            $insights['moments'][] = [
                'icon' => 'fas fa-palette',
                'text' => "Vous avez exploré <strong>{$eventTypesCount}</strong> type" . ($eventTypesCount > 1 ? 's' : '') . " d'événements différents"
            ];
        }
        
        // Récupérer la catégorie préférée
        $favoriteCategory = $this->getFavoriteCategory($userId, $dateFrom);
        if ($favoriteCategory) {
            $insights['suggestions'][] = [
                'category' => $favoriteCategory['category'],
                'reason' => "Vous avez acheté {$favoriteCategory['count']} billet" . ($favoriteCategory['count'] > 1 ? 's' : '') . " pour des événements " . strtolower($favoriteCategory['category'])
            ];
        }
        
        // Récupérer les événements similaires à recommander
        $recommendedCategories = $this->getRecommendedCategories($userId, $dateFrom);
        $insights['recommended_categories'] = $recommendedCategories;
        
        return $insights;
    }

    /**
     * Récupère le mois le plus actif de l'utilisateur.
     */
    private function getMostActiveMonth(int $userId, ?\DateTimeImmutable $dateFrom = null): ?array
    {
        return $this->userStatsRepository->findMostActiveMonth($userId, $dateFrom);
    }

    /**
     * Calcule le total économisé avec les codes promo.
     */
    private function getTotalSavedWithPromos(int $userId, ?\DateTimeImmutable $dateFrom = null): float
    {
        return $this->userStatsRepository->calculateTotalSavedWithPromos($userId, $dateFrom);
    }

    /**
     * Compte le nombre de types d'événements différents.
     */
    private function getEventTypesCount(int $userId, ?\DateTimeImmutable $dateFrom = null): int
    {
        return $this->userStatsRepository->countEventTypes($userId, $dateFrom);
    }

    /**
     * Récupère la catégorie préférée de l'utilisateur.
     */
    private function getFavoriteCategory(int $userId, ?\DateTimeImmutable $dateFrom = null): ?array
    {
        return $this->userStatsRepository->findFavoriteCategory($userId, $dateFrom);
    }

    /**
     * Récupère les catégories recommandées basées sur l'historique.
     */
    private function getRecommendedCategories(int $userId, ?\DateTimeImmutable $dateFrom = null): array
    {
        return $this->userStatsRepository->findRecommendedCategories($userId);
    }

    /**
     * Récupère la comparaison des dépenses entre l'année en cours et l'année précédente.
     */
    private function fetchYearComparison(int $userId): array
    {
        return $this->orderRepository->findYearComparison($userId);
    }
}
