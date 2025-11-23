<?php

namespace App\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Component\HttpFoundation\Request;

class CartSyncService
{
    public function __construct(
        private readonly Connection $connection
    ) {
    }

    /**
     * Récupère ou crée un panier pour un utilisateur ou une session.
     *
     * @param int|null $userId ID de l'utilisateur (null si non connecté)
     * @param string|null $sessionToken Token de session (pour utilisateurs non connectés)
     * @return array|null Retourne le panier avec ses items, ou null si erreur
     */
    public function getOrCreateCart(?int $userId = null, ?string $sessionToken = null): ?array
    {
        try {
            $sql = 'SELECT id, user_id, status, session_token, currency, total_amount, expires_at 
                    FROM aiolia.carts 
                    WHERE status = :status';
            
            $params = ['status' => 'active'];
            
            if ($userId) {
                $sql .= ' AND user_id = :user_id';
                $params['user_id'] = $userId;
            } elseif ($sessionToken) {
                $sql .= ' AND session_token = :session_token';
                $params['session_token'] = $sessionToken;
            } else {
                return null;
            }
            
            $sql .= ' ORDER BY updated_at DESC LIMIT 1';
            
            $cart = $this->connection->executeQuery($sql, $params)->fetchAssociative();
            
            if (false === $cart) {
                // Créer un nouveau panier
                $cart = $this->createCart($userId, $sessionToken);
            }
            
            if ($cart) {
                // Récupérer les items du panier
                $cart['items'] = $this->getCartItems((int) $cart['id']);
            }
            
            return $cart;
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération du panier: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Crée un nouveau panier.
     */
    private function createCart(?int $userId = null, ?string $sessionToken = null): ?array
    {
        try {
            $expiresAt = new \DateTimeImmutable('+30 days');
            
            $this->connection->insert('aiolia.carts', [
                'user_id' => $userId,
                'session_token' => $sessionToken,
                'status' => 'active',
                'currency' => 'MGA',
                'total_amount' => 0,
                'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
            ]);
            
            $cartId = (int) $this->connection->lastInsertId();
            
            return [
                'id' => $cartId,
                'user_id' => $userId,
                'status' => 'active',
                'session_token' => $sessionToken,
                'currency' => 'MGA',
                'total_amount' => 0,
                'expires_at' => $expiresAt,
                'items' => [],
            ];
        } catch (Exception $e) {
            error_log('Erreur lors de la création du panier: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère les items d'un panier.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCartItems(int $cartId): array
    {
        try {
            $sql = 'SELECT 
                        ci.id,
                        ci.cart_id,
                        ci.event_id,
                        ci.ticket_type_id,
                        ci.adult_ticket_type_id,
                        ci.child_ticket_type_id,
                        ci.quantity,
                        ci.adult_quantity,
                        ci.child_quantity,
                        ci.unit_price,
                        ci.adult_price,
                        ci.child_price,
                        ci.total_price,
                        ci.cart_key,
                        ci.created_at
                    FROM aiolia.cart_items ci
                    WHERE ci.cart_id = :cart_id
                    ORDER BY ci.created_at ASC';
            
            $items = $this->connection->executeQuery($sql, ['cart_id' => $cartId])->fetchAllAssociative();
            
            return array_map(function (array $item): array {
                $createdAt = isset($item['created_at']) 
                    ? (is_string($item['created_at']) ? $item['created_at'] : $item['created_at']->format('Y-m-d H:i:s'))
                    : null;
                
                return [
                    'id' => (int) $item['id'],
                    'cartId' => (int) $item['cart_id'],
                    'eventId' => isset($item['event_id']) ? (int) $item['event_id'] : null,
                    'ticketTypeId' => isset($item['ticket_type_id']) ? (int) $item['ticket_type_id'] : null,
                    'adultTicketTypeId' => isset($item['adult_ticket_type_id']) ? (int) $item['adult_ticket_type_id'] : null,
                    'childTicketTypeId' => isset($item['child_ticket_type_id']) ? (int) $item['child_ticket_type_id'] : null,
                    'quantity' => isset($item['quantity']) ? (int) $item['quantity'] : 0,
                    'adultQuantity' => isset($item['adult_quantity']) ? (int) $item['adult_quantity'] : 0,
                    'childQuantity' => isset($item['child_quantity']) ? (int) $item['child_quantity'] : 0,
                    'unitPrice' => isset($item['unit_price']) ? (float) $item['unit_price'] : 0,
                    'adultPrice' => isset($item['adult_price']) && $item['adult_price'] > 0 ? (float) $item['adult_price'] : null,
                    'childPrice' => isset($item['child_price']) && $item['child_price'] > 0 ? (float) $item['child_price'] : null,
                    'totalPrice' => isset($item['total_price']) ? (float) $item['total_price'] : 0,
                    'cartKey' => $item['cart_key'] ?? null,
                    'createdAt' => $createdAt,
                ];
            }, $items);
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des items du panier: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Sauvegarde les items du panier dans la base de données.
     *
     * @param int $cartId ID du panier
     * @param array<string, array<string, mixed>> $cartItems Items du panier (format session)
     * @return bool True si succès, false sinon
     */
    public function saveCartItems(int $cartId, array $cartItems): bool
    {
        try {
            // Commencer une transaction
            $this->connection->beginTransaction();
            
            try {
                // Supprimer les anciens items du panier
                $this->connection->delete('aiolia.cart_items', ['cart_id' => $cartId]);
                
                // Insérer les nouveaux items
                $totalAmount = 0;
                foreach ($cartItems as $cartKey => $item) {
                    // Déterminer le ticket_type_id principal (pour compatibilité)
                    $ticketTypeId = $item['ticketTypeId'] ?? null;
                    if (!$ticketTypeId && isset($item['adultTicketTypeId'])) {
                        $ticketTypeId = $item['adultTicketTypeId'];
                    } elseif (!$ticketTypeId && isset($item['childTicketTypeId'])) {
                        $ticketTypeId = $item['childTicketTypeId'];
                    }
                    
                    // Récupérer event_id depuis ticket_type si nécessaire
                    $eventId = $item['eventId'] ?? null;
                    if (!$eventId && $ticketTypeId) {
                        $eventSql = 'SELECT event_id FROM aiolia.ticket_types WHERE id = :ticket_type_id LIMIT 1';
                        $eventResult = $this->connection->executeQuery($eventSql, ['ticket_type_id' => $ticketTypeId])->fetchAssociative();
                        if ($eventResult) {
                            $eventId = (int) $eventResult['event_id'];
                        }
                    }
                    
                    if (!$eventId || !$ticketTypeId) {
                        continue; // Skip si données insuffisantes
                    }
                    
                    // Récupérer les prix depuis les ticket_types si absents
                    $adultPrice = $item['adultPrice'] ?? null;
                    $childPrice = $item['childPrice'] ?? null;
                    
                    if (($adultPrice === null || $adultPrice === 0) && isset($item['adultTicketTypeId'])) {
                        $adultPrice = $this->getTicketTypePrice($item['adultTicketTypeId']);
                    }
                    if (($childPrice === null || $childPrice === 0) && isset($item['childTicketTypeId'])) {
                        $childPrice = $this->getTicketTypePrice($item['childTicketTypeId']);
                    }
                    
                    // Si toujours null, utiliser le prix du ticket_type principal
                    if (($adultPrice === null || $adultPrice === 0) && $ticketTypeId) {
                        $adultPrice = $this->getTicketTypePrice($ticketTypeId);
                    }
                    if (($childPrice === null || $childPrice === 0) && $ticketTypeId) {
                        $childPrice = $this->getTicketTypePrice($ticketTypeId);
                    }
                    
                    // Utiliser les prix récupérés pour recalculer le total
                    $adultTotal = ($item['adultQuantity'] ?? 0) * ($adultPrice ?? 0);
                    $childTotal = ($item['childQuantity'] ?? 0) * ($childPrice ?? 0);
                    $itemTotal = $adultTotal + $childTotal;
                    $totalAmount += $itemTotal;
                    
                    $quantity = ($item['adultQuantity'] ?? 0) + ($item['childQuantity'] ?? 0);
                    
                    $this->connection->insert('aiolia.cart_items', [
                        'cart_id' => $cartId,
                        'event_id' => $eventId,
                        'ticket_type_id' => $ticketTypeId,
                        'adult_ticket_type_id' => $item['adultTicketTypeId'] ?? null,
                        'child_ticket_type_id' => $item['childTicketTypeId'] ?? null,
                        'quantity' => $quantity > 0 ? $quantity : 1,
                        'adult_quantity' => $item['adultQuantity'] ?? 0,
                        'child_quantity' => $item['childQuantity'] ?? 0,
                        'unit_price' => $adultPrice ?? $childPrice ?? 0,
                        'adult_price' => $adultPrice ?? 0,
                        'child_price' => $childPrice ?? 0,
                        'total_price' => $itemTotal,
                        'cart_key' => $cartKey,
                    ]);
                }
                
                // Mettre à jour le montant total du panier
                $this->connection->update(
                    'aiolia.carts',
                    [
                        'total_amount' => $totalAmount,
                        'updated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                    ],
                    ['id' => $cartId]
                );
                
                // Valider la transaction
                $this->connection->commit();
                return true;
            } catch (Exception $e) {
                // Annuler la transaction en cas d'erreur
                $this->connection->rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            error_log('Erreur lors de la sauvegarde du panier: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime un item du panier.
     */
    public function removeCartItem(int $cartId, string $cartKey): bool
    {
        try {
            $deleted = $this->connection->delete('aiolia.cart_items', [
                'cart_id' => $cartId,
                'cart_key' => $cartKey,
            ]);
            
            // Recalculer le total du panier
            $this->recalculateCartTotal($cartId);
            
            return $deleted > 0;
        } catch (Exception $e) {
            error_log('Erreur lors de la suppression de l\'item du panier: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Recalcule le total du panier.
     */
    private function recalculateCartTotal(int $cartId): void
    {
        try {
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
        } catch (Exception $e) {
            error_log('Erreur lors du recalcul du total du panier: ' . $e->getMessage());
        }
    }

    /**
     * Convertit les items du panier DB en format session.
     *
     * @param array<int, array<string, mixed>> $dbItems Items depuis la base de données
     * @return array<string, array<string, mixed>> Items au format session
     */
    public function convertDbItemsToSessionFormat(array $dbItems): array
    {
        $sessionItems = [];
        
        foreach ($dbItems as $item) {
            // Si on a une clé sauvegardée dans la DB, l'utiliser
            // Sinon, générer une clé cohérente avec la logique d'ajout
            if (!empty($item['cartKey'])) {
                $cartKey = $item['cartKey'];
            } else {
                // Si l'item a à la fois adultTicketTypeId et childTicketTypeId, utiliser la clé basée sur l'événement
                // Sinon, utiliser la clé classique avec ticket_type_id
                if (isset($item['adultTicketTypeId']) && $item['adultTicketTypeId'] > 0 
                    && isset($item['childTicketTypeId']) && $item['childTicketTypeId'] > 0) {
                    $cartKey = 'event_' . $item['eventId'];
                } else {
                    $cartKey = 'event_' . $item['eventId'] . '_ticket_' . $item['ticketTypeId'];
                }
            }
            
            // Si les prix sont 0 ou null, essayer de les récupérer depuis les ticket_types
            $adultPrice = $item['adultPrice'] ?? null;
            $childPrice = $item['childPrice'] ?? null;
            
            // Si les prix ne sont pas disponibles, les récupérer depuis les ticket_types
            if (($adultPrice === null || $adultPrice === 0) && isset($item['adultTicketTypeId'])) {
                $adultPrice = $this->getTicketTypePrice($item['adultTicketTypeId']);
            }
            if (($childPrice === null || $childPrice === 0) && isset($item['childTicketTypeId'])) {
                $childPrice = $this->getTicketTypePrice($item['childTicketTypeId']);
            }
            
            $sessionItems[$cartKey] = [
                'eventId' => $item['eventId'],
                'ticketTypeId' => $item['ticketTypeId'],
                'adultTicketTypeId' => $item['adultTicketTypeId'],
                'childTicketTypeId' => $item['childTicketTypeId'],
                'adultQuantity' => $item['adultQuantity'],
                'childQuantity' => $item['childQuantity'],
                'adultPrice' => $adultPrice,
                'childPrice' => $childPrice,
                'currency' => 'MGA', // À récupérer depuis le panier si nécessaire
                'added_at' => $item['createdAt'] ?? (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ];
        }
        
        return $sessionItems;
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
     * Fusionne deux paniers (LocalStorage + DB) en donnant priorité au plus récent.
     *
     * @param array<string, array<string, mixed>> $localStorageItems Items depuis LocalStorage
     * @param array<string, array<string, mixed>> $dbItems Items depuis la DB
     * @return array<string, array<string, mixed>> Items fusionnés
     */
    public function mergeCarts(array $localStorageItems, array $dbItems): array
    {
        $merged = $dbItems; // Commencer avec les items de la DB
        
        // Fusionner avec les items du LocalStorage
        foreach ($localStorageItems as $cartKey => $localItem) {
            if (isset($merged[$cartKey])) {
                // Si l'item existe dans les deux, comparer les dates d'ajout pour déterminer la version la plus récente
                $localAddedAt = isset($localItem['added_at']) ? strtotime($localItem['added_at']) : 0;
                $dbAddedAt = isset($merged[$cartKey]['added_at']) ? strtotime($merged[$cartKey]['added_at']) : 0;
                
                // Si la version locale est plus récente, la prendre
                // Sinon, garder celle de la DB (qui est déjà dans $merged)
                if ($localAddedAt > $dbAddedAt) {
                    $merged[$cartKey] = $localItem;
                }
                // Sinon, on garde celle de la DB qui est déjà dans $merged
            } else {
                // Nouvel item du LocalStorage
                $merged[$cartKey] = $localItem;
            }
        }
        
        return $merged;
    }

    /**
     * Génère un token de session unique pour les utilisateurs non connectés.
     */
    public function generateSessionToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}

