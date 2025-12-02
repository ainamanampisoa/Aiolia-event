-- ============================================================
--  AIOLIA – DONNÉES DE TEST POUR ORGANISATEURS & ÉVÉNEMENTS
--  Génération : 2025-01-XX
--  Version : Données de test pour le Sprint 2
-- ============================================================
--
-- IMPORTANT : Ce script nécessite que des utilisateurs existent déjà
-- dans la table utilisateurs avec les IDs 1, 2, 3, 4, 5.
-- 
-- Pour utiliser ce script :
-- 1. Assurez-vous d'avoir exécuté schema.sql
-- 2. Créez au moins 5 utilisateurs dans la table utilisateurs
-- 3. Exécutez ce script : psql -U aiolia_user -d aiolia_event -f dataOrgs.sql
--
-- Contenu :
-- - 10 catégories d'événements
-- - 15 tags d'événements
-- - 5 profils organisateurs (nécessite utilisateurs 1-5)
-- - 5 lieux
-- - 5 événements complets avec tous les détails
-- - Modes de paiement, langues, accessibilité, tags, médias
-- ============================================================

\c aiolia_event;
SET search_path TO aiolia, public;

-- ============================================================
-- Catégories d'événements
-- ============================================================
INSERT INTO aiolia.categories_evenements (slug, libelle, description, nom_icone, est_actif, ordre_affichage, cree_le)
VALUES
    ('concert', 'Concert', 'Concerts et spectacles musicaux', 'music', TRUE, 1, now()),
    ('sport', 'Sport', 'Événements sportifs et compétitions', 'sport', TRUE, 2, now()),
    ('conference', 'Conférence', 'Conférences et séminaires', 'conference', TRUE, 3, now()),
    ('festival', 'Festival', 'Festivals culturels et artistiques', 'festival', TRUE, 4, now()),
    ('theatre', 'Théâtre', 'Spectacles de théâtre et comédie', 'theatre', TRUE, 5, now()),
    ('exposition', 'Exposition', 'Expositions d''art et culturelles', 'exposition', TRUE, 6, now()),
    ('danse', 'Danse', 'Spectacles de danse', 'dance', TRUE, 7, now()),
    ('cinema', 'Cinéma', 'Projections et événements cinématographiques', 'cinema', TRUE, 8, now()),
    ('gastronomie', 'Gastronomie', 'Événements culinaires et dégustations', 'food', TRUE, 9, now()),
    ('atelier', 'Atelier', 'Ateliers et formations', 'workshop', TRUE, 10, now())
ON CONFLICT (slug) DO NOTHING;

-- ============================================================
-- Tags d'événements
-- ============================================================
INSERT INTO aiolia.tags_evenements (slug, libelle, cree_le)
VALUES
    ('jazz', 'Jazz', now()),
    ('rock', 'Rock', now()),
    ('pop', 'Pop', now()),
    ('traditionnel', 'Traditionnel', now()),
    ('contemporain', 'Contemporain', now()),
    ('gratuit', 'Gratuit', now()),
    ('payant', 'Payant', now()),
    ('en-plein-air', 'En plein air', now()),
    ('interieur', 'Intérieur', now()),
    ('famille', 'Famille', now()),
    ('adulte', 'Adulte', now()),
    ('jeune', 'Jeune', now()),
    ('premium', 'Premium', now()),
    ('local', 'Local', now()),
    ('international', 'International', now())
ON CONFLICT (slug) DO NOTHING;

-- ============================================================
-- Profils Organisateurs (nécessite des utilisateurs existants)
-- ============================================================
-- Note: Ces INSERT supposent qu'il existe déjà des utilisateurs avec id 1, 2, 3, etc.
-- Ajustez les id_utilisateur selon vos données réelles

INSERT INTO aiolia.profils_organisateurs (
    id_utilisateur, nom_affichage, nom_legal, email_support, telephone_support,
    url_site_web, biographie, type_organisation, statut_verification, onboarding_termine_le, cree_le, modifie_le
)
VALUES
    (
        1, 
        'Jazz Club Antananarivo', 
        'Jazz Club Antananarivo SARL',
        'contact@jazzclub-tana.mg',
        '+261341234567',
        'https://jazzclub-tana.mg',
        'Le Jazz Club Antananarivo est une référence pour les amateurs de jazz à Madagascar. Nous organisons des concerts réguliers avec des artistes locaux et internationaux.',
        'company',
        'verified',
        now(),
        now(),
        now()
    ),
    (
        2,
        'Madagascar Sports Events',
        'MSE Association',
        'info@msevents.mg',
        '+261341234568',
        'https://msevents.mg',
        'Organisation d''événements sportifs majeurs à Madagascar : marathons, tournois de football, compétitions d''athlétisme.',
        'non_profit',
        'verified',
        now(),
        now(),
        now()
    ),
    (
        3,
        'Centre Culturel Albert Camus',
        'CCAC',
        'contact@ccac.mg',
        '+261341234569',
        'https://ccac.mg',
        'Centre culturel proposant des conférences, expositions et spectacles variés pour promouvoir la culture à Madagascar.',
        'non_profit',
        'verified',
        now(),
        now(),
        now()
    ),
    (
        4,
        'Festival de Musique de Nosy Be',
        'FMNB',
        'festival@nosybe.mg',
        '+261341234570',
        NULL,
        'Festival annuel de musique sur l''île de Nosy Be, mettant en avant les talents musicaux malgaches.',
        'collective',
        'pending',
        NULL,
        now(),
        now()
    ),
    (
        5,
        'Théâtre Municipal d''Antananarivo',
        'TMA',
        'theatre@tana.mg',
        '+261341234571',
        'https://theatre-tana.mg',
        'Théâtre municipal proposant une programmation variée de pièces de théâtre, comédies et spectacles.',
        'non_profit',
        'verified',
        now(),
        now(),
        now()
    )
ON CONFLICT (id_utilisateur) DO NOTHING;

-- ============================================================
-- Lieux
-- ============================================================
INSERT INTO aiolia.lieux (
    id_profil_organisateur, nom, slug, description,
    ligne_adresse_1, ville, code_pays, latitude, longitude,
    email_contact, telephone_contact, capacite, est_actif, cree_le, modifie_le
)
VALUES
    (
        (SELECT id FROM aiolia.profils_organisateurs WHERE nom_affichage = 'Jazz Club Antananarivo' LIMIT 1),
        'Le Grand Café de la Gare',
        'grand-cafe-gare',
        'Café-restaurant avec scène pour concerts intimes',
        'Avenue de l''Indépendance',
        'Antananarivo',
        'MG',
        -18.8792,
        47.5079,
        'contact@grandcafe.mg',
        '+261341234567',
        150,
        TRUE,
        now(),
        now()
    ),
    (
        (SELECT id FROM aiolia.profils_organisateurs WHERE nom_affichage = 'Madagascar Sports Events' LIMIT 1),
        'Stade Municipal de Mahamasina',
        'stade-mahamasina',
        'Stade principal d''Antananarivo pour événements sportifs',
        'Avenue de l''Indépendance',
        'Antananarivo',
        'MG',
        -18.9200,
        47.5200,
        'stade@tana.mg',
        '+261341234568',
        40000,
        TRUE,
        now(),
        now()
    ),
    (
        (SELECT id FROM aiolia.profils_organisateurs WHERE nom_affichage = 'Centre Culturel Albert Camus' LIMIT 1),
        'Centre Culturel Albert Camus',
        'ccac-lieu',
        'Salle polyvalente pour conférences et expositions',
        'Rue Ratsimilaho',
        'Antananarivo',
        'MG',
        -18.9100,
        47.5300,
        'contact@ccac.mg',
        '+261341234569',
        300,
        TRUE,
        now(),
        now()
    ),
    (
        (SELECT id FROM aiolia.profils_organisateurs WHERE nom_affichage = 'Festival de Musique de Nosy Be' LIMIT 1),
        'Plage d''Andilana',
        'plage-andilana',
        'Plage publique pour événements en plein air',
        'Andilana',
        'Nosy Be',
        'MG',
        -13.4000,
        48.2500,
        NULL,
        NULL,
        5000,
        TRUE,
        now(),
        now()
    ),
    (
        (SELECT id FROM aiolia.profils_organisateurs WHERE nom_affichage = 'Théâtre Municipal d''Antananarivo' LIMIT 1),
        'Théâtre Municipal',
        'theatre-municipal',
        'Salle de spectacle avec 500 places',
        'Avenue de la République',
        'Antananarivo',
        'MG',
        -18.9000,
        47.5100,
        'theatre@tana.mg',
        '+261341234571',
        500,
        TRUE,
        now(),
        now()
    )
ON CONFLICT (slug) DO NOTHING;

-- ============================================================
-- Événements
-- ============================================================
INSERT INTO aiolia.evenements (
    id_profil_organisateur, id_categorie_principale, id_lieu,
    slug, titre, sous_titre, resume, description,
    statut, visibilite, format_evenement, capacite,
    commence_le, se_termine_le, ventes_commencent_le, ventes_se_terminent_le,
    code_langue, est_en_vedette, est_mis_en_avant,
    url_youtube, nom_lieu_texte, adresse_complete, tarif_unique,
    cree_le, modifie_le
)
VALUES
    (
        (SELECT id FROM aiolia.profils_organisateurs WHERE nom_affichage = 'Jazz Club Antananarivo' LIMIT 1),
        1,
        (SELECT id FROM aiolia.lieux WHERE slug = 'grand-cafe-gare' LIMIT 1),
        'jazz-show-kounde',
        'Jazz show avec Koundé',
        'Concert intime de jazz',
        'Soirée exceptionnelle avec le groupe Koundé qui vous emmènera dans un voyage musical à travers les standards du jazz et leurs compositions originales.',
        'Un concert intimiste dans l''ambiance chaleureuse du Grand Café de la Gare. Koundé, groupe phare de la scène jazz malgache, vous propose une soirée inoubliable mêlant jazz traditionnel et influences contemporaines.',
        'published',
        'public',
        'in_person',
        150,
        (now() + INTERVAL '30 days')::TIMESTAMPTZ,
        (now() + INTERVAL '30 days' + INTERVAL '3 hours')::TIMESTAMPTZ,
        now(),
        (now() + INTERVAL '29 days')::TIMESTAMPTZ,
        'fr-FR',
        TRUE,
        TRUE,
        NULL,
        'Le Grand Café de la Gare',
        'Avenue de l''Indépendance, Antananarivo 101',
        FALSE,
        now(),
        now()
    ),
    (
        (SELECT id FROM aiolia.profils_organisateurs WHERE nom_affichage = 'Madagascar Sports Events' LIMIT 1),
        2,
        (SELECT id FROM aiolia.lieux WHERE slug = 'stade-mahamasina' LIMIT 1),
        'marathon-tana-2025',
        'Marathon International d''Antananarivo 2025',
        'Course à pied - 42km, 21km, 10km',
        'Le plus grand événement de course à pied de Madagascar. Rejoignez des milliers de coureurs pour cette édition 2025 avec parcours certifié.',
        'Trois distances au choix : marathon complet (42km), semi-marathon (21km) et course populaire (10km). Parcours traversant les plus beaux quartiers de la capitale. Médaille finisher et t-shirt pour tous les participants.',
        'published',
        'public',
        'in_person',
        5000,
        (now() + INTERVAL '60 days')::TIMESTAMPTZ,
        (now() + INTERVAL '60 days' + INTERVAL '6 hours')::TIMESTAMPTZ,
        now(),
        (now() + INTERVAL '59 days')::TIMESTAMPTZ,
        'fr-FR',
        TRUE,
        FALSE,
        NULL,
        'Stade Municipal de Mahamasina',
        'Avenue de l''Indépendance, Antananarivo',
        FALSE,
        now(),
        now()
    ),
    (
        (SELECT id FROM aiolia.profils_organisateurs WHERE nom_affichage = 'Centre Culturel Albert Camus' LIMIT 1),
        3,
        (SELECT id FROM aiolia.lieux WHERE slug = 'ccac-lieu' LIMIT 1),
        'conference-entrepreneuriat-2025',
        'Conférence sur l''Entrepreneuriat à Madagascar',
        'Opportunités et défis pour les jeunes entrepreneurs',
        'Une journée de conférences et d''échanges sur l''entrepreneuriat à Madagascar avec des intervenants locaux et internationaux.',
        'Programme complet : conférences plénières, ateliers pratiques, networking et témoignages d''entrepreneurs à succès. Déjeuner inclus.',
        'published',
        'public',
        'in_person',
        300,
        (now() + INTERVAL '45 days')::TIMESTAMPTZ,
        (now() + INTERVAL '45 days' + INTERVAL '8 hours')::TIMESTAMPTZ,
        now(),
        (now() + INTERVAL '44 days')::TIMESTAMPTZ,
        'fr-FR',
        FALSE,
        FALSE,
        NULL,
        'Centre Culturel Albert Camus',
        'Rue Ratsimilaho, Antananarivo',
        TRUE,
        now(),
        now()
    ),
    (
        (SELECT id FROM aiolia.profils_organisateurs WHERE nom_affichage = 'Festival de Musique de Nosy Be' LIMIT 1),
        4,
        (SELECT id FROM aiolia.lieux WHERE slug = 'plage-andilana' LIMIT 1),
        'festival-musique-nosybe-2025',
        'Festival de Musique de Nosy Be 2025',
        '3 jours de musique sur la plage',
        'Le plus grand festival de musique de l''océan Indien. 3 jours de concerts avec des artistes locaux et internationaux sur la magnifique plage d''Andilana.',
        'Programmation variée : musique traditionnelle malgache, reggae, jazz, pop. Restauration et hébergement sur place. Camping autorisé.',
        'published',
        'public',
        'in_person',
        5000,
        (now() + INTERVAL '90 days')::TIMESTAMPTZ,
        (now() + INTERVAL '92 days')::TIMESTAMPTZ,
        now(),
        (now() + INTERVAL '89 days')::TIMESTAMPTZ,
        'fr-FR',
        TRUE,
        TRUE,
        'https://www.youtube.com/watch?v=exemple',
        'Plage d''Andilana',
        'Andilana, Nosy Be',
        FALSE,
        now(),
        now()
    ),
    (
        (SELECT id FROM aiolia.profils_organisateurs WHERE nom_affichage = 'Théâtre Municipal d''Antananarivo' LIMIT 1),
        5,
        (SELECT id FROM aiolia.lieux WHERE slug = 'theatre-municipal' LIMIT 1),
        'piece-theatre-hira-gasikara',
        'Hira Gasikara - Pièce de Théâtre',
        'Comédie dramatique en malgache',
        'Une pièce de théâtre contemporaine qui explore les réalités sociales de Madagascar à travers l''histoire d''une famille tananarivienne.',
        'Mise en scène moderne avec des acteurs talentueux. Durée : 2h avec entracte. Texte en malgache avec sous-titres français disponibles.',
        'published',
        'public',
        'in_person',
        500,
        (now() + INTERVAL '20 days')::TIMESTAMPTZ,
        (now() + INTERVAL '20 days' + INTERVAL '2 hours 30 minutes')::TIMESTAMPTZ,
        now(),
        (now() + INTERVAL '19 days')::TIMESTAMPTZ,
        'mg',
        FALSE,
        FALSE,
        NULL,
        'Théâtre Municipal',
        'Avenue de la République, Antananarivo',
        FALSE,
        now(),
        now()
    )
ON CONFLICT (slug) DO NOTHING;

-- ============================================================
-- Modes de paiement par événement
-- ============================================================
-- Pour chaque événement, on ajoute plusieurs modes de paiement
INSERT INTO aiolia.modes_paiement_evenements (id_evenement, mode_paiement, est_actif, cree_le)
SELECT e.id, 'mastercard', TRUE, now()
FROM aiolia.evenements e
WHERE e.slug = 'jazz-show-kounde'
ON CONFLICT (id_evenement, mode_paiement) DO NOTHING;

INSERT INTO aiolia.modes_paiement_evenements (id_evenement, mode_paiement, est_actif, cree_le)
SELECT e.id, 'visa', TRUE, now()
FROM aiolia.evenements e
WHERE e.slug = 'jazz-show-kounde'
ON CONFLICT (id_evenement, mode_paiement) DO NOTHING;

INSERT INTO aiolia.modes_paiement_evenements (id_evenement, mode_paiement, est_actif, cree_le)
SELECT e.id, 'mvola', TRUE, now()
FROM aiolia.evenements e
WHERE e.slug = 'jazz-show-kounde'
ON CONFLICT (id_evenement, mode_paiement) DO NOTHING;

INSERT INTO aiolia.modes_paiement_evenements (id_evenement, mode_paiement, est_actif, cree_le)
SELECT e.id, 'orange', TRUE, now()
FROM aiolia.evenements e
WHERE e.slug = 'jazz-show-kounde'
ON CONFLICT (id_evenement, mode_paiement) DO NOTHING;

INSERT INTO aiolia.modes_paiement_evenements (id_evenement, mode_paiement, est_actif, cree_le)
SELECT e.id, 'airtel', TRUE, now()
FROM aiolia.evenements e
WHERE e.slug = 'jazz-show-kounde'
ON CONFLICT (id_evenement, mode_paiement) DO NOTHING;

-- Marathon - tous les modes de paiement
INSERT INTO aiolia.modes_paiement_evenements (id_evenement, mode_paiement, est_actif, cree_le)
SELECT e.id, pm, TRUE, now()
FROM aiolia.evenements e
CROSS JOIN unnest(ARRAY['mastercard', 'visa', 'mvola', 'orange', 'airtel', 'espace']::TEXT[]) AS pm
WHERE e.slug = 'marathon-tana-2025'
ON CONFLICT (id_evenement, mode_paiement) DO NOTHING;

-- Conférence - paiement mobile uniquement
INSERT INTO aiolia.modes_paiement_evenements (id_evenement, mode_paiement, est_actif, cree_le)
SELECT e.id, pm, TRUE, now()
FROM aiolia.evenements e
CROSS JOIN unnest(ARRAY['mvola', 'orange', 'airtel']::TEXT[]) AS pm
WHERE e.slug = 'conference-entrepreneuriat-2025'
ON CONFLICT (id_evenement, mode_paiement) DO NOTHING;

-- Festival - tous les modes
INSERT INTO aiolia.modes_paiement_evenements (id_evenement, mode_paiement, est_actif, cree_le)
SELECT e.id, pm, TRUE, now()
FROM aiolia.evenements e
CROSS JOIN unnest(ARRAY['mastercard', 'visa', 'mvola', 'orange', 'airtel', 'espace', 'bank_transfer']::TEXT[]) AS pm
WHERE e.slug = 'festival-musique-nosybe-2025'
ON CONFLICT (id_evenement, mode_paiement) DO NOTHING;

-- Théâtre - paiement mobile et cartes
INSERT INTO aiolia.modes_paiement_evenements (id_evenement, mode_paiement, est_actif, cree_le)
SELECT e.id, pm, TRUE, now()
FROM aiolia.evenements e
CROSS JOIN unnest(ARRAY['mastercard', 'visa', 'mvola', 'orange']::TEXT[]) AS pm
WHERE e.slug = 'piece-theatre-hira-gasikara'
ON CONFLICT (id_evenement, mode_paiement) DO NOTHING;

-- ============================================================
-- Langues par événement
-- ============================================================
INSERT INTO aiolia.langues_evenements (id_evenement, code_langue, cree_le)
SELECT e.id, 'fr', now()
FROM aiolia.evenements e
WHERE e.slug = 'jazz-show-kounde'
ON CONFLICT (id_evenement, code_langue) DO NOTHING;

INSERT INTO aiolia.langues_evenements (id_evenement, code_langue, cree_le)
SELECT e.id, 'mg', now()
FROM aiolia.evenements e
WHERE e.slug = 'jazz-show-kounde'
ON CONFLICT (id_evenement, code_langue) DO NOTHING;

INSERT INTO aiolia.langues_evenements (id_evenement, code_langue, cree_le)
SELECT e.id, lang, now()
FROM aiolia.evenements e
CROSS JOIN unnest(ARRAY['fr', 'mg', 'en']::VARCHAR[]) AS lang
WHERE e.slug = 'marathon-tana-2025'
ON CONFLICT (id_evenement, code_langue) DO NOTHING;

INSERT INTO aiolia.langues_evenements (id_evenement, code_langue, cree_le)
SELECT e.id, lang, now()
FROM aiolia.evenements e
CROSS JOIN unnest(ARRAY['fr', 'mg']::VARCHAR[]) AS lang
WHERE e.slug = 'conference-entrepreneuriat-2025'
ON CONFLICT (id_evenement, code_langue) DO NOTHING;

INSERT INTO aiolia.langues_evenements (id_evenement, code_langue, cree_le)
SELECT e.id, lang, now()
FROM aiolia.evenements e
CROSS JOIN unnest(ARRAY['fr', 'mg', 'en']::VARCHAR[]) AS lang
WHERE e.slug = 'festival-musique-nosybe-2025'
ON CONFLICT (id_evenement, code_langue) DO NOTHING;

INSERT INTO aiolia.langues_evenements (id_evenement, code_langue, cree_le)
SELECT e.id, 'mg', now()
FROM aiolia.evenements e
WHERE e.slug = 'piece-theatre-hira-gasikara'
ON CONFLICT (id_evenement, code_langue) DO NOTHING;

INSERT INTO aiolia.langues_evenements (id_evenement, code_langue, cree_le)
SELECT e.id, 'fr', now()
FROM aiolia.evenements e
WHERE e.slug = 'piece-theatre-hira-gasikara'
ON CONFLICT (id_evenement, code_langue) DO NOTHING;

-- ============================================================
-- Accessibilité par événement
-- ============================================================
-- Jazz Show - accessible fauteuil roulant
INSERT INTO aiolia.accessibilite_evenements (id_evenement, type_accessibilite, description, cree_le)
SELECT e.id, 'wheelchair', 'Accès fauteuil roulant disponible, rampe d''accès', now()
FROM aiolia.evenements e
WHERE e.slug = 'jazz-show-kounde'
ON CONFLICT (id_evenement, type_accessibilite) DO NOTHING;

-- Marathon - accessible mobilité réduite
INSERT INTO aiolia.accessibilite_evenements (id_evenement, type_accessibilite, description, cree_le)
SELECT e.id, 'mobility', 'Parcours adapté pour personnes à mobilité réduite', now()
FROM aiolia.evenements e
WHERE e.slug = 'marathon-tana-2025'
ON CONFLICT (id_evenement, type_accessibilite) DO NOTHING;

-- Conférence - accessible fauteuil et malentendants
INSERT INTO aiolia.accessibilite_evenements (id_evenement, type_accessibilite, description, cree_le)
SELECT e.id, acc_type, 
       CASE 
           WHEN acc_type = 'wheelchair' THEN 'Accès fauteuil roulant'
           ELSE 'Boucles magnétiques disponibles'
       END,
       now()
FROM aiolia.evenements e
CROSS JOIN unnest(ARRAY['wheelchair', 'hearing']::TEXT[]) AS acc_type
WHERE e.slug = 'conference-entrepreneuriat-2025'
ON CONFLICT (id_evenement, type_accessibilite) DO NOTHING;

-- Festival - accessible fauteuil
INSERT INTO aiolia.accessibilite_evenements (id_evenement, type_accessibilite, description, cree_le)
SELECT e.id, 'wheelchair', 'Zone accessible en fauteuil roulant prévue', now()
FROM aiolia.evenements e
WHERE e.slug = 'festival-musique-nosybe-2025'
ON CONFLICT (id_evenement, type_accessibilite) DO NOTHING;

-- Théâtre - accessible fauteuil et malentendants
INSERT INTO aiolia.accessibilite_evenements (id_evenement, type_accessibilite, description, cree_le)
SELECT e.id, acc_type,
       CASE 
           WHEN acc_type = 'wheelchair' THEN 'Places accessibles au rez-de-chaussée'
           ELSE 'Sous-titrage disponible'
       END,
       now()
FROM aiolia.evenements e
CROSS JOIN unnest(ARRAY['wheelchair', 'hearing']::TEXT[]) AS acc_type
WHERE e.slug = 'piece-theatre-hira-gasikara'
ON CONFLICT (id_evenement, type_accessibilite) DO NOTHING;

-- ============================================================
-- Tags par événement
-- ============================================================
INSERT INTO aiolia.liens_tags_evenements (id_evenement, id_tag)
SELECT e.id, t.id
FROM aiolia.evenements e, aiolia.tags_evenements t
WHERE e.slug = 'jazz-show-kounde' 
  AND t.slug IN ('jazz', 'payant', 'interieur', 'adulte', 'local')
ON CONFLICT (id_evenement, id_tag) DO NOTHING;

INSERT INTO aiolia.liens_tags_evenements (id_evenement, id_tag)
SELECT e.id, t.id
FROM aiolia.evenements e, aiolia.tags_evenements t
WHERE e.slug = 'marathon-tana-2025' 
  AND t.slug IN ('sport', 'payant', 'en-plein-air', 'famille', 'international')
ON CONFLICT (id_evenement, id_tag) DO NOTHING;

INSERT INTO aiolia.liens_tags_evenements (id_evenement, id_tag)
SELECT e.id, t.id
FROM aiolia.evenements e, aiolia.tags_evenements t
WHERE e.slug = 'conference-entrepreneuriat-2025' 
  AND t.slug IN ('payant', 'interieur', 'adulte', 'local')
ON CONFLICT (id_evenement, id_tag) DO NOTHING;

INSERT INTO aiolia.liens_tags_evenements (id_evenement, id_tag)
SELECT e.id, t.id
FROM aiolia.evenements e, aiolia.tags_evenements t
WHERE e.slug = 'festival-musique-nosybe-2025' 
  AND t.slug IN ('festival', 'payant', 'en-plein-air', 'famille', 'local', 'premium')
ON CONFLICT (id_evenement, id_tag) DO NOTHING;

INSERT INTO aiolia.liens_tags_evenements (id_evenement, id_tag)
SELECT e.id, t.id
FROM aiolia.evenements e, aiolia.tags_evenements t
WHERE e.slug = 'piece-theatre-hira-gasikara' 
  AND t.slug IN ('theatre', 'payant', 'interieur', 'adulte', 'local', 'traditionnel')
ON CONFLICT (id_evenement, id_tag) DO NOTHING;

-- ============================================================
-- Médias d'événements (affiches et photos)
-- ============================================================
INSERT INTO aiolia.medias_evenements (
    id_evenement, type_media, url, texte_alternatif, ordre_affichage, 
    est_public, format_affiche, est_affiche_principale, cree_le
)
SELECT 
    e.id,
    'image',
    'https://example.com/images/jazz-show-kounde-poster.jpg',
    'Affiche du concert Jazz Show avec Koundé',
    0,
    TRUE,
    'portrait',
    TRUE,
    now()
FROM aiolia.evenements e
WHERE e.slug = 'jazz-show-kounde';

INSERT INTO aiolia.medias_evenements (
    id_evenement, type_media, url, texte_alternatif, ordre_affichage, 
    est_public, format_affiche, est_affiche_principale, cree_le
)
SELECT 
    e.id,
    'image',
    'https://example.com/images/marathon-tana-2025-poster.jpg',
    'Affiche du Marathon International d''Antananarivo 2025',
    0,
    TRUE,
    'paysage',
    TRUE,
    now()
FROM aiolia.evenements e
WHERE e.slug = 'marathon-tana-2025'
ON CONFLICT DO NOTHING;

INSERT INTO aiolia.medias_evenements (
    id_evenement, type_media, url, texte_alternatif, ordre_affichage, 
    est_public, format_affiche, est_affiche_principale, cree_le
)
SELECT 
    e.id,
    'image',
    'https://example.com/images/conference-entrepreneuriat-poster.jpg',
    'Affiche de la conférence sur l''entrepreneuriat',
    0,
    TRUE,
    'portrait',
    TRUE,
    now()
FROM aiolia.evenements e
WHERE e.slug = 'conference-entrepreneuriat-2025'
ON CONFLICT DO NOTHING;

INSERT INTO aiolia.medias_evenements (
    id_evenement, type_media, url, texte_alternatif, ordre_affichage, 
    est_public, format_affiche, est_affiche_principale, cree_le
)
SELECT 
    e.id,
    'image',
    'https://example.com/images/festival-nosybe-poster.jpg',
    'Affiche du Festival de Musique de Nosy Be 2025',
    0,
    TRUE,
    'paysage',
    TRUE,
    now()
FROM aiolia.evenements e
WHERE e.slug = 'festival-musique-nosybe-2025'
ON CONFLICT DO NOTHING;

INSERT INTO aiolia.medias_evenements (
    id_evenement, type_media, url, texte_alternatif, ordre_affichage, 
    est_public, format_affiche, est_affiche_principale, cree_le
)
SELECT 
    e.id,
    'image',
    'https://example.com/images/theatre-hira-gasikara-poster.jpg',
    'Affiche de la pièce de théâtre Hira Gasikara',
    0,
    TRUE,
    'portrait',
    TRUE,
    now()
FROM aiolia.evenements e
WHERE e.slug = 'piece-theatre-hira-gasikara'
ON CONFLICT DO NOTHING;

-- Photos additionnelles pour le festival
INSERT INTO aiolia.medias_evenements (
    id_evenement, type_media, url, texte_alternatif, ordre_affichage, 
    est_public, format_affiche, est_affiche_principale, cree_le
)
SELECT 
    e.id,
    'image',
    photo_data.url,
    photo_data.alt,
    photo_data.ordre,
    TRUE,
    NULL,
    FALSE,
    now()
FROM aiolia.evenements e
CROSS JOIN (
    SELECT * FROM (VALUES
        ('https://example.com/images/festival-nosybe-photo1.jpg', 'Photo du festival - Scène principale', 1),
        ('https://example.com/images/festival-nosybe-photo2.jpg', 'Photo du festival - Public', 2),
        ('https://example.com/images/festival-nosybe-photo3.jpg', 'Photo du festival - Artistes', 3)
    ) AS t(url, alt, ordre)
) AS photo_data
WHERE e.slug = 'festival-musique-nosybe-2025';

-- ============================================================
-- Récapitulatif
-- ============================================================
-- Données insérées :
-- - 10 catégories d'événements
-- - 15 tags d'événements
-- - 5 profils organisateurs
-- - 5 lieux
-- - 5 événements complets
-- - Modes de paiement pour chaque événement
-- - Langues pour chaque événement
-- - Accessibilité pour chaque événement
-- - Tags associés aux événements
-- - Médias (affiches et photos) pour chaque événement

