-- ===================================================================
-- Script pour mettre à jour des événements en "passés" et créer
-- des achats pour l'utilisateur "Aina Fanelie"
-- 
-- Ce script :
-- 1. Met à jour 3-4 événements pour qu'ils soient passés
-- 2. Crée des commandes (orders) pour ces événements
-- 3. Crée des order_items et tickets
-- 4. Crée des transactions de paiement
-- ===================================================================

BEGIN;

SET search_path TO aiolia, public;

-- -------------------------------------------------------------------
-- Étape 1 : Trouver l'utilisateur "Aina Fanelie"
-- -------------------------------------------------------------------
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
    ticket_count INTEGER;
    qr_code_val TEXT;
BEGIN
    -- Trouver l'utilisateur Aina Fanelie (id=1 ou par email/nom)
    SELECT id INTO aina_user_id
    FROM aiolia.users
    WHERE (id = 1 AND first_name ILIKE '%Aina%' AND last_name ILIKE '%Fanelie%')
       OR (first_name ILIKE '%Aina%' AND last_name ILIKE '%Fanelie%')
    LIMIT 1;

    IF aina_user_id IS NULL THEN
        RAISE EXCEPTION 'Utilisateur Aina Fanelie introuvable. Veuillez vérifier les données.';
    END IF;

    RAISE NOTICE 'Utilisateur trouvé : ID = %', aina_user_id;

    -- -------------------------------------------------------------------
    -- Étape 2 : Mettre à jour 3 événements pour qu'ils soient passés
    -- On choisit des événements avec des dates récentes dans le passé
    -- -------------------------------------------------------------------
    
    -- Événement 1 : Concert Jazz (était le 2026-01-27, on le met il y a 2 mois)
    -- IMPORTANT: ends_at doit être APRÈS starts_at (contrainte CHECK)
    -- IMPORTANT: sales_ends_at doit être APRÈS sales_starts_at (contrainte CHECK)
    UPDATE aiolia.events
    SET starts_at = (now() - INTERVAL '2 months' - INTERVAL '5 days')::timestamptz,
        ends_at = (now() - INTERVAL '2 months' - INTERVAL '5 days' + INTERVAL '4 hours')::timestamptz,
        sales_starts_at = (now() - INTERVAL '2 months' - INTERVAL '10 days')::timestamptz,
        sales_ends_at = (now() - INTERVAL '2 months' - INTERVAL '5 days')::timestamptz,
        status = 'archived'
    WHERE slug = 'concert-jazz-tana'
    RETURNING id INTO event_1_id;

    -- Événement 2 : Concert Folk (était le 2026-02-20, on le met il y a 1 mois)
    UPDATE aiolia.events
    SET starts_at = (now() - INTERVAL '1 month' - INTERVAL '3 days')::timestamptz,
        ends_at = (now() - INTERVAL '1 month' - INTERVAL '3 days' + INTERVAL '3 hours 30 minutes')::timestamptz,
        sales_starts_at = (now() - INTERVAL '1 month' - INTERVAL '8 days')::timestamptz,
        sales_ends_at = (now() - INTERVAL '1 month' - INTERVAL '3 days')::timestamptz,
        status = 'archived'
    WHERE slug = 'concert-folk-tana'
    RETURNING id INTO event_2_id;

    -- Événement 3 : Festival Gastronomique (était le 2026-02-28, on le met il y a 3 semaines)
    UPDATE aiolia.events
    SET starts_at = (now() - INTERVAL '3 weeks' - INTERVAL '1 day')::timestamptz,
        ends_at = (now() - INTERVAL '3 weeks' - INTERVAL '1 day' + INTERVAL '10 hours')::timestamptz,
        sales_starts_at = (now() - INTERVAL '3 weeks' - INTERVAL '6 days')::timestamptz,
        sales_ends_at = (now() - INTERVAL '3 weeks' - INTERVAL '1 day')::timestamptz,
        status = 'archived'
    WHERE slug = 'festival-gastronomie-tana'
    RETURNING id INTO event_3_id;

    RAISE NOTICE 'Événements mis à jour : Event 1 = %, Event 2 = %, Event 3 = %', event_1_id, event_2_id, event_3_id;

    -- -------------------------------------------------------------------
    -- Étape 3 : Récupérer les ticket_types pour ces événements
    -- -------------------------------------------------------------------
    
    -- Ticket type pour Event 1 (Jazz)
    SELECT id INTO ticket_type_1_id
    FROM aiolia.ticket_types
    WHERE event_id = event_1_id
    ORDER BY base_price ASC
    LIMIT 1;

    -- Ticket type pour Event 2 (Folk)
    SELECT id INTO ticket_type_2_id
    FROM aiolia.ticket_types
    WHERE event_id = event_2_id
    ORDER BY base_price ASC
    LIMIT 1;

    -- Ticket type pour Event 3 (Festival Gastronomique)
    SELECT id INTO ticket_type_3_id
    FROM aiolia.ticket_types
    WHERE event_id = event_3_id
    ORDER BY base_price ASC
    LIMIT 1;

    IF ticket_type_1_id IS NULL OR ticket_type_2_id IS NULL OR ticket_type_3_id IS NULL THEN
        RAISE EXCEPTION 'Certains ticket_types sont introuvables pour les événements sélectionnés.';
    END IF;

    RAISE NOTICE 'Ticket types trouvés : TT1 = %, TT2 = %, TT3 = %', ticket_type_1_id, ticket_type_2_id, ticket_type_3_id;

    -- -------------------------------------------------------------------
    -- Étape 4 : Créer les commandes (orders)
    -- -------------------------------------------------------------------
    
    -- Commande 1 : Concert Jazz (il y a 2 mois)
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
        (SELECT base_price FROM aiolia.ticket_types WHERE id = ticket_type_1_id) * 2, -- 2 billets
        0,
        'MGA',
        jsonb_build_object(
            'payment_method', 'mvola',
            'event_title', (SELECT title FROM aiolia.events WHERE id = event_1_id),
            'purchase_date', (now() - INTERVAL '2 months' - INTERVAL '5 days')::text
        ),
        (now() - INTERVAL '2 months' - INTERVAL '5 days')::timestamptz,
        (now() - INTERVAL '2 months' - INTERVAL '5 days')::timestamptz
    )
    RETURNING id INTO order_1_id;

    -- Commande 2 : Concert Folk (il y a 1 mois)
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
        (SELECT base_price FROM aiolia.ticket_types WHERE id = ticket_type_2_id) * 1, -- 1 billet
        0,
        'MGA',
        jsonb_build_object(
            'payment_method', 'mvola',
            'event_title', (SELECT title FROM aiolia.events WHERE id = event_2_id),
            'purchase_date', (now() - INTERVAL '1 month' - INTERVAL '3 days')::text
        ),
        (now() - INTERVAL '1 month' - INTERVAL '3 days')::timestamptz,
        (now() - INTERVAL '1 month' - INTERVAL '3 days')::timestamptz
    )
    RETURNING id INTO order_2_id;

    -- Commande 3 : Festival Gastronomique (il y a 3 semaines)
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
        (SELECT base_price FROM aiolia.ticket_types WHERE id = ticket_type_3_id) * 3, -- 3 billets
        0,
        'MGA',
        jsonb_build_object(
            'payment_method', 'mvola',
            'event_title', (SELECT title FROM aiolia.events WHERE id = event_3_id),
            'purchase_date', (now() - INTERVAL '3 weeks' - INTERVAL '1 day')::text
        ),
        (now() - INTERVAL '3 weeks' - INTERVAL '1 day')::timestamptz,
        (now() - INTERVAL '3 weeks' - INTERVAL '1 day')::timestamptz
    )
    RETURNING id INTO order_3_id;

    RAISE NOTICE 'Commandes créées : Order 1 = %, Order 2 = %, Order 3 = %', order_1_id, order_2_id, order_3_id;

    -- -------------------------------------------------------------------
    -- Étape 5 : Créer les order_items
    -- -------------------------------------------------------------------
    
    -- Order item 1 : 2 billets pour le Concert Jazz
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
        2,
        (SELECT base_price FROM aiolia.ticket_types WHERE id = ticket_type_1_id),
        0,
        0,
        (SELECT base_price FROM aiolia.ticket_types WHERE id = ticket_type_1_id) * 2,
        (now() - INTERVAL '2 months' - INTERVAL '5 days')::timestamptz
    )
    RETURNING id INTO order_item_1_id;

    -- Order item 2 : 1 billet pour le Concert Folk
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
        (SELECT base_price FROM aiolia.ticket_types WHERE id = ticket_type_2_id),
        0,
        0,
        (SELECT base_price FROM aiolia.ticket_types WHERE id = ticket_type_2_id) * 1,
        (now() - INTERVAL '1 month' - INTERVAL '3 days')::timestamptz
    )
    RETURNING id INTO order_item_2_id;

    -- Order item 3 : 3 billets pour le Festival Gastronomique
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
        3,
        (SELECT base_price FROM aiolia.ticket_types WHERE id = ticket_type_3_id),
        0,
        0,
        (SELECT base_price FROM aiolia.ticket_types WHERE id = ticket_type_3_id) * 3,
        (now() - INTERVAL '3 weeks' - INTERVAL '1 day')::timestamptz
    )
    RETURNING id INTO order_item_3_id;

    RAISE NOTICE 'Order items créés : OI1 = %, OI2 = %, OI3 = %', order_item_1_id, order_item_2_id, order_item_3_id;

    -- -------------------------------------------------------------------
    -- Étape 6 : Créer les tickets
    -- -------------------------------------------------------------------
    
    -- Tickets pour Order Item 1 (2 billets)
    FOR ticket_count IN 1..2 LOOP
        qr_code_val := 'TKT-' || order_item_1_id || '-' || ticket_count || '-' || encode(gen_random_bytes(8), 'hex');
        INSERT INTO aiolia.tickets (
            order_item_id,
            ticket_type_id,
            owner_user_id,
            status,
            qr_code,
            qr_checksum,
            issued_at,
            metadata
        )
        VALUES (
            order_item_1_id,
            ticket_type_1_id,
            aina_user_id,
            'used', -- Billets utilisés car événement passé
            qr_code_val,
            encode(digest(qr_code_val, 'sha256'), 'hex'),
            (now() - INTERVAL '2 months' - INTERVAL '5 days')::timestamptz,
            jsonb_build_object('order_id', order_1_id, 'order_item_id', order_item_1_id)
        );
    END LOOP;

    -- Ticket pour Order Item 2 (1 billet)
    qr_code_val := 'TKT-' || order_item_2_id || '-1-' || encode(gen_random_bytes(8), 'hex');
    INSERT INTO aiolia.tickets (
        order_item_id,
        ticket_type_id,
        owner_user_id,
        status,
        qr_code,
        qr_checksum,
        issued_at,
        metadata
    )
    VALUES (
        order_item_2_id,
        ticket_type_2_id,
        aina_user_id,
        'used', -- Billet utilisé car événement passé
        qr_code_val,
        encode(digest(qr_code_val, 'sha256'), 'hex'),
        (now() - INTERVAL '1 month' - INTERVAL '3 days')::timestamptz,
        jsonb_build_object('order_id', order_2_id, 'order_item_id', order_item_2_id)
    );

    -- Tickets pour Order Item 3 (3 billets)
    FOR ticket_count IN 1..3 LOOP
        qr_code_val := 'TKT-' || order_item_3_id || '-' || ticket_count || '-' || encode(gen_random_bytes(8), 'hex');
        INSERT INTO aiolia.tickets (
            order_item_id,
            ticket_type_id,
            owner_user_id,
            status,
            qr_code,
            qr_checksum,
            issued_at,
            metadata
        )
        VALUES (
            order_item_3_id,
            ticket_type_3_id,
            aina_user_id,
            'used', -- Billets utilisés car événement passé
            qr_code_val,
            encode(digest(qr_code_val, 'sha256'), 'hex'),
            (now() - INTERVAL '3 weeks' - INTERVAL '1 day')::timestamptz,
            jsonb_build_object('order_id', order_3_id, 'order_item_id', order_item_3_id)
        );
    END LOOP;

    RAISE NOTICE 'Tickets créés avec succès';

    -- -------------------------------------------------------------------
    -- Étape 7 : Créer les transactions de paiement
    -- -------------------------------------------------------------------
    
    -- Transaction 1 : Concert Jazz
    INSERT INTO aiolia.payment_transactions (
        order_id,
        mvola_correlation_id,
        mvola_transaction_id,
        transaction_reference,
        status,
        amount,
        currency,
        customer_msisdn,
        partner_msisdn,
        payment_method,
        callback_data,
        created_at,
        updated_at
    )
    VALUES (
        order_1_id,
        'MVOLA-' || order_1_id || '-' || encode(gen_random_bytes(4), 'hex'),
        'MVOLA-TXN-' || order_1_id || '-' || encode(gen_random_bytes(4), 'hex'),
        'TXN-REF-' || order_1_id || '-' || encode(gen_random_bytes(4), 'hex'),
        'paid',
        (SELECT total_amount FROM aiolia.orders WHERE id = order_1_id),
        'MGA',
        '+261340000001',
        '+261340000000',
        'mvola',
        jsonb_build_object(
            'status', 'paid',
            'payment_date', (now() - INTERVAL '2 months' - INTERVAL '5 days')::text,
            'method', 'mvola'
        ),
        (now() - INTERVAL '2 months' - INTERVAL '5 days')::timestamptz,
        (now() - INTERVAL '2 months' - INTERVAL '5 days')::timestamptz
    );

    -- Transaction 2 : Concert Folk
    INSERT INTO aiolia.payment_transactions (
        order_id,
        mvola_correlation_id,
        mvola_transaction_id,
        transaction_reference,
        status,
        amount,
        currency,
        customer_msisdn,
        partner_msisdn,
        payment_method,
        callback_data,
        created_at,
        updated_at
    )
    VALUES (
        order_2_id,
        'MVOLA-' || order_2_id || '-' || encode(gen_random_bytes(4), 'hex'),
        'MVOLA-TXN-' || order_2_id || '-' || encode(gen_random_bytes(4), 'hex'),
        'TXN-REF-' || order_2_id || '-' || encode(gen_random_bytes(4), 'hex'),
        'paid',
        (SELECT total_amount FROM aiolia.orders WHERE id = order_2_id),
        'MGA',
        '+261340000001',
        '+261340000000',
        'mvola',
        jsonb_build_object(
            'status', 'paid',
            'payment_date', (now() - INTERVAL '1 month' - INTERVAL '3 days')::text,
            'method', 'mvola'
        ),
        (now() - INTERVAL '1 month' - INTERVAL '3 days')::timestamptz,
        (now() - INTERVAL '1 month' - INTERVAL '3 days')::timestamptz
    );

    -- Transaction 3 : Festival Gastronomique
    INSERT INTO aiolia.payment_transactions (
        order_id,
        mvola_correlation_id,
        mvola_transaction_id,
        transaction_reference,
        status,
        amount,
        currency,
        customer_msisdn,
        partner_msisdn,
        payment_method,
        callback_data,
        created_at,
        updated_at
    )
    VALUES (
        order_3_id,
        'MVOLA-' || order_3_id || '-' || encode(gen_random_bytes(4), 'hex'),
        'MVOLA-TXN-' || order_3_id || '-' || encode(gen_random_bytes(4), 'hex'),
        'TXN-REF-' || order_3_id || '-' || encode(gen_random_bytes(4), 'hex'),
        'paid',
        (SELECT total_amount FROM aiolia.orders WHERE id = order_3_id),
        'MGA',
        '+261340000001',
        '+261340000000',
        'mvola',
        jsonb_build_object(
            'status', 'paid',
            'payment_date', (now() - INTERVAL '3 weeks' - INTERVAL '1 day')::text,
            'method', 'mvola'
        ),
        (now() - INTERVAL '3 weeks' - INTERVAL '1 day')::timestamptz,
        (now() - INTERVAL '3 weeks' - INTERVAL '1 day')::timestamptz
    );

    RAISE NOTICE 'Transactions de paiement créées avec succès';

    -- -------------------------------------------------------------------
    -- Étape 8 : Créer l'historique des statuts de commande
    -- -------------------------------------------------------------------
    
    -- Historique pour Order 1
    INSERT INTO aiolia.order_status_history (order_id, status_from, status_to, changed_at)
    VALUES (order_1_id, NULL, 'pending', (now() - INTERVAL '2 months' - INTERVAL '5 days' - INTERVAL '5 minutes')::timestamptz);
    
    INSERT INTO aiolia.order_status_history (order_id, status_from, status_to, changed_at)
    VALUES (order_1_id, 'pending', 'paid', (now() - INTERVAL '2 months' - INTERVAL '5 days')::timestamptz);

    -- Historique pour Order 2
    INSERT INTO aiolia.order_status_history (order_id, status_from, status_to, changed_at)
    VALUES (order_2_id, NULL, 'pending', (now() - INTERVAL '1 month' - INTERVAL '3 days' - INTERVAL '5 minutes')::timestamptz);
    
    INSERT INTO aiolia.order_status_history (order_id, status_from, status_to, changed_at)
    VALUES (order_2_id, 'pending', 'paid', (now() - INTERVAL '1 month' - INTERVAL '3 days')::timestamptz);

    -- Historique pour Order 3
    INSERT INTO aiolia.order_status_history (order_id, status_from, status_to, changed_at)
    VALUES (order_3_id, NULL, 'pending', (now() - INTERVAL '3 weeks' - INTERVAL '1 day' - INTERVAL '5 minutes')::timestamptz);
    
    INSERT INTO aiolia.order_status_history (order_id, status_from, status_to, changed_at)
    VALUES (order_3_id, 'pending', 'paid', (now() - INTERVAL '3 weeks' - INTERVAL '1 day')::timestamptz);

    RAISE NOTICE 'Historique des statuts créé avec succès';

    RAISE NOTICE '=== Script terminé avec succès ===';
    RAISE NOTICE 'Utilisateur : % (ID: %)', aina_user_id, aina_user_id;
    RAISE NOTICE 'Événements mis à jour : 3';
    RAISE NOTICE 'Commandes créées : 3';
    RAISE NOTICE 'Tickets créés : 6 (2 + 1 + 3)';
    RAISE NOTICE 'Transactions créées : 3';

END $$;

COMMIT;

-- Vérification finale
SELECT 
    u.id as user_id,
    u.first_name || ' ' || u.last_name as user_name,
    COUNT(DISTINCT o.id) as total_orders,
    COUNT(DISTINCT t.id) as total_tickets,
    SUM(o.total_amount) as total_spent
FROM aiolia.users u
LEFT JOIN aiolia.orders o ON o.user_id = u.id
LEFT JOIN aiolia.order_items oi ON oi.order_id = o.id
LEFT JOIN aiolia.tickets t ON t.order_item_id = oi.id
WHERE (u.id = 1 AND u.first_name ILIKE '%Aina%' AND u.last_name ILIKE '%Fanelie%')
   OR (u.first_name ILIKE '%Aina%' AND u.last_name ILIKE '%Fanelie%')
GROUP BY u.id, u.first_name, u.last_name;
