<?php

namespace App\Controller\Organisateur;

use App\Form\TypeBilletPriceType;
use App\Service\Organisateur\BilletService;
use App\Service\Organisateur\HistoriquePrixBilletService;
use App\Service\Organisateur\TypeBilletService;
use App\Service\QrCodeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tickets')]
class TicketController extends AbstractController
{
    public function __construct(
        private BilletService $billetService,
        private TypeBilletService $typeBilletService,
        private HistoriquePrixBilletService $historiquePrixBilletService,
        private QrCodeService $qrCodeService
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
        $limit = 6;
        $categorieFilter = $request->query->get('categorie');
        $segmentFilter = $request->query->get('segment');
        
        $paginator = $this->typeBilletService->getByOrganizerPaginated($user, $page, $limit, $categorieFilter, $segmentFilter);
        $totalItems = $paginator->count();
        $totalPages = (int) ceil($totalItems / $limit);
        
        
        $allTypesBillets = $this->typeBilletService->getByOrganizer($user);
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
        
        return $this->render('Organisateur/ticket/categories.html.twig', [
            'typesBillets' => iterator_to_array($paginator),
            'categories' => $categories,
            'segments' => $segments,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'limit' => $limit,
            'categorieFilter' => $categorieFilter,
            'segmentFilter' => $segmentFilter,
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
    public function scanning(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        return $this->render('Organisateur/ticket/scanning.html.twig');
    }

    #[Route('/stock-alerts', name: 'app_ticket_stock_alerts')]
    public function stockAlerts(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        
        $user = $this->getUser();
        
        $typesBillets = $this->typeBilletService->getByOrganizer($user);

        
        $niveauFilter = $request->query->get('niveau');
        $categorieFilter = $request->query->get('categorie');
        $segmentFilter = $request->query->get('segment');

        
        $alertesTotal = [];
        foreach ($typesBillets as $typeBillet) {
            $inventaire = $typeBillet->getInventaire();
            if ($inventaire) {
                $quantiteRestante = $inventaire->getQuantiteTotale() - $inventaire->getQuantiteVendue() - $inventaire->getQuantiteReservee();
                $pourcentage = $inventaire->getQuantiteTotale() > 0
                    ? ($quantiteRestante / $inventaire->getQuantiteTotale()) * 100
                    : 0;

                if ($pourcentage <= 10) {
                    $niveau = $pourcentage <= 5 ? 'critique' : 'attention';
                    
                    $alertesTotal[] = [
                        'typeBillet' => $typeBillet,
                        'inventaire' => $inventaire,
                        'quantiteRestante' => $quantiteRestante,
                        'pourcentage' => $pourcentage,
                        'niveau' => $niveau,
                    ];
                }
            }
        }

        
        $alertesCritiquesTotal = array_filter($alertesTotal, fn($a) => $a['niveau'] === 'critique');
        $alertesAttentionTotal = array_filter($alertesTotal, fn($a) => $a['niveau'] === 'attention');

        
        $alertes = [];
        foreach ($alertesTotal as $alerte) {
            $typeBillet = $alerte['typeBillet'];
            $niveau = $alerte['niveau'];
            $categorieNom = $typeBillet->getConfigurationCategorie() ? $typeBillet->getConfigurationCategorie()->getNom() : null;
            $segmentNom = $typeBillet->getConfigurationSegment() ? $typeBillet->getConfigurationSegment()->getNom() : null;
            
            
            if ($niveauFilter && $niveau !== $niveauFilter) {
                continue;
            }
            if ($categorieFilter && $categorieNom !== $categorieFilter) {
                continue;
            }
            if ($segmentFilter && $segmentNom !== $segmentFilter) {
                continue;
            }
            
            $alertes[] = $alerte;
        }
        
        
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 3;
        $totalItems = count($alertes);
        $totalPages = max(1, (int) ceil($totalItems / $limit));
        
        
        $offset = ($page - 1) * $limit;
        $alertesPaginated = array_slice($alertes, $offset, $limit);
        
        
        $alertesCritiquesPaginated = array_filter($alertesPaginated, fn($a) => $a['niveau'] === 'critique');
        $alertesAttentionPaginated = array_filter($alertesPaginated, fn($a) => $a['niveau'] === 'attention');

        
        $categories = [];
        $segments = [];
        foreach ($typesBillets as $tb) {
            if ($tb->getConfigurationCategorie() && !isset($categories[$tb->getConfigurationCategorie()->getNom()])) {
                $categories[$tb->getConfigurationCategorie()->getNom()] = $tb->getConfigurationCategorie()->getNom();
            }
            if ($tb->getConfigurationSegment()) {
                $segmentNom = $tb->getConfigurationSegment()->getNom();
                
                if ($segmentNom !== 'tous' && !isset($segments[$segmentNom])) {
                    $segments[$segmentNom] = $segmentNom;
                }
            }
        }
        
        return $this->render('Organisateur/ticket/stock_alerts.html.twig', [
            'alertes' => $alertes,
            'alertesTotal' => $alertesTotal,
            'alertesPaginated' => $alertesPaginated,
            'alertesCritiques' => array_values($alertesCritiquesTotal),
            'alertesAttention' => array_values($alertesAttentionTotal),
            'alertesCritiquesPaginated' => array_values($alertesCritiquesPaginated),
            'alertesAttentionPaginated' => array_values($alertesAttentionPaginated),
            'categories' => $categories,
            'segments' => $segments,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'limit' => $limit,
            'niveauFilter' => $niveauFilter,
            'categorieFilter' => $categorieFilter,
            'segmentFilter' => $segmentFilter,
        ]);
    }

    #[Route('/historique-prix', name: 'app_ticket_historique_prix')]
    public function historiquePrix(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        
        $user = $this->getUser();

        $typesBillets = $this->typeBilletService->getByOrganizer($user);
        $categorieFilter = $request->query->get('categorie');
        $segmentFilter = $request->query->get('segment');
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 5;

        $historiques = [];
        $paginationData = [];

        
        $paginator = $this->historiquePrixBilletService->getByOrganizerPaginated($user, $page, $limit, $categorieFilter, $segmentFilter);
        $totalItems = $paginator->count();
        $totalPages = max(1, (int) ceil($totalItems / $limit));

        
        $groupedByType = [];
        $itemsOnCurrentPage = 0;
        foreach ($paginator as $hist) {
            $itemsOnCurrentPage++;
            $typeId = $hist->getTypeBillet()->getId();
            if (!isset($groupedByType[$typeId])) {
                $groupedByType[$typeId] = [
                    'typeBillet' => $hist->getTypeBillet(),
                    'historique' => [],
                ];
            }
            $groupedByType[$typeId]['historique'][] = $hist;
        }

        $historiques = $groupedByType;

        $paginationData = [
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'limit' => $limit,
            'categorieFilter' => $categorieFilter,
            'segmentFilter' => $segmentFilter,
            'itemsOnCurrentPage' => $itemsOnCurrentPage,
        ];

        
        $categories = [];
        $segments = [];
        foreach ($typesBillets as $tb) {
            if ($tb->getConfigurationCategorie() && !isset($categories[$tb->getConfigurationCategorie()->getNom()])) {
                $categories[$tb->getConfigurationCategorie()->getNom()] = $tb->getConfigurationCategorie()->getNom();
            }
            if ($tb->getConfigurationSegment()) {
                $segmentNom = $tb->getConfigurationSegment()->getNom();
                
                if ($segmentNom !== 'tous' && !isset($segments[$segmentNom])) {
                    $segments[$segmentNom] = $segmentNom;
                }
            }
        }

        
        if (empty($paginationData)) {
            $paginationData = [
                'currentPage' => 1,
                'totalPages' => 0,
                'totalItems' => 0,
                'limit' => $limit,
                'itemsOnCurrentPage' => 0,
            ];
        }

        return $this->render('Organisateur/ticket/historique_prix.html.twig', [
            'pagination' => $paginationData,
            'historiques' => $historiques,
            'categories' => $categories,
            'segments' => $segments,
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

        return $this->render('Organisateur/ticket/show.html.twig', [
            'billet' => $billet,
            'qrCodeUrl' => $qrCodeUrl,
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
            
            
            $nouveauPrixString = is_numeric($nouveauPrix) ? number_format((float)$nouveauPrix, 2, '.', '') : (string)$nouveauPrix;
            
            
            $ancienPrix = $typeBillet->getPrixDeBase();
            
            
            $this->typeBilletService->update($typeBillet, [
                'prixDeBase' => $nouveauPrixString,
            ]);
            
            
            $this->historiquePrixBilletService->enregistrerChangement(
                $typeBillet,
                $ancienPrix,
                $nouveauPrixString,
                $user,
                $raison,
                ['action' => 'modification_prix', 'billet_id' => $billet->getId()]
            );
            
            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => true,
                    'message' => 'Prix modifié avec succès',
                    'nouveauPrix' => number_format((float)$nouveauPrix, 2, ',', ' '),
                    'devise' => $typeBillet->getDevise(),
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
}

