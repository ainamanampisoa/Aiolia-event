<?php

namespace App\Controller\Organisateur;

use App\Entity\TypeBillet;
use App\Repository\Organisateur\InventaireBilletRepository;
use App\Repository\Organisateur\OrganizerProfileRepository;
use App\Repository\Organisateur\TypeBilletRepository;
use App\Service\Organisateur\InventaireBilletService;
use App\Service\Organisateur\WaitlistService;
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
        private OrganizerProfileRepository $organizerProfileRepository,
        private TypeBilletRepository $typeBilletRepository,
        private InventaireBilletService $inventaireBilletService
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

        // Récupérer les données formatées avec pagination
        $data = $this->waitlistService->getFormattedWaitlistDataPaginated($organizerProfile->getId(), $page, $perPage);

        return $this->render('Organisateur/waitlist/index.html.twig', [
            'users' => $data['users'],
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

        if ($quantiteTotale === null || $quantiteTotale < 0) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Quantité invalide',
                'errors' => ['quantiteTotale' => 'La quantité doit être un nombre positif']
            ], 400);
        }

        $inventaire = $this->inventaireBilletService->getByTypeBillet($typeBillet);
        
        if (!$inventaire) {
            $inventaire = $this->inventaireBilletService->create([
                'quantiteTotale' => $quantiteTotale,
                'quantiteVendue' => 0,
                'quantiteReservee' => 0
            ], $typeBillet);
        } else {
            if ($quantiteTotale < $inventaire->getQuantiteVendue()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Quantité invalide',
                    'errors' => ['quantiteTotale' => 'La quantité totale ne peut pas être inférieure à la quantité vendue (' . $inventaire->getQuantiteVendue() . ')']
                ], 400);
            }

            $this->inventaireBilletService->update($inventaire, [
                'quantiteTotale' => $quantiteTotale
            ]);
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'Quantité mise à jour avec succès',
            'data' => [
                'quantiteTotale' => $inventaire->getQuantiteTotale(),
                'quantiteVendue' => $inventaire->getQuantiteVendue(),
                'quantiteDisponible' => $inventaire->getQuantiteDisponible()
            ]
        ]);
    }
}

