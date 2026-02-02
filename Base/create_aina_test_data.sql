-- ============================================================
-- Script pour créer des données de test pour Aina Fanelie
-- 2 achats de l'année passée, total < 100 000 MGA
-- ============================================================

\c aiolia_event;
SET search_path TO aiolia, public;

DO $$
DECLARE
    aina_user_id BIGINT;
    event_1_id BIGINT;
    event_2_id BIGINT;
    ticket_type_1_id BIGINT;
    ticket_type_2_id BIGINT;
    order_1_id BIGINT;
    order_2_id BIGINT;
    order_item_1_id BIGINT;
    order_item_2_id BIGINT;
    ticket_1_id BIGINT;
    ticket_2_id BIGINT;
    total_amount_1 NUMERIC;
    total_amount_2 NUMERIC;
    old_ticket_type_ids BIGINT[];
    category_1_id BIGINT;
    category_2_id BIGINT;
BEGIN
    -- ============================================================
    -- Étape 1 : Trouver ou créer l'utilisateur Aina Fanelie
    -- ============================================================
    
    SELECT id INTO aina_user_id
    FROM aiolia.users
    WHERE LOWER(first_name) = 'aina' AND LOWER(last_name) = 'fanelie'
    LIMIT 1;
    
    IF aina_user_id IS NULL THEN
        -- Créer l'utilisateur s'il n'existe pas
        INSERT INTO aiolia.users (
            email,
            login_identifier,
            login_method,
            password_hash,
            first_name,
            last_name,
            phone,
            country_code,
            language_code,
            timezone,
            role,
            status,
            is_email_verified,
            is_phone_verified,
            accepted_terms_at,
            created_at,
            updated_at
        )
        VALUES (
            'aina.fanelie@example.com',
            'aina.fanelie@example.com',
            'password',
            crypt('password123', gen_salt('bf', 12)),
            'Aina',
            'Fanelie',
            '+261340000000',
            'MG',
            'fr-FR',
            'Indian/Antananarivo',
            'user',
            1,
            TRUE,
            TRUE,
            NOW() - INTERVAL '1 year',
            NOW() - INTERVAL '1 year',
            NOW()
        )
        RETURNING id INTO aina_user_id;
        
        RAISE NOTICE 'Utilisateur Aina Fanelie créé avec ID: %', aina_user_id;
    ELSE
        RAISE NOTICE 'Utilisateur Aina Fanelie trouvé avec ID: %', aina_user_id;
    END IF;
    
    -- ============================================================
    -- Étape 2 : Trouver 2 événements de catégories DIFFÉRENTES
    -- On va utiliser les événements existants et créer des dates passées pour les commandes
    -- ============================================================
    
    -- Trouver 2 catégories différentes
    SELECT DISTINCT primary_category_id INTO category_1_id
    FROM aiolia.events
    WHERE status = 'published'
      AND primary_category_id IS NOT NULL
    ORDER BY primary_category_id ASC
    LIMIT 1;
    
    SELECT DISTINCT primary_category_id INTO category_2_id
    FROM aiolia.events
    WHERE status = 'published'
      AND primary_category_id IS NOT NULL
      AND primary_category_id != COALESCE(category_1_id, 0)
    ORDER BY primary_category_id ASC
    LIMIT 1;
    
    -- Si pas de catégories différentes, prendre n'importe quels événements
    IF category_1_id IS NULL OR category_2_id IS NULL THEN
        -- Prendre les 2 premiers événements publiés disponibles
        SELECT id INTO event_1_id
        FROM aiolia.events
        WHERE status = 'published'
        ORDER BY id ASC
        LIMIT 1;
        
        SELECT id INTO event_2_id
        FROM aiolia.events
        WHERE status = 'published'
          AND id != COALESCE(event_1_id, 0)
        ORDER BY id ASC
        LIMIT 1;
    ELSE
        -- Prendre un événement de la première catégorie
        SELECT id INTO event_1_id
        FROM aiolia.events
        WHERE status = 'published'
          AND primary_category_id = category_1_id
        ORDER BY id ASC
        LIMIT 1;
        
        -- Prendre un événement de la deuxième catégorie
        SELECT id INTO event_2_id
        FROM aiolia.events
        WHERE status = 'published'
          AND primary_category_id = category_2_id
          AND id != COALESCE(event_1_id, 0)
        ORDER BY id ASC
        LIMIT 1;
    END IF;
    
    -- Si toujours pas trouvé, prendre n'importe quels événements
    IF event_1_id IS NULL THEN
        SELECT id INTO event_1_id
        FROM aiolia.events
        ORDER BY id ASC
        LIMIT 1;
    END IF;
    
    IF event_2_id IS NULL THEN
        SELECT id INTO event_2_id
        FROM aiolia.events
        WHERE id != COALESCE(event_1_id, 0)
        ORDER BY id ASC
        LIMIT 1;
    END IF;
    
    IF event_1_id IS NULL OR event_2_id IS NULL THEN
        RAISE EXCEPTION 'Pas assez d''événements trouvés dans la base de données. Exécutez d''abord mydata.sql pour créer des événements.';
    END IF;
    
    RAISE NOTICE 'Événements sélectionnés : Event 1 = %, Event 2 = %', event_1_id, event_2_id;
    
    -- ============================================================
    -- Étape 3 : Trouver des types de tickets pour ces événements
    -- ============================================================
    
    -- Ticket type pour événement 1 (prix réduit : autour de 25 000 MGA)
    SELECT id INTO ticket_type_1_id
    FROM aiolia.ticket_types
    WHERE event_id = event_1_id
      AND base_price BETWEEN 20000 AND 35000
    ORDER BY base_price ASC
    LIMIT 1;
    
    -- Si pas trouvé, prendre le moins cher
    IF ticket_type_1_id IS NULL THEN
        SELECT id INTO ticket_type_1_id
        FROM aiolia.ticket_types
        WHERE event_id = event_1_id
        ORDER BY base_price ASC
        LIMIT 1;
    END IF;
    
    -- Ticket type pour événement 2 (prix réduit : autour de 30 000 MGA)
    SELECT id INTO ticket_type_2_id
    FROM aiolia.ticket_types
    WHERE event_id = event_2_id
      AND base_price BETWEEN 25000 AND 40000
    ORDER BY base_price ASC
    LIMIT 1;
    
    -- Si pas trouvé, prendre le moins cher
    IF ticket_type_2_id IS NULL THEN
        SELECT id INTO ticket_type_2_id
        FROM aiolia.ticket_types
        WHERE event_id = event_2_id
        ORDER BY base_price ASC
        LIMIT 1;
    END IF;
    
    IF ticket_type_1_id IS NULL OR ticket_type_2_id IS NULL THEN
        RAISE EXCEPTION 'Pas de types de tickets trouvés pour les événements sélectionnés.';
    END IF;
    
    RAISE NOTICE 'Types de tickets sélectionnés : TT1 = %, TT2 = %', ticket_type_1_id, ticket_type_2_id;
    
    -- ============================================================
    -- Étape 4 : Supprimer les anciennes commandes de Aina (si elles existent)
    -- ============================================================
    
    -- Récupérer les ticket_type_id des tickets existants avant suppression
    SELECT ARRAY_AGG(DISTINCT t.ticket_type_id) INTO old_ticket_type_ids
    FROM aiolia.tickets t
    WHERE t.owner_user_id = aina_user_id;
    
    -- Supprimer les tickets
    DELETE FROM aiolia.tickets WHERE owner_user_id = aina_user_id;
    
    -- Supprimer les order_items
    DELETE FROM aiolia.order_items WHERE order_id IN (SELECT id FROM aiolia.orders WHERE user_id = aina_user_id);
    
    -- Supprimer les commandes
    DELETE FROM aiolia.orders WHERE user_id = aina_user_id;
    
    -- Mettre à jour ticket_inventory pour décrémenter sold_quantity
    IF old_ticket_type_ids IS NOT NULL AND array_length(old_ticket_type_ids, 1) > 0 THEN
        UPDATE aiolia.ticket_inventory
        SET 
            sold_quantity = GREATEST(0, sold_quantity - 1),
            updated_at = NOW()
        WHERE ticket_type_id = ANY(old_ticket_type_ids);
        
        RAISE NOTICE 'Ticket inventory mis à jour (décrémenté) pour % types de tickets', array_length(old_ticket_type_ids, 1);
    END IF;
    
    RAISE NOTICE 'Anciennes données supprimées pour Aina Fanelie';
    
    -- ============================================================
    -- Étape 5 : Créer la commande 1 (il y a 8 mois)
    -- ============================================================
    
    SELECT base_price INTO total_amount_1
    FROM aiolia.ticket_types
    WHERE id = ticket_type_1_id;
    
    -- Ajuster le prix pour que le total soit exactement 80 000 MGA
    IF total_amount_1 > 40000 THEN
        total_amount_1 := 35000; -- Forcer à 35 000 MGA
    END IF;
    
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
            'purchase_date', (NOW() - INTERVAL '8 months')::text
        ),
        (NOW() - INTERVAL '8 months')::timestamptz,
        (NOW() - INTERVAL '8 months')::timestamptz
    )
    RETURNING id INTO order_1_id;
    
    -- ============================================================
    -- Étape 6 : Créer la commande 2 (il y a 4 mois)
    -- ============================================================
    
    SELECT base_price INTO total_amount_2
    FROM aiolia.ticket_types
    WHERE id = ticket_type_2_id;
    
    -- Ajuster les prix pour que le total soit exactement 80 000 MGA
    IF (total_amount_1 + total_amount_2) != 80000 THEN
        -- Si le total est trop élevé, ajuster
        IF (total_amount_1 + total_amount_2) > 80000 THEN
            total_amount_1 := 35000;
            total_amount_2 := 45000;
        ELSE
            -- Si le total est trop bas, ajuster proportionnellement
            total_amount_1 := 35000;
            total_amount_2 := 45000;
        END IF;
    END IF;
    
    -- S'assurer que le total est exactement 80 000 MGA
    total_amount_1 := 35000;
    total_amount_2 := 45000;
    
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
            'purchase_date', (NOW() - INTERVAL '4 months')::text
        ),
        (NOW() - INTERVAL '4 months')::timestamptz,
        (NOW() - INTERVAL '4 months')::timestamptz
    )
    RETURNING id INTO order_2_id;
    
    RAISE NOTICE 'Commandes créées : Order 1 = % (%, MGA), Order 2 = % (%, MGA)', 
        order_1_id, total_amount_1, order_2_id, total_amount_2;
    RAISE NOTICE 'Total des achats : % MGA', (total_amount_1 + total_amount_2);
    
    -- ============================================================
    -- Étape 7 : Créer les order_items
    -- ============================================================
    
    -- Order item 1 : 1 billet pour l'événement 1
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
        1,
        total_amount_1,
        0,
        0,
        total_amount_1,
        (NOW() - INTERVAL '8 months')::timestamptz
    )
    RETURNING id INTO order_item_1_id;
    
    -- Order item 2 : 1 billet pour l'événement 2
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
        1,
        total_amount_2,
        0,
        0,
        total_amount_2,
        (NOW() - INTERVAL '4 months')::timestamptz
    )
    RETURNING id INTO order_item_2_id;
    
    RAISE NOTICE 'Order items créés : OI1 = %, OI2 = %', order_item_1_id, order_item_2_id;
    
    -- ============================================================
    -- Étape 8 : Créer les tickets
    -- ============================================================
    
    -- Ticket 1
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
        'TICKET-' || ticket_type_1_id || '-' || aina_user_id || '-' || order_1_id || '-' || encode(gen_random_bytes(8), 'hex'),
        encode(digest('TICKET-' || ticket_type_1_id || '-' || aina_user_id || '-' || order_1_id, 'sha256'), 'hex'),
        (NOW() - INTERVAL '8 months')::timestamptz
    )
    RETURNING id INTO ticket_1_id;
    
    -- Ticket 2
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
        'TICKET-' || ticket_type_2_id || '-' || aina_user_id || '-' || order_2_id || '-' || encode(gen_random_bytes(8), 'hex'),
        encode(digest('TICKET-' || ticket_type_2_id || '-' || aina_user_id || '-' || order_2_id, 'sha256'), 'hex'),
        (NOW() - INTERVAL '4 months')::timestamptz
    )
    RETURNING id INTO ticket_2_id;
    
    RAISE NOTICE 'Tickets créés : Ticket 1 = %, Ticket 2 = %', ticket_1_id, ticket_2_id;
    
    -- ============================================================
    -- Étape 9 : Mettre à jour les montants des commandes si nécessaire
    -- ============================================================
    
    UPDATE aiolia.orders
    SET total_amount = total_amount_1
    WHERE id = order_1_id;
    
    UPDATE aiolia.orders
    SET total_amount = total_amount_2
    WHERE id = order_2_id;
    
    -- ============================================================
    -- Étape 10 : Mettre à jour les dates des événements pour qu'ils soient passés
    -- (nécessaire pour que les billets apparaissent dans "mes billets passés")
    -- ============================================================
    
    -- Événement 1 : Il y a 8 mois
    UPDATE aiolia.events
    SET 
        starts_at = (NOW() - INTERVAL '8 months')::timestamptz,
        ends_at = (NOW() - INTERVAL '8 months' + INTERVAL '3 hours')::timestamptz,
        updated_at = NOW()
    WHERE id = event_1_id;
    
    -- Événement 2 : Il y a 4 mois
    UPDATE aiolia.events
    SET 
        starts_at = (NOW() - INTERVAL '4 months')::timestamptz,
        ends_at = (NOW() - INTERVAL '4 months' + INTERVAL '3 hours')::timestamptz,
        updated_at = NOW()
    WHERE id = event_2_id;
    
    RAISE NOTICE 'Dates des événements mises à jour pour qu''ils soient passés';
    
    -- ============================================================
    -- Étape 11 : Mettre à jour ticket_inventory pour les statistiques
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
        1,
        NOW()
    )
    ON CONFLICT (ticket_type_id) 
    DO UPDATE SET 
        sold_quantity = aiolia.ticket_inventory.sold_quantity + 1,
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
        1,
        NOW()
    )
    ON CONFLICT (ticket_type_id) 
    DO UPDATE SET 
        sold_quantity = aiolia.ticket_inventory.sold_quantity + 1,
        updated_at = NOW();
    
    RAISE NOTICE 'Ticket inventory mis à jour pour les statistiques';
    
    RAISE NOTICE '✅ Données créées avec succès pour Aina Fanelie !';
    RAISE NOTICE '   - 2 commandes créées (2 catégories d''événements différentes)';
    RAISE NOTICE '   - Commande 1 : % MGA', total_amount_1;
    RAISE NOTICE '   - Commande 2 : % MGA', total_amount_2;
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

-- Détails des commandes
SELECT 
    o.id as order_id,
    o.status,
    o.total_amount,
    o.currency,
    o.created_at as date_achat,
    e.title as evenement,
    oi.quantity as quantite,
    tt.name as type_ticket
FROM aiolia.users u
JOIN aiolia.orders o ON o.user_id = u.id
JOIN aiolia.order_items oi ON oi.order_id = o.id
JOIN aiolia.ticket_types tt ON tt.id = oi.ticket_type_id
JOIN aiolia.events e ON e.id = tt.event_id
WHERE LOWER(u.first_name) = 'aina' AND LOWER(u.last_name) = 'fanelie'
ORDER BY o.created_at DESC;
