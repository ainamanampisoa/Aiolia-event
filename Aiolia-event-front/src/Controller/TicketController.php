<?php

namespace App\Controller;

use App\Repository\EventRepository;
use App\Repository\TicketRepository;
use App\Service\CartSyncService;
use App\Service\PaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TicketController extends AbstractController
{
    public function __construct(
        private readonly TicketRepository $ticketRepository,
        private readonly EventRepository $eventRepository,
        private readonly CartSyncService $cartSyncService,
        private readonly PaymentService $paymentService
    ) {
    }

    #[Route('/cart', name: 'cart')]
    public function cart(Request $request): Response
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        // Tenter de synchroniser avec la DB si utilisateur connecté
        $user = $session->get('user');
        $userId = $user && is_array($user) ? ($user['id'] ?? null) : null;
        
        // Récupérer le panier depuis la session
        $cartItems = $session->get('cart_items', []);
        
        if ($userId) {
            // Récupérer le panier depuis la DB (source de vérité)
            $dbCart = $this->cartSyncService->getOrCreateCart($userId, null);
            if ($dbCart) {
                $dbItems = $this->cartSyncService->convertDbItemsToSessionFormat($dbCart['items']);
                
                // Si la DB a des items, utiliser la DB comme source de vérité
                // Sinon, si la session a des items, les sauvegarder dans la DB
                if (!empty($dbItems)) {
                    // La DB est la source de vérité
                    $cartItems = $dbItems;
                    $session->set('cart_items', $cartItems);
                } elseif (!empty($cartItems)) {
                    // Sauvegarder les items de la session dans la DB
                    $this->cartSyncService->saveCartItems((int) $dbCart['id'], $cartItems);
                } else {
                    // Les deux sont vides, s'assurer que la session est vide
                    $session->set('cart_items', []);
                }
            }
        }
        
        $items = $this->formatCartItemsForTemplate($cartItems);

        return $this->render('ticket/cart.html.twig', [
            'items' => $items,
        ]);
    }

    #[Route('/cart/remove/{cartKey}', name: 'remove_from_cart', methods: ['POST', 'DELETE'])]
    public function removeCartItem(string $cartKey, Request $request): Response
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $cartItems = $session->get('cart_items', []);

        if (isset($cartItems[$cartKey])) {
            unset($cartItems[$cartKey]);
            $session->set('cart_items', $cartItems);
            
            // Synchroniser avec la DB
            $user = $session->get('user');
            $userId = $user && is_array($user) ? ($user['id'] ?? null) : null;
            $sessionToken = $session->get('cart_session_token');
            
            if ($userId) {
                $dbCart = $this->cartSyncService->getOrCreateCart($userId, null);
                if ($dbCart) {
                    $this->cartSyncService->removeCartItem((int) $dbCart['id'], $cartKey);
                }
            } elseif ($sessionToken) {
                $dbCart = $this->cartSyncService->getOrCreateCart(null, $sessionToken);
                if ($dbCart) {
                    $this->cartSyncService->removeCartItem((int) $dbCart['id'], $cartKey);
                }
            }
            
            $this->addFlash('success', 'Élément retiré du panier avec succès.');
        } else {
            $this->addFlash('error', 'Élément introuvable dans le panier.');
        }

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'status' => 'success',
                'message' => 'Élément retiré du panier avec succès.',
            ]);
        }

        return $this->redirectToRoute('cart');
    }

    #[Route('/add-to-cart', name: 'add_to_cart', methods: ['POST'])]
    public function addToCart(Request $request): Response
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $eventId = (int) $request->request->get('event_id', 0);
        $ticketTypeId = (int) $request->request->get('ticket_type_id', 0);
        $adultTicketTypeId = (int) $request->request->get('adult_ticket_type_id', 0);
        $childTicketTypeId = (int) $request->request->get('child_ticket_type_id', 0);
        $adultQuantity = (int) $request->request->get('adult_quantity', 0);
        $childQuantity = (int) $request->request->get('child_quantity', 0);

        if ($eventId <= 0) {
            $this->addFlash('error', 'Données invalides.');
            return $this->redirectToRoute('events');
        }

        if ($adultQuantity <= 0 && $childQuantity <= 0) {
            $this->addFlash('error', 'Veuillez sélectionner au moins une quantité.');
            return $this->redirectToRoute('event_details', ['id' => $eventId]);
        }

        // Si on a les deux ticket_type_id séparés (adulte et enfant), utiliser celui qui a une quantité > 0
        if ($adultTicketTypeId > 0 && $childTicketTypeId > 0) {
            // Mode avec deux types séparés - utiliser la clé basée uniquement sur l'événement
            $ticketTypeId = $adultTicketTypeId; // Garder pour compatibilité, mais utiliser pour la clé
        } elseif ($ticketTypeId <= 0) {
            // Si aucun ticket_type_id n'est fourni, essayer de le déterminer
            if ($adultTicketTypeId > 0) {
                $ticketTypeId = $adultTicketTypeId;
            } elseif ($childTicketTypeId > 0) {
                $ticketTypeId = $childTicketTypeId;
            } else {
                $this->addFlash('error', 'Type de billet invalide.');
                return $this->redirectToRoute('event_details', ['id' => $eventId]);
            }
        }

        // Récupérer les détails de l'événement
        $event = $this->eventRepository->findEventDetailsById($eventId);
        if (null === $event) {
            $this->addFlash('error', 'Événement introuvable.');
            return $this->redirectToRoute('events');
        }

        // Récupérer les types de billets
        $ticketTypes = $this->eventRepository->findTicketTypesByEventId($eventId);
        
        // Calculer les prix adultes et enfants
        $adultPrice = 0;
        $childPrice = 0;
        $currency = 'MGA';
        
        // Si on a les deux types séparés, récupérer les prix de chaque type
        if ($adultTicketTypeId > 0 && $childTicketTypeId > 0) {
            $adultTicketType = null;
            $childTicketType = null;
            foreach ($ticketTypes as $ticketType) {
                if ($ticketType['id'] === $adultTicketTypeId) {
                    $adultTicketType = $ticketType;
                }
                if ($ticketType['id'] === $childTicketTypeId) {
                    $childTicketType = $ticketType;
                }
            }
            
            if ($adultTicketType && $adultQuantity > 0) {
                $adultPrice = $adultTicketType['base_price'];
                $currency = $adultTicketType['currency'] ?? 'MGA';
            }
            if ($childTicketType && $childQuantity > 0) {
                $childPrice = $childTicketType['base_price'];
                if ($adultPrice === 0) {
                    $currency = $childTicketType['currency'] ?? 'MGA';
                }
            }
        } else {
            // Mode classique avec un seul ticket_type_id
            $selectedTicketType = null;
            foreach ($ticketTypes as $ticketType) {
                if ($ticketType['id'] === $ticketTypeId) {
                    $selectedTicketType = $ticketType;
                    break;
                }
            }

            if (null === $selectedTicketType) {
                $this->addFlash('error', 'Type de billet introuvable.');
                return $this->redirectToRoute('event_details', ['id' => $eventId]);
            }

            if ($selectedTicketType['age_category'] === 'adult' || $selectedTicketType['age_category'] === 'all') {
                $adultPrice = $selectedTicketType['base_price'];
            }
            if ($selectedTicketType['age_category'] === 'child' || $selectedTicketType['age_category'] === 'all') {
                $childPrice = $selectedTicketType['base_price'];
            }
            $currency = $selectedTicketType['currency'] ?? 'MGA';
        }

        // Récupérer le panier existant depuis la session
        $cartItems = $session->get('cart_items', []);

        // Si on a les deux types (adulte et enfant), utiliser une clé basée uniquement sur l'événement
        // Cela permet de regrouper les billets adultes et enfants du même événement dans une seule entrée
        $hasBothTypes = $adultTicketTypeId > 0 && $childTicketTypeId > 0;
        
        // Vérifier si un événement avec cette clé existe déjà (même si on n'a qu'un seul type maintenant)
        $eventKey = 'event_' . $eventId;
        $hasEventKey = isset($cartItems[$eventKey]);
        
        // Vérifier aussi si un item avec la clé classique existe déjà
        $classicKey = 'event_' . $eventId . '_ticket_' . $ticketTypeId;
        $hasClassicKey = isset($cartItems[$classicKey]);
        
        // Si on a les deux types OU si la clé événement existe déjà, utiliser la clé basée sur l'événement
        if ($hasBothTypes || $hasEventKey) {
            // Clé basée uniquement sur l'événement pour regrouper les deux types
            $cartKey = $eventKey;
            
            // Si un item avec la clé classique existe, le migrer vers la clé événement
            if ($hasClassicKey && !$hasEventKey) {
                $cartItems[$cartKey] = $cartItems[$classicKey];
                unset($cartItems[$classicKey]);
                // Mettre à jour les IDs de ticket types si nécessaire
                if (!isset($cartItems[$cartKey]['adultTicketTypeId']) && $adultTicketTypeId > 0) {
                    $cartItems[$cartKey]['adultTicketTypeId'] = $adultTicketTypeId;
                }
                if (!isset($cartItems[$cartKey]['childTicketTypeId']) && $childTicketTypeId > 0) {
                    $cartItems[$cartKey]['childTicketTypeId'] = $childTicketTypeId;
                }
            }
        } else {
            // Clé classique avec ticket_type_id
            $cartKey = $classicKey;
            
            // Si un item avec la clé événement existe, utiliser cette clé à la place
            if ($hasEventKey) {
                $cartKey = $eventKey;
            }
        }
        
        if (isset($cartItems[$cartKey])) {
            // Mettre à jour les quantités
            $cartItems[$cartKey]['adultQuantity'] += $adultQuantity;
            $cartItems[$cartKey]['childQuantity'] += $childQuantity;
            // Mettre à jour les prix si nécessaire (prendre les plus récents)
            if ($adultPrice > 0) {
                $cartItems[$cartKey]['adultPrice'] = $adultPrice;
            }
            if ($childPrice > 0) {
                $cartItems[$cartKey]['childPrice'] = $childPrice;
            }
        } else {
            // Ajouter un nouvel élément au panier
            $cartItems[$cartKey] = [
                'eventId' => $eventId,
                'ticketTypeId' => $ticketTypeId,
                'adultTicketTypeId' => $adultTicketTypeId > 0 ? $adultTicketTypeId : null,
                'childTicketTypeId' => $childTicketTypeId > 0 ? $childTicketTypeId : null,
                'adultQuantity' => $adultQuantity,
                'childQuantity' => $childQuantity,
                'adultPrice' => $adultPrice > 0 ? $adultPrice : null,
                'childPrice' => $childPrice > 0 ? $childPrice : null,
                'currency' => $currency,
                'added_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ];
        }

        // Sauvegarder le panier en session
        $session->set('cart_items', $cartItems);

        // Synchroniser avec la DB si utilisateur connecté
        $user = $session->get('user');
        $userId = $user && is_array($user) ? ($user['id'] ?? null) : null;
        
        if ($userId) {
            // Récupérer ou créer le panier DB
            $dbCart = $this->cartSyncService->getOrCreateCart($userId, null);
            if ($dbCart) {
                // Sauvegarder les items dans la DB
                $this->cartSyncService->saveCartItems((int) $dbCart['id'], $cartItems);
            }
        } else {
            // Pour les utilisateurs non connectés, utiliser un session_token
            $sessionToken = $session->get('cart_session_token');
            if (!$sessionToken) {
                $sessionToken = $this->cartSyncService->generateSessionToken();
                $session->set('cart_session_token', $sessionToken);
            }
            
            // Créer ou récupérer le panier avec session_token
            $dbCart = $this->cartSyncService->getOrCreateCart(null, $sessionToken);
            if ($dbCart) {
                // Sauvegarder les items dans la DB
                $this->cartSyncService->saveCartItems((int) $dbCart['id'], $cartItems);
            }
        }

        $this->addFlash('success', 'Événement ajouté au panier avec succès.');
        return $this->redirectToRoute('cart');
    }

    #[Route('/become-organizer', name: 'become_organizer')]
    public function becomeOrganizer(): Response
    {
        // TODO: Implémenter la page devenir organisateur
        return $this->render('organizer/become.html.twig');
    }

    #[Route('/contact', name: 'contact')]
    public function contact(): Response
    {
        // Page de contact (copie depuis vente-ticket/contact.html)
        return $this->render('contact/index.html.twig');
    }

    #[Route('/about', name: 'about')]
    public function about(): Response
    {
        // TODO: Implémenter la page à propos
        return $this->render('about/index.html.twig');
    }

    // Checkout pages
    #[Route('/checkout/summary', name: 'checkout_summary')]
    public function checkoutSummary(): Response
    {
        return $this->redirectToRoute('checkout_payment');
    }

    #[Route('/checkout/payment', name: 'checkout_payment')]
    public function checkoutPayment(Request $request): Response
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        // Récupérer le paramètre event (optionnel) - format: "eventId-loopIndex" ou juste "eventId"
        $eventParam = $request->query->get('event');
        $eventIdToFilter = null;
        
        if ($eventParam) {
            // Extraire l'ID de l'événement (avant le tiret si présent)
            $parts = explode('-', $eventParam);
            $eventIdToFilter = (int) $parts[0];
        }

        // Récupérer le panier depuis la session
        $cartItems = $session->get('cart_items', []);
        
        // Tenter de synchroniser avec la DB si utilisateur connecté
        $user = $session->get('user');
        $userId = $user && is_array($user) ? ($user['id'] ?? null) : null;
        
        if ($userId) {
            // Récupérer le panier depuis la DB
            $dbCart = $this->cartSyncService->getOrCreateCart($userId, null);
            if ($dbCart && !empty($dbCart['items'])) {
                $dbItems = $this->cartSyncService->convertDbItemsToSessionFormat($dbCart['items']);
                // Fusionner les deux paniers (priorité au plus récent)
                $cartItems = $this->cartSyncService->mergeCarts($cartItems, $dbItems);
                // Mettre à jour la session avec les items fusionnés
                $session->set('cart_items', $cartItems);
            } elseif ($dbCart && !empty($cartItems)) {
                // Sauvegarder les items de la session dans la DB
                $this->cartSyncService->saveCartItems((int) $dbCart['id'], $cartItems);
            }
        }
        
        // Filtrer les items si un eventId est spécifié
        if ($eventIdToFilter !== null) {
            $cartItems = array_filter($cartItems, function($item) use ($eventIdToFilter) {
                return isset($item['eventId']) && (int) $item['eventId'] === $eventIdToFilter;
            });
        }
        
        $items = $this->formatCartItemsForTemplate($cartItems);

        $orderTotal = 0;
        foreach ($items as $item) {
            $adultPrice = $item['event']['adultPrice'] ?? 0;
            $childPrice = $item['event']['childPrice'] ?? 0;
            $adultTotal = ($item['adultQuantity'] ?? 0) * $adultPrice;
            $childTotal = ($item['childQuantity'] ?? 0) * $childPrice;
            $orderTotal += $adultTotal + $childTotal;
        }

        $serviceFees = 0;
        $paymentDeadline = new \DateTime('+15 minutes');
        $reference = 'CMD-' . date('Ymd') . '-0001';

        return $this->render('ticket/payment.html.twig', [
            'items' => $items,
            'orderTotal' => $orderTotal,
            'serviceFees' => $serviceFees,
            'paymentDeadline' => $paymentDeadline,
            'orderReference' => $reference,
        ]);
    }

    #[Route('/checkout/process', name: 'checkout_process', methods: ['POST'])]
    public function processPayment(Request $request): Response
    {
        error_log('=== DÉBUT TRAITEMENT PAIEMENT ===');
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        // Récupérer les données du formulaire
        $paymentMethod = $request->request->get('payment_method', 'mvola');
        $paymentName = $request->request->get('payment_name', '');
        $paymentEmail = $request->request->get('payment_email', '');
        $paymentPhone = $request->request->get('payment_phone', '');
        $terms = $request->request->get('terms', false);

        if (!$terms) {
            $this->addFlash('error', 'Vous devez accepter les conditions générales d\'utilisation.');
            return $this->redirectToRoute('checkout_payment');
        }

        // Récupérer le panier depuis la session
        $cartItems = $session->get('cart_items', []);
        error_log('Panier initial: ' . count($cartItems) . ' items');
        
        // Si le panier est vide, essayer de le synchroniser avec la DB
        if (empty($cartItems)) {
            $user = $session->get('user');
            $userId = $user && is_array($user) ? ($user['id'] ?? null) : null;
            
            if ($userId) {
                $dbCart = $this->cartSyncService->getOrCreateCart($userId, null);
                if ($dbCart && !empty($dbCart['items'])) {
                    $dbItems = $this->cartSyncService->convertDbItemsToSessionFormat($dbCart['items']);
                    $cartItems = $dbItems;
                    $session->set('cart_items', $cartItems);
                    error_log('Panier récupéré depuis DB: ' . count($cartItems) . ' items');
                }
            }
        }
        
        // Filtrer les items si un eventId est spécifié dans la requête
        $eventParam = $request->query->get('event');
        if ($eventParam) {
            $parts = explode('-', $eventParam);
            $eventIdToFilter = (int) $parts[0];
            error_log('Filtrage par eventId: ' . $eventIdToFilter);
            $cartItemsBeforeFilter = $cartItems;
            $cartItems = array_filter($cartItems, function($item) use ($eventIdToFilter) {
                $itemEventId = isset($item['eventId']) ? (int) $item['eventId'] : 0;
                error_log('Item eventId: ' . $itemEventId . ' vs filter: ' . $eventIdToFilter);
                return $itemEventId === $eventIdToFilter;
            });
            error_log('Panier après filtrage: ' . count($cartItems) . ' items (avant: ' . count($cartItemsBeforeFilter) . ')');
        }

        if (empty($cartItems)) {
            error_log('ERREUR: Panier vide après filtrage');
            error_log('Items du panier avant filtrage: ' . json_encode(array_keys($cartItemsBeforeFilter ?? [])));
            $this->addFlash('error', 'Votre panier est vide. Impossible de procéder au paiement.');
            return $this->redirectToRoute('checkout_payment', $eventParam ? ['event' => $eventParam] : []);
        }

        // Récupérer l'utilisateur connecté
        $user = $session->get('user');
        $userId = $user && is_array($user) ? ($user['id'] ?? null) : null;

        // Préparer les données de paiement
        $paymentData = [
            'payment_method' => $paymentMethod,
            'payment_name' => $paymentName,
            'payment_email' => $paymentEmail,
            'payment_phone' => $paymentPhone,
        ];

        try {
            error_log('Panier items: ' . count($cartItems));
            error_log('User ID: ' . ($userId ?? 'null'));
            
            // Traiter le paiement
            $result = $this->paymentService->processPayment($userId, $cartItems, $paymentData);
            
            error_log('Résultat paiement: ' . json_encode($result));

            if ($result['success']) {
                // Retirer les items payés du panier en session
                $remainingCartItems = $session->get('cart_items', []);
                $cartKeysToRemove = array_keys($cartItems);
                foreach ($cartKeysToRemove as $cartKey) {
                    unset($remainingCartItems[$cartKey]);
                }
                
                // Mettre à jour la session avec les items restants
                $session->set('cart_items', $remainingCartItems);

                // Synchroniser avec la DB pour s'assurer que tout est cohérent
                // Les items ont déjà été retirés de la DB par PaymentService
                // On met simplement à jour la session avec les items restants (qui devraient être vides)
                $session->set('cart_items', $remainingCartItems);
                
                // Si l'utilisateur est connecté, forcer la synchronisation avec la DB
                if ($userId) {
                    // Récupérer le panier actif (un nouveau devrait être créé car l'ancien est "converted")
                    $dbCart = $this->cartSyncService->getOrCreateCart($userId, null);
                    if ($dbCart) {
                        // Sauvegarder les items restants dans le panier DB
                        $this->cartSyncService->saveCartItems((int) $dbCart['id'], $remainingCartItems);
                        // Récupérer les items de la DB pour s'assurer qu'ils sont à jour
                        $dbItems = $this->cartSyncService->convertDbItemsToSessionFormat($dbCart['items']);
                        // Mettre à jour la session avec les items de la DB (devrait être vide)
                        $session->set('cart_items', $dbItems);
                    }
                }

                // Stocker les informations de la commande dans la session pour la page de confirmation
                $session->set('last_order', [
                    'order_id' => $result['order_id'],
                    'total_amount' => $result['total_amount'],
                    'tickets_count' => count($result['tickets']),
                ]);

                $this->addFlash('success', 'Paiement effectué avec succès !');
                return $this->redirectToRoute('checkout_confirmation');
            } else {
                $this->addFlash('error', 'Une erreur est survenue lors du traitement du paiement.');
                return $this->redirectToRoute('checkout_payment');
            }
        } catch (\Exception $e) {
            error_log('=== ERREUR LORS DU TRAITEMENT DU PAIEMENT ===');
            error_log('Message: ' . $e->getMessage());
            error_log('Fichier: ' . $e->getFile() . ':' . $e->getLine());
            error_log('Stack trace: ' . $e->getTraceAsString());
            $this->addFlash('error', 'Une erreur est survenue lors du traitement du paiement. Veuillez réessayer.');
            return $this->redirectToRoute('checkout_payment', $eventParam ? ['event' => $eventParam] : []);
        }
    }

    #[Route('/checkout/confirmation', name: 'checkout_confirmation')]
    public function checkoutConfirmation(Request $request): Response
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $lastOrder = $session->get('last_order');
        
        if (!$lastOrder) {
            $this->addFlash('warning', 'Aucune commande récente trouvée. Si vous venez de payer, votre commande a peut-être été traitée. Vérifiez vos billets.');
            return $this->redirectToRoute('my_tickets');
        }

        // Générer un code de commande lisible
        $orderCode = 'CMD-' . str_pad((string) $lastOrder['order_id'], 6, '0', STR_PAD_LEFT);

        return $this->render('ticket/confirmation.html.twig', [
            'order_id' => $lastOrder['order_id'],
            'order_code' => $orderCode,
            'total_amount' => $lastOrder['total_amount'],
            'tickets_count' => $lastOrder['tickets_count'],
        ]);
    }

    // My tickets
    #[Route('/my-tickets', name: 'my_tickets')]
    public function myTickets(Request $request): Response
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

        // Filtre par statut : upcoming | past | cancelled | all
        $filter = (string) $request->query->get('filter', 'upcoming');
        if (!in_array($filter, ['upcoming', 'past', 'cancelled', 'all'], true)) {
            $filter = 'upcoming';
        }

        $tickets = $this->ticketRepository->findUserTickets($userId, $filter);

        // Compter les billets par statut pour les badges
        $statusCounts = [
            'upcoming' => 0,
            'past' => 0,
            'cancelled' => 0,
        ];

        foreach ($this->ticketRepository->findUserTickets($userId, 'all') as $ticket) {
            $key = $ticket['status_key'] ?? 'upcoming';
            if (isset($statusCounts[$key])) {
                $statusCounts[$key]++;
            }
        }

        return $this->render('ticket/my_tickets.html.twig', [
            'tickets' => $tickets,
            'currentFilter' => $filter,
            'statusCounts' => $statusCounts,
        ]);
    }

    #[Route('/my-tickets/{id}', name: 'my_ticket_details')]
    public function myTicketDetails(int $id): Response
    {
        return $this->render('ticket/my_ticket_details.html.twig', ['id' => $id]);
    }

    #[Route('/api/tickets', name: 'api_tickets_list', methods: ['GET'])]
    public function listUserTickets(): JsonResponse
    {
        // TODO: Récupérer les billets de l'utilisateur connecté
        return new JsonResponse([
            'message' => 'Liste des billets de l\'utilisateur - À implémenter',
            'status' => 'success',
            'data' => []
        ]);
    }


    #[Route('/api/tickets/{id}', name: 'api_tickets_show', methods: ['GET'])]
    public function showTicket(int $id): JsonResponse
    {
        // TODO: Récupérer les détails d'un billet
        return new JsonResponse([
            'message' => "Détails du billet {$id} - À implémenter",
            'status' => 'success',
            'data' => []
        ]);
    }

    #[Route('/api/tickets/{id}/pdf', name: 'api_tickets_pdf', methods: ['GET'])]
    public function downloadTicketPdf(int $id): JsonResponse
    {
        // TODO: Télécharger le PDF du billet
        return new JsonResponse([
            'message' => "Téléchargement PDF du billet {$id} - À implémenter",
            'status' => 'success'
        ]);
    }

    #[Route('/api/tickets/{id}/transfer', name: 'api_tickets_transfer', methods: ['POST'])]
    public function transferTicket(int $id, Request $request): JsonResponse
    {
        // TODO: Transférer un billet à un autre utilisateur
        $email = $request->request->get('email', '');
        
        return new JsonResponse([
            'message' => "Transfert du billet {$id} vers {$email} - À implémenter",
            'status' => 'success'
        ]);
    }

    #[Route('/api/tickets/{id}/validate', name: 'api_tickets_validate', methods: ['POST'])]
    public function validateTicket(int $id): JsonResponse
    {
        // TODO: Valider un billet (pour les organisateurs)
        return new JsonResponse([
            'message' => "Validation du billet {$id} - À implémenter",
            'status' => 'success'
        ]);
    }

    #[Route('/api/tickets/purchase', name: 'api_tickets_purchase', methods: ['POST'])]
    public function purchaseTickets(Request $request): JsonResponse
    {
        // TODO: Acheter des billets
        $eventId = $request->request->get('event_id', '');
        $categoryId = $request->request->get('category_id', '');
        $quantity = $request->request->get('quantity', 1);
        $promoCode = $request->request->get('promo_code', '');

        return new JsonResponse([
            'message' => 'Achat de billets - À implémenter',
            'status' => 'success',
            'purchase_data' => [
                'event_id' => $eventId,
                'category_id' => $categoryId,
                'quantity' => $quantity,
                'promo_code' => $promoCode
            ]
        ]);
    }

    #[Route('/api/tickets/cart', name: 'api_tickets_cart', methods: ['GET'])]
    public function getCart(Request $request): JsonResponse
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $cartItems = $session->get('cart_items', []);
        $items = $this->formatCartItemsForTemplate($cartItems);

        return new JsonResponse([
            'status' => 'success',
            'count' => count($cartItems),
            'items' => $items,
        ]);
    }

    #[Route('/api/tickets/cart/count', name: 'api_tickets_cart_count', methods: ['GET'])]
    public function getCartCount(Request $request): JsonResponse
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        // Récupérer le panier depuis la session
        $cartItems = $session->get('cart_items', []);
        
        // Tenter de synchroniser avec la DB si utilisateur connecté
        $user = $session->get('user');
        $userId = $user && is_array($user) ? ($user['id'] ?? null) : null;
        
        if ($userId) {
            // Récupérer le panier depuis la DB
            $dbCart = $this->cartSyncService->getOrCreateCart($userId, null);
            if ($dbCart && !empty($dbCart['items'])) {
                $dbItems = $this->cartSyncService->convertDbItemsToSessionFormat($dbCart['items']);
                // Fusionner les deux paniers (priorité au plus récent)
                $cartItems = $this->cartSyncService->mergeCarts($cartItems, $dbItems);
                // Mettre à jour la session avec les items fusionnés
                $session->set('cart_items', $cartItems);
                // Sauvegarder dans la DB
                $this->cartSyncService->saveCartItems((int) $dbCart['id'], $cartItems);
            } elseif ($dbCart && !empty($cartItems)) {
                // Sauvegarder les items de la session dans la DB
                $this->cartSyncService->saveCartItems((int) $dbCart['id'], $cartItems);
            }
        }

        return new JsonResponse([
            'status' => 'success',
            'count' => count($cartItems),
        ]);
    }

    #[Route('/api/tickets/cart/sync', name: 'api_tickets_cart_sync', methods: ['POST'])]
    public function syncCart(Request $request): JsonResponse
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $data = json_decode($request->getContent(), true);
        $localStorageItems = $data['items'] ?? [];

        // Récupérer les items de la session
        $sessionItems = $session->get('cart_items', []);

        // Fusionner LocalStorage et Session
        $mergedItems = $this->cartSyncService->mergeCarts($localStorageItems, $sessionItems);

        // Mettre à jour la session
        $session->set('cart_items', $mergedItems);

        // Synchroniser avec la DB si utilisateur connecté
        $user = $session->get('user');
        $userId = $user && is_array($user) ? ($user['id'] ?? null) : null;
        $sessionToken = $session->get('cart_session_token');

        if ($userId) {
            $dbCart = $this->cartSyncService->getOrCreateCart($userId, null);
            if ($dbCart) {
                $this->cartSyncService->saveCartItems((int) $dbCart['id'], $mergedItems);
            }
        } elseif ($sessionToken) {
            $dbCart = $this->cartSyncService->getOrCreateCart(null, $sessionToken);
            if ($dbCart) {
                $this->cartSyncService->saveCartItems((int) $dbCart['id'], $mergedItems);
            }
        }

        // Récupérer les items formatés pour la réponse
        $items = $this->formatCartItemsForTemplate($mergedItems);

        return new JsonResponse([
            'status' => 'success',
            'count' => count($mergedItems),
            'items' => $items,
            'message' => 'Panier synchronisé avec succès.',
        ]);
    }

    #[Route('/api/tickets/cart/load', name: 'api_tickets_cart_load', methods: ['GET'])]
    public function loadCart(Request $request): JsonResponse
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        // Récupérer depuis la session
        $cartItems = $session->get('cart_items', []);

        // Tenter de récupérer depuis la DB
        $user = $session->get('user');
        $userId = $user && is_array($user) ? ($user['id'] ?? null) : null;
        $sessionToken = $session->get('cart_session_token');

        if ($userId) {
            $dbCart = $this->cartSyncService->getOrCreateCart($userId, null);
            if ($dbCart && !empty($dbCart['items'])) {
                $dbItems = $this->cartSyncService->convertDbItemsToSessionFormat($dbCart['items']);
                $cartItems = $this->cartSyncService->mergeCarts($cartItems, $dbItems);
                $session->set('cart_items', $cartItems);
            }
        } elseif ($sessionToken) {
            $dbCart = $this->cartSyncService->getOrCreateCart(null, $sessionToken);
            if ($dbCart && !empty($dbCart['items'])) {
                $dbItems = $this->cartSyncService->convertDbItemsToSessionFormat($dbCart['items']);
                $cartItems = $this->cartSyncService->mergeCarts($cartItems, $dbItems);
                $session->set('cart_items', $cartItems);
            }
        }

        $items = $this->formatCartItemsForTemplate($cartItems);

        return new JsonResponse([
            'status' => 'success',
            'count' => count($cartItems),
            'items' => $items,
        ]);
    }

    #[Route('/api/tickets/cart', name: 'api_tickets_add_to_cart', methods: ['POST'])]
    public function addToCartApi(Request $request): JsonResponse
    {
        // TODO: Ajouter des billets au panier
        return new JsonResponse([
            'message' => 'Ajout au panier - À implémenter',
            'status' => 'success'
        ]);
    }

    #[Route('/api/tickets/cart/{id}', name: 'api_tickets_remove_from_cart', methods: ['DELETE'])]
    public function removeFromCart(int $id): JsonResponse
    {
        // TODO: Retirer des billets du panier
        return new JsonResponse([
            'message' => "Suppression du panier {$id} - À implémenter",
            'status' => 'success'
        ]);
    }

    /**
     * Formate les données du panier pour le template.
     *
     * @param array<string, array<string, mixed>> $cartItems
     * @return array<int, array<string, mixed>>
     */
    private function formatCartItemsForTemplate(array $cartItems): array
    {
        $formattedItems = [];

        foreach ($cartItems as $cartKey => $cartItem) {
            $eventId = $cartItem['eventId'];
            $event = $this->eventRepository->findEventDetailsById($eventId);

            if (null === $event) {
                continue;
            }

            // Construire la location
            $locationParts = [];
            if (!empty($event['venue_name'])) {
                $locationParts[] = $event['venue_name'];
            }
            if (!empty($event['space_name'])) {
                $locationParts[] = $event['space_name'];
            }
            if (!empty($event['city'])) {
                $locationParts[] = $event['city'];
            }
            if (!empty($event['region'])) {
                $locationParts[] = $event['region'];
            }
            $location = !empty($locationParts) ? implode(', ', $locationParts) : 'Lieu non spécifié';

            // Construire l'image
            $image = 'vente-ticket/images/img1.png';
            if (!empty($event['image_url'])) {
                $imageUrl = $event['image_url'];
                if (str_starts_with($imageUrl, 'http')) {
                    $image = $imageUrl;
                } else {
                    $image = $imageUrl;
                }
            }

            // Récupérer la date d'ajout au panier (ou utiliser maintenant si absente pour compatibilité)
            $addedAt = isset($cartItem['added_at']) 
                ? new \DateTimeImmutable($cartItem['added_at'])
                : new \DateTimeImmutable();
            
            // Récupérer la date de l'événement
            $eventDate = $event['starts_at'] instanceof \DateTimeImmutable 
                ? $event['starts_at']
                : ($event['starts_at'] ? new \DateTimeImmutable($event['starts_at']) : new \DateTimeImmutable());

            // Récupérer les prix depuis les ticket_types si absents
            $adultPrice = $cartItem['adultPrice'] ?? null;
            $childPrice = $cartItem['childPrice'] ?? null;
            
            // Si les prix sont absents ou 0, les récupérer depuis les ticket_types
            if (($adultPrice === null || $adultPrice === 0) && isset($cartItem['adultTicketTypeId'])) {
                $adultPrice = $this->ticketRepository->findTicketTypePrice($cartItem['adultTicketTypeId']);
            }
            if (($childPrice === null || $childPrice === 0) && isset($cartItem['childTicketTypeId'])) {
                $childPrice = $this->ticketRepository->findTicketTypePrice($cartItem['childTicketTypeId']);
            }
            
            // Si toujours null, utiliser le prix du ticket_type principal
            if (($adultPrice === null || $adultPrice === 0) && isset($cartItem['ticketTypeId'])) {
                $adultPrice = $this->ticketRepository->findTicketTypePrice($cartItem['ticketTypeId']);
            }
            if (($childPrice === null || $childPrice === 0) && isset($cartItem['ticketTypeId'])) {
                $childPrice = $this->ticketRepository->findTicketTypePrice($cartItem['ticketTypeId']);
            }
            
            $formattedItems[] = [
                'cart_key' => $cartKey,
                'event' => [
                    'id' => $eventId,
                    'image' => $image,
                    'title' => $event['title'],
                    'category' => $event['category_label'] ?? 'Événement',
                    'location' => $location,
                    'date' => $eventDate instanceof \DateTimeImmutable 
                        ? \DateTime::createFromImmutable($eventDate)
                        : ($eventDate ?? new \DateTime()),
                    'starts_at' => $eventDate,
                    'added_at' => $addedAt,
                    'adultPrice' => $adultPrice ?? 0,
                    'childPrice' => $childPrice ?? 0,
                ],
                'adultQuantity' => $cartItem['adultQuantity'] ?? 0,
                'childQuantity' => $cartItem['childQuantity'] ?? 0,
            ];
        }

        return $formattedItems;
    }

}

