<?php

namespace App\Service;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class ActivityService
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ?LoggerInterface $logger = null
    ) {
    }

    /**
     * Enregistre une activité dans audit_logs
     */
    public function logToAudit(
        int $userId,
        string $scope,
        string $action,
        ?int $entityId = null,
        ?string $entityType = null,
        ?array $changes = null
    ): void {
        try {
            $params = [
                'actor_user_id' => $userId,
                'scope' => $scope,
                'action' => $action,
            ];
            
            if ($entityId !== null) {
                $params['entity_id'] = $entityId;
            }
            if ($entityType !== null) {
                $params['entity_type'] = $entityType;
            }
            if ($changes !== null) {
                $params['changes'] = json_encode($changes);
            }
            
            // created_at a une valeur par défaut (now()) dans la table, donc pas besoin de l'inclure
            $this->connection->insert('aiolia.audit_logs', $params);
        } catch (\Exception $e) {
            $this->logger?->error('Failed to log to audit: ' . $e->getMessage());
        }
    }

    /**
     * Log une suppression du panier
     */
    public function logCartRemoval(int $userId, int $eventId): void
    {
        $this->logToAudit($userId, 'cart', 'cart_item_removed', $eventId, 'Event');
    }

    /**
     * Log une suppression de favoris
     */
    public function logFavoriteRemoval(int $userId, int $eventId): void
    {
        $this->logToAudit($userId, 'favorites', 'favorite_removed', $eventId, 'Event');
    }

    /**
     * Enregistre une activité utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @param string $type Type d'activité (ticket, favorite, cart, wallet, search, profile, etc.)
     * @param string $title Titre de l'activité
     * @param string $meta Métadonnées (date, statut, etc.)
     * @param array $metadata Informations supplémentaires (event_id, amount, etc.)
     */
    public function logActivity(
        int $userId,
        string $type,
        string $title,
        string $meta = '',
        array $metadata = []
    ): void {
        try {
            // Vérifier si la table user_activities existe
            // Si elle n'existe pas, on peut créer une table simple pour les activités
            // Pour l'instant, on va juste logger dans les logs
            $this->logger?->info('User activity logged', [
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'meta' => $meta,
                'metadata' => $metadata,
            ]);
            
            // TODO: Créer une table user_activities si nécessaire pour un tracking plus complet
            // Pour l'instant, les activités sont récupérées depuis les tables existantes
            
        } catch (\Exception $e) {
            $this->logger?->error('Failed to log activity: ' . $e->getMessage());
        }
    }

    /**
     * Log une activité de profil (upload photo, modification)
     */
    public function logProfileActivity(int $userId, string $action, array $details = []): void
    {
        $titles = [
            'avatar_uploaded' => 'Photo de profil mise à jour',
            'profile_updated' => 'Profil modifié',
            'settings_updated' => 'Paramètres modifiés',
        ];
        
        $title = $titles[$action] ?? 'Action sur le profil';
        $meta = date('d M Y') . ' · Paramètres du compte';
        
        $this->logActivity($userId, 'profile', $title, $meta, $details);
    }

    /**
     * Log une activité wallet
     */
    public function logWalletActivity(int $userId, string $type, float $amount, string $status = 'completed'): void
    {
        $titles = [
            'recharge' => 'Rechargement wallet : ' . number_format($amount, 0, ',', ' ') . ' MGA',
            'transfer' => 'Transfert wallet : ' . number_format($amount, 0, ',', ' ') . ' MGA',
        ];
        
        $title = $titles[$type] ?? 'Transaction wallet';
        $meta = date('d M Y') . ' · ' . ($status === 'completed' ? 'Confirmé' : 'En attente');
        
        $this->logActivity($userId, 'wallet', $title, $meta, ['amount' => $amount, 'type' => $type]);
    }
}
