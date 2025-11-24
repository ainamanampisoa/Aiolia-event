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
    SELECT
        op.id AS organizer_profile_id,
        op.user_id
    FROM organizer_profiles op
    ORDER BY CASE
        WHEN op.user_id = (
            SELECT id FROM users WHERE email = 'admin@aiolia.com'
        ) THEN 0
        ELSE 1
    END
    LIMIT 1
),
venue_data AS (
    SELECT *
    FROM (VALUES
        (
            'cafe-de-la-gare',
            'Café de la Gare',
            'Salle iconique du centre-ville pour concerts intimistes.',
            'Rue du Stade',
            NULL,
            'Antananarivo',
            'Analamanga',
            '101',
            'MG',
            -18.908200,
            47.525700,
            'Indian/Antananarivo',
            400
        ),
        (
            'le-dome-smartone',
            'Le Dôme by SmartOne',
            'Espace moderne pensé pour les événements corporate et networking.',
            'Immeuble Atrium, Galaxy Andraharo',
            NULL,
            'Antananarivo',
            'Analamanga',
            '101',
            'MG',
            -18.854900,
            47.520300,
            'Indian/Antananarivo',
            250
        )
    ) AS v (
        slug,
        name,
        description,
        address_line1,
        address_line2,
        city,
        region,
        postal_code,
        country_code,
        latitude,
        longitude,
        timezone,
        capacity
    )
),
venue_insert AS (
    INSERT INTO venues (
        organizer_id,
        name,
        slug,
        description,
        address_line1,
        address_line2,
        city,
        region,
        postal_code,
        country_code,
        latitude,
        longitude,
        timezone,
        capacity
    )
    SELECT
        organizer.organizer_profile_id,
        vd.name,
        vd.slug,
        vd.description,
        vd.address_line1,
        vd.address_line2,
        vd.city,
        vd.region,
        vd.postal_code,
        vd.country_code,
        vd.latitude,
        vd.longitude,
        vd.timezone,
        vd.capacity
    FROM organizer
    CROSS JOIN venue_data vd
    ON CONFLICT (slug) DO UPDATE
        SET name = EXCLUDED.name,
            description = EXCLUDED.description,
            address_line1 = EXCLUDED.address_line1,
            address_line2 = EXCLUDED.address_line2,
            city = EXCLUDED.city,
            region = EXCLUDED.region,
            postal_code = EXCLUDED.postal_code,
            country_code = EXCLUDED.country_code,
            latitude = EXCLUDED.latitude,
            longitude = EXCLUDED.longitude,
            timezone = EXCLUDED.timezone,
            capacity = EXCLUDED.capacity,
            updated_at = now()
    RETURNING id, slug
),
space_defs AS (
    SELECT *
    FROM (VALUES
        (
            'cafe-de-la-gare',
            'Salle principale',
            'Espace central pour concerts acoustiques et showcases.',
            350
        ),
        (
            'le-dome-smartone',
            'Espace conférence',
            'Salle modulable pour networking, pitchs et ateliers.',
            220
        )
    ) AS sd (venue_slug, space_name, description, capacity)
),
space_insert AS (
    INSERT INTO venue_spaces (venue_id, name, description, capacity, is_default)
    SELECT
        v.id,
        sd.space_name,
        sd.description,
        sd.capacity,
        TRUE
    FROM space_defs sd
    JOIN venue_insert v ON v.slug = sd.venue_slug
    WHERE NOT EXISTS (
        SELECT 1
        FROM venue_spaces vs
        WHERE vs.venue_id = v.id
          AND vs.name = sd.space_name
    )
),
space_lookup AS (
    SELECT
        v.slug AS venue_slug,
        sd.space_name,
        vs.id AS space_id
    FROM venue_insert v
    JOIN space_defs sd ON sd.venue_slug = v.slug
    JOIN venue_spaces vs ON vs.venue_id = v.id AND vs.name = sd.space_name
),
event_data AS (
    SELECT *
    FROM (VALUES
        (
            'concert-music-sunday',
            'Music on Sunday',
            'Live showcase',
            'Un concert acoustique intimiste pour démarrer la semaine.',
            'Soirée musicale dédiée aux artistes montants de la scène locale.',
            'public',
            'published',
            'in_person',
            'Indian/Antananarivo',
            now() + INTERVAL '15 days',
            now() + INTERVAL '15 days' + INTERVAL '3 hours',
            now() + INTERVAL '1 day',
            now() + INTERVAL '14 days',
            350,
            'fr-FR',
            TRUE,
            FALSE,
            'concert',
            '/vente-ticket/images/img1.png',
            'cafe-de-la-gare',
            'Salle principale',
            '{"venue_name":"Café de la Gare","address":"Rue du Stade","city":"Antananarivo","region":"Analamanga","country":"MG"}'::jsonb
        ),
        (
            'business-connect-mada',
            'Business Connect Mada',
            'Rencontres & networking',
            'Un cocktail pour connecter les entrepreneurs malgaches.',
            'Événement thématique pour échanger sur les tendances startup et financement.',
            'public',
            'published',
            'in_person',
            'Indian/Antananarivo',
            now() + INTERVAL '28 days',
            now() + INTERVAL '28 days' + INTERVAL '4 hours',
            now() + INTERVAL '5 days',
            now() + INTERVAL '26 days',
            220,
            'fr-FR',
            FALSE,
            TRUE,
            'business',
            '/vente-ticket/images/img2.png',
            'le-dome-smartone',
            'Espace conférence',
            '{"venue_name":"Le Dôme by SmartOne","address":"Immeuble Atrium, Galaxy Andraharo","city":"Antananarivo","region":"Analamanga","country":"MG"}'::jsonb
        )
    ) AS data (
        slug,
        title,
        subtitle,
        summary,
        description,
        visibility,
        status,
        event_format,
        timezone,
        starts_at,
        ends_at,
        sales_starts_at,
        sales_ends_at,
        capacity,
        language_code,
        is_featured,
        is_highlighted,
        category_slug,
        cover_image_url,
        venue_slug,
        space_name,
        location_override
    )
),
event_insert AS (
    INSERT INTO events (
        organizer_id,
        primary_category_id,
        venue_id,
        main_space_id,
        slug,
        title,
        subtitle,
        summary,
        description,
        visibility,
        status,
        event_format,
        timezone,
        capacity,
        language_code,
        is_featured,
        is_highlighted,
        starts_at,
        ends_at,
        sales_starts_at,
        sales_ends_at,
        location_override,
        cover_image_url
    )
    SELECT
        organizer.user_id,
        cat.id,
        v.id,
        sl.space_id,
        data.slug,
        data.title,
        data.subtitle,
        data.summary,
        data.description,
        data.visibility::event_visibility_enum,
        data.status::event_status_enum,
        data.event_format,
        data.timezone,
        data.capacity,
        data.language_code,
        data.is_featured,
        data.is_highlighted,
        data.starts_at,
        data.ends_at,
        data.sales_starts_at,
        data.sales_ends_at,
        data.location_override,
        data.cover_image_url
    FROM organizer
    CROSS JOIN event_data data
    LEFT JOIN event_categories cat ON cat.slug = data.category_slug
    JOIN venue_insert v ON v.slug = data.venue_slug
    LEFT JOIN space_lookup sl
        ON sl.venue_slug = data.venue_slug
       AND sl.space_name = data.space_name
    ON CONFLICT (slug) DO UPDATE
        SET title = EXCLUDED.title,
            subtitle = EXCLUDED.subtitle,
            summary = EXCLUDED.summary,
            description = EXCLUDED.description,
            visibility = EXCLUDED.visibility,
            status = EXCLUDED.status,
            event_format = EXCLUDED.event_format,
            timezone = EXCLUDED.timezone,
            capacity = EXCLUDED.capacity,
            primary_category_id = EXCLUDED.primary_category_id,
            venue_id = EXCLUDED.venue_id,
            main_space_id = EXCLUDED.main_space_id,
            starts_at = EXCLUDED.starts_at,
            ends_at = EXCLUDED.ends_at,
            sales_starts_at = EXCLUDED.sales_starts_at,
            sales_ends_at = EXCLUDED.sales_ends_at,
            location_override = EXCLUDED.location_override,
            cover_image_url = EXCLUDED.cover_image_url,
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
    base_price, service_fee, vat_rate, age_category,
    sales_start, sales_end, min_per_order, max_per_order
)
SELECT evt.id,
       CASE evt.slug
           WHEN 'business-connect-mada' THEN 'Pass Networking'
           ELSE 'Pass Concert'
       END,
       CASE evt.slug
           WHEN 'business-connect-mada' THEN 'Accès complet à la soirée networking, cocktail inclus.'
           ELSE 'Accès libre à l''ensemble de la soirée musicale.'
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
       'all'::age_category_enum,
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

-- -------------------------------------------------------------------
-- Événements de test pour les catégories d'âge (adulte/enfant)
-- -------------------------------------------------------------------
WITH organizer AS (
    SELECT
        op.id AS organizer_profile_id,
        op.user_id
    FROM organizer_profiles op
    ORDER BY CASE
        WHEN op.user_id = (
            SELECT id FROM users WHERE email = 'admin@aiolia.com'
        ) THEN 0
        ELSE 1
    END
    LIMIT 1
),
venue_famille AS (
    INSERT INTO venues (
        organizer_id,
        name,
        slug,
        description,
        address_line1,
        city,
        region,
        postal_code,
        country_code,
        latitude,
        longitude,
        timezone,
        capacity
    )
    SELECT
        organizer.organizer_profile_id,
        'Parc des Familles',
        'parc-des-familles',
        'Espace en plein air idéal pour les événements familiaux et les activités pour enfants.',
        'Avenue de l''Indépendance',
        'Antananarivo',
        'Analamanga',
        '101',
        'MG',
        -18.879200,
        47.507500,
        'Indian/Antananarivo',
        500
    FROM organizer
    ON CONFLICT (slug) DO UPDATE
        SET name = EXCLUDED.name,
            description = EXCLUDED.description,
            updated_at = now()
    RETURNING id, slug
),
venue_corporate AS (
    INSERT INTO venues (
        organizer_id,
        name,
        slug,
        description,
        address_line1,
        city,
        region,
        postal_code,
        country_code,
        latitude,
        longitude,
        timezone,
        capacity
    )
    SELECT
        organizer.organizer_profile_id,
        'Centre de Conférences',
        'centre-conferences',
        'Espace professionnel moderne pour séminaires et formations.',
        'Zone Galaxy Andraharo',
        'Antananarivo',
        'Analamanga',
        '101',
        'MG',
        -18.854900,
        47.520300,
        'Indian/Antananarivo',
        150
    FROM organizer
    ON CONFLICT (slug) DO UPDATE
        SET name = EXCLUDED.name,
            description = EXCLUDED.description,
            updated_at = now()
    RETURNING id, slug
),
space_famille AS (
    INSERT INTO venue_spaces (venue_id, name, description, capacity, is_default)
    SELECT v.id, 'Espace principal', 'Grande pelouse avec scène et aire de jeux', 500, TRUE
    FROM venue_famille v
    WHERE NOT EXISTS (
        SELECT 1 FROM venue_spaces vs WHERE vs.venue_id = v.id AND vs.name = 'Espace principal'
    )
    RETURNING id, venue_id
),
space_corporate AS (
    INSERT INTO venue_spaces (venue_id, name, description, capacity, is_default)
    SELECT v.id, 'Salle principale', 'Amphithéâtre équipé pour conférences', 150, TRUE
    FROM venue_corporate v
    WHERE NOT EXISTS (
        SELECT 1 FROM venue_spaces vs WHERE vs.venue_id = v.id AND vs.name = 'Salle principale'
    )
    RETURNING id, venue_id
),
event_famille AS (
    INSERT INTO events (
        organizer_id,
        primary_category_id,
        venue_id,
        main_space_id,
        slug,
        title,
        subtitle,
        summary,
        description,
        visibility,
        status,
        event_format,
        timezone,
        capacity,
        language_code,
        is_featured,
        is_highlighted,
        starts_at,
        ends_at,
        sales_starts_at,
        sales_ends_at,
        location_override,
        cover_image_url
    )
    SELECT
        organizer.user_id,
        cat.id,
        vf.id,
        sf.id,
        'festival-famille-enfants',
        'Festival Famille & Enfants',
        'Journée découverte',
        'Une journée entière dédiée aux familles avec activités pour tous les âges.',
        'Festival en plein air avec ateliers créatifs, spectacles de marionnettes, jeux géants, et animations pour enfants. Espace restauration et aire de pique-nique disponibles. Les enfants de moins de 3 ans sont gratuits.',
        'public'::event_visibility_enum,
        'published'::event_status_enum,
        'in_person',
        'Indian/Antananarivo',
        500,
        'fr-FR',
        TRUE,
        TRUE,
        now() + INTERVAL '20 days',
        now() + INTERVAL '20 days' + INTERVAL '8 hours',
        now() + INTERVAL '2 days',
        now() + INTERVAL '18 days',
        '{"venue_name":"Parc des Familles","address":"Avenue de l''Indépendance","city":"Antananarivo","region":"Analamanga","country":"MG"}'::jsonb,
        '/vente-ticket/images/img1.png'
    FROM organizer
    CROSS JOIN venue_famille vf
    CROSS JOIN space_famille sf
    LEFT JOIN event_categories cat ON cat.slug = 'concert'
    ON CONFLICT (slug) DO UPDATE
        SET title = EXCLUDED.title,
            subtitle = EXCLUDED.subtitle,
            summary = EXCLUDED.summary,
            description = EXCLUDED.description,
            updated_at = now()
    RETURNING id, slug
),
event_corporate AS (
    INSERT INTO events (
        organizer_id,
        primary_category_id,
        venue_id,
        main_space_id,
        slug,
        title,
        subtitle,
        summary,
        description,
        visibility,
        status,
        event_format,
        timezone,
        capacity,
        language_code,
        is_featured,
        is_highlighted,
        starts_at,
        ends_at,
        sales_starts_at,
        sales_ends_at,
        location_override,
        cover_image_url
    )
    SELECT
        organizer.user_id,
        cat.id,
        vc.id,
        sc.id,
        'seminaire-professionnel-adultes',
        'Séminaire Professionnel',
        'Formation & Networking',
        'Formation intensive pour professionnels sur les nouvelles technologies.',
        'Séminaire d''une journée destiné aux professionnels du secteur IT. Programme incluant conférences, ateliers pratiques, et session de networking. Repas de midi inclus. Réservé aux adultes (18 ans et plus).',
        'public'::event_visibility_enum,
        'published'::event_status_enum,
        'in_person',
        'Indian/Antananarivo',
        150,
        'fr-FR',
        FALSE,
        TRUE,
        now() + INTERVAL '25 days',
        now() + INTERVAL '25 days' + INTERVAL '6 hours',
        now() + INTERVAL '3 days',
        now() + INTERVAL '23 days',
        '{"venue_name":"Centre de Conférences","address":"Zone Galaxy Andraharo","city":"Antananarivo","region":"Analamanga","country":"MG"}'::jsonb,
        '/vente-ticket/images/img2.png'
    FROM organizer
    CROSS JOIN venue_corporate vc
    CROSS JOIN space_corporate sc
    LEFT JOIN event_categories cat ON cat.slug = 'business'
    ON CONFLICT (slug) DO UPDATE
        SET title = EXCLUDED.title,
            subtitle = EXCLUDED.subtitle,
            summary = EXCLUDED.summary,
            description = EXCLUDED.description,
            updated_at = now()
    RETURNING id, slug
)
-- Catégories et tags pour les nouveaux événements
INSERT INTO event_category_links (event_id, category_id)
SELECT evt.id, cat.id
FROM (SELECT id, slug FROM events WHERE slug IN ('festival-famille-enfants', 'seminaire-professionnel-adultes')) evt
JOIN event_categories cat ON cat.slug = CASE evt.slug
    WHEN 'seminaire-professionnel-adultes' THEN 'business'
    ELSE 'concert'
END
ON CONFLICT DO NOTHING;

-- Médias pour les nouveaux événements
INSERT INTO event_media (event_id, media_type, url, alt_text, display_order, is_public)
SELECT evt.id,
       'image',
       CASE evt.slug
           WHEN 'seminaire-professionnel-adultes' THEN '/vente-ticket/images/img2.png'
           ELSE '/vente-ticket/images/img1.png'
       END,
       CASE evt.slug
           WHEN 'seminaire-professionnel-adultes' THEN 'Affiche Séminaire Professionnel'
           ELSE 'Affiche Festival Famille & Enfants'
       END,
       1,
       TRUE
FROM (SELECT id, slug FROM events WHERE slug IN ('festival-famille-enfants', 'seminaire-professionnel-adultes')) evt
WHERE NOT EXISTS (
    SELECT 1 FROM event_media em WHERE em.event_id = evt.id AND em.display_order = 1
);

-- Types de billets pour "Festival Famille & Enfants" (ADULTE ET ENFANT)
WITH evt_famille AS (
    SELECT id, sales_starts_at, sales_ends_at FROM events WHERE slug = 'festival-famille-enfants'
)
INSERT INTO ticket_types (
    event_id, name, description, currency,
    base_price, service_fee, vat_rate, age_category,
    sales_start, sales_end, min_per_order, max_per_order,
    metadata
)
SELECT evt.id,
       'Billet Adulte',
       'Accès complet pour un adulte (18 ans et plus). Inclut toutes les activités et animations.',
       'MGA',
       15000,
        750,
        20,
       'adult'::age_category_enum,
       evt.sales_starts_at,
       evt.sales_ends_at,
       1,
       10,
       '{"age_min":18,"age_max":null,"requires_accompaniment":false}'::jsonb
FROM evt_famille evt
WHERE NOT EXISTS (
    SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Billet Adulte'
)
UNION ALL
SELECT evt.id,
       'Billet Enfant',
       'Accès complet pour un enfant (3 à 17 ans). Gratuit pour les moins de 3 ans.',
       'MGA',
       8000,
        400,
        20,
       'child'::age_category_enum,
       evt.sales_starts_at,
       evt.sales_ends_at,
       1,
       10,
       '{"age_min":3,"age_max":17,"requires_accompaniment":true,"accompaniment_age_min":18,"special_conditions":"Gratuit pour les moins de 3 ans"}'::jsonb
FROM evt_famille evt
WHERE NOT EXISTS (
    SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Billet Enfant'
);

-- Inventaire pour les billets famille
WITH tt_famille AS (
    SELECT tt.id, tt.name
    FROM ticket_types tt
    JOIN events ev ON ev.id = tt.event_id
    WHERE ev.slug = 'festival-famille-enfants'
)
INSERT INTO ticket_inventory (ticket_type_id, total_quantity, reserved_quantity, sold_quantity)
SELECT tt.id,
       CASE tt.name
           WHEN 'Billet Adulte' THEN 300
           WHEN 'Billet Enfant' THEN 200
           ELSE 0
       END,
       0,
       0
FROM tt_famille tt
ON CONFLICT (ticket_type_id) DO NOTHING;

-- Types de billets pour "Séminaire Professionnel" (ADULTE SEULEMENT)
WITH evt_corporate AS (
    SELECT id, sales_starts_at, sales_ends_at FROM events WHERE slug = 'seminaire-professionnel-adultes'
)
INSERT INTO ticket_types (
    event_id, name, description, currency,
    base_price, service_fee, vat_rate, age_category,
    sales_start, sales_end, min_per_order, max_per_order,
    metadata
)
SELECT evt.id,
       'Pass Standard',
       'Accès complet au séminaire pour un adulte (18 ans et plus). Inclut repas de midi et documentation.',
       'MGA',
       250000,
        12500,
        20,
       'adult'::age_category_enum,
       evt.sales_starts_at,
       evt.sales_ends_at,
       1,
       5,
       '{"age_min":18,"age_max":null,"includes_lunch":true,"includes_documentation":true}'::jsonb
FROM evt_corporate evt
WHERE NOT EXISTS (
    SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Pass Standard'
)
UNION ALL
SELECT evt.id,
       'Pass Premium',
       'Accès VIP avec place réservée en première rangée, repas premium, et accès à la session exclusive.',
       'MGA',
       400000,
        20000,
        20,
       'adult'::age_category_enum,
       evt.sales_starts_at,
       evt.sales_ends_at,
       1,
       3,
       '{"age_min":18,"age_max":null,"includes_lunch":true,"includes_documentation":true,"vip_seating":true,"exclusive_session":true}'::jsonb
FROM evt_corporate evt
WHERE NOT EXISTS (
    SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Pass Premium'
);

-- Inventaire pour les billets corporate
WITH tt_corporate AS (
    SELECT tt.id, tt.name
    FROM ticket_types tt
    JOIN events ev ON ev.id = tt.event_id
    WHERE ev.slug = 'seminaire-professionnel-adultes'
)
INSERT INTO ticket_inventory (ticket_type_id, total_quantity, reserved_quantity, sold_quantity)
SELECT tt.id,
       CASE tt.name
           WHEN 'Pass Standard' THEN 120
           WHEN 'Pass Premium' THEN 30
           ELSE 0
       END,
       0,
       0
FROM tt_corporate tt
ON CONFLICT (ticket_type_id) DO NOTHING;

-- ============================================================
-- ÉVÉNEMENTS AVEC TYPES VIP/GOLD/SILVER GROUPÉS
-- (avec prix adultes/enfants séparés)
-- ============================================================

-- Concert Premium avec types VIP/Gold/Silver
WITH organizer AS (
    SELECT op.user_id
    FROM organizer_profiles op
    ORDER BY op.id
    LIMIT 1
),
venue_premium AS (
    SELECT id FROM venues ORDER BY id LIMIT 1
),
space_premium AS (
    SELECT id FROM venue_spaces ORDER BY id LIMIT 1
),
event_premium AS (
    INSERT INTO events (
        organizer_id,
        primary_category_id,
        venue_id,
        main_space_id,
        slug,
        title,
        subtitle,
        summary,
        description,
        visibility,
        status,
        event_format,
        timezone,
        capacity,
        language_code,
        is_featured,
        is_highlighted,
        starts_at,
        ends_at,
        sales_starts_at,
        sales_ends_at,
        location_override,
        cover_image_url
    )
    SELECT
        organizer.user_id,
        cat.id,
        vp.id,
        sp.id,
        'concert-premium-vip-types',
        'Concert Premium - Types VIP Groupés',
        'Expérience musicale exclusive',
        'Un concert exceptionnel avec différents types de billets pour tous les âges. Profitez de l''expérience VIP, Gold ou Silver selon vos préférences.',
        'Concert premium avec plusieurs catégories de billets. Types VIP avec accès prioritaire et zone exclusive, Gold avec meilleur placement, et Silver avec accès standard amélioré. Tarifs adaptés pour adultes et enfants.',
        'public'::event_visibility_enum,
        'published'::event_status_enum,
        'in_person',
        'Indian/Antananarivo',
        800,
        'fr-FR',
        TRUE,
        TRUE,
        now() + INTERVAL '25 days',
        now() + INTERVAL '25 days' + INTERVAL '4 hours',
        now() - INTERVAL '1 day',
        now() + INTERVAL '24 days',
        '{"venue_name":"Salle Premium","address":"Boulevard de l''Indépendance","city":"Antananarivo","region":"Analamanga","country":"MG"}'::jsonb,
        '/vente-ticket/images/img1.png'
    FROM organizer
    CROSS JOIN venue_premium vp
    CROSS JOIN space_premium sp
    LEFT JOIN event_categories cat ON cat.slug = 'concert'
    ON CONFLICT (slug) DO UPDATE
        SET title = EXCLUDED.title,
            subtitle = EXCLUDED.subtitle,
            summary = EXCLUDED.summary,
            description = EXCLUDED.description,
            updated_at = now()
    RETURNING id, slug, sales_starts_at, sales_ends_at
)
-- Types de billets VIP avec prix adultes/enfants
INSERT INTO ticket_types (
    event_id, name, description, currency,
    base_price, service_fee, vat_rate, age_category,
    sales_start, sales_end, min_per_order, max_per_order,
    metadata
)
-- VIP Adulte
SELECT evt.id,
       'VIP',
       'Billet VIP pour adulte. Accès prioritaire, zone VIP exclusive, parking réservé et boissons offertes.',
       'MGA',
       100000,
       5000,
       20,
       'adult'::age_category_enum,
       evt.sales_starts_at,
       evt.sales_ends_at,
       1,
       5,
       '{"vip":true,"priority_access":true,"exclusive_area":true,"parking":true,"drinks":true,"age_min":18}'::jsonb
FROM event_premium evt
WHERE NOT EXISTS (
    SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'VIP' AND tt.age_category = 'adult'
)
UNION ALL
-- VIP Enfant
SELECT evt.id,
       'VIP',
       'Billet VIP pour enfant. Accès prioritaire, zone VIP exclusive adaptée aux enfants.',
       'MGA',
       50000,
       2500,
       20,
       'child'::age_category_enum,
       evt.sales_starts_at,
       evt.sales_ends_at,
       1,
       5,
       '{"vip":true,"priority_access":true,"exclusive_area":true,"age_min":3,"age_max":17,"requires_accompaniment":true}'::jsonb
FROM event_premium evt
WHERE NOT EXISTS (
    SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'VIP' AND tt.age_category = 'child'
)
UNION ALL
-- Gold Adulte
SELECT evt.id,
       'Gold',
       'Billet Gold pour adulte. Meilleur placement, avantages premium.',
       'MGA',
       75000,
       3750,
       20,
       'adult'::age_category_enum,
       evt.sales_starts_at,
       evt.sales_ends_at,
       1,
       10,
       '{"gold":true,"premium_seating":true,"age_min":18}'::jsonb
FROM event_premium evt
WHERE NOT EXISTS (
    SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Gold' AND tt.age_category = 'adult'
)
UNION ALL
-- Gold Enfant
SELECT evt.id,
       'Gold',
       'Billet Gold pour enfant. Meilleur placement, avantages premium.',
       'MGA',
       37500,
       1875,
       20,
       'child'::age_category_enum,
       evt.sales_starts_at,
       evt.sales_ends_at,
       1,
       10,
       '{"gold":true,"premium_seating":true,"age_min":3,"age_max":17,"requires_accompaniment":true}'::jsonb
FROM event_premium evt
WHERE NOT EXISTS (
    SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Gold' AND tt.age_category = 'child'
)
UNION ALL
-- Silver Adulte
SELECT evt.id,
       'Silver',
       'Billet Silver pour adulte. Placement standard amélioré.',
       'MGA',
       50000,
       2500,
       20,
       'adult'::age_category_enum,
       evt.sales_starts_at,
       evt.sales_ends_at,
       1,
       20,
       '{"silver":true,"standard_plus":true,"age_min":18}'::jsonb
FROM event_premium evt
WHERE NOT EXISTS (
    SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Silver' AND tt.age_category = 'adult'
)
UNION ALL
-- Silver Enfant
SELECT evt.id,
       'Silver',
       'Billet Silver pour enfant. Placement standard amélioré.',
       'MGA',
       25000,
       1250,
       20,
       'child'::age_category_enum,
       evt.sales_starts_at,
       evt.sales_ends_at,
       1,
       20,
       '{"silver":true,"standard_plus":true,"age_min":3,"age_max":17,"requires_accompaniment":true}'::jsonb
FROM event_premium evt
WHERE NOT EXISTS (
    SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Silver' AND tt.age_category = 'child'
);

-- Inventaire pour les billets premium VIP/Gold/Silver
WITH tt_premium AS (
    SELECT tt.id, tt.name, tt.age_category
    FROM ticket_types tt
    JOIN events ev ON ev.id = tt.event_id
    WHERE ev.slug = 'concert-premium-vip-types'
)
INSERT INTO ticket_inventory (ticket_type_id, total_quantity, reserved_quantity, sold_quantity)
SELECT tt.id,
       CASE 
           WHEN tt.name = 'VIP' AND tt.age_category = 'adult' THEN 50
           WHEN tt.name = 'VIP' AND tt.age_category = 'child' THEN 30
           WHEN tt.name = 'Gold' AND tt.age_category = 'adult' THEN 100
           WHEN tt.name = 'Gold' AND tt.age_category = 'child' THEN 50
           WHEN tt.name = 'Silver' AND tt.age_category = 'adult' THEN 200
           WHEN tt.name = 'Silver' AND tt.age_category = 'child' THEN 100
           ELSE 0
       END,
       0,
       0
FROM tt_premium tt
ON CONFLICT (ticket_type_id) DO NOTHING;

-- Festival Sportif avec types VIP/Standard
WITH organizer AS (
    SELECT op.user_id
    FROM organizer_profiles op
    ORDER BY op.id
    LIMIT 1
),
venue_sport AS (
    SELECT id FROM venues ORDER BY id LIMIT 1
),
space_sport AS (
    SELECT id FROM venue_spaces ORDER BY id LIMIT 1
),
event_sport AS (
    INSERT INTO events (
        organizer_id,
        primary_category_id,
        venue_id,
        main_space_id,
        slug,
        title,
        subtitle,
        summary,
        description,
        visibility,
        status,
        event_format,
        timezone,
        capacity,
        language_code,
        is_featured,
        is_highlighted,
        starts_at,
        ends_at,
        sales_starts_at,
        sales_ends_at,
        location_override,
        cover_image_url
    )
    SELECT
        organizer.user_id,
        cat.id,
        vs.id,
        ss.id,
        'festival-sportif-vip-standard',
        'Festival Sportif - VIP et Standard',
        'Compétition sportive familiale',
        'Festival sportif avec différentes catégories de billets. Profitez de l''événement en VIP ou Standard, avec des tarifs adaptés pour toute la famille.',
        'Festival sportif avec plusieurs disciplines. Billets VIP pour une expérience premium avec accès privilégié, ou Standard pour profiter de l''événement à un tarif accessible. Tarifs adaptés pour adultes et enfants.',
        'public'::event_visibility_enum,
        'published'::event_status_enum,
        'in_person',
        'Indian/Antananarivo',
        600,
        'fr-FR',
        FALSE,
        FALSE,
        now() + INTERVAL '35 days',
        now() + INTERVAL '35 days' + INTERVAL '6 hours',
        now() + INTERVAL '1 day',
        now() + INTERVAL '34 days',
        '{"venue_name":"Stade Municipal","address":"Avenue de la République","city":"Antananarivo","region":"Analamanga","country":"MG"}'::jsonb,
        '/vente-ticket/images/img1.png'
    FROM organizer
    CROSS JOIN venue_sport vs
    CROSS JOIN space_sport ss
    LEFT JOIN event_categories cat ON cat.slug = 'concert'
    ON CONFLICT (slug) DO UPDATE
        SET title = EXCLUDED.title,
            subtitle = EXCLUDED.subtitle,
            summary = EXCLUDED.summary,
            description = EXCLUDED.description,
            updated_at = now()
    RETURNING id, slug, sales_starts_at, sales_ends_at
)
-- Types VIP et Standard avec prix adultes/enfants
INSERT INTO ticket_types (
    event_id, name, description, currency,
    base_price, service_fee, vat_rate, age_category,
    sales_start, sales_end, min_per_order, max_per_order,
    metadata
)
-- VIP Adulte
SELECT evt.id,
       'VIP',
       'Billet VIP pour adulte. Tribune VIP, restauration incluse, accès parking.',
       'MGA',
       80000,
       4000,
       20,
       'adult'::age_category_enum,
       evt.sales_starts_at,
       evt.sales_ends_at,
       1,
       8,
       '{"vip":true,"vip_stand":true,"food":true,"parking":true,"age_min":18}'::jsonb
FROM event_sport evt
WHERE NOT EXISTS (
    SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'VIP' AND tt.age_category = 'adult'
)
UNION ALL
-- VIP Enfant
SELECT evt.id,
       'VIP',
       'Billet VIP pour enfant. Tribune VIP adaptée, restauration incluse.',
       'MGA',
       40000,
       2000,
       20,
       'child'::age_category_enum,
       evt.sales_starts_at,
       evt.sales_ends_at,
       1,
       8,
       '{"vip":true,"vip_stand":true,"food":true,"age_min":3,"age_max":17,"requires_accompaniment":true}'::jsonb
FROM event_sport evt
WHERE NOT EXISTS (
    SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'VIP' AND tt.age_category = 'child'
)
UNION ALL
-- Standard Adulte
SELECT evt.id,
       'Standard',
       'Billet Standard pour adulte. Accès général avec bon placement.',
       'MGA',
       30000,
       1500,
       20,
       'adult'::age_category_enum,
       evt.sales_starts_at,
       evt.sales_ends_at,
       1,
       15,
       '{"standard":true,"general_access":true,"age_min":18}'::jsonb
FROM event_sport evt
WHERE NOT EXISTS (
    SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Standard' AND tt.age_category = 'adult'
)
UNION ALL
-- Standard Enfant
SELECT evt.id,
       'Standard',
       'Billet Standard pour enfant. Accès général avec bon placement.',
       'MGA',
       15000,
       750,
       20,
       'child'::age_category_enum,
       evt.sales_starts_at,
       evt.sales_ends_at,
       1,
       15,
       '{"standard":true,"general_access":true,"age_min":3,"age_max":17,"requires_accompaniment":true}'::jsonb
FROM event_sport evt
WHERE NOT EXISTS (
    SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Standard' AND tt.age_category = 'child'
);

-- Inventaire pour les billets sportifs
WITH tt_sport AS (
    SELECT tt.id, tt.name, tt.age_category
    FROM ticket_types tt
    JOIN events ev ON ev.id = tt.event_id
    WHERE ev.slug = 'festival-sportif-vip-standard'
)
INSERT INTO ticket_inventory (ticket_type_id, total_quantity, reserved_quantity, sold_quantity)
SELECT tt.id,
       CASE 
           WHEN tt.name = 'VIP' AND tt.age_category = 'adult' THEN 80
           WHEN tt.name = 'VIP' AND tt.age_category = 'child' THEN 40
           WHEN tt.name = 'Standard' AND tt.age_category = 'adult' THEN 250
           WHEN tt.name = 'Standard' AND tt.age_category = 'child' THEN 130
           ELSE 0
       END,
       0,
       0
FROM tt_sport tt
ON CONFLICT (ticket_type_id) DO NOTHING;

-- ===================================================================
-- Données de test pour l'historique d'achat, statistiques et historique financier
-- ===================================================================

-- Récupérer les utilisateurs réels avec leurs emails
WITH test_users AS (
    SELECT id, email, first_name, last_name FROM users 
    WHERE email IN ('fifalianavalea@gmail.com', 'ainaravonihanitrarivo@gmail.com')
    ORDER BY id
),
test_events AS (
    SELECT e.id, e.title, e.slug, e.starts_at, e.created_at
    FROM events e
    WHERE e.starts_at > now() - INTERVAL '6 months'
    ORDER BY e.created_at DESC
    LIMIT 10
),
test_ticket_types AS (
    SELECT tt.id, tt.event_id, tt.name, tt.base_price, tt.age_category, e.title as event_title
    FROM ticket_types tt
    JOIN test_events e ON e.id = tt.event_id
    WHERE tt.age_category IN ('adult', 'all')
    ORDER BY tt.event_id, tt.id
)
-- Créer des commandes réelles avec différents statuts pour chaque utilisateur
INSERT INTO orders (
    user_id, status, total_amount, discount_amount, currency, 
    promotion_code, payment_due_at, created_at, updated_at
)
SELECT 
    tu.id,
    CASE 
        -- fifalianavalea@gmail.com (organisateur) : 4 commandes payées, 1 remboursée
        WHEN tu.email = 'fifalianavalea@gmail.com' AND row_number() OVER (PARTITION BY tu.id ORDER BY random()) <= 4 THEN 'paid'::order_status_enum
        WHEN tu.email = 'fifalianavalea@gmail.com' AND row_number() OVER (PARTITION BY tu.id ORDER BY random()) = 5 THEN 'refunded'::order_status_enum
        -- ainaravonihanitrarivo@gmail.com (user) : 5 commandes payées, 1 remboursée, 1 annulée
        WHEN tu.email = 'ainaravonihanitrarivo@gmail.com' AND row_number() OVER (PARTITION BY tu.id ORDER BY random()) <= 5 THEN 'paid'::order_status_enum
        WHEN tu.email = 'ainaravonihanitrarivo@gmail.com' AND row_number() OVER (PARTITION BY tu.id ORDER BY random()) = 6 THEN 'refunded'::order_status_enum
        WHEN tu.email = 'ainaravonihanitrarivo@gmail.com' AND row_number() OVER (PARTITION BY tu.id ORDER BY random()) = 7 THEN 'cancelled'::order_status_enum
        ELSE 'paid'::order_status_enum
    END,
    CASE 
        WHEN tu.email = 'fifalianavalea@gmail.com' THEN (random() * 400000 + 150000)::numeric(12,2) -- 150k-550k
        WHEN tu.email = 'ainaravonihanitrarivo@gmail.com' THEN (random() * 300000 + 100000)::numeric(12,2) -- 100k-400k
        ELSE (random() * 200000 + 50000)::numeric(12,2)
    END,
    CASE WHEN random() > 0.75 THEN (random() * 30000 + 5000)::numeric(12,2) ELSE 0 END,
    'MGA',
    CASE WHEN random() > 0.85 THEN 'PROMO10' ELSE NULL END,
    now() + INTERVAL '15 minutes',
    CASE 
        WHEN tu.email = 'fifalianavalea@gmail.com' THEN now() - (random() * INTERVAL '60 days' + INTERVAL '30 days') -- 30-90 jours
        WHEN tu.email = 'ainaravonihanitrarivo@gmail.com' THEN now() - (random() * INTERVAL '45 days' + INTERVAL '15 days') -- 15-60 jours
        ELSE now() - (random() * INTERVAL '30 days')
    END,
    CASE 
        WHEN tu.email = 'fifalianavalea@gmail.com' THEN now() - (random() * INTERVAL '60 days' + INTERVAL '30 days')
        WHEN tu.email = 'ainaravonihanitrarivo@gmail.com' THEN now() - (random() * INTERVAL '45 days' + INTERVAL '15 days')
        ELSE now() - (random() * INTERVAL '30 days')
    END
FROM test_users tu
CROSS JOIN generate_series(1, 
    CASE 
        WHEN tu.email = 'fifalianavalea@gmail.com' THEN 5
        WHEN tu.email = 'ainaravonihanitrarivo@gmail.com' THEN 7
        ELSE 3
    END
)
ON CONFLICT DO NOTHING;

-- Créer des order_items pour chaque commande avec des événements réels
WITH orders_with_items AS (
    SELECT o.id AS order_id, o.user_id, o.status, o.total_amount, o.created_at,
           ROW_NUMBER() OVER (PARTITION BY o.user_id ORDER BY o.created_at DESC) as rn
    FROM orders o
    WHERE o.status IN ('paid'::order_status_enum, 'refunded'::order_status_enum, 'cancelled'::order_status_enum)
),
ticket_types_for_orders AS (
    SELECT tt.id, tt.event_id, tt.base_price, tt.age_category, e.title as event_title
    FROM ticket_types tt
    JOIN events e ON e.id = tt.event_id
    WHERE tt.age_category IN ('adult', 'all')
      AND e.starts_at > now() - INTERVAL '6 months'
    ORDER BY tt.event_id, tt.id
)
INSERT INTO order_items (
    order_id, ticket_type_id, quantity, unit_price, service_fee, 
    vat_amount, total_amount, created_at
)
SELECT 
    owi.order_id,
    tto.id,
    CASE 
        -- Quantités réalistes selon le montant de la commande
        WHEN owi.total_amount > 400000 THEN (random() * 2 + 3)::integer -- 3-5 billets
        WHEN owi.total_amount > 200000 THEN (random() * 2 + 2)::integer -- 2-4 billets
        ELSE (random() * 2 + 1)::integer -- 1-3 billets
    END,
    tto.base_price,
    0, -- Pas de frais de service pour le moment
    0, -- Pas de TVA pour le moment
    LEAST(
        tto.base_price * CASE 
            WHEN owi.total_amount > 400000 THEN (random() * 2 + 3)::integer
            WHEN owi.total_amount > 200000 THEN (random() * 2 + 2)::integer
            ELSE (random() * 2 + 1)::integer
        END,
        owi.total_amount * 1.1 -- Ne pas dépasser le montant de la commande de plus de 10%
    )::numeric(12,2),
    owi.created_at
FROM orders_with_items owi
CROSS JOIN LATERAL (
    SELECT * FROM ticket_types_for_orders 
    WHERE base_price > 0
    ORDER BY random() 
    LIMIT 1
) tto
ON CONFLICT DO NOTHING;

-- Créer des factures (ticket_invoices) pour les commandes confirmées
INSERT INTO ticket_invoices (
    order_id, customer_id, currency, subtotal_amount, tax_amount, 
    total_amount, status, issued_at, due_at, paid_at, created_at, updated_at
)
SELECT 
    o.id,
    o.user_id,
    o.currency,
    o.total_amount - o.discount_amount,
    0, -- Pas de taxe pour le moment
    o.total_amount - o.discount_amount,
    CASE 
        WHEN o.status = 'paid'::order_status_enum THEN 'paid'::payment_status_enum
        WHEN o.status = 'refunded'::order_status_enum THEN 'refunded'::payment_status_enum
        ELSE 'failed'::payment_status_enum
    END,
    o.created_at,
    o.payment_due_at,
    CASE WHEN o.status = 'paid'::order_status_enum THEN o.created_at + INTERVAL '5 minutes' ELSE NULL END,
    o.created_at,
    o.updated_at
FROM orders o
WHERE o.status IN ('paid'::order_status_enum, 'refunded'::order_status_enum)
ON CONFLICT DO NOTHING;

-- Créer des paiements (ticket_payments) pour les factures payées avec des références réalistes
INSERT INTO ticket_payments (
    invoice_id, provider, provider_reference, status, amount, 
    currency, paid_at, metadata, created_at, updated_at
)
SELECT 
    ti.id,
    CASE 
        -- Répartition réaliste des méthodes de paiement à Madagascar
        WHEN (random() * 100) < 50 THEN 'orange' -- 50% Orange Money
        WHEN (random() * 100) < 80 THEN 'airtel' -- 30% Airtel Money
        ELSE 'telma' -- 20% Telma (M-Vola)
    END,
    CASE 
        WHEN (random() * 100) < 50 THEN 'ORANGE-' || LPAD((random() * 999999)::integer::text, 8, '0')
        WHEN (random() * 100) < 80 THEN 'AIRTEL-' || LPAD((random() * 999999)::integer::text, 8, '0')
        ELSE 'TELMA-' || LPAD((random() * 999999)::integer::text, 8, '0')
    END,
    'paid'::payment_status_enum,
    ti.total_amount,
    ti.currency,
    ti.paid_at,
    jsonb_build_object(
        'method', 'mobile_money',
        'transaction_id', 'TXN-' || LPAD((random() * 99999999)::integer::text, 10, '0'),
        'phone', '+261' || LPAD((300000000 + random() * 99999999)::integer::text, 9, '0')
    ),
    ti.created_at,
    ti.updated_at
FROM ticket_invoices ti
WHERE ti.status = 'paid'
ON CONFLICT DO NOTHING;

-- Créer des tickets pour les order_items des commandes confirmées
WITH confirmed_order_items AS (
    SELECT oi.id, oi.order_id, oi.ticket_type_id, oi.quantity, o.id AS order_id_real
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    WHERE o.status = 'paid'::order_status_enum
),
ticket_owners AS (
    SELECT o.user_id, oi.id AS order_item_id
    FROM orders o
    JOIN order_items oi ON oi.order_id = o.id
    WHERE o.status = 'paid'::order_status_enum
)
INSERT INTO tickets (
    order_item_id, ticket_type_id, owner_user_id, status, qr_code, issued_at, metadata
)
SELECT 
    coi.id,
    coi.ticket_type_id,
    to_owner.user_id,
    'valid',
    'TICKET-' || LPAD(coi.id::text, 8, '0') || '-' || LPAD((generate_series(1, coi.quantity))::text, 4, '0'),
    (SELECT created_at FROM orders WHERE id = coi.order_id_real),
    jsonb_build_object('order_id', coi.order_id_real, 'issued_via', 'system')
FROM confirmed_order_items coi
JOIN ticket_owners to_owner ON to_owner.order_item_id = coi.id
CROSS JOIN generate_series(1, coi.quantity)
ON CONFLICT DO NOTHING;

-- Créer des transactions wallet pour les utilisateurs réels
WITH user_wallets AS (
    SELECT w.id, w.user_id, u.email
    FROM wallets w
    JOIN users u ON u.id = w.user_id
    WHERE u.email IN ('fifalianavalea@gmail.com', 'ainaravonihanitrarivo@gmail.com')
)
INSERT INTO wallet_transactions (
    wallet_id, transaction_type, status, amount, points_delta, 
    description, related_entity, related_id, created_at
)
WITH transaction_types AS (
    SELECT 
        w.id as wallet_id,
        w.user_id,
        w.email,
        CASE 
            -- fifalianavalea@gmail.com (organisateur) : plus de recharges
            WHEN w.email = 'fifalianavalea@gmail.com' THEN
                CASE WHEN (random() * 100) < 60 THEN 'credit'::wallet_transaction_type_enum
                     WHEN (random() * 100) < 85 THEN 'debit'::wallet_transaction_type_enum
                     ELSE 'credit'::wallet_transaction_type_enum
                END
            -- ainaravonihanitrarivo@gmail.com (user) : plus d'achats
            WHEN w.email = 'ainaravonihanitrarivo@gmail.com' THEN
                CASE WHEN (random() * 100) < 70 THEN 'debit'::wallet_transaction_type_enum
                     WHEN (random() * 100) < 90 THEN 'credit'::wallet_transaction_type_enum
                     ELSE 'credit'::wallet_transaction_type_enum
                END
            -- Autres : mix
            ELSE
                CASE WHEN (random() * 100) < 50 THEN 'debit'::wallet_transaction_type_enum
                     WHEN (random() * 100) < 80 THEN 'credit'::wallet_transaction_type_enum
                     ELSE 'credit'::wallet_transaction_type_enum
                END
        END as txn_type
    FROM user_wallets w
    CROSS JOIN generate_series(1, 
        CASE 
            WHEN w.email = 'fifalianavalea@gmail.com' THEN 12
            WHEN w.email = 'ainaravonihanitrarivo@gmail.com' THEN 10
            ELSE 6
        END
    )
)
SELECT 
    tt.wallet_id,
    tt.txn_type,
    'completed',
    CASE 
        -- Recharge (credit) : montant positif
        WHEN tt.txn_type = 'credit'::wallet_transaction_type_enum THEN
            CASE 
                WHEN tt.email = 'fifalianavalea@gmail.com' THEN (random() * 800000 + 200000)::numeric(14,2) -- 200k-1M
                WHEN tt.email = 'ainaravonihanitrarivo@gmail.com' THEN (random() * 400000 + 100000)::numeric(14,2) -- 100k-500k
                ELSE (random() * 300000 + 50000)::numeric(14,2) -- 50k-350k
            END
        -- Achat ou remboursement : montant négatif
        ELSE 
            -(random() * 250000 + 50000)::numeric(14,2) -- -50k à -300k
    END,
    CASE 
        -- Recharge (credit) : points positifs
        WHEN tt.txn_type = 'credit'::wallet_transaction_type_enum THEN
            CASE 
                WHEN tt.email = 'fifalianavalea@gmail.com' THEN (random() * 800 + 200)::integer -- 200-1000 points
                WHEN tt.email = 'ainaravonihanitrarivo@gmail.com' THEN (random() * 400 + 100)::integer -- 100-500 points
                ELSE (random() * 300 + 50)::integer -- 50-350 points
            END
        -- Achat ou remboursement : points négatifs
        ELSE 
            -(random() * 250 + 50)::integer -- -50 à -300 points
    END,
    CASE 
        -- Recharge (credit)
        WHEN tt.txn_type = 'credit'::wallet_transaction_type_enum THEN
            CASE 
                WHEN (random() * 100) < 60 THEN 'Recharge wallet via Mobile Money'
                WHEN (random() * 100) < 80 THEN 'Recharge wallet via virement bancaire'
                ELSE 'Bonus fidélité'
            END
        -- Achat (debit)
        WHEN tt.txn_type = 'debit'::wallet_transaction_type_enum THEN 'Achat de billets'
        -- Remboursement (credit)
        ELSE 'Remboursement événement annulé'
    END,
    CASE 
        WHEN tt.txn_type = 'debit'::wallet_transaction_type_enum OR tt.txn_type = 'credit'::wallet_transaction_type_enum THEN 'order'
        ELSE NULL
    END,
    CASE 
        WHEN tt.txn_type = 'debit'::wallet_transaction_type_enum OR tt.txn_type = 'credit'::wallet_transaction_type_enum THEN 
            (SELECT o.id FROM orders o WHERE o.user_id = tt.user_id ORDER BY random() LIMIT 1)
        ELSE NULL
    END,
    CASE 
        WHEN tt.email = 'fifalianavalea@gmail.com' THEN now() - (random() * INTERVAL '60 days' + INTERVAL '30 days')
        WHEN tt.email = 'ainaravonihanitrarivo@gmail.com' THEN now() - (random() * INTERVAL '45 days' + INTERVAL '15 days')
        ELSE now() - (random() * INTERVAL '30 days')
    END
FROM transaction_types tt
ON CONFLICT DO NOTHING;

-- Mettre à jour les soldes des wallets
UPDATE wallets w
SET balance = COALESCE((
    SELECT SUM(amount)
    FROM wallet_transactions wt
    WHERE wt.wallet_id = w.id AND wt.status = 'completed'
), 0),
points_balance = COALESCE((
    SELECT SUM(points_delta)
    FROM wallet_transactions wt
    WHERE wt.wallet_id = w.id AND wt.status = 'completed'
), 0)
WHERE w.user_id IN (
    SELECT id FROM users 
    WHERE email IN ('fifalianavalea@gmail.com', 'ainaravonihanitrarivo@gmail.com')
);

-- Créer l'historique des statuts de commande
INSERT INTO order_status_history (
    order_id, status_from, status_to, changed_by, changed_at, metadata
)
SELECT 
    o.id,
    'pending',
    o.status,
    o.user_id,
    o.created_at,
    jsonb_build_object('source', 'system', 'reason', 'initial_status')
FROM orders o
WHERE o.status IN ('paid'::order_status_enum, 'refunded'::order_status_enum, 'cancelled'::order_status_enum)
ON CONFLICT DO NOTHING;

-- Créer l'historique des paiements
INSERT INTO ticket_payment_history (
    payment_id, status_from, status_to, changed_at, metadata
)
SELECT 
    tp.id,
    'initiated',
    tp.status,
    tp.created_at,
    jsonb_build_object('provider', tp.provider, 'reference', tp.provider_reference)
FROM ticket_payments tp
ON CONFLICT DO NOTHING;

COMMIT;

