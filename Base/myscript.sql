--Data
psql -h localhost -U aiolia_user -d aiolia_event -f /home/aina/Documents/MyProject/Aiolia-event/Base/mydata.sql
---Requete

psql -h localhost -U aiolia_user -d aiolia_event -c "SELECT id, email, created_at FROM aiolia.users ORDER BY id LIMIT 5;"
psql -h localhost -U aiolia_user -d aiolia_event -c "SELECT COUNT(*) FROM aiolia.events;"

SELECT op.id, u.email, op.display_name
FROM aiolia.organizer_profiles op
JOIN aiolia.users u ON u.id = op.user_id
ORDER BY op.id DESC LIMIT 5;

-- 1. Crée (ou met à jour) le profil organisateur
WITH usr AS (
    SELECT id FROM aiolia.users WHERE email = 'fifalianavalea@gmail.com'
)
INSERT INTO aiolia.organizer_profiles (
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

-- 2. Change son rôle en “organizer”
UPDATE aiolia.users
SET role = 'organizer'
WHERE email = 'fifalianavalea@gmail.com';

-- 3. Vérifier si des événements existent
DO $$
DECLARE
    event_count INTEGER;
    user_id_val BIGINT;
    organizer_profile_id_val BIGINT;
BEGIN
    -- Récupérer l'ID de l'utilisateur
    SELECT id INTO user_id_val FROM aiolia.users WHERE email = 'fifalianavalea@gmail.com';
    
    IF user_id_val IS NULL THEN
        RAISE NOTICE 'Utilisateur fifalianavalea@gmail.com introuvable!';
        RETURN;
    END IF;
    
    -- Récupérer l'ID du profil organisateur
    SELECT id INTO organizer_profile_id_val FROM aiolia.organizer_profiles WHERE user_id = user_id_val;
    
    IF organizer_profile_id_val IS NULL THEN
        RAISE NOTICE 'Profil organisateur introuvable pour cet utilisateur!';
        RETURN;
    END IF;
    
    -- Compter les événements
    SELECT COUNT(*) INTO event_count FROM aiolia.events;
    
    IF event_count = 0 THEN
        RAISE NOTICE 'Aucun événement trouvé dans la base. Exécutez d''abord mydata.sql pour créer des événements de test.';
        RAISE NOTICE 'Commande: psql -h localhost -U aiolia_user -d aiolia_event -f /home/aina/Documents/MyProject/Aiolia-event/Base/mydata.sql';
    ELSE
        -- Rattacher tous les événements existants à cet organisateur
        UPDATE aiolia.events
        SET organizer_id = user_id_val
        WHERE organizer_id IS NULL OR organizer_id != user_id_val;
        
        RAISE NOTICE 'Événements rattachés: %', (SELECT COUNT(*) FROM aiolia.events WHERE organizer_id = user_id_val);
        
        -- Rattacher aussi les lieux existants à cet organisateur
        UPDATE aiolia.venues
        SET organizer_id = organizer_profile_id_val
        WHERE organizer_id IS NULL OR organizer_id != organizer_profile_id_val;
        
        RAISE NOTICE 'Lieux rattachés: %', (SELECT COUNT(*) FROM aiolia.venues WHERE organizer_id = organizer_profile_id_val);
    END IF;
END $$;

-- 4. Afficher un résumé
SELECT 
    'Événements totaux' AS type,
    COUNT(*)::TEXT AS count
FROM aiolia.events
UNION ALL
SELECT 
    'Événements de fifalianavalea@gmail.com' AS type,
    COUNT(*)::TEXT AS count
FROM aiolia.events e
JOIN aiolia.users u ON u.id = e.organizer_id
WHERE u.email = 'fifalianavalea@gmail.com'
UNION ALL
SELECT 
    'Lieux totaux' AS type,
    COUNT(*)::TEXT AS count
FROM aiolia.venues
UNION ALL
SELECT 
    'Lieux de fifalianavalea@gmail.com' AS type,
    COUNT(*)::TEXT AS count
FROM aiolia.venues v
JOIN aiolia.organizer_profiles op ON op.id = v.organizer_id
JOIN aiolia.users u ON u.id = op.user_id
WHERE u.email = 'fifalianavalea@gmail.com';