-- Requête pour vérifier les utilisateurs avec billets pour "Music on Sunday"
-- Diagnostic pour comprendre pourquoi aucun utilisateur n'est trouvé

SET search_path TO aiolia, public;

-- 1. Vérifier l'événement
SELECT 
    id,
    slug,
    title,
    starts_at,
    status
FROM aiolia.events
WHERE slug = 'concert-music-sunday';

-- 2. Vérifier les types de billets pour cet événement
SELECT 
    tt.id,
    tt.name,
    tt.event_id,
    e.title AS event_title
FROM aiolia.ticket_types tt
JOIN aiolia.events e ON e.id = tt.event_id
WHERE e.slug = 'concert-music-sunday';

-- 3. Vérifier TOUTES les commandes (tous statuts) pour cet événement
SELECT 
    o.id AS order_id,
    o.user_id,
    o.status AS order_status,
    u.email,
    u.first_name,
    u.last_name,
    u.is_email_verified,
    COUNT(t.id) AS ticket_count,
    STRING_AGG(DISTINCT t.status::text, ', ') AS ticket_statuses
FROM aiolia.orders o
JOIN aiolia.users u ON u.id = o.user_id
LEFT JOIN aiolia.order_items oi ON oi.order_id = o.id
LEFT JOIN aiolia.tickets t ON t.order_item_id = oi.id
LEFT JOIN aiolia.ticket_types tt ON tt.id = COALESCE(t.ticket_type_id, oi.ticket_type_id)
LEFT JOIN aiolia.events e ON e.id = tt.event_id
WHERE e.slug = 'concert-music-sunday' OR o.id IN (
    SELECT DISTINCT oi2.order_id 
    FROM aiolia.order_items oi2
    JOIN aiolia.ticket_types tt2 ON tt2.id = oi2.ticket_type_id
    JOIN aiolia.events e2 ON e2.id = tt2.event_id
    WHERE e2.slug = 'concert-music-sunday'
)
GROUP BY o.id, o.user_id, o.status, u.email, u.first_name, u.last_name, u.is_email_verified
ORDER BY o.created_at DESC;

-- 4. Vérifier les billets pour cet événement (tous statuts)
SELECT 
    t.id AS ticket_id,
    t.status AS ticket_status,
    t.owner_user_id,
    u.email,
    u.is_email_verified,
    o.id AS order_id,
    o.status AS order_status,
    e.title AS event_title,
    e.slug AS event_slug
FROM aiolia.tickets t
JOIN aiolia.order_items oi ON oi.id = t.order_item_id
JOIN aiolia.orders o ON o.id = oi.order_id
JOIN aiolia.users u ON u.id = t.owner_user_id
JOIN aiolia.ticket_types tt ON tt.id = t.ticket_type_id
JOIN aiolia.events e ON e.id = tt.event_id
WHERE e.slug = 'concert-music-sunday'
ORDER BY t.id DESC;

-- 5. Requête exacte utilisée par findUsersWithTicketsForEvent
SELECT DISTINCT
    u.id,
    u.email,
    u.first_name,
    u.last_name,
    u.is_email_verified,
    o.status AS order_status,
    t.status AS ticket_status,
    e.title AS event_title
FROM aiolia.users u
INNER JOIN aiolia.orders o ON o.user_id = u.id
INNER JOIN aiolia.order_items oi ON oi.order_id = o.id
INNER JOIN aiolia.tickets t ON t.order_item_id = oi.id
INNER JOIN aiolia.ticket_types tt ON tt.id = t.ticket_type_id
WHERE tt.event_id = (SELECT id FROM aiolia.events WHERE slug = 'concert-music-sunday')
  AND o.status = 'paid'
  AND t.status = 'valid'
  AND u.is_email_verified = TRUE;

-- 6. Vérifier pourquoi les utilisateurs ne sont pas trouvés (diagnostic étape par étape)
-- 6a. Utilisateurs avec commandes pour cet événement (sans filtre)
SELECT DISTINCT
    u.id,
    u.email,
    u.is_email_verified,
    o.status AS order_status,
    COUNT(DISTINCT t.id) AS ticket_count,
    STRING_AGG(DISTINCT t.status::text, ', ') AS ticket_statuses
FROM aiolia.users u
INNER JOIN aiolia.orders o ON o.user_id = u.id
INNER JOIN aiolia.order_items oi ON oi.order_id = o.id
INNER JOIN aiolia.tickets t ON t.order_item_id = oi.id
INNER JOIN aiolia.ticket_types tt ON tt.id = t.ticket_type_id
WHERE tt.event_id = (SELECT id FROM aiolia.events WHERE slug = 'concert-music-sunday')
GROUP BY u.id, u.email, u.is_email_verified, o.status;

