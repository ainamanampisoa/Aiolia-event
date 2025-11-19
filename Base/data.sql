-- ============================================================
--  AIOLIA – DONNÉES DE TEST POUR LES FONCTIONNALITÉS ADMIN
--  Génération : 2025-11-11
--  Objectif  : peupler la base avec un jeu d'essai riche
--              (utilisateurs, organisateurs, abonnements, paiements)
--              Dates basées sur l'année courante (2025) et le mois courant
-- ============================================================

\c aiolia_event;
SET search_path TO aiolia, public;

BEGIN;

-- Variables pour le mois et l'année courants
DO $$
DECLARE
    current_year INTEGER := EXTRACT(YEAR FROM CURRENT_DATE);
    current_month INTEGER := EXTRACT(MONTH FROM CURRENT_DATE);
    current_date_val DATE := CURRENT_DATE;
    seq_record RECORD;
BEGIN
-- Réinitialisation des tables clés (cascade pour respecter les FK)
TRUNCATE TABLE
    historique_paiements_abonnements,
    paiements_abonnements,
    elements_factures_abonnements,
    factures_abonnements,
    abonnements_organisateurs,
    profils_organisateurs,
    plans_abonnements,
    portefeuilles,
    statistiques_evenements_utilisateurs,
    profils_utilisateurs,
    utilisateurs
RESTART IDENTITY CASCADE;

    -- Réinitialisation explicite de toutes les séquences
    PERFORM setval('sequence_numero_facture', 100000, false);
    
    FOR seq_record IN 
        SELECT schemaname, sequencename 
        FROM pg_sequences 
        WHERE schemaname = 'aiolia'
        AND sequencename LIKE '%_id_seq'
    LOOP
        EXECUTE format('SELECT setval(%L, 1, false)', 
            seq_record.schemaname || '.' || seq_record.sequencename);
    END LOOP;
END $$;

-- ------------------------------------------------------------
-- 1. Utilisateurs (60 organisateurs + 15 utilisateurs + 5 admins = 80)
-- ------------------------------------------------------------
-- Génération des 60 organisateurs avec leurs profils
INSERT INTO utilisateurs (
    id,
    email,
    identifiant_connexion,
    methode_connexion,
    hash_mot_de_passe,
    prenom,
    nom,
    telephone,
    code_pays,
    code_langue,
    fuseau_horaire,
    role,
    statut,
    email_verifie,
    telephone_verifie,
    termes_acceptes_le,
    cree_le,
    modifie_le
)
SELECT
    gs,
    CONCAT('organisateur', LPAD(gs::text, 2, '0'), '@yopmail.com'),
    CONCAT('organisateur', LPAD(gs::text, 2, '0'), '@yopmail.com'),
    'password',
    crypt('Org#Test123', gen_salt('bf', 12)),
    CONCAT('Organisateur', LPAD(gs::text, 2, '0')),
    CONCAT('Nom', LPAD(gs::text, 2, '0')),
    CONCAT('+2613200', LPAD(gs::text, 5, '0')),
    'MG',
    'fr-FR',
    'Indian/Antananarivo',
    'organizer',
    CASE 
        WHEN gs <= 52 THEN 1  -- Validés (52 premiers)
        ELSE 0                 -- Non validés (8 derniers)
    END,
    CASE WHEN gs <= 52 THEN TRUE ELSE FALSE END,
    CASE WHEN gs <= 52 THEN TRUE ELSE FALSE END,
    CASE WHEN gs <= 52 THEN CURRENT_DATE - INTERVAL '30 days' ELSE NULL END,
    CURRENT_DATE - INTERVAL '60 days' + (gs * INTERVAL '1 day'),
    CURRENT_DATE
FROM generate_series(1, 60) AS gs;

    -- Utilisateurs finaux (15 comptes)
INSERT INTO utilisateurs (
    id,
    email,
    identifiant_connexion,
    methode_connexion,
    hash_mot_de_passe,
    prenom,
    nom,
    telephone,
    code_pays,
    code_langue,
    fuseau_horaire,
    role,
    statut,
    email_verifie,
    telephone_verifie,
    termes_acceptes_le,
    cree_le,
    modifie_le
)
SELECT
    60 + gs,
    CONCAT('user', LPAD(gs::text, 2, '0'), '@yopmail.com'),
    CONCAT('user', LPAD(gs::text, 2, '0'), '@yopmail.com'),
    'password',
    crypt('User#Test123', gen_salt('bf', 12)),
    CONCAT('Client', LPAD(gs::text, 2, '0')),
    CONCAT('Nom', LPAD(gs::text, 2, '0')),
    CONCAT('+2613201', LPAD(gs::text, 4, '0')),
    'MG',
    'fr-FR',
    'Indian/Antananarivo',
    'user',
    1,
    TRUE,
    FALSE,
    CURRENT_DATE - INTERVAL '30 days',
    CURRENT_DATE - INTERVAL '30 days' + (gs * INTERVAL '1 day'),
    CURRENT_DATE
FROM generate_series(1, 15) AS gs;

    -- Administrateurs (5 comptes)
INSERT INTO utilisateurs (
    id,
    email,
    identifiant_connexion,
    methode_connexion,
    hash_mot_de_passe,
    prenom,
    nom,
    telephone,
    code_pays,
    code_langue,
    fuseau_horaire,
    role,
    statut,
    email_verifie,
    telephone_verifie,
    termes_acceptes_le,
    cree_le,
    modifie_le
)
SELECT
    75 + gs,
    CONCAT('admin', LPAD(gs::text, 2, '0'), '@yopmail.com'),
    CONCAT('admin', LPAD(gs::text, 2, '0'), '@yopmail.com'),
    'password',
    crypt('Admin#Test123', gen_salt('bf', 12)),
    CONCAT('Admin', LPAD(gs::text, 2, '0')),
    CONCAT('Nom', LPAD(gs::text, 2, '0')),
    CONCAT('+2613202', LPAD(gs::text, 4, '0')),
    'MG',
    'fr-FR',
    'Indian/Antananarivo',
    'admin',
    1,
    TRUE,
    TRUE,
    CURRENT_DATE - INTERVAL '90 days',
    CURRENT_DATE - INTERVAL '90 days' + (gs * INTERVAL '1 day'),
    CURRENT_DATE
FROM generate_series(1, 5) AS gs;

-- Profils utilisateurs enrichis
INSERT INTO profils_utilisateurs (
    id_utilisateur,
    telephone,
    code_pays,
    code_langue,
    fuseau_horaire,
    url_avatar,
    mode_sombre_active,
    opt_in_marketing,
    categories_preferees
)
SELECT
    u.id,
    u.telephone,
    u.code_pays,
    u.code_langue,
    u.fuseau_horaire,
    CONCAT('https://cdn.aiolia.test/avatars/', u.id, '.png'),
    (u.role = 'admin'),
    (u.role = 'user'),
    CASE
        WHEN u.role = 'organizer' THEN ARRAY['concert', 'conference']
        WHEN u.role = 'user' THEN ARRAY['concert', 'sport']
        ELSE ARRAY['gestion']
    END
FROM utilisateurs u;

-- Statistiques utilisateurs initiales
INSERT INTO statistiques_evenements_utilisateurs (id_utilisateur, evenements_auxquels_a_participe, evenements_a_venir, depenses_totales, categories_favorites, dernier_evenement_le, modifie_le)
SELECT
    u.id,
    CASE WHEN u.role = 'user' THEN (u.id % 5) ELSE 0 END,
    CASE WHEN u.role = 'user' THEN ((u.id + 1) % 3) ELSE 0 END,
    CASE WHEN u.role = 'user' THEN (u.id % 5) * 25000 ELSE 0 END,
    CASE
        WHEN u.role = 'user' THEN ARRAY['concert']
        WHEN u.role = 'organizer' THEN ARRAY['business']
        ELSE ARRAY['admin']
    END,
    CASE WHEN u.role = 'user' THEN CURRENT_DATE - (u.id % 5) * INTERVAL '10 days' ELSE NULL END,
    CURRENT_DATE
FROM utilisateurs u;

-- Comptes portefeuilles
INSERT INTO portefeuilles (id_utilisateur, solde, solde_points, devise, cree_le, modifie_le)
SELECT
    u.id,
    CASE
        WHEN u.role = 'organizer' THEN 500000 + (u.id * 10000)
        WHEN u.role = 'user' THEN 75000 + (u.id * 2500)
        ELSE 1000000 + (u.id * 5000)
    END,
    CASE WHEN u.role = 'user' THEN u.id * 10 ELSE 0 END,
    'MGA',
    CURRENT_DATE,
    CURRENT_DATE
FROM utilisateurs u;

-- ------------------------------------------------------------
-- 2. Plans d'abonnement (9 offres : Basic, Pro, Enterprise × 3 périodes)
--    SANS les plans "lifetime" (à vie)
-- ------------------------------------------------------------
INSERT INTO plans_abonnements (
    id,
    code,
    nom,
    description,
    niveau,
    periode_facturation,
    nombre_periodes,
    devise,
    prix,
    taux_tva,
    fonctionnalites,
    ordre_affichage,
    est_populaire,
    est_actif,
    cree_le,
    modifie_le
) VALUES
    -- BASIC - Mensuel
    (1, 'BASIC_MONTHLY', 'Plan Basic Mensuel', 'Offre de base pour démarrer vos événements - Facturation mensuelle', 'basic', 'monthly', 1, 'MGA', 150000, 20, '{"events_limit":3,"support":"email","features":["gestion_evenements","tableau_bord"]}', 1, FALSE, TRUE, CURRENT_DATE - INTERVAL '365 days', CURRENT_DATE),
    -- BASIC - Trimestriel
    (2, 'BASIC_QUARTERLY', 'Plan Basic Trimestriel', 'Offre de base - Facturation trimestrielle avec réduction', 'basic', 'quarterly', 1, 'MGA', 420000, 20, '{"events_limit":3,"support":"email","features":["gestion_evenements","tableau_bord"],"discount":"6.7%"}', 2, FALSE, TRUE, CURRENT_DATE - INTERVAL '365 days', CURRENT_DATE),
    -- BASIC - Annuel
    (3, 'BASIC_YEARLY', 'Plan Basic Annuel', 'Offre de base - Facturation annuelle avec réduction', 'basic', 'yearly', 1, 'MGA', 1620000, 20, '{"events_limit":3,"support":"email","features":["gestion_evenements","tableau_bord"],"discount":"10%"}', 3, FALSE, TRUE, CURRENT_DATE - INTERVAL '365 days', CURRENT_DATE),
    
    -- PRO - Mensuel
    (4, 'PRO_MONTHLY', 'Plan Pro Mensuel', 'Offre professionnelle avec fonctionnalités avancées - Facturation mensuelle', 'pro', 'monthly', 1, 'MGA', 350000, 20, '{"events_limit":15,"support":"chat","features":["gestion_evenements","tableau_bord","statistiques_avancees","support_prioritaire"]}', 4, TRUE, TRUE, CURRENT_DATE - INTERVAL '365 days', CURRENT_DATE),
    -- PRO - Trimestriel
    (5, 'PRO_QUARTERLY', 'Plan Pro Trimestriel', 'Offre professionnelle - Facturation trimestrielle avec réduction', 'pro', 'quarterly', 1, 'MGA', 980000, 20, '{"events_limit":15,"support":"chat","features":["gestion_evenements","tableau_bord","statistiques_avancees","support_prioritaire"],"discount":"6.7%"}', 5, FALSE, TRUE, CURRENT_DATE - INTERVAL '365 days', CURRENT_DATE),
    -- PRO - Annuel
    (6, 'PRO_YEARLY', 'Plan Pro Annuel', 'Offre professionnelle - Facturation annuelle avec réduction', 'pro', 'yearly', 1, 'MGA', 3780000, 20, '{"events_limit":15,"support":"chat","features":["gestion_evenements","tableau_bord","statistiques_avancees","support_prioritaire"],"discount":"10%"}', 6, FALSE, TRUE, CURRENT_DATE - INTERVAL '365 days', CURRENT_DATE),
    
    -- ENTERPRISE - Mensuel
    (7, 'ENTERPRISE_MONTHLY', 'Plan Enterprise Mensuel', 'Offre entreprise avec toutes les fonctionnalités - Facturation mensuelle', 'enterprise', 'monthly', 1, 'MGA', 600000, 20, '{"events_limit":-1,"support":"phone","features":["gestion_evenements","tableau_bord","statistiques_avancees","support_prioritaire","api_access","white_label"]}', 7, FALSE, TRUE, CURRENT_DATE - INTERVAL '365 days', CURRENT_DATE),
    -- ENTERPRISE - Trimestriel
    (8, 'ENTERPRISE_QUARTERLY', 'Plan Enterprise Trimestriel', 'Offre entreprise - Facturation trimestrielle avec réduction', 'enterprise', 'quarterly', 1, 'MGA', 1680000, 20, '{"events_limit":-1,"support":"phone","features":["gestion_evenements","tableau_bord","statistiques_avancees","support_prioritaire","api_access","white_label"],"discount":"6.7%"}', 8, FALSE, TRUE, CURRENT_DATE - INTERVAL '365 days', CURRENT_DATE),
    -- ENTERPRISE - Annuel
    (9, 'ENTERPRISE_YEARLY', 'Plan Enterprise Annuel', 'Offre entreprise - Facturation annuelle avec réduction', 'enterprise', 'yearly', 1, 'MGA', 6480000, 20, '{"events_limit":-1,"support":"phone","features":["gestion_evenements","tableau_bord","statistiques_avancees","support_prioritaire","api_access","white_label"],"discount":"10%"}', 9, FALSE, TRUE, CURRENT_DATE - INTERVAL '365 days', CURRENT_DATE);

-- ------------------------------------------------------------
-- 3. Profils organisateurs (60 organisateurs)
-- ------------------------------------------------------------
INSERT INTO profils_organisateurs (
    id_utilisateur,
    nom_affichage,
    nom_legal,
    numero_tva,
    email_support,
    telephone_support,
    url_site_web,
    biographie,
    type_organisation,
    numero_immatriculation,
    taille_entreprise,
    statut_verification,
    onboarding_termine_le,
    cree_le,
    modifie_le
)
SELECT
    u.id,
    CONCAT('Organisateur ', LPAD(u.id::text, 2, '0')),
    CONCAT('AIOLIA ORG ', LPAD(u.id::text, 2, '0')),
    CONCAT('TIN-', 100000 + u.id),
    u.email,
    u.telephone,
    CONCAT('https://organizer', LPAD(u.id::text, 2, '0'), '.aiolia.test'),
    CONCAT('Biographie de démonstration pour organisateur ', u.id),
    CASE 
        WHEN u.id % 4 = 1 THEN 'company'::organizer_type_enum
        WHEN u.id % 4 = 2 THEN 'individual'::organizer_type_enum
        WHEN u.id % 4 = 3 THEN 'non_profit'::organizer_type_enum
        ELSE 'collective'::organizer_type_enum
    END,
    CONCAT('RC-', 52000 + u.id),
    CASE WHEN u.id % 2 = 0 THEN '50-100' ELSE '1-10' END,
    CASE
        WHEN u.id <= 52 THEN 'verified'  -- 52 organisateurs validés
        ELSE 'pending'                    -- 8 organisateurs non validés
    END,
    CASE WHEN u.id <= 52 THEN CURRENT_DATE - INTERVAL '120 days' ELSE NULL END,
    CURRENT_DATE - INTERVAL '200 days',
    CURRENT_DATE
FROM utilisateurs u
WHERE u.role = 'organizer';

-- ------------------------------------------------------------
-- 4. Abonnements organisateurs avec différents scénarios
-- ------------------------------------------------------------
-- Scénarios :
-- 1-10 : Basic (10 organisateurs)
-- 11-25 : Changent parfois leurs offres (15 organisateurs)
-- 26-45 : Respectent les dates d'échéance (20 organisateurs)
-- 46-52 : Presque en retard (7 organisateurs)
-- 53-60 : Non validés (8 organisateurs) - pas d'abonnement actif

WITH organizer_scenarios AS (
    SELECT
        op.id AS organizer_profile_id,
        op.id_utilisateur,
        CASE
            -- 1-10 : Basic
            WHEN op.id <= 10 THEN 1  -- Basic Mensuel
            -- 11-25 : Changent parfois leurs offres (mélange)
            WHEN op.id BETWEEN 11 AND 15 THEN 1  -- Basic
            WHEN op.id BETWEEN 16 AND 20 THEN 4  -- Pro
            WHEN op.id BETWEEN 21 AND 25 THEN 7  -- Enterprise
            -- 26-45 : Respectent les dates (mélange)
            WHEN op.id BETWEEN 26 AND 30 THEN 1  -- Basic
            WHEN op.id BETWEEN 31 AND 35 THEN 4  -- Pro
            WHEN op.id BETWEEN 36 AND 40 THEN 7  -- Enterprise
            WHEN op.id BETWEEN 41 AND 45 THEN 3  -- Basic Annuel
            -- 46-52 : Presque en retard (mélange)
            WHEN op.id BETWEEN 46 AND 48 THEN 1  -- Basic
            WHEN op.id BETWEEN 49 AND 50 THEN 4  -- Pro
            WHEN op.id BETWEEN 51 AND 52 THEN 7  -- Enterprise
            ELSE NULL  -- 53-60 : Pas d'abonnement
        END AS plan_id,
        CASE
            WHEN op.id <= 10 THEN 'basic_fixed'
            WHEN op.id BETWEEN 11 AND 25 THEN 'changes_offers'
            WHEN op.id BETWEEN 26 AND 45 THEN 'respects_deadlines'
            WHEN op.id BETWEEN 46 AND 52 THEN 'almost_late'
            ELSE NULL
        END AS scenario_type
    FROM profils_organisateurs op
    WHERE op.id <= 52  -- Seulement les 52 validés ont des abonnements
)
INSERT INTO abonnements_organisateurs (
    id_profil_organisateur,
    id_plan,
    statut,
    mois_prepayes_restants,
    commence_le,
    debut_periode_courante,
    fin_periode_courante,
    renouvellement_le,
    annuler_a_la_fin_periode,
    annule_le,
    mis_en_pause_le,
    repris_le,
    metadonnees,
    cree_le,
    modifie_le
)
SELECT
    os.organizer_profile_id,
    os.plan_id,
    CASE
        -- Scénarios avec pauses programmées
        WHEN os.scenario_type = 'changes_offers' AND os.organizer_profile_id BETWEEN 21 AND 25 THEN 'paused'::subscription_status_enum
        -- Scénario almost_late : si septembre n'est pas payé, le compte est en pause
        WHEN os.scenario_type = 'almost_late' THEN 'paused'::subscription_status_enum
        ELSE 'active'::subscription_status_enum
    END,
    CASE
        -- 4 mois en avance sans payer
        WHEN os.organizer_profile_id BETWEEN 11 AND 15 THEN 4
        -- 7 mois en avance avec pauses
        WHEN os.organizer_profile_id BETWEEN 21 AND 25 THEN 7
        ELSE 0
    END,
    CASE
        -- Abonnements annuels commencés en début d'année
        WHEN os.plan_id IN (3, 6, 9) THEN DATE_TRUNC('year', CURRENT_DATE)
        ELSE CURRENT_DATE - INTERVAL '6 months'
    END,
    DATE_TRUNC('month', CURRENT_DATE),
    DATE_TRUNC('month', CURRENT_DATE) + INTERVAL '1 month' - INTERVAL '1 day',
    DATE_TRUNC('month', CURRENT_DATE) + INTERVAL '1 month',
    FALSE,
    NULL,
    CASE
        -- Pauses pour novembre et décembre (organisateurs 21-25)
        WHEN os.organizer_profile_id BETWEEN 21 AND 25 THEN CURRENT_DATE - INTERVAL '15 days'
        -- Pause pour almost_late : si septembre n'est pas payé avant le 11 octobre, pause depuis le 11 octobre
        WHEN os.scenario_type = 'almost_late' THEN DATE_TRUNC('month', CURRENT_DATE) - INTERVAL '1 month' + INTERVAL '10 days'
        ELSE NULL
    END,
    CASE
        -- Reprise après décembre
        WHEN os.organizer_profile_id BETWEEN 21 AND 25 THEN DATE_TRUNC('year', CURRENT_DATE) + INTERVAL '1 year'
        ELSE NULL
    END,
    jsonb_build_object(
        'scenario_type', os.scenario_type,
        'organizer_id', os.organizer_profile_id
    ),
    CURRENT_DATE - INTERVAL '6 months',
    CURRENT_DATE
FROM organizer_scenarios os
WHERE os.plan_id IS NOT NULL;

-- ------------------------------------------------------------
-- 5. Factures d'abonnements selon les scénarios
-- ------------------------------------------------------------
-- Scénarios de factures :
-- 1. Organisateurs payés annuellement (basic, pro, enterprise) - IDs 41-45
-- 2. Organisateurs payés 4 mois en avance sans payer - IDs 11-15
-- 3. Organisateurs payés mois par mois - IDs 1-10, 26-40
-- 4. Organisateurs payés 7 mois à l'avance avec pauses (novembre/décembre décalés) - IDs 21-25
-- 5. Organisateurs dont le prochain paiement est l'année prochaine - IDs 41-45 (annuels)

WITH subscription_plans AS (
    SELECT
        os.id AS subscription_id,
        os.id_profil_organisateur,
        os.id_plan,
        os.statut,
        os.mois_prepayes_restants,
        os.mis_en_pause_le,
        os.repris_le,
        op.id_utilisateur,
        sp.prix,
        sp.taux_tva,
        sp.periode_facturation,
        sp.niveau,
        CASE
            WHEN op.id <= 10 THEN 'basic_fixed'
            WHEN op.id BETWEEN 11 AND 15 THEN 'prepaid_4_months'
            WHEN op.id BETWEEN 16 AND 20 THEN 'changes_offers'
            WHEN op.id BETWEEN 21 AND 25 THEN 'prepaid_7_months_paused'
            WHEN op.id BETWEEN 26 AND 45 THEN 'monthly_payment'
            WHEN op.id BETWEEN 46 AND 52 THEN 'almost_late'
            ELSE NULL
        END AS scenario_type
    FROM abonnements_organisateurs os
    JOIN profils_organisateurs op ON op.id = os.id_profil_organisateur
    JOIN plans_abonnements sp ON sp.id = os.id_plan
),
invoice_months AS (
    SELECT
        sp.subscription_id,
        sp.id_profil_organisateur,
        sp.id_plan,
        sp.id_utilisateur,
        sp.prix,
        sp.taux_tva,
        sp.periode_facturation,
        sp.niveau,
        sp.scenario_type,
        sp.mois_prepayes_restants,
        sp.mis_en_pause_le,
        sp.repris_le,
        gs AS month_offset,
        DATE_TRUNC('month', CURRENT_DATE) - (gs * INTERVAL '1 month') AS billing_month
    FROM subscription_plans sp
    CROSS JOIN generate_series(0, 11) AS gs  -- 12 derniers mois
    WHERE sp.scenario_type IS NOT NULL
),
-- CTE pour déterminer quels mois sont en pause (basé sur la règle : si mois M non payé avant le 11 de M+1, alors M+1 est en pause)
pause_months AS (
    SELECT DISTINCT
        im.subscription_id,
        im.billing_month,
        im.month_offset,
        -- Un mois est en pause si le mois précédent n'a pas été payé avant le 11 de ce mois
        CASE
            -- Mois en pause programmés (scénario prepaid_7_months_paused)
            WHEN im.scenario_type = 'prepaid_7_months_paused' 
                AND EXTRACT(MONTH FROM im.billing_month) IN (11, 12) THEN TRUE
            -- Pour le scénario almost_late : si septembre (offset 2) n'est pas payé, octobre (offset 1) et novembre (offset 0) sont en pause
            WHEN im.scenario_type = 'almost_late' 
                AND im.month_offset IN (0, 1) THEN TRUE
            ELSE FALSE
        END AS is_pause_month
    FROM invoice_months im
),
invoice_calculations AS (
    SELECT
        im.*,
        COALESCE(pm.is_pause_month, FALSE) AS is_pause_month,
        CASE
            WHEN im.periode_facturation = 'yearly' THEN im.prix / 12
            WHEN im.periode_facturation = 'quarterly' THEN im.prix / 3
            ELSE im.prix
        END AS monthly_price,
        -- Déterminer si la facture doit être prépayée
        CASE
            WHEN im.scenario_type IN ('prepaid_4_months', 'prepaid_7_months_paused')
                AND im.month_offset < im.mois_prepayes_restants THEN TRUE
            ELSE FALSE
        END AS is_prepaid,
        -- Déterminer le statut de la facture
        CASE
            -- Si c'est un mois en pause, statut suspendue
            WHEN COALESCE(pm.is_pause_month, FALSE) THEN 'suspendue'
            -- Annuels payés en début d'année
            WHEN im.scenario_type = 'monthly_payment' 
                AND im.periode_facturation = 'yearly'
                AND im.month_offset <= 11 THEN 'paid'
            -- 4 mois prépayés sans payer (statut pending)
            WHEN im.scenario_type = 'prepaid_4_months'
                AND im.month_offset < 4 THEN 'pending'
            -- 7 mois prépayés avec pauses (sauf mois en pause)
            WHEN im.scenario_type = 'prepaid_7_months_paused'
                AND im.month_offset < 7
                AND NOT (EXTRACT(MONTH FROM im.billing_month) IN (11, 12)) THEN 'pending'
            -- Mois par mois payés
            WHEN im.scenario_type IN ('basic_fixed', 'monthly_payment')
                AND im.month_offset <= 5 THEN 'paid'
            -- Presque en retard : septembre (offset 2) = overdue, octobre (offset 1) et novembre (offset 0) = suspendue (déjà géré ci-dessus)
            WHEN im.scenario_type = 'almost_late'
                AND im.month_offset = 2 THEN 'overdue'
            -- Mois futurs (sauf si en pause)
            WHEN im.month_offset = 0 AND NOT COALESCE(pm.is_pause_month, FALSE) THEN 'issued'
            ELSE 'draft'
        END AS invoice_status,
        -- Date de paiement
        CASE
            WHEN im.scenario_type = 'monthly_payment' 
                AND im.periode_facturation = 'yearly'
                AND im.month_offset <= 11 THEN im.billing_month + INTERVAL '5 days'
            WHEN im.scenario_type IN ('basic_fixed', 'monthly_payment')
                AND im.month_offset <= 5 THEN im.billing_month + INTERVAL '5 days'
            ELSE NULL
        END AS paid_date,
        -- Mode de paiement (réparti entre les différents modes)
        CASE
            -- Factures payées mensuellement
            WHEN im.scenario_type IN ('basic_fixed', 'monthly_payment')
                AND im.month_offset <= 5 THEN 
                    CASE (im.id_utilisateur % 5)
                        WHEN 0 THEN 'orange'
                        WHEN 1 THEN 'airtel'
                        WHEN 2 THEN 'telma'
                        WHEN 3 THEN 'espace'
                        ELSE 'bank_transfer'
                    END
            -- Factures payées annuellement
            WHEN im.scenario_type = 'monthly_payment' 
                AND im.periode_facturation = 'yearly'
                AND im.month_offset <= 11 THEN
                    CASE (im.id_utilisateur % 5)
                        WHEN 0 THEN 'orange'
                        WHEN 1 THEN 'airtel'
                        WHEN 2 THEN 'telma'
                        WHEN 3 THEN 'espace'
                        ELSE 'bank_transfer'
                    END
            -- Factures prépayées 4 mois
            WHEN im.scenario_type = 'prepaid_4_months'
                AND im.month_offset < 4 THEN 
                    CASE (im.id_utilisateur % 4)
                        WHEN 0 THEN 'orange'
                        WHEN 1 THEN 'airtel'
                        WHEN 2 THEN 'telma'
                        ELSE 'espace'
                    END
            -- Factures prépayées 7 mois (sauf mois en pause)
            WHEN im.scenario_type = 'prepaid_7_months_paused'
                AND im.month_offset < 7
                AND NOT (EXTRACT(MONTH FROM im.billing_month) IN (11, 12)) THEN 
                    CASE (im.id_utilisateur % 4)
                        WHEN 0 THEN 'orange'
                        WHEN 1 THEN 'airtel'
                        WHEN 2 THEN 'telma'
                        ELSE 'bank_transfer'
                    END
            ELSE NULL
        END AS payment_method
    FROM invoice_months im
    LEFT JOIN pause_months pm ON pm.subscription_id = im.subscription_id 
        AND pm.billing_month = im.billing_month
)
    INSERT INTO factures_abonnements (
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
        methode_paiement,
        statut,
        emise_le,
        echeance_le,
        payee_le,
        metadonnees,
        cree_le,
        modifie_le
    )
    SELECT
    ic.subscription_id,
    ic.id_utilisateur,
        'MGA',
    CASE WHEN ic.is_pause_month THEN 0 ELSE ic.monthly_price END,
    CASE WHEN ic.is_pause_month THEN 0 ELSE ic.monthly_price * (ic.taux_tva / 100) END,
    CASE WHEN ic.is_pause_month THEN 0 ELSE ic.monthly_price * (1 + ic.taux_tva / 100) END,
    CASE WHEN ic.is_pause_month THEN 0 ELSE ic.monthly_price END,
    CASE WHEN ic.is_pause_month THEN 0 ELSE ic.monthly_price * (ic.taux_tva / 100) END,
    CASE WHEN ic.is_pause_month THEN 0 ELSE ic.monthly_price * (1 + ic.taux_tva / 100) END,
    ic.billing_month,
    ic.is_pause_month,
    ic.is_prepaid,
    ic.payment_method,
    -- Si c'est un mois en pause, forcer le statut à suspendue
    CASE WHEN ic.is_pause_month THEN 'suspendue' ELSE ic.invoice_status END,
    ic.billing_month,
    CASE WHEN ic.is_pause_month THEN NULL ELSE ic.billing_month + INTERVAL '10 days' END,
    ic.paid_date,
    jsonb_build_object(
        'scenario_type', ic.scenario_type,
        'month_offset', ic.month_offset,
        'plan_level', ic.niveau,
        'billing_period', ic.periode_facturation
    ),
    ic.billing_month,
    CURRENT_DATE
FROM invoice_calculations ic
WHERE ic.month_offset <= 11  -- Seulement les 12 derniers mois
    AND (ic.invoice_status != 'draft' OR ic.month_offset = 0);  -- Inclure les factures du mois courant même si draft

-- ------------------------------------------------------------
-- 6. Paiements d'abonnements pour les factures payées et prépayées
-- ------------------------------------------------------------
-- Créer des paiements pour toutes les factures qui ont été payées (paid)
INSERT INTO paiements_abonnements (
    id_facture,
    fournisseur,
    reference_fournisseur,
    statut,
    montant,
    devise,
    paye_le,
    metadonnees,
    cree_le,
    modifie_le
)
SELECT
    fa.id,
    fa.methode_paiement,
    CONCAT('REF-', LPAD(fa.id::text, 8, '0'), '-', TO_CHAR(COALESCE(fa.payee_le, fa.emise_le), 'YYYYMMDD')),
    'paid'::payment_status_enum,
    fa.montant_total,
    fa.devise,
    COALESCE(fa.payee_le, fa.emise_le),
    jsonb_build_object(
        'invoice_number', fa.numero_facture,
        'billing_month', fa.mois_facturation,
        'scenario_type', fa.metadonnees->>'scenario_type',
        'is_prepaid', fa.est_prepayee
    ),
    COALESCE(fa.payee_le, fa.emise_le),
    COALESCE(fa.payee_le, fa.emise_le)
FROM factures_abonnements fa
WHERE fa.statut IN ('paid', 'pending')
    AND fa.methode_paiement IS NOT NULL;

-- Créer l'historique des paiements
INSERT INTO historique_paiements_abonnements (
    id_paiement,
    statut_de,
    statut_vers,
    modifie_le,
    metadonnees
)
SELECT
    pa.id,
    NULL,
    'paid'::payment_status_enum,
    pa.paye_le,
    jsonb_build_object('detail', 'Paiement initial enregistré')
FROM paiements_abonnements pa;

COMMIT;

