<?php

namespace App\Service\Organisateur;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class MvolaService
{
    private ?string $accessToken = null;
    private ?\DateTimeInterface $tokenExpiration = null;

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $environment,
        private string $consumerKey,
        private string $consumerSecret,
        private string $apiBaseUrl,
        private string $tokenUrl,
        private string $merchantMsisdn,
        private string $merchantName,
        private string $merchantAccountId,
        private string $callbackUrl,
        private string $notificationUrl,
        private int $requestTimeout,
        private int $connectionTimeout
    ) {
    }

    /**
     * Obtient un token d'accès OAuth2
     */
    private function getAccessToken(): string
    {
        if ($this->accessToken && $this->tokenExpiration && $this->tokenExpiration > new \DateTime()) {
            return $this->accessToken;
        }
    
        try {
            $this->logger->info('Requesting MVola access token');
    
            // Encodage en Base64 pour Basic Auth
            $credentials = base64_encode($this->consumerKey . ':' . $this->consumerSecret);
            
            // LOG POUR DEBUG
            $this->logger->info('MVola Auth Debug', [
                'consumer_key' => $this->consumerKey,
                'consumer_secret' => substr($this->consumerSecret, 0, 10) . '...', // Affiche seulement le début
                'credentials_base64' => $credentials,
                'expected_base64' => 'QWlucmw0Z2R3WmE5RUxGNjhzZDRZRmc5bWVVYTpHVm14bWFYbk5lMFV6M0RyZmN1dDRSd3E2Z1Fh'
            ]);
    
            $response = $this->httpClient->request('POST', $this->tokenUrl, [
                'headers' => [
                    'Authorization' => 'Basic ' . $credentials,
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => [
                    'grant_type' => 'client_credentials',
                ],
                'timeout' => $this->connectionTimeout,
                'verify_peer' => false,
                'verify_host' => false,
            ]);
    
            $data = $response->toArray();
            $this->accessToken = $data['access_token'];
            
            $expiresIn = $data['expires_in'] ?? 3600;
            $this->tokenExpiration = (new \DateTime())->modify("+{$expiresIn} seconds");
    
            $this->logger->info('MVola access token obtained successfully', [
                'expires_in' => $expiresIn
            ]);
    
            return $this->accessToken;
        } catch (\Exception $e) {
            $this->logger->error('Failed to obtain MVola access token', [
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Impossible d\'obtenir le token MVola: ' . $e->getMessage());
        }
    }
    /**
     * Initie un paiement MVola avec les headers exacts requis
     */
    public function initiatePayment(
        string $customerMsisdn,
        float $amount,
        string $reference,
        string $description
    ): array {
        $token = $this->getAccessToken();

        // Génération du X-CorrelationID
        $correlationId = $this->generateCorrelationId();

        // Construction du corps de la requête
        $requestData = [
            'amount' => (string) number_format($amount, 0, '', ''),
            'currency' => 'Ar',
            'descriptionText' => $description,
            'requestingOrganisationTransactionReference' => $reference,
            'requestDate' => (new \DateTime())->format('Y-m-d\TH:i:s.v\Z'),
            'originalTransactionReference' => '',
            'debitParty' => [
                [
                    'key' => 'msisdn',
                    'value' => $customerMsisdn,
                ],
            ],
            'creditParty' => [
                [
                    'key' => 'msisdn',
                    'value' => $this->merchantMsisdn,
                ],
            ],
            'metadata' => [
                [
                    'key' => 'partnerName',
                    'value' => $this->merchantName,
                ],
            ],
        ];

        $this->logger->info('Initiating MVola payment', [
            'reference' => $reference,
            'amount' => $amount,
            'customer' => $customerMsisdn,
            'merchant' => $this->merchantMsisdn,
            'correlation_id' => $correlationId,
        ]);

        try {
            $response = $this->httpClient->request(
                'POST', 
                $this->apiBaseUrl . '/mvola/mm/transactions/type/merchantpay/1.0.0/',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token,
                        'Version' => '1.0',
                        'X-CorrelationID' => $correlationId,
                        'UserLanguage' => 'mg',
                        'UserAccountIdentifier' => 'msisdn;' . $this->merchantMsisdn,
                        'partnerName' => $this->merchantName,
                        'Content-Type' => 'application/json',
                        'Cache-Control' => 'no-cache',
                        'X-Callback-URL' => $this->callbackUrl,
                    ],
                    'json' => $requestData,
                    'timeout' => $this->requestTimeout,
                ]
            );

            $result = $response->toArray();

            $this->logger->info('MVola payment initiated successfully', [
                'reference' => $reference,
                'server_correlation_id' => $result['serverCorrelationId'] ?? null,
                'status' => $result['status'] ?? 'unknown',
            ]);

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to initiate MVola payment', [
                'reference' => $reference,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw new \RuntimeException('Erreur lors de l\'initiation du paiement MVola: ' . $e->getMessage());
        }
    }

    /**
     * Vérifie le statut d'une transaction
     */
    public function getTransactionStatus(string $transactionReference): array
    {
        $token = $this->getAccessToken();
        $correlationId = $this->generateCorrelationId();

        $this->logger->info('Checking MVola transaction status', [
            'transaction_reference' => $transactionReference,
            'correlation_id' => $correlationId,
        ]);

        try {
            $response = $this->httpClient->request(
                'GET',
                $this->apiBaseUrl . '/mvola/mm/transactions/type/merchantpay/1.0.0/' . $transactionReference,
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token,
                        'Version' => '1.0',
                        'X-CorrelationID' => $correlationId,
                        'UserLanguage' => 'mg',
                        'UserAccountIdentifier' => 'msisdn;' . $this->merchantMsisdn,
                        'Cache-Control' => 'no-cache',
                    ],
                    'timeout' => $this->requestTimeout,
                ]
            );

            $result = $response->toArray();

            $this->logger->info('MVola transaction status retrieved', [
                'transaction_reference' => $transactionReference,
                'status' => $result['status'] ?? 'unknown',
            ]);

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get MVola transaction status', [
                'reference' => $transactionReference,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw new \RuntimeException('Erreur lors de la vérification du statut: ' . $e->getMessage());
        }
    }

    /**
     * Génère un ID de corrélation unique au format mvola-xxxxxxxx
     */
    private function generateCorrelationId(): string
    {
        return 'mvola-' . bin2hex(random_bytes(4));
    }

    /**
     * Vérifie si l'environnement est sandbox
     */
    public function isSandbox(): bool
    {
        return $this->environment === 'sandbox';
    }

    /**
     * Récupère le token d'accès (uniquement pour le debug)
     * NE PAS exposer cette valeur en production.
     */
    public function getAccessTokenForDebug(): string
    {
        return $this->getAccessToken();
    }

    /**
     * Retourne les informations du marchand
     */
    public function getMerchantInfo(): array
    {
        return [
            'msisdn' => $this->merchantMsisdn,
            'name' => $this->merchantName,
            'account_id' => $this->merchantAccountId,
        ];
    }
}