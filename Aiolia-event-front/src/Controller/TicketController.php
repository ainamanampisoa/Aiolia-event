<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TicketController extends AbstractController
{
    public function __construct(
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

        $cartItems = $session->get('cart_items', []);
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
        $adultQuantity = (int) $request->request->get('adult_quantity', 0);
        $childQuantity = (int) $request->request->get('child_quantity', 0);

        if ($eventId <= 0 || $ticketTypeId <= 0) {
            $this->addFlash('error', 'Données invalides.');
            return $this->redirectToRoute('events');
        }

        if ($adultQuantity <= 0 && $childQuantity <= 0) {
            $this->addFlash('error', 'Veuillez sélectionner au moins une quantité.');
            return $this->redirectToRoute('event_details', ['id' => $eventId]);
        }

        // Récupérer les détails de l'événement
        $event = $this->fetchEventDetails($eventId);
        if (null === $event) {
            $this->addFlash('error', 'Événement introuvable.');
            return $this->redirectToRoute('events');
        }

        // Récupérer les types de billets
        $ticketTypes = $this->fetchTicketTypes($eventId);
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

        // Calculer les prix adultes et enfants
        $adultPrice = 0;
        $childPrice = 0;
        
        if ($selectedTicketType['age_category'] === 'adult' || $selectedTicketType['age_category'] === 'all') {
            $adultPrice = $selectedTicketType['base_price'];
        }
        if ($selectedTicketType['age_category'] === 'child' || $selectedTicketType['age_category'] === 'all') {
            $childPrice = $selectedTicketType['base_price'];
        }

        // Récupérer le panier existant
        $cartItems = $session->get('cart_items', []);

        // Vérifier si l'événement existe déjà dans le panier avec le même type de billet
        $cartKey = 'event_' . $eventId . '_ticket_' . $ticketTypeId;
        
        if (isset($cartItems[$cartKey])) {
            // Mettre à jour les quantités
            $cartItems[$cartKey]['adultQuantity'] += $adultQuantity;
            $cartItems[$cartKey]['childQuantity'] += $childQuantity;
        } else {
            // Ajouter un nouvel élément au panier
            $cartItems[$cartKey] = [
                'eventId' => $eventId,
                'ticketTypeId' => $ticketTypeId,
                'adultQuantity' => $adultQuantity,
                'childQuantity' => $childQuantity,
                'adultPrice' => $adultPrice,
                'childPrice' => $childPrice > 0 ? $childPrice : null,
                'currency' => $selectedTicketType['currency'] ?? 'MGA',
            ];
        }

        // Sauvegarder le panier en session
        $session->set('cart_items', $cartItems);

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

        $cartItems = $session->get('cart_items', []);
        $items = $this->formatCartItemsForTemplate($cartItems);

        $orderTotal = 0;
        foreach ($items as $item) {
            $adultTotal = $item['adultQuantity'] * $item['event']['adultPrice'];
            $childPrice = $item['event']['childPrice'] ?? 0;
            $childTotal = $item['childQuantity'] * $childPrice;
            $orderTotal += $adultTotal + $childTotal;
        }

        $serviceFees = 3000;
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
    public function myTickets(): Response
    {
        return $this->render('ticket/my_tickets.html.twig');
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
    public function getCart(): JsonResponse
    {
        // TODO: Récupérer le panier d'achat
        return new JsonResponse([
            'message' => 'Panier d\'achat - À implémenter',
            'status' => 'success',
            'data' => []
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

            $formattedItems[] = [
                'cart_key' => $cartKey,
                'event' => [
                    'id' => $eventId,
                    'image' => $image,
                    'title' => $event['title'],
                    'category' => $event['category_label'] ?? 'Événement',
                    'location' => $location,
                    'date' => $event['starts_at'] instanceof \DateTimeImmutable 
                        ? \DateTime::createFromImmutable($event['starts_at'])
                        : ($event['starts_at'] ?? new \DateTime()),
                    'adultPrice' => $cartItem['adultPrice'],
                    'childPrice' => $cartItem['childPrice'],
                ],
                'adultQuantity' => $cartItem['adultQuantity'],
                'childQuantity' => $cartItem['childQuantity'],
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
}

