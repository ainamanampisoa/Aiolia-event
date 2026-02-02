-- ============================================================
-- Script pour supprimer toutes les données de Aina Fanelie
-- et créer 3 commandes avec des événements business/festival
-- qui donnent exactement 80 000 MGA SANS ajustement
-- ============================================================

\c aiolia_event;
SET search_path TO aiolia, public;

DO $$
DECLARE
    aina_user_id BIGINT;
    event_1_id BIGINT;
    event_2_id BIGINT;
    event_3_id BIGINT;
    ticket_type_1_id BIGINT;
    ticket_type_2_id BIGINT;
    ticket_type_3_id BIGINT;
    order_1_id BIGINT;
    order_2_id BIGINT;
    order_3_id BIGINT;
    order_item_1_id BIGINT;
    order_item_2_id BIGINT;
    order_item_3_id BIGINT;
    ticket_1_id BIGINT;
    ticket_2_id BIGINT;
    ticket_3_id BIGINT;
    ticket_4_id BIGINT;
    ticket_5_id BIGINT;
    ticket_6_id BIGINT;
    total_amount_1 NUMERIC;
    total_amount_2 NUMERIC;
    total_amount_3 NUMERIC;
    unit_price_1 NUMERIC;
    unit_price_2 NUMERIC;
    unit_price_3 NUMERIC;
    business_category_id BIGINT;
    festival_category_id BIGINT;
    old_ticket_type_ids BIGINT[];
    deleted_tickets_count INTEGER;
    deleted_orders_count INTEGER;
    deleted_ticket_chance_count INTEGER;
    found_combination BOOLEAN := FALSE;
BEGIN
    -- ============================================================
    -- Étape 1 : Trouver l'utilisateur Aina Fanelie
    -- ============================================================
    
    SELECT id INTO aina_user_id
    FROM aiolia.users
    WHERE LOWER(first_name) = 'aina' AND LOWER(last_name) = 'fanelie'
    LIMIT 1;
    
    IF aina_user_id IS NULL THEN
        RAISE EXCEPTION 'Utilisateur "Aina Fanelie" non trouvé.';
    END IF;
    
    RAISE NOTICE 'Utilisateur trouvé : ID = %', aina_user_id;
    
    -- ============================================================
    -- Étape 2 : SUPPRIMER TOUTES LES DONNÉES EXISTANTES
    -- ============================================================
    
    -- Récupérer les ticket_type_id des tickets existants avant suppression
    SELECT ARRAY_AGG(DISTINCT t.ticket_type_id) INTO old_ticket_type_ids
    FROM aiolia.tickets t
    WHERE t.owner_user_id = aina_user_id;
    
    -- Supprimer l'historique des notifications (d'abord, car il référence notifications)
    DELETE FROM aiolia.notification_history 
    WHERE notification_id IN (SELECT id FROM aiolia.notifications WHERE user_id = aina_user_id);
    
    -- Supprimer les notifications
    DELETE FROM aiolia.notifications WHERE user_id = aina_user_id;
    
    -- Supprimer les entrées Ticket Chance
    DELETE FROM aiolia.ticket_chance_entries WHERE user_id = aina_user_id;
    GET DIAGNOSTICS deleted_ticket_chance_count = ROW_COUNT;
    
    -- Supprimer les tickets
    DELETE FROM aiolia.tickets WHERE owner_user_id = aina_user_id;
    GET DIAGNOSTICS deleted_tickets_count = ROW_COUNT;
    
    -- Supprimer les order_items
    DELETE FROM aiolia.order_items WHERE order_id IN (SELECT id FROM aiolia.orders WHERE user_id = aina_user_id);
    
    -- Supprimer les commandes
    DELETE FROM aiolia.orders WHERE user_id = aina_user_id;
    GET DIAGNOSTICS deleted_orders_count = ROW_COUNT;
    
    -- Mettre à jour ticket_inventory pour décrémenter sold_quantity
    IF old_ticket_type_ids IS NOT NULL AND array_length(old_ticket_type_ids, 1) > 0 THEN
        UPDATE aiolia.ticket_inventory ti
        SET 
            sold_quantity = GREATEST(0, sold_quantity - (
                SELECT COUNT(*) 
                FROM aiolia.tickets t 
                WHERE t.ticket_type_id = ti.ticket_type_id 
                  AND t.owner_user_id = aina_user_id
            )),
            updated_at = NOW()
        WHERE ticket_type_id = ANY(old_ticket_type_ids);
        
        RAISE NOTICE 'Ticket inventory mis à jour (décrémenté) pour % types de tickets', array_length(old_ticket_type_ids, 1);
    END IF;
    
    RAISE NOTICE '✅ Données supprimées :';
    RAISE NOTICE '   - % commande(s)', deleted_orders_count;
    RAISE NOTICE '   - % ticket(s)', deleted_tickets_count;
    RAISE NOTICE '   - % entrée(s) Ticket Chance', deleted_ticket_chance_count;
    RAISE NOTICE '   - Toutes les notifications';
    
    -- ============================================================
    -- Étape 3 : Trouver les catégories business et festival
    -- ============================================================
    
    SELECT id INTO business_category_id
    FROM aiolia.event_categories
    WHERE LOWER(label) LIKE '%business%' OR LOWER(slug) LIKE '%business%'
    LIMIT 1;
    
    SELECT id INTO festival_category_id
    FROM aiolia.event_categories
    WHERE LOWER(label) LIKE '%festival%' OR LOWER(slug) LIKE '%festival%'
    LIMIT 1;
    
    IF business_category_id IS NULL THEN
        RAISE EXCEPTION 'Catégorie "business" non trouvée.';
    END IF;
    
    IF festival_category_id IS NULL THEN
        RAISE EXCEPTION 'Catégorie "festival" non trouvée.';
    END IF;
    
    RAISE NOTICE 'Catégories trouvées : Business = %, Festival = %', business_category_id, festival_category_id;
    
    -- ============================================================
    -- Étape 4 : Trouver 3 événements qui donnent exactement 80 000 MGA
    -- ============================================================
    
    -- Chercher une combinaison qui donne exactement 80 000 MGA (ou la plus proche)
    -- Event 1 (Business, 3 billets) + Event 2 (Festival, 2 billets) + Event 3 (1 billet) = 80 000
    
    SELECT 
        e1.id,
        tt1.id,
        tt1.base_price,
        e2.id,
        tt2.id,
        tt2.base_price,
        e3.id,
        tt3.id,
        tt3.base_price,
        ABS((tt1.base_price * 3 + tt2.base_price * 2 + tt3.base_price) - 80000) as diff
    INTO 
        event_1_id,
        ticket_type_1_id,
        unit_price_1,
        event_2_id,
        ticket_type_2_id,
        unit_price_2,
        event_3_id,
        ticket_type_3_id,
        unit_price_3
    FROM aiolia.events e1
    JOIN aiolia.ticket_types tt1 ON tt1.event_id = e1.id
    CROSS JOIN aiolia.events e2
    JOIN aiolia.ticket_types tt2 ON tt2.event_id = e2.id
    CROSS JOIN aiolia.events e3
    JOIN aiolia.ticket_types tt3 ON tt3.event_id = e3.id
    WHERE e1.status = 'published'
      AND e2.status = 'published'
      AND e3.status = 'published'
      AND e1.primary_category_id = business_category_id
      AND e2.primary_category_id = festival_category_id
      AND (e3.primary_category_id = business_category_id OR e3.primary_category_id = festival_category_id)
      AND e1.id != e2.id
      AND e1.id != e3.id
      AND e2.id != e3.id
      AND e1.slug NOT LIKE '%music%'
      AND e1.slug NOT LIKE '%sunday%'
      AND e2.slug NOT LIKE '%music%'
      AND e2.slug NOT LIKE '%sunday%'
      AND e3.slug NOT LIKE '%music%'
      AND e3.slug NOT LIKE '%sunday%'
    ORDER BY diff ASC
    LIMIT 1;
    
    IF event_1_id IS NULL THEN
        RAISE EXCEPTION 'Aucune combinaison d''événements trouvée (business/festival, sans Music on Sunday).';
    END IF;
    
    DECLARE
        calculated_total NUMERIC;
        difference NUMERIC;
    BEGIN
        calculated_total := (unit_price_1 * 3) + (unit_price_2 * 2) + unit_price_3;
        difference := ABS(calculated_total - 80000);
        
        IF difference < 0.01 THEN
            RAISE NOTICE 'Combinaison EXACTE trouvée : Total = % MGA', calculated_total;
        ELSE
            RAISE NOTICE 'Combinaison la plus proche trouvée : Total = % MGA (différence: % MGA)', 
                calculated_total, difference;
        END IF;
    END;
    
    IF event_1_id IS NULL OR event_2_id IS NULL OR event_3_id IS NULL THEN
        RAISE EXCEPTION 'Pas assez d''événements trouvés avec les catégories business/festival (sans Music on Sunday).';
    END IF;
    
    -- Calculer les montants finaux
    total_amount_1 := unit_price_1 * 3;
    total_amount_2 := unit_price_2 * 2;
    total_amount_3 := unit_price_3;
    
    RAISE NOTICE 'Événements sélectionnés (prix RÉELS, SANS ajustement) :';
    RAISE NOTICE '   - Event 1 = % (Business, prix: % MGA × 3 = % MGA)', 
        event_1_id, unit_price_1, total_amount_1;
    RAISE NOTICE '   - Event 2 = % (Festival, prix: % MGA × 2 = % MGA)', 
        event_2_id, unit_price_2, total_amount_2;
    RAISE NOTICE '   - Event 3 = % (Business/Festival, prix: % MGA × 1 = % MGA)', 
        event_3_id, unit_price_3, total_amount_3;
    RAISE NOTICE '   - TOTAL : % MGA', (total_amount_1 + total_amount_2 + total_amount_3);
    
    -- ============================================================
    -- Étape 5 : Créer les 3 commandes (année passée)
    -- ============================================================
    
    -- Commande 1 : 3 billets - il y a 10 mois
    INSERT INTO aiolia.orders (
        user_id,
        status,
        total_amount,
        discount_amount,
        currency,
        notes,
        created_at,
        updated_at
    )
    VALUES (
        aina_user_id,
        'paid',
        total_amount_1,
        0,
        'MGA',
        jsonb_build_object(
            'payment_method', 'mvola',
            'event_title', (SELECT title FROM aiolia.events WHERE id = event_1_id),
            'purchase_date', (NOW() - INTERVAL '10 months')::text,
            'ticket_quantity', 3,
            'unit_price', unit_price_1
        ),
        (NOW() - INTERVAL '10 months')::timestamptz,
        (NOW() - INTERVAL '10 months')::timestamptz
    )
    RETURNING id INTO order_1_id;
    
    -- Commande 2 : 2 billets - il y a 7 mois
    INSERT INTO aiolia.orders (
        user_id,
        status,
        total_amount,
        discount_amount,
        currency,
        notes,
        created_at,
        updated_at
    )
    VALUES (
        aina_user_id,
        'paid',
        total_amount_2,
        0,
        'MGA',
        jsonb_build_object(
            'payment_method', 'mvola',
            'event_title', (SELECT title FROM aiolia.events WHERE id = event_2_id),
            'purchase_date', (NOW() - INTERVAL '7 months')::text,
            'ticket_quantity', 2,
            'unit_price', unit_price_2
        ),
        (NOW() - INTERVAL '7 months')::timestamptz,
        (NOW() - INTERVAL '7 months')::timestamptz
    )
    RETURNING id INTO order_2_id;
    
    -- Commande 3 : 1 billet - il y a 4 mois
    INSERT INTO aiolia.orders (
        user_id,
        status,
        total_amount,
        discount_amount,
        currency,
        notes,
        created_at,
        updated_at
    )
    VALUES (
        aina_user_id,
        'paid',
        total_amount_3,
        0,
        'MGA',
        jsonb_build_object(
            'payment_method', 'mvola',
            'event_title', (SELECT title FROM aiolia.events WHERE id = event_3_id),
            'purchase_date', (NOW() - INTERVAL '4 months')::text,
            'ticket_quantity', 1,
            'unit_price', unit_price_3
        ),
        (NOW() - INTERVAL '4 months')::timestamptz,
        (NOW() - INTERVAL '4 months')::timestamptz
    )
    RETURNING id INTO order_3_id;
    
    RAISE NOTICE 'Commandes créées :';
    RAISE NOTICE '   - Order 1 = % (%, MGA pour 3 billets, il y a 10 mois)', order_1_id, total_amount_1;
    RAISE NOTICE '   - Order 2 = % (%, MGA pour 2 billets, il y a 7 mois)', order_2_id, total_amount_2;
    RAISE NOTICE '   - Order 3 = % (%, MGA pour 1 billet, il y a 4 mois)', order_3_id, total_amount_3;
    
    -- ============================================================
    -- Étape 6 : Créer les order_items
    -- ============================================================
    
    -- Order item 1 : 3 billets pour l'événement 1
    INSERT INTO aiolia.order_items (
        order_id,
        ticket_type_id,
        quantity,
        unit_price,
        service_fee,
        vat_amount,
        total_amount,
        created_at
    )
    VALUES (
        order_1_id,
        ticket_type_1_id,
        3,
        unit_price_1,
        0,
        0,
        total_amount_1,
        (NOW() - INTERVAL '10 months')::timestamptz
    )
    RETURNING id INTO order_item_1_id;
    
    -- Order item 2 : 2 billets pour l'événement 2
    INSERT INTO aiolia.order_items (
        order_id,
        ticket_type_id,
        quantity,
        unit_price,
        service_fee,
        vat_amount,
        total_amount,
        created_at
    )
    VALUES (
        order_2_id,
        ticket_type_2_id,
        2,
        unit_price_2,
        0,
        0,
        total_amount_2,
        (NOW() - INTERVAL '7 months')::timestamptz
    )
    RETURNING id INTO order_item_2_id;
    
    -- Order item 3 : 1 billet pour l'événement 3
    INSERT INTO aiolia.order_items (
        order_id,
        ticket_type_id,
        quantity,
        unit_price,
        service_fee,
        vat_amount,
        total_amount,
        created_at
    )
    VALUES (
        order_3_id,
        ticket_type_3_id,
        1,
        unit_price_3,
        0,
        0,
        total_amount_3,
        (NOW() - INTERVAL '4 months')::timestamptz
    )
    RETURNING id INTO order_item_3_id;
    
    RAISE NOTICE 'Order items créés : OI1 = % (3 billets), OI2 = % (2 billets), OI3 = % (1 billet)', 
        order_item_1_id, order_item_2_id, order_item_3_id;
    
    -- ============================================================
    -- Étape 7 : Créer les 6 tickets (3 + 2 + 1)
    -- ============================================================
    
    -- Tickets pour la commande 1 (3 billets - événement 1)
    INSERT INTO aiolia.tickets (
        order_item_id,
        ticket_type_id,
        owner_user_id,
        status,
        qr_code,
        qr_checksum,
        issued_at
    )
    VALUES (
        order_item_1_id,
        ticket_type_1_id,
        aina_user_id,
        'valid',
        'TICKET-' || ticket_type_1_id || '-' || aina_user_id || '-' || order_1_id || '-1-' || encode(gen_random_bytes(8), 'hex'),
        encode(digest('TICKET-' || ticket_type_1_id || '-' || aina_user_id || '-' || order_1_id || '-1', 'sha256'), 'hex'),
        (NOW() - INTERVAL '10 months')::timestamptz
    )
    RETURNING id INTO ticket_1_id;
    
    INSERT INTO aiolia.tickets (
        order_item_id,
        ticket_type_id,
        owner_user_id,
        status,
        qr_code,
        qr_checksum,
        issued_at
    )
    VALUES (
        order_item_1_id,
        ticket_type_1_id,
        aina_user_id,
        'valid',
        'TICKET-' || ticket_type_1_id || '-' || aina_user_id || '-' || order_1_id || '-2-' || encode(gen_random_bytes(8), 'hex'),
        encode(digest('TICKET-' || ticket_type_1_id || '-' || aina_user_id || '-' || order_1_id || '-2', 'sha256'), 'hex'),
        (NOW() - INTERVAL '10 months')::timestamptz
    )
    RETURNING id INTO ticket_2_id;
    
    INSERT INTO aiolia.tickets (
        order_item_id,
        ticket_type_id,
        owner_user_id,
        status,
        qr_code,
        qr_checksum,
        issued_at
    )
    VALUES (
        order_item_1_id,
        ticket_type_1_id,
        aina_user_id,
        'valid',
        'TICKET-' || ticket_type_1_id || '-' || aina_user_id || '-' || order_1_id || '-3-' || encode(gen_random_bytes(8), 'hex'),
        encode(digest('TICKET-' || ticket_type_1_id || '-' || aina_user_id || '-' || order_1_id || '-3', 'sha256'), 'hex'),
        (NOW() - INTERVAL '10 months')::timestamptz
    )
    RETURNING id INTO ticket_3_id;
    
    -- Tickets pour la commande 2 (2 billets - événement 2)
    INSERT INTO aiolia.tickets (
        order_item_id,
        ticket_type_id,
        owner_user_id,
        status,
        qr_code,
        qr_checksum,
        issued_at
    )
    VALUES (
        order_item_2_id,
        ticket_type_2_id,
        aina_user_id,
        'valid',
        'TICKET-' || ticket_type_2_id || '-' || aina_user_id || '-' || order_2_id || '-1-' || encode(gen_random_bytes(8), 'hex'),
        encode(digest('TICKET-' || ticket_type_2_id || '-' || aina_user_id || '-' || order_2_id || '-1', 'sha256'), 'hex'),
        (NOW() - INTERVAL '7 months')::timestamptz
    )
    RETURNING id INTO ticket_4_id;
    
    INSERT INTO aiolia.tickets (
        order_item_id,
        ticket_type_id,
        owner_user_id,
        status,
        qr_code,
        qr_checksum,
        issued_at
    )
    VALUES (
        order_item_2_id,
        ticket_type_2_id,
        aina_user_id,
        'valid',
        'TICKET-' || ticket_type_2_id || '-' || aina_user_id || '-' || order_2_id || '-2-' || encode(gen_random_bytes(8), 'hex'),
        encode(digest('TICKET-' || ticket_type_2_id || '-' || aina_user_id || '-' || order_2_id || '-2', 'sha256'), 'hex'),
        (NOW() - INTERVAL '7 months')::timestamptz
    )
    RETURNING id INTO ticket_5_id;
    
    -- Ticket pour la commande 3 (1 billet - événement 3)
    INSERT INTO aiolia.tickets (
        order_item_id,
        ticket_type_id,
        owner_user_id,
        status,
        qr_code,
        qr_checksum,
        issued_at
    )
    VALUES (
        order_item_3_id,
        ticket_type_3_id,
        aina_user_id,
        'valid',
        'TICKET-' || ticket_type_3_id || '-' || aina_user_id || '-' || order_3_id || '-1-' || encode(gen_random_bytes(8), 'hex'),
        encode(digest('TICKET-' || ticket_type_3_id || '-' || aina_user_id || '-' || order_3_id || '-1', 'sha256'), 'hex'),
        (NOW() - INTERVAL '4 months')::timestamptz
    )
    RETURNING id INTO ticket_6_id;
    
    RAISE NOTICE 'Tickets créés : 6 tickets (3 + 2 + 1)';
    
    -- ============================================================
    -- Étape 8 : Mettre à jour les dates des événements pour qu'ils soient passés
    -- ============================================================
    
    -- Événement 1 : Il y a 10 mois
    UPDATE aiolia.events
    SET 
        starts_at = (NOW() - INTERVAL '10 months')::timestamptz,
        ends_at = (NOW() - INTERVAL '10 months' + INTERVAL '3 hours')::timestamptz,
        updated_at = NOW()
    WHERE id = event_1_id;
    
    -- Événement 2 : Il y a 7 mois
    UPDATE aiolia.events
    SET 
        starts_at = (NOW() - INTERVAL '7 months')::timestamptz,
        ends_at = (NOW() - INTERVAL '7 months' + INTERVAL '3 hours')::timestamptz,
        updated_at = NOW()
    WHERE id = event_2_id;
    
    -- Événement 3 : Il y a 4 mois
    UPDATE aiolia.events
    SET 
        starts_at = (NOW() - INTERVAL '4 months')::timestamptz,
        ends_at = (NOW() - INTERVAL '4 months' + INTERVAL '3 hours')::timestamptz,
        updated_at = NOW()
    WHERE id = event_3_id;
    
    RAISE NOTICE 'Dates des événements mises à jour pour qu''ils soient passés (l''année passée)';
    
    -- ============================================================
    -- Étape 9 : Mettre à jour ticket_inventory pour les statistiques
    -- ============================================================
    
    -- Mettre à jour ou insérer dans ticket_inventory pour le ticket_type_1
    INSERT INTO aiolia.ticket_inventory (
        ticket_type_id,
        total_quantity,
        reserved_quantity,
        sold_quantity,
        updated_at
    )
    VALUES (
        ticket_type_1_id,
        100,
        0,
        3, -- 3 billets vendus
        NOW()
    )
    ON CONFLICT (ticket_type_id) 
    DO UPDATE SET 
        sold_quantity = aiolia.ticket_inventory.sold_quantity + 3,
        updated_at = NOW();
    
    -- Mettre à jour ou insérer dans ticket_inventory pour le ticket_type_2
    INSERT INTO aiolia.ticket_inventory (
        ticket_type_id,
        total_quantity,
        reserved_quantity,
        sold_quantity,
        updated_at
    )
    VALUES (
        ticket_type_2_id,
        100,
        0,
        2, -- 2 billets vendus
        NOW()
    )
    ON CONFLICT (ticket_type_id) 
    DO UPDATE SET 
        sold_quantity = aiolia.ticket_inventory.sold_quantity + 2,
        updated_at = NOW();
    
    -- Mettre à jour ou insérer dans ticket_inventory pour le ticket_type_3
    INSERT INTO aiolia.ticket_inventory (
        ticket_type_id,
        total_quantity,
        reserved_quantity,
        sold_quantity,
        updated_at
    )
    VALUES (
        ticket_type_3_id,
        100,
        0,
        1, -- 1 billet vendu
        NOW()
    )
    ON CONFLICT (ticket_type_id) 
    DO UPDATE SET 
        sold_quantity = aiolia.ticket_inventory.sold_quantity + 1,
        updated_at = NOW();
    
    RAISE NOTICE 'Ticket inventory mis à jour pour les statistiques';
    
    RAISE NOTICE '';
    RAISE NOTICE '✅ Données créées avec succès pour Aina Fanelie !';
    RAISE NOTICE '   - Anciennes données supprimées';
    RAISE NOTICE '   - 3 commandes créées avec événements business/festival (SANS Music on Sunday)';
    RAISE NOTICE '   - Commande 1 : % MGA (3 billets × % MGA, Business, il y a 10 mois)', total_amount_1, unit_price_1;
    RAISE NOTICE '   - Commande 2 : % MGA (2 billets × % MGA, Festival, il y a 7 mois)', total_amount_2, unit_price_2;
    RAISE NOTICE '   - Commande 3 : % MGA (1 billet × % MGA, il y a 4 mois)', total_amount_3, unit_price_3;
    RAISE NOTICE '   - Total : % MGA (PRIX RÉELS, SANS ajustement)', (total_amount_1 + total_amount_2 + total_amount_3);
    RAISE NOTICE '   - Dates des événements mises à jour (passés, l''année passée)';
    RAISE NOTICE '   - Ticket inventory mis à jour pour les statistiques globales';
    
END $$;

-- ============================================================
-- Vérification des données créées
-- ============================================================

SELECT 
    u.id as user_id,
    u.first_name || ' ' || u.last_name as nom_complet,
    u.email,
    COUNT(DISTINCT o.id) as nombre_achats,
    SUM(o.total_amount) as total_achete_mga,
    MIN(o.created_at) as premier_achat,
    MAX(o.created_at) as dernier_achat,
    COUNT(DISTINCT t.id) as nombre_tickets
FROM aiolia.users u
JOIN aiolia.orders o ON o.user_id = u.id
LEFT JOIN aiolia.order_items oi ON oi.order_id = o.id
LEFT JOIN aiolia.tickets t ON t.order_item_id = oi.id
WHERE LOWER(u.first_name) = 'aina' AND LOWER(u.last_name) = 'fanelie'
GROUP BY u.id, u.first_name, u.last_name, u.email;

-- Détails des commandes avec catégories et prix réels
SELECT 
    o.id as order_id,
    o.status,
    o.total_amount,
    o.currency,
    o.created_at as date_achat,
    e.title as evenement,
    ec.label as categorie,
    ec.slug as category_slug,
    oi.quantity as quantite,
    oi.unit_price as prix_unitaire,
    tt.name as type_ticket,
    e.starts_at as date_evenement
FROM aiolia.users u
JOIN aiolia.orders o ON o.user_id = u.id
JOIN aiolia.order_items oi ON oi.order_id = o.id
JOIN aiolia.ticket_types tt ON tt.id = oi.ticket_type_id
JOIN aiolia.events e ON e.id = tt.event_id
LEFT JOIN aiolia.event_categories ec ON ec.id = e.primary_category_id
WHERE LOWER(u.first_name) = 'aina' AND LOWER(u.last_name) = 'fanelie'
ORDER BY o.created_at DESC;
