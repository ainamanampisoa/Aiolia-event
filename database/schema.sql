-- ============================================================================
-- AIOLIA EVENT - SCHEMA SQL COMPLET
-- ============================================================================
-- Système de gestion d'événements avec billetterie, paiement et statistiques
-- ============================================================================

-- ============================================================================
-- 1. AUTHENTIFICATION & UTILISATEURS
-- ============================================================================

-- Table des utilisateurs
CREATE TABLE users (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    phone VARCHAR(20),
    photo_url VARCHAR(500),
    role ENUM('user', 'co_organizer', 'organizer', 'admin') DEFAULT 'user',
    email_verified BOOLEAN DEFAULT FALSE,
    phone_verified BOOLEAN DEFAULT FALSE,
    oauth_provider ENUM('google', 'facebook', 'local') DEFAULT 'local',
    oauth_provider_id VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    dark_mode BOOLEAN DEFAULT FALSE,
    language VARCHAR(5) DEFAULT 'fr',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login_at TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_oauth (oauth_provider, oauth_provider_id)
);

-- Table des tokens JWT (refresh tokens)
CREATE TABLE refresh_tokens (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    token VARCHAR(500) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    is_revoked BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_token (user_id, token),
    INDEX idx_expires (expires_at)
);

-- Table des permissions
CREATE TABLE permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    module VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table de liaison rôles-permissions
CREATE TABLE role_permissions (
    role ENUM('user', 'co_organizer', 'organizer', 'admin') NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (role, permission_id),
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

-- ============================================================================
-- 2. CATÉGORIES & ÉVÉNEMENTS
-- ============================================================================

-- Table des catégories d'événements
CREATE TABLE event_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slug (slug)
);

-- Table des événements
CREATE TABLE events (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    organizer_id BIGINT NOT NULL,
    category_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    short_description VARCHAR(500),
    location VARCHAR(255),
    address TEXT,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    timezone VARCHAR(50) DEFAULT 'Indian/Antananarivo',
    status ENUM('draft', 'published', 'ongoing', 'completed', 'cancelled') DEFAULT 'draft',
    is_featured BOOLEAN DEFAULT FALSE,
    is_premium BOOLEAN DEFAULT FALSE,
    premium_expires_at TIMESTAMP NULL,
    total_capacity INT,
    min_age INT,
    max_age INT,
    dress_code VARCHAR(100),
    language VARCHAR(100),
    accessibility_info TEXT,
    refund_policy TEXT,
    terms_conditions TEXT,
    views_count INT DEFAULT 0,
    favorites_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    published_at TIMESTAMP NULL,
    FOREIGN KEY (organizer_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (category_id) REFERENCES event_categories(id),
    INDEX idx_organizer (organizer_id),
    INDEX idx_category (category_id),
    INDEX idx_dates (start_date, end_date),
    INDEX idx_status (status),
    INDEX idx_slug (slug),
    INDEX idx_location (location),
    FULLTEXT idx_search (title, description, location)
);

-- Table des médias d'événements
CREATE TABLE event_media (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    event_id BIGINT NOT NULL,
    media_type ENUM('image', 'video', 'document') NOT NULL,
    file_url VARCHAR(500) NOT NULL,
    file_name VARCHAR(255),
    file_size BIGINT,
    mime_type VARCHAR(100),
    is_primary BOOLEAN DEFAULT FALSE,
    display_order INT DEFAULT 0,
    uploaded_by BIGINT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id),
    INDEX idx_event (event_id),
    INDEX idx_type (media_type)
);

-- Table d'équipe d'organisateurs (co-organisateurs)
CREATE TABLE event_team (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    event_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    role ENUM('owner', 'co_organizer', 'moderator') DEFAULT 'co_organizer',
    permissions JSON, -- Liste des permissions spécifiques
    invited_by BIGINT,
    invited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    accepted_at TIMESTAMP NULL,
    status ENUM('pending', 'accepted', 'declined') DEFAULT 'pending',
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (invited_by) REFERENCES users(id),
    UNIQUE KEY unique_event_user (event_id, user_id),
    INDEX idx_event (event_id),
    INDEX idx_user (user_id)
);

-- ============================================================================
-- 3. BILLETS & CATÉGORIES DE BILLETS
-- ============================================================================

-- Table des catégories de billets
CREATE TABLE ticket_categories (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    event_id BIGINT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    original_price DECIMAL(10, 2), -- Pour afficher les réductions
    currency VARCHAR(3) DEFAULT 'MGA',
    quantity_total INT NOT NULL,
    quantity_sold INT DEFAULT 0,
    quantity_reserved INT DEFAULT 0, -- En cours de réservation (panier)
    min_purchase INT DEFAULT 1,
    max_purchase INT DEFAULT 10,
    sale_start_date DATETIME,
    sale_end_date DATETIME,
    is_active BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0,
    perks JSON, -- Avantages inclus (JSON array)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    INDEX idx_event (event_id),
    CHECK (quantity_sold + quantity_reserved <= quantity_total)
);

-- Historique des modifications de prix
CREATE TABLE ticket_price_history (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    ticket_category_id BIGINT NOT NULL,
    old_price DECIMAL(10, 2),
    new_price DECIMAL(10, 2) NOT NULL,
    reason VARCHAR(255), -- 'dynamic_pricing', 'manual', 'promotion'
    changed_by BIGINT,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_category_id) REFERENCES ticket_categories(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id),
    INDEX idx_category (ticket_category_id)
);

-- Configuration de tarification dynamique
CREATE TABLE dynamic_pricing_rules (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    ticket_category_id BIGINT NOT NULL,
    threshold_percentage INT NOT NULL, -- Ex: 50% vendu
    price_multiplier DECIMAL(5, 2) NOT NULL, -- Ex: 1.2 = +20%
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_category_id) REFERENCES ticket_categories(id) ON DELETE CASCADE,
    INDEX idx_category (ticket_category_id)
);

-- Table des billets individuels
CREATE TABLE tickets (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    ticket_category_id BIGINT NOT NULL,
    order_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    ticket_number VARCHAR(50) UNIQUE NOT NULL,
    qr_code_data VARCHAR(500) UNIQUE NOT NULL,
    qr_code_image_url VARCHAR(500),
    status ENUM('valid', 'used', 'cancelled', 'refunded', 'transferred') DEFAULT 'valid',
    original_owner_id BIGINT, -- Si le billet a été transféré
    current_owner_id BIGINT, -- Propriétaire actuel
    check_in_at TIMESTAMP NULL,
    check_in_by BIGINT NULL, -- ID de l'organisateur qui a scanné
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_category_id) REFERENCES ticket_categories(id),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE RESTRICT,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (original_owner_id) REFERENCES users(id),
    FOREIGN KEY (current_owner_id) REFERENCES users(id),
    FOREIGN KEY (check_in_by) REFERENCES users(id),
    INDEX idx_category (ticket_category_id),
    INDEX idx_order (order_id),
    INDEX idx_user (user_id),
    INDEX idx_qr (qr_code_data),
    INDEX idx_status (status)
);

-- Historique de transfert de billets
CREATE TABLE ticket_transfers (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    ticket_id BIGINT NOT NULL,
    from_user_id BIGINT NOT NULL,
    to_user_id BIGINT NOT NULL,
    to_email VARCHAR(255), -- Si le destinataire n'a pas encore de compte
    transfer_token VARCHAR(100) UNIQUE,
    status ENUM('pending', 'accepted', 'declined', 'expired') DEFAULT 'pending',
    expires_at TIMESTAMP NOT NULL,
    transferred_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    accepted_at TIMESTAMP NULL,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (from_user_id) REFERENCES users(id),
    FOREIGN KEY (to_user_id) REFERENCES users(id),
    INDEX idx_ticket (ticket_id),
    INDEX idx_token (transfer_token)
);

-- ============================================================================
-- 4. CODES PROMO & PROMOTIONS
-- ============================================================================

-- Table des codes promo
CREATE TABLE promo_codes (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    discount_type ENUM('percentage', 'fixed_amount') NOT NULL,
    discount_value DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'MGA',
    max_uses INT, -- NULL = illimité
    current_uses INT DEFAULT 0,
    max_uses_per_user INT DEFAULT 1,
    valid_from DATETIME NOT NULL,
    valid_until DATETIME NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    min_purchase_amount DECIMAL(10, 2),
    applicable_to ENUM('all', 'specific_events', 'specific_categories', 'specific_tickets') DEFAULT 'all',
    created_by BIGINT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_code (code),
    INDEX idx_active (is_active),
    INDEX idx_dates (valid_from, valid_until)
);

-- Table de liaison codes promo - événements
CREATE TABLE promo_code_events (
    promo_code_id BIGINT NOT NULL,
    event_id BIGINT NOT NULL,
    PRIMARY KEY (promo_code_id, event_id),
    FOREIGN KEY (promo_code_id) REFERENCES promo_codes(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
);

-- Table de liaison codes promo - catégories de billets
CREATE TABLE promo_code_ticket_categories (
    promo_code_id BIGINT NOT NULL,
    ticket_category_id BIGINT NOT NULL,
    PRIMARY KEY (promo_code_id, ticket_category_id),
    FOREIGN KEY (promo_code_id) REFERENCES promo_codes(id) ON DELETE CASCADE,
    FOREIGN KEY (ticket_category_id) REFERENCES ticket_categories(id) ON DELETE CASCADE
);

-- Utilisation des codes promo par utilisateur
CREATE TABLE promo_code_usage (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    promo_code_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    order_id BIGINT NOT NULL,
    discount_applied DECIMAL(10, 2) NOT NULL,
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (promo_code_id) REFERENCES promo_codes(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_promo (promo_code_id),
    INDEX idx_user (user_id)
);

-- ============================================================================
-- 5. COMMANDES & PAIEMENTS
-- ============================================================================

-- Table des commandes
CREATE TABLE orders (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded') DEFAULT 'pending',
    subtotal DECIMAL(10, 2) NOT NULL,
    discount_amount DECIMAL(10, 2) DEFAULT 0,
    promo_code_id BIGINT,
    total_amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'MGA',
    payment_status ENUM('pending', 'processing', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    payment_method ENUM('orange_money', 'airtel_money', 'telma_money', 'bank_card', 'bank_transfer', 'cash') NULL,
    billing_email VARCHAR(255),
    billing_phone VARCHAR(20),
    billing_address TEXT,
    notes TEXT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    completed_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    refunded_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (promo_code_id) REFERENCES promo_codes(id),
    INDEX idx_user (user_id),
    INDEX idx_order_number (order_number),
    INDEX idx_status (status),
    INDEX idx_payment_status (payment_status),
    INDEX idx_created (created_at)
);

-- Table des items de commande
CREATE TABLE order_items (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    order_id BIGINT NOT NULL,
    ticket_category_id BIGINT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (ticket_category_id) REFERENCES ticket_categories(id),
    INDEX idx_order (order_id)
);

-- Table des paiements (transactions)
CREATE TABLE payments (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    order_id BIGINT NOT NULL,
    payment_method ENUM('orange_money', 'airtel_money', 'telma_money', 'bank_card', 'bank_transfer', 'cash') NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'MGA',
    status ENUM('pending', 'processing', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    transaction_id VARCHAR(255) UNIQUE, -- ID de la transaction Mobile Money
    reference_number VARCHAR(100),
    phone_number VARCHAR(20), -- Pour Mobile Money
    provider_response JSON, -- Réponse complète du fournisseur de paiement
    error_message TEXT,
    processed_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    refunded_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE RESTRICT,
    INDEX idx_order (order_id),
    INDEX idx_transaction (transaction_id),
    INDEX idx_status (status),
    INDEX idx_method (payment_method)
);

-- Table des factures
CREATE TABLE invoices (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    order_id BIGINT NOT NULL,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    pdf_url VARCHAR(500),
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE RESTRICT,
    INDEX idx_order (order_id),
    INDEX idx_invoice_number (invoice_number)
);

-- ============================================================================
-- 6. PANIER D'ACHAT
-- ============================================================================

-- Table du panier
CREATE TABLE cart (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    session_id VARCHAR(100), -- Pour les utilisateurs non connectés
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_session (session_id)
);

-- Table des items du panier
CREATE TABLE cart_items (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    cart_id BIGINT NOT NULL,
    ticket_category_id BIGINT NOT NULL,
    quantity INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cart_id) REFERENCES cart(id) ON DELETE CASCADE,
    FOREIGN KEY (ticket_category_id) REFERENCES ticket_categories(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_ticket (cart_id, ticket_category_id),
    INDEX idx_cart (cart_id)
);

-- ============================================================================
-- 7. FAVORIS & WISHLIST
-- ============================================================================

-- Table des favoris
CREATE TABLE favorites (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    event_id BIGINT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_event (user_id, event_id),
    INDEX idx_user (user_id),
    INDEX idx_event (event_id)
);

-- ============================================================================
-- 8. HISTORIQUE & RECHERCHES
-- ============================================================================

-- Historique de recherche
CREATE TABLE search_history (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    search_query VARCHAR(255) NOT NULL,
    filters JSON, -- Filtres appliqués (catégorie, date, prix, etc.)
    results_count INT,
    searched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_query (search_query)
);

-- Historique de vues d'événements
CREATE TABLE event_views (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    event_id BIGINT NOT NULL,
    user_id BIGINT NULL, -- NULL si utilisateur non connecté
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_event (event_id),
    INDEX idx_user (user_id),
    INDEX idx_date (viewed_at)
);

-- ============================================================================
-- 9. PORTEFEUILLE & POINTS DE FIDÉLITÉ
-- ============================================================================

-- Table du portefeuille numérique
CREATE TABLE wallet (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNIQUE NOT NULL,
    loyalty_points INT DEFAULT 0,
    total_earned_points INT DEFAULT 0,
    balance DECIMAL(10, 2) DEFAULT 0.00, -- Solde en argent
    currency VARCHAR(3) DEFAULT 'MGA',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
);

-- Historique des transactions du portefeuille
CREATE TABLE wallet_transactions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    wallet_id BIGINT NOT NULL,
    transaction_type ENUM('credit', 'debit') NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    points INT DEFAULT 0,
    description VARCHAR(255),
    reference_type ENUM('order', 'refund', 'gift', 'game', 'referral') NOT NULL,
    reference_id BIGINT, -- ID de la commande, parrainage, etc.
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (wallet_id) REFERENCES wallet(id) ON DELETE CASCADE,
    INDEX idx_wallet (wallet_id),
    INDEX idx_created (created_at)
);

-- Programme de fidélité - Règles
CREATE TABLE loyalty_rules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    event_type ENUM('purchase', 'referral', 'review', 'game_win') NOT NULL,
    points_earned INT NOT NULL,
    min_amount DECIMAL(10, 2), -- Montant minimum pour gagner des points
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================================
-- 10. PARRAINAGE & INVITATIONS
-- ============================================================================

-- Table des parrainages
CREATE TABLE referrals (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    referrer_id BIGINT NOT NULL, -- Celui qui invite
    referred_id BIGINT, -- Celui qui est invité (NULL tant qu'il ne s'inscrit pas)
    referred_email VARCHAR(255) NOT NULL,
    referral_code VARCHAR(50) UNIQUE NOT NULL,
    status ENUM('pending', 'completed', 'rewarded') DEFAULT 'pending',
    discount_amount DECIMAL(10, 2), -- Réduction accordée au filleul
    reward_amount DECIMAL(10, 2), -- Récompense du parrain
    referred_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL, -- Quand le filleul s'inscrit
    rewarded_at TIMESTAMP NULL, -- Quand le parrain reçoit sa récompense
    FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (referred_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_referrer (referrer_id),
    INDEX idx_code (referral_code),
    INDEX idx_status (status)
);

-- ============================================================================
-- 11. MINI-JEU "TICKET CHANCE"
-- ============================================================================

-- Table des participations au jeu
CREATE TABLE game_participations (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    game_type VARCHAR(50) DEFAULT 'ticket_chance',
    prize_type ENUM('discount', 'free_ticket', 'points', 'nothing') NOT NULL,
    prize_value DECIMAL(10, 2),
    promo_code_id BIGINT NULL, -- Si c'est une réduction
    ticket_id BIGINT NULL, -- Si c'est un billet gratuit
    points_awarded INT,
    is_claimed BOOLEAN DEFAULT FALSE,
    claimed_at TIMESTAMP NULL,
    played_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (promo_code_id) REFERENCES promo_codes(id),
    FOREIGN KEY (ticket_id) REFERENCES tickets(id),
    INDEX idx_user (user_id),
    INDEX idx_played (played_at)
);

-- Configuration du jeu
CREATE TABLE game_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    game_type VARCHAR(50) NOT NULL,
    max_plays_per_user_daily INT DEFAULT 1,
    max_plays_per_user_total INT,
    prize_probabilities JSON, -- Probabilités de chaque type de lot
    is_active BOOLEAN DEFAULT TRUE,
    start_date DATETIME,
    end_date DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================================
-- 12. ÉVÉNEMENTS ENTRE AMIS
-- ============================================================================

-- Relations d'amitié
CREATE TABLE friendships (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    friend_id BIGINT NOT NULL,
    status ENUM('pending', 'accepted', 'declined', 'blocked') DEFAULT 'pending',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    accepted_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (friend_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_friendship (user_id, friend_id),
    INDEX idx_user (user_id),
    INDEX idx_friend (friend_id),
    INDEX idx_status (status)
);

-- Événements partagés entre amis
CREATE TABLE friend_events (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    event_id BIGINT NOT NULL,
    visibility ENUM('public', 'friends_only', 'private') DEFAULT 'friends_only',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_event (user_id, event_id),
    INDEX idx_user (user_id),
    INDEX idx_event (event_id)
);

-- ============================================================================
-- 13. LISTE D'ATTENTE
-- ============================================================================

-- Liste d'attente pour événements complets
CREATE TABLE waiting_list (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    event_id BIGINT NOT NULL,
    ticket_category_id BIGINT,
    user_id BIGINT NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    quantity_requested INT DEFAULT 1,
    status ENUM('waiting', 'notified', 'converted', 'expired') DEFAULT 'waiting',
    priority_score INT DEFAULT 0, -- Pour gérer la priorité
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notified_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (ticket_category_id) REFERENCES ticket_categories(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_event (event_id),
    INDEX idx_user (user_id),
    INDEX idx_status (status)
);

-- ============================================================================
-- 14. NOTIFICATIONS
-- ============================================================================

-- Table des notifications
CREATE TABLE notifications (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    type ENUM('order_confirmation', 'payment_success', 'event_reminder', 'ticket_transferred', 
              'new_event', 'promotion', 'almost_full', 'price_drop', 'waiting_list', 'friend_request', 
              'game_prize', 'referral_reward', 'organizer_update') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    channel ENUM('email', 'push', 'sms', 'in_app') NOT NULL,
    status ENUM('pending', 'sent', 'failed', 'read') DEFAULT 'pending',
    reference_type VARCHAR(50), -- 'order', 'event', 'ticket', etc.
    reference_id BIGINT,
    metadata JSON, -- Données supplémentaires
    sent_at TIMESTAMP NULL,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_type (type),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
);

-- Préférences de notifications utilisateur
CREATE TABLE notification_preferences (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNIQUE NOT NULL,
    email_order_confirmation BOOLEAN DEFAULT TRUE,
    email_event_reminder BOOLEAN DEFAULT TRUE,
    email_organizer_updates BOOLEAN DEFAULT TRUE,
    email_promotions BOOLEAN DEFAULT FALSE,
    email_new_events BOOLEAN DEFAULT FALSE,
    push_order_confirmation BOOLEAN DEFAULT TRUE,
    push_event_reminder BOOLEAN DEFAULT TRUE,
    push_promotions BOOLEAN DEFAULT TRUE,
    push_almost_full BOOLEAN DEFAULT FALSE,
    push_new_events BOOLEAN DEFAULT TRUE,
    sms_order_confirmation BOOLEAN DEFAULT FALSE,
    sms_event_reminder BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================================
-- 15. AVIS & ÉVALUATIONS
-- ============================================================================

-- Table des avis sur les événements
CREATE TABLE reviews (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    event_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    order_id BIGINT NOT NULL, -- Seuls ceux qui ont acheté peuvent laisser un avis
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(255),
    comment TEXT,
    is_verified_purchase BOOLEAN DEFAULT TRUE,
    is_published BOOLEAN DEFAULT TRUE,
    organizer_response TEXT,
    organizer_response_at TIMESTAMP NULL,
    helpful_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_event (user_id, event_id),
    INDEX idx_event (event_id),
    INDEX idx_user (user_id),
    INDEX idx_rating (rating)
);

-- Votes utiles sur les avis
CREATE TABLE review_votes (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    review_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    is_helpful BOOLEAN NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_review (user_id, review_id)
);

-- ============================================================================
-- 16. STATISTIQUES & ANALYTIQUES
-- ============================================================================

-- Statistiques globales des événements
CREATE TABLE event_statistics (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    event_id BIGINT UNIQUE NOT NULL,
    total_views INT DEFAULT 0,
    unique_views INT DEFAULT 0,
    total_favorites INT DEFAULT 0,
    total_tickets_sold INT DEFAULT 0,
    total_revenue DECIMAL(12, 2) DEFAULT 0,
    average_ticket_price DECIMAL(10, 2) DEFAULT 0,
    conversion_rate DECIMAL(5, 2) DEFAULT 0, -- Pourcentage de vues qui achètent
    average_cart_value DECIMAL(10, 2) DEFAULT 0,
    total_refunds INT DEFAULT 0,
    refund_rate DECIMAL(5, 2) DEFAULT 0,
    average_rating DECIMAL(3, 2) DEFAULT 0,
    total_reviews INT DEFAULT 0,
    last_calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
);

-- Statistiques utilisateur
CREATE TABLE user_statistics (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNIQUE NOT NULL,
    total_events_attended INT DEFAULT 0,
    total_tickets_purchased INT DEFAULT 0,
    total_spent DECIMAL(12, 2) DEFAULT 0,
    average_order_value DECIMAL(10, 2) DEFAULT 0,
    favorite_category_id INT,
    total_referrals INT DEFAULT 0,
    total_reviews INT DEFAULT 0,
    average_rating_given DECIMAL(3, 2) DEFAULT 0,
    loyalty_tier ENUM('bronze', 'silver', 'gold', 'platinum') DEFAULT 'bronze',
    last_purchase_at TIMESTAMP NULL,
    last_calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (favorite_category_id) REFERENCES event_categories(id)
);

-- Statistiques de vente quotidiennes
CREATE TABLE daily_sales_stats (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    event_id BIGINT NOT NULL,
    stat_date DATE NOT NULL,
    tickets_sold INT DEFAULT 0,
    revenue DECIMAL(10, 2) DEFAULT 0,
    refunds INT DEFAULT 0,
    refund_amount DECIMAL(10, 2) DEFAULT 0,
    new_views INT DEFAULT 0,
    conversion_rate DECIMAL(5, 2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    UNIQUE KEY unique_event_date (event_id, stat_date),
    INDEX idx_event (event_id),
    INDEX idx_date (stat_date)
);

-- ============================================================================
-- 17. RAPPORTS
-- ============================================================================

-- Rapports générés
CREATE TABLE reports (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    event_id BIGINT,
    generated_by BIGINT NOT NULL,
    report_type ENUM('sales', 'financial', 'attendance', 'post_event', 'tax', 'participant_list') NOT NULL,
    format ENUM('pdf', 'csv', 'excel') NOT NULL,
    file_url VARCHAR(500),
    date_from DATE,
    date_to DATE,
    filters JSON, -- Filtres appliqués au rapport
    status ENUM('generating', 'completed', 'failed') DEFAULT 'generating',
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (generated_by) REFERENCES users(id),
    INDEX idx_event (event_id),
    INDEX idx_generated_by (generated_by),
    INDEX idx_type (report_type)
);

-- ============================================================================
-- 18. LOGS & AUDIT
-- ============================================================================

-- Logs d'audit pour traçabilité
CREATE TABLE audit_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NOT NULL, -- 'event', 'ticket', 'order', etc.
    entity_id BIGINT NOT NULL,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
);

-- ============================================================================
-- 19. CONFIGURATION SYSTÈME
-- ============================================================================

-- Paramètres du système
CREATE TABLE system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE, -- Accessible via API publique
    updated_by BIGINT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id),
    INDEX idx_key (setting_key)
);

-- ============================================================================
-- 20. TRADUCTIONS (MULTI-LANGUE)
-- ============================================================================

-- Table des traductions
CREATE TABLE translations (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    entity_type VARCHAR(50) NOT NULL, -- 'event', 'category', 'ticket_category', etc.
    entity_id BIGINT NOT NULL,
    field_name VARCHAR(50) NOT NULL, -- 'title', 'description', etc.
    language VARCHAR(5) NOT NULL, -- 'fr', 'en', 'mg'
    translated_value TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_translation (entity_type, entity_id, field_name, language),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_language (language)
);

-- ============================================================================
-- VUES UTILES
-- ============================================================================

-- Vue des événements à venir avec statistiques
CREATE VIEW upcoming_events_with_stats AS
SELECT 
    e.*,
    ec.name as category_name,
    u.first_name as organizer_first_name,
    u.last_name as organizer_last_name,
    es.total_tickets_sold,
    es.total_revenue,
    es.average_rating,
    es.total_reviews,
    (SELECT COUNT(*) FROM favorites WHERE event_id = e.id) as favorites_count,
    (SELECT SUM(quantity_total - quantity_sold) FROM ticket_categories WHERE event_id = e.id) as available_tickets
FROM events e
LEFT JOIN event_categories ec ON e.category_id = ec.id
LEFT JOIN users u ON e.organizer_id = u.id
LEFT JOIN event_statistics es ON e.event_id = es.event_id
WHERE e.status = 'published' 
  AND e.start_date > NOW()
ORDER BY e.start_date ASC;

-- Vue des meilleurs événements
CREATE VIEW top_rated_events AS
SELECT 
    e.*,
    es.average_rating,
    es.total_reviews,
    es.total_tickets_sold
FROM events e
INNER JOIN event_statistics es ON e.id = es.event_id
WHERE e.status = 'published' 
  AND es.total_reviews >= 5
ORDER BY es.average_rating DESC, es.total_reviews DESC
LIMIT 100;

-- Vue du dashboard organisateur
CREATE VIEW organizer_dashboard AS
SELECT 
    u.id as organizer_id,
    COUNT(DISTINCT e.id) as total_events,
    COUNT(DISTINCT CASE WHEN e.status = 'published' THEN e.id END) as published_events,
    COUNT(DISTINCT CASE WHEN e.start_date > NOW() THEN e.id END) as upcoming_events,
    SUM(es.total_tickets_sold) as total_tickets_sold,
    SUM(es.total_revenue) as total_revenue,
    AVG(es.average_rating) as average_event_rating
FROM users u
LEFT JOIN events e ON u.id = e.organizer_id
LEFT JOIN event_statistics es ON e.id = es.event_id
WHERE u.role IN ('organizer', 'admin')
GROUP BY u.id;

-- ============================================================================
-- INDEX SUPPLÉMENTAIRES POUR PERFORMANCE
-- ============================================================================

-- Index composites pour recherches fréquentes
CREATE INDEX idx_events_search ON events(status, start_date, category_id);
CREATE INDEX idx_tickets_user_status ON tickets(user_id, status);
CREATE INDEX idx_orders_user_status ON orders(user_id, status, created_at);
CREATE INDEX idx_notifications_user_unread ON notifications(user_id, status, created_at);

-- ============================================================================
-- FIN DU SCHÉMA
-- ============================================================================

