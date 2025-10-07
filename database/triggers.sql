-- ============================================================================
-- TRIGGERS - AUTOMATISATION & INTÉGRITÉ DES DONNÉES
-- ============================================================================

DELIMITER //

-- ============================================================================
-- 1. GESTION DES STATISTIQUES AUTOMATIQUES
-- ============================================================================

-- Mise à jour automatique du compteur de favoris
CREATE TRIGGER after_favorite_insert
AFTER INSERT ON favorites
FOR EACH ROW
BEGIN
    UPDATE events 
    SET favorites_count = favorites_count + 1 
    WHERE id = NEW.event_id;
    
    UPDATE event_statistics 
    SET total_favorites = total_favorites + 1 
    WHERE event_id = NEW.event_id;
END//

CREATE TRIGGER after_favorite_delete
AFTER DELETE ON favorites
FOR EACH ROW
BEGIN
    UPDATE events 
    SET favorites_count = GREATEST(0, favorites_count - 1) 
    WHERE id = OLD.event_id;
    
    UPDATE event_statistics 
    SET total_favorites = GREATEST(0, total_favorites - 1) 
    WHERE event_id = OLD.event_id;
END//

-- Mise à jour automatique du compteur de vues
CREATE TRIGGER after_event_view_insert
AFTER INSERT ON event_views
FOR EACH ROW
BEGIN
    UPDATE events 
    SET views_count = views_count + 1 
    WHERE id = NEW.event_id;
    
    UPDATE event_statistics 
    SET total_views = total_views + 1 
    WHERE event_id = NEW.event_id;
END//

-- ============================================================================
-- 2. GESTION DES BILLETS
-- ============================================================================

-- Génération automatique du numéro de billet
CREATE TRIGGER before_ticket_insert
BEFORE INSERT ON tickets
FOR EACH ROW
BEGIN
    DECLARE event_prefix VARCHAR(10);
    DECLARE ticket_count INT;
    
    -- Générer un numéro de billet unique
    SELECT COUNT(*) INTO ticket_count 
    FROM tickets 
    WHERE ticket_category_id = NEW.ticket_category_id;
    
    SET NEW.ticket_number = CONCAT(
        'TKT-',
        LPAD(NEW.ticket_category_id, 6, '0'),
        '-',
        LPAD(ticket_count + 1, 6, '0')
    );
    
    -- Générer les données QR code
    SET NEW.qr_code_data = CONCAT(
        'AIOLIA-',
        UUID(),
        '-',
        NEW.ticket_number
    );
    
    -- Définir le propriétaire initial et actuel
    IF NEW.original_owner_id IS NULL THEN
        SET NEW.original_owner_id = NEW.user_id;
    END IF;
    
    IF NEW.current_owner_id IS NULL THEN
        SET NEW.current_owner_id = NEW.user_id;
    END IF;
END//

-- Mise à jour du quota de billets vendus
CREATE TRIGGER after_ticket_insert
AFTER INSERT ON tickets
FOR EACH ROW
BEGIN
    UPDATE ticket_categories 
    SET quantity_sold = quantity_sold + 1 
    WHERE id = NEW.ticket_category_id;
    
    -- Mise à jour des statistiques
    UPDATE event_statistics es
    INNER JOIN ticket_categories tc ON tc.event_id = es.event_id
    SET es.total_tickets_sold = es.total_tickets_sold + 1
    WHERE tc.id = NEW.ticket_category_id;
END//

-- Gestion de l'annulation de billet
CREATE TRIGGER after_ticket_cancel
AFTER UPDATE ON tickets
FOR EACH ROW
BEGIN
    IF NEW.status IN ('cancelled', 'refunded') AND OLD.status NOT IN ('cancelled', 'refunded') THEN
        UPDATE ticket_categories 
        SET quantity_sold = GREATEST(0, quantity_sold - 1) 
        WHERE id = NEW.ticket_category_id;
        
        UPDATE event_statistics es
        INNER JOIN ticket_categories tc ON tc.event_id = es.event_id
        SET es.total_refunds = es.total_refunds + 1
        WHERE tc.id = NEW.ticket_category_id;
    END IF;
END//

-- ============================================================================
-- 3. GESTION DES COMMANDES
-- ============================================================================

-- Génération automatique du numéro de commande
CREATE TRIGGER before_order_insert
BEFORE INSERT ON orders
FOR EACH ROW
BEGIN
    SET NEW.order_number = CONCAT(
        'ORD-',
        DATE_FORMAT(NOW(), '%Y%m%d'),
        '-',
        LPAD(FLOOR(RAND() * 999999), 6, '0')
    );
END//

-- Mise à jour des statistiques après validation de commande
CREATE TRIGGER after_order_complete
AFTER UPDATE ON orders
FOR EACH ROW
BEGIN
    IF NEW.status = 'completed' AND OLD.status != 'completed' THEN
        -- Mise à jour du revenu total
        UPDATE event_statistics es
        INNER JOIN order_items oi ON oi.order_id = NEW.id
        INNER JOIN ticket_categories tc ON tc.id = oi.ticket_category_id
        SET es.total_revenue = es.total_revenue + NEW.total_amount
        WHERE es.event_id = tc.event_id;
        
        -- Mise à jour des statistiques utilisateur
        UPDATE user_statistics 
        SET 
            total_tickets_purchased = total_tickets_purchased + (
                SELECT SUM(quantity) FROM order_items WHERE order_id = NEW.id
            ),
            total_spent = total_spent + NEW.total_amount,
            last_purchase_at = NEW.completed_at
        WHERE user_id = NEW.user_id;
    END IF;
END//

-- ============================================================================
-- 4. GESTION DU PORTEFEUILLE & POINTS DE FIDÉLITÉ
-- ============================================================================

-- Création automatique du portefeuille pour nouveau utilisateur
CREATE TRIGGER after_user_insert
AFTER INSERT ON users
FOR EACH ROW
BEGIN
    -- Créer le portefeuille
    INSERT INTO wallet (user_id, loyalty_points, total_earned_points, balance)
    VALUES (NEW.id, 0, 0, 0.00);
    
    -- Créer les préférences de notification par défaut
    INSERT INTO notification_preferences (user_id)
    VALUES (NEW.id);
    
    -- Créer les statistiques utilisateur
    INSERT INTO user_statistics (user_id)
    VALUES (NEW.id);
END//

-- Mise à jour automatique du portefeuille
CREATE TRIGGER after_wallet_transaction
AFTER INSERT ON wallet_transactions
FOR EACH ROW
BEGIN
    IF NEW.transaction_type = 'credit' THEN
        UPDATE wallet 
        SET 
            balance = balance + NEW.amount,
            loyalty_points = loyalty_points + NEW.points,
            total_earned_points = total_earned_points + NEW.points
        WHERE id = NEW.wallet_id;
    ELSE
        UPDATE wallet 
        SET 
            balance = balance - NEW.amount,
            loyalty_points = GREATEST(0, loyalty_points - NEW.points)
        WHERE id = NEW.wallet_id;
    END IF;
END//

-- Attribution automatique de points de fidélité après achat
CREATE TRIGGER after_order_loyalty_points
AFTER UPDATE ON orders
FOR EACH ROW
BEGIN
    DECLARE points_to_award INT;
    DECLARE wallet_id_var BIGINT;
    
    IF NEW.status = 'completed' AND OLD.status != 'completed' THEN
        -- Calculer les points (1 point par tranche de 1000 MGA dépensés)
        SET points_to_award = FLOOR(NEW.total_amount / 1000);
        
        -- Récupérer l'ID du portefeuille
        SELECT id INTO wallet_id_var FROM wallet WHERE user_id = NEW.user_id;
        
        IF points_to_award > 0 THEN
            INSERT INTO wallet_transactions (
                wallet_id, 
                transaction_type, 
                amount, 
                points, 
                description, 
                reference_type, 
                reference_id
            ) VALUES (
                wallet_id_var,
                'credit',
                0,
                points_to_award,
                CONCAT('Points de fidélité pour commande ', NEW.order_number),
                'order',
                NEW.id
            );
        END IF;
    END IF;
END//

-- ============================================================================
-- 5. GESTION DES CODES PROMO
-- ============================================================================

-- Incrémenter l'utilisation du code promo
CREATE TRIGGER after_promo_code_use
AFTER INSERT ON promo_code_usage
FOR EACH ROW
BEGIN
    UPDATE promo_codes 
    SET current_uses = current_uses + 1 
    WHERE id = NEW.promo_code_id;
END//

-- ============================================================================
-- 6. GESTION DES RÉSERVATIONS (PANIER)
-- ============================================================================

-- Réserver les billets lors de l'ajout au panier
CREATE TRIGGER after_cart_item_insert
AFTER INSERT ON cart_items
FOR EACH ROW
BEGIN
    UPDATE ticket_categories 
    SET quantity_reserved = quantity_reserved + NEW.quantity 
    WHERE id = NEW.ticket_category_id;
END//

-- Libérer les billets lors de la suppression du panier
CREATE TRIGGER after_cart_item_delete
AFTER DELETE ON cart_items
FOR EACH ROW
BEGIN
    UPDATE ticket_categories 
    SET quantity_reserved = GREATEST(0, quantity_reserved - OLD.quantity) 
    WHERE id = OLD.ticket_category_id;
END//

-- Mise à jour de la réservation
CREATE TRIGGER after_cart_item_update
AFTER UPDATE ON cart_items
FOR EACH ROW
BEGIN
    IF NEW.quantity != OLD.quantity THEN
        UPDATE ticket_categories 
        SET quantity_reserved = quantity_reserved + (NEW.quantity - OLD.quantity) 
        WHERE id = NEW.ticket_category_id;
    END IF;
END//

-- ============================================================================
-- 7. GESTION DES AVIS
-- ============================================================================

-- Mise à jour de la moyenne des notes
CREATE TRIGGER after_review_insert
AFTER INSERT ON reviews
FOR EACH ROW
BEGIN
    UPDATE event_statistics 
    SET 
        total_reviews = total_reviews + 1,
        average_rating = (
            SELECT AVG(rating) 
            FROM reviews 
            WHERE event_id = NEW.event_id AND is_published = TRUE
        )
    WHERE event_id = NEW.event_id;
    
    -- Ajouter des points de fidélité pour l'avis
    INSERT INTO wallet_transactions (
        wallet_id, 
        transaction_type, 
        amount, 
        points, 
        description, 
        reference_type, 
        reference_id
    )
    SELECT 
        w.id,
        'credit',
        0,
        5,
        'Points pour avis publié',
        'review',
        NEW.id
    FROM wallet w
    WHERE w.user_id = NEW.user_id;
END//

CREATE TRIGGER after_review_update
AFTER UPDATE ON reviews
FOR EACH ROW
BEGIN
    IF NEW.rating != OLD.rating OR NEW.is_published != OLD.is_published THEN
        UPDATE event_statistics 
        SET 
            average_rating = (
                SELECT AVG(rating) 
                FROM reviews 
                WHERE event_id = NEW.event_id AND is_published = TRUE
            )
        WHERE event_id = NEW.event_id;
    END IF;
END//

CREATE TRIGGER after_review_delete
AFTER DELETE ON reviews
FOR EACH ROW
BEGIN
    UPDATE event_statistics 
    SET 
        total_reviews = GREATEST(0, total_reviews - 1),
        average_rating = COALESCE((
            SELECT AVG(rating) 
            FROM reviews 
            WHERE event_id = OLD.event_id AND is_published = TRUE
        ), 0)
    WHERE event_id = OLD.event_id;
END//

-- ============================================================================
-- 8. GESTION DES PARRAINAGES
-- ============================================================================

-- Récompenser le parrain quand le filleul s'inscrit
CREATE TRIGGER after_referral_complete
AFTER UPDATE ON referrals
FOR EACH ROW
BEGIN
    DECLARE referrer_wallet_id BIGINT;
    DECLARE reward_points INT DEFAULT 50;
    
    IF NEW.status = 'completed' AND OLD.status != 'completed' THEN
        -- Récupérer l'ID du portefeuille du parrain
        SELECT id INTO referrer_wallet_id 
        FROM wallet 
        WHERE user_id = NEW.referrer_id;
        
        -- Attribuer les points de fidélité
        INSERT INTO wallet_transactions (
            wallet_id, 
            transaction_type, 
            amount, 
            points, 
            description, 
            reference_type, 
            reference_id
        ) VALUES (
            referrer_wallet_id,
            'credit',
            0,
            reward_points,
            'Parrainage réussi',
            'referral',
            NEW.id
        );
        
        -- Mettre à jour le statut
        UPDATE referrals 
        SET status = 'rewarded', rewarded_at = NOW() 
        WHERE id = NEW.id;
        
        -- Mettre à jour les statistiques
        UPDATE user_statistics 
        SET total_referrals = total_referrals + 1 
        WHERE user_id = NEW.referrer_id;
    END IF;
END//

-- ============================================================================
-- 9. GESTION DES TRANSFERTS DE BILLETS
-- ============================================================================

-- Transférer le billet au nouveau propriétaire
CREATE TRIGGER after_ticket_transfer_accept
AFTER UPDATE ON ticket_transfers
FOR EACH ROW
BEGIN
    IF NEW.status = 'accepted' AND OLD.status != 'accepted' THEN
        UPDATE tickets 
        SET 
            current_owner_id = NEW.to_user_id,
            status = 'transferred'
        WHERE id = NEW.ticket_id;
    END IF;
END//

-- ============================================================================
-- 10. LOGS D'AUDIT AUTOMATIQUES
-- ============================================================================

-- Audit des modifications d'événements
CREATE TRIGGER after_event_update_audit
AFTER UPDATE ON events
FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (
        user_id, 
        action, 
        entity_type, 
        entity_id, 
        old_values, 
        new_values
    ) VALUES (
        NEW.organizer_id,
        'update',
        'event',
        NEW.id,
        JSON_OBJECT(
            'title', OLD.title,
            'status', OLD.status,
            'start_date', OLD.start_date,
            'end_date', OLD.end_date
        ),
        JSON_OBJECT(
            'title', NEW.title,
            'status', NEW.status,
            'start_date', NEW.start_date,
            'end_date', NEW.end_date
        )
    );
END//

-- Audit des paiements
CREATE TRIGGER after_payment_update_audit
AFTER UPDATE ON payments
FOR EACH ROW
BEGIN
    IF NEW.status != OLD.status THEN
        INSERT INTO audit_logs (
            user_id, 
            action, 
            entity_type, 
            entity_id, 
            old_values, 
            new_values
        )
        SELECT 
            o.user_id,
            'payment_status_change',
            'payment',
            NEW.id,
            JSON_OBJECT('status', OLD.status),
            JSON_OBJECT('status', NEW.status)
        FROM orders o
        WHERE o.id = NEW.order_id;
    END IF;
END//

-- ============================================================================
-- 11. ALERTES & NOTIFICATIONS AUTOMATIQUES
-- ============================================================================

-- Alerte stock bas
CREATE TRIGGER after_ticket_sale_check_stock
AFTER UPDATE ON ticket_categories
FOR EACH ROW
BEGIN
    DECLARE remaining_tickets INT;
    DECLARE event_organizer_id BIGINT;
    
    SET remaining_tickets = NEW.quantity_total - NEW.quantity_sold;
    
    -- Si moins de 10% de billets restants
    IF remaining_tickets <= (NEW.quantity_total * 0.1) AND remaining_tickets > 0 THEN
        -- Récupérer l'organisateur
        SELECT organizer_id INTO event_organizer_id 
        FROM events 
        WHERE id = NEW.event_id;
        
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
            event_organizer_id,
            'almost_full',
            'Stock de billets presque épuisé',
            CONCAT('Il ne reste que ', remaining_tickets, ' billets pour la catégorie "', NEW.name, '"'),
            'in_app',
            'ticket_category',
            NEW.id
        );
    END IF;
END//

-- Notification automatique après achat
CREATE TRIGGER after_order_complete_notify
AFTER UPDATE ON orders
FOR EACH ROW
BEGIN
    IF NEW.status = 'completed' AND OLD.status != 'completed' THEN
        INSERT INTO notifications (
            user_id, 
            type, 
            title, 
            message, 
            channel, 
            reference_type, 
            reference_id
        ) VALUES 
        (
            NEW.user_id,
            'order_confirmation',
            'Commande confirmée',
            CONCAT('Votre commande ', NEW.order_number, ' a été confirmée avec succès'),
            'email',
            'order',
            NEW.id
        ),
        (
            NEW.user_id,
            'order_confirmation',
            'Commande confirmée',
            CONCAT('Votre commande ', NEW.order_number, ' a été confirmée avec succès'),
            'in_app',
            'order',
            NEW.id
        );
    END IF;
END//

DELIMITER ;

-- ============================================================================
-- FIN DES TRIGGERS
-- ============================================================================

