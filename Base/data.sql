\c aiolia_event;
SET search_path TO aiolia;

TRUNCATE TABLE
    notifications_operateurs,
    historique_statuts_transactions,
    transactions_paiement_mobile,
    historique_paiements_abonnements,
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
-- IMPORTANT: Utiliser le format +261 pour le domaine phone_e164
INSERT INTO utilisateurs (
    id,
    email,
    identifiant_connexion,
    methode_connexion,
    hash_mot_de_passe,
    prenom,
    nom,
    telephone,
    role,
    statut,
    cree_le,
    modifie_le
) VALUES
    (1, TRIM('admin1@yopmail.com'), 'admin1', 'password', '$2y$10$ylqnSxPyu8h9h/J/xLMf7OXbSvojM.ajqezk2Mq0qmN64e1KnCPAS', 'Admin', 'One', '+261343500003', 'admin', 1, '2025-01-10', '2025-01-10'),
    (2, TRIM('admin2@yopmail.com'), 'admin2', 'password', '$2y$10$ylqnSxPyu8h9h/J/xLMf7OXbSvojM.ajqezk2Mq0qmN64e1KnCPAS', 'Admin', 'Two', '+261343500004', 'admin', 1, '2025-01-11', '2025-01-11');

INSERT INTO profils_admin (id, id_utilisateur, nom_affichage, nom_legal, cree_le, modifie_le)
VALUES
    (1, 1, 'Admin One', 'Aiolia HQ', '2025-01-10', '2025-01-10'),
    (2, 2, 'Admin Two', 'Aiolia Ops', '2025-01-11', '2025-01-11');

-- INSERTION DES ORGANISATEURS AVEC NUMÉROS ALTERNÉS
INSERT INTO utilisateurs (
    id,
    email,
    identifiant_connexion,
    methode_connexion,
    hash_mot_de_passe,
    prenom,
    nom,
    telephone,
    role,
    statut,
    cree_le,
    modifie_le
)
SELECT
    profile_id + 2,
    regexp_replace(format('organisateur%02s@yopmail.com', profile_id + 10), '\s+', '', 'g'),
    format('organisateur%02s', profile_id + 10),
    'password',
    'hash-organizer',
    'Org',
    format('Test %02s', profile_id),
    CASE 
        WHEN profile_id % 2 = 1 THEN '+261343500003'  -- IDs impairs
        ELSE '+261343500004'                          -- IDs pairs
    END,
    'organizer',
    1,
    created_on,
    created_on
FROM (VALUES
        (1,  DATE '2025-06-05'),
        (2,  DATE '2025-06-05'),
        (3,  DATE '2025-06-05'),
        (4,  DATE '2025-06-05'),
        (5,  DATE '2025-06-05'),
        (6,  DATE '2025-06-05'),
        (7,  DATE '2025-06-05'),
        (8,  DATE '2025-06-05'),
        (9,  DATE '2025-06-05'),
        (10, DATE '2025-06-05'),
        (11, DATE '2025-07-04'),
        (12, DATE '2025-07-18'),
        (13, DATE '2025-08-06'),
        (14, DATE '2025-08-14'),
        (15, DATE '2025-08-22'),
        (16, DATE '2025-08-30'),
        (17, DATE '2025-09-03'),
        (18, DATE '2025-09-11'),
        (19, DATE '2025-09-19'),
        (20, DATE '2025-09-27'),
        (21, DATE '2025-10-08'),
        (22, DATE '2025-10-16'),
        (23, DATE '2025-10-24'),
        (24, DATE '2025-11-05'),
        (25, DATE '2025-11-13'),
        (26, DATE '2025-11-21')
) AS organizer_seed(profile_id, created_on);

-- PROFILS ORGANISATEURS
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
    CASE 
        WHEN profile_id <= 22 THEN 'verified'
        ELSE 'pending'
    END,
    created_on,
    created_on
FROM (VALUES
        (1,  DATE '2025-06-05'),
        (2,  DATE '2025-06-05'),
        (3,  DATE '2025-06-05'),
        (4,  DATE '2025-06-05'),
        (5,  DATE '2025-06-05'),
        (6,  DATE '2025-06-05'),
        (7,  DATE '2025-06-05'),
        (8,  DATE '2025-06-05'),
        (9,  DATE '2025-06-05'),
        (10, DATE '2025-06-05'),
        (11, DATE '2025-07-04'),
        (12, DATE '2025-07-18'),
        (13, DATE '2025-08-06'),
        (14, DATE '2025-08-14'),
        (15, DATE '2025-08-22'),
        (16, DATE '2025-08-30'),
        (17, DATE '2025-09-03'),
        (18, DATE '2025-09-11'),
        (19, DATE '2025-09-19'),
        (20, DATE '2025-09-27'),
        (21, DATE '2025-10-08'),
        (22, DATE '2025-10-16'),
        (23, DATE '2025-10-24'),
        (24, DATE '2025-11-05'),
        (25, DATE '2025-11-13'),
        (26, DATE '2025-11-21')
) AS organizer_seed(profile_id, created_on);

-- UTILISATEURS NORMAUX
INSERT INTO utilisateurs (
    id,
    email,
    identifiant_connexion,
    methode_connexion,
    hash_mot_de_passe,
    prenom,
    nom,
    telephone,
    role,
    statut,
    cree_le,
    modifie_le
)
SELECT
    28 + gs AS id,
    regexp_replace(format('utilisateur%02s@yopmail.com', gs), '\s+', '', 'g'),
    format('user%02s', gs),
    'password',
    'hash-user',
    format('User%02s', gs),
    'Test',
    CASE WHEN gs % 2 = 0 THEN '+261343500003' ELSE '+261343500004' END,
    'user',
    1,
    DATE '2025-05-01' + (gs || ' days')::interval,
    DATE '2025-05-01' + (gs || ' days')::interval
FROM generate_series(10, 50) AS gs;

SELECT setval(pg_get_serial_sequence('utilisateurs', 'id'), 100, true);
SELECT setval(pg_get_serial_sequence('profils_organisateurs', 'id'), 26, true);
SELECT setval(pg_get_serial_sequence('profils_admin', 'id'), 2, true);

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

SELECT setval(pg_get_serial_sequence('plans_abonnements', 'id'), 7, true);

/* ========================================================================== */
/* 3. SIMPLES ABONNEMENTS DE TEST (SANS CTE COMPLEXE)                         */
/* ========================================================================== */
-- INSÉRER QUELQUES ABONNEMENTS DIRECTEMENT
INSERT INTO abonnements_organisateurs (
    id_profil_organisateur,
    id_plan,
    statut,
    mois_prepayes_restants,
    commence_le,
    debut_periode_courante,
    fin_periode_courante
)
SELECT
    po.id,
    p.id,
    'active',
    0,
    '2025-11-01',
    '2025-11-01',
    '2025-11-30'
FROM profils_organisateurs po
CROSS JOIN plans_abonnements p
WHERE po.id <= 5
LIMIT 10;

-- FACTURES POUR CES ABONNEMENTS
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
    statut,
    emise_le,
    echeance_le,
    payee_le
)
SELECT
    ao.id,
    u.id,
    'MGA',
    p.prix,
    ROUND(p.prix * p.taux_tva / 100, 2),
    p.prix + ROUND(p.prix * p.taux_tva / 100, 2),
    p.prix,
    ROUND(p.prix * p.taux_tva / 100, 2),
    p.prix + ROUND(p.prix * p.taux_tva / 100, 2),
    '2025-11-01',
    FALSE,
    FALSE,
    'paid',
    '2025-11-01',
    '2025-11-15',
    '2025-11-05'
FROM abonnements_organisateurs ao
JOIN plans_abonnements p ON ao.id_plan = p.id
JOIN profils_organisateurs po ON ao.id_profil_organisateur = po.id
JOIN utilisateurs u ON po.id_utilisateur = u.id
LIMIT 10;

/* ========================================================================== */
/* 4. MODES DE PAIEMENT                                                       */
/* ========================================================================== */
INSERT INTO modes_paiement (code, libelle, est_actif, ordre_affichage)
VALUES 
    ('mvola', 'MVola', TRUE, 1),
    ('orange', 'Orange Money', TRUE, 2),
    ('airtel', 'Airtel Money', TRUE, 3),
    ('espace', 'Espace', TRUE, 4),
    ('carte_bancaire', 'Carte bancaire', TRUE, 5)
ON CONFLICT (code) DO NOTHING;

/* ========================================================================== */
/* 5. TRANSACTIONS DE PAIEMENT MOBILE SIMPLES                                */
/* ========================================================================== */
INSERT INTO transactions_paiement_mobile (
    reference_transaction,
    id_facture,
    id_utilisateur,
    id_profil_organisateur,
    operateur_mobile,
    type_paiement,
    numero_telephone,
    numero_transaction_operateur,
    montant,
    statut_paiement,
    initie_le,
    confirme_le,
    expire_le
)
SELECT
    'SUB-' || TO_CHAR(fa.emise_le, 'YYYYMMDD') || '-' || LPAD(fa.id::TEXT, 6, '0'),
    fa.id,
    fa.id_client,
    ao.id_profil_organisateur,
    CASE 
        WHEN u.telephone = '+261343500003' THEN 'mvola'
        ELSE 'orange'
    END,
    CASE 
        WHEN fa.id % 3 = 0 THEN 'abonnement'
        WHEN fa.id % 3 = 1 THEN 'renouvellement'
        ELSE 'mise_a_niveau'
    END,
    u.telephone,
    'OP-' || TO_CHAR(fa.emise_le, 'YYYYMMDD') || '-' || LPAD((fa.id * 1000)::TEXT, 6, '0'),
    fa.montant_total,
    'paid'::payment_status_enum,  -- CAST vers le bon type
    fa.emise_le + INTERVAL '2 hours',
    fa.emise_le + INTERVAL '3 hours',
    fa.emise_le + INTERVAL '26 hours'
FROM factures_abonnements fa
JOIN utilisateurs u ON fa.id_client = u.id
JOIN abonnements_organisateurs ao ON fa.id_abonnement = ao.id
LIMIT 10;

/* ========================================================================== */
/* 6. HISTORIQUE DES TRANSACTIONS (CORRIGÉ POUR LES TYPES)                   */
/* ========================================================================== */
INSERT INTO historique_statuts_transactions (
    id_transaction,
    statut_de,
    statut_vers,
    raison,
    cree_le
)
SELECT
    tp.id,
    NULL::payment_status_enum,  -- CAST pour le type NULL
    'initiated'::payment_status_enum,
    'Transaction initiée',
    tp.initie_le - INTERVAL '30 minutes'
FROM transactions_paiement_mobile tp

UNION ALL

SELECT
    tp.id,
    'initiated'::payment_status_enum,
    'processing'::payment_status_enum,
    'En attente de confirmation opérateur',
    tp.initie_le
FROM transactions_paiement_mobile tp

UNION ALL

SELECT
    tp.id,
    'processing'::payment_status_enum,
    tp.statut_paiement,  -- Déjà du bon type payment_status_enum
    'Paiement confirmé par l''opérateur',
    tp.confirme_le
FROM transactions_paiement_mobile tp;

/* ========================================================================== */
/* 7. VÉRIFICATION                                                           */
/* ========================================================================== */
SELECT '=== DONNÉES CRÉÉES AVEC SUCCÈS ===' as message;

SELECT 'Utilisateurs totaux:' as type, COUNT(*) as count FROM utilisateurs
UNION ALL
SELECT 'Organisateurs:', COUNT(*) FROM profils_organisateurs
UNION ALL
SELECT 'Abonnements:', COUNT(*) FROM abonnements_organisateurs
UNION ALL
SELECT 'Factures:', COUNT(*) FROM factures_abonnements
UNION ALL
SELECT 'Transactions mobile:', COUNT(*) FROM transactions_paiement_mobile;

-- Vérifier que l'utilisateur organisateur11 existe
SELECT '=== VÉRIFICATION organisateur11 ===' as check_message;
SELECT id, email, telephone, role FROM utilisateurs WHERE email = 'organisateur11@yopmail.com';

-- Vérifier quelques transactions
SELECT '=== 5 DERNIÈRES TRANSACTIONS ===' as check_message;
SELECT 
    reference_transaction,
    operateur_mobile,
    numero_telephone,
    montant,
    statut_paiement,
    TO_CHAR(initie_le, 'YYYY-MM-DD HH24:MI') as date_initiation
FROM transactions_paiement_mobile
ORDER BY initie_le DESC
LIMIT 5;