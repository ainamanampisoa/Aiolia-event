-- ============================================
-- AIOLIA EVENT PLATFORM - JEU DE DONNÉES TEST
-- Génération : 2025-11-10
-- ============================================

SET search_path TO aiolia, public;

-- ============================================
-- 1️⃣ UTILISATEURS ORGANISATEURS (NON VALIDÉS)
-- ============================================

WITH new_users AS (
    INSERT INTO users (
        email,
        password_hash,
        first_name,
        last_name,
        status,
        is_email_verified,
        language_code,
        accepted_terms_at
    )
    VALUES
        ('valeafifaliana@gmail.com', crypt('TestPassword#1', gen_salt('bf')), 'Valea', 'Fifaliana', 'pending', FALSE, 'fr-FR', now()),
        ('malalavalea@gmail.com',    crypt('TestPassword#2', gen_salt('bf')), 'Malala', 'Valea',     'pending', FALSE, 'fr-FR', now())
    ON CONFLICT (email) DO UPDATE
        SET
            status = EXCLUDED.status,
            updated_at = now()
    RETURNING user_id, email
),
organizer_role AS (
    SELECT role_id
    FROM user_roles
    WHERE code = 'organizer'
)
INSERT INTO user_role_assignments (user_id, role_id, assigned_at, assigned_by)
SELECT nu.user_id, orole.role_id, now(), NULL
FROM new_users nu
CROSS JOIN organizer_role orole
ON CONFLICT (user_id, role_id) DO NOTHING;

-- ============================================
-- 2️⃣ PROFILS ORGANISATEURS (STATUT PENDING)
-- ============================================

INSERT INTO organizer_profiles (
    user_id,
    display_name,
    legal_name,
    tax_number,
    support_email,
    support_phone,
    website_url,
    biography,
    verification_status
)
SELECT
    u.user_id,
    CASE u.email
        WHEN 'valeafifaliana@gmail.com' THEN 'Valea Évènements'
        WHEN 'malalavalea@gmail.com'    THEN 'Malala Productions'
    END,
    NULL,
    NULL,
    NULL,
    NULL,
    NULL,
    'Compte test non validé',
    'pending'
FROM users u
WHERE u.email IN ('valeafifaliana@gmail.com', 'malalavalea@gmail.com')
ON CONFLICT (user_id) DO UPDATE
    SET
        verification_status = EXCLUDED.verification_status,
        updated_at = now();

-- ============================================
-- 3️⃣ UTILISATEUR ADMINISTRATEUR
-- ============================================

WITH admin_user AS (
    INSERT INTO users (
        email,
        password_hash,
        first_name,
        last_name,
        status,
        is_email_verified,
        language_code,
        accepted_terms_at
    )
    VALUES (
        'admin@aiolia-event.test',
        crypt('AdminStrong#2025', gen_salt('bf')),
        'Alex',
        'Admin',
        'active',
        TRUE,
        'fr-FR',
        now()
    )
    ON CONFLICT (email) DO UPDATE
        SET
            status = 'active',
            is_email_verified = TRUE,
            updated_at = now()
    RETURNING user_id
)
INSERT INTO user_role_assignments (user_id, role_id, assigned_at, assigned_by)
SELECT au.user_id, ur.role_id, now(), NULL
FROM admin_user au
CROSS JOIN LATERAL (
    SELECT role_id FROM user_roles WHERE code = 'admin'
) ur
ON CONFLICT (user_id, role_id) DO NOTHING;

-- ============================================
-- 4️⃣ PLANS D'ABONNEMENT
-- ============================================

WITH upsert_plans AS (
    INSERT INTO subscription_plans (
        code,
        name,
        description,
        billing_period,
        period_count,
        currency,
        price,
        setup_fee,
        vat_rate,
        features,
        is_active
    )
    VALUES
        (
            'STARTER',
            'Formule Starter',
            'Abonnement mensuel pour petits organisateurs',
            'monthly',
            1,
            'MGA',
            150000,
            0,
            20.00,
            jsonb_build_object('events_limit', 3, 'support', 'email'),
            TRUE
        ),
        (
            'PREMIUM',
            'Formule Premium',
            'Abonnement annuel avec analytics avancées',
            'yearly',
            1,
            'MGA',
            1500000,
            100000,
            20.00,
            jsonb_build_object('events_limit', 'illimité', 'support', '24/7'),
            TRUE
        )
    ON CONFLICT (code) DO UPDATE
        SET
            name = EXCLUDED.name,
            description = EXCLUDED.description,
            billing_period = EXCLUDED.billing_period,
            period_count = EXCLUDED.period_count,
            price = EXCLUDED.price,
            setup_fee = EXCLUDED.setup_fee,
            vat_rate = EXCLUDED.vat_rate,
            features = EXCLUDED.features,
            is_active = EXCLUDED.is_active,
            updated_at = now()
    RETURNING plan_id, code
)
SELECT 1;

-- ============================================
-- 5️⃣ SOUSCRIPTIONS ORGANISATEURS
-- ============================================

WITH plans AS (
    SELECT code, plan_id FROM subscription_plans WHERE code IN ('STARTER', 'PREMIUM')
),
organizers AS (
    SELECT
        op.organizer_id,
        u.email
    FROM organizer_profiles op
    JOIN users u ON u.user_id = op.user_id
    WHERE u.email IN ('valeafifaliana@gmail.com', 'malalavalea@gmail.com')
),
insert_subscriptions AS (
    INSERT INTO organizer_subscriptions (
        organizer_id,
        plan_id,
        status,
        starts_at,
        current_period_start,
        current_period_end,
        renewal_at,
        trial_ends_at,
        cancel_at_period_end,
        metadata
    )
    SELECT
        org.organizer_id,
        CASE org.email
            WHEN 'valeafifaliana@gmail.com' THEN (SELECT plan_id FROM plans WHERE code = 'STARTER')
            WHEN 'malalavalea@gmail.com' THEN (SELECT plan_id FROM plans WHERE code = 'PREMIUM')
        END,
        CASE org.email
            WHEN 'valeafifaliana@gmail.com' THEN 'active'
            ELSE 'past_due'
        END,
        now() - INTERVAL '45 days',
        now() - INTERVAL '15 days',
        CASE org.email
            WHEN 'valeafifaliana@gmail.com' THEN now() + INTERVAL '15 days'
            ELSE now() - INTERVAL '5 days'
        END,
        CASE org.email
            WHEN 'valeafifaliana@gmail.com' THEN now() + INTERVAL '15 days'
            ELSE now() - INTERVAL '5 days'
        END,
        CASE org.email
            WHEN 'valeafifaliana@gmail.com' THEN NULL
            ELSE now() - INTERVAL '10 days'
        END,
        CASE org.email
            WHEN 'valeafifaliana@gmail.com' THEN FALSE
            ELSE TRUE
        END,
        jsonb_build_object('import_source', 'test_seed')
    FROM organizers org
    ON CONFLICT (organizer_id) DO UPDATE
        SET
            plan_id = EXCLUDED.plan_id,
            status = EXCLUDED.status,
            current_period_start = EXCLUDED.current_period_start,
            current_period_end = EXCLUDED.current_period_end,
            renewal_at = EXCLUDED.renewal_at,
            trial_ends_at = EXCLUDED.trial_ends_at,
            cancel_at_period_end = EXCLUDED.cancel_at_period_end,
            metadata = EXCLUDED.metadata,
            updated_at = now()
    RETURNING subscription_id, organizer_id, status
)
INSERT INTO organizer_subscription_status_history (
    subscription_id,
    status_from,
    status_to,
    reason,
    changed_by,
    metadata,
    changed_at
)
SELECT
    ins.subscription_id,
    CASE ins.status
        WHEN 'active' THEN 'pending'
        ELSE 'active'
    END,
    ins.status,
    'Initialisation jeu de données',
    NULL,
    jsonb_build_object('source', 'seed'),
    now() - INTERVAL '1 day'
FROM insert_subscriptions ins
ON CONFLICT DO NOTHING;

-- Ajout d'un changement de statut supplémentaire pour la souscription en retard
WITH past_due_sub AS (
    SELECT subscription_id
    FROM organizer_subscriptions os
    JOIN organizer_profiles op ON op.organizer_id = os.organizer_id
    JOIN users u ON u.user_id = op.user_id
    WHERE u.email = 'malalavalea@gmail.com'
)
INSERT INTO organizer_subscription_status_history (
    subscription_id,
    status_from,
    status_to,
    reason,
    changed_by,
    metadata,
    changed_at
)
SELECT
    ps.subscription_id,
    'active',
    'past_due',
    'Facture impayée',
    NULL,
    jsonb_build_object('balance_due', 525000),
    now() - INTERVAL '5 days'
FROM past_due_sub ps
ON CONFLICT DO NOTHING;

-- ============================================
-- 6️⃣ FACTURES DE RÉFÉRENCE
-- ============================================

WITH subs AS (
    SELECT
        os.subscription_id,
        u.user_id AS organizer_user_id,
        u.email
    FROM organizer_subscriptions os
    JOIN organizer_profiles op ON op.organizer_id = os.organizer_id
    JOIN users u ON u.user_id = op.user_id
),
insert_invoices AS (
    INSERT INTO invoices (
        customer_id,
        subscription_id,
        currency,
        subtotal_amount,
        tax_amount,
        total_amount,
        status,
        issued_at,
        due_at,
        paid_at,
        metadata
    )
    SELECT
        subs.organizer_user_id,
        subs.subscription_id,
        'MGA',
        CASE subs.email
            WHEN 'valeafifaliana@gmail.com' THEN 150000
            ELSE 525000
        END,
        CASE subs.email
            WHEN 'valeafifaliana@gmail.com' THEN 30000
            ELSE 105000
        END,
        CASE subs.email
            WHEN 'valeafifaliana@gmail.com' THEN 180000
            ELSE 630000
        END,
        CASE subs.email
            WHEN 'valeafifaliana@gmail.com' THEN 'paid'
            ELSE 'overdue'
        END,
        CASE subs.email
            WHEN 'valeafifaliana@gmail.com' THEN now() - INTERVAL '20 days'
            ELSE now() - INTERVAL '30 days'
        END,
        CASE subs.email
            WHEN 'valeafifaliana@gmail.com' THEN now() - INTERVAL '10 days'
            ELSE now() - INTERVAL '15 days'
        END,
        CASE subs.email
            WHEN 'valeafifaliana@gmail.com' THEN now() - INTERVAL '9 days'
            ELSE NULL
        END,
        jsonb_build_object('usage_period', to_char(now(), 'YYYY-MM'))
    FROM subs
    ON CONFLICT (invoice_number) DO NOTHING
    RETURNING invoice_id, status, subscription_id
)
INSERT INTO invoice_status_history (
    invoice_id,
    status_from,
    status_to,
    amount_snapshot,
    changed_by,
    notes,
    changed_at
)
SELECT
    ii.invoice_id,
    CASE ii.status
        WHEN 'paid' THEN 'issued'
        ELSE 'issued'
    END,
    ii.status,
    NULL,
    NULL,
    'Seed status update',
    CASE ii.status
        WHEN 'paid' THEN now() - INTERVAL '9 days'
        ELSE now() - INTERVAL '3 days'
    END
FROM insert_invoices ii
ON CONFLICT DO NOTHING;
