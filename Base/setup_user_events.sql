-- ============================================================
-- Script pour rattacher les événements à un utilisateur
-- Usage: psql -h localhost -U aiolia_user -d aiolia_event -f setup_user_events.sql
-- ============================================================

SET search_path TO aiolia, public;

BEGIN;

-- Email de l'utilisateur à configurer
\set user_email 'fifalianavalea@gmail.com'

-- 1. Vérifier que l'utilisateur existe
DO $$
DECLARE
    user_id_val BIGINT;
    event_count INTEGER;
BEGIN
    SELECT id INTO user_id_val FROM users WHERE email = :'user_email';
    
    IF user_id_val IS NULL THEN
        RAISE EXCEPTION 'Utilisateur % introuvable!', :'user_email';
    END IF;
    
    RAISE NOTICE 'Utilisateur trouvé: ID = %', user_id_val;
    
    -- Compter les événements
    SELECT COUNT(*) INTO event_count FROM events;
    RAISE NOTICE 'Nombre d''événements dans la base: %', event_count;
    
    IF event_count = 0 THEN
        RAISE WARNING 'Aucun événement trouvé! Exécutez d''abord mydata.sql';
    END IF;
END $$;

-- 2. Créer ou mettre à jour le profil organisateur
WITH usr AS (
    SELECT id FROM users WHERE email = :'user_email'
)
INSERT INTO organizer_profiles (
    user_id,
    display_name,
    legal_name,
    tax_number,
    support_email,
    support_phone,
    website_url,
    biography,
    organization_type,
    verification_status,
    onboarding_completed_at
)
SELECT
    id,
    'Valea Events',
    'Valea Events SARL',
    'IF-2025',
    'support@valea-events.mg',
    '+261320000123',
    'https://valea-events.mg',
    'Profil organisateur créé manuellement pour Valea.',
    'company',
    'verified',
    now()
FROM usr
ON CONFLICT (user_id) DO UPDATE
SET display_name = EXCLUDED.display_name,
    legal_name = EXCLUDED.legal_name,
    verification_status = EXCLUDED.verification_status,
    onboarding_completed_at = EXCLUDED.onboarding_completed_at;

-- 3. Changer le rôle en "organizer"
UPDATE users
SET role = 'organizer'
WHERE email = :'user_email';

-- 4. Rattacher tous les événements existants à cet organisateur
WITH usr AS (
    SELECT id FROM users WHERE email = :'user_email'
)
UPDATE events
SET organizer_id = usr.id
FROM usr
WHERE events.organizer_id IS NULL OR events.organizer_id != usr.id;

-- 5. Rattacher tous les lieux existants à cet organisateur
WITH usr AS (
    SELECT op.id AS organizer_profile_id
    FROM organizer_profiles op
    JOIN users u ON u.id = op.user_id
    WHERE u.email = :'user_email'
)
UPDATE venues
SET organizer_id = usr.organizer_profile_id
FROM usr
WHERE venues.organizer_id IS NULL OR venues.organizer_id != usr.organizer_profile_id;

-- 6. Afficher un résumé
SELECT 
    '=== RÉSUMÉ ===' AS info,
    '' AS valeur;

SELECT 
    'Événements totaux' AS type,
    COUNT(*)::TEXT AS count
FROM events
UNION ALL
SELECT 
    'Événements de ' || :'user_email' AS type,
    COUNT(*)::TEXT AS count
FROM events e
JOIN users u ON u.id = e.organizer_id
WHERE u.email = :'user_email'
UNION ALL
SELECT 
    'Lieux totaux' AS type,
    COUNT(*)::TEXT AS count
FROM venues
UNION ALL
SELECT 
    'Lieux de ' || :'user_email' AS type,
    COUNT(*)::TEXT AS count
FROM venues v
JOIN organizer_profiles op ON op.id = v.organizer_id
JOIN users u ON u.id = op.user_id
WHERE u.email = :'user_email';

COMMIT;

