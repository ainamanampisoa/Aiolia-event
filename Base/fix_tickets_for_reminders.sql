-- Script pour corriger les problèmes qui empêchent les rappels d'être envoyés
-- À exécuter après avoir diagnostiqué avec check_users_with_tickets.sql

BEGIN;

SET search_path TO aiolia, public;

-- 1. Vérifier et corriger les emails non vérifiés pour les utilisateurs avec billets
UPDATE aiolia.users u
SET is_email_verified = TRUE
WHERE u.id IN (
    SELECT DISTINCT o.user_id
    FROM aiolia.orders o
    JOIN aiolia.order_items oi ON oi.order_id = o.id
    JOIN aiolia.tickets t ON t.order_item_id = oi.id
    JOIN aiolia.ticket_types tt ON tt.id = t.ticket_type_id
    JOIN aiolia.events e ON e.id = tt.event_id
    WHERE e.slug = 'concert-music-sunday'
    AND o.status = 'paid'
    AND t.status = 'valid'
    AND u.is_email_verified = FALSE
);

-- 2. Vérifier et corriger les statuts de commande (si nécessaire)
-- Note: Ne pas changer automatiquement, juste afficher les commandes à vérifier
SELECT 
    o.id AS order_id,
    o.status AS current_status,
    o.user_id,
    u.email,
    'Vérifier manuellement si cette commande doit être "paid"' AS action
FROM aiolia.orders o
JOIN aiolia.users u ON u.id = o.user_id
JOIN aiolia.order_items oi ON oi.order_id = o.id
JOIN aiolia.tickets t ON t.order_item_id = oi.id
JOIN aiolia.ticket_types tt ON tt.id = t.ticket_type_id
JOIN aiolia.events e ON e.id = tt.event_id
WHERE e.slug = 'concert-music-sunday'
AND o.status != 'paid'
AND t.status = 'valid';

-- 3. Vérifier et corriger les statuts de billets (si nécessaire)
UPDATE aiolia.tickets t
SET status = 'valid'
WHERE t.id IN (
    SELECT t.id
    FROM aiolia.tickets t
    JOIN aiolia.order_items oi ON oi.id = t.order_item_id
    JOIN aiolia.orders o ON o.id = oi.order_id
    JOIN aiolia.ticket_types tt ON tt.id = t.ticket_type_id
    JOIN aiolia.events e ON e.id = tt.event_id
    WHERE e.slug = 'concert-music-sunday'
    AND o.status = 'paid'
    AND t.status != 'valid'
    AND t.status != 'used'
    AND t.status != 'cancelled'
);

-- 4. Vérification finale : Liste des utilisateurs qui devraient recevoir des rappels
SELECT DISTINCT
    u.id,
    u.email,
    u.first_name,
    u.last_name,
    u.is_email_verified,
    o.status AS order_status,
    t.status AS ticket_status,
    e.title AS event_title,
    e.starts_at
FROM aiolia.users u
INNER JOIN aiolia.orders o ON o.user_id = u.id
INNER JOIN aiolia.order_items oi ON oi.order_id = o.id
INNER JOIN aiolia.tickets t ON t.order_item_id = oi.id
INNER JOIN aiolia.ticket_types tt ON tt.id = t.ticket_type_id
INNER JOIN aiolia.events e ON e.id = tt.event_id
WHERE e.slug = 'concert-music-sunday'
  AND o.status = 'paid'
  AND t.status = 'valid'
  AND u.is_email_verified = TRUE
ORDER BY u.email;

COMMIT;

