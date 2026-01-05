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
VALUES ('concert', 'Concert', 'Concerts, soirées live, DJ sets et showcases musicaux', 1)
ON CONFLICT (slug) DO NOTHING;

INSERT INTO event_categories (slug, label, description, display_order)
VALUES ('business', 'Business', 'Conférences, séminaires, formations et workshops professionnels', 2)
ON CONFLICT (slug) DO NOTHING;

INSERT INTO event_categories (slug, label, description, display_order)
VALUES ('festival', 'Festival', 'Festivals multi-activités, gastronomie, artisanat et cinéma', 3)
ON CONFLICT (slug) DO NOTHING;

INSERT INTO event_categories (slug, label, description, display_order)
VALUES ('culture', 'Culture', 'Spectacles de danse, théâtre, performances culturelles et artistiques', 4)
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
        -- Antananarivo (capitale) - Lieux de concerts réels
        (
            'cafe-de-la-gare',
            'Café de la Gare',
            'Salle iconique du centre-ville pour concerts intimistes.',
            'Avenue de l''Indépendance, Gare de Soarano',
            'Quartier Analakely',
            'Antananarivo',
            'Antananarivo',
            '101',
            'MG',
            -18.9031,
            47.5211,
            'Indian/Antananarivo',
            400
        ),
        (
            'le-glacier',
            'Le Glacier',
            'Salle de concert emblématique d''Analakely pour concerts live et soirées dansantes.',
            'Avenue de l''Indépendance',
            'Analakely',
            'Antananarivo',
            'Antananarivo',
            '101',
            'MG',
            -18.9144,
            47.5181,
            'Indian/Antananarivo',
            450
        ),
        (
            'kudeta-urban-club',
            'Kudeta Urban Club',
            'Club urbain moderne au Carlton Hotel pour soirées électro et dancehall.',
            'Carlton Hotel, Rue Stibbe',
            'Anosy',
            'Antananarivo',
            'Antananarivo',
            '101',
            'MG',
            -18.914398,
            47.51806,
            'Indian/Antananarivo',
            300
        ),
        (
            'jaos-pub',
            'Cabaret Jao''s Pub',
            'Cabaret convivial à Ambohipo pour concerts acoustiques et folk.',
            'Ambohipo',
            NULL,
            'Antananarivo',
            'Antananarivo',
            '101',
            'MG',
            -18.8720,
            47.5570,
            'Indian/Antananarivo',
            200
        ),
        (
            'restaurant-taxi-be',
            'Restaurant Taxi Be',
            'Espace concert à Antanimena pour rock et metal.',
            'Antanimena',
            NULL,
            'Antananarivo',
            'Antananarivo',
            '101',
            'MG',
            -18.9200,
            47.5300,
            'Indian/Antananarivo',
            400
        ),
        (
            'le-louvre-hotel-spa',
            'Le Louvre Hotel & Spa',
            'Hôtel de prestige à Antaninarenina pour concerts jazz et piano bar.',
            '4, Place P. Tsiranana',
            'Antaninarenina',
            'Antananarivo',
            'Antananarivo',
            '101',
            'MG',
            -18.8748,
            47.54729,
            'Indian/Antananarivo',
            250
        ),
        (
            'espace-nambinintsoa',
            'Espace Nambinintsoa',
            'Grand espace en plein air à Talatamaty pour concerts pop et hip-hop.',
            'Talatamaty',
            NULL,
            'Antananarivo',
            'Antananarivo',
            '101',
            'MG',
            -18.8500,
            47.4800,
            'Indian/Antananarivo',
            1500
        ),
        (
            'le-dome-smartone',
            'Le Dôme by SmartOne',
            'Espace moderne pensé pour les événements corporate et networking.',
            'Immeuble Atrium, Galaxy Andraharo',
            NULL,
            'Antananarivo',
            'Antananarivo',
            '101',
            'MG',
            -18.8549,
            47.5203,
            'Indian/Antananarivo',
            250
        ),
        (
            'parc-des-familles',
            'Parc des Familles',
            'Espace en plein air idéal pour les événements familiaux et les activités pour enfants.',
            'Avenue de l''Indépendance',
            'Quartier Analakely',
            'Antananarivo',
            'Antananarivo',
            '101',
            'MG',
            -18.8792,
            47.5079,
            'Indian/Antananarivo',
            500
        ),
        (
            'centre-conferences-tana',
            'Centre de Conférences d''Antananarivo',
            'Espace professionnel moderne pour séminaires et formations.',
            'Zone Galaxy Andraharo',
            NULL,
            'Antananarivo',
            'Antananarivo',
            '101',
            'MG',
            -18.8549,
            47.5203,
            'Indian/Antananarivo',
            300
        ),
        (
            'salle-premium-tana',
            'Salle Premium d''Antananarivo',
            'Salle de spectacle moderne pour événements premium.',
            'Boulevard de l''Indépendance',
            'Quartier Analakely',
            'Antananarivo',
            'Antananarivo',
            '101',
            'MG',
            -18.8792,
            47.5079,
            'Indian/Antananarivo',
            800
        ),
        (
            'stade-municipal-tana',
            'Stade Municipal d''Antananarivo',
            'Stade pour événements sportifs et festivals en plein air.',
            'Avenue de la République',
            'Quartier Isoraka',
            'Antananarivo',
            'Antananarivo',
            '101',
            'MG',
            -18.9082,
            47.5257,
            'Indian/Antananarivo',
            600
        ),
        -- Toamasina (Tamatave)
        (
            'salle-culturelle-toamasina',
            'Salle Culturelle de Toamasina',
            'Espace polyvalent pour événements culturels et artistiques.',
            'Boulevard de la République',
            'Centre-ville',
            'Toamasina',
            'Toamasina',
            '501',
            'MG',
            -18.1443,
            49.3958,
            'Indian/Antananarivo',
            500
        ),
        (
            'espace-tech-toamasina',
            'Espace Tech de Toamasina',
            'Espace dédié aux technologies et à l''innovation.',
            'Boulevard Joffre',
            'Quartier Industriel',
            'Toamasina',
            'Toamasina',
            '501',
            'MG',
            -18.1443,
            49.3958,
            'Indian/Antananarivo',
            150
        ),
        (
            'centre-affaires-toamasina',
            'Centre d''Affaires de Toamasina',
            'Espace professionnel moderne pour séminaires et conférences.',
            'Avenue de France',
            'Quartier Port',
            'Toamasina',
            'Toamasina',
            '501',
            'MG',
            -18.1443,
            49.3958,
            'Indian/Antananarivo',
            300
        ),
        -- Mahajanga (Majunga)
        (
            'plage-mahajanga',
            'Espace Événementiel Plage de Mahajanga',
            'Lieu en plein air face à la mer pour festivals et concerts.',
            'Avenue de France',
            'Bord de mer',
            'Mahajanga',
            'Mahajanga',
            '401',
            'MG',
            -15.7167,
            46.3167,
            'Indian/Antananarivo',
            800
        ),
        (
            'centre-conferences-majunga',
            'Centre de Conférences de Mahajanga',
            'Centre moderne pour conférences et séminaires.',
            'Avenue de France',
            'Quartier Centre',
            'Mahajanga',
            'Mahajanga',
            '401',
            'MG',
            -15.7167,
            46.3167,
            'Indian/Antananarivo',
            350
        ),
        (
            'salle-culturelle-majunga',
            'Salle Culturelle de Mahajanga',
            'Espace polyvalent pour événements culturels.',
            'Boulevard Poincaré',
            'Quartier Centre',
            'Mahajanga',
            'Mahajanga',
            '401',
            'MG',
            -15.7167,
            46.3167,
            'Indian/Antananarivo',
            400
        ),
        -- Antsiranana (Diego Suarez)
        (
            'hotel-diego-suarez',
            'Salle de Conférence Hôtel Diego Suarez',
            'Espace hôtelier moderne pour événements d''entreprise.',
            'Boulevard de la Mer',
            'Quartier Joffre',
            'Antsiranana',
            'Antsiranana',
            '201',
            'MG',
            -12.2764,
            49.2917,
            'Indian/Antananarivo',
            200
        ),
        (
            'centre-culturel-diego',
            'Centre Culturel d''Antsiranana',
            'Espace culturel moderne pour festivals et événements artistiques.',
            'Avenue de la Mer',
            'Quartier Centre',
            'Antsiranana',
            'Antsiranana',
            '201',
            'MG',
            -12.2764,
            49.2917,
            'Indian/Antananarivo',
            500
        ),
        (
            'complexe-culturel-diego',
            'Complexe Culturel d''Antsiranana',
            'Complexe culturel moderne pour festivals et événements artistiques.',
            'Hell-Ville',
            'Quartier Joffre',
            'Antsiranana',
            'Antsiranana',
            '201',
            'MG',
            -12.2764,
            49.2917,
            'Indian/Antananarivo',
            400
        ),
        -- Toliara (Tuléar)
        (
            'centre-culturel-toliara',
            'Centre Culturel de Toliara',
            'Espace dédié aux arts et à la culture du Sud de Madagascar.',
            'Avenue de l''Indépendance',
            'Centre-ville',
            'Toliara',
            'Toliara',
            '601',
            'MG',
            -23.3514,
            43.6853,
            'Indian/Antananarivo',
            400
        ),
        (
            'theatre-toliara',
            'Théâtre de Toliara',
            'Théâtre moderne pour spectacles et événements culturels.',
            'Avenue de la Mer',
            'Quartier Centre',
            'Toliara',
            'Toliara',
            '601',
            'MG',
            -23.3514,
            43.6853,
            'Indian/Antananarivo',
            350
        ),
        (
            'centre-affaires-toliara',
            'Centre d''Affaires de Toliara',
            'Espace professionnel pour séminaires et conférences.',
            'Boulevard Lyautey',
            'Quartier Centre',
            'Toliara',
            'Toliara',
            '601',
            'MG',
            -23.3514,
            43.6853,
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
            400,
            TRUE
        ),
        (
            'cafe-de-la-gare',
            'Jardin',
            'Espace extérieur pour sessions acoustiques intimistes.',
            200,
            FALSE
        ),
        (
            'le-glacier',
            'Salle principale',
            'Grande salle pour concerts live et soirées dansantes.',
            450,
            TRUE
        ),
        (
            'kudeta-urban-club',
            'Club',
            'Espace clubbing avec piste de danse et système son professionnel.',
            300,
            TRUE
        ),
        (
            'jaos-pub',
            'Scène cabaret',
            'Scène intimiste pour concerts acoustiques et performances live.',
            200,
            TRUE
        ),
        (
            'restaurant-taxi-be',
            'Espace concert',
            'Salle équipée pour concerts rock et metal avec scène surélevée.',
            400,
            TRUE
        ),
        (
            'le-louvre-hotel-spa',
            'Salle de réception',
            'Salle élégante pour concerts jazz et piano bar.',
            250,
            TRUE
        ),
        (
            'espace-nambinintsoa',
            'Espace plein air',
            'Vaste espace extérieur pour grands concerts et festivals.',
            1500,
            TRUE
        ),
        (
            'le-dome-smartone',
            'Espace conférence',
            'Salle modulable pour networking, pitchs et ateliers.',
            250,
            TRUE
        ),
        (
            'salle-culturelle-toamasina',
            'Salle principale',
            'Grande salle polyvalente pour concerts et spectacles.',
            500,
            TRUE
        ),
        (
            'centre-affaires-antsirabe',
            'Salle principale',
            'Amphithéâtre moderne équipé pour conférences et formations.',
            300,
            TRUE
        ),
        (
            'plage-mahajanga',
            'Espace principal',
            'Scène en plein air face à la mer avec système son et lumière.',
            800,
            TRUE
        ),
        (
            'universite-fianarantsoa',
            'Amphithéâtre principal',
            'Grand amphithéâtre universitaire pour conférences académiques.',
            600,
            TRUE
        ),
        (
            'centre-culturel-toliara',
            'Salle principale',
            'Salle de spectacle moderne pour arts et culture.',
            400,
            TRUE
        ),
        (
            'hotel-diego-suarez',
            'Salle de conférence',
            'Salle de conférence hôtelière équipée pour séminaires.',
            200,
            TRUE
        ),
        (
            'espace-culturel-morondava',
            'Espace principal',
            'Grande salle polyvalente pour expositions et spectacles.',
            600,
            TRUE
        ),
        (
            'centre-formation-ambositra',
            'Salle principale',
            'Amphithéâtre équipé pour conférences et formations.',
            300,
            TRUE
        ),
        (
            'salle-fetes-ambatondrazaka',
            'Salle principale',
            'Salle des fêtes pour concerts et événements culturels.',
            250,
            TRUE
        ),
        (
            'centre-hospitalier-manakara',
            'Salle de conférence',
            'Salle de conférence médicale équipée.',
            200,
            TRUE
        ),
        (
            'complexe-culturel-nosy-be',
            'Salle principale',
            'Grande salle de projection et spectacles.',
            500,
            TRUE
        ),
        (
            'espace-tech-toamasina',
            'Espace conférence',
            'Salle modulable pour ateliers et formations tech.',
            150,
            TRUE
        ),
        (
            'theatre-fort-dauphin',
            'Salle principale',
            'Théâtre moderne avec scène et gradins.',
            400,
            TRUE
        ),
        (
            'centre-conferences-majunga',
            'Salle principale',
            'Grande salle de conférence équipée.',
            350,
            TRUE
        ),
        (
            'parc-thermal-antsirabe',
            'Espace principal',
            'Parc en plein air avec scène et stands.',
            600,
            TRUE
        ),
        (
            'lycee-ambalavao',
            'Amphithéâtre',
            'Amphithéâtre scolaire pour conférences.',
            250,
            TRUE
        )
    ) AS sd (venue_slug, space_name, description, capacity, is_default)
),
space_insert AS (
    INSERT INTO venue_spaces (venue_id, name, description, capacity, is_default)
    SELECT
        v.id,
        sd.space_name,
        sd.description,
        sd.capacity,
        sd.is_default
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
            'Music on Sunday - Scène Malgache',
            'Live showcase',
            'Un concert acoustique intimiste mettant en avant les talents de la scène musicale malgache.',
            'Soirée musicale dédiée aux artistes émergents de Madagascar. Découvrez les nouvelles voix du salegy, du tsapiky et de la musique traditionnelle revisitée. Ambiance chaleureuse garantie avec des artistes locaux qui font vibrer la capitale.',
            'public',
            'published',
            'in_person',
            'Indian/Antananarivo',
            ('2026-01-25 20:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-01-25 23:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-01-10 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-01-25 20:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            400,
            'fr-FR',
            TRUE,
            FALSE,
            'concert',
            'vente-ticket/images/img1.png',
            'cafe-de-la-gare',
            'Salle principale',
            '{"venue_name":"Café de la Gare","address":"Avenue de l''Indépendance, Gare de Soarano, Analakely","city":"Antananarivo","region":"Antananarivo","country":"MG","latitude":-18.9031,"longitude":47.5211}'::jsonb
        ),
        (
            'business-connect-mada',
            'Business Connect Madagascar',
            'Rencontres & networking',
            'Un cocktail networking pour connecter les entrepreneurs et investisseurs malgaches.',
            'Événement thématique pour échanger sur les tendances startup, financement et innovation à Madagascar. Rencontrez des entrepreneurs locaux, des investisseurs et des mentors. Cocktail dînatoire inclus avec produits locaux.',
            'public',
            'published',
            'in_person',
            'Indian/Antananarivo',
            ('2026-01-28 18:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-01-28 22:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-01-15 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-01-27 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            250,
            'fr-FR',
            FALSE,
            TRUE,
            'business',
            'vente-ticket/images/img2.png',
            'le-dome-smartone',
            'Espace conférence',
            '{"venue_name":"Le Dôme by SmartOne","address":"Immeuble Atrium, Galaxy Andraharo","city":"Antananarivo","region":"Antananarivo","country":"MG"}'::jsonb
        ),
        (
            'festival-musique-toamasina',
            'Festival de Musique de Toamasina',
            'Festival en bord de mer',
            'Grand festival de musique sur la côte Est de Madagascar.',
            'Festival de musique en plein air face à l''océan Indien. Découvrez les meilleurs artistes malgaches et internationaux dans un cadre exceptionnel. Food trucks, bars et animations toute la journée.',
            'public',
            'published',
            'in_person',
            'Indian/Antananarivo',
            ('2026-02-08 14:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-02-08 22:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-01-20 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-02-07 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            500,
            'fr-FR',
            TRUE,
            TRUE,
            'festival',
            'vente-ticket/images/img2.png',
            'salle-culturelle-toamasina',
            'Salle principale',
            '{"venue_name":"Salle Culturelle de Toamasina","address":"Boulevard de la République, Centre-ville","city":"Toamasina","region":"Toamasina","country":"MG"}'::jsonb
        ),
        (
            'concert-reggae-tana',
            'Reggae Vibes Tana',
            'Soirée Roots & Culture',
            'Concert reggae exceptionnel avec les meilleures vibrations de la capitale.',
            'Une soirée dédiée au reggae et à la culture rasta. Venez vibrer au son des basses et des messages positifs. Artistes locaux et invités surprises pour une ambiance inoubliable.',
            'public',
            'published',
            'in_person',
            'Indian/Antananarivo',
            ('2026-02-05 20:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-02-05 23:30:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-01-18 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-02-05 20:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            350,
            'fr-FR',
            FALSE,
            TRUE,
            'concert',
            'vente-ticket/images/img1.png',
            'le-glacier',
            'Salle principale',
            '{"venue_name":"Le Glacier","address":"Avenue de l''Indépendance, Analakely","city":"Antananarivo","region":"Antananarivo","country":"MG","latitude":-18.9144,"longitude":47.5181}'::jsonb
        ),
        (
            'spectacle-culturel-toliara',
            'Spectacle Culturel du Sud',
            'Arts & Culture',
            'Spectacle mettant en valeur les arts et traditions du Sud de Madagascar.',
            'Grand spectacle culturel présentant les danses traditionnelles, musiques et chants du Sud de Madagascar. Découvrez la richesse culturelle de Toliara avec des troupes locales et des artistes renommés.',
            'public',
            'published',
            'in_person',
            'Indian/Antananarivo',
            ('2026-02-12 19:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-02-12 22:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-01-25 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-02-10 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            400,
            'fr-FR',
            TRUE,
            FALSE,
            'culture',
            'vente-ticket/images/img1.png',
            'centre-culturel-toliara',
            'Salle principale',
            '{"venue_name":"Centre Culturel de Toliara","address":"Avenue de l''Indépendance, Centre-ville","city":"Toliara","region":"Toliara","country":"MG"}'::jsonb
        ),
        (
            'festival-plage-mahajanga',
            'Festival Plage de Mahajanga',
            'Festival en plein air',
            'Grand festival de musique et culture sur la plage de Mahajanga.',
            'Festival en plein air face à la mer avec concerts, spectacles de danse traditionnelle, stands d''artisanat local et food trucks. Ambiance festive garantie avec les meilleurs artistes de Mahajanga.',
            'public',
            'published',
            'in_person',
            'Indian/Antananarivo',
            ('2026-03-15 10:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-03-15 20:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-02-01 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-03-13 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            800,
            'fr-FR',
            TRUE,
            TRUE,
            'festival',
            'vente-ticket/images/img2.png',
            'plage-mahajanga',
            'Espace principal',
            '{"venue_name":"Espace Événementiel Plage de Mahajanga","address":"Avenue de France, Bord de mer","city":"Mahajanga","region":"Mahajanga","country":"MG"}'::jsonb
        ),
        (
            'conference-academique-tana',
            'Conférence Académique d''Antananarivo',
            'Colloque scientifique',
            'Colloque international sur le développement durable à Madagascar.',
            'Conférence académique réunissant chercheurs, étudiants et professionnels autour des enjeux du développement durable à Madagascar. Thèmes abordés : agriculture, environnement, énergies renouvelables, et économie verte.',
            'public',
            'published',
            'in_person',
            'Indian/Antananarivo',
            ('2026-02-22 08:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-02-22 15:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-02-01 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-02-20 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            600,
            'fr-FR',
            FALSE,
            FALSE,
            'business',
            'vente-ticket/images/img1.png',
            'centre-conferences-tana',
            'Salle principale',
            '{"venue_name":"Centre de Conférences d''Antananarivo","address":"Zone Galaxy Andraharo","city":"Antananarivo","region":"Antananarivo","country":"MG"}'::jsonb
        ),
        (
            'concert-salegy-tana',
            'Salegy Fever Tana',
            'Le meilleur du Salegy',
            'Une nuit de folie dédiée au Salegy, la musique qui fait danser tout Madagascar !',
            'Retrouvez les plus grands noms du genre pour une soirée inoubliable au Glacier. Ambiance survoltée et danse jusqu''au bout de la nuit !',
            'public',
            'published',
            'in_person',
            'Indian/Antananarivo',
            ('2026-02-12 21:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-02-13 02:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-01-25 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-02-12 21:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            450,
            'fr-FR',
            TRUE,
            TRUE,
            'concert',
            'vente-ticket/images/img1.png',
            'le-glacier',
            'Salle principale',
            '{"venue_name":"Le Glacier","address":"Avenue de l''Indépendance, Analakely","city":"Antananarivo","region":"Antananarivo","country":"MG","latitude":-18.9144,"longitude":47.5181}'::jsonb
        ),
        (
            'concert-rock-tana',
            'Rock Evolution Tana',
            'Le meilleur du rock malgache',
            'Une nuit électrique pour les amateurs de rock avec les groupes phares de la capitale.',
            'Venez découvrir la scène rock malgache au Taxi Be. Au programme : reprises de classiques et compositions originales. Ambiance survoltée garantie !',
            'public',
            'published',
            'in_person',
            'Indian/Antananarivo',
            ('2026-03-08 20:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-03-09 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-02-15 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-03-08 20:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            350,
            'fr-FR',
            FALSE,
            TRUE,
            'concert',
            'vente-ticket/images/img2.png',
            'restaurant-taxi-be',
            'Espace concert',
            '{"venue_name":"Restaurant Taxi Be","address":"Antanimena","city":"Antananarivo","region":"Antananarivo","country":"MG","latitude":-18.9200,"longitude":47.5300}'::jsonb
        ),
        (
            'festival-artisanat-majunga',
            'Festival d''Artisanat de Mahajanga',
            'Arts & Culture',
            'Grande exposition d''artisanat malgache sur la côte Ouest.',
            'Festival mettant en valeur l''artisanat traditionnel de Mahajanga. Découvrez les créations locales, assistez à des démonstrations et participez à des ateliers. Marché artisanal, spectacles de danse et musique traditionnelle.',
            'public',
            'published',
            'in_person',
            'Indian/Antananarivo',
            ('2026-02-15 09:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-02-15 18:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-01-25 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-02-13 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            600,
            'fr-FR',
            TRUE,
            FALSE,
            'festival',
            'vente-ticket/images/img1.png',
            'salle-culturelle-majunga',
            'Espace principal',
            '{"venue_name":"Salle Culturelle de Mahajanga","address":"Boulevard Poincaré, Quartier Centre","city":"Mahajanga","region":"Mahajanga","country":"MG"}'::jsonb
        ),
        (
            'conference-agriculture-tana',
            'Conférence Agriculture Durable',
            'Colloque scientifique',
            'Colloque sur l''agriculture durable et l''agroécologie à Madagascar.',
            'Conférence réunissant agriculteurs, chercheurs et décideurs autour de l''agriculture durable. Thèmes : techniques agroécologiques, gestion des ressources, adaptation au changement climatique. Visites de terrain incluses.',
            'public',
            'published',
            'in_person',
            'Indian/Antananarivo',
            ('2026-02-18 08:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-02-18 17:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-01-28 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-02-16 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            300,
            'fr-FR',
            FALSE,
            TRUE,
            'business',
            'vente-ticket/images/img2.png',
            'centre-conferences-tana',
            'Salle principale',
            '{"venue_name":"Centre de Conférences d''Antananarivo","address":"Zone Galaxy Andraharo","city":"Antananarivo","region":"Antananarivo","country":"MG"}'::jsonb
        ),
        (
            'concert-jazz-tana',
            'Jazz Night à Antananarivo',
            'Concert jazz',
            'Soirée jazz intimiste à Antananarivo.',
            'Concert de jazz avec des musiciens locaux et internationaux. Ambiance chaleureuse dans un cadre exceptionnel. Bar et restauration légère disponibles sur place.',
            'public',
            'published',
            'in_person',
            'Indian/Antananarivo',
            ('2026-01-27 20:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-01-28 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-01-12 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-01-27 20:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            250,
            'fr-FR',
            FALSE,
            FALSE,
            'concert',
            'vente-ticket/images/img1.png',
            'le-louvre-hotel-spa',
            'Salle de réception',
            '{"venue_name":"Le Louvre Hotel & Spa","address":"4, Place P. Tsiranana, Antaninarenina","city":"Antananarivo","region":"Antananarivo","country":"MG","latitude":-18.8748,"longitude":47.54729}'::jsonb
        ),
        (
            'concert-electro-tana',
            'Electro Urban Night',
            'DJ Set au Kudeta',
            'Soirée électro exclusive avec les meilleurs DJs de la capitale au Kudeta Urban Club.',
            'Vivez une expérience unique avec une sélection House, Techno et Afrobeat. Show visuel, cocktails et bonne humeur au rendez-vous. Le spot idéal pour faire la fête.',
            'public',
            'published',
            'in_person',
            'Indian/Antananarivo',
            ('2026-02-25 22:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-02-26 04:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-02-05 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-02-25 22:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            300,
            'fr-FR',
            FALSE,
            TRUE,
            'concert',
            'vente-ticket/images/img2.png',
            'kudeta-urban-club',
            'Club',
            '{"venue_name":"Kudeta Urban Club","address":"Carlton Hotel, Rue Stibbe, Anosy","city":"Antananarivo","region":"Antananarivo","country":"MG","latitude":-18.914398,"longitude":47.51806}'::jsonb
        ),
        (
            'festival-cinema-nosy-be',
            'Festival de Cinéma de Nosy Be',
            'Festival cinéma',
            'Festival de cinéma malgache et international sur l''île de Nosy Be.',
            'Festival de cinéma présentant des films malgaches et internationaux. Projections en plein air, rencontres avec les réalisateurs, ateliers de cinéma. Soirées de gala et remise de prix.',
            'public',
            'published',
            'in_person',
            'Indian/Antananarivo',
            ('2026-03-05 18:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-03-07 23:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-02-01 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-03-03 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            500,
            'fr-FR',
            TRUE,
            TRUE,
            'festival',
            'vente-ticket/images/img1.png',
            'complexe-culturel-nosy-be',
            'Salle principale',
            '{"venue_name":"Complexe Culturel d''Antsiranana","address":"Boulevard de la Mer","city":"Antsiranana","region":"Antsiranana","country":"MG"}'::jsonb
        ),
        (
            'concert-folk-tana',
            'Folk & Blues Heritage',
            'Cabaret acoustique',
            'Une soirée intime avec les légendes du folk malgache au Jao''s Pub.',
            'Laissez-vous emporter par les mélodies acoustiques et les textes profonds des artistes folk de la Grande Île. Ambiance conviviale et authentique.',
            'public',
            'published',
            'in_person',
            'Indian/Antananarivo',
            ('2026-02-20 20:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-02-20 23:30:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-02-01 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-02-20 20:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            200,
            'fr-FR',
            FALSE,
            TRUE,
            'concert',
            'vente-ticket/images/img2.png',
            'jaos-pub',
            'Scène cabaret',
            '{"venue_name":"Cabaret Jao''s Pub","address":"Ambohipo","city":"Antananarivo","region":"Antananarivo","country":"MG","latitude":-18.8720,"longitude":47.5570}'::jsonb
        ),
        (
            'spectacle-danse-toliara',
            'Spectacle de Danse Traditionnelle',
            'Arts & Culture',
            'Grand spectacle de danse traditionnelle du Sud de Madagascar.',
            'Spectacle présentant les danses traditionnelles du Sud de Madagascar. Troupes locales et artistes renommés. Découvrez la richesse culturelle du Sud à travers la danse, la musique et les costumes traditionnels.',
            'public',
            'published',
            'in_person',
            'Indian/Antananarivo',
            ('2026-03-12 19:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-03-12 22:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-02-20 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-03-10 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            400,
            'fr-FR',
            TRUE,
            FALSE,
            'culture',
            'vente-ticket/images/img1.png',
            'theatre-toliara',
            'Salle principale',
            '{"venue_name":"Théâtre de Toliara","address":"Avenue de la Mer, Quartier Centre","city":"Toliara","region":"Toliara","country":"MG"}'::jsonb
        ),
        (
            'concert-world-tana',
            'World Music Fusion',
            'Scène ouverte au Café de la Gare',
            'Un mélange éclectique de sons du monde et de rythmes malgaches au Café de la Gare.',
            'Venez découvrir des collaborations inédites entre artistes locaux et internationaux dans le cadre magnifique du Café de la Gare.',
            'public',
            'published',
            'in_person',
            'Indian/Antananarivo',
            ('2026-03-12 19:30:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-03-12 23:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-02-20 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-03-12 19:30:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            400,
            'fr-FR',
            TRUE,
            FALSE,
            'concert',
            'vente-ticket/images/img1.png',
            'cafe-de-la-gare',
            'Salle principale',
            '{"venue_name":"Café de la Gare","address":"Avenue de l''Indépendance, Gare de Soarano, Analakely","city":"Antananarivo","region":"Antananarivo","country":"MG","latitude":-18.9031,"longitude":47.5211}'::jsonb
        ),
        (
            'concert-blues-tana',
            'Blues & Soul Night',
            'Élégance au Louvre',
            'Une soirée de blues refiné dans le cadre prestigieux de l''Hôtel Le Louvre.',
            'Laissez-vous envoûter par les voix soul et les guitares blues. Une ambiance feutrée pour une soirée d''exception au cœur d''Antaninarenina.',
            'public',
            'published',
            'in_person',
            'Indian/Antananarivo',
            ('2026-03-18 20:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-03-18 23:30:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-02-15 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-03-18 20:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            250,
            'fr-FR',
            FALSE,
            TRUE,
            'concert',
            'vente-ticket/images/img2.png',
            'le-louvre-hotel-spa',
            'Salle de réception',
            '{"venue_name":"Le Louvre Hotel & Spa","address":"4, Place P. Tsiranana, Antaninarenina","city":"Antananarivo","region":"Antananarivo","country":"MG","latitude":-18.8748,"longitude":47.54729}'::jsonb
        ),
        (
            'festival-gastronomie-tana',
            'Festival Gastronomique d''Antananarivo',
            'Festival gastronomie',
            'Festival mettant en valeur la cuisine malgache et les produits locaux.',
            'Festival gastronomique avec dégustations, ateliers culinaires, et concours de cuisine. Découvrez les spécialités régionales, rencontrez des chefs locaux, et participez à des démonstrations culinaires. Marché de produits locaux.',
            'public',
            'published',
            'in_person',
            'Indian/Antananarivo',
            ('2026-02-28 10:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-02-28 20:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-02-05 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-02-26 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            600,
            'fr-FR',
            TRUE,
            TRUE,
            'festival',
            'vente-ticket/images/img1.png',
            'parc-des-familles',
            'Espace principal',
            '{"venue_name":"Parc des Familles","address":"Avenue de l''Indépendance, Quartier Analakely","city":"Antananarivo","region":"Antananarivo","country":"MG"}'::jsonb
        ),
        (
            'concert-pop-tana',
            'Pop Generation Tana',
            'Open Air au Nambinintsoa',
            'Le plus gros concert pop de la saison à l''Espace Nambinintsoa Talatamaty.',
            'Préparez-vous pour un show explosif avec les stars de la pop locale. Chorégraphies, effets spéciaux et ambiance festive garantie pour toute la famille.',
            'public',
            'published',
            'in_person',
            'Indian/Antananarivo',
            ('2026-03-25 14:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-03-25 21:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-03-01 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-03-25 14:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            1200,
            'fr-FR',
            FALSE,
            FALSE,
            'concert',
            'vente-ticket/images/img2.png',
            'espace-nambinintsoa',
            'Espace plein air',
            '{"venue_name":"Espace Nambinintsoa","address":"Talatamaty","city":"Antananarivo","region":"Antananarivo","country":"MG","latitude":-18.8500,"longitude":47.4800}'::jsonb
        ),
        (
            'concert-kabosy-night-tana',
            'Kabosy Night Fever',
            'Concert acoustique au Café de la Gare',
            'Une soirée dédiée au kabosy, l''instrument emblématique de Madagascar.',
            'Découvrez la magie du kabosy avec les plus grands maîtres de l''instrument. Une fusion entre tradition et modernité dans le cadre historique de la Gare de Soarano.',
            'public',
            'published',
            'in_person',
            'Indian/Antananarivo',
            ('2026-04-05 19:30:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-04-05 22:30:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-03-01 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-04-05 19:30:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            300,
            'fr-FR',
            FALSE,
            TRUE,
            'concert',
            'vente-ticket/images/img1.png',
            'cafe-de-la-gare',
            'Salle principale',
            '{"venue_name":"Café de la Gare","address":"Avenue de l''Indépendance, Gare de Soarano, Analakely","city":"Antananarivo","region":"Antananarivo","country":"MG","latitude":-18.9031,"longitude":47.5211}'::jsonb
        ),
        (
            'concert-metal-mada-tana',
            'Metal Mada Fest',
            'Metal malgache au Taxi Be',
            'La plus grosse soirée metal de la capitale au Restaurant Taxi Be Antanimena.',
            'Préparez-vous pour un déferlement de décibels avec les groupes phares du metal malgache. Une ambiance électrique pour les puristes du genre.',
            'public',
            'published',
            'in_person',
            'Indian/Antananarivo',
            ('2026-04-12 20:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-04-13 01:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-03-15 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-04-12 20:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            400,
            'fr-FR',
            FALSE,
            FALSE,
            'concert',
            'vente-ticket/images/img1.png',
            'restaurant-taxi-be',
            'Espace concert',
            '{"venue_name":"Restaurant Taxi Be","address":"Antanimena","city":"Antananarivo","region":"Antananarivo","country":"MG","latitude":-18.9200,"longitude":47.5300}'::jsonb
        ),
        (
            'concert-afro-dancehall-tana',
            'Afro Dancehall Club',
            'Clubbing au Kudeta',
            'Soirée Afrobeat et Dancehall exclusive au Kudeta Urban Club.',
            'Vivez le meilleur des sons urbains africains. Danse, cocktails et DJs de renom pour une nuit de folie à Anosy.',
            'public',
            'published',
            'in_person',
            'Indian/Antananarivo',
            ('2026-04-18 22:30:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-04-19 04:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-03-20 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-04-18 22:30:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            250,
            'fr-FR',
            FALSE,
            TRUE,
            'concert',
            'vente-ticket/images/img1.png',
            'kudeta-urban-club',
            'Club',
            '{"venue_name":"Kudeta Urban Club","address":"Carlton Hotel, Rue Stibbe, Anosy","city":"Antananarivo","region":"Antananarivo","country":"MG","latitude":-18.914398,"longitude":47.51806}'::jsonb
        ),
        (
            'concert-vako-drazana-tana',
            'Vako-drazana Live',
            'Cabaret traditionnel au Jao''s Pub',
            'Une rencontre authentique avec les musiques des hauts plateaux au Cabaret Jao''s Pub.',
            'Laissez-vous transporter par les chants polyphoniques et les rythmes traditionnels malgaches. Une soirée conviviale au cœur d''Ambohipo.',
            'public',
            'published',
            'in_person',
            'Indian/Antananarivo',
            ('2026-04-24 19:30:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-04-24 23:30:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-04-01 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-04-24 19:30:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            200,
            'fr-FR',
            FALSE,
            TRUE,
            'concert',
            'vente-ticket/images/img1.png',
            'jaos-pub',
            'Scène cabaret',
            '{"venue_name":"Cabaret Jao''s Pub","address":"Ambohipo","city":"Antananarivo","region":"Antananarivo","country":"MG","latitude":-18.8720,"longitude":47.5570}'::jsonb
        ),
        (
            'concert-rap-gasy-tana',
            'Rap Gasy Heritage',
            'Hip Hop Open Air à Talatamaty',
            'Le plus gros rassemblement hip-hop de l''année à l''Espace Nambinintsoa.',
            'Retrouvez les pionniers et la nouvelle garde du rap malgache pour un show d''exception en plein air.',
            'public',
            'published',
            'in_person',
            'Indian/Antananarivo',
            ('2026-05-01 14:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-05-01 21:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-04-05 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-05-01 14:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            1500,
            'fr-FR',
            TRUE,
            FALSE,
            'concert',
            'vente-ticket/images/img1.png',
            'espace-nambinintsoa',
            'Espace plein air',
            '{"venue_name":"Espace Nambinintsoa","address":"Talatamaty","city":"Antananarivo","region":"Antananarivo","country":"MG","latitude":-18.8500,"longitude":47.4800}'::jsonb
        ),
        (
            'concert-piano-bar-tana',
            'Soft Jazz & Piano Bar',
            'Soirée chic au Louvre',
            'Une ambiance feutrée et élégante avec les meilleurs pianistes de jazz à l''Hôtel Le Louvre.',
            'Détendez-vous avec une sélection de standards de jazz et de variétés internationales dans un cadre luxueux au cœur d''Antaninarenina.',
            'public',
            'published',
            'in_person',
            'Indian/Antananarivo',
            ('2026-05-10 19:30:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-05-10 23:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-04-10 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-05-10 19:30:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            150,
            'fr-FR',
            FALSE,
            TRUE,
            'concert',
            'vente-ticket/images/img1.png',
            'le-louvre-hotel-spa',
            'Salle de réception',
            '{"venue_name":"Le Louvre Hotel & Spa","address":"4, Place P. Tsiranana, Antaninarenina","city":"Antananarivo","region":"Antananarivo","country":"MG","latitude":-18.8748,"longitude":47.54729}'::jsonb
        ),
        (
            'concert-tsapiky-fever-tana',
            'Tsapiky Night Fever',
            'Ambiance Sud au Glacier',
            'Venez danser sur les rythmes endiablés du Tuléar au Glacier Analakely.',
            'Le tsapiky débarque dans la capitale pour une nuit de danse ininterrompue. Préparez vos chaussures, ça va chauffer !',
            'public',
            'published',
            'in_person',
            'Indian/Antananarivo',
            ('2026-05-15 21:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-05-16 03:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-04-20 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-05-15 21:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            400,
            'fr-FR',
            TRUE,
            TRUE,
            'concert',
            'vente-ticket/images/img1.png',
            'le-glacier',
            'Salle principale',
            '{"venue_name":"Le Glacier","address":"Avenue de l''Indépendance, Analakely","city":"Antananarivo","region":"Antananarivo","country":"MG","latitude":-18.9144,"longitude":47.5181}'::jsonb
        ),
        (
            'theatre-contemporain-tana',
            'Festival de Théâtre Contemporain',
            'Créations malgaches',
            'Une semaine dédiée à la création théâtrale malgache contemporaine.',
            'Découvrez une sélection de pièces originales mettant en scène les enjeux de la société malgache actuelle. Performances, lectures dramatiques et débats.',
            'public',
            'published',
            'in_person',
            'Indian/Antananarivo',
            ('2026-05-20 18:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-05-25 21:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-04-15 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-05-18 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            200,
            'fr-FR',
            FALSE,
            FALSE,
            'culture',
            'vente-ticket/images/img1.png',
            'centre-conferences-tana',
            'Salle principale',
            '{"venue_name":"Centre de Conférences d''Antananarivo","address":"Zone Galaxy Andraharo","city":"Antananarivo","region":"Antananarivo","country":"MG"}'::jsonb
        ),
        (
            'concert-cabaret-tana',
            'Cabaret Night Live',
            'Soirée cabaret au Glacier',
            'Ambiance feutrée et performances live exceptionnelles au cœur d''Analakely.',
            'Venez vivre une expérience cabaret unique avec des artistes talentueux, de la bonne musique et une ambiance chaleureuse au Glacier.',
            'public',
            'published',
            'in_person',
            'Indian/Antananarivo',
            ('2026-06-05 20:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-06-06 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-05-15 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-06-05 20:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            350,
            'fr-FR',
            FALSE,
            TRUE,
            'concert',
            'vente-ticket/images/img1.png',
            'le-glacier',
            'Salle principale',
            '{"venue_name":"Le Glacier","address":"Avenue de l''Indépendance, Analakely","city":"Antananarivo","region":"Antananarivo","country":"MG","latitude":-18.9144,"longitude":47.5181}'::jsonb
        ),
        (
            'concert-electro-night-tana',
            'Electro Night Fusion',
            'Le son de demain au Kudeta',
            'Une nuit immersive dans les rythmes électroniques les plus pointus de la capitale.',
            'Les meilleurs DJs de la scène electro se relaient au platines du Kudeta pour vous faire danser toute la nuit sur des rythmes hypnotiques.',
            'public',
            'published',
            'in_person',
            'Indian/Antananarivo',
            ('2026-06-12 22:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-06-13 04:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-05-20 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-06-12 22:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            250,
            'fr-FR',
            FALSE,
            TRUE,
            'concert',
            'vente-ticket/images/img1.png',
            'kudeta-urban-club',
            'Club',
            '{"venue_name":"Kudeta Urban Club","address":"Carlton Hotel, Rue Stibbe, Anosy","city":"Antananarivo","region":"Antananarivo","country":"MG","latitude":-18.914398,"longitude":47.51806}'::jsonb
        ),
        (
            'concert-acoustic-tana',
            'Acoustic Garden Session',
            'Douceur musicale au Café de la Gare',
            'Un moment suspendu avec les plus belles voix malgaches en version acoustique.',
            'Profitez du cadre magnifique du jardin du Café de la Gare pour une session acoustique intimiste et chaleureuse. Un pur délice pour les oreilles.',
            'public',
            'published',
            'in_person',
            'Indian/Antananarivo',
            ('2026-06-20 18:30:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-06-20 21:30:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-06-01 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            ('2026-06-20 18:30:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
            200,
            'fr-FR',
            FALSE,
            TRUE,
            'concert',
            'vente-ticket/images/img1.png',
            'cafe-de-la-gare',
            'Jardin',
            '{"venue_name":"Café de la Gare","address":"Avenue de l''Indépendance, Gare de Soarano, Analakely","city":"Antananarivo","region":"Antananarivo","country":"MG","latitude":-18.9031,"longitude":47.5211}'::jsonb
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
    -- Business : conférences, séminaires, workshops
    WHEN 'business-connect-mada' THEN 'business'
    WHEN 'conference-academique-tana' THEN 'business'
    WHEN 'conference-agriculture-tana' THEN 'business'
    -- Festival : festivals multi-activités
    WHEN 'festival-musique-toamasina' THEN 'festival'
    WHEN 'festival-plage-mahajanga' THEN 'festival'
    WHEN 'festival-artisanat-morondava' THEN 'festival'
    WHEN 'festival-artisanat-majunga' THEN 'festival'
    WHEN 'festival-cinema-nosy-be' THEN 'festival'
    WHEN 'festival-gastronomie-antsirabe' THEN 'festival'
    WHEN 'festival-gastronomie-tana' THEN 'festival'
    WHEN 'festival-famille-enfants' THEN 'festival'
    -- Culture : danse, théâtre, spectacles
    WHEN 'spectacle-culturel-toliara' THEN 'culture'
    WHEN 'spectacle-danse-toliara' THEN 'culture'
    WHEN 'spectacle-danse-fort-dauphin' THEN 'culture'
    WHEN 'theatre-contemporain-tana' THEN 'culture'
    -- Concert : soirées live, concerts
    ELSE 'concert'
END
ON CONFLICT DO NOTHING;

WITH evt AS (
    SELECT id, slug FROM events WHERE slug IN (
        'concert-music-sunday', 
        'business-connect-mada',
        'festival-musique-toamasina',
        'concert-reggae-tana',
        'festival-plage-mahajanga',
        'conference-academique-tana',
        'concert-salegy-tana',
        'concert-rock-tana',
        'festival-artisanat-majunga',
        'conference-agriculture-tana',
        'concert-jazz-tana',
        'concert-electro-tana',
        'festival-cinema-nosy-be',
        'concert-folk-tana',
        'concert-world-tana',
        'concert-blues-tana',
        'festival-gastronomie-tana',
        'concert-pop-tana',
        'spectacle-culturel-toliara',
        'spectacle-danse-toliara',
        'concert-kabosy-night-tana',
        'concert-metal-mada-tana',
        'concert-afro-dancehall-tana',
        'concert-vako-drazana-tana',
        'concert-rap-gasy-tana',
        'concert-piano-bar-tana',
        'concert-tsapiky-fever-tana',
        'theatre-contemporain-tana',
        'concert-cabaret-tana',
        'concert-electro-night-tana',
        'concert-acoustic-tana'
    )
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
    SELECT id, slug FROM events WHERE slug IN (
        'concert-music-sunday', 
        'business-connect-mada',
        'festival-musique-toamasina',
        'concert-reggae-tana',
        'festival-plage-mahajanga',
        'conference-academique-tana',
        'concert-salegy-tana',
        'concert-rock-tana',
        'festival-artisanat-majunga',
        'conference-agriculture-tana',
        'concert-jazz-tana',
        'concert-electro-tana',
        'festival-cinema-nosy-be',
        'concert-folk-tana',
        'concert-world-tana',
        'concert-blues-tana',
        'festival-gastronomie-tana',
        'concert-pop-tana',
        'spectacle-culturel-toliara',
        'spectacle-danse-toliara',
        'concert-kabosy-night-tana',
        'concert-metal-mada-tana',
        'concert-afro-dancehall-tana',
        'concert-vako-drazana-tana',
        'concert-rap-gasy-tana',
        'concert-piano-bar-tana',
        'concert-tsapiky-fever-tana',
        'theatre-contemporain-tana',
        'concert-cabaret-tana',
        'concert-electro-night-tana',
        'concert-acoustic-tana'
    )
)
INSERT INTO event_media (
    event_id, media_type, url, alt_text,
    display_order, is_public
)
SELECT evt.id,
       'image',
       CASE evt.slug
           WHEN 'concert-music-sunday' THEN 'vente-ticket/images/img1.png'
           WHEN 'business-connect-mada' THEN 'vente-ticket/images/img2.png'
           WHEN 'concert-reggae-tana' THEN 'vente-ticket/images/img1.png'
           WHEN 'festival-plage-mahajanga' THEN 'vente-ticket/images/img2.png'
           WHEN 'conference-academique-tana' THEN 'vente-ticket/images/img1.png'
           WHEN 'concert-salegy-tana' THEN 'vente-ticket/images/img2.png'
           WHEN 'concert-rock-tana' THEN 'vente-ticket/images/img1.png'
           WHEN 'conference-agriculture-tana' THEN 'vente-ticket/images/img2.png'
           WHEN 'concert-jazz-tana' THEN 'vente-ticket/images/img1.png'
           WHEN 'concert-electro-tana' THEN 'vente-ticket/images/img2.png'
           WHEN 'festival-cinema-nosy-be' THEN 'vente-ticket/images/img1.png'
           WHEN 'concert-folk-tana' THEN 'vente-ticket/images/img2.png'
           WHEN 'concert-world-tana' THEN 'vente-ticket/images/img1.png'
           WHEN 'concert-blues-tana' THEN 'vente-ticket/images/img2.png'
           WHEN 'festival-gastronomie-tana' THEN 'vente-ticket/images/img1.png'
           WHEN 'concert-pop-tana' THEN 'vente-ticket/images/img2.png'
           WHEN 'spectacle-culturel-toliara' THEN 'vente-ticket/images/img1.png'
           WHEN 'spectacle-danse-toliara' THEN 'vente-ticket/images/img2.png'
           WHEN 'concert-kabosy-night-tana' THEN 'vente-ticket/images/img1.png'
           WHEN 'concert-metal-mada-tana' THEN 'vente-ticket/images/img2.png'
           WHEN 'concert-afro-dancehall-tana' THEN 'vente-ticket/images/img1.png'
           WHEN 'concert-vako-drazana-tana' THEN 'vente-ticket/images/img2.png'
           WHEN 'concert-rap-gasy-tana' THEN 'vente-ticket/images/img1.png'
           WHEN 'concert-piano-bar-tana' THEN 'vente-ticket/images/img2.png'
           WHEN 'concert-tsapiky-fever-tana' THEN 'vente-ticket/images/img1.png'
           WHEN 'theatre-contemporain-tana' THEN 'vente-ticket/images/img2.png'
           WHEN 'concert-cabaret-tana' THEN 'vente-ticket/images/img1.png'
           WHEN 'concert-electro-night-tana' THEN 'vente-ticket/images/img2.png'
           WHEN 'concert-acoustic-tana' THEN 'vente-ticket/images/img1.png'
           ELSE 'vente-ticket/images/img1.png'
       END,
       CASE evt.slug
           WHEN 'concert-music-sunday' THEN 'Affiche Music on Sunday - Scène Malgache'
           WHEN 'business-connect-mada' THEN 'Affiche Business Connect Madagascar'
           WHEN 'concert-reggae-tana' THEN 'Affiche Reggae Vibes Tana'
           WHEN 'concert-jazz-tana' THEN 'Affiche Jazz Night à Antananarivo'
           WHEN 'concert-salegy-tana' THEN 'Affiche Salegy Fever Tana'
           WHEN 'concert-rock-tana' THEN 'Affiche Rock Evolution Tana'
           WHEN 'concert-electro-tana' THEN 'Affiche Electro Urban Night'
           WHEN 'concert-folk-tana' THEN 'Affiche Folk & Blues Heritage'
           WHEN 'concert-world-tana' THEN 'Affiche World Music Fusion'
           WHEN 'concert-blues-tana' THEN 'Affiche Blues & Soul Night'
           WHEN 'concert-pop-tana' THEN 'Affiche Pop Generation Tana'
           WHEN 'spectacle-culturel-toliara' THEN 'Affiche Spectacle Culturel du Sud'
           WHEN 'spectacle-danse-toliara' THEN 'Affiche Spectacle de Danse Traditionnelle'
           WHEN 'concert-kabosy-night-tana' THEN 'Affiche Kabosy Night Fever'
           WHEN 'concert-metal-mada-tana' THEN 'Affiche Metal Mada Fest'
           WHEN 'concert-afro-dancehall-tana' THEN 'Affiche Afro Dancehall Club'
           WHEN 'concert-vako-drazana-tana' THEN 'Affiche Vako-drazana Live'
           WHEN 'concert-rap-gasy-tana' THEN 'Affiche Rap Gasy Heritage'
           WHEN 'concert-piano-bar-tana' THEN 'Affiche Soft Jazz & Piano Bar'
           WHEN 'concert-tsapiky-fever-tana' THEN 'Affiche Tsapiky Night Fever'
           WHEN 'theatre-contemporain-tana' THEN 'Affiche Festival de Théâtre Contemporain'
           WHEN 'concert-cabaret-tana' THEN 'Affiche Cabaret Night Live'
           WHEN 'concert-electro-night-tana' THEN 'Affiche Electro Night Fusion'
           WHEN 'concert-acoustic-tana' THEN 'Affiche Acoustic Garden Session'
           ELSE 'Affiche Événement Aiolia'
       END,
       1,
       TRUE
FROM evt
ON CONFLICT DO NOTHING;

WITH evt AS (
    SELECT id, slug, starts_at FROM events WHERE slug IN (
        'concert-music-sunday', 
        'business-connect-mada',
        'concert-reggae-tana',
        'festival-plage-mahajanga',
        'concert-jazz-tana',
        'concert-salegy-tana',
        'concert-rock-tana',
        'conference-academique-tana',
        'conference-agriculture-tana',
        'concert-electro-tana',
        'concert-folk-tana',
        'concert-world-tana',
        'concert-blues-tana',
        'concert-pop-tana',
        'spectacle-culturel-toliara',
        'spectacle-danse-toliara',
        'concert-kabosy-night-tana',
        'concert-metal-mada-tana',
        'concert-afro-dancehall-tana',
        'concert-vako-drazana-tana',
        'concert-rap-gasy-tana',
        'concert-piano-bar-tana',
        'concert-tsapiky-fever-tana',
        'theatre-contemporain-tana',
        'concert-cabaret-tana',
        'concert-electro-night-tana',
        'concert-acoustic-tana'
    )
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
    SELECT id, slug, sales_starts_at, sales_ends_at FROM events WHERE slug IN (
        'concert-music-sunday', 
        'business-connect-mada',
        'festival-musique-toamasina',
        'concert-reggae-tana',
        'festival-plage-mahajanga',
        'concert-jazz-tana',
        'conference-academique-tana',
        'concert-salegy-tana',
        'concert-rock-tana',
        'conference-agriculture-tana',
        'concert-electro-tana',
        'festival-cinema-nosy-be',
        'concert-folk-tana',
        'concert-world-tana',
        'concert-blues-tana',
        'festival-gastronomie-tana',
        'concert-pop-tana',
        'spectacle-culturel-toliara',
        'spectacle-danse-toliara',
        'concert-kabosy-night-tana',
        'concert-metal-mada-tana',
        'concert-afro-dancehall-tana',
        'concert-vako-drazana-tana',
        'concert-rap-gasy-tana',
        'concert-piano-bar-tana',
        'concert-tsapiky-fever-tana',
        'theatre-contemporain-tana',
        'concert-cabaret-tana',
        'concert-electro-night-tana',
        'concert-acoustic-tana'
    )
)
-- Types de billets variés pour démontrer toutes les fonctionnalités
INSERT INTO ticket_types (
    event_id, name, description, currency,
    base_price, service_fee, vat_rate, age_category,
    sales_start, sales_end, min_per_order, max_per_order
)
-- Concert Music Sunday : Billet simple (all) - Concert showcase intimiste
SELECT evt.id, 'Pass Concert', 'Accès libre à l''ensemble de la soirée musicale. Showcase artistes émergents.', 'MGA', 35000, 1750, 20, 'all'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 4
FROM evt WHERE evt.slug = 'concert-music-sunday'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Pass Concert')
UNION ALL
-- Business Connect : Billet simple (all)
SELECT evt.id, 'Pass Networking', 'Accès complet à la soirée networking, cocktail inclus.', 'MGA', 120000, 6000, 20, 'all'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 2
FROM evt WHERE evt.slug = 'business-connect-mada'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Pass Networking')
UNION ALL
-- Festival Toamasina : VIP et Standard avec adulte/enfant
SELECT evt.id, 'VIP', 'Billet VIP pour adulte. Zone VIP, boissons offertes, parking réservé.', 'MGA', 150000, 7500, 20, 'adult'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 6
FROM evt WHERE evt.slug = 'festival-musique-toamasina'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'VIP' AND tt.age_category = 'adult')
UNION ALL
SELECT evt.id, 'VIP', 'Billet VIP pour enfant. Zone VIP adaptée, boissons offertes.', 'MGA', 75000, 3750, 20, 'child'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 6
FROM evt WHERE evt.slug = 'festival-musique-toamasina'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'VIP' AND tt.age_category = 'child')
UNION ALL
SELECT evt.id, 'Standard', 'Billet Standard pour adulte. Accès général avec bon placement.', 'MGA', 60000, 3000, 20, 'adult'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 10
FROM evt WHERE evt.slug = 'festival-musique-toamasina'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Standard' AND tt.age_category = 'adult')
UNION ALL
SELECT evt.id, 'Standard', 'Billet Standard pour enfant. Accès général avec bon placement.', 'MGA', 30000, 1500, 20, 'child'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 10
FROM evt WHERE evt.slug = 'festival-musique-toamasina'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Standard' AND tt.age_category = 'child')
UNION ALL
-- Séminaire Antsirabe : Standard et Premium (adulte seulement)
SELECT evt.id, 'Pass Standard', 'Accès complet au séminaire. Repas de midi inclus.', 'MGA', 80000, 4000, 20, 'adult'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 5
FROM evt WHERE evt.slug = 'seminaire-entrepreneurs-antsirabe'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Pass Standard')
UNION ALL
SELECT evt.id, 'Pass Premium', 'Accès premium avec place réservée, repas premium et documentation exclusive.', 'MGA', 120000, 6000, 20, 'adult'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 3
FROM evt WHERE evt.slug = 'seminaire-entrepreneurs-antsirabe'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Pass Premium')
UNION ALL
-- Festival Plage Mahajanga : VIP/Gold/Silver avec adulte/enfant
SELECT evt.id, 'VIP', 'Billet VIP pour adulte. Zone VIP face à la mer, restauration incluse.', 'MGA', 100000, 5000, 20, 'adult'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 8
FROM evt WHERE evt.slug = 'festival-plage-mahajanga'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'VIP' AND tt.age_category = 'adult')
UNION ALL
SELECT evt.id, 'VIP', 'Billet VIP pour enfant. Zone VIP adaptée, restauration incluse.', 'MGA', 50000, 2500, 20, 'child'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 8
FROM evt WHERE evt.slug = 'festival-plage-mahajanga'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'VIP' AND tt.age_category = 'child')
UNION ALL
SELECT evt.id, 'Gold', 'Billet Gold pour adulte. Meilleur placement, avantages premium.', 'MGA', 70000, 3500, 20, 'adult'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 12
FROM evt WHERE evt.slug = 'festival-plage-mahajanga'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Gold' AND tt.age_category = 'adult')
UNION ALL
SELECT evt.id, 'Gold', 'Billet Gold pour enfant. Meilleur placement, avantages premium.', 'MGA', 35000, 1750, 20, 'child'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 12
FROM evt WHERE evt.slug = 'festival-plage-mahajanga'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Gold' AND tt.age_category = 'child')
UNION ALL
SELECT evt.id, 'Silver', 'Billet Silver pour adulte. Placement standard amélioré.', 'MGA', 45000, 2250, 20, 'adult'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 15
FROM evt WHERE evt.slug = 'festival-plage-mahajanga'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Silver' AND tt.age_category = 'adult')
UNION ALL
SELECT evt.id, 'Silver', 'Billet Silver pour enfant. Placement standard amélioré.', 'MGA', 22500, 1125, 20, 'child'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 15
FROM evt WHERE evt.slug = 'festival-plage-mahajanga'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Silver' AND tt.age_category = 'child')
UNION ALL
-- Conférence Fianarantsoa : Standard et Étudiant (avec réduction)
SELECT evt.id, 'Pass Standard', 'Accès complet à la conférence. Documentation incluse.', 'MGA', 50000, 2500, 20, 'all'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 10
FROM evt WHERE evt.slug = 'conference-academique-fianarantsoa'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Pass Standard')
UNION ALL
SELECT evt.id, 'Pass Étudiant', 'Accès étudiant avec réduction. Carte étudiante requise.', 'MGA', 20000, 1000, 20, 'all'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 10
FROM evt WHERE evt.slug = 'conference-academique-fianarantsoa'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Pass Étudiant')
UNION ALL
-- Spectacle Toliara : Adulte et Enfant séparés
SELECT evt.id, 'Billet Adulte', 'Accès complet pour un adulte (18 ans et plus).', 'MGA', 25000, 1250, 20, 'adult'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 8
FROM evt WHERE evt.slug = 'spectacle-culturel-toliara'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Billet Adulte')
UNION ALL
SELECT evt.id, 'Billet Enfant', 'Accès complet pour un enfant (3 à 17 ans). Gratuit pour les moins de 3 ans.', 'MGA', 12000, 600, 20, 'child'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 8
FROM evt WHERE evt.slug = 'spectacle-culturel-toliara'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Billet Enfant')
UNION ALL
-- Concert Rock Tana (Rock Evolution)
SELECT evt.id, 'Pass Standard', 'Accès complet au concert. Placement général.', 'MGA', 50000, 2500, 20, 'adult'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 4
FROM evt WHERE evt.slug = 'concert-rock-tana'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Pass Standard')
UNION ALL
SELECT evt.id, 'Pass Premium', 'Accès premium avec place réservée et une consommation gratuite.', 'MGA', 80000, 4000, 20, 'adult'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 2
FROM evt WHERE evt.slug = 'concert-rock-tana'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Pass Premium')
UNION ALL
-- Festival Artisanat Morondava : Adulte et Enfant
SELECT evt.id, 'Billet Adulte', 'Accès complet pour un adulte. Inclut toutes les activités et démonstrations.', 'MGA', 20000, 1000, 20, 'adult'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 10
FROM evt WHERE evt.slug = 'festival-artisanat-morondava'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Billet Adulte')
UNION ALL
SELECT evt.id, 'Billet Enfant', 'Accès complet pour un enfant. Ateliers créatifs inclus.', 'MGA', 10000, 500, 20, 'child'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 10
FROM evt WHERE evt.slug = 'festival-artisanat-morondava'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Billet Enfant')
UNION ALL
-- Conférence Agriculture Ambositra : Standard et Étudiant
SELECT evt.id, 'Pass Standard', 'Accès complet à la conférence. Documentation incluse.', 'MGA', 30000, 1500, 20, 'all'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 8
FROM evt WHERE evt.slug = 'conference-agriculture-ambositra'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Pass Standard')
UNION ALL
SELECT evt.id, 'Pass Étudiant', 'Accès étudiant avec réduction. Carte étudiante requise.', 'MGA', 15000, 750, 20, 'all'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 8
FROM evt WHERE evt.slug = 'conference-agriculture-ambositra'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Pass Étudiant')
UNION ALL
-- Concert Jazz Ambatondrazaka : Billet simple
SELECT evt.id, 'Pass Concert', 'Accès libre à l''ensemble de la soirée jazz.', 'MGA', 35000, 1750, 20, 'all'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 6
FROM evt WHERE evt.slug = 'concert-jazz-ambatondrazaka'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Pass Concert')
UNION ALL
-- Séminaire Santé Manakara : Standard et Premium
SELECT evt.id, 'Pass Standard', 'Accès complet au séminaire. Documentation incluse.', 'MGA', 60000, 3000, 20, 'adult'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 4
FROM evt WHERE evt.slug = 'seminaire-sante-manakara'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Pass Standard')
UNION ALL
SELECT evt.id, 'Pass Premium', 'Accès premium avec repas, documentation exclusive et accès aux ateliers privés.', 'MGA', 90000, 4500, 20, 'adult'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 2
FROM evt WHERE evt.slug = 'seminaire-sante-manakara'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Pass Premium')
UNION ALL
-- Festival Cinéma Nosy Be : VIP et Standard avec adulte/enfant
SELECT evt.id, 'VIP', 'Billet VIP pour adulte. Accès prioritaire, séances privées, rencontres avec réalisateurs.', 'MGA', 120000, 6000, 20, 'adult'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 5
FROM evt WHERE evt.slug = 'festival-cinema-nosy-be'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'VIP' AND tt.age_category = 'adult')
UNION ALL
SELECT evt.id, 'VIP', 'Billet VIP pour enfant. Accès prioritaire, séances adaptées.', 'MGA', 60000, 3000, 20, 'child'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 5
FROM evt WHERE evt.slug = 'festival-cinema-nosy-be'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'VIP' AND tt.age_category = 'child')
UNION ALL
SELECT evt.id, 'Standard', 'Billet Standard pour adulte. Accès à toutes les projections publiques.', 'MGA', 50000, 2500, 20, 'adult'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 10
FROM evt WHERE evt.slug = 'festival-cinema-nosy-be'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Standard' AND tt.age_category = 'adult')
UNION ALL
SELECT evt.id, 'Standard', 'Billet Standard pour enfant. Accès à toutes les projections publiques.', 'MGA', 25000, 1250, 20, 'child'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 10
FROM evt WHERE evt.slug = 'festival-cinema-nosy-be'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Standard' AND tt.age_category = 'child')
UNION ALL
-- Concert Folk Tana (Folk & Blues Heritage)
SELECT evt.id, 'Pass Standard', 'Accès complet au concert. Documentation et placement privilégié.', 'MGA', 50000, 2500, 20, 'all'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 5
FROM evt WHERE evt.slug = 'concert-folk-tana'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Pass Standard')
UNION ALL
SELECT evt.id, 'Pass Étudiant', 'Accès étudiant avec réduction. Carte étudiante requise.', 'MGA', 25000, 1250, 20, 'all'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 5
FROM evt WHERE evt.slug = 'concert-folk-tana'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Pass Étudiant')
UNION ALL
-- Spectacle Danse Toliara : Adulte et Enfant
SELECT evt.id, 'Billet Adulte', 'Accès complet pour un adulte. Spectacle de danse traditionnelle.', 'MGA', 30000, 1500, 20, 'adult'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 8
FROM evt WHERE evt.slug = 'spectacle-danse-toliara'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Billet Adulte')
UNION ALL
SELECT evt.id, 'Billet Enfant', 'Accès complet pour un enfant. Spectacle de danse traditionnelle.', 'MGA', 15000, 750, 20, 'child'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 8
FROM evt WHERE evt.slug = 'spectacle-danse-toliara'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Billet Enfant')
UNION ALL
-- Concert Kabosy Night Tana (Acoustique, intimiste)
SELECT evt.id, 'Pass Concert', 'Accès au concert de kabosy. Ambiance intimiste.', 'MGA', 35000, 1750, 20, 'all'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 10
FROM evt WHERE evt.slug = 'concert-kabosy-night-tana'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Pass Concert')
UNION ALL
-- Concert Metal Mada Fest (Rock/Metal, prix moyen)
SELECT evt.id, 'Pass Concert', 'Accès au festival metal. Ambiance rock.', 'MGA', 45000, 2250, 20, 'all'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 10
FROM evt WHERE evt.slug = 'concert-metal-mada-tana'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Pass Concert')
UNION ALL
-- Concert Afro Dancehall Club (Clubbing, prix premium)
SELECT evt.id, 'Pass Concert', 'Accès au club. Soirée dancehall exclusive.', 'MGA', 60000, 3000, 20, 'all'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 10
FROM evt WHERE evt.slug = 'concert-afro-dancehall-tana'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Pass Concert')
UNION ALL
-- Concert Vako-drazana Live (Traditionnel, prix accessible)
SELECT evt.id, 'Pass Concert', 'Accès au concert traditionnel. Musique des hauts plateaux.', 'MGA', 30000, 1500, 20, 'all'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 10
FROM evt WHERE evt.slug = 'concert-vako-drazana-tana'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Pass Concert')
UNION ALL
-- Concert Rap Gasy Heritage (Hip-hop, grand espace)
SELECT evt.id, 'Pass Concert', 'Accès au festival hip-hop. Open air à Talatamaty.', 'MGA', 40000, 2000, 20, 'all'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 10
FROM evt WHERE evt.slug = 'concert-rap-gasy-tana'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Pass Concert')
UNION ALL
-- Concert Piano Bar Tana (Jazz/Piano, lieu prestige)
SELECT evt.id, 'Pass Concert', 'Accès au piano bar. Ambiance feutrée au Louvre.', 'MGA', 55000, 2750, 20, 'all'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 10
FROM evt WHERE evt.slug = 'concert-piano-bar-tana'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Pass Concert')
UNION ALL
-- Concert Tsapiky Fever Tana (Danse, prix standard)
SELECT evt.id, 'Pass Concert', 'Accès au concert tsapiky. Ambiance dansante.', 'MGA', 38000, 1900, 20, 'all'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 10
FROM evt WHERE evt.slug = 'concert-tsapiky-fever-tana'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Pass Concert')
UNION ALL
-- Concert Cabaret Tana (Cabaret, prix moyen)
SELECT evt.id, 'Pass Concert', 'Accès au cabaret. Performances live et ambiance chaleureuse.', 'MGA', 42000, 2100, 20, 'all'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 10
FROM evt WHERE evt.slug = 'concert-cabaret-tana'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Pass Concert')
UNION ALL
-- Concert Electro Night Fusion (Clubbing électro, prix premium)
SELECT evt.id, 'Pass Concert', 'Accès à la soirée électro. Club Kudeta.', 'MGA', 58000, 2900, 20, 'all'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 10
FROM evt WHERE evt.slug = 'concert-electro-night-tana'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Pass Concert')
UNION ALL
-- Concert Acoustic Garden Session (Acoustique jardin, prix accessible)
SELECT evt.id, 'Pass Concert', 'Accès au concert acoustique en jardin.', 'MGA', 32000, 1600, 20, 'all'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 10
FROM evt WHERE evt.slug = 'concert-acoustic-tana'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Pass Concert')
UNION ALL
-- New Culture Tana
SELECT evt.id, 'Pass Culture', 'Accès complet au festival de théâtre contemporain.', 'MGA', 40000, 2000, 20, 'all'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 10
FROM evt WHERE evt.slug = 'theatre-contemporain-tana'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Pass Culture')
UNION ALL
-- Spectacle Danse Fort-Dauphin : Adulte et Enfant
SELECT evt.id, 'Billet Adulte', 'Accès complet pour un adulte. Spectacle de danse traditionnelle.', 'MGA', 30000, 1500, 20, 'adult'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 8
FROM evt WHERE evt.slug = 'spectacle-danse-fort-dauphin'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Billet Adulte')
UNION ALL
SELECT evt.id, 'Billet Enfant', 'Accès complet pour un enfant. Spectacle de danse traditionnelle.', 'MGA', 15000, 750, 20, 'child'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 8
FROM evt WHERE evt.slug = 'spectacle-danse-fort-dauphin'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Billet Enfant')
UNION ALL
-- Concert Blues & Soul Night Tana : Standard et Étudiant
SELECT evt.id, 'Pass Standard', 'Accès au concert blues. Ambiance feutrée au Louvre.', 'MGA', 52000, 2600, 20, 'all'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 6
FROM evt WHERE evt.slug = 'concert-blues-tana'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Pass Standard')
UNION ALL
SELECT evt.id, 'Pass Étudiant', 'Accès étudiant avec réduction au concert blues.', 'MGA', 28000, 1400, 20, 'all'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 6
FROM evt WHERE evt.slug = 'concert-blues-tana'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Pass Étudiant')
UNION ALL
-- Festival Gastronomie Antsirabe : VIP et Standard avec adulte/enfant
SELECT evt.id, 'VIP', 'Billet VIP pour adulte. Accès prioritaire, dégustations premium, ateliers privés.', 'MGA', 80000, 4000, 20, 'adult'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 8
FROM evt WHERE evt.slug = 'festival-gastronomie-antsirabe'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'VIP' AND tt.age_category = 'adult')
UNION ALL
SELECT evt.id, 'VIP', 'Billet VIP pour enfant. Accès prioritaire, dégustations adaptées.', 'MGA', 40000, 2000, 20, 'child'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 8
FROM evt WHERE evt.slug = 'festival-gastronomie-antsirabe'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'VIP' AND tt.age_category = 'child')
UNION ALL
SELECT evt.id, 'Standard', 'Billet Standard pour adulte. Accès général avec dégustations.', 'MGA', 40000, 2000, 20, 'adult'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 12
FROM evt WHERE evt.slug = 'festival-gastronomie-antsirabe'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Standard' AND tt.age_category = 'adult')
UNION ALL
SELECT evt.id, 'Standard', 'Billet Standard pour enfant. Accès général avec dégustations.', 'MGA', 20000, 1000, 20, 'child'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 12
FROM evt WHERE evt.slug = 'festival-gastronomie-antsirabe'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Standard' AND tt.age_category = 'child')
UNION ALL
-- Séminaire Éducation Ambalavao : Standard et Étudiant
SELECT evt.id, 'Pass Standard', 'Accès complet au séminaire. Documentation incluse.', 'MGA', 40000, 2000, 20, 'all'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 6
FROM evt WHERE evt.slug = 'seminaire-education-ambalavao'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Pass Standard')
UNION ALL
SELECT evt.id, 'Pass Étudiant', 'Accès étudiant avec réduction. Carte étudiante requise.', 'MGA', 20000, 1000, 20, 'all'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 6
FROM evt WHERE evt.slug = 'seminaire-education-ambalavao'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Pass Étudiant')
UNION ALL
-- Concert Reggae Tana : Standard et Premium
SELECT evt.id, 'Pass Standard', 'Accès au concert reggae. Placement général.', 'MGA', 40000, 2000, 20, 'all'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 8
FROM evt WHERE evt.slug = 'concert-reggae-tana'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Pass Standard')
UNION ALL
SELECT evt.id, 'Pass Premium', 'Accès premium au concert reggae avec zone VIP.', 'MGA', 65000, 3250, 20, 'all'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 4
FROM evt WHERE evt.slug = 'concert-reggae-tana'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Pass Premium')
UNION ALL
-- Conférence Académique Tana : Standard et Étudiant
SELECT evt.id, 'Pass Standard', 'Accès complet à la conférence. Documentation incluse.', 'MGA', 50000, 2500, 20, 'all'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 10
FROM evt WHERE evt.slug = 'conference-academique-tana'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Pass Standard')
UNION ALL
SELECT evt.id, 'Pass Étudiant', 'Accès étudiant avec réduction. Carte étudiante requise.', 'MGA', 20000, 1000, 20, 'all'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 10
FROM evt WHERE evt.slug = 'conference-academique-tana'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Pass Étudiant')
UNION ALL
-- Conférence Agriculture Tana : Standard et Étudiant
SELECT evt.id, 'Pass Standard', 'Accès complet à la conférence. Documentation incluse.', 'MGA', 30000, 1500, 20, 'all'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 8
FROM evt WHERE evt.slug = 'conference-agriculture-tana'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Pass Standard')
UNION ALL
SELECT evt.id, 'Pass Étudiant', 'Accès étudiant avec réduction. Carte étudiante requise.', 'MGA', 15000, 750, 20, 'all'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 8
FROM evt WHERE evt.slug = 'conference-agriculture-tana'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Pass Étudiant')
UNION ALL
-- Concert Jazz Tana : Billet simple
SELECT evt.id, 'Pass Concert', 'Accès libre à l''ensemble de la soirée jazz au Louvre Hotel.', 'MGA', 48000, 2400, 20, 'all'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 6
FROM evt WHERE evt.slug = 'concert-jazz-tana'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Pass Concert')
UNION ALL
-- Concert Salegy Fever Tana : Billet simple
SELECT evt.id, 'Pass Concert', 'Accès au concert salegy. Ambiance dansante au Glacier.', 'MGA', 42000, 2100, 20, 'all'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 8
FROM evt WHERE evt.slug = 'concert-salegy-tana'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Pass Concert')
UNION ALL
-- Concert Electro Urban Night : Billet simple clubbing
SELECT evt.id, 'Pass Concert', 'Accès à la soirée électro au Kudeta Urban Club.', 'MGA', 55000, 2750, 20, 'all'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 6
FROM evt WHERE evt.slug = 'concert-electro-tana'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Pass Concert')
UNION ALL
-- Concert World Music Fusion : Billet simple
SELECT evt.id, 'Pass Concert', 'Accès au concert world music au Café de la Gare.', 'MGA', 38000, 1900, 20, 'all'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 8
FROM evt WHERE evt.slug = 'concert-world-tana'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Pass Concert')
UNION ALL
-- Concert Pop Generation Tana : Billet Adulte et Enfant
SELECT evt.id, 'Billet Adulte', 'Accès adulte au grand concert pop à Nambinintsoa.', 'MGA', 45000, 2250, 20, 'adult'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 10
FROM evt WHERE evt.slug = 'concert-pop-tana'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Billet Adulte')
UNION ALL
SELECT evt.id, 'Billet Enfant', 'Accès enfant au grand concert pop à Nambinintsoa.', 'MGA', 25000, 1250, 20, 'child'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 10
FROM evt WHERE evt.slug = 'concert-pop-tana'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Billet Enfant')
UNION ALL
-- Séminaire Santé Toliara : Standard et Premium
SELECT evt.id, 'Pass Standard', 'Accès complet au séminaire. Documentation incluse.', 'MGA', 60000, 3000, 20, 'adult'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 4
FROM evt WHERE evt.slug = 'seminaire-sante-toliara'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Pass Standard')
UNION ALL
SELECT evt.id, 'Pass Premium', 'Accès premium avec repas, documentation exclusive et accès aux ateliers privés.', 'MGA', 90000, 4500, 20, 'adult'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 2
FROM evt WHERE evt.slug = 'seminaire-sante-toliara'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Pass Premium')
UNION ALL
-- Festival Gastronomie Tana : VIP et Standard avec adulte/enfant
SELECT evt.id, 'VIP', 'Billet VIP pour adulte. Accès prioritaire, dégustations premium, ateliers privés.', 'MGA', 80000, 4000, 20, 'adult'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 8
FROM evt WHERE evt.slug = 'festival-gastronomie-tana'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'VIP' AND tt.age_category = 'adult')
UNION ALL
SELECT evt.id, 'VIP', 'Billet VIP pour enfant. Accès prioritaire, dégustations adaptées.', 'MGA', 40000, 2000, 20, 'child'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 8
FROM evt WHERE evt.slug = 'festival-gastronomie-tana'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'VIP' AND tt.age_category = 'child')
UNION ALL
SELECT evt.id, 'Standard', 'Billet Standard pour adulte. Accès général avec dégustations.', 'MGA', 40000, 2000, 20, 'adult'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 12
FROM evt WHERE evt.slug = 'festival-gastronomie-tana'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Standard' AND tt.age_category = 'adult')
UNION ALL
SELECT evt.id, 'Standard', 'Billet Standard pour enfant. Accès général avec dégustations.', 'MGA', 20000, 1000, 20, 'child'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 12
FROM evt WHERE evt.slug = 'festival-gastronomie-tana'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Standard' AND tt.age_category = 'child')
UNION ALL
-- Festival Artisanat Majunga : Adulte et Enfant
SELECT evt.id, 'Billet Adulte', 'Accès complet pour un adulte. Inclut toutes les activités et démonstrations.', 'MGA', 20000, 1000, 20, 'adult'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 10
FROM evt WHERE evt.slug = 'festival-artisanat-majunga'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Billet Adulte')
UNION ALL
SELECT evt.id, 'Billet Enfant', 'Accès complet pour un enfant. Ateliers créatifs inclus.', 'MGA', 10000, 500, 20, 'child'::age_category_enum, evt.sales_starts_at, evt.sales_ends_at, 1, 10
FROM evt WHERE evt.slug = 'festival-artisanat-majunga'
AND NOT EXISTS (SELECT 1 FROM ticket_types tt WHERE tt.event_id = evt.id AND tt.name = 'Billet Enfant');

WITH tt AS (
    SELECT tt.id, ev.slug, tt.name, tt.age_category
    FROM ticket_types tt
    JOIN events ev ON ev.id = tt.event_id
    WHERE ev.slug IN (
        'concert-music-sunday', 
        'business-connect-mada',
        'festival-musique-toamasina',
        'concert-reggae-tana',
        'festival-plage-mahajanga',
        'conference-academique-tana',
        'concert-salegy-tana',
        'concert-rock-tana',
        'festival-artisanat-majunga',
        'conference-agriculture-tana',
        'concert-jazz-tana',
        'concert-electro-tana',
        'festival-cinema-nosy-be',
        'concert-folk-tana',
        'concert-world-tana',
        'concert-blues-tana',
        'festival-gastronomie-tana',
        'concert-pop-tana',
        'spectacle-culturel-toliara',
        'spectacle-danse-toliara',
        'concert-kabosy-night-tana',
        'concert-metal-mada-tana',
        'concert-afro-dancehall-tana',
        'concert-vako-drazana-tana',
        'concert-rap-gasy-tana',
        'concert-piano-bar-tana',
        'concert-tsapiky-fever-tana',
        'theatre-contemporain-tana',
        'concert-cabaret-tana',
        'concert-electro-night-tana',
        'concert-acoustic-tana'
    )
)
INSERT INTO ticket_inventory (
    ticket_type_id, total_quantity, reserved_quantity, sold_quantity
)
SELECT tt.id,
       CASE 
           WHEN tt.slug = 'concert-music-sunday' THEN 400
           WHEN tt.slug = 'business-connect-mada' THEN 250
           WHEN tt.slug = 'concert-reggae-tana' AND tt.name = 'Pass Standard' THEN 200
           WHEN tt.slug = 'concert-reggae-tana' AND tt.name = 'Pass Premium' THEN 100
           WHEN tt.slug = 'concert-salegy-tana' AND tt.name = 'Billet Adulte' THEN 250
           WHEN tt.slug = 'concert-salegy-tana' AND tt.name = 'Billet Enfant' THEN 150
           WHEN tt.slug = 'concert-rock-tana' AND tt.name = 'Pass Standard' THEN 150
           WHEN tt.slug = 'concert-rock-tana' AND tt.name = 'Pass Premium' THEN 50
           WHEN tt.slug = 'concert-folk-tana' AND tt.name = 'Pass Standard' THEN 100
           WHEN tt.slug = 'concert-folk-tana' AND tt.name = 'Pass Étudiant' THEN 50
           WHEN tt.slug = 'concert-world-tana' AND tt.name = 'Billet Adulte' THEN 250
           WHEN tt.slug = 'concert-world-tana' AND tt.name = 'Billet Enfant' THEN 150
           WHEN tt.slug = 'concert-blues-tana' AND tt.name = 'Pass Standard' THEN 250
           WHEN tt.slug = 'concert-blues-tana' AND tt.name = 'Pass Étudiant' THEN 100
           WHEN tt.slug = 'concert-pop-tana' AND tt.name = 'Pass Standard' THEN 150
           WHEN tt.slug = 'concert-pop-tana' AND tt.name = 'Pass Étudiant' THEN 100
           WHEN tt.slug = 'conference-academique-tana' AND tt.name = 'Pass Standard' THEN 400
           WHEN tt.slug = 'conference-academique-tana' AND tt.name = 'Pass Étudiant' THEN 200
           WHEN tt.slug = 'conference-agriculture-tana' AND tt.name = 'Pass Standard' THEN 200
            WHEN tt.slug = 'conference-agriculture-tana' AND tt.name = 'Pass Étudiant' THEN 100
            WHEN tt.slug = 'concert-jazz-tana' THEN 250
            WHEN tt.slug = 'concert-electro-tana' AND tt.name = 'Pass Standard' THEN 150
            WHEN tt.slug = 'concert-electro-tana' AND tt.name = 'Pass Premium' THEN 50
            WHEN tt.slug = 'spectacle-culturel-toliara' AND tt.name = 'Billet Adulte' THEN 250
            WHEN tt.slug = 'spectacle-culturel-toliara' AND tt.name = 'Billet Enfant' THEN 150
            WHEN tt.slug = 'spectacle-danse-toliara' AND tt.name = 'Billet Adulte' THEN 250
            WHEN tt.slug = 'spectacle-danse-toliara' AND tt.name = 'Billet Enfant' THEN 150
            WHEN tt.slug IN ('concert-kabosy-night-tana', 'concert-metal-mada-tana', 'concert-afro-dancehall-tana', 'concert-vako-drazana-tana', 'concert-rap-gasy-tana', 'concert-piano-bar-tana', 'concert-tsapiky-fever-tana', 'concert-cabaret-tana', 'concert-electro-night-tana', 'concert-acoustic-tana') THEN 300
            WHEN tt.slug = 'theatre-contemporain-tana' THEN 200
           ELSE 100
       END,
       0,
       0
FROM tt
ON CONFLICT (ticket_type_id) DO NOTHING;

WITH tt AS (
    SELECT tt.id, ev.slug, ev.sales_starts_at
    FROM ticket_types tt
    JOIN events ev ON ev.id = tt.event_id
    WHERE ev.slug IN (
        'concert-music-sunday', 
        'business-connect-mada',
        'festival-musique-toamasina',
        'concert-reggae-tana',
        'festival-plage-mahajanga',
        'concert-jazz-tana',
        'concert-salegy-tana',
        'concert-rock-tana',
        'spectacle-culturel-toliara',
        'spectacle-danse-toliara',
        'concert-kabosy-night-tana',
        'theatre-contemporain-tana',
        'concert-cabaret-tana',
        'concert-electro-night-tana',
        'concert-acoustic-tana'
    )
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
            'Antananarivo',
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
            'Antananarivo',
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
    SELECT v.id, 'Espace principal', 'Grande pelouse avec scène et aire de jeux', 500, 
        NOT EXISTS (
            SELECT 1 FROM venue_spaces vs2 
            WHERE vs2.venue_id = v.id AND vs2.is_default = TRUE
        )
    FROM venue_famille v
    WHERE NOT EXISTS (
        SELECT 1 FROM venue_spaces vs WHERE vs.venue_id = v.id AND vs.name = 'Espace principal'
    )
    RETURNING id, venue_id
),
space_corporate AS (
    INSERT INTO venue_spaces (venue_id, name, description, capacity, is_default)
    SELECT v.id, 'Salle principale', 'Amphithéâtre équipé pour conférences', 150, 
        NOT EXISTS (
            SELECT 1 FROM venue_spaces vs2 
            WHERE vs2.venue_id = v.id AND vs2.is_default = TRUE
        )
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
        ('2026-01-30 08:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
        ('2026-01-30 16:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
        ('2026-01-15 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
        ('2026-01-28 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
        '{"venue_name":"Parc des Familles","address":"Avenue de l''Indépendance","city":"Antananarivo","region":"Antananarivo","country":"MG"}'::jsonb,
        'vente-ticket/images/img1.png'
    FROM organizer
    CROSS JOIN venue_famille vf
    CROSS JOIN space_famille sf
    LEFT JOIN event_categories cat ON cat.slug = 'festival'
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
        ('2026-02-10 09:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
        ('2026-02-10 15:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
        ('2026-01-20 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
        ('2026-02-08 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
        '{"venue_name":"Centre de Conférences","address":"Zone Galaxy Andraharo","city":"Antananarivo","region":"Antananarivo","country":"MG"}'::jsonb,
        'vente-ticket/images/img2.png'
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
    WHEN 'festival-famille-enfants' THEN 'festival'
    ELSE 'concert'
END
ON CONFLICT DO NOTHING;

-- Médias pour les nouveaux événements
INSERT INTO event_media (event_id, media_type, url, alt_text, display_order, is_public)
SELECT evt.id,
       'image',
       CASE evt.slug
           WHEN 'seminaire-professionnel-adultes' THEN 'vente-ticket/images/img2.png'
           ELSE 'vente-ticket/images/img1.png'
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
        'Grand Concert Gala d''Antananarivo',
        'Soirée Prestige avec Stars Malgaches',
        'Un concert exceptionnel avec les plus grandes stars de Madagascar. Profitez de l''expérience VIP, Gold ou Silver selon vos préférences.',
        'Concert premium avec plusieurs catégories de billets. Types VIP avec accès prioritaire et zone exclusive, Gold avec meilleur placement, et Silver avec accès standard amélioré. Tarifs adaptés pour adultes et enfants.',
        'public'::event_visibility_enum,
        'published'::event_status_enum,
        'in_person',
        'Indian/Antananarivo',
        800,
        'fr-FR',
        TRUE,
        TRUE,
        ('2026-02-14 20:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
        ('2026-02-15 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
        ('2026-01-20 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
        ('2026-02-13 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
        '{"venue_name":"Salle Premium","address":"Boulevard de l''Indépendance","city":"Antananarivo","region":"Antananarivo","country":"MG"}'::jsonb,
        'vente-ticket/images/img1.png'
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
        ('2026-03-20 10:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
        ('2026-03-20 16:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
        ('2026-02-15 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
        ('2026-03-18 00:00:00'::timestamp AT TIME ZONE 'Indian/Antananarivo')::timestamptz,
        '{"venue_name":"Stade Municipal","address":"Avenue de la République","city":"Antananarivo","region":"Antananarivo","country":"MG"}'::jsonb,
        'vente-ticket/images/img2.png'
    FROM organizer
    CROSS JOIN venue_sport vs
    CROSS JOIN space_sport ss
    LEFT JOIN event_categories cat ON cat.slug = 'festival'
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
-- Types d'accessibilité
-- ===================================================================
INSERT INTO aiolia.types_accessibilite (code, libelle, url_image, ordre_affichage, est_actif)
VALUES
    ('acces1', 'Accès fauteuil roulant', 'images/acces1.svg', 1, TRUE),
    ('acces2', 'Accès malentendant', 'images/acces2.svg', 2, TRUE),
    ('acces3', 'Accès malvoyant', 'images/acces3.svg', 3, TRUE),
    ('acces4', 'Parking accessible', 'images/acces4.svg', 4, TRUE),
    ('acces5', 'Toilettes accessibles', 'images/acces5.svg', 5, TRUE),
    ('acces6', 'Transport accessible', 'images/acces6.svg', 6, TRUE)
ON CONFLICT (code) DO NOTHING;

-- ===================================================================
-- Liens événements - accessibilité
-- Attribution logique selon le type d'événement et le lieu
-- ===================================================================
WITH event_access AS (
    SELECT 
        e.id AS event_id,
        e.slug,
        ec.slug AS category_slug,
        v.slug AS venue_slug,
        ta.id AS type_accessibilite_id,
        ta.code
    FROM aiolia.events e
    CROSS JOIN aiolia.types_accessibilite ta
    JOIN aiolia.venues v ON v.id = e.venue_id
    LEFT JOIN aiolia.event_categories ec ON ec.id = e.primary_category_id
)
INSERT INTO aiolia.event_accessibility_links (event_id, type_accessibilite_id)
SELECT event_id, type_accessibilite_id
FROM event_access
WHERE 
    -- Accès fauteuil roulant (acces1) : tous les événements
    (code = 'acces1')
    OR
    -- Accès malentendant (acces2) : concerts, spectacles, conférences, festivals
    (code = 'acces2' AND (
        category_slug IN ('concert', 'business') OR
        slug LIKE '%festival%' OR
        slug LIKE '%spectacle%' OR
        slug LIKE '%conference%' OR
        slug LIKE '%seminaire%'
    ))
    OR
    -- Accès malvoyant (acces3) : conférences, business, spectacles, festivals culturels
    (code = 'acces3' AND (
        category_slug IN ('business', 'concert') OR
        slug LIKE '%conference%' OR
        slug LIKE '%seminaire%' OR
        slug LIKE '%spectacle%' OR
        slug LIKE '%workshop%'
    ))
    OR
    -- Parking accessible (acces4) : festivals, conférences, business, stades, parcs, plages
    (code = 'acces4' AND (
        slug LIKE '%festival%' OR 
        category_slug = 'business' OR
        slug LIKE '%conference%' OR
        slug LIKE '%seminaire%' OR
        venue_slug LIKE '%stade%' OR
        venue_slug LIKE '%parc%' OR
        venue_slug LIKE '%plage%'
    ))
    OR
    -- Toilettes accessibles (acces5) : tous les événements
    (code = 'acces5')
    OR
    -- Transport accessible (acces6) : festivals en plein air, stades, parcs, plages
    (code = 'acces6' AND (
        slug LIKE '%festival%' OR
        venue_slug LIKE '%stade%' OR
        venue_slug LIKE '%parc%' OR
        venue_slug LIKE '%plage%'
    ))
ON CONFLICT (event_id, type_accessibilite_id) DO NOTHING;

COMMIT;

