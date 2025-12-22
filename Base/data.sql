\c aiolia_event;
SET search_path TO aiolia;

TRUNCATE TABLE
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
    (1, TRIM('admin1@yopmail.com'), 'admin1', 'password', '$2y$13$vS5.Y6Ou8Ipz5B31DNCzFe1XWCBGKBKJ7zBdR6.dLs3lmLBRbpAXy', 'Admin', 'One', '+261343500003', 'admin', 1, '2025-01-10', '2025-01-10'),
    (2, TRIM('admin2@yopmail.com'), 'admin2', 'password', '$2y$13$vS5.Y6Ou8Ipz5B31DNCzFe1XWCBGKBKJ7zBdR6.dLs3lmLBRbpAXy', 'Admin', 'Two', '+261343500004', 'admin', 1, '2025-01-11', '2025-01-11');

INSERT INTO profils_admin (id, id_utilisateur, nom_affichage, nom_legal, cree_le, modifie_le)
VALUES
    (1, 1, 'Admin One', 'Aiolia HQ', '2025-01-10', '2025-01-10'),
    (2, 2, 'Admin Two', 'Aiolia Ops', '2025-01-11', '2025-01-11');

-- INSERTION DES 26 ORGANISATEURS
INSERT INTO utilisateurs (
    id,
    email,
    identifiant_connexion,
    methode_connexion,
    hash_mot_de_passe,admin1@yopmail.com
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
    '$2y$13$vS5.Y6Ou8Ipz5B31DNCzFe1XWCBGKBKJ7zBdR6.dLs3lmLBRbpAXy',  -- azerty! avec cost 13
    'Org',
    format('Test %02s', profile_id),
    CASE 
        WHEN profile_id % 2 = 1 THEN '+261343500003'  -- IDs impairs
        ELSE '+261343500004'                          -- IDs pairs
    END,
    'organizer',
    CASE 
        WHEN profile_id IN (23, 25, 26) THEN 0  -- Non validés
        ELSE 1
    END,
    created_on,
    created_on
FROM (VALUES
        -- Juin 2025 : 10 organisateurs
        (1,  DATE '2025-06-01'), (2,  DATE '2025-06-02'), (3,  DATE '2025-06-03'),
        (4,  DATE '2025-06-04'), (5,  DATE '2025-06-05'), (6,  DATE '2025-06-06'),
        (7,  DATE '2025-06-07'), (8,  DATE '2025-06-08'), (9,  DATE '2025-06-09'),
        (10, DATE '2025-06-10'),
        -- Juillet 2025 : +2 nouveaux
        (11, DATE '2025-07-04'), (12, DATE '2025-07-18'),
        -- Août 2025 : +4 nouveaux
        (13, DATE '2025-08-06'), (14, DATE '2025-08-14'), (15, DATE '2025-08-22'), (16, DATE '2025-08-30'),
        -- Septembre 2025 : +4 nouveaux
        (17, DATE '2025-09-03'), (18, DATE '2025-09-11'), (19, DATE '2025-09-19'), (20, DATE '2025-09-27'),
        -- Octobre 2025 : +3 nouveaux (1 non validé = ID 23)
        (21, DATE '2025-10-08'), (22, DATE '2025-10-16'), (23, DATE '2025-10-24'),
        -- Novembre 2025 : +3 nouveaux (2 non validés = IDs 25-26)
        (24, DATE '2025-11-05'), (25, DATE '2025-11-13'), (26, DATE '2025-11-21')
) AS organizer_seed(profile_id, created_on);

-- PROFILS ORGANISATEURS
-- Statut vérification : verified sauf pour IDs 23, 25, 26 (pending)
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
        WHEN profile_id IN (23, 25, 26) THEN 'pending'  -- Non validés
        ELSE 'verified'
    END,
    created_on,
    created_on
FROM (VALUES
        (1,  DATE '2025-06-01'), (2,  DATE '2025-06-02'), (3,  DATE '2025-06-03'),
        (4,  DATE '2025-06-04'), (5,  DATE '2025-06-05'), (6,  DATE '2025-06-06'),
        (7,  DATE '2025-06-07'), (8,  DATE '2025-06-08'), (9,  DATE '2025-06-09'),
        (10, DATE '2025-06-10'),
        (11, DATE '2025-07-04'), (12, DATE '2025-07-18'),
        (13, DATE '2025-08-06'), (14, DATE '2025-08-14'), (15, DATE '2025-08-22'), (16, DATE '2025-08-30'),
        (17, DATE '2025-09-03'), (18, DATE '2025-09-11'), (19, DATE '2025-09-19'), (20, DATE '2025-09-27'),
        (21, DATE '2025-10-08'), (22, DATE '2025-10-16'), (23, DATE '2025-10-24'),
        (24, DATE '2025-11-05'), (25, DATE '2025-11-13'), (26, DATE '2025-11-21')
) AS organizer_seed(profile_id, created_on);

-- UTILISATEURS NORMAUX (78 utilisateurs)
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
    '$2y$13$vS5.Y6Ou8Ipz5B31DNCzFe1XWCBGKBKJ7zBdR6.dLs3lmLBRbpAXy',  -- azerty! avec cost 13
    format('User%02s', gs),
    'Test',
    CASE WHEN gs % 2 = 0 THEN '+261343500003' ELSE '+261343500004' END,
    'user',
    1,
    DATE '2025-05-01' + (gs || ' days')::interval,
    DATE '2025-05-01' + (gs || ' days')::interval
FROM generate_series(1, 78) AS gs;

SELECT setval(pg_get_serial_sequence('utilisateurs', 'id'), 106, true);
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
/* 3. ABONNEMENTS ET FACTURES                                                 */
/* ========================================================================== */

-- JUIN 2025 : 10 organisateurs actifs
-- 5 basic mensuel (IDs 1,3,5,7,9), 2 pro mensuel (IDs 2,4), 3 entreprise mensuel (IDs 6,8,10)
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
    CASE po.id
        WHEN 1 THEN 1  -- Basic mensuel
        WHEN 2 THEN 2  -- Pro mensuel
        WHEN 3 THEN 1  -- Basic mensuel
        WHEN 4 THEN 2  -- Pro mensuel
        WHEN 5 THEN 1  -- Basic mensuel
        WHEN 6 THEN 3  -- Entreprise mensuel
        WHEN 7 THEN 1  -- Basic mensuel
        WHEN 8 THEN 3  -- Entreprise mensuel
        WHEN 9 THEN 1  -- Basic mensuel
        WHEN 10 THEN 3 -- Entreprise mensuel
    END,
    'active',
    0,
    '2025-06-01',
    '2025-06-01',
    '2025-06-30'
FROM profils_organisateurs po
WHERE po.id BETWEEN 1 AND 10
  AND po.statut_verification = 'verified';  -- Exclure les non validés

-- Factures pour JUIN 2025
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
    '2025-06-01',
    FALSE,
    FALSE,
    'paid',
    '2025-06-01',
    '2025-06-15',
    '2025-06-05'
FROM abonnements_organisateurs ao
JOIN plans_abonnements p ON ao.id_plan = p.id
JOIN profils_organisateurs po ON ao.id_profil_organisateur = po.id
JOIN utilisateurs u ON po.id_utilisateur = u.id
WHERE ao.debut_periode_courante = '2025-06-01'
  AND ao.statut = 'active'
  AND po.statut_verification = 'verified';  -- Exclure les non validés

-- JUILLET 2025 : 12 organisateurs actifs
-- Les 10 existants + 2 nouveaux (IDs 11,12)
-- 4 basic, 6 pro, 2 entreprise
-- Juin avait : 5 basic, 2 pro, 3 entreprise
-- Juillet : 4 basic, 6 pro, 2 entreprise
-- Changements : 1 basic devient pro, 1 entreprise devient pro, 2 nouveaux = pro

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
    CASE po.id
        WHEN 1 THEN 2  -- Basic -> Pro
        WHEN 2 THEN 2  -- Pro (garde)
        WHEN 3 THEN 1  -- Basic (garde)
        WHEN 4 THEN 2  -- Pro (garde)
        WHEN 5 THEN 1  -- Basic (garde)
        WHEN 6 THEN 2  -- Entreprise -> Pro
        WHEN 7 THEN 1  -- Basic (garde)
        WHEN 8 THEN 3  -- Entreprise (garde)
        WHEN 9 THEN 1  -- Basic (garde)
        WHEN 10 THEN 3 -- Entreprise (garde)
    END,
    'active',
    0,
    '2025-07-01',
    '2025-07-01',
    '2025-07-31'
FROM profils_organisateurs po
WHERE po.id BETWEEN 1 AND 10
  AND po.statut_verification = 'verified';  -- Exclure les non validés

-- Nouveaux organisateurs (11,12) avec plan pro
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
    2,  -- Pro mensuel
    'active',
    0,
    '2025-07-01',
    '2025-07-01',
    '2025-07-31'
FROM profils_organisateurs po
WHERE po.id IN (11, 12)
  AND po.statut_verification = 'verified';  -- Exclure les non validés

-- Factures pour JUILLET 2025
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
    '2025-07-01',
    FALSE,
    FALSE,
    'paid',
    '2025-07-01',
    '2025-07-15',
    '2025-07-05'
FROM abonnements_organisateurs ao
JOIN plans_abonnements p ON ao.id_plan = p.id
JOIN profils_organisateurs po ON ao.id_profil_organisateur = po.id
JOIN utilisateurs u ON po.id_utilisateur = u.id
WHERE ao.debut_periode_courante = '2025-07-01'
  AND ao.statut = 'active'
  AND po.statut_verification = 'verified';  -- Exclure les non validés

-- AOÛT 2025 : 16 organisateurs actifs
-- Les 12 existants + 4 nouveaux (IDs 13-16)
-- 4 basic, 5 pro, 7 entreprise
-- Juillet : 4 basic, 6 pro, 2 entreprise
-- Août : 4 basic, 5 pro, 7 entreprise
-- Changements : 1 pro devient entreprise, 4 nouveaux = entreprise

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
    CASE po.id
        WHEN 1 THEN 2  -- Pro (garde)
        WHEN 2 THEN 3  -- Pro -> Entreprise
        WHEN 3 THEN 1  -- Basic (garde)
        WHEN 4 THEN 2  -- Pro (garde)
        WHEN 5 THEN 1  -- Basic (garde)
        WHEN 6 THEN 2  -- Pro (garde)
        WHEN 7 THEN 1  -- Basic (garde)
        WHEN 8 THEN 3  -- Entreprise (garde)
        WHEN 9 THEN 1  -- Basic (garde)
        WHEN 10 THEN 3 -- Entreprise (garde)
        WHEN 11 THEN 2 -- Pro (garde)
        WHEN 12 THEN 2 -- Pro (garde)
    END,
    'active',
    0,
    '2025-08-01',
    '2025-08-01',
    '2025-08-31'
FROM profils_organisateurs po
WHERE po.id BETWEEN 1 AND 12
  AND po.statut_verification = 'verified';  -- Exclure les non validés

-- Nouveaux organisateurs (13-16) avec plan entreprise
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
    3,  -- Entreprise mensuel
    'active',
    0,
    '2025-08-01',
    '2025-08-01',
    '2025-08-31'
FROM profils_organisateurs po
WHERE po.id IN (13, 14, 15, 16)
  AND po.statut_verification = 'verified';  -- Exclure les non validés

-- Factures pour AOÛT 2025
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
    '2025-08-01',
    FALSE,
    FALSE,
    'paid',
    '2025-08-01',
    '2025-08-15',
    '2025-08-05'
FROM abonnements_organisateurs ao
JOIN plans_abonnements p ON ao.id_plan = p.id
JOIN profils_organisateurs po ON ao.id_profil_organisateur = po.id
JOIN utilisateurs u ON po.id_utilisateur = u.id
WHERE ao.debut_periode_courante = '2025-08-01'
  AND ao.statut = 'active'
  AND po.statut_verification = 'verified';  -- Exclure les non validés

-- 2 organisateurs en pause en août (qui reviennent en octobre)
-- IDs 1 et 2 en pause à partir du 15 août
UPDATE abonnements_organisateurs
SET statut = 'paused',
    mis_en_pause_le = '2025-08-15',
    debut_periode_courante = '2025-08-15',
    fin_periode_courante = '2025-09-30'
WHERE id_profil_organisateur IN (1, 2)
  AND debut_periode_courante = '2025-08-01';

-- SEPTEMBRE 2025 : 18 organisateurs actifs + 2 en pause
-- Les 2 en pause restent en pause
-- 4 nouveaux (IDs 17-20)
-- Mensuels : 2 basic, 3 pro, 4 entreprise (9 total)
-- Trimestriels : 3 basic, 4 pro, 2 entreprise (9 total)
-- Les 4 nouveaux doivent avoir des plans trimestriels

-- Premièrement, créons les 4 nouveaux organisateurs avec plans trimestriels
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
    CASE po.id
        WHEN 17 THEN 4  -- Basic trimestriel
        WHEN 18 THEN 5  -- Pro trimestriel
        WHEN 19 THEN 4  -- Basic trimestriel
        WHEN 20 THEN 5  -- Pro trimestriel
    END,
    'active',
    0,
    '2025-09-01',
    '2025-09-01',
    '2025-11-30'  -- Fin du trimestre
FROM profils_organisateurs po
WHERE po.id IN (17, 18, 19, 20)
  AND po.statut_verification = 'verified';  -- Exclure les non validés

-- Ensuite, créons les abonnements pour les organisateurs existants (sauf en pause)
-- Certains passent de mensuel à trimestriel
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
    CASE po.id
        -- Trimestriels (9 organisateurs)
        WHEN 3 THEN 4   -- Basic trimestriel
        WHEN 4 THEN 5   -- Pro trimestriel
        WHEN 5 THEN 4   -- Basic trimestriel
        WHEN 6 THEN 6   -- Entreprise trimestriel
        WHEN 7 THEN 4   -- Basic trimestriel
        WHEN 8 THEN 6   -- Entreprise trimestriel
        WHEN 11 THEN 5  -- Pro trimestriel
        WHEN 12 THEN 5  -- Pro trimestriel
        -- Mensuels (9 organisateurs)
        WHEN 9 THEN 1   -- Basic mensuel
        WHEN 10 THEN 3  -- Entreprise mensuel
        WHEN 13 THEN 3  -- Entreprise mensuel
        WHEN 14 THEN 3  -- Entreprise mensuel
        WHEN 15 THEN 3  -- Entreprise mensuel
        WHEN 16 THEN 3  -- Entreprise mensuel
    END,
    'active',
    0,
    '2025-09-01',
    '2025-09-01',
    CASE 
        WHEN po.id IN (3, 4, 5, 6, 7, 8, 11, 12, 17, 18, 19, 20) THEN TIMESTAMPTZ '2025-11-30'  -- Trimestriels
        ELSE TIMESTAMPTZ '2025-09-30'  -- Mensuels
    END
FROM profils_organisateurs po
WHERE (po.id BETWEEN 3 AND 16 OR po.id IN (9, 10, 13, 14, 15, 16))
  AND po.statut_verification = 'verified';  -- Exclure les non validés

-- Factures pour SEPTEMBRE 2025 (9 mensuels + 9 trimestriels = 18 factures)
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
    '2025-09-01',
    FALSE,
    FALSE,
    'paid',
    '2025-09-01',
    '2025-09-15',
    '2025-09-05'
FROM abonnements_organisateurs ao
JOIN plans_abonnements p ON ao.id_plan = p.id
JOIN profils_organisateurs po ON ao.id_profil_organisateur = po.id
JOIN utilisateurs u ON po.id_utilisateur = u.id
WHERE ao.debut_periode_courante = '2025-09-01'
  AND ao.statut = 'active'
  AND po.statut_verification = 'verified';  -- Exclure les non validés

-- Factures pour SEPTEMBRE 2025 : Factures avec montant 0 pour les organisateurs en pause (IDs 1, 2)
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
    0,  -- Montant 0 pour les organisateurs en pause
    0,
    0,
    0,
    0,
    0,
    '2025-09-01',
    TRUE,  -- est_mois_pause = TRUE
    FALSE,
    'paid',
    '2025-09-01',
    '2025-09-15',
    '2025-09-01'
FROM abonnements_organisateurs ao
JOIN profils_organisateurs po ON ao.id_profil_organisateur = po.id
JOIN utilisateurs u ON po.id_utilisateur = u.id
WHERE ao.statut = 'paused'
  AND ao.id_profil_organisateur IN (1, 2)
  AND ao.mis_en_pause_le <= '2025-09-01'
  AND (ao.repris_le IS NULL OR ao.repris_le > '2025-09-01');

-- OCTOBRE 2025 : 22 organisateurs actifs + 0 en pause
-- Les 2 en pause (IDs 1,2) reviennent
-- 3 nouveaux (IDs 21-23, dont 1 non validé = 23)
-- Mensuels : 3 basic, 3 pro, 2 entreprise (8 total)
-- Trimestriels : 1 basic, 2 pro, 4 entreprise (7 total)
-- Prépayés : 9

-- D'abord, réactivons les 2 organisateurs en pause (IDs 1,2)
INSERT INTO abonnements_organisateurs (
    id_profil_organisateur,
    id_plan,
    statut,
    mois_prepayes_restants,
    commence_le,
    debut_periode_courante,
    fin_periode_courante,
    repris_le
)
SELECT
    po.id,
    2,  -- Pro mensuel (comme avant la pause)
    'active',
    0,
    '2025-10-01',
    '2025-10-01',
    '2025-10-31',
    '2025-10-01'
FROM profils_organisateurs po
WHERE po.id IN (1, 2)
  AND po.statut_verification = 'verified';  -- Exclure les non validés

-- Ensuite, créons les abonnements mensuels pour octobre
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
    CASE po.id
        WHEN 9 THEN 1   -- Basic mensuel
        WHEN 17 THEN 1  -- Basic mensuel (change de trimestriel à mensuel)
        WHEN 21 THEN 1  -- Basic mensuel (nouveau)
        WHEN 18 THEN 2  -- Pro mensuel (change de trimestriel à mensuel)
        WHEN 22 THEN 2  -- Pro mensuel (nouveau)
        WHEN 10 THEN 3  -- Entreprise mensuel
        WHEN 13 THEN 3  -- Entreprise mensuel
        WHEN 14 THEN 3  -- Entreprise mensuel
    END,
    'active',
    0,
    '2025-10-01',
    '2025-10-01',
    '2025-10-31'
FROM profils_organisateurs po
WHERE po.id IN (9, 10, 13, 14, 17, 18, 21, 22)
  AND po.statut_verification = 'verified';  -- Exclure les non validés (23)

-- Créons les abonnements trimestriels pour octobre
-- Ce sont les organisateurs qui ont déjà un trimestriel en septembre et qui continuent
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
    CASE po.id
        WHEN 3 THEN 4   -- Basic trimestriel
        WHEN 4 THEN 5   -- Pro trimestriel
        WHEN 12 THEN 5  -- Pro trimestriel
        WHEN 6 THEN 6   -- Entreprise trimestriel
        WHEN 7 THEN 6   -- Entreprise trimestriel
        WHEN 8 THEN 6   -- Entreprise trimestriel
        WHEN 11 THEN 6  -- Entreprise trimestriel
    END,
    'active',
    0,
    '2025-10-01',
    '2025-10-01',
    '2025-12-31'
FROM profils_organisateurs po
WHERE po.id IN (3, 4, 6, 7, 8, 11, 12)
  AND po.statut_verification = 'verified';  -- Exclure les non validés

-- Mettons 4 organisateurs en pause (IDs 15, 16, 19, 20) qui reviendront en décembre
INSERT INTO abonnements_organisateurs (
    id_profil_organisateur,
    id_plan,
    statut,
    mois_prepayes_restants,
    commence_le,
    debut_periode_courante,
    fin_periode_courante,
    mis_en_pause_le
)
SELECT
    po.id,
    CASE po.id
        WHEN 15 THEN 3  -- Entreprise mensuel
        WHEN 16 THEN 3  -- Entreprise mensuel
        WHEN 19 THEN 4  -- Basic trimestriel
        WHEN 20 THEN 5  -- Pro trimestriel
    END,
    'paused',
    0,
    '2025-10-01',
    '2025-10-15',
    '2025-11-30',
    '2025-10-15'
FROM profils_organisateurs po
WHERE po.id IN (15, 16, 19, 20);

-- Créons 9 abonnements prépayés
-- EXCLURE les organisateurs non validés (23, 25, 26)
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
    7,  -- Entreprise prépayé annuel
    'active',
    12,
    '2025-10-01',
    '2025-10-01',
    '2026-09-30'
FROM profils_organisateurs po
WHERE po.id IN (5, 24, 1, 2, 9, 10, 11, 12, 13)
  AND po.statut_verification = 'verified'  -- Exclure les non validés
LIMIT 9;

-- Factures pour OCTOBRE 2025 : 6 mensuels + 7 trimestriels + 9 prépayés
-- Factures mensuelles (6)
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
    '2025-10-01',
    FALSE,
    FALSE,
    'paid',
    '2025-10-01',
    '2025-10-15',
    '2025-10-05'
FROM abonnements_organisateurs ao
JOIN plans_abonnements p ON ao.id_plan = p.id
JOIN profils_organisateurs po ON ao.id_profil_organisateur = po.id
JOIN utilisateurs u ON po.id_utilisateur = u.id
WHERE ao.debut_periode_courante = '2025-10-01'
  AND ao.statut = 'active'
  AND p.periode_facturation = 'monthly'
  AND ao.mois_prepayes_restants = 0
  AND ao.id_profil_organisateur IN (1, 2, 9, 10, 17, 18)
  AND po.statut_verification = 'verified'  -- Exclure les non validés
LIMIT 6;

-- Factures trimestrielles (7)
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
    '2025-10-01',
    FALSE,
    FALSE,
    'paid',
    '2025-10-01',
    '2025-10-15',
    '2025-10-05'
FROM abonnements_organisateurs ao
JOIN plans_abonnements p ON ao.id_plan = p.id
JOIN profils_organisateurs po ON ao.id_profil_organisateur = po.id
JOIN utilisateurs u ON po.id_utilisateur = u.id
WHERE ao.debut_periode_courante = '2025-10-01'
  AND ao.statut = 'active'
  AND p.periode_facturation = 'quarterly'
  AND ao.mois_prepayes_restants = 0
  AND ao.id_profil_organisateur IN (3, 4, 6, 7, 8, 11, 12)
  AND po.statut_verification = 'verified'  -- Exclure les non validés
LIMIT 7;

-- Factures prépayées (9)
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
    4000000,
    ROUND(4000000 * 20 / 100, 2),
    4000000 + ROUND(4000000 * 20 / 100, 2),
    4000000,
    ROUND(4000000 * 20 / 100, 2),
    4000000 + ROUND(4000000 * 20 / 100, 2),
    '2025-10-01',
    FALSE,
    TRUE,
    'paid',
    '2025-10-01',
    '2025-10-01',
    '2025-10-01'
FROM abonnements_organisateurs ao
JOIN profils_organisateurs po ON ao.id_profil_organisateur = po.id
JOIN utilisateurs u ON po.id_utilisateur = u.id
WHERE ao.debut_periode_courante = '2025-10-01'
  AND ao.statut = 'active'
  AND ao.mois_prepayes_restants > 0
  AND po.statut_verification = 'verified'  -- Exclure les non validés
LIMIT 9;

-- Factures pour OCTOBRE 2025 : Factures avec montant 0 pour les organisateurs en pause (IDs 15, 16, 19, 20)
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
    0,  -- Montant 0 pour les organisateurs en pause
    0,
    0,
    0,
    0,
    0,
    '2025-10-01',
    TRUE,  -- est_mois_pause = TRUE
    FALSE,
    'paid',
    '2025-10-01',
    '2025-10-15',
    '2025-10-01'
FROM abonnements_organisateurs ao
JOIN profils_organisateurs po ON ao.id_profil_organisateur = po.id
JOIN utilisateurs u ON po.id_utilisateur = u.id
WHERE ao.statut = 'paused'
  AND ao.id_profil_organisateur IN (15, 16, 19, 20)
  AND ao.mis_en_pause_le <= '2025-10-01'
  AND (ao.repris_le IS NULL OR ao.repris_le > '2025-10-01');

-- NOVEMBRE 2025 : 19 organisateurs actifs + 4 en pause
-- Les 4 en pause restent (IDs 15, 16, 19, 20)
-- 3 nouveaux (IDs 24-26, dont 2 non validés = 25,26)
-- Mensuels : 0 basic, 2 pro, 1 entreprise (3 total)
-- Trimestriels : 1 basic, 1 pro, 0 entreprise (2 total)
-- Prépayés : 14

-- D'abord, créons les 3 abonnements mensuels
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
    CASE po.id
        WHEN 18 THEN 2  -- Pro mensuel
        WHEN 24 THEN 2  -- Pro mensuel (nouveau)
        WHEN 13 THEN 3  -- Entreprise mensuel
    END,
    'active',
    0,
    '2025-11-01',
    '2025-11-01',
    '2025-11-30'
FROM profils_organisateurs po
WHERE po.id IN (18, 24, 13)
  AND po.statut_verification = 'verified';  -- Exclure les non validés (25, 26)

-- Créons les 2 abonnements trimestriels
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
    CASE po.id
        WHEN 3 THEN 4   -- Basic trimestriel
        WHEN 4 THEN 5   -- Pro trimestriel
    END,
    'active',
    0,
    '2025-11-01',
    '2025-11-01',
    '2026-01-31'
FROM profils_organisateurs po
WHERE po.id IN (3, 4)
  AND po.statut_verification = 'verified';  -- Exclure les non validés

-- Ajoutons 5 nouveaux prépayés (total prépayés = 9 d'octobre + 5 = 14)
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
    7,  -- Entreprise prépayé annuel
    'active',
    12,
    '2025-11-01',
    '2025-11-01',
    '2026-10-31'
FROM profils_organisateurs po
WHERE po.id IN (21, 22, 14, 17, 6)
  AND po.statut_verification = 'verified'  -- Exclure les non validés (23, 25, 26)
LIMIT 5;

-- Factures pour NOVEMBRE 2025 : 3 mensuels + 2 trimestriels
-- Factures mensuelles (3)
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
WHERE ao.debut_periode_courante = '2025-11-01'
  AND ao.statut = 'active'
  AND p.periode_facturation = 'monthly'
  AND ao.mois_prepayes_restants = 0
  AND ao.id_profil_organisateur IN (18, 24, 13)
  AND po.statut_verification = 'verified'  -- Exclure les non validés (25, 26)
LIMIT 3;

-- Factures trimestrielles (2)
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
WHERE ao.debut_periode_courante = '2025-11-01'
  AND ao.statut = 'active'
  AND p.periode_facturation = 'quarterly'
  AND ao.mois_prepayes_restants = 0
  AND ao.id_profil_organisateur IN (3, 4)
  AND po.statut_verification = 'verified'  -- Exclure les non validés
LIMIT 2;

-- Factures pour NOVEMBRE 2025 : Factures avec montant 0 pour les organisateurs en pause (IDs 15, 16, 19, 20)
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
    0,  -- Montant 0 pour les organisateurs en pause
    0,
    0,
    0,
    0,
    0,
    '2025-11-01',
    TRUE,  -- est_mois_pause = TRUE
    FALSE,
    'paid',
    '2025-11-01',
    '2025-11-15',
    '2025-11-01'
FROM abonnements_organisateurs ao
JOIN profils_organisateurs po ON ao.id_profil_organisateur = po.id
JOIN utilisateurs u ON po.id_utilisateur = u.id
WHERE ao.statut = 'paused'
  AND ao.id_profil_organisateur IN (15, 16, 19, 20)
  AND ao.mis_en_pause_le <= '2025-11-01'
  AND (ao.repris_le IS NULL OR ao.repris_le > '2025-11-01');

-- NOVEMBRE 2025 : Ajout des abonnements actifs pour atteindre 19 organisateurs actifs
-- On a déjà : 3 mensuels + 2 trimestriels + 5 nouveaux prépayés = 10
-- Il manque 9 organisateurs actifs → les 9 prépayés d'octobre continuent en novembre
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
    ao.id_profil_organisateur,
    ao.id_plan,
    'active',
    ao.mois_prepayes_restants,
    '2025-11-01',
    '2025-11-01',
    ao.fin_periode_courante
FROM abonnements_organisateurs ao
WHERE ao.debut_periode_courante = '2025-10-01'
  AND ao.statut = 'active'
  AND ao.mois_prepayes_restants > 0
LIMIT 9;

-- DÉCEMBRE 2025 : 23 organisateurs actifs + 0 en pause
-- Les 4 en pause (IDs 15, 16, 19, 20) reviennent
-- 0 mensuels, 0 trimestriels, 7 prépayés
-- Les autres consomment leur crédit prépayé

-- Réactivation des 4 organisateurs en pause
INSERT INTO abonnements_organisateurs (
    id_profil_organisateur,
    id_plan,
    statut,
    mois_prepayes_restants,
    commence_le,
    debut_periode_courante,
    fin_periode_courante,
    repris_le
)
SELECT
    po.id,
    CASE po.id
        WHEN 15 THEN 3  -- Entreprise mensuel
        WHEN 16 THEN 3  -- Entreprise mensuel
        WHEN 19 THEN 4  -- Basic trimestriel
        WHEN 20 THEN 5  -- Pro trimestriel
    END,
    'active',
    0,
    '2025-12-01',
    '2025-12-01',
    '2025-12-31',
    '2025-12-01'
FROM profils_organisateurs po
WHERE po.id IN (15, 16, 19, 20);

-- DÉCEMBRE 2025 : Ajout des abonnements actifs pour atteindre 23 organisateurs actifs
-- On a déjà : 4 qui reviennent de pause = 4
-- Il manque 19 organisateurs actifs → les 14 prépayés de novembre continuent + 5 autres organisateurs actifs
-- D'abord, les 14 prépayés de novembre continuent en décembre
-- Les 9 prépayés d'octobre (5, 24, 1, 2, 9, 10, 11, 12, 13) + les 5 nouveaux de novembre (21, 22, 14, 17, 6)
-- NOTE: Les organisateurs non validés (23, 25, 26) sont exclus
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
    ao.id_profil_organisateur,
    ao.id_plan,
    'active',
    ao.mois_prepayes_restants,
    '2025-12-01',
    '2025-12-01',
    ao.fin_periode_courante
FROM abonnements_organisateurs ao
WHERE ao.debut_periode_courante = '2025-11-01'
  AND ao.statut = 'active'
  AND ao.mois_prepayes_restants > 0;

-- Ensuite, ajoutons les organisateurs actifs de novembre (non prépayés) pour décembre
-- Ce sont les 3 mensuels (18, 24, 13) et les 2 trimestriels (3, 4) = 5 organisateurs
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
    ao.id_profil_organisateur,
    ao.id_plan,
    'active',
    ao.mois_prepayes_restants,
    '2025-12-01',
    '2025-12-01',
    CASE 
        WHEN p.periode_facturation = 'quarterly' THEN TIMESTAMPTZ '2026-01-31'
        ELSE TIMESTAMPTZ '2025-12-31'
    END
FROM abonnements_organisateurs ao
JOIN plans_abonnements p ON ao.id_plan = p.id
WHERE ao.debut_periode_courante = '2025-11-01'
  AND ao.statut = 'active'
  AND ao.mois_prepayes_restants = 0
  AND ao.id_profil_organisateur IN (3, 4, 13, 18, 24)  -- Les 5 organisateurs non prépayés de novembre
  AND ao.id_profil_organisateur NOT IN (15, 16, 19, 20);  -- Exclure ceux déjà ajoutés (pause)
  -- Note: Les organisateurs non validés (25, 26) sont déjà exclus car ils n'ont pas d'abonnements

-- Ajoutons les organisateurs trimestriels d'octobre qui continuent en décembre
-- Ce sont les trimestriels qui ont débuté en octobre (6, 7, 8, 11, 12) et qui doivent être comptés pour décembre
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
    ao.id_profil_organisateur,
    ao.id_plan,
    'active',
    ao.mois_prepayes_restants,
    '2025-12-01',
    '2025-12-01',
    ao.fin_periode_courante
FROM abonnements_organisateurs ao
WHERE ao.debut_periode_courante = '2025-10-01'
  AND ao.statut = 'active'
  AND ao.id_profil_organisateur IN (6, 7, 8, 11, 12)  -- Trimestriels d'octobre (sauf 3, 4 qui sont déjà dans les 5 ci-dessus)
  AND ao.id_profil_organisateur NOT IN (15, 16, 19, 20);  -- Exclure ceux déjà ajoutés (pause)

-- Ajoutons aussi les organisateurs mensuels d'octobre qui continuent en décembre
-- Ce sont les mensuels qui ne sont pas déjà prépayés (17, 21, 22)
-- Note : 1, 2, 9, 10 sont déjà prépayés, donc inclus dans les 14 prépayés ci-dessus
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
    ao.id_profil_organisateur,
    ao.id_plan,
    'active',
    ao.mois_prepayes_restants,
    '2025-12-01',
    '2025-12-01',
    TIMESTAMPTZ '2025-12-31'
FROM abonnements_organisateurs ao
WHERE ao.debut_periode_courante = '2025-10-01'
  AND ao.statut = 'active'
  AND ao.mois_prepayes_restants = 0
  AND ao.id_profil_organisateur IN (17, 21, 22)  -- Mensuels d'octobre non prépayés
  AND ao.id_profil_organisateur NOT IN (15, 16, 19, 20, 3, 4, 13, 18, 24, 6, 7, 8, 11, 12);  -- Exclure ceux déjà ajoutés

-- Factures pour DÉCEMBRE 2025 : 7 prépayés consomment leur crédit
-- Même si c'est de la consommation de crédit prépayé, on crée des factures pour le chiffre d'affaires
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
    '2025-12-01',
    FALSE,
    TRUE,
    'paid',
    '2025-12-01',
    '2025-12-01',
    '2025-12-01'
FROM abonnements_organisateurs ao
JOIN plans_abonnements p ON ao.id_plan = p.id
JOIN profils_organisateurs po ON ao.id_profil_organisateur = po.id
JOIN utilisateurs u ON po.id_utilisateur = u.id
WHERE ao.debut_periode_courante = '2025-12-01'
  AND ao.statut = 'active'
  AND ao.mois_prepayes_restants > 0
  AND po.statut_verification = 'verified'  -- Exclure les non validés
LIMIT 7;

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
/* 5. TRANSACTIONS DE PAIEMENT MOBILE                                         */
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
    'paid'::payment_status_enum,
    fa.emise_le + INTERVAL '2 hours',
    fa.emise_le + INTERVAL '3 hours',
    fa.emise_le + INTERVAL '26 hours'
FROM factures_abonnements fa
JOIN utilisateurs u ON fa.id_client = u.id
JOIN abonnements_organisateurs ao ON fa.id_abonnement = ao.id
JOIN profils_organisateurs po ON ao.id_profil_organisateur = po.id
WHERE po.statut_verification = 'verified';  -- Exclure les non validés

/* ========================================================================== */
/* 6. VÉRIFICATION                                                           */
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

-- Vérification par mois
SELECT '=== ORGANISATEURS ACTIFS PAR MOIS ===' as check_message;

WITH mois_list AS (
    SELECT generate_series(
        DATE '2025-06-01',
        DATE '2025-12-01',
        INTERVAL '1 month'
    )::DATE as mois_debut
),
factures_par_mois AS (
    SELECT 
        DATE_TRUNC('month', fa.mois_facturation::DATE) as mois,
        ao.id_profil_organisateur,
        COUNT(*) as nb_factures,
        BOOL_OR(fa.est_mois_pause) as en_pause
    FROM factures_abonnements fa
    JOIN abonnements_organisateurs ao ON fa.id_abonnement = ao.id
    GROUP BY DATE_TRUNC('month', fa.mois_facturation::DATE), ao.id_profil_organisateur
)
SELECT 
    TO_CHAR(ml.mois_debut, 'YYYY-MM') as mois,
    COUNT(DISTINCT fpm.id_profil_organisateur) FILTER (
        WHERE fpm.en_pause = FALSE
    ) as actifs,
    COUNT(DISTINCT fpm.id_profil_organisateur) FILTER (
        WHERE fpm.en_pause = TRUE
    ) as en_pause,
    COUNT(DISTINCT fpm.id_profil_organisateur) FILTER (
        WHERE fpm.en_pause IS NULL
    ) as sans_facture
FROM mois_list ml
LEFT JOIN factures_par_mois fpm ON fpm.mois = ml.mois_debut
GROUP BY ml.mois_debut
ORDER BY ml.mois_debut;

-- Vérification des plans par mois
SELECT '=== PLANS PAR MOIS ===' as check_message;

WITH factures_details AS (
    SELECT 
        DATE_TRUNC('month', fa.mois_facturation::DATE) as mois,
        p.code as plan_code,
        p.niveau as niveau,
        p.periode_facturation as periode,
        COUNT(DISTINCT ao.id_profil_organisateur) as nb_organisateurs,
        COUNT(DISTINCT fa.id) as nb_factures
    FROM factures_abonnements fa
    JOIN abonnements_organisateurs ao ON fa.id_abonnement = ao.id
    JOIN plans_abonnements p ON ao.id_plan = p.id
    WHERE fa.est_mois_pause = FALSE
    GROUP BY DATE_TRUNC('month', fa.mois_facturation::DATE), p.code, p.niveau, p.periode_facturation
)
SELECT 
    TO_CHAR(mois, 'YYYY-MM') as mois,
    plan_code,
    niveau,
    periode,
    nb_organisateurs,
    nb_factures
FROM factures_details
ORDER BY mois, nb_organisateurs DESC;