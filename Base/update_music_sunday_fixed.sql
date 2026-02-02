-- ============================================
-- Script CORRIGÉ pour mettre à jour Music on Sunday
-- Ce script prend en compte les fuseaux horaires
-- ============================================

-- IMPORTANT: Si vous êtes à 16h23 et que l'événement s'affiche à 13h18,
-- c'est probablement un problème de fuseau horaire (différence de 3h)
-- 
-- Ce script force l'heure dans le fuseau horaire de la base de données
-- ou utilise UTC si nécessaire

-- ============================================
-- OPTION 1: Mettre l'événement dans exactement 24h
-- (recommandé pour tester les notifications 24h)
-- Ce script arrondit à l'heure exacte pour éviter les problèmes de précision
-- ============================================

-- Arrondir à l'heure exacte dans 24h (ex: si maintenant 16:29, l'événement sera à 16:00 demain)
UPDATE aiolia.events
SET 
    starts_at = (date_trunc('hour', NOW() + INTERVAL '24 hours'))::timestamptz,
    ends_at = (date_trunc('hour', NOW() + INTERVAL '27 hours'))::timestamptz,
    sales_starts_at = NOW(),
    sales_ends_at = (date_trunc('hour', NOW() + INTERVAL '24 hours'))::timestamptz,
    updated_at = NOW()
WHERE slug = 'concert-music-sunday';

UPDATE aiolia.event_sessions
SET 
    starts_at = (date_trunc('hour', NOW() + INTERVAL '24 hours'))::timestamptz,
    ends_at = (date_trunc('hour', NOW() + INTERVAL '25 hours'))::timestamptz
WHERE event_id IN (
    SELECT id FROM aiolia.events WHERE slug = 'concert-music-sunday'
);

-- ============================================
-- OPTION 2: Mettre l'événement dans exactement 2h
-- (pour tester les notifications 2h avant)
-- Décommentez cette section et commentez l'option 1
-- ============================================

/*
-- Arrondir à l'heure exacte dans 2h
UPDATE aiolia.events
SET 
    starts_at = (date_trunc('hour', NOW() + INTERVAL '2 hours'))::timestamptz,
    ends_at = (date_trunc('hour', NOW() + INTERVAL '5 hours'))::timestamptz,
    sales_starts_at = NOW(),
    sales_ends_at = (date_trunc('hour', NOW() + INTERVAL '2 hours'))::timestamptz,
    updated_at = NOW()
WHERE slug = 'concert-music-sunday';

UPDATE aiolia.event_sessions
SET 
    starts_at = (date_trunc('hour', NOW() + INTERVAL '2 hours'))::timestamptz,
    ends_at = (date_trunc('hour', NOW() + INTERVAL '3 hours'))::timestamptz
WHERE event_id IN (
    SELECT id FROM aiolia.events WHERE slug = 'concert-music-sunday'
);
*/

-- ============================================
-- OPTION 3: Forcer une heure spécifique en UTC
-- Si votre base de données est en UTC et que vous voulez
-- que l'événement soit demain à 16h23 UTC (par exemple)
-- ============================================

/*
-- Exemple: Demain à 16h23 UTC
UPDATE aiolia.events
SET 
    starts_at = ((date_trunc('day', NOW() AT TIME ZONE 'UTC') + INTERVAL '1 day' + INTERVAL '16 hours' + INTERVAL '23 minutes') AT TIME ZONE 'UTC')::timestamptz,
    ends_at = ((date_trunc('day', NOW() AT TIME ZONE 'UTC') + INTERVAL '1 day' + INTERVAL '19 hours' + INTERVAL '23 minutes') AT TIME ZONE 'UTC')::timestamptz,
    sales_starts_at = NOW(),
    sales_ends_at = ((date_trunc('day', NOW() AT TIME ZONE 'UTC') + INTERVAL '1 day' + INTERVAL '16 hours' + INTERVAL '23 minutes') AT TIME ZONE 'UTC')::timestamptz,
    updated_at = NOW()
WHERE slug = 'concert-music-sunday';
*/

-- ============================================
-- VÉRIFICATION IMPORTANTE
-- ============================================
-- Après avoir exécuté le script, exécutez cette requête pour vérifier :
--
-- SELECT 
--     id, 
--     title, 
--     slug, 
--     starts_at,
--     starts_at AT TIME ZONE 'UTC' as starts_at_utc,
--     starts_at AT TIME ZONE 'Africa/Nairobi' as starts_at_eat,
--     NOW() as current_time_db,
--     NOW() AT TIME ZONE 'UTC' as current_time_utc,
--     NOW() AT TIME ZONE 'Africa/Nairobi' as current_time_eat,
--     ROUND(EXTRACT(EPOCH FROM (starts_at - NOW())) / 3600, 2) as hours_until_start,
--     current_setting('timezone') as db_timezone
-- FROM aiolia.events 
-- WHERE slug = 'concert-music-sunday';
--
-- Vérifiez que :
-- 1. hours_until_start est proche de 24 (ou 2 si vous testez l'option 2)
-- 2. L'heure affichée correspond à ce que vous attendez
-- ============================================
