-- ============================================================================
-- AIOLIA EVENT - SCHEMA PostgreSQL SIMPLIFIÉ
-- ============================================================================
-- Système de gestion d'événements avec billetterie et paiement
-- ============================================================================

-- Types ENUM personnalisés
CREATE TYPE user_role AS ENUM ('user', 'co_organizer', 'organizer', 'admin');
CREATE TYPE oauth_provider AS ENUM ('google', 'facebook', 'local');
CREATE TYPE event_status AS ENUM ('draft', 'published', 'ongoing', 'completed', 'cancelled');
CREATE TYPE order_status AS ENUM ('pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded');
CREATE TYPE payment_status AS ENUM ('pending', 'processing', 'paid', 'failed', 'refunded');
CREATE TYPE payment_method AS ENUM ('orange_money', 'airtel_money', 'mvola', 'bank_card', 'bank_transfer');
CREATE TYPE ticket_status AS ENUM ('valid', 'used', 'cancelled', 'refunded', 'transferred');
CREATE TYPE notification_type AS ENUM ('order_confirmation', 'payment_success', 'event_reminder', 'ticket_transferred', 'new_event', 'promotion');
CREATE TYPE notification_channel AS ENUM ('email', 'push', 'sms', 'in_app');
CREATE TYPE notification_status AS ENUM ('pending', 'sent', 'failed', 'read');

-- ============================================================================
-- 1. UTILISATEURS & AUTHENTIFICATION
-- ============================================================================

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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login_at TIMESTAMP
);

CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);

-- Table des tokens de rafraîchissement
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
-- 2. CATÉGORIES & ÉVÉNEMENTS
-- ============================================================================

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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    published_at TIMESTAMP
);

CREATE INDEX idx_events_organizer ON events(organizer_id);
CREATE INDEX idx_events_category ON events(category_id);
CREATE INDEX idx_events_dates ON events(start_date, end_date);
CREATE INDEX idx_events_status ON events(status);
CREATE INDEX idx_events_slug ON events(slug);

-- Recherche plein texte
CREATE INDEX idx_events_search ON events USING GIN(to_tsvector('french', title || ' ' || COALESCE(description, '')));

-- Médias d'événements
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

-- ============================================================================
-- 3. BILLETS & CATÉGORIES
-- ============================================================================

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

-- ============================================================================
-- 4. COMMANDES & PAIEMENTS
-- ============================================================================

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
    provider_response JSONB,
    error_message TEXT,
    completed_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_payments_order ON payments(order_id);
CREATE INDEX idx_payments_transaction ON payments(transaction_id);
CREATE INDEX idx_payments_status ON payments(status);

-- ============================================================================
-- 5. BILLETS INDIVIDUELS
-- ============================================================================

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

-- ============================================================================
-- 6. CODES PROMO
-- ============================================================================

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
-- 7. PANIER D'ACHAT
-- ============================================================================

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
-- 8. FAVORIS
-- ============================================================================

CREATE TABLE favorites (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    event_id BIGINT NOT NULL REFERENCES events(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, event_id)
);

CREATE INDEX idx_favorites_user ON favorites(user_id);
CREATE INDEX idx_favorites_event ON favorites(event_id);

-- ============================================================================
-- 9. NOTIFICATIONS
-- ============================================================================

CREATE TABLE notifications (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    type notification_type NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    channel notification_channel NOT NULL,
    status notification_status DEFAULT 'pending',
    reference_type VARCHAR(50),
    reference_id BIGINT,
    metadata JSONB,
    sent_at TIMESTAMP,
    read_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_notifications_user ON notifications(user_id);
CREATE INDEX idx_notifications_type ON notifications(type);
CREATE INDEX idx_notifications_status ON notifications(status);

-- ============================================================================
-- 10. AVIS & ÉVALUATIONS
-- ============================================================================

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
-- 11. STATISTIQUES
-- ============================================================================

CREATE TABLE event_statistics (
    id BIGSERIAL PRIMARY KEY,
    event_id BIGINT UNIQUE NOT NULL REFERENCES events(id) ON DELETE CASCADE,
    total_views INT DEFAULT 0,
    total_favorites INT DEFAULT 0,
    total_tickets_sold INT DEFAULT 0,
    total_revenue DECIMAL(12, 2) DEFAULT 0,
    average_ticket_price DECIMAL(10, 2) DEFAULT 0,
    conversion_rate DECIMAL(5, 2) DEFAULT 0,
    average_rating DECIMAL(3, 2) DEFAULT 0,
    total_reviews INT DEFAULT 0,
    last_calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_event_statistics_event ON event_statistics(event_id);

-- ============================================================================
-- 12. CONFIGURATION SYSTÈME
-- ============================================================================

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
-- VUES UTILES
-- ============================================================================

-- Vue des événements à venir
CREATE VIEW upcoming_events AS
SELECT 
    e.*,
    ec.name as category_name,
    u.first_name || ' ' || u.last_name as organizer_name,
    es.total_tickets_sold,
    es.total_revenue,
    es.average_rating,
    (SELECT COUNT(*) FROM favorites WHERE event_id = e.id) as favorites_count
FROM events e
LEFT JOIN event_categories ec ON e.category_id = ec.id
LEFT JOIN users u ON e.organizer_id = u.id
LEFT JOIN event_statistics es ON e.id = es.event_id
WHERE e.status = 'published' 
  AND e.start_date > CURRENT_TIMESTAMP
ORDER BY e.start_date ASC;

-- ============================================================================
-- TRIGGERS POUR updated_at
-- ============================================================================

-- Fonction pour mettre à jour automatiquement updated_at
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Application des triggers
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

-- ============================================================================
-- DONNÉES INITIALES
-- ============================================================================

-- Catégories d'événements par défaut
INSERT INTO event_categories (name, slug, description, icon) VALUES
('Concert', 'concert', 'Concerts et spectacles musicaux', 'music'),
('Conférence', 'conference', 'Conférences et séminaires', 'presentation'),
('Sport', 'sport', 'Événements sportifs', 'sports'),
('Festival', 'festival', 'Festivals et célébrations', 'festival'),
('Théâtre', 'theatre', 'Pièces de théâtre', 'theater'),
('Formation', 'formation', 'Formations et ateliers', 'school'),
('Networking', 'networking', 'Événements de réseautage', 'people'),
('Autre', 'autre', 'Autres types d\'événements', 'category');

-- Paramètres système par défaut
INSERT INTO system_settings (setting_key, setting_value, setting_type, description, is_public) VALUES
('site_name', 'Aiolia Event', 'string', 'Nom du site', true),
('site_email', 'contact@aiolia-event.mg', 'string', 'Email de contact', true),
('default_currency', 'MGA', 'string', 'Devise par défaut', true),
('ticket_reservation_timeout', '15', 'number', 'Durée de réservation du panier en minutes', false),
('max_tickets_per_order', '10', 'number', 'Nombre maximum de billets par commande', true),
('enable_mobile_money', 'true', 'boolean', 'Activer les paiements Mobile Money', false);

-- ============================================================================
-- FIN DU SCHÉMA
-- ============================================================================
