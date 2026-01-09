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
     */
    #[Route('/test/connection', name: 'api_mvola_test_connection', methods: ['GET'])]
    public function testConnection(): JsonResponse
    {
        try {
            $this->logger->info('Testing MVola API connection');

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
     * Voir les détails exacts de la requête qui sera envoyée à MVola
     */
    #[Route('/test/request-details', name: 'api_mvola_test_request_details', methods: ['POST'])] // CORRIGÉ: POST
    public function testRequestDetails(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            $customerPhone = $data['customer_phone'] ?? '0343500003';
            $amount = $data['amount'] ?? 100;
            $description = $data['description'] ?? 'Test request details';
            $reference = 'DETAILS-' . time() . '-' . rand(1000, 9999);
            $pin = $data['pin'] ?? null;
            
            // Récupérer le token pour les logs
            $tokenResult = $this->mvolaClient->getAccessToken();
            
            // Simuler ce qui sera envoyé
            $payload = [
                'amount' => (string) ((int) round($amount)),
                'currency' => 'Ar',
                'descriptionText' => substr($description, 0, 50),
                'requestingOrganisationTransactionReference' => $reference,
                'requestDate' => date('Y-m-d\TH:i:s.v\Z'),
                'debitParty' => [
                    [
                        'key' => 'msisdn',
                        'value' => $customerPhone
                    ]
                ],
                'creditParty' => [
                    [
                        'key' => 'msisdn',
                        'value' => $_ENV['MVOLA_MERCHANT_MSISDN'] ?? '0340000000'
                    ]
                ],
                'metadata' => [
                    [
                        'key' => 'partnerName',
                        'value' => $_ENV['MVOLA_MERCHANT_NAME'] ?? 'Test'
                    ],
                    [
                        'key' => 'fc',
                        'value' => 'USD'
                    ],
                    [
                        'key' => 'amountFc',
                        'value' => '1'
                    ]
                ]
            ];
            
            // Ajouter le PIN si fourni
            if ($pin !== null) {
                $payload['metadata'][] = [
                    'key' => 'pin',
                    'value' => $pin
                ];
            }
            
            // Vérifier le format JSON
            $jsonPayload = json_encode($payload, JSON_PRETTY_PRINT);
            $jsonError = json_last_error();
            
            return $this->json([
                'success' => true,
                'message' => 'Détails de la requête',
                'data' => [
                    'token_info' => [
                        'obtained' => $tokenResult['success'],
                        'length' => $tokenResult['success'] ? strlen($tokenResult['access_token']) : 0,
                        'preview' => $tokenResult['success'] ? substr($tokenResult['access_token'], 0, 20) . '...' . substr($tokenResult['access_token'], -20) : null
                    ],
                    'request_payload' => $payload,
                    'json_validation' => [
                        'valid' => $jsonError === JSON_ERROR_NONE,
                        'error' => $jsonError !== JSON_ERROR_NONE ? json_last_error_msg() : 'Aucune erreur',
                        'encoded_length' => strlen($jsonPayload)
                    ],
                    'headers_example' => [
                        'Authorization' => 'Bearer ' . ($tokenResult['success'] ? '***' . substr($tokenResult['access_token'], -10) : 'NO_TOKEN'),
                        'Version' => '1.0',
                        'X-CorrelationID' => 'test-' . time(),
                        'UserLanguage' => 'mg',
                        'UserAccountIdentifier' => 'msisdn;' . ($_ENV['MVOLA_MERCHANT_MSISDN'] ?? '0340000000'),
                        'partnerName' => $_ENV['MVOLA_MERCHANT_NAME'] ?? 'Test',
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json'
                    ],
                    'url' => rtrim($_ENV['MVOLA_API_BASE_URL'] ?? 'https://devapi.mvola.mg', '/') . '/mvola/mm/transactions/type/merchantpay/1.0.0',
                    'request_info' => [
                        'customer_phone' => $customerPhone,
                        'amount' => $amount,
                        'description' => $description,
                        'reference' => $reference,
                        'has_pin' => $pin !== null
                    ],
                    'common_issues' => [
                        '1. Format de date' => 'Doit être YYYY-MM-DDThh:mm:ss.sssZ (ISO 8601 avec millisecondes)',
                        '2. Montant' => 'Doit être une string, pas un number (ex: "1000" pas 1000)',
                        '3. MSISDN client' => 'Doit être au format 034xxxxxxx',
                        '4. MSISDN merchant' => 'Doit être valide et configuré dans MVOLA_MERCHANT_MSISDN',
                        '5. Currency' => 'Doit être "Ar" pour Ariary',
                        '6. Reference' => 'Doit être unique à chaque requête'
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Vérifier les problèmes courants
     */
    #[Route('/test/common-issues', name: 'api_mvola_test_common_issues', methods: ['GET'])]
    public function testCommonIssues(): JsonResponse
    {
        // Tester différents formats de requête
        $testCases = [
            [
                'name' => 'Problème 1: Format de date incorrect',
                'incorrect' => date('Y-m-d H:i:s'), // Mauvais format
                'correct' => date('Y-m-d\TH:i:s.v\Z'), // Bon format
                'description' => 'MVola exige le format ISO 8601 avec millisecondes'
            ],
            [
                'name' => 'Problème 2: Montant comme nombre',
                'incorrect' => 100, // Number au lieu de string
                'correct' => '100', // String
                'description' => 'Le montant doit être une chaîne de caractères'
            ],
            [
                'name' => 'Problème 3: MSISDN mal formaté',
                'incorrect' => '+261341234567', // Avec +261
                'correct' => '0341234567', // Format local
                'description' => 'Format attendu: 034xxxxxxx (sans indicatif pays)'
            ],
            [
                'name' => 'Problème 4: Currency incorrect',
                'incorrect' => 'MGA', // Code ISO incorrect
                'correct' => 'Ar', // Code MVola
                'description' => 'MVola utilise "Ar" pour Ariary, pas "MGA"'
            ],
            [
                'name' => 'Problème 5: Metadata manquante',
                'incorrect' => [], // Metadata vide
                'correct' => [
                    ['key' => 'partnerName', 'value' => 'VotreNom'],
                    ['key' => 'fc', 'value' => 'USD'],
                    ['key' => 'amountFc', 'value' => '1']
                ],
                'description' => 'Les metadata sont requises avec partnerName, fc, amountFc'
            ]
        ];
        
        // Vérifier la configuration actuelle
        $currentConfig = [
            'MVOLA_MERCHANT_MSISDN' => $_ENV['MVOLA_MERCHANT_MSISDN'] ?? 'NOT SET',
            'MVOLA_MERCHANT_NAME' => $_ENV['MVOLA_MERCHANT_NAME'] ?? 'NOT SET',
            'MVOLA_API_BASE_URL' => $_ENV['MVOLA_API_BASE_URL'] ?? 'NOT SET',
            'APP_ENV' => $_ENV['APP_ENV'] ?? 'NOT SET'
        ];
        
        // Normaliser le MSISDN pour vérification
        $normalizedMsisdn = $this->normalizeMsisdnForTest($_ENV['MVOLA_MERCHANT_MSISDN'] ?? '');
        
        return $this->json([
            'success' => true,
            'message' => 'Problèmes courants MVola',
            'common_issues' => $testCases,
            'current_configuration' => $currentConfig,
            'validation' => [
                'merchant_msisdn_normalized' => $normalizedMsisdn,
                'merchant_msisdn_valid' => strlen($normalizedMsisdn) >= 10 && strpos($normalizedMsisdn, '0') === 0,
                'missing_configurations' => array_filter($currentConfig, fn($value) => $value === 'NOT SET' || empty($value))
            ],
            'recommendations' => [
                'Vérifiez .env.local' => 'Assurez-vous que toutes les variables MVOLA_* sont définies',
                'Format MSISDN' => 'Utilisez le format 034xxxxxxx (10 chiffres)',
                'Test minimal' => 'Commencez avec un montant de 100 MGA',
                'Contact support' => 'Si l\'erreur 500 persiste, contactez le support MVola'
            ]
        ]);
    }

    /**
     * Tester avec une requête HTTP directe
     */
    #[Route('/test/direct-http', name: 'api_mvola_test_direct_http', methods: ['POST'])]
    public function testDirectHttp(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            $customerPhone = $data['customer_phone'] ?? '0343500003';
            $amount = $data['amount'] ?? 100;
            $reference = 'DIRECT-' . time() . '-' . rand(1000, 9999);
            
            // Obtenir le token d'abord
            $tokenResult = $this->mvolaClient->getAccessToken();
            if (!$tokenResult['success']) {
                return $this->json([
                    'success' => false,
                    'error' => 'Échec d\'obtention du token: ' . ($tokenResult['error'] ?? ''),
                    'step' => 'token_retrieval'
                ], Response::HTTP_BAD_REQUEST);
            }
        
            $accessToken = $tokenResult['access_token'];
            $apiBaseUrl = $_ENV['MVOLA_API_BASE_URL'] ?? 'https://devapi.mvola.mg';
            $endpoint = rtrim($apiBaseUrl, '/') . '/mvola/mm/transactions/type/merchantpay/1.0.0';
            
            // Préparer la requête très simple
            $payload = [
                'amount' => (string) ((int) round($amount)),
                'currency' => 'Ar',
                'descriptionText' => 'Test HTTP direct',
                'requestingOrganisationTransactionReference' => $reference,
                'requestDate' => $this->generateRequestDate(),
                'debitParty' => [
                    [
                        'key' => 'msisdn',
                        'value' => $customerPhone
                    ]
                ],
                'creditParty' => [
                    [
                        'key' => 'msisdn',
                        'value' => $_ENV['MVOLA_MERCHANT_MSISDN'] ?? '0340000000'
                    ]
                ],
                'metadata' => [
                    [
                        'key' => 'partnerName',
                        'value' => $_ENV['MVOLA_MERCHANT_NAME'] ?? 'TestAPI'
                    ]
                ]
            ];
            
            // Utiliser curl
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $endpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $accessToken,
                    'Version: 1.0',
                    'X-CorrelationID: ' . $reference,
                    'UserLanguage: mg',
                    'UserAccountIdentifier: msisdn;' . ($_ENV['MVOLA_MERCHANT_MSISDN'] ?? '0340000000'),
                    'partnerName: ' . ($_ENV['MVOLA_MERCHANT_NAME'] ?? 'TestAPI'),
                    'Content-Type: application/json',
                    'Accept: application/json'
                ],
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HEADER => true, // Pour voir les headers de réponse
                CURLINFO_HEADER_OUT => true // Pour voir les headers envoyés
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            $sentHeaders = curl_getinfo($ch, CURLINFO_HEADER_OUT);
            
            // Séparer headers et body
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $responseHeaders = substr($response, 0, $headerSize);
            $responseBody = substr($response, $headerSize);
            
            curl_close($ch);
            
            // Analyser la réponse
            $responseData = json_decode($responseBody, true) ?: $responseBody;
            
            return $this->json([
                'success' => $httpCode >= 200 && $httpCode < 300,
                'http_status' => $httpCode,
                'curl_error' => $error ?: 'Aucune erreur',
                'response' => $responseData,
                'response_headers' => $responseHeaders,
                'request_details' => [
                    'url' => $endpoint,
                    'payload' => $payload,
                    'headers_sent' => $sentHeaders,
                    'token_preview' => substr($accessToken, 0, 20) . '...',
                    'reference' => $reference
                ],
                'analysis' => [
                    'token_valid' => !empty($accessToken),
                    'payload_valid_json' => json_last_error() === JSON_ERROR_NONE,
                    'is_500_error' => $httpCode === 500,
                    'suggestion' => $httpCode === 500 ? 'Problème côté serveur MVola. Contactez le support.' : 'Analysez la réponse ci-dessus'
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
                'suggestion' => 'Vérifiez que curl est activé dans PHP'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Documentation MVola
     */
    #[Route('/test/documentation', name: 'api_mvola_test_documentation', methods: ['GET'])]
    public function testDocumentation(): JsonResponse
    {
        $expectedStructure = [
            'required_fields' => [
                'amount' => 'string (ex: "1000") - Montant en Ariary',
                'currency' => 'string ("Ar" pour Ariary)',
                'descriptionText' => 'string (max 50 caractères)',
                'requestingOrganisationTransactionReference' => 'string (référence unique)',
                'requestDate' => 'string (ISO 8601 avec millisecondes: YYYY-MM-DDThh:mm:ss.sssZ)',
                'debitParty' => 'array of objects with key/value (client)',
                'creditParty' => 'array of objects with key/value (merchant)',
                'metadata' => 'array of objects with key/value (minimum: partnerName)'
            ],
            'example_request' => [
                'amount' => '1000',
                'currency' => 'Ar',
                'descriptionText' => 'Payment for goods',
                'requestingOrganisationTransactionReference' => 'TXN-' . time(),
                'requestDate' => date('Y-m-d\TH:i:s.v\Z'),
                'debitParty' => [
                    ['key' => 'msisdn', 'value' => '0341234567']
                ],
                'creditParty' => [
                    ['key' => 'msisdn', 'value' => '0349876543']
                ],
                'metadata' => [
                    ['key' => 'partnerName', 'value' => 'TestMerchant'],
                    ['key' => 'fc', 'value' => 'USD'],
                    ['key' => 'amountFc', 'value' => '1']
                ]
            ],
            'common_errors' => [
                '400' => 'Bad Request - Paramètres invalides',
                '401' => 'Unauthorized - Token invalide ou expiré',
                '500' => 'Internal Server Error - Problème serveur MVola',
                '4002' => 'DB Update Error - Transaction peut être créée malgré l\'erreur'
            ],
            'testing_recommendations' => [
                '1. Test token' => 'Vérifiez d\'abord que vous obtenez un token valide',
                '2. Format date' => 'Utilisez le bon format ISO 8601 avec millisecondes',
                '3. Montant minimal' => 'Commencez avec 100 MGA',
                '4. Référence unique' => 'Générez une nouvelle référence à chaque test',
                '5. MSISDN test' => 'Utilisez 0343500003 pour les tests MVola',
                '6. Vérifiez logs' => 'Consultez var/log/dev.log pour les détails'
            ]
        ];
        
        return $this->json([
            'success' => true,
            'documentation' => $expectedStructure,
            'quick_test' => [
                'test_token' => 'GET /api/mvola/test/connection',
                'test_request_details' => 'POST /api/mvola/test/request-details avec {"customer_phone": "0343500003", "amount": 100}',
                'test_payment' => 'POST /api/mvola/test/payment avec les mêmes données',
                'check_common_issues' => 'GET /api/mvola/test/common-issues'
            ]
        ]);
    }

    /**
     * Test d'initiation de paiement MVola
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
            $pin = $data['pin'] ?? null;

            $this->logger->info('Testing MVola payment initiation', [
                'reference' => $reference,
                'customer_phone' => $customerPhone,
                'amount' => $amount,
                'has_pin' => $pin !== null
            ]);

            // Initier la transaction
            $result = $this->mvolaClient->initiateTransaction(
                $amount,
                $customerPhone,
                $reference,
                $description,
                $pin
            );

            // Retourner le résultat tel quel pour debug
            return $this->json($result, 
                $result['success'] ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST
            );

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
     */
    #[Route('/test/status/{serverCorrelationId}', name: 'api_mvola_test_status', methods: ['GET'])]
    public function testTransactionStatus(string $serverCorrelationId): JsonResponse
    {
        try {
            $this->logger->info('Testing MVola transaction status check', [
                'server_correlation_id' => $serverCorrelationId
            ]);

            $result = $this->mvolaClient->getTransactionStatus($serverCorrelationId);

            return $this->json($result, 
                $result['success'] ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST
            );

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
     * Helper: Normaliser MSISDN pour les tests
     */
    private function normalizeMsisdnForTest(string $msisdn): string
    {
        // Même logique que dans le service
        $msisdn = preg_replace('/[^0-9+]/', '', trim($msisdn));
        
        if (strpos($msisdn, '+261') === 0) {
            $msisdn = '0' . substr($msisdn, 4);
        }
        
        if (strpos($msisdn, '261') === 0 && strlen($msisdn) > 9) {
            $msisdn = '0' . substr($msisdn, 3);
        }
        
        if (strpos($msisdn, '0') !== 0 && strlen($msisdn) >= 9) {
            $msisdn = '0' . $msisdn;
        }
        
        return $msisdn;
    }

    /**
     * Helper: Générer une date au format MVola
     */
    private function generateRequestDate(): string
    {
        $microtime = microtime(true);
        $seconds = floor($microtime);
        $milliseconds = str_pad((int)(($microtime - $seconds) * 1000), 3, '0', STR_PAD_LEFT);
        return gmdate('Y-m-d\TH:i:s.', $seconds) . $milliseconds . 'Z';
    }

    /**
     * Tester avec retry automatique
     */
    #[Route('/test/payment-with-retry', name: 'api_mvola_test_payment_with_retry', methods: ['POST'])]
    public function testPaymentWithRetry(Request $request): JsonResponse
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
            $description = $data['description'] ?? 'Test avec retry';
            $maxRetries = $data['max_retries'] ?? 2;
            $pin = $data['pin'] ?? null;

            $attempts = [];
            $finalResult = null;

            for ($i = 1; $i <= $maxRetries; $i++) {
                $reference = 'RETRY-' . $i . '-' . time();
                
                $this->logger->info('Payment attempt ' . $i, [
                    'reference' => $reference,
                    'customer' => $customerPhone,
                    'amount' => $amount
                ]);

                $result = $this->mvolaClient->initiateTransaction(
                    $amount,
                    $customerPhone,
                    $reference,
                    $description,
                    $pin
                );

                $attempts[] = [
                    'attempt' => $i,
                    'reference' => $reference,
                    'result' => $result
                ];

                if ($result['success']) {
                    $finalResult = $result;
                    break;
                }

                // Attendre avant la prochaine tentative
                if ($i < $maxRetries) {
                    sleep(1);
                }
            }

            return $this->json([
                'success' => $finalResult ? true : false,
                'attempts' => $attempts,
                'final_result' => $finalResult,
                'summary' => [
                    'total_attempts' => count($attempts),
                    'succeeded' => $finalResult ? true : false,
                    'has_4002_error' => false // À implémenter si nécessaire
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}