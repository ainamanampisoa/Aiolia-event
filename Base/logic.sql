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
    ti.id_client AS user_id,
    date_trunc('month', ti.payee_le) AS month,
    SUM(ti.montant_total) AS total_spend
FROM factures_billets ti
WHERE ti.statut IN ('paid', 'partially_paid')
  AND ti.payee_le IS NOT NULL
GROUP BY ti.id_client, date_trunc('month', ti.payee_le);

COMMENT ON MATERIALIZED VIEW aiolia.mv_user_monthly_spend IS
    'Agrégation mensuelle des montants payés par utilisateur sur les factures tickets.';

CREATE OR REPLACE VIEW aiolia.vw_user_dashboard_summary AS
SELECT
    u.id AS user_id,
    u.prenom,
    u.nom,
    u.role,
    COALESCE(w.solde, 0) AS wallet_balance,
    COALESCE(w.solde_points, 0) AS loyalty_points,
    COALESCE(s.evenements_auxquels_a_participe, 0) AS events_attended,
    COALESCE(s.depenses_totales, 0) AS total_spend,
    s.dernier_evenement_le
FROM utilisateurs u
LEFT JOIN portefeuilles w ON w.id_utilisateur = u.id
LEFT JOIN statistiques_evenements_utilisateurs s ON s.id_utilisateur = u.id;

COMMENT ON VIEW aiolia.vw_user_dashboard_summary IS
    'Vue synthétique utilisée pour le tableau de bord utilisateur (soldes, points et activité).';

CREATE OR REPLACE VIEW aiolia.vw_event_sales_summary AS
SELECT
    e.id AS event_id,
    e.titre,
    e.commence_le,
    COALESCE(SUM(oi.quantite) FILTER (WHERE o.statut IN ('paid', 'awaiting_payment')), 0) AS tickets_sold,
    COALESCE(SUM(oi.montant_total) FILTER (WHERE o.statut IN ('paid', 'awaiting_payment')), 0) AS gross_revenue
FROM evenements e
LEFT JOIN types_billets tt ON tt.id_evenement = e.id
LEFT JOIN elements_commandes oi ON oi.id_type_billet = tt.id
LEFT JOIN commandes o ON o.id = oi.id_commande
GROUP BY e.id, e.titre, e.commence_le;

COMMENT ON VIEW aiolia.vw_event_sales_summary IS
    'Vue analytique des ventes par événement (billets vendus et revenu brut filtrés sur commandes payées).';

CREATE OR REPLACE VIEW aiolia.vw_subscription_payment_summary AS
SELECT
    si.id_abonnement,
    os.id_profil_organisateur,
    op.id_utilisateur AS organizer_user_id,
    op.type_organisation,
    op.statut_verification,
    si.id_client,
    sp.statut,
    si.montant_total,
    sp.paye_le
FROM factures_abonnements si
LEFT JOIN abonnements_organisateurs os ON os.id = si.id_abonnement
LEFT JOIN profils_organisateurs op ON op.id = os.id_profil_organisateur
LEFT JOIN paiements_abonnements sp ON sp.id_facture = si.id;

COMMENT ON VIEW aiolia.vw_subscription_payment_summary IS
    'Vue de suivi des paiements d’abonnements avec état de vérification des organisateurs.';

CREATE OR REPLACE VIEW aiolia.vw_ticket_payments_detailed AS
SELECT
    tp.id AS payment_id,
    ti.numero_facture,
    ti.id_client,
    ti.id_commande,
    tp.fournisseur,
    tp.statut,
    tp.montant,
    tp.devise,
    tp.paye_le,
    tph.statut_de,
    tph.statut_vers,
    tph.modifie_le,
    tph.metadonnees
FROM paiements_billets tp
LEFT JOIN factures_billets ti ON ti.id = tp.id_facture
LEFT JOIN historique_paiements_billets tph ON tph.id_paiement = tp.id;

COMMENT ON VIEW aiolia.vw_ticket_payments_detailed IS
    'Historique détaillé des paiements de billets incluant les transitions de statut.';

CREATE OR REPLACE VIEW aiolia.vw_subscription_payments_detailed AS
SELECT
    sp.id AS payment_id,
    si.numero_facture,
    si.id_abonnement,
    os.id_profil_organisateur,
    op.id_utilisateur AS organizer_user_id,
    op.type_organisation,
    op.statut_verification,
    sp.fournisseur,
    sp.statut,
    sp.montant,
    sp.devise,
    sp.paye_le,
    sph.statut_de,
    sph.statut_vers,
    sph.modifie_le,
    sph.metadonnees
FROM paiements_abonnements sp
LEFT JOIN factures_abonnements si ON si.id = sp.id_facture
LEFT JOIN abonnements_organisateurs os ON os.id = si.id_abonnement
LEFT JOIN profils_organisateurs op ON op.id = os.id_profil_organisateur
LEFT JOIN historique_paiements_abonnements sph ON sph.id_paiement = sp.id;

COMMENT ON VIEW aiolia.vw_subscription_payments_detailed IS
    'Historique détaillé des paiements d’abonnement avec métadonnées organisateur.';

CREATE OR REPLACE VIEW aiolia.vw_ticket_invoices_overdue AS
SELECT
    ti.numero_facture,
    ti.id_client,
    ti.id_commande,
    ti.montant_total,
    ti.emise_le,
    ti.echeance_le,
    ti.statut
FROM factures_billets ti
WHERE ti.statut IN ('issued', 'overdue')
   OR (ti.statut = 'draft' AND ti.echeance_le IS NOT NULL AND ti.echeance_le < now());

COMMENT ON VIEW aiolia.vw_ticket_invoices_overdue IS
    'Liste les factures tickets en retard ou proches de l’échéance.';

CREATE OR REPLACE VIEW aiolia.vw_subscription_invoices_overdue AS
SELECT
    si.numero_facture,
    si.id_abonnement,
    os.id_profil_organisateur,
    op.id_utilisateur AS organizer_user_id,
    op.type_organisation,
    op.statut_verification,
    si.montant_total,
    si.emise_le,
    si.echeance_le,
    si.statut
FROM factures_abonnements si
LEFT JOIN abonnements_organisateurs os ON os.id = si.id_abonnement
LEFT JOIN profils_organisateurs op ON op.id = os.id_profil_organisateur
WHERE si.statut IN ('issued', 'overdue')
   OR (si.statut = 'draft' AND si.echeance_le IS NOT NULL AND si.echeance_le < now());

COMMENT ON VIEW aiolia.vw_subscription_invoices_overdue IS
    'Liste les factures d’abonnement à risque ou échues avec informations organisateur.';

-- ------------------------------------------------------------
-- Module : Organisations & Abonnements (front)
-- ------------------------------------------------------------
CREATE OR REPLACE VIEW aiolia.vw_subscription_invoice_items AS
SELECT
    sii.id AS item_id,
    si.numero_facture,
    si.id_abonnement,
    os.id_profil_organisateur,
    op.id_utilisateur AS organizer_user_id,
    op.type_organisation,
    op.statut_verification,
    sii.id_plan,
    sp.code AS plan_code,
    sp.nom AS plan_name,
    sii.description,
    sii.quantite,
    sii.prix_unitaire,
    sii.montant_total
FROM elements_factures_abonnements sii
JOIN factures_abonnements si ON si.id = sii.id_facture
LEFT JOIN abonnements_organisateurs os ON os.id = si.id_abonnement
LEFT JOIN profils_organisateurs op ON op.id = os.id_profil_organisateur
LEFT JOIN plans_abonnements sp ON sp.id = sii.id_plan;

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
    v_wallet portefeuilles%ROWTYPE;
    v_new_balance NUMERIC(14,2);
    v_new_points INTEGER;
BEGIN
    IF NEW.statut <> 'pending' THEN
        RETURN NEW;
    END IF;

    IF NEW.type_transaction IN ('credit', 'debit') AND NEW.montant <= 0 THEN
        RAISE EXCEPTION 'Le montant doit être strictement positif pour une transaction monétaire (%).', NEW.id;
    ELSIF NEW.type_transaction IN ('points_credit', 'points_debit') AND NEW.variation_points <= 0 THEN
        RAISE EXCEPTION 'La variation de points doit être strictement positive pour une transaction de points (%).', NEW.id;
    END IF;

    SELECT *
      INTO v_wallet
      FROM portefeuilles
     WHERE id = NEW.id_portefeuille
     FOR UPDATE;

    IF NOT FOUND THEN
        RAISE EXCEPTION 'Portefeuille % introuvable pour la transaction %.', NEW.id_portefeuille, NEW.id;
    END IF;

    v_new_balance := v_wallet.solde;
    v_new_points := v_wallet.solde_points;

    CASE NEW.type_transaction
        WHEN 'credit' THEN
            v_new_balance := v_wallet.solde + NEW.montant;
        WHEN 'debit' THEN
            IF NEW.montant > v_wallet.solde THEN
                RAISE EXCEPTION 'Solde insuffisant sur le portefeuille % pour débiter %.', NEW.id_portefeuille, NEW.montant;
            END IF;
            v_new_balance := v_wallet.solde - NEW.montant;
        WHEN 'points_credit' THEN
            v_new_points := v_wallet.solde_points + NEW.variation_points;
        WHEN 'points_debit' THEN
            IF NEW.variation_points > v_wallet.solde_points THEN
                RAISE EXCEPTION 'Solde de points insuffisant sur le portefeuille % pour déduire % points.', NEW.id_portefeuille, NEW.variation_points;
            END IF;
            v_new_points := v_wallet.solde_points - NEW.variation_points;
    END CASE;

    IF NEW.type_transaction IN ('credit', 'debit') THEN
        UPDATE portefeuilles
           SET solde = v_new_balance,
               modifie_le = now()
         WHERE id = NEW.id_portefeuille;
    ELSE
        UPDATE portefeuilles
           SET solde_points = v_new_points,
               modifie_le = now()
         WHERE id = NEW.id_portefeuille;
    END IF;

    NEW.statut := 'completed';
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
    SELECT quantite_totale, quantite_reservee, quantite_vendue
      INTO v_total, v_reserved, v_sold
      FROM inventaire_billets
     WHERE id_type_billet = NEW.id_type_billet
     FOR UPDATE;

    IF NOT FOUND THEN
        RAISE EXCEPTION 'Inventaire introuvable pour le type de billet %.', NEW.id_type_billet;
    END IF;

    IF v_reserved + v_sold + NEW.quantite > v_total THEN
        RAISE EXCEPTION 'Stock insuffisant pour le type de billet %, quantité demandée : %, disponible : %.',
            NEW.id_type_billet,
            NEW.quantite,
            v_total - v_reserved - v_sold;
    END IF;

    UPDATE inventaire_billets
       SET quantite_reservee = v_reserved + NEW.quantite,
           modifie_le = now()
     WHERE id_type_billet = NEW.id_type_billet;

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
    IF NEW.id_utilisateur_proprietaire IS NULL THEN
        RETURN NEW;
    END IF;

    SELECT e.commence_le,
           oi.montant_total,
           oi.quantite
      INTO v_event_start,
           v_total_amount,
           v_quantity
      FROM types_billets tt
      LEFT JOIN evenements e ON e.id = tt.id_evenement
      LEFT JOIN elements_commandes oi ON oi.id = NEW.id_element_commande
     WHERE tt.id = NEW.id_type_billet;

    v_last_event_at := COALESCE(v_event_start, NEW.emis_le);

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

    INSERT INTO statistiques_evenements_utilisateurs (
        id_utilisateur,
        evenements_auxquels_a_participe,
        evenements_a_venir,
        depenses_totales,
        dernier_evenement_le,
        modifie_le
    )
    VALUES (
        NEW.id_utilisateur_proprietaire,
        v_attended_increment,
        v_upcoming_increment,
        v_ticket_value,
        v_last_event_at,
        now()
    )
    ON CONFLICT (id_utilisateur) DO UPDATE
        SET evenements_auxquels_a_participe = statistiques_evenements_utilisateurs.evenements_auxquels_a_participe + EXCLUDED.evenements_auxquels_a_participe,
            evenements_a_venir = statistiques_evenements_utilisateurs.evenements_a_venir + EXCLUDED.evenements_a_venir,
            depenses_totales = statistiques_evenements_utilisateurs.depenses_totales + EXCLUDED.depenses_totales,
            dernier_evenement_le = GREATEST(statistiques_evenements_utilisateurs.dernier_evenement_le, EXCLUDED.dernier_evenement_le),
            modifie_le = now();

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

COMMENT ON FUNCTION tickets_record_stats() IS
    'Met à jour les statistiques utilisateur lors de l’émission d’un billet (participation, dépenses, prochaines dates).';

-- ------------------------------------------------------------
-- 3. Triggers
-- ------------------------------------------------------------
DROP TRIGGER IF EXISTS trg_wallet_transactions_apply ON transactions_portefeuilles;
CREATE TRIGGER trg_wallet_transactions_apply
BEFORE INSERT ON transactions_portefeuilles
FOR EACH ROW EXECUTE FUNCTION wallet_transactions_apply();

COMMENT ON TRIGGER trg_wallet_transactions_apply ON transactions_portefeuilles IS
    'Avant insertion : applique immédiatement les effets des transactions de portefeuille.';

DROP TRIGGER IF EXISTS trg_order_items_adjust_inventory ON elements_commandes;
CREATE TRIGGER trg_order_items_adjust_inventory
AFTER INSERT ON elements_commandes
FOR EACH ROW EXECUTE FUNCTION order_items_adjust_inventory();

COMMENT ON TRIGGER trg_order_items_adjust_inventory ON elements_commandes IS
    'Après insertion : réserve le stock correspondant pour le type de billet ciblé.';

DROP TRIGGER IF EXISTS trg_tickets_record_stats ON billets;
CREATE TRIGGER trg_tickets_record_stats
AFTER INSERT ON billets
FOR EACH ROW EXECUTE FUNCTION tickets_record_stats();

COMMENT ON TRIGGER trg_tickets_record_stats ON billets IS
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
