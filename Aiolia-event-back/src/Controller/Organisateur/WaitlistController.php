<?php

namespace App\Controller\Organisateur;

use App\Entity\TypeBillet;
use App\Entity\User;
use App\Repository\Organisateur\InventaireBilletRepository;
use App\Repository\Organisateur\OrganizerProfileRepository;
use App\Repository\Organisateur\TypeBilletRepository;
use App\Repository\UserRepository;
use App\Service\Organisateur\InventaireBilletService;
use App\Service\Organisateur\TypeBilletService;
use App\Service\Organisateur\WaitlistService;
use App\Service\Organisateur\WaitlistEmailService;
use Doctrine\ORM\EntityManagerInterface;
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
        private TypeBilletService $typeBilletService,
        private InventaireBilletService $inventaireBilletService,
        private UserRepository $userRepository,
        private WaitlistEmailService $waitlistEmailService,
        private EntityManagerInterface $entityManager
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

        // Récupérer l'utilisateur de la liste d'attente
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
        $action = $data['action'] ?? null; // 'accept' ou 'reject'
        $quantiteTotale = isset($data['quantiteTotale']) ? (int)$data['quantiteTotale'] : null;
        $prixDeBase = isset($data['prixDeBase']) ? (float)$data['prixDeBase'] : null;

        if (!in_array($action, ['accept', 'reject'])) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Action invalide. Doit être "accept" ou "reject"'
            ], 400);
        }

        // Validation pour l'acceptation
        if ($action === 'accept') {
            if ($quantiteTotale === null || $quantiteTotale < 0) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Quantité invalide',
                    'errors' => ['quantiteTotale' => 'La quantité doit être un nombre positif']
                ], 400);
            }

            if ($prixDeBase === null || $prixDeBase < 0) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Prix invalide',
                    'errors' => ['prixDeBase' => 'Le prix doit être un nombre positif']
                ], 400);
            }

            $inventaire = $this->inventaireBilletService->getByTypeBillet($typeBillet);
            $quantiteVendue = $inventaire ? $inventaire->getQuantiteVendue() : 0;

            if ($quantiteTotale < $quantiteVendue) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Quantité invalide',
                    'errors' => ['quantiteTotale' => 'La quantité totale ne peut pas être inférieure à la quantité vendue (' . $quantiteVendue . ')']
                ], 400);
            }

            // Vérifier la capacité de l'événement
            $quantiteDisponible = $quantiteTotale - $quantiteVendue;
            $conn = $this->entityManager->getConnection();
            
            // Récupérer la quantité demandée en liste d'attente
            $waitlistSql = 'SELECT SUM(quantite_demandee) as quantite_demandee
                          FROM aiolia.listes_attente_billets
                          WHERE id_utilisateur = :userId
                          AND id_type_billet = :typeBilletId
                          AND statut = :statut';
            
            $waitlistResult = $conn->executeQuery($waitlistSql, [
                'userId' => $userId,
                'typeBilletId' => $typeBilletId,
                'statut' => 'pending'
            ])->fetchAssociative();
            
            $quantiteDemandee = (int)($waitlistResult['quantite_demandee'] ?? 0);
            
            if ($quantiteDisponible < $quantiteDemandee) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Capacité insuffisante',
                    'errors' => ['quantiteTotale' => 'La quantité disponible (' . $quantiteDisponible . ') est insuffisante pour satisfaire la demande (' . $quantiteDemandee . ')']
                ], 400);
            }

            // Mettre à jour le prix et la quantité
            $this->typeBilletService->update($typeBillet, [
                'prixDeBase' => $prixDeBase
            ]);

            if (!$inventaire) {
                $inventaire = $this->inventaireBilletService->create([
                    'quantiteTotale' => $quantiteTotale,
                    'quantiteVendue' => 0,
                    'quantiteReservee' => 0
                ], $typeBillet);
            } else {
                $this->inventaireBilletService->update($inventaire, [
                    'quantiteTotale' => $quantiteTotale
                ]);
            }

            // Supprimer l'entrée de la liste d'attente
            $deleteSql = 'DELETE FROM aiolia.listes_attente_billets
                         WHERE id_utilisateur = :userId
                         AND id_type_billet = :typeBilletId';
            
            $conn->executeStatement($deleteSql, [
                'userId' => $userId,
                'typeBilletId' => $typeBilletId
            ]);

            // Envoyer l'email de confirmation
            $this->waitlistEmailService->sendAcceptanceEmail($waitlistUser, $event, $typeBillet, $quantiteDemandee);

            return new JsonResponse([
                'success' => true,
                'message' => 'Liste d\'attente acceptée avec succès. Un email de confirmation a été envoyé à l\'utilisateur.'
            ]);
        } else {
            // Rejet
            // Supprimer l'entrée de la liste d'attente
            $conn = $this->entityManager->getConnection();
            $deleteSql = 'DELETE FROM aiolia.listes_attente_billets
                         WHERE id_utilisateur = :userId
                         AND id_type_billet = :typeBilletId';
            
            $conn->executeStatement($deleteSql, [
                'userId' => $userId,
                'typeBilletId' => $typeBilletId
            ]);

            // Envoyer l'email de rejet
            $this->waitlistEmailService->sendRejectionEmail($waitlistUser, $event, $typeBillet);

            return new JsonResponse([
                'success' => true,
                'message' => 'Liste d\'attente rejetée. Un email de notification a été envoyé à l\'utilisateur.'
            ]);
        }
    }
}

