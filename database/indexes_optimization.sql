-- ============================================================================
-- INDEX ADDITIONNELS & OPTIMISATIONS
-- ============================================================================
-- Ce fichier contient des index supplémentaires pour optimiser les requêtes
-- les plus fréquentes de la plateforme Aiolia Event
-- ============================================================================

-- ============================================================================
-- 1. OPTIMISATION DES RECHERCHES D'ÉVÉNEMENTS
-- ============================================================================

-- Recherche d'événements par statut, date et catégorie (requête très fréquente)
CREATE INDEX idx_events_status_date_cat 
ON events(status, start_date, category_id);

-- Recherche par localisation
CREATE INDEX idx_events_location 
ON events(location, start_date);

-- Événements d'un organisateur spécifique
CREATE INDEX idx_events_organizer_status 
ON events(organizer_id, status, start_date);

-- Événements featured/premium pour page d'accueil
CREATE INDEX idx_events_featured 
ON events(is_featured, is_premium, start_date) 
WHERE status = 'published';

-- Recherche géographique (pour fonctionnalités futures)
CREATE INDEX idx_events_geo 
ON events(latitude, longitude) 
WHERE status = 'published';

-- ============================================================================
-- 2. OPTIMISATION DES BILLETS
-- ============================================================================

-- Billets d'un utilisateur par statut (Mon profil -> Mes billets)
CREATE INDEX idx_tickets_user_status_date 
ON tickets(user_id, status, created_at DESC);

-- Billets d'un événement pour check-in
CREATE INDEX idx_tickets_event_status 
ON tickets(ticket_category_id, status);

-- Recherche par QR code (check-in rapide)
CREATE INDEX idx_tickets_qr_unique 
ON tickets(qr_code_data);

-- Billets transférés
CREATE INDEX idx_tickets_transfers 
ON tickets(current_owner_id, original_owner_id) 
WHERE status = 'transferred';

-- ============================================================================
-- 3. OPTIMISATION DES COMMANDES
-- ============================================================================

-- Commandes d'un utilisateur (historique)
CREATE INDEX idx_orders_user_status_date 
ON orders(user_id, status, created_at DESC);

-- Commandes en attente de paiement
CREATE INDEX idx_orders_payment_pending 
ON orders(payment_status, created_at) 
WHERE status = 'pending';

-- Commandes par méthode de paiement (analytics)
CREATE INDEX idx_orders_payment_method 
ON orders(payment_method, status, created_at);

-- Commandes avec code promo
CREATE INDEX idx_orders_promo 
ON orders(promo_code_id, status);

-- ============================================================================
-- 4. OPTIMISATION DES PAIEMENTS
-- ============================================================================

-- Paiements par statut et date (rapports financiers)
CREATE INDEX idx_payments_status_date 
ON payments(status, created_at DESC);

-- Paiements par méthode (analytics Mobile Money)
CREATE INDEX idx_payments_method_status 
ON payments(payment_method, status, created_at);

-- Recherche par transaction_id (webhook callback)
CREATE INDEX idx_payments_transaction 
ON payments(transaction_id, status);

-- Paiements à traiter
CREATE INDEX idx_payments_processing 
ON payments(status, created_at) 
WHERE status IN ('pending', 'processing');

-- ============================================================================
-- 5. OPTIMISATION DU PANIER
-- ============================================================================

-- Panier d'un utilisateur (accès ultra-fréquent)
CREATE INDEX idx_cart_user_updated 
ON cart(user_id, updated_at DESC);

-- Paniers expirés à nettoyer
CREATE INDEX idx_cart_expired 
ON cart(expires_at) 
WHERE expires_at IS NOT NULL;

-- Items du panier par catégorie de billet
CREATE INDEX idx_cart_items_ticket 
ON cart_items(ticket_category_id, cart_id);

-- ============================================================================
-- 6. OPTIMISATION DES NOTIFICATIONS
-- ============================================================================

-- Notifications non lues d'un utilisateur (accès très fréquent)
CREATE INDEX idx_notifications_user_unread 
ON notifications(user_id, status, created_at DESC) 
WHERE status IN ('pending', 'sent');

-- Notifications à envoyer
CREATE INDEX idx_notifications_pending 
ON notifications(status, channel, created_at) 
WHERE status = 'pending';

-- Notifications par type (analytics)
CREATE INDEX idx_notifications_type_date 
ON notifications(type, created_at);

-- ============================================================================
-- 7. OPTIMISATION DES STATISTIQUES
-- ============================================================================

-- Statistiques par événement (dashboard organisateur)
CREATE INDEX idx_event_stats_event 
ON event_statistics(event_id, last_calculated_at);

-- Statistiques par utilisateur
CREATE INDEX idx_user_stats_user 
ON user_statistics(user_id, last_calculated_at);

-- Statistiques quotidiennes par événement et date
CREATE INDEX idx_daily_stats_event_date 
ON daily_sales_stats(event_id, stat_date DESC);

-- ============================================================================
-- 8. OPTIMISATION DES VUES D'ÉVÉNEMENTS
-- ============================================================================

-- Vues par événement (analytics)
CREATE INDEX idx_event_views_event_date 
ON event_views(event_id, viewed_at DESC);

-- Vues par utilisateur (historique)
CREATE INDEX idx_event_views_user_date 
ON event_views(user_id, viewed_at DESC) 
WHERE user_id IS NOT NULL;

-- Vues uniques par IP (anti-bot)
CREATE INDEX idx_event_views_ip_event 
ON event_views(ip_address, event_id, viewed_at);

-- ============================================================================
-- 9. OPTIMISATION DES FAVORIS & WISHLIST
-- ============================================================================

-- Favoris d'un utilisateur
CREATE INDEX idx_favorites_user_date 
ON favorites(user_id, created_at DESC);

-- Événements les plus favoris
CREATE INDEX idx_favorites_event_count 
ON favorites(event_id, created_at);

-- ============================================================================
-- 10. OPTIMISATION DE L'HISTORIQUE DE RECHERCHE
-- ============================================================================

-- Recherches récentes d'un utilisateur
CREATE INDEX idx_search_history_user_date 
ON search_history(user_id, searched_at DESC);

-- Recherches populaires (analytics)
CREATE INDEX idx_search_history_query_date 
ON search_history(search_query, searched_at);

-- ============================================================================
-- 11. OPTIMISATION DU PORTEFEUILLE
-- ============================================================================

-- Portefeuille par utilisateur (accès très fréquent)
CREATE INDEX idx_wallet_user 
ON wallet(user_id);

-- Transactions du portefeuille par date
CREATE INDEX idx_wallet_trans_wallet_date 
ON wallet_transactions(wallet_id, created_at DESC);

-- Transactions par type (analytics)
CREATE INDEX idx_wallet_trans_type_date 
ON wallet_transactions(transaction_type, reference_type, created_at);

-- ============================================================================
-- 12. OPTIMISATION DES CODES PROMO
-- ============================================================================

-- Recherche de code promo actif (validation lors du checkout)
CREATE INDEX idx_promo_codes_active 
ON promo_codes(code, is_active, valid_from, valid_until);

-- Codes promo par créateur
CREATE INDEX idx_promo_codes_creator 
ON promo_codes(created_by, is_active);

-- Utilisation des codes promo
CREATE INDEX idx_promo_usage_code_user 
ON promo_code_usage(promo_code_id, user_id);

-- ============================================================================
-- 13. OPTIMISATION DES AVIS
-- ============================================================================

-- Avis par événement (affichage page événement)
CREATE INDEX idx_reviews_event_published 
ON reviews(event_id, is_published, created_at DESC);

-- Avis par utilisateur
CREATE INDEX idx_reviews_user_date 
ON reviews(user_id, created_at DESC);

-- Avis par note (filtrage)
CREATE INDEX idx_reviews_rating_event 
ON reviews(event_id, rating, is_published);

-- ============================================================================
-- 14. OPTIMISATION DU PARRAINAGE
-- ============================================================================

-- Parrainages par parrain
CREATE INDEX idx_referrals_referrer_status 
ON referrals(referrer_id, status, referred_at DESC);

-- Recherche par code de parrainage
CREATE INDEX idx_referrals_code 
ON referrals(referral_code, status);

-- Parrainages par filleul
CREATE INDEX idx_referrals_referred 
ON referrals(referred_id, status);

-- ============================================================================
-- 15. OPTIMISATION DE LA LISTE D'ATTENTE
-- ============================================================================

-- Liste d'attente par événement et statut
CREATE INDEX idx_waiting_list_event_status 
ON waiting_list(event_id, status, priority_score DESC, joined_at);

-- Liste d'attente par utilisateur
CREATE INDEX idx_waiting_list_user 
ON waiting_list(user_id, status);

-- Liste d'attente à notifier (expiration)
CREATE INDEX idx_waiting_list_expired 
ON waiting_list(status, expires_at) 
WHERE status = 'notified';

-- ============================================================================
-- 16. OPTIMISATION DES RAPPORTS
-- ============================================================================

-- Rapports par événement
CREATE INDEX idx_reports_event_type 
ON reports(event_id, report_type, generated_at DESC);

-- Rapports par générateur
CREATE INDEX idx_reports_generator 
ON reports(generated_by, report_type, generated_at DESC);

-- ============================================================================
-- 17. OPTIMISATION DES LOGS D'AUDIT
-- ============================================================================

-- Logs par utilisateur
CREATE INDEX idx_audit_logs_user_date 
ON audit_logs(user_id, created_at DESC);

-- Logs par entité (traçabilité)
CREATE INDEX idx_audit_logs_entity 
ON audit_logs(entity_type, entity_id, created_at DESC);

-- Logs par action
CREATE INDEX idx_audit_logs_action_date 
ON audit_logs(action, created_at DESC);

-- ============================================================================
-- 18. OPTIMISATION DU MINI-JEU
-- ============================================================================

-- Participations par utilisateur
CREATE INDEX idx_game_participations_user_date 
ON game_participations(user_id, played_at DESC);

-- Lots non réclamés (notifications)
CREATE INDEX idx_game_participations_unclaimed 
ON game_participations(user_id, is_claimed, expires_at) 
WHERE is_claimed = FALSE;

-- ============================================================================
-- 19. OPTIMISATION DES ÉQUIPES D'ORGANISATEURS
-- ============================================================================

-- Équipe d'un événement
CREATE INDEX idx_event_team_event_status 
ON event_team(event_id, status);

-- Événements d'un co-organisateur
CREATE INDEX idx_event_team_user_status 
ON event_team(user_id, status);

-- ============================================================================
-- 20. OPTIMISATION DES TRADUCTIONS
-- ============================================================================

-- Traductions par entité et langue
CREATE INDEX idx_translations_entity_lang 
ON translations(entity_type, entity_id, language);

-- ============================================================================
-- ANALYSE DES PERFORMANCES
-- ============================================================================

-- Activer le slow query log pour identifier les requêtes lentes
-- SET GLOBAL slow_query_log = 'ON';
-- SET GLOBAL long_query_time = 1; -- Requêtes > 1 seconde

-- Analyser l'utilisation des index
-- SHOW INDEX FROM events;
-- EXPLAIN SELECT * FROM events WHERE status = 'published';

-- ============================================================================
-- MAINTENANCE DES INDEX
-- ============================================================================

-- Reconstruire les index fragmentés (à exécuter périodiquement)
-- OPTIMIZE TABLE events;
-- OPTIMIZE TABLE tickets;
-- OPTIMIZE TABLE orders;
-- OPTIMIZE TABLE notifications;

-- Analyser les tables pour mettre à jour les statistiques
-- ANALYZE TABLE events;
-- ANALYZE TABLE tickets;
-- ANALYZE TABLE orders;

-- ============================================================================
-- INDEX COMPOSITES AVANCÉS (À AJOUTER SI NÉCESSAIRE)
-- ============================================================================

-- Recherche avancée d'événements (tous les filtres)
-- CREATE INDEX idx_events_advanced_search 
-- ON events(status, category_id, location, start_date, end_date);

-- Analytics avancées billets
-- CREATE INDEX idx_tickets_analytics 
-- ON tickets(ticket_category_id, status, check_in_at);

-- ============================================================================
-- PARTITIONNEMENT (POUR TRÈS GROSSES TABLES)
-- ============================================================================

-- Exemple : Partitionner audit_logs par mois
/*
ALTER TABLE audit_logs
PARTITION BY RANGE (YEAR(created_at) * 100 + MONTH(created_at)) (
    PARTITION p202501 VALUES LESS THAN (202502),
    PARTITION p202502 VALUES LESS THAN (202503),
    PARTITION p202503 VALUES LESS THAN (202504),
    -- ... ajouter dynamiquement chaque mois
    PARTITION pmax VALUES LESS THAN MAXVALUE
);
*/

-- Exemple : Partitionner event_views par date
/*
ALTER TABLE event_views
PARTITION BY RANGE (TO_DAYS(viewed_at)) (
    PARTITION p2025_01 VALUES LESS THAN (TO_DAYS('2025-02-01')),
    PARTITION p2025_02 VALUES LESS THAN (TO_DAYS('2025-03-01')),
    -- ... etc
);
*/

-- ============================================================================
-- RECOMMANDATIONS FINALES
-- ============================================================================

/*
1. MONITORING
   - Surveiller la taille des index (ils peuvent dépasser la taille des tables)
   - Identifier les index inutilisés : 
     SELECT * FROM sys.schema_unused_indexes;

2. CACHE
   - Activer le query cache MySQL
   - Utiliser Redis pour les statistiques fréquemment accédées

3. ARCHITECTURE
   - Read replicas pour les requêtes de lecture (analytics, stats)
   - Master pour les écritures (commandes, paiements)

4. OPTIMISATION APPLICATIVE
   - Pagination systématique (LIMIT/OFFSET)
   - Eager loading pour éviter N+1 queries
   - Batch processing pour notifications et emails

5. TESTS DE CHARGE
   - Tester avec Apache Bench ou JMeter
   - Simuler 1000+ utilisateurs simultanés
   - Identifier les goulots d'étranglement

6. BACKUP
   - Backup quotidien complet
   - Backup incrémental toutes les heures
   - Point-in-time recovery activé
*/

-- ============================================================================
-- FIN DES OPTIMISATIONS
-- ============================================================================

