-- Script de correction pour les colonnes de base de données PostgreSQL
-- Date: 2025-10-22

-- Vérifier si les colonnes existent et les créer si nécessaire
DO $$
BEGIN
    -- Ajouter la colonne account_status si elle n'existe pas
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name = 'users' AND column_name = 'account_status') THEN
        ALTER TABLE users ADD COLUMN account_status VARCHAR(20) NOT NULL DEFAULT 'active';
        COMMENT ON COLUMN users.account_status IS 'Statut du compte: active, pending_validation, rejected, suspended';
        CREATE INDEX IF NOT EXISTS idx_account_status ON users(account_status);
    END IF;
END $$;

-- Créer la table user_validation_requests si elle n'existe pas
CREATE TABLE IF NOT EXISTS user_validation_requests (
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
CREATE INDEX IF NOT EXISTS idx_uvr_status ON user_validation_requests(status);
CREATE INDEX IF NOT EXISTS idx_uvr_created_at ON user_validation_requests(created_at);
CREATE INDEX IF NOT EXISTS idx_uvr_user_id ON user_validation_requests(user_id);

-- Créer la table audit_logs si elle n'existe pas
CREATE TABLE IF NOT EXISTS audit_logs (
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
CREATE INDEX IF NOT EXISTS idx_audit_action ON audit_logs(action);
CREATE INDEX IF NOT EXISTS idx_audit_created_at ON audit_logs(created_at);
CREATE INDEX IF NOT EXISTS idx_audit_performed_by ON audit_logs(performed_by);
CREATE INDEX IF NOT EXISTS idx_audit_entity ON audit_logs(entity_type, entity_id);

-- Insérer un log pour la correction du système si pas déjà fait
INSERT INTO audit_logs (performed_by, action, entity_type, entity_id, details, created_at)
SELECT NULL, 'system_migration', 'System', NULL, 
    '{"migration": "user_validation_system_fix", "description": "Correction du système de validation des utilisateurs", "tables_created": ["user_validation_requests", "audit_logs"]}'::jsonb,
    NOW()
WHERE NOT EXISTS (SELECT 1 FROM audit_logs WHERE action = 'system_migration');
