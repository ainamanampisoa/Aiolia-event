-- ============================================================================
-- AIOLIA EVENT - SCHÉMA POSTGRESQL OPTIMISÉ
-- ============================================================================
-- Version: 3.0 Optimisé - Toutes fonctionnalités couvertes
-- Description: Système complet de gestion d'événements avec billetterie
-- Tables: 32 (24 originales + 8 nouvelles)
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
CREATE TYPE activity_type AS ENUM ('view', 'click', 'share', 'favorite', 'search', 'purchase');
CREATE TYPE audit_action AS ENUM ('create', 'update', 'delete', 'login', 'logout', 'payment', 'refund', 'transfer');
CREATE TYPE reward_type AS ENUM ('discount', 'free_ticket', 'loyalty_points', 'voucher');

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
    
    -- Statistiques personnelles (dénormalisé pour performance)
    total_events_attended INT DEFAULT 0,
    total_amount_spent DECIMAL(12, 2) DEFAULT 0,
    favorite_category_id INT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login_at TIMESTAMP
);

CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_language ON users(language);
CREATE INDEX idx_users_loyalty_tier ON users(loyalty_tier);

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
CREATE INDEX idx_refresh_tokens_expires ON refresh_tokens(expires_at) WHERE is_revoked = FALSE;

-- ============================================================================
-- SECTION 3: ÉVÉNEMENTS
-- ============================================================================

-- Table 3: Catégories
CREATE TABLE event_categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_event_categories_slug ON event_categories(slug);
CREATE INDEX idx_event_categories_active ON event_categories(is_active);

-- Table 4: Tags (NOUVEAU - pour améliorer recherche et suggestions)
CREATE TABLE event_tags (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    slug VARCHAR(50) UNIQUE NOT NULL,
    usage_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_event_tags_slug ON event_tags(slug);
CREATE INDEX idx_event_tags_usage ON event_tags(usage_count DESC);

-- Table 5: Événements
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
    
    -- Tarification
    tax_rate DECIMAL(5, 2) DEFAULT 0,
    tax_included BOOLEAN DEFAULT TRUE,
    enable_dynamic_pricing BOOLEAN DEFAULT FALSE,
    
    -- Statistiques (dénormalisé pour performance)
    tickets_sold INT DEFAULT 0,
    revenue_total DECIMAL(12, 2) DEFAULT 0,
    average_rating DECIMAL(3, 2) DEFAULT 0,
    reviews_count INT DEFAULT 0,
    
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
CREATE INDEX idx_events_location ON events USING GIN(to_tsvector('french', COALESCE(location, '') || ' ' || COALESCE(address, '')));

-- Table 6: Association événements-tags (NOUVEAU)
CREATE TABLE event_tags_association (
    id BIGSERIAL PRIMARY KEY,
    event_id BIGINT NOT NULL REFERENCES events(id) ON DELETE CASCADE,
    tag_id INT NOT NULL REFERENCES event_tags(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(event_id, tag_id)
);

CREATE INDEX idx_event_tags_assoc_event ON event_tags_association(event_id);
CREATE INDEX idx_event_tags_assoc_tag ON event_tags_association(tag_id);

-- Table 7: Médias
CREATE TABLE event_media (
    id BIGSERIAL PRIMARY KEY,
    event_id BIGINT NOT NULL REFERENCES events(id) ON DELETE CASCADE,
    media_type VARCHAR(20) NOT NULL CHECK (media_type IN ('image', 'video', 'document')),
    file_url VARCHAR(500) NOT NULL,
    file_name VARCHAR(255),
    file_size BIGINT,
    mime_type VARCHAR(100),
    is_primary BOOLEAN DEFAULT FALSE,
    display_order INT DEFAULT 0,
    uploaded_by BIGINT NOT NULL REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_event_media_event ON event_media(event_id);
CREATE INDEX idx_event_media_type ON event_media(media_type);

-- Table 8: Collaborateurs
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

-- Table 9: Catégories de billets
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
    
    -- Tarification dynamique
    enable_dynamic_pricing BOOLEAN DEFAULT FALSE,
    pricing_tier_1_threshold INT,
    pricing_tier_1_price DECIMAL(10, 2),
    pricing_tier_2_threshold INT,
    pricing_tier_2_price DECIMAL(10, 2),
    pricing_tier_3_threshold INT,
    pricing_tier_3_price DECIMAL(10, 2),
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT check_ticket_quantities CHECK (quantity_sold + quantity_reserved <= quantity_total)
);

CREATE INDEX idx_ticket_categories_event ON ticket_categories(event_id);
CREATE INDEX idx_ticket_categories_active ON ticket_categories(is_active);
CREATE INDEX idx_ticket_categories_dates ON ticket_categories(sale_start_date, sale_end_date);

-- Table 10: Historique prix
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
CREATE INDEX idx_price_history_date ON ticket_price_history(created_at);

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
    tax_amount DECIMAL(10, 2) DEFAULT 0,
    total_amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'MGA',
    payment_status payment_status DEFAULT 'pending',
    payment_method payment_method,
    billing_email VARCHAR(255),
    billing_phone VARCHAR(20),
    billing_name VARCHAR(255),
    completed_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_orders_user ON orders(user_id);
CREATE INDEX idx_orders_number ON orders(order_number);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_payment_status ON orders(payment_status);
CREATE INDEX idx_orders_created ON orders(created_at DESC);

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
CREATE INDEX idx_order_items_category ON order_items(ticket_category_id);

-- Table 13: Billets (déplacé après orders)
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
CREATE INDEX idx_tickets_number ON tickets(ticket_number);

-- Table 14: Transferts de billets
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
CREATE INDEX idx_ticket_transfers_status ON ticket_transfers(status);

-- Table 15: Paiements
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
    provider_response TEXT,
    completed_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_payments_order ON payments(order_id);
CREATE INDEX idx_payments_transaction ON payments(transaction_id);
CREATE INDEX idx_payments_status ON payments(status);
CREATE INDEX idx_payments_method ON payments(payment_method);
CREATE INDEX idx_payments_created ON payments(created_at DESC);

-- Table 16: Codes promo
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
    applicable_to VARCHAR(20) DEFAULT 'all' CHECK (applicable_to IN ('all', 'specific_events', 'specific_categories')),
    created_by BIGINT NOT NULL REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_promo_codes_code ON promo_codes(code);
CREATE INDEX idx_promo_codes_active ON promo_codes(is_active);
CREATE INDEX idx_promo_codes_dates ON promo_codes(valid_from, valid_until);

-- Table 17: Association codes promo - événements (NOUVEAU)
CREATE TABLE event_promo_codes (
    id BIGSERIAL PRIMARY KEY,
    promo_code_id BIGINT NOT NULL REFERENCES promo_codes(id) ON DELETE CASCADE,
    event_id BIGINT NOT NULL REFERENCES events(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(promo_code_id, event_id)
);

CREATE INDEX idx_event_promo_codes_promo ON event_promo_codes(promo_code_id);
CREATE INDEX idx_event_promo_codes_event ON event_promo_codes(event_id);

-- Table 18: Utilisation codes promo
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
CREATE INDEX idx_promo_usage_order ON promo_code_usage(order_id);

-- ============================================================================
-- SECTION 6: PANIER
-- ============================================================================

-- Table 19: Panier
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
CREATE INDEX idx_cart_expires ON cart(expires_at);

-- Table 20: Items du panier
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

-- Table 21: Favoris
CREATE TABLE favorites (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    event_id BIGINT NOT NULL REFERENCES events(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, event_id)
);

CREATE INDEX idx_favorites_user ON favorites(user_id);
CREATE INDEX idx_favorites_event ON favorites(event_id);

-- Table 22: Parrainage
CREATE TABLE user_referrals (
    id BIGSERIAL PRIMARY KEY,
    referrer_user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    referred_email VARCHAR(255) NOT NULL,
    referred_user_id BIGINT REFERENCES users(id),
    referral_code VARCHAR(50) UNIQUE NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    reward_points INT,
    reward_discount_code VARCHAR(50),
    completed_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_referrals_referrer ON user_referrals(referrer_user_id);
CREATE INDEX idx_referrals_code ON user_referrals(referral_code);
CREATE INDEX idx_referrals_status ON user_referrals(status);

-- Table 23: Connexions (amis)
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
CREATE INDEX idx_connections_friend ON user_connections(friend_user_id);
CREATE INDEX idx_connections_status ON user_connections(status);

-- ============================================================================
-- SECTION 8: ACTIVITÉ UTILISATEUR (NOUVEAU - pour suggestions)
-- ============================================================================

-- Table 24: Historique de recherche (NOUVEAU)
CREATE TABLE user_search_history (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT REFERENCES users(id) ON DELETE CASCADE,
    session_id VARCHAR(100),
    search_query VARCHAR(255) NOT NULL,
    search_filters JSONB,
    results_count INT,
    clicked_event_id BIGINT REFERENCES events(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_search_history_user ON user_search_history(user_id);
CREATE INDEX idx_search_history_query ON user_search_history(search_query);
CREATE INDEX idx_search_history_created ON user_search_history(created_at DESC);

-- Table 25: Log d'activité utilisateur (NOUVEAU - pour algorithme de suggestions)
CREATE TABLE user_activity_log (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT REFERENCES users(id) ON DELETE CASCADE,
    session_id VARCHAR(100),
    activity_type activity_type NOT NULL,
    event_id BIGINT REFERENCES events(id) ON DELETE CASCADE,
    category_id INT REFERENCES event_categories(id) ON DELETE SET NULL,
    metadata JSONB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_activity_log_user ON user_activity_log(user_id);
CREATE INDEX idx_activity_log_type ON user_activity_log(activity_type);
CREATE INDEX idx_activity_log_event ON user_activity_log(event_id);
CREATE INDEX idx_activity_log_created ON user_activity_log(created_at DESC);
CREATE INDEX idx_activity_log_user_created ON user_activity_log(user_id, created_at DESC);

-- ============================================================================
-- SECTION 9: MINI-JEU "TICKET CHANCE" (NOUVEAU)
-- ============================================================================

-- Table 26: Participations au mini-jeu (NOUVEAU)
CREATE TABLE mini_game_participations (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    game_type VARCHAR(50) DEFAULT 'ticket_chance',
    is_winner BOOLEAN DEFAULT FALSE,
    reward_id BIGINT REFERENCES mini_game_rewards(id),
    played_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP
);

CREATE INDEX idx_mini_game_user ON mini_game_participations(user_id);
CREATE INDEX idx_mini_game_winner ON mini_game_participations(is_winner);
CREATE INDEX idx_mini_game_played ON mini_game_participations(played_at DESC);

-- Table 27: Récompenses du mini-jeu (NOUVEAU)
CREATE TABLE mini_game_rewards (
    id BIGSERIAL PRIMARY KEY,
    reward_type reward_type NOT NULL,
    reward_value DECIMAL(10, 2),
    reward_description VARCHAR(255) NOT NULL,
    promo_code_id BIGINT REFERENCES promo_codes(id),
    ticket_category_id BIGINT REFERENCES ticket_categories(id),
    loyalty_points INT,
    probability DECIMAL(5, 2) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    max_claims INT,
    current_claims INT DEFAULT 0,
    valid_from TIMESTAMP,
    valid_until TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_mini_game_rewards_type ON mini_game_rewards(reward_type);
CREATE INDEX idx_mini_game_rewards_active ON mini_game_rewards(is_active);

-- Table 28: Réclamations de récompenses (NOUVEAU)
CREATE TABLE mini_game_reward_claims (
    id BIGSERIAL PRIMARY KEY,
    participation_id BIGINT NOT NULL REFERENCES mini_game_participations(id) ON DELETE CASCADE,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    reward_id BIGINT NOT NULL REFERENCES mini_game_rewards(id),
    order_id BIGINT REFERENCES orders(id),
    is_claimed BOOLEAN DEFAULT FALSE,
    is_used BOOLEAN DEFAULT FALSE,
    claimed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    used_at TIMESTAMP,
    expires_at TIMESTAMP NOT NULL
);

CREATE INDEX idx_reward_claims_user ON mini_game_reward_claims(user_id);
CREATE INDEX idx_reward_claims_participation ON mini_game_reward_claims(participation_id);
CREATE INDEX idx_reward_claims_status ON mini_game_reward_claims(is_claimed, is_used);

-- ============================================================================
-- SECTION 10: NOTIFICATIONS
-- ============================================================================

-- Table 29: Templates de notifications (NOUVEAU)
CREATE TABLE notification_templates (
    id SERIAL PRIMARY KEY,
    template_key VARCHAR(100) UNIQUE NOT NULL,
    notification_type notification_type NOT NULL,
    channel notification_channel NOT NULL,
    subject_template VARCHAR(255),
    body_template TEXT NOT NULL,
    variables JSONB,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_notification_templates_key ON notification_templates(template_key);
CREATE INDEX idx_notification_templates_type ON notification_templates(notification_type);

-- Table 30: Notifications
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
    template_id INT REFERENCES notification_templates(id),
    sent_at TIMESTAMP,
    read_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_notifications_user ON notifications(user_id);
CREATE INDEX idx_notifications_type ON notifications(type);
CREATE INDEX idx_notifications_status ON notifications(status);
CREATE INDEX idx_notifications_user_read ON notifications(user_id, read_at);
CREATE INDEX idx_notifications_created ON notifications(created_at DESC);

-- ============================================================================
-- SECTION 11: AVIS
-- ============================================================================

-- Table 31: Avis
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
CREATE INDEX idx_reviews_published ON reviews(is_published);

-- ============================================================================
-- SECTION 12: LISTE D'ATTENTE
-- ============================================================================

-- Table 32: Liste d'attente
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
CREATE INDEX idx_waitlist_status ON event_waitlist(status);
CREATE INDEX idx_waitlist_position ON event_waitlist(position);

-- ============================================================================
-- SECTION 13: SYSTÈME & AUDIT
-- ============================================================================

-- Table 33: Configuration
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
CREATE INDEX idx_system_settings_public ON system_settings(is_public);

-- Table 34: Audit log (NOUVEAU - traçabilité)
CREATE TABLE audit_log (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT REFERENCES users(id) ON DELETE SET NULL,
    action audit_action NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id BIGINT NOT NULL,
    old_values JSONB,
    new_values JSONB,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_audit_log_user ON audit_log(user_id);
CREATE INDEX idx_audit_log_action ON audit_log(action);
CREATE INDEX idx_audit_log_entity ON audit_log(entity_type, entity_id);
CREATE INDEX idx_audit_log_created ON audit_log(created_at DESC);

-- ============================================================================
-- SECTION 14: VUES
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
    u.first_name || ' ' || u.last_name as friend_name,
    u.photo_url as friend_photo
FROM tickets t1
JOIN ticket_categories tc ON t1.ticket_category_id = tc.id
JOIN user_connections uc ON t1.user_id = uc.user_id AND uc.status = 'accepted'
JOIN tickets t2 ON uc.friend_user_id = t2.user_id AND tc.id = t2.ticket_category_id
JOIN users u ON uc.friend_user_id = u.id
WHERE t1.status = 'valid' AND t2.status = 'valid';

-- Vue pour les suggestions d'événements (NOUVEAU)
CREATE VIEW user_event_suggestions AS
SELECT DISTINCT
    u.id as user_id,
    e.id as event_id,
    e.title,
    e.slug,
    e.start_date,
    -- Score de pertinence basé sur plusieurs facteurs
    (
        -- Catégorie préférée (30 points)
        CASE WHEN e.category_id = u.favorite_category_id THEN 30 ELSE 0 END +
        -- Événements vus récemment dans cette catégorie (20 points)
        (SELECT COUNT(*) * 5 FROM user_activity_log ual 
         WHERE ual.user_id = u.id 
         AND ual.category_id = e.category_id 
         AND ual.created_at > CURRENT_TIMESTAMP - INTERVAL '30 days'
         LIMIT 4) +
        -- Événements favoris dans cette catégorie (25 points)
        (SELECT COUNT(*) * 5 FROM favorites f 
         JOIN events e2 ON f.event_id = e2.id
         WHERE f.user_id = u.id 
         AND e2.category_id = e.category_id
         LIMIT 5) +
        -- Popularité générale (25 points max)
        LEAST(e.views_count / 100, 25)
    ) as relevance_score
FROM users u
CROSS JOIN events e
WHERE e.status = 'published'
  AND e.start_date > CURRENT_TIMESTAMP
  AND e.id NOT IN (
      -- Exclure les événements déjà achetés
      SELECT DISTINCT e3.id 
      FROM events e3
      JOIN ticket_categories tc ON e3.id = tc.event_id
      JOIN tickets t ON tc.id = t.ticket_category_id
      WHERE t.user_id = u.id
  )
ORDER BY relevance_score DESC;

-- Vue des statistiques utilisateur (NOUVEAU)
CREATE VIEW user_statistics AS
SELECT 
    u.id as user_id,
    u.email,
    u.total_events_attended,
    u.total_amount_spent,
    u.loyalty_points,
    u.loyalty_tier,
    (SELECT COUNT(*) FROM favorites WHERE user_id = u.id) as favorites_count,
    (SELECT COUNT(*) FROM user_connections WHERE user_id = u.id AND status = 'accepted') as friends_count,
    (SELECT COUNT(DISTINCT tc.event_id) FROM tickets t 
     JOIN ticket_categories tc ON t.ticket_category_id = tc.id
     WHERE t.user_id = u.id AND t.status != 'cancelled') as unique_events_attended,
    (SELECT ec.name FROM event_categories ec WHERE ec.id = u.favorite_category_id) as favorite_category_name,
    (SELECT COUNT(*) FROM orders WHERE user_id = u.id AND status = 'completed') as completed_orders_count,
    (SELECT SUM(total_amount) FROM orders WHERE user_id = u.id AND status = 'completed') as lifetime_spending
FROM users u;

-- ============================================================================
-- SECTION 15: FONCTIONS
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

-- Fonction pour calculer le prix dynamique (NOUVEAU)
CREATE OR REPLACE FUNCTION get_dynamic_price(p_ticket_category_id BIGINT)
RETURNS DECIMAL(10, 2) AS $$
DECLARE
    v_base_price DECIMAL(10, 2);
    v_quantity_sold INT;
    v_quantity_total INT;
    v_enable_dynamic BOOLEAN;
    v_tier1_threshold INT;
    v_tier1_price DECIMAL(10, 2);
    v_tier2_threshold INT;
    v_tier2_price DECIMAL(10, 2);
    v_tier3_threshold INT;
    v_tier3_price DECIMAL(10, 2);
BEGIN
    SELECT price, quantity_sold, quantity_total, enable_dynamic_pricing,
           pricing_tier_1_threshold, pricing_tier_1_price,
           pricing_tier_2_threshold, pricing_tier_2_price,
           pricing_tier_3_threshold, pricing_tier_3_price
    INTO v_base_price, v_quantity_sold, v_quantity_total, v_enable_dynamic,
         v_tier1_threshold, v_tier1_price,
         v_tier2_threshold, v_tier2_price,
         v_tier3_threshold, v_tier3_price
    FROM ticket_categories
    WHERE id = p_ticket_category_id;
    
    IF NOT v_enable_dynamic OR v_quantity_total = 0 THEN
        RETURN v_base_price;
    END IF;
    
    -- Calculer le prix selon les paliers
    IF v_tier3_threshold IS NOT NULL AND v_quantity_sold >= v_tier3_threshold THEN
        RETURN v_tier3_price;
    ELSIF v_tier2_threshold IS NOT NULL AND v_quantity_sold >= v_tier2_threshold THEN
        RETURN v_tier2_price;
    ELSIF v_tier1_threshold IS NOT NULL AND v_quantity_sold >= v_tier1_threshold THEN
        RETURN v_tier1_price;
    ELSE
        RETURN v_base_price;
    END IF;
END;
$$ LANGUAGE plpgsql;

-- Fonction pour mettre à jour les statistiques d'événement (NOUVEAU)
CREATE OR REPLACE FUNCTION update_event_statistics()
RETURNS TRIGGER AS $$
DECLARE
    v_event_id BIGINT;
BEGIN
    -- Déterminer l'ID de l'événement selon le contexte
    IF TG_TABLE_NAME = 'tickets' THEN
        SELECT tc.event_id INTO v_event_id
        FROM ticket_categories tc
        WHERE tc.id = COALESCE(NEW.ticket_category_id, OLD.ticket_category_id);
    ELSIF TG_TABLE_NAME = 'reviews' THEN
        v_event_id := COALESCE(NEW.event_id, OLD.event_id);
    END IF;
    
    -- Mettre à jour les statistiques
    UPDATE events SET
        tickets_sold = (
            SELECT COUNT(*) FROM tickets t
            JOIN ticket_categories tc ON t.ticket_category_id = tc.id
            WHERE tc.event_id = v_event_id AND t.status NOT IN ('cancelled', 'refunded')
        ),
        revenue_total = (
            SELECT COALESCE(SUM(oi.total_price), 0) FROM order_items oi
            JOIN ticket_categories tc ON oi.ticket_category_id = tc.id
            JOIN orders o ON oi.order_id = o.id
            WHERE tc.event_id = v_event_id AND o.status = 'completed'
        ),
        average_rating = (
            SELECT COALESCE(AVG(rating), 0) FROM reviews
            WHERE event_id = v_event_id AND is_published = true
        ),
        reviews_count = (
            SELECT COUNT(*) FROM reviews
            WHERE event_id = v_event_id AND is_published = true
        )
    WHERE id = v_event_id;
    
    RETURN COALESCE(NEW, OLD);
END;
$$ LANGUAGE plpgsql;

-- Fonction pour mettre à jour les statistiques utilisateur (NOUVEAU)
CREATE OR REPLACE FUNCTION update_user_statistics()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.status = 'completed' AND OLD.status != 'completed' THEN
        UPDATE users SET
            total_amount_spent = total_amount_spent + NEW.total_amount
        WHERE id = NEW.user_id;
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- ============================================================================
-- SECTION 16: TRIGGERS
-- ============================================================================

-- Triggers pour updated_at
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

CREATE TRIGGER update_notification_templates_updated_at BEFORE UPDATE ON notification_templates
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Triggers pour statistiques automatiques
CREATE TRIGGER trigger_update_event_stats_on_ticket 
    AFTER INSERT OR UPDATE OR DELETE ON tickets
    FOR EACH ROW EXECUTE FUNCTION update_event_statistics();

CREATE TRIGGER trigger_update_event_stats_on_review 
    AFTER INSERT OR UPDATE OR DELETE ON reviews
    FOR EACH ROW EXECUTE FUNCTION update_event_statistics();

CREATE TRIGGER trigger_update_user_stats_on_order 
    AFTER UPDATE ON orders
    FOR EACH ROW EXECUTE FUNCTION update_user_statistics();

-- ============================================================================
-- SECTION 17: DONNÉES INITIALES
-- ============================================================================

-- Catégories d'événements (les traductions sont dans translations.js)
INSERT INTO event_categories (name, slug, description, icon, display_order) VALUES
('Concert', 'concert', 'Concerts et spectacles musicaux', 'music', 1),
('Conférence', 'conference', 'Conférences et séminaires', 'presentation', 2),
('Sport', 'sport', 'Événements sportifs', 'sports', 3),
('Festival', 'festival', 'Festivals et célébrations', 'festival', 4),
('Théâtre', 'theatre', 'Pièces de théâtre', 'theater', 5),
('Formation', 'formation', 'Formations et ateliers', 'school', 6),
('Networking', 'networking', 'Événements de réseautage', 'people', 7),
('Autre', 'autre', 'Autres types d''événements', 'category', 8);

-- Tags populaires
INSERT INTO event_tags (name, slug) VALUES
('Musique', 'musique'),
('Tech', 'tech'),
('Business', 'business'),
('Art', 'art'),
('Culture', 'culture'),
('Famille', 'famille'),
('Jeunesse', 'jeunesse'),
('Gastronomie', 'gastronomie'),
('Charité', 'charite'),
('Innovation', 'innovation');

-- Templates de notifications
INSERT INTO notification_templates (template_key, notification_type, channel, subject_template, body_template, variables) VALUES
('order_confirmation_email', 'order_confirmation', 'email', 
 'Confirmation de commande #{{order_number}}',
 'Bonjour {{user_name}},\n\nVotre commande #{{order_number}} a été confirmée.\nMontant total: {{total_amount}} {{currency}}\n\nMerci pour votre achat!',
 '["order_number", "user_name", "total_amount", "currency"]'::jsonb),

('payment_success_email', 'payment_success', 'email',
 'Paiement confirmé - Commande #{{order_number}}',
 'Bonjour {{user_name}},\n\nVotre paiement de {{amount}} {{currency}} a été confirmé.\nVos billets sont disponibles dans votre compte.',
 '["order_number", "user_name", "amount", "currency"]'::jsonb),

('event_reminder_email', 'event_reminder', 'email',
 'Rappel: {{event_title}} commence dans 24h',
 'Bonjour {{user_name}},\n\nN''oubliez pas! L''événement "{{event_title}}" commence demain à {{start_time}}.\n\nLieu: {{location}}\n\nVos billets sont prêts!',
 '["user_name", "event_title", "start_time", "location"]'::jsonb),

('new_event_push', 'new_event', 'push',
 'Nouvel événement: {{event_title}}',
 'Un nouvel événement dans votre catégorie préférée "{{category_name}}" vient d''être publié!',
 '["event_title", "category_name"]'::jsonb),

('promotion_push', 'promotion', 'push',
 'Promotion flash: {{discount}}% de réduction!',
 'Code promo: {{promo_code}}\nValable jusqu''au {{valid_until}}',
 '["discount", "promo_code", "valid_until"]'::jsonb);

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
('referral_bonus_points', '500', 'number', 'Points bonus parrainage', false),
('enable_mini_game', 'true', 'boolean', 'Activer mini-jeu Ticket Chance', true),
('mini_game_daily_limit', '3', 'number', 'Participations quotidiennes max au mini-jeu', false),
('low_stock_threshold', '10', 'number', 'Seuil alerte stock bas', false),
('event_reminder_hours', '24', 'number', 'Heures avant événement pour rappel', false),
('search_history_retention_days', '90', 'number', 'Jours de rétention historique recherche', false),
('activity_log_retention_days', '180', 'number', 'Jours de rétention logs activité', false);

-- Récompenses du mini-jeu
INSERT INTO mini_game_rewards (reward_type, reward_value, reward_description, loyalty_points, probability, is_active, max_claims) VALUES
('loyalty_points', 100, 'Gagnez 100 points de fidélité', 100, 40.00, true, 1000),
('loyalty_points', 500, 'Gagnez 500 points de fidélité', 500, 20.00, true, 500),
('discount', 10, 'Réduction de 10% sur votre prochain achat', NULL, 25.00, true, 300),
('discount', 25, 'Réduction de 25% sur votre prochain achat', NULL, 10.00, true, 100),
('discount', 50, 'Réduction de 50% sur votre prochain achat', NULL, 4.00, true, 50),
('voucher', 10000, 'Bon d''achat de 10 000 MGA', NULL, 0.90, true, 10),
('free_ticket', NULL, 'Billet gratuit pour un événement sélectionné', NULL, 0.10, true, 5);

-- ============================================================================
-- FIN DU SCHÉMA - 34 TABLES
-- ============================================================================

/*
RÉSUMÉ DES 34 TABLES (24 originales + 10 nouvelles):

TABLES ORIGINALES:
1.  users                      - Utilisateurs (amélioré avec stats)
2.  refresh_tokens             - Tokens JWT
3.  event_categories           - Catégories événements
5.  events                     - Événements (amélioré avec stats dénormalisées)
7.  event_media                - Médias (amélioré)
8.  event_collaborators        - Co-organisateurs
9.  ticket_categories          - Catégories billets (amélioré avec tarification dynamique)
10. ticket_price_history       - Historique modifications prix
13. tickets                    - Billets individuels
14. ticket_transfers           - Transferts billets
11. orders                     - Commandes (amélioré)
12. order_items                - Items commandes
15. payments                   - Paiements (amélioré)
16. promo_codes                - Codes promotionnels (amélioré)
18. promo_code_usage           - Utilisation codes promo
19. cart                       - Panier d'achat
20. cart_items                 - Items du panier
21. favorites                  - Favoris
22. user_referrals             - Système de parrainage (amélioré)
23. user_connections           - Connexions sociales (amélioré)
30. notifications              - Notifications
31. reviews                    - Avis et évaluations
32. event_waitlist             - Liste d'attente (amélioré)
33. system_settings            - Configuration système (amélioré)

NOUVELLES TABLES:
4.  event_tags                 - Tags pour recherche avancée
6.  event_tags_association     - Association événements-tags
17. event_promo_codes          - Codes promo liés à des événements spécifiques
24. user_search_history        - Historique de recherche utilisateur
25. user_activity_log          - Logs d'activité (vues, clics, favoris)
26. mini_game_participations   - Participations au mini-jeu "Ticket Chance"
27. mini_game_rewards          - Récompenses disponibles
28. mini_game_reward_claims    - Réclamations de récompenses
29. notification_templates     - Templates de notifications réutilisables
34. audit_log                  - Traçabilité complète des actions

NOUVELLES VUES:
- upcoming_events              - Événements à venir avec stats
- event_attendees_friends      - Amis au même événement
- user_event_suggestions       - Suggestions personnalisées (NOUVEAU)
- user_statistics              - Statistiques utilisateur complètes (NOUVEAU)

NOUVELLES FONCTIONS:
- update_updated_at_column()          - MAJ automatique updated_at
- check_event_conflicts()             - Détection conflits de dates
- get_dynamic_price()                 - Calcul prix dynamique (NOUVEAU)
- update_event_statistics()           - MAJ stats événements (NOUVEAU)
- update_user_statistics()            - MAJ stats utilisateurs (NOUVEAU)

OPTIMISATIONS:
✅ Toutes les fonctionnalités demandées couvertes
✅ Historique de recherche et suggestions "Pour vous"
✅ Mini-jeu "Ticket Chance" complet
✅ Tarification dynamique automatique
✅ Statistiques dénormalisées pour performance
✅ Audit log pour conformité et sécurité
✅ Templates de notifications réutilisables
✅ Index optimisés pour toutes les requêtes fréquentes
✅ Triggers pour mise à jour automatique des stats
✅ Support multi-événements dans le panier
✅ Codes promo liés à des événements spécifiques
✅ Système de tags pour meilleure recherche
*/

