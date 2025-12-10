
SET search_path TO aiolia, public;

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
    'Vue de suivi des paiements d''abonnements avec état de vérification des organisateurs.';

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
    'Historique détaillé des paiements d''abonnement avec métadonnées organisateur.';

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
    sii.montant_total,
    si.id AS invoice_id
FROM elements_factures_abonnements sii
JOIN factures_abonnements si ON si.id = sii.id_facture
LEFT JOIN abonnements_organisateurs os ON os.id = si.id_abonnement
LEFT JOIN profils_organisateurs op ON op.id = os.id_profil_organisateur
LEFT JOIN plans_abonnements sp ON sp.id = sii.id_plan;

COMMENT ON VIEW aiolia.vw_subscription_invoice_items IS
    'Détaille les lignes des factures d''abonnement, incluant plan et statut d''organisateur.';

-- ------------------------------------------------------------
-- Module : Statistiques de vues d'événements
-- ------------------------------------------------------------
CREATE OR REPLACE VIEW aiolia.vw_evenements_plus_vus AS
SELECT
    e.id AS id_evenement,
    e.titre,
    e.slug,
    e.statut,
    e.visibilite,
    e.commence_le,
    e.se_termine_le,
    e.url_image_couverture,
    po.id AS id_profil_organisateur,
    po.nom_affichage AS nom_organisateur,
    ce.libelle AS categorie,
    COUNT(ve.id) AS nombre_vues_total,
    COUNT(DISTINCT ve.id_utilisateur) AS nombre_visiteurs_uniques,
    COUNT(DISTINCT DATE(ve.cree_le)) AS nombre_jours_avec_vues,
    MAX(ve.cree_le) AS derniere_vue_le,
    MIN(ve.cree_le) AS premiere_vue_le,
    AVG(ve.duree_vue_secondes) AS duree_moyenne_vue_secondes,
    COUNT(CASE WHEN ve.type_vue = 'page' THEN 1 END) AS vues_page,
    COUNT(CASE WHEN ve.type_vue = 'listing' THEN 1 END) AS vues_listing,
    COUNT(CASE WHEN ve.type_vue = 'search' THEN 1 END) AS vues_recherche,
    COUNT(CASE WHEN ve.cree_le >= NOW() - INTERVAL '7 days' THEN 1 END) AS vues_7_derniers_jours,
    COUNT(CASE WHEN ve.cree_le >= NOW() - INTERVAL '30 days' THEN 1 END) AS vues_30_derniers_jours
FROM evenements e
LEFT JOIN vues_evenements ve ON ve.id_evenement = e.id
LEFT JOIN profils_organisateurs po ON po.id = e.id_profil_organisateur
LEFT JOIN categories_evenements ce ON ce.id = e.id_categorie_principale
WHERE e.statut = 'published'
GROUP BY e.id, e.titre, e.slug, e.statut, e.visibilite, e.commence_le, e.se_termine_le, 
         e.url_image_couverture, po.id, po.nom_affichage, ce.libelle
ORDER BY nombre_vues_total DESC, derniere_vue_le DESC NULLS LAST;

COMMENT ON VIEW aiolia.vw_evenements_plus_vus IS
    'Vue agrégée des événements les plus vus avec statistiques détaillées de vues.';

CREATE OR REPLACE VIEW aiolia.vw_statistiques_vues_evenements AS
SELECT
    DATE_TRUNC('day', ve.cree_le) AS date_vue,
    ve.id_evenement,
    e.titre AS titre_evenement,
    COUNT(*) AS nombre_vues,
    COUNT(DISTINCT ve.id_utilisateur) AS visiteurs_uniques,
    COUNT(DISTINCT ve.adresse_ip) AS adresses_ip_uniques,
    AVG(ve.duree_vue_secondes) AS duree_moyenne_secondes,
    COUNT(CASE WHEN ve.type_vue = 'page' THEN 1 END) AS vues_page,
    COUNT(CASE WHEN ve.type_vue = 'listing' THEN 1 END) AS vues_listing,
    COUNT(CASE WHEN ve.type_vue = 'search' THEN 1 END) AS vues_recherche
FROM vues_evenements ve
JOIN evenements e ON e.id = ve.id_evenement
WHERE e.statut = 'published'
GROUP BY DATE_TRUNC('day', ve.cree_le), ve.id_evenement, e.titre
ORDER BY date_vue DESC, nombre_vues DESC;

COMMENT ON VIEW aiolia.vw_statistiques_vues_evenements IS
    'Statistiques quotidiennes de vues par événement avec détails par type de vue.';

-- ------------------------------------------------------------
-- 2. Fonctions pour les triggers
-- ------------------------------------------------------------

-- Fonction pour appliquer les transactions de portefeuille
CREATE OR REPLACE FUNCTION wallet_transactions_apply()
RETURNS TRIGGER AS $$
BEGIN
    -- Appliquer la transaction uniquement si le statut est 'completed'
    IF NEW.statut = 'completed' THEN
        IF NEW.type_transaction IN ('credit', 'points_credit') THEN
            -- Crédit : ajouter au solde
            UPDATE portefeuilles
            SET 
                solde = solde + CASE WHEN NEW.type_transaction = 'credit' THEN NEW.montant ELSE 0 END,
                solde_points = solde_points + CASE WHEN NEW.type_transaction = 'points_credit' THEN NEW.variation_points ELSE 0 END,
                modifie_le = NOW()
            WHERE id = NEW.id_portefeuille;
        ELSIF NEW.type_transaction IN ('debit', 'points_debit') THEN
            -- Débit : soustraire du solde
            UPDATE portefeuilles
            SET 
                solde = solde - CASE WHEN NEW.type_transaction = 'debit' THEN NEW.montant ELSE 0 END,
                solde_points = solde_points - CASE WHEN NEW.type_transaction = 'points_debit' THEN NEW.variation_points ELSE 0 END,
                modifie_le = NOW()
            WHERE id = NEW.id_portefeuille;
        END IF;
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

COMMENT ON FUNCTION wallet_transactions_apply() IS
    'Applique les effets des transactions de portefeuille sur le solde.';

-- Fonction pour ajuster l'inventaire lors de la création d'éléments de commande
CREATE OR REPLACE FUNCTION order_items_adjust_inventory()
RETURNS TRIGGER AS $$
DECLARE
    v_type_billet_id BIGINT;
BEGIN
    v_type_billet_id := NEW.id_type_billet;
    
    -- Réserver les billets dans l'inventaire
    UPDATE inventaire_billets
    SET 
        quantite_reservee = quantite_reservee + NEW.quantite,
        modifie_le = NOW()
    WHERE id_type_billet = v_type_billet_id;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

COMMENT ON FUNCTION order_items_adjust_inventory() IS
    'Réserve le stock correspondant pour le type de billet lors de la création d''un élément de commande.';

-- Fonction pour enregistrer les statistiques lors de la création de billets
CREATE OR REPLACE FUNCTION tickets_record_stats()
RETURNS TRIGGER AS $$
DECLARE
    v_id_evenement BIGINT;
    v_commence_le TIMESTAMPTZ;
BEGIN
    -- Récupérer l'ID de l'événement depuis le type de billet
    SELECT tb.id_evenement, e.commence_le INTO v_id_evenement, v_commence_le
    FROM types_billets tb
    LEFT JOIN evenements e ON e.id = tb.id_evenement
    WHERE tb.id = NEW.id_type_billet;
    
    -- Si l'utilisateur propriétaire existe, mettre à jour ses statistiques
    IF NEW.id_utilisateur_proprietaire IS NOT NULL THEN
        -- Insérer ou mettre à jour les statistiques utilisateur
        INSERT INTO statistiques_evenements_utilisateurs (
            id_utilisateur,
            evenements_auxquels_a_participe,
            evenements_a_venir,
            dernier_evenement_le,
            modifie_le
        )
        VALUES (
            NEW.id_utilisateur_proprietaire,
            CASE WHEN NEW.statut = 'used' THEN 1 ELSE 0 END,
            CASE WHEN v_commence_le IS NOT NULL AND v_commence_le > NOW() THEN 1 ELSE 0 END,
            COALESCE(v_commence_le, NOW()),
            NOW()
        )
        ON CONFLICT (id_utilisateur) DO UPDATE
        SET 
            evenements_auxquels_a_participe = statistiques_evenements_utilisateurs.evenements_auxquels_a_participe + 
                CASE WHEN NEW.statut = 'used' THEN 1 ELSE 0 END,
            evenements_a_venir = statistiques_evenements_utilisateurs.evenements_a_venir + 
                CASE WHEN v_commence_le IS NOT NULL AND v_commence_le > NOW() THEN 1 ELSE 0 END,
            dernier_evenement_le = GREATEST(
                COALESCE(statistiques_evenements_utilisateurs.dernier_evenement_le, '1970-01-01'::TIMESTAMPTZ), 
                COALESCE(v_commence_le, NOW())
            ),
            modifie_le = NOW();
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

COMMENT ON FUNCTION tickets_record_stats() IS
    'Met à jour les statistiques utilisateur liées aux billets lors de la création d''un billet.';

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
