<?php

namespace App\Service\Organisateur;

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
        // Créer un client HTTP avec des options pour améliorer la résolution DNS
        if ($httpClient === null) {
            $this->httpClient = HttpClient::create([
                'timeout' => 30,
                'verify_peer' => true,
                'verify_host' => true,
                'resolve' => [
                    // Forcer la résolution DNS si nécessaire
                    // 'devapi.mvola.mg' => '104.18.19.187:443', // IP résolue (optionnel, pour debug)
                ],
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
     * Méthode helper pour logger (utilise le logger Symfony si disponible, sinon error_log)
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
        
        // Écrire aussi dans un fichier dédié MVola pour faciliter le debug
        $logFile = sys_get_temp_dir() . '/mvola_debug.log';
        @file_put_contents($logFile, date('Y-m-d H:i:s') . ' - ' . $logMessage . "\n", FILE_APPEND);
        
        if (($_ENV['APP_ENV'] ?? 'dev') === 'dev') {
            error_log($logMessage, 4); // 4 = SAPI error log handler
        }
    }

    /**
     * Obtient un access token via l'API d'authentification MVola.
     * 
     * @return array{success: bool, access_token?: string, error?: string, raw_response?: array}
     */
    public function getAccessToken(): array
    {
        // Utiliser le token en cache s'il est encore valide
        if ($this->cachedAccessToken !== null && $this->tokenExpiresAt !== null && time() < $this->tokenExpiresAt) {
            return [
                'success' => true,
                'access_token' => $this->cachedAccessToken,
            ];
        }

        try {
            // Implémentation basée sur le code Laravel fonctionnel fourni :
            // - Authentification sur {BASE_URL}/token
            // - Authorization: Basic base64(client_id:client_secret)
            // - grant_type=client_credentials, scope=EXT_INT_MVOLA_SCOPE
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
            // Cache le token pour 55 minutes (généralement valide 1h)
            $this->tokenExpiresAt = time() + (55 * 60);

            return [
                'success' => true,
                'access_token' => $this->cachedAccessToken,
            ];
        } catch (HttpExceptionInterface | TransportExceptionInterface $e) {
            // Log détaillé pour diagnostiquer les problèmes de réseau/DNS
            $this->log('error', 'Erreur authentification', [
                'type' => get_class($e),
                'message' => $e->getMessage(),
                'url' => $authUrl,
                'base_url' => $this->apiBaseUrl,
            ]);
            
            return [
                'success' => false,
                'error' => 'Erreur lors de l\'authentification MVola: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Génère un X-CorrelationID unique pour la requête.
     * Format EXACT comme dans Postman: mvola-12345678 (8 chiffres)
     */
    private function generateCorrelationId(): string
    {
        // 8 chiffres aléatoires pour reproduire le format Postman
        $random = random_int(10000000, 99999999);
        return 'mvola-' . $random;
    }

    /**
     * Normalise un numéro de téléphone au format MVola (034xxxxxxx).
     * Format EXACT comme dans Postman: 0343500003 ou 0382795455
     * 
     * @param string $msisdn Numéro de téléphone à normaliser
     * @return string Numéro normalisé
     */
    private function normalizeMsisdn(string $msisdn): string
    {
        // Supprimer tous les espaces, tirets, points, etc.
        $msisdn = preg_replace('/[^0-9+]/', '', trim($msisdn));
        
        // Si le numéro commence par +261, le remplacer par 0
        if (strpos($msisdn, '+261') === 0) {
            $msisdn = '0' . substr($msisdn, 4);
        }
        
        // Si le numéro commence par 261, le remplacer par 0
        if (strpos($msisdn, '261') === 0 && strlen($msisdn) > 9) {
            $msisdn = '0' . substr($msisdn, 3);
        }
        
        // S'assurer que le numéro commence par 0 et a au moins 10 chiffres
        if (strpos($msisdn, '0') !== 0 && strlen($msisdn) >= 9) {
            $msisdn = '0' . $msisdn;
        }
        
        // Vérifier que le format est correct (10 chiffres commençant par 0)
        if (strlen($msisdn) < 10 || strpos($msisdn, '0') !== 0) {
            error_log('[MVola] ATTENTION: Format de numéro suspect: ' . $msisdn);
        }
        
        return $msisdn;
    }

    /**
     * Initialise une transaction MerchantPay selon la documentation officielle.
     * 
     * @param float $amount Montant en MGA (sans décimales)
     * @param string $customerMsisdn Numéro MVola du client (ex: 03412345678)
     * @param string $transactionReference Référence unique de la transaction côté client
     * @param string $description Description de la transaction (max 50 caractères)
     * 
     * @return array{success: bool, serverCorrelationId?: string, transactionReference?: string, status?: string, error?: string, raw_response?: array}
     */
    public function initiateTransaction(
        float $amount,
        string $customerMsisdn,
        string $transactionReference,
        string $description = 'Paiement de billets'
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
        
        // Valider les numéros de téléphone
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

        // Obtenir l'access token (IMPORTANT: doit être valide)
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
        
        // Vérification du token
        error_log('[MVola] Token valide - Préfixe: ' . substr($accessToken, 0, 20) . '...');
        error_log('[MVola] Token longueur: ' . strlen($accessToken) . ' caractères');
        
        $correlationId = $this->generateCorrelationId();

        try {
            // Endpoint selon la documentation: /mvola/mm/transactions/type/merchantpay/1.0.0/
            // IMPORTANT: Pas de slash final dans Postman
            $endpoint = rtrim($this->apiBaseUrl, '/') . '/mvola/mm/transactions/type/merchantpay/1.0.0';
            
            // Date au format exact MVola (ISO 8601 avec millisecondes réelles)
            // Format strict: YYYY-MM-DDTHH:mm:ss.SSSZ (ex: 2022-05-16T08:59:03.076Z)
            // IMPORTANT: Utiliser UTC strict (gmdate) pour éviter les problèmes de timezone
            $microtime = microtime(true);
            $seconds = floor($microtime);
            $milliseconds = str_pad((int)(($microtime - $seconds) * 1000), 3, '0', STR_PAD_LEFT);
            // Utiliser gmdate pour garantir UTC (pas d'ajout de +3h car on est déjà en UTC)
            $requestDate = gmdate('Y-m-d\TH:i:s.', $seconds) . $milliseconds . 'Z';

            // Validation des champs AVANT de créer le payload
            if (empty($description) || trim($description) === '') {
                $description = 'Paiement de billets';
            }

            // Nettoyer la description pour éviter les caractères spéciaux non supportés
            // Supprimer les apostrophes et autres caractères spéciaux qui peuvent causer l'erreur 4001
            $description = preg_replace('/[^\w\s\-.,]/u', '', $description);
            $description = trim($description);
            if (empty($description)) {
                $description = 'Paiement de billets';
            }

            if (empty($transactionReference) || trim($transactionReference) === '') {
                // Générer une référence unique avec timestamp et microsecondes pour éviter les doublons
                $transactionReference = 'TXN-' . time() . '-' . uniqid('', true);
            }

            // S'assurer que la référence de transaction est unique (pas de doublons)
            $transactionReference = trim($transactionReference);

            // Payload EXACTEMENT comme Postman (ordre et champs identiques)
            // IMPORTANT: L'ordre des champs peut être important pour certaines APIs
            $payload = [
                'amount' => (string) ((int) round($amount)),
                'currency' => 'Ar',
                'descriptionText' => substr($description, 0, 50),
                'requestingOrganisationTransactionReference' => $transactionReference,
                'requestDate' => $requestDate,
                // SUPPRIMEZ "originalTransactionReference" complètement (selon Postman)
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
                    // CHANGER "merchantName" en "partnerName" comme Postman
                    [
                        'key' => 'partnerName',
                        'value' => $this->merchantName
                    ],
                    // AJOUTER ces deux champs obligatoires de Postman
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
            
            // Vérification finale que tous les champs sont bien présents
            // CORRECTION : Retirer 'originalTransactionReference' car il n'est plus dans le payload
            $expectedFields = ['amount', 'currency', 'descriptionText', 'requestingOrganisationTransactionReference', 'requestDate', 'debitParty', 'creditParty', 'metadata'];
            $missingInPayload = [];
            foreach ($expectedFields as $field) {
                if (!isset($payload[$field])) {
                    $missingInPayload[] = $field;
                }
            }
            if (!empty($missingInPayload)) {
                error_log('[MVola] ERREUR: Champs manquants dans payload: ' . implode(', ', $missingInPayload));
                return [
                    'success' => false,
                    'error' => 'Champs manquants dans payload: ' . implode(', ', $missingInPayload),
                ];
            }

            // Log de vérification AVANT envoi
            error_log('[MVola] ========== VÉRIFICATION FINALE ==========');
            error_log('[MVola] Amount: ' . $payload['amount']);
            error_log('[MVola] Currency: ' . $payload['currency']);
            error_log('[MVola] Description: ' . $payload['descriptionText']);
            error_log('[MVola] RequestDate: ' . $payload['requestDate']);
            error_log('[MVola] TransactionRef: ' . $payload['requestingOrganisationTransactionReference']);
            error_log('[MVola] DebitParty: ' . $payload['debitParty'][0]['value']);
            error_log('[MVola] CreditParty: ' . $payload['creditParty'][0]['value']);
            error_log('[MVola] Metadata count: ' . count($payload['metadata']));
            foreach ($payload['metadata'] as $index => $meta) {
                error_log('[MVola]   Metadata[' . $index . ']: ' . $meta['key'] . ' = ' . $meta['value']);
            }
            error_log('[MVola] ========================================');
            
            // Validation stricte: s'assurer qu'aucun champ n'est null ou vide
            $requiredFields = [
                'amount' => $payload['amount'],
                'currency' => $payload['currency'],
                'descriptionText' => $payload['descriptionText'],
                'requestingOrganisationTransactionReference' => $payload['requestingOrganisationTransactionReference'],
                'requestDate' => $payload['requestDate'],
                'debitParty[0].key' => $payload['debitParty'][0]['key'] ?? null,
                'debitParty[0].value' => $payload['debitParty'][0]['value'] ?? null,
                'creditParty[0].key' => $payload['creditParty'][0]['key'] ?? null,
                'creditParty[0].value' => $payload['creditParty'][0]['value'] ?? null,
                'metadata[0].key' => $payload['metadata'][0]['key'] ?? null,
                'metadata[0].value' => $payload['metadata'][0]['value'] ?? null,
                'metadata[1].key' => $payload['metadata'][1]['key'] ?? null,
                'metadata[1].value' => $payload['metadata'][1]['value'] ?? null,
                'metadata[2].key' => $payload['metadata'][2]['key'] ?? null,
                'metadata[2].value' => $payload['metadata'][2]['value'] ?? null,
            ];
            
            $missingFields = [];
            foreach ($requiredFields as $field => $value) {
                if ($value === null || $value === '') {
                    $missingFields[] = $field;
                }
            }
            
            if (!empty($missingFields)) {
                error_log('[MVola] ERREUR: Champs manquants ou vides: ' . implode(', ', $missingFields));
                error_log('[MVola] Payload complet: ' . json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                return [
                    'success' => false,
                    'error' => 'Champs requis manquants ou vides: ' . implode(', ', $missingFields),
                ];
            }
            
            // Vérifications supplémentaires
            if (empty($payload['amount']) || $payload['amount'] === '0' || $payload['amount'] === '') {
                return [
                    'success' => false,
                    'error' => 'Le montant ne peut pas être vide ou zéro',
                ];
            }
            
            if (empty($payload['descriptionText']) || trim($payload['descriptionText']) === '') {
                $payload['descriptionText'] = 'Paiement de billets';
            }
            
            if (empty($payload['requestingOrganisationTransactionReference']) || trim($payload['requestingOrganisationTransactionReference']) === '') {
                return [
                    'success' => false,
                    'error' => 'La référence de transaction ne peut pas être vide',
                ];
            }
            
            if (empty($payload['debitParty'][0]['value']) || trim($payload['debitParty'][0]['value']) === '') {
                return [
                    'success' => false,
                    'error' => 'Le numéro de téléphone du client ne peut pas être vide',
                ];
            }
            
            if (empty($payload['creditParty'][0]['value']) || trim($payload['creditParty'][0]['value']) === '') {
                return [
                    'success' => false,
                    'error' => 'Le numéro de téléphone du partenaire ne peut pas être vide',
                ];
            }

            // Headers EXACTEMENT comme dans Postman (ordre et format identiques)
            // IMPORTANT: Les noms de headers sont sensibles à la casse
            $merchantMsisdnClean = trim($merchantMsisdn);
            $userAccountIdentifier = 'msisdn;' . $merchantMsisdnClean;
            
            // Headers EXACTEMENT dans l'ordre de Postman
            $headers = [
                'Authorization' => 'Bearer ' . trim($accessToken),
                'Version' => '1.0',
                'X-CorrelationID' => $correlationId,
                'UserLanguage' => 'mg',
                'UserAccountIdentifier' => $userAccountIdentifier,
                'partnerName' => trim($this->merchantName), // IMPORTANT: 'partnerName' avec 'p' minuscule
                'Content-Type' => 'application/json',
                'Cache-Control' => 'no-cache',
                'Accept' => 'application/json',
            ];
            
            // Ajouter X-Callback-URL seulement si fourni (comme dans Postman)
            if (!empty($this->callbackUrl)) {
                $headers['X-Callback-URL'] = trim($this->callbackUrl);
            }
            
            // Log des headers pour vérification
            error_log('[MVola] ========== HEADERS ENVOYÉS ==========');
            foreach ($headers as $key => $value) {
                if ($key === 'Authorization') {
                    error_log($key . ': Bearer ***' . substr($value, -10));
                } else {
                    error_log($key . ': ' . $value);
                }
            }
            error_log('==========================================');

            // Envoyer la requête
            try {
                // Encoder le payload en JSON
                $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                
                if ($jsonPayload === false) {
                    $error = 'Erreur lors de l\'encodage JSON: ' . json_last_error_msg();
                    error_log('[MVola] ' . $error);
                    return [
                        'success' => false,
                        'error' => $error,
                    ];
                }
                
                // Log final avant envoi
                error_log('[MVola] ========== REQUÊTE FINALE ==========');
                error_log('URL: ' . $endpoint);
                error_log('Method: POST');
                error_log('Headers count: ' . count($headers));
                error_log('Body length: ' . strlen($jsonPayload) . ' bytes');
                error_log('==========================================');
                
                $response = $this->httpClient->request('POST', $endpoint, [
                    'headers' => $headers,
                    'body' => $jsonPayload,
                    'timeout' => 30,
                ]);

                $statusCode = $response->getStatusCode();
                $data = $response->toArray(false);
                
                // Log de la réponse
                error_log("\n" . str_repeat('=', 80) . "\n");
                error_log("[MVola] RÉPONSE REÇUE:\n");
                error_log("Status Code: " . $statusCode . "\n");
                error_log("Response:\n" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n");
                error_log(str_repeat('=', 80) . "\n");
                
                $this->log('debug', 'Réponse MVola reçue', [
                    'status_code' => $statusCode,
                    'response' => $data,
                ]);

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

                // Gérer les erreurs
                $apiError = $data['errorDescription'] ?? $data['message'] ?? 'Erreur inconnue';
                $missingField = null;
                
                // Essayer de trouver le champ manquant
                if (preg_match('/Missing field[:\s]+([a-zA-Z_]+)/i', $apiError, $matches)) {
                    $missingField = $matches[1] ?? null;
                }
                if (!$missingField && preg_match('/field[:\s]+([a-zA-Z_]+)[\s]+is required/i', $apiError, $matches)) {
                    $missingField = $matches[1] ?? null;
                }

                return [
                    'success' => false,
                    'error' => 'Erreur lors de l\'initiation de la transaction MVola (HTTP ' . $statusCode . ', détail: ' . $apiError . ')',
                    'raw_response' => $data,
                    'payload_sent' => $payload,
                    'http_status' => $statusCode,
                    'missing_field' => $missingField,
                ];
                
            } catch (\Exception $e) {
                $this->log('error', 'Exception lors de l\'appel API MVola', [
                    'message' => $e->getMessage(),
                    'endpoint' => $endpoint,
                    'payload' => $payload,
                ]);
                throw $e;
            }

        } catch (HttpExceptionInterface | TransportExceptionInterface $e) {
            return [
                'success' => false,
                'error' => 'Erreur lors de l\'appel API MVola: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Récupère les détails d'une transaction.
     * 
     * @param string $transId ID de la transaction MVola
     * 
     * @return array{success: bool, transaction?: array, error?: string}
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
     * Vérifie le statut d'une transaction via serverCorrelationId.
     * 
     * @param string $serverCorrelationId ID de corrélation retourné lors de l'initiation
     * 
     * @return array{success: bool, status?: string, transaction?: array, error?: string}
     */
    public function getTransactionStatus(string $serverCorrelationId): array
    {
        $tokenResult = $this->getAccessToken();
        if (!$tokenResult['success']) {
            return $tokenResult;
        }

        try {
            $endpoint = rtrim($this->apiBaseUrl, '/') . '/mvola/mm/transactions/type/merchantpay/1.0.0/status/' . $serverCorrelationId;
            $correlationId = $this->generateCorrelationId();

            $response = $this->httpClient->request('GET', $endpoint, [
                'headers' => [
                    'Version' => '1.0',
                    'X-CorrelationID' => $correlationId,
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
            return [
                'success' => false,
                'error' => 'Erreur lors de la vérification du statut: ' . $e->getMessage(),
            ];
        }
    }
}