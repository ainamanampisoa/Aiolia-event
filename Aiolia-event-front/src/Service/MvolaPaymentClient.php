<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Client pour l'API MVola MerchantPay selon la documentation officielle.
 * 
 * Documentation basée sur API_MerchantPay.pdf v1.0
 * Sandbox URL: https://devapi.mvola.mg
 * Production URL: https://api.mvola.mg
 */
class MvolaPaymentClient
{
    private ?string $cachedAccessToken = null;
    private ?int $tokenExpiresAt = null;

    private HttpClientInterface $httpClient;

    private string $baseUrl;
    private string $consumerKey;
    private string $consumerSecret;
    private string $partnerMsisdn;
    private string $partnerName;
    private ?string $callbackUrl;
    private ?LoggerInterface $logger;

    public function __construct(
        string $baseUrl,
        string $consumerKey,
        string $consumerSecret,
        string $partnerMsisdn,
        string $partnerName,
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
        $this->baseUrl = $baseUrl;
        $this->consumerKey = $consumerKey;
        $this->consumerSecret = $consumerSecret;
        $this->partnerMsisdn = $partnerMsisdn;
        $this->partnerName = $partnerName;
        $this->callbackUrl = $callbackUrl;
        $this->logger = $logger;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

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

        if (($_ENV['APP_ENV'] ?? 'dev') === 'dev') {
            error_log($logMessage, 4);
        }
    }

    public function getAccessToken(): array
    {
        if ($this->cachedAccessToken !== null && $this->tokenExpiresAt !== null && time() < $this->tokenExpiresAt) {
            return [
                'success' => true,
                'access_token' => $this->cachedAccessToken,
            ];
        }

        try {
            $authUrl = rtrim($this->baseUrl, '/') . '/token';

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
                'url' => $authUrl ?? null,
                'base_url' => $this->baseUrl,
            ]);

            return [
                'success' => false,
                'error' => 'Erreur lors de l\'authentification MVola: ' . $e->getMessage(),
            ];
        }
    }

    private function generateCorrelationId(): string
    {
        $random = random_int(10000000, 99999999);
        return 'mvola-' . $random;
    }

    private function normalizeMsisdn(string $msisdn): string
    {
        // Supprimer TOUS les caractères non numériques sauf +
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

    public function initiateTransaction(
        float $amount,
        string $customerMsisdn,
        string $transactionReference,
        string $description = 'Paiement de billets'
    ): array {
        if (empty($this->baseUrl) || empty($this->consumerKey) || empty($this->consumerSecret) || empty($this->partnerMsisdn)) {
            return [
                'success' => false,
                'error' => 'Configuration MVola incomplète.',
            ];
        }

        // 🔧 FIX 1: Nettoyer TOUS les champs des espaces invisibles
        $customerMsisdn = preg_replace('/\s+/', '', $this->normalizeMsisdn($customerMsisdn));
        $partnerMsisdn = preg_replace('/\s+/', '', $this->normalizeMsisdn($this->partnerMsisdn));
        $transactionReference = preg_replace('/\s+/', '', trim($transactionReference));
        $description = preg_replace('/\s+/', ' ', trim($description)); // Remplacer multiples espaces par un seul

        if (empty($customerMsisdn) || strlen($customerMsisdn) < 10) {
            return [
                'success' => false,
                'error' => 'Numéro de téléphone client invalide. Format attendu: 034xxxxxxx',
            ];
        }

        if (empty($partnerMsisdn) || strlen($partnerMsisdn) < 10) {
            return [
                'success' => false,
                'error' => 'Numéro de téléphone partenaire invalide. Vérifiez MVOLA_PARTNER_MSISDN',
            ];
        }

        $tokenResult = $this->getAccessToken();
        if (!$tokenResult['success']) {
            error_log('[MVola] ERREUR: Impossible d\'obtenir le token - ' . ($tokenResult['error'] ?? 'Erreur inconnue'));
            return $tokenResult;
        }

        $accessToken = $tokenResult['access_token'];
        if (empty($accessToken)) {
            error_log('[MVola] ERREUR: Token vide après récupération');
            return [
                'success' => false,
                'error' => 'Token d\'accès MVola vide',
            ];
        }

        error_log('[MVola] Token valide - Préfixe: ' . substr($accessToken, 0, 20) . '...');
        error_log('[MVola] Token longueur: ' . strlen($accessToken) . ' caractères');

        $correlationId = $this->generateCorrelationId();

        try {
            $endpoint = rtrim($this->baseUrl, '/') . '/mvola/mm/transactions/type/merchantpay/1.0.0';

            // Date au format exact MVola (ISO 8601 avec millisecondes)
            $microtime = microtime(true);
            $seconds = floor($microtime);
            $milliseconds = str_pad((int) (($microtime - $seconds) * 1000), 3, '0', STR_PAD_LEFT);
            $requestDate = gmdate('Y-m-d\TH:i:s.', $seconds) . $milliseconds . 'Z';

            // Validation et nettoyage de la description
            if (empty($description) || trim($description) === '') {
                $description = 'Paiement de billets';
            }
            $description = preg_replace('/[^\w\s\-.,]/u', '', $description);
            $description = trim($description);
            if (empty($description)) {
                $description = 'Paiement de billets';
            }

            if (empty($transactionReference) || trim($transactionReference) === '') {
                $transactionReference = 'TXN-' . time() . '-' . uniqid('', true);
            }

            // 🔧 FIX 2: NE PAS inclure originalTransactionReference s'il est vide
            $payload = [
                'amount' => (string) ((int) round($amount)),
                'currency' => 'Ar',
                'descriptionText' => substr($description, 0, 50),
                'requestingOrganisationTransactionReference' => $transactionReference,
                'requestDate' => $requestDate,
                // ❌ SUPPRIMÉ: 'originalTransactionReference' => '',
                'debitParty' => [
                    [
                        'key' => 'msisdn',
                        'value' => $customerMsisdn
                    ]
                ],
                'creditParty' => [
                    [
                        'key' => 'msisdn',
                        'value' => $partnerMsisdn
                    ]
                ],
                'metadata' => [
                    [
                        'key' => 'partnerName',
                        'value' => $this->partnerName
                    ]
                ],
            ];

            // Validation finale
            $requiredFields = [
                'amount' => $payload['amount'],
                'currency' => $payload['currency'],
                'descriptionText' => $payload['descriptionText'],
                'requestingOrganisationTransactionReference' => $payload['requestingOrganisationTransactionReference'],
                'requestDate' => $payload['requestDate'],
                'debitParty.value' => $payload['debitParty'][0]['value'] ?? null,
                'creditParty.value' => $payload['creditParty'][0]['value'] ?? null,
            ];

            $missingFields = [];
            foreach ($requiredFields as $field => $value) {
                if (empty($value)) {
                    $missingFields[] = $field;
                }
            }

            if (!empty($missingFields)) {
                $this->log('error', 'Champs requis manquants dans le payload', [
                    'missing_fields' => $missingFields,
                    'payload' => $payload
                ]);
                return [
                    'success' => false,
                    'error' => 'Champs requis manquants: ' . implode(', ', $missingFields),
                ];
            }

            // 🔧 FIX 3: Nettoyer UserAccountIdentifier de TOUS les espaces
            $partnerMsisdnClean = preg_replace('/\s+/', '', $partnerMsisdn);
            $userAccountIdentifier = 'msisdn;' . $partnerMsisdnClean;

            // Vérification stricte
            if (strpos($userAccountIdentifier, ' ') !== false) {
                throw new \RuntimeException('UserAccountIdentifier contient des espaces invisibles');
            }

            error_log('[MVola] UserAccountIdentifier: [' . $userAccountIdentifier . ']');
            error_log('[MVola] UserAccountIdentifier hex: ' . bin2hex($userAccountIdentifier));

            // Headers EXACTEMENT dans l'ordre de Postman
            $headers = [
                'Authorization' => 'Bearer ' . trim($accessToken),
                'Version' => '1.0',
                'X-CorrelationID' => $correlationId,
                'UserLanguage' => 'mg',
                'UserAccountIdentifier' => $userAccountIdentifier,
                'partnerName' => preg_replace('/\s+/', ' ', trim($this->partnerName)),
                'Content-Type' => 'application/json',
                'Cache-Control' => 'no-cache',
                'Accept' => 'application/json',
            ];

            if (!empty($this->callbackUrl)) {
                $headers['X-Callback-URL'] = trim($this->callbackUrl);
            }

            error_log('[MVola] ========== HEADERS ENVOYÉS ==========');
            foreach ($headers as $key => $value) {
                if ($key === 'Authorization') {
                    error_log($key . ': Bearer ***' . substr($value, -10));
                } else {
                    error_log($key . ': ' . $value);
                }
            }
            error_log('==========================================');

            $headersForLog = array_merge($headers, ['Authorization' => 'Bearer ***']);
            error_log("\n" . str_repeat('=', 80) . "\n");
            error_log("[MVola] REQUÊTE COMPLÈTE:\n");
            error_log("URL: " . $endpoint . "\n");
            error_log("Headers:\n" . json_encode($headersForLog, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n");
            error_log("Payload JSON:\n" . json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n");
            error_log(str_repeat('=', 80) . "\n");

            $this->log('debug', 'Requête MVola complète', [
                'endpoint' => $endpoint,
                'method' => 'POST',
                'headers' => $headersForLog,
                'payload' => $payload,
                'correlation_id' => $correlationId,
            ]);

            // Encoder le JSON
            $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if ($jsonPayload === false) {
                $error = 'Erreur lors de l\'encodage JSON du payload: ' . json_last_error_msg();
                error_log('[MVola] ' . $error);
                return [
                    'success' => false,
                    'error' => $error,
                ];
            }

            error_log('[MVola] ========== JSON BODY EXACT ==========');
            error_log($jsonPayload);
            error_log('==========================================');

            // Vérifier que le JSON est valide
            $decodedCheck = json_decode($jsonPayload, true);
            if ($decodedCheck === null && json_last_error() !== JSON_ERROR_NONE) {
                error_log('[MVola] ERREUR: JSON invalide après encodage!');
                return [
                    'success' => false,
                    'error' => 'JSON invalide: ' . json_last_error_msg(),
                ];
            }

            // Envoyer la requête
            if (!function_exists('curl_init')) {
                error_log('[MVola] cURL non disponible, utilisation de Symfony HttpClient');
                $response = $this->httpClient->request('POST', $endpoint, [
                    'headers' => $headers,
                    'body' => $jsonPayload,
                ]);
            } else {
                // Utiliser cURL directement pour un contrôle total
                $ch = curl_init($endpoint);

                $curlHeaders = [];
                foreach ($headers as $key => $value) {
                    $curlHeaders[] = $key . ': ' . $value;
                }

                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $jsonPayload,
                    CURLOPT_HTTPHEADER => $curlHeaders,
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_SSL_VERIFYHOST => 2,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_VERBOSE => false,
                ]);

                $curlResponse = curl_exec($ch);
                $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);

                if ($curlError) {
                    error_log('[MVola] Erreur cURL: ' . $curlError);
                    return [
                        'success' => false,
                        'error' => 'Erreur cURL: ' . $curlError,
                    ];
                }

                $data = json_decode($curlResponse, true);
                if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                    error_log('[MVola] Erreur décodage JSON réponse: ' . json_last_error_msg());
                    error_log('[MVola] Réponse brute: ' . $curlResponse);
                    $data = ['raw_response' => $curlResponse];
                }

                // 🔧 FIX 4: Créer une réponse propre au lieu d'une classe anonyme
                $response = new \stdClass();
                $response->statusCode = $statusCode;
                $response->data = $data;
            }

            if (is_object($response) && property_exists($response, 'statusCode')) {
                // Réponse cURL
                $statusCode = $response->statusCode;
                $data = $response->data;
            } else {
                // Réponse Symfony HttpClient
                $statusCode = $response->getStatusCode();
                $data = $response->toArray(false);
            }

            error_log("\n" . str_repeat('=', 80) . "\n");
            error_log("[MVola] RÉPONSE REÇUE:\n");
            error_log("Status Code: " . $statusCode . "\n");
            error_log("Response:\n" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n");
            error_log(str_repeat('=', 80) . "\n");

            $this->log('debug', 'Réponse MVola reçue', [
                'status_code' => $statusCode,
                'response' => $data,
            ]);

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

            // Construire un message d'erreur détaillé
            $apiError = null;
            $errorKeys = [
                'errorDescription',
                'error_description',
                'errorDescriptionText',
                'error',
                'message',
                'fault.description',
                'fault.detail',
                'detail',
                'errorMessage',
                'error_message',
                'statusDescription',
                'status_description'
            ];

            foreach ($errorKeys as $key) {
                if (strpos($key, '.') !== false) {
                    $parts = explode('.', $key);
                    if (isset($data[$parts[0]][$parts[1]])) {
                        $apiError = $data[$parts[0]][$parts[1]];
                        break;
                    }
                } elseif (isset($data[$key])) {
                    $apiError = $data[$key];
                    break;
                }
            }

            if (!$apiError) {
                $apiError = 'Réponse API: ' . json_encode($data, JSON_UNESCAPED_UNICODE);
            }

            $missingField = null;
            $missingFieldKeys = ['fault.detail', 'detail', 'missingField', 'missing_field', 'field'];

            foreach ($missingFieldKeys as $key) {
                if (strpos($key, '.') !== false) {
                    $parts = explode('.', $key);
                    if (isset($data[$parts[0]][$parts[1]])) {
                        $missingField = $data[$parts[0]][$parts[1]];
                        break;
                    }
                } elseif (isset($data[$key])) {
                    $missingField = $data[$key];
                    break;
                }
            }

            if (!$missingField && preg_match('/Missing field[:\s]+([a-zA-Z_]+)/i', $apiError, $matches)) {
                $missingField = $matches[1] ?? null;
            }
            if (!$missingField && preg_match('/field[:\s]+([a-zA-Z_]+)[\s]+is required/i', $apiError, $matches)) {
                $missingField = $matches[1] ?? null;
            }

            $logOutput = "\n" . str_repeat('=', 80) . "\n";
            $logOutput .= "[MVola] ERREUR DÉTECTÉE:\n";
            $logOutput .= "HTTP Status: " . $statusCode . "\n";
            $logOutput .= "Message d'erreur API: " . ($apiError ?? 'Non spécifié') . "\n";
            $logOutput .= "Champ manquant détecté: " . ($missingField ?? 'Non détecté') . "\n";
            $logOutput .= "\n--- RÉPONSE COMPLÈTE DE L'API ---\n";
            $logOutput .= json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            $logOutput .= "\n--- PAYLOAD ENVOYÉ ---\n";
            $logOutput .= json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            $logOutput .= str_repeat('=', 80) . "\n";

            error_log($logOutput);
            file_put_contents('php://stderr', $logOutput);

            $this->log('error', 'Erreur initiation transaction', [
                'http_status' => $statusCode,
                'api_response' => $data,
                'payload_sent' => $payload,
                'missing_field' => $missingField,
                'endpoint' => $endpoint,
            ]);

            $message = sprintf(
                'Erreur lors de l\'initiation de la transaction MVola (HTTP %d%s)',
                $statusCode,
                $apiError ? (', détail: ' . $apiError) : ''
            );

            if ($missingField) {
                $message .= ' - Champ manquant: ' . $missingField;
            } else {
                $message .= ' - Réponse complète: ' . json_encode($data, JSON_UNESCAPED_UNICODE);
            }

            return [
                'success' => false,
                'error' => $message,
                'raw_response' => $data,
                'payload_sent' => $payload,
                'http_status' => $statusCode,
                'missing_field' => $missingField,
            ];
        } catch (HttpExceptionInterface | TransportExceptionInterface $e) {
            return [
                'success' => false,
                'error' => 'Erreur lors de l\'appel API MVola: ' . $e->getMessage(),
            ];
        } catch (\Throwable $e) {
            // 🔧 FIX 5: Corriger la variable $errorMessage non définie
            $this->log('error', 'Erreur lors de l\'initiation du paiement MVola', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(), // ✅ FIX ICI
                'debug' => [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
            ];
        }
    }

    public function getTransactionDetails(string $transId): array
    {
        $tokenResult = $this->getAccessToken();
        if (!$tokenResult['success']) {
            return $tokenResult;
        }

        try {
            $endpoint = rtrim($this->baseUrl, '/') . '/mvola/mm/transactions/type/merchantpay/1.0.0/' . $transId;
            $correlationId = $this->generateCorrelationId();

            $response = $this->httpClient->request('GET', $endpoint, [
                'headers' => [
                    'Version' => '1.0',
                    'X-CorrelationID' => $correlationId,
                    'UserLanguage' => 'FR',
                    'UserAccountIdentifier' => 'msisdn;' . $this->partnerMsisdn,
                    'partnerName' => $this->partnerName,
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

    public function getTransactionStatus(string $serverCorrelationId): array
    {
        $tokenResult = $this->getAccessToken();
        if (!$tokenResult['success']) {
            return $tokenResult;
        }

        try {
            $endpoint = rtrim($this->baseUrl, '/') . '/mvola/mm/transactions/type/merchantpay/1.0.0/status/' . $serverCorrelationId;
            $correlationId = $this->generateCorrelationId();

            $response = $this->httpClient->request('GET', $endpoint, [
                'headers' => [
                    'Version' => '1.0',
                    'X-CorrelationID' => $correlationId,
                    'UserLanguage' => 'mg',
                    'UserAccountIdentifier' => 'msisdn;' . $this->partnerMsisdn,
                    'partnerName' => $this->partnerName,
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
            return [
                'success' => false,
                'error' => 'Erreur lors de la vérification du statut: ' . $e->getMessage(),
            ];
        }
    }
}