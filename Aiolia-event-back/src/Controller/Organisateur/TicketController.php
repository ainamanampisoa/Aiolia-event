<?php

namespace App\Controller\Organisateur;

use App\Entity\Event;
use App\Form\TypeBilletPriceType;
use App\Repository\Organisateur\EventRepository;
use App\Repository\Organisateur\TicketInvoiceRepository;
use App\Service\Organisateur\BilletService;
use App\Service\Organisateur\TicketManagementService;
use App\Service\Organisateur\TypeBilletService;
use App\Service\Organisateur\WaitlistService;
use App\Service\QrCodeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tickets')]
class TicketController extends AbstractController
{
    public function __construct(
        private BilletService $billetService,
        private TicketManagementService $ticketManagementService,
        private TypeBilletService $typeBilletService,
        private QrCodeService $qrCodeService,
        private EventRepository $eventRepository,
        private TicketInvoiceRepository $ticketInvoiceRepository,
        private WaitlistService $waitlistService, // Service pour la liste d'attente
    ) {
    }

    #[Route('', name: 'app_ticket_index')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        
        $user = $this->getUser();
        
        $billets = $this->billetService->getByOrganizer($user);
        $stats = $this->billetService->getStatsByOrganizer($user);
        
        return $this->render('Organisateur/ticket/index.html.twig', [
            'billets' => $billets,
            'stats' => $stats,
        ]);
    }

    #[Route('/categories', name: 'app_ticket_categories')]
    public function categories(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $user = $this->getUser();

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 100;
        $categorieFilter = $request->query->get('categorie');
        $segmentFilter = $request->query->get('segment');
        $eventId = $request->query->get('eventId');

        $event = null;
        if ($eventId) {
            $event = $this->eventRepository->getById($eventId);
            if ($event) {
                $organizerProfile = $event->getProfilOrganisateur();
                if ($organizerProfile && $organizerProfile->getUtilisateur() !== $user) {
                    $event = null;
                }
            }
        }

        $paginator = $this->typeBilletService->getByOrganizerPaginated($user, $page, $limit, $categorieFilter, $segmentFilter, $event);
        $totalItems = $paginator->count();
        $totalPages = (int) ceil($totalItems / $limit);

        $allTypesBillets = $event
            ? $this->typeBilletService->getByEvenement($event)
            : $this->typeBilletService->getByOrganizer($user);
        $categories = [];
        $segments = [];
        foreach ($allTypesBillets as $tb) {
            if ($tb->getConfigurationCategorie() && !isset($categories[$tb->getConfigurationCategorie()->getId()])) {
                $categories[$tb->getConfigurationCategorie()->getId()] = $tb->getConfigurationCategorie();
            }
            if ($tb->getConfigurationSegment()) {
                if ($tb->getConfigurationSegment()->getNom() !== 'tous' && !isset($segments[$tb->getConfigurationSegment()->getId()])) {
                    $segments[$tb->getConfigurationSegment()->getId()] = $tb->getConfigurationSegment();
                }
            }
        }

        $typesBilletsArray = iterator_to_array($paginator);
        $statsByType = [];
        $totalStats = ['stockTotal' => 0, 'vendus' => 0, 'disponibles' => 0, 'revenus' => 0];

        /** @var \App\Entity\TypeBillet $typeBillet */
        foreach ($typesBilletsArray as $typeBillet) {
            $stats = $this->billetService->getSalesStatsByTypeBillet($typeBillet);
            $statsByType[(string)$typeBillet->getId()] = $stats;

            $totalStats['stockTotal'] += $stats['stockTotal'];
            $totalStats['vendus'] += $stats['vendus'];
            $totalStats['disponibles'] += $stats['disponibles'];
            $totalStats['revenus'] += $stats['revenus'];
        }

        $totalStats['tauxVente'] = $totalStats['stockTotal'] > 0
            ? round(($totalStats['vendus'] / $totalStats['stockTotal']) * 100, 1)
            : 0;

        return $this->render('Organisateur/ticket/categories.html.twig', [
            'typesBillets' => $typesBilletsArray,
            'statsByType' => $statsByType,
            'totalStats' => $totalStats,
            'categories' => $categories,
            'segments' => $segments,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'limit' => $limit,
            'categorieFilter' => $categorieFilter,
            'segmentFilter' => $segmentFilter,
            'event' => $event,
        ]);
    }

    #[Route('/qrcodes', name: 'app_ticket_qrcodes')]
    public function qrcodes(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        
        $user = $this->getUser();
        
        $billets = $this->billetService->getByOrganizer($user);
        
        $billetsWithQr = [];
        foreach ($billets as $billet) {
            $qrCodeUrl = $this->qrCodeService->generateQrCodeForBillet(
                $billet->getCodeQr()
            );
            
            $billetsWithQr[] = [
                'billet' => $billet,
                'qrCodeUrl' => $qrCodeUrl,
            ];
        }

        return $this->render('Organisateur/ticket/qrcodes.html.twig', [
            'billetsWithQr' => $billetsWithQr,
        ]);
    }

    #[Route('/scanning', name: 'app_ticket_scanning')]
    public function scanning(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $user = $this->getUser();
        $eventId = $request->query->get('eventId');
        
        $event = null;
        if ($eventId) {
            $event = $this->eventRepository->getById($eventId);
            if ($event) {
                $organizerProfile = $event->getProfilOrganisateur();
                if ($organizerProfile && $organizerProfile->getUtilisateur() !== $user) {
                    $event = null;
                }
            }
        }

        return $this->render('Organisateur/ticket/scanning.html.twig', [
            'scanApiUrl' => $this->generateUrl('organisateur_ticket_api_scan'),
            'event' => $event,
        ]);
    }

    #[Route('/stock-alerts', name: 'app_ticket_stock_alerts')]
    public function stockAlerts(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $user = $this->getUser();
        $niveauFilter = $request->query->get('niveau');
        $categorieFilter = $request->query->get('categorie');
        $segmentFilter = $request->query->get('segment');
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 10;

        $result = $this->ticketManagementService->calculateStockAlerts(
            $user,
            $niveauFilter,
            $categorieFilter,
            $segmentFilter
        );

        // Pagination des alertes filtrées
        $totalItems = count($result['alertes']);
        $totalPages = max(1, (int) ceil($totalItems / $limit));
        $offset = ($page - 1) * $limit;
        $alertesPaginated = array_slice($result['alertes'], $offset, $limit);
        
        // Filtrer les alertes paginées par niveau
        $alertesCritiquesPaginated = array_filter($alertesPaginated, fn($a) => $a['niveau'] === 'critique');
        $alertesAttentionPaginated = array_filter($alertesPaginated, fn($a) => $a['niveau'] === 'attention');

        return $this->render('Organisateur/ticket/stock_alerts.html.twig', [
            'alertes' => $result['alertes'],
            'alertesTotal' => $result['alertesTotal'],
            'alertesPaginated' => $alertesPaginated,
            'alertesCritiques' => $result['alertesCritiques'],
            'alertesAttention' => $result['alertesAttention'],
            'alertesCritiquesPaginated' => array_values($alertesCritiquesPaginated),
            'alertesAttentionPaginated' => array_values($alertesAttentionPaginated),
            'categories' => $result['categories'],
            'segments' => $result['segments'],
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'limit' => $limit,
            'niveauFilter' => $niveauFilter,
            'categorieFilter' => $categorieFilter,
            'segmentFilter' => $segmentFilter,
            'eventsCount' => $result['eventsCount'],
            'capaciteParCategorie' => $result['capaciteParCategorie'],
        ]);
    }

    #[Route('/stock-debug', name: 'app_ticket_stock_debug')]
    public function stockDebug(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $user = $this->getUser();
        
        // Récupérer tous les types de billets
        $allTypesBillets = $this->typeBilletService->getByOrganizer($user);
        
        $debugData = [];
        foreach ($allTypesBillets as $typeBillet) {
            $inventaire = $typeBillet->getInventaire();
            $evenement = $typeBillet->getEvenement();
            
            if (!$inventaire || !$evenement) {
                continue;
            }
            
            $quantiteTotale = $inventaire->getQuantiteTotale();
            $quantiteVendue = $inventaire->getQuantiteVendue();
            $quantiteReservee = $inventaire->getQuantiteReservee();
            
            $quantiteRestante = max(0, $quantiteTotale - $quantiteVendue - $quantiteReservee);
            $pourcentage = $quantiteTotale > 0 ? ($quantiteRestante / $quantiteTotale) * 100 : 0;
            
            $debugData[] = [
                'evenement' => $evenement->getTitre(),
                'type_billet' => $typeBillet->getNom(),
                'total' => $quantiteTotale,
                'vendu' => $quantiteVendue,
                'reserve' => $quantiteReservee,
                'restant' => $quantiteRestante,
                'pourcentage' => round($pourcentage, 2),
                'est_alerte' => $pourcentage <= 10 ? 'OUI' : 'NON',
                'niveau' => $quantiteRestante === 0 || $pourcentage <= 5 ? 'critique' : 
                        ($pourcentage <= 10 ? 'attention' : 'ok'),
            ];
        }
        
        return $this->render('Organisateur/ticket/debug.html.twig', [
            'debugData' => $debugData,
        ]);
    }
    
    #[Route('/historique-prix', name: 'app_ticket_historique_prix')]
    public function historiquePrix(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $user = $this->getUser();
        $categorieFilter = $request->query->get('categorie');
        $segmentFilter = $request->query->get('segment');
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 5;

        $result = $this->ticketManagementService->getGroupedPriceHistory(
            $user,
            $page,
            $limit,
            $categorieFilter,
            $segmentFilter
        );

        return $this->render('Organisateur/ticket/historique_prix.html.twig', [
            'pagination' => $result['pagination'],
            'historiques' => $result['historiques'],
            'categories' => $result['categories'],
            'segments' => $result['segments'],
        ]);
    }

    #[Route('/{id}', name: 'app_ticket_show', requirements: ['id' => '\d+'])]
    public function show(string $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        
        $user = $this->getUser();
        
        $billet = $this->billetService->getById($id);
        
        if (!$billet) {
            throw $this->createNotFoundException('Billet non trouvé');
        }
        
        $organizerBillets = $this->billetService->getByOrganizer($user);
        $belongsToOrganizer = false;
        foreach ($organizerBillets as $orgBillet) {
            if ((string)$orgBillet->getId() === (string)$billet->getId()) {
                $belongsToOrganizer = true;
                break;
            }
        }
        
        if (!$belongsToOrganizer) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à ce billet');
        }
        
        $qrCodeUrl = $this->qrCodeService->generateQrCodeForBillet($billet->getCodeQr());

        // Récupérer la facture si le billet est lié à une commande
        $facture = null;
        if ($billet->getElementCommande() && $billet->getElementCommande()->getCommande()) {
            $commandeId = $billet->getElementCommande()->getCommande()->getId();
            $facture = $this->ticketInvoiceRepository->findOneBy(['orderId' => $commandeId]);
        }

        return $this->render('Organisateur/ticket/show.html.twig', [
            'billet' => $billet,
            'qrCodeUrl' => $qrCodeUrl,
            'facture' => $facture,
        ]);
    }

    #[Route('/{id}/edit-price', name: 'app_ticket_edit_price', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function editPrice(string $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        
        $user = $this->getUser();
        
        $billet = $this->billetService->getById($id);
        
        if (!$billet) {
            throw $this->createNotFoundException('Billet non trouvé');
        }
        
        $organizerBillets = $this->billetService->getByOrganizer($user);
        $belongsToOrganizer = false;
        foreach ($organizerBillets as $orgBillet) {
            if ((string)$orgBillet->getId() === (string)$billet->getId()) {
                $belongsToOrganizer = true;
                break;
            }
        }
        
        if (!$belongsToOrganizer) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à ce billet');
        }
        
        $typeBillet = $billet->getTypeBillet();
        if (!$typeBillet) {
            throw $this->createNotFoundException('Type de billet non trouvé');
        }
        
        $form = $this->createForm(TypeBilletPriceType::class, [
            'prixDeBase' => $typeBillet->getPrixDeBase(),
        ], [
            'devise' => $typeBillet->getDevise(),
        ]);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $nouveauPrix = $data['prixDeBase'];
            $raison = $data['raison'] ?? null;
            
            $result = $this->ticketManagementService->updatePrice(
                $typeBillet,
                $nouveauPrix,
                $user,
                $raison,
                ['action' => 'modification_prix', 'billet_id' => $billet->getId()]
            );
            
            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => true,
                    'message' => 'Prix modifié avec succès',
                    'nouveauPrix' => $result['nouveauPrix'],
                    'devise' => $result['devise'],
                ]);
            }
            
            $this->addFlash('success', 'Le prix a été modifié avec succès');
            return $this->redirectToRoute('organisateur_tickets_index');
        }
        
        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'html' => $this->renderView('Organisateur/ticket/_edit_price_modal.html.twig', [
                    'form' => $form->createView(),
                    'billet' => $billet,
                    'typeBillet' => $typeBillet,
                ]),
            ]);
        }
        
        return $this->render('Organisateur/ticket/edit_price.html.twig', [
            'form' => $form->createView(),
            'billet' => $billet,
            'typeBillet' => $typeBillet,
        ]);
    }

    #[Route('/types/{id}/edit', name: 'app_ticket_type_edit', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function editTypeBillet(string $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $user = $this->getUser();
        $typeBillet = $this->typeBilletService->getById($id);

        if (!$typeBillet) {
            return $this->json([
                'success' => false,
                'message' => 'Type de billet non trouvé',
            ], 404);
        }

        // Vérifier que l'utilisateur a accès à ce type de billet
        $organizerTypes = $this->typeBilletService->getByOrganizer($user);
        $belongsToOrganizer = false;
        foreach ($organizerTypes as $orgType) {
            if ((string)$orgType->getId() === (string)$typeBillet->getId()) {
                $belongsToOrganizer = true;
                break;
            }
        }

        if (!$belongsToOrganizer) {
            return $this->json([
                'success' => false,
                'message' => 'Vous n\'avez pas accès à ce type de billet',
            ], 403);
        }

        // Vérifier que l'événement est en cours ou à venir
        $event = $typeBillet->getEvenement();
        if (!$event) {
            return $this->json([
                'success' => false,
                'message' => 'Événement non trouvé',
            ], 404);
        }

        if (!$this->ticketManagementService->isEventActiveOrUpcoming($event)) {
            return $this->json([
                'success' => false,
                'message' => 'L\'événement est terminé, vous ne pouvez plus modifier ce type de billet',
            ], 403);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['prixDeBase']) || !is_numeric($data['prixDeBase'])) {
            return $this->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => ['prixDeBase' => 'Le prix doit être un nombre']
            ], 400);
        }

        if (!isset($data['quantiteTotale']) || !is_numeric($data['quantiteTotale'])) {
            return $this->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => ['quantiteTotale' => 'La quantité doit être un nombre']
            ], 400);
        }

        $result = $this->ticketManagementService->updateTypeBillet(
            $typeBillet,
            (float)$data['prixDeBase'],
            (int)$data['quantiteTotale'],
            $user
        );

        $statusCode = $result['success'] ? 200 : 400;
        return $this->json($result, $statusCode);
    }

    /**
     * API pour notifier la liste d'attente après modification du stock
     * Cette méthode est appelée MANUELLEMENT après une modification de stock
     */
    #[Route('/api/stock/{id}/notify-waitlist', name: 'api_stock_notify_waitlist', methods: ['POST'])]
    public function notifyWaitlistAfterStockUpdate(string $id, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        
        $user = $this->getUser();
        $typeBillet = $this->typeBilletService->getById($id);

        if (!$typeBillet) {
            return $this->json([
                'success' => false,
                'message' => 'Type de billet non trouvé',
            ], 404);
        }

        // Vérifier que l'utilisateur a accès à ce type de billet
        $organizerTypes = $this->typeBilletService->getByOrganizer($user);
        $belongsToOrganizer = false;
        foreach ($organizerTypes as $orgType) {
            if ((string)$orgType->getId() === (string)$typeBillet->getId()) {
                $belongsToOrganizer = true;
                break;
            }
        }

        if (!$belongsToOrganizer) {
            return $this->json([
                'success' => false,
                'message' => 'Vous n\'avez pas accès à ce type de billet',
            ], 403);
        }

        // Récupérer le stock supplémentaire ajouté
        $additionalStock = (int) $request->request->get('additional_stock', 0);
        
        if ($additionalStock <= 0) {
            return $this->json([
                'success' => false,
                'message' => 'Aucun stock supplémentaire n\'a été ajouté',
            ], 400);
        }

        try {
            // 1. Récupérer la liste d'attente triée par date d'inscription
            $waitlist = $this->waitlistService->getPendingWaitlist($typeBillet);
            
            // 2. Calculer combien de personnes on peut notifier
            $canSatisfy = $this->calculateSatisfiableRequests($waitlist, $additionalStock);
            
            // 3. Notifier les utilisateurs concernés (les plus anciens)
            $notified = $this->waitlistService->notifyUsers($canSatisfy['users']);
            
            return $this->json([
                'success' => true,
                'message' => sprintf(
                    'Notification envoyée à %d personne(s) - %d billet(s) attribués',
                    $notified['users_count'],
                    $notified['tickets_count']
                ),
                'remaining_waitlist' => $canSatisfy['remaining'],
                'new_stock_available' => $additionalStock - $notified['tickets_count']
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la notification : ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calculer combien de demandes peuvent être satisfaites
     */
    private function calculateSatisfiableRequests(array $waitlist, int $additionalStock): array
    {
        $users = [];
        $remainingStock = $additionalStock;
        $remainingWaitlist = [];

        foreach ($waitlist as $waitlistEntry) {
            if ($remainingStock <= 0) {
                $remainingWaitlist[] = $waitlistEntry;
                continue;
            }

            // Nombre de billets demandés par l'utilisateur
            $requestedTickets = $waitlistEntry->getNombreBillets();
            
            // Nombre de billets qu'on peut attribuer
            $ticketsToAssign = min($requestedTickets, $remainingStock);
            
            if ($ticketsToAssign > 0) {
                $users[] = [
                    'waitlist' => $waitlistEntry,
                    'tickets_allocated' => $ticketsToAssign,
                    'user' => $waitlistEntry->getUtilisateur(),
                ];
                $remainingStock -= $ticketsToAssign;
            }

            // Si l'utilisateur n'a pas obtenu tous ses billets, on le garde dans la liste d'attente
            if ($ticketsToAssign < $requestedTickets) {
                $remainingWaitlist[] = $waitlistEntry;
            }
        }

        return [
            'users' => $users,
            'remaining' => $remainingWaitlist,
            'stock_used' => $additionalStock - $remainingStock,
        ];
    }

    /**
     * API pour vérifier la liste d'attente d'un type de billet
     */
    #[Route('/api/waitlist/check/{id}', name: 'api_waitlist_check', methods: ['GET'])]
    public function checkWaitlist(string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        
        $user = $this->getUser();
        $typeBillet = $this->typeBilletService->getById($id);

        if (!$typeBillet) {
            return $this->json([
                'success' => false,
                'message' => 'Type de billet non trouvé',
            ], 404);
        }

        // Vérifier que l'utilisateur a accès à ce type de billet
        $organizerTypes = $this->typeBilletService->getByOrganizer($user);
        $belongsToOrganizer = false;
        foreach ($organizerTypes as $orgType) {
            if ((string)$orgType->getId() === (string)$typeBillet->getId()) {
                $belongsToOrganizer = true;
                break;
            }
        }

        if (!$belongsToOrganizer) {
            return $this->json([
                'success' => false,
                'message' => 'Vous n\'avez pas accès à ce type de billet',
            ], 403);
        }

        try {
            $waitlist = $this->waitlistService->getPendingWaitlist($typeBillet);
            
            $waitlistData = [];
            foreach ($waitlist as $item) {
                $user = $item->getUtilisateur();
                $waitlistData[] = [
                    'id' => $item->getId(),
                    'user_nom' => $user ? $user->getNomComplet() : 'Utilisateur inconnu',
                    'user_email' => $user ? $user->getEmail() : 'Email inconnu',
                    'nombre_billets' => $item->getNombreBillets(),
                    'date_inscription' => $item->getDateInscription() ? $item->getDateInscription()->format('Y-m-d H:i:s') : null,
                    'statut' => $item->getStatut(),
                ];
            }
            
            return $this->json([
                'success' => true,
                'waitlist_count' => count($waitlist),
                'waitlist' => $waitlistData,
                'type_billet' => [
                    'id' => $typeBillet->getId(),
                    'nom' => $typeBillet->getNom(),
                    'evenement' => $typeBillet->getEvenement() ? $typeBillet->getEvenement()->getTitre() : null,
                ]
            ]);
        } catch (\Exception $e) {
            // Log l'erreur
            error_log('Erreur lors de la vérification de la liste d\'attente: ' . $e->getMessage());
            
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification : ' . $e->getMessage(),
                'waitlist_count' => 0,
                'waitlist' => []
            ], 500);
        }
    }
    /**
     * API pour vérifier toutes les listes d'attente
     */
    #[Route('/api/waitlist/check-all', name: 'api_waitlist_check_all', methods: ['GET'])]
    public function checkAllWaitlists(): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        
        $user = $this->getUser();

        try {
            // Récupérer tous les types de billets de l'organisateur
            $organizerTypes = $this->typeBilletService->getByOrganizer($user);
            
            $waitlists = [];
            $totalWaitlistCount = 0;
            $eventsWithWaitlist = [];
            
            foreach ($organizerTypes as $typeBillet) {
                $waitlist = $this->waitlistService->getPendingWaitlist($typeBillet);
                $waitlistCount = count($waitlist);
                
                if ($waitlistCount > 0) {
                    $event = $typeBillet->getEvenement();
                    $eventId = $event ? $event->getId() : null;
                    
                    if ($eventId && !in_array($eventId, $eventsWithWaitlist)) {
                        $eventsWithWaitlist[] = $eventId;
                    }
                    
                    $waitlists[] = [
                        'type_billet_id' => $typeBillet->getId(),
                        'type_billet_name' => $typeBillet->getNom(),
                        'event_id' => $eventId,
                        'event_name' => $event ? $event->getTitre() : null,
                        'waitlist_count' => $waitlistCount,
                    ];
                    
                    $totalWaitlistCount += $waitlistCount;
                }
            }
            
            return $this->json([
                'success' => true,
                'total_waitlist_count' => $totalWaitlistCount,
                'events_with_waitlist' => count($eventsWithWaitlist),
                'waitlists' => $waitlists,
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification : ' . $e->getMessage(),
            ], 500);
        }
    }
}