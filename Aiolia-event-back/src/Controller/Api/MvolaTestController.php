<?php

namespace App\Controller\Api;

use App\Service\Organisateur\MvolaPaymentClientService;
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
        private MvolaPaymentClientService $mvolaClient,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Test de connexion à l'API MVola
     * 
     * @example GET /api/mvola/test/connection
     */
    #[Route('/test/connection', name: 'api_mvola_test_connection', methods: ['GET'])]
    public function testConnection(): JsonResponse
    {
        try {
            $this->logger->info('Testing MVola API connection with new client');

            // Obtenir un token via le nouveau client
            $tokenResult = $this->mvolaClient->getAccessToken();
            
            if (!$tokenResult['success']) {
                throw new \RuntimeException('Échec de la connexion à l\'API MVola: ' . ($tokenResult['error'] ?? 'Erreur inconnue'));
            }

            return $this->json([
                'success' => true,
                'message' => 'Connexion à l\'API MVola réussie',
                'data' => [
                    'api_base_url' => $this->mvolaClient->getApiBaseUrl(),
                    'token_obtained' => true,
                    'token_preview' => isset($tokenResult['access_token']) ? 
                        substr($tokenResult['access_token'], 0, 50) . '...' : null,
                    'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
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
     * Test d'initiation de paiement MVola
     * 
     * @example POST /api/mvola/test/payment
     * Body: {
     *   "customer_phone": "0343500003",
     *   "amount": 1000,
     *   "description": "Test paiement",
     *   "reference": "OPTIONNEL-ref-personnalisee"
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
            $description = $data['description'] ?? 'Test paiement MVola';
            $reference = $data['reference'] ?? 'TEST-' . strtoupper(uniqid());

            $this->logger->info('Testing MVola payment initiation with new client', [
                'reference' => $reference,
                'customer_phone' => $customerPhone,
                'amount' => $amount
            ]);

            // Initier la transaction avec le nouveau client
            $result = $this->mvolaClient->initiateTransaction(
                $amount,
                $customerPhone,
                $reference,
                $description
            );

            // Si succès
            if ($result['success']) {
                return $this->json([
                    'success' => true,
                    'message' => 'Transaction initiée avec succès',
                    'data' => [
                        'reference' => $reference,
                        'server_correlation_id' => $result['serverCorrelationId'] ?? null,
                        'transaction_reference' => $result['transactionReference'] ?? null,
                        'status' => $result['status'] ?? 'processing',
                        'notification_method' => $result['notificationMethod'] ?? 'polling',
                        'next_steps' => [
                            'check_status_url' => isset($result['serverCorrelationId']) ? 
                                'http://127.0.0.1:8000/api/mvola/test/status/' . $result['serverCorrelationId'] : null,
                            'check_status_method' => 'GET'
                        ]
                    ]
                ], Response::HTTP_OK);
            } else {
                // Si erreur
                $errorMessage = $result['error'] ?? 'Erreur inconnue lors de l\'initiation de la transaction';
                
                $this->logger->error('MVola payment initiation failed', [
                    'error' => $errorMessage,
                    'reference' => $reference,
                    'customer' => $customerPhone,
                    'amount' => $amount
                ]);

                return $this->json([
                    'success' => false,
                    'error' => $errorMessage,
                    'debug_info' => [
                        'customer_phone' => $customerPhone,
                        'amount' => $amount,
                        'reference' => $reference,
                        'http_status' => $result['http_status'] ?? null,
                        'missing_field' => $result['missing_field'] ?? null,
                        'payload_sent' => $result['payload_sent'] ?? null
                    ]
                ], Response::HTTP_BAD_REQUEST);
            }

        } catch (\Exception $e) {
            $this->logger->error('MVola payment test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->json([
                'success' => false,
                'error' => 'Erreur système: ' . $e->getMessage(),
                'debug_info' => [
                    'customer_phone' => $customerPhone ?? 'non défini',
                    'amount' => $amount ?? 'non défini',
                    'reference' => $reference ?? 'non généré'
                ]
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Test de vérification du statut d'une transaction
     * 
     * @example GET /api/mvola/test/status/{serverCorrelationId}
     */
    #[Route('/test/status/{serverCorrelationId}', name: 'api_mvola_test_status', methods: ['GET'])]
    public function testTransactionStatus(string $serverCorrelationId): JsonResponse
    {
        try {
            $this->logger->info('Testing MVola transaction status check', [
                'server_correlation_id' => $serverCorrelationId
            ]);

            // Vérifier le statut avec le nouveau client
            $result = $this->mvolaClient->getTransactionStatus($serverCorrelationId);

            if ($result['success']) {
                return $this->json([
                    'success' => true,
                    'message' => 'Statut récupéré avec succès',
                    'data' => [
                        'server_correlation_id' => $serverCorrelationId,
                        'status' => $result['status'] ?? 'unknown',
                        'transaction_details' => $result['transaction'] ?? [],
                        'full_response' => $result['transaction'] ?? []
                    ]
                ], Response::HTTP_OK);
            } else {
                return $this->json([
                    'success' => false,
                    'error' => $result['error'] ?? 'Erreur inconnue lors de la vérification du statut',
                    'server_correlation_id' => $serverCorrelationId
                ], Response::HTTP_BAD_REQUEST);
            }

        } catch (\Exception $e) {
            $this->logger->error('MVola status check failed', [
                'server_correlation_id' => $serverCorrelationId,
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
                'server_correlation_id' => $serverCorrelationId
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Détails d'une transaction par son ID
     * 
     * @example GET /api/mvola/test/details/{transactionId}
     */
    #[Route('/test/details/{transactionId}', name: 'api_mvola_test_details', methods: ['GET'])]
    public function testTransactionDetails(string $transactionId): JsonResponse
    {
        try {
            $this->logger->info('Testing MVola transaction details', [
                'transaction_id' => $transactionId
            ]);

            // Récupérer les détails avec le nouveau client
            $result = $this->mvolaClient->getTransactionDetails($transactionId);

            if ($result['success']) {
                return $this->json([
                    'success' => true,
                    'message' => 'Détails récupérés avec succès',
                    'data' => [
                        'transaction_id' => $transactionId,
                        'details' => $result['transaction'] ?? [],
                        'full_response' => $result['transaction'] ?? []
                    ]
                ], Response::HTTP_OK);
            } else {
                return $this->json([
                    'success' => false,
                    'error' => $result['error'] ?? 'Erreur inconnue lors de la récupération des détails',
                    'transaction_id' => $transactionId
                ], Response::HTTP_BAD_REQUEST);
            }

        } catch (\Exception $e) {
            $this->logger->error('MVola details check failed', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId
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
            // Exemple: Mettre à jour le statut de la transaction dans votre base de données
            // $serverCorrelationId = $data['serverCorrelationId'] ?? null;
            // $status = $data['status'] ?? null;
            // $transactionReference = $data['transactionReference'] ?? null;
            
            return $this->json([
                'success' => true,
                'message' => 'Callback reçu et traité',
                'received_data' => $data
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
     * Vérification de l'environnement et de la configuration
     * 
     * @example GET /api/mvola/test/env
     */
    #[Route('/test/env', name: 'api_mvola_test_env', methods: ['GET'])]
    public function testEnv(): JsonResponse
    {
        return $this->json([
            'success' => true,
            'message' => 'Configuration MVola',
            'data' => [
                'consumer_key' => $_ENV['MVOLA_CONSUMER_KEY'] ?? 'NOT SET',
                'consumer_secret_preview' => substr($_ENV['MVOLA_CONSUMER_SECRET'] ?? 'NOT SET', 0, 10) . '...',
                'merchant_msisdn' => $_ENV['MVOLA_MERCHANT_MSISDN'] ?? 'NOT SET',
                'merchant_name' => $_ENV['MVOLA_MERCHANT_NAME'] ?? 'NOT SET',
                'api_base_url' => $_ENV['MVOLA_API_BASE_URL'] ?? 'NOT SET',
                'callback_url' => $_ENV['MVOLA_CALLBACK_URL'] ?? 'NOT SET',
                'environment' => $_ENV['MVOLA_ENVIRONMENT'] ?? 'NOT SET'
            ]
        ]);
    }

    /**
     * Test complet du flow MVola (initiation + vérification)
     * 
     * @example POST /api/mvola/test/full-flow
     * Body: {
     *   "customer_phone": "0343500003",
     *   "amount": 1000,
     *   "description": "Test flow complet"
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
            $description = $data['description'] ?? 'Test flow complet';
            $reference = 'FLOW-' . strtoupper(uniqid());

            $steps = [];
            $serverCorrelationId = null;
            $transactionResult = null;

            // Étape 1: Test de connexion
            $this->logger->info('Step 1: Testing connection');
            $tokenResult = $this->mvolaClient->getAccessToken();
            
            $steps[] = [
                'step' => 1,
                'name' => 'Connection test',
                'status' => $tokenResult['success'] ? 'success' : 'failed',
                'message' => $tokenResult['success'] ? 'Connexion établie' : 'Échec: ' . ($tokenResult['error'] ?? ''),
                'timestamp' => date('H:i:s')
            ];

            if (!$tokenResult['success']) {
                throw new \RuntimeException('Échec de la connexion initiale');
            }

            // Étape 2: Initiation de la transaction
            $this->logger->info('Step 2: Initiating transaction');
            $transactionResult = $this->mvolaClient->initiateTransaction(
                $amount,
                $customerPhone,
                $reference,
                $description
            );

            $serverCorrelationId = $transactionResult['serverCorrelationId'] ?? null;

            $steps[] = [
                'step' => 2,
                'name' => 'Payment initiation',
                'status' => $transactionResult['success'] ? 'success' : 'failed',
                'message' => $transactionResult['success'] ? 'Transaction initiée' : 'Échec: ' . ($transactionResult['error'] ?? ''),
                'server_correlation_id' => $serverCorrelationId,
                'initial_status' => $transactionResult['status'] ?? 'unknown',
                'timestamp' => date('H:i:s')
            ];

            if (!$transactionResult['success']) {
                throw new \RuntimeException('Échec de l\'initiation de la transaction');
            }

            // Étape 3: Vérification du statut (après un délai)
            if ($serverCorrelationId) {
                sleep(3); // Attendre 3 secondes
                
                $this->logger->info('Step 3: Checking transaction status');
                $statusResult = $this->mvolaClient->getTransactionStatus($serverCorrelationId);
                
                $steps[] = [
                    'step' => 3,
                    'name' => 'Status check',
                    'status' => $statusResult['success'] ? 'success' : 'warning',
                    'message' => $statusResult['success'] ? 
                        'Statut récupéré: ' . ($statusResult['status'] ?? 'unknown') : 
                        'Impossible de récupérer le statut: ' . ($statusResult['error'] ?? ''),
                    'final_status' => $statusResult['status'] ?? 'unknown',
                    'timestamp' => date('H:i:s')
                ];
            }

            return $this->json([
                'success' => true,
                'message' => 'Test du flow complet terminé',
                'data' => [
                    'reference' => $reference,
                    'server_correlation_id' => $serverCorrelationId,
                    'transaction_reference' => $transactionResult['transactionReference'] ?? null,
                    'steps' => $steps,
                    'summary' => [
                        'total_steps' => count($steps),
                        'successful_steps' => count(array_filter($steps, fn($step) => in_array($step['status'], ['success']))),
                        'failed_steps' => count(array_filter($steps, fn($step) => $step['status'] === 'failed')),
                        'final_status' => $steps[count($steps)-1]['final_status'] ?? 'unknown'
                    ]
                ]
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            $this->logger->error('MVola full flow test failed', [
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
                'steps' => $steps ?? [],
                'last_reference' => $reference ?? 'non généré',
                'last_server_correlation_id' => $serverCorrelationId ?? null
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}