-- ============================================================================
-- STORED PROCEDURES - OPÉRATIONS COURANTES
-- ============================================================================

DELIMITER //

-- ============================================================================
-- 1. GESTION DES BILLETS
-- ============================================================================

-- Procédure pour vérifier la disponibilité des billets
CREATE PROCEDURE check_ticket_availability(
    IN p_ticket_category_id BIGINT,
    IN p_quantity INT,
    OUT p_available BOOLEAN,
    OUT p_remaining INT
)
BEGIN
    SELECT 
        (quantity_total - quantity_sold - quantity_reserved) >= p_quantity,
        (quantity_total - quantity_sold - quantity_reserved)
    INTO p_available, p_remaining
    FROM ticket_categories
    WHERE id = p_ticket_category_id;
END//

-- Procédure pour générer des billets après paiement
CREATE PROCEDURE generate_tickets_for_order(
    IN p_order_id BIGINT
)
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_ticket_category_id BIGINT;
    DECLARE v_quantity INT;
    DECLARE v_user_id BIGINT;
    DECLARE v_counter INT;
    
    DECLARE cur CURSOR FOR 
        SELECT ticket_category_id, quantity 
        FROM order_items 
        WHERE order_id = p_order_id;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    -- Récupérer l'utilisateur
    SELECT user_id INTO v_user_id FROM orders WHERE id = p_order_id;
    
    OPEN cur;
    
    read_loop: LOOP
        FETCH cur INTO v_ticket_category_id, v_quantity;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Générer les billets
        SET v_counter = 0;
        WHILE v_counter < v_quantity DO
            INSERT INTO tickets (
                ticket_category_id,
                order_id,
                user_id,
                status
            ) VALUES (
                v_ticket_category_id,
                p_order_id,
                v_user_id,
                'valid'
            );
            
            SET v_counter = v_counter + 1;
        END WHILE;
        
    END LOOP;
    
    CLOSE cur;
END//

-- Procédure pour scanner un billet (check-in)
CREATE PROCEDURE checkin_ticket(
    IN p_qr_code_data VARCHAR(500),
    IN p_scanned_by BIGINT,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE v_ticket_id BIGINT;
    DECLARE v_status VARCHAR(20);
    DECLARE v_event_start DATETIME;
    DECLARE v_event_id BIGINT;
    
    -- Trouver le billet
    SELECT id, status INTO v_ticket_id, v_status
    FROM tickets
    WHERE qr_code_data = p_qr_code_data;
    
    IF v_ticket_id IS NULL THEN
        SET p_success = FALSE;
        SET p_message = 'Billet non trouvé';
    ELSEIF v_status = 'used' THEN
        SET p_success = FALSE;
        SET p_message = 'Billet déjà utilisé';
    ELSEIF v_status IN ('cancelled', 'refunded') THEN
        SET p_success = FALSE;
        SET p_message = 'Billet annulé ou remboursé';
    ELSE
        -- Vérifier que l'événement a commencé
        SELECT e.id, e.start_date INTO v_event_id, v_event_start
        FROM events e
        INNER JOIN ticket_categories tc ON tc.event_id = e.id
        INNER JOIN tickets t ON t.ticket_category_id = tc.id
        WHERE t.id = v_ticket_id;
        
        -- Marquer comme utilisé
        UPDATE tickets
        SET 
            status = 'used',
            check_in_at = NOW(),
            check_in_by = p_scanned_by
        WHERE id = v_ticket_id;
        
        SET p_success = TRUE;
        SET p_message = 'Check-in réussi';
    END IF;
END//

-- ============================================================================
-- 2. GESTION DES COMMANDES
-- ============================================================================

-- Procédure pour créer une commande depuis le panier
CREATE PROCEDURE create_order_from_cart(
    IN p_user_id BIGINT,
    IN p_promo_code VARCHAR(50),
    OUT p_order_id BIGINT,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE v_cart_id BIGINT;
    DECLARE v_subtotal DECIMAL(10, 2) DEFAULT 0;
    DECLARE v_discount DECIMAL(10, 2) DEFAULT 0;
    DECLARE v_total DECIMAL(10, 2);
    DECLARE v_promo_code_id BIGINT DEFAULT NULL;
    DECLARE v_promo_discount_type VARCHAR(20);
    DECLARE v_promo_discount_value DECIMAL(10, 2);
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_ticket_category_id BIGINT;
    DECLARE v_quantity INT;
    DECLARE v_unit_price DECIMAL(10, 2);
    
    DECLARE cur CURSOR FOR 
        SELECT ci.ticket_category_id, ci.quantity, tc.price
        FROM cart_items ci
        INNER JOIN ticket_categories tc ON ci.ticket_category_id = tc.id
        WHERE ci.cart_id = v_cart_id;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    -- Récupérer le panier
    SELECT id INTO v_cart_id FROM cart WHERE user_id = p_user_id LIMIT 1;
    
    IF v_cart_id IS NULL THEN
        SET p_success = FALSE;
        SET p_message = 'Panier vide';
        RETURN;
    END IF;
    
    -- Calculer le sous-total
    SELECT SUM(ci.quantity * tc.price) INTO v_subtotal
    FROM cart_items ci
    INNER JOIN ticket_categories tc ON ci.ticket_category_id = tc.id
    WHERE ci.cart_id = v_cart_id;
    
    -- Valider et appliquer le code promo si fourni
    IF p_promo_code IS NOT NULL THEN
        SELECT id, discount_type, discount_value
        INTO v_promo_code_id, v_promo_discount_type, v_promo_discount_value
        FROM promo_codes
        WHERE code = p_promo_code
          AND is_active = TRUE
          AND NOW() BETWEEN valid_from AND valid_until
          AND (max_uses IS NULL OR current_uses < max_uses)
        LIMIT 1;
        
        IF v_promo_code_id IS NOT NULL THEN
            IF v_promo_discount_type = 'percentage' THEN
                SET v_discount = v_subtotal * (v_promo_discount_value / 100);
            ELSE
                SET v_discount = v_promo_discount_value;
            END IF;
        END IF;
    END IF;
    
    SET v_total = v_subtotal - v_discount;
    
    -- Créer la commande
    INSERT INTO orders (
        user_id,
        subtotal,
        discount_amount,
        promo_code_id,
        total_amount,
        status,
        payment_status
    ) VALUES (
        p_user_id,
        v_subtotal,
        v_discount,
        v_promo_code_id,
        v_total,
        'pending',
        'pending'
    );
    
    SET p_order_id = LAST_INSERT_ID();
    
    -- Copier les items du panier vers la commande
    OPEN cur;
    
    read_loop: LOOP
        FETCH cur INTO v_ticket_category_id, v_quantity, v_unit_price;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        INSERT INTO order_items (
            order_id,
            ticket_category_id,
            quantity,
            unit_price,
            total_price
        ) VALUES (
            p_order_id,
            v_ticket_category_id,
            v_quantity,
            v_unit_price,
            v_quantity * v_unit_price
        );
    END LOOP;
    
    CLOSE cur;
    
    -- Vider le panier
    DELETE FROM cart_items WHERE cart_id = v_cart_id;
    
    -- Enregistrer l'utilisation du code promo
    IF v_promo_code_id IS NOT NULL THEN
        INSERT INTO promo_code_usage (promo_code_id, user_id, order_id, discount_applied)
        VALUES (v_promo_code_id, p_user_id, p_order_id, v_discount);
    END IF;
    
    SET p_success = TRUE;
    SET p_message = 'Commande créée avec succès';
END//

-- Procédure pour finaliser une commande après paiement réussi
CREATE PROCEDURE complete_order(
    IN p_order_id BIGINT,
    IN p_payment_id BIGINT
)
BEGIN
    -- Mettre à jour la commande
    UPDATE orders
    SET 
        status = 'completed',
        payment_status = 'paid',
        completed_at = NOW()
    WHERE id = p_order_id;
    
    -- Générer les billets
    CALL generate_tickets_for_order(p_order_id);
    
    -- Générer la facture
    INSERT INTO invoices (order_id, invoice_number)
    VALUES (
        p_order_id,
        CONCAT('INV-', DATE_FORMAT(NOW(), '%Y%m%d'), '-', LPAD(p_order_id, 8, '0'))
    );
END//

-- Procédure pour annuler une commande
CREATE PROCEDURE cancel_order(
    IN p_order_id BIGINT,
    IN p_reason VARCHAR(255),
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE v_status VARCHAR(20);
    
    SELECT status INTO v_status FROM orders WHERE id = p_order_id;
    
    IF v_status = 'completed' THEN
        SET p_success = FALSE;
        SET p_message = 'Impossible d\'annuler une commande complétée';
    ELSE
        -- Annuler la commande
        UPDATE orders
        SET 
            status = 'cancelled',
            cancelled_at = NOW(),
            notes = CONCAT(COALESCE(notes, ''), '\nRaison d\'annulation: ', p_reason)
        WHERE id = p_order_id;
        
        -- Annuler les billets associés
        UPDATE tickets
        SET status = 'cancelled'
        WHERE order_id = p_order_id;
        
        SET p_success = TRUE;
        SET p_message = 'Commande annulée avec succès';
    END IF;
END//

-- ============================================================================
-- 3. STATISTIQUES & RAPPORTS
-- ============================================================================

-- Procédure pour calculer les statistiques d'un événement
CREATE PROCEDURE calculate_event_statistics(
    IN p_event_id BIGINT
)
BEGIN
    DECLARE v_stats_exist BOOLEAN DEFAULT FALSE;
    
    -- Vérifier si les stats existent
    SELECT EXISTS(SELECT 1 FROM event_statistics WHERE event_id = p_event_id)
    INTO v_stats_exist;
    
    IF v_stats_exist THEN
        -- Mettre à jour les statistiques existantes
        UPDATE event_statistics
        SET 
            total_views = (SELECT COUNT(*) FROM event_views WHERE event_id = p_event_id),
            unique_views = (SELECT COUNT(DISTINCT COALESCE(user_id, ip_address)) FROM event_views WHERE event_id = p_event_id),
            total_favorites = (SELECT COUNT(*) FROM favorites WHERE event_id = p_event_id),
            total_tickets_sold = (SELECT COALESCE(SUM(quantity_sold), 0) FROM ticket_categories WHERE event_id = p_event_id),
            total_revenue = (
                SELECT COALESCE(SUM(o.total_amount), 0)
                FROM orders o
                INNER JOIN order_items oi ON oi.order_id = o.id
                INNER JOIN ticket_categories tc ON tc.id = oi.ticket_category_id
                WHERE tc.event_id = p_event_id AND o.status = 'completed'
            ),
            average_ticket_price = (
                SELECT AVG(price)
                FROM ticket_categories
                WHERE event_id = p_event_id
            ),
            conversion_rate = (
                SELECT CASE 
                    WHEN COUNT(DISTINCT ev.user_id) > 0 
                    THEN (COUNT(DISTINCT t.user_id) * 100.0 / COUNT(DISTINCT ev.user_id))
                    ELSE 0 
                END
                FROM event_views ev
                LEFT JOIN tickets t ON t.user_id = ev.user_id
                INNER JOIN ticket_categories tc ON tc.id = t.ticket_category_id
                WHERE ev.event_id = p_event_id AND tc.event_id = p_event_id
            ),
            average_cart_value = (
                SELECT AVG(total_amount)
                FROM orders o
                INNER JOIN order_items oi ON oi.order_id = o.id
                INNER JOIN ticket_categories tc ON tc.id = oi.ticket_category_id
                WHERE tc.event_id = p_event_id AND o.status = 'completed'
            ),
            total_refunds = (
                SELECT COUNT(*)
                FROM tickets t
                INNER JOIN ticket_categories tc ON tc.id = t.ticket_category_id
                WHERE tc.event_id = p_event_id AND t.status IN ('cancelled', 'refunded')
            ),
            average_rating = (
                SELECT COALESCE(AVG(rating), 0)
                FROM reviews
                WHERE event_id = p_event_id AND is_published = TRUE
            ),
            total_reviews = (
                SELECT COUNT(*)
                FROM reviews
                WHERE event_id = p_event_id AND is_published = TRUE
            ),
            last_calculated_at = NOW()
        WHERE event_id = p_event_id;
    ELSE
        -- Créer les statistiques
        INSERT INTO event_statistics (event_id)
        VALUES (p_event_id);
        
        -- Recalculer
        CALL calculate_event_statistics(p_event_id);
    END IF;
END//

-- Procédure pour calculer les statistiques utilisateur
CREATE PROCEDURE calculate_user_statistics(
    IN p_user_id BIGINT
)
BEGIN
    UPDATE user_statistics
    SET 
        total_events_attended = (
            SELECT COUNT(DISTINCT tc.event_id)
            FROM tickets t
            INNER JOIN ticket_categories tc ON tc.id = t.ticket_category_id
            WHERE t.user_id = p_user_id AND t.status = 'used'
        ),
        total_tickets_purchased = (
            SELECT COUNT(*)
            FROM tickets
            WHERE user_id = p_user_id AND status NOT IN ('cancelled', 'refunded')
        ),
        total_spent = (
            SELECT COALESCE(SUM(total_amount), 0)
            FROM orders
            WHERE user_id = p_user_id AND status = 'completed'
        ),
        average_order_value = (
            SELECT AVG(total_amount)
            FROM orders
            WHERE user_id = p_user_id AND status = 'completed'
        ),
        favorite_category_id = (
            SELECT tc.event_id
            FROM tickets t
            INNER JOIN ticket_categories tc ON tc.id = t.ticket_category_id
            INNER JOIN events e ON e.id = tc.event_id
            WHERE t.user_id = p_user_id
            GROUP BY e.category_id
            ORDER BY COUNT(*) DESC
            LIMIT 1
        ),
        total_referrals = (
            SELECT COUNT(*)
            FROM referrals
            WHERE referrer_id = p_user_id AND status = 'completed'
        ),
        total_reviews = (
            SELECT COUNT(*)
            FROM reviews
            WHERE user_id = p_user_id AND is_published = TRUE
        ),
        average_rating_given = (
            SELECT AVG(rating)
            FROM reviews
            WHERE user_id = p_user_id
        ),
        last_calculated_at = NOW()
    WHERE user_id = p_user_id;
END//

-- Procédure pour générer des statistiques quotidiennes
CREATE PROCEDURE generate_daily_sales_stats(
    IN p_date DATE
)
BEGIN
    INSERT INTO daily_sales_stats (
        event_id,
        stat_date,
        tickets_sold,
        revenue,
        refunds,
        refund_amount,
        new_views,
        conversion_rate
    )
    SELECT 
        tc.event_id,
        p_date,
        COUNT(DISTINCT t.id) as tickets_sold,
        COALESCE(SUM(oi.total_price), 0) as revenue,
        COUNT(DISTINCT CASE WHEN t.status IN ('cancelled', 'refunded') THEN t.id END) as refunds,
        COALESCE(SUM(CASE WHEN t.status IN ('cancelled', 'refunded') THEN oi.unit_price END), 0) as refund_amount,
        COUNT(DISTINCT ev.id) as new_views,
        CASE 
            WHEN COUNT(DISTINCT ev.user_id) > 0 
            THEN (COUNT(DISTINCT t.user_id) * 100.0 / COUNT(DISTINCT ev.user_id))
            ELSE 0 
        END as conversion_rate
    FROM events e
    LEFT JOIN ticket_categories tc ON tc.event_id = e.id
    LEFT JOIN tickets t ON t.ticket_category_id = tc.id AND DATE(t.created_at) = p_date
    LEFT JOIN order_items oi ON oi.ticket_category_id = tc.id
    LEFT JOIN orders o ON o.id = oi.order_id AND DATE(o.created_at) = p_date AND o.status = 'completed'
    LEFT JOIN event_views ev ON ev.event_id = e.id AND DATE(ev.viewed_at) = p_date
    GROUP BY tc.event_id
    ON DUPLICATE KEY UPDATE
        tickets_sold = VALUES(tickets_sold),
        revenue = VALUES(revenue),
        refunds = VALUES(refunds),
        refund_amount = VALUES(refund_amount),
        new_views = VALUES(new_views),
        conversion_rate = VALUES(conversion_rate);
END//

-- ============================================================================
-- 4. TARIFICATION DYNAMIQUE
-- ============================================================================

-- Procédure pour appliquer la tarification dynamique
CREATE PROCEDURE apply_dynamic_pricing(
    IN p_ticket_category_id BIGINT
)
BEGIN
    DECLARE v_quantity_total INT;
    DECLARE v_quantity_sold INT;
    DECLARE v_current_price DECIMAL(10, 2);
    DECLARE v_original_price DECIMAL(10, 2);
    DECLARE v_sold_percentage INT;
    DECLARE v_price_multiplier DECIMAL(5, 2);
    DECLARE v_new_price DECIMAL(10, 2);
    
    -- Récupérer les infos du billet
    SELECT 
        quantity_total, 
        quantity_sold, 
        price, 
        COALESCE(original_price, price)
    INTO 
        v_quantity_total, 
        v_quantity_sold, 
        v_current_price, 
        v_original_price
    FROM ticket_categories
    WHERE id = p_ticket_category_id;
    
    -- Calculer le pourcentage vendu
    SET v_sold_percentage = (v_quantity_sold * 100 / v_quantity_total);
    
    -- Trouver la règle de tarification applicable
    SELECT price_multiplier INTO v_price_multiplier
    FROM dynamic_pricing_rules
    WHERE ticket_category_id = p_ticket_category_id
      AND is_active = TRUE
      AND threshold_percentage <= v_sold_percentage
    ORDER BY threshold_percentage DESC
    LIMIT 1;
    
    IF v_price_multiplier IS NOT NULL THEN
        SET v_new_price = ROUND(v_original_price * v_price_multiplier, 2);
        
        IF v_new_price != v_current_price THEN
            -- Mettre à jour le prix
            UPDATE ticket_categories
            SET price = v_new_price
            WHERE id = p_ticket_category_id;
            
            -- Enregistrer dans l'historique
            INSERT INTO ticket_price_history (
                ticket_category_id,
                old_price,
                new_price,
                reason
            ) VALUES (
                p_ticket_category_id,
                v_current_price,
                v_new_price,
                'dynamic_pricing'
            );
        END IF;
    END IF;
END//

-- ============================================================================
-- 5. LISTE D'ATTENTE
-- ============================================================================

-- Procédure pour notifier la liste d'attente
CREATE PROCEDURE notify_waiting_list(
    IN p_ticket_category_id BIGINT,
    IN p_available_quantity INT
)
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_user_id BIGINT;
    DECLARE v_email VARCHAR(255);
    DECLARE v_quantity_requested INT;
    DECLARE v_notified INT DEFAULT 0;
    
    DECLARE cur CURSOR FOR 
        SELECT user_id, email, quantity_requested
        FROM waiting_list
        WHERE ticket_category_id = p_ticket_category_id
          AND status = 'waiting'
        ORDER BY priority_score DESC, joined_at ASC;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN cur;
    
    read_loop: LOOP
        FETCH cur INTO v_user_id, v_email, v_quantity_requested;
        IF done OR v_notified >= p_available_quantity THEN
            LEAVE read_loop;
        END IF;
        
        -- Créer une notification
        INSERT INTO notifications (
            user_id,
            type,
            title,
            message,
            channel,
            reference_type,
            reference_id
        ) VALUES (
            v_user_id,
            'waiting_list',
            'Billets disponibles',
            'Des billets sont maintenant disponibles pour l\'événement que vous suivez',
            'email',
            'ticket_category',
            p_ticket_category_id
        );
        
        -- Mettre à jour le statut
        UPDATE waiting_list
        SET 
            status = 'notified',
            notified_at = NOW(),
            expires_at = DATE_ADD(NOW(), INTERVAL 24 HOUR)
        WHERE user_id = v_user_id AND ticket_category_id = p_ticket_category_id;
        
        SET v_notified = v_notified + v_quantity_requested;
    END LOOP;
    
    CLOSE cur;
END//

-- ============================================================================
-- 6. RECOMMANDATIONS
-- ============================================================================

-- Procédure pour obtenir des recommandations d'événements personnalisées
CREATE PROCEDURE get_recommended_events(
    IN p_user_id BIGINT,
    IN p_limit INT
)
BEGIN
    -- Recommandations basées sur :
    -- 1. Catégories favorites
    -- 2. Historique d'achat
    -- 3. Recherches récentes
    -- 4. Événements populaires
    
    SELECT DISTINCT
        e.*,
        (
            -- Score basé sur la catégorie favorite
            CASE WHEN e.category_id = us.favorite_category_id THEN 50 ELSE 0 END +
            -- Score basé sur les recherches
            (SELECT COUNT(*) * 10 FROM search_history sh 
             WHERE sh.user_id = p_user_id 
             AND (e.title LIKE CONCAT('%', sh.search_query, '%') 
                  OR e.description LIKE CONCAT('%', sh.search_query, '%'))
             LIMIT 5
            ) +
            -- Score basé sur la popularité
            (es.total_tickets_sold / NULLIF(e.total_capacity, 0) * 30) +
            -- Score basé sur la note
            (es.average_rating * 10)
        ) as recommendation_score
    FROM events e
    INNER JOIN event_statistics es ON es.event_id = e.id
    LEFT JOIN user_statistics us ON us.user_id = p_user_id
    WHERE e.status = 'published'
      AND e.start_date > NOW()
      AND e.id NOT IN (
          SELECT tc.event_id 
          FROM tickets t 
          INNER JOIN ticket_categories tc ON tc.id = t.ticket_category_id
          WHERE t.user_id = p_user_id
      )
      AND e.id NOT IN (
          SELECT event_id FROM favorites WHERE user_id = p_user_id
      )
    ORDER BY recommendation_score DESC, e.start_date ASC
    LIMIT p_limit;
END//

-- ============================================================================
-- 7. RECHERCHE AVANCÉE
-- ============================================================================

-- Procédure pour recherche d'événements avec filtres
CREATE PROCEDURE search_events(
    IN p_user_id BIGINT,
    IN p_query VARCHAR(255),
    IN p_category_id INT,
    IN p_min_price DECIMAL(10, 2),
    IN p_max_price DECIMAL(10, 2),
    IN p_start_date DATE,
    IN p_end_date DATE,
    IN p_location VARCHAR(255),
    IN p_offset INT,
    IN p_limit INT
)
BEGIN
    -- Enregistrer la recherche dans l'historique
    IF p_user_id IS NOT NULL THEN
        INSERT INTO search_history (user_id, search_query, filters, results_count)
        VALUES (
            p_user_id,
            COALESCE(p_query, ''),
            JSON_OBJECT(
                'category_id', p_category_id,
                'min_price', p_min_price,
                'max_price', p_max_price,
                'start_date', p_start_date,
                'end_date', p_end_date,
                'location', p_location
            ),
            0 -- Sera mis à jour après
        );
    END IF;
    
    -- Rechercher les événements
    SELECT 
        e.*,
        ec.name as category_name,
        es.average_rating,
        es.total_reviews,
        (SELECT MIN(price) FROM ticket_categories WHERE event_id = e.id) as min_price,
        (SELECT MAX(price) FROM ticket_categories WHERE event_id = e.id) as max_price,
        (SELECT SUM(quantity_total - quantity_sold) FROM ticket_categories WHERE event_id = e.id) as available_tickets
    FROM events e
    INNER JOIN event_categories ec ON e.category_id = ec.id
    LEFT JOIN event_statistics es ON es.event_id = e.id
    WHERE e.status = 'published'
      AND e.start_date > NOW()
      AND (p_query IS NULL OR MATCH(e.title, e.description, e.location) AGAINST(p_query IN NATURAL LANGUAGE MODE))
      AND (p_category_id IS NULL OR e.category_id = p_category_id)
      AND (p_start_date IS NULL OR DATE(e.start_date) >= p_start_date)
      AND (p_end_date IS NULL OR DATE(e.end_date) <= p_end_date)
      AND (p_location IS NULL OR e.location LIKE CONCAT('%', p_location, '%'))
      AND (
          p_min_price IS NULL OR p_max_price IS NULL OR
          e.id IN (
              SELECT event_id FROM ticket_categories 
              WHERE price BETWEEN COALESCE(p_min_price, 0) AND COALESCE(p_max_price, 999999)
          )
      )
    ORDER BY 
        CASE WHEN e.is_featured THEN 0 ELSE 1 END,
        es.average_rating DESC,
        e.start_date ASC
    LIMIT p_offset, p_limit;
END//

DELIMITER ;

-- ============================================================================
-- FIN DES PROCÉDURES STOCKÉES
-- ============================================================================

