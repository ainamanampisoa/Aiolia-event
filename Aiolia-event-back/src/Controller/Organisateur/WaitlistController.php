<?php

namespace App\Controller\Organisateur;

use App\Repository\Organisateur\OrganizerProfileRepository;
use App\Repository\Organisateur\TypeBilletRepository;
use App\Repository\UserRepository;
use App\Service\Organisateur\WaitlistService;
use App\Service\Organisateur\WaitlistManagementService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/organisateur/waitlist')]
#[IsGranted('ROLE_ORGANIZER')]
class WaitlistController extends AbstractController
{
    public function __construct(
        private WaitlistService $waitlistService,
        private WaitlistManagementService $waitlistManagementService,
        private OrganizerProfileRepository $organizerProfileRepository,
        private TypeBilletRepository $typeBilletRepository,
        private UserRepository $userRepository
    ) {
    }
    
    #[Route('', name: 'organisateur_waitlist_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        // Récupérer le profil organisateur
        $organizerProfile = $this->organizerProfileRepository->findByUser($user);
        if (!$organizerProfile) {
            throw $this->createAccessDeniedException('Profil organisateur non trouvé.');
        }

        // Récupérer les paramètres de pagination
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = 20;

        // Récupérer les données formatées avec pagination groupées par événement
        $data = $this->waitlistService->getFormattedWaitlistDataByEventPaginated($organizerProfile->getId(), $page, $perPage);

        return $this->render('Organisateur/waitlist/index.html.twig', [
            'events' => $data['events'],
            'pagination' => $data['pagination'],
            'statistics' => $data['statistics'],
        ]);
    }

    #[Route('/update-quantity/{typeBilletId}', name: 'organisateur_waitlist_update_quantity', methods: ['POST'])]
    public function updateQuantity(string $typeBilletId, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        $organizerProfile = $this->organizerProfileRepository->findByUser($user);
        if (!$organizerProfile) {
            return new JsonResponse(['success' => false, 'message' => 'Profil organisateur non trouvé'], 403);
        }

        $typeBillet = $this->typeBilletRepository->find($typeBilletId);
        if (!$typeBillet) {
            return new JsonResponse(['success' => false, 'message' => 'Type de billet non trouvé'], 404);
        }

        $event = $typeBillet->getEvenement();
        if (!$event || $event->getProfilOrganisateur()?->getId() !== $organizerProfile->getId()) {
            return new JsonResponse(['success' => false, 'message' => 'Accès non autorisé à cet événement'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $quantiteTotale = isset($data['quantiteTotale']) ? (int)$data['quantiteTotale'] : null;

        if ($quantiteTotale === null) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Quantité invalide',
                'errors' => ['quantiteTotale' => 'La quantité est requise']
            ], 400);
        }

        $result = $this->waitlistManagementService->updateQuantity($typeBillet, $quantiteTotale);

        if (!$result['success']) {
            return new JsonResponse($result, 400);
        }

        return new JsonResponse($result);
    }

    #[Route('/process/{userId}/{typeBilletId}', name: 'organisateur_waitlist_process', methods: ['POST'])]
    public function processWaitlistRequest(string $userId, string $typeBilletId, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        $organizerProfile = $this->organizerProfileRepository->findByUser($user);
        if (!$organizerProfile) {
            return new JsonResponse(['success' => false, 'message' => 'Profil organisateur non trouvé'], 403);
        }

        $waitlistUser = $this->userRepository->find($userId);
        if (!$waitlistUser) {
            return new JsonResponse(['success' => false, 'message' => 'Utilisateur non trouvé'], 404);
        }

        $typeBillet = $this->typeBilletRepository->find($typeBilletId);
        if (!$typeBillet) {
            return new JsonResponse(['success' => false, 'message' => 'Type de billet non trouvé'], 404);
        }

        $event = $typeBillet->getEvenement();
        if (!$event || $event->getProfilOrganisateur()?->getId() !== $organizerProfile->getId()) {
            return new JsonResponse(['success' => false, 'message' => 'Accès non autorisé à cet événement'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $action = $data['action'] ?? null;
        $quantiteTotale = isset($data['quantiteTotale']) ? (int)$data['quantiteTotale'] : null;
        $prixDeBase = isset($data['prixDeBase']) ? (float)$data['prixDeBase'] : null;

        $result = $this->waitlistManagementService->processWaitlistRequest(
            $waitlistUser,
            $typeBillet,
            $action,
            $quantiteTotale,
            $prixDeBase
        );

        $statusCode = $result['success'] ? 200 : 400;
        return new JsonResponse($result, $statusCode);
    }
}

