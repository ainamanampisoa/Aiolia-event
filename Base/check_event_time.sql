-- Script de vérification de l'heure de l'événement
-- Utilisez ce script pour vérifier l'heure actuelle de l'événement

SELECT 
    id, 
    title, 
    slug, 
    starts_at,
    starts_at AT TIME ZONE 'UTC' as starts_at_utc,
    starts_at AT TIME ZONE 'Africa/Nairobi' as starts_at_eat,
    NOW() as current_time_db,
    NOW() AT TIME ZONE 'UTC' as current_time_utc,
    NOW() AT TIME ZONE 'Africa/Nairobi' as current_time_eat,
    ROUND(EXTRACT(EPOCH FROM (starts_at - NOW())) / 3600, 2) as hours_until_start,
    current_setting('timezone') as db_timezone
FROM aiolia.events 
WHERE slug = 'concert-music-sunday';
