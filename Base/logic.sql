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

-- ------------------------------------------------------------
-- 4. Fonction de génération automatique des factures mensuelles
-- ------------------------------------------------------------
CREATE OR REPLACE FUNCTION generate_monthly_subscription_invoices(target_month DATE DEFAULT date_trunc('month', CURRENT_DATE)) 
RETURNS TABLE(
    subscription_id BIGINT,
    invoice_id BIGINT,
    invoice_number TEXT,
    status TEXT,
    amount NUMERIC(12,2),
    action TEXT
) AS $$
DECLARE
    v_subscription RECORD;
    v_plan RECORD;
    v_existing_invoice RECORD;
    v_invoice_id BIGINT;
    v_invoice_number TEXT;
    v_month_start DATE;
    v_due_date TIMESTAMPTZ;
    v_subtotal NUMERIC(12,2);
    v_tax_amount NUMERIC(12,2);
    v_total_amount NUMERIC(12,2);
    v_vat_rate NUMERIC(5,2);
    v_currency TEXT;
    v_invoice_status TEXT;
    v_is_pause_month BOOLEAN;
    v_has_prepaid_credit BOOLEAN;
BEGIN
    -- S'assurer que target_month est le premier jour du mois
    v_month_start := date_trunc('month', target_month)::DATE;
    v_due_date := (v_month_start + INTERVAL '10 days')::TIMESTAMPTZ;

    -- Parcourir tous les abonnements actifs ou en pause
    FOR v_subscription IN
        SELECT 
            os.id,
            os.id_profil_organisateur,
            os.statut,
            os.mois_prepayes_restants,
            os.mis_en_pause_le,
            os.repris_le,
            op.id_utilisateur,
            os.id_plan
        FROM abonnements_organisateurs os
        INNER JOIN profils_organisateurs op ON op.id = os.id_profil_organisateur
        WHERE os.statut IN ('active', 'paused')
            AND os.annule_le IS NULL
    LOOP
        -- Récupérer les informations du plan (incluant le niveau/type d'offre)
        SELECT 
            sp.id,
            sp.code,
            sp.nom,
            sp.niveau,
            sp.prix,
            sp.taux_tva,
            sp.devise,
            sp.periode_facturation,
            sp.nombre_periodes
        INTO v_plan
        FROM plans_abonnements sp
        WHERE sp.id = v_subscription.id_plan
            AND sp.est_actif = true;

        IF NOT FOUND THEN
            -- Plan introuvable ou inactif, passer à l'abonnement suivant
            CONTINUE;
        END IF;

        -- Vérifier que le niveau du plan est valide
        IF v_plan.niveau NOT IN ('basic', 'pro', 'enterprise') THEN
            -- Niveau invalide, passer à l'abonnement suivant
            CONTINUE;
        END IF;

        v_vat_rate := COALESCE(v_plan.taux_tva, 20);
        v_currency := COALESCE(v_plan.devise, 'MGA');

        -- Déterminer si c'est un mois en pause
        v_is_pause_month := (v_subscription.statut = 'paused') 
            OR (v_subscription.mis_en_pause_le IS NOT NULL 
                AND v_subscription.mis_en_pause_le <= v_month_start 
                AND (v_subscription.repris_le IS NULL OR v_subscription.repris_le > v_month_start));

        -- Vérifier si une facture existe déjà pour ce mois et cet abonnement
        SELECT id, numero_facture, statut, est_prepayee, est_mois_pause
        INTO v_existing_invoice
        FROM factures_abonnements
        WHERE id_abonnement = v_subscription.id
            AND mois_facturation = v_month_start;

        -- Si la facture existe déjà et que l'abonnement est actif, ne pas générer
        IF v_existing_invoice IS NOT NULL AND v_subscription.statut = 'active' AND NOT v_is_pause_month THEN
            RETURN QUERY SELECT 
                v_subscription.id::BIGINT,
                v_existing_invoice.id::BIGINT,
                v_existing_invoice.numero_facture,
                v_existing_invoice.statut,
                0::NUMERIC(12,2),
                'skipped'::TEXT;
            CONTINUE;
        END IF;

        -- Si la facture existe déjà pour un mois en pause, ne pas régénérer
        IF v_existing_invoice IS NOT NULL AND v_is_pause_month THEN
            RETURN QUERY SELECT 
                v_subscription.id::BIGINT,
                v_existing_invoice.id::BIGINT,
                v_existing_invoice.numero_facture,
                v_existing_invoice.statut,
                0::NUMERIC(12,2),
                'skipped_pause'::TEXT;
            CONTINUE;
        END IF;

        -- Vérifier si l'organisateur a du crédit prépayé
        v_has_prepaid_credit := (v_subscription.mois_prepayes_restants > 0);

        -- Si période prépayée et facture existe déjà, reporter le mois (ne pas générer)
        IF v_has_prepaid_credit AND v_existing_invoice IS NOT NULL AND v_existing_invoice.est_prepayee THEN
            RETURN QUERY SELECT 
                v_subscription.id::BIGINT,
                v_existing_invoice.id::BIGINT,
                v_existing_invoice.numero_facture,
                v_existing_invoice.statut,
                0::NUMERIC(12,2),
                'deferred'::TEXT;
            CONTINUE;
        END IF;

        -- Calculer les montants
        IF v_is_pause_month THEN
            -- Facture en pause : montant à 0
            v_subtotal := 0;
            v_tax_amount := 0;
            v_total_amount := 0;
            v_invoice_status := 'suspendue'; -- Statut pour facture suspendue (en pause)
        ELSE
            -- Facture normale - calcul selon le type d'offre et la période de facturation
            IF v_plan.periode_facturation = 'yearly' THEN
                -- Pour un abonnement annuel, diviser le prix par 12
                v_subtotal := v_plan.prix / 12;
            ELSIF v_plan.periode_facturation = 'quarterly' THEN
                -- Pour un abonnement trimestriel, diviser le prix par 3
                v_subtotal := v_plan.prix / 3;
            ELSE
                -- Abonnement mensuel : prix mensuel
                v_subtotal := v_plan.prix;
            END IF;
            
            v_tax_amount := v_subtotal * (v_vat_rate / 100);
            v_total_amount := v_subtotal + v_tax_amount;
            
            -- Si crédit prépayé disponible, utiliser le crédit (statut pending)
            IF v_has_prepaid_credit THEN
                v_invoice_status := 'pending'; -- Facture prépayée en attente de consommation
            ELSE
                v_invoice_status := 'issued'; -- Facture normale émise
            END IF;
        END IF;

        -- Générer le numéro de facture
        v_invoice_number := LPAD(nextval('sequence_numero_facture')::text, 8, '0');

        -- Insérer la facture
        INSERT INTO factures_abonnements (
            numero_facture,
            id_abonnement,
            id_client,
            devise,
            montant_sous_total,
            montant_tva,
            montant_total,
            montant_ht,
            montant_tva_detail,
            montant_ttc,
            mois_facturation,
            est_mois_pause,
            est_prepayee,
            statut,
            emise_le,
            echeance_le,
            metadonnees,
            cree_le,
            modifie_le
        ) VALUES (
            v_invoice_number,
            v_subscription.id,
            v_subscription.id_utilisateur,
            v_currency,
            v_subtotal,
            v_tax_amount,
            v_total_amount,
            v_subtotal,
            v_tax_amount,
            v_total_amount,
            v_month_start,
            v_is_pause_month,
            v_has_prepaid_credit,
            v_invoice_status,
            v_month_start::TIMESTAMPTZ,
            v_due_date,
            jsonb_build_object(
                'auto_generated', true,
                'month', EXTRACT(MONTH FROM v_month_start),
                'year', EXTRACT(YEAR FROM v_month_start),
                'billing_period', v_plan.periode_facturation,
                'plan_level', v_plan.niveau,
                'plan_code', v_plan.code,
                'plan_name', v_plan.nom,
                'subscription_status', v_subscription.statut
            ),
            now(),
            now()
        ) RETURNING id INTO v_invoice_id;

        -- Si crédit prépayé utilisé, décrémenter le crédit
        IF v_has_prepaid_credit AND NOT v_is_pause_month THEN
            UPDATE abonnements_organisateurs
            SET mois_prepayes_restants = GREATEST(0, mois_prepayes_restants - 1),
                modifie_le = now()
            WHERE id = v_subscription.id;
        END IF;

        -- Retourner le résultat
        RETURN QUERY SELECT 
            v_subscription.id::BIGINT,
            v_invoice_id::BIGINT,
            v_invoice_number::TEXT,
            v_invoice_status::TEXT,
            v_total_amount::NUMERIC(12,2),
            CASE 
                WHEN v_is_pause_month THEN 'created_pause'::TEXT
                WHEN v_has_prepaid_credit THEN 'created_prepaid'::TEXT
                ELSE 'created'::TEXT
            END;
    END LOOP;

    RETURN;
END;
$$ LANGUAGE plpgsql;

COMMENT ON FUNCTION generate_monthly_subscription_invoices(DATE) IS
    'Génère automatiquement les factures mensuelles pour tous les abonnements actifs ou en pause.
    - Si actif : facture avec statut "issued", échéance 10 jours après le 1er du mois
    - Si en pause : facture à 0 Ar avec statut "suspendue" et est_mois_pause = true
    - Les factures en pause sont générées pour chaque mois où l''organisateur est en pause
    - Vérifie l''existence d''une facture pour éviter les doublons
    - Gère les crédits prépayés : mois reportés sans remboursement
    - À exécuter le 1er jour de chaque mois';

-- ------------------------------------------------------------
-- 5. Fonction pour mettre à jour le statut des factures en retard
-- ------------------------------------------------------------
CREATE OR REPLACE FUNCTION update_overdue_invoices_status() 
RETURNS TABLE(
    invoice_id BIGINT,
    invoice_number TEXT,
    old_status TEXT,
    new_status TEXT,
    days_overdue INTEGER
) AS $$
DECLARE
    v_invoice RECORD;
    v_current_date DATE;
    v_due_date DATE;
    v_days_overdue INTEGER;
BEGIN
    v_current_date := CURRENT_DATE;

    -- Parcourir toutes les factures émises non payées
    FOR v_invoice IN
        SELECT 
            si.id,
            si.numero_facture,
            si.statut,
            si.echeance_le,
            si.mois_facturation
        FROM factures_abonnements si
        WHERE si.statut IN ('issued', 'draft')
            AND si.payee_le IS NULL
            AND si.echeance_le IS NOT NULL
    LOOP
        v_due_date := v_invoice.echeance_le::DATE;
        
        -- Vérifier si la date d'échéance est dépassée
        IF v_current_date > v_due_date THEN
            v_days_overdue := EXTRACT(DAY FROM (v_current_date - v_due_date))::INTEGER;
            
            -- Mettre à jour le statut en "overdue" (retard)
            UPDATE factures_abonnements
            SET statut = 'overdue',
                modifie_le = now(),
                metadonnees = COALESCE(metadonnees, '{}'::jsonb) || jsonb_build_object(
                    'marked_overdue_at', now(),
                    'days_overdue', v_days_overdue
                )
            WHERE id = v_invoice.id;

            RETURN QUERY SELECT 
                v_invoice.id::BIGINT,
                v_invoice.numero_facture::TEXT,
                v_invoice.statut::TEXT,
                'overdue'::TEXT,
                v_days_overdue::INTEGER;
        END IF;
    END LOOP;

    RETURN;
END;
$$ LANGUAGE plpgsql;

COMMENT ON FUNCTION update_overdue_invoices_status() IS
    'Met à jour le statut des factures non payées après la date d''échéance (10 jours après le 1er du mois).
    Change le statut de "issued" ou "draft" à "overdue" pour les factures en retard.
    À exécuter quotidiennement après le 10ème jour du mois.';

-- ------------------------------------------------------------
-- 6. Fonction pour mettre automatiquement en pause les abonnements non payés
-- ------------------------------------------------------------
CREATE OR REPLACE FUNCTION auto_pause_unpaid_subscriptions() 
RETURNS TABLE(
    subscription_id BIGINT,
    organizer_profile_id BIGINT,
    organizer_user_id BIGINT,
    invoice_id BIGINT,
    invoice_number TEXT,
    old_status TEXT,
    new_status TEXT,
    paused_at TIMESTAMPTZ
) AS $$
DECLARE
    v_current_date DATE;
    v_current_day INTEGER;
    v_current_month DATE;
    v_subscription RECORD;
    v_invoice RECORD;
    v_paused_at TIMESTAMPTZ;
BEGIN
    v_current_date := CURRENT_DATE;
    v_current_day := EXTRACT(DAY FROM v_current_date)::INTEGER;
    v_current_month := date_trunc('month', v_current_date)::DATE;
    v_paused_at := now();

    -- Vérifier si on est après le 10ème jour du mois
    IF v_current_day <= 10 THEN
        -- Si on est avant ou le 10ème jour, ne rien faire
        RETURN;
    END IF;

    -- Parcourir les factures du mois courant non payées
    FOR v_invoice IN
        SELECT 
            si.id AS invoice_id,
            si.numero_facture,
            si.id_abonnement,
            si.mois_facturation,
            si.statut AS invoice_status,
            si.payee_le,
            os.id AS subscription_id,
            os.id_profil_organisateur,
            os.statut AS subscription_status,
            os.mis_en_pause_le,
            op.id_utilisateur AS organizer_user_id
        FROM factures_abonnements si
        INNER JOIN abonnements_organisateurs os ON os.id = si.id_abonnement
        INNER JOIN profils_organisateurs op ON op.id = os.id_profil_organisateur
        WHERE si.mois_facturation = v_current_month
            AND si.statut IN ('issued', 'overdue')
            AND si.payee_le IS NULL
            AND os.statut = 'active'  -- Seulement les abonnements actifs
            AND os.annule_le IS NULL  -- Pas d'abonnements annulés
            AND os.mis_en_pause_le IS NULL  -- Pas déjà en pause
    LOOP
        -- Mettre en pause l'abonnement
        UPDATE abonnements_organisateurs
        SET statut = 'paused'::subscription_status_enum,
            mis_en_pause_le = v_paused_at,
            modifie_le = now(),
            metadonnees = COALESCE(metadonnees, '{}'::jsonb) || jsonb_build_object(
                'auto_paused', true,
                'auto_paused_at', v_paused_at,
                'auto_paused_reason', 'Non paiement de la facture du mois courant avant le 11ème jour',
                'invoice_id', v_invoice.invoice_id,
                'invoice_number', v_invoice.numero_facture
            )
        WHERE id = v_invoice.subscription_id;

        -- Mettre à jour la facture pour indiquer qu'elle est liée à une pause
        UPDATE factures_abonnements
        SET metadonnees = COALESCE(metadonnees, '{}'::jsonb) || jsonb_build_object(
                'subscription_paused', true,
                'subscription_paused_at', v_paused_at
            ),
            modifie_le = now()
        WHERE id = v_invoice.invoice_id;

        -- Retourner le résultat
        RETURN QUERY SELECT 
            v_invoice.subscription_id::BIGINT,
            v_invoice.id_profil_organisateur::BIGINT,
            v_invoice.organizer_user_id::BIGINT,
            v_invoice.invoice_id::BIGINT,
            v_invoice.numero_facture::TEXT,
            v_invoice.subscription_status::TEXT,
            'paused'::TEXT,
            v_paused_at::TIMESTAMPTZ;
    END LOOP;

    RETURN;
END;
$$ LANGUAGE plpgsql;

COMMENT ON FUNCTION auto_pause_unpaid_subscriptions() IS
    'Met automatiquement en pause les abonnements dont la facture du mois courant n''a pas été payée avant le 11ème jour du mois.
    Règle : Si l''organisateur ne paie pas son abonnement du mois courant avant le 11ème jour, son compte est automatiquement mis en pause.
    À exécuter quotidiennement après le 10ème jour du mois (idéalement le 11ème jour à minuit).';

-- ============================================================
-- fin du fichier
-- ============================================================

-- ------------------------------------------------------------
-- Récapitulatif logique
-- ------------------------------------------------------------
-- Vues matérialisées : 1
-- Vues : 8
-- Fonctions : 7
-- Triggers : 3
