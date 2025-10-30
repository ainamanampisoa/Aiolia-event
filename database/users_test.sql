-- ============================================================================
-- UTILISATEURS DE TEST POUR AIOLIA EVENT
-- ============================================================================
-- Exécuter avec : sudo -u postgres psql -d aiolia_event -f database/users_test.sql
-- ============================================================================

-- 1. ADMIN - admin@test.com / password: admin123
INSERT INTO users (email, password_hash, first_name, last_name, phone, role, email_verified, is_active, created_at, updated_at)
VALUES (
    'admin@test.com',
    '$2y$13$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Admin',
    'Test',
    '+261 34 12 345 67',
    'admin',
    true,
    true,
    CURRENT_TIMESTAMP,
    CURRENT_TIMESTAMP
) ON CONFLICT (email) DO NOTHING;

-- 2. ORGANIZER - organizer@test.com / password: password
INSERT INTO users (email, password_hash, first_name, last_name, phone, role, email_verified, is_active, created_at, updated_at)
VALUES (
    'organizer@test.com',
    '$2y$13$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Jean',
    'Organisateur',
    '+261 33 23 456 78',
    'organizer',
    true,
    true,
    CURRENT_TIMESTAMP,
    CURRENT_TIMESTAMP
) ON CONFLICT (email) DO NOTHING;

-- 3. USER SIMPLE - user@test.com / password: password
INSERT INTO users (email, password_hash, first_name, last_name, phone, role, email_verified, is_active, created_at, updated_at)
VALUES (
    'user@test.com',
    '$2y$13$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Marie',
    'Utilisateur',
    '+261 32 34 567 89',
    'user',
    true,
    true,
    CURRENT_TIMESTAMP,
    CURRENT_TIMESTAMP
) ON CONFLICT (email) DO NOTHING;

-- ============================================================================
-- INFORMATIONS DE CONNEXION
-- ============================================================================
-- 
-- Tous les comptes utilisent le même mot de passe : password
-- 
-- | Email                | Mot de passe | Rôle      | Nom                  |
-- |----------------------|--------------|-----------|----------------------|
-- | admin@test.com       | password     | admin     | Admin Test           |
-- | organizer@test.com   | password     | organizer | Jean Organisateur    |
-- | user@test.com        | password     | user      | Marie Utilisateur    |
-- 
-- ============================================================================
-- Pour tester l'inscription, créez un nouveau compte via l'interface
-- ============================================================================












