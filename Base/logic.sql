
SET search_path TO aiolia;

-- ------------------------------------------------------------
-- Fonctions pour les triggers
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
