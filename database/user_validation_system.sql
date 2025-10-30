-- Migration pour le système de validation des utilisateurs
-- Date: 2025-10-22

-- Ajout du champ accountStatus à la table users
ALTER TABLE users 
ADD COLUMN account_status VARCHAR(20) NOT NULL DEFAULT 'active' 
COMMENT 'Statut du compte: active, pending_validation, rejected, suspended';

-- Créer un index sur account_status pour améliorer les performances
CREATE INDEX idx_account_status ON users(account_status);

-- Table pour les demandes de validation
CREATE TABLE user_validation_requests (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    requested_role VARCHAR(50) NOT NULL COMMENT 'organizer ou co_organizer',
    status VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, approved, rejected',
    reason TEXT COMMENT 'Raison de la demande fournie par l\'utilisateur',
    admin_comment TEXT COMMENT 'Commentaire de l\'admin lors de la validation/rejet',
    validated_by BIGINT COMMENT 'ID de l\'admin qui a validé/rejeté',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    validated_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (validated_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour l'historique des actions (audit log)
CREATE TABLE audit_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    performed_by BIGINT COMMENT 'ID de l\'utilisateur qui a effectué l\'action',
    action VARCHAR(100) NOT NULL COMMENT 'Type d\'action (user_created, user_validated, etc.)',
    entity_type VARCHAR(100) NOT NULL COMMENT 'Type d\'entité concernée (User, Event, etc.)',
    entity_id BIGINT COMMENT 'ID de l\'entité concernée',
    details JSON COMMENT 'Détails supplémentaires en JSON',
    ip_address VARCHAR(45) COMMENT 'Adresse IP de l\'utilisateur',
    user_agent VARCHAR(255) COMMENT 'User agent du navigateur',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_action (action),
    INDEX idx_created_at (created_at),
    INDEX idx_performed_by (performed_by),
    INDEX idx_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insérer un log pour la création du système
INSERT INTO audit_logs (performed_by, action, entity_type, entity_id, details, created_at)
VALUES (NULL, 'system_migration', 'System', NULL, 
    JSON_OBJECT(
        'migration', 'user_validation_system',
        'description', 'Mise en place du système de validation des utilisateurs',
        'tables_created', JSON_ARRAY('user_validation_requests', 'audit_logs')
    ), 
    NOW()
);

