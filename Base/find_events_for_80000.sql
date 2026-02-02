-- ============================================================
-- Script pour trouver des événements qui donnent exactement 80 000 MGA
-- avec 3 achats (3 billets, 2 billets, 1 billet) sans ajustement
-- ============================================================

\c aiolia_event;
SET search_path TO aiolia, public;

-- Trouver toutes les combinaisons possibles qui donnent 80 000 MGA
WITH event_prices AS (
    SELECT 
        e.id as event_id,
        e.title,
        e.slug,
        ec.label as category_name,
        ec.slug as category_slug,
        tt.id as ticket_type_id,
        tt.name as ticket_type_name,
        tt.base_price as price
    FROM aiolia.events e
    JOIN aiolia.ticket_types tt ON tt.event_id = e.id
    LEFT JOIN aiolia.event_categories ec ON ec.id = e.primary_category_id
    WHERE e.status = 'published'
      AND tt.base_price > 0
    ORDER BY e.id, tt.base_price
),
combinations AS (
    SELECT 
        e1.event_id as event_1_id,
        e1.title as event_1_title,
        e1.category_name as event_1_category,
        e1.price as price_1,
        e1.price * 3 as total_1,
        
        e2.event_id as event_2_id,
        e2.title as event_2_title,
        e2.category_name as event_2_category,
        e2.price as price_2,
        e2.price * 2 as total_2,
        
        e3.event_id as event_3_id,
        e3.title as event_3_title,
        e3.category_name as event_3_category,
        e3.price as price_3,
        e3.price as total_3,
        
        (e1.price * 3 + e2.price * 2 + e3.price) as grand_total,
        
        e1.ticket_type_id as ticket_type_1_id,
        e2.ticket_type_id as ticket_type_2_id,
        e3.ticket_type_id as ticket_type_3_id
    FROM event_prices e1
    CROSS JOIN event_prices e2
    CROSS JOIN event_prices e3
    WHERE e1.event_id != e2.event_id
      AND e1.event_id != e3.event_id
      AND e2.event_id != e3.event_id
      AND ABS((e1.price * 3 + e2.price * 2 + e3.price) - 80000) < 1  -- Tolérance de 1 MGA
      AND e1.slug NOT LIKE '%music%'  -- Exclure Music on Sunday
      AND e2.slug NOT LIKE '%music%'
      AND e3.slug NOT LIKE '%music%'
    LIMIT 10
)
SELECT 
    event_1_id,
    event_1_title,
    event_1_category,
    price_1,
    total_1 as "3 billets",
    
    event_2_id,
    event_2_title,
    event_2_category,
    price_2,
    total_2 as "2 billets",
    
    event_3_id,
    event_3_title,
    event_3_category,
    price_3,
    total_3 as "1 billet",
    
    grand_total,
    
    ticket_type_1_id,
    ticket_type_2_id,
    ticket_type_3_id
FROM combinations
ORDER BY ABS(grand_total - 80000), event_1_id, event_2_id, event_3_id;

-- Afficher aussi les événements avec leurs prix pour référence
SELECT 
    e.id as event_id,
    e.title,
    e.slug,
    ec.label as category,
    MIN(tt.base_price) as prix_min,
    MAX(tt.base_price) as prix_max,
    AVG(tt.base_price) as prix_moyen,
    COUNT(tt.id) as nb_types_tickets
FROM aiolia.events e
JOIN aiolia.ticket_types tt ON tt.event_id = e.id
LEFT JOIN aiolia.event_categories ec ON ec.id = e.primary_category_id
WHERE e.status = 'published'
  AND e.slug NOT LIKE '%music%'
GROUP BY e.id, e.title, e.slug, ec.label
ORDER BY e.id;
