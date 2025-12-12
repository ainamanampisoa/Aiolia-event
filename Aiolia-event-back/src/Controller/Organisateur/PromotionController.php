<?php

namespace App\Controller\Organisateur;

use App\Repository\Organisateur\OrganizerProfileRepository;
use App\Service\Organisateur\ApplicationPromotionService;
use App\Service\Organisateur\CodePromotionnelService;
use App\Service\Organisateur\EventService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/organisateur/promotions')]
#[IsGranted('ROLE_ORGANIZER')]
class PromotionController extends AbstractController
{
    #[Route('', name: 'organisateur_promotions_index')]
    public function index(
        Request $request,
        CodePromotionnelService $codePromotionnelService,
        ApplicationPromotionService $applicationPromotionService,
        OrganizerProfileRepository $organizerProfileRepository
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        
        $organizerProfile = $organizerProfileRepository->findOneBy(['utilisateur' => $user]);
        if (!$organizerProfile) {
            throw $this->createAccessDeniedException('Profil organisateur non trouvé.');
        }

        
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = 3;

        
        $dateDebut = null;
        $dateFin = null;
        
        if ($request->query->has('date_debut') && $request->query->get('date_debut')) {
            try {
                $dateDebut = new \DateTimeImmutable($request->query->get('date_debut'));
            } catch (\Exception $e) {
                
                $dateDebut = null;
            }
        }
        
        if ($request->query->has('date_fin') && $request->query->get('date_fin')) {
            try {
                $dateFin = new \DateTimeImmutable($request->query->get('date_fin'));
                
                $dateFin = $dateFin->setTime(23, 59, 59);
            } catch (\Exception $e) {
                
                $dateFin = null;
            }
        }

        
        $paginationData = $codePromotionnelService->getByOrganisateurPaginated(
            $organizerProfile,
            $page,
            $perPage,
            $dateDebut,
            $dateFin
        );
        $promotions = $paginationData['items'];
        
        
        $allPromotions = $codePromotionnelService->getByOrganisateur($organizerProfile);
        $promotionsActives = $codePromotionnelService->getActiveByOrganisateur($organizerProfile);
        $promotionsExpirantBientot = $codePromotionnelService->getExpiringSoon($organizerProfile, 7);

        
        $totalUtilisations = 0;
        $totalRemises = 0;
        foreach ($allPromotions as $promotion) {
            $totalUtilisations += $codePromotionnelService->countUtilisations($promotion);
            $totalRemises += $codePromotionnelService->getTotalRemise($promotion);
        }

        
        $promotionsAvecStats = [];
        foreach ($promotions as $promotion) {
            $utilisations = $codePromotionnelService->countUtilisations($promotion);
            $remise = $codePromotionnelService->getTotalRemise($promotion);
            
            $promotionsAvecStats[] = [
                'promotion' => $promotion,
                'utilisations' => $utilisations,
                'remise' => $remise,
            ];
        }

        return $this->render('Organisateur/promotion/index.html.twig', [
            'promotions' => $promotionsAvecStats,
            'totalPromotions' => count($allPromotions),
            'promotionsActives' => count($promotionsActives),
            'totalUtilisations' => $totalUtilisations,
            'totalRemises' => $totalRemises,
            'promotionsExpirantBientot' => count($promotionsExpirantBientot),
            'pagination' => [
                'current_page' => $paginationData['current_page'],
                'total_pages' => $paginationData['pages'],
                'total_items' => $paginationData['total'],
                'per_page' => $paginationData['per_page'],
            ],
            'filters' => [
                'date_debut' => $dateDebut ? $dateDebut->format('Y-m-d') : '',
                'date_fin' => $dateFin ? $dateFin->format('Y-m-d') : '',
            ],
        ]);
    }

    #[Route('/new', name: 'organisateur_promotions_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        CodePromotionnelService $codePromotionnelService,
        OrganizerProfileRepository $organizerProfileRepository,
        EventService $eventService
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        
        $organizerProfile = $organizerProfileRepository->findOneBy(['utilisateur' => $user]);
        if (!$organizerProfile) {
            throw $this->createAccessDeniedException('Profil organisateur non trouvé.');
        }

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            
            
            if (!empty($data['start_date'])) {
                $dateStr = str_replace(' ', 'T', $data['start_date']);
                $data['commenceLe'] = new \DateTime($dateStr);
            }
            if (!empty($data['end_date'])) {
                $dateStr = str_replace(' ', 'T', $data['end_date']);
                $data['seTermineLe'] = new \DateTime($dateStr);
            }

            
            $data['typePromotion'] = $data['discount_type'] === 'percentage' ? 'percent' : 'amount';
            $data['valeur'] = $data['discount_value'] ?? 0;

            
            $metadonnees = [];
            if (isset($data['description'])) {
                $metadonnees['description'] = $data['description'];
            }
            if (isset($data['events'])) {
                $metadonnees['events'] = $data['events'];
            }
            if (isset($data['categories'])) {
                $metadonnees['categories'] = $data['categories'];
            }
            if (isset($data['min_order'])) {
                $metadonnees['min_order'] = $data['min_order'];
            }
            if (isset($data['first_purchase'])) {
                $metadonnees['first_purchase'] = (bool) $data['first_purchase'];
            }
            if (isset($data['cumulative'])) {
                $metadonnees['cumulative'] = (bool) $data['cumulative'];
            }
            if (isset($data['auto_apply'])) {
                $metadonnees['auto_apply'] = (bool) $data['auto_apply'];
            }
            $data['metadonnees'] = $metadonnees;

            
            if (!empty($data['max_uses'])) {
                $data['utilisationMaximaleTotale'] = (int) $data['max_uses'];
            }
            if (!empty($data['max_uses_per_user'])) {
                $data['utilisationMaximaleParUtilisateur'] = (int) $data['max_uses_per_user'];
            }

            try {
                $codePromotionnelService->create($data, $organizerProfile);
                $this->addFlash('success', 'Code promo créé avec succès !');
                return $this->redirectToRoute('organisateur_promotions_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création du code promo : ' . $e->getMessage());
            }
        }

        
        $now = new \DateTime();
        $events = $eventService->searchMultiCriteria([
            'idOrganisateur' => $organizerProfile->getId(),
            'dateFin' => null, 
            'limit' => 1000,
        ]);
        
        
        $upcomingEvents = array_filter($events, function($event) use ($now) {
            
            if ($event->getSeTermineLe() === null) {
                
                return $event->getCommenceLe() === null || $event->getCommenceLe() >= $now;
            }
            return $event->getSeTermineLe() >= $now;
        });

        return $this->render('Organisateur/promotion/new.html.twig', [
            'events' => $upcomingEvents,
        ]);
    }

    #[Route('/{id}/edit', name: 'organisateur_promotions_edit', methods: ['GET', 'POST'])]
    public function edit(
        string $id,
        Request $request,
        CodePromotionnelService $codePromotionnelService,
        OrganizerProfileRepository $organizerProfileRepository,
        EventService $eventService
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        
        $organizerProfile = $organizerProfileRepository->findOneBy(['utilisateur' => $user]);
        if (!$organizerProfile) {
            throw $this->createAccessDeniedException('Profil organisateur non trouvé.');
        }

        
        $promotion = $codePromotionnelService->getById($id);
        if (!$promotion) {
            throw $this->createNotFoundException('Promotion non trouvée.');
        }

        
        if ($promotion->getProfilOrganisateur()?->getId() !== $organizerProfile->getId()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette promotion.');
        }

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            
            
            if (!empty($data['start_date'])) {
                $dateStr = str_replace(' ', 'T', $data['start_date']);
                $data['commenceLe'] = new \DateTime($dateStr);
            } elseif (isset($data['start_date']) && $data['start_date'] === '') {
                $data['commenceLe'] = null;
            }
            if (!empty($data['end_date'])) {
                $dateStr = str_replace(' ', 'T', $data['end_date']);
                $data['seTermineLe'] = new \DateTime($dateStr);
            }

            
            $data['typePromotion'] = $data['discount_type'] === 'percentage' ? 'percent' : 'amount';
            $data['valeur'] = $data['discount_value'] ?? 0;

            
            $metadonnees = $promotion->getMetadonnees() ?? [];
            if (isset($data['description'])) {
                $metadonnees['description'] = $data['description'];
            }
            if (isset($data['events'])) {
                $metadonnees['events'] = $data['events'];
            }
            if (isset($data['categories'])) {
                $metadonnees['categories'] = $data['categories'];
            }
            if (isset($data['min_order'])) {
                $metadonnees['min_order'] = $data['min_order'];
            }
            if (isset($data['first_purchase'])) {
                $metadonnees['first_purchase'] = (bool) $data['first_purchase'];
            }
            if (isset($data['cumulative'])) {
                $metadonnees['cumulative'] = (bool) $data['cumulative'];
            }
            if (isset($data['auto_apply'])) {
                $metadonnees['auto_apply'] = (bool) $data['auto_apply'];
            }
            $data['metadonnees'] = $metadonnees;

            
            if (!empty($data['max_uses'])) {
                $data['utilisationMaximaleTotale'] = (int) $data['max_uses'];
            } elseif (isset($data['max_uses']) && $data['max_uses'] === '') {
                $data['utilisationMaximaleTotale'] = null;
            }
            if (!empty($data['max_uses_per_user'])) {
                $data['utilisationMaximaleParUtilisateur'] = (int) $data['max_uses_per_user'];
            }

            try {
                $codePromotionnelService->update($promotion, $data);
                $this->addFlash('success', 'Code promo modifié avec succès !');
                return $this->redirectToRoute('organisateur_promotions_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la modification du code promo : ' . $e->getMessage());
            }
        }

        
        $now = new \DateTime();
        $events = $eventService->searchMultiCriteria([
            'idOrganisateur' => $organizerProfile->getId(),
            'dateFin' => null, 
            'limit' => 1000,
        ]);
        
        
        $upcomingEvents = array_filter($events, function($event) use ($now) {
            
            if ($event->getSeTermineLe() === null) {
                
                return $event->getCommenceLe() === null || $event->getCommenceLe() >= $now;
            }
            return $event->getSeTermineLe() >= $now;
        });

        return $this->render('Organisateur/promotion/edit.html.twig', [
            'promotion' => $promotion,
            'events' => $upcomingEvents,
        ]);
    }

    #[Route('/{id}/history', name: 'organisateur_promotions_history', methods: ['GET'])]
    public function history(
        string $id,
        Request $request,
        CodePromotionnelService $codePromotionnelService,
        ApplicationPromotionService $applicationPromotionService,
        OrganizerProfileRepository $organizerProfileRepository
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        
        $organizerProfile = $organizerProfileRepository->findOneBy(['utilisateur' => $user]);
        if (!$organizerProfile) {
            throw $this->createAccessDeniedException('Profil organisateur non trouvé.');
        }

        
        $promotion = $codePromotionnelService->getById($id);
        if (!$promotion) {
            throw $this->createNotFoundException('Promotion non trouvée.');
        }

        
        if ($promotion->getProfilOrganisateur()?->getId() !== $organizerProfile->getId()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette promotion.');
        }

        
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = 5;
        
        $allApplications = $applicationPromotionService->getByPromotion($promotion);
        $totalItems = count($allApplications);
        $totalPages = (int) ceil($totalItems / $perPage);
        
        $offset = ($page - 1) * $perPage;
        $applications = array_slice($allApplications, $offset, $perPage);
        
        $totalUtilisations = $codePromotionnelService->countUtilisations($promotion);
        $totalRemise = $codePromotionnelService->getTotalRemise($promotion);

        return $this->render('Organisateur/promotion/history.html.twig', [
            'promotion' => $promotion,
            'applications' => $applications,
            'totalUtilisations' => $totalUtilisations,
            'totalRemise' => $totalRemise,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $totalItems,
                'per_page' => $perPage,
            ],
        ]);
    }

    #[Route('/{id}/delete', name: 'organisateur_promotions_delete', methods: ['POST'])]
    public function delete(
        string $id,
        Request $request,
        CodePromotionnelService $codePromotionnelService,
        OrganizerProfileRepository $organizerProfileRepository,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        
        $token = $request->request->get('_token');
        if (!$csrfTokenManager->isTokenValid(new CsrfToken('delete_promotion', $token))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        
        $organizerProfile = $organizerProfileRepository->findOneBy(['utilisateur' => $user]);
        if (!$organizerProfile) {
            throw $this->createAccessDeniedException('Profil organisateur non trouvé.');
        }

        
        $promotion = $codePromotionnelService->getById($id);
        if (!$promotion) {
            throw $this->createNotFoundException('Promotion non trouvée.');
        }

        
        if ($promotion->getProfilOrganisateur()?->getId() !== $organizerProfile->getId()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette promotion.');
        }

        try {
            $codePromotionnelService->delete($promotion);
            $this->addFlash('success', 'Code promo supprimé avec succès !');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la suppression du code promo : ' . $e->getMessage());
        }

        return $this->redirectToRoute('organisateur_promotions_index');
    }
}

