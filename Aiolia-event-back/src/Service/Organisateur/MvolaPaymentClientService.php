<?php

namespace App\Service\Organisateur;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Client pour l'API MVola MerchantPay avec support du code PIN.
 * 
 * Documentation basée sur API_MerchantPay.pdf v1.0
 * Sandbox URL: https://devapi.mvola.mg
 * Production URL: https://api.mvola.mg
 */
class MvolaPaymentClientService
{
    private ?string $cachedAccessToken = null;
    private ?int $tokenExpiresAt = null;

    private HttpClientInterface $httpClient;

    private string $apiBaseUrl;
    private string $consumerKey;
    private string $consumerSecret;
    private string $merchantMsisdn;
    private string $merchantName;
    private ?string $callbackUrl;
    private ?LoggerInterface $logger;

    public function __construct(
        string $apiBaseUrl,
        string $consumerKey,
        string $consumerSecret,
        string $merchantMsisdn,
        string $merchantName,
        ?string $callbackUrl = null,
        ?HttpClientInterface $httpClient = null,
        ?LoggerInterface $logger = null,
    ) {
        if ($httpClient === null) {
            $this->httpClient = HttpClient::create([
                'timeout' => 30,
                'verify_peer' => true,
                'verify_host' => true,
            ]);
        } else {
            $this->httpClient = $httpClient;
        }
        $this->apiBaseUrl = $apiBaseUrl;
        $this->consumerKey = $consumerKey;
        $this->consumerSecret = $consumerSecret;
        $this->merchantMsisdn = $merchantMsisdn;
        $this->merchantName = $merchantName;
        $this->callbackUrl = $callbackUrl;
        $this->logger = $logger;
    }

    /**
     * Retourne l'URL de base de l'API MVola.
     */
    public function getApiBaseUrl(): string
    {
        return $this->apiBaseUrl;
    }

    /**
     * Méthode helper pour logger
     */
    private function log(string $level, string $message, array $context = []): void
    {
        $logMessage = '[MVola] ' . $message;
        if (!empty($context)) {
            $logMessage .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        }
        
        if ($this->logger !== null) {
            $this->logger->log($level, $message, $context);
        }

        error_log($logMessage);
        
        $logFile = sys_get_temp_dir() . '/mvola_debug.log';
        @file_put_contents($logFile, date('Y-m-d H:i:s') . ' - ' . $logMessage . "\n", FILE_APPEND);
    }

    /**
     * Obtient un access token via l'API d'authentification MVola.
     */
    public function getAccessToken(): array
    {
        if ($this->cachedAccessToken !== null && $this->tokenExpiresAt !== null && time() < $this->tokenExpiresAt) {
            return [
                'success' => true,
                'access_token' => $this->cachedAccessToken,
            ];
        }

        try {
            $authUrl = rtrim($this->apiBaseUrl, '/') . '/token';

            $response = $this->httpClient->request('POST', $authUrl, [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($this->consumerKey . ':' . $this->consumerSecret),
                    'Cache-Control' => 'no-cache',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => http_build_query([
                    'grant_type' => 'client_credentials',
                    'scope' => 'EXT_INT_MVOLA_SCOPE',
                ]),
            ]);

            $data = $response->toArray(false);

            if (!isset($data['access_token'])) {
                return [
                    'success' => false,
                    'error' => 'Impossible de récupérer un access_token MVola (token manquant).',
                    'raw_response' => $data,
                ];
            }

            $this->cachedAccessToken = $data['access_token'];
            $this->tokenExpiresAt = time() + (55 * 60);

            return [
                'success' => true,
                'access_token' => $this->cachedAccessToken,
            ];
        } catch (HttpExceptionInterface | TransportExceptionInterface $e) {
            $this->log('error', 'Erreur authentification', [
                'type' => get_class($e),
                'message' => $e->getMessage(),
                'url' => $authUrl ?? 'unknown',
            ]);
            
            return [
                'success' => false,
                'error' => 'Erreur lors de l\'authentification MVola: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Génère un X-CorrelationID unique pour la requête.
     */
    private function generateCorrelationId(): string
    {
        $random = random_int(10000000, 99999999);
        return 'mvola-' . $random;
    }

    /**
     * Normalise un numéro de téléphone au format MVola (034xxxxxxx).
     */
    private function normalizeMsisdn(string $msisdn): string
    {
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
        
        if (strlen($msisdn) < 10 || strpos($msisdn, '0') !== 0) {
            error_log('[MVola] ATTENTION: Format de numéro suspect: ' . $msisdn);
        }
        
        return $msisdn;
    }

    /**
     * Valide le format du code PIN MVola (4-6 chiffres)
     */
    private function validatePin(string $pin): bool
    {
        return preg_match('/^[0-9]{4,6}$/', $pin) === 1;
    }

    /**
     * Extrait le serverCorrelationId des headers de la réponse MVola.
     * AMÉLIORÉ : Extraction plus robuste avec multiples tentatives
     */
    private function extractCorrelationIdFromResponse($response): ?string
    {
        try {
            $headers = $response->getHeaders(false); // false = ne pas throw d'exception
            
            $this->log('debug', 'Extracting correlationId from headers', [
                'available_headers' => array_keys($headers)
            ]);
            
            // Liste exhaustive de headers possibles (avec variations de casse)
            $possibleHeaders = [
                'x-correlationid',
                'x-correlation-id',
                'X-CorrelationID',
                'X-Correlation-ID',
                'correlationid',
                'CorrelationID',
                'correlation-id',
                'Correlation-ID',
                'server-correlation-id',
                'Server-Correlation-ID',
                'serverCorrelationId',
                'ServerCorrelationId',
                'servercorrelationid'
            ];
            
            // Chercher dans tous les headers possibles
            foreach ($possibleHeaders as $headerName) {
                // Essai direct
                if (isset($headers[$headerName][0]) && !empty($headers[$headerName][0])) {
                    $correlationId = trim($headers[$headerName][0]);
                    $this->log('info', 'CorrelationId trouvé dans header', [
                        'header_name' => $headerName,
                        'value' => $correlationId
                    ]);
                    return $correlationId;
                }
                
                // Essai case-insensitive
                foreach ($headers as $key => $values) {
                    if (strtolower($key) === strtolower($headerName) && isset($values[0]) && !empty($values[0])) {
                        $correlationId = trim($values[0]);
                        $this->log('info', 'CorrelationId trouvé (case-insensitive)', [
                            'header_name' => $key,
                            'value' => $correlationId
                        ]);
                        return $correlationId;
                    }
                }
            }
            
            // Chercher aussi dans le body de la réponse
            try {
                $body = $response->getContent(false);
                $data = json_decode($body, true);
                
                if (isset($data['serverCorrelationId']) && !empty($data['serverCorrelationId'])) {
                    $correlationId = trim($data['serverCorrelationId']);
                    $this->log('info', 'CorrelationId trouvé dans body', [
                        'value' => $correlationId
                    ]);
                    return $correlationId;
                }
            } catch (\Exception $e) {
                $this->log('debug', 'Impossible de parser le body pour correlationId', [
                    'error' => $e->getMessage()
                ]);
            }
            
            $this->log('warning', 'Aucun correlationId trouvé', [
                'headers_checked' => count($possibleHeaders),
                'headers_available' => array_keys($headers)
            ]);
            
            return null;
            
        } catch (\Exception $e) {
            $this->log('error', 'Erreur lors de l\'extraction du correlationId', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Vérifie si une transaction existe malgré une erreur 4002.
     * AMÉLIORÉ : Utilise le client correlationId si serverCorrelationId est absent
     */
    public function verifyTransactionAfter4002Error(
        string $transactionReference,
        ?string $serverCorrelationId = null,
        ?string $clientCorrelationId = null,
        int $maxAttempts = 5,
        int $delayBetweenAttempts = 2
    ): array {
        $this->log('info', 'Vérification transaction après erreur 4002', [
            'transaction_reference' => $transactionReference,
            'server_correlation_id' => $serverCorrelationId,
            'client_correlation_id' => $clientCorrelationId,
        ]);
        
        $correlationIdsToTry = array_filter([
            $serverCorrelationId,
            $clientCorrelationId
        ]);
        
        if (empty($correlationIdsToTry)) {
            $this->log('warning', 'Aucun correlationId disponible pour vérification');
            return [
                'success' => false,
                'error' => 'Impossible de vérifier la transaction : aucun correlationId disponible',
                'transaction_reference' => $transactionReference,
                'manual_check_required' => true,
            ];
        }
        
        // Essayer avec chaque correlationId disponible
        foreach ($correlationIdsToTry as $index => $correlationId) {
            $idType = $index === 0 ? 'server' : 'client';
            $this->log('info', "Tentative avec {$idType}CorrelationId", [
                'correlation_id' => $correlationId
            ]);
            
            $attempts = 0;
            while ($attempts < $maxAttempts) {
                $attempts++;
                $this->log('debug', "Tentative {$attempts}/{$maxAttempts}", [
                    'correlation_id' => $correlationId,
                    'type' => $idType
                ]);
                
                $statusResult = $this->getTransactionStatus($correlationId);
                
                if ($statusResult['success']) {
                    $this->log('info', "Transaction trouvée via {$idType}CorrelationId!", [
                        'status' => $statusResult['status'] ?? 'unknown',
                        'correlation_id' => $correlationId,
                        'attempts' => $attempts
                    ]);
                    
                    return [
                        'success' => true,
                        'transaction' => $statusResult['transaction'] ?? null,
                        'status' => $statusResult['status'] ?? 'found',
                        'found_via' => $idType . 'CorrelationId',
                        'correlation_id' => $correlationId,
                        'attempts_needed' => $attempts
                    ];
                }
                
                if ($attempts < $maxAttempts) {
                    sleep($delayBetweenAttempts);
                }
            }
            
            $this->log('warning', "{$idType}CorrelationId n'a pas permis de trouver la transaction", [
                'correlation_id' => $correlationId,
                'attempts' => $maxAttempts
            ]);
        }
        
        // Si on a un webhook/callback URL, informer l'utilisateur
        if ($this->callbackUrl) {
            $this->log('info', 'En attente de notification webhook', [
                'transaction_reference' => $transactionReference,
                'callback_url' => $this->callbackUrl,
            ]);
            
            return [
                'success' => false,
                'error' => 'Transaction non trouvée automatiquement. Vérifiez les notifications webhook.',
                'requires_callback_check' => true,
                'transaction_reference' => $transactionReference,
                'tried_correlation_ids' => $correlationIdsToTry
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Impossible de vérifier l\'état de la transaction automatiquement. Vérifiez manuellement dans le DevPortal MVola.',
            'transaction_reference' => $transactionReference,
            'manual_check_required' => true,
            'tried_correlation_ids' => $correlationIdsToTry
        ];
    }

    /**
     * Initialise une transaction MerchantPay avec code PIN.
     * AMÉLIORÉ : Meilleure gestion de l'erreur 4002
     */
    public function initiateTransaction(
        float $amount,
        string $customerMsisdn,
        string $transactionReference,
        string $description = 'Paiement de billets',
        ?string $pin = null
    ): array {
        // Vérifier la configuration
        if (empty($this->apiBaseUrl) || empty($this->consumerKey) || empty($this->consumerSecret) || empty($this->merchantMsisdn)) {
            return [
                'success' => false,
                'error' => 'Configuration MVola incomplète.',
            ];
        }

        // Normaliser les numéros de téléphone
        $customerMsisdn = $this->normalizeMsisdn($customerMsisdn);
        $merchantMsisdn = $this->normalizeMsisdn($this->merchantMsisdn);
        
        // Validations
        if (empty($customerMsisdn) || strlen($customerMsisdn) < 10) {
            return [
                'success' => false,
                'error' => 'Numéro de téléphone client invalide. Format attendu: 034xxxxxxx',
            ];
        }
        
        if (empty($merchantMsisdn) || strlen($merchantMsisdn) < 10) {
            return [
                'success' => false,
                'error' => 'Numéro de téléphone partenaire invalide. Vérifiez MVOLA_PARTNER_MSISDN',
            ];
        }

        // Si un PIN est fourni, le valider
        if ($pin !== null && !$this->validatePin($pin)) {
            return [
                'success' => false,
                'error' => 'Code PIN invalide. Il doit contenir 4 à 6 chiffres.',
            ];
        }

        // Obtenir l'access token
        $tokenResult = $this->getAccessToken();
        if (!$tokenResult['success']) {
            return $tokenResult;
        }

        $accessToken = $tokenResult['access_token'];
        $correlationId = $this->generateCorrelationId();

        try {
            $endpoint = rtrim($this->apiBaseUrl, '/') . '/mvola/mm/transactions/type/merchantpay/1.0.0';
            
            // Date au format exact MVola
            $microtime = microtime(true);
            $seconds = floor($microtime);
            $milliseconds = str_pad((int)(($microtime - $seconds) * 1000), 3, '0', STR_PAD_LEFT);
            $requestDate = gmdate('Y-m-d\TH:i:s.', $seconds) . $milliseconds . 'Z';

            // Payload
            $payload = [
                'amount' => (string) ((int) round($amount)),
                'currency' => 'Ar',
                'descriptionText' => substr($description ?: 'Paiement de billets', 0, 50),
                'requestingOrganisationTransactionReference' => $transactionReference,
                'requestDate' => $requestDate,
                'debitParty' => [
                    [
                        'key' => 'msisdn',
                        'value' => $customerMsisdn
                    ]
                ],
                'creditParty' => [
                    [
                        'key' => 'msisdn',
                        'value' => $merchantMsisdn
                    ]
                ],
                'metadata' => [
                    [
                        'key' => 'partnerName',
                        'value' => $this->merchantName
                    ],
                    [
                        'key' => 'fc',
                        'value' => 'USD'
                    ],
                    [
                        'key' => 'amountFc',
                        'value' => '1'
                    ]
                ],
            ];
            
            // Ajouter le code PIN si fourni
            if ($pin !== null) {
                $payload['metadata'][] = [
                    'key' => 'pin',
                    'value' => $pin
                ];
            }

            // Headers
            $headers = [
                'Authorization' => 'Bearer ' . trim($accessToken),
                'Version' => '1.0',
                'X-CorrelationID' => $correlationId,
                'UserLanguage' => 'mg',
                'UserAccountIdentifier' => 'msisdn;' . trim($merchantMsisdn),
                'partnerName' => trim($this->merchantName),
                'Content-Type' => 'application/json',
                'Cache-Control' => 'no-cache',
                'Accept' => 'application/json',
            ];
            
            if (!empty($this->callbackUrl)) {
                $headers['X-Callback-URL'] = trim($this->callbackUrl);
            }
            
            $this->log('info', 'Envoi requête MVola', [
                'endpoint' => $endpoint,
                'reference' => $transactionReference,
                'correlation_id' => $correlationId,
                'amount' => $payload['amount']
            ]);

            // Envoyer la requête
            $response = $this->httpClient->request('POST', $endpoint, [
                'headers' => $headers,
                'body' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'timeout' => 30,
            ]);

            $statusCode = $response->getStatusCode();
            $data = $response->toArray(false);
            
            $this->log('info', 'Réponse MVola reçue', [
                'status_code' => $statusCode,
                'has_error' => isset($data['errorCode'])
            ]);

            // ========== GESTION SPÉCIALE ERREUR 4002 ==========
            if ($statusCode === 500 && 
                isset($data['errorParameters'][0]['value']) && 
                $data['errorParameters'][0]['value'] === '4002') {
                
                $this->log('warning', 'Erreur 4002 détectée', [
                    'transaction_reference' => $transactionReference,
                    'client_correlation_id' => $correlationId
                ]);
                
                // Extraire le serverCorrelationId (AMÉLIORÉ)
                $serverCorrelationId = $this->extractCorrelationIdFromResponse($response);
                
                $this->log('info', 'Extraction correlationId pour 4002', [
                    'server_correlation_id' => $serverCorrelationId,
                    'client_correlation_id' => $correlationId
                ]);
                
                // Tentative de vérification automatique (AMÉLIORÉ)
                $verificationResult = $this->verifyTransactionAfter4002Error(
                    $transactionReference,
                    $serverCorrelationId,
                    $correlationId, // Utiliser aussi le client correlationId
                    5, // maxAttempts
                    2  // delayBetweenAttempts
                );
                
                if ($verificationResult['success']) {
                    // Transaction trouvée !
                    return [
                        'success' => true,
                        'serverCorrelationId' => $verificationResult['correlation_id'],
                        'transactionReference' => $transactionReference,
                        'status' => $verificationResult['status'] ?? 'processing',
                        'notificationMethod' => 'polling',
                        'raw_response' => $data,
                        'note' => 'Transaction créée malgré erreur DB 4002 (auto-verified)',
                        'verification_result' => $verificationResult
                    ];
                } else {
                    // Transaction non trouvée
                    return [
                        'success' => false,
                        'error' => 'Transaction initiée mais nécessite vérification manuelle. Code erreur: 4002 (DB Update Error)',
                        'transaction_reference' => $transactionReference,
                        'server_correlation_id' => $serverCorrelationId,
                        'client_correlation_id' => $correlationId,
                        'requires_manual_check' => true,
                        'raw_response' => $data,
                        'verification_attempts' => $verificationResult
                    ];
                }
            }
            // ========== FIN GESTION ERREUR 4002 ==========
            
            // Vérifier le statut de la réponse
            if ($statusCode >= 200 && $statusCode < 300) {
                return [
                    'success' => true,
                    'serverCorrelationId' => $data['serverCorrelationId'] ?? null,
                    'transactionReference' => $data['transactionReference'] ?? $transactionReference,
                    'status' => $data['status'] ?? 'processing',
                    'notificationMethod' => $data['notificationMethod'] ?? 'polling',
                    'raw_response' => $data,
                ];
            }

            // Gérer les erreurs normales
            $apiError = $data['errorDescription'] ?? $data['message'] ?? 'Erreur inconnue';
            
            return [
                'success' => false,
                'error' => 'Erreur lors de l\'initiation de la transaction MVola (HTTP ' . $statusCode . '): ' . $apiError,
                'raw_response' => $data,
                'http_status' => $statusCode,
            ];

        } catch (HttpExceptionInterface | TransportExceptionInterface $e) {
            $this->log('error', 'Exception lors de l\'appel API', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Erreur lors de l\'appel API MVola: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Récupère les détails d'une transaction.
     */
    public function getTransactionDetails(string $transId): array
    {
        $tokenResult = $this->getAccessToken();
        if (!$tokenResult['success']) {
            return $tokenResult;
        }

        try {
            $endpoint = rtrim($this->apiBaseUrl, '/') . '/mvola/mm/transactions/type/merchantpay/1.0.0/' . $transId;
            $correlationId = $this->generateCorrelationId();

            $response = $this->httpClient->request('GET', $endpoint, [
                'headers' => [
                    'Version' => '1.0',
                    'X-CorrelationID' => $correlationId,
                    'UserLanguage' => 'FR',
                    'UserAccountIdentifier' => 'msisdn;' . $this->merchantMsisdn,
                    'partnerName' => $this->merchantName,
                    'Authorization' => 'Bearer ' . $tokenResult['access_token'],
                    'Cache-Control' => 'no-cache',
                ],
            ]);

            $data = $response->toArray(false);

            return [
                'success' => true,
                'transaction' => $data,
            ];
        } catch (HttpExceptionInterface | TransportExceptionInterface $e) {
            return [
                'success' => false,
                'error' => 'Erreur lors de la récupération des détails: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Vérifie le statut d'une transaction via correlationId.
     */
    public function getTransactionStatus(string $correlationId): array
    {
        $tokenResult = $this->getAccessToken();
        if (!$tokenResult['success']) {
            return $tokenResult;
        }

        try {
            $endpoint = rtrim($this->apiBaseUrl, '/') . '/mvola/mm/transactions/type/merchantpay/1.0.0/status/' . $correlationId;
            $newCorrelationId = $this->generateCorrelationId();

            $response = $this->httpClient->request('GET', $endpoint, [
                'headers' => [
                    'Version' => '1.0',
                    'X-CorrelationID' => $newCorrelationId,
                    'UserLanguage' => 'mg',
                    'UserAccountIdentifier' => 'msisdn;' . $this->merchantMsisdn,
                    'partnerName' => $this->merchantName,
                    'Authorization' => 'Bearer ' . $tokenResult['access_token'],
                    'Cache-Control' => 'no-cache',
                ],
            ]);

            $data = $response->toArray(false);

            return [
                'success' => true,
                'status' => $data['status'] ?? 'unknown',
                'transaction' => $data,
            ];
        } catch (HttpExceptionInterface | TransportExceptionInterface $e) {
            $this->log('debug', 'Erreur getTransactionStatus', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Erreur lors de la vérification du statut: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Effectue un paiement avec code PIN.
     */
    public function payWithPin(
        float $amount,
        string $customerMsisdn,
        string $transactionReference,
        string $description,
        string $pin
    ): array {
        if (!$this->validatePin($pin)) {
            return [
                'success' => false,
                'error' => 'Code PIN invalide. Il doit contenir 4 à 6 chiffres.',
                'is_pin_error' => true,
            ];
        }

        return $this->initiateTransaction(
            $amount,
            $customerMsisdn,
            $transactionReference,
            $description,
            $pin
        );
    }
}