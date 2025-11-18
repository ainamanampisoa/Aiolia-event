-- ============================================================
--  AIOLIA – DONNÉES DE TEST POUR LES FONCTIONNALITÉS ADMIN
--  Génération : 2025-11-11
--  Objectif  : peupler la base avec un jeu d'essai riche
--              (utilisateurs, organisateurs, abonnements, paiements)
-- ============================================================

\c aiolia_event;
SET search_path TO aiolia, public;

BEGIN;

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

-- Réinitialisation explicite de toutes les séquences (pour garantir la réinitialisation)
DO $$
DECLARE
    seq_record RECORD;
BEGIN
    -- Réinitialiser la séquence des numéros de facture
    PERFORM setval('sequence_numero_facture', 100000, false);
    
    -- Réinitialiser toutes les séquences IDENTITY des tables principales
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
-- 1. Utilisateurs (30 comptes : 10 organisateurs, 15 utilisateurs, 5 admins)
--    - 5 organisateurs actifs
--    - 5 organisateurs non validés utilisant les adresses indiquées
-- ------------------------------------------------------------
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
) VALUES
    -- Organisateurs actifs
    (1, 'organisateur1@yopmail.com', 'organisateur1@yopmail.com', 'password', crypt('Org#Actif123', gen_salt('bf', 12)), 'OrgActif', 'Rafal', '+261320000001', 'MG', 'fr-FR', 'Indian/Antananarivo', 'organizer', 1, TRUE, TRUE, '2024-01-10', '2024-01-10', '2025-02-01'),
    (2, 'organisateur2@yopmail.com', 'organisateur2@yopmail.com', 'password', crypt('Org#Actif123', gen_salt('bf', 12)), 'OrgActif', 'Miora', '+261320000002', 'MG', 'fr-FR', 'Indian/Antananarivo', 'organizer', 1, TRUE, TRUE, '2024-02-15', '2024-02-15', '2025-02-01'),
    (3, 'organisateur3@yopmail.com', 'organisateur3@yopmail.com', 'password', crypt('Org#Actif123', gen_salt('bf', 12)), 'OrgActif', 'Santatra', '+261320000003', 'MG', 'fr-FR', 'Indian/Antananarivo', 'organizer', 1, TRUE, TRUE, '2024-03-05', '2024-03-05', '2025-02-01'),
    (4, 'organisateur4@yopmail.com', 'organisateur4@yopmail.com', 'password', crypt('Org#Actif123', gen_salt('bf', 12)), 'OrgActif', 'Tahina', '+261320000004', 'MG', 'fr-FR', 'Indian/Antananarivo', 'organizer', 1, TRUE, TRUE, '2024-04-12', '2024-04-12', '2025-02-01'),
    (5, 'organisateur5@yopmail.com', 'organisateur5@yopmail.com', 'password', crypt('Org#Actif123', gen_salt('bf', 12)), 'OrgActif', 'Feno', '+261320000005', 'MG', 'fr-FR', 'Indian/Antananarivo', 'organizer', 1, TRUE, TRUE, '2024-05-08', '2024-05-08', '2025-02-01'),
    -- Organisateurs non validés (adresses imposées)
    (6, 'valeafifaliana+org1@yopmail.com', 'valeafifaliana+org1@yopmail.com', 'password', crypt('Org#Pending123', gen_salt('bf', 12)), 'OrgPending', 'Anja', '+261320000006', 'MG', 'fr-FR', 'Indian/Antananarivo', 'organizer', 0, FALSE, FALSE, NULL, '2024-06-01', '2025-02-01'),
    (7, 'valeafifaliana+org2@yopmail.com', 'valeafifaliana+org2@yopmail.com', 'password', crypt('Org#Pending123', gen_salt('bf', 12)), 'OrgPending', 'Lova', '+261320000007', 'MG', 'fr-FR', 'Indian/Antananarivo', 'organizer', 0, FALSE, FALSE, NULL, '2024-06-15', '2025-02-01'),
    (8, 'valeafifaliana+org3@yopmail.com', 'valeafifaliana+org3@yopmail.com', 'password', crypt('Org#Pending123', gen_salt('bf', 12)), 'OrgPending', 'Hery', '+261320000008', 'MG', 'fr-FR', 'Indian/Antananarivo', 'organizer', 0, FALSE, FALSE, NULL, '2024-07-01', '2025-02-01'),
    (9, 'malalavalea@gmail.com', 'malalavalea@gmail.com', 'password', crypt('Org#Pending123', gen_salt('bf', 12)), 'OrgPending', 'Irina', '+261320000009', 'MG', 'fr-FR', 'Indian/Antananarivo', 'organizer', 0, FALSE, FALSE, NULL, '2024-07-20', '2025-02-01'),
    (10, 'malalavalea+org5@yopmail.com', 'malalavalea+org5@yopmail.com', 'password', crypt('Org#Pending123', gen_salt('bf', 12)), 'OrgPending', 'Josoa', '+261320000010', 'MG', 'fr-FR', 'Indian/Antananarivo', 'organizer', 0, FALSE, FALSE, NULL, '2024-08-05', '2025-02-01'),
    -- Utilisateurs finaux (15 comptes)
    (11, 'user01@yopmail.com', 'user01@yopmail.com', 'password', crypt('User#Test123', gen_salt('bf', 12)), 'Client', 'Un', '+261320000011', 'MG', 'fr-FR', 'Indian/Antananarivo', 'user', 1, TRUE, FALSE, '2024-02-01', '2024-02-01', '2025-02-01'),
    (12, 'user02@yopmail.com', 'user02@yopmail.com', 'password', crypt('User#Test123', gen_salt('bf', 12)), 'Client', 'Deux', '+261320000012', 'MG', 'fr-FR', 'Indian/Antananarivo', 'user', 1, TRUE, FALSE, '2024-02-02', '2024-02-02', '2025-02-01'),
    (13, 'user03@yopmail.com', 'user03@yopmail.com', 'password', crypt('User#Test123', gen_salt('bf', 12)), 'Client', 'Trois', '+261320000013', 'MG', 'fr-FR', 'Indian/Antananarivo', 'user', 1, TRUE, FALSE, '2024-02-03', '2024-02-03', '2025-02-01'),
    (14, 'user04@yopmail.com', 'user04@yopmail.com', 'password', crypt('User#Test123', gen_salt('bf', 12)), 'Client', 'Quatre', '+261320000014', 'MG', 'fr-FR', 'Indian/Antananarivo', 'user', 1, TRUE, FALSE, '2024-02-04', '2024-02-04', '2025-02-01'),
    (15, 'user05@yopmail.com', 'user05@yopmail.com', 'password', crypt('User#Test123', gen_salt('bf', 12)), 'Client', 'Cinq', '+261320000015', 'MG', 'fr-FR', 'Indian/Antananarivo', 'user', 1, TRUE, FALSE, '2024-02-05', '2024-02-05', '2025-02-01'),
    (16, 'user06@yopmail.com', 'user06@yopmail.com', 'password', crypt('User#Test123', gen_salt('bf', 12)), 'Client', 'Six', '+261320000016', 'MG', 'fr-FR', 'Indian/Antananarivo', 'user', 1, TRUE, FALSE, '2024-02-06', '2024-02-06', '2025-02-01'),
    (17, 'user07@yopmail.com', 'user07@yopmail.com', 'password', crypt('User#Test123', gen_salt('bf', 12)), 'Client', 'Sept', '+261320000017', 'MG', 'fr-FR', 'Indian/Antananarivo', 'user', 1, TRUE, FALSE, '2024-02-07', '2024-02-07', '2025-02-01'),
    (18, 'user08@yopmail.com', 'user08@yopmail.com', 'password', crypt('User#Test123', gen_salt('bf', 12)), 'Client', 'Huit', '+261320000018', 'MG', 'fr-FR', 'Indian/Antananarivo', 'user', 1, TRUE, FALSE, '2024-02-08', '2024-02-08', '2025-02-01'),
    (19, 'user09@yopmail.com', 'user09@yopmail.com', 'password', crypt('User#Test123', gen_salt('bf', 12)), 'Client', 'Neuf', '+261320000019', 'MG', 'fr-FR', 'Indian/Antananarivo', 'user', 1, TRUE, FALSE, '2024-02-09', '2024-02-09', '2025-02-01'),
    (20, 'user10@yopmail.com', 'user10@yopmail.com', 'password', crypt('User#Test123', gen_salt('bf', 12)), 'Client', 'Dix', '+261320000020', 'MG', 'fr-FR', 'Indian/Antananarivo', 'user', 1, TRUE, FALSE, '2024-02-10', '2024-02-10', '2025-02-01'),
    (21, 'user11@yopmail.com', 'user11@yopmail.com', 'password', crypt('User#Test123', gen_salt('bf', 12)), 'Client', 'Onze', '+261320000021', 'MG', 'fr-FR', 'Indian/Antananarivo', 'user', 0, FALSE, FALSE, NULL, '2024-02-11', '2025-02-01'),
    (22, 'user12@yopmail.com', 'user12@yopmail.com', 'password', crypt('User#Test123', gen_salt('bf', 12)), 'Client', 'Douze', '+261320000022', 'MG', 'fr-FR', 'Indian/Antananarivo', 'user', 0, FALSE, FALSE, NULL, '2024-02-12', '2025-02-01'),
    (23, 'user13@yopmail.com', 'user13@yopmail.com', 'password', crypt('User#Test123', gen_salt('bf', 12)), 'Client', 'Treize', '+261320000023', 'MG', 'fr-FR', 'Indian/Antananarivo', 'user', 0, FALSE, FALSE, NULL, '2024-02-13', '2025-02-01'),
    (24, 'user14@yopmail.com', 'user14@yopmail.com', 'password', crypt('User#Test123', gen_salt('bf', 12)), 'Client', 'Quatorze', '+261320000024', 'MG', 'fr-FR', 'Indian/Antananarivo', 'user', 1, TRUE, FALSE, '2024-02-14', '2024-02-14', '2025-02-01'),
    (25, 'user15@yopmail.com', 'user15@yopmail.com', 'password', crypt('User#Test123', gen_salt('bf', 12)), 'Client', 'Quinze', '+261320000025', 'MG', 'fr-FR', 'Indian/Antananarivo', 'user', 1, TRUE, FALSE, '2024-02-15', '2024-02-15', '2025-02-01'),
    -- Administrateurs (5 comptes)
    (26, 'admin01@yopmail.com', 'admin01@yopmail.com', 'password', crypt('Admin#Test123', gen_salt('bf', 12)), 'Admin', 'Alpha', '+261320000026', 'MG', 'fr-FR', 'Indian/Antananarivo', 'admin', 1, TRUE, TRUE, '2024-01-05', '2024-01-05', '2025-02-01'),
    (27, 'admin02@yopmail.com', 'admin02@yopmail.com', 'password', crypt('Admin#Test123', gen_salt('bf', 12)), 'Admin', 'Beta', '+261320000027', 'MG', 'fr-FR', 'Indian/Antananarivo', 'admin', 1, TRUE, TRUE, '2024-01-06', '2024-01-06', '2025-02-01'),
    (28, 'admin03@yopmail.com', 'admin03@yopmail.com', 'password', crypt('Admin#Test123', gen_salt('bf', 12)), 'Admin', 'Gamma', '+261320000028', 'MG', 'fr-FR', 'Indian/Antananarivo', 'admin', 1, TRUE, TRUE, '2024-01-07', '2024-01-07', '2025-02-01'),
    (29, 'admin04@yopmail.com', 'admin04@yopmail.com', 'password', crypt('Admin#Test123', gen_salt('bf', 12)), 'Admin', 'Delta', '+261320000029', 'MG', 'fr-FR', 'Indian/Antananarivo', 'admin', 1, TRUE, TRUE, '2024-01-08', '2024-01-08', '2025-02-01'),
    (30, 'admin05@yopmail.com', 'admin05@yopmail.com', 'password', crypt('Admin#Test123', gen_salt('bf', 12)), 'Admin', 'Epsilon', '+261320000030', 'MG', 'fr-FR', 'Indian/Antananarivo', 'admin', 1, TRUE, TRUE, '2024-01-09', '2024-01-09', '2025-02-01');

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
    COALESCE(u.telephone, CONCAT('+2613201', LPAD(u.id::text, 4, '0'))),
    COALESCE(u.code_pays, 'MG'),
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
    CASE WHEN u.role = 'user' THEN now() - (u.id % 5) * INTERVAL '10 days' ELSE NULL END,
    now()
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
    now(),
    now()
FROM utilisateurs u;

-- ------------------------------------------------------------
-- 2. Plans d'abonnement (3 offres : Basic, Pro, Enterprise)
--    Les organisateurs peuvent choisir n'importe quelle offre
--    indépendamment de leur organization_type
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
    (1, 'BASIC', 'Plan Basic', 'Offre de base pour démarrer vos événements', 'basic', 'monthly', 1, 'MGA', 150000, 20, '{"events_limit":3,"support":"email","features":["gestion_evenements","tableau_bord"]}', 1, FALSE, TRUE, '2024-01-01', '2025-02-01'),
    (2, 'PRO', 'Plan Pro', 'Offre professionnelle avec fonctionnalités avancées', 'pro', 'monthly', 1, 'MGA', 350000, 20, '{"events_limit":15,"support":"chat","features":["gestion_evenements","tableau_bord","statistiques_avancees","support_prioritaire"]}', 2, TRUE, TRUE, '2024-01-01', '2025-02-01'),
    (3, 'ENTERPRISE', 'Plan Enterprise', 'Offre entreprise avec toutes les fonctionnalités', 'enterprise', 'monthly', 1, 'MGA', 600000, 20, '{"events_limit":-1,"support":"phone","features":["gestion_evenements","tableau_bord","statistiques_avancees","support_prioritaire","api_access","white_label"]}', 3, FALSE, TRUE, '2024-01-01', '2025-02-01');

-- ------------------------------------------------------------
-- 3. Profils organisateurs & abonnements
-- ------------------------------------------------------------
WITH organizer_base AS (
    SELECT
        u.id AS user_id,
        ROW_NUMBER() OVER (ORDER BY u.id) AS rn,
        u.statut
    FROM utilisateurs u
    WHERE u.role = 'organizer'
)
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
    ob.user_id,
    CONCAT('Organisateur ', LPAD(ob.rn::text, 2, '0')),
    CONCAT('AIOLIA ORG ', LPAD(ob.rn::text, 2, '0')),
    CONCAT('TIN-', 100000 + ob.rn),
    u.email,
    u.telephone,
    CONCAT('https://organizer', LPAD(ob.rn::text, 2, '0'), '.aiolia.test'),
    CONCAT('Biographie de démonstration pour organisateur ', ob.rn),
    CASE 
        WHEN ob.rn <= 3 THEN 'company'::organizer_type_enum
        WHEN ob.rn <= 6 THEN 'individual'::organizer_type_enum
        WHEN ob.rn <= 8 THEN 'non_profit'::organizer_type_enum
        ELSE 'collective'::organizer_type_enum
    END,
    CONCAT('RC-', 52000 + ob.rn),
    CASE WHEN ob.rn <= 4 THEN '50-100' ELSE '1-10' END,
    CASE
        WHEN ob.rn <= 5 THEN 'verified'
        WHEN ob.rn = 6 THEN 'pending'
        WHEN ob.rn = 7 THEN 'pending'
        WHEN ob.rn = 8 THEN 'rejected'
        ELSE 'pending'
    END,
    CASE WHEN ob.rn <= 5 THEN now() - INTERVAL '120 days' ELSE NULL END,
    now() - INTERVAL '200 days',
    now()
FROM organizer_base ob
JOIN utilisateurs u ON u.id = ob.user_id;

-- Abonnements des organisateurs (un abonnement par organisateur)
-- Scénarios avec tous les statuts de facture :
-- - Organisateurs 1-3 : Abonnement annuel complet (12 mois payés = 'paid')
-- - Organisateurs 4-5 : Abonnement avec pauses (certains mois non payés)
-- - Organisateur 6 : Paiements en retard (statut 'overdue' puis 'paid')
-- - Organisateur 7 : Factures remboursées (statut 'refunded')
-- - Organisateur 8 : Factures annulées (statut 'void')
-- - Organisateur 9 : Factures en attente (statut 'issued')
-- - Organisateur 10 : Factures brouillon (statut 'draft')
WITH organizer_ranked AS (
    SELECT
        op.id AS organizer_profile_id,
        op.id_utilisateur,
        op.type_organisation,
        ROW_NUMBER() OVER (ORDER BY op.id) AS rn
    FROM profils_organisateurs op
)
INSERT INTO abonnements_organisateurs (
    id_profil_organisateur,
    id_plan,
    statut,
    commence_le,
    debut_periode_courante,
    fin_periode_courante,
    renouvellement_le,
    annuler_a_la_fin_periode,
    annule_le,
    metadonnees,
    cree_le,
    modifie_le
)
SELECT
    orr.organizer_profile_id,
    -- Distribution libre des plans : mélange des 3 offres
    CASE 
        WHEN orr.rn % 3 = 1 THEN 1  -- Plan Basic
        WHEN orr.rn % 3 = 2 THEN 2  -- Plan Pro (le plus populaire)
        ELSE 3                       -- Plan Enterprise
    END,
    CASE
        WHEN orr.rn <= 3 THEN 'active'::subscription_status_enum  -- Abonnements annuels actifs
        WHEN orr.rn <= 5 THEN 'active'::subscription_status_enum  -- Abonnements avec pauses mais actifs
        WHEN orr.rn = 6 THEN 'past_due'::subscription_status_enum  -- Paiements en retard presque tous les mois
        WHEN orr.rn = 7 THEN 'active'::subscription_status_enum  -- Paiements en retard + remboursements
        WHEN orr.rn = 8 THEN 'suspended'::subscription_status_enum  -- Suspendu
        WHEN orr.rn = 9 THEN 'pending'::subscription_status_enum  -- En attente
        ELSE 'cancelled'::subscription_status_enum
    END,
    -- Date de début : il y a 12 mois pour les abonnements annuels
    date_trunc('month', now() - INTERVAL '12 months'),
    date_trunc('month', now()),
    date_trunc('month', now()) + INTERVAL '1 month' - INTERVAL '1 day',
    date_trunc('month', now() + INTERVAL '1 month'),
    (orr.rn >= 9),
    CASE WHEN orr.rn = 10 THEN now() - INTERVAL '30 days' ELSE NULL END,
    jsonb_build_object(
        'admin_note', CONCAT('Abonnement de test #', orr.rn),
        'organization_type', orr.type_organisation,
        'plan_chosen_freely', TRUE,
        'subscription_type', CASE 
            WHEN orr.rn <= 3 THEN 'annual'
            WHEN orr.rn <= 5 THEN 'monthly_with_pauses'
            WHEN orr.rn = 6 THEN 'monthly_late_payments'
            WHEN orr.rn = 7 THEN 'monthly_with_refunds'
            ELSE 'monthly'
        END
    ),
    date_trunc('month', now() - INTERVAL '12 months'),
    now()
FROM organizer_ranked orr;

-- ------------------------------------------------------------
-- 4. Factures, paiements et historiques
-- Scénarios avec TOUS les statuts de facture :
-- - Organisateurs 1-3 : 12 mois payés (statut 'paid')
-- - Organisateurs 4-5 : 12 mois avec pauses (certains mois non payés)
-- - Organisateur 6 : 12 mois avec statut 'overdue' puis 'paid' (en retard)
-- - Organisateur 7 : 12 mois avec statut 'refunded' (remboursés)
-- - Organisateur 8 : 12 mois avec statut 'void' (annulés)
-- - Organisateur 9 : 12 mois avec statut 'issued' (en attente)
-- - Organisateur 10 : 12 mois avec statut 'draft' (brouillon)
-- ------------------------------------------------------------
WITH organizer_subscription_ranked AS (
    SELECT
        os.id AS subscription_id,
        os.id_profil_organisateur,
        os.id_plan,
        op.id_utilisateur,
        os.metadonnees->>'subscription_type' AS subscription_type,
        ROW_NUMBER() OVER (ORDER BY os.id) AS org_rn
    FROM abonnements_organisateurs os
    JOIN profils_organisateurs op ON op.id = os.id_profil_organisateur
),
subscription_context AS (
    SELECT
        osr.subscription_id,
        osr.id_profil_organisateur,
        osr.id_plan,
        osr.id_utilisateur,
        sp.prix,
        sp.taux_tva,
        osr.subscription_type,
        osr.org_rn
    FROM organizer_subscription_ranked osr
    JOIN plans_abonnements sp ON sp.id = osr.id_plan
),
invoice_source AS (
    SELECT
        sc.subscription_id,
        sc.id_utilisateur AS customer_id,
        sc.id_plan,
        sc.prix,
        sc.taux_tva,
        sc.subscription_type,
        sc.org_rn,
        gs AS period_index,
        date_trunc('month', now() - (12 - gs) * INTERVAL '1 month') AS issued_at,
        -- Déterminer si le mois doit être payé ou non (pour les pauses)
        CASE
            -- Organisateurs 1-3 : tous les mois payés (abonnement annuel)
            WHEN sc.org_rn <= 3 THEN TRUE
            -- Organisateur 4 : pause aux mois 3, 6, 9 (ne paie pas ces mois)
            WHEN sc.org_rn = 4 AND gs IN (3, 6, 9) THEN FALSE
            -- Organisateur 5 : pause aux mois 2, 5, 8 (ne paie pas ces mois)
            WHEN sc.org_rn = 5 AND gs IN (2, 5, 8) THEN FALSE
            -- Autres : tous les mois payés
            ELSE TRUE
        END AS should_be_paid
    FROM subscription_context sc
    CROSS JOIN generate_series(1, 12) AS gs
),
invoice_rows AS (
    INSERT INTO factures_abonnements (
        id_abonnement,
        id_client,
        devise,
        montant_sous_total,
        montant_tva,
        montant_total,
        mois_facturation,
        est_mois_pause,
        est_prepayee,
        statut,
        emise_le,
        echeance_le,
        payee_le,
        metadonnees,
        cree_le,
        modifie_le
    )
    SELECT
        isrc.subscription_id,
        isrc.customer_id,
        'MGA',
        ROUND(isrc.prix, 2),
        ROUND(isrc.prix * isrc.taux_tva / 100, 2),
        ROUND(isrc.prix * (1 + isrc.taux_tva / 100), 2),
        date_trunc('month', isrc.issued_at)::DATE,
        NOT isrc.should_be_paid,
        FALSE,
        CASE
            -- Organisateurs 1-3 : tous les mois payés (statut 'paid')
            WHEN isrc.org_rn <= 3 AND isrc.should_be_paid THEN 'paid'
            -- Organisateurs 4-5 : mois payés normalement, mois de pause = non émis
            WHEN isrc.org_rn BETWEEN 4 AND 5 AND isrc.should_be_paid THEN 
                CASE 
                    WHEN isrc.period_index <= 8 THEN 'paid'
                    WHEN isrc.period_index = 9 THEN 'issued'
                    ELSE 'overdue'
                END
            -- Organisateur 6 : statut 'overdue' (en retard) pour les premiers mois, puis 'paid'
            WHEN isrc.org_rn = 6 THEN
                CASE
                    WHEN isrc.period_index <= 6 THEN 'overdue'  -- En retard
                    WHEN isrc.period_index <= 10 THEN 'paid'  -- Payé après retard
                    ELSE 'overdue'  -- Retour en retard
                END
            -- Organisateur 7 : statut 'refunded' (remboursé) pour certains mois
            WHEN isrc.org_rn = 7 THEN
                CASE
                    WHEN isrc.period_index IN (2, 5, 8, 11) THEN 'refunded'  -- Mois remboursés
                    WHEN isrc.period_index <= 6 THEN 'paid'  -- Mois payés
                    ELSE 'issued'  -- En attente
                END
            -- Organisateur 8 : statut 'void' (annulé) pour certains mois
            WHEN isrc.org_rn = 8 THEN
                CASE
                    WHEN isrc.period_index IN (3, 6, 9) THEN 'void'  -- Factures annulées
                    WHEN isrc.period_index <= 5 THEN 'paid'  -- Mois payés
                    WHEN isrc.period_index <= 8 THEN 'issued'  -- En attente
                    ELSE 'overdue'  -- En retard
                END
            -- Organisateur 9 : statut 'issued' (en attente) pour tous les mois
            WHEN isrc.org_rn = 9 THEN 'issued'
            -- Organisateur 10 : statut 'draft' (brouillon) pour tous les mois
            WHEN isrc.org_rn = 10 THEN 'draft'
            ELSE 'overdue'
        END,
        isrc.issued_at,
        -- Date d'échéance : 15 jours après l'émission (respect des dates d'échéance mensuelles)
        isrc.issued_at + INTERVAL '15 days',
        -- Date de paiement : selon le statut (NULL pour draft, issued, void, overdue non payé)
        CASE
            -- Organisateurs 1-3 : paiement rapide (3-5 jours après émission, AVANT échéance)
            WHEN isrc.org_rn <= 3 AND isrc.should_be_paid THEN isrc.issued_at + INTERVAL '3 days' + (isrc.period_index % 3) * INTERVAL '1 day'
            -- Organisateurs 4-5 : paiement normal pour les mois payés (AVANT échéance)
            WHEN isrc.org_rn BETWEEN 4 AND 5 AND isrc.should_be_paid AND isrc.period_index <= 8 THEN isrc.issued_at + INTERVAL '5 days'
            -- Organisateur 6 : paiements EN RETARD (5-20 jours APRÈS l'échéance) pour les mois payés
            WHEN isrc.org_rn = 6 AND isrc.period_index BETWEEN 7 AND 10 THEN 
                (isrc.issued_at + INTERVAL '15 days') + INTERVAL '5 days' + (isrc.period_index % 15) * INTERVAL '1 day'
            -- Organisateur 7 : paiements pour les mois payés, puis remboursés
            WHEN isrc.org_rn = 7 THEN
                CASE
                    WHEN isrc.period_index IN (2, 5, 8, 11) THEN 
                        -- Paiement initial puis remboursement
                        isrc.issued_at + INTERVAL '5 days'
                    WHEN isrc.period_index <= 6 THEN 
                        -- Paiements normaux
                        isrc.issued_at + INTERVAL '5 days'
                    ELSE NULL  -- En attente
                END
            -- Organisateur 8 : paiement pour les mois payés (pas pour void)
            WHEN isrc.org_rn = 8 AND isrc.period_index <= 5 AND isrc.period_index NOT IN (3) THEN isrc.issued_at + INTERVAL '7 days'
            -- Organisateur 9 : pas de paiement (tous en 'issued')
            -- Organisateur 10 : pas de paiement (tous en 'draft')
            ELSE NULL
        END,
        jsonb_build_object(
            'period_index', isrc.period_index,
            'subscription_type', isrc.subscription_type,
            'month_name', to_char(isrc.issued_at, 'Month YYYY'),
            'note', CASE 
                WHEN NOT isrc.should_be_paid THEN 'Pause - mois non payé'
                WHEN isrc.org_rn = 6 AND isrc.period_index <= 6 THEN 'Facture en retard - non payée'
                WHEN isrc.org_rn = 6 THEN 'Paiement en retard - dépassement échéance'
                WHEN isrc.org_rn = 7 AND isrc.period_index IN (2, 5, 8, 11) THEN 'Facture remboursée'
                WHEN isrc.org_rn = 8 AND isrc.period_index IN (3, 6, 9) THEN 'Facture annulée'
                WHEN isrc.org_rn = 9 THEN 'Facture en attente de paiement'
                WHEN isrc.org_rn = 10 THEN 'Facture en brouillon'
                ELSE 'Facture générée pour scénarios admin'
            END,
            'is_pause_month', NOT isrc.should_be_paid,
            'is_late_payment', CASE 
                WHEN isrc.org_rn = 6 AND isrc.period_index BETWEEN 7 AND 10 THEN TRUE
                ELSE FALSE
            END,
            'days_late', CASE
                WHEN isrc.org_rn = 6 AND isrc.period_index BETWEEN 7 AND 10 THEN 5 + (isrc.period_index % 15)
                ELSE NULL
            END,
            'invoice_status_type', CASE
                WHEN isrc.org_rn <= 3 THEN 'paid'
                WHEN isrc.org_rn = 6 AND isrc.period_index <= 6 THEN 'overdue'
                WHEN isrc.org_rn = 6 THEN 'paid_late'
                WHEN isrc.org_rn = 7 AND isrc.period_index IN (2, 5, 8, 11) THEN 'refunded'
                WHEN isrc.org_rn = 8 AND isrc.period_index IN (3, 6, 9) THEN 'void'
                WHEN isrc.org_rn = 9 THEN 'issued'
                WHEN isrc.org_rn = 10 THEN 'draft'
                ELSE 'paid'
            END
        ),
        isrc.issued_at,
        isrc.issued_at + INTERVAL '1 hour'
    FROM invoice_source isrc
    WHERE isrc.should_be_paid = TRUE  -- Ne créer des factures que pour les mois qui doivent être payés
    RETURNING
        id,
        id_abonnement,
        id_client,
        statut,
        montant_total,
        emise_le,
        echeance_le,
        payee_le,
        metadonnees
),
invoice_items AS (
    INSERT INTO elements_factures_abonnements (
        id_facture,
        id_plan,
        description,
        quantite,
        prix_unitaire,
        montant_total,
        metadonnees
    )
    SELECT
        ir.id,
        sc.id_plan,
        CONCAT('Abonnement plan #', sc.id_plan, ' - Période ', (ir.metadonnees ->> 'period_index')),
        1,
        sc.prix,
        sc.prix,
        jsonb_build_object('period_index', ir.metadonnees ->> 'period_index')
    FROM invoice_rows ir
    JOIN subscription_context sc ON sc.subscription_id = ir.id_abonnement
    RETURNING id_facture
),
payment_rows AS (
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
        ir.id,
        CASE
            WHEN ir.statut = 'paid' THEN 'orange'
            WHEN ir.statut = 'refunded' THEN 'orange'  -- Paiement initial via Orange
            WHEN ir.statut = 'partially_paid' THEN 'bank_transfer'
            ELSE 'telma'
        END,
        CONCAT('PAY-', ir.id),
        CASE
            WHEN ir.statut = 'paid' THEN 'paid'::payment_status_enum
            WHEN ir.statut = 'refunded' THEN 'refunded'::payment_status_enum  -- Statut remboursé
            WHEN ir.statut = 'partially_paid' THEN 'processing'::payment_status_enum
            ELSE 'processing'::payment_status_enum
        END,
        CASE
            WHEN ir.statut = 'paid' THEN ir.montant_total
            WHEN ir.statut = 'refunded' THEN ir.montant_total  -- Montant initial avant remboursement
            WHEN ir.statut = 'partially_paid' THEN ROUND(ir.montant_total * 0.6, 2)
            ELSE ROUND(ir.montant_total * 0.1, 2)
        END,
        'MGA',
        -- Utiliser la date de paiement de la facture si elle existe
        COALESCE(ir.payee_le, 
            CASE
                WHEN ir.statut = 'paid' THEN ir.emise_le + INTERVAL '5 days'
                WHEN ir.statut = 'refunded' THEN ir.emise_le + INTERVAL '5 days'  -- Date de paiement initial
                WHEN ir.statut = 'partially_paid' THEN ir.emise_le + INTERVAL '25 days'
                ELSE NULL
            END
        ),
        jsonb_build_object(
            'status_source', ir.statut,
            'admin_comment', CASE
                WHEN ir.statut = 'refunded' THEN 'Paiement remboursé'
                WHEN (ir.metadonnees->>'is_late_payment')::boolean THEN 'Paiement en retard'
                ELSE 'Paiement test'
            END,
            'due_date', ir.echeance_le,
            'payment_delay_days', CASE 
                WHEN ir.payee_le IS NOT NULL AND ir.echeance_le IS NOT NULL THEN 
                    EXTRACT(DAY FROM (ir.payee_le - ir.echeance_le))
                ELSE NULL
            END,
            'days_late', (ir.metadonnees->>'days_late')::integer,
            'is_late_payment', (ir.metadonnees->>'is_late_payment')::boolean,
            'refund_date', CASE 
                WHEN ir.statut = 'refunded' THEN (ir.payee_le + INTERVAL '10 days')::text
                ELSE NULL
            END,
            'refund_amount', CASE 
                WHEN ir.statut = 'refunded' THEN ir.montant_total
                ELSE NULL
            END
        ),
        COALESCE(ir.payee_le, ir.emise_le) + INTERVAL '2 hours',
        COALESCE(ir.payee_le, ir.emise_le) + INTERVAL '2 hours'
    FROM invoice_rows ir
    WHERE ir.statut IN ('paid', 'partially_paid', 'refunded') AND ir.payee_le IS NOT NULL
    RETURNING
        id,
        id_facture,
        statut,
        metadonnees,
        cree_le
)
INSERT INTO historique_paiements_abonnements (
    id_paiement,
    statut_de,
    statut_vers,
    modifie_le,
    metadonnees
)
SELECT
    pr.id,
    NULL::payment_status_enum,
    'initiated'::payment_status_enum,
    pr.cree_le - INTERVAL '2 hours',
    jsonb_build_object('detail', 'Création du paiement')
FROM payment_rows pr
UNION ALL
SELECT
    pr.id,
    'initiated'::payment_status_enum,
    (CASE 
        WHEN pr.statut = 'refunded' THEN 'paid'::payment_status_enum  -- D'abord payé
        WHEN pr.statut = 'paid' THEN 'paid'::payment_status_enum
        ELSE 'processing'::payment_status_enum
    END),
    pr.cree_le,
    jsonb_build_object('detail', 'Mise à jour du paiement', 'context', pr.metadonnees)
FROM payment_rows pr
UNION ALL
-- Ajouter les remboursements dans l'historique pour les paiements remboursés
SELECT
    pr.id,
    'paid'::payment_status_enum,
    'refunded'::payment_status_enum,
    (pr.cree_le + INTERVAL '10 days'),
    jsonb_build_object(
        'detail', 'Remboursement effectué',
        'refund_amount', (pr.metadonnees->>'refund_amount')::numeric,
        'refund_reason', 'Demande client',
        'refund_date', (pr.cree_le + INTERVAL '10 days')::text
    )
FROM payment_rows pr
WHERE pr.statut = 'refunded';

COMMIT;


