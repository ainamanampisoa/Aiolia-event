-- ============================================================================
-- AIOLIA EVENT - SCHÉMA POSTGRESQL FINAL (Sans JSONB)
-- ============================================================================
-- Version: 2.0 Final - Tables Relationnelles Classiques
-- Description: Système complet de gestion d'événements avec billetterie
-- Logique métier: Dans le code applicatif (pas dans la BDD)
-- Traductions: Dans translations.js (pas dans la BDD)
-- ============================================================================

-- ============================================================================
-- SECTION 1: TYPES ENUM
-- ============================================================================

CREATE TYPE user_role AS ENUM ('user', 'co_organizer', 'organizer', 'admin');
CREATE TYPE oauth_provider AS ENUM ('google', 'facebook', 'local');
CREATE TYPE event_status AS ENUM ('draft', 'published', 'ongoing', 'completed', 'cancelled');
CREATE TYPE order_status AS ENUM ('pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded');
CREATE TYPE payment_status AS ENUM ('pending', 'processing', 'paid', 'failed', 'refunded');
CREATE TYPE payment_method AS ENUM ('orange_money', 'airtel_money', 'mvola', 'bank_card', 'bank_transfer');
CREATE TYPE ticket_status AS ENUM ('valid', 'used', 'cancelled', 'refunded', 'transferred');
CREATE TYPE notification_type AS ENUM ('order_confirmation', 'payment_success', 'event_reminder', 'ticket_transferred', 'new_event', 'promotion', 'alert');
CREATE TYPE notification_channel AS ENUM ('email', 'push', 'sms', 'in_app');
CREATE TYPE collaborator_role AS ENUM ('owner', 'admin', 'editor', 'viewer');
CREATE TYPE transfer_status AS ENUM ('pending', 'accepted', 'declined', 'cancelled', 'expired');

-- ============================================================================
-- SECTION 2: UTILISATEURS
-- ============================================================================

-- Table 1: Utilisateurs
CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    phone VARCHAR(20),
    photo_url VARCHAR(500),
    role user_role DEFAULT 'user',
    email_verified BOOLEAN DEFAULT FALSE,
    oauth_provider oauth_provider DEFAULT 'local',
    oauth_provider_id VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    
    -- Préférences
    theme VARCHAR(20) DEFAULT 'light',
    language VARCHAR(5) DEFAULT 'fr',
    notifications_email BOOLEAN DEFAULT TRUE,
    notifications_push BOOLEAN DEFAULT TRUE,
    notifications_sms BOOLEAN DEFAULT FALSE,
    marketing_emails BOOLEAN DEFAULT TRUE,
    
    -- Fidélité
    loyalty_points INT DEFAULT 0,
    points_lifetime_earned INT DEFAULT 0,
    loyalty_tier VARCHAR(50) DEFAULT 'bronze',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login_at TIMESTAMP
);

CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_language ON users(language);

-- ============================================================================
-- SECTION 3: TABLE 2 - TOKENS
-- ============================================================================

-- Table 2: Tokens
CREATE TABLE refresh_tokens (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token VARCHAR(500) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    is_revoked BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_refresh_tokens_user ON refresh_tokens(user_id);
CREATE INDEX idx_refresh_tokens_token ON refresh_tokens(token);

-- ============================================================================
-- SECTION 4: ÉVÉNEMENTS
-- ============================================================================

-- Table 3: Catégories
CREATE TABLE event_categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_event_categories_slug ON event_categories(slug);
CREATE INDEX idx_event_categories_active ON event_categories(is_active);

-- Table 4: Événements
CREATE TABLE events (
    id BIGSERIAL PRIMARY KEY,
    organizer_id BIGINT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    category_id INT NOT NULL REFERENCES event_categories(id),
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    short_description VARCHAR(500),
    location VARCHAR(255),
    address TEXT,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    start_date TIMESTAMP NOT NULL,
    end_date TIMESTAMP NOT NULL,
    timezone VARCHAR(50) DEFAULT 'Indian/Antananarivo',
    status event_status DEFAULT 'draft',
    is_featured BOOLEAN DEFAULT FALSE,
    total_capacity INT,
    views_count INT DEFAULT 0,
    tax_rate DECIMAL(5, 2) DEFAULT 0,
    tax_included BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    published_at TIMESTAMP
);

CREATE INDEX idx_events_organizer ON events(organizer_id);
CREATE INDEX idx_events_category ON events(category_id);
CREATE INDEX idx_events_dates ON events(start_date, end_date);
CREATE INDEX idx_events_status ON events(status);
CREATE INDEX idx_events_slug ON events(slug);
CREATE INDEX idx_events_featured ON events(is_featured) WHERE is_featured = TRUE;
CREATE INDEX idx_events_search ON events USING GIN(to_tsvector('french', title || ' ' || COALESCE(description, '')));

-- Table 5: Médias
CREATE TABLE event_media (
    id BIGSERIAL PRIMARY KEY,
    event_id BIGINT NOT NULL REFERENCES events(id) ON DELETE CASCADE,
    media_type VARCHAR(20) NOT NULL CHECK (media_type IN ('image', 'video', 'document')),
    file_url VARCHAR(500) NOT NULL,
    file_name VARCHAR(255),
    is_primary BOOLEAN DEFAULT FALSE,
    display_order INT DEFAULT 0,
    uploaded_by BIGINT NOT NULL REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_event_media_event ON event_media(event_id);

-- Table 6: Collaborateurs
CREATE TABLE event_collaborators (
    id BIGSERIAL PRIMARY KEY,
    event_id BIGINT NOT NULL REFERENCES events(id) ON DELETE CASCADE,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    role collaborator_role DEFAULT 'editor',
    can_edit_event BOOLEAN DEFAULT TRUE,
    can_manage_tickets BOOLEAN DEFAULT TRUE,
    can_view_sales BOOLEAN DEFAULT TRUE,
    can_manage_team BOOLEAN DEFAULT FALSE,
    can_send_notifications BOOLEAN DEFAULT TRUE,
    invited_by BIGINT REFERENCES users(id),
    invited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    UNIQUE(event_id, user_id)
);

CREATE INDEX idx_collaborators_event ON event_collaborators(event_id);
CREATE INDEX idx_collaborators_user ON event_collaborators(user_id);

-- ============================================================================
-- SECTION 4: BILLETTERIE
-- ============================================================================

-- Table 7: Catégories de billets
CREATE TABLE ticket_categories (
    id BIGSERIAL PRIMARY KEY,
    event_id BIGINT NOT NULL REFERENCES events(id) ON DELETE CASCADE,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'MGA',
    quantity_total INT NOT NULL,
    quantity_sold INT DEFAULT 0,
    quantity_reserved INT DEFAULT 0,
    min_purchase INT DEFAULT 1,
    max_purchase INT DEFAULT 10,
    sale_start_date TIMESTAMP,
    sale_end_date TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT check_ticket_quantities CHECK (quantity_sold + quantity_reserved <= quantity_total)
);

CREATE INDEX idx_ticket_categories_event ON ticket_categories(event_id);
CREATE INDEX idx_ticket_categories_active ON ticket_categories(is_active);

-- Table 8: Historique prix
CREATE TABLE ticket_price_history (
    id BIGSERIAL PRIMARY KEY,
    ticket_category_id BIGINT NOT NULL REFERENCES ticket_categories(id) ON DELETE CASCADE,
    old_price DECIMAL(10, 2),
    new_price DECIMAL(10, 2) NOT NULL,
    reason VARCHAR(255),
    changed_by BIGINT NOT NULL REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_price_history_category ON ticket_price_history(ticket_category_id);

-- Table 9: Billets
CREATE TABLE tickets (
    id BIGSERIAL PRIMARY KEY,
    ticket_category_id BIGINT NOT NULL REFERENCES ticket_categories(id),
    order_id BIGINT NOT NULL REFERENCES orders(id) ON DELETE RESTRICT,
    user_id BIGINT NOT NULL REFERENCES users(id),
    ticket_number VARCHAR(50) UNIQUE NOT NULL,
    qr_code_data VARCHAR(500) UNIQUE NOT NULL,
    qr_code_image_url VARCHAR(500),
    status ticket_status DEFAULT 'valid',
    check_in_at TIMESTAMP,
    check_in_by BIGINT REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_tickets_category ON tickets(ticket_category_id);
CREATE INDEX idx_tickets_order ON tickets(order_id);
CREATE INDEX idx_tickets_user ON tickets(user_id);
CREATE INDEX idx_tickets_qr ON tickets(qr_code_data);
CREATE INDEX idx_tickets_status ON tickets(status);

-- Table 10: Transferts de billets
CREATE TABLE ticket_transfers (
    id BIGSERIAL PRIMARY KEY,
    ticket_id BIGINT NOT NULL REFERENCES tickets(id) ON DELETE CASCADE,
    from_user_id BIGINT NOT NULL REFERENCES users(id),
    to_email VARCHAR(255) NOT NULL,
    to_user_id BIGINT REFERENCES users(id),
    status transfer_status DEFAULT 'pending',
    transfer_code VARCHAR(100) UNIQUE NOT NULL,
    message TEXT,
    expires_at TIMESTAMP NOT NULL,
    accepted_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_ticket_transfers_ticket ON ticket_transfers(ticket_id);
CREATE INDEX idx_ticket_transfers_from_user ON ticket_transfers(from_user_id);
CREATE INDEX idx_ticket_transfers_code ON ticket_transfers(transfer_code);

-- ============================================================================
-- SECTION 5: COMMANDES & PAIEMENTS
-- ============================================================================

-- Table 11: Commandes
CREATE TABLE orders (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id),
    order_number VARCHAR(50) UNIQUE NOT NULL,
    status order_status DEFAULT 'pending',
    subtotal DECIMAL(10, 2) NOT NULL,
    discount_amount DECIMAL(10, 2) DEFAULT 0,
    total_amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'MGA',
    payment_status payment_status DEFAULT 'pending',
    payment_method payment_method,
    billing_email VARCHAR(255),
    billing_phone VARCHAR(20),
    completed_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_orders_user ON orders(user_id);
CREATE INDEX idx_orders_number ON orders(order_number);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_created ON orders(created_at);

-- Table 12: Items de commande
CREATE TABLE order_items (
    id BIGSERIAL PRIMARY KEY,
    order_id BIGINT NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    ticket_category_id BIGINT NOT NULL REFERENCES ticket_categories(id),
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_order_items_order ON order_items(order_id);

-- Table 13: Paiements
CREATE TABLE payments (
    id BIGSERIAL PRIMARY KEY,
    order_id BIGINT NOT NULL REFERENCES orders(id) ON DELETE RESTRICT,
    payment_method payment_method NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'MGA',
    status payment_status DEFAULT 'pending',
    transaction_id VARCHAR(255) UNIQUE,
    reference_number VARCHAR(100),
    phone_number VARCHAR(20),
    error_message TEXT,
    completed_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_payments_order ON payments(order_id);
CREATE INDEX idx_payments_transaction ON payments(transaction_id);
CREATE INDEX idx_payments_status ON payments(status);

-- Table 14: Codes promo
CREATE TABLE promo_codes (
    id BIGSERIAL PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    discount_type VARCHAR(20) NOT NULL CHECK (discount_type IN ('percentage', 'fixed_amount')),
    discount_value DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'MGA',
    max_uses INT,
    current_uses INT DEFAULT 0,
    max_uses_per_user INT DEFAULT 1,
    valid_from TIMESTAMP NOT NULL,
    valid_until TIMESTAMP NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    min_purchase_amount DECIMAL(10, 2),
    created_by BIGINT NOT NULL REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_promo_codes_code ON promo_codes(code);
CREATE INDEX idx_promo_codes_active ON promo_codes(is_active);

-- Table 15: Utilisation codes promo
CREATE TABLE promo_code_usage (
    id BIGSERIAL PRIMARY KEY,
    promo_code_id BIGINT NOT NULL REFERENCES promo_codes(id),
    user_id BIGINT NOT NULL REFERENCES users(id),
    order_id BIGINT NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    discount_applied DECIMAL(10, 2) NOT NULL,
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_promo_usage_code ON promo_code_usage(promo_code_id);
CREATE INDEX idx_promo_usage_user ON promo_code_usage(user_id);

-- ============================================================================
-- SECTION 6: PANIER
-- ============================================================================

-- Table 16: Panier
CREATE TABLE cart (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT REFERENCES users(id) ON DELETE CASCADE,
    session_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP
);

CREATE INDEX idx_cart_user ON cart(user_id);
CREATE INDEX idx_cart_session ON cart(session_id);

-- Table 17: Items du panier
CREATE TABLE cart_items (
    id BIGSERIAL PRIMARY KEY,
    cart_id BIGINT NOT NULL REFERENCES cart(id) ON DELETE CASCADE,
    ticket_category_id BIGINT NOT NULL REFERENCES ticket_categories(id) ON DELETE CASCADE,
    quantity INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(cart_id, ticket_category_id)
);

CREATE INDEX idx_cart_items_cart ON cart_items(cart_id);

-- ============================================================================
-- SECTION 7: FAVORIS & SOCIAL
-- ============================================================================

-- Table 18: Favoris
CREATE TABLE favorites (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    event_id BIGINT NOT NULL REFERENCES events(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, event_id)
);

CREATE INDEX idx_favorites_user ON favorites(user_id);
CREATE INDEX idx_favorites_event ON favorites(event_id);

-- Table 19: Parrainage
CREATE TABLE user_referrals (
    id BIGSERIAL PRIMARY KEY,
    referrer_user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    referred_email VARCHAR(255) NOT NULL,
    referred_user_id BIGINT REFERENCES users(id),
    referral_code VARCHAR(50) UNIQUE NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    reward_points INT,
    completed_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_referrals_referrer ON user_referrals(referrer_user_id);
CREATE INDEX idx_referrals_code ON user_referrals(referral_code);

-- Table 20: Connexions (amis)
CREATE TABLE user_connections (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    friend_user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    accepted_at TIMESTAMP,
    UNIQUE(user_id, friend_user_id),
    CHECK(user_id != friend_user_id)
);

CREATE INDEX idx_connections_user ON user_connections(user_id);

-- ============================================================================
-- SECTION 8: NOTIFICATIONS
-- ============================================================================

-- Table 21: Notifications
CREATE TABLE notifications (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    type notification_type NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    channel notification_channel NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    priority VARCHAR(20) DEFAULT 'normal',
    reference_type VARCHAR(50),
    reference_id BIGINT,
    sent_at TIMESTAMP,
    read_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_notifications_user ON notifications(user_id);
CREATE INDEX idx_notifications_type ON notifications(type);
CREATE INDEX idx_notifications_status ON notifications(status);

-- ============================================================================
-- SECTION 9: AVIS
-- ============================================================================

-- Table 22: Avis
CREATE TABLE reviews (
    id BIGSERIAL PRIMARY KEY,
    event_id BIGINT NOT NULL REFERENCES events(id) ON DELETE CASCADE,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    order_id BIGINT NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(255),
    comment TEXT,
    is_verified_purchase BOOLEAN DEFAULT TRUE,
    is_published BOOLEAN DEFAULT TRUE,
    organizer_response TEXT,
    organizer_response_at TIMESTAMP,
    helpful_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, event_id)
);

CREATE INDEX idx_reviews_event ON reviews(event_id);
CREATE INDEX idx_reviews_user ON reviews(user_id);
CREATE INDEX idx_reviews_rating ON reviews(rating);

-- ============================================================================
-- SECTION 10: LISTE D'ATTENTE
-- ============================================================================

-- Table 23: Liste d'attente
CREATE TABLE event_waitlist (
    id BIGSERIAL PRIMARY KEY,
    event_id BIGINT NOT NULL REFERENCES events(id) ON DELETE CASCADE,
    ticket_category_id BIGINT REFERENCES ticket_categories(id) ON DELETE CASCADE,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    email VARCHAR(255) NOT NULL,
    quantity_requested INT DEFAULT 1,
    status VARCHAR(20) DEFAULT 'waiting',
    notified_at TIMESTAMP,
    expires_at TIMESTAMP,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    position INT,
    UNIQUE(event_id, user_id, ticket_category_id)
);

CREATE INDEX idx_waitlist_event ON event_waitlist(event_id);
CREATE INDEX idx_waitlist_user ON event_waitlist(user_id);

-- ============================================================================
-- SECTION 11: SYSTÈME
-- ============================================================================

-- Table 24: Configuration
CREATE TABLE system_settings (
    id SERIAL PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type VARCHAR(20) DEFAULT 'string' CHECK (setting_type IN ('string', 'number', 'boolean', 'json')),
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE,
    updated_by BIGINT REFERENCES users(id),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_system_settings_key ON system_settings(setting_key);

-- ============================================================================
-- SECTION 12: VUES
-- ============================================================================

-- Vue des événements à venir (stats calculées à la volée)
CREATE VIEW upcoming_events AS
SELECT 
    e.*,
    ec.name as category_name,
    u.first_name || ' ' || u.last_name as organizer_name,
    (SELECT COUNT(*) FROM tickets t 
     JOIN ticket_categories tc ON t.ticket_category_id = tc.id 
     WHERE tc.event_id = e.id AND t.status != 'cancelled') as total_tickets_sold,
    (SELECT SUM(oi.total_price) FROM order_items oi 
     JOIN ticket_categories tc ON oi.ticket_category_id = tc.id 
     JOIN orders o ON oi.order_id = o.id
     WHERE tc.event_id = e.id AND o.status = 'completed') as total_revenue,
    (SELECT AVG(rating) FROM reviews WHERE event_id = e.id AND is_published = true) as average_rating,
    (SELECT COUNT(*) FROM favorites WHERE event_id = e.id) as favorites_count
FROM events e
LEFT JOIN event_categories ec ON e.category_id = ec.id
LEFT JOIN users u ON e.organizer_id = u.id
WHERE e.status = 'published' 
  AND e.start_date > CURRENT_TIMESTAMP
ORDER BY e.start_date ASC;

-- Vue des amis au même événement
CREATE VIEW event_attendees_friends AS
SELECT 
    t1.user_id,
    t1.ticket_category_id,
    tc.event_id,
    uc.friend_user_id,
    u.first_name || ' ' || u.last_name as friend_name
FROM tickets t1
JOIN ticket_categories tc ON t1.ticket_category_id = tc.id
JOIN user_connections uc ON t1.user_id = uc.user_id AND uc.status = 'accepted'
JOIN tickets t2 ON uc.friend_user_id = t2.user_id AND tc.id = t2.ticket_category_id
JOIN users u ON uc.friend_user_id = u.id
WHERE t1.status = 'valid' AND t2.status = 'valid';

-- ============================================================================
-- SECTION 13: FONCTIONS
-- ============================================================================

-- Fonction pour mettre à jour updated_at
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Fonction de détection de conflits
CREATE OR REPLACE FUNCTION check_event_conflicts(
    p_organizer_id BIGINT,
    p_start_date TIMESTAMP,
    p_end_date TIMESTAMP,
    p_event_id BIGINT DEFAULT NULL
)
RETURNS TABLE(
    conflicting_event_id BIGINT,
    conflicting_title VARCHAR
) AS $$
BEGIN
    RETURN QUERY
    SELECT e.id, e.title
    FROM events e
    WHERE e.id != COALESCE(p_event_id, -1)
    AND e.organizer_id = p_organizer_id
    AND (e.start_date, e.end_date) OVERLAPS (p_start_date, p_end_date)
    AND e.status NOT IN ('cancelled', 'draft');
END;
$$ LANGUAGE plpgsql;

-- ============================================================================
-- SECTION 14: TRIGGERS
-- ============================================================================

CREATE TRIGGER update_users_updated_at BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_events_updated_at BEFORE UPDATE ON events
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_ticket_categories_updated_at BEFORE UPDATE ON ticket_categories
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_orders_updated_at BEFORE UPDATE ON orders
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_payments_updated_at BEFORE UPDATE ON payments
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_tickets_updated_at BEFORE UPDATE ON tickets
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_promo_codes_updated_at BEFORE UPDATE ON promo_codes
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_reviews_updated_at BEFORE UPDATE ON reviews
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_cart_updated_at BEFORE UPDATE ON cart
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- ============================================================================
-- SECTION 15: DONNÉES INITIALES
-- ============================================================================

-- Catégories d'événements (les traductions sont dans translations.js)
INSERT INTO event_categories (name, slug, description, icon) VALUES
('Concert', 'concert', 'Concerts et spectacles musicaux', 'music'),
('Conférence', 'conference', 'Conférences et séminaires', 'presentation'),
('Sport', 'sport', 'Événements sportifs', 'sports'),
('Festival', 'festival', 'Festivals et célébrations', 'festival'),
('Théâtre', 'theatre', 'Pièces de théâtre', 'theater'),
('Formation', 'formation', 'Formations et ateliers', 'school'),
('Networking', 'networking', 'Événements de réseautage', 'people'),
('Autre', 'autre', 'Autres types d''événements', 'category');

-- Paramètres système
INSERT INTO system_settings (setting_key, setting_value, setting_type, description, is_public) VALUES
('site_name', 'Aiolia Event', 'string', 'Nom du site', true),
('site_email', 'contact@aiolia-event.mg', 'string', 'Email de contact', true),
('default_currency', 'MGA', 'string', 'Devise par défaut', true),
('default_language', 'fr', 'string', 'Langue par défaut', true),
('supported_languages', '["fr", "en", "mg"]', 'json', 'Langues supportées', true),
('ticket_reservation_timeout', '15', 'number', 'Durée réservation panier (minutes)', false),
('max_tickets_per_order', '10', 'number', 'Nombre max billets par commande', true),
('enable_mobile_money', 'true', 'boolean', 'Activer paiements Mobile Money', false),
('platform_fee_percentage', '5', 'number', 'Commission plateforme (%)', false),
('loyalty_points_per_1000_mga', '10', 'number', 'Points par 1000 MGA dépensés', false),
('referral_bonus_points', '500', 'number', 'Points bonus parrainage', false);

-- ============================================================================
-- FIN DU SCHÉMA - 24 TABLES
-- ============================================================================

/*
RÉSUMÉ DES 24 TABLES:
1.  users                      - Utilisateurs (avec wallet intégré)
2.  refresh_tokens             - Tokens JWT
3.  event_categories           - Catégories événements
4.  events                     - Événements
5.  event_media                - Médias (images, vidéos)
6.  event_collaborators        - Co-organisateurs
7.  ticket_categories          - Catégories billets
8.  ticket_price_history       - Historique modifications prix
9.  tickets                    - Billets individuels
10. ticket_transfers           - Transferts billets
11. orders                     - Commandes
12. order_items                - Items commandes
13. payments                   - Paiements
14. promo_codes                - Codes promotionnels
15. promo_code_usage           - Utilisation codes promo
16. cart                       - Panier d'achat
17. cart_items                 - Items du panier
18. favorites                  - Favoris
19. user_referrals             - Système de parrainage
20. user_connections           - Connexions sociales (amis)
21. notifications              - Notifications + Alertes
22. reviews                    - Avis et évaluations
23. event_waitlist             - Liste d'attente
24. system_settings            - Configuration système

OPTIMISATIONS:
- ❌ AUCUN JSONB : Toute la logique dans le code applicatif
- ❌ PAS de tables statistiques : Calculées à la volée dans le backend
- ✅ Traductions : Fichier translations.js (FR/EN/MG)
- ✅ Tables relationnelles classiques uniquement
- ✅ Total: 24 tables
*/
