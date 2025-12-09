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
        $this->baseUrl = $baseUrl;
        $this->consumerKey = $consumerKey;
        $this->consumerSecret = $consumerSecret;
        $this->partnerMsisdn = $partnerMsisdn;
        $this->partnerName = $partnerName;
        $this->callbackUrl = $callbackUrl;
        $this->logger = $logger;
    }

    /**
     * Retourne l'URL de base de l'API MVola.
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
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
                'base_url' => $this->baseUrl,
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
        if (empty($this->baseUrl) || empty($this->consumerKey) || empty($this->consumerSecret) || empty($this->partnerMsisdn)) {
            return [
                'success' => false,
                'error' => 'Configuration MVola incomplète.',
            ];
        }

        // Normaliser les numéros de téléphone
        $customerMsisdn = $this->normalizeMsisdn($customerMsisdn);
        $partnerMsisdn = $this->normalizeMsisdn($this->partnerMsisdn);
        
        // Valider les numéros de téléphone
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
            $endpoint = rtrim($this->baseUrl, '/') . '/mvola/mm/transactions/type/merchantpay/1.0.0';
            
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
                'descriptionText' => substr($description, 0, 50), // Description nettoyée
                'requestingOrganisationTransactionReference' => $transactionReference, // Référence unique
                'requestDate' => $requestDate,
                'originalTransactionReference' => '', // Présent mais vide comme dans Postman
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
            
            // Vérification finale que tous les champs sont bien présents
            $expectedFields = ['amount', 'currency', 'descriptionText', 'requestingOrganisationTransactionReference', 'requestDate', 'originalTransactionReference', 'debitParty', 'creditParty', 'metadata'];
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
            error_log('[MVola] PartnerName: ' . $payload['metadata'][0]['value']);
            error_log('[MVola] ========================================');
            
            // Validation stricte: s'assurer qu'aucun champ n'est null ou vide (sauf originalTransactionReference)
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
            ];
            
            // Validation: originalTransactionReference peut être vide mais doit être présent
            // Tous les autres champs doivent être non vides
            $missingFields = [];
            foreach ($requiredFields as $field => $value) {
                // originalTransactionReference peut être vide, mais tous les autres doivent être remplis
                if ($field === 'originalTransactionReference') {
                    continue; // On skip car il peut être vide
                }
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
            
            // Ne pas ajouter originalTransactionReference si vide (omis comme dans certains cas Postman)
            // L'API peut ne pas l'accepter comme chaîne vide
            
            // Validation finale: s'assurer qu'aucun champ requis n'est vide
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

            // Headers EXACTEMENT comme dans Postman (ordre et format identiques)
            // IMPORTANT: Les noms de headers sont sensibles à la casse
            // IMPORTANT: Les valeurs doivent être des strings sans guillemets, sans espaces, sans caractères invisibles
            $partnerMsisdnClean = trim($partnerMsisdn); // Nettoyer le numéro
            $userAccountIdentifier = 'msisdn;' . $partnerMsisdnClean; // Format exact: msisdn;0382795455 (sans guillemets, sans espaces)
            
            // Vérification que UserAccountIdentifier est bien formaté (exactement comme Postman)
            error_log('[MVola] UserAccountIdentifier brut: [' . $userAccountIdentifier . ']');
            error_log('[MVola] UserAccountIdentifier longueur: ' . strlen($userAccountIdentifier) . ' caractères');
            error_log('[MVola] UserAccountIdentifier hex: ' . bin2hex($userAccountIdentifier)); // Pour voir les caractères invisibles
            
            // Headers EXACTEMENT dans l'ordre de Postman
            $headers = [
                'Authorization' => 'Bearer ' . trim($accessToken),
                'Version' => '1.0',
                'X-CorrelationID' => $correlationId,
                'UserLanguage' => 'mg',
                'UserAccountIdentifier' => $userAccountIdentifier,
                'partnerName' => trim($this->partnerName),
                'Content-Type' => 'application/json',
                'Cache-Control' => 'no-cache',
                'Accept' => 'application/json', // Ajouté comme dans Postman
            ];
            
            // Ajouter X-Callback-URL seulement si fourni (comme dans Postman)
            if (!empty($this->callbackUrl)) {
                $headers['X-Callback-URL'] = trim($this->callbackUrl);
            }
            
            // Log des headers pour vérification
            error_log('[MVola] ========== HEADERS ENVOYÉS ==========');
            foreach ($headers as $key => $value) {
                if ($key === 'Authorization') {
                    error_log($key . ': Bearer ***' . substr($value, -10)); // Afficher les 10 derniers caractères
                } else {
                    error_log($key . ': ' . $value);
                }
            }
            error_log('==========================================');

            // Afficher directement dans la console/erreur (pour comparaison avec Postman)
            $headersForLog = array_merge($headers, ['Authorization' => 'Bearer ***']); // Masquer le token
            error_log("\n" . str_repeat('=', 80) . "\n");
            error_log("[MVola] REQUÊTE COMPLÈTE (identique à Postman):\n");
            error_log("URL: " . $endpoint . "\n");
            error_log("Headers (ordre Postman):\n" . json_encode($headersForLog, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n");
            error_log("Payload JSON (identique à Postman):\n" . json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n");
            error_log("Token valide: Oui (longueur: " . strlen($accessToken) . " caractères)\n");
            error_log(str_repeat('=', 80) . "\n");
            
            $this->log('debug', 'Requête MVola complète', [
                'endpoint' => $endpoint,
                'method' => 'POST',
                'headers' => $headersForLog,
                'payload' => $payload,
                'correlation_id' => $correlationId,
            ]);

            // Envoyer la requête
            // IMPORTANT: Utiliser 'json' pour encoder automatiquement en JSON
            // Cela garantit que le Content-Type est bien application/json
            try {
                // Vérifier le payload avant envoi et comparer avec Postman
                $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if ($payloadJson === false) {
                    $error = 'Erreur lors de l\'encodage JSON du payload: ' . json_last_error_msg();
                    error_log('[MVola] ' . $error);
                    return [
                        'success' => false,
                        'error' => $error,
                    ];
                }
                
                // Log du JSON exact qui sera envoyé (pour comparaison avec Postman)
                error_log("\n[MVola] ========== PAYLOAD JSON EXACT ==========");
                error_log($payloadJson);
                error_log("==========================================\n");
                
                // Vérifier le format JSON (doit être identique à Postman)
                $decodedPayload = json_decode($payloadJson, true);
                if ($decodedPayload === null) {
                    error_log('[MVola] ERREUR: Le payload JSON est invalide!');
                    return [
                        'success' => false,
                        'error' => 'Payload JSON invalide: ' . json_last_error_msg(),
                    ];
                }
                
                // Vérifier chaque champ individuellement
                error_log('[MVola] Vérification des champs du payload:');
                error_log('  - amount: ' . (isset($decodedPayload['amount']) ? $decodedPayload['amount'] : 'MANQUANT'));
                error_log('  - currency: ' . (isset($decodedPayload['currency']) ? $decodedPayload['currency'] : 'MANQUANT'));
                error_log('  - descriptionText: ' . (isset($decodedPayload['descriptionText']) ? $decodedPayload['descriptionText'] : 'MANQUANT'));
                error_log('  - requestingOrganisationTransactionReference: ' . (isset($decodedPayload['requestingOrganisationTransactionReference']) ? $decodedPayload['requestingOrganisationTransactionReference'] : 'MANQUANT'));
                error_log('  - requestDate: ' . (isset($decodedPayload['requestDate']) ? $decodedPayload['requestDate'] : 'MANQUANT'));
                error_log('  - originalTransactionReference: ' . (isset($decodedPayload['originalTransactionReference']) ? ('"' . $decodedPayload['originalTransactionReference'] . '"') : 'MANQUANT'));
                error_log('  - debitParty: ' . (isset($decodedPayload['debitParty']) ? json_encode($decodedPayload['debitParty']) : 'MANQUANT'));
                error_log('  - creditParty: ' . (isset($decodedPayload['creditParty']) ? json_encode($decodedPayload['creditParty']) : 'MANQUANT'));
                error_log('  - metadata: ' . (isset($decodedPayload['metadata']) ? json_encode($decodedPayload['metadata']) : 'MANQUANT'));
                
                // Vérifier que tous les champs de Postman sont présents
                $postmanFields = [
                    'amount', 'currency', 'descriptionText', 
                    'requestingOrganisationTransactionReference', 
                    'requestDate', 'originalTransactionReference',
                    'debitParty', 'creditParty', 'metadata'
                ];
                
                $missingPostmanFields = [];
                foreach ($postmanFields as $field) {
                    if (!isset($payload[$field])) {
                        $missingPostmanFields[] = $field;
                    }
                }
                
                if (!empty($missingPostmanFields)) {
                    error_log('[MVola] ERREUR CRITIQUE: Champs Postman manquants: ' . implode(', ', $missingPostmanFields));
                    return [
                        'success' => false,
                        'error' => 'Champs Postman manquants: ' . implode(', ', $missingPostmanFields),
                    ];
                }
                
                // Encoder le JSON manuellement (comme Postman) au lieu d'utiliser l'option 'json'
                // IMPORTANT: Utiliser JSON_UNESCAPED_SLASHES pour éviter d'échapper les slashes
                // et JSON_UNESCAPED_UNICODE pour les caractères spéciaux
                $jsonBody = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                
                if ($jsonBody === false) {
                    $error = 'Erreur lors de l\'encodage JSON: ' . json_last_error_msg();
                    error_log('[MVola] ' . $error);
                    return [
                        'success' => false,
                        'error' => $error,
                    ];
                }
                
                // Log du JSON exact pour comparaison avec Postman
                error_log('[MVola] ========== JSON BODY EXACT ==========');
                error_log($jsonBody);
                error_log('==========================================');
                
                // Vérifier que le JSON est valide
                $decodedCheck = json_decode($jsonBody, true);
                if ($decodedCheck === null && json_last_error() !== JSON_ERROR_NONE) {
                    error_log('[MVola] ERREUR: JSON invalide après encodage!');
                    return [
                        'success' => false,
                        'error' => 'JSON invalide: ' . json_last_error_msg(),
                    ];
                }
                
                // IMPORTANT: S'assurer que Content-Type est bien application/json
                // Symfony HttpClient peut parfois modifier les headers
                if (!isset($headers['Content-Type'])) {
                    $headers['Content-Type'] = 'application/json';
                }
                
                // Encoder le payload en JSON
                $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                
                // Log final avant envoi
                error_log('[MVola] ========== REQUÊTE FINALE ==========');
                error_log('URL: ' . $endpoint);
                error_log('Method: POST');
                error_log('Headers count: ' . count($headers));
                error_log('Body length: ' . strlen($jsonPayload) . ' bytes');
                error_log('Body complet: ' . $jsonPayload);
                error_log('==========================================');
                
                try {
                    // Vérifier si cURL est disponible, sinon utiliser Symfony HttpClient
                    if (!function_exists('curl_init')) {
                        error_log('[MVola] cURL non disponible, utilisation de Symfony HttpClient');
                        // Utiliser Symfony HttpClient avec body manuel pour contrôle total
                        $response = $this->httpClient->request('POST', $endpoint, [
                            'headers' => $headers,
                            'body' => $jsonPayload, // Encodage manuel comme Postman
                        ]);
                    } else {
                        // Utiliser cURL directement pour avoir un contrôle total (comme Postman)
                        $ch = curl_init($endpoint);
                        
                        // Préparer les headers pour cURL (format exact: "Header: Value")
                        $curlHeaders = [];
                        foreach ($headers as $key => $value) {
                            $curlHeaders[] = $key . ': ' . $value;
                        }
                        
                        // Configuration cURL (exactement comme Postman)
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
                        
                        // Exécuter la requête
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
                        
                        // Décoder la réponse
                        $data = json_decode($curlResponse, true);
                        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                            error_log('[MVola] Erreur décodage JSON réponse: ' . json_last_error_msg());
                            error_log('[MVola] Réponse brute: ' . $curlResponse);
                            $data = ['raw_response' => $curlResponse];
                        }
                        
                        // Créer une réponse compatible avec le reste du code
                        // On simule une ResponseInterface de Symfony
                        $response = new class($statusCode, $data, $curlResponse) {
                            private $statusCode;
                            private $data;
                            private $rawResponse;
                            
                            public function __construct($statusCode, $data, $rawResponse) {
                                $this->statusCode = $statusCode;
                                $this->data = $data;
                                $this->rawResponse = $rawResponse;
                            }
                            
                            public function getStatusCode() {
                                return $this->statusCode;
                            }
                            
                            public function toArray($throw = true) {
                                return $this->data;
                            }
                        };
                        
                        error_log('[MVola] Requête envoyée avec succès (via cURL direct), attente de la réponse...');
                    }
                } catch (\Exception $e) {
                    error_log('[MVola] Exception lors de l\'envoi: ' . $e->getMessage());
                    error_log('[MVola] Stack trace: ' . $e->getTraceAsString());
                    throw $e;
                }

                $statusCode = $response->getStatusCode();
                $data = $response->toArray(false);
                
                // Log de la réponse pour debug - AFFICHAGE DIRECT
                error_log("\n" . str_repeat('=', 80) . "\n");
                error_log("[MVola] RÉPONSE REÇUE:\n");
                error_log("Status Code: " . $statusCode . "\n");
                error_log("Response:\n" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n");
                error_log(str_repeat('=', 80) . "\n");
                
                $this->log('debug', 'Réponse MVola reçue', [
                    'status_code' => $statusCode,
                    'response' => $data,
                ]);
            } catch (\Exception $e) {
                // Log de l'erreur complète
                $this->log('error', 'Exception lors de l\'appel API MVola', [
                    'message' => $e->getMessage(),
                    'endpoint' => $endpoint,
                    'payload' => $payload,
                    'headers' => array_keys($headers),
                ]);
                throw $e;
            }

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

            // Construire un message d'erreur plus détaillé pour le debug
            // Essayer toutes les clés possibles pour trouver le message d'erreur
            $apiError = null;
            $errorKeys = [
                'errorDescription', 'error_description', 'errorDescriptionText', 'error',
                'message', 'fault.description', 'fault.detail', 'detail',
                'errorMessage', 'error_message', 'statusDescription', 'status_description'
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
            
            // Si pas trouvé, convertir toute la réponse en string pour debug
            if (!$apiError) {
                $apiError = 'Réponse API: ' . json_encode($data, JSON_UNESCAPED_UNICODE);
            }
            
            // Extraire le nom du champ manquant si disponible
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
            
            // Essayer de parser le message d'erreur pour trouver le champ
            if (!$missingField && preg_match('/Missing field[:\s]+([a-zA-Z_]+)/i', $apiError, $matches)) {
                $missingField = $matches[1] ?? null;
            }
            if (!$missingField && preg_match('/field[:\s]+([a-zA-Z_]+)[\s]+is required/i', $apiError, $matches)) {
                $missingField = $matches[1] ?? null;
            }

            // Log détaillé pour debug - AFFICHAGE DIRECT (forcé)
            $logOutput = "\n" . str_repeat('=', 80) . "\n";
            $logOutput .= "[MVola] ERREUR DÉTECTÉE:\n";
            $logOutput .= "HTTP Status: " . $statusCode . "\n";
            $logOutput .= "Message d'erreur API: " . ($apiError ?? 'Non spécifié') . "\n";
            $logOutput .= "Champ manquant détecté: " . ($missingField ?? 'Non détecté') . "\n";
            $logOutput .= "\n--- RÉPONSE COMPLÈTE DE L'API ---\n";
            $logOutput .= json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            $logOutput .= "\n--- PAYLOAD ENVOYÉ ---\n";
            $logOutput .= json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            $logOutput .= "\n--- HEADERS ENVOYÉS ---\n";
            $logOutput .= json_encode(array_merge($headers, ['Authorization' => 'Bearer ***']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            $logOutput .= str_repeat('=', 80) . "\n";
            
            // Forcer l'affichage dans plusieurs endroits
            error_log($logOutput);
            // Aussi dans stderr
            file_put_contents('php://stderr', $logOutput);
            // Et dans stdout si possible
            if (php_sapi_name() !== 'cli') {
                // En mode web, on peut aussi logger dans un fichier temporaire
                @file_put_contents(sys_get_temp_dir() . '/mvola_debug.log', $logOutput, FILE_APPEND);
            }

            // Log détaillé pour debug
            $this->log('error', 'Erreur initiation transaction', [
                'http_status' => $statusCode,
                'api_response' => $data,
                'payload_sent' => $payload,
                'missing_field' => $missingField,
                'endpoint' => $endpoint,
                'headers' => array_merge($headers, ['Authorization' => 'Bearer ***']), // Masquer le token
            ]);

            // Construire un message d'erreur détaillé
            $message = sprintf(
                'Erreur lors de l\'initiation de la transaction MVola (HTTP %d%s)',
                $statusCode,
                $apiError ? (', détail: ' . $apiError) : ''
            );
            
            if ($missingField) {
                $message .= ' - Champ manquant: ' . $missingField;
            } else {
                // Ajouter toute la réponse si on ne trouve pas le champ manquant
                $message .= ' - Réponse complète: ' . json_encode($data, JSON_UNESCAPED_UNICODE);
            }

            return [
                'success' => false,
                'error' => $message,
                'raw_response' => $data,
                'payload_sent' => $payload, // Inclure le payload pour debug
                'http_status' => $statusCode,
                'missing_field' => $missingField,
            ];
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


