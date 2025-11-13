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

-- 3. Rattache tous les événements existants à cet organisateur
UPDATE aiolia.events
SET organizer_id = usr.id
FROM (SELECT id FROM aiolia.users WHERE email = 'fifalianavalea@gmail.com') AS usr;

-- 4. (Optionnel) Rattache aussi les lieux existants à cet organisateur
UPDATE aiolia.venues
SET organizer_id = usr.id
FROM (SELECT id FROM aiolia.users WHERE email = 'fifalianavalea@gmail.com') AS usr;