-- ============================================================
-- Script pour vérifier les événements disponibles avec leurs catégories et prix
-- ============================================================

\c aiolia_event;
SET search_path TO aiolia, public;

-- Voir toutes les catégories disponibles
SELECT 
    id,
    label,
    slug,
    description
FROM aiolia.event_categories
ORDER BY label;

-- Voir les événements avec leurs catégories et prix de tickets
SELECT 
    e.id as event_id,
    e.title,
    e.slug,
    e.status,
    e.starts_at,
    ec.label as category_name,
    ec.slug as category_slug,
    tt.id as ticket_type_id,
    tt.name as ticket_type_name,
    tt.base_price,
    COUNT(t.id) as tickets_sold
FROM aiolia.events e
LEFT JOIN aiolia.event_categories ec ON ec.id = e.primary_category_id
LEFT JOIN aiolia.ticket_types tt ON tt.event_id = e.id
LEFT JOIN aiolia.tickets t ON t.ticket_type_id = tt.id
WHERE e.status = 'published'
GROUP BY e.id, e.title, e.slug, e.status, e.starts_at, ec.name, ec.slug, tt.id, tt.name, tt.base_price
ORDER BY e.id, tt.base_price;

-- Voir les événements passés avec catégories business et festival
SELECT 
    e.id as event_id,
    e.title,
    e.starts_at,
    ec.label as category_name,
    ec.slug as category_slug,
    MIN(tt.base_price) as prix_min,
    MAX(tt.base_price) as prix_max,
    AVG(tt.base_price) as prix_moyen
FROM aiolia.events e
LEFT JOIN aiolia.event_categories ec ON ec.id = e.primary_category_id
LEFT JOIN aiolia.ticket_types tt ON tt.event_id = e.id
WHERE e.status = 'published'
  AND (LOWER(ec.name) LIKE '%business%' 
       OR LOWER(ec.name) LIKE '%festival%'
       OR LOWER(ec.slug) LIKE '%business%'
       OR LOWER(ec.slug) LIKE '%festival%')
  AND e.starts_at < NOW()
GROUP BY e.id, e.title, e.starts_at, ec.name, ec.slug
ORDER BY e.starts_at DESC;
