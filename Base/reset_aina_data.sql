-- ============================================================
-- Script pour supprimer toutes les données de Aina Fanelie
-- et recréer 3 commandes avec 3, 2 et 1 billets
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
        -- Compter combien de tickets par type ont été supprimés
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
    WHERE LOWER(name) LIKE '%business%' OR LOWER(slug) LIKE '%business%'
    LIMIT 1;
    
    SELECT id INTO festival_category_id
    FROM aiolia.event_categories
    WHERE LOWER(name) LIKE '%festival%' OR LOWER(slug) LIKE '%festival%'
    LIMIT 1;
    
    -- Si les catégories n'existent pas, prendre les premières catégories disponibles
    IF business_category_id IS NULL THEN
        SELECT id INTO business_category_id
        FROM aiolia.event_categories
        ORDER BY id ASC
        LIMIT 1;
    END IF;
    
    IF festival_category_id IS NULL THEN
        SELECT id INTO festival_category_id
        FROM aiolia.event_categories
        WHERE id != COALESCE(business_category_id, 0)
        ORDER BY id ASC
        LIMIT 1;
    END IF;
    
    IF business_category_id IS NULL OR festival_category_id IS NULL THEN
        RAISE NOTICE 'ATTENTION: Catégories business/festival non trouvées, utilisation de catégories par défaut';
    END IF;
    
    RAISE NOTICE 'Catégories sélectionnées : Business = %, Festival = %', business_category_id, festival_category_id;
    
    -- ============================================================
    -- Étape 4 : Trouver 3 événements (business et festival)
    -- ============================================================
    
    -- Événement 1 : Business (3 billets)
    SELECT id INTO event_1_id
    FROM aiolia.events
    WHERE status = 'published'
      AND primary_category_id = business_category_id
    ORDER BY id ASC
    LIMIT 1;
    
    -- Si pas trouvé, prendre n'importe quel événement
    IF event_1_id IS NULL THEN
        SELECT id INTO event_1_id
        FROM aiolia.events
        WHERE status = 'published'
        ORDER BY id ASC
        LIMIT 1;
    END IF;
    
    -- Événement 2 : Festival (2 billets)
    SELECT id INTO event_2_id
    FROM aiolia.events
    WHERE status = 'published'
      AND primary_category_id = festival_category_id
      AND id != COALESCE(event_1_id, 0)
    ORDER BY id ASC
    LIMIT 1;
    
    -- Si pas trouvé, prendre un autre événement
    IF event_2_id IS NULL THEN
        SELECT id INTO event_2_id
        FROM aiolia.events
        WHERE status = 'published'
          AND id != COALESCE(event_1_id, 0)
        ORDER BY id ASC
        LIMIT 1;
    END IF;
    
    -- Événement 3 : Business ou Festival (1 billet)
    SELECT id INTO event_3_id
    FROM aiolia.events
    WHERE status = 'published'
      AND (primary_category_id = business_category_id OR primary_category_id = festival_category_id)
      AND id != COALESCE(event_1_id, 0)
      AND id != COALESCE(event_2_id, 0)
    ORDER BY id ASC
    LIMIT 1;
    
    -- Si pas trouvé, prendre n'importe quel autre événement
    IF event_3_id IS NULL THEN
        SELECT id INTO event_3_id
        FROM aiolia.events
        WHERE status = 'published'
          AND id != COALESCE(event_1_id, 0)
          AND id != COALESCE(event_2_id, 0)
        ORDER BY id ASC
        LIMIT 1;
    END IF;
    
    IF event_1_id IS NULL OR event_2_id IS NULL OR event_3_id IS NULL THEN
        RAISE EXCEPTION 'Pas assez d''événements trouvés dans la base de données.';
    END IF;
    
    RAISE NOTICE 'Événements sélectionnés : Event 1 = %, Event 2 = %, Event 3 = %', event_1_id, event_2_id, event_3_id;
    
    -- ============================================================
    -- Étape 5 : Trouver des types de tickets pour ces événements
    -- ============================================================
    
    -- Ticket type pour événement 1 (3 billets - ~40 000 MGA)
    SELECT id INTO ticket_type_1_id
    FROM aiolia.ticket_types
    WHERE event_id = event_1_id
    ORDER BY base_price ASC
    LIMIT 1;
    
    -- Ticket type pour événement 2 (2 billets - ~26 666 MGA)
    SELECT id INTO ticket_type_2_id
    FROM aiolia.ticket_types
    WHERE event_id = event_2_id
    ORDER BY base_price ASC
    LIMIT 1;
    
    -- Ticket type pour événement 3 (1 billet - ~13 334 MGA)
    SELECT id INTO ticket_type_3_id
    FROM aiolia.ticket_types
    WHERE event_id = event_3_id
    ORDER BY base_price ASC
    LIMIT 1;
    
    IF ticket_type_1_id IS NULL OR ticket_type_2_id IS NULL OR ticket_type_3_id IS NULL THEN
        RAISE EXCEPTION 'Pas de types de tickets trouvés pour les événements sélectionnés.';
    END IF;
    
    RAISE NOTICE 'Types de tickets sélectionnés : TT1 = %, TT2 = %, TT3 = %', ticket_type_1_id, ticket_type_2_id, ticket_type_3_id;
    
    -- ============================================================
    -- Étape 6 : Créer les 3 commandes (année passée)
    -- ============================================================
    
    -- Commande 1 : 3 billets - 40 000 MGA (il y a 10 mois)
    total_amount_1 := 40000;
    unit_price_1 := 13333; -- ~13 333 MGA par billet
    
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
            'ticket_quantity', 3
        ),
        (NOW() - INTERVAL '10 months')::timestamptz,
        (NOW() - INTERVAL '10 months')::timestamptz
    )
    RETURNING id INTO order_1_id;
    
    -- Commande 2 : 2 billets - 26 666 MGA (il y a 7 mois)
    total_amount_2 := 26666;
    unit_price_2 := 13333; -- ~13 333 MGA par billet
    
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
            'ticket_quantity', 2
        ),
        (NOW() - INTERVAL '7 months')::timestamptz,
        (NOW() - INTERVAL '7 months')::timestamptz
    )
    RETURNING id INTO order_2_id;
    
    -- Commande 3 : 1 billet - 13 334 MGA (il y a 4 mois)
    total_amount_3 := 13334;
    unit_price_3 := 13334; -- 13 334 MGA
    
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
            'ticket_quantity', 1
        ),
        (NOW() - INTERVAL '4 months')::timestamptz,
        (NOW() - INTERVAL '4 months')::timestamptz
    )
    RETURNING id INTO order_3_id;
    
    RAISE NOTICE 'Commandes créées :';
    RAISE NOTICE '   - Order 1 = % (%, MGA pour 3 billets, il y a 10 mois)', order_1_id, total_amount_1;
    RAISE NOTICE '   - Order 2 = % (%, MGA pour 2 billets, il y a 7 mois)', order_2_id, total_amount_2;
    RAISE NOTICE '   - Order 3 = % (%, MGA pour 1 billet, il y a 4 mois)', order_3_id, total_amount_3;
    RAISE NOTICE '   - Total : 80 000 MGA';
    
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
    RAISE NOTICE '   - 3 commandes créées (catégories business et festival)';
    RAISE NOTICE '   - Commande 1 : 40 000 MGA (3 billets, il y a 10 mois)';
    RAISE NOTICE '   - Commande 2 : 26 666 MGA (2 billets, il y a 7 mois)';
    RAISE NOTICE '   - Commande 3 : 13 334 MGA (1 billet, il y a 4 mois)';
    RAISE NOTICE '   - Total : 80 000 MGA';
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

-- Détails des commandes avec catégories
SELECT 
    o.id as order_id,
    o.status,
    o.total_amount,
    o.currency,
    o.created_at as date_achat,
    e.title as evenement,
    ec.name as categorie,
    oi.quantity as quantite,
    oi.unit_price as prix_unitaire,
    tt.name as type_ticket
FROM aiolia.users u
JOIN aiolia.orders o ON o.user_id = u.id
JOIN aiolia.order_items oi ON oi.order_id = o.id
JOIN aiolia.ticket_types tt ON tt.id = oi.ticket_type_id
JOIN aiolia.events e ON e.id = tt.event_id
LEFT JOIN aiolia.event_categories ec ON ec.id = e.primary_category_id
WHERE LOWER(u.first_name) = 'aina' AND LOWER(u.last_name) = 'fanelie'
ORDER BY o.created_at DESC;
