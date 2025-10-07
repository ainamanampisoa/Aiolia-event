-- ============================================================================
-- DONNÉES DE BASE (SEEDS)
-- ============================================================================

-- ============================================================================
-- 1. PERMISSIONS
-- ============================================================================

INSERT INTO permissions (name, description, module) VALUES
-- Module Utilisateurs
('user.view', 'Voir les utilisateurs', 'users'),
('user.create', 'Créer des utilisateurs', 'users'),
('user.edit', 'Modifier des utilisateurs', 'users'),
('user.delete', 'Supprimer des utilisateurs', 'users'),

-- Module Événements
('event.view', 'Voir les événements', 'events'),
('event.create', 'Créer des événements', 'events'),
('event.edit', 'Modifier des événements', 'events'),
('event.delete', 'Supprimer des événements', 'events'),
('event.publish', 'Publier des événements', 'events'),
('event.manage_team', 'Gérer l\'équipe d\'événement', 'events'),

-- Module Billets
('ticket.view', 'Voir les billets', 'tickets'),
('ticket.create', 'Créer des billets', 'tickets'),
('ticket.edit', 'Modifier des billets', 'tickets'),
('ticket.delete', 'Supprimer des billets', 'tickets'),
('ticket.scan', 'Scanner les billets (check-in)', 'tickets'),

-- Module Commandes
('order.view', 'Voir les commandes', 'orders'),
('order.view_all', 'Voir toutes les commandes', 'orders'),
('order.manage', 'Gérer les commandes', 'orders'),
('order.refund', 'Rembourser les commandes', 'orders'),

-- Module Codes Promo
('promo.view', 'Voir les codes promo', 'promo'),
('promo.create', 'Créer des codes promo', 'promo'),
('promo.edit', 'Modifier des codes promo', 'promo'),
('promo.delete', 'Supprimer des codes promo', 'promo'),

-- Module Rapports
('report.view', 'Voir les rapports', 'reports'),
('report.generate', 'Générer des rapports', 'reports'),
('report.export', 'Exporter des rapports', 'reports'),

-- Module Statistiques
('stats.view', 'Voir les statistiques', 'statistics'),
('stats.view_all', 'Voir toutes les statistiques', 'statistics'),

-- Module Administration
('admin.settings', 'Gérer les paramètres système', 'admin'),
('admin.users', 'Administrer les utilisateurs', 'admin'),
('admin.logs', 'Voir les logs d\'audit', 'admin');

-- ============================================================================
-- 2. ATTRIBUTION DES PERMISSIONS PAR RÔLE
-- ============================================================================

-- Permissions pour UTILISATEUR standard
INSERT INTO role_permissions (role, permission_id)
SELECT 'user', id FROM permissions WHERE name IN (
    'event.view',
    'ticket.view',
    'order.view'
);

-- Permissions pour CO-ORGANISATEUR
INSERT INTO role_permissions (role, permission_id)
SELECT 'co_organizer', id FROM permissions WHERE name IN (
    'event.view',
    'event.edit',
    'ticket.view',
    'ticket.create',
    'ticket.edit',
    'ticket.scan',
    'order.view',
    'promo.view',
    'promo.create',
    'stats.view',
    'report.view',
    'report.generate'
);

-- Permissions pour ORGANISATEUR
INSERT INTO role_permissions (role, permission_id)
SELECT 'organizer', id FROM permissions WHERE name IN (
    'event.view',
    'event.create',
    'event.edit',
    'event.delete',
    'event.publish',
    'event.manage_team',
    'ticket.view',
    'ticket.create',
    'ticket.edit',
    'ticket.delete',
    'ticket.scan',
    'order.view',
    'order.manage',
    'order.refund',
    'promo.view',
    'promo.create',
    'promo.edit',
    'promo.delete',
    'report.view',
    'report.generate',
    'report.export',
    'stats.view'
);

-- Permissions pour ADMIN (toutes les permissions)
INSERT INTO role_permissions (role, permission_id)
SELECT 'admin', id FROM permissions;

-- ============================================================================
-- 3. CATÉGORIES D'ÉVÉNEMENTS
-- ============================================================================

INSERT INTO event_categories (name, slug, description, icon, display_order) VALUES
('Concert', 'concert', 'Concerts et spectacles musicaux en live', '🎵', 1),
('Conférence', 'conference', 'Conférences, séminaires et événements professionnels', '📊', 2),
('Sport', 'sport', 'Événements sportifs et compétitions', '⚽', 3),
('Théâtre', 'theatre', 'Pièces de théâtre et spectacles', '🎭', 4),
('Festival', 'festival', 'Festivals culturels et artistiques', '🎪', 5),
('Exposition', 'exposition', 'Expositions d\'art et musées', '🎨', 6),
('Formation', 'formation', 'Ateliers et formations professionnelles', '📚', 7),
('Gastronomie', 'gastronomie', 'Événements culinaires et dégustations', '🍽️', 8),
('Enfants', 'enfants', 'Événements pour enfants et familles', '🎈', 9),
('Nightlife', 'nightlife', 'Soirées et événements nocturnes', '🌙', 10),
('Networking', 'networking', 'Événements de networking professionnel', '🤝', 11),
('Autre', 'autre', 'Autres types d\'événements', '📅', 99);

-- ============================================================================
-- 4. PARAMÈTRES SYSTÈME
-- ============================================================================

INSERT INTO system_settings (setting_key, setting_value, setting_type, description, is_public) VALUES
-- Paramètres généraux
('site_name', 'Aiolia Event', 'string', 'Nom du site', TRUE),
('site_description', 'Plateforme de gestion d\'événements à Madagascar', 'string', 'Description du site', TRUE),
('support_email', 'support@aiolia-event.com', 'string', 'Email de support', TRUE),
('default_currency', 'MGA', 'string', 'Devise par défaut', TRUE),
('default_language', 'fr', 'string', 'Langue par défaut', TRUE),
('timezone', 'Indian/Antananarivo', 'string', 'Fuseau horaire', TRUE),

-- Paramètres de paiement
('payment_orange_enabled', 'true', 'boolean', 'Activer Orange Money', FALSE),
('payment_airtel_enabled', 'true', 'boolean', 'Activer Airtel Money', FALSE),
('payment_telma_enabled', 'true', 'boolean', 'Activer Telma Money', FALSE),
('payment_fee_percentage', '2.5', 'number', 'Frais de paiement (%)', FALSE),

-- Paramètres de panier
('cart_expiration_hours', '24', 'number', 'Expiration du panier (heures)', FALSE),
('cart_reservation_minutes', '15', 'number', 'Durée de réservation des billets dans le panier (minutes)', FALSE),

-- Paramètres de billets
('ticket_transfer_enabled', 'true', 'boolean', 'Autoriser le transfert de billets', TRUE),
('ticket_transfer_deadline_hours', '48', 'number', 'Délai avant événement pour transférer (heures)', FALSE),
('ticket_refund_enabled', 'true', 'boolean', 'Autoriser les remboursements', TRUE),
('ticket_refund_deadline_days', '7', 'number', 'Délai pour demander un remboursement (jours)', FALSE),

-- Paramètres de fidélité
('loyalty_enabled', 'true', 'boolean', 'Activer le programme de fidélité', TRUE),
('loyalty_points_per_1000mga', '1', 'number', 'Points par tranche de 1000 MGA', FALSE),
('loyalty_bronze_threshold', '0', 'number', 'Seuil points tier Bronze', FALSE),
('loyalty_silver_threshold', '500', 'number', 'Seuil points tier Silver', FALSE),
('loyalty_gold_threshold', '2000', 'number', 'Seuil points tier Gold', FALSE),
('loyalty_platinum_threshold', '5000', 'number', 'Seuil points tier Platinum', FALSE),

-- Paramètres de parrainage
('referral_enabled', 'true', 'boolean', 'Activer le parrainage', TRUE),
('referral_discount_amount', '5000', 'number', 'Réduction pour le filleul (MGA)', FALSE),
('referral_reward_points', '50', 'number', 'Points pour le parrain', FALSE),

-- Paramètres de mini-jeu
('game_enabled', 'true', 'boolean', 'Activer le mini-jeu Ticket Chance', TRUE),
('game_max_plays_daily', '1', 'number', 'Parties max par jour', FALSE),
('game_prize_discount_probability', '30', 'number', 'Probabilité réduction (%)', FALSE),
('game_prize_ticket_probability', '5', 'number', 'Probabilité billet gratuit (%)', FALSE),
('game_prize_points_probability', '50', 'number', 'Probabilité points (%)', FALSE),

-- Paramètres de notifications
('notification_email_enabled', 'true', 'boolean', 'Activer notifications email', FALSE),
('notification_push_enabled', 'true', 'boolean', 'Activer notifications push', FALSE),
('notification_sms_enabled', 'false', 'boolean', 'Activer notifications SMS', FALSE),
('notification_event_reminder_hours', '24', 'number', 'Rappel avant événement (heures)', FALSE),

-- Paramètres d\'organisateurs
('organizer_commission_percentage', '10', 'number', 'Commission plateforme (%)', FALSE),
('organizer_payout_delay_days', '7', 'number', 'Délai avant paiement organisateur (jours)', FALSE),
('organizer_min_payout', '50000', 'number', 'Montant minimum de retrait (MGA)', FALSE),

-- Paramètres de recherche
('search_results_per_page', '20', 'number', 'Résultats par page', TRUE),
('featured_events_count', '10', 'number', 'Nombre d\'événements en vedette', TRUE),

-- Paramètres de fichiers
('max_upload_size_mb', '10', 'number', 'Taille max upload (MB)', FALSE),
('allowed_image_types', 'jpg,jpeg,png,webp', 'string', 'Types d\'images autorisés', FALSE),
('allowed_video_types', 'mp4,webm', 'string', 'Types de vidéos autorisés', FALSE);

-- ============================================================================
-- 5. RÈGLES DE FIDÉLITÉ
-- ============================================================================

INSERT INTO loyalty_rules (name, description, event_type, points_earned, min_amount) VALUES
('Achat de billet', 'Points pour achat de billet', 'purchase', 1, 1000),
('Parrainage réussi', 'Points pour parrainage réussi', 'referral', 50, NULL),
('Avis publié', 'Points pour publication d\'avis', 'review', 5, NULL),
('Gain au jeu', 'Points gagnés au mini-jeu', 'game_win', 10, NULL);

-- ============================================================================
-- 6. CONFIGURATION DU MINI-JEU
-- ============================================================================

INSERT INTO game_settings (
    game_type, 
    max_plays_per_user_daily, 
    max_plays_per_user_total, 
    prize_probabilities, 
    is_active,
    start_date,
    end_date
) VALUES (
    'ticket_chance',
    1,
    NULL,
    JSON_OBJECT(
        'discount', 30,
        'free_ticket', 5,
        'points', 50,
        'nothing', 15
    ),
    TRUE,
    NOW(),
    NULL
);

-- ============================================================================
-- 7. UTILISATEURS DE TEST (À SUPPRIMER EN PRODUCTION)
-- ============================================================================

-- Mot de passe pour tous : "Password123!"
-- Hash bcrypt de "Password123!"
SET @password_hash = '$2a$10$YourHashHere';

INSERT INTO users (email, password_hash, first_name, last_name, phone, role, email_verified, oauth_provider) VALUES
('admin@aiolia-event.com', @password_hash, 'Admin', 'System', '+261340000001', 'admin', TRUE, 'local'),
('organizer@aiolia-event.com', @password_hash, 'Jean', 'Rakoto', '+261340000002', 'organizer', TRUE, 'local'),
('user@aiolia-event.com', @password_hash, 'Marie', 'Rasoa', '+261340000003', 'user', TRUE, 'local');

-- ============================================================================
-- 8. ÉVÉNEMENT DE DÉMONSTRATION
-- ============================================================================

INSERT INTO events (
    organizer_id,
    category_id,
    title,
    slug,
    description,
    short_description,
    location,
    address,
    latitude,
    longitude,
    start_date,
    end_date,
    status,
    is_featured,
    total_capacity
)
SELECT 
    u.id,
    1, -- Concert
    'Festival de Musique Malagasy 2025',
    'festival-musique-malagasy-2025',
    'Le plus grand festival de musique à Madagascar ! Venez découvrir les meilleurs artistes locaux et internationaux dans une ambiance exceptionnelle. Au programme : concerts, spectacles, animations et bien plus encore !',
    'Le plus grand festival de musique à Madagascar avec les meilleurs artistes',
    'Mahamasina Stadium',
    'Mahamasina, Antananarivo, Madagascar',
    -18.9078,
    47.5270,
    DATE_ADD(NOW(), INTERVAL 60 DAY),
    DATE_ADD(NOW(), INTERVAL 62 DAY),
    'published',
    TRUE,
    10000
FROM users u
WHERE u.email = 'organizer@aiolia-event.com'
LIMIT 1;

-- Catégories de billets pour l'événement de démo
INSERT INTO ticket_categories (
    event_id,
    name,
    description,
    price,
    original_price,
    quantity_total,
    quantity_sold,
    quantity_reserved,
    min_purchase,
    max_purchase,
    sale_start_date,
    sale_end_date,
    perks
)
SELECT 
    e.id,
    'VIP',
    'Accès VIP avec places assises premium, backstage et meet & greet',
    150000.00,
    150000.00,
    500,
    0,
    0,
    1,
    10,
    NOW(),
    e.start_date,
    JSON_ARRAY('Accès backstage', 'Meet & greet', 'Places assises premium', 'Boisson offerte')
FROM events e
WHERE e.slug = 'festival-musique-malagasy-2025'
UNION ALL
SELECT 
    e.id,
    'Standard',
    'Billet d\'accès standard au festival',
    50000.00,
    50000.00,
    5000,
    0,
    0,
    1,
    10,
    NOW(),
    e.start_date,
    JSON_ARRAY('Accès au festival', 'Zone debout')
FROM events e
WHERE e.slug = 'festival-musique-malagasy-2025'
UNION ALL
SELECT 
    e.id,
    'Étudiant',
    'Tarif réduit pour étudiants (carte étudiante requise)',
    30000.00,
    30000.00,
    2000,
    0,
    0,
    1,
    5,
    NOW(),
    e.start_date,
    JSON_ARRAY('Accès au festival', 'Tarif étudiant')
FROM events e
WHERE e.slug = 'festival-musique-malagasy-2025';

-- Créer les statistiques pour l'événement de démo
INSERT INTO event_statistics (event_id)
SELECT id FROM events WHERE slug = 'festival-musique-malagasy-2025';

-- ============================================================================
-- 9. CODES PROMO DE DÉMONSTRATION
-- ============================================================================

INSERT INTO promo_codes (
    code,
    description,
    discount_type,
    discount_value,
    max_uses,
    current_uses,
    max_uses_per_user,
    valid_from,
    valid_until,
    is_active,
    min_purchase_amount,
    applicable_to,
    created_by
)
SELECT 
    'BIENVENUE2025',
    'Code de bienvenue - 10% de réduction',
    'percentage',
    10.00,
    1000,
    0,
    1,
    NOW(),
    DATE_ADD(NOW(), INTERVAL 90 DAY),
    TRUE,
    20000.00,
    'all',
    u.id
FROM users u
WHERE u.email = 'admin@aiolia-event.com'
UNION ALL
SELECT 
    'EARLY50',
    'Réduction early bird de 50%',
    'percentage',
    50.00,
    100,
    0,
    1,
    NOW(),
    DATE_ADD(NOW(), INTERVAL 30 DAY),
    TRUE,
    NULL,
    'all',
    u.id
FROM users u
WHERE u.email = 'admin@aiolia-event.com'
UNION ALL
SELECT 
    'FIDELE5000',
    'Réduction de 5000 MGA pour clients fidèles',
    'fixed_amount',
    5000.00,
    NULL,
    0,
    3,
    NOW(),
    DATE_ADD(NOW(), INTERVAL 365 DAY),
    TRUE,
    50000.00,
    'all',
    u.id
FROM users u
WHERE u.email = 'admin@aiolia-event.com';

-- ============================================================================
-- 10. RÈGLES DE TARIFICATION DYNAMIQUE
-- ============================================================================

-- Appliquer +20% quand 50% des billets sont vendus
-- Appliquer +30% quand 75% des billets sont vendus
-- Appliquer +50% quand 90% des billets sont vendus

INSERT INTO dynamic_pricing_rules (ticket_category_id, threshold_percentage, price_multiplier, is_active)
SELECT 
    tc.id,
    50,
    1.20,
    TRUE
FROM ticket_categories tc
INNER JOIN events e ON e.id = tc.event_id
WHERE e.slug = 'festival-musique-malagasy-2025'
UNION ALL
SELECT 
    tc.id,
    75,
    1.30,
    TRUE
FROM ticket_categories tc
INNER JOIN events e ON e.id = tc.event_id
WHERE e.slug = 'festival-musique-malagasy-2025'
UNION ALL
SELECT 
    tc.id,
    90,
    1.50,
    TRUE
FROM ticket_categories tc
INNER JOIN events e ON e.id = tc.event_id
WHERE e.slug = 'festival-musique-malagasy-2025';

-- ============================================================================
-- FIN DES SEEDS
-- ============================================================================

