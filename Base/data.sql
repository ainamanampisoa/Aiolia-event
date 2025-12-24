-- ============================================================
-- INSERTION DES DONNÉES - AIOLIA EVENTS
-- ============================================================

\c aiolia_event;
SET search_path TO aiolia, public;

-- S'assurer que l'extension pgcrypto est chargée
CREATE EXTENSION IF NOT EXISTS pgcrypto;

-- ============================================================
-- 1. INSERTION DES TYPES D'ABONNEMENTS (plans_abonnements)
-- ============================================================
INSERT INTO plans_abonnements (code, nom, description, niveau, periode_facturation, nombre_periodes, devise, prix, taux_tva, fonctionnalites, ordre_affichage, est_actif) VALUES
-- Basic - Mensuel
('basic_monthly', 'Basic Mensuel', 'Abonnement mensuel Basic', 'basic', 'monthly', 1, 'MGA', 50000, 20, '{"events_limit": 5, "tickets_per_event": 100, "basic_support": true}', 1, true),

-- Basic - Trimestriel (avec réduction 10%)
('basic_quarterly', 'Basic Trimestriel', 'Abonnement trimestriel Basic avec réduction', 'basic', 'quarterly', 3, 'MGA', 135000, 20, '{"events_limit": 5, "tickets_per_event": 100, "basic_support": true}', 2, true),

-- Basic - Annuel (avec réduction 20%)
('basic_yearly', 'Basic Annuel', 'Abonnement annuel Basic avec réduction', 'basic', 'yearly', 12, 'MGA', 480000, 20, '{"events_limit": 5, "tickets_per_event": 100, "priority_support": true}', 3, true),

-- Pro - Mensuel
('pro_monthly', 'Pro Mensuel', 'Abonnement mensuel Pro', 'pro', 'monthly', 1, 'MGA', 150000, 20, '{"events_limit": 20, "tickets_per_event": 500, "priority_support": true, "custom_branding": true}', 4, true),

-- Pro - Trimestriel (avec réduction 10%)
('pro_quarterly', 'Pro Trimestriel', 'Abonnement trimestriel Pro avec réduction', 'pro', 'quarterly', 3, 'MGA', 405000, 20, '{"events_limit": 20, "tickets_per_event": 500, "priority_support": true, "custom_branding": true}', 5, true),

-- Pro - Annuel (avec réduction 20%)
('pro_yearly', 'Pro Annuel', 'Abonnement annuel Pro avec réduction', 'pro', 'yearly', 12, 'MGA', 1440000, 20, '{"events_limit": 20, "tickets_per_event": 500, "priority_support": true, "custom_branding": true, "dedicated_account_manager": true}', 6, true),

-- Enterprise - Mensuel
('enterprise_monthly', 'Enterprise Mensuel', 'Abonnement mensuel Enterprise', 'enterprise', 'monthly', 1, 'MGA', 300000, 20, '{"events_limit": 100, "tickets_per_event": 5000, "priority_support": true, "custom_branding": true, "dedicated_account_manager": true, "api_access": true}', 7, true),

-- Enterprise - Trimestriel (avec réduction 10%)
('enterprise_quarterly', 'Enterprise Trimestriel', 'Abonnement trimestriel Enterprise avec réduction', 'enterprise', 'quarterly', 3, 'MGA', 810000, 20, '{"events_limit": 100, "tickets_per_event": 5000, "priority_support": true, "custom_branding": true, "dedicated_account_manager": true, "api_access": true}', 8, true),

-- Enterprise - Annuel (avec réduction 20%)
('enterprise_yearly', 'Enterprise Annuel', 'Abonnement annuel Enterprise avec réduction', 'enterprise', 'yearly', 12, 'MGA', 2880000, 20, '{"events_limit": 100, "tickets_per_event": 5000, "priority_support": true, "custom_branding": true, "dedicated_account_manager": true, "api_access": true, "white_label": true}', 9, true);

-- ============================================================
-- 2. INSERTION DES ADMINISTRATEURS (2 admins)
-- ============================================================
-- Mot de passe haché: Admin123!
DO $$
DECLARE
    v_salt1 TEXT;
    v_salt2 TEXT;
BEGIN
    SELECT public.gen_salt('bf'::text, 8) INTO v_salt1;
    SELECT public.gen_salt('bf'::text, 8) INTO v_salt2;
    INSERT INTO utilisateurs (email, identifiant_connexion, methode_connexion, hash_mot_de_passe, prenom, nom, telephone, code_pays, code_langue, fuseau_horaire, role, statut, fournisseur_auth, email_verifie, telephone_verifie, termes_acceptes_le) VALUES
    ('admin1@yopmail.com', 'admin1@yopmail.com', 'password', crypt('Admin123!', v_salt1), 'Admin', 'Principal', '+261340000001', 'MG', 'fr-FR', 'Indian/Antananarivo', 'admin', 1, 'password', true, true, '2025-01-01 00:00:00'),
    ('admin2@yopmail.com', 'admin2@yopmail.com', 'password', crypt('Admin123!', v_salt2), 'Admin', 'Secondaire', '+261340000002', 'MG', 'fr-FR', 'Indian/Antananarivo', 'admin', 1, 'password', true, true, '2025-01-01 00:00:00');
END $$;

-- Profils admin
INSERT INTO profils_admin (id_utilisateur, nom_affichage, nom_legal, email_support, telephone_support) VALUES
((SELECT id FROM utilisateurs WHERE email = 'admin1@yopmail.com'), 'Administrateur Principal', 'Admin Principal SARL', 'support@aiolia.mg', '+261340000001'),
((SELECT id FROM utilisateurs WHERE email = 'admin2@yopmail.com'), 'Administrateur Secondaire', 'Admin Secondaire SARL', 'support2@aiolia.mg', '+261340000002');

-- ============================================================
-- 3. INSERTION DES UTILISATEURS (45 utilisateurs)
-- ============================================================
DO $$
DECLARE
    i INTEGER;
    v_salt TEXT;
BEGIN
    SELECT public.gen_salt('bf'::text, 8) INTO v_salt;
    FOR i IN 1..45 LOOP
        INSERT INTO utilisateurs (email, identifiant_connexion, methode_connexion, hash_mot_de_passe, prenom, nom, telephone, code_pays, code_langue, fuseau_horaire, role, statut, fournisseur_auth, email_verifie, telephone_verifie, termes_acceptes_le) VALUES
        ('user' || i || '@yopmail.com', 'user' || i || '@yopmail.com', 'password', crypt('User123!', v_salt), 
         'Utilisateur' || i, 
         CASE WHEN i <= 30 THEN 'Nom' || i ELSE NULL END,
         '+26134' || LPAD((1000000 + i)::TEXT, 6, '0'), 
         'MG', 'fr-FR', 'Indian/Antananarivo', 'user', 1, 'password', true, true, '2025-01-01 00:00:00');
    END LOOP;
END $$;

-- ============================================================
-- 4. INSERTION DES ORGANISATEURS (26 organisateurs)
-- ============================================================
-- Fonction pour créer un organisateur
CREATE OR REPLACE FUNCTION creer_organisateur(
    p_email TEXT,
    p_nom_affichage TEXT,
    p_nom_legal TEXT,
    p_type_organisation organizer_type_enum DEFAULT 'company',
    p_statut_verification TEXT DEFAULT 'verified'
) RETURNS BIGINT AS $$
DECLARE
    v_user_id BIGINT;
    v_organisateur_id BIGINT;
    v_organisateur_num INTEGER;
    v_salt TEXT;
BEGIN
    -- Extraire le numéro de l'email
    v_organisateur_num := substring(p_email from 'organisateur([0-9]+)@')::INTEGER;
    
    -- Générer le salt
    SELECT public.gen_salt('bf'::text, 8) INTO v_salt;
    
    -- Créer l'utilisateur
    INSERT INTO utilisateurs (email, identifiant_connexion, methode_connexion, hash_mot_de_passe, prenom, nom, telephone, code_pays, code_langue, fuseau_horaire, role, statut, fournisseur_auth, email_verifie, telephone_verifie, termes_acceptes_le) VALUES
    (p_email, p_email, 'password', crypt('Organisateur123!', v_salt), 
     'Organisateur', 
     'Nom' || v_organisateur_num,
     '+26134' || LPAD((2000000 + v_organisateur_num)::TEXT, 6, '0'), 
     'MG', 'fr-FR', 'Indian/Antananarivo', 'organizer', 1, 'password', true, true, '2025-01-01 00:00:00')
    RETURNING id INTO v_user_id;
    
    -- Créer le profil organisateur
    INSERT INTO profils_organisateurs (id_utilisateur, nom_affichage, nom_legal, email_support, telephone_support, type_organisation, statut_verification, onboarding_termine_le) VALUES
    (v_user_id, p_nom_affichage, p_nom_legal, p_email, '+26134' || LPAD((2000000 + v_organisateur_num)::TEXT, 6, '0'), p_type_organisation, p_statut_verification, '2025-06-01 00:00:00')
    RETURNING id INTO v_organisateur_id;
    
    RETURN v_organisateur_id;
END;
$$ LANGUAGE plpgsql;

-- Création des 26 organisateurs (numéros 11 à 36)
SELECT creer_organisateur('organisateur11@yopmail.com', 'Organisateur 11', 'Organisateur 11 SARL');
SELECT creer_organisateur('organisateur12@yopmail.com', 'Organisateur 12', 'Organisateur 12 SARL');
SELECT creer_organisateur('organisateur13@yopmail.com', 'Organisateur 13', 'Organisateur 13 SARL');
SELECT creer_organisateur('organisateur14@yopmail.com', 'Organisateur 14', 'Organisateur 14 SARL');
SELECT creer_organisateur('organisateur15@yopmail.com', 'Organisateur 15', 'Organisateur 15 SARL');
SELECT creer_organisateur('organisateur16@yopmail.com', 'Organisateur 16', 'Organisateur 16 SARL');
SELECT creer_organisateur('organisateur17@yopmail.com', 'Organisateur 17', 'Organisateur 17 SARL');
SELECT creer_organisateur('organisateur18@yopmail.com', 'Organisateur 18', 'Organisateur 18 SARL');
SELECT creer_organisateur('organisateur19@yopmail.com', 'Organisateur 19', 'Organisateur 19 SARL');
SELECT creer_organisateur('organisateur20@yopmail.com', 'Organisateur 20', 'Organisateur 20 SARL');
SELECT creer_organisateur('organisateur21@yopmail.com', 'Organisateur 21', 'Organisateur 21 SARL');
SELECT creer_organisateur('organisateur22@yopmail.com', 'Organisateur 22', 'Organisateur 22 SARL');
SELECT creer_organisateur('organisateur23@yopmail.com', 'Organisateur 23', 'Organisateur 23 SARL');
SELECT creer_organisateur('organisateur24@yopmail.com', 'Organisateur 24', 'Organisateur 24 SARL');
SELECT creer_organisateur('organisateur25@yopmail.com', 'Organisateur 25', 'Organisateur 25 SARL');
SELECT creer_organisateur('organisateur26@yopmail.com', 'Organisateur 26', 'Organisateur 26 SARL');
SELECT creer_organisateur('organisateur27@yopmail.com', 'Organisateur 27', 'Organisateur 27 SARL');
SELECT creer_organisateur('organisateur28@yopmail.com', 'Organisateur 28', 'Organisateur 28 SARL');
SELECT creer_organisateur('organisateur29@yopmail.com', 'Organisateur 29', 'Organisateur 29 SARL');
SELECT creer_organisateur('organisateur30@yopmail.com', 'Organisateur 30', 'Organisateur 30 SARL');
SELECT creer_organisateur('organisateur31@yopmail.com', 'Organisateur 31', 'Organisateur 31 SARL');
SELECT creer_organisateur('organisateur32@yopmail.com', 'Organisateur 32', 'Organisateur 32 SARL');
SELECT creer_organisateur('organisateur33@yopmail.com', 'Organisateur 33', 'Organisateur 33 SARL');
SELECT creer_organisateur('organisateur34@yopmail.com', 'Organisateur 34', 'Organisateur 34 SARL');
SELECT creer_organisateur('organisateur35@yopmail.com', 'Organisateur 35', 'Organisateur 35 SARL');
SELECT creer_organisateur('organisateur36@yopmail.com', 'Organisateur 36', 'Organisateur 36 SARL');

-- ============================================================
-- 5. CREATION DES ABONNEMENTS PAR MOIS
-- ============================================================

-- Fonction pour créer un abonnement
CREATE OR REPLACE FUNCTION creer_abonnement(
    p_organisateur_email TEXT,
    p_plan_code TEXT,
    p_date_debut TIMESTAMPTZ,
    p_statut subscription_status_enum DEFAULT 'active'
) RETURNS BIGINT AS $$
DECLARE
    v_organisateur_id BIGINT;
    v_plan_id BIGINT;
    v_abonnement_id BIGINT;
    v_periode_facturation TEXT;
    v_fin_periode TIMESTAMPTZ;
BEGIN
    -- Récupérer l'ID de l'organisateur
    SELECT po.id INTO v_organisateur_id
    FROM profils_organisateurs po
    JOIN utilisateurs u ON po.id_utilisateur = u.id
    WHERE u.email = p_organisateur_email;
    
    -- Récupérer l'ID du plan
    SELECT id, periode_facturation INTO v_plan_id, v_periode_facturation
    FROM plans_abonnements
    WHERE code = p_plan_code;
    
    -- Calculer la fin de période
    CASE v_periode_facturation
        WHEN 'monthly' THEN v_fin_periode := p_date_debut + INTERVAL '1 month';
        WHEN 'quarterly' THEN v_fin_periode := p_date_debut + INTERVAL '3 months';
        WHEN 'yearly' THEN v_fin_periode := p_date_debut + INTERVAL '1 year';
        ELSE v_fin_periode := p_date_debut + INTERVAL '1 month';
    END CASE;
    
    -- Créer l'abonnement
    INSERT INTO abonnements_organisateurs (
        id_profil_organisateur, id_plan, statut, 
        commence_le, debut_periode_courante, fin_periode_courante,
        renouvellement_le
    ) VALUES (
        v_organisateur_id, v_plan_id, p_statut,
        p_date_debut, p_date_debut, v_fin_periode,
        v_fin_periode
    )
    RETURNING id INTO v_abonnement_id;
    
    RETURN v_abonnement_id;
END;
$$ LANGUAGE plpgsql;

-- Fonction pour créer une facture d'abonnement
CREATE OR REPLACE FUNCTION creer_facture_abonnement(
    p_abonnement_id BIGINT,
    p_mois_facturation DATE,
    p_statut TEXT DEFAULT 'paid',
    p_est_prepayee BOOLEAN DEFAULT false,
    p_date_paiement TIMESTAMPTZ DEFAULT NULL
) RETURNS BIGINT AS $$
DECLARE
    v_facture_id BIGINT;
    v_user_id BIGINT;
    v_plan_id BIGINT;
    v_prix NUMERIC;
    v_tva NUMERIC;
    v_total NUMERIC;
    v_existing_id BIGINT;
BEGIN
    -- Vérifier si une facture existe déjà pour cet abonnement et ce mois
    SELECT id INTO v_existing_id
    FROM factures_abonnements
    WHERE id_abonnement = p_abonnement_id
    AND mois_facturation = p_mois_facturation
    LIMIT 1;
    
    -- Si une facture existe déjà, la retourner
    IF v_existing_id IS NOT NULL THEN
        RETURN v_existing_id;
    END IF;
    
    -- Récupérer les informations de l'abonnement
    SELECT 
        po.id_utilisateur,
        ao.id_plan,
        pa.prix,
        pa.taux_tva
    INTO v_user_id, v_plan_id, v_prix, v_tva
    FROM abonnements_organisateurs ao
    JOIN profils_organisateurs po ON ao.id_profil_organisateur = po.id
    JOIN plans_abonnements pa ON ao.id_plan = pa.id
    WHERE ao.id = p_abonnement_id;
    
    -- Calculer les montants
    v_tva := (v_prix * v_tva) / 100;
    v_total := v_prix + v_tva;
    
    -- Créer la facture
    INSERT INTO factures_abonnements (
        id_abonnement, id_client, devise,
        montant_sous_total, montant_tva, montant_total,
        montant_ht, montant_tva_detail, montant_ttc,
        mois_facturation, est_prepayee, statut,
        emise_le, echeance_le, payee_le
    ) VALUES (
        p_abonnement_id, v_user_id, 'MGA',
        v_prix, v_tva, v_total,
        v_prix, v_tva, v_total,
        p_mois_facturation, p_est_prepayee, p_statut,
        p_mois_facturation::TIMESTAMPTZ,
        p_mois_facturation::TIMESTAMPTZ + INTERVAL '30 days',
        COALESCE(p_date_paiement, p_mois_facturation::TIMESTAMPTZ + INTERVAL '1 day')
    )
    RETURNING id INTO v_facture_id;
    
    -- Ajouter l'élément de facture
    INSERT INTO elements_factures_abonnements (
        id_facture, id_plan, description,
        quantite, prix_unitaire, montant_total
    ) VALUES (
        v_facture_id, v_plan_id, 'Abonnement mensuel',
        1, v_prix, v_prix
    );
    
    RETURN v_facture_id;
END;
$$ LANGUAGE plpgsql;

-- ============================================================
-- 6. ABONNEMENTS JUIN 2025
-- ============================================================
-- 10 organisateurs actifs (11-20)
-- 5 basic, 2 pro, 3 enterprise - tous mensuels
DO $$
DECLARE
    i INTEGER;
    v_abonnement_id BIGINT;
BEGIN
    -- Organisateurs 11-15: Basic
    FOR i IN 11..15 LOOP
        SELECT creer_abonnement('organisateur' || i || '@yopmail.com', 'basic_monthly', '2025-06-01 00:00:00') INTO v_abonnement_id;
    END LOOP;
    
    -- Organisateurs 16-17: Pro
    FOR i IN 16..17 LOOP
        SELECT creer_abonnement('organisateur' || i || '@yopmail.com', 'pro_monthly', '2025-06-01 00:00:00') INTO v_abonnement_id;
    END LOOP;
    
    -- Organisateurs 18-20: Enterprise
    FOR i IN 18..20 LOOP
        SELECT creer_abonnement('organisateur' || i || '@yopmail.com', 'enterprise_monthly', '2025-06-01 00:00:00') INTO v_abonnement_id;
    END LOOP;
END $$;

-- Factures Juin 2025
-- 10 factures: 5 basic, 2 pro, 3 enterprise (organisateurs 11-20)
DO $$
DECLARE
    v_abonnement RECORD;
    v_facture_id BIGINT;
    v_count INTEGER := 0;
BEGIN
    FOR v_abonnement IN 
        SELECT ao.id 
        FROM abonnements_organisateurs ao
        JOIN profils_organisateurs po ON ao.id_profil_organisateur = po.id
        JOIN utilisateurs u ON po.id_utilisateur = u.id
        WHERE u.email LIKE 'organisateur%@yopmail.com'
        AND ao.commence_le = '2025-06-01 00:00:00'
        AND ao.statut = 'active'
        AND u.email BETWEEN 'organisateur11@yopmail.com' AND 'organisateur20@yopmail.com'
        ORDER BY u.email
    LOOP
        SELECT creer_facture_abonnement(v_abonnement.id, '2025-06-01'::DATE) INTO v_facture_id;
        v_count := v_count + 1;
    END LOOP;
    
    RAISE NOTICE 'Juin 2025: % factures créées', v_count;
END $$;

-- ============================================================
-- 7. ABONNEMENTS JUILLET 2025
-- ============================================================
-- +2 nouveaux organisateurs (21-22)
-- Total: 12 organisateurs
-- 4 basic, 6 pro, 2 enterprise
DO $$
DECLARE
    v_abonnement_id BIGINT;
BEGIN
    -- Nouveaux organisateurs 21-22: Pro
    SELECT creer_abonnement('organisateur21@yopmail.com', 'pro_monthly', '2025-07-01 00:00:00') INTO v_abonnement_id;
    SELECT creer_abonnement('organisateur22@yopmail.com', 'pro_monthly', '2025-07-01 00:00:00') INTO v_abonnement_id;
    
    -- Mettre à jour certains abonnements existants (changement de plan)
    -- Organisateur 14: Basic -> Pro
    UPDATE abonnements_organisateurs ao
    SET id_plan = (SELECT id FROM plans_abonnements WHERE code = 'pro_monthly'),
        modifie_le = NOW()
    FROM profils_organisateurs po
    JOIN utilisateurs u ON po.id_utilisateur = u.id
    WHERE ao.id_profil_organisateur = po.id
    AND u.email = 'organisateur14@yopmail.com';
    
    -- Organisateur 15: Basic -> Pro
    UPDATE abonnements_organisateurs ao
    SET id_plan = (SELECT id FROM plans_abonnements WHERE code = 'pro_monthly'),
        modifie_le = NOW()
    FROM profils_organisateurs po
    JOIN utilisateurs u ON po.id_utilisateur = u.id
    WHERE ao.id_profil_organisateur = po.id
    AND u.email = 'organisateur15@yopmail.com';
END $$;

-- Factures Juillet 2025
DO $$
DECLARE
    v_abonnement RECORD;
    v_facture_id BIGINT;
BEGIN
    FOR v_abonnement IN 
        SELECT ao.id 
        FROM abonnements_organisateurs ao
        JOIN profils_organisateurs po ON ao.id_profil_organisateur = po.id
        JOIN utilisateurs u ON po.id_utilisateur = u.id
        WHERE u.email LIKE 'organisateur%@yopmail.com'
        AND ao.statut = 'active'
    LOOP
        SELECT creer_facture_abonnement(v_abonnement.id, '2025-07-01') INTO v_facture_id;
    END LOOP;
END $$;

-- ============================================================
-- 8. ABONNEMENTS AOÛT 2025
-- ============================================================
-- +4 nouveaux organisateurs (23-26)
-- Total: 16 organisateurs
-- 4 basic, 5 pro, 7 enterprise
-- 2 organisateurs en pause (reviennent en octobre)
DO $$
DECLARE
    v_abonnement_id BIGINT;
BEGIN
    -- Nouveaux organisateurs 23-26: Enterprise
    SELECT creer_abonnement('organisateur23@yopmail.com', 'enterprise_monthly', '2025-08-01 00:00:00') INTO v_abonnement_id;
    SELECT creer_abonnement('organisateur24@yopmail.com', 'enterprise_monthly', '2025-08-01 00:00:00') INTO v_abonnement_id;
    SELECT creer_abonnement('organisateur25@yopmail.com', 'enterprise_monthly', '2025-08-01 00:00:00') INTO v_abonnement_id;
    SELECT creer_abonnement('organisateur26@yopmail.com', 'enterprise_monthly', '2025-08-01 00:00:00') INTO v_abonnement_id;
    
    -- Mettre en pause 2 organisateurs (16 et 17)
    UPDATE abonnements_organisateurs ao
    SET statut = 'paused',
        mis_en_pause_le = '2025-08-15 00:00:00',
        modifie_le = NOW()
    FROM profils_organisateurs po
    JOIN utilisateurs u ON po.id_utilisateur = u.id
    WHERE ao.id_profil_organisateur = po.id
    AND u.email IN ('organisateur16@yopmail.com', 'organisateur17@yopmail.com');
END $$;

-- Factures Août 2025 (14 factures seulement - 2 en pause)
DO $$
DECLARE
    v_abonnement RECORD;
    v_count INTEGER := 0;
    v_facture_id BIGINT;
BEGIN
    FOR v_abonnement IN 
        SELECT ao.id 
        FROM abonnements_organisateurs ao
        JOIN profils_organisateurs po ON ao.id_profil_organisateur = po.id
        JOIN utilisateurs u ON po.id_utilisateur = u.id
        WHERE u.email LIKE 'organisateur%@yopmail.com'
        AND ao.statut = 'active'
        AND u.email NOT IN ('organisateur16@yopmail.com', 'organisateur17@yopmail.com')
    LOOP
        SELECT creer_facture_abonnement(v_abonnement.id, '2025-08-01') INTO v_facture_id;
        v_count := v_count + 1;
    END LOOP;
    
    RAISE NOTICE 'Août 2025: % factures créées', v_count;
END $$;

-- ============================================================
-- 9. ABONNEMENTS SEPTEMBRE 2025
-- ============================================================
-- +4 nouveaux organisateurs (27-30)
-- Total: 20 organisateurs (18 actifs + 2 en pause)
-- 9 factures mensuelles, 9 factures trimestrielles
DO $$
DECLARE
    v_abonnement_id BIGINT;
BEGIN
    -- Nouveaux organisateurs 27-30
    SELECT creer_abonnement('organisateur27@yopmail.com', 'basic_quarterly', '2025-09-01 00:00:00') INTO v_abonnement_id;
    SELECT creer_abonnement('organisateur28@yopmail.com', 'pro_quarterly', '2025-09-01 00:00:00') INTO v_abonnement_id;
    SELECT creer_abonnement('organisateur29@yopmail.com', 'enterprise_quarterly', '2025-09-01 00:00:00') INTO v_abonnement_id;
    SELECT creer_abonnement('organisateur30@yopmail.com', 'basic_monthly', '2025-09-01 00:00:00') INTO v_abonnement_id;
    
    -- Changer certains abonnements en trimestriel
    UPDATE abonnements_organisateurs ao
    SET id_plan = 
        CASE 
            WHEN pa.niveau = 'basic' THEN (SELECT id FROM plans_abonnements WHERE code = 'basic_quarterly')
            WHEN pa.niveau = 'pro' THEN (SELECT id FROM plans_abonnements WHERE code = 'pro_quarterly')
            WHEN pa.niveau = 'enterprise' THEN (SELECT id FROM plans_abonnements WHERE code = 'enterprise_quarterly')
        END,
        modifie_le = NOW()
    FROM plans_abonnements pa
    WHERE ao.id_plan = pa.id
    AND ao.id_profil_organisateur IN (
        SELECT po.id 
        FROM profils_organisateurs po
        JOIN utilisateurs u ON po.id_utilisateur = u.id
        WHERE u.email IN ('organisateur11@yopmail.com', 'organisateur12@yopmail.com', 'organisateur13@yopmail.com',
                         'organisateur18@yopmail.com', 'organisateur19@yopmail.com', 'organisateur20@yopmail.com',
                         'organisateur21@yopmail.com', 'organisateur22@yopmail.com', 'organisateur23@yopmail.com')
    );
END $$;

-- Factures Septembre 2025
DO $$
DECLARE
    v_abonnement RECORD;
    v_mensuel_count INTEGER := 0;
    v_trimestre_count INTEGER := 0;
    v_facture_id BIGINT;
BEGIN
    FOR v_abonnement IN 
        SELECT ao.id, pa.periode_facturation
        FROM abonnements_organisateurs ao
        JOIN profils_organisateurs po ON ao.id_profil_organisateur = po.id
        JOIN utilisateurs u ON po.id_utilisateur = u.id
        JOIN plans_abonnements pa ON ao.id_plan = pa.id
        WHERE u.email LIKE 'organisateur%@yopmail.com'
        AND ao.statut = 'active'
        AND u.email NOT IN ('organisateur16@yopmail.com', 'organisateur17@yopmail.com')
    LOOP
        SELECT creer_facture_abonnement(v_abonnement.id, '2025-09-01') INTO v_facture_id;
        
        IF v_abonnement.periode_facturation = 'monthly' THEN
            v_mensuel_count := v_mensuel_count + 1;
        ELSE
            v_trimestre_count := v_trimestre_count + 1;
        END IF;
    END LOOP;
    
    RAISE NOTICE 'Septembre 2025: % mensuels, % trimestriels', v_mensuel_count, v_trimestre_count;
END $$;

-- ============================================================
-- 10. ABONNEMENTS OCTOBRE 2025
-- ============================================================
-- +3 nouveaux organisateurs (31-33, dont 1 non validé)
-- Total: 23 organisateurs (22 actifs + 1 non validé)
-- Réactiver les 2 organisateurs en pause
-- Mettre 4 organisateurs en pause
DO $$
DECLARE
    v_abonnement_id BIGINT;
BEGIN
    -- Nouveaux organisateurs 31-33
    SELECT creer_abonnement('organisateur31@yopmail.com', 'basic_monthly', '2025-10-01 00:00:00') INTO v_abonnement_id;
    SELECT creer_abonnement('organisateur32@yopmail.com', 'pro_monthly', '2025-10-01 00:00:00') INTO v_abonnement_id;
    
    -- Organisateur 33 non validé
    SELECT creer_abonnement('organisateur33@yopmail.com', 'enterprise_monthly', '2025-10-01 00:00:00') INTO v_abonnement_id;
    UPDATE profils_organisateurs 
    SET statut_verification = 'pending'
    WHERE id_utilisateur = (SELECT id FROM utilisateurs WHERE email = 'organisateur33@yopmail.com');
    
    -- Réactiver les organisateurs en pause (16 et 17)
    UPDATE abonnements_organisateurs ao
    SET statut = 'active',
        repris_le = '2025-10-01 00:00:00',
        modifie_le = NOW()
    FROM profils_organisateurs po
    JOIN utilisateurs u ON po.id_utilisateur = u.id
    WHERE ao.id_profil_organisateur = po.id
    AND u.email IN ('organisateur16@yopmail.com', 'organisateur17@yopmail.com');
    
    -- Mettre 4 organisateurs en pause (11, 18, 24, 27)
    UPDATE abonnements_organisateurs ao
    SET statut = 'paused',
        mis_en_pause_le = '2025-10-15 00:00:00',
        modifie_le = NOW()
    FROM profils_organisateurs po
    JOIN utilisateurs u ON po.id_utilisateur = u.id
    WHERE ao.id_profil_organisateur = po.id
    AND u.email IN ('organisateur11@yopmail.com', 'organisateur18@yopmail.com', 
                   'organisateur24@yopmail.com', 'organisateur27@yopmail.com');
END $$;

-- Factures Octobre 2025
DO $$
DECLARE
    v_abonnement RECORD;
    v_type_plan TEXT;
    v_mensuel_count INTEGER := 0;
    v_trimestre_count INTEGER := 0;
    v_prepaye_count INTEGER := 0;
    v_facture_id BIGINT;
BEGIN
    FOR v_abonnement IN 
        SELECT ao.id, pa.periode_facturation, pa.niveau
        FROM abonnements_organisateurs ao
        JOIN profils_organisateurs po ON ao.id_profil_organisateur = po.id
        JOIN utilisateurs u ON po.id_utilisateur = u.id
        JOIN plans_abonnements pa ON ao.id_plan = pa.id
        WHERE u.email LIKE 'organisateur%@yopmail.com'
        AND ao.statut = 'active'
        AND u.email != 'organisateur33@yopmail.com' -- non validé
        AND u.email NOT IN ('organisateur11@yopmail.com', 'organisateur18@yopmail.com', 
                           'organisateur24@yopmail.com', 'organisateur27@yopmail.com') -- en pause
    LOOP
        -- Déterminer le type de facturation
        IF v_abonnement.niveau IN ('basic', 'pro', 'enterprise') AND 
           v_abonnement.periode_facturation = 'monthly' AND
           v_mensuel_count < 8 THEN
            -- Factures mensuelles
            SELECT creer_facture_abonnement(v_abonnement.id, '2025-10-01') INTO v_facture_id;
            v_mensuel_count := v_mensuel_count + 1;
            
        ELSIF v_abonnement.niveau IN ('basic', 'pro', 'enterprise') AND 
              v_abonnement.periode_facturation = 'quarterly' AND
              v_trimestre_count < 7 THEN
            -- Factures trimestrielles
            SELECT creer_facture_abonnement(v_abonnement.id, '2025-10-01') INTO v_facture_id;
            v_trimestre_count := v_trimestre_count + 1;
            
        ELSE
            -- Factures prépayées
            SELECT creer_facture_abonnement(v_abonnement.id, '2025-10-01', 'paid', true) INTO v_facture_id;
            v_prepaye_count := v_prepaye_count + 1;
        END IF;
    END LOOP;
    
    RAISE NOTICE 'Octobre 2025: % mensuels, % trimestriels, % prépayés', 
        v_mensuel_count, v_trimestre_count, v_prepaye_count;
END $$;

-- ============================================================
-- 11. ABONNEMENTS NOVEMBRE 2025
-- ============================================================
-- +3 nouveaux organisateurs (34-36, dont 2 non validés)
-- Total: 26 organisateurs (19 actifs + 4 en pause + 3 non validés)
DO $$
DECLARE
    v_abonnement_id BIGINT;
BEGIN
    -- Nouveaux organisateurs 34-36
    SELECT creer_abonnement('organisateur34@yopmail.com', 'pro_monthly', '2025-11-01 00:00:00') INTO v_abonnement_id;
    SELECT creer_abonnement('organisateur35@yopmail.com', 'enterprise_monthly', '2025-11-01 00:00:00') INTO v_abonnement_id;
    SELECT creer_abonnement('organisateur36@yopmail.com', 'basic_quarterly', '2025-11-01 00:00:00') INTO v_abonnement_id;
    
    -- Organisateurs 35 et 36 non validés
    UPDATE profils_organisateurs 
    SET statut_verification = 'pending'
    WHERE id_utilisateur IN (
        SELECT id FROM utilisateurs 
        WHERE email IN ('organisateur35@yopmail.com', 'organisateur36@yopmail.com')
    );
END $$;

-- Factures Novembre 2025
DO $$
DECLARE
    v_abonnement RECORD;
    v_type_plan TEXT;
    v_mensuel_count INTEGER := 0;
    v_trimestre_count INTEGER := 0;
    v_prepaye_count INTEGER := 0;
    v_facture_id BIGINT;
BEGIN
    FOR v_abonnement IN 
        SELECT ao.id, pa.periode_facturation, pa.niveau
        FROM abonnements_organisateurs ao
        JOIN profils_organisateurs po ON ao.id_profil_organisateur = po.id
        JOIN utilisateurs u ON po.id_utilisateur = u.id
        JOIN plans_abonnements pa ON ao.id_plan = pa.id
        WHERE u.email LIKE 'organisateur%@yopmail.com'
        AND ao.statut = 'active'
        AND po.statut_verification = 'verified'
        AND u.email NOT IN ('organisateur33@yopmail.com', 'organisateur35@yopmail.com', 'organisateur36@yopmail.com') -- non validés
        AND u.email NOT IN ('organisateur11@yopmail.com', 'organisateur18@yopmail.com', 
                           'organisateur24@yopmail.com', 'organisateur27@yopmail.com') -- en pause
    LOOP
        -- Déterminer le type de facturation
        IF v_abonnement.niveau IN ('basic', 'pro', 'enterprise') AND 
           v_abonnement.periode_facturation = 'monthly' AND
           v_mensuel_count < 3 THEN
            -- Factures mensuelles
            SELECT creer_facture_abonnement(v_abonnement.id, '2025-11-01') INTO v_facture_id;
            v_mensuel_count := v_mensuel_count + 1;
            
        ELSIF v_abonnement.niveau IN ('basic', 'pro', 'enterprise') AND 
              v_abonnement.periode_facturation = 'quarterly' AND
              v_trimestre_count < 2 THEN
            -- Factures trimestrielles
            SELECT creer_facture_abonnement(v_abonnement.id, '2025-11-01') INTO v_facture_id;
            v_trimestre_count := v_trimestre_count + 1;
            
        ELSE
            -- Factures prépayées
            SELECT creer_facture_abonnement(v_abonnement.id, '2025-11-01', 'paid', true) INTO v_facture_id;
            v_prepaye_count := v_prepaye_count + 1;
        END IF;
    END LOOP;
    
    RAISE NOTICE 'Novembre 2025: % mensuels, % trimestriels, % prépayés', 
        v_mensuel_count, v_trimestre_count, v_prepaye_count;
END $$;

-- ============================================================
-- 12. ABONNEMENTS DÉCEMBRE 2025
-- ============================================================
-- Réactiver les 4 organisateurs en pause
-- Total: 23 organisateurs actifs + 3 non validés
DO $$
BEGIN
    -- Réactiver les organisateurs en pause
    UPDATE abonnements_organisateurs ao
    SET statut = 'active',
        repris_le = '2025-12-01 00:00:00',
        modifie_le = NOW()
    FROM profils_organisateurs po
    JOIN utilisateurs u ON po.id_utilisateur = u.id
    WHERE ao.id_profil_organisateur = po.id
    AND u.email IN ('organisateur11@yopmail.com', 'organisateur18@yopmail.com', 
                   'organisateur24@yopmail.com', 'organisateur27@yopmail.com');
END $$;

-- Factures Décembre 2025
DO $$
DECLARE
    v_abonnement RECORD;
    v_type_plan TEXT;
    v_mensuel_count INTEGER := 0;
    v_trimestre_count INTEGER := 0;
    v_prepaye_count INTEGER := 0;
    v_facture_id BIGINT;
BEGIN
    FOR v_abonnement IN 
        SELECT ao.id, pa.periode_facturation, pa.niveau
        FROM abonnements_organisateurs ao
        JOIN profils_organisateurs po ON ao.id_profil_organisateur = po.id
        JOIN utilisateurs u ON po.id_utilisateur = u.id
        JOIN plans_abonnements pa ON ao.id_plan = pa.id
        WHERE u.email LIKE 'organisateur%@yopmail.com'
        AND ao.statut = 'active'
        AND po.statut_verification = 'verified'
        AND u.email NOT IN ('organisateur33@yopmail.com', 'organisateur35@yopmail.com', 'organisateur36@yopmail.com') -- non validés
    LOOP
        -- Déterminer le type de facturation
        IF v_abonnement.niveau IN ('basic', 'pro', 'enterprise') AND 
           v_abonnement.periode_facturation = 'monthly' AND
           v_mensuel_count < 10 THEN
            -- Factures mensuelles
            SELECT creer_facture_abonnement(v_abonnement.id, '2025-12-01') INTO v_facture_id;
            v_mensuel_count := v_mensuel_count + 1;
            
        ELSIF v_abonnement.niveau IN ('basic', 'pro', 'enterprise') AND 
              v_abonnement.periode_facturation = 'quarterly' AND
              v_trimestre_count < 6 THEN
            -- Factures trimestrielles
            SELECT creer_facture_abonnement(v_abonnement.id, '2025-12-01') INTO v_facture_id;
            v_trimestre_count := v_trimestre_count + 1;
            
        ELSE
            -- Factures prépayées
            SELECT creer_facture_abonnement(v_abonnement.id, '2025-12-01', 'paid', true) INTO v_facture_id;
            v_prepaye_count := v_prepaye_count + 1;
        END IF;
    END LOOP;
    
    RAISE NOTICE 'Décembre 2025: % mensuels, % trimestriels, % prépayés', 
        v_mensuel_count, v_trimestre_count, v_prepaye_count;
END $$;

-- ============================================================
-- 13. ABONNEMENTS JANVIER 2026
-- ============================================================
-- Offre populaire: Enterprise mensuel
-- Créer des transactions pour certains paiements
DO $$
DECLARE
    v_abonnement RECORD;
    v_facture_id BIGINT;
BEGIN
    -- Factures Janvier 2026
    FOR v_abonnement IN (
        SELECT ao.id 
        FROM abonnements_organisateurs ao
        JOIN profils_organisateurs po ON ao.id_profil_organisateur = po.id
        JOIN utilisateurs u ON po.id_utilisateur = u.id
        JOIN plans_abonnements pa ON ao.id_plan = pa.id
        WHERE u.email LIKE 'organisateur%@yopmail.com'
        AND ao.statut = 'active'
        AND po.statut_verification = 'verified'
        AND u.email NOT IN ('organisateur33@yopmail.com', 'organisateur35@yopmail.com', 'organisateur36@yopmail.com')
        AND pa.code = 'enterprise_monthly'
        LIMIT 5
    ) LOOP
        SELECT creer_facture_abonnement(v_abonnement.id, '2026-01-01') INTO v_facture_id;
    END LOOP;
END $$;

-- ============================================================
-- 14. VERIFICATION DES DONNEES
-- ============================================================
-- Vérification du nombre d'utilisateurs
SELECT 'Utilisateurs totaux' as type, COUNT(*) as nombre FROM utilisateurs
UNION ALL
SELECT 'Administrateurs', COUNT(*) FROM utilisateurs WHERE role = 'admin'
UNION ALL
SELECT 'Organisateurs', COUNT(*) FROM utilisateurs WHERE role = 'organizer'
UNION ALL
SELECT 'Utilisateurs normaux', COUNT(*) FROM utilisateurs WHERE role = 'user';

-- Vérification des abonnements par mois
SELECT 
    DATE_TRUNC('month', ao.commence_le) as mois,
    pa.niveau,
    pa.periode_facturation,
    COUNT(*) as nombre_abonnements,
    COUNT(CASE WHEN ao.statut = 'active' THEN 1 END) as actifs,
    COUNT(CASE WHEN ao.statut = 'paused' THEN 1 END) as en_pause
FROM abonnements_organisateurs ao
JOIN plans_abonnements pa ON ao.id_plan = pa.id
GROUP BY DATE_TRUNC('month', ao.commence_le), pa.niveau, pa.periode_facturation
ORDER BY mois, pa.niveau;

-- Vérification des factures par mois
SELECT 
    DATE_TRUNC('month', fa.mois_facturation) as mois,
    pa.niveau,
    pa.periode_facturation,
    COUNT(*) as nombre_factures,
    COUNT(CASE WHEN fa.est_prepayee THEN 1 END) as prepayees,
    SUM(fa.montant_total) as montant_total
FROM factures_abonnements fa
JOIN abonnements_organisateurs ao ON fa.id_abonnement = ao.id
JOIN plans_abonnements pa ON ao.id_plan = pa.id
GROUP BY DATE_TRUNC('month', fa.mois_facturation), pa.niveau, pa.periode_facturation
ORDER BY mois, pa.niveau;

-- Statistiques des organisateurs non validés
SELECT 
    po.statut_verification,
    COUNT(*) as nombre_organisateurs
FROM profils_organisateurs po
GROUP BY po.statut_verification;

-- Nettoyage des fonctions temporaires
DROP FUNCTION IF EXISTS creer_organisateur(TEXT, TEXT, TEXT, organizer_type_enum, TEXT);
DROP FUNCTION IF EXISTS creer_abonnement(TEXT, TEXT, TIMESTAMPTZ, subscription_status_enum);
DROP FUNCTION IF EXISTS creer_facture_abonnement(BIGINT, DATE, TEXT, BOOLEAN, TIMESTAMPTZ);

-- ============================================================
-- FIN DU SCRIPT
-- ============================================================