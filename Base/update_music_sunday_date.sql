-- Mise à jour de la date de l'événement "Music on Sunday" pour demain à 20h00
-- Pour tester les notifications de rappel (24h et 2h avant)

UPDATE aiolia.events
SET 
    starts_at = (date_trunc('day', now()) + INTERVAL '1 day' + INTERVAL '20 hours')::timestamptz,
    ends_at = (date_trunc('day', now()) + INTERVAL '1 day' + INTERVAL '23 hours')::timestamptz,
    sales_ends_at = (date_trunc('day', now()) + INTERVAL '1 day' + INTERVAL '20 hours')::timestamptz,
    updated_at = now()
WHERE slug = 'concert-music-sunday';

-- Mise à jour également des sessions associées
UPDATE aiolia.event_sessions
SET 
    starts_at = (date_trunc('day', now()) + INTERVAL '1 day' + INTERVAL '19 hours')::timestamptz,
    ends_at = (date_trunc('day', now()) + INTERVAL '1 day' + INTERVAL '20 hours')::timestamptz
WHERE event_id IN (
    SELECT id FROM aiolia.events WHERE slug = 'concert-music-sunday'
);

