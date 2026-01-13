<?php

namespace App\Service;

use App\Service\Notification\UserMailer;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PaymentService
{
    private string $logFile;

    public function __construct(
        private readonly Connection $connection,
        private readonly CartSyncService $cartSyncService,
        private readonly ?MvolaPaymentClient $mvolaClient = null,
        private readonly ?UserMailer $userMailer = null,
        ?ParameterBagInterface $parameterBag = null
    ) {
        // Définir le chemin du fichier de log
        $projectDir = $parameterBag?->get('kernel.project_dir') ?? dirname(__DIR__, 2);
        $logDir = $projectDir . '/var/log';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $this->logFile = $logDir . '/mvola.log';

        // Log de démarrage
        $this->logInfo('PaymentService initialisé', ['log_file' => $this->logFile]);
    }

    /**
     * Écrit un message dans le fichier de log Mvola.
     */
    private function writeLog(string $level, string $message, array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $logLine = "[{$timestamp}] [{$level}] {$message}{$contextStr}\n";

        @file_put_contents($this->logFile, $logLine, FILE_APPEND);

        // Également écrire dans error_log pour compatibilité
        error_log("MVola [{$level}]: {$message}");
    }

    /**
     * Écrit un message d'info dans le log.
     */
    private function logInfo(string $message, array $context = []): void
    {
        $this->writeLog('INFO', $message, $context);
    }

    /**
     * Écrit un message d'erreur dans le log.
     */
    private function logError(string $message, array $context = []): void
    {
        $this->writeLog('ERROR', $message, $context);
    }

    /**
     * Écrit un message de debug dans le log.
     */
    private function logDebug(string $message, array $context = []): void
    {
        $this->writeLog('DEBUG', $message, $context);
    }

    /**
     * Traite un paiement et crée les tickets associés.
     *
     * @param int|null $userId ID de l'utilisateur (null si non connecté)
     * @param array<string, array<string, mixed>> $cartItems Items du panier à payer
     * @param array<string, mixed> $paymentData Données de paiement (méthode, email, téléphone, etc.)
     * @return array<string, mixed> Résultat du paiement avec order_id et tickets créés
     * @throws Exception En cas d'erreur lors du traitement
     */
    public function processPayment(?int $userId, array $cartItems, array $paymentData): array
    {
        $this->logDebug('Début du traitement du paiement', [
            'user_id' => $userId,
            'cart_items_count' => count($cartItems),
            'payment_method' => $paymentData['payment_method'] ?? 'unknown'
        ]);

        // Calculer le total de la commande (commun aux deux modes : simulé et réel)
        $totalAmount = 0;
        foreach ($cartItems as $item) {
            $adultTotal = ($item['adultQuantity'] ?? 0) * ($item['adultPrice'] ?? 0);
            $childTotal = ($item['childQuantity'] ?? 0) * ($item['childPrice'] ?? 0);
            $totalAmount += $adultTotal + $childTotal;
        }

        $this->logDebug('Total calculé', ['total_amount' => $totalAmount]);

        // MODE SIMULATION (utilisateur non connecté) :
        // On ne touche pas à la base de données, on renvoie juste un résultat "succès"
        if (null === $userId) {
            $this->logInfo('Mode simulation - utilisateur non connecté');
            $result = [
                'success' => true,
                // Identifiant de commande simulé
                'order_id' => random_int(100000, 999999),
                // Pas de tickets réellement créés en base dans ce mode
                'tickets' => [],
                'total_amount' => $totalAmount,
            ];
            $this->logInfo('Résultat simulation', $result);
            return $result;
        }

        $this->logInfo('Mode complet - utilisateur connecté');

        // MODE COMPLET (utilisateur connecté) :
        // On persiste la commande et les tickets en base
        $this->connection->beginTransaction();

        try {
            // Récupérer ou créer le panier
            $dbCart = $this->cartSyncService->getOrCreateCart($userId, null);
            if (!$dbCart) {
                throw new \RuntimeException('Impossible de récupérer le panier.');
            }

            $cartId = (int) $dbCart['id'];

            // Sauvegarder les cart_keys des items payés pour pouvoir les retirer après paiement
            $cartKeys = array_keys($cartItems);

            // Créer la commande
            $orderId = $this->createOrder($userId, $cartId, $totalAmount, $paymentData, $cartKeys);

            // Si méthode de paiement MVola, initier la transaction
            $paymentMethod = $paymentData['payment_method'] ?? 'mvola';
            if ($paymentMethod === 'mvola' && $this->mvolaClient !== null) {
                // Sauvegarder les items du panier dans les notes de la commande AVANT de les retirer
                // Cela permet de les récupérer lors du callback même si le panier est vide
                $dbCartItems = $this->cartSyncService->getCartItems($cartId);
                $cartItemsData = [];
                foreach ($dbCartItems as $item) {
                    if (in_array($item['cartKey'] ?? '', $cartKeys)) {
                        $cartItemsData[] = [
                            'cart_key' => $item['cartKey'] ?? '',
                            'event_id' => $item['eventId'] ?? null,
                            'ticket_type_id' => $item['ticketTypeId'] ?? null,
                            'adult_ticket_type_id' => $item['adultTicketTypeId'] ?? null,
                            'child_ticket_type_id' => $item['childTicketTypeId'] ?? null,
                            'quantity' => $item['quantity'] ?? 0,
                            'adult_quantity' => $item['adultQuantity'] ?? 0,
                            'child_quantity' => $item['childQuantity'] ?? 0,
                            'unit_price' => $item['unitPrice'] ?? null,
                            'adult_price' => $item['adultPrice'] ?? null,
                            'child_price' => $item['childPrice'] ?? null,
                        ];
                    }
                }

                $this->logDebug('Items sauvegardés dans les notes de la commande', [
                    'order_id' => $orderId,
                    'cart_items_data_count' => count($cartItemsData),
                    'cart_items_data' => $cartItemsData
                ]);

                // Mettre à jour les notes de la commande avec les items sauvegardés
                $orderNotes = json_decode($this->connection->executeQuery(
                    'SELECT notes FROM aiolia.orders WHERE id = :order_id',
                    ['order_id' => $orderId]
                )->fetchOne(), true) ?: [];

                $orderNotes['cart_items_data'] = $cartItemsData;

                $this->connection->update(
                    'aiolia.orders',
                    [
                        'notes' => json_encode($orderNotes),
                    ],
                    ['id' => $orderId]
                );

                $mvolaResult = $this->initiateMvolaPayment($orderId, $totalAmount, $paymentData);

                if (!$mvolaResult['success']) {
                    throw new \RuntimeException('Erreur lors de l\'initiation du paiement MVola: ' . ($mvolaResult['error'] ?? 'Erreur inconnue'));
                }

                // Retirer immédiatement les items payés du panier (avant le callback)
                // Cela évite qu'ils réapparaissent si l'utilisateur revient sur la page du panier
                $this->removeCartItems($cartId, $cartKeys);

                // La commande reste en "pending" jusqu'à confirmation du callback
                $this->connection->commit();

                return [
                    'success' => true,
                    'order_id' => $orderId,
                    'tickets' => [], // Les tickets seront créés après confirmation du paiement
                    'total_amount' => $totalAmount,
                    'payment_status' => 'pending',
                    'mvola_correlation_id' => $mvolaResult['serverCorrelationId'] ?? null,
                    'cart_keys_removed' => $cartKeys, // Retourner les cart_keys retirés
                ];
            }

            // Pour les autres méthodes de paiement ou si MVola n'est pas configuré, créer directement les tickets
            // Créer les order_items et les tickets
            $orderItems = [];
            $tickets = [];

            foreach ($cartItems as $cartKey => $item) {
                $eventId = (int) $item['eventId'];
                $adultQuantity = (int) ($item['adultQuantity'] ?? 0);
                $childQuantity = (int) ($item['childQuantity'] ?? 0);
                $adultTicketTypeId = isset($item['adultTicketTypeId']) && $item['adultTicketTypeId'] > 0
                    ? (int) $item['adultTicketTypeId']
                    : null;
                $childTicketTypeId = isset($item['childTicketTypeId']) && $item['childTicketTypeId'] > 0
                    ? (int) $item['childTicketTypeId']
                    : null;
                $adultPrice = (float) ($item['adultPrice'] ?? 0);
                $childPrice = (float) ($item['childPrice'] ?? 0);

                // Récupérer les prix depuis la base de données si nécessaire
                if ($adultQuantity > 0 && $adultTicketTypeId && $adultPrice === 0) {
                    $adultPrice = $this->getTicketTypePrice($adultTicketTypeId) ?? 0;
                }
                if ($childQuantity > 0 && $childTicketTypeId && $childPrice === 0) {
                    $childPrice = $this->getTicketTypePrice($childTicketTypeId) ?? 0;
                }

                // Traiter les billets adultes
                if ($adultQuantity > 0 && $adultTicketTypeId) {
                    $orderItemId = $this->createOrderItem(
                        $orderId,
                        $adultTicketTypeId,
                        $adultQuantity,
                        $adultPrice
                    );
                    $orderItems[] = $orderItemId;

                    // Créer les tickets adultes
                    for ($i = 0; $i < $adultQuantity; $i++) {
                        $ticketId = $this->createTicket($orderItemId, $adultTicketTypeId, $userId, $adultPrice);
                        $tickets[] = $ticketId;
                    }
                }

                // Traiter les billets enfants
                if ($childQuantity > 0 && $childTicketTypeId) {
                    $orderItemId = $this->createOrderItem(
                        $orderId,
                        $childTicketTypeId,
                        $childQuantity,
                        $childPrice
                    );
                    $orderItems[] = $orderItemId;

                    // Créer les tickets enfants
                    for ($i = 0; $i < $childQuantity; $i++) {
                        $ticketId = $this->createTicket($orderItemId, $childTicketTypeId, $userId, $childPrice);
                        $tickets[] = $ticketId;
                    }
                }

                // Si on a un ticket_type_id principal mais pas de adult/child séparés
                // et qu'on n'a pas encore traité de billets pour cet item
                if (
                    !isset($item['adultTicketTypeId']) && !isset($item['childTicketTypeId'])
                    && isset($item['ticketTypeId'])
                    && ($adultQuantity > 0 || $childQuantity > 0)
                ) {
                    $ticketTypeId = (int) $item['ticketTypeId'];
                    $quantity = $adultQuantity + $childQuantity;
                    $price = $adultPrice > 0 ? $adultPrice : ($childPrice > 0 ? $childPrice : 0);

                    // Si le prix n'est pas disponible, le récupérer depuis la base de données
                    if ($price === 0) {
                        $price = $this->getTicketTypePrice($ticketTypeId) ?? 0;
                    }

                    if ($price > 0 && $quantity > 0) {
                        $orderItemId = $this->createOrderItem(
                            $orderId,
                            $ticketTypeId,
                            $quantity,
                            $price
                        );
                        $orderItems[] = $orderItemId;

                        // Créer les tickets
                        for ($i = 0; $i < $quantity; $i++) {
                            $ticketId = $this->createTicket($orderItemId, $ticketTypeId, $userId, $price);
                            $tickets[] = $ticketId;
                        }
                    }
                }
            }

            // Mettre à jour le statut de la commande à "paid"
            $this->connection->update(
                'aiolia.orders',
                [
                    'status' => 'paid',
                    'updated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                ],
                ['id' => $orderId]
            );

            // Retirer les items du panier
            $this->removeCartItems($cartId, array_keys($cartItems));

            // Marquer le panier comme converti
            $this->connection->update(
                'aiolia.carts',
                [
                    'status' => 'converted',
                    'updated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                ],
                ['id' => $cartId]
            );

            $this->connection->commit();

            // Envoyer l'email de confirmation
            if ($this->userMailer) {
                $order = $this->connection->executeQuery(
                    'SELECT * FROM aiolia.orders WHERE id = :order_id',
                    ['order_id' => $orderId]
                )->fetchAssociative();
                if ($order) {
                    $this->userMailer->sendPaymentConfirmation($order);
                }
            }

            return [
                'success' => true,
                'order_id' => $orderId,
                'tickets' => $tickets,
                'total_amount' => $totalAmount,
            ];
        } catch (Exception $e) {
            $this->connection->rollBack();
            $this->logError('Erreur lors du traitement du paiement', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Crée une commande dans la base de données.
     */
    private function createOrder(?int $userId, int $cartId, float $totalAmount, array $paymentData, array $cartKeys = []): int
    {
        $paymentMethod = $paymentData['payment_method'] ?? 'mvola';
        $paymentDueAt = new \DateTimeImmutable('+15 minutes');

        $this->connection->insert('aiolia.orders', [
            'user_id' => $userId,
            'cart_id' => $cartId,
            // Le statut "awaiting_payment" n'existe plus dans l'ENUM,
            // on utilise "pending" pour représenter une commande en attente de paiement.
            'status' => 'pending',
            'total_amount' => $totalAmount,
            'discount_amount' => 0,
            'currency' => 'MGA',
            'payment_due_at' => $paymentDueAt->format('Y-m-d H:i:s'),
            'notes' => json_encode([
                'payment_method' => $paymentMethod,
                'payment_email' => $paymentData['payment_email'] ?? null,
                'payment_phone' => $paymentData['payment_phone'] ?? null,
                'payment_name' => $paymentData['payment_name'] ?? null,
                'cart_keys' => $cartKeys, // Sauvegarder les cart_keys des items payés
            ]),
        ]);

        return (int) $this->connection->lastInsertId();
    }

    /**
     * Crée un order_item dans la base de données.
     */
    private function createOrderItem(int $orderId, int $ticketTypeId, int $quantity, float $unitPrice): int
    {
        $totalAmount = $quantity * $unitPrice;

        $this->connection->insert('aiolia.order_items', [
            'order_id' => $orderId,
            'ticket_type_id' => $ticketTypeId,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'service_fee' => 0,
            'vat_amount' => 0,
            'total_amount' => $totalAmount,
        ]);

        return (int) $this->connection->lastInsertId();
    }

    /**
     * Crée un ticket dans la base de données.
     */
    private function createTicket(int $orderItemId, int $ticketTypeId, ?int $userId, float $price): int
    {
        // Générer un QR code unique
        $qrCode = 'TICKET_' . uniqid() . '_' . bin2hex(random_bytes(8));

        // Récupérer l'event_id depuis le ticket_type
        $eventSql = 'SELECT event_id FROM aiolia.ticket_types WHERE id = :ticket_type_id LIMIT 1';
        $eventResult = $this->connection->executeQuery($eventSql, ['ticket_type_id' => $ticketTypeId])->fetchAssociative();

        if (!$eventResult || !isset($eventResult['event_id'])) {
            throw new \RuntimeException("Impossible de trouver l'événement pour le ticket_type_id: {$ticketTypeId}");
        }

        $eventId = (int) $eventResult['event_id'];

        // Insérer le ticket
        $this->connection->insert('aiolia.tickets', [
            'order_item_id' => $orderItemId,
            'ticket_type_id' => $ticketTypeId,
            'owner_user_id' => $userId,
            'status' => 'valid',
            'qr_code' => $qrCode,
            'issued_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);

        $ticketId = (int) $this->connection->lastInsertId();

        // Mettre à jour l'inventaire des tickets
        $this->updateTicketInventory($ticketTypeId);

        return $ticketId;
    }

    /**
     * Met à jour l'inventaire des tickets après la vente.
     */
    private function updateTicketInventory(int $ticketTypeId): void
    {
        // Vérifier si l'inventaire existe
        $inventorySql = 'SELECT * FROM aiolia.ticket_inventory WHERE ticket_type_id = :ticket_type_id';
        $inventory = $this->connection->executeQuery($inventorySql, ['ticket_type_id' => $ticketTypeId])->fetchAssociative();

        if ($inventory) {
            // Utiliser une requête SQL directe pour l'incrémentation
            $updateSql = 'UPDATE aiolia.ticket_inventory SET sold_quantity = sold_quantity + 1, updated_at = NOW() WHERE ticket_type_id = :ticket_type_id';
            $this->connection->executeStatement($updateSql, ['ticket_type_id' => $ticketTypeId]);
        } else {
            // Créer l'inventaire si il n'existe pas
            $this->connection->insert('aiolia.ticket_inventory', [
                'ticket_type_id' => $ticketTypeId,
                'total_quantity' => 0,
                'reserved_quantity' => 0,
                'sold_quantity' => 1,
            ]);
        }
    }

    /**
     * Retire les items du panier après paiement.
     */
    private function removeCartItems(int $cartId, array $cartKeys): void
    {
        if (empty($cartKeys)) {
            return;
        }

        // Construire la clause IN pour supprimer plusieurs items
        $placeholders = [];
        $params = ['cart_id' => $cartId];

        foreach ($cartKeys as $index => $cartKey) {
            $placeholder = 'cart_key_' . $index;
            $placeholders[] = ':' . $placeholder;
            $params[$placeholder] = $cartKey;
        }

        $sql = 'DELETE FROM aiolia.cart_items WHERE cart_id = :cart_id AND cart_key IN (' . implode(', ', $placeholders) . ')';
        $this->connection->executeStatement($sql, $params);

        // Recalculer le total du panier
        $this->recalculateCartTotal($cartId);
    }

    /**
     * Recalcule le total du panier.
     */
    private function recalculateCartTotal(int $cartId): void
    {
        $sql = 'SELECT SUM(total_price) as total FROM aiolia.cart_items WHERE cart_id = :cart_id';
        $result = $this->connection->executeQuery($sql, ['cart_id' => $cartId])->fetchAssociative();

        $total = $result ? (float) ($result['total'] ?? 0) : 0;

        $this->connection->update(
            'aiolia.carts',
            [
                'total_amount' => $total,
                'updated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ],
            ['id' => $cartId]
        );
    }

    /**
     * Récupère le prix d'un type de billet depuis la base de données.
     */
    private function getTicketTypePrice(int $ticketTypeId): ?float
    {
        try {
            $sql = 'SELECT base_price FROM aiolia.ticket_types WHERE id = :ticket_type_id LIMIT 1';
            $result = $this->connection->executeQuery($sql, ['ticket_type_id' => $ticketTypeId])->fetchAssociative();

            if ($result && isset($result['base_price'])) {
                return (float) $result['base_price'];
            }

            return null;
        } catch (Exception $e) {
            $this->logError('Erreur lors de la récupération du prix du type de billet', [
                'ticket_type_id' => $ticketTypeId,
                'message' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Initie un paiement MVola et sauvegarde la transaction.
     * 
     * @return array{success: bool, serverCorrelationId?: string, error?: string}
     */
    private function initiateMvolaPayment(int $orderId, float $amount, array $paymentData): array
    {
        if ($this->mvolaClient === null) {
            return [
                'success' => false,
                'error' => 'Service MVola non configuré',
            ];
        }

        try {
            // Générer une référence de base pour la transaction
            // La fonction initiateTransactionWithRetry générera des références uniques à chaque retry
            $baseTransactionReference = 'ORDER-' . $orderId;

            // Récupérer le numéro de téléphone du client
            $customerMsisdn = trim($paymentData['payment_phone'] ?? '');
            if (empty($customerMsisdn)) {
                return [
                    'success' => false,
                    'error' => 'Numéro de téléphone MVola requis',
                ];
            }

            // Valider que le montant est positif
            if ($amount <= 0) {
                return [
                    'success' => false,
                    'error' => 'Le montant doit être supérieur à zéro',
                ];
            }

            // Initier la transaction MVola avec retry automatique pour gérer l'erreur 4002
            $result = $this->mvolaClient->initiateTransactionWithRetry(
                $amount,
                $customerMsisdn,
                $baseTransactionReference,
                'Paiement de billets - Commande #' . $orderId,
                3 // maxRetries = 3 tentatives
            );

            if (!$result['success']) {
                return [
                    'success' => false,
                    'error' => $result['error'] ?? 'Erreur lors de l\'initiation de la transaction MVola',
                ];
            }

            // Sauvegarder la transaction dans la base de données
            $serverCorrelationId = $result['serverCorrelationId'] ?? null;
            // Utiliser la référence retournée (qui peut être différente après un retry)
            $finalTransactionReference = $result['transactionReference'] ?? $baseTransactionReference;
            
            if ($serverCorrelationId) {
                $this->connection->insert('aiolia.payment_transactions', [
                    'order_id' => $orderId,
                    'mvola_correlation_id' => $serverCorrelationId,
                    'mvola_transaction_id' => $result['raw_response']['transactionReference'] ?? null,
                    'transaction_reference' => $finalTransactionReference,
                    'status' => 'initiated', // Valeurs valides: 'initiated', 'processing', 'paid', 'failed', 'refunded'
                    'amount' => $amount,
                    'currency' => 'MGA',
                    'customer_msisdn' => $customerMsisdn,
                    'payment_method' => 'mvola',
                    'callback_data' => json_encode($result['raw_response'] ?? []),
                    'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                    'updated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                ]);
            }

            // EN SANDBOX : Créer automatiquement les tickets après un court délai
            // Car le callback ne peut pas être appelé depuis localhost
            // En production, on attendra le callback réel
            $baseUrl = $this->mvolaClient ? $this->mvolaClient->getBaseUrl() : '';
            $isSandbox = !empty($baseUrl) && strpos($baseUrl, 'devapi.mvola.mg') !== false;

            $this->logDebug('Vérification mode sandbox', [
                'base_url' => $baseUrl,
                'is_sandbox' => $isSandbox,
                'server_correlation_id' => $serverCorrelationId,
                'mvola_client_null' => $this->mvolaClient === null
            ]);

            if ($isSandbox && $serverCorrelationId) {
                $this->logInfo('Mode sandbox détecté - Création automatique des tickets après délai', [
                    'order_id' => $orderId,
                    'server_correlation_id' => $serverCorrelationId
                ]);

                // Créer les tickets après 2 secondes (simule le délai du callback)
                // Utiliser un processus asynchrone ou un délai simple
                // Pour l'instant, on crée directement les tickets
                try {
                    // Attendre 1 seconde pour simuler le délai du callback
                    sleep(1);

                    // Créer les tickets automatiquement en sandbox
                    $ticketResult = $this->createTicketsAfterPayment($orderId);

                    if ($ticketResult['success']) {
                        $this->logInfo('Tickets créés automatiquement en sandbox', [
                            'order_id' => $orderId,
                            'tickets_count' => count($ticketResult['tickets'] ?? [])
                        ]);
                    } else {
                        $this->logError('Erreur lors de la création automatique des tickets en sandbox', [
                            'order_id' => $orderId,
                            'error' => $ticketResult['error'] ?? 'Erreur inconnue'
                        ]);
                    }
                } catch (\Throwable $e) {
                    $this->logError('Exception lors de la création automatique des tickets en sandbox', [
                        'order_id' => $orderId,
                        'message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            return [
                'success' => true,
                'serverCorrelationId' => $serverCorrelationId,
            ];
        } catch (\Throwable $e) {
            $this->logError('Erreur lors de l\'initiation du paiement MVola', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $errorMessage,
                'debug' => [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'log_file' => $logFile, // Indiquer où sont les logs
                ],
            ];
        }
    }

    /**
     * Crée les tickets après confirmation du paiement MVola.
     * Appelé depuis MvolaController après réception du callback de succès.
     */
    public function createTicketsAfterPayment(int $orderId): array
    {
        $this->connection->beginTransaction();

        try {
            // Récupérer la commande
            $order = $this->connection->executeQuery(
                'SELECT * FROM aiolia.orders WHERE id = :order_id',
                ['order_id' => $orderId]
            )->fetchAssociative();

            if (!$order) {
                throw new \RuntimeException("Commande non trouvée: {$orderId}");
            }

            $userId = (int) $order['user_id'];

            $orderItems = [];
            $tickets = [];

            // Récupérer les items sauvegardés depuis les notes de la commande
            $orderNotes = json_decode($order['notes'] ?? '{}', true);
            $savedCartItems = $orderNotes['cart_items_data'] ?? [];

            // Log pour debug
            $this->logDebug('Début de la création des tickets après paiement', [
                'order_id' => $orderId,
                'saved_cart_items_count' => count($savedCartItems),
                'order_notes' => $order['notes'] ?? null,
                'saved_cart_items' => $savedCartItems
            ]);

            // Si on a des items sauvegardés, les utiliser
            // Sinon, essayer de récupérer depuis le panier (pour compatibilité avec les anciennes commandes)
            if (!empty($savedCartItems)) {
                // Convertir les items sauvegardés au format attendu
                $formattedCartItems = [];
                foreach ($savedCartItems as $savedItem) {
                    $cartKey = $savedItem['cart_key'] ?? 'event_' . ($savedItem['event_id'] ?? '');
                    $adultTicketTypeId = $savedItem['adult_ticket_type_id'] ?? null;
                    $childTicketTypeId = $savedItem['child_ticket_type_id'] ?? null;
                    $ticketTypeId = $savedItem['ticket_type_id'] ?? null;

                    // Si on n'a pas de adult/child séparés mais qu'on a un ticket_type_id principal
                    // et des quantités, utiliser le ticket_type_id principal
                    if (!$adultTicketTypeId && !$childTicketTypeId && $ticketTypeId) {
                        $adultQuantity = (int) ($savedItem['adult_quantity'] ?? 0);
                        $childQuantity = (int) ($savedItem['child_quantity'] ?? 0);
                        $totalQuantity = $adultQuantity + $childQuantity;

                        if ($totalQuantity > 0) {
                            // Si on a des quantités adultes et enfants, créer deux entrées
                            if ($adultQuantity > 0 && $childQuantity > 0) {
                                // Créer une entrée pour les adultes
                                $formattedCartItems[$cartKey . '_adult'] = [
                                    'eventId' => $savedItem['event_id'] ?? null,
                                    'ticketTypeId' => $ticketTypeId,
                                    'adultTicketTypeId' => $ticketTypeId,
                                    'childTicketTypeId' => null,
                                    'adultQuantity' => $adultQuantity,
                                    'childQuantity' => 0,
                                    'adultPrice' => $savedItem['adult_price'] ?? $savedItem['price'] ?? null,
                                    'childPrice' => null,
                                ];
                                // Créer une entrée pour les enfants
                                $formattedCartItems[$cartKey . '_child'] = [
                                    'eventId' => $savedItem['event_id'] ?? null,
                                    'ticketTypeId' => $ticketTypeId,
                                    'adultTicketTypeId' => null,
                                    'childTicketTypeId' => $ticketTypeId,
                                    'adultQuantity' => 0,
                                    'childQuantity' => $childQuantity,
                                    'adultPrice' => null,
                                    'childPrice' => $savedItem['child_price'] ?? $savedItem['price'] ?? null,
                                ];
                            } else {
                                // Une seule quantité, utiliser adult ou child selon ce qui est disponible
                                $formattedCartItems[$cartKey] = [
                                    'eventId' => $savedItem['event_id'] ?? null,
                                    'ticketTypeId' => $ticketTypeId,
                                    'adultTicketTypeId' => $adultQuantity > 0 ? $ticketTypeId : null,
                                    'childTicketTypeId' => $childQuantity > 0 ? $ticketTypeId : null,
                                    'quantity' => (int) ($savedItem['quantity'] ?? $totalQuantity),
                                    'adultQuantity' => $adultQuantity,
                                    'childQuantity' => $childQuantity,
                                    'unitPrice' => isset($savedItem['unit_price']) ? (float) $savedItem['unit_price'] : null,
                                    'adultPrice' => isset($savedItem['adult_price']) ? (float) $savedItem['adult_price'] : null,
                                    'childPrice' => isset($savedItem['child_price']) ? (float) $savedItem['child_price'] : null,
                                ];
                            }
                        } else {
                            // Pas de quantités dans adult/child, utiliser la quantité principale
                            $quantity = (int) ($savedItem['quantity'] ?? 0);
                            if ($quantity > 0) {
                                $formattedCartItems[$cartKey] = [
                                    'eventId' => $savedItem['event_id'] ?? null,
                                    'ticketTypeId' => $ticketTypeId,
                                    'adultTicketTypeId' => null,
                                    'childTicketTypeId' => null,
                                    'quantity' => $quantity,
                                    'adultQuantity' => 0,
                                    'childQuantity' => 0,
                                    'unitPrice' => isset($savedItem['unit_price']) ? (float) $savedItem['unit_price'] : null,
                                    'adultPrice' => null,
                                    'childPrice' => null,
                                ];
                            }
                        }
                    } else {
                        // Format normal avec adult/child séparés
                        $formattedCartItems[$cartKey] = [
                            'eventId' => $savedItem['event_id'] ?? null,
                            'ticketTypeId' => $ticketTypeId,
                            'adultTicketTypeId' => $adultTicketTypeId,
                            'childTicketTypeId' => $childTicketTypeId,
                            'quantity' => (int) ($savedItem['quantity'] ?? 0),
                            'adultQuantity' => (int) ($savedItem['adult_quantity'] ?? 0),
                            'childQuantity' => (int) ($savedItem['child_quantity'] ?? 0),
                            'unitPrice' => isset($savedItem['unit_price']) ? (float) $savedItem['unit_price'] : null,
                            'adultPrice' => isset($savedItem['adult_price']) ? (float) $savedItem['adult_price'] : null,
                            'childPrice' => isset($savedItem['child_price']) ? (float) $savedItem['child_price'] : null,
                        ];
                    }
                }
            } else {
                // Fallback : récupérer depuis le panier (pour compatibilité)
                $cartId = (int) $order['cart_id'];
                $dbCartItems = $this->cartSyncService->getCartItems($cartId);

                if (empty($dbCartItems)) {
                    throw new \RuntimeException("Aucun item trouvé pour la commande: {$orderId}. Les items du panier ont peut-être été supprimés avant le callback.");
                }

                // Récupérer les cart_keys des items payés depuis les notes de la commande
                $paidCartKeys = $orderNotes['cart_keys'] ?? [];

                // Si on a des cart_keys spécifiques, ne traiter que ces items
                if (!empty($paidCartKeys)) {
                    // Filtrer les items pour ne garder que ceux qui ont été payés
                    $dbCartItems = array_filter($dbCartItems, function ($item) use ($paidCartKeys) {
                        return in_array($item['cart_key'] ?? '', $paidCartKeys);
                    });
                }

                // Convertir les items du panier au format attendu
                $formattedCartItems = $this->cartSyncService->convertDbItemsToSessionFormat($dbCartItems);
            }

            // Log pour debug
            $this->logDebug('Items formatés pour création des tickets', [
                'formatted_cart_items_count' => count($formattedCartItems),
                'formatted_cart_items' => $formattedCartItems
            ]);

            foreach ($formattedCartItems as $item) {
                $adultQuantity = (int) ($item['adultQuantity'] ?? 0);
                $childQuantity = (int) ($item['childQuantity'] ?? 0);
                $totalQuantity = (int) ($item['quantity'] ?? 0);
                $adultTicketTypeId = isset($item['adultTicketTypeId']) && $item['adultTicketTypeId'] > 0
                    ? (int) $item['adultTicketTypeId']
                    : null;
                $childTicketTypeId = isset($item['childTicketTypeId']) && $item['childTicketTypeId'] > 0
                    ? (int) $item['childTicketTypeId']
                    : null;
                $ticketTypeId = isset($item['ticketTypeId']) && $item['ticketTypeId'] > 0
                    ? (int) $item['ticketTypeId']
                    : null;
                $adultPrice = (float) ($item['adultPrice'] ?? 0);
                $childPrice = (float) ($item['childPrice'] ?? 0);
                $unitPrice = (float) ($item['unitPrice'] ?? 0);

                // Log pour debug
                $this->logDebug('Traitement d\'un item', [
                    'adult_quantity' => $adultQuantity,
                    'child_quantity' => $childQuantity,
                    'total_quantity' => $totalQuantity,
                    'adult_ticket_type_id' => $adultTicketTypeId,
                    'child_ticket_type_id' => $childTicketTypeId,
                    'ticket_type_id' => $ticketTypeId,
                    'adult_price' => $adultPrice,
                    'child_price' => $childPrice,
                    'unit_price' => $unitPrice
                ]);

                $itemProcessed = false;

                // Traiter les billets adultes
                if ($adultQuantity > 0 && $adultTicketTypeId) {
                    $price = $adultPrice > 0 ? $adultPrice : ($unitPrice > 0 ? $unitPrice : 0);
                    if ($price === 0) {
                        $price = $this->getTicketTypePrice($adultTicketTypeId) ?? 0;
                    }

                    if ($price > 0) {
                        $orderItemId = $this->createOrderItem($orderId, $adultTicketTypeId, $adultQuantity, $price);
                        $orderItems[] = $orderItemId;
                        $this->logInfo("Création de l'order_item pour billets adultes", [
                            'order_item_id' => $orderItemId,
                            'quantity' => $adultQuantity,
                            'ticket_type_id' => $adultTicketTypeId,
                            'price' => $price
                        ]);

                        for ($i = 0; $i < $adultQuantity; $i++) {
                            $ticketId = $this->createTicket($orderItemId, $adultTicketTypeId, $userId, $price);
                            $tickets[] = $ticketId;
                        }
                        $this->logInfo("Billets adultes créés", ['count' => $adultQuantity]);
                        $itemProcessed = true;
                    }
                }

                // Traiter les billets enfants
                if ($childQuantity > 0 && $childTicketTypeId) {
                    $price = $childPrice > 0 ? $childPrice : ($unitPrice > 0 ? $unitPrice : 0);
                    if ($price === 0) {
                        $price = $this->getTicketTypePrice($childTicketTypeId) ?? 0;
                    }

                    if ($price > 0) {
                        $orderItemId = $this->createOrderItem($orderId, $childTicketTypeId, $childQuantity, $price);
                        $orderItems[] = $orderItemId;
                        $this->logInfo("Création de l'order_item pour billets enfants", [
                            'order_item_id' => $orderItemId,
                            'quantity' => $childQuantity,
                            'ticket_type_id' => $childTicketTypeId,
                            'price' => $price
                        ]);

                        for ($i = 0; $i < $childQuantity; $i++) {
                            $ticketId = $this->createTicket($orderItemId, $childTicketTypeId, $userId, $price);
                            $tickets[] = $ticketId;
                        }
                        $this->logInfo("Billets enfants créés", ['count' => $childQuantity]);
                        $itemProcessed = true;
                    }
                }

                // Si on n'a pas encore traité cet item et qu'on a un ticket_type_id principal
                if (!$itemProcessed && $ticketTypeId) {
                    $quantity = $adultQuantity + $childQuantity;

                    // Si on n'a pas de quantités dans adult/child, utiliser la quantité principale
                    if ($quantity === 0 && $totalQuantity > 0) {
                        $quantity = $totalQuantity;
                    }

                    if ($quantity > 0) {
                        $price = $adultPrice > 0 ? $adultPrice : ($childPrice > 0 ? $childPrice : ($unitPrice > 0 ? $unitPrice : 0));

                        // Si le prix n'est pas disponible, le récupérer depuis la base de données
                        if ($price === 0) {
                            $price = $this->getTicketTypePrice($ticketTypeId) ?? 0;
                        }

                        if ($price > 0) {
                            $orderItemId = $this->createOrderItem($orderId, $ticketTypeId, $quantity, $price);
                            $orderItems[] = $orderItemId;
                            $this->logInfo("Création de l'order_item pour billets standards", [
                                'order_item_id' => $orderItemId,
                                'quantity' => $quantity,
                                'ticket_type_id' => $ticketTypeId,
                                'price' => $price
                            ]);

                            // Créer les tickets
                            for ($i = 0; $i < $quantity; $i++) {
                                $ticketId = $this->createTicket($orderItemId, $ticketTypeId, $userId, $price);
                                $tickets[] = $ticketId;
                            }
                            $this->logInfo("Billets standards créés", ['count' => $quantity]);
                            $itemProcessed = true;
                        } else {
                            $this->logError("Impossible de créer l'order_item - prix invalide", [
                                'ticket_type_id' => $ticketTypeId,
                                'quantity' => $quantity,
                                'price' => $price
                            ]);
                        }
                    }
                }

                // Si l'item n'a toujours pas été traité, c'est une erreur
                if (!$itemProcessed) {
                    $this->logError("Item non traité - données manquantes ou invalides", [
                        'item' => $item,
                        'adult_quantity' => $adultQuantity,
                        'child_quantity' => $childQuantity,
                        'total_quantity' => $totalQuantity,
                        'adult_ticket_type_id' => $adultTicketTypeId,
                        'child_ticket_type_id' => $childTicketTypeId,
                        'ticket_type_id' => $ticketTypeId
                    ]);
                }
            }

            // Log final
            $this->logInfo('Création des tickets terminée', [
                'total_order_items' => count($orderItems),
                'total_tickets' => count($tickets),
                'order_id' => $orderId
            ]);

            // Mettre à jour le statut de la commande à "paid"
            // IMPORTANT: Même si Mvola renvoie 'pending' en sandbox, pour nous c'est un paiement réussi
            // Donc on met toujours le statut à 'paid' quand le callback indique un succès
            $this->connection->update(
                'aiolia.orders',
                [
                    'status' => 'paid',
                    'updated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                ],
                ['id' => $orderId]
            );

            $this->logInfo('Statut de la commande mis à jour à paid', [
                'order_id' => $orderId,
                'total_order_items' => count($orderItems),
                'total_tickets' => count($tickets)
            ]);

            // Retirer uniquement les items payés du panier si le panier existe encore
            // Utiliser les cart_keys sauvegardés dans la commande si disponibles
            $paidCartKeys = $orderNotes['cart_keys'] ?? [];
            $cartKeysToRemove = !empty($paidCartKeys) ? $paidCartKeys : array_keys($formattedCartItems);

            // Vérifier que le panier existe et que les items existent avant de les retirer
            // Note: Les items peuvent déjà avoir été retirés lors de l'initiation du paiement MVola
            $cartId = (int) ($order['cart_id'] ?? 0);
            if ($cartId > 0 && !empty($cartKeysToRemove)) {
                try {
                    $this->removeCartItems($cartId, $cartKeysToRemove);
                } catch (\Exception $e) {
                    // Ignorer l'erreur si les items ont déjà été retirés
                    $this->logDebug('Impossible de retirer les items du panier (peut-être déjà retirés)', [
                        'message' => $e->getMessage()
                    ]);
                }

                // Vérifier qu'il ne reste plus d'items dans le panier
                $remainingItems = $this->cartSyncService->getCartItems($cartId);

                // Si le panier est vide, le marquer comme converti
                if (empty($remainingItems)) {
                    $this->connection->update(
                        'aiolia.carts',
                        [
                            'status' => 'converted',
                            'updated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                        ],
                        ['id' => $cartId]
                    );
                }
            }

            // Vider aussi le panier de la session si l'utilisateur est connecté
            // Note: On ne peut pas accéder directement à la session ici, mais le panier DB est vidé
            // Le contrôleur videra la session lors de la confirmation

            $this->connection->commit();

            // Envoyer l'email de confirmation
            if ($this->userMailer) {
                $this->userMailer->sendPaymentConfirmation($order);
            }

            return [
                'success' => true,
                'tickets' => $tickets,
                'order_items' => $orderItems,
            ];
        } catch (\Throwable $e) {
            $this->connection->rollBack();
            $this->logError('Erreur lors de la création des tickets après paiement', [
                'order_id' => $orderId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}

