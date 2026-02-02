-- ============================================================
-- Script pour supprimer toutes les données de Aina Fanelie
-- et recréer 3 commandes avec 3, 2 et 1 billets
-- Utilise les événements RÉELS passés avec leurs catégories et prix
-- Catégories : business et festival
-- Total : 80 000 MGA pour l'année passée
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
    event_1_price NUMERIC;
    event_2_price NUMERIC;
    event_3_price NUMERIC;
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
        RAISE EXCEPTION 'Catégorie "business" non trouvée. Vérifiez les catégories disponibles.';
    END IF;
    
    IF festival_category_id IS NULL THEN
        RAISE EXCEPTION 'Catégorie "festival" non trouvée. Vérifiez les catégories disponibles.';
    END IF;
    
    RAISE NOTICE 'Catégories trouvées : Business = %, Festival = %', business_category_id, festival_category_id;
    
    -- ============================================================
    -- Étape 4 : Trouver 3 événements (peu importe leur date) avec leurs prix RÉELS
    -- On les mettra à jour pour qu'ils soient passés après
    -- ============================================================
    
    -- Événement 1 : Business (3 billets)
    SELECT 
        e.id,
        MIN(tt.id),
        MIN(tt.base_price)
    INTO event_1_id, ticket_type_1_id, event_1_price
    FROM aiolia.events e
    JOIN aiolia.ticket_types tt ON tt.event_id = e.id
    WHERE e.status = 'published'
      AND e.primary_category_id = business_category_id
    GROUP BY e.id
    ORDER BY e.id ASC
    LIMIT 1;
    
    -- Si pas trouvé, prendre n'importe quel événement business
    IF event_1_id IS NULL THEN
        SELECT 
            e.id,
            MIN(tt.id),
            MIN(tt.base_price)
        INTO event_1_id, ticket_type_1_id, event_1_price
        FROM aiolia.events e
        JOIN aiolia.ticket_types tt ON tt.event_id = e.id
        WHERE e.status = 'published'
        GROUP BY e.id
        ORDER BY e.id ASC
        LIMIT 1;
    END IF;
    
    -- Événement 2 : Festival (2 billets)
    SELECT 
        e.id,
        MIN(tt.id),
        MIN(tt.base_price)
    INTO event_2_id, ticket_type_2_id, event_2_price
    FROM aiolia.events e
    JOIN aiolia.ticket_types tt ON tt.event_id = e.id
    WHERE e.status = 'published'
      AND e.primary_category_id = festival_category_id
      AND e.id != COALESCE(event_1_id, 0)
    GROUP BY e.id
    ORDER BY e.id ASC
    LIMIT 1;
    
    -- Si pas trouvé, prendre un autre événement
    IF event_2_id IS NULL THEN
        SELECT 
            e.id,
            MIN(tt.id),
            MIN(tt.base_price)
        INTO event_2_id, ticket_type_2_id, event_2_price
        FROM aiolia.events e
        JOIN aiolia.ticket_types tt ON tt.event_id = e.id
        WHERE e.status = 'published'
          AND e.id != COALESCE(event_1_id, 0)
        GROUP BY e.id
        ORDER BY e.id ASC
        LIMIT 1;
    END IF;
    
    -- Événement 3 : Business ou Festival (1 billet)
    SELECT 
        e.id,
        MIN(tt.id),
        MIN(tt.base_price)
    INTO event_3_id, ticket_type_3_id, event_3_price
    FROM aiolia.events e
    JOIN aiolia.ticket_types tt ON tt.event_id = e.id
    WHERE e.status = 'published'
      AND (e.primary_category_id = business_category_id OR e.primary_category_id = festival_category_id)
      AND e.id != COALESCE(event_1_id, 0)
      AND e.id != COALESCE(event_2_id, 0)
    GROUP BY e.id
    ORDER BY e.id ASC
    LIMIT 1;
    
    -- Si pas trouvé, prendre n'importe quel autre événement
    IF event_3_id IS NULL THEN
        SELECT 
            e.id,
            MIN(tt.id),
            MIN(tt.base_price)
        INTO event_3_id, ticket_type_3_id, event_3_price
        FROM aiolia.events e
        JOIN aiolia.ticket_types tt ON tt.event_id = e.id
        WHERE e.status = 'published'
          AND e.id != COALESCE(event_1_id, 0)
          AND e.id != COALESCE(event_2_id, 0)
        GROUP BY e.id
        ORDER BY e.id ASC
        LIMIT 1;
    END IF;
    
    IF event_1_id IS NULL OR event_2_id IS NULL OR event_3_id IS NULL THEN
        RAISE EXCEPTION 'Pas assez d''événements trouvés avec les catégories business/festival.';
    END IF;
    
    RAISE NOTICE 'Événements sélectionnés :';
    RAISE NOTICE '   - Event 1 = % (Business, prix: % MGA)', event_1_id, event_1_price;
    RAISE NOTICE '   - Event 2 = % (Festival, prix: % MGA)', event_2_id, event_2_price;
    RAISE NOTICE '   - Event 3 = % (Business/Festival, prix: % MGA)', event_3_id, event_3_price;
    
    -- ============================================================
    -- Étape 5 : Calculer les montants avec les PRIX RÉELS des événements
    -- ============================================================
    
    -- Utiliser les prix réels des événements
    total_amount_1 := event_1_price * 3;  -- 3 billets au prix réel
    unit_price_1 := event_1_price;
    
    total_amount_2 := event_2_price * 2;  -- 2 billets au prix réel
    unit_price_2 := event_2_price;
    
    total_amount_3 := event_3_price;      -- 1 billet au prix réel
    unit_price_3 := event_3_price;
    
    -- Calculer le total réel
    DECLARE
        real_total NUMERIC := total_amount_1 + total_amount_2 + total_amount_3;
        target_total NUMERIC := 80000;
        adjustment_needed NUMERIC;
    BEGIN
        adjustment_needed := target_total - real_total;
        
        RAISE NOTICE 'Prix réels des événements :';
        RAISE NOTICE '   - Event 1 : % MGA × 3 = % MGA', event_1_price, total_amount_1;
        RAISE NOTICE '   - Event 2 : % MGA × 2 = % MGA', event_2_price, total_amount_2;
        RAISE NOTICE '   - Event 3 : % MGA × 1 = % MGA', event_3_price, total_amount_3;
        RAISE NOTICE '   - Total réel : % MGA', real_total;
        RAISE NOTICE '   - Ajustement nécessaire : % MGA', adjustment_needed;
        
        -- Si un ajustement est nécessaire pour arriver à 80 000, l'appliquer proportionnellement
        IF ABS(adjustment_needed) > 0.01 THEN
            DECLARE
                ratio NUMERIC;
                new_total_1 NUMERIC;
                new_total_2 NUMERIC;
                new_total_3 NUMERIC;
                final_total NUMERIC;
                final_adjust NUMERIC;
            BEGIN
                -- Calculer le ratio pour arriver à 80 000
                ratio := target_total / real_total;
                
                -- Appliquer le ratio proportionnellement
                new_total_1 := ROUND(total_amount_1 * ratio);
                new_total_2 := ROUND(total_amount_2 * ratio);
                new_total_3 := ROUND(total_amount_3 * ratio);
                
                -- Vérifier que les montants sont positifs
                IF new_total_1 <= 0 THEN new_total_1 := 1000; END IF;
                IF new_total_2 <= 0 THEN new_total_2 := 1000; END IF;
                IF new_total_3 <= 0 THEN new_total_3 := 1000; END IF;
                
                final_total := new_total_1 + new_total_2 + new_total_3;
                final_adjust := target_total - final_total;
                
                -- Ajuster finement pour arriver exactement à 80 000
                IF ABS(final_adjust) > 0.01 THEN
                    -- Ajuster la commande la plus importante
                    new_total_1 := new_total_1 + final_adjust;
                    IF new_total_1 <= 0 THEN
                        new_total_1 := 1000;
                        new_total_2 := new_total_2 + final_adjust;
                        IF new_total_2 <= 0 THEN
                            new_total_2 := 1000;
                            new_total_3 := new_total_3 + final_adjust;
                        END IF;
                    END IF;
                END IF;
                
                total_amount_1 := new_total_1;
                total_amount_2 := new_total_2;
                total_amount_3 := new_total_3;
                
                -- Recalculer les prix unitaires
                unit_price_1 := ROUND(total_amount_1 / 3, 2);
                unit_price_2 := ROUND(total_amount_2 / 2, 2);
                unit_price_3 := total_amount_3;
                
                RAISE NOTICE 'Ajustement appliqué proportionnellement';
            END;
        END IF;
        
        RAISE NOTICE 'Montants finaux :';
        RAISE NOTICE '   - Commande 1 : % MGA (3 billets × % MGA)', total_amount_1, unit_price_1;
        RAISE NOTICE '   - Commande 2 : % MGA (2 billets × % MGA)', total_amount_2, unit_price_2;
        RAISE NOTICE '   - Commande 3 : % MGA (1 billet × % MGA)', total_amount_3, unit_price_3;
        RAISE NOTICE '   - Total : % MGA', (total_amount_1 + total_amount_2 + total_amount_3);
    END;
    
    RAISE NOTICE 'Montants calculés :';
    RAISE NOTICE '   - Commande 1 : % MGA (3 billets × % MGA)', total_amount_1, unit_price_1;
    RAISE NOTICE '   - Commande 2 : % MGA (2 billets × % MGA)', total_amount_2, unit_price_2;
    RAISE NOTICE '   - Commande 3 : % MGA (1 billet)', total_amount_3;
    RAISE NOTICE '   - Total : % MGA', (total_amount_1 + total_amount_2 + total_amount_3);
    
    -- ============================================================
    -- Étape 6 : Créer les 3 commandes (année passée)
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
    -- Étape 7 : Créer les order_items
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
    -- Étape 8 : Créer les 6 tickets (3 + 2 + 1)
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
    -- Étape 9 : Mettre à jour les dates des événements pour qu'ils soient passés
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
    
    RAISE NOTICE 'Dates des événements mises à jour pour qu''ils soient passés';
    
    -- ============================================================
    -- Étape 10 : Mettre à jour ticket_inventory pour les statistiques
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
    RAISE NOTICE '✅ Données réinitialisées avec succès pour Aina Fanelie !';
    RAISE NOTICE '   - Anciennes données supprimées';
    RAISE NOTICE '   - 3 commandes créées avec événements RÉELS passés';
    RAISE NOTICE '   - Commande 1 : % MGA (3 billets × % MGA, Business, il y a 10 mois)', total_amount_1, unit_price_1;
    RAISE NOTICE '   - Commande 2 : % MGA (2 billets × % MGA, Festival, il y a 7 mois)', total_amount_2, unit_price_2;
    RAISE NOTICE '   - Commande 3 : % MGA (1 billet, il y a 4 mois)', total_amount_3;
    RAISE NOTICE '   - Total : % MGA', (total_amount_1 + total_amount_2 + total_amount_3);
    RAISE NOTICE '   - Dates des événements mises à jour (passés)';
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
