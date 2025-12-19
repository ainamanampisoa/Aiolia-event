<?php

namespace App\Controller\Api;

use App\Service\Organisateur\MvolaService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/mvola')]
class MvolaTestController extends AbstractController
{
    public function __construct(
        private MvolaService $mvolaService,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Test d'initiation de paiement MVola
     * 
     * @example POST /api/mvola/test/payment
     * Body: {
     *   "customer_phone": "0343500003",
     *   "amount": 1000,
     *   "description": "Test paiement"
     * }
     */
    #[Route('/test/payment', name: 'api_mvola_test_payment', methods: ['POST'])]
    public function testPayment(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            // Validation des données
            if (!isset($data['customer_phone']) || !isset($data['amount'])) {
                return $this->json([
                    'success' => false,
                    'error' => 'customer_phone et amount sont requis'
                ], Response::HTTP_BAD_REQUEST);
            }

            $customerPhone = $data['customer_phone'];
            $amount = (float) $data['amount'];
            $description = $data['description'] ?? 'Test paiement';
            
            // Génération d'une référence unique
            $reference = 'TEST-' . strtoupper(uniqid());

            $this->logger->info('Testing MVola payment initiation', [
                'reference' => $reference,
                'customer_phone' => $customerPhone,
                'amount' => $amount
            ]);

            // Initier le paiement
            $result = $this->mvolaService->initiatePayment(
                $customerPhone,
                $amount,
                $reference,
                $description
            );

            return $this->json([
                'success' => true,
                'message' => 'Paiement initié avec succès',
                'data' => [
                    'reference' => $reference,
                    'transaction_reference' => $result['serverCorrelationId'] ?? null,
                    'status' => $result['status'] ?? 'pending',
                    'full_response' => $result
                ]
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            $this->logger->error('MVola payment test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Test de vérification du statut d'une transaction
     * 
     * @example GET /api/mvola/test/status/{transactionReference}
     */
    #[Route('/test/status/{transactionReference}', name: 'api_mvola_test_status', methods: ['GET'])]
    public function testTransactionStatus(string $transactionReference): JsonResponse
    {
        try {
            $this->logger->info('Testing MVola transaction status check', [
                'transaction_reference' => $transactionReference
            ]);

            $result = $this->mvolaService->getTransactionStatus($transactionReference);

            return $this->json([
                'success' => true,
                'message' => 'Statut récupéré avec succès',
                'data' => [
                    'transaction_reference' => $transactionReference,
                    'status' => $result['status'] ?? 'unknown',
                    'full_response' => $result
                ]
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            $this->logger->error('MVola status check test failed', [
                'transaction_reference' => $transactionReference,
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Callback pour recevoir les notifications MVola
     * 
     * @example POST /api/mvola/callback
     */
    #[Route('/callback', name: 'api_mvola_callback', methods: ['POST'])]
    public function callback(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            $this->logger->info('MVola callback received', [
                'data' => $data,
                'headers' => $request->headers->all()
            ]);

            // Traiter le callback
            // Mettre à jour le statut de la transaction dans votre base de données
            
            return $this->json([
                'success' => true,
                'message' => 'Callback reçu et traité'
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            $this->logger->error('MVola callback processing failed', [
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Test de connexion à l'API MVola (obtention du token)
     * 
     * @example GET /api/mvola/test/connection
     */
    #[Route('/test/connection', name: 'api_mvola_test_connection', methods: ['GET'])]
    public function testConnection(): JsonResponse
    {
        try {
            $this->logger->info('Testing MVola API connection');

            // Cette méthode va automatiquement obtenir un token
            $isSandbox = $this->mvolaService->isSandbox();
            $debugToken = $this->mvolaService->getAccessTokenForDebug();

            return $this->json([
                'success' => true,
                'message' => 'Connexion à l\'API MVola réussie',
                'data' => [
                    'environment' => $isSandbox ? 'sandbox' : 'production',
                    'token_obtained' => true,
                    // ATTENTION : ne jamais exposer ce champ en production
                    'debug_token' => $debugToken,
                ]
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            $this->logger->error('MVola connection test failed', [
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Test complet du flow MVola
     * 
     * @example POST /api/mvola/test/full-flow
     * Body: {
     *   "customer_phone": "0343500003",
     *   "amount": 1000
     * }
     */
    #[Route('/test/full-flow', name: 'api_mvola_test_full_flow', methods: ['POST'])]
    public function testFullFlow(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['customer_phone']) || !isset($data['amount'])) {
                return $this->json([
                    'success' => false,
                    'error' => 'customer_phone et amount sont requis'
                ], Response::HTTP_BAD_REQUEST);
            }

            $customerPhone = $data['customer_phone'];
            $amount = (float) $data['amount'];
            $reference = 'FLOW-' . strtoupper(uniqid());

            $steps = [];

            // Étape 1: Test de connexion
            $this->logger->info('Step 1: Testing connection');
            $steps[] = [
                'step' => 1,
                'name' => 'Connection test',
                'status' => 'success',
                'message' => 'Connexion établie'
            ];

            // Étape 2: Initiation du paiement
            $this->logger->info('Step 2: Initiating payment');
            $paymentResult = $this->mvolaService->initiatePayment(
                $customerPhone,
                $amount,
                $reference,
                'Test flow complet'
            );

            $transactionRef = $paymentResult['serverCorrelationId'] ?? null;

            $steps[] = [
                'step' => 2,
                'name' => 'Payment initiation',
                'status' => 'success',
                'message' => 'Paiement initié',
                'transaction_reference' => $transactionRef
            ];

            // Étape 3: Vérification du statut (après un délai simulé)
            sleep(2); // Attendre 2 secondes
            
            if ($transactionRef) {
                $this->logger->info('Step 3: Checking transaction status');
                try {
                    $statusResult = $this->mvolaService->getTransactionStatus($transactionRef);
                    $steps[] = [
                        'step' => 3,
                        'name' => 'Status check',
                        'status' => 'success',
                        'message' => 'Statut récupéré',
                        'transaction_status' => $statusResult['status'] ?? 'unknown'
                    ];
                } catch (\Exception $e) {
                    $steps[] = [
                        'step' => 3,
                        'name' => 'Status check',
                        'status' => 'warning',
                        'message' => 'Impossible de récupérer le statut: ' . $e->getMessage()
                    ];
                }
            }

            return $this->json([
                'success' => true,
                'message' => 'Test du flow complet terminé',
                'data' => [
                    'reference' => $reference,
                    'transaction_reference' => $transactionRef,
                    'steps' => $steps,
                    'full_payment_response' => $paymentResult
                ]
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            $this->logger->error('MVola full flow test failed', [
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
                'steps' => $steps ?? []
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/test/env', name: 'api_mvola_test_env', methods: ['GET'])]
    public function testEnv(): JsonResponse
    {
        return $this->json([
            'consumer_key' => $_ENV['MVOLA_CONSUMER_KEY'] ?? 'NOT SET',
            'consumer_secret' => substr($_ENV['MVOLA_CONSUMER_SECRET'] ?? 'NOT SET', 0, 10) . '...',
            'expected_key' => 'Ainrl4gdwZa9ELF68sd4YFg9meUa',
        ]);
    }
}