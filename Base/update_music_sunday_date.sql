-- Mise à jour de l'événement avec correction de la période de vente
UPDATE aiolia.events
SET 
    starts_at = (date_trunc('day', now()) + INTERVAL '1 day' + INTERVAL '20 hours')::timestamptz,
    ends_at = (date_trunc('day', now()) + INTERVAL '1 day' + INTERVAL '23 hours')::timestamptz,
    sales_starts_at = now(), -- On force le début des ventes à "maintenant" pour éviter le conflit
    sales_ends_at = (date_trunc('day', now()) + INTERVAL '1 day' + INTERVAL '20 hours')::timestamptz,
    updated_at = now()
WHERE slug = 'concert-music-sunday';

-- Mise à jour des sessions associées (déjà correcte)
UPDATE aiolia.event_sessions
SET 
    starts_at = (date_trunc('day', now()) + INTERVAL '1 day' + INTERVAL '19 hours')::timestamptz,
    ends_at = (date_trunc('day', now()) + INTERVAL '1 day' + INTERVAL '20 hours')::timestamptz
WHERE event_id IN (
    SELECT id FROM aiolia.events WHERE slug = 'concert-music-sunday'
);