-- ============================================
-- AIOLIA EVENT PLATFORM - JEU DE DONNÉES TEST
-- Génération : 2025-11-10
-- ============================================

SET search_path TO aiolia, public;

-- ============================================
-- 1️⃣ UTILISATEURS ORGANISATEURS (NON VALIDÉS)
-- ============================================

WITH new_users AS (
    INSERT INTO users (
        email,
        password_hash,
        first_name,
        last_name,
        status,
        is_email_verified,
        language_code,
        accepted_terms_at
    )
    VALUES
        ('valeafifaliana@gmail.com', crypt('TestPassword#1', gen_salt('bf')), 'Valea', 'Fifaliana', 'pending', FALSE, 'fr-FR', now()),
        ('malalavalea@gmail.com',    crypt('TestPassword#2', gen_salt('bf')), 'Malala', 'Valea',     'pending', FALSE, 'fr-FR', now())
    ON CONFLICT (email) DO UPDATE
        SET
            status = EXCLUDED.status,
            updated_at = now()
    RETURNING user_id, email
),
organizer_role AS (
    SELECT role_id
    FROM user_roles
    WHERE code = 'organizer'
)
INSERT INTO user_role_assignments (user_id, role_id, assigned_at, assigned_by)
SELECT nu.user_id, orole.role_id, now(), NULL
FROM new_users nu
CROSS JOIN organizer_role orole
ON CONFLICT (user_id, role_id) DO NOTHING;

-- ============================================
-- 2️⃣ PROFILS ORGANISATEURS (STATUT PENDING)
-- ============================================

INSERT INTO organizer_profiles (
    user_id,
    display_name,
    legal_name,
    tax_number,
    support_email,
    support_phone,
    website_url,
    biography,
    verification_status
)
SELECT
    u.user_id,
    CASE u.email
        WHEN 'valeafifaliana@gmail.com' THEN 'Valea Évènements'
        WHEN 'malalavalea@gmail.com'    THEN 'Malala Productions'
    END,
    NULL,
    NULL,
    NULL,
    NULL,
    NULL,
    'Compte test non validé',
    'pending'
FROM users u
WHERE u.email IN ('valeafifaliana@gmail.com', 'malalavalea@gmail.com')
ON CONFLICT (user_id) DO UPDATE
    SET
        verification_status = EXCLUDED.verification_status,
        updated_at = now();

