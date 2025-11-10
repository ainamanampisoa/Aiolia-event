-- Aiolia Event Platform - Fonctions & Déclencheurs métiers
-- Ce fichier regroupe les fonctions stockées et triggers liés aux règles de gestion.

SET search_path TO aiolia, public;

-- Génération d’un numéro de facture unique ------------------------------------------

CREATE OR REPLACE FUNCTION fn_generate_invoice_number()
RETURNS TEXT
LANGUAGE plpgsql
AS $$
DECLARE
    next_seq BIGINT;
BEGIN
    next_seq := nextval('invoice_number_seq');
    RETURN to_char(now(), 'YYYY') || '-' || lpad(next_seq::text, 8, '0');
END;
$$;

CREATE OR REPLACE FUNCTION trg_set_invoice_number()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
BEGIN
    IF TG_OP = 'UPDATE' AND OLD.status = NEW.status THEN
        RETURN NEW;
    END IF;
    IF NEW.invoice_number IS NULL THEN
        NEW.invoice_number := fn_generate_invoice_number();
    END IF;
    IF NEW.confirmed_at IS NULL AND NEW.status IN ('paid', 'refunded') THEN
        NEW.confirmed_at := now();
    END IF;
    RETURN NEW;
END;
$$;

DROP TRIGGER IF EXISTS trg_orders_set_invoice ON orders;
CREATE TRIGGER trg_orders_set_invoice
BEFORE INSERT OR UPDATE OF status ON orders
FOR EACH ROW
WHEN (NEW.status IN ('paid', 'refunded'))
EXECUTE FUNCTION trg_set_invoice_number();

-- Historisation des statuts de commande --------------------------------------------

CREATE OR REPLACE FUNCTION trg_log_order_status_history()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
DECLARE
    v_actor UUID := NULL;
    v_setting TEXT;
BEGIN
    v_setting := current_setting('aiolia.current_user_id', true);
    IF v_setting IS NOT NULL AND v_setting <> '' THEN
        BEGIN
            v_actor := v_setting::uuid;
        EXCEPTION
            WHEN others THEN
                v_actor := NULL;
        END;
    END IF;

    IF TG_OP = 'INSERT' OR (TG_OP = 'UPDATE' AND NEW.status IS DISTINCT FROM OLD.status) THEN
        INSERT INTO order_status_history (
            order_id,
            user_id,
            status_from,
            status_to,
            amount_snapshot,
            discount_snapshot,
            wallet_snapshot,
            metadata,
            changed_at
        )
        VALUES (
            NEW.id,
            v_actor,
            CASE WHEN TG_OP = 'INSERT' THEN NULL ELSE OLD.status END,
            NEW.status,
            NEW.total_amount,
            NEW.discount_total,
            NEW.wallet_amount,
            jsonb_build_object(
                'trigger', 'order_status_change',
                'operation', TG_OP,
                'timestamp', now()
            ),
            now()
        );
    END IF;

    RETURN NEW;
END;
$$;

DROP TRIGGER IF EXISTS trg_orders_status_history ON orders;
CREATE TRIGGER trg_orders_status_history
AFTER INSERT OR UPDATE OF status ON orders
FOR EACH ROW
EXECUTE FUNCTION trg_log_order_status_history();

-- Actualisation automatique du portefeuille -----------------------------------------

CREATE OR REPLACE FUNCTION trg_sync_wallet_balance()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
DECLARE
    amount_delta NUMERIC(14,2) := 0;
    points_delta INTEGER := 0;
BEGIN
    IF (TG_OP = 'INSERT' AND NEW.status = 'completed')
       OR (TG_OP = 'UPDATE' AND NEW.status = 'completed' AND OLD.status <> 'completed')
    THEN
        CASE NEW.transaction_type
            WHEN 'credit' THEN
                amount_delta := NEW.amount;
            WHEN 'debit' THEN
                amount_delta := NEW.amount * -1;
            WHEN 'points_credit' THEN
                points_delta := NEW.points_delta;
            WHEN 'points_debit' THEN
                points_delta := NEW.points_delta * -1;
        END CASE;

        UPDATE wallets
        SET
            balance = balance + amount_delta,
            points_balance = points_balance + points_delta,
            updated_at = now()
        WHERE id = NEW.wallet_id;
    END IF;
    RETURN NEW;
END;
$$;

DROP TRIGGER IF EXISTS trg_wallet_transactions_sync ON wallet_transactions;
CREATE TRIGGER trg_wallet_transactions_sync
AFTER INSERT OR UPDATE OF status ON wallet_transactions
FOR EACH ROW
EXECUTE FUNCTION trg_sync_wallet_balance();

-- Remise à zéro si transaction rebasculée

CREATE OR REPLACE FUNCTION trg_wallet_transaction_revert()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
DECLARE
    amount_delta NUMERIC(14,2) := 0;
    points_delta INTEGER := 0;
BEGIN
    IF TG_OP = 'UPDATE'
       AND OLD.status = 'completed'
       AND NEW.status IN ('cancelled', 'failed')
    THEN
        CASE OLD.transaction_type
            WHEN 'credit' THEN amount_delta := OLD.amount * -1;
            WHEN 'debit' THEN amount_delta := OLD.amount;
            WHEN 'points_credit' THEN points_delta := OLD.points_delta * -1;
            WHEN 'points_debit' THEN points_delta := OLD.points_delta;
        END CASE;

        UPDATE wallets
        SET
            balance = balance + amount_delta,
            points_balance = points_balance + points_delta,
            updated_at = now()
        WHERE id = OLD.wallet_id;
    END IF;
    RETURN NEW;
END;
$$;

DROP TRIGGER IF EXISTS trg_wallet_transactions_revert ON wallet_transactions;
CREATE TRIGGER trg_wallet_transactions_revert
AFTER UPDATE OF status ON wallet_transactions
FOR EACH ROW
EXECUTE FUNCTION trg_wallet_transaction_revert();

-- Historisation des modifications de prix ------------------------------------------

CREATE OR REPLACE FUNCTION trg_log_ticket_price_history()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
DECLARE
    v_actor UUID := NULL;
    v_setting TEXT;
    v_base_changed BOOLEAN := FALSE;
    v_fee_changed BOOLEAN := FALSE;
    v_vat_changed BOOLEAN := FALSE;
    v_source TEXT := 'manual';
    v_reason TEXT := 'Mise à jour automatique du tarif';
BEGIN
    IF TG_OP <> 'UPDATE' THEN
        RETURN NEW;
    END IF;

    v_base_changed := COALESCE(OLD.base_price, -1) <> COALESCE(NEW.base_price, -1);
    v_fee_changed := COALESCE(OLD.service_fee, -1) <> COALESCE(NEW.service_fee, -1);
    v_vat_changed := COALESCE(OLD.vat_rate, -1) <> COALESCE(NEW.vat_rate, -1);

    IF NOT (v_base_changed OR v_fee_changed OR v_vat_changed) THEN
        RETURN NEW;
    END IF;

    v_setting := current_setting('aiolia.current_user_id', true);
    IF v_setting IS NOT NULL AND v_setting <> '' THEN
        BEGIN
            v_actor := v_setting::uuid;
        EXCEPTION
            WHEN others THEN
                v_actor := NULL;
        END;
    END IF;

    v_setting := current_setting('aiolia.price_change_source', true);
    IF v_setting IS NOT NULL AND v_setting <> '' THEN
        v_source := v_setting;
    END IF;
    IF v_source NOT IN ('manual', 'rule', 'promotion', 'system') THEN
        v_source := 'system';
    END IF;

    v_setting := current_setting('aiolia.price_change_reason', true);
    IF v_setting IS NOT NULL AND v_setting <> '' THEN
        v_reason := v_setting;
    END IF;

    INSERT INTO ticket_price_history (
        ticket_type_id,
        changed_by,
        change_source,
        previous_base_price,
        new_base_price,
        previous_service_fee,
        new_service_fee,
        previous_vat_rate,
        new_vat_rate,
        change_reason,
        metadata,
        changed_at
    )
    VALUES (
        NEW.id,
        v_actor,
        v_source,
        OLD.base_price,
        NEW.base_price,
        OLD.service_fee,
        NEW.service_fee,
        OLD.vat_rate,
        NEW.vat_rate,
        v_reason,
        jsonb_build_object(
            'trigger', 'ticket_price_update',
            'base_price_changed', v_base_changed,
            'service_fee_changed', v_fee_changed,
            'vat_rate_changed', v_vat_changed,
            'operation', TG_OP
        ),
        now()
    );

    RETURN NEW;
END;
$$;

DROP TRIGGER IF EXISTS trg_ticket_types_price_history ON ticket_types;
CREATE TRIGGER trg_ticket_types_price_history
AFTER UPDATE OF base_price, service_fee, vat_rate ON ticket_types
FOR EACH ROW
EXECUTE FUNCTION trg_log_ticket_price_history();

-- Gestion des quotas ---------------------------------------------------------------

CREATE OR REPLACE FUNCTION adjust_ticket_quota_usage(p_ticket_type_id UUID, p_delta INTEGER)
RETURNS VOID
LANGUAGE plpgsql
AS $$
DECLARE
    rec RECORD;
    v_new_sold INTEGER;
BEGIN
    IF p_delta = 0 THEN
        RETURN;
    END IF;

    FOR rec IN
        SELECT
            g.id AS quota_group_id,
            g.capacity_total,
            g.capacity_sold,
            g.enforce_limits,
            l.weight
        FROM ticket_quota_groups g
        JOIN ticket_quota_links l ON l.quota_group_id = g.id
        WHERE l.ticket_type_id = p_ticket_type_id
    LOOP
        v_new_sold := rec.capacity_sold + (rec.weight * p_delta);

        IF v_new_sold < 0 THEN
            v_new_sold := 0;
        END IF;

        IF rec.enforce_limits AND v_new_sold > rec.capacity_total THEN
            RAISE EXCEPTION
                'La capacité du quota % serait dépassée (tentative % > %)',
                rec.quota_group_id,
                v_new_sold,
                rec.capacity_total
            USING ERRCODE = '23514';
        END IF;

        UPDATE ticket_quota_groups
        SET
            capacity_sold = v_new_sold,
            updated_at = now()
        WHERE id = rec.quota_group_id;
    END LOOP;
END;
$$;

-- Rafraichissement des statistiques utilisateur -------------------------------------

CREATE OR REPLACE FUNCTION refresh_user_statistics(p_user_id UUID)
RETURNS VOID
LANGUAGE plpgsql
AS $$
DECLARE
    v_events_count INTEGER := 0;
    v_tickets_count INTEGER := 0;
    v_lifetime_spend NUMERIC(14,2) := 0;
    v_last_purchase TIMESTAMPTZ;
    v_favorite_category UUID;
BEGIN
    WITH base_data AS (
        SELECT
            o.id AS order_id,
            o.user_id,
            o.total_amount,
            o.discount_total,
            o.wallet_amount,
            o.confirmed_at,
            t.id AS ticket_id,
            t.status AS ticket_status,
            tt.id AS ticket_type_id,
            e.id AS event_id,
            e.primary_category_id
        FROM orders o
        LEFT JOIN order_items oi ON oi.order_id = o.id
        LEFT JOIN ticket_types tt ON tt.id = oi.ticket_type_id
        LEFT JOIN tickets t ON t.order_item_id = oi.id
        LEFT JOIN events e ON e.id = tt.event_id
        WHERE o.user_id = p_user_id
          AND o.status IN ('paid', 'refunded')
    ), filtered_tickets AS (
        SELECT *
        FROM base_data
        WHERE ticket_id IS NOT NULL
          AND ticket_status IN ('valid', 'used', 'transferred')
    )
    SELECT
        COALESCE(COUNT(DISTINCT CASE WHEN ticket_id IS NOT NULL THEN event_id END), 0),
        COALESCE(COUNT(DISTINCT ticket_id), 0),
        MAX(confirmed_at)
    INTO
        v_events_count,
        v_tickets_count,
        v_last_purchase
    FROM base_data;

    SELECT
        COALESCE(SUM(o.total_amount - o.discount_total - o.wallet_amount), 0)
    INTO
        v_lifetime_spend
    FROM orders o
    WHERE o.user_id = p_user_id
      AND o.status IN ('paid', 'refunded');

    SELECT primary_category_id
    INTO v_favorite_category
    FROM filtered_tickets
    WHERE primary_category_id IS NOT NULL
    GROUP BY primary_category_id
    ORDER BY COUNT(*) DESC, MAX(confirmed_at) DESC
    LIMIT 1;

    UPDATE user_statistics
    SET
        events_attended_count = v_events_count,
        tickets_owned_count = v_tickets_count,
        lifetime_spend = v_lifetime_spend,
        favorite_category_id = v_favorite_category,
        last_purchase_at = v_last_purchase,
        updated_at = now()
    WHERE id = p_user_id;

    IF NOT FOUND THEN
        INSERT INTO user_statistics (
            id,
            events_attended_count,
            tickets_owned_count,
            lifetime_spend,
            favorite_category_id,
            last_purchase_at,
            updated_at
        )
        VALUES (
            p_user_id,
            v_events_count,
            v_tickets_count,
            v_lifetime_spend,
            v_favorite_category,
            v_last_purchase,
            now()
        );
    END IF;
END;
$$;

CREATE OR REPLACE FUNCTION trg_refresh_user_stats_from_orders()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
BEGIN
    PERFORM refresh_user_statistics(NEW.user_id);
    RETURN NEW;
END;
$$;

DROP TRIGGER IF EXISTS trg_orders_refresh_stats ON orders;
CREATE TRIGGER trg_orders_refresh_stats
AFTER INSERT OR UPDATE OF status ON orders
FOR EACH ROW
WHEN (NEW.status IN ('paid', 'refunded'))
EXECUTE FUNCTION trg_refresh_user_stats_from_orders();

CREATE OR REPLACE FUNCTION trg_refresh_user_stats_from_tickets()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
BEGIN
    IF NEW.owner_user_id IS NOT NULL THEN
        PERFORM refresh_user_statistics(NEW.owner_user_id);
    END IF;
    IF TG_OP = 'UPDATE' AND OLD.owner_user_id IS NOT NULL AND OLD.owner_user_id <> NEW.owner_user_id THEN
        PERFORM refresh_user_statistics(OLD.owner_user_id);
    END IF;
    RETURN NEW;
END;
$$;

DROP TRIGGER IF EXISTS trg_tickets_refresh_stats ON tickets;
CREATE TRIGGER trg_tickets_refresh_stats
AFTER INSERT OR UPDATE OF owner_user_id, status ON tickets
FOR EACH ROW
EXECUTE FUNCTION trg_refresh_user_stats_from_tickets();

-- Logs automatiques sur les notifications ------------------------------------------

CREATE OR REPLACE FUNCTION trg_log_notification_history()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
BEGIN
    INSERT INTO notification_history (notification_id, status, message, created_at)
    VALUES (NEW.id, NEW.status, NULL, now());
    RETURN NEW;
END;
$$;

DROP TRIGGER IF EXISTS trg_notifications_history ON notifications;
CREATE TRIGGER trg_notifications_history
AFTER UPDATE OF status ON notifications
FOR EACH ROW
EXECUTE FUNCTION trg_log_notification_history();

-- Mise à jour automatique des quantités de billets vendus ---------------------------

CREATE OR REPLACE FUNCTION trg_sync_ticket_type_counters()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
DECLARE
    v_delta INTEGER := 0;
BEGIN
    IF TG_OP = 'UPDATE' AND NEW.ticket_type_id IS DISTINCT FROM OLD.ticket_type_id THEN
        IF OLD.status IN ('valid', 'used', 'transferred') THEN
            UPDATE ticket_types
            SET quantity_sold = GREATEST(quantity_sold - 1, 0)
            WHERE id = OLD.ticket_type_id;
            PERFORM adjust_ticket_quota_usage(OLD.ticket_type_id, -1);
        END IF;
        IF NEW.status IN ('valid', 'used', 'transferred') THEN
            UPDATE ticket_types
            SET quantity_sold = quantity_sold + 1
            WHERE id = NEW.ticket_type_id;
            PERFORM adjust_ticket_quota_usage(NEW.ticket_type_id, 1);
        END IF;
        RETURN NEW;
    END IF;

    IF TG_OP = 'INSERT' THEN
        IF NEW.status IN ('valid', 'used', 'transferred') THEN
            v_delta := 1;
        END IF;
    ELSIF TG_OP = 'UPDATE' THEN
        IF OLD.status IN ('valid', 'used', 'transferred') AND NEW.status IN ('cancelled', 'refunded') THEN
            v_delta := -1;
        ELSIF OLD.status IN ('cancelled', 'refunded') AND NEW.status IN ('valid', 'used', 'transferred') THEN
            v_delta := 1;
        ELSE
            RETURN NEW;
        END IF;
    ELSE
        RETURN NEW;
    END IF;

    IF v_delta <> 0 THEN
        UPDATE ticket_types
        SET quantity_sold = GREATEST(quantity_sold + v_delta, 0)
        WHERE id = NEW.ticket_type_id;

        PERFORM adjust_ticket_quota_usage(NEW.ticket_type_id, v_delta);
    END IF;

    RETURN NEW;
END;
$$;

DROP TRIGGER IF EXISTS trg_tickets_sync_counters ON tickets;
CREATE TRIGGER trg_tickets_sync_counters
AFTER INSERT OR UPDATE OF status ON tickets
FOR EACH ROW
EXECUTE FUNCTION trg_sync_ticket_type_counters();

-- Fin des fonctions -----------------------------------------------------------------

