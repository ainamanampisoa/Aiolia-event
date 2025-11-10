-- Aiolia Event Platform - Données d’amorçage
-- Ce script insère les valeurs initiales nécessaires au fonctionnement de la plateforme.

SET search_path TO aiolia, public;

-- Rôles par défaut ------------------------------------------------------

INSERT INTO user_roles (code, label, description)
VALUES
    ('admin', 'Administrateur', 'Supervision complète du système'),
    ('organizer', 'Organisateur', 'Gestion des événements et des ventes'),
    ('user', 'Participant', 'Utilisateur front-office standard')
ON CONFLICT (code) DO NOTHING;

-- Canaux de notification ------------------------------------------------

INSERT INTO notification_channels (code, description)
VALUES
    ('email', 'Envoi par email'),
    ('web_push', 'Notification push web'),
    ('sms', 'Notification SMS'),
    ('in_app', 'Centre de notifications interne')
ON CONFLICT (code) DO NOTHING;

-- Catégories d’événements ----------------------------------------------

INSERT INTO event_categories (slug, label, description, icon_name, display_order)
VALUES
    ('concert', 'Concert & Musique', 'Concerts live, DJ sets et expériences musicales', 'music', 10),
    ('sport', 'Sport & Tournois', 'Matchs, compétitions, tournois amateurs et pro', 'sports', 20),
    ('conference', 'Conférences & Talks', 'Conférences, panels, séminaires inspirants', 'mic', 30),
    ('festival', 'Festivals & Foires', 'Festivals culturels, gastronomiques, artisanat', 'festival', 40),
    ('business', 'Business & Networking', 'Meetups, networking, ateliers professionnels', 'briefcase', 50),
    ('gaming', 'Esport & Gaming', 'LAN party, tournois esport, communauté gaming', 'gamepad', 60),
    ('art', 'Arts & Expositions', 'Expositions, théâtre, performance artistique', 'palette', 70),
    ('family', 'Famille & Enfants', 'Sorties familiales, activités enfants', 'family', 80),
    ('education', 'Éducation & Formation', 'Formations, bootcamps, ateliers pédagogiques', 'book', 90),
    ('lifestyle', 'Lifestyle & Bien-être', 'Bien-être, yoga, food, expériences lifestyle', 'spa', 100)
ON CONFLICT (slug) DO UPDATE
SET label = EXCLUDED.label,
    description = EXCLUDED.description,
    icon_name = EXCLUDED.icon_name,
    display_order = EXCLUDED.display_order;

-- Tags initiaux ---------------------------------------------------------

INSERT INTO event_tags (slug, label)
VALUES
    ('nouveaute', 'Nouveauté'),
    ('tendance', 'Tendance'),
    ('vip', 'VIP'),
    ('gratuit', 'Gratuit'),
    ('immersif', 'Expérience immersive')
ON CONFLICT (slug) DO NOTHING;

-- Utilisateur administrateur par défaut ---------------------------------

WITH upsert_admin AS (
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
        'admin@aiolia-event.com',
        crypt('AdminChangeMe!2025', gen_salt('bf')),
        'Admin',
        'Principal',
        'active',
        TRUE,
        'fr-FR',
        now()
    )
    ON CONFLICT (email)
    DO UPDATE SET
        updated_at = now(),
        status = 'active'
    RETURNING user_id
)
INSERT INTO user_role_assignments (user_id, role_id, assigned_at, assigned_by)
SELECT upsert_admin.user_id, roles.role_id, now(), upsert_admin.user_id
FROM upsert_admin
JOIN user_roles AS roles ON roles.code = 'admin'
ON CONFLICT (user_id, role_id) DO NOTHING;

-- Portefeuille initial pour l’administrateur ---------------------------

INSERT INTO wallets (user_id, currency, balance, points_balance)
SELECT user_id, 'MGA', 0, 0
FROM users
WHERE email = 'admin@aiolia-event.com'
ON CONFLICT (user_id) DO NOTHING;

-- Préférences de notification par défaut --------------------------------

INSERT INTO user_notification_preferences (user_id, channel_code, enabled)
SELECT u.user_id, c.code, TRUE
FROM users u
JOIN notification_channels c ON TRUE
WHERE u.email = 'admin@aiolia-event.com'
ON CONFLICT (user_id, channel_code) DO NOTHING;

-- Venue & Événement exemple --------------------------------------------

WITH organizer_user AS (
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
        'organizer@aiolia-event.com',
        crypt('OrganizerChangeMe!2025', gen_salt('bf')),
        'Orga',
        'Master',
        'active',
        TRUE,
        'fr-FR',
        now()
    )
    ON CONFLICT (email)
    DO UPDATE SET
        updated_at = now(),
        status = 'active'
    RETURNING user_id
),
organizer_profile AS (
    INSERT INTO organizer_profiles (
        user_id,
        display_name,
        legal_name,
        tax_number,
        support_email,
        support_phone,
        website_url,
        biography,
        verification_status,
        onboarding_completed_at
    )
    SELECT
        ou.user_id,
        'Aiolia Events Studio',
        'Aiolia Events SARL',
        'TIN-2025-001',
        'support@aiolia-event.com',
        '+261340000000',
        'https://aiolia-event.com',
        'Organisation fictive de démonstration pour la plateforme Aiolia.',
        'verified',
        now()
    FROM organizer_user ou
    ON CONFLICT (user_id) DO UPDATE
        SET updated_at = now(),
            verification_status = 'verified'
    RETURNING organizer_id, user_id
),
assign_role AS (
    INSERT INTO user_role_assignments (user_id, role_id, assigned_at, assigned_by)
    SELECT op.user_id, r.role_id, now(), (SELECT user_id FROM users WHERE email = 'admin@aiolia-event.com')
    FROM organizer_profile op
    JOIN user_roles r ON r.code = 'organizer'
    ON CONFLICT (user_id, role_id) DO NOTHING
    RETURNING user_id
),
venue_insert AS (
    INSERT INTO venues (
        organizer_id,
        name,
        description,
        address_line1,
        city,
        region,
        country_code,
        latitude,
        longitude,
        capacity
    )
    SELECT
        op.organizer_id,
        'Arena Antananarivo',
        'Salle polyvalente moderne pour concerts et conventions.',
        'Route du By-Pass, Zone Galaxy',
        'Antananarivo',
        'Analamanga',
        'MG',
        -18.879190,
        47.507905,
        5000
    FROM organizer_profile op
    RETURNING venue_id, organizer_id
),
event_insert AS (
    INSERT INTO events (
        organizer_id,
        primary_category_id,
        venue_id,
        slug,
        title,
        subtitle,
        summary,
        description,
        cover_image_url,
        status,
        visibility,
        capacity,
        timezone,
        starts_at,
        ends_at,
        sales_starts_at,
        sales_ends_at,
        language_code,
        is_featured
    )
    SELECT
        v.organizer_id,
        (SELECT category_id FROM event_categories WHERE slug = 'concert'),
        v.venue_id,
        'aiolia-live-experience',
        'Aiolia Live Experience',
        'La soirée immersive de l’année',
        'Une expérience musicale interactive mêlant DJs, hologrammes et mini-jeux.',
        'Une immersion totale dans l’univers Aiolia avec animations, stands partenaires, zones VR et performances surprises.',
        'https://cdn.aiolia-event.com/events/cover-aiolia-live.jpg',
        'published',
        'public',
        3500,
        'Indian/Antananarivo',
        now() + INTERVAL '45 days',
        now() + INTERVAL '45 days 6 hours',
        now() - INTERVAL '10 days',
        now() + INTERVAL '44 days',
        'fr-FR',
        TRUE
    FROM venue_insert v
    RETURNING event_id, organizer_id
),
session_insert AS (
    INSERT INTO event_sessions (
        event_id,
        title,
        starts_at,
        ends_at,
        capacity,
        location_override
    )
    SELECT
        e.event_id,
        'Session principale',
        e.starts_at,
        e.ends_at,
        3500,
        NULL
    FROM event_insert e
    RETURNING session_id, event_id
)
INSERT INTO ticket_types (
    event_id,
    session_id,
    name,
    description,
    currency,
    base_price,
    service_fee,
    vat_rate,
    quantity_total,
    sales_start,
    sales_end,
    min_per_order,
    max_per_order,
    status,
    is_transferable
)
SELECT
    e.event_id,
    s.session_id,
    tt.name,
    tt.description,
    tt.currency,
    tt.base_price,
    tt.service_fee,
    tt.vat_rate,
    tt.quantity_total,
    tt.sales_start,
    tt.sales_end,
    tt.min_per_order,
    tt.max_per_order,
    tt.status,
    tt.is_transferable
FROM event_insert e
CROSS JOIN session_insert s
CROSS JOIN LATERAL (
    VALUES
        ('Pass Early Bird', 'Tarif promotionnel limité aux 500 premiers billets', 'MGA', 35000, 1500, 20, 500, now() - INTERVAL '10 days', now() + INTERVAL '15 days', 1, 4, 'on_sale', TRUE),
        ('Pass Premium', 'Accès premium avec zone lounge et goodies', 'MGA', 85000, 2500, 20, 1200, now() - INTERVAL '5 days', e.starts_at - INTERVAL '1 day', 1, 6, 'on_sale', TRUE),
        ('Pass Backstage', 'Rencontre avec les artistes + accès coulisses', 'MGA', 150000, 3500, 20, 150, now() - INTERVAL '5 days', e.starts_at - INTERVAL '2 days', 1, 2, 'on_sale', FALSE)
) AS tt(name, description, currency, base_price, service_fee, vat_rate, quantity_total, sales_start, sales_end, min_per_order, max_per_order, status, is_transferable)
ON CONFLICT DO NOTHING;

-- Quotas de billets ---------------------------------------------------------------

WITH event_ref AS (
    SELECT event_id, capacity
    FROM events
    WHERE slug = 'aiolia-live-experience'
),
quota_global AS (
    INSERT INTO ticket_quota_groups (
        event_id,
        name,
        description,
        capacity_total,
        capacity_reserved,
        capacity_sold,
        per_user_limit,
        enforce_limits
    )
    SELECT
        event_id,
        'Quota global',
        'Limite globale de billets disponibles pour l’événement',
        COALESCE(capacity, 0),
        0,
        0,
        6,
        TRUE
    FROM event_ref
    ON CONFLICT (event_id, name) DO UPDATE
        SET capacity_total = EXCLUDED.capacity_total,
            per_user_limit = EXCLUDED.per_user_limit,
            description = EXCLUDED.description,
            updated_at = now()
    RETURNING quota_group_id, event_id
),
quota_vip AS (
    INSERT INTO ticket_quota_groups (
        event_id,
        name,
        description,
        capacity_total,
        capacity_reserved,
        capacity_sold,
        per_user_limit,
        enforce_limits
    )
    SELECT
        event_id,
        'Quota premium',
        'Limite dédiée aux billets premium et backstage',
        1500,
        0,
        0,
        2,
        TRUE
    FROM event_ref
    ON CONFLICT (event_id, name) DO UPDATE
        SET capacity_total = EXCLUDED.capacity_total,
            per_user_limit = EXCLUDED.per_user_limit,
            description = EXCLUDED.description,
            updated_at = now()
    RETURNING quota_group_id, event_id
)
INSERT INTO ticket_quota_links (quota_group_id, ticket_type_id, weight)
SELECT q.quota_group_id, tt.ticket_type_id,
       CASE
           WHEN q.name = 'Quota global' THEN 1
           WHEN q.name = 'Quota premium' AND tt.name = 'Pass Backstage' THEN 2
           ELSE 1
       END
FROM (
    SELECT quota_group_id, event_id, 'Quota global'::TEXT AS name FROM quota_global
    UNION ALL
    SELECT quota_group_id, event_id, 'Quota premium'::TEXT AS name FROM quota_vip
) q
JOIN ticket_types tt ON tt.event_id = q.event_id
WHERE
    (q.name = 'Quota global')
    OR (q.name = 'Quota premium' AND tt.name IN ('Pass Premium', 'Pass Backstage'))
ON CONFLICT (quota_group_id, ticket_type_id) DO UPDATE
    SET weight = EXCLUDED.weight,
        created_at = LEAST(ticket_quota_links.created_at, now());

-- Règles dynamiques de prix --------------------------------------------------------

INSERT INTO ticket_pricing_rules (
    ticket_type_id,
    rule_type,
    threshold_quantity,
    price,
    discount_percent,
    starts_at,
    ends_at,
    metadata
)
SELECT
    tt.ticket_type_id,
    rules.rule_type,
    rules.threshold_quantity,
    rules.price,
    rules.discount_percent,
    rules.starts_at,
    rules.ends_at,
    rules.metadata
FROM ticket_types tt
JOIN events e ON e.event_id = tt.event_id
CROSS JOIN LATERAL (
    VALUES
        ('tier', 300, 32000::NUMERIC, NULL::NUMERIC, NULL::TIMESTAMPTZ, NULL::TIMESTAMPTZ, jsonb_build_object('label', 'Super Early')),
        ('time_window', NULL, NULL, 10, e.sales_starts_at, e.sales_starts_at + INTERVAL '7 days', jsonb_build_object('label', 'Lancement -10%')),
        ('tier', 1000, 38000::NUMERIC, NULL::NUMERIC, NULL::TIMESTAMPTZ, NULL::TIMESTAMPTZ, jsonb_build_object('label', 'Prix standard'))
) AS rules(rule_type, threshold_quantity, price, discount_percent, starts_at, ends_at, metadata)
WHERE tt.name = 'Pass Early Bird'
ON CONFLICT DO NOTHING;

-- Promotion d’exemple --------------------------------------------------------------

INSERT INTO promotions (
    code,
    name,
    description,
    discount_type,
    discount_value,
    max_discount_amount,
    max_usage_total,
    max_usage_per_user,
    starts_at,
    ends_at,
    is_stackable,
    status,
    metadata
)
VALUES (
    'WELCOME10',
    'Bienvenue sur Aiolia',
    'Remise de 10% sur la première commande des nouveaux utilisateurs',
    'percent',
    10,
    NULL,
    1000,
    1,
    now(),
    now() + INTERVAL '6 months',
    FALSE,
    'active',
    jsonb_build_object('conditions', 'Réservé aux nouveaux utilisateurs')
)
ON CONFLICT (code) DO NOTHING;

INSERT INTO promotion_targets (promotion_id, event_id, ticket_type_id)
SELECT p.promotion_id, e.event_id, NULL
FROM promotions p
JOIN events e ON e.slug = 'aiolia-live-experience'
WHERE p.code = 'WELCOME10'
ON CONFLICT DO NOTHING;

-- Préférences front-office par défaut ---------------------------------------------

INSERT INTO user_preferences (user_id, preference_key, preference_value)
SELECT u.user_id, 'ui.theme', to_jsonb('dark'::text)
FROM users u
WHERE u.email = 'admin@aiolia-event.com'
ON CONFLICT (user_id, preference_key)
DO UPDATE SET preference_value = EXCLUDED.preference_value, updated_at = now();

INSERT INTO user_preferences (user_id, preference_key, preference_value)
SELECT u.user_id, 'ui.theme', to_jsonb('dark'::text)
FROM users u
WHERE u.email = 'organizer@aiolia-event.com'
ON CONFLICT (user_id, preference_key)
DO UPDATE SET preference_value = EXCLUDED.preference_value, updated_at = now();

-- Validation de compte organisateur -----------------------------------------------

INSERT INTO user_validation_requests (
    user_id,
    requested_at,
    status,
    reviewer_user_id,
    reviewed_at,
    rejection_reason,
    additional_documents,
    metadata
)
SELECT
    op.user_id,
    now() - INTERVAL '2 days',
    'approved',
    admin_user.user_id,
    now() - INTERVAL '1 day',
    NULL,
    jsonb_build_object('business_license', 'licence_orga.pdf'),
    jsonb_build_object('source', 'seed_data')
FROM organizer_profiles op
JOIN users organizer_user ON organizer_user.user_id = op.user_id
JOIN users admin_user ON admin_user.email = 'admin@aiolia-event.com'
ON CONFLICT DO NOTHING;

-- Statistiques utilisateur initiales -----------------------------------------------

INSERT INTO user_statistics (user_id)
SELECT user_id FROM users
ON CONFLICT (user_id) DO NOTHING;

-- Fin du script d’amorçage ---------------------------------------------------------
