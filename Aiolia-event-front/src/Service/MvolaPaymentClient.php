<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Client très simplifié pour l'API MVola MerchantPay (sandbox).
 *
 * Objectif : avoir un point d'intégration clair, testable, et
 * indépendant des détails exacts de l'API (URL, headers, etc.)
 * qui viennent de la doc PDF.
 *
 * Les vraies URLs / payloads peuvent ensuite être ajustés en fonction
 * de la documentation officielle.
 */
class MvolaPaymentClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        // Ces valeurs viennent idéalement du .env (sandbox)
        private readonly string $baseUrl = '',
        private readonly string $consumerKey = '',
        private readonly string $consumerSecret = '',
        private readonly string $merchantMsisdn = '',
        private readonly ?string $callbackUrl = null,
    ) {
    }

    /**
     * Initialise un paiement MerchantPay MVola (sandbox).
     *
     * @param float  $amount          Montant en MGA
     * @param string $customerMsisdn  Numéro MVola du client (MSISDN)
     * @param string $externalId      Référence de commande côté Aiolia
     *
     * @return array{success: bool, raw_response: array<string, mixed>|null, error?: string}
     */
    public function initiateMerchantPay(float $amount, string $customerMsisdn, string $externalId): array
    {
        // Sécurité basique : on évite d'appeler l'API si la config est vide
        if ('' === $this->baseUrl || '' === $this->consumerKey || '' === $this->consumerSecret || '' === $this->merchantMsisdn) {
            return [
                'success' => false,
                'raw_response' => null,
                'error' => 'Configuration MVola incomplète (baseUrl / consumerKey / consumerSecret / merchantMsisdn).',
            ];
        }

        try {
            // 1) Récupérer un access_token (flux simplifié – à adapter selon la doc MVola)
            $tokenResponse = $this->httpClient->request('POST', rtrim($this->baseUrl, '/') . '/token', [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'consumerKey' => $this->consumerKey,
                    'consumerSecret' => $this->consumerSecret,
                ],
            ]);

            $tokenData = $tokenResponse->toArray(false);
            $accessToken = $tokenData['access_token'] ?? null;

            if (null === $accessToken) {
                return [
                    'success' => false,
                    'raw_response' => $tokenData,
                    'error' => 'Impossible de récupérer un access_token MVola.',
                ];
            }

            // 2) Appel MerchantPay (endpoint et payload à ajuster selon la doc PDF)
            $paymentPayload = [
                'amount' => $amount,
                'currency' => 'MGA',
                'customerMsisdn' => $customerMsisdn,
                'merchantMsisdn' => $this->merchantMsisdn,
                'externalId' => $externalId,
            ];

            if ($this->callbackUrl) {
                $paymentPayload['callbackUrl'] = $this->callbackUrl;
            }

            $paymentResponse = $this->httpClient->request('POST', rtrim($this->baseUrl, '/') . '/merchantpay', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $paymentPayload,
            ]);

            $paymentData = $paymentResponse->toArray(false);

            // Suivant la doc, il faudra adapter cette logique (statut, codes, etc.)
            $status = strtolower((string)($paymentData['status'] ?? ''));
            $isSuccess = in_array($status, ['success', 'completed', 'accepted'], true)
                || ($paymentResponse->getStatusCode() >= 200 && $paymentResponse->getStatusCode() < 300);

            return [
                'success' => $isSuccess,
                'raw_response' => $paymentData,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'raw_response' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
}


