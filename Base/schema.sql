-- ============================================
-- AIOLIA EVENT PLATFORM - SCHEMA POSTGRESQL
-- Génération : 2025-11-10
-- ============================================

-- ============================================
-- 1️⃣ SUPPRESSION ET CREATION DE L'UTILISATEUR ET DE LA BASE
-- ============================================

-- À exécuter depuis une connexion admin (ex: postgres)
DROP DATABASE IF EXISTS aiolia_event;
DROP ROLE IF EXISTS aiolia_user;

CREATE ROLE aiolia_user WITH
    LOGIN
    PASSWORD 'aiolia2025'
    CREATEDB
    NOSUPERUSER
    NOCREATEROLE;

CREATE DATABASE aiolia_event
    WITH 
        OWNER = aiolia_user
        ENCODING = 'UTF8'
        LC_COLLATE = 'fr_FR.UTF-8'
        LC_CTYPE = 'fr_FR.UTF-8'
        TEMPLATE = template0;

-- ============================================
-- 2️⃣ CONNEXION À LA BASE
-- ============================================
\c aiolia_event;

-- ============================================
-- 3️⃣ EXTENSIONS ET SCHEMA
-- ============================================
CREATE EXTENSION IF NOT EXISTS "pgcrypto";
CREATE EXTENSION IF NOT EXISTS citext;

CREATE SCHEMA IF NOT EXISTS aiolia AUTHORIZATION aiolia_user;
SET search_path TO aiolia, public;

-- ============================================
-- 4️⃣ DOMAINES
-- ============================================
CREATE DOMAIN currency_code AS CHAR(3)
  CHECK (VALUE ~ '^[A-Z]{3}$');

CREATE DOMAIN phone_e164 AS VARCHAR(20)
  CHECK (VALUE ~ '^\+\d{6,18}$');

-- ============================================
-- 5️⃣ SÉQUENCES
-- ============================================
CREATE SEQUENCE IF NOT EXISTS invoice_number_seq START WITH 100000;


-- Tables coeur Utilisateurs & Rôles -------------------------------------------------

CREATE TABLE users (
    user_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    email CITEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    first_name TEXT NOT NULL,
    last_name TEXT,
    phone phone_e164,
    country_code CHAR(2),
    language_code VARCHAR(10) NOT NULL DEFAULT 'fr-FR',
    timezone TEXT DEFAULT 'Indian/Antananarivo',
    avatar_url TEXT,
    status TEXT NOT NULL DEFAULT 'pending'
        CHECK (status IN ('pending', 'active', 'suspended', 'deleted')),
    auth_provider TEXT NOT NULL DEFAULT 'password'
        CHECK (auth_provider IN ('password', 'google', 'facebook', 'apple', 'azure')),
    oauth_provider_id TEXT,
    is_email_verified BOOLEAN NOT NULL DEFAULT FALSE,
    is_phone_verified BOOLEAN NOT NULL DEFAULT FALSE,
    two_factor_type TEXT
        CHECK (two_factor_type IS NULL OR two_factor_type IN ('totp', 'sms')),
    accepted_terms_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    last_login_at TIMESTAMPTZ
);

CREATE TABLE user_roles (
    role_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    code TEXT NOT NULL UNIQUE,
    label TEXT NOT NULL,
    description TEXT
);

CREATE TABLE user_role_assignments (
    user_id UUID NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    role_id UUID NOT NULL REFERENCES user_roles(role_id) ON DELETE CASCADE,
    assigned_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    assigned_by UUID REFERENCES users(user_id),
    PRIMARY KEY (user_id, role_id)
);

CREATE TABLE organizer_profiles (
    organizer_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL UNIQUE REFERENCES users(user_id) ON DELETE CASCADE,
    display_name TEXT NOT NULL,
    legal_name TEXT,
    tax_number TEXT,
    support_email CITEXT,
    support_phone phone_e164,
    website_url TEXT,
    biography TEXT,
    verification_status TEXT NOT NULL DEFAULT 'pending'
        CHECK (verification_status IN ('pending', 'verified', 'rejected')),
    onboarding_completed_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE user_validation_requests (
    validation_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    requested_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    status TEXT NOT NULL DEFAULT 'pending'
        CHECK (status IN ('pending', 'approved', 'rejected', 'cancelled')),
    reviewer_user_id UUID REFERENCES users(user_id),
    reviewed_at TIMESTAMPTZ,
    rejection_reason TEXT,
    additional_documents JSONB,
    metadata JSONB
);

CREATE TABLE user_preferences (
    preference_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    preference_key TEXT NOT NULL,
    preference_value JSONB NOT NULL,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE (user_id, preference_key)
);

-- Tables de classification ----------------------------------------------------------

CREATE TABLE event_categories (
    category_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    slug TEXT NOT NULL UNIQUE,
    label TEXT NOT NULL,
    description TEXT,
    icon_name TEXT,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    display_order INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE user_statistics (
    user_id UUID PRIMARY KEY REFERENCES users(user_id) ON DELETE CASCADE,
    events_attended_count INTEGER NOT NULL DEFAULT 0,
    tickets_owned_count INTEGER NOT NULL DEFAULT 0,
    lifetime_spend NUMERIC(14,2) NOT NULL DEFAULT 0,
    favorite_category_id UUID REFERENCES event_categories(category_id),
    last_purchase_at TIMESTAMPTZ,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE event_tags (
    tag_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    slug TEXT NOT NULL UNIQUE,
    label TEXT NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- Lieux & Événements ----------------------------------------------------------------

CREATE TABLE venues (
    venue_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organizer_id UUID REFERENCES organizer_profiles(organizer_id) ON DELETE SET NULL,
    name TEXT NOT NULL,
    description TEXT,
    address_line1 TEXT,
    address_line2 TEXT,
    city TEXT,
    region TEXT,
    postal_code TEXT,
    country_code CHAR(2),
    latitude NUMERIC(10,7),
    longitude NUMERIC(10,7),
    capacity INTEGER,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE events (
    event_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organizer_id UUID NOT NULL REFERENCES organizer_profiles(organizer_id) ON DELETE CASCADE,
    primary_category_id UUID REFERENCES event_categories(category_id),
    venue_id UUID REFERENCES venues(venue_id),
    slug TEXT NOT NULL UNIQUE,
    title TEXT NOT NULL,
    subtitle TEXT,
    summary TEXT,
    description TEXT,
    cover_image_url TEXT,
    status TEXT NOT NULL DEFAULT 'draft'
        CHECK (status IN ('draft', 'published', 'cancelled', 'archived')),
    visibility TEXT NOT NULL DEFAULT 'public'
        CHECK (visibility IN ('public', 'private', 'unlisted')),
    capacity INTEGER,
    timezone TEXT NOT NULL DEFAULT 'Indian/Antananarivo',
    starts_at TIMESTAMPTZ NOT NULL,
    ends_at TIMESTAMPTZ NOT NULL,
    sales_starts_at TIMESTAMPTZ,
    sales_ends_at TIMESTAMPTZ,
    age_restriction TEXT,
    language_code VARCHAR(10) DEFAULT 'fr-FR',
    is_featured BOOLEAN NOT NULL DEFAULT FALSE,
    is_highlighted BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE event_category_links (
    event_id UUID NOT NULL REFERENCES events(event_id) ON DELETE CASCADE,
    category_id UUID NOT NULL REFERENCES event_categories(category_id),
    PRIMARY KEY (event_id, category_id)
);

CREATE TABLE event_tag_links (
    event_id UUID NOT NULL REFERENCES events(event_id) ON DELETE CASCADE,
    tag_id UUID NOT NULL REFERENCES event_tags(tag_id),
    PRIMARY KEY (event_id, tag_id)
);

CREATE TABLE event_sessions (
    session_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    event_id UUID NOT NULL REFERENCES events(event_id) ON DELETE CASCADE,
    title TEXT,
    starts_at TIMESTAMPTZ NOT NULL,
    ends_at TIMESTAMPTZ NOT NULL,
    capacity INTEGER,
    location_override TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE event_media (
    media_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    event_id UUID NOT NULL REFERENCES events(event_id) ON DELETE CASCADE,
    media_type TEXT NOT NULL CHECK (media_type IN ('image', 'video', 'document')),
    url TEXT NOT NULL,
    alt_text TEXT,
    display_order INTEGER NOT NULL DEFAULT 0,
    is_public BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE event_audit_logs (
    audit_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    event_id UUID NOT NULL REFERENCES events(event_id) ON DELETE CASCADE,
    actor_user_id UUID REFERENCES users(user_id),
    action TEXT NOT NULL,
    payload JSONB,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- Tickets & Commandes ----------------------------------------------------------------

CREATE TABLE ticket_types (
    ticket_type_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    event_id UUID NOT NULL REFERENCES events(event_id) ON DELETE CASCADE,
    session_id UUID REFERENCES event_sessions(session_id),
    name TEXT NOT NULL,
    description TEXT,
    currency currency_code NOT NULL DEFAULT 'MGA',
    base_price NUMERIC(12,2) NOT NULL CHECK (base_price >= 0),
    service_fee NUMERIC(12,2) NOT NULL DEFAULT 0 CHECK (service_fee >= 0),
    vat_rate NUMERIC(5,2) NOT NULL DEFAULT 0 CHECK (vat_rate >= 0),
    quantity_total INTEGER NOT NULL CHECK (quantity_total >= 0),
    quantity_reserved INTEGER NOT NULL DEFAULT 0 CHECK (quantity_reserved >= 0),
    quantity_sold INTEGER NOT NULL DEFAULT 0 CHECK (quantity_sold >= 0),
    sales_start TIMESTAMPTZ,
    sales_end TIMESTAMPTZ,
    min_per_order INTEGER NOT NULL DEFAULT 1 CHECK (min_per_order > 0),
    max_per_order INTEGER CHECK (max_per_order IS NULL OR max_per_order >= min_per_order),
    status TEXT NOT NULL DEFAULT 'inactive'
        CHECK (status IN ('inactive', 'on_sale', 'sold_out', 'hidden')),
    is_transferable BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE ticket_price_history (
    price_history_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    ticket_type_id UUID NOT NULL REFERENCES ticket_types(ticket_type_id) ON DELETE CASCADE,
    changed_by UUID REFERENCES users(user_id),
    change_source TEXT NOT NULL DEFAULT 'manual'
        CHECK (change_source IN ('manual', 'rule', 'promotion', 'system')),
    previous_base_price NUMERIC(12,2),
    new_base_price NUMERIC(12,2),
    previous_service_fee NUMERIC(12,2),
    new_service_fee NUMERIC(12,2),
    previous_vat_rate NUMERIC(5,2),
    new_vat_rate NUMERIC(5,2),
    change_reason TEXT,
    metadata JSONB,
    changed_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE ticket_quota_groups (
    quota_group_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    event_id UUID NOT NULL REFERENCES events(event_id) ON DELETE CASCADE,
    name TEXT NOT NULL,
    description TEXT,
    capacity_total INTEGER NOT NULL CHECK (capacity_total >= 0),
    capacity_reserved INTEGER NOT NULL DEFAULT 0 CHECK (capacity_reserved >= 0),
    capacity_sold INTEGER NOT NULL DEFAULT 0 CHECK (capacity_sold >= 0),
    per_user_limit INTEGER CHECK (per_user_limit IS NULL OR per_user_limit > 0),
    enforce_limits BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE (event_id, name)
);

CREATE TABLE ticket_quota_links (
    quota_group_id UUID NOT NULL REFERENCES ticket_quota_groups(quota_group_id) ON DELETE CASCADE,
    ticket_type_id UUID NOT NULL REFERENCES ticket_types(ticket_type_id) ON DELETE CASCADE,
    weight INTEGER NOT NULL DEFAULT 1 CHECK (weight > 0),
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    PRIMARY KEY (quota_group_id, ticket_type_id)
);

CREATE TABLE ticket_pricing_rules (
    pricing_rule_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    ticket_type_id UUID NOT NULL REFERENCES ticket_types(ticket_type_id) ON DELETE CASCADE,
    rule_type TEXT NOT NULL CHECK (rule_type IN ('tier', 'time_window', 'promo')),
    threshold_quantity INTEGER CHECK (threshold_quantity IS NULL OR threshold_quantity > 0),
    starts_at TIMESTAMPTZ,
    ends_at TIMESTAMPTZ,
    price NUMERIC(12,2) CHECK (price IS NULL OR price >= 0),
    discount_percent NUMERIC(5,2) CHECK (discount_percent IS NULL OR (discount_percent >= 0 AND discount_percent <= 100)),
    metadata JSONB,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE carts (
    cart_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID REFERENCES users(user_id) ON DELETE SET NULL,
    session_token TEXT UNIQUE,
    status TEXT NOT NULL DEFAULT 'active'
        CHECK (status IN ('active', 'converted', 'abandoned', 'expired')),
    currency currency_code NOT NULL DEFAULT 'MGA',
    total_amount NUMERIC(12,2) NOT NULL DEFAULT 0,
    expires_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE cart_items (
    cart_item_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    cart_id UUID NOT NULL REFERENCES carts(cart_id) ON DELETE CASCADE,
    ticket_type_id UUID NOT NULL REFERENCES ticket_types(ticket_type_id),
    quantity INTEGER NOT NULL CHECK (quantity > 0),
    unit_price NUMERIC(12,2) NOT NULL CHECK (unit_price >= 0),
    total_price NUMERIC(12,2) NOT NULL CHECK (total_price >= 0),
    metadata JSONB,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE (cart_id, ticket_type_id)
);

CREATE TABLE orders (
    order_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    cart_id UUID REFERENCES carts(cart_id),
    status TEXT NOT NULL DEFAULT 'pending'
        CHECK (status IN ('pending', 'awaiting_payment', 'paid', 'cancelled', 'refunded', 'failed')),
    total_amount NUMERIC(12,2) NOT NULL CHECK (total_amount >= 0),
    discount_total NUMERIC(12,2) NOT NULL DEFAULT 0 CHECK (discount_total >= 0),
    wallet_amount NUMERIC(12,2) NOT NULL DEFAULT 0 CHECK (wallet_amount >= 0),
    currency currency_code NOT NULL,
    promotion_code TEXT,
    invoice_number TEXT UNIQUE,
    notes TEXT,
    placed_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    confirmed_at TIMESTAMPTZ,
    cancelled_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE order_status_history (
    history_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_id UUID NOT NULL REFERENCES orders(order_id) ON DELETE CASCADE,
    user_id UUID REFERENCES users(user_id),
    status_from TEXT,
    status_to TEXT NOT NULL,
    amount_snapshot NUMERIC(12,2),
    discount_snapshot NUMERIC(12,2),
    wallet_snapshot NUMERIC(12,2),
    metadata JSONB,
    changed_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE order_items (
    order_item_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_id UUID NOT NULL REFERENCES orders(order_id) ON DELETE CASCADE,
    ticket_type_id UUID NOT NULL REFERENCES ticket_types(ticket_type_id),
    quantity INTEGER NOT NULL CHECK (quantity > 0),
    unit_price NUMERIC(12,2) NOT NULL CHECK (unit_price >= 0),
    service_fee_amount NUMERIC(12,2) NOT NULL DEFAULT 0 CHECK (service_fee_amount >= 0),
    vat_amount NUMERIC(12,2) NOT NULL DEFAULT 0 CHECK (vat_amount >= 0),
    total_amount NUMERIC(12,2) NOT NULL CHECK (total_amount >= 0),
    currency currency_code NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE tickets (
    ticket_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_item_id UUID NOT NULL REFERENCES order_items(order_item_id) ON DELETE CASCADE,
    ticket_type_id UUID NOT NULL REFERENCES ticket_types(ticket_type_id),
    owner_user_id UUID REFERENCES users(user_id),
    qr_code TEXT NOT NULL,
    qr_checksum TEXT,
    seat_label TEXT,
    status TEXT NOT NULL DEFAULT 'valid'
        CHECK (status IN ('valid', 'used', 'cancelled', 'refunded', 'transferred')),
    issued_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    checked_in_at TIMESTAMPTZ,
    transferred_at TIMESTAMPTZ,
    metadata JSONB
);

CREATE TABLE ticket_transfers (
    transfer_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    ticket_id UUID NOT NULL REFERENCES tickets(ticket_id) ON DELETE CASCADE,
    from_user_id UUID REFERENCES users(user_id),
    to_user_id UUID REFERENCES users(user_id),
    to_email CITEXT,
    status TEXT NOT NULL DEFAULT 'pending'
        CHECK (status IN ('pending', 'accepted', 'declined', 'cancelled')),
    token TEXT NOT NULL,
    expires_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE payments (
    payment_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_id UUID NOT NULL REFERENCES orders(order_id) ON DELETE CASCADE,
    provider TEXT NOT NULL CHECK (provider IN ('orange', 'airtel', 'telma', 'stripe', 'paypal', 'cash')),
    provider_reference TEXT,
    status TEXT NOT NULL DEFAULT 'initiated'
        CHECK (status IN ('initiated', 'processing', 'paid', 'failed', 'refunded')),
    amount NUMERIC(12,2) NOT NULL CHECK (amount >= 0),
    currency currency_code NOT NULL,
    paid_at TIMESTAMPTZ,
    metadata JSONB,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- Portefeuille & Fidélité -----------------------------------------------------------

CREATE TABLE wallets (
    wallet_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL UNIQUE REFERENCES users(user_id) ON DELETE CASCADE,
    currency currency_code NOT NULL DEFAULT 'MGA',
    balance NUMERIC(14,2) NOT NULL DEFAULT 0,
    locked_balance NUMERIC(14,2) NOT NULL DEFAULT 0,
    points_balance INTEGER NOT NULL DEFAULT 0,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE wallet_transactions (
    transaction_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    wallet_id UUID NOT NULL REFERENCES wallets(wallet_id) ON DELETE CASCADE,
    transaction_type TEXT NOT NULL
        CHECK (transaction_type IN ('credit', 'debit', 'points_credit', 'points_debit')),
    amount NUMERIC(14,2) NOT NULL DEFAULT 0,
    points_delta INTEGER NOT NULL DEFAULT 0,
    related_entity TEXT,
    related_id UUID,
    description TEXT,
    status TEXT NOT NULL DEFAULT 'pending'
        CHECK (status IN ('pending', 'completed', 'cancelled', 'failed')),
    processed_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- Promotions & Codes ----------------------------------------------------------------

CREATE TABLE promotions (
    promotion_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    code TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    description TEXT,
    discount_type TEXT NOT NULL CHECK (discount_type IN ('percent', 'amount')),
    discount_value NUMERIC(12,2) NOT NULL CHECK (discount_value > 0),
    max_discount_amount NUMERIC(12,2),
    max_usage_total INTEGER,
    max_usage_per_user INTEGER,
    starts_at TIMESTAMPTZ,
    ends_at TIMESTAMPTZ,
    is_stackable BOOLEAN NOT NULL DEFAULT FALSE,
    status TEXT NOT NULL DEFAULT 'draft'
        CHECK (status IN ('draft', 'active', 'paused', 'expired')),
    metadata JSONB,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE promotion_targets (
    promotion_id UUID NOT NULL REFERENCES promotions(promotion_id) ON DELETE CASCADE,
    event_id UUID REFERENCES events(event_id) ON DELETE CASCADE,
    ticket_type_id UUID REFERENCES ticket_types(ticket_type_id) ON DELETE CASCADE,
    PRIMARY KEY (promotion_id, event_id, ticket_type_id)
);

CREATE TABLE promotion_redemptions (
    redemption_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    promotion_id UUID NOT NULL REFERENCES promotions(promotion_id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    order_id UUID NOT NULL REFERENCES orders(order_id) ON DELETE CASCADE,
    discount_amount NUMERIC(12,2) NOT NULL DEFAULT 0,
    redeemed_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE (promotion_id, user_id, order_id)
);

-- Wishlist, Invitations, Social -----------------------------------------------------

CREATE TABLE wishlists (
    wishlist_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    title TEXT NOT NULL DEFAULT 'Favoris',
    is_default BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE wishlist_items (
    wishlist_id UUID NOT NULL REFERENCES wishlists(wishlist_id) ON DELETE CASCADE,
    event_id UUID NOT NULL REFERENCES events(event_id) ON DELETE CASCADE,
    added_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    PRIMARY KEY (wishlist_id, event_id)
);

CREATE TABLE event_invitations (
    invitation_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    event_id UUID NOT NULL REFERENCES events(event_id) ON DELETE CASCADE,
    sender_user_id UUID NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    recipient_email CITEXT NOT NULL,
    message TEXT,
    token TEXT NOT NULL UNIQUE,
    status TEXT NOT NULL DEFAULT 'pending'
        CHECK (status IN ('pending', 'accepted', 'declined', 'expired')),
    reward_points INTEGER DEFAULT 0,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    responded_at TIMESTAMPTZ
);

CREATE TABLE ticket_chance_entries (
    entry_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    event_id UUID REFERENCES events(event_id) ON DELETE SET NULL,
    prize_type TEXT NOT NULL CHECK (prize_type IN ('discount', 'free_ticket', 'points')),
    prize_value NUMERIC(12,2),
    points_awarded INTEGER,
    status TEXT NOT NULL DEFAULT 'pending'
        CHECK (status IN ('pending', 'won', 'lost', 'claimed')),
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    claimed_at TIMESTAMPTZ
);

CREATE TABLE user_event_visibility (
    user_id UUID NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    event_id UUID NOT NULL REFERENCES events(event_id) ON DELETE CASCADE,
    visibility TEXT NOT NULL CHECK (visibility IN ('public', 'friends', 'private')),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    PRIMARY KEY (user_id, event_id)
);

-- Notifications ---------------------------------------------------------------------

CREATE TABLE notification_channels (
    channel_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    code TEXT NOT NULL UNIQUE,
    description TEXT
);

CREATE TABLE notification_templates (
    template_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    code TEXT NOT NULL UNIQUE,
    channel_code TEXT NOT NULL,
    subject TEXT,
    body TEXT NOT NULL,
    metadata JSONB,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE user_notification_preferences (
    user_id UUID NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    channel_code TEXT NOT NULL,
    enabled BOOLEAN NOT NULL DEFAULT TRUE,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    PRIMARY KEY (user_id, channel_code)
);

CREATE TABLE notifications (
    notification_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    channel_code TEXT NOT NULL,
    template_code TEXT,
    payload JSONB NOT NULL,
    status TEXT NOT NULL DEFAULT 'pending'
        CHECK (status IN ('pending', 'queued', 'sent', 'failed', 'read')),
    scheduled_at TIMESTAMPTZ,
    sent_at TIMESTAMPTZ,
    read_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE notification_history (
    history_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    notification_id UUID NOT NULL REFERENCES notifications(notification_id) ON DELETE CASCADE,
    status TEXT NOT NULL,
    message TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- Rapports & Exports ----------------------------------------------------------------

CREATE TABLE reports (
    report_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organizer_id UUID REFERENCES organizer_profiles(organizer_id) ON DELETE CASCADE,
    admin_user_id UUID REFERENCES users(user_id),
    code TEXT NOT NULL,
    parameters JSONB,
    status TEXT NOT NULL DEFAULT 'pending'
        CHECK (status IN ('pending', 'generating', 'ready', 'failed')),
    generated_file_url TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    completed_at TIMESTAMPTZ
);

CREATE TABLE audit_logs (
    audit_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    actor_user_id UUID REFERENCES users(user_id),
    scope TEXT NOT NULL,
    action TEXT NOT NULL,
    entity_type TEXT,
    entity_id UUID,
    changes JSONB,
    ip_address INET,
    user_agent TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- Historisation & Statistiques ------------------------------------------------------

CREATE TABLE daily_sales_summary (
    summary_date DATE NOT NULL,
    event_id UUID NOT NULL REFERENCES events(event_id) ON DELETE CASCADE,
    tickets_sold INTEGER NOT NULL DEFAULT 0,
    gross_revenue NUMERIC(14,2) NOT NULL DEFAULT 0,
    net_revenue NUMERIC(14,2) NOT NULL DEFAULT 0,
    PRIMARY KEY (summary_date, event_id)
);

CREATE TABLE user_search_history (
    search_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID REFERENCES users(user_id) ON DELETE CASCADE,
    keywords TEXT NOT NULL,
    filters JSONB,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- Indexations stratégiques ----------------------------------------------------------

CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_users_created_at ON users(created_at);
CREATE INDEX idx_events_status ON events(status);
CREATE INDEX idx_events_featured ON events(is_featured) WHERE is_featured;
CREATE INDEX idx_events_dates ON events(starts_at, ends_at);
CREATE INDEX idx_ticket_types_event ON ticket_types(event_id);
CREATE INDEX idx_orders_user ON orders(user_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_payments_order ON payments(order_id);
CREATE INDEX idx_wallet_transactions_wallet ON wallet_transactions(wallet_id);
CREATE INDEX idx_notifications_user_status ON notifications(user_id, status);
CREATE INDEX idx_ticket_price_history_ticket ON ticket_price_history(ticket_type_id);
CREATE INDEX idx_order_status_history_order ON order_status_history(order_id);
CREATE INDEX idx_ticket_quota_groups_event ON ticket_quota_groups(event_id);
CREATE INDEX idx_ticket_quota_links_ticket ON ticket_quota_links(ticket_type_id);
CREATE INDEX idx_promotion_targets_event ON promotion_targets(event_id);
CREATE INDEX idx_daily_sales_event_date ON daily_sales_summary(event_id, summary_date);
CREATE UNIQUE INDEX uq_user_validation_requests_pending ON user_validation_requests(user_id) WHERE status = 'pending';
CREATE UNIQUE INDEX uq_ticket_transfers_pending ON ticket_transfers(ticket_id) WHERE status = 'pending';
CREATE UNIQUE INDEX uq_wishlists_default ON wishlists(user_id) WHERE is_default;

-- Contraintes supplémentaires -------------------------------------------------------

ALTER TABLE ticket_types
    ADD CONSTRAINT chk_ticket_counts
        CHECK (quantity_reserved <= quantity_total AND quantity_sold <= quantity_total);

ALTER TABLE wallet_transactions
    ADD CONSTRAINT chk_wallet_transaction_amount
        CHECK (
            (transaction_type IN ('credit', 'debit') AND amount <> 0)
            OR (transaction_type IN ('points_credit', 'points_debit') AND points_delta <> 0)
        );

ALTER TABLE promotion_redemptions
    ADD CONSTRAINT chk_promotion_discount_amount
        CHECK (discount_amount >= 0);

ALTER TABLE tickets
    ADD CONSTRAINT uq_tickets_qr_code UNIQUE (qr_code);

ALTER TABLE ticket_quota_groups
    ADD CONSTRAINT chk_ticket_quota_capacity
        CHECK (
            capacity_reserved <= capacity_total
            AND capacity_sold <= capacity_total
        );

-- Fin du schéma ---------------------------------------------------------------------

