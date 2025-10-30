-- Migration pour le système de validation des utilisateurs (PostgreSQL)
-- Date: 2025-10-22

-- Ajout du champ account_status à la table users
ALTER TABLE users 
ADD COLUMN account_status VARCHAR(20) NOT NULL DEFAULT 'active';

-- Ajouter un commentaire sur la colonne
COMMENT ON COLUMN users.account_status IS 'Statut du compte: active, pending_validation, rejected, suspended';

-- Créer un index sur account_status pour améliorer les performances
CREATE INDEX idx_account_status ON users(account_status);

-- Table pour les demandes de validation
CREATE TABLE user_validation_requests (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    requested_role VARCHAR(50) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    reason TEXT,
    admin_comment TEXT,
    validated_by BIGINT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    validated_at TIMESTAMP,
    
    CONSTRAINT fk_user_validation_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_validation_admin FOREIGN KEY (validated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Commentaires sur les colonnes
COMMENT ON COLUMN user_validation_requests.requested_role IS 'organizer ou co_organizer';
COMMENT ON COLUMN user_validation_requests.status IS 'pending, approved, rejected';
COMMENT ON COLUMN user_validation_requests.reason IS 'Raison de la demande fournie par l''utilisateur';
COMMENT ON COLUMN user_validation_requests.admin_comment IS 'Commentaire de l''admin lors de la validation/rejet';
COMMENT ON COLUMN user_validation_requests.validated_by IS 'ID de l''admin qui a validé/rejeté';

-- Index pour améliorer les performances
CREATE INDEX idx_uvr_status ON user_validation_requests(status);
CREATE INDEX idx_uvr_created_at ON user_validation_requests(created_at);
CREATE INDEX idx_uvr_user_id ON user_validation_requests(user_id);

-- Table pour l'historique des actions (audit log)
CREATE TABLE audit_logs (
    id BIGSERIAL PRIMARY KEY,
    performed_by BIGINT,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100) NOT NULL,
    entity_id BIGINT,
    details JSONB,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_audit_log_user FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Commentaires sur les colonnes
COMMENT ON COLUMN audit_logs.performed_by IS 'ID de l''utilisateur qui a effectué l''action';
COMMENT ON COLUMN audit_logs.action IS 'Type d''action (user_created, user_validated, etc.)';
COMMENT ON COLUMN audit_logs.entity_type IS 'Type d''entité concernée (User, Event, etc.)';
COMMENT ON COLUMN audit_logs.entity_id IS 'ID de l''entité concernée';
COMMENT ON COLUMN audit_logs.details IS 'Détails supplémentaires en JSON';
COMMENT ON COLUMN audit_logs.ip_address IS 'Adresse IP de l''utilisateur';
COMMENT ON COLUMN audit_logs.user_agent IS 'User agent du navigateur';

-- Index pour améliorer les performances
CREATE INDEX idx_audit_action ON audit_logs(action);
CREATE INDEX idx_audit_created_at ON audit_logs(created_at);
CREATE INDEX idx_audit_performed_by ON audit_logs(performed_by);
CREATE INDEX idx_audit_entity ON audit_logs(entity_type, entity_id);

-- Insérer un log pour la création du système
INSERT INTO audit_logs (performed_by, action, entity_type, entity_id, details, created_at)
VALUES (NULL, 'system_migration', 'System', NULL, 
    '{"migration": "user_validation_system", "description": "Mise en place du système de validation des utilisateurs", "tables_created": ["user_validation_requests", "audit_logs"]}'::jsonb,
    NOW()
);

