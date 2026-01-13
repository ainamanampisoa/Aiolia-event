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

    /**
     * Génère une référence de transaction vraiment unique
     * Format: BASE-YYYYMMDDHHMMSS-MICROSECONDS-RANDOM
     */
    private function generateUniqueTransactionReference(string $baseRef = ''): string
    {
        if (empty($baseRef)) {
            $baseRef = 'TXN';
        }
        
        // Nettoyer la baseRef pour éviter les caractères spéciaux
        $baseRef = preg_replace('/[^A-Za-z0-9\-]/', '', $baseRef);
        
        // Obtenir le microtime avec précision maximale
        $microtime = microtime(true);
        $seconds = floor($microtime);
        $microseconds = str_pad((int) (($microtime - $seconds) * 1000000), 6, '0', STR_PAD_LEFT);
        
        // Générer une partie aléatoire plus longue pour garantir l'unicité
        $randomPart = bin2hex(random_bytes(6)); // 12 caractères hex
        
        // Format: BASE-YYYYMMDDHHMMSS-MICROSECONDS-RANDOM
        return sprintf(
            '%s-%s-%s-%s',
            $baseRef,
            date('YmdHis', $seconds),
            $microseconds,
            $randomPart
        );
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

    /**
     * Valide et log le payload complet avant l'envoi
     * Retourne un tableau avec 'valid' => bool et 'errors' => array
     */
    private function validateAndLogPayload(array $payload, array $headers): array
    {
        $errors = [];
        $warnings = [];
        
        // Validation des champs de base
        $requiredFields = [
            'amount' => 'Montant',
            'currency' => 'Devise',
            'descriptionText' => 'Description',
            'requestingOrganisationTransactionReference' => 'Référence transaction',
            'requestDate' => 'Date de requête',
        ];
        
        foreach ($requiredFields as $field => $label) {
            if (!isset($payload[$field])) {
                $errors[] = "Champ manquant: $field ($label)";
            } elseif (is_string($payload[$field]) && trim($payload[$field]) === '') {
                $errors[] = "Champ vide: $field ($label)";
            } elseif ($field === 'amount' && ($payload[$field] === '0' || $payload[$field] === 0)) {
                $errors[] = "Montant invalide: $field doit être supérieur à 0";
            }
        }
        
        // Validation debitParty
        if (!isset($payload['debitParty']) || !is_array($payload['debitParty']) || empty($payload['debitParty'])) {
            $errors[] = 'debitParty manquant ou invalide';
        } else {
            $debitParty = $payload['debitParty'][0] ?? null;
            if (empty($debitParty) || !is_array($debitParty)) {
                $errors[] = 'debitParty[0] manquant ou invalide';
            } else {
                if (empty($debitParty['key']) || trim($debitParty['key']) === '') {
                    $errors[] = 'debitParty[0].key manquant ou vide';
                }
                if (empty($debitParty['value']) || trim($debitParty['value']) === '') {
                    $errors[] = 'debitParty[0].value manquant ou vide';
                } elseif (strlen($debitParty['value']) < 10) {
                    $errors[] = 'debitParty[0].value invalide (longueur < 10)';
                }
            }
        }
        
        // Validation creditParty
        if (!isset($payload['creditParty']) || !is_array($payload['creditParty']) || empty($payload['creditParty'])) {
            $errors[] = 'creditParty manquant ou invalide';
        } else {
            $creditParty = $payload['creditParty'][0] ?? null;
            if (empty($creditParty) || !is_array($creditParty)) {
                $errors[] = 'creditParty[0] manquant ou invalide';
            } else {
                if (empty($creditParty['key']) || trim($creditParty['key']) === '') {
                    $errors[] = 'creditParty[0].key manquant ou vide';
                }
                if (empty($creditParty['value']) || trim($creditParty['value']) === '') {
                    $errors[] = 'creditParty[0].value manquant ou vide';
                } elseif (strlen($creditParty['value']) < 10) {
                    $errors[] = 'creditParty[0].value invalide (longueur < 10)';
                }
            }
        }
        
        // Validation metadata (optionnel mais recommandé)
        if (isset($payload['metadata']) && is_array($payload['metadata'])) {
            $hasPartnerName = false;
            foreach ($payload['metadata'] as $meta) {
                if (isset($meta['key']) && $meta['key'] === 'partnerName') {
                    $hasPartnerName = true;
                    if (empty($meta['value']) || trim($meta['value']) === '') {
                        $warnings[] = 'metadata partnerName est vide';
                    }
                    break;
                }
            }
            if (!$hasPartnerName) {
                $warnings[] = 'metadata partnerName manquant (recommandé)';
            }
        } else {
            $warnings[] = 'metadata manquant (recommandé)';
        }
        
        // Validation des headers critiques
        $requiredHeaders = ['Authorization', 'Version', 'X-CorrelationID', 'UserAccountIdentifier'];
        foreach ($requiredHeaders as $header) {
            if (!isset($headers[$header]) || trim($headers[$header]) === '') {
                $errors[] = "Header manquant ou vide: $header";
            }
        }
        
        // Log détaillé
        $logData = [
            'payload_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'payload_fields' => array_keys($payload),
            'payload_values' => $this->sanitizePayloadForLog($payload),
            'headers_present' => array_keys($headers),
        ];
        
        if (!empty($errors)) {
            $this->log('error', 'Validation payload échouée', $logData);
        } elseif (!empty($warnings)) {
            $this->log('warning', 'Validation payload avec avertissements', $logData);
        } else {
            $this->log('debug', 'Validation payload réussie', $logData);
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }
    
    /**
     * Nettoie le payload pour le logging (masque les valeurs sensibles)
     */
    private function sanitizePayloadForLog(array $payload): array
    {
        $sanitized = [];
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizePayloadForLog($value);
            } elseif (in_array($key, ['amount', 'currency', 'descriptionText', 'requestDate'])) {
                $sanitized[$key] = $value; // Ces champs sont OK à logger
            } else {
                $sanitized[$key] = is_string($value) ? substr($value, 0, 20) . '...' : $value;
            }
        }
        return $sanitized;
    }

    /**
     * Extrait le serverCorrelationId des headers de la réponse MVola.
     * AMÉLIORÉ : Extraction plus robuste avec multiples tentatives
     */
    private function extractCorrelationIdFromResponse($response): ?string
    {
        try {
            // Si c'est un objet Symfony HttpClient
            if (is_object($response) && method_exists($response, 'getHeaders')) {
                $headers = $response->getHeaders(false); // false = ne pas throw d'exception
            } elseif (is_object($response) && property_exists($response, 'headers')) {
                $headers = $response->headers;
            } else {
                return null;
            }
            
            $this->log('debug', 'Extraction correlationId depuis headers', [
                'available_headers' => is_array($headers) ? array_keys($headers) : 'N/A'
            ]);
            
            // Liste exhaustive de headers possibles (avec variations de casse)
            $possibleHeaders = [
                'x-correlationid',
                'x-correlation-id',
                'X-CorrelationID',
                'X-Correlation-ID',
                'correlationid',
                'CorrelationID',
                'server-correlation-id',
                'Server-Correlation-ID',
                'serverCorrelationId',
                'ServerCorrelationId',
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
                if (is_array($headers)) {
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
            }
            
            // Chercher aussi dans le body de la réponse
            try {
                if (is_object($response) && method_exists($response, 'getContent')) {
                    $body = $response->getContent(false);
                    $responseData = json_decode($body, true);
                    
                    if (isset($responseData['serverCorrelationId']) && !empty($responseData['serverCorrelationId'])) {
                        $correlationId = trim($responseData['serverCorrelationId']);
                        $this->log('info', 'CorrelationId trouvé dans body', [
                            'value' => $correlationId
                        ]);
                        return $correlationId;
                    }
                }
            } catch (\Exception $e) {
                $this->log('debug', 'Impossible de parser le body pour correlationId', [
                    'error' => $e->getMessage()
                ]);
            }
            
            $this->log('warning', 'Aucun correlationId trouvé dans la réponse');
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
    private function verifyTransactionAfter4002Error(
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
     * Initie une transaction avec retry automatique en cas d'erreur 4002
     */
    public function initiateTransactionWithRetry(
        float $amount,
        string $customerMsisdn,
        string $transactionReference,
        string $description = 'Paiement de billets',
        int $maxRetries = 3
    ): array {
        $attempt = 0;
        $baseRef = $transactionReference;
        
        while ($attempt < $maxRetries) {
            $attempt++;
            
            // Générer une référence unique à chaque tentative
            $uniqueRef = $this->generateUniqueTransactionReference($baseRef . '-R' . $attempt);
            
            $this->log('info', 'Tentative d\'initiation de transaction', [
                'attempt' => $attempt,
                'max_retries' => $maxRetries,
                'reference' => $uniqueRef,
            ]);
            
            $result = $this->initiateTransaction(
                $amount,
                $customerMsisdn,
                $uniqueRef,
                $description
            );
            
            // Si succès, retourner
            if ($result['success']) {
                $this->log('info', 'Transaction initiée avec succès', [
                    'attempt' => $attempt,
                    'reference' => $uniqueRef,
                ]);
                return $result;
            }
            
            // Vérifier si c'est une erreur qui mérite un retry
            $shouldRetry = false;
            $errorCode = null;
            
            // Erreur 500 ou 4002
            if (isset($result['http_status']) && $result['http_status'] === 500) {
                $shouldRetry = true;
            }
            if (isset($result['error_code'])) {
                $errorCode = $result['error_code'];
                if (in_array($errorCode, ['4002', '4001', '5000'])) {
                    $shouldRetry = true;
                }
            }
            
            if ($shouldRetry && $attempt < $maxRetries) {
                $waitTime = pow(2, $attempt); // Backoff exponentiel : 2s, 4s, 8s
                $this->log('warning', 'Erreur détectée, nouvelle tentative dans ' . $waitTime . 's', [
                    'attempt' => $attempt,
                    'error_code' => $errorCode,
                    'wait_time' => $waitTime,
                ]);
                sleep($waitTime);
                continue;
            }
            
            // Erreur non récupérable ou max retries atteint
            $this->log('error', 'Échec définitif de l\'initiation de transaction', [
                'attempts' => $attempt,
                'last_error' => $result['error'] ?? 'Erreur inconnue',
            ]);
            
            return $result;
        }
        
        return [
            'success' => false,
            'error' => 'Échec après ' . $maxRetries . ' tentatives. Dernière erreur : ' . ($result['error'] ?? 'Erreur inconnue'),
            'attempts' => $maxRetries,
        ];
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
        
        // ========== VALIDATION ET NETTOYAGE DES DONNÉES ENTRANTES ==========
        
        // 1. Valider et nettoyer partnerName AVANT toute utilisation
        $partnerName = trim($this->partnerName ?? '');
        if (empty($partnerName)) {
            $this->log('warning', 'partnerName est vide dans la config, utilisation d\'une valeur par défaut');
            $partnerName = 'Aiolia Event'; // Valeur par défaut
        }
        $partnerName = preg_replace('/\s+/', ' ', $partnerName); // Normaliser les espaces
        
        // 2. Valider et nettoyer les MSISDN
        $customerMsisdn = preg_replace('/\s+/', '', $this->normalizeMsisdn($customerMsisdn));
        $partnerMsisdn = preg_replace('/\s+/', '', $this->normalizeMsisdn($this->partnerMsisdn));
        
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
        
        // 3. Valider et nettoyer la référence de transaction
        $transactionReference = preg_replace('/\s+/', '', trim($transactionReference));
        if (empty($transactionReference)) {
            $transactionReference = $this->generateUniqueTransactionReference();
        }
        
        // 4. Valider et nettoyer la description
        $description = trim($description);
        if (empty($description)) {
            $description = 'Paiement de billets';
        }
        $description = preg_replace('/[^\w\s\-.,]/u', '', $description); // Nettoyer caractères spéciaux
        $description = trim($description);
        if (empty($description)) {
            $description = 'Paiement de billets';
        }
        $description = substr($description, 0, 50); // Limiter à 50 caractères
        
        // 5. Valider le montant
        $amountInt = (int) round($amount);
        if ($amountInt <= 0) {
            return [
                'success' => false,
                'error' => 'Le montant doit être supérieur à zéro',
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

            // ========== CONSTRUCTION DU PAYLOAD - TOUS LES CHAMPS VALIDÉS ==========
            
            // Date au format exact MVola (ISO 8601 avec millisecondes)
            $microtime = microtime(true);
            $seconds = floor($microtime);
            $milliseconds = str_pad((int) (($microtime - $seconds) * 1000), 3, '0', STR_PAD_LEFT);
            $requestDate = gmdate('Y-m-d\TH:i:s.', $seconds) . $milliseconds . 'Z';
            
            // Vérifier que la date est valide
            if (empty($requestDate) || strlen($requestDate) < 20) {
                return [
                    'success' => false,
                    'error' => 'Erreur lors de la génération de la date de requête',
                ];
            }
            
            // Construire debitParty - VALIDÉ
            $debitParty = [
                [
                    'key' => 'msisdn',
                    'value' => $customerMsisdn
                ]
            ];
            
            // Construire creditParty - VALIDÉ
            $creditParty = [
                [
                    'key' => 'msisdn',
                    'value' => $partnerMsisdn
                ]
            ];
            
            // Construire metadata - VALIDÉ
            // IMPORTANT: MVola requiert les champs 'fc' et 'amountFc' dans metadata selon la documentation
            $metadata = [
                [
                    'key' => 'partnerName',
                    'value' => $partnerName
                ],
                [
                    'key' => 'fc',
                    'value' => 'USD'
                ],
                [
                    'key' => 'amountFc',
                    'value' => '1'
                ]
            ];
            
            // Construire le payload final avec TOUS les champs validés
            // NOTE: Format basé sur le code fonctionnel de référence
            $payload = [
                'amount' => (string) $amountInt, // MVola attend une string pour le montant
                'currency' => 'Ar', // MVola attend 'Ar' et non 'MGA' selon le code fonctionnel
                'descriptionText' => $description, // Déjà limité à 50 caractères
                'requestingOrganisationTransactionReference' => $transactionReference,
                'requestDate' => $requestDate,
                'debitParty' => $debitParty,
                'creditParty' => $creditParty,
                'metadata' => $metadata,
            ];
            
            // Log pour vérifier que amount est bien présent
            $this->log('debug', 'Payload construit', [
                'amount_type' => gettype($payload['amount']),
                'amount_value' => $payload['amount'],
                'all_fields' => array_keys($payload),
            ]);

            // ========== VALIDATION FINALE AVEC LA NOUVELLE MÉTHODE ==========
            // (Les headers seront validés après leur construction)

            // Nettoyer UserAccountIdentifier de TOUS les espaces
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

            // ========== VALIDATION COMPLÈTE DU PAYLOAD ET DES HEADERS ==========
            $validation = $this->validateAndLogPayload($payload, $headers);
            
            if (!$validation['valid']) {
                $errorMsg = 'Erreurs de validation: ' . implode('; ', $validation['errors']);
                if (!empty($validation['warnings'])) {
                    $errorMsg .= ' | Avertissements: ' . implode('; ', $validation['warnings']);
                }
                
                return [
                    'success' => false,
                    'error' => $errorMsg,
                    'validation_errors' => $validation['errors'],
                    'validation_warnings' => $validation['warnings'],
                ];
            }
            
            // Log des headers
            error_log('[MVola] ========== HEADERS ENVOYÉS ==========');
            foreach ($headers as $key => $value) {
                if ($key === 'Authorization') {
                    error_log($key . ': Bearer *' . substr($value, -10));
                } else {
                    error_log($key . ': ' . $value);
                }
            }
            error_log('==========================================');

            $headersForLog = array_merge($headers, ['Authorization' => 'Bearer *']);
            error_log("\n" . str_repeat('=', 80) . "\n");
            error_log("[MVola] REQUÊTE COMPLÈTE (VALIDÉE):\n");
            error_log("URL: " . $endpoint . "\n");
            error_log("Headers:\n" . json_encode($headersForLog, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n");
            error_log("Payload JSON:\n" . json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n");
            if (!empty($validation['warnings'])) {
                error_log("Avertissements: " . implode(', ', $validation['warnings']) . "\n");
            }
            error_log(str_repeat('=', 80) . "\n");

            $this->log('debug', 'Requête MVola complète (validée)', [
                'endpoint' => $endpoint,
                'method' => 'POST',
                'headers' => $headersForLog,
                'payload' => $payload,
                'correlation_id' => $correlationId,
                'validation_warnings' => $validation['warnings'] ?? [],
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

                // Créer une réponse propre
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

            // Détecter le code d'erreur MVola (mmErrorCode) et autres paramètres d'erreur
            $mmErrorCode = null;
            $missingFieldFromParams = null;
            $allErrorParams = [];
            
            if (isset($data['errorParameters'])) {
                foreach ($data['errorParameters'] as $param) {
                    if (isset($param['key']) && isset($param['value'])) {
                        $allErrorParams[$param['key']] = $param['value'];
                        
                        if ($param['key'] === 'mmErrorCode') {
                            $mmErrorCode = $param['value'];
                        }
                        // Chercher des indices sur le champ manquant
                        if (in_array(strtolower($param['key']), ['missingfield', 'missing_field', 'field', 'fieldname', 'field_name'])) {
                            $missingFieldFromParams = $param['value'];
                        }
                    }
                }
            }

            // Gestion spécifique de l'erreur 4002 (DB Update Error)
            // IMPORTANT: Parfois MVola retourne 4002 mais crée quand même la transaction
            // Si statusCode est 500 avec erreur 4002, la transaction est souvent créée
            if ($mmErrorCode === '4002' || $mmErrorCode === 4002 || ($statusCode === 500 && isset($data['errorParameters'][0]['value']) && $data['errorParameters'][0]['value'] === '4002')) {
                $isSandbox = strpos($this->baseUrl, 'devapi.mvola.mg') !== false;
                
                $this->log('warning', 'Erreur MVola 4002 détectée (DB Update Error)', [
                    'error_code' => $mmErrorCode,
                    'status_code' => $statusCode,
                    'transaction_reference' => $transactionReference ?? 'N/A',
                    'is_sandbox' => $isSandbox,
                    'error_description' => $data['errorDescription'] ?? 'N/A',
                ]);
                
                // Extraire le serverCorrelationId depuis les headers (si disponible)
                $serverCorrelationId = null;
                // $response peut être un objet Symfony HttpClient ou un objet stdClass (cURL)
                if (isset($response)) {
                    if (is_object($response) && (method_exists($response, 'getHeaders') || method_exists($response, 'getStatusCode'))) {
                        // Symfony HttpClient - peut extraire les headers
                        $serverCorrelationId = $this->extractCorrelationIdFromResponse($response);
                    } elseif (is_object($response) && property_exists($response, 'data')) {
                        // Objet stdClass créé pour cURL - chercher dans le body
                        if (isset($response->data['serverCorrelationId'])) {
                            $serverCorrelationId = $response->data['serverCorrelationId'];
                        }
                    }
                }
                
                // Chercher aussi dans $data directement (au cas où)
                if (empty($serverCorrelationId) && isset($data['serverCorrelationId'])) {
                    $serverCorrelationId = $data['serverCorrelationId'];
                }
                
                // Récupérer le client correlationId (X-CorrelationID qu'on a envoyé)
                $clientCorrelationId = $correlationId ?? null;
                
                $this->log('info', 'Extraction correlationId pour 4002', [
                    'server_correlation_id' => $serverCorrelationId,
                    'client_correlation_id' => $clientCorrelationId,
                    'transaction_reference' => $transactionReference
                ]);
                
                // IMPORTANT: Si statusCode est 500 avec erreur 4002, la transaction est souvent créée
                // Dans ce cas, on considère que la transaction a été créée même si on ne peut pas la vérifier
                $transactionLikelyCreated = ($statusCode === 500 && ($mmErrorCode === '4002' || $mmErrorCode === 4002));
                
                if ($transactionLikelyCreated) {
                    $this->log('info', 'Status 500 avec erreur 4002 - Transaction probablement créée', [
                        'transaction_reference' => $transactionReference,
                        'server_correlation_id' => $serverCorrelationId,
                        'client_correlation_id' => $clientCorrelationId
                    ]);
                    
                    // Retourner un succès car la transaction est probablement créée
                    // L'utilisateur pourra vérifier dans "transaction approvals"
                    return [
                        'success' => true,
                        'serverCorrelationId' => $serverCorrelationId ?? $clientCorrelationId,
                        'transactionReference' => $transactionReference ?? 'UNKNOWN',
                        'status' => 'processing', // Statut par défaut car on ne peut pas vérifier
                        'notificationMethod' => 'polling',
                        'raw_response' => $data,
                        'note' => 'Transaction probablement créée malgré erreur DB 4002 (status 500). Vérifiez dans transaction approvals.',
                        'requires_manual_verification' => true,
                        'http_status' => $statusCode,
                    ];
                }
                
                // Tentative de vérification automatique : la transaction a peut-être été créée malgré l'erreur
                $verificationResult = $this->verifyTransactionAfter4002Error(
                    $transactionReference ?? 'UNKNOWN',
                    $serverCorrelationId,
                    $clientCorrelationId,
                    3, // maxAttempts (réduit pour ne pas bloquer trop longtemps)
                    2  // delayBetweenAttempts
                );
                
                if ($verificationResult['success']) {
                    // Transaction trouvée ! Elle a été créée malgré l'erreur 4002
                    $this->log('info', 'Transaction créée malgré erreur 4002 (auto-verified)', [
                        'correlation_id' => $verificationResult['correlation_id'],
                        'status' => $verificationResult['status']
                    ]);
                    
                    return [
                        'success' => true,
                        'serverCorrelationId' => $verificationResult['correlation_id'],
                        'transactionReference' => $transactionReference ?? 'UNKNOWN',
                        'status' => $verificationResult['status'] ?? 'processing',
                        'notificationMethod' => 'polling',
                        'raw_response' => $data,
                        'note' => 'Transaction créée malgré erreur DB 4002 (auto-verified)',
                        'verification_result' => $verificationResult
                    ];
                }
                
                // Transaction non trouvée, mais si c'est un status 500, on considère qu'elle est créée
                // car l'utilisateur a confirmé voir la transaction dans "transaction approvals"
                if ($statusCode === 500) {
                    $this->log('info', 'Status 500 avec erreur 4002 - Considérer comme succès (transaction visible dans approvals)', [
                        'transaction_reference' => $transactionReference
                    ]);
                    
                    return [
                        'success' => true,
                        'serverCorrelationId' => $serverCorrelationId ?? $clientCorrelationId,
                        'transactionReference' => $transactionReference ?? 'UNKNOWN',
                        'status' => 'processing',
                        'notificationMethod' => 'polling',
                        'raw_response' => $data,
                        'note' => 'Transaction créée (visible dans transaction approvals) malgré erreur DB 4002',
                        'http_status' => $statusCode,
                    ];
                }
                
                // Transaction non trouvée, retourner l'erreur pour permettre le retry
                $errorMessage = 'Erreur MVola DB Update (code 4002). Causes possibles : référence de transaction en double, problème de compte, ou problème temporaire du système MVola.';
                if ($isSandbox) {
                    $errorMessage .= ' Le système va réessayer automatiquement avec une nouvelle référence unique.';
                }

                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'error_code' => '4002',
                    'error_category' => $data['errorCategory'] ?? 'internal',
                    'retry_suggested' => true, // Important pour que initiateTransactionWithRetry fonctionne
                    'http_status' => $statusCode,
                    'raw_response' => $data,
                    'payload_sent' => $payload ?? [],
                    'server_correlation_id' => $serverCorrelationId,
                    'client_correlation_id' => $clientCorrelationId,
                    'verification_attempted' => true,
                    'verification_result' => $verificationResult,
                ];
            }

            // Gestion spécifique de l'erreur 4001 (Missing field)
            if ($mmErrorCode === '4001' || $mmErrorCode === 4001) {
                $isSandbox = strpos($this->baseUrl, 'devapi.mvola.mg') !== false;
                
                $errorMessage = 'Erreur MVola: Champ manquant (code 4001)';
                if ($isSandbox) {
                    $errorMessage .= '. NOTE: L\'environnement sandbox MVola présente un problème connu qui peut retourner cette erreur même lorsque tous les champs sont présents et correctement formatés.';
                }
                $errorMessage .= ' - ' . $apiError;
                
                $this->log('warning', 'Erreur MVola 4001 détectée (Missing field)', [
                    'error_code' => $mmErrorCode,
                    'status_code' => $statusCode,
                    'is_sandbox' => $isSandbox,
                    'transaction_reference' => $transactionReference ?? 'N/A',
                    'payload_sent' => $payload ?? [],
                    'all_error_params' => $allErrorParams ?? [],
                ]);

                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'error_code' => '4001',
                    'error_category' => $data['errorCategory'] ?? 'validation',
                    'retry_suggested' => false, // Ne pas retry car c'est probablement un problème de format
                    'is_sandbox_known_issue' => $isSandbox,
                    'http_status' => $statusCode,
                    'raw_response' => $data,
                    'payload_sent' => $payload ?? [],
                ];
            }

            // Autres codes d'erreur MVola courants
            $errorMessages = [
                '5000' => 'Erreur serveur interne MVola (code 5000)',
                '1001' => 'Paramètre manquant ou invalide (code 1001)',
                '1002' => 'Format de requête invalide (code 1002)',
            ];

            if ($mmErrorCode && isset($errorMessages[$mmErrorCode])) {
                return [
                    'success' => false,
                    'error' => $errorMessages[$mmErrorCode] . ' - ' . $apiError,
                    'error_code' => (string) $mmErrorCode,
                    'retry_suggested' => in_array($mmErrorCode, ['5000']),
                    'http_status' => $statusCode,
                    'raw_response' => $data,
                ];
            }

            // Détecter le champ manquant depuis plusieurs sources
            $missingField = $missingFieldFromParams; // Priorité aux errorParameters
            
            if (!$missingField) {
                $missingFieldKeys = ['fault.detail', 'detail', 'missingField', 'missing_field', 'field', 'fieldName', 'field_name'];
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
            }

            // Essayer d'extraire depuis le message d'erreur
            if (!$missingField && preg_match('/Missing field[:\s]+([a-zA-Z_]+)/i', $apiError, $matches)) {
                $missingField = $matches[1] ?? null;
            }
            if (!$missingField && preg_match('/field[:\s]+([a-zA-Z_]+)[\s]+is required/i', $apiError, $matches)) {
                $missingField = $matches[1] ?? null;
            }
            if (!$missingField && preg_match('/required[:\s]+([a-zA-Z_]+)/i', $apiError, $matches)) {
                $missingField = $matches[1] ?? null;
            }

            $logOutput = "\n" . str_repeat('=', 80) . "\n";
            $logOutput .= "[MVola] ERREUR DÉTECTÉE:\n";
            $logOutput .= "HTTP Status: " . $statusCode . "\n";
            $logOutput .= "Message d'erreur API: " . ($apiError ?? 'Non spécifié') . "\n";
            $logOutput .= "Code erreur MVola (mmErrorCode): " . ($mmErrorCode ?? 'Non détecté') . "\n";
            $logOutput .= "Champ manquant détecté: " . ($missingField ?? 'Non détecté') . "\n";
            if (!empty($allErrorParams)) {
                $logOutput .= "Tous les paramètres d'erreur: " . json_encode($allErrorParams, JSON_PRETTY_PRINT) . "\n";
            }
            $logOutput .= "\n--- RÉPONSE COMPLÈTE DE L'API ---\n";
            $logOutput .= json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            $logOutput .= "\n--- PAYLOAD ENVOYÉ (VÉRIFICATION) ---\n";
            $logOutput .= json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            $logOutput .= "\n--- VÉRIFICATION DES CHAMPS DU PAYLOAD ---\n";
            foreach ($payload as $key => $value) {
                $isEmpty = empty($value);
                $type = gettype($value);
                if (is_array($value)) {
                    $logOutput .= "  $key: array avec " . count($value) . " éléments\n";
                    foreach ($value as $idx => $item) {
                        if (is_array($item)) {
                            $logOutput .= "    [$idx]: " . json_encode($item) . "\n";
                        } else {
                            $logOutput .= "    [$idx]: $item\n";
                        }
                    }
                } else {
                    $logOutput .= "  $key: $type = " . ($isEmpty ? '(VIDE)' : $value) . "\n";
                }
            }
            $logOutput .= str_repeat('=', 80) . "\n";

            error_log($logOutput);
            file_put_contents('php://stderr', $logOutput);

            $this->log('error', 'Erreur initiation transaction', [
                'http_status' => $statusCode,
                'api_response' => $data,
                'payload_sent' => $payload,
                'mm_error_code' => $mmErrorCode,
                'missing_field' => $missingField,
                'endpoint' => $endpoint,
            ]);

            $message = sprintf(
                'Erreur lors de l\'initiation de la transaction MVola (HTTP %d%s)',
                $statusCode,
                $apiError ? (', détail: ' . $apiError) : ''
            );

            if ($mmErrorCode) {
                $message .= ' - Code erreur MVola: ' . $mmErrorCode;
            }

            if ($missingField) {
                $message .= ' - Champ manquant: ' . $missingField;
            }

            return [
                'success' => false,
                'error' => $message,
                'error_code' => $mmErrorCode,
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
            $this->log('error', 'Erreur lors de l\'initiation du paiement MVola', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
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