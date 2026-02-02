-- ============================================================
-- Script pour mettre à jour "Music on Sunday" pour demain
-- et changer les types de billets en VIP, Silver, Gold
-- ============================================================

\c aiolia_event;
SET search_path TO aiolia, public;

DO $$
DECLARE
    music_sunday_id BIGINT;
    tomorrow_start TIMESTAMPTZ;
    tomorrow_end TIMESTAMPTZ;
    event_duration INTERVAL;
    sales_start TIMESTAMPTZ;
    sales_end TIMESTAMPTZ;
    vip_ticket_id BIGINT;
    silver_ticket_id BIGINT;
    gold_ticket_id BIGINT;
BEGIN
    -- Trouver l'événement "Music on Sunday"
    SELECT id, sales_starts_at, sales_ends_at INTO music_sunday_id, sales_start, sales_end
    FROM aiolia.events
    WHERE LOWER(title) LIKE '%music on sunday%'
       OR LOWER(slug) LIKE '%music-sunday%'
    LIMIT 1;
    
    IF music_sunday_id IS NULL THEN
        RAISE EXCEPTION 'Événement "Music on Sunday" non trouvé';
    END IF;
    
    -- Calculer demain à la même heure (ou à 14h00 par défaut)
    SELECT 
        CASE 
            WHEN starts_at > NOW() THEN 
                -- Si l'événement est dans le futur, on garde l'heure
                DATE_TRUNC('day', NOW() + INTERVAL '1 day') + (starts_at::TIME)
            ELSE 
                -- Sinon, on met 14h00 demain
                DATE_TRUNC('day', NOW() + INTERVAL '1 day') + INTERVAL '14 hours'
        END
    INTO tomorrow_start
    FROM aiolia.events
    WHERE id = music_sunday_id;
    
    -- Calculer la durée de l'événement
    SELECT ends_at - starts_at INTO event_duration
    FROM aiolia.events
    WHERE id = music_sunday_id;
    
    -- Si pas de durée trouvée, mettre 3 heures par défaut
    IF event_duration IS NULL THEN
        event_duration := INTERVAL '3 hours';
    END IF;
    
    -- Calculer la fin de l'événement
    tomorrow_end := tomorrow_start + event_duration;
    
    -- Mettre à jour les dates de vente si nécessaire
    IF sales_start IS NULL OR sales_start > tomorrow_start THEN
        sales_start := NOW();
    END IF;
    IF sales_end IS NULL OR sales_end > tomorrow_start THEN
        sales_end := tomorrow_start;
    END IF;
    
    -- Mettre à jour l'événement
    UPDATE aiolia.events
    SET 
        starts_at = tomorrow_start,
        ends_at = tomorrow_end,
        sales_starts_at = sales_start,
        sales_ends_at = sales_end,
        updated_at = NOW()
    WHERE id = music_sunday_id;
    
    RAISE NOTICE '✅ Événement "Music on Sunday" mis à jour pour demain';
    RAISE NOTICE '   - ID: %', music_sunday_id;
    RAISE NOTICE '   - Début: %', tomorrow_start;
    RAISE NOTICE '   - Fin: %', tomorrow_end;
    RAISE NOTICE '   - Durée: %', event_duration;
    
    -- ============================================================
    -- Supprimer les anciens types de billets (s'ils existent)
    -- ============================================================
    
    -- D'abord, supprimer toutes les dépendances dans l'ordre inverse
    -- 1. Supprimer les pricing_rules
    DELETE FROM aiolia.pricing_rules
    WHERE ticket_type_id IN (
        SELECT id FROM aiolia.ticket_types WHERE event_id = music_sunday_id
    );
    
    -- 2. Supprimer les cart_items (paniers en cours)
    DELETE FROM aiolia.cart_items
    WHERE ticket_type_id IN (
        SELECT id FROM aiolia.ticket_types WHERE event_id = music_sunday_id
    );
    
    -- 3. Supprimer l'inventaire associé
    DELETE FROM aiolia.ticket_inventory
    WHERE ticket_type_id IN (
        SELECT id FROM aiolia.ticket_types WHERE event_id = music_sunday_id
    );
    
    -- Note: On ne supprime PAS order_items, tickets, ticket_price_history
    -- car ils font partie de l'historique des commandes
    
    -- 3. Supprimer les order_items (si nécessaire, mais attention aux commandes existantes)
    -- On ne supprime pas les order_items car ils font partie de l'historique des commandes
    
    -- 4. Ensuite, supprimer les types de billets
    DELETE FROM aiolia.ticket_types
    WHERE event_id = music_sunday_id;
    
    RAISE NOTICE '✅ Anciens types de billets supprimés';
    
    -- ============================================================
    -- Créer les nouveaux types de billets : VIP, Silver, Gold
    -- ============================================================
    
    -- VIP (le plus cher)
    INSERT INTO aiolia.ticket_types (
        event_id, name, description, currency,
        base_price, service_fee, vat_rate, age_category,
        sales_start, sales_end, min_per_order, max_per_order,
        metadata
    )
    VALUES (
        music_sunday_id,
        'VIP',
        'Billet VIP - Accès prioritaire, zone VIP exclusive, parking réservé et boissons offertes.',
        'MGA',
        100000,  -- Prix de base
        5000,    -- Frais de service (5%)
        20,      -- TVA (20%)
        'all'::age_category_enum,
        sales_start,
        sales_end,
        1,       -- Minimum par commande
        5,       -- Maximum par commande
        '{"vip":true,"priority_access":true,"exclusive_area":true,"parking":true,"drinks":true}'::jsonb
    )
    RETURNING id INTO vip_ticket_id;
    
    -- Silver (prix moyen)
    INSERT INTO aiolia.ticket_types (
        event_id, name, description, currency,
        base_price, service_fee, vat_rate, age_category,
        sales_start, sales_end, min_per_order, max_per_order,
        metadata
    )
    VALUES (
        music_sunday_id,
        'Silver',
        'Billet Silver - Meilleur placement, avantages premium.',
        'MGA',
        75000,   -- Prix de base
        3750,    -- Frais de service (5%)
        20,      -- TVA (20%)
        'all'::age_category_enum,
        sales_start,
        sales_end,
        1,       -- Minimum par commande
        10,      -- Maximum par commande
        '{"silver":true,"premium_seating":true}'::jsonb
    )
    RETURNING id INTO silver_ticket_id;
    
    -- Gold (prix intermédiaire)
    INSERT INTO aiolia.ticket_types (
        event_id, name, description, currency,
        base_price, service_fee, vat_rate, age_category,
        sales_start, sales_end, min_per_order, max_per_order,
        metadata
    )
    VALUES (
        music_sunday_id,
        'Gold',
        'Billet Gold - Placement premium avec vue optimale.',
        'MGA',
        85000,   -- Prix de base
        4250,    -- Frais de service (5%)
        20,      -- TVA (20%)
        'all'::age_category_enum,
        sales_start,
        sales_end,
        1,       -- Minimum par commande
        8,       -- Maximum par commande
        '{"gold":true,"premium_seating":true,"optimal_view":true}'::jsonb
    )
    RETURNING id INTO gold_ticket_id;
    
    RAISE NOTICE '✅ Nouveaux types de billets créés :';
    RAISE NOTICE '   - VIP (ID: %, Prix: 100,000 MGA)', vip_ticket_id;
    RAISE NOTICE '   - Silver (ID: %, Prix: 75,000 MGA)', silver_ticket_id;
    RAISE NOTICE '   - Gold (ID: %, Prix: 85,000 MGA)', gold_ticket_id;
    
    -- ============================================================
    -- Créer l'inventaire pour chaque type de billet
    -- ============================================================
    
    -- Inventaire VIP (50 billets)
    INSERT INTO aiolia.ticket_inventory (ticket_type_id, total_quantity, reserved_quantity, sold_quantity)
    VALUES (vip_ticket_id, 50, 0, 0)
    ON CONFLICT (ticket_type_id) DO UPDATE
        SET total_quantity = 50,
            reserved_quantity = 0,
            sold_quantity = 0,
            updated_at = NOW();
    
    -- Inventaire Silver (150 billets)
    INSERT INTO aiolia.ticket_inventory (ticket_type_id, total_quantity, reserved_quantity, sold_quantity)
    VALUES (silver_ticket_id, 150, 0, 0)
    ON CONFLICT (ticket_type_id) DO UPDATE
        SET total_quantity = 150,
            reserved_quantity = 0,
            sold_quantity = 0,
            updated_at = NOW();
    
    -- Inventaire Gold (100 billets)
    INSERT INTO aiolia.ticket_inventory (ticket_type_id, total_quantity, reserved_quantity, sold_quantity)
    VALUES (gold_ticket_id, 100, 0, 0)
    ON CONFLICT (ticket_type_id) DO UPDATE
        SET total_quantity = 100,
            reserved_quantity = 0,
            sold_quantity = 0,
            updated_at = NOW();
    
    RAISE NOTICE '✅ Inventaire créé :';
    RAISE NOTICE '   - VIP: 50 billets disponibles';
    RAISE NOTICE '   - Silver: 150 billets disponibles';
    RAISE NOTICE '   - Gold: 100 billets disponibles';
    
END $$;

-- ============================================================
-- Vérification de l'événement
-- ============================================================

SELECT 
    id,
    title,
    slug,
    starts_at,
    ends_at,
    starts_at AT TIME ZONE 'Indian/Antananarivo' as starts_at_eat,
    ends_at AT TIME ZONE 'Indian/Antananarivo' as ends_at_eat,
    NOW() AT TIME ZONE 'Indian/Antananarivo' as current_time_eat,
    EXTRACT(EPOCH FROM (starts_at - NOW())) / 3600 as hours_until_start
FROM aiolia.events
WHERE LOWER(title) LIKE '%music on sunday%'
   OR LOWER(slug) LIKE '%music-sunday%';

-- ============================================================
-- Vérification des types de billets
-- ============================================================

SELECT 
    tt.id,
    tt.name,
    tt.base_price,
    tt.service_fee,
    tt.vat_rate,
    tt.age_category,
    ti.total_quantity,
    ti.sold_quantity,
    (ti.total_quantity - ti.sold_quantity - ti.reserved_quantity) as available_quantity
FROM aiolia.ticket_types tt
LEFT JOIN aiolia.ticket_inventory ti ON ti.ticket_type_id = tt.id
JOIN aiolia.events e ON e.id = tt.event_id
WHERE (LOWER(e.title) LIKE '%music on sunday%' OR LOWER(e.slug) LIKE '%music-sunday%')
ORDER BY tt.base_price DESC;
