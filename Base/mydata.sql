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
    SELECT op.id
    FROM organizer_profiles op
    ORDER BY CASE
        WHEN op.user_id = (
            SELECT id FROM users WHERE email = 'admin@aiolia.com'
        ) THEN 0
        ELSE 1
    END
    LIMIT 1
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
-- Événements de démonstration + rattachements (2 entrées)
-- -------------------------------------------------------------------
WITH organizer AS (
    SELECT op.id
    FROM organizer_profiles op
    ORDER BY CASE
        WHEN op.user_id = (
            SELECT id FROM users WHERE email = 'admin@aiolia.com'
        ) THEN 0
        ELSE 1
    END
    LIMIT 1
),
event_insert AS (
    INSERT INTO events (
        organizer_profile_id, slug, title, subtitle, summary,
        description, visibility, status, timezone,
        venue_name, venue_address, city, region, country_code,
        latitude, longitude, starts_at, ends_at,
        sales_starts_at, sales_ends_at,
        capacity, language_code, is_featured, is_highlighted
    )
    SELECT organizer.id,
           data.slug,
           data.title,
           data.subtitle,
           data.summary,
           data.description,
           data.visibility::event_visibility_enum,
           data.status::event_status_enum,
           data.timezone,
           data.venue_name,
           data.venue_address,
           data.city,
           data.region,
           data.country_code,
           data.latitude,
           data.longitude,
           data.starts_at,
           data.ends_at,
           data.sales_starts_at,
           data.sales_ends_at,
           data.capacity,
           data.language_code,
           data.is_featured,
           data.is_highlighted
    FROM organizer
    CROSS JOIN (
        VALUES
            (
                'concert-music-sunday',
                'Music on Sunday',
                'Live showcase',
                'Un concert acoustique intimiste pour démarrer la semaine.',
                'Soirée musicale dédiée aux artistes montants de la scène locale.',
                'public',
                'published',
                'Indian/Antananarivo',
                'Café de la Gare',
                'Rue du Stade',
                'Antananarivo',
                'Analamanga',
                'MG',
                -18.9082,
                47.5257,
                now() + INTERVAL '15 days',
                now() + INTERVAL '15 days' + INTERVAL '3 hours',
                now() + INTERVAL '1 day',
                now() + INTERVAL '14 days',
                350,
                'fr-FR',
                TRUE,
                FALSE
            ),
            (
                'business-connect-mada',
                'Business Connect Mada',
                'Rencontres & networking',
                'Un cocktail pour connecter les entrepreneurs malgaches.',
                'Événement thématique pour échanger sur les tendances startup et financement.',
                'public',
                'published',
                'Indian/Antananarivo',
                'Le Dôme by SmartOne',
                'Immeuble Atrium, Galaxy Andraharo',
                'Antananarivo',
                'Analamanga',
                'MG',
                -18.8549,
                47.5203,
                now() + INTERVAL '28 days',
                now() + INTERVAL '28 days' + INTERVAL '4 hours',
                now() + INTERVAL '5 days',
                now() + INTERVAL '26 days',
                220,
                'fr-FR',
                FALSE,
                TRUE
            )
    ) AS data (
        slug, title, subtitle, summary,
        description, visibility, status, timezone,
        venue_name, venue_address, city, region, country_code,
        latitude, longitude, starts_at, ends_at,
        sales_starts_at, sales_ends_at,
        capacity, language_code, is_featured, is_highlighted
    )
    ON CONFLICT (slug) DO UPDATE
        SET title = EXCLUDED.title,
            subtitle = EXCLUDED.subtitle,
            summary = EXCLUDED.summary,
            description = EXCLUDED.description,
            venue_name = EXCLUDED.venue_name,
            venue_address = EXCLUDED.venue_address,
            city = EXCLUDED.city,
            region = EXCLUDED.region,
            country_code = EXCLUDED.country_code,
            latitude = EXCLUDED.latitude,
            longitude = EXCLUDED.longitude,
            starts_at = EXCLUDED.starts_at,
            ends_at = EXCLUDED.ends_at,
            sales_starts_at = EXCLUDED.sales_starts_at,
            sales_ends_at = EXCLUDED.sales_ends_at,
            capacity = EXCLUDED.capacity,
            updated_at = now()
    RETURNING id, slug
)
INSERT INTO event_category_links (event_id, category_id)
SELECT evt.id,
       cat.id
FROM event_insert evt
JOIN event_categories cat ON cat.slug = CASE evt.slug
    WHEN 'business-connect-mada' THEN 'business'
    ELSE 'concert'
END
ON CONFLICT DO NOTHING;

WITH evt AS (
    SELECT id, slug FROM events WHERE slug IN ('concert-music-sunday', 'business-connect-mada')
)
INSERT INTO event_tag_links (event_id, tag_id)
SELECT evt.id,
       tag.id
FROM evt
JOIN event_tags tag ON tag.slug = CASE evt.slug
    WHEN 'business-connect-mada' THEN 'networking'
    ELSE 'live'
END
ON CONFLICT DO NOTHING;

WITH evt AS (
    SELECT id, slug FROM events WHERE slug IN ('concert-music-sunday', 'business-connect-mada')
)
INSERT INTO event_media (
    event_id, media_type, url, alt_text,
    display_order, is_public
)
SELECT evt.id,
       'image',
       CASE evt.slug
           WHEN 'business-connect-mada' THEN '/vente-ticket/images/img2.png'
           ELSE '/vente-ticket/images/img1.png'
       END,
       CASE evt.slug
           WHEN 'business-connect-mada' THEN 'Affiche Business Connect Madagascar'
           ELSE 'Affiche Music on Sunday'
       END,
       1,
       TRUE
FROM evt
ON CONFLICT DO NOTHING;

WITH evt AS (
    SELECT id, slug, starts_at FROM events WHERE slug IN ('concert-music-sunday', 'business-connect-mada')
)
INSERT INTO event_sessions (
    event_id, title, description,
    starts_at, ends_at, capacity, location_override
)
SELECT evt.id,
       CASE evt.slug
           WHEN 'business-connect-mada' THEN 'Session networking & pitch'
           ELSE 'Ouverture des portes'
       END,
       CASE evt.slug
           WHEN 'business-connect-mada' THEN 'Rencontres thématiques en petits groupes et prises de contact.'
           ELSE 'Accueil des participants et contrôle des billets.'
       END,
       evt.starts_at - INTERVAL '1 hour',
       evt.starts_at,
       CASE evt.slug
           WHEN 'business-connect-mada' THEN 220
           ELSE 350
       END,
       CASE evt.slug
           WHEN 'business-connect-mada' THEN 'Hall principal & rooftop'
           ELSE 'Hall principal'
       END
FROM evt
WHERE NOT EXISTS (
    SELECT 1
    FROM event_sessions es
    WHERE es.event_id = evt.id
      AND es.title = CASE evt.slug
           WHEN 'business-connect-mada' THEN 'Session networking & pitch'
           ELSE 'Ouverture des portes'
       END
);

WITH evt AS (
    SELECT id, slug, sales_starts_at, sales_ends_at FROM events WHERE slug IN ('concert-music-sunday', 'business-connect-mada')
)
INSERT INTO ticket_types (
    event_id, name, description, currency,
    base_price, service_fee, vat_rate,
    sales_start, sales_end, min_per_order, max_per_order
)
SELECT evt.id,
       CASE evt.slug
           WHEN 'business-connect-mada' THEN 'Pass Networking'
           ELSE 'Pass Concert'
       END,
       CASE evt.slug
           WHEN 'business-connect-mada' THEN 'Accès complet à la soirée networking, cocktail inclus.'
           ELSE 'Accès libre à l’ensemble de la soirée musicale.'
       END,
       'MGA',
       CASE evt.slug
           WHEN 'business-connect-mada' THEN 120000
           ELSE 80000
       END,
       CASE evt.slug
           WHEN 'business-connect-mada' THEN 6000
           ELSE 4000
       END,
       20,
       evt.sales_starts_at,
       evt.sales_ends_at,
       1,
       CASE evt.slug
           WHEN 'business-connect-mada' THEN 2
           ELSE 4
       END
FROM evt
WHERE NOT EXISTS (
    SELECT 1
    FROM ticket_types tt
    WHERE tt.event_id = evt.id
      AND tt.name = CASE evt.slug
           WHEN 'business-connect-mada' THEN 'Pass Networking'
           ELSE 'Pass Concert'
       END
);

WITH tt AS (
    SELECT tt.id, ev.slug
    FROM ticket_types tt
    JOIN events ev ON ev.id = tt.event_id
    WHERE ev.slug IN ('concert-music-sunday', 'business-connect-mada')
)
INSERT INTO ticket_inventory (
    ticket_type_id, total_quantity, reserved_quantity, sold_quantity
)
SELECT tt.id,
       CASE tt.slug
           WHEN 'business-connect-mada' THEN 220
           ELSE 350
       END,
       0,
       0
FROM tt
ON CONFLICT (ticket_type_id) DO NOTHING;

WITH tt AS (
    SELECT tt.id, ev.slug, ev.sales_starts_at
    FROM ticket_types tt
    JOIN events ev ON ev.id = tt.event_id
    WHERE ev.slug IN ('concert-music-sunday', 'business-connect-mada')
)
INSERT INTO pricing_rules (
    ticket_type_id, rule_type, threshold_value, value, starts_at, ends_at
)
SELECT tt.id,
       'tier',
       CASE tt.slug
           WHEN 'business-connect-mada' THEN 50
           ELSE 100
       END,
       CASE tt.slug
           WHEN 'business-connect-mada' THEN 100000
           ELSE 75000
       END,
       tt.sales_starts_at,
       tt.sales_starts_at + INTERVAL '7 days'
FROM tt
WHERE NOT EXISTS (
    SELECT 1
    FROM pricing_rules pr
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

