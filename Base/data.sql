-- ============================================================
--  AIOLIA – DONNÉES DE TEST
--  Génération : 2025-11-10
--  Version : Données pour statistiques avec critères spécifiques
-- ============================================================

BEGIN;

-- Nettoyage des données existantes
TRUNCATE TABLE aiolia.utilisateurs CASCADE;

-- ============================================================
-- 1. Utilisateurs (79 organisateurs + 78 utilisateurs + 2 admins = 159)
-- ============================================================
-- Répartition des organisateurs :
-- Juillet 2025 : 45 organisateurs (IDs 1-45)
-- Août 2025 : +5 = 50 organisateurs (IDs 46-50)
-- Septembre 2025 : +13 = 63 organisateurs (IDs 51-63)
-- Octobre 2025 : +6 = 69 organisateurs (IDs 64-69)
-- Novembre 2025 : +10 = 79 organisateurs (IDs 70-79)

INSERT INTO aiolia.utilisateurs (
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
    CASE 
        WHEN gs <= 79 THEN CONCAT('organisateur', LPAD(gs::text, 3, '0'), '@yopmail.com')
        WHEN gs <= 157 THEN CONCAT('client', LPAD((gs - 79)::text, 3, '0'), '@yopmail.com')
        ELSE CONCAT('admin', LPAD((gs - 157)::text, 3, '0'), '@yopmail.com')
    END,
    CASE 
        WHEN gs <= 79 THEN CONCAT('organisateur', LPAD(gs::text, 3, '0'), '@yopmail.com')
        WHEN gs <= 157 THEN CONCAT('client', LPAD((gs - 79)::text, 3, '0'), '@yopmail.com')
        ELSE CONCAT('admin', LPAD((gs - 157)::text, 3, '0'), '@yopmail.com')
    END,
    'password',
    crypt('Org#Test123', gen_salt('bf', 12)),
    CASE 
        WHEN gs <= 79 THEN CONCAT('Organisateur', LPAD(gs::text, 3, '0'))
        WHEN gs <= 157 THEN CONCAT('Client', LPAD((gs - 79)::text, 3, '0'))
        ELSE CONCAT('Admin', LPAD((gs - 157)::text, 3, '0'))
    END,
    CASE 
        WHEN gs <= 79 THEN CONCAT('NomOrg', LPAD(gs::text, 3, '0'))
        WHEN gs <= 157 THEN CONCAT('NomUser', LPAD((gs - 79)::text, 3, '0'))
        ELSE CONCAT('NomAdmin', LPAD((gs - 157)::text, 3, '0'))
    END,
    CONCAT('+2613200', LPAD(gs::text, 5, '0')),
    'MG',
    'fr-FR',
    'Indian/Antananarivo',
    CASE 
        WHEN gs <= 79 THEN 'organizer'::user_role_enum
        WHEN gs <= 157 THEN 'user'::user_role_enum
        ELSE 'admin'::user_role_enum
    END,
    1,
    TRUE,
    TRUE,
    CURRENT_DATE - INTERVAL '30 days',
    -- Dates de création variées pour les organisateurs
    CASE
        -- Juillet 2025 : 45 organisateurs (IDs 1-45)
        WHEN gs <= 45 THEN DATE '2025-07-01' + ((gs - 1) * INTERVAL '1 day')
        -- Août 2025 : +5 organisateurs (IDs 46-50)
        WHEN gs <= 50 THEN DATE '2025-08-01' + ((gs - 46) * INTERVAL '1 day')
        -- Septembre 2025 : +13 organisateurs (IDs 51-63)
        WHEN gs <= 63 THEN DATE '2025-09-01' + ((gs - 51) * INTERVAL '1 day')
        -- Octobre 2025 : +6 organisateurs (IDs 64-69)
        WHEN gs <= 69 THEN DATE '2025-10-01' + ((gs - 64) * INTERVAL '1 day')
        -- Novembre 2025 : +10 organisateurs (IDs 70-79)
        WHEN gs <= 79 THEN DATE '2025-11-01' + ((gs - 70) * INTERVAL '1 day')
        -- Utilisateurs et admins : dates variées
        ELSE CURRENT_DATE - INTERVAL '60 days' + (gs * INTERVAL '1 day')
    END,
    CURRENT_DATE
FROM generate_series(1, 159) gs;

-- ============================================================
-- 2. Plans d'abonnements
-- ============================================================
-- Les plans existent déjà dans le schéma, on les insère seulement s'ils n'existent pas
INSERT INTO aiolia.plans_abonnements (
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
    -- BASIC - Mensuel (ID 1)
    (1, 'BASIC_MONTHLY', 'Plan Basic Mensuel', 'Offre de base pour démarrer vos événements - Facturation mensuelle', 'basic', 'monthly', 1, 'MGA', 150000, 20, '{"events_limit":3,"support":"email","features":["gestion_evenements","tableau_bord"]}'::jsonb, 1, FALSE, TRUE, CURRENT_DATE - INTERVAL '365 days', CURRENT_DATE),
    -- BASIC - Trimestriel (ID 2)
    (2, 'BASIC_QUARTERLY', 'Plan Basic Trimestriel', 'Offre de base - Facturation trimestrielle avec réduction', 'basic', 'quarterly', 1, 'MGA', 420000, 20, '{"events_limit":3,"support":"email","features":["gestion_evenements","tableau_bord"],"discount":"6.7%"}'::jsonb, 2, FALSE, TRUE, CURRENT_DATE - INTERVAL '365 days', CURRENT_DATE),
    -- PRO - Mensuel (ID 4)
    (4, 'PRO_MONTHLY', 'Plan Pro Mensuel', 'Offre professionnelle avec fonctionnalités avancées - Facturation mensuelle', 'pro', 'monthly', 1, 'MGA', 350000, 20, '{"events_limit":15,"support":"chat","features":["gestion_evenements","tableau_bord","statistiques_avancees","support_prioritaire"]}'::jsonb, 4, TRUE, TRUE, CURRENT_DATE - INTERVAL '365 days', CURRENT_DATE),
    -- PRO - Trimestriel (ID 5)
    (5, 'PRO_QUARTERLY', 'Plan Pro Trimestriel', 'Offre professionnelle - Facturation trimestrielle avec réduction', 'pro', 'quarterly', 1, 'MGA', 980000, 20, '{"events_limit":15,"support":"chat","features":["gestion_evenements","tableau_bord","statistiques_avancees","support_prioritaire"],"discount":"6.7%"}'::jsonb, 5, FALSE, TRUE, CURRENT_DATE - INTERVAL '365 days', CURRENT_DATE),
    -- ENTERPRISE - Mensuel (ID 7)
    (7, 'ENTERPRISE_MONTHLY', 'Plan Enterprise Mensuel', 'Offre entreprise avec toutes les fonctionnalités - Facturation mensuelle', 'enterprise', 'monthly', 1, 'MGA', 600000, 20, '{"events_limit":-1,"support":"phone","features":["gestion_evenements","tableau_bord","statistiques_avancees","support_prioritaire","api_access","white_label"]}'::jsonb, 7, FALSE, TRUE, CURRENT_DATE - INTERVAL '365 days', CURRENT_DATE),
    -- ENTERPRISE - Trimestriel (ID 8)
    (8, 'ENTERPRISE_QUARTERLY', 'Plan Enterprise Trimestriel', 'Offre entreprise - Facturation trimestrielle avec réduction', 'enterprise', 'quarterly', 1, 'MGA', 1680000, 20, '{"events_limit":-1,"support":"phone","features":["gestion_evenements","tableau_bord","statistiques_avancees","support_prioritaire","api_access","white_label"],"discount":"6.7%"}'::jsonb, 8, FALSE, TRUE, CURRENT_DATE - INTERVAL '365 days', CURRENT_DATE)
ON CONFLICT (id) DO NOTHING;

-- ============================================================
-- 3. Profils organisateurs (79 organisateurs)
-- ============================================================
INSERT INTO aiolia.profils_organisateurs (
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
    CONCAT('Organisateur ', LPAD(u.id::text, 3, '0')),
    CONCAT('AIOLIA ORG ', LPAD(u.id::text, 2, '0')),
    CONCAT('TIN-', 200000 + u.id),
    u.email,
    u.telephone,
    CONCAT('https://organizer', LPAD(u.id::text, 3, '0'), '.aiolia.test'),
    CONCAT('Biographie de démonstration pour organisateur ', u.id),
    CASE 
        WHEN u.id % 4 = 1 THEN 'company'::organizer_type_enum
        WHEN u.id % 4 = 2 THEN 'individual'::organizer_type_enum
        WHEN u.id % 4 = 3 THEN 'non_profit'::organizer_type_enum
        ELSE 'collective'::organizer_type_enum
    END,
    CONCAT('RC-', 152000 + u.id),
    CASE WHEN u.id % 2 = 0 THEN '50-100' ELSE '1-10' END,
    'verified',
    CASE
        -- Juillet 2025 : 45 organisateurs (IDs 1-45)
        WHEN u.id <= 45 THEN DATE '2025-07-01' + ((u.id - 1) * INTERVAL '1 day')
        -- Août 2025 : +5 organisateurs (IDs 46-50)
        WHEN u.id <= 50 THEN DATE '2025-08-01' + ((u.id - 46) * INTERVAL '1 day')
        -- Septembre 2025 : +13 organisateurs (IDs 51-63)
        WHEN u.id <= 63 THEN DATE '2025-09-01' + ((u.id - 51) * INTERVAL '1 day')
        -- Octobre 2025 : +6 organisateurs (IDs 64-69)
        WHEN u.id <= 69 THEN DATE '2025-10-01' + ((u.id - 64) * INTERVAL '1 day')
        -- Novembre 2025 : +10 organisateurs (IDs 70-79)
        ELSE DATE '2025-11-01' + ((u.id - 70) * INTERVAL '1 day')
    END,
    CASE
        -- Juillet 2025 : 45 organisateurs (IDs 1-45)
        WHEN u.id <= 45 THEN DATE '2025-07-01' + ((u.id - 1) * INTERVAL '1 day')
        -- Août 2025 : +5 organisateurs (IDs 46-50)
        WHEN u.id <= 50 THEN DATE '2025-08-01' + ((u.id - 46) * INTERVAL '1 day')
        -- Septembre 2025 : +13 organisateurs (IDs 51-63)
        WHEN u.id <= 63 THEN DATE '2025-09-01' + ((u.id - 51) * INTERVAL '1 day')
        -- Octobre 2025 : +6 organisateurs (IDs 64-69)
        WHEN u.id <= 69 THEN DATE '2025-10-01' + ((u.id - 64) * INTERVAL '1 day')
        -- Novembre 2025 : +10 organisateurs (IDs 70-79)
        ELSE DATE '2025-11-01' + ((u.id - 70) * INTERVAL '1 day')
    END,
    CURRENT_DATE
FROM aiolia.utilisateurs u
WHERE u.role = 'organizer';

-- ============================================================
-- 4. Abonnements organisateurs avec répartition par mois
-- ============================================================
-- Popularité par mois :
-- Juillet 2025 : Basic mensuel (plan 1)
-- Août 2025 : Basic trimestriel (plan 2)
-- Septembre 2025 : Enterprise trimestriel (plan 8)
-- Octobre 2025 : Enterprise mensuel (plan 7)
-- Novembre 2025 : Pro mensuel (plan 4)

WITH organizer_plans AS (
    SELECT
        op.id AS organizer_profile_id,
        op.id_utilisateur,
        op.cree_le,
        CASE
            -- Juillet 2025 : Basic mensuel (IDs 1-45)
            WHEN op.cree_le >= DATE '2025-07-01' AND op.cree_le < DATE '2025-08-01' THEN 1
            -- Août 2025 : Basic trimestriel (IDs 46-50)
            WHEN op.cree_le >= DATE '2025-08-01' AND op.cree_le < DATE '2025-09-01' THEN 2
            -- Septembre 2025 : Enterprise trimestriel (IDs 51-63)
            WHEN op.cree_le >= DATE '2025-09-01' AND op.cree_le < DATE '2025-10-01' THEN 8
            -- Octobre 2025 : Enterprise mensuel (IDs 64-69)
            WHEN op.cree_le >= DATE '2025-10-01' AND op.cree_le < DATE '2025-11-01' THEN 7
            -- Novembre 2025 : Pro mensuel (IDs 70-79)
            WHEN op.cree_le >= DATE '2025-11-01' THEN 4
            -- Par défaut : Basic mensuel
            ELSE 1
        END AS plan_id,
        -- Déterminer le statut de l'abonnement
        CASE
            -- Août : 5 organisateurs en pause (IDs 46-50)
            -- 3 reviennent en octobre (mensuel) : IDs 46-48
            -- 2 reviennent en décembre (trimestriel) : IDs 49-50
            WHEN op.id_utilisateur >= 46 AND op.id_utilisateur <= 50 THEN 'paused'::subscription_status_enum
            -- Octobre : 5 organisateurs en pause (IDs 64-68), reviennent en décembre
            WHEN op.id_utilisateur >= 64 AND op.id_utilisateur <= 68 THEN 'paused'::subscription_status_enum
            -- Tous les autres sont actifs
            ELSE 'active'::subscription_status_enum
        END AS subscription_status,
        -- Date de mise en pause
        CASE
            WHEN op.id_utilisateur >= 46 AND op.id_utilisateur <= 50 THEN DATE '2025-08-15'
            WHEN op.id_utilisateur >= 64 AND op.id_utilisateur <= 68 THEN DATE '2025-10-15'
            ELSE NULL
        END AS paused_date,
        -- Date de reprise
        CASE
            WHEN op.id_utilisateur >= 46 AND op.id_utilisateur <= 48 THEN DATE '2025-10-01'  -- 3 organisateurs, mensuel
            WHEN op.id_utilisateur >= 49 AND op.id_utilisateur <= 50 THEN DATE '2025-12-01'  -- 2 organisateurs, trimestriel
            WHEN op.id_utilisateur >= 64 AND op.id_utilisateur <= 68 THEN DATE '2025-12-01'  -- 5 organisateurs, mensuel
            ELSE NULL
        END AS resumed_date
    FROM aiolia.profils_organisateurs op
)
INSERT INTO aiolia.abonnements_organisateurs (
    id_profil_organisateur,
    id_plan,
    commence_le,
    statut,
    mis_en_pause_le,
    repris_le,
    mois_prepayes_restants,
    modifie_le,
    cree_le,
    metadonnees
)
SELECT
    op.organizer_profile_id,
    op.plan_id,
    op.cree_le,
    op.subscription_status,
    op.paused_date,
    op.resumed_date,
    -- Mois prepayés restants pour les trimestriels qui reprennent en décembre (2 mois restants)
    CASE
        WHEN op.id_utilisateur >= 49 AND op.id_utilisateur <= 50 THEN 2
        ELSE 0
    END,
    CURRENT_DATE,
    op.cree_le,
    jsonb_build_object(
        'pause_scenario', CASE
            WHEN op.id_utilisateur >= 46 AND op.id_utilisateur <= 48 THEN 'august_pause_october_resume_monthly'
            WHEN op.id_utilisateur >= 49 AND op.id_utilisateur <= 50 THEN 'august_pause_december_resume_quarterly'
            WHEN op.id_utilisateur >= 64 AND op.id_utilisateur <= 68 THEN 'october_pause_december_resume_monthly'
            ELSE NULL
        END
    )
FROM organizer_plans op;

-- ============================================================
-- 5. Génération des factures d'abonnements
-- ============================================================
-- Règle : Factures créées le 1er du mois, échéance le 10 du mois
-- Si non payée dans les 10 jours, statut = 'overdue' et organisateur en pause

WITH subscription_data AS (
    SELECT
        ao.id AS subscription_id,
        ao.id_profil_organisateur,
        ao.id_plan,
        ao.commence_le,
        ao.statut AS subscription_status,
        ao.mis_en_pause_le,
        ao.repris_le,
        ao.mois_prepayes_restants,
        op.id_utilisateur,
        sp.prix,
        sp.taux_tva,
        sp.periode_facturation,
        sp.niveau
    FROM aiolia.abonnements_organisateurs ao
    JOIN aiolia.profils_organisateurs op ON op.id = ao.id_profil_organisateur
    JOIN aiolia.plans_abonnements sp ON sp.id = ao.id_plan
),
invoice_months AS (
    SELECT
        sd.*,
        gs.month_date AS billing_month,
        EXTRACT(MONTH FROM gs.month_date) AS month_num,
        EXTRACT(YEAR FROM gs.month_date) AS year_num,
        -- Déterminer si ce mois est en pause
        CASE
            -- Août pause (IDs 46-50) : exclure août et septembre
            WHEN sd.id_utilisateur >= 46 AND sd.id_utilisateur <= 50 
                AND gs.month_date >= DATE '2025-08-01' AND gs.month_date < DATE '2025-10-01' THEN TRUE
            -- Août pause trimestriel (IDs 49-50) : exclure aussi octobre et novembre
            WHEN sd.id_utilisateur >= 49 AND sd.id_utilisateur <= 50 
                AND gs.month_date >= DATE '2025-10-01' AND gs.month_date < DATE '2025-12-01' THEN TRUE
            -- Octobre pause (IDs 64-68) : exclure octobre et novembre
            WHEN sd.id_utilisateur >= 64 AND sd.id_utilisateur <= 68 
                AND gs.month_date >= DATE '2025-10-01' AND gs.month_date < DATE '2025-12-01' THEN TRUE
            ELSE FALSE
        END AS is_paused_month
    FROM subscription_data sd
    CROSS JOIN LATERAL (
        SELECT DATE_TRUNC('month', generate_series(
            DATE_TRUNC('month', sd.commence_le),
            DATE_TRUNC('month', CURRENT_DATE),
            '1 month'::interval
        )) AS month_date
    ) gs
    WHERE 
        -- Exclure les mois de pause
        NOT (
            -- Août pause (IDs 46-50) : exclure août et septembre
            (sd.id_utilisateur >= 46 AND sd.id_utilisateur <= 50 
                AND gs.month_date >= DATE '2025-08-01' AND gs.month_date < DATE '2025-10-01')
            OR
            -- Août pause trimestriel (IDs 49-50) : exclure aussi octobre et novembre
            (sd.id_utilisateur >= 49 AND sd.id_utilisateur <= 50 
                AND gs.month_date >= DATE '2025-10-01' AND gs.month_date < DATE '2025-12-01')
            OR
            -- Octobre pause (IDs 64-68) : exclure octobre et novembre
            (sd.id_utilisateur >= 64 AND sd.id_utilisateur <= 68 
                AND gs.month_date >= DATE '2025-10-01' AND gs.month_date < DATE '2025-12-01')
        )
),
invoice_calculations AS (
    SELECT
        im.*,
        CASE
            -- Pour les trimestriels qui reprennent en décembre (IDs 49-50), utiliser le prix mensuel
            WHEN im.id_utilisateur >= 49 AND im.id_utilisateur <= 50 
                AND im.billing_month >= DATE '2025-12-01' THEN im.prix / 3
            WHEN im.periode_facturation = 'quarterly' THEN im.prix / 3
            ELSE im.prix
        END AS monthly_price,
        -- Statut de la facture
        CASE
            -- Factures passées : payées avant échéance
            WHEN im.billing_month < DATE_TRUNC('month', CURRENT_DATE) THEN 
                CASE
                    WHEN im.is_paused_month THEN 'suspendue'
                    ELSE 'paid'
                END
            -- Facture du mois courant : vérifier si échéance passée
            WHEN im.billing_month = DATE_TRUNC('month', CURRENT_DATE) THEN
                CASE
                    WHEN CURRENT_DATE > (im.billing_month + INTERVAL '10 days') THEN 'overdue'
                    ELSE 'issued'
                END
            -- Factures futures : draft
            ELSE 'draft'
        END AS invoice_status,
        -- Date de paiement : seulement si payée avant échéance
        CASE
            WHEN im.billing_month < DATE_TRUNC('month', CURRENT_DATE) 
                AND NOT im.is_paused_month THEN
                im.billing_month + INTERVAL '1 day' + (im.id_utilisateur % 9) * INTERVAL '1 day'
            -- Factures de reprise : payer immédiatement
            WHEN im.id_utilisateur >= 46 AND im.id_utilisateur <= 48 
                AND im.billing_month = DATE '2025-10-01' THEN DATE '2025-10-01' + INTERVAL '2 days'
            WHEN im.id_utilisateur >= 49 AND im.id_utilisateur <= 50 
                AND im.billing_month = DATE '2025-12-01' THEN DATE '2025-12-01' + INTERVAL '2 days'
            WHEN im.id_utilisateur >= 64 AND im.id_utilisateur <= 68 
                AND im.billing_month = DATE '2025-12-01' THEN DATE '2025-12-01' + INTERVAL '2 days'
            ELSE NULL
        END AS paid_date,
        -- Mode de paiement
        CASE
            WHEN im.billing_month < DATE_TRUNC('month', CURRENT_DATE) THEN
                CASE (im.id_utilisateur % 5)
                    WHEN 0 THEN 'orange'
                    WHEN 1 THEN 'airtel'
                    WHEN 2 THEN 'telma'
                    WHEN 3 THEN 'espace'
                    ELSE 'bank_transfer'
                END
            -- Factures de reprise
            WHEN im.id_utilisateur >= 46 AND im.id_utilisateur <= 48 
                AND im.billing_month = DATE '2025-10-01' THEN 'orange'
            WHEN im.id_utilisateur >= 49 AND im.id_utilisateur <= 50 
                AND im.billing_month = DATE '2025-12-01' THEN 'airtel'
            WHEN im.id_utilisateur >= 64 AND im.id_utilisateur <= 68 
                AND im.billing_month = DATE '2025-12-01' THEN 'telma'
            ELSE NULL
        END AS payment_method,
        im.is_paused_month
    FROM invoice_months im
)
INSERT INTO aiolia.factures_abonnements (
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
    -- Varier les montants pour créer des différences dans les top payeurs
    CASE
        WHEN ic.id_utilisateur <= 10 THEN 
            ic.monthly_price * (1.0 + (ic.id_utilisateur::float / 50) + (EXTRACT(MONTH FROM ic.billing_month)::float / 200))
        WHEN ic.id_utilisateur <= 20 THEN 
            ic.monthly_price * (1.0 + ((ic.id_utilisateur - 10)::float / 100) + (EXTRACT(MONTH FROM ic.billing_month)::float / 300))
        WHEN ic.id_utilisateur <= 30 THEN 
            ic.monthly_price * (1.0 - ((ic.id_utilisateur - 20)::float / 300))
        ELSE ic.monthly_price
    END AS montant_sous_total,
    (CASE
        WHEN ic.id_utilisateur <= 10 THEN 
            ic.monthly_price * (1.0 + (ic.id_utilisateur::float / 50) + (EXTRACT(MONTH FROM ic.billing_month)::float / 200))
        WHEN ic.id_utilisateur <= 20 THEN 
            ic.monthly_price * (1.0 + ((ic.id_utilisateur - 10)::float / 100) + (EXTRACT(MONTH FROM ic.billing_month)::float / 300))
        WHEN ic.id_utilisateur <= 30 THEN 
            ic.monthly_price * (1.0 - ((ic.id_utilisateur - 20)::float / 300))
        ELSE ic.monthly_price
    END) * (ic.taux_tva / 100) AS montant_tva,
    (CASE
        WHEN ic.id_utilisateur <= 10 THEN 
            ic.monthly_price * (1.0 + (ic.id_utilisateur::float / 50) + (EXTRACT(MONTH FROM ic.billing_month)::float / 200))
        WHEN ic.id_utilisateur <= 20 THEN 
            ic.monthly_price * (1.0 + ((ic.id_utilisateur - 10)::float / 100) + (EXTRACT(MONTH FROM ic.billing_month)::float / 300))
        WHEN ic.id_utilisateur <= 30 THEN 
            ic.monthly_price * (1.0 - ((ic.id_utilisateur - 20)::float / 300))
        ELSE ic.monthly_price
    END) * (1 + ic.taux_tva / 100) AS montant_total,
    CASE
        WHEN ic.id_utilisateur <= 10 THEN 
            ic.monthly_price * (1.0 + (ic.id_utilisateur::float / 50) + (EXTRACT(MONTH FROM ic.billing_month)::float / 200))
        WHEN ic.id_utilisateur <= 20 THEN 
            ic.monthly_price * (1.0 + ((ic.id_utilisateur - 10)::float / 100) + (EXTRACT(MONTH FROM ic.billing_month)::float / 300))
        WHEN ic.id_utilisateur <= 30 THEN 
            ic.monthly_price * (1.0 - ((ic.id_utilisateur - 20)::float / 300))
        ELSE ic.monthly_price
    END AS montant_ht,
    (CASE
        WHEN ic.id_utilisateur <= 10 THEN 
            ic.monthly_price * (1.0 + (ic.id_utilisateur::float / 50) + (EXTRACT(MONTH FROM ic.billing_month)::float / 200))
        WHEN ic.id_utilisateur <= 20 THEN 
            ic.monthly_price * (1.0 + ((ic.id_utilisateur - 10)::float / 100) + (EXTRACT(MONTH FROM ic.billing_month)::float / 300))
        WHEN ic.id_utilisateur <= 30 THEN 
            ic.monthly_price * (1.0 - ((ic.id_utilisateur - 20)::float / 300))
        ELSE ic.monthly_price
    END) * (ic.taux_tva / 100) AS montant_tva_detail,
    (CASE
        WHEN ic.id_utilisateur <= 10 THEN 
            ic.monthly_price * (1.0 + (ic.id_utilisateur::float / 50) + (EXTRACT(MONTH FROM ic.billing_month)::float / 200))
        WHEN ic.id_utilisateur <= 20 THEN 
            ic.monthly_price * (1.0 + ((ic.id_utilisateur - 10)::float / 100) + (EXTRACT(MONTH FROM ic.billing_month)::float / 300))
        WHEN ic.id_utilisateur <= 30 THEN 
            ic.monthly_price * (1.0 - ((ic.id_utilisateur - 20)::float / 300))
        ELSE ic.monthly_price
    END) * (1 + ic.taux_tva / 100) AS montant_ttc,
    ic.billing_month,
    FALSE AS est_mois_pause,
    -- Est prepayée : pour les trimestriels qui reprennent en décembre (décalage)
    CASE
        WHEN ic.id_utilisateur >= 49 AND ic.id_utilisateur <= 50 
            AND ic.billing_month = DATE '2025-12-01' THEN TRUE
        ELSE FALSE
    END,
    ic.payment_method,
    ic.invoice_status,
    ic.billing_month,
    ic.billing_month + INTERVAL '10 days',
    ic.paid_date,
    jsonb_build_object(
        'plan_level', ic.niveau,
        'billing_period', ic.periode_facturation,
        'month', TO_CHAR(ic.billing_month, 'YYYY-MM')
    ),
    ic.billing_month,
    CURRENT_DATE
FROM invoice_calculations ic;

-- ============================================================
-- 6. Mise à jour automatique des abonnements en pause
--      pour les organisateurs avec factures en retard (overdue)
-- ============================================================
UPDATE aiolia.abonnements_organisateurs ao
SET 
    statut = 'paused'::subscription_status_enum,
    mis_en_pause_le = COALESCE(ao.mis_en_pause_le, CURRENT_DATE),
    modifie_le = CURRENT_DATE,
    metadonnees = COALESCE(ao.metadonnees, '{}'::jsonb) || jsonb_build_object(
        'auto_paused_reason', 'invoice_overdue',
        'auto_paused_at', CURRENT_DATE,
        'overdue_invoice_month', TO_CHAR(fa.mois_facturation, 'YYYY-MM')
    )
FROM aiolia.factures_abonnements fa
WHERE fa.id_abonnement = ao.id
    AND fa.statut = 'overdue'
    AND ao.statut = 'active'
    -- Ne pas mettre en pause ceux qui sont déjà en pause manuellement
    AND NOT EXISTS (
        SELECT 1 FROM aiolia.abonnements_organisateurs ao2
        WHERE ao2.id = ao.id
        AND ao2.metadonnees->>'pause_scenario' IS NOT NULL
    );

-- ============================================================
-- 7. Paiements d'abonnements pour les factures payées
-- ============================================================
INSERT INTO aiolia.paiements_abonnements (
    id_facture,
    fournisseur,
    reference_fournisseur,
    montant,
    devise,
    statut,
    paye_le,
    cree_le,
    modifie_le
)
SELECT
    fa.id,
    fa.methode_paiement,
    CONCAT('REF-', fa.id, '-', EXTRACT(EPOCH FROM fa.payee_le)::bigint),
    fa.montant_total,
    fa.devise,
    'paid'::payment_status_enum,
    fa.payee_le,
    fa.payee_le,
    CURRENT_DATE
FROM aiolia.factures_abonnements fa
WHERE fa.statut = 'paid'
    AND fa.payee_le IS NOT NULL
    AND NOT EXISTS (
        SELECT 1 FROM aiolia.paiements_abonnements pa
        WHERE pa.id_facture = fa.id
    );

COMMIT;

