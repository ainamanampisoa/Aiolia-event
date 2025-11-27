
\c aiolia_event;
SET search_path TO aiolia, public;

TRUNCATE TABLE
    historique_paiements_abonnements,
    paiements_abonnements,
    elements_factures_abonnements,
    factures_abonnements,
    abonnements_organisateurs,
    plans_abonnements,
    profils_organisateurs,
    profils_admin,
    profils_utilisateurs,
    utilisateurs
    RESTART IDENTITY CASCADE;

/* ========================================================================== */
/* 1. UTILISATEURS & PROFILS                                                  */
/* ========================================================================== */
INSERT INTO utilisateurs (
    id,
    email,
    identifiant_connexion,
    methode_connexion,
    hash_mot_de_passe,
    prenom,
    nom,
    role,
    statut,
    cree_le,
    modifie_le
) VALUES
    (1, 'admin1@yopmail.com', 'admin1', 'password', '$2y$10$ylqnSxPyu8h9h/J/xLMf7OXbSvojM.ajqezk2Mq0qmN64e1KnCPAS', 'Admin', 'One', 'admin', 1, '2025-01-10', '2025-01-10'),
    (2, 'admin2@yopmail.com', 'admin2', 'password', '$2y$10$ylqnSxPyu8h9h/J/xLMf7OXbSvojM.ajqezk2Mq0qmN64e1KnCPAS', 'Admin', 'Two', 'admin', 1, '2025-01-11', '2025-01-11');

INSERT INTO profils_admin (id, id_utilisateur, nom_affichage, nom_legal, cree_le, modifie_le)
VALUES
    (1, 1, 'Admin One', 'Aiolia HQ', '2025-01-10', '2025-01-10'),
    (2, 2, 'Admin Two', 'Aiolia Ops', '2025-01-11', '2025-01-11');

INSERT INTO utilisateurs (
    id,
    email,
    identifiant_connexion,
    methode_connexion,
    hash_mot_de_passe,
    prenom,
    nom,
    role,
    statut,
    cree_le,
    modifie_le
)
SELECT
    profile_id + 2,
    format('organisateur%02s@yopmail.com', profile_id),
    format('organisateur%02s', profile_id),
    'password',
    'hash-organizer',
    'Org',
    format('Test %02s', profile_id),
    'organizer',
    1,
    created_on,
    created_on
FROM (VALUES
        (1,  DATE '2025-06-05', 'verified'),
        (2,  DATE '2025-06-05', 'verified'),
        (3,  DATE '2025-06-05', 'verified'),
        (4,  DATE '2025-06-05', 'verified'),
        (5,  DATE '2025-06-05', 'verified'),
        (6,  DATE '2025-06-05', 'verified'),
        (7,  DATE '2025-06-05', 'verified'),
        (8,  DATE '2025-06-05', 'verified'),
        (9,  DATE '2025-06-05', 'verified'),
        (10, DATE '2025-06-05', 'verified'),
        (11, DATE '2025-07-04', 'verified'),
        (12, DATE '2025-07-18', 'verified'),
        (13, DATE '2025-08-06', 'verified'),
        (14, DATE '2025-08-14', 'verified'),
        (15, DATE '2025-08-22', 'verified'),
        (16, DATE '2025-08-30', 'verified'),
        (17, DATE '2025-09-03', 'verified'),
        (18, DATE '2025-09-11', 'verified'),
        (19, DATE '2025-07-05', 'verified'),
        (20, DATE '2025-07-12', 'verified'),
        (21, DATE '2025-07-18', 'verified'),
        (22, DATE '2025-07-24', 'verified'),
        (23, DATE '2025-09-10', 'pending'),
        (24, DATE '2025-09-12', 'pending'),
        (25, DATE '2025-09-15', 'pending'),
        (26, DATE '2025-09-18', 'verified')
) AS organizer_seed(profile_id, created_on, verification_status);

INSERT INTO profils_organisateurs (
    id,
    id_utilisateur,
    nom_affichage,
    nom_legal,
    type_organisation,
    statut_verification,
    cree_le,
    modifie_le
)
SELECT
    profile_id,
    profile_id + 2,
    format('Organisateur %02s', profile_id),
    format('Organisateur %02s SARL', profile_id),
    'company',
    verification_status,
    created_on,
    created_on
FROM (VALUES
        (1,  DATE '2025-06-05', 'verified'),
        (2,  DATE '2025-06-05', 'verified'),
        (3,  DATE '2025-06-05', 'verified'),
        (4,  DATE '2025-06-05', 'verified'),
        (5,  DATE '2025-06-05', 'verified'),
        (6,  DATE '2025-06-05', 'verified'),
        (7,  DATE '2025-06-05', 'verified'),
        (8,  DATE '2025-06-05', 'verified'),
        (9,  DATE '2025-06-05', 'verified'),
        (10, DATE '2025-06-05', 'verified'),
        (11, DATE '2025-07-04', 'verified'),
        (12, DATE '2025-07-18', 'verified'),
        (13, DATE '2025-08-06', 'verified'),
        (14, DATE '2025-08-14', 'verified'),
        (15, DATE '2025-08-22', 'verified'),
        (16, DATE '2025-08-30', 'verified'),
        (17, DATE '2025-09-03', 'verified'),
        (18, DATE '2025-09-11', 'verified'),
        (19, DATE '2025-09-19', 'verified'),
        (20, DATE '2025-09-27', 'verified'),
        (21, DATE '2025-10-08', 'verified'),
        (22, DATE '2025-10-16', 'verified'),
        (23, DATE '2025-10-24', 'pending'),
        (24, DATE '2025-11-05', 'pending'),
        (25, DATE '2025-11-13', 'pending'),
        (26, DATE '2025-11-21', 'verified')
) AS organizer_seed(profile_id, created_on, verification_status);

INSERT INTO utilisateurs (
    id,
    email,
    identifiant_connexion,
    methode_connexion,
    hash_mot_de_passe,
    prenom,
    nom,
    role,
    statut,
    cree_le,
    modifie_le
)
SELECT
    28 + gs AS id,
    format('utilisateur%02s@yopmail.com', gs),
    format('user%02s', gs),
    'password',
    'hash-user',
    format('User%02s', gs),
    'Test',
    'user',
    1,
    DATE '2025-05-01' + (gs || ' days')::interval,
    DATE '2025-05-01' + (gs || ' days')::interval
FROM generate_series(1, 50) AS gs;

SELECT setval(pg_get_serial_sequence('aiolia.utilisateurs', 'id'), 100, true);
SELECT setval(pg_get_serial_sequence('aiolia.profils_organisateurs', 'id'), 26, true);
SELECT setval(pg_get_serial_sequence('aiolia.profils_admin', 'id'), 2, true);

/* ========================================================================== */
/* 2. PLANS D'ABONNEMENT                                                      */
/* ========================================================================== */
INSERT INTO plans_abonnements (
    id,
    code,
    nom,
    niveau,
    description,
    periode_facturation,
    nombre_periodes,
    devise,
    prix,
    taux_tva,
    fonctionnalites,
    ordre_affichage
) VALUES
    (1, 'BASIC_MENSUEL', 'Basic Mensuel', 'basic', 'Plan basic mensuel', 'monthly', 1, 'MGA', 150000, 20, '{"support":"email"}', 1),
    (2, 'PRO_MENSUEL', 'Pro Mensuel', 'pro', 'Plan pro mensuel', 'monthly', 1, 'MGA', 280000, 20, '{"support":"prioritaire"}', 2),
    (3, 'ENTREPRISE_MENSUEL', 'Entreprise Mensuel', 'enterprise', 'Plan entreprise mensuel', 'monthly', 1, 'MGA', 450000, 20, '{"support":"dedie"}', 3),
    (4, 'BASIC_TRIMESTRE', 'Basic Trimestriel', 'basic', 'Basic trimestriel', 'quarterly', 3, 'MGA', 420000, 20, '{"support":"email"}', 4),
    (5, 'PRO_TRIMESTRE', 'Pro Trimestriel', 'pro', 'Pro trimestriel', 'quarterly', 3, 'MGA', 720000, 20, '{"support":"prioritaire"}', 5),
    (6, 'ENTREPRISE_TRIMESTRE', 'Entreprise Trimestriel', 'enterprise', 'Entreprise trimestriel', 'quarterly', 3, 'MGA', 1260000, 20, '{"support":"dedie"}', 6),
    (7, 'ENTREPRISE_ANNUEL', 'Entreprise Prépayé', 'enterprise', 'Crédit prépayé annuel', 'yearly', 12, 'MGA', 4800000, 20, '{"support":"dedie","paquet":"prepay"}', 7);

SELECT setval(pg_get_serial_sequence('aiolia.plans_abonnements', 'id'), 7, true);

/* ========================================================================== */
/* 3. ABONNEMENTS MENSUELS (JUIN → DÉCEMBRE 2025)                             */
/* ========================================================================== */
WITH month_assignments AS (
    SELECT DATE '2025-06-01' AS period_start, jsonb_build_array(
        jsonb_build_object('organizer', 1, 'plan', 'BASIC_MENSUEL'),
        jsonb_build_object('organizer', 2, 'plan', 'BASIC_MENSUEL'),
        jsonb_build_object('organizer', 3, 'plan', 'BASIC_MENSUEL'),
        jsonb_build_object('organizer', 4, 'plan', 'BASIC_MENSUEL'),
        jsonb_build_object('organizer', 5, 'plan', 'BASIC_MENSUEL'),
        jsonb_build_object('organizer', 6, 'plan', 'PRO_MENSUEL'),
        jsonb_build_object('organizer', 7, 'plan', 'PRO_MENSUEL'),
        jsonb_build_object('organizer', 8, 'plan', 'ENTREPRISE_MENSUEL'),
        jsonb_build_object('organizer', 9, 'plan', 'ENTREPRISE_MENSUEL'),
        jsonb_build_object('organizer', 10, 'plan', 'ENTREPRISE_MENSUEL')
    ) AS assignments
    UNION ALL
    SELECT DATE '2025-07-01', jsonb_build_array(
        jsonb_build_object('organizer', 1, 'plan', 'BASIC_MENSUEL'),
        jsonb_build_object('organizer', 2, 'plan', 'BASIC_MENSUEL'),
        jsonb_build_object('organizer', 3, 'plan', 'BASIC_MENSUEL'),
        jsonb_build_object('organizer', 4, 'plan', 'BASIC_MENSUEL'),
        jsonb_build_object('organizer', 5, 'plan', 'PRO_MENSUEL'),
        jsonb_build_object('organizer', 6, 'plan', 'PRO_MENSUEL'),
        jsonb_build_object('organizer', 7, 'plan', 'PRO_MENSUEL'),
        jsonb_build_object('organizer', 8, 'plan', 'PRO_MENSUEL'),
        jsonb_build_object('organizer', 9, 'plan', 'PRO_MENSUEL'),
        jsonb_build_object('organizer', 10, 'plan', 'PRO_MENSUEL'),
        jsonb_build_object('organizer', 11, 'plan', 'ENTREPRISE_MENSUEL'),
        jsonb_build_object('organizer', 12, 'plan', 'ENTREPRISE_MENSUEL')
    )
    UNION ALL
    SELECT DATE '2025-08-01', jsonb_build_array(
        jsonb_build_object('organizer', 1, 'plan', 'BASIC_MENSUEL'),
        jsonb_build_object('organizer', 2, 'plan', 'BASIC_MENSUEL'),
        jsonb_build_object('organizer', 3, 'plan', 'BASIC_MENSUEL'),
        jsonb_build_object('organizer', 4, 'plan', 'BASIC_MENSUEL'),
        jsonb_build_object('organizer', 5, 'plan', 'PRO_MENSUEL'),
        jsonb_build_object('organizer', 6, 'plan', 'PRO_MENSUEL'),
        jsonb_build_object('organizer', 7, 'plan', 'PRO_MENSUEL'),
        jsonb_build_object('organizer', 8, 'plan', 'PRO_MENSUEL'),
        jsonb_build_object('organizer', 9, 'plan', 'PRO_MENSUEL'),
        jsonb_build_object('organizer', 10, 'plan', 'ENTREPRISE_MENSUEL'),
        jsonb_build_object('organizer', 11, 'plan', 'ENTREPRISE_MENSUEL'),
        jsonb_build_object('organizer', 12, 'plan', 'ENTREPRISE_MENSUEL'),
        jsonb_build_object('organizer', 13, 'plan', 'ENTREPRISE_MENSUEL'),
        jsonb_build_object('organizer', 14, 'plan', 'ENTREPRISE_MENSUEL'),
        jsonb_build_object('organizer', 15, 'plan', 'ENTREPRISE_MENSUEL'),
        jsonb_build_object('organizer', 16, 'plan', 'ENTREPRISE_MENSUEL')
    )
    UNION ALL
    SELECT DATE '2025-09-01', jsonb_build_array(
        jsonb_build_object('organizer', 1, 'plan', 'BASIC_MENSUEL'),
        jsonb_build_object('organizer', 2, 'plan', 'BASIC_MENSUEL'),
        jsonb_build_object('organizer', 3, 'plan', 'PRO_MENSUEL'),
        jsonb_build_object('organizer', 4, 'plan', 'PRO_MENSUEL'),
        jsonb_build_object('organizer', 5, 'plan', 'PRO_MENSUEL'),
        jsonb_build_object('organizer', 6, 'plan', 'ENTREPRISE_MENSUEL'),
        jsonb_build_object('organizer', 7, 'plan', 'ENTREPRISE_MENSUEL'),
        jsonb_build_object('organizer', 8, 'plan', 'ENTREPRISE_MENSUEL'),
        jsonb_build_object('organizer', 9, 'plan', 'ENTREPRISE_MENSUEL'),
        jsonb_build_object('organizer', 10, 'plan', 'BASIC_TRIMESTRE'),
        jsonb_build_object('organizer', 11, 'plan', 'BASIC_TRIMESTRE'),
        jsonb_build_object('organizer', 12, 'plan', 'BASIC_TRIMESTRE'),
        jsonb_build_object('organizer', 13, 'plan', 'PRO_TRIMESTRE'),
        jsonb_build_object('organizer', 14, 'plan', 'PRO_TRIMESTRE'),
        jsonb_build_object('organizer', 15, 'plan', 'PRO_TRIMESTRE'),
        jsonb_build_object('organizer', 16, 'plan', 'PRO_TRIMESTRE'),
        jsonb_build_object('organizer', 17, 'plan', 'ENTREPRISE_TRIMESTRE'),
        jsonb_build_object('organizer', 18, 'plan', 'ENTREPRISE_TRIMESTRE')
    )
    UNION ALL
    SELECT DATE '2025-10-01', jsonb_build_array(
        jsonb_build_object('organizer', 1, 'plan', 'BASIC_MENSUEL'),
        jsonb_build_object('organizer', 2, 'plan', 'BASIC_MENSUEL'),
        jsonb_build_object('organizer', 3, 'plan', 'PRO_MENSUEL'),
        jsonb_build_object('organizer', 4, 'plan', 'PRO_MENSUEL'),
        jsonb_build_object('organizer', 5, 'plan', 'ENTREPRISE_MENSUEL'),
        jsonb_build_object('organizer', 6, 'plan', 'ENTREPRISE_MENSUEL'),
        jsonb_build_object('organizer', 7, 'plan', 'BASIC_TRIMESTRE'),
        jsonb_build_object('organizer', 8, 'plan', 'PRO_TRIMESTRE'),
        jsonb_build_object('organizer', 9, 'plan', 'PRO_TRIMESTRE'),
        jsonb_build_object('organizer', 10, 'plan', 'ENTREPRISE_TRIMESTRE'),
        jsonb_build_object('organizer', 11, 'plan', 'ENTREPRISE_TRIMESTRE'),
        jsonb_build_object('organizer', 12, 'plan', 'ENTREPRISE_TRIMESTRE'),
        jsonb_build_object('organizer', 13, 'plan', 'ENTREPRISE_TRIMESTRE'),
        jsonb_build_object('organizer', 14, 'plan', 'ENTREPRISE_ANNUEL'),
        jsonb_build_object('organizer', 15, 'plan', 'ENTREPRISE_ANNUEL'),
        jsonb_build_object('organizer', 16, 'plan', 'ENTREPRISE_ANNUEL'),
        jsonb_build_object('organizer', 17, 'plan', 'ENTREPRISE_ANNUEL'),
        jsonb_build_object('organizer', 18, 'plan', 'ENTREPRISE_ANNUEL'),
        jsonb_build_object('organizer', 19, 'plan', 'ENTREPRISE_ANNUEL'),
        jsonb_build_object('organizer', 20, 'plan', 'ENTREPRISE_ANNUEL'),
        jsonb_build_object('organizer', 21, 'plan', 'ENTREPRISE_ANNUEL'),
        jsonb_build_object('organizer', 22, 'plan', 'ENTREPRISE_ANNUEL')
    )
    UNION ALL
    SELECT DATE '2025-11-01', jsonb_build_array(
        jsonb_build_object('organizer', 1, 'plan', 'PRO_MENSUEL'),
        jsonb_build_object('organizer', 2, 'plan', 'PRO_MENSUEL'),
        jsonb_build_object('organizer', 3, 'plan', 'ENTREPRISE_MENSUEL'),
        jsonb_build_object('organizer', 4, 'plan', 'BASIC_TRIMESTRE'),
        jsonb_build_object('organizer', 5, 'plan', 'PRO_TRIMESTRE'),
        jsonb_build_object('organizer', 6, 'plan', 'ENTREPRISE_ANNUEL'),
        jsonb_build_object('organizer', 7, 'plan', 'ENTREPRISE_ANNUEL'),
        jsonb_build_object('organizer', 8, 'plan', 'ENTREPRISE_ANNUEL'),
        jsonb_build_object('organizer', 9, 'plan', 'ENTREPRISE_ANNUEL'),
        jsonb_build_object('organizer', 10, 'plan', 'ENTREPRISE_ANNUEL'),
        jsonb_build_object('organizer', 11, 'plan', 'ENTREPRISE_ANNUEL'),
        jsonb_build_object('organizer', 12, 'plan', 'ENTREPRISE_ANNUEL'),
        jsonb_build_object('organizer', 13, 'plan', 'ENTREPRISE_ANNUEL'),
        jsonb_build_object('organizer', 14, 'plan', 'ENTREPRISE_ANNUEL'),
        jsonb_build_object('organizer', 15, 'plan', 'ENTREPRISE_ANNUEL'),
        jsonb_build_object('organizer', 16, 'plan', 'ENTREPRISE_ANNUEL'),
        jsonb_build_object('organizer', 17, 'plan', 'ENTREPRISE_ANNUEL'),
        jsonb_build_object('organizer', 18, 'plan', 'ENTREPRISE_ANNUEL'),
        jsonb_build_object('organizer', 19, 'plan', 'ENTREPRISE_ANNUEL')
    )
    UNION ALL
    SELECT DATE '2025-12-01', jsonb_build_array(
        jsonb_build_object('organizer', 1, 'plan', 'ENTREPRISE_ANNUEL'),
        jsonb_build_object('organizer', 2, 'plan', 'ENTREPRISE_ANNUEL'),
        jsonb_build_object('organizer', 3, 'plan', 'ENTREPRISE_ANNUEL'),
        jsonb_build_object('organizer', 4, 'plan', 'ENTREPRISE_ANNUEL'),
        jsonb_build_object('organizer', 5, 'plan', 'ENTREPRISE_ANNUEL'),
        jsonb_build_object('organizer', 6, 'plan', 'ENTREPRISE_ANNUEL'),
        jsonb_build_object('organizer', 7, 'plan', 'ENTREPRISE_ANNUEL'),
        jsonb_build_object('organizer', 8, 'plan', 'ENTREPRISE_TRIMESTRE'),
        jsonb_build_object('organizer', 9, 'plan', 'ENTREPRISE_TRIMESTRE'),
        jsonb_build_object('organizer', 10, 'plan', 'ENTREPRISE_TRIMESTRE'),
        jsonb_build_object('organizer', 11, 'plan', 'ENTREPRISE_TRIMESTRE'),
        jsonb_build_object('organizer', 12, 'plan', 'ENTREPRISE_TRIMESTRE'),
        jsonb_build_object('organizer', 13, 'plan', 'ENTREPRISE_TRIMESTRE'),
        jsonb_build_object('organizer', 14, 'plan', 'ENTREPRISE_TRIMESTRE'),
        jsonb_build_object('organizer', 15, 'plan', 'ENTREPRISE_TRIMESTRE'),
        jsonb_build_object('organizer', 16, 'plan', 'ENTREPRISE_TRIMESTRE'),
        jsonb_build_object('organizer', 17, 'plan', 'ENTREPRISE_TRIMESTRE'),
        jsonb_build_object('organizer', 18, 'plan', 'ENTREPRISE_TRIMESTRE'),
        jsonb_build_object('organizer', 19, 'plan', 'ENTREPRISE_TRIMESTRE'),
        jsonb_build_object('organizer', 20, 'plan', 'ENTREPRISE_TRIMESTRE'),
        jsonb_build_object('organizer', 21, 'plan', 'ENTREPRISE_TRIMESTRE'),
        jsonb_build_object('organizer', 22, 'plan', 'ENTREPRISE_TRIMESTRE'),
        jsonb_build_object('organizer', 23, 'plan', 'ENTREPRISE_TRIMESTRE')
    )
),
expanded AS (
    SELECT
        period_start,
        (assignment ->> 'organizer')::INTEGER AS organizer_id,
        assignment ->> 'plan' AS plan_code
    FROM month_assignments
    CROSS JOIN LATERAL jsonb_array_elements(assignments) AS assignment
),
inserted_subscriptions AS (
    INSERT INTO abonnements_organisateurs (
        id_profil_organisateur,
        id_plan,
        statut,
        mois_prepayes_restants,
        commence_le,
        debut_periode_courante,
        fin_periode_courante,
        renouvellement_le
    )
    SELECT
        organizer_id,
        plan.id,
        'active',
        CASE WHEN plan.code = 'ENTREPRISE_ANNUEL' THEN 6 ELSE 0 END,
        period_start,
        period_start,
        CASE
            WHEN plan.periode_facturation = 'monthly' THEN (period_start + INTERVAL '1 month' - INTERVAL '1 day')
            WHEN plan.periode_facturation = 'quarterly' THEN (period_start + INTERVAL '3 month' - INTERVAL '1 day')
            ELSE (period_start + INTERVAL '12 month' - INTERVAL '1 day')
        END,
        CASE
            WHEN plan.periode_facturation = 'monthly' THEN period_start + INTERVAL '1 month'
            WHEN plan.periode_facturation = 'quarterly' THEN period_start + INTERVAL '3 month'
            ELSE period_start + INTERVAL '12 month'
        END
    FROM expanded
    INNER JOIN plans_abonnements AS plan ON plan.code = expanded.plan_code
    ORDER BY period_start, organizer_id
    RETURNING id, id_profil_organisateur, id_plan, commence_le
),
paused_periods AS (
    SELECT * FROM (VALUES
        (21, 'BASIC_MENSUEL', DATE '2025-08-01'),
        (22, 'PRO_MENSUEL', DATE '2025-08-01'),
        (21, 'BASIC_MENSUEL', DATE '2025-09-01'),
        (22, 'PRO_MENSUEL', DATE '2025-09-01'),
        (23, 'BASIC_MENSUEL', DATE '2025-10-01'),
        (24, 'PRO_MENSUEL', DATE '2025-10-01'),
        (25, 'BASIC_TRIMESTRE', DATE '2025-10-01'),
        (26, 'PRO_TRIMESTRE', DATE '2025-10-01'),
        (23, 'BASIC_MENSUEL', DATE '2025-11-01'),
        (24, 'PRO_MENSUEL', DATE '2025-11-01'),
        (25, 'BASIC_TRIMESTRE', DATE '2025-11-01'),
        (26, 'PRO_TRIMESTRE', DATE '2025-11-01')
    ) AS p(organizer_id, plan_code, period_start)
),
paused_subscriptions AS (
    INSERT INTO abonnements_organisateurs (
        id_profil_organisateur,
        id_plan,
        statut,
        mois_prepayes_restants,
        commence_le,
        debut_periode_courante,
        fin_periode_courante,
        renouvellement_le
    )
    SELECT
        po.id,
        plan.id,
        'paused',
        0,
        paused_periods.period_start,
        paused_periods.period_start,
        CASE
            WHEN plan.periode_facturation = 'monthly' THEN (paused_periods.period_start + INTERVAL '1 month' - INTERVAL '1 day')
            WHEN plan.periode_facturation = 'quarterly' THEN (paused_periods.period_start + INTERVAL '3 month' - INTERVAL '1 day')
            ELSE (paused_periods.period_start + INTERVAL '12 month' - INTERVAL '1 day')
        END,
        CASE
            WHEN plan.periode_facturation = 'monthly' THEN paused_periods.period_start + INTERVAL '1 month'
            WHEN plan.periode_facturation = 'quarterly' THEN paused_periods.period_start + INTERVAL '3 month'
            ELSE paused_periods.period_start + INTERVAL '12 month'
        END
    FROM paused_periods
    INNER JOIN plans_abonnements AS plan ON plan.code = paused_periods.plan_code
    INNER JOIN profils_organisateurs AS po ON po.id = paused_periods.organizer_id
    ORDER BY paused_periods.period_start, paused_periods.organizer_id
    RETURNING id, id_profil_organisateur, id_plan, commence_le
),
factures_actives AS (
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
        payee_le
    )
    SELECT
        sub.id,
        po.id_utilisateur,
        plan.devise,
        plan.prix,
        ROUND(plan.prix * plan.taux_tva / 100, 2),
        plan.prix + ROUND(plan.prix * plan.taux_tva / 100, 2),
        plan.prix,
        ROUND(plan.prix * plan.taux_tva / 100, 2),
        plan.prix + ROUND(plan.prix * plan.taux_tva / 100, 2),
        sub.commence_le::DATE,
        FALSE,
        plan.code = 'ENTREPRISE_ANNUEL',
        CASE
            WHEN plan.code = 'ENTREPRISE_ANNUEL' THEN 'bank_transfer'
            WHEN plan.periode_facturation = 'quarterly' THEN 'telma'
            ELSE 'espace'
        END,
        CASE
            WHEN plan.code = 'ENTREPRISE_ANNUEL' THEN 'partially_paid'
            ELSE 'paid'
        END,
        sub.commence_le,
        sub.commence_le + INTERVAL '15 days',
        sub.commence_le + INTERVAL '5 days'
    FROM inserted_subscriptions AS sub
    INNER JOIN plans_abonnements AS plan ON plan.id = sub.id_plan
    INNER JOIN profils_organisateurs AS po ON po.id = sub.id_profil_organisateur
    RETURNING id
),
factures_pauses AS (
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
        payee_le
    )
    SELECT
        sub.id,
        po.id_utilisateur,
        plan.devise,
        0,
        0,
        0,
        0,
        0,
        0,
        sub.commence_le::DATE,
        TRUE,
        FALSE,
        NULL,
        'void',
        sub.commence_le,
        sub.commence_le + INTERVAL '15 days',
        NULL
    FROM paused_subscriptions AS sub
    INNER JOIN plans_abonnements AS plan ON plan.id = sub.id_plan
    INNER JOIN profils_organisateurs AS po ON po.id = sub.id_profil_organisateur
    RETURNING id
)
SELECT
    (SELECT COUNT(*) FROM factures_actives) +
    (SELECT COUNT(*) FROM factures_pauses) AS factures_generees;

SELECT setval(pg_get_serial_sequence('aiolia.abonnements_organisateurs', 'id'), (SELECT COALESCE(MAX(id), 1) FROM abonnements_organisateurs), true);
SELECT setval(pg_get_serial_sequence('aiolia.factures_abonnements', 'id'), (SELECT COALESCE(MAX(id), 1) FROM factures_abonnements), true);

