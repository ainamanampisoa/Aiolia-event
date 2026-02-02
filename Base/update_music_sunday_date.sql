-- ============================================
-- Script pour tester les notifications
-- ============================================
-- Ce script met à jour l'événement "Music on Sunday" pour tester les notifications
-- IMPORTANT: Ce script utilise le fuseau horaire de la base de données
-- ============================================

-- Vérification du fuseau horaire actuel
-- SELECT current_setting('timezone') as current_timezone, NOW() as current_time;

-- ============================================
-- OPTION 1: Pour tester la notification 24h avant
-- L'événement sera dans exactement 24h à partir de maintenant
-- ============================================

UPDATE aiolia.events
SET 
    starts_at = (NOW() + INTERVAL '24 hours')::timestamptz,
    ends_at = (NOW() + INTERVAL '27 hours')::timestamptz,
    sales_starts_at = NOW(), -- On force le début des ventes à "maintenant"
    sales_ends_at = (NOW() + INTERVAL '24 hours')::timestamptz,
    updated_at = NOW()
WHERE slug = 'concert-music-sunday';

-- Mise à jour des sessions associées
UPDATE aiolia.event_sessions
SET 
    starts_at = (NOW() + INTERVAL '24 hours')::timestamptz,
    ends_at = (NOW() + INTERVAL '25 hours')::timestamptz
WHERE event_id IN (
    SELECT id FROM aiolia.events WHERE slug = 'concert-music-sunday'
);

-- ============================================
-- OPTION 2: Pour tester la notification 2h avant
-- Décommentez cette section et commentez l'option 1
-- ============================================

/*
UPDATE aiolia.events
SET 
    starts_at = (NOW() + INTERVAL '2 hours')::timestamptz,
    ends_at = (NOW() + INTERVAL '5 hours')::timestamptz,
    sales_starts_at = NOW(),
    sales_ends_at = (NOW() + INTERVAL '2 hours')::timestamptz,
    updated_at = NOW()
WHERE slug = 'concert-music-sunday';

UPDATE aiolia.event_sessions
SET 
    starts_at = (NOW() + INTERVAL '2 hours')::timestamptz,
    ends_at = (NOW() + INTERVAL '3 hours')::timestamptz
WHERE event_id IN (
    SELECT id FROM aiolia.events WHERE slug = 'concert-music-sunday'
);
*/

-- ============================================
-- OPTION 3: Pour définir une heure spécifique (ex: demain à 20h00 heure locale)
-- Ajustez l'heure selon votre fuseau horaire
-- ============================================

/*
-- Exemple: Demain à 20h00 dans le fuseau horaire de la base de données
UPDATE aiolia.events
SET 
    starts_at = (date_trunc('day', NOW() + INTERVAL '1 day') + INTERVAL '20 hours')::timestamptz,
    ends_at = (date_trunc('day', NOW() + INTERVAL '1 day') + INTERVAL '23 hours')::timestamptz,
    sales_starts_at = NOW(),
    sales_ends_at = (date_trunc('day', NOW() + INTERVAL '1 day') + INTERVAL '20 hours')::timestamptz,
    updated_at = NOW()
WHERE slug = 'concert-music-sunday';

UPDATE aiolia.event_sessions
SET 
    starts_at = (date_trunc('day', NOW() + INTERVAL '1 day') + INTERVAL '19 hours')::timestamptz,
    ends_at = (date_trunc('day', NOW() + INTERVAL '1 day') + INTERVAL '20 hours')::timestamptz
WHERE event_id IN (
    SELECT id FROM aiolia.events WHERE slug = 'concert-music-sunday'
);
*/

-- ============================================
-- Vérification après exécution
-- ============================================
-- Pour vérifier que la mise à jour a bien fonctionné :
-- 
-- SELECT 
--     id, 
--     title, 
--     slug, 
--     starts_at,
--     starts_at AT TIME ZONE 'UTC' as starts_at_utc,
--     starts_at AT TIME ZONE 'Africa/Nairobi' as starts_at_eat,
--     NOW() as current_time,
--     NOW() AT TIME ZONE 'UTC' as current_time_utc,
--     NOW() AT TIME ZONE 'Africa/Nairobi' as current_time_eat,
--     EXTRACT(EPOCH FROM (starts_at - NOW())) / 3600 as hours_until_start
-- FROM aiolia.events 
-- WHERE slug = 'concert-music-sunday';
--
-- Cette requête vous montrera :
-- - L'heure de l'événement dans différents fuseaux horaires
-- - Le nombre d'heures restantes jusqu'à l'événement
-- ============================================