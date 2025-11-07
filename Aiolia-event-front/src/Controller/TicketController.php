<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TicketController extends AbstractController
{
    #[Route('/cart', name: 'cart')]
    public function cart(): Response
    {
        $items = $this->getMockCartItems();

        return $this->render('ticket/cart.html.twig', [
            'items' => $items,
        ]);
    }

    #[Route('/add-to-cart', name: 'add_to_cart', methods: ['POST'])]
    public function addToCart(Request $request): Response
    {
        // TODO: Implémenter l'ajout au panier
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
    public function checkoutPayment(): Response
    {
        $items = $this->getMockCartItems();

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
     * Données factices pour le panier en attendant la connexion au backend.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getMockCartItems(): array
    {
        return [
            [
                'event' => [
                    'id' => 1,
                    'image' => 'vente-ticket/images/img1.png',
                    'title' => 'Music on Sunday',
                    'category' => 'Soirée live',
                    'location' => 'Analakely au Café de la Gare',
                    'date' => new \DateTime('+7 days 20:00'),
                    'adultPrice' => 50000,
                    'childPrice' => 25000,
                ],
                'adultQuantity' => 1,
                'childQuantity' => 1,
            ],
            [
                'event' => [
                    'id' => 2,
                    'image' => 'vente-ticket/images/img1.png',
                    'title' => 'Jazz Night Downtown',
                    'category' => 'Concert',
                    'location' => 'Antaninarenina - Hall Central',
                    'date' => new \DateTime('+14 days 21:00'),
                    'adultPrice' => 80000,
                    'childPrice' => null,
                ],
                'adultQuantity' => 2,
                'childQuantity' => 0,
            ],
        ];
    }
}

