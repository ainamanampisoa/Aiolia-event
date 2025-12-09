<?php

namespace App\Controller;

use App\Service\MvolaPaymentClient;
use App\Service\PaymentService;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Contrôleur pour gérer les callbacks et webhooks MVola.
 */
class MvolaController extends AbstractController
{
    public function __construct(
        private readonly Connection $connection,
        private readonly MvolaPaymentClient $mvolaClient,
        private readonly PaymentService $paymentService,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Endpoint de callback MVola (appelé par MVola après une transaction).
     * 
     * Selon la documentation, MVola envoie une requête PUT avec les détails de la transaction.
     * 
     * @Route("/api/mvola/callback", name="mvola_callback", methods=["PUT", "POST"])
     */
    public function callback(Request $request): JsonResponse
    {
        $this->logger->info('MVola callback reçu', [
            'method' => $request->getMethod(),
            'headers' => $request->headers->all(),
        ]);

        try {
            // Récupérer les données du callback
            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                $this->logger->error('MVola callback: données JSON invalides');
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Données invalides',
                ], 400);
            }

            $this->logger->info('MVola callback data', ['data' => $data]);

            // Extraire les informations importantes
            $serverCorrelationId = $data['serverCorrelationId'] ?? null;
            $transactionStatus = $data['transactionStatus'] ?? null;
            $transactionReference = $data['transactionReference'] ?? null;

            if (!$serverCorrelationId) {
                $this->logger->error('MVola callback: serverCorrelationId manquant');
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'serverCorrelationId manquant',
                ], 400);
            }

            // Chercher la transaction dans la base de données
            $transaction = $this->findTransactionByCorrelationId($serverCorrelationId);

            if (!$transaction) {
                $this->logger->warning('MVola callback: transaction non trouvée', [
                    'serverCorrelationId' => $serverCorrelationId,
                ]);
                
                // Sauvegarder quand même le callback pour investigation
                $this->saveCallbackData($serverCorrelationId, $data);
                
                return new JsonResponse([
                    'status' => 'received',
                    'message' => 'Callback reçu mais transaction non trouvée',
                ], 200);
            }

            // Mettre à jour le statut de la transaction
            $this->updateTransactionStatus(
                (int) $transaction['id'],
                $transactionStatus,
                $data
            );

            // Si le paiement est réussi, mettre à jour la commande
            if ($transactionStatus === 'completed') {
                $this->handleSuccessfulPayment((int) $transaction['order_id'], $data);
            } elseif ($transactionStatus === 'failed') {
                $this->handleFailedPayment((int) $transaction['order_id'], $data);
            }

            $this->logger->info('MVola callback traité avec succès', [
                'serverCorrelationId' => $serverCorrelationId,
                'status' => $transactionStatus,
            ]);

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Callback traité',
            ], 200);

        } catch (\Throwable $e) {
            $this->logger->error('Erreur lors du traitement du callback MVola', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new JsonResponse([
                'status' => 'error',
                'message' => 'Erreur lors du traitement',
            ], 500);
        }
    }

    /**
     * Endpoint de webhook MVola (alternative au callback).
     * 
     * @Route("/api/mvola/webhook", name="mvola_webhook", methods=["PUT", "POST"])
     */
    public function webhook(Request $request): JsonResponse
    {
        // Pour l'instant, rediriger vers le callback
        return $this->callback($request);
    }

    /**
     * Vérifie le statut d'une transaction (polling).
     * 
     * @Route("/api/mvola/status/{serverCorrelationId}", name="mvola_check_status", methods=["GET"])
     */
    public function checkStatus(string $serverCorrelationId): JsonResponse
    {
        try {
            $result = $this->mvolaClient->getTransactionStatus($serverCorrelationId);

            if (!$result['success']) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => $result['error'] ?? 'Erreur lors de la vérification',
                ], 400);
            }

            return new JsonResponse([
                'status' => 'success',
                'data' => $result['transaction'] ?? null,
            ], 200);

        } catch (\Throwable $e) {
            $this->logger->error('Erreur lors de la vérification du statut', [
                'error' => $e->getMessage(),
            ]);

            return new JsonResponse([
                'status' => 'error',
                'message' => 'Erreur lors de la vérification',
            ], 500);
        }
    }

    /**
     * Trouve une transaction par serverCorrelationId.
     */
    private function findTransactionByCorrelationId(string $serverCorrelationId): ?array
    {
        try {
            $sql = 'SELECT * FROM aiolia.payment_transactions 
                    WHERE mvola_correlation_id = :correlation_id 
                    LIMIT 1';
            
            $result = $this->connection->executeQuery($sql, [
                'correlation_id' => $serverCorrelationId,
            ])->fetchAssociative();

            return $result ?: null;
        } catch (\Throwable $e) {
            $this->logger->error('Erreur lors de la recherche de transaction', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Sauvegarde les données de callback même si la transaction n'est pas trouvée.
     */
    private function saveCallbackData(string $serverCorrelationId, array $data): void
    {
        try {
            // Mapper le statut de MVola vers les valeurs valides de l'enum
            $status = $this->mapMvolaStatusToEnum($data['transactionStatus'] ?? 'initiated');
            
            $this->connection->insert('aiolia.payment_transactions', [
                'mvola_correlation_id' => $serverCorrelationId,
                'status' => $status, // Valeurs valides: 'initiated', 'processing', 'paid', 'failed', 'refunded'
                'callback_data' => json_encode($data),
                'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'updated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur lors de la sauvegarde du callback', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Mappe le statut MVola vers les valeurs valides de l'enum.
     */
    private function mapMvolaStatusToEnum(?string $mvolaStatus): string
    {
        // Valeurs valides de payment_status_enum: 'initiated', 'processing', 'paid', 'failed', 'refunded'
        $statusMap = [
            'completed' => 'paid',
            'success' => 'paid',
            'paid' => 'paid',
            'failed' => 'failed',
            'failure' => 'failed',
            'error' => 'failed',
            'processing' => 'processing',
            'pending' => 'processing',
            'initiated' => 'initiated',
            'refunded' => 'refunded',
        ];
        
        return $statusMap[strtolower($mvolaStatus ?? '')] ?? 'initiated';
    }

    /**
     * Met à jour le statut d'une transaction.
     */
    private function updateTransactionStatus(int $transactionId, ?string $status, array $callbackData): void
    {
        try {
            // Mapper le statut vers les valeurs valides de l'enum
            $mappedStatus = $this->mapMvolaStatusToEnum($status);
            
            $this->connection->update(
                'aiolia.payment_transactions',
                [
                    'status' => $mappedStatus, // Valeurs valides: 'initiated', 'processing', 'paid', 'failed', 'refunded'
                    'callback_data' => json_encode($callbackData),
                    'updated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                ],
                ['id' => $transactionId]
            );
        } catch (\Throwable $e) {
            $this->logger->error('Erreur lors de la mise à jour du statut', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Gère un paiement réussi.
     */
    private function handleSuccessfulPayment(int $orderId, array $callbackData): void
    {
        try {
            // Créer les tickets après confirmation du paiement
            $result = $this->paymentService->createTicketsAfterPayment($orderId);
            
            if ($result['success']) {
                $this->logger->info('Paiement réussi et tickets créés', [
                    'order_id' => $orderId,
                    'tickets_count' => count($result['tickets'] ?? []),
                ]);
            } else {
                $this->logger->error('Erreur lors de la création des tickets après paiement', [
                    'order_id' => $orderId,
                ]);
            }
        } catch (\Throwable $e) {
            $this->logger->error('Erreur lors du traitement du paiement réussi', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Gère un paiement échoué.
     */
    private function handleFailedPayment(int $orderId, array $callbackData): void
    {
        try {
            // Mettre à jour le statut de la commande
            $this->connection->update(
                'aiolia.orders',
                [
                    'status' => 'payment_failed',
                    'updated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                ],
                ['id' => $orderId]
            );

            $this->logger->info('Paiement échoué traité', ['order_id' => $orderId]);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur lors du traitement du paiement échoué', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}

