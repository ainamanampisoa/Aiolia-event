
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
    'Détaille les lignes des factures d’abonnement, incluant plan et statut d’organisateur.';

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
