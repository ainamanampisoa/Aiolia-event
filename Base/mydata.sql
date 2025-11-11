BEGIN;

SET search_path TO aiolia, public;

-- ===================================================================
-- Jeu de données minimal pour accompagner le développement.
-- Prérequis : créer au moins un utilisateur via le formulaire
--             avec l’email "admin@aiolia.com" (adapter si besoin)
--             avant d’exécuter ce script.
-- Chaque bloc est idempotent (ON CONFLICT / WHERE NOT EXISTS).
-- ===================================================================

-- -------------------------------------------------------------------
-- Catalogues de base
-- -------------------------------------------------------------------
INSERT INTO event_categories (slug, label, description, display_order)
VALUES ('concert', 'Concert', 'Concerts et showcases grand public', 1)
ON CONFLICT (slug) DO NOTHING;

INSERT INTO event_categories (slug, label, description, display_order)
VALUES ('business', 'Business', 'Rencontres professionnelles & networking', 2)
ON CONFLICT (slug) DO NOTHING;

INSERT INTO event_tags (slug, label)
VALUES ('live', 'Live music'), ('networking', 'Networking')
ON CONFLICT (slug) DO NOTHING;

-- -------------------------------------------------------------------
-- Plans d’abonnement organisateur
-- -------------------------------------------------------------------
INSERT INTO subscription_plans (
    code, name, description, billing_period,
    period_count, currency, price, vat_rate,
    features, is_active
) VALUES (
    'STARTER',
    'Starter',
    'Plan de démarrage pour les organisateurs.',
    'monthly',
    1,
    'MGA',
    50000,
    20,
    '{"tickets":100}'::jsonb,
    TRUE
) ON CONFLICT (code) DO NOTHING;

-- -------------------------------------------------------------------
-- Profil organisateur basé sur l’utilisateur créé via le formulaire
-- -------------------------------------------------------------------
WITH base_user AS (
    SELECT id FROM users WHERE email = 'admin@aiolia.com'
)
INSERT INTO organizer_profiles (
    user_id, display_name, legal_name, tax_number,
    support_email, support_phone, website_url, biography,
    organization_type, verification_status, onboarding_completed_at
)
SELECT
    id,
    'Aiolia Events',
    'Aiolia Events SARL',
    'TN-001',
    'support@aiolia.mg',
    '+261320000999',
    'https://aiolia.mg',
    'Organisateur principal utilisé pour les scénarios de démonstration.',
    'company',
    'verified',
    now()
FROM base_user
ON CONFLICT (user_id) DO NOTHING;

-- -------------------------------------------------------------------
-- Abonnement organisateur (plan STARTER)
-- -------------------------------------------------------------------
WITH organizer AS (
    SELECT id FROM organizer_profiles WHERE user_id = (
        SELECT id FROM users WHERE email = 'admin@aiolia.com'
    )
),
plan AS (
    SELECT id FROM subscription_plans WHERE code = 'STARTER'
)
INSERT INTO organizer_subscriptions (
    organizer_id, plan_id, status,
    starts_at, current_period_start, current_period_end, cancel_at_period_end
)
SELECT organizer.id,
       plan.id,
       'active',
       now(),
       now(),
       now() + INTERVAL '30 days',
       FALSE
FROM organizer, plan
WHERE NOT EXISTS (
    SELECT 1
    FROM organizer_subscriptions os
    WHERE os.organizer_id = organizer.id
      AND os.plan_id = plan.id
);

-- -------------------------------------------------------------------
-- Événement de démonstration + rattachements
-- -------------------------------------------------------------------
WITH organizer AS (
    SELECT id FROM organizer_profiles WHERE user_id = (
        SELECT id FROM users WHERE email = 'admin@aiolia.com'
    )
),
evt AS (
    INSERT INTO events (
        organizer_profile_id, slug, title, subtitle, summary,
        description, visibility, status, timezone,
        venue_name, venue_address, city, region, country_code,
        latitude, longitude, starts_at, ends_at,
        sales_starts_at, sales_ends_at,
        capacity, language_code, is_featured, is_highlighted
    )
    SELECT
        organizer.id,
        'concert-demo',
        'Concert de démonstration',
        'Live showcase',
        'Un événement de test visible sur le front.',
        'Cet événement illustre le parcours complet billetterie + commande.',
        'public',
        'published',
        'Indian/Antananarivo',
        'Palais des Sports',
        'Rue du Stade',
        'Antananarivo',
        'Analamanga',
        'MG',
        -18.87919,
        47.50791,
        now() + INTERVAL '30 days',
        now() + INTERVAL '30 days' + INTERVAL '3 hours',
        now(),
        now() + INTERVAL '25 days',
        500,
        'fr-FR',
        TRUE,
        FALSE
    FROM organizer
    ON CONFLICT (slug) DO UPDATE
        SET title = EXCLUDED.title,
            subtitle = EXCLUDED.subtitle,
            summary = EXCLUDED.summary,
            description = EXCLUDED.description,
            updated_at = now()
    RETURNING id
)
INSERT INTO event_category_links (event_id, category_id)
SELECT evt.id, cat.id
FROM evt
JOIN event_categories cat ON cat.slug = 'concert'
ON CONFLICT DO NOTHING;

INSERT INTO event_tag_links (event_id, tag_id)
SELECT ev.id, tag.id
FROM events ev
JOIN event_tags tag ON tag.slug = 'live'
WHERE ev.slug = 'concert-demo'
ON CONFLICT DO NOTHING;

INSERT INTO event_media (
    event_id, media_type, url, alt_text,
    display_order, is_public
)
SELECT ev.id,
       'image',
       'https://cdn.aiolia.mg/demo/concert-demo.jpg',
       'Affiche officielle du concert de démonstration',
       1,
       TRUE
FROM events ev
WHERE ev.slug = 'concert-demo'
ON CONFLICT DO NOTHING;

INSERT INTO event_sessions (
    event_id, title, description,
    starts_at, ends_at, capacity, location_override
)
SELECT ev.id,
       'Ouverture des portes',
       'Accueil des participants et contrôle des billets.',
       ev.starts_at - INTERVAL '1 hour',
       ev.starts_at,
       500,
       'Hall principal'
FROM events ev
WHERE ev.slug = 'concert-demo'
  AND NOT EXISTS (
        SELECT 1 FROM event_sessions es
        WHERE es.event_id = ev.id AND es.title = 'Ouverture des portes'
    );

-- -------------------------------------------------------------------
-- Billetterie de l’événement démo
-- -------------------------------------------------------------------
INSERT INTO ticket_types (
    event_id, name, description, currency,
    base_price, service_fee, vat_rate,
    sales_start, sales_end, min_per_order, max_per_order
)
SELECT ev.id,
       'Pass Standard',
       'Accès libre à l’ensemble du concert.',
       'MGA',
       80000,
       4000,
       20,
       ev.sales_starts_at,
       ev.sales_ends_at,
       1,
       4
FROM events ev
WHERE ev.slug = 'concert-demo'
  AND NOT EXISTS (
        SELECT 1 FROM ticket_types tt
        WHERE tt.event_id = ev.id AND tt.name = 'Pass Standard'
    );

INSERT INTO ticket_inventory (
    ticket_type_id, total_quantity, reserved_quantity, sold_quantity
)
SELECT tt.id, 500, 0, 0
FROM ticket_types tt
JOIN events ev ON ev.id = tt.event_id
WHERE ev.slug = 'concert-demo'
ON CONFLICT (ticket_type_id) DO NOTHING;

INSERT INTO pricing_rules (
    ticket_type_id, rule_type, threshold_value, value, starts_at, ends_at
)
SELECT tt.id,
       'tier',
       100,
       75000,
       ev.sales_starts_at,
       ev.sales_starts_at + INTERVAL '10 days'
FROM ticket_types tt
JOIN events ev ON ev.id = tt.event_id
WHERE ev.slug = 'concert-demo'
  AND NOT EXISTS (
        SELECT 1 FROM pricing_rules pr
        WHERE pr.ticket_type_id = tt.id
    );

-- -------------------------------------------------------------------
-- Code promotionnel de test
-- -------------------------------------------------------------------
WITH organizer AS (
    SELECT id FROM organizer_profiles WHERE user_id = (
        SELECT id FROM users WHERE email = 'admin@aiolia.com'
    )
)
INSERT INTO promotion_codes (
    organizer_profile_id, code, promotion_type, value,
    max_usage_total, max_usage_per_user, starts_at, ends_at
)
SELECT organizer.id,
       'DEMO10',
       'percent',
       10,
       200,
       1,
       now(),
       now() + INTERVAL '45 days'
FROM organizer
ON CONFLICT (code) DO NOTHING;

-- -------------------------------------------------------------------
-- Modèles de notifications essentiels
-- -------------------------------------------------------------------
INSERT INTO notification_templates (code, channel, subject, body, metadata)
VALUES
    ('order_confirmation', 'email', 'Confirmation de commande', 'Merci pour votre achat sur Aiolia Event.', '{"type":"order"}'),
    ('event_reminder', 'email', 'Rappel événement', 'Votre événement approche, pensez à votre billet.', '{"type":"reminder"}')
ON CONFLICT (code) DO NOTHING;

COMMIT;

