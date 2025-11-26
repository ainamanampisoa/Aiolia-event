<?php

namespace App\Controller;

use App\Service\CartSyncService;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TicketController extends AbstractController
{
    public function __construct(
        private readonly Connection $connection,
        private readonly CartSyncService $cartSyncService
    ) {
    }

    #[Route('/cart', name: 'cart')]
    public function cart(Request $request): Response
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
        $event = $this->fetchEventDetails($eventId);
        if (null === $event) {
            $this->addFlash('error', 'Événement introuvable.');
            return $this->redirectToRoute('events');
        }

        // Récupérer les types de billets
        $ticketTypes = $this->fetchTicketTypes($eventId);
        
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

    #[Route('/checkout/confirmation', name: 'checkout_confirmation')]
    public function checkoutConfirmation(): Response
    {
        return $this->render('ticket/confirmation.html.twig');
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

        $tickets = $this->fetchUserTickets($userId, $filter);

        // Compter les billets par statut pour les badges
        $statusCounts = [
            'upcoming' => 0,
            'past' => 0,
            'cancelled' => 0,
        ];

        foreach ($this->fetchUserTickets($userId, 'all') as $ticket) {
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

    /**
     * Récupère les billets de l'utilisateur avec leur événement associé.
     *
     * @param int $userId
     * @param string $filter Statut à filtrer : upcoming | past | cancelled | all
     * @return array<int, array<string, mixed>>
     */
    private function fetchUserTickets(int $userId, string $filter = 'upcoming'): array
    {
        $sql = <<<SQL
            SELECT
                t.id AS ticket_id,
                t.status AS ticket_status,
                e.id AS event_id,
                e.title AS event_title,
                COALESCE(e.location_override->>'venue_name', v.name) AS venue_name,
                COALESCE(e.location_override->>'address', NULLIF(CONCAT_WS(', ', v.address_line1, v.address_line2), '')) AS venue_address,
                COALESCE(e.location_override->>'city', v.city) AS city,
                COALESCE(e.location_override->>'region', v.region) AS region,
                COALESCE(e.location_override->>'country', v.country_code) AS country_code,
                COALESCE(primary_cat.label, cat.label) AS category_label,
                COALESCE(media.url, e.cover_image_url) AS image_url,
                e.starts_at,
                e.ends_at,
                tt.name AS ticket_type,
                tt.age_category,
                tt.base_price,
                o.id AS order_id,
                o.created_at AS order_created_at
            FROM aiolia.tickets t
            INNER JOIN aiolia.order_items oi ON oi.id = t.order_item_id
            INNER JOIN aiolia.orders o ON o.id = oi.order_id
            INNER JOIN aiolia.ticket_types tt ON tt.id = t.ticket_type_id
            INNER JOIN aiolia.events e ON e.id = tt.event_id
            LEFT JOIN aiolia.venues v ON v.id = e.venue_id
            LEFT JOIN aiolia.event_categories primary_cat ON primary_cat.id = e.primary_category_id
            LEFT JOIN LATERAL (
                SELECT c.label
                FROM aiolia.event_category_links cl
                JOIN aiolia.event_categories c ON c.id = cl.category_id
                WHERE cl.event_id = e.id
                ORDER BY c.display_order ASC, c.label ASC
                LIMIT 1
            ) AS cat ON TRUE
            LEFT JOIN LATERAL (
                SELECT m.url
                FROM aiolia.event_media m
                WHERE m.event_id = e.id
                  AND m.is_public IS TRUE
                ORDER BY m.display_order ASC, m.id ASC
                LIMIT 1
            ) AS media ON TRUE
            WHERE o.user_id = :user_id
              AND o.status = 'paid'
        SQL;

        $params = ['user_id' => $userId];

        // Filtrage par statut "fonctionnel" (à venir / passé / annulé)
        if ($filter === 'upcoming') {
            $sql .= " AND t.status = 'valid' AND e.starts_at >= NOW()";
        } elseif ($filter === 'past') {
            $sql .= " AND e.starts_at < NOW()";
        } elseif ($filter === 'cancelled') {
            $sql .= " AND t.status IN ('cancelled', 'refunded')";
        }

        $sql .= ' ORDER BY e.starts_at DESC, t.id DESC';

        $rows = $this->connection->executeQuery($sql, $params)->fetchAllAssociative();

        return array_map(static function (array $row): array {
            $eventDate = isset($row['starts_at']) ? new \DateTimeImmutable($row['starts_at']) : null;
            $orderDate = isset($row['order_created_at']) ? new \DateTimeImmutable($row['order_created_at']) : null;

            // Générer un code de commande lisible à partir de l'ID
            $orderCode = null;
            if (isset($row['order_id']) && null !== $row['order_id']) {
                $orderCode = 'CMD-' . str_pad((string) $row['order_id'], 6, '0', STR_PAD_LEFT);
            }

            // Déterminer le statut UX
            $statusKey = 'upcoming';
            if (in_array($row['ticket_status'], ['cancelled', 'refunded'], true)) {
                $statusKey = 'cancelled';
            } elseif ($eventDate && $eventDate < new \DateTimeImmutable()) {
                $statusKey = 'past';
            }

            $statusLabel = match ($statusKey) {
                'upcoming' => 'À venir',
                'past' => 'Passé',
                'cancelled' => 'Annulé',
                default => ucfirst((string) $row['ticket_status']),
            };

            // Construire la localisation lisible
            $locationParts = [];
            if (!empty($row['venue_name'])) {
                $locationParts[] = $row['venue_name'];
            }
            if (!empty($row['city'])) {
                $locationParts[] = $row['city'];
            }
            if (!empty($row['region'])) {
                $locationParts[] = $row['region'];
            }
            $location = !empty($locationParts) ? implode(', ', $locationParts) : 'Lieu à confirmer';

            return [
                'id' => (int) $row['ticket_id'],
                'status_key' => $statusKey,
                'status_label' => $statusLabel,
                'ticket_type' => $row['ticket_type'],
                'age_category' => $row['age_category'],
                'price' => isset($row['base_price']) ? (float) $row['base_price'] : null,
                'order_number' => $orderCode,
                'order_date' => $orderDate,
                'event' => [
                    'id' => (int) $row['event_id'],
                    'title' => $row['event_title'],
                    'category' => $row['category_label'] ?? 'Évènement',
                    'image' => $row['image_url'] ?: 'vente-ticket/images/img1.png',
                    'location' => $location,
                    'date' => $eventDate,
                ],
            ];
        }, $rows);
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
            $event = $this->fetchEventDetails($eventId);

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
                $adultPrice = $this->getTicketTypePrice($cartItem['adultTicketTypeId']);
            }
            if (($childPrice === null || $childPrice === 0) && isset($cartItem['childTicketTypeId'])) {
                $childPrice = $this->getTicketTypePrice($cartItem['childTicketTypeId']);
            }
            
            // Si toujours null, utiliser le prix du ticket_type principal
            if (($adultPrice === null || $adultPrice === 0) && isset($cartItem['ticketTypeId'])) {
                $adultPrice = $this->getTicketTypePrice($cartItem['ticketTypeId']);
            }
            if (($childPrice === null || $childPrice === 0) && isset($cartItem['ticketTypeId'])) {
                $childPrice = $this->getTicketTypePrice($cartItem['ticketTypeId']);
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

    /**
     * @return array<string, mixed>|null
     */
    private function fetchEventDetails(int $id): ?array
    {
        $sql = <<<SQL
            SELECT
                e.id,
                e.slug,
                e.title,
                e.subtitle,
                e.summary,
                e.description,
                e.event_format,
                e.timezone,
                e.starts_at,
                e.ends_at,
                e.sales_starts_at,
                e.sales_ends_at,
                e.capacity,
                e.language_code,
                e.location_override,
                e.created_at,
                COALESCE(primary_cat.label, cat.label) AS category_label,
                COALESCE(primary_cat.slug, cat.slug) AS category_slug,
                COALESCE(media.url, e.cover_image_url) AS image_url,
                media.alt_text AS image_alt,
                pricing.min_price,
                pricing.max_price,
                v.name AS venue_name_fallback,
                v.address_line1,
                v.address_line2,
                v.city AS venue_city,
                v.region AS venue_region,
                v.country_code AS venue_country,
                v.latitude,
                v.longitude,
                v.capacity AS venue_capacity,
                vs.name AS space_name
            FROM aiolia.events e
            LEFT JOIN aiolia.venues v ON v.id = e.venue_id
            LEFT JOIN aiolia.venue_spaces vs ON vs.id = e.main_space_id
            LEFT JOIN aiolia.event_categories primary_cat ON primary_cat.id = e.primary_category_id
            LEFT JOIN LATERAL (
                SELECT c.slug, c.label
                FROM aiolia.event_category_links cl
                JOIN aiolia.event_categories c ON c.id = cl.category_id
                WHERE cl.event_id = e.id
                ORDER BY c.display_order ASC, c.label ASC
                LIMIT 1
            ) AS cat ON TRUE
            LEFT JOIN LATERAL (
                SELECT m.url, m.alt_text
                FROM aiolia.event_media m
                WHERE m.event_id = e.id
                  AND m.is_public IS TRUE
                ORDER BY m.display_order ASC, m.id ASC
                LIMIT 1
            ) AS media ON TRUE
            LEFT JOIN LATERAL (
                SELECT
                    MIN(tt.base_price) AS min_price,
                    MAX(tt.base_price) AS max_price
                FROM aiolia.ticket_types tt
                WHERE tt.event_id = e.id
            ) AS pricing ON TRUE
            WHERE e.id = :id
            LIMIT 1
        SQL;

        $row = $this->connection->executeQuery($sql, ['id' => $id])->fetchAssociative();

        if (false === $row) {
            return null;
        }

        $startsAt = isset($row['starts_at']) ? new \DateTimeImmutable($row['starts_at']) : null;
        $endsAt = isset($row['ends_at']) ? new \DateTimeImmutable($row['ends_at']) : null;
        $salesStartsAt = isset($row['sales_starts_at']) ? new \DateTimeImmutable($row['sales_starts_at']) : null;
        $salesEndsAt = isset($row['sales_ends_at']) ? new \DateTimeImmutable($row['sales_ends_at']) : null;
        $createdAt = isset($row['created_at']) ? new \DateTimeImmutable($row['created_at']) : null;

        $override = [];
        if (!empty($row['location_override'])) {
            $decoded = json_decode($row['location_override'], true);
            if (is_array($decoded)) {
                $override = $decoded;
            }
        }

        $venueName = $override['venue_name'] ?? $row['venue_name_fallback'];
        $venueAddress = $override['address'] ?? trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([$row['address_line1'], $row['address_line2']]))));
        if ('' === $venueAddress) {
            $venueAddress = null;
        }
        $city = $override['city'] ?? $row['venue_city'];
        $region = $override['region'] ?? $row['venue_region'];
        $countryCode = $override['country'] ?? $row['venue_country'];
        $latitude = isset($override['latitude'])
            ? (float) $override['latitude']
            : (isset($row['latitude']) ? (float) $row['latitude'] : null);
        $longitude = isset($override['longitude'])
            ? (float) $override['longitude']
            : (isset($row['longitude']) ? (float) $row['longitude'] : null);

        return [
            'id' => (int) $row['id'],
            'slug' => $row['slug'],
            'title' => $row['title'],
            'subtitle' => $row['subtitle'],
            'summary' => $row['summary'],
            'description' => $row['description'] ?? $row['summary'],
            'event_format' => $row['event_format'],
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'sales_starts_at' => $salesStartsAt,
            'sales_ends_at' => $salesEndsAt,
            'capacity' => isset($row['capacity']) ? (int) $row['capacity'] : null,
            'language_code' => $row['language_code'],
            'timezone' => $row['timezone'],
            'created_at' => $createdAt,
            'category_label' => $row['category_label'] ?? 'Évènement',
            'category_slug' => $row['category_slug'],
            'image_url' => $row['image_url'],
            'image_alt' => $row['image_alt'] ?? $row['title'],
            'min_price' => null !== $row['min_price'] ? (float) $row['min_price'] : null,
            'max_price' => null !== $row['max_price'] ? (float) $row['max_price'] : null,
            'venue_name' => $venueName,
            'venue_address' => $venueAddress,
            'city' => $city,
            'region' => $region,
            'country_code' => $countryCode,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'space_name' => $row['space_name'],
            'venue_capacity' => isset($row['venue_capacity']) ? (int) $row['venue_capacity'] : null,
            'venue_raw' => [
                'address_line1' => $row['address_line1'],
                'address_line2' => $row['address_line2'],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchTicketTypes(int $eventId): array
    {
        $sql = <<<SQL
            SELECT
                tt.id,
                tt.name,
                tt.description,
                tt.currency,
                tt.base_price,
                tt.service_fee,
                tt.vat_rate,
                tt.age_category,
                tt.min_per_order,
                tt.max_per_order,
                tt.metadata,
                inv.total_quantity,
                inv.reserved_quantity,
                inv.sold_quantity
            FROM aiolia.ticket_types tt
            LEFT JOIN aiolia.ticket_inventory inv ON inv.ticket_type_id = tt.id
            WHERE tt.event_id = :event_id
            ORDER BY 
                CASE tt.age_category 
                    WHEN 'adult' THEN 1 
                    WHEN 'child' THEN 2 
                    WHEN 'all' THEN 3 
                    ELSE 4 
                END,
                tt.base_price ASC NULLS LAST, 
                tt.name ASC
        SQL;

        $rows = $this->connection->executeQuery($sql, ['event_id' => $eventId])->fetchAllAssociative();

        return array_map(static function (array $row): array {
            $total = isset($row['total_quantity']) ? (int) $row['total_quantity'] : null;
            $sold = isset($row['sold_quantity']) ? (int) $row['sold_quantity'] : 0;
            $reserved = isset($row['reserved_quantity']) ? (int) $row['reserved_quantity'] : 0;
            $available = null;
            if (null !== $total) {
                $available = max($total - $sold - $reserved, 0);
            }

            $metadata = null;
            if (!empty($row['metadata'])) {
                $decoded = json_decode($row['metadata'], true);
                if (is_array($decoded)) {
                    $metadata = $decoded;
                }
            }

            return [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'description' => $row['description'],
                'currency' => $row['currency'],
                'base_price' => (float) $row['base_price'],
                'service_fee' => isset($row['service_fee']) ? (float) $row['service_fee'] : null,
                'vat_rate' => isset($row['vat_rate']) ? (float) $row['vat_rate'] : null,
                'age_category' => $row['age_category'] ?? 'all',
                'metadata' => $metadata,
                'min_per_order' => isset($row['min_per_order']) ? (int) $row['min_per_order'] : null,
                'max_per_order' => isset($row['max_per_order']) ? (int) $row['max_per_order'] : null,
                'available' => $available,
                'total_quantity' => $total,
                'sold_quantity' => $sold,
                'reserved_quantity' => $reserved,
                'is_available' => null === $available || $available > 0,
            ];
        }, $rows);
    }

    /**
     * Récupère le prix d'un type de billet depuis la base de données.
     */
    private function getTicketTypePrice(int $ticketTypeId): ?float
    {
        try {
            $sql = 'SELECT base_price FROM aiolia.ticket_types WHERE id = :ticket_type_id LIMIT 1';
            $result = $this->connection->executeQuery($sql, ['ticket_type_id' => $ticketTypeId])->fetchAssociative();
            
            if ($result && isset($result['base_price'])) {
                return (float) $result['base_price'];
            }
            
            return null;
        } catch (\Exception $e) {
            error_log('Erreur lors de la récupération du prix du type de billet: ' . $e->getMessage());
            return null;
        }
    }
}

