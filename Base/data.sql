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
    subscription_payment_history,
    subscription_payments,
    subscription_invoice_items,
    subscription_invoices,
    organizer_subscriptions,
    organizer_profiles,
    subscription_plans,
    wallets,
    user_event_stats,
    user_profiles,
    users
RESTART IDENTITY CASCADE;

-- ------------------------------------------------------------
-- 1. Utilisateurs (30 comptes : 10 organisateurs, 15 utilisateurs, 5 admins)
--    - 5 organisateurs actifs
--    - 5 organisateurs non validés utilisant les adresses indiquées
-- ------------------------------------------------------------
INSERT INTO users (
    id,
    email,
    login_identifier,
    login_method,
    password_hash,
    first_name,
    last_name,
    phone,
    country_code,
    language_code,
    timezone,
    role,
    status,
    is_email_verified,
    is_phone_verified,
    accepted_terms_at,
    created_at,
    updated_at
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
    (9, 'malalavalea+org4@yopmail.com', 'malalavalea+org4@yopmail.com', 'password', crypt('Org#Pending123', gen_salt('bf', 12)), 'OrgPending', 'Irina', '+261320000009', 'MG', 'fr-FR', 'Indian/Antananarivo', 'organizer', 0, FALSE, FALSE, NULL, '2024-07-20', '2025-02-01'),
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
INSERT INTO user_profiles (
    user_id,
    phone,
    country_code,
    language_code,
    timezone,
    avatar_url,
    dark_mode_enabled,
    marketing_opt_in,
    preferred_categories
)
SELECT
    u.id,
    COALESCE(u.phone, CONCAT('+2613201', LPAD(u.id::text, 4, '0'))),
    COALESCE(u.country_code, 'MG'),
    u.language_code,
    u.timezone,
    CONCAT('https://cdn.aiolia.test/avatars/', u.id, '.png'),
    (u.role = 'admin'),
    (u.role = 'user'),
    CASE
        WHEN u.role = 'organizer' THEN ARRAY['concert', 'conference']
        WHEN u.role = 'user' THEN ARRAY['concert', 'sport']
        ELSE ARRAY['gestion']
    END
FROM users u;

-- Statistiques utilisateurs initiales
INSERT INTO user_event_stats (user_id, events_attended, upcoming_events, total_spend, favorite_categories, last_event_at, updated_at)
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
FROM users u;

-- Comptes portefeuilles
INSERT INTO wallets (user_id, balance, points_balance, currency, created_at, updated_at)
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
FROM users u;

-- ------------------------------------------------------------
-- 2. Plans d'abonnement
-- ------------------------------------------------------------
INSERT INTO subscription_plans (
    id,
    code,
    name,
    description,
    billing_period,
    period_count,
    currency,
    price,
    vat_rate,
    features,
    is_active,
    created_at,
    updated_at
) VALUES
    (1, 'STARTER', 'Starter Organisateur', 'Plan de base pour petits organisateurs', 'monthly', 1, 'MGA', 240000, 20, '{"events_limit":5,"support":"email"}', TRUE, '2024-01-01', '2025-02-01'),
    (2, 'GROWTH', 'Croissance Organisateur', 'Plan intermédiaire', 'monthly', 1, 'MGA', 360000, 20, '{"events_limit":12,"support":"chat"}', TRUE, '2024-01-01', '2025-02-01'),
    (3, 'PRO', 'Pro Organisateur', 'Plan avancé pour organisateurs établis', 'monthly', 1, 'MGA', 520000, 20, '{"events_limit":"unlimited","support":"dedicated"}', TRUE, '2024-01-01', '2025-02-01');

-- ------------------------------------------------------------
-- 3. Profils organisateurs & abonnements
-- ------------------------------------------------------------
WITH organizer_base AS (
    SELECT
        u.id AS user_id,
        ROW_NUMBER() OVER (ORDER BY u.id) AS rn,
        u.status
    FROM users u
    WHERE u.role = 'organizer'
)
INSERT INTO organizer_profiles (
    user_id,
    display_name,
    legal_name,
    tax_number,
    support_email,
    support_phone,
    website_url,
    biography,
    organization_type,
    company_registration_number,
    company_size,
    verification_status,
    onboarding_completed_at,
    created_at,
    updated_at
)
SELECT
    ob.user_id,
    CONCAT('Organisateur ', LPAD(ob.rn::text, 2, '0')),
    CONCAT('AIOLIA ORG ', LPAD(ob.rn::text, 2, '0')),
    CONCAT('TIN-', 100000 + ob.rn),
    u.email,
    u.phone,
    CONCAT('https://organizer', LPAD(ob.rn::text, 2, '0'), '.aiolia.test'),
    CONCAT('Biographie de démonstration pour organisateur ', ob.rn),
    CASE WHEN ob.rn <= 4 THEN 'company'::organizer_type_enum ELSE 'individual'::organizer_type_enum END,
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
JOIN users u ON u.id = ob.user_id;

-- Abonnements des organisateurs (un abonnement par organisateur)
WITH organizer_ranked AS (
    SELECT
        op.id AS organizer_profile_id,
        op.user_id,
        ROW_NUMBER() OVER (ORDER BY op.id) AS rn
    FROM organizer_profiles op
)
INSERT INTO organizer_subscriptions (
    organizer_id,
    plan_id,
    status,
    starts_at,
    current_period_start,
    current_period_end,
    renewal_at,
    cancel_at_period_end,
    cancelled_at,
    metadata,
    created_at,
    updated_at
)
SELECT
    orr.organizer_profile_id,
    ((orr.rn - 1) % 3) + 1,
    CASE
        WHEN orr.rn <= 5 THEN 'active'::subscription_status_enum
        WHEN orr.rn = 6 THEN 'pending'::subscription_status_enum
        WHEN orr.rn = 7 THEN 'past_due'::subscription_status_enum
        WHEN orr.rn = 8 THEN 'suspended'::subscription_status_enum
        ELSE 'cancelled'::subscription_status_enum
    END,
    now() - INTERVAL '360 days',
    date_trunc('month', now() - INTERVAL '90 days'),
    date_trunc('month', now() + INTERVAL '0 days') + INTERVAL '1 month' - INTERVAL '1 day',
    date_trunc('month', now() + INTERVAL '30 days'),
    (orr.rn >= 8),
    CASE WHEN orr.rn = 10 THEN now() - INTERVAL '30 days' ELSE NULL END,
    jsonb_build_object('admin_note', CONCAT('Abonnement de test #', orr.rn)),
    now() - INTERVAL '360 days',
    now()
FROM organizer_ranked orr;

-- ------------------------------------------------------------
-- 4. Factures, paiements et historiques (10 occurrences par organisateur)
-- ------------------------------------------------------------
WITH subscription_context AS (
    SELECT
        os.id AS subscription_id,
        os.organizer_id,
        os.plan_id,
        op.user_id,
        sp.price,
        sp.vat_rate
    FROM organizer_subscriptions os
    JOIN organizer_profiles op ON op.id = os.organizer_id
    JOIN subscription_plans sp ON sp.id = os.plan_id
),
invoice_source AS (
    SELECT
        sc.subscription_id,
        sc.user_id AS customer_id,
        sc.plan_id,
        sc.price,
        sc.vat_rate,
        gs AS period_index,
        date_trunc('month', now() - (10 - gs) * INTERVAL '1 month') AS issued_at
    FROM subscription_context sc
    CROSS JOIN generate_series(1, 10) AS gs
),
invoice_rows AS (
    INSERT INTO subscription_invoices (
        subscription_id,
        customer_id,
        currency,
        subtotal_amount,
        tax_amount,
        total_amount,
        status,
        issued_at,
        due_at,
        paid_at,
        metadata,
        created_at,
        updated_at
    )
    SELECT
        isrc.subscription_id,
        isrc.customer_id,
        'MGA',
        ROUND(isrc.price, 2),
        ROUND(isrc.price * isrc.vat_rate / 100, 2),
        ROUND(isrc.price * (1 + isrc.vat_rate / 100), 2),
        CASE
            WHEN isrc.period_index <= 6 THEN 'paid'
            WHEN isrc.period_index = 7 THEN 'partially_paid'
            WHEN isrc.period_index = 8 THEN 'issued'
            ELSE 'overdue'
        END,
        isrc.issued_at,
        isrc.issued_at + INTERVAL '15 days',
        CASE
            WHEN isrc.period_index <= 6 THEN isrc.issued_at + INTERVAL '5 days'
            WHEN isrc.period_index = 7 THEN isrc.issued_at + INTERVAL '20 days'
            ELSE NULL
        END,
        jsonb_build_object(
            'period_index', isrc.period_index,
            'note', 'Facture générée pour scénarios admin'
        ),
        isrc.issued_at,
        isrc.issued_at + INTERVAL '1 hour'
    FROM invoice_source isrc
    RETURNING
        id,
        subscription_id,
        customer_id,
        status,
        total_amount,
        issued_at,
        metadata
),
invoice_items AS (
    INSERT INTO subscription_invoice_items (
        invoice_id,
        plan_id,
        description,
        quantity,
        unit_price,
        total_amount,
        metadata
    )
    SELECT
        ir.id,
        sc.plan_id,
        CONCAT('Abonnement plan #', sc.plan_id, ' - Période ', (ir.metadata ->> 'period_index')),
        1,
        sc.price,
        sc.price,
        jsonb_build_object('period_index', ir.metadata ->> 'period_index')
    FROM invoice_rows ir
    JOIN subscription_context sc ON sc.subscription_id = ir.subscription_id
    RETURNING invoice_id
),
payment_rows AS (
    INSERT INTO subscription_payments (
        invoice_id,
        provider,
        provider_reference,
        status,
        amount,
        currency,
        paid_at,
        metadata,
        created_at,
        updated_at
    )
    SELECT
        ir.id,
        CASE
            WHEN ir.status = 'paid' THEN 'orange'
            WHEN ir.status = 'partially_paid' THEN 'bank_transfer'
            ELSE 'telma'
        END,
        CONCAT('PAY-', ir.id),
        CASE
            WHEN ir.status = 'paid' THEN 'paid'::payment_status_enum
            WHEN ir.status = 'partially_paid' THEN 'processing'::payment_status_enum
            ELSE 'processing'::payment_status_enum
        END,
        CASE
            WHEN ir.status = 'paid' THEN ir.total_amount
            WHEN ir.status = 'partially_paid' THEN ROUND(ir.total_amount * 0.6, 2)
            ELSE ROUND(ir.total_amount * 0.1, 2)
        END,
        'MGA',
        CASE
            WHEN ir.status = 'paid' THEN ir.issued_at + INTERVAL '5 days'
            WHEN ir.status = 'partially_paid' THEN ir.issued_at + INTERVAL '25 days'
            ELSE NULL
        END,
        jsonb_build_object(
            'status_source', ir.status,
            'admin_comment', 'Paiement test'
        ),
        ir.issued_at + INTERVAL '2 hours',
        ir.issued_at + INTERVAL '2 hours'
    FROM invoice_rows ir
    WHERE ir.status IN ('paid', 'partially_paid')
    RETURNING
        id,
        invoice_id,
        status,
        metadata,
        created_at
)
INSERT INTO subscription_payment_history (
    payment_id,
    status_from,
    status_to,
    changed_at,
    metadata
)
SELECT
    pr.id,
    NULL::payment_status_enum,
    'initiated'::payment_status_enum,
    pr.created_at - INTERVAL '2 hours',
    jsonb_build_object('detail', 'Création du paiement')
FROM payment_rows pr
UNION ALL
SELECT
    pr.id,
    'initiated'::payment_status_enum,
    (CASE WHEN pr.status = 'paid' THEN 'paid' ELSE 'processing' END)::payment_status_enum,
    pr.created_at,
    jsonb_build_object('detail', 'Mise à jour du paiement', 'context', pr.metadata)
FROM payment_rows pr;

COMMIT;


