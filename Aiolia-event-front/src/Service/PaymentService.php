<?php

namespace App\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

class PaymentService
{
    public function __construct(
        private readonly Connection $connection,
        private readonly CartSyncService $cartSyncService,
        private readonly ?MvolaPaymentClient $mvolaClient = null
    ) {
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
        error_log('PaymentService::processPayment - Début');
        error_log('User ID: ' . ($userId ?? 'null'));
        error_log('Cart items count: ' . count($cartItems));
        
        // Calculer le total de la commande (commun aux deux modes : simulé et réel)
        $totalAmount = 0;
        foreach ($cartItems as $item) {
            $adultTotal = ($item['adultQuantity'] ?? 0) * ($item['adultPrice'] ?? 0);
            $childTotal = ($item['childQuantity'] ?? 0) * ($item['childPrice'] ?? 0);
            $totalAmount += $adultTotal + $childTotal;
        }
        
        error_log('Total calculé: ' . $totalAmount);

        // MODE SIMULATION (utilisateur non connecté) :
        // On ne touche pas à la base de données, on renvoie juste un résultat "succès"
        if (null === $userId) {
            error_log('Mode simulation - utilisateur non connecté');
            $result = [
                'success' => true,
                // Identifiant de commande simulé
                'order_id' => random_int(100000, 999999),
                // Pas de tickets réellement créés en base dans ce mode
                'tickets' => [],
                'total_amount' => $totalAmount,
            ];
            error_log('Résultat simulation: ' . json_encode($result));
            return $result;
        }
        
        error_log('Mode complet - utilisateur connecté');

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
                $mvolaResult = $this->initiateMvolaPayment($orderId, $totalAmount, $paymentData);
                
                if (!$mvolaResult['success']) {
                    throw new \RuntimeException('Erreur lors de l\'initiation du paiement MVola: ' . ($mvolaResult['error'] ?? 'Erreur inconnue'));
                }
                
                // Retirer immédiatement les items payés du panier (avant le callback)
                // Cela évite qu'ils réapparaissent si l'utilisateur revient sur la page du panier
                $this->removeCartItems($cartId, $cartKeys);
                
                // La commande reste en "awaiting_payment" jusqu'à confirmation du callback
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
                if (!isset($item['adultTicketTypeId']) && !isset($item['childTicketTypeId']) 
                    && isset($item['ticketTypeId']) 
                    && ($adultQuantity > 0 || $childQuantity > 0)) {
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
            
            return [
                'success' => true,
                'order_id' => $orderId,
                'tickets' => $tickets,
                'total_amount' => $totalAmount,
            ];
        } catch (Exception $e) {
            $this->connection->rollBack();
            error_log('Erreur lors du traitement du paiement: ' . $e->getMessage());
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
            error_log('Erreur lors de la récupération du prix du type de billet: ' . $e->getMessage());
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
            // Générer une référence unique pour la transaction
            $transactionReference = 'ORDER-' . $orderId . '-' . time();
            
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

            // Initier la transaction MVola
            $result = $this->mvolaClient->initiateTransaction(
                $amount,
                $customerMsisdn,
                $transactionReference,
                'Paiement de billets - Commande #' . $orderId
            );

            if (!$result['success']) {
                return [
                    'success' => false,
                    'error' => $result['error'] ?? 'Erreur lors de l\'initiation de la transaction MVola',
                ];
            }

            // Sauvegarder la transaction dans la base de données
            $serverCorrelationId = $result['serverCorrelationId'] ?? null;
            if ($serverCorrelationId) {
                $this->connection->insert('aiolia.payment_transactions', [
                    'order_id' => $orderId,
                    'mvola_correlation_id' => $serverCorrelationId,
                    'mvola_transaction_id' => $result['raw_response']['transactionReference'] ?? null,
                    'transaction_reference' => $transactionReference,
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

            return [
                'success' => true,
                'serverCorrelationId' => $serverCorrelationId,
            ];
        } catch (\Throwable $e) {
            $errorMessage = 'Erreur lors de l\'initiation du paiement MVola: ' . $e->getMessage();
            error_log($errorMessage);
            error_log('Stack trace: ' . $e->getTraceAsString());
            
            // Écrire aussi dans un fichier dédié
            $logFile = sys_get_temp_dir() . '/mvola_debug.log';
            $logContent = date('Y-m-d H:i:s') . " - ERREUR MVola:\n";
            $logContent .= "Message: " . $errorMessage . "\n";
            $logContent .= "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
            $logContent .= "Stack trace: " . $e->getTraceAsString() . "\n";
            $logContent .= str_repeat('=', 80) . "\n";
            @file_put_contents($logFile, $logContent, FILE_APPEND);
            
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
            
            // Récupérer les items du panier depuis la commande
            $cartId = (int) $order['cart_id'];
            $dbCartItems = $this->cartSyncService->getCartItems($cartId);
            
            if (empty($dbCartItems)) {
                throw new \RuntimeException("Aucun item trouvé pour la commande: {$orderId}");
            }

            $orderItems = [];
            $tickets = [];

            // Récupérer les cart_keys des items payés depuis les notes de la commande
            $orderNotes = json_decode($order['notes'] ?? '{}', true);
            $paidCartKeys = $orderNotes['cart_keys'] ?? [];
            
            // Si on a des cart_keys spécifiques, ne traiter que ces items
            // Sinon, traiter tous les items du panier (comportement par défaut)
            if (!empty($paidCartKeys)) {
                // Filtrer les items pour ne garder que ceux qui ont été payés
                $dbCartItems = array_filter($dbCartItems, function($item) use ($paidCartKeys) {
                    return in_array($item['cart_key'] ?? '', $paidCartKeys);
                });
            }
            
            // Convertir les items du panier au format attendu
            $formattedCartItems = $this->cartSyncService->convertDbItemsToSessionFormat($dbCartItems);

            foreach ($formattedCartItems as $item) {
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

                // Traiter les billets adultes
                if ($adultQuantity > 0 && $adultTicketTypeId) {
                    $orderItemId = $this->createOrderItem($orderId, $adultTicketTypeId, $adultQuantity, $adultPrice);
                    $orderItems[] = $orderItemId;
                    
                    for ($i = 0; $i < $adultQuantity; $i++) {
                        $ticketId = $this->createTicket($orderItemId, $adultTicketTypeId, $userId, $adultPrice);
                        $tickets[] = $ticketId;
                    }
                }
                
                // Traiter les billets enfants
                if ($childQuantity > 0 && $childTicketTypeId) {
                    $orderItemId = $this->createOrderItem($orderId, $childTicketTypeId, $childQuantity, $childPrice);
                    $orderItems[] = $orderItemId;
                    
                    for ($i = 0; $i < $childQuantity; $i++) {
                        $ticketId = $this->createTicket($orderItemId, $childTicketTypeId, $userId, $childPrice);
                        $tickets[] = $ticketId;
                    }
                }
            }

            // Retirer uniquement les items payés du panier
            // Utiliser les cart_keys sauvegardés dans la commande si disponibles
            $cartKeysToRemove = !empty($paidCartKeys) ? $paidCartKeys : array_keys($formattedCartItems);
            
            // Vérifier que les items existent avant de les retirer
            // Note: Les items peuvent déjà avoir été retirés lors de l'initiation du paiement MVola
            if (!empty($cartKeysToRemove)) {
                $this->removeCartItems($cartId, $cartKeysToRemove);
            }
            
            // Vérifier qu'il ne reste plus d'items dans le panier
            $remainingItems = $this->cartSyncService->getCartItems($cartId);
            
            // Si le panier est vide ou ne contient que les items payés, le marquer comme converti
            // Sinon, le laisser actif pour les autres items
            if (empty($remainingItems)) {
                // Marquer le panier comme converti seulement s'il est vide
                $this->connection->update(
                    'aiolia.carts',
                    [
                        'status' => 'converted',
                        'updated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                    ],
                    ['id' => $cartId]
                );
            }
            
            // Vider aussi le panier de la session si l'utilisateur est connecté
            // Note: On ne peut pas accéder directement à la session ici, mais le panier DB est vidé
            // Le contrôleur videra la session lors de la confirmation

            $this->connection->commit();

            return [
                'success' => true,
                'tickets' => $tickets,
                'order_items' => $orderItems,
            ];
        } catch (\Throwable $e) {
            $this->connection->rollBack();
            error_log('Erreur lors de la création des tickets après paiement: ' . $e->getMessage());
            throw $e;
        }
    }
}

