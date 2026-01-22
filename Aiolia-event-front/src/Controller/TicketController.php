<?php

namespace App\Controller;

use App\Repository\EventRepository;
use App\Repository\TicketRepository;
use App\Service\ActivityService;
use App\Service\CartSyncService;
use App\Service\PaymentService;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\Routing\Annotation\Route;

class TicketController extends AbstractController
{
    public function __construct(
        private readonly TicketRepository $ticketRepository,
        private readonly EventRepository $eventRepository,
        private readonly CartSyncService $cartSyncService,
        private readonly PaymentService $paymentService,
        private readonly ActivityService $activityService,
        private readonly Connection $connection
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
                if (!empty($dbItems)) {
                    // La DB est la source de vérité
                    $cartItems = $dbItems;
                    $session->set('cart_items', $cartItems);
                } else {
                    // La DB est vide : soit le panier est vraiment vide, soit les items ont été payés
                    // Dans ce cas, vider aussi la session pour éviter de restaurer des items payés
                    $session->set('cart_items', []);
                    $cartItems = [];
                }
            } else {
                // Pas de panier DB, vider la session
                $session->set('cart_items', []);
                $cartItems = [];
            }
        }

        $items = $this->formatCartItemsForTemplate($cartItems);

        // Récupérer les codes promo appliqués par événement depuis la session
        $appliedPromoCodes = $session->get('applied_promo_codes', []);

        return $this->render('ticket/cart.html.twig', [
            'items' => $items,
            'appliedPromoCodes' => $appliedPromoCodes,
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
            // Récupérer l'eventId avant de supprimer
            $eventId = $cartItems[$cartKey]['eventId'] ?? null;

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

                // Logger l'activité de suppression
                if ($eventId) {
                    $this->activityService->logCartRemoval($userId, (int) $eventId);
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
            $cartItems = array_filter($cartItems, function ($item) use ($eventIdToFilter) {
                return isset($item['eventId']) && (int) $item['eventId'] === $eventIdToFilter;
            });
        }

        $items = $this->formatCartItemsForTemplate($cartItems);

        // Récupérer les codes promo appliqués par événement
        $appliedPromoCodes = $session->get('applied_promo_codes', []);
        
        $orderTotal = 0;
        $discountAmount = 0;
        $promoCodes = [];
        
        // Calculer le total et la réduction par événement
        foreach ($items as $index => $item) {
            $adultPrice = $item['event']['adultPrice'] ?? 0;
            $childPrice = $item['event']['childPrice'] ?? 0;
            $adultTotal = ($item['adultQuantity'] ?? 0) * $adultPrice;
            $childTotal = ($item['childQuantity'] ?? 0) * $childPrice;
            $itemTotal = $adultTotal + $childTotal;
            $orderTotal += $itemTotal;
            
            // Vérifier si cet événement a un code promo appliqué
            $eventKey = ($item['event']['id'] ?? '') . '-' . $index;
            if (isset($appliedPromoCodes[$eventKey])) {
                $promo = $appliedPromoCodes[$eventKey];
                $discountAmount += (float) ($promo['discount_amount'] ?? 0);
                if (!empty($promo['code'])) {
                    $promoCodes[] = $promo['code'];
                }
            }
        }
        
        // Si un eventId est filtré, utiliser uniquement le code promo de cet événement
        if ($eventIdToFilter !== null) {
            $discountAmount = 0;
            $promoCodes = [];
            foreach ($items as $index => $item) {
                $eventKey = ($item['event']['id'] ?? '') . '-' . $index;
                if (isset($appliedPromoCodes[$eventKey]) && ($item['event']['id'] ?? null) === $eventIdToFilter) {
                    $promo = $appliedPromoCodes[$eventKey];
                    $discountAmount = (float) ($promo['discount_amount'] ?? 0);
                    if (!empty($promo['code'])) {
                        $promoCodes = [$promo['code']];
                    }
                    break;
                }
            }
        }
        
        $promoCode = !empty($promoCodes) ? implode(', ', $promoCodes) : null;

        $serviceFees = 0;
        $subtotal = $orderTotal;
        $orderTotal = max(0, $orderTotal - $discountAmount);
        $paymentDeadline = new \DateTime('+15 minutes');
        $reference = 'CMD-' . date('Ymd') . '-0001';

        return $this->render('ticket/payment.html.twig', [
            'items' => $items,
            'subtotal' => $subtotal,
            'orderTotal' => $orderTotal,
            'discountAmount' => $discountAmount,
            'promoCode' => $promoCode,
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
        
        // Récupérer les codes promo depuis la session (appliqués dans le panier) ou depuis le formulaire
        $appliedPromoCodes = $session->get('applied_promo_codes', []);
        $promoCodesList = [];
        foreach ($appliedPromoCodes as $promo) {
            if (!empty($promo['code'])) {
                $promoCodesList[] = $promo['code'];
            }
        }
        $promoCode = !empty($promoCodesList) ? implode(', ', $promoCodesList) : trim($request->request->get('promo_code', ''));
        
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
            $cartItems = array_filter($cartItems, function ($item) use ($eventIdToFilter) {
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
            'promo_code' => !empty($promoCode) ? $promoCode : null,
        ];

        try {
            error_log('Panier items: ' . count($cartItems));
            error_log('User ID: ' . ($userId ?? 'null'));

            // Traiter le paiement
            $result = $this->paymentService->processPayment($userId, $cartItems, $paymentData);

            error_log('Résultat paiement: ' . json_encode($result));

            if ($result['success']) {
                // Retirer les items payés du panier en session IMMÉDIATEMENT
                $remainingCartItems = $session->get('cart_items', []);
                $cartKeysToRemove = array_keys($cartItems);
                foreach ($cartKeysToRemove as $cartKey) {
                    unset($remainingCartItems[$cartKey]);
                }

                // Mettre à jour la session avec les items restants
                $session->set('cart_items', $remainingCartItems);

                // Si l'utilisateur est connecté, forcer la synchronisation avec la DB
                if ($userId) {
                    // Récupérer le panier actif depuis la DB
                    // Les items payés ont déjà été retirés de la DB par PaymentService
                    $dbCart = $this->cartSyncService->getOrCreateCart($userId, null);
                    if ($dbCart) {
                        // Récupérer les items de la DB (source de vérité)
                        $dbItems = $this->cartSyncService->convertDbItemsToSessionFormat($dbCart['items']);

                        // Utiliser la DB comme source de vérité
                        // Si la DB est vide, vider aussi la session
                        // Si la DB a des items, synchroniser la session avec la DB
                        $session->set('cart_items', $dbItems);
                    } else {
                        // Pas de panier DB, vider la session
                        $session->set('cart_items', []);
                    }
                }

                // Calculer le nombre de tickets depuis les items du panier (avant paiement)
                // Car pour MVola, les tickets ne sont créés qu'après le callback
                $ticketsCount = 0;
                foreach ($cartItems as $item) {
                    $ticketsCount += (int) ($item['adultQuantity'] ?? 0);
                    $ticketsCount += (int) ($item['childQuantity'] ?? 0);
                }

                // Stocker les informations de la commande dans la session pour la page de confirmation
                $session->set('last_order', [
                    'order_id' => $result['order_id'],
                    'total_amount' => $result['total_amount'],
                    'tickets_count' => $ticketsCount > 0 ? $ticketsCount : count($result['tickets'] ?? []),
                ]);

                $this->addFlash('success', 'Paiement effectué avec succès !');
                return $this->redirectToRoute('checkout_confirmation');
            } else {
                // Afficher l'erreur détaillée pour le debug
                $errorMessage = $result['error'] ?? 'Une erreur est survenue lors du traitement du paiement.';

                // Log complet
                $logOutput = "\n" . str_repeat('=', 80) . "\n";
                $logOutput .= "=== ERREUR PAIEMENT MVOLA ===\n";
                $logOutput .= "Erreur: " . $errorMessage . "\n";
                if (isset($result['missing_field'])) {
                    $logOutput .= "Champ manquant: " . $result['missing_field'] . "\n";
                }
                if (isset($result['raw_response'])) {
                    $logOutput .= "Réponse API complète:\n" . json_encode($result['raw_response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
                }
                if (isset($result['payload_sent'])) {
                    $logOutput .= "Payload envoyé:\n" . json_encode($result['payload_sent'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
                }
                $logOutput .= "Résultat complet:\n" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
                $logOutput .= str_repeat('=', 80) . "\n";

                error_log($logOutput);
                file_put_contents('php://stderr', $logOutput);

                // Écrire aussi dans un fichier dédié pour faciliter l'accès
                $logFile = sys_get_temp_dir() . '/mvola_debug.log';
                @file_put_contents($logFile, date('Y-m-d H:i:s') . "\n" . $logOutput . "\n", FILE_APPEND);

                // En mode dev, afficher plus de détails dans le message
                if (($_ENV['APP_ENV'] ?? 'dev') === 'dev') {
                    $detailedError = $errorMessage;
                    if (isset($result['missing_field'])) {
                        $detailedError .= ' - Champ manquant: ' . $result['missing_field'];
                    }
                    if (isset($result['raw_response'])) {
                        $detailedError .= ' - Réponse: ' . json_encode($result['raw_response'], JSON_UNESCAPED_UNICODE);
                    }
                    $this->addFlash('error', '[DEV] ' . $detailedError);
                } else {
                    $this->addFlash('error', 'Une erreur est survenue lors du traitement du paiement.');
                }
                return $this->redirectToRoute('checkout_payment');
            }
        } catch (\Exception $e) {
            error_log('=== ERREUR LORS DU TRAITEMENT DU PAIEMENT ===');
            error_log('Message: ' . $e->getMessage());
            error_log('Fichier: ' . $e->getFile() . ':' . $e->getLine());
            error_log('Stack trace: ' . $e->getTraceAsString());

            // Message générique pour l'utilisateur
            $this->addFlash('error', 'Une erreur est survenue lors du traitement du paiement. Veuillez réessayer.');

            // Message détaillé pour le debug en environnement de dev
            if ($_ENV['APP_ENV'] ?? 'dev' === 'dev') {
                $this->addFlash('error', sprintf('[DEV] %s', $e->getMessage()));
            }

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

    #[Route('/my-tickets/{id}/pdf', name: 'ticket_pdf', methods: ['GET'])]
    public function downloadTicketPdf(int $id, Request $request): Response
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

        $ticket = $this->ticketRepository->findUserTicketById($userId, $id);

        if (!$ticket) {
            throw $this->createNotFoundException('Billet introuvable ou non accessible.');
        }

        // 1. Générer le QR Code en local (plus fiable)
        try {
            $qrData = $ticket['qr_code'] ?? (string) $ticket['id'];
            $result = (new \Endroid\QrCode\Builder\Builder(
                writer: new \Endroid\QrCode\Writer\PngWriter(),
                writerOptions: [],
                validateResult: false,
                data: $qrData,
                encoding: new \Endroid\QrCode\Encoding\Encoding('UTF-8'),
                errorCorrectionLevel: \Endroid\QrCode\ErrorCorrectionLevel::High,
                size: 200,
                margin: 10
            ))->build();
            $qrCodeBase64 = $result->getDataUri();
        } catch (\Exception $e) {
            error_log('Erreur génération QR Code: ' . $e->getMessage());
            $qrCodeBase64 = null;
        }

        // 2. Gestion de l'image de l'événement avec logs de debug
        $eventImage = $ticket['event']['image'] ?? null;
        $eventImageBase64 = null;

        error_log("--- DEBUG PDF IMAGE ---");
        error_log("Event Image Raw: " . ($eventImage ?? 'NULL'));

        if ($eventImage) {
            if (str_starts_with($eventImage, 'http')) {
                // Image distante
                error_log("Type: Distante");
                $eventImageBase64 = $this->imageToBase64($eventImage);
            } else {
                // Image locale
                $projectDir = $this->getParameter('kernel.project_dir');
                // Enlever le slash initial si présent pour éviter //
                $cleanPath = ltrim($eventImage, '/');
                $localPath = $projectDir . '/public/' . $cleanPath;

                error_log("Type: Locale");
                error_log("Chemin construit: " . $localPath);

                if (file_exists($localPath)) {
                    error_log("Fichier existe: OUI");
                    $eventImageBase64 = $this->imageToBase64($localPath);
                } else {
                    error_log("Fichier existe: NON");
                    // Essayer sans 'public/' si jamais le chemin en BDD inclut déjà 'public' (cas rare mais possible)
                    $altPath = $projectDir . '/' . $cleanPath;
                    error_log("Essai chemin alternatif: " . $altPath);
                    if (file_exists($altPath)) {
                        error_log("Fichier alternatif existe: OUI");
                        $eventImageBase64 = $this->imageToBase64($altPath);
                    }
                }
            }
        }

        if ($eventImageBase64) {
            error_log("Conversion Base64: SUCCES (longueur: " . strlen($eventImageBase64) . ")");
        } else {
            error_log("Conversion Base64: ECHEC ou NULL");
        }

        // Fallback
        if (!$eventImageBase64) {
            $defaultPath = $this->getParameter('kernel.project_dir') . '/public/vente-ticket/images/img1.png';
            error_log("Utilisation image fallback: " . $defaultPath);
            $eventImageBase64 = $this->imageToBase64($defaultPath);
        }

        $html = $this->renderView('ticket/pdf.html.twig', [
            'ticket' => $ticket,
            'public_dir' => $this->getParameter('kernel.project_dir') . '/public',
            'qrCodeBase64' => $qrCodeBase64,
            'eventImageBase64' => $eventImageBase64,
        ]);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = sprintf('Billet_%d_%s.pdf', $ticket['id'], date('Y-m-d'));

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Helper pour convertir une image (URL ou chemin local) en Base64
     */
    private function imageToBase64(string $pathOrUrl): ?string
    {
        try {
            $data = @file_get_contents($pathOrUrl);

            if ($data === false) {
                error_log("imageToBase64: file_get_contents a échoué pour " . $pathOrUrl);
                return null;
            }

            $ext = pathinfo($pathOrUrl, PATHINFO_EXTENSION);
            if (empty($ext)) {
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->buffer($data);
            } else {
                $mimeType = match (strtolower($ext)) {
                    'jpg', 'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    'svg' => 'image/svg+xml',
                    'webp' => 'image/webp',
                    default => 'image/png'
                };
            }

            error_log("imageToBase64: MIME type détecté: " . $mimeType);

            $base64 = base64_encode($data);
            return 'data:' . $mimeType . ';base64,' . $base64;
        } catch (\Exception $e) {
            error_log('Erreur conversion image Base64 pour PDF: ' . $e->getMessage());
            return null;
        }
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

    #[Route('/api/tickets/promo/validate', name: 'api_tickets_validate_promo', methods: ['POST', 'OPTIONS'])]
    public function validatePromoCode(Request $request): JsonResponse
    {
        // Initialiser le logging dès le début - AVANT TOUT
        $logDir = dirname(__DIR__, 2) . '/var/log';
        @mkdir($logDir, 0755, true);
        $logFile = $logDir . '/promo_validation.log';
        $isDev = ($_ENV['APP_ENV'] ?? 'dev') === 'dev';
        
        // Écrire immédiatement dans le fichier de log
        $logEntry = date('Y-m-d H:i:s') . " - ===== MÉTHODE APPELÉE =====\n";
        $logEntry .= "Method: " . $request->getMethod() . "\n";
        $logEntry .= "URI: " . $request->getRequestUri() . "\n";
        $logEntry .= "Content-Type: " . $request->headers->get('Content-Type', 'N/A') . "\n";
        @file_put_contents($logFile, $logEntry, FILE_APPEND);
        error_log('=== VALIDATE PROMO CODE CALLED === Method: ' . $request->getMethod());
        
        // Gérer les requêtes OPTIONS (preflight CORS)
        if ($request->getMethod() === 'OPTIONS') {
            @file_put_contents($logFile, date('Y-m-d H:i:s') . " - Requête OPTIONS (preflight CORS)\n", FILE_APPEND);
            return new JsonResponse([], 200, [
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'POST, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, X-Requested-With',
                'Access-Control-Max-Age' => '3600'
            ]);
        }
        
        try {
            $session = $request->getSession();
            if (!$session->isStarted()) {
                $session->start();
            }
            @file_put_contents($logFile, date('Y-m-d H:i:s') . " - Session initialisée\n", FILE_APPEND);
        } catch (\Exception $e) {
            $errorMsg = date('Y-m-d H:i:s') . " - ERREUR initialisation session: " . $e->getMessage() . "\n";
            $errorMsg .= "Trace: " . $e->getTraceAsString() . "\n";
            @file_put_contents($logFile, $errorMsg, FILE_APPEND);
            error_log('Erreur initialisation session: ' . $e->getMessage());
            return new JsonResponse([
                'success' => false,
                'error' => 'Erreur lors de l\'initialisation de la session',
                'debug' => $isDev ? $e->getMessage() : null
            ], 500);
        }

        $code = trim($request->request->get('code', ''));
        $eventId = trim($request->request->get('event_id', ''));
        @file_put_contents($logFile, date('Y-m-d H:i:s') . " - Code reçu: " . ($code ?: 'vide') . " - EventId: " . ($eventId ?: 'vide') . "\n", FILE_APPEND);
        
        if (empty($code)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Code promo requis'
            ], 400);
        }
        
        if (empty($eventId)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'ID événement requis'
            ], 400);
        }

        // Récupérer l'utilisateur (optionnel pour la validation)
        $user = $session->get('user');
        $userId = null;
        if ($user) {
            if (is_array($user)) {
                $userId = $user['id'] ?? null;
            } elseif (is_numeric($user)) {
                $userId = (int) $user;
            } elseif (is_object($user) && method_exists($user, 'getId')) {
                $userId = $user->getId();
            }
        }
        
        // Log pour debug
        $logMessage = date('Y-m-d H:i:s') . ' - User session: ' . json_encode($user) . ' - Extracted userId: ' . ($userId ?? 'null') . "\n";
        @file_put_contents($logFile, $logMessage, FILE_APPEND);
        error_log('User session data: ' . json_encode($user));
        error_log('Extracted userId: ' . ($userId ?? 'null'));

        // Récupérer les items du panier et trouver l'événement spécifique
        $cartItems = $session->get('cart_items', []);
        
        // Extraire l'eventId du format "eventId-loopIndex" ou juste "eventId"
        $eventIdParts = explode('-', $eventId);
        $targetEventId = (int) $eventIdParts[0];
        
        // Trouver l'item correspondant à cet événement
        $targetItem = null;
        foreach ($cartItems as $item) {
            // Récupérer l'eventId de l'item
            $itemEventId = null;
            if (isset($item['ticketTypeId'])) {
                // Récupérer l'eventId depuis ticket_types
                try {
                    $ticketType = $this->connection->executeQuery(
                        'SELECT event_id FROM aiolia.ticket_types WHERE id = :id',
                        ['id' => $item['ticketTypeId']]
                    )->fetchAssociative();
                    $itemEventId = $ticketType ? (int) $ticketType['event_id'] : null;
                } catch (\Exception $e) {
                    error_log('Erreur récupération eventId: ' . $e->getMessage());
                }
            }
            
            if ($itemEventId === $targetEventId) {
                $targetItem = $item;
                break;
            }
        }
        
        if (!$targetItem) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Événement introuvable dans le panier'
            ], 400);
        }

        // Calculer le total uniquement pour cet événement
        $totalAmount = 0;
        $adultTotal = ($targetItem['adultQuantity'] ?? 0) * ($targetItem['adultPrice'] ?? 0);
        $childTotal = ($targetItem['childQuantity'] ?? 0) * ($targetItem['childPrice'] ?? 0);
        $totalAmount = $adultTotal + $childTotal;

        if ($totalAmount <= 0) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Aucun billet pour cet événement'
            ], 400);
        }

        // Valider le code promo
        try {
            $codeUpper = strtoupper(trim($code));
            error_log('Validation code promo: ' . $codeUpper . ' pour user: ' . $userId);
            
            $sql = <<<SQL
                SELECT 
                    id,
                    code,
                    promotion_type,
                    value,
                    max_usage_total,
                    max_usage_per_user,
                    starts_at,
                    ends_at
                FROM aiolia.promotion_codes
                WHERE UPPER(TRIM(code)) = UPPER(TRIM(:code))
                  AND (starts_at IS NULL OR starts_at <= NOW())
                  AND (ends_at IS NULL OR ends_at >= NOW())
            SQL;

            try {
                $promo = $this->connection->executeQuery($sql, ['code' => $code])->fetchAssociative();
                error_log('Résultat requête promo: ' . ($promo ? 'trouvé (id: ' . ($promo['id'] ?? 'N/A') . ')' : 'non trouvé'));
            } catch (\Exception $dbError) {
                $errorDetails = [
                    'message' => $dbError->getMessage(),
                    'code' => $dbError->getCode(),
                    'file' => $dbError->getFile(),
                    'line' => $dbError->getLine()
                ];
                error_log('Erreur DB lors de la requête promo: ' . json_encode($errorDetails));
                
                // Écrire aussi dans le fichier de log
                $logDir = dirname(__DIR__, 2) . '/var/log';
                if (!is_dir($logDir)) {
                    @mkdir($logDir, 0755, true);
                }
                $logFile = $logDir . '/promo_validation.log';
                $logMessage = date('Y-m-d H:i:s') . " - ERREUR DB: " . json_encode($errorDetails, JSON_PRETTY_PRINT) . "\n";
                $logMessage .= "SQL: {$sql}\n";
                $logMessage .= "Code: {$code}\n";
                $logMessage .= str_repeat('-', 80) . "\n";
                @file_put_contents($logFile, $logMessage, FILE_APPEND);
                
                throw $dbError;
            }

            if (!$promo) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Code promo introuvable ou expiré'
                ], 400);
            }

            $promotionId = (int) $promo['id'];
            $promotionType = $promo['promotion_type'];
            $value = (float) $promo['value'];

            // Vérifier les limites d'utilisation totale
            if ($promo['max_usage_total'] !== null) {
                $usageCount = $this->connection->executeQuery(
                    'SELECT COUNT(*) FROM aiolia.promotion_applications WHERE promotion_id = :promotion_id',
                    ['promotion_id' => $promotionId]
                )->fetchOne();
                
                if ($usageCount >= (int) $promo['max_usage_total']) {
                    return new JsonResponse([
                        'success' => false,
                        'error' => 'Code promo épuisé'
                    ], 400);
                }
            }

            // Vérifier les limites d'utilisation par utilisateur (seulement si connecté)
            if ($promo['max_usage_per_user'] !== null && $userId) {
                $userUsageCount = $this->connection->executeQuery(
                    'SELECT COUNT(*) FROM aiolia.promotion_applications WHERE promotion_id = :promotion_id AND user_id = :user_id',
                    ['promotion_id' => $promotionId, 'user_id' => $userId]
                )->fetchOne();
                
                if ($userUsageCount >= (int) $promo['max_usage_per_user']) {
                    return new JsonResponse([
                        'success' => false,
                        'error' => 'Vous avez déjà utilisé ce code promo'
                    ], 400);
                }
            }

            // Calculer la réduction uniquement pour cet événement
            $discountAmount = 0;
            if ($promotionType === 'percent') {
                $discountAmount = $totalAmount * ($value / 100);
            } elseif ($promotionType === 'amount') {
                $discountAmount = min($value, $totalAmount);
            }

            $totalAfterDiscount = max(0, $totalAmount - $discountAmount);

            // Stocker le code promo en session par eventId
            $appliedPromoCodes = $session->get('applied_promo_codes', []);
            $appliedPromoCodes[$eventId] = [
                'code' => $code,
                'promotion_id' => $promotionId,
                'discount_amount' => $discountAmount,
                'promotion_type' => $promotionType,
                'value' => $value,
                'event_id' => $targetEventId
            ];
            $session->set('applied_promo_codes', $appliedPromoCodes);

            return new JsonResponse([
                'success' => true,
                'code' => $code,
                'discount_amount' => $discountAmount,
                'total_before' => $totalAmount,
                'total_after' => $totalAfterDiscount,
                'promotion_type' => $promotionType,
                'value' => $value
            ]);
        } catch (\Exception $e) {
            $isDev = ($_ENV['APP_ENV'] ?? 'dev') === 'dev';
            $errorMessage = $e->getMessage();
            $errorFile = $e->getFile();
            $errorLine = $e->getLine();
            $errorTrace = $e->getTraceAsString();
            
            // Log détaillé
            $logDir = dirname(__DIR__, 2) . '/var/log';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            $logFile = $logDir . '/promo_validation.log';
            $logMessage = date('Y-m-d H:i:s') . " - ERREUR: {$errorMessage}\n";
            $logMessage .= "Fichier: {$errorFile}:{$errorLine}\n";
            $logMessage .= "Stack trace:\n{$errorTrace}\n";
            $logMessage .= str_repeat('-', 80) . "\n";
            @file_put_contents($logFile, $logMessage, FILE_APPEND);
            
            error_log('Erreur validation code promo: ' . $errorMessage);
            error_log('Fichier: ' . $errorFile . ':' . $errorLine);
            error_log('Stack trace: ' . $errorTrace);
            
            return new JsonResponse([
                'success' => false,
                'error' => 'Erreur lors de la validation du code promo. Veuillez réessayer.',
                'debug' => $isDev ? [
                    'message' => $errorMessage,
                    'file' => $errorFile,
                    'line' => $errorLine
                ] : null
            ], 500);
        }
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

    #[Route('/api/tickets/promo/remove', name: 'api_tickets_remove_promo', methods: ['POST'])]
    public function removePromoCode(Request $request): JsonResponse
    {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $eventId = trim($request->request->get('event_id', ''));
        
        if (empty($eventId)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'ID événement requis'
            ], 400);
        }

        // Retirer le code promo pour cet événement spécifique
        $appliedPromoCodes = $session->get('applied_promo_codes', []);
        if (isset($appliedPromoCodes[$eventId])) {
            unset($appliedPromoCodes[$eventId]);
            $session->set('applied_promo_codes', $appliedPromoCodes);
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'Code promo retiré'
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

