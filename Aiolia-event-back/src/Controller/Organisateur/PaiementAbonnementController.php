<?php

namespace App\Controller\Organisateur;

use App\Service\Organisateur\PaiementAbonnementService;
use App\Service\Organisateur\MvolaPaymentClientService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/organisateur/paiement-abonnement')]
#[IsGranted('ROLE_ORGANIZER')]
class PaiementAbonnementController extends AbstractController
{
    public function __construct(
        private PaiementAbonnementService $paiementAbonnementService,
        private MvolaPaymentClientService $mvolaClient
    ) {
    }
    
    #[Route('', name: 'organisateur_paiement_abonnement_index', methods: ['GET'])]
    public function index(): Response
    {
        $niveaux = $this->paiementAbonnementService->getAvailableNiveaux();
        $plansGroupedByNiveau = $this->paiementAbonnementService->getPlansGroupedByNiveau();

        return $this->render('Organisateur/paiementAbonnement/index.html.twig', [
            'niveaux' => $niveaux,
            'plansGroupedByNiveau' => $plansGroupedByNiveau,
        ]);
    }

    #[Route('/initiate', name: 'organisateur_paiement_abonnement_initiate', methods: ['POST'])]
    public function initiate(Request $request): Response
    {
        $planId = $request->request->get('plan_id');
        $customerPhone = $request->request->get('customer_phone');

        // Validation basique
        if (empty($planId) || empty($customerPhone)) {
            $this->addFlash('error', 'Le plan et le numéro de téléphone sont requis.');
            return $this->redirectToRoute('organisateur_paiement_abonnement_index');
        }

        // Ici, vous devriez récupérer le plan depuis votre base de données
        // Pour l'exemple, on utilise un montant fixe
        $amount = 10000; // 10 000 Ar (exemple)
        
        // Générer une référence unique
        $reference = 'SUB-' . uniqid() . '-' . $planId;

        try {
            // Initier le paiement avec le nouveau service
            $result = $this->mvolaClient->initiateTransaction(
                $amount,
                $customerPhone,
                $reference,
                'Abonnement premium'
            );

            if ($result['success']) {
                $this->addFlash('success', 'Paiement initié avec succès ! ID de transaction: ' . 
                    ($result['serverCorrelationId'] ?? $reference));
                
                // Ici, vous devriez enregistrer la transaction dans votre base de données
                // avec le serverCorrelationId pour pouvoir vérifier le statut plus tard
                
                // Exemple de log ou de sauvegarde :
                // $this->paiementAbonnementService->saveTransaction(
                //     $planId,
                //     $customerPhone,
                //     $amount,
                //     $reference,
                //     $result['serverCorrelationId'] ?? null,
                //     $result['status'] ?? 'pending'
                // );
                
            } else {
                $this->addFlash('error', 'Erreur lors du paiement: ' . ($result['error'] ?? 'Erreur inconnue'));
            }

            return $this->redirectToRoute('organisateur_paiement_abonnement_index');
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur système: ' . $e->getMessage());
            return $this->redirectToRoute('organisateur_paiement_abonnement_index');
        }
    }

    /**
     * Endpoint API pour vérifier le statut d'un paiement
     * (Utile si vous avez une page de statut en temps réel)
     */
    #[Route('/status/{serverCorrelationId}', name: 'organisateur_paiement_abonnement_status', methods: ['GET'])]
    public function checkStatus(string $serverCorrelationId): JsonResponse
    {
        try {
            $result = $this->mvolaClient->getTransactionStatus($serverCorrelationId);
            
            return $this->json([
                'success' => $result['success'],
                'status' => $result['status'] ?? 'unknown',
                'transaction' => $result['transaction'] ?? null,
                'error' => $result['error'] ?? null,
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Endpoint pour recevoir les callbacks MVola
     * (Doit être public - configurez dans security.yaml)
     */
    #[Route('/callback', name: 'organisateur_paiement_abonnement_callback', methods: ['POST'])]
    public function callback(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            // Log pour debug
            error_log('[MVola Callback] Données reçues: ' . json_encode($data));
            
            $serverCorrelationId = $data['serverCorrelationId'] ?? null;
            $status = $data['status'] ?? null;
            $transactionReference = $data['transactionReference'] ?? null;
            
            if ($serverCorrelationId && $status) {
                // Mettre à jour le statut dans votre base de données
                // $this->paiementAbonnementService->updateTransactionStatus(
                //     $serverCorrelationId,
                //     $status,
                //     $transactionReference
                // );
                
                error_log("[MVola Callback] Transaction $serverCorrelationId mise à jour: $status");
            }
            
            return $this->json([
                'success' => true,
                'message' => 'Callback traité',
            ]);
            
        } catch (\Exception $e) {
            error_log('[MVola Callback] Erreur: ' . $e->getMessage());
            
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}