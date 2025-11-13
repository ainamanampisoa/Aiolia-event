-- ============================================================
--  AIOLIA – LOGIQUE APPLICATIVE (FONCTIONS, VUES, TRIGGERS)
--  Génération : 2025-11-10
-- ============================================================

SET search_path TO aiolia, public;

-- ------------------------------------------------------------
-- 1. Vues matérialisées & vues utiles
-- ------------------------------------------------------------
DROP MATERIALIZED VIEW IF EXISTS aiolia.mv_user_monthly_spend;
CREATE MATERIALIZED VIEW aiolia.mv_user_monthly_spend AS
SELECT
    ti.customer_id AS user_id,
    date_trunc('month', ti.paid_at) AS month,
    SUM(ti.total_amount) AS total_spend
FROM ticket_invoices ti
WHERE ti.status IN ('paid', 'partially_paid')
  AND ti.paid_at IS NOT NULL
GROUP BY ti.customer_id, date_trunc('month', ti.paid_at);

COMMENT ON MATERIALIZED VIEW aiolia.mv_user_monthly_spend IS
    'Agrégation mensuelle des montants payés par utilisateur sur les factures tickets.';

CREATE OR REPLACE VIEW aiolia.vw_user_dashboard_summary AS
SELECT
    u.id AS user_id,
    u.first_name,
    u.last_name,
    u.role,
    COALESCE(w.balance, 0) AS wallet_balance,
    COALESCE(w.points_balance, 0) AS loyalty_points,
    COALESCE(s.events_attended, 0) AS events_attended,
    COALESCE(s.total_spend, 0) AS total_spend,
    s.last_event_at
FROM users u
LEFT JOIN wallets w ON w.user_id = u.id
LEFT JOIN user_event_stats s ON s.user_id = u.id;

COMMENT ON VIEW aiolia.vw_user_dashboard_summary IS
    'Vue synthétique utilisée pour le tableau de bord utilisateur (soldes, points et activité).';

CREATE OR REPLACE VIEW aiolia.vw_event_sales_summary AS
SELECT
    e.id AS event_id,
    e.title,
    e.starts_at,
    COALESCE(SUM(oi.quantity) FILTER (WHERE o.status IN ('paid', 'awaiting_payment')), 0) AS tickets_sold,
    COALESCE(SUM(oi.total_amount) FILTER (WHERE o.status IN ('paid', 'awaiting_payment')), 0) AS gross_revenue
FROM events e
LEFT JOIN ticket_types tt ON tt.event_id = e.id
LEFT JOIN order_items oi ON oi.ticket_type_id = tt.id
LEFT JOIN orders o ON o.id = oi.order_id
GROUP BY e.id, e.title, e.starts_at;

COMMENT ON VIEW aiolia.vw_event_sales_summary IS
    'Vue analytique des ventes par événement (billets vendus et revenu brut filtrés sur commandes payées).';

CREATE OR REPLACE VIEW aiolia.vw_subscription_payment_summary AS
SELECT
    si.subscription_id,
    os.organizer_profile_id,
    op.user_id AS organizer_user_id,
    op.organization_type,
    op.verification_status,
    si.customer_id,
    sp.status,
    si.total_amount,
    sp.paid_at
FROM subscription_invoices si
LEFT JOIN organizer_subscriptions os ON os.id = si.subscription_id
LEFT JOIN organizer_profiles op ON op.id = os.organizer_profile_id
LEFT JOIN subscription_payments sp ON sp.invoice_id = si.id;

COMMENT ON VIEW aiolia.vw_subscription_payment_summary IS
    'Vue de suivi des paiements d’abonnements avec état de vérification des organisateurs.';

CREATE OR REPLACE VIEW aiolia.vw_ticket_payments_detailed AS
SELECT
    tp.id AS payment_id,
    ti.invoice_number,
    ti.customer_id,
    ti.order_id,
    tp.provider,
    tp.status,
    tp.amount,
    tp.currency,
    tp.paid_at,
    tph.status_from,
    tph.status_to,
    tph.changed_at,
    tph.metadata
FROM ticket_payments tp
LEFT JOIN ticket_invoices ti ON ti.id = tp.invoice_id
LEFT JOIN ticket_payment_history tph ON tph.payment_id = tp.id;

COMMENT ON VIEW aiolia.vw_ticket_payments_detailed IS
    'Historique détaillé des paiements de billets incluant les transitions de statut.';

CREATE OR REPLACE VIEW aiolia.vw_subscription_payments_detailed AS
SELECT
    sp.id AS payment_id,
    si.invoice_number,
    si.subscription_id,
    os.organizer_profile_id,
    op.user_id AS organizer_user_id,
    op.organization_type,
    op.verification_status,
    sp.provider,
    sp.status,
    sp.amount,
    sp.currency,
    sp.paid_at,
    sph.status_from,
    sph.status_to,
    sph.changed_at,
    sph.metadata
FROM subscription_payments sp
LEFT JOIN subscription_invoices si ON si.id = sp.invoice_id
LEFT JOIN organizer_subscriptions os ON os.id = si.subscription_id
LEFT JOIN organizer_profiles op ON op.id = os.organizer_profile_id
LEFT JOIN subscription_payment_history sph ON sph.payment_id = sp.id;

COMMENT ON VIEW aiolia.vw_subscription_payments_detailed IS
    'Historique détaillé des paiements d’abonnement avec métadonnées organisateur.';

CREATE OR REPLACE VIEW aiolia.vw_ticket_invoices_overdue AS
SELECT
    ti.invoice_number,
    ti.customer_id,
    ti.order_id,
    ti.total_amount,
    ti.issued_at,
    ti.due_at,
    ti.status
FROM ticket_invoices ti
WHERE ti.status IN ('issued', 'overdue')
   OR (ti.status = 'draft' AND ti.due_at IS NOT NULL AND ti.due_at < now());

COMMENT ON VIEW aiolia.vw_ticket_invoices_overdue IS
    'Liste les factures tickets en retard ou proches de l’échéance.';

CREATE OR REPLACE VIEW aiolia.vw_subscription_invoices_overdue AS
SELECT
    si.invoice_number,
    si.subscription_id,
    os.organizer_profile_id,
    op.user_id AS organizer_user_id,
    op.organization_type,
    op.verification_status,
    si.total_amount,
    si.issued_at,
    si.due_at,
    si.status
FROM subscription_invoices si
LEFT JOIN organizer_subscriptions os ON os.id = si.subscription_id
LEFT JOIN organizer_profiles op ON op.id = os.organizer_profile_id
WHERE si.status IN ('issued', 'overdue')
   OR (si.status = 'draft' AND si.due_at IS NOT NULL AND si.due_at < now());

COMMENT ON VIEW aiolia.vw_subscription_invoices_overdue IS
    'Liste les factures d’abonnement à risque ou échues avec informations organisateur.';

-- ------------------------------------------------------------
-- Module : Organisations & Abonnements (front)
-- ------------------------------------------------------------
CREATE OR REPLACE VIEW aiolia.vw_subscription_invoice_items AS
SELECT
    sii.id AS item_id,
    si.invoice_number,
    si.subscription_id,
    os.organizer_profile_id,
    op.user_id AS organizer_user_id,
    op.organization_type,
    op.verification_status,
    sii.plan_id,
    sp.code AS plan_code,
    sp.name AS plan_name,
    sii.description,
    sii.quantity,
    sii.unit_price,
    sii.total_amount
FROM subscription_invoice_items sii
JOIN subscription_invoices si ON si.id = sii.invoice_id
LEFT JOIN organizer_subscriptions os ON os.id = si.subscription_id
LEFT JOIN organizer_profiles op ON op.id = os.organizer_profile_id
LEFT JOIN subscription_plans sp ON sp.id = sii.plan_id;

COMMENT ON VIEW aiolia.vw_subscription_invoice_items IS
    'Détaille les lignes des factures d’abonnement, incluant plan et statut d’organisateur.';

-- ------------------------------------------------------------
-- 2. Fonctions utilitaires
-- ------------------------------------------------------------
CREATE OR REPLACE FUNCTION refresh_user_monthly_spend() RETURNS void AS $$
BEGIN
    -- L'exécution en mode CONCURRENTLY n'est pas autorisée dans une fonction.
    REFRESH MATERIALIZED VIEW aiolia.mv_user_monthly_spend;
END;
$$ LANGUAGE plpgsql;

COMMENT ON FUNCTION refresh_user_monthly_spend() IS
    'Rafraîchit la vue matérialisée des dépenses mensuelles utilisateurs.';

CREATE OR REPLACE FUNCTION wallet_transactions_apply() RETURNS trigger AS $$
DECLARE
    v_wallet wallets%ROWTYPE;
    v_new_balance NUMERIC(14,2);
    v_new_points INTEGER;
BEGIN
    IF NEW.status <> 'pending' THEN
        RETURN NEW;
    END IF;

    IF NEW.transaction_type IN ('credit', 'debit') AND NEW.amount <= 0 THEN
        RAISE EXCEPTION 'Le montant doit être strictement positif pour une transaction monétaire (%).', NEW.id;
    ELSIF NEW.transaction_type IN ('points_credit', 'points_debit') AND NEW.points_delta <= 0 THEN
        RAISE EXCEPTION 'La variation de points doit être strictement positive pour une transaction de points (%).', NEW.id;
    END IF;

    SELECT *
      INTO v_wallet
      FROM wallets
     WHERE id = NEW.wallet_id
     FOR UPDATE;

    IF NOT FOUND THEN
        RAISE EXCEPTION 'Portefeuille % introuvable pour la transaction %.', NEW.wallet_id, NEW.id;
    END IF;

    v_new_balance := v_wallet.balance;
    v_new_points := v_wallet.points_balance;

    CASE NEW.transaction_type
        WHEN 'credit' THEN
            v_new_balance := v_wallet.balance + NEW.amount;
        WHEN 'debit' THEN
            IF NEW.amount > v_wallet.balance THEN
                RAISE EXCEPTION 'Solde insuffisant sur le portefeuille % pour débiter %.', NEW.wallet_id, NEW.amount;
            END IF;
            v_new_balance := v_wallet.balance - NEW.amount;
        WHEN 'points_credit' THEN
            v_new_points := v_wallet.points_balance + NEW.points_delta;
        WHEN 'points_debit' THEN
            IF NEW.points_delta > v_wallet.points_balance THEN
                RAISE EXCEPTION 'Solde de points insuffisant sur le portefeuille % pour déduire % points.', NEW.wallet_id, NEW.points_delta;
            END IF;
            v_new_points := v_wallet.points_balance - NEW.points_delta;
    END CASE;

    IF NEW.transaction_type IN ('credit', 'debit') THEN
        UPDATE wallets
           SET balance = v_new_balance,
               updated_at = now()
         WHERE id = NEW.wallet_id;
    ELSE
        UPDATE wallets
           SET points_balance = v_new_points,
               updated_at = now()
         WHERE id = NEW.wallet_id;
    END IF;

    NEW.status := 'completed';
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

COMMENT ON FUNCTION wallet_transactions_apply() IS
    'Applique les transactions de portefeuille en validant les soldes et en verrouillant la cible.';

CREATE OR REPLACE FUNCTION order_items_adjust_inventory() RETURNS trigger AS $$
DECLARE
    v_total INTEGER;
    v_reserved INTEGER;
    v_sold INTEGER;
BEGIN
    SELECT total_quantity, reserved_quantity, sold_quantity
      INTO v_total, v_reserved, v_sold
      FROM ticket_inventory
     WHERE ticket_type_id = NEW.ticket_type_id
     FOR UPDATE;

    IF NOT FOUND THEN
        RAISE EXCEPTION 'Inventaire introuvable pour le type de billet %.', NEW.ticket_type_id;
    END IF;

    IF v_reserved + v_sold + NEW.quantity > v_total THEN
        RAISE EXCEPTION 'Stock insuffisant pour le type de billet %, quantité demandée : %, disponible : %.',
            NEW.ticket_type_id,
            NEW.quantity,
            v_total - v_reserved - v_sold;
    END IF;

    UPDATE ticket_inventory
       SET reserved_quantity = v_reserved + NEW.quantity,
           updated_at = now()
     WHERE ticket_type_id = NEW.ticket_type_id;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

COMMENT ON FUNCTION order_items_adjust_inventory() IS
    'Réserve le stock à la création d’un item de commande avec contrôles de capacité.';

CREATE OR REPLACE FUNCTION tickets_record_stats() RETURNS trigger AS $$
DECLARE
    v_event_start TIMESTAMPTZ;
    v_total_amount NUMERIC(12,2);
    v_quantity INTEGER;
    v_ticket_value NUMERIC(12,2) := 0;
    v_attended_increment INTEGER := 0;
    v_upcoming_increment INTEGER := 0;
    v_last_event_at TIMESTAMPTZ;
BEGIN
    IF NEW.owner_user_id IS NULL THEN
        RETURN NEW;
    END IF;

    SELECT e.starts_at,
           oi.total_amount,
           oi.quantity
      INTO v_event_start,
           v_total_amount,
           v_quantity
      FROM ticket_types tt
      LEFT JOIN events e ON e.id = tt.event_id
      LEFT JOIN order_items oi ON oi.id = NEW.order_item_id
     WHERE tt.id = NEW.ticket_type_id;

    v_last_event_at := COALESCE(v_event_start, NEW.issued_at);

    IF v_quantity IS NULL OR v_quantity = 0 THEN
        v_quantity := 1;
    END IF;

    IF v_total_amount IS NOT NULL THEN
        v_ticket_value := v_total_amount / v_quantity;
    END IF;

    IF v_event_start IS NULL THEN
        v_attended_increment := 1;
    ELSIF v_event_start <= now() THEN
        v_attended_increment := 1;
    ELSE
        v_upcoming_increment := 1;
    END IF;

    INSERT INTO user_event_stats (
        user_id,
        events_attended,
        upcoming_events,
        total_spend,
        last_event_at,
        updated_at
    )
    VALUES (
        NEW.owner_user_id,
        v_attended_increment,
        v_upcoming_increment,
        v_ticket_value,
        v_last_event_at,
        now()
    )
    ON CONFLICT (user_id) DO UPDATE
        SET events_attended = user_event_stats.events_attended + EXCLUDED.events_attended,
            upcoming_events = user_event_stats.upcoming_events + EXCLUDED.upcoming_events,
            total_spend = user_event_stats.total_spend + EXCLUDED.total_spend,
            last_event_at = GREATEST(user_event_stats.last_event_at, EXCLUDED.last_event_at),
            updated_at = now();

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

COMMENT ON FUNCTION tickets_record_stats() IS
    'Met à jour les statistiques utilisateur lors de l’émission d’un billet (participation, dépenses, prochaines dates).';

-- ------------------------------------------------------------
-- 3. Triggers
-- ------------------------------------------------------------
DROP TRIGGER IF EXISTS trg_wallet_transactions_apply ON wallet_transactions;
CREATE TRIGGER trg_wallet_transactions_apply
BEFORE INSERT ON wallet_transactions
FOR EACH ROW EXECUTE FUNCTION wallet_transactions_apply();

COMMENT ON TRIGGER trg_wallet_transactions_apply ON wallet_transactions IS
    'Avant insertion : applique immédiatement les effets des transactions de portefeuille.';

DROP TRIGGER IF EXISTS trg_order_items_adjust_inventory ON order_items;
CREATE TRIGGER trg_order_items_adjust_inventory
AFTER INSERT ON order_items
FOR EACH ROW EXECUTE FUNCTION order_items_adjust_inventory();

COMMENT ON TRIGGER trg_order_items_adjust_inventory ON order_items IS
    'Après insertion : réserve le stock correspondant pour le type de billet ciblé.';

DROP TRIGGER IF EXISTS trg_tickets_record_stats ON tickets;
CREATE TRIGGER trg_tickets_record_stats
AFTER INSERT ON tickets
FOR EACH ROW EXECUTE FUNCTION tickets_record_stats();

COMMENT ON TRIGGER trg_tickets_record_stats ON tickets IS
    'Après insertion : met à jour les statistiques utilisateur liées aux billets.';

-- ============================================================
-- fin du fichier
-- ============================================================

-- ------------------------------------------------------------
-- Récapitulatif logique
-- ------------------------------------------------------------
-- Vues matérialisées : 1
-- Vues : 8
-- Fonctions : 4
-- Triggers : 3
