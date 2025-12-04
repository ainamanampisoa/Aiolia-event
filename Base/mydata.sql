BEGIN;

SET search_path TO aiolia, public;

-- ============================================================
-- Utilisateurs & Profils
-- ============================================================
INSERT INTO users (id, email, login_identifier, login_method, password_hash, first_name, last_name, phone, country_code, language_code, timezone, avatar_url, role, status, auth_provider, oauth_provider_id, is_email_verified, is_phone_verified, two_factor_type, accepted_terms_at, created_at, updated_at, last_login_at)
VALUES
    (1, 'alice.dupont@example.com', 'alice.dupont', 'password', 'hash-1', 'Alice', 'Dupont', '+261320000001', 'MG', 'fr-FR', 'Indian/Antananarivo', NULL, 'admin', 'active', 'password', NULL, TRUE, TRUE, 'totp', '2025-01-01 08:00:00+00', '2025-01-01 08:00:00+00', '2025-01-01 08:00:00+00', '2025-02-01 08:00:00+00'),
    (2, 'benoit.rakoto@example.com', 'benoit.rakoto', 'password', 'hash-2', 'Benoit', 'Rakoto', '+261320000002', 'MG', 'fr-FR', 'Indian/Antananarivo', NULL, 'organizer', 'active', 'password', NULL, TRUE, FALSE, NULL, '2025-01-02 08:00:00+00', '2025-01-02 08:00:00+00', '2025-01-02 08:00:00+00', '2025-02-02 08:00:00+00'),
    (3, 'celine.ranaivo@example.com', 'celine.ranaivo', 'password', 'hash-3', 'Celine', 'Ranaivo', '+261320000003', 'MG', 'fr-FR', 'Indian/Antananarivo', NULL, 'user', 'active', 'password', NULL, TRUE, TRUE, NULL, '2025-01-03 08:00:00+00', '2025-01-03 08:00:00+00', '2025-01-03 08:00:00+00', '2025-02-03 08:00:00+00'),
    (4, 'dina.andriam@example.com', 'dina.andriam', 'password', 'hash-4', 'Dina', 'Andriam', '+261320000004', 'MG', 'fr-FR', 'Indian/Antananarivo', NULL, 'user', 'active', 'password', NULL, TRUE, FALSE, NULL, '2025-01-04 08:00:00+00', '2025-01-04 08:00:00+00', '2025-01-04 08:00:00+00', '2025-02-04 08:00:00+00'),
    (5, 'eric.solofom@example.com', 'eric.solofom', 'password', 'hash-5', 'Eric', 'Solofom', '+261320000005', 'MG', 'fr-FR', 'Indian/Antananarivo', NULL, 'organizer', 'active', 'password', NULL, TRUE, TRUE, NULL, '2025-01-05 08:00:00+00', '2025-01-05 08:00:00+00', '2025-01-05 08:00:00+00', '2025-02-05 08:00:00+00'),
    (6, 'fanja.raso@example.com', 'fanja.raso', 'password', 'hash-6', 'Fanja', 'Raso', '+261320000006', 'MG', 'fr-FR', 'Indian/Antananarivo', NULL, 'user', 'active', 'password', NULL, TRUE, FALSE, NULL, '2025-01-06 08:00:00+00', '2025-01-06 08:00:00+00', '2025-01-06 08:00:00+00', '2025-02-06 08:00:00+00'),
    (7, 'gael.rak@example.com', 'gael.rak', 'password', 'hash-7', 'Gael', 'Rak', '+261320000007', 'MG', 'fr-FR', 'Indian/Antananarivo', NULL, 'user', 'active', 'password', NULL, TRUE, TRUE, NULL, '2025-01-07 08:00:00+00', '2025-01-07 08:00:00+00', '2025-01-07 08:00:00+00', '2025-02-07 08:00:00+00'),
    (8, 'helena.randria@example.com', 'helena.randria', 'password', 'hash-8', 'Helena', 'Randria', '+261320000008', 'MG', 'fr-FR', 'Indian/Antananarivo', NULL, 'user', 'active', 'password', NULL, TRUE, TRUE, NULL, '2025-01-08 08:00:00+00', '2025-01-08 08:00:00+00', '2025-01-08 08:00:00+00', '2025-02-08 08:00:00+00'),
    (9, 'isa.rava@example.com', 'isa.rava', 'password', 'hash-9', 'Isa', 'Rava', '+261320000009', 'MG', 'fr-FR', 'Indian/Antananarivo', NULL, 'organizer', 'active', 'password', NULL, TRUE, TRUE, NULL, '2025-01-09 08:00:00+00', '2025-01-09 08:00:00+00', '2025-01-09 08:00:00+00', '2025-02-09 08:00:00+00'),
    (10, 'joel.tovo@example.com', 'joel.tovo', 'password', 'hash-10', 'Joel', 'Tovo', '+261320000010', 'MG', 'fr-FR', 'Indian/Antananarivo', NULL, 'user', 'active', 'password', NULL, TRUE, TRUE, NULL, '2025-01-10 08:00:00+00', '2025-01-10 08:00:00+00', '2025-01-10 08:00:00+00', '2025-02-10 08:00:00+00');

INSERT INTO user_profiles (user_id, phone, country_code, language_code, timezone, avatar_url, dark_mode_enabled, marketing_opt_in, preferred_categories, updated_at)
VALUES
    (1, '+261320000001', 'MG', 'fr-FR', 'Indian/Antananarivo', NULL, TRUE, TRUE, ARRAY['concert', 'festival'], '2025-02-01 09:00:00+00'),
    (2, '+261320000002', 'MG', 'fr-FR', 'Indian/Antananarivo', NULL, FALSE, TRUE, ARRAY['conference'], '2025-02-02 09:00:00+00'),
    (3, '+261320000003', 'MG', 'fr-FR', 'Indian/Antananarivo', NULL, TRUE, FALSE, ARRAY['atelier'], '2025-02-03 09:00:00+00'),
    (4, '+261320000004', 'MG', 'fr-FR', 'Indian/Antananarivo', NULL, FALSE, TRUE, ARRAY['sport'], '2025-02-04 09:00:00+00'),
    (5, '+261320000005', 'MG', 'fr-FR', 'Indian/Antananarivo', NULL, TRUE, TRUE, ARRAY['festival'], '2025-02-05 09:00:00+00'),
    (6, '+261320000006', 'MG', 'fr-FR', 'Indian/Antananarivo', NULL, FALSE, FALSE, ARRAY['conference'], '2025-02-06 09:00:00+00'),
    (7, '+261320000007', 'MG', 'fr-FR', 'Indian/Antananarivo', NULL, TRUE, TRUE, ARRAY['concert'], '2025-02-07 09:00:00+00'),
    (8, '+261320000008', 'MG', 'fr-FR', 'Indian/Antananarivo', NULL, TRUE, TRUE, ARRAY['exposition'], '2025-02-08 09:00:00+00'),
    (9, '+261320000009', 'MG', 'fr-FR', 'Indian/Antananarivo', NULL, FALSE, TRUE, ARRAY['festival'], '2025-02-09 09:00:00+00'),
    (10, '+261320000010', 'MG', 'fr-FR', 'Indian/Antananarivo', NULL, TRUE, FALSE, ARRAY['sport'], '2025-02-10 09:00:00+00');

INSERT INTO user_preferences (user_id, preference_key, preference_value, updated_at)
VALUES
    (1, 'notifications.email', '{"enabled": true}', '2025-02-01 09:30:00+00'),
    (2, 'notifications.web_push', '{"enabled": true}', '2025-02-02 09:30:00+00'),
    (3, 'ui.dark_mode', '{"enabled": true}', '2025-02-03 09:30:00+00'),
    (4, 'ui.dark_mode', '{"enabled": false}', '2025-02-04 09:30:00+00'),
    (5, 'notifications.email', '{"enabled": true}', '2025-02-05 09:30:00+00'),
    (6, 'notifications.sms', '{"enabled": true}', '2025-02-06 09:30:00+00'),
    (7, 'ui.language', '{"value": "fr-FR"}', '2025-02-07 09:30:00+00'),
    (8, 'ui.language', '{"value": "fr-FR"}', '2025-02-08 09:30:00+00'),
    (9, 'notifications.email', '{"enabled": false}', '2025-02-09 09:30:00+00'),
    (10, 'notifications.web_push', '{"enabled": false}', '2025-02-10 09:30:00+00');

INSERT INTO user_search_history (id, user_id, keywords, filters, searched_at)
VALUES
    (1, 1, 'concert jazz', '{"city":"Antananarivo"}', '2025-02-01 10:00:00+00'),
    (2, 2, 'conférence tech', '{"type":"conference"}', '2025-02-02 10:00:00+00'),
    (3, 3, 'atelier photo', '{"price":{"max":50000}}', '2025-02-03 10:00:00+00'),
    (4, 4, 'course trail', '{"date":{"from":"2025-03-01"}}', '2025-02-04 10:00:00+00'),
    (5, 5, 'festival musique', '{"category":"festival"}', '2025-02-05 10:00:00+00'),
    (6, 6, 'meetup business', '{"type":"networking"}', '2025-02-06 10:00:00+00'),
    (7, 7, 'concert rock', '{"city":"Toamasina"}', '2025-02-07 10:00:00+00'),
    (8, 8, 'exposition art', '{"category":"exposition"}', '2025-02-08 10:00:00+00'),
    (9, 9, 'festival food', '{"category":"gastronomie"}', '2025-02-09 10:00:00+00'),
    (10, 10, 'marathon', '{"distance":"42k"}', '2025-02-10 10:00:00+00');

INSERT INTO user_event_stats (user_id, events_attended, upcoming_events, total_spend, favorite_categories, last_event_at, updated_at)
VALUES
    (1, 5, 2, 250000, ARRAY['concert', 'festival'], '2024-12-15 19:00:00+00', '2025-02-01 11:00:00+00'),
    (2, 3, 1, 180000, ARRAY['conference'], '2024-12-10 15:00:00+00', '2025-02-02 11:00:00+00'),
    (3, 4, 2, 200000, ARRAY['atelier'], '2024-12-08 09:00:00+00', '2025-02-03 11:00:00+00'),
    (4, 2, 1, 90000, ARRAY['sport'], '2024-11-30 07:00:00+00', '2025-02-04 11:00:00+00'),
    (5, 6, 2, 320000, ARRAY['festival'], '2024-12-20 18:00:00+00', '2025-02-05 11:00:00+00'),
    (6, 3, 1, 150000, ARRAY['conference'], '2024-12-12 14:00:00+00', '2025-02-06 11:00:00+00'),
    (7, 5, 2, 210000, ARRAY['concert'], '2024-12-18 21:00:00+00', '2025-02-07 11:00:00+00'),
    (8, 4, 2, 160000, ARRAY['exposition'], '2024-12-05 16:00:00+00', '2025-02-08 11:00:00+00'),
    (9, 3, 1, 175000, ARRAY['festival'], '2024-12-14 20:00:00+00', '2025-02-09 11:00:00+00'),
    (10, 2, 1, 110000, ARRAY['sport'], '2024-12-01 06:00:00+00', '2025-02-10 11:00:00+00');

INSERT INTO wallets (id, user_id, currency, balance, points_balance, updated_at, created_at)
VALUES
    (1, 1, 'MGA', 150000, 150, '2025-02-01 12:00:00+00', '2025-01-01 08:10:00+00'),
    (2, 2, 'MGA', 120000, 120, '2025-02-02 12:00:00+00', '2025-01-02 08:10:00+00'),
    (3, 3, 'MGA', 90000, 90, '2025-02-03 12:00:00+00', '2025-01-03 08:10:00+00'),
    (4, 4, 'MGA', 60000, 60, '2025-02-04 12:00:00+00', '2025-01-04 08:10:00+00'),
    (5, 5, 'MGA', 200000, 200, '2025-02-05 12:00:00+00', '2025-01-05 08:10:00+00'),
    (6, 6, 'MGA', 80000, 80, '2025-02-06 12:00:00+00', '2025-01-06 08:10:00+00'),
    (7, 7, 'MGA', 110000, 110, '2025-02-07 12:00:00+00', '2025-01-07 08:10:00+00'),
    (8, 8, 'MGA', 95000, 95, '2025-02-08 12:00:00+00', '2025-01-08 08:10:00+00'),
    (9, 9, 'MGA', 175000, 175, '2025-02-09 12:00:00+00', '2025-01-09 08:10:00+00'),
    (10, 10, 'MGA', 70000, 70, '2025-02-10 12:00:00+00', '2025-01-10 08:10:00+00');

INSERT INTO wallet_transactions (id, wallet_id, transaction_type, status, amount, points_delta, description, related_entity, related_id, created_at)
VALUES
    (1, 1, 'credit', 'completed', 50000, 50, 'Recharge mobile money', 'order', 1, '2025-02-01 12:30:00+00'),
    (2, 2, 'debit', 'completed', 30000, -30, 'Achat billet', 'order', 2, '2025-02-02 12:30:00+00'),
    (3, 3, 'credit', 'completed', 40000, 40, 'Bonus fidélité', 'promotion', 1, '2025-02-03 12:30:00+00'),
    (4, 4, 'debit', 'completed', 20000, -20, 'Achat billet', 'order', 4, '2025-02-04 12:30:00+00'),
    (5, 5, 'credit', 'completed', 60000, 60, 'Recharge mobile money', 'order', 5, '2025-02-05 12:30:00+00'),
    (6, 6, 'points_credit', 'completed', 0, 30, 'Points événement', 'event', 6, '2025-02-06 12:30:00+00'),
    (7, 7, 'points_debit', 'completed', 0, -25, 'Utilisation points', 'order', 7, '2025-02-07 12:30:00+00'),
    (8, 8, 'debit', 'completed', 25000, -25, 'Achat billet', 'order', 8, '2025-02-08 12:30:00+00'),
    (9, 9, 'credit', 'completed', 70000, 70, 'Recharge mobile money', 'order', 9, '2025-02-09 12:30:00+00'),
    (10, 10, 'debit', 'completed', 15000, -15, 'Achat billet', 'order', 10, '2025-02-10 12:30:00+00');

-- ============================================================
-- Organisations & Abonnements
-- ============================================================
INSERT INTO organizer_profiles (id, user_id, display_name, legal_name, tax_number, support_email, support_phone, website_url, biography, organization_type, company_registration_number, company_size, verification_status, onboarding_completed_at, created_at, updated_at)
VALUES
    (1, 2, 'Rakoto Events', 'Rakoto Events SARL', 'TN-001', 'support@rakoto-events.mg', '+261326000001', 'https://rakoto-events.mg', 'Organisateur d''événements tech.', 'company', 'RC-001', '11-50', 'verified', '2025-01-15 10:00:00+00', '2025-01-02 10:00:00+00', '2025-02-02 10:00:00+00'),
    (2, 5, 'Solofom Prod', 'Solofom Productions', 'TN-002', 'contact@solofom.mg', '+261326000002', 'https://solofom.mg', 'Production de concerts.', 'company', 'RC-002', '51-200', 'verified', '2025-01-16 10:00:00+00', '2025-01-05 10:00:00+00', '2025-02-05 10:00:00+00'),
    (3, 9, 'Rava Art', 'Rava Art Collective', 'TN-003', 'hello@rava-art.mg', '+261326000003', 'https://rava-art.mg', 'Collectif artistique.', 'collective', 'RC-003', '6-10', 'verified', '2025-01-17 10:00:00+00', '2025-01-09 10:00:00+00', '2025-02-09 10:00:00+00'),
    (4, 1, 'Dupont Conferences', 'Dupont Consulting', 'TN-004', 'events@dupont.mg', '+261326000004', 'https://dupont.mg', 'Conférences professionnelles.', 'company', 'RC-004', '11-50', 'verified', '2025-01-18 10:00:00+00', '2025-01-01 10:00:00+00', '2025-02-01 10:00:00+00'),
    (5, 3, 'Celine Workshops', 'Celine Atelier', 'TN-005', 'atelier@celine.mg', '+261326000005', 'https://celine.mg', 'Ateliers créatifs.', 'individual', NULL, '1-5', 'verified', '2025-01-19 10:00:00+00', '2025-01-03 10:00:00+00', '2025-02-03 10:00:00+00'),
    (6, 4, 'Dina Sports', 'Dina Sports Assoc.', 'TN-006', 'contact@dinasports.mg', '+261326000006', 'https://dinasports.mg', 'Événements sportifs.', 'non_profit', 'RC-006', '11-50', 'verified', '2025-01-20 10:00:00+00', '2025-01-04 10:00:00+00', '2025-02-04 10:00:00+00'),
    (7, 6, 'Fanja Business', 'Fanja Biz Meetups', 'TN-007', 'hello@fanjabiz.mg', '+261326000007', 'https://fanjabiz.mg', 'Meetups entrepreneurs.', 'company', 'RC-007', '6-10', 'verified', '2025-01-21 10:00:00+00', '2025-01-06 10:00:00+00', '2025-02-06 10:00:00+00'),
    (8, 7, 'Gael Live', 'Gael Live Studios', 'TN-008', 'support@gaellive.mg', '+261326000008', 'https://gaellive.mg', 'Organisation de concerts.', 'company', 'RC-008', '11-50', 'verified', '2025-01-22 10:00:00+00', '2025-01-07 10:00:00+00', '2025-02-07 10:00:00+00'),
    (9, 8, 'Helena Expo', 'Helena Expo SARL', 'TN-009', 'info@helenaexpo.mg', '+261326000009', 'https://helenaexpo.mg', 'Expositions culturelles.', 'company', 'RC-009', '11-50', 'verified', '2025-01-23 10:00:00+00', '2025-01-08 10:00:00+00', '2025-02-08 10:00:00+00'),
    (10, 10, 'Joel Trail', 'Joel Trail Assoc.', 'TN-010', 'trail@joel.mg', '+261326000010', 'https://joeltrail.mg', 'Courses en nature.', 'non_profit', 'RC-010', '6-10', 'verified', '2025-01-24 10:00:00+00', '2025-01-10 10:00:00+00', '2025-02-10 10:00:00+00');

INSERT INTO subscription_plans (id, code, name, description, billing_period, period_count, currency, price, vat_rate, features, is_active, created_at, updated_at)
VALUES
    (1, 'BASIC', 'Plan Basic', 'Plan de base pour organisateur.', 'monthly', 1, 'MGA', 50000, 20, '{"tickets":100}', TRUE, '2025-01-01 11:00:00+00', '2025-02-01 11:00:00+00'),
    (2, 'PRO', 'Plan Pro', 'Plan professionnel.', 'monthly', 1, 'MGA', 100000, 20, '{"tickets":500}', TRUE, '2025-01-02 11:00:00+00', '2025-02-02 11:00:00+00'),
    (3, 'PREMIUM', 'Plan Premium', 'Plan premium.', 'monthly', 1, 'MGA', 200000, 20, '{"tickets":1000}', TRUE, '2025-01-03 11:00:00+00', '2025-02-03 11:00:00+00'),
    (4, 'STARTER', 'Plan Starter', 'Plan pour débutants.', 'monthly', 1, 'MGA', 40000, 20, '{"tickets":50}', TRUE, '2025-01-04 11:00:00+00', '2025-02-04 11:00:00+00'),
    (5, 'BUSINESS', 'Plan Business', 'Plan business.', 'monthly', 1, 'MGA', 150000, 20, '{"tickets":700}', TRUE, '2025-01-05 11:00:00+00', '2025-02-05 11:00:00+00'),
    (6, 'CORP', 'Plan Corporate', 'Plan entreprise.', 'monthly', 1, 'MGA', 250000, 20, '{"tickets":1500}', TRUE, '2025-01-06 11:00:00+00', '2025-02-06 11:00:00+00'),
    (7, 'EVENTPLUS', 'Plan Event Plus', 'Plan plus.', 'monthly', 1, 'MGA', 180000, 20, '{"tickets":800}', TRUE, '2025-01-07 11:00:00+00', '2025-02-07 11:00:00+00'),
    (8, 'ULTIMATE', 'Plan Ultimate', 'Plan ultime.', 'monthly', 1, 'MGA', 300000, 20, '{"tickets":2000}', TRUE, '2025-01-08 11:00:00+00', '2025-02-08 11:00:00+00'),
    (9, 'ARTIST', 'Plan Artist', 'Plan artistique.', 'monthly', 1, 'MGA', 90000, 20, '{"tickets":400}', TRUE, '2025-01-09 11:00:00+00', '2025-02-09 11:00:00+00'),
    (10, 'SPORT', 'Plan Sport', 'Plan sport.', 'monthly', 1, 'MGA', 120000, 20, '{"tickets":600}', TRUE, '2025-01-10 11:00:00+00', '2025-02-10 11:00:00+00');

INSERT INTO organizer_subscriptions (id, organizer_id, plan_id, status, starts_at, current_period_start, current_period_end, renewal_at, cancel_at_period_end, cancelled_at, metadata, created_at, updated_at)
VALUES
    (1, 1, 1, 'active', '2025-01-15 00:00:00+00', '2025-01-15 00:00:00+00', '2025-02-15 00:00:00+00', '2025-02-14 00:00:00+00', FALSE, NULL, '{"auto_renew":true}', '2025-01-15 11:00:00+00', '2025-02-01 11:00:00+00'),
    (2, 2, 2, 'active', '2025-01-16 00:00:00+00', '2025-01-16 00:00:00+00', '2025-02-16 00:00:00+00', '2025-02-15 00:00:00+00', FALSE, NULL, '{"auto_renew":true}', '2025-01-16 11:00:00+00', '2025-02-02 11:00:00+00'),
    (3, 3, 3, 'active', '2025-01-17 00:00:00+00', '2025-01-17 00:00:00+00', '2025-02-17 00:00:00+00', '2025-02-16 00:00:00+00', FALSE, NULL, '{"auto_renew":true}', '2025-01-17 11:00:00+00', '2025-02-03 11:00:00+00'),
    (4, 4, 4, 'active', '2025-01-18 00:00:00+00', '2025-01-18 00:00:00+00', '2025-02-18 00:00:00+00', '2025-02-17 00:00:00+00', FALSE, NULL, '{"auto_renew":true}', '2025-01-18 11:00:00+00', '2025-02-04 11:00:00+00'),
    (5, 5, 5, 'active', '2025-01-19 00:00:00+00', '2025-01-19 00:00:00+00', '2025-02-19 00:00:00+00', '2025-02-18 00:00:00+00', FALSE, NULL, '{"auto_renew":true}', '2025-01-19 11:00:00+00', '2025-02-05 11:00:00+00'),
    (6, 6, 6, 'active', '2025-01-20 00:00:00+00', '2025-01-20 00:00:00+00', '2025-02-20 00:00:00+00', '2025-02-19 00:00:00+00', FALSE, NULL, '{"auto_renew":true}', '2025-01-20 11:00:00+00', '2025-02-06 11:00:00+00'),
    (7, 7, 7, 'active', '2025-01-21 00:00:00+00', '2025-01-21 00:00:00+00', '2025-02-21 00:00:00+00', '2025-02-20 00:00:00+00', FALSE, NULL, '{"auto_renew":true}', '2025-01-21 11:00:00+00', '2025-02-07 11:00:00+00'),
    (8, 8, 8, 'active', '2025-01-22 00:00:00+00', '2025-01-22 00:00:00+00', '2025-02-22 00:00:00+00', '2025-02-21 00:00:00+00', FALSE, NULL, '{"auto_renew":true}', '2025-01-22 11:00:00+00', '2025-02-08 11:00:00+00'),
    (9, 9, 9, 'active', '2025-01-23 00:00:00+00', '2025-01-23 00:00:00+00', '2025-02-23 00:00:00+00', '2025-02-22 00:00:00+00', FALSE, NULL, '{"auto_renew":true}', '2025-01-23 11:00:00+00', '2025-02-09 11:00:00+00'),
    (10, 10, 10, 'active', '2025-01-24 00:00:00+00', '2025-01-24 00:00:00+00', '2025-02-24 00:00:00+00', '2025-02-23 00:00:00+00', FALSE, NULL, '{"auto_renew":true}', '2025-01-24 11:00:00+00', '2025-02-10 11:00:00+00');

-- ============================================================
-- Événements & Médias
-- ============================================================
INSERT INTO event_categories (id, slug, label, description, display_order, created_at)
VALUES
    (1, 'concert', 'Concert', 'Concerts live.', 1, '2025-01-01 12:00:00+00'),
    (2, 'conference', 'Conférence', 'Conférences professionnelles.', 2, '2025-01-02 12:00:00+00'),
    (3, 'atelier', 'Atelier', 'Ateliers pratiques.', 3, '2025-01-03 12:00:00+00'),
    (4, 'sport', 'Sport', 'Événements sportifs.', 4, '2025-01-04 12:00:00+00'),
    (5, 'festival', 'Festival', 'Festivals variés.', 5, '2025-01-05 12:00:00+00'),
    (6, 'exposition', 'Exposition', 'Expositions culturelles.', 6, '2025-01-06 12:00:00+00'),
    (7, 'gastronomie', 'Gastronomie', 'Événements culinaires.', 7, '2025-01-07 12:00:00+00'),
    (8, 'business', 'Business', 'Rencontres business.', 8, '2025-01-08 12:00:00+00'),
    (9, 'bien-etre', 'Bien-être', 'Ateliers bien-être.', 9, '2025-01-09 12:00:00+00'),
    (10, 'gaming', 'Gaming', 'Événements gaming.', 10, '2025-01-10 12:00:00+00');

INSERT INTO events (id, organizer_profile_id, slug, title, subtitle, summary, description, visibility, status, timezone, venue_name, venue_address, city, region, country_code, latitude, longitude, starts_at, ends_at, sales_starts_at, sales_ends_at, capacity, language_code, is_featured, is_highlighted, created_at, updated_at)
VALUES
    (1, 1, 'concert-etoile-2025', 'Concert Étoile 2025', 'Nuit musicale', 'Un grand concert.', 'Description du concert.', 'public', 'published', 'Indian/Antananarivo', 'Palais des Sports', 'Rue Ravoninahitriniarivo', 'Antananarivo', 'Analamanga', 'MG', -18.87919, 47.50791, '2025-03-10 18:00:00+00', '2025-03-10 23:00:00+00', '2025-02-01 08:00:00+00', '2025-03-09 20:00:00+00', 5000, 'fr-FR', TRUE, TRUE, '2025-01-15 12:30:00+00', '2025-02-01 12:30:00+00'),
    (2, 2, 'tech-conf-2025', 'Tech Conf 2025', 'Innover ensemble', 'Conférence tech.', 'Description conférence.', 'public', 'published', 'Indian/Antananarivo', 'Centre de Conférences', 'Avenue de la Tech', 'Antananarivo', 'Analamanga', 'MG', -18.9083, 47.5361, '2025-04-05 09:00:00+00', '2025-04-05 18:00:00+00', '2025-02-10 08:00:00+00', '2025-04-04 20:00:00+00', 800, 'fr-FR', TRUE, FALSE, '2025-01-16 12:30:00+00', '2025-02-02 12:30:00+00'),
    (3, 3, 'atelier-photo-2025', 'Atelier Photo Créative', 'Techniques avancées', 'Atelier photo.', 'Description atelier.', 'public', 'published', 'Indian/Antananarivo', 'Studio Mandrosoa', 'Rue de la Photo', 'Antananarivo', 'Analamanga', 'MG', -18.8795, 47.5160, '2025-03-20 10:00:00+00', '2025-03-20 16:00:00+00', '2025-02-05 08:00:00+00', '2025-03-19 20:00:00+00', 40, 'fr-FR', FALSE, FALSE, '2025-01-17 12:30:00+00', '2025-02-03 12:30:00+00'),
    (4, 4, 'trail-hautes-terres', 'Trail des Hautes Terres', 'Course nature', 'Trail montagneux.', 'Description trail.', 'public', 'published', 'Indian/Antananarivo', 'Départ Ambohimanga', 'Route du Trail', 'Antananarivo', 'Analamanga', 'MG', -18.8167, 47.5500, '2025-05-01 04:00:00+00', '2025-05-01 14:00:00+00', '2025-02-15 08:00:00+00', '2025-04-30 20:00:00+00', 1500, 'fr-FR', FALSE, TRUE, '2025-01-18 12:30:00+00', '2025-02-04 12:30:00+00'),
    (5, 5, 'festival-culturel', 'Festival Culturel', 'Trois jours de fête', 'Festival culturel.', 'Description festival.', 'public', 'published', 'Indian/Antananarivo', 'Parc Culturel', 'Boulevard des Arts', 'Antananarivo', 'Analamanga', 'MG', -18.8765, 47.5200, '2025-06-12 12:00:00+00', '2025-06-14 23:00:00+00', '2025-03-01 08:00:00+00', '2025-06-11 20:00:00+00', 10000, 'fr-FR', TRUE, TRUE, '2025-01-19 12:30:00+00', '2025-02-05 12:30:00+00'),
    (6, 6, 'course-urbaine', 'Course Urbaine', '5 km en ville', 'Course urbaine.', 'Description course.', 'public', 'published', 'Indian/Antananarivo', 'Stade Municipal', 'Rue du Stade', 'Antananarivo', 'Analamanga', 'MG', -18.9000, 47.5500, '2025-04-12 06:00:00+00', '2025-04-12 09:00:00+00', '2025-02-20 08:00:00+00', '2025-04-11 20:00:00+00', 2000, 'fr-FR', FALSE, FALSE, '2025-01-20 12:30:00+00', '2025-02-06 12:30:00+00'),
    (7, 7, 'business-meet', 'Business Meet 2025', 'Rencontres B2B', 'Meetup business.', 'Description meetup.', 'public', 'published', 'Indian/Antananarivo', 'Hôtel Carlton', 'Rue Pierre St', 'Antananarivo', 'Analamanga', 'MG', -18.9100, 47.5300, '2025-03-28 08:00:00+00', '2025-03-28 17:00:00+00', '2025-02-10 08:00:00+00', '2025-03-27 20:00:00+00', 600, 'fr-FR', FALSE, FALSE, '2025-01-21 12:30:00+00', '2025-02-07 12:30:00+00'),
    (8, 8, 'concert-rock', 'Concert Rock Horizon', 'Soirée rock', 'Concert rock.', 'Description rock.', 'public', 'published', 'Indian/Antananarivo', 'Arena Horizon', 'Rue Horizon', 'Antananarivo', 'Analamanga', 'MG', -18.9050, 47.5450, '2025-04-18 19:00:00+00', '2025-04-19 01:00:00+00', '2025-02-15 08:00:00+00', '2025-04-17 20:00:00+00', 4000, 'fr-FR', TRUE, TRUE, '2025-01-22 12:30:00+00', '2025-02-08 12:30:00+00'),
    (9, 9, 'expo-art', 'Expo Art Moderne', 'Créations locales', 'Exposition art.', 'Description expo.', 'public', 'published', 'Indian/Antananarivo', 'Galerie Lapasoa', 'Rue de l''Art', 'Antananarivo', 'Analamanga', 'MG', -18.9055, 47.5205, '2025-05-05 10:00:00+00', '2025-05-10 18:00:00+00', '2025-02-25 08:00:00+00', '2025-05-04 20:00:00+00', 200, 'fr-FR', FALSE, FALSE, '2025-01-23 12:30:00+00', '2025-02-09 12:30:00+00'),
    (10, 10, 'trail-cote-est', 'Trail Côte Est', 'Aventure tropicale', 'Trail côtier.', 'Description trail.', 'public', 'published', 'Indian/Antananarivo', 'Plage Foulpointe', 'Route Côtière', 'Toamasina', 'Atsinanana', 'MG', -18.2630, 49.4080, '2025-07-01 04:00:00+00', '2025-07-01 15:00:00+00', '2025-03-05 08:00:00+00', '2025-06-30 20:00:00+00', 2500, 'fr-FR', TRUE, TRUE, '2025-01-24 12:30:00+00', '2025-02-10 12:30:00+00');

INSERT INTO event_category_links (event_id, category_id)
VALUES
    (1, 1),
    (2, 2),
    (3, 3),
    (4, 4),
    (5, 5),
    (6, 4),
    (7, 8),
    (8, 1),
    (9, 6),
    (10, 4);

INSERT INTO event_tags (id, slug, label, created_at)
VALUES
    (1, 'musique', 'Musique', '2025-01-01 13:00:00+00'),
    (2, 'tech', 'Tech', '2025-01-02 13:00:00+00'),
    (3, 'photo', 'Photo', '2025-01-03 13:00:00+00'),
    (4, 'outdoor', 'Outdoor', '2025-01-04 13:00:00+00'),
    (5, 'culture', 'Culture', '2025-01-05 13:00:00+00'),
    (6, 'sport', 'Sport', '2025-01-06 13:00:00+00'),
    (7, 'business', 'Business', '2025-01-07 13:00:00+00'),
    (8, 'rock', 'Rock', '2025-01-08 13:00:00+00'),
    (9, 'art', 'Art', '2025-01-09 13:00:00+00'),
    (10, 'trail', 'Trail', '2025-01-10 13:00:00+00');

INSERT INTO event_tag_links (event_id, tag_id)
VALUES
    (1, 1),
    (2, 2),
    (3, 3),
    (4, 4),
    (5, 5),
    (6, 6),
    (7, 7),
    (8, 8),
    (9, 9),
    (10, 10);

INSERT INTO event_sessions (id, event_id, title, description, starts_at, ends_at, capacity, location_override, created_at)
VALUES
    (1, 1, 'Ouverture', 'Session d''ouverture.', '2025-03-10 18:00:00+00', '2025-03-10 19:00:00+00', 5000, NULL, '2025-02-01 13:00:00+00'),
    (2, 2, 'Keynote', 'Keynote principale.', '2025-04-05 09:30:00+00', '2025-04-05 10:30:00+00', 800, NULL, '2025-02-02 13:00:00+00'),
    (3, 3, 'Prise de vue', 'Atelier pratique.', '2025-03-20 10:30:00+00', '2025-03-20 12:00:00+00', 40, NULL, '2025-02-03 13:00:00+00'),
    (4, 4, 'Briefing', 'Briefing course.', '2025-05-01 04:00:00+00', '2025-05-01 05:00:00+00', 1500, NULL, '2025-02-04 13:00:00+00'),
    (5, 5, 'Concert jour 1', 'Concert principal.', '2025-06-12 18:00:00+00', '2025-06-12 23:00:00+00', 10000, NULL, '2025-02-05 13:00:00+00'),
    (6, 6, 'Course 5K', 'Course principale.', '2025-04-12 06:00:00+00', '2025-04-12 07:00:00+00', 2000, NULL, '2025-02-06 13:00:00+00'),
    (7, 7, 'Networking', 'Session networking.', '2025-03-28 12:00:00+00', '2025-03-28 13:30:00+00', 600, 'Salle Horizon', '2025-02-07 13:00:00+00'),
    (8, 8, 'Soundcheck', 'Balance scène.', '2025-04-18 16:00:00+00', '2025-04-18 17:00:00+00', 4000, NULL, '2025-02-08 13:00:00+00'),
    (9, 9, 'Vernissage', 'Ouverture expo.', '2025-05-05 10:00:00+00', '2025-05-05 12:00:00+00', 200, NULL, '2025-02-09 13:00:00+00'),
    (10, 10, 'Briefing trail', 'Briefing sécurité.', '2025-07-01 04:00:00+00', '2025-07-01 05:00:00+00', 2500, NULL, '2025-02-10 13:00:00+00');

INSERT INTO event_media (id, event_id, media_type, url, alt_text, display_order, is_public, created_at)
VALUES
    (1, 1, 'image', 'https://cdn.aiolia.mg/events/1/banner.jpg', 'Affiche concert', 1, TRUE, '2025-02-01 14:00:00+00'),
    (2, 2, 'image', 'https://cdn.aiolia.mg/events/2/banner.jpg', 'Affiche conférence', 1, TRUE, '2025-02-02 14:00:00+00'),
    (3, 3, 'image', 'https://cdn.aiolia.mg/events/3/banner.jpg', 'Atelier photo', 1, TRUE, '2025-02-03 14:00:00+00'),
    (4, 4, 'image', 'https://cdn.aiolia.mg/events/4/banner.jpg', 'Trail', 1, TRUE, '2025-02-04 14:00:00+00'),
    (5, 5, 'image', 'https://cdn.aiolia.mg/events/5/banner.jpg', 'Festival', 1, TRUE, '2025-02-05 14:00:00+00'),
    (6, 6, 'image', 'https://cdn.aiolia.mg/events/6/banner.jpg', 'Course urbaine', 1, TRUE, '2025-02-06 14:00:00+00'),
    (7, 7, 'image', 'https://cdn.aiolia.mg/events/7/banner.jpg', 'Business meet', 1, TRUE, '2025-02-07 14:00:00+00'),
    (8, 8, 'image', 'https://cdn.aiolia.mg/events/8/banner.jpg', 'Concert rock', 1, TRUE, '2025-02-08 14:00:00+00'),
    (9, 9, 'image', 'https://cdn.aiolia.mg/events/9/banner.jpg', 'Expo art', 1, TRUE, '2025-02-09 14:00:00+00'),
    (10, 10, 'image', 'https://cdn.aiolia.mg/events/10/banner.jpg', 'Trail côte est', 1, TRUE, '2025-02-10 14:00:00+00');

-- ============================================================
-- Billetterie & Commandes
-- ============================================================
INSERT INTO ticket_types (id, event_id, session_id, name, description, currency, base_price, service_fee, vat_rate, sales_start, sales_end, min_per_order, max_per_order, metadata, created_at, updated_at)
VALUES
    (1, 1, 1, 'Pass VIP', 'Accès VIP.', 'MGA', 150000, 5000, 20, '2025-02-01 08:00:00+00', '2025-03-09 20:00:00+00', 1, 5, '{"zone":"VIP"}', '2025-02-01 15:00:00+00', '2025-02-01 15:00:00+00'),
    (2, 2, 2, 'Pass Conférence', 'Accès complet.', 'MGA', 80000, 4000, 20, '2025-02-10 08:00:00+00', '2025-04-04 20:00:00+00', 1, 5, '{"badge":"complet"}', '2025-02-02 15:00:00+00', '2025-02-02 15:00:00+00'),
    (3, 3, 3, 'Atelier Standard', 'Accès atelier.', 'MGA', 60000, 3000, 20, '2025-02-05 08:00:00+00', '2025-03-19 20:00:00+00', 1, 2, '{"kit":"inclus"}', '2025-02-03 15:00:00+00', '2025-02-03 15:00:00+00'),
    (4, 4, 4, 'Dossard 42K', 'Course 42K.', 'MGA', 90000, 4500, 20, '2025-02-15 08:00:00+00', '2025-04-30 20:00:00+00', 1, 4, '{"distance":"42k"}', '2025-02-04 15:00:00+00', '2025-02-04 15:00:00+00'),
    (5, 5, 5, 'Pass 3 jours', 'Accès 3 jours.', 'MGA', 120000, 6000, 20, '2025-03-01 08:00:00+00', '2025-06-11 20:00:00+00', 1, 6, '{"jours":3}', '2025-02-05 15:00:00+00', '2025-02-05 15:00:00+00'),
    (6, 6, 6, 'Ticket Course', 'Participation course.', 'MGA', 40000, 2000, 20, '2025-02-20 08:00:00+00', '2025-04-11 20:00:00+00', 1, 5, '{"distance":"5k"}', '2025-02-06 15:00:00+00', '2025-02-06 15:00:00+00'),
    (7, 7, 7, 'Pass Business', 'Accès rencontres.', 'MGA', 70000, 3500, 20, '2025-02-10 08:00:00+00', '2025-03-27 20:00:00+00', 1, 3, '{"networking":true}', '2025-02-07 15:00:00+00', '2025-02-07 15:00:00+00'),
    (8, 8, 8, 'Pass Rock', 'Accès soirée.', 'MGA', 90000, 4500, 20, '2025-02-15 08:00:00+00', '2025-04-17 20:00:00+00', 1, 4, '{"zone":"fosse"}', '2025-02-08 15:00:00+00', '2025-02-08 15:00:00+00'),
    (9, 9, 9, 'Pass Expo', 'Accès expo.', 'MGA', 30000, 1500, 20, '2025-02-25 08:00:00+00', '2025-05-04 20:00:00+00', 1, 5, '{"jours":5}', '2025-02-09 15:00:00+00', '2025-02-09 15:00:00+00'),
    (10, 10, 10, 'Dossard Trail', 'Trail côte est.', 'MGA', 95000, 4750, 20, '2025-03-05 08:00:00+00', '2025-06-30 20:00:00+00', 1, 4, '{"distance":"30k"}', '2025-02-10 15:00:00+00', '2025-02-10 15:00:00+00');

INSERT INTO ticket_inventory (ticket_type_id, total_quantity, reserved_quantity, sold_quantity, updated_at)
VALUES
    (1, 500, 50, 120, '2025-02-15 10:00:00+00'),
    (2, 800, 40, 250, '2025-02-16 10:00:00+00'),
    (3, 40, 5, 30, '2025-02-17 10:00:00+00'),
    (4, 1500, 60, 400, '2025-02-18 10:00:00+00'),
    (5, 1000, 80, 300, '2025-02-19 10:00:00+00'),
    (6, 2000, 70, 500, '2025-02-20 10:00:00+00'),
    (7, 600, 30, 180, '2025-02-21 10:00:00+00'),
    (8, 4000, 150, 900, '2025-02-22 10:00:00+00'),
    (9, 200, 10, 90, '2025-02-23 10:00:00+00'),
    (10, 2500, 100, 700, '2025-02-24 10:00:00+00');

INSERT INTO ticket_price_history (id, ticket_type_id, changed_by, previous_price, new_price, changed_at, reason, metadata)
VALUES
    (1, 1, 1, 140000, 150000, '2025-02-05 08:00:00+00', 'Ajustement VIP', '{"note":"tarif dynamique"}'),
    (2, 2, 2, 75000, 80000, '2025-02-06 08:00:00+00', 'Ajustement', '{"note":"demande élevée"}'),
    (3, 3, 3, 55000, 60000, '2025-02-07 08:00:00+00', 'Ajustement', '{"note":"matériel inclus"}'),
    (4, 4, 4, 85000, 90000, '2025-02-08 08:00:00+00', 'Ajustement', '{"note":"coûts logistiques"}'),
    (5, 5, 5, 110000, 120000, '2025-02-09 08:00:00+00', 'Ajustement', '{"note":"programmation étendue"}'),
    (6, 6, 6, 38000, 40000, '2025-02-10 08:00:00+00', 'Ajustement', '{"note":"logistique"}'),
    (7, 7, 7, 65000, 70000, '2025-02-11 08:00:00+00', 'Ajustement', '{"note":"plus d''intervenants"}'),
    (8, 8, 8, 85000, 90000, '2025-02-12 08:00:00+00', 'Ajustement', '{"note":"production scène"}'),
    (9, 9, 9, 28000, 30000, '2025-02-13 08:00:00+00', 'Ajustement', '{"note":"ajout artistes"}'),
    (10, 10, 10, 90000, 95000, '2025-02-14 08:00:00+00', 'Ajustement', '{"note":"logistique terrain"}');

INSERT INTO carts (id, user_id, status, session_token, currency, total_amount, expires_at, created_at, updated_at)
VALUES
    (1, 1, 'converted', 'session-1', 'MGA', 200000, '2025-02-01 16:00:00+00', '2025-02-01 15:30:00+00', '2025-02-01 15:45:00+00'),
    (2, 2, 'converted', 'session-2', 'MGA', 90000, '2025-02-02 16:00:00+00', '2025-02-02 15:30:00+00', '2025-02-02 15:45:00+00'),
    (3, 3, 'converted', 'session-3', 'MGA', 63000, '2025-02-03 16:00:00+00', '2025-02-03 15:30:00+00', '2025-02-03 15:45:00+00'),
    (4, 4, 'converted', 'session-4', 'MGA', 99000, '2025-02-04 16:00:00+00', '2025-02-04 15:30:00+00', '2025-02-04 15:45:00+00'),
    (5, 5, 'converted', 'session-5', 'MGA', 126000, '2025-02-05 16:00:00+00', '2025-02-05 15:30:00+00', '2025-02-05 15:45:00+00'),
    (6, 6, 'converted', 'session-6', 'MGA', 42000, '2025-02-06 16:00:00+00', '2025-02-06 15:30:00+00', '2025-02-06 15:45:00+00'),
    (7, 7, 'converted', 'session-7', 'MGA', 73500, '2025-02-07 16:00:00+00', '2025-02-07 15:30:00+00', '2025-02-07 15:45:00+00'),
    (8, 8, 'converted', 'session-8', 'MGA', 94500, '2025-02-08 16:00:00+00', '2025-02-08 15:30:00+00', '2025-02-08 15:45:00+00'),
    (9, 9, 'converted', 'session-9', 'MGA', 31500, '2025-02-09 16:00:00+00', '2025-02-09 15:30:00+00', '2025-02-09 15:45:00+00'),
    (10, 10, 'converted', 'session-10', 'MGA', 99750, '2025-02-10 16:00:00+00', '2025-02-10 15:30:00+00', '2025-02-10 15:45:00+00');

INSERT INTO cart_items (id, cart_id, ticket_type_id, quantity, unit_price, total_price, created_at)
VALUES
    (1, 1, 1, 1, 150000, 150000, '2025-02-01 15:35:00+00'),
    (2, 2, 2, 1, 80000, 80000, '2025-02-02 15:35:00+00'),
    (3, 3, 3, 1, 60000, 60000, '2025-02-03 15:35:00+00'),
    (4, 4, 4, 1, 90000, 90000, '2025-02-04 15:35:00+00'),
    (5, 5, 5, 1, 120000, 120000, '2025-02-05 15:35:00+00'),
    (6, 6, 6, 1, 40000, 40000, '2025-02-06 15:35:00+00'),
    (7, 7, 7, 1, 70000, 70000, '2025-02-07 15:35:00+00'),
    (8, 8, 8, 1, 90000, 90000, '2025-02-08 15:35:00+00'),
    (9, 9, 9, 1, 30000, 30000, '2025-02-09 15:35:00+00'),
    (10, 10, 10, 1, 95000, 95000, '2025-02-10 15:35:00+00');

INSERT INTO orders (id, user_id, cart_id, status, total_amount, discount_amount, currency, promotion_code, payment_due_at, notes, created_at, updated_at)
VALUES
    (1, 1, 1, 'paid', 200000, 0, 'MGA', 'PROMO10', '2025-02-02 08:00:00+00', 'Achat VIP', '2025-02-01 16:00:00+00', '2025-02-01 16:05:00+00'),
    (2, 2, 2, 'paid', 90000, 0, 'MGA', NULL, '2025-02-03 08:00:00+00', 'Achat conférence', '2025-02-02 16:00:00+00', '2025-02-02 16:05:00+00'),
    (3, 3, 3, 'paid', 63000, 0, 'MGA', NULL, '2025-02-04 08:00:00+00', 'Achat atelier', '2025-02-03 16:00:00+00', '2025-02-03 16:05:00+00'),
    (4, 4, 4, 'paid', 99000, 0, 'MGA', NULL, '2025-02-05 08:00:00+00', 'Achat trail', '2025-02-04 16:00:00+00', '2025-02-04 16:05:00+00'),
    (5, 5, 5, 'paid', 126000, 6000, 'MGA', 'FEST6', '2025-02-06 08:00:00+00', 'Achat festival', '2025-02-05 16:00:00+00', '2025-02-05 16:05:00+00'),
    (6, 6, 6, 'paid', 42000, 0, 'MGA', NULL, '2025-02-07 08:00:00+00', 'Achat course', '2025-02-06 16:00:00+00', '2025-02-06 16:05:00+00'),
    (7, 7, 7, 'paid', 73500, 0, 'MGA', NULL, '2025-02-08 08:00:00+00', 'Achat business', '2025-02-07 16:00:00+00', '2025-02-07 16:05:00+00'),
    (8, 8, 8, 'paid', 94500, 0, 'MGA', NULL, '2025-02-09 08:00:00+00', 'Achat rock', '2025-02-08 16:00:00+00', '2025-02-08 16:05:00+00'),
    (9, 9, 9, 'paid', 31500, 0, 'MGA', NULL, '2025-02-10 08:00:00+00', 'Achat expo', '2025-02-09 16:00:00+00', '2025-02-09 16:05:00+00'),
    (10, 10, 10, 'paid', 99750, 0, 'MGA', NULL, '2025-02-11 08:00:00+00', 'Achat trail', '2025-02-10 16:00:00+00', '2025-02-10 16:05:00+00');

INSERT INTO order_items (id, order_id, ticket_type_id, quantity, unit_price, service_fee, vat_amount, total_amount, created_at)
VALUES
    (1, 1, 1, 1, 150000, 5000, 45000, 200000, '2025-02-01 16:01:00+00'),
    (2, 2, 2, 1, 80000, 4000, 6000, 90000, '2025-02-02 16:01:00+00'),
    (3, 3, 3, 1, 60000, 3000, 3000, 66000, '2025-02-03 16:01:00+00'),
    (4, 4, 4, 1, 90000, 4500, 4500, 99000, '2025-02-04 16:01:00+00'),
    (5, 5, 5, 1, 120000, 6000, 6000, 132000, '2025-02-05 16:01:00+00'),
    (6, 6, 6, 1, 40000, 2000, 2000, 44000, '2025-02-06 16:01:00+00'),
    (7, 7, 7, 1, 70000, 3500, 3500, 77000, '2025-02-07 16:01:00+00'),
    (8, 8, 8, 1, 90000, 4500, 4500, 99000, '2025-02-08 16:01:00+00'),
    (9, 9, 9, 1, 30000, 1500, 1500, 33000, '2025-02-09 16:01:00+00'),
    (10, 10, 10, 1, 95000, 4750, 4750, 104500, '2025-02-10 16:01:00+00');

INSERT INTO order_status_history (id, order_id, status_from, status_to, changed_by, metadata, changed_at)
VALUES
    (1, 1, 'pending', 'paid', 1, '{"note":"Paiement confirmé"}', '2025-02-01 16:02:00+00'),
    (2, 2, 'pending', 'paid', 2, '{"note":"Paiement confirmé"}', '2025-02-02 16:02:00+00'),
    (3, 3, 'pending', 'paid', 3, '{"note":"Paiement confirmé"}', '2025-02-03 16:02:00+00'),
    (4, 4, 'pending', 'paid', 4, '{"note":"Paiement confirmé"}', '2025-02-04 16:02:00+00'),
    (5, 5, 'pending', 'paid', 5, '{"note":"Paiement confirmé"}', '2025-02-05 16:02:00+00'),
    (6, 6, 'pending', 'paid', 6, '{"note":"Paiement confirmé"}', '2025-02-06 16:02:00+00'),
    (7, 7, 'pending', 'paid', 7, '{"note":"Paiement confirmé"}', '2025-02-07 16:02:00+00'),
    (8, 8, 'pending', 'paid', 8, '{"note":"Paiement confirmé"}', '2025-02-08 16:02:00+00'),
    (9, 9, 'pending', 'paid', 9, '{"note":"Paiement confirmé"}', '2025-02-09 16:02:00+00'),
    (10, 10, 'pending', 'paid', 10, '{"note":"Paiement confirmé"}', '2025-02-10 16:02:00+00');

INSERT INTO tickets (id, order_item_id, ticket_type_id, owner_user_id, status, qr_code, qr_checksum, issued_at, metadata)
VALUES
    (1, 1, 1, 1, 'valid', 'QR-0001', 'CHK-0001', '2025-02-01 16:03:00+00', '{"seat":"A1"}'),
    (2, 2, 2, 2, 'valid', 'QR-0002', 'CHK-0002', '2025-02-02 16:03:00+00', '{"seat":"B2"}'),
    (3, 3, 3, 3, 'valid', 'QR-0003', 'CHK-0003', '2025-02-03 16:03:00+00', '{"kit":"photo"}'),
    (4, 4, 4, 4, 'valid', 'QR-0004', 'CHK-0004', '2025-02-04 16:03:00+00', '{"dossard":"42"}'),
    (5, 5, 5, 5, 'valid', 'QR-0005', 'CHK-0005', '2025-02-05 16:03:00+00', '{"pass":"3j"}'),
    (6, 6, 6, 6, 'valid', 'QR-0006', 'CHK-0006', '2025-02-06 16:03:00+00', '{"dossard":"5k"}'),
    (7, 7, 7, 7, 'valid', 'QR-0007', 'CHK-0007', '2025-02-07 16:03:00+00', '{"zone":"business"}'),
    (8, 8, 8, 8, 'valid', 'QR-0008', 'CHK-0008', '2025-02-08 16:03:00+00', '{"zone":"fosse"}'),
    (9, 9, 9, 9, 'valid', 'QR-0009', 'CHK-0009', '2025-02-09 16:03:00+00', '{"jour":"1"}'),
    (10, 10, 10, 10, 'valid', 'QR-0010', 'CHK-0010', '2025-02-10 16:03:00+00', '{"dossard":"trail"}');

INSERT INTO ticket_transfers (id, ticket_id, from_user_id, to_user_id, to_email, status, token, expires_at, created_at)
VALUES
    (1, 1, 1, 2, 'benoit.rakoto@example.com', 'accepted', 'token-1', '2025-03-10 12:00:00+00', '2025-02-11 08:00:00+00'),
    (2, 2, 2, 3, 'celine.ranaivo@example.com', 'accepted', 'token-2', '2025-04-05 12:00:00+00', '2025-02-12 08:00:00+00'),
    (3, 3, 3, 4, 'dina.andriam@example.com', 'pending', 'token-3', '2025-03-20 12:00:00+00', '2025-02-13 08:00:00+00'),
    (4, 4, 4, 5, 'eric.solofom@example.com', 'pending', 'token-4', '2025-05-01 12:00:00+00', '2025-02-14 08:00:00+00'),
    (5, 5, 5, 6, 'fanja.raso@example.com', 'accepted', 'token-5', '2025-06-12 12:00:00+00', '2025-02-15 08:00:00+00'),
    (6, 6, 6, 7, 'gael.rak@example.com', 'declined', 'token-6', '2025-04-12 12:00:00+00', '2025-02-16 08:00:00+00'),
    (7, 7, 7, 8, 'helena.randria@example.com', 'pending', 'token-7', '2025-03-28 12:00:00+00', '2025-02-17 08:00:00+00'),
    (8, 8, 8, 9, 'isa.rava@example.com', 'accepted', 'token-8', '2025-04-18 12:00:00+00', '2025-02-18 08:00:00+00'),
    (9, 9, 9, 10, 'joel.tovo@example.com', 'pending', 'token-9', '2025-05-05 12:00:00+00', '2025-02-19 08:00:00+00'),
    (10, 10, 10, 1, 'alice.dupont@example.com', 'pending', 'token-10', '2025-07-01 12:00:00+00', '2025-02-20 08:00:00+00');

-- ============================================================
-- Wishlist, Gamification & Social
-- ============================================================
INSERT INTO wishlists (id, user_id, title, is_default, created_at)
VALUES
    (1, 1, 'Favoris Alice', TRUE, '2025-02-01 17:00:00+00'),
    (2, 2, 'Favoris Benoit', TRUE, '2025-02-02 17:00:00+00'),
    (3, 3, 'Favoris Celine', TRUE, '2025-02-03 17:00:00+00'),
    (4, 4, 'Favoris Dina', TRUE, '2025-02-04 17:00:00+00'),
    (5, 5, 'Favoris Eric', TRUE, '2025-02-05 17:00:00+00'),
    (6, 6, 'Favoris Fanja', TRUE, '2025-02-06 17:00:00+00'),
    (7, 7, 'Favoris Gael', TRUE, '2025-02-07 17:00:00+00'),
    (8, 8, 'Favoris Helena', TRUE, '2025-02-08 17:00:00+00'),
    (9, 9, 'Favoris Isa', TRUE, '2025-02-09 17:00:00+00'),
    (10, 10, 'Favoris Joel', TRUE, '2025-02-10 17:00:00+00');

INSERT INTO wishlist_items (wishlist_id, event_id, added_at)
VALUES
    (1, 5, '2025-02-01 17:05:00+00'),
    (2, 1, '2025-02-02 17:05:00+00'),
    (3, 3, '2025-02-03 17:05:00+00'),
    (4, 4, '2025-02-04 17:05:00+00'),
    (5, 8, '2025-02-05 17:05:00+00'),
    (6, 2, '2025-02-06 17:05:00+00'),
    (7, 7, '2025-02-07 17:05:00+00'),
    (8, 9, '2025-02-08 17:05:00+00'),
    (9, 6, '2025-02-09 17:05:00+00'),
    (10, 10, '2025-02-10 17:05:00+00');

INSERT INTO ticket_chance_entries (id, user_id, event_id, prize_type, prize_value, status, created_at, claimed_at)
VALUES
    (1, 1, 1, 'percent', 10, 'won', '2025-02-01 18:00:00+00', '2025-02-02 08:00:00+00'),
    (2, 2, 2, 'amount', 15000, 'lost', '2025-02-02 18:00:00+00', NULL),
    (3, 3, 3, 'percent', 5, 'pending', '2025-02-03 18:00:00+00', NULL),
    (4, 4, 4, 'amount', 20000, 'won', '2025-02-04 18:00:00+00', '2025-02-05 08:00:00+00'),
    (5, 5, 5, 'percent', 15, 'lost', '2025-02-05 18:00:00+00', NULL),
    (6, 6, 6, 'amount', 10000, 'pending', '2025-02-06 18:00:00+00', NULL),
    (7, 7, 7, 'percent', 8, 'won', '2025-02-07 18:00:00+00', '2025-02-08 08:00:00+00'),
    (8, 8, 8, 'amount', 12000, 'lost', '2025-02-08 18:00:00+00', NULL),
    (9, 9, 9, 'percent', 12, 'pending', '2025-02-09 18:00:00+00', NULL),
    (10, 10, 10, 'amount', 18000, 'won', '2025-02-10 18:00:00+00', '2025-02-11 08:00:00+00');

INSERT INTO user_connections (user_id, target_user_id, relation_type, created_at)
VALUES
    (1, 2, 'friend', '2025-02-01 19:00:00+00'),
    (2, 3, 'friend', '2025-02-02 19:00:00+00'),
    (3, 4, 'friend', '2025-02-03 19:00:00+00'),
    (4, 5, 'friend', '2025-02-04 19:00:00+00'),
    (5, 6, 'friend', '2025-02-05 19:00:00+00'),
    (6, 7, 'friend', '2025-02-06 19:00:00+00'),
    (7, 8, 'friend', '2025-02-07 19:00:00+00'),
    (8, 9, 'friend', '2025-02-08 19:00:00+00'),
    (9, 10, 'friend', '2025-02-09 19:00:00+00'),
    (10, 1, 'friend', '2025-02-10 19:00:00+00');

INSERT INTO event_invites (id, event_id, sender_user_id, recipient_user_id, recipient_email, status, token, sent_at)
VALUES
    (1, 1, 1, 2, 'benoit.rakoto@example.com', 'accepted', 'invite-1', '2025-02-01 20:00:00+00'),
    (2, 2, 2, 3, 'celine.ranaivo@example.com', 'pending', 'invite-2', '2025-02-02 20:00:00+00'),
    (3, 3, 3, 4, 'dina.andriam@example.com', 'accepted', 'invite-3', '2025-02-03 20:00:00+00'),
    (4, 4, 4, 5, 'eric.solofom@example.com', 'declined', 'invite-4', '2025-02-04 20:00:00+00'),
    (5, 5, 5, 6, 'fanja.raso@example.com', 'pending', 'invite-5', '2025-02-05 20:00:00+00'),
    (6, 6, 6, 7, 'gael.rak@example.com', 'accepted', 'invite-6', '2025-02-06 20:00:00+00'),
    (7, 7, 7, 8, 'helena.randria@example.com', 'pending', 'invite-7', '2025-02-07 20:00:00+00'),
    (8, 8, 8, 9, 'isa.rava@example.com', 'accepted', 'invite-8', '2025-02-08 20:00:00+00'),
    (9, 9, 9, 10, 'joel.tovo@example.com', 'pending', 'invite-9', '2025-02-09 20:00:00+00'),
    (10, 10, 10, 1, 'alice.dupont@example.com', 'accepted', 'invite-10', '2025-02-10 20:00:00+00');

INSERT INTO referral_rewards (id, referrer_user_id, referred_user_id, reward_type, reward_value, created_at, redeemed_at)
VALUES
    (1, 1, 2, 'amount', 10000, '2025-02-01 21:00:00+00', '2025-02-10 08:00:00+00'),
    (2, 2, 3, 'points', 200, '2025-02-02 21:00:00+00', NULL),
    (3, 3, 4, 'percent', 10, '2025-02-03 21:00:00+00', '2025-02-12 08:00:00+00'),
    (4, 4, 5, 'amount', 15000, '2025-02-04 21:00:00+00', NULL),
    (5, 5, 6, 'points', 300, '2025-02-05 21:00:00+00', '2025-02-14 08:00:00+00'),
    (6, 6, 7, 'percent', 8, '2025-02-06 21:00:00+00', NULL),
    (7, 7, 8, 'amount', 12000, '2025-02-07 21:00:00+00', '2025-02-16 08:00:00+00'),
    (8, 8, 9, 'points', 250, '2025-02-08 21:00:00+00', NULL),
    (9, 9, 10, 'percent', 12, '2025-02-09 21:00:00+00', '2025-02-18 08:00:00+00'),
    (10, 10, 1, 'amount', 18000, '2025-02-10 21:00:00+00', NULL);

INSERT INTO recommendation_cache (user_id, event_id, score, reason, generated_at)
VALUES
    (1, 5, 0.925, 'Historique festivals.', '2025-02-01 22:00:00+00'),
    (2, 2, 0.875, 'Intérêt conférences.', '2025-02-02 22:00:00+00'),
    (3, 3, 0.890, 'Préférences ateliers.', '2025-02-03 22:00:00+00'),
    (4, 4, 0.860, 'Historique trail.', '2025-02-04 22:00:00+00'),
    (5, 1, 0.910, 'Préférences concerts.', '2025-02-05 22:00:00+00'),
    (6, 7, 0.845, 'Profil business.', '2025-02-06 22:00:00+00'),
    (7, 8, 0.935, 'Favoris concerts.', '2025-02-07 22:00:00+00'),
    (8, 9, 0.830, 'Intérêt expositions.', '2025-02-08 22:00:00+00'),
    (9, 10, 0.870, 'Historique trails.', '2025-02-09 22:00:00+00'),
    (10, 6, 0.805, 'Intérêt sport.', '2025-02-10 22:00:00+00');

-- ============================================================
-- Notifications
-- ============================================================
INSERT INTO notification_templates (id, code, channel, subject, body, metadata, created_at)
VALUES
    (1, 'order_confirmation', 'email', 'Confirmation de commande', 'Merci pour votre achat.', '{"type":"order"}', '2025-02-01 08:00:00+00'),
    (2, 'event_reminder', 'email', 'Rappel événement', 'Votre événement approche.', '{"type":"reminder"}', '2025-02-02 08:00:00+00'),
    (3, 'promotion', 'web_push', 'Nouvelle promotion', 'Profitez de nos offres.', '{"type":"promo"}', '2025-02-03 08:00:00+00'),
    (4, 'ticket_ready', 'email', 'Billet disponible', 'Votre billet est prêt.', '{"type":"ticket"}', '2025-02-04 08:00:00+00'),
    (5, 'payment_received', 'email', 'Paiement reçu', 'Nous avons reçu votre paiement.', '{"type":"payment"}', '2025-02-05 08:00:00+00'),
    (6, 'recommendation', 'web_push', 'Événements pour vous', 'Voici nos suggestions.', '{"type":"recommendation"}', '2025-02-06 08:00:00+00'),
    (7, 'wishlist', 'email', 'Votre liste de souhaits', 'Un événement de votre wishlist est bientôt.', '{"type":"wishlist"}', '2025-02-07 08:00:00+00'),
    (8, 'ticket_transfer', 'email', 'Transfert de billet', 'Vous avez reçu un billet.', '{"type":"transfer"}', '2025-02-08 08:00:00+00'),
    (9, 'wallet_credit', 'email', 'Crédit portefeuille', 'Votre portefeuille a été crédité.', '{"type":"wallet"}', '2025-02-09 08:00:00+00'),
    (10, 'subscription_invoice', 'email', 'Facture abonnement', 'Votre facture est disponible.', '{"type":"subscription"}', '2025-02-10 08:00:00+00');

INSERT INTO notifications (id, user_id, template_id, channel, status, payload, scheduled_at, sent_at, read_at, created_at)
VALUES
    (1, 1, 1, 'email', 'sent', '{"order_id":1}', '2025-02-01 16:05:00+00', '2025-02-01 16:06:00+00', '2025-02-01 16:10:00+00', '2025-02-01 16:05:00+00'),
    (2, 2, 2, 'email', 'sent', '{"event_id":2}', '2025-02-02 16:05:00+00', '2025-02-02 16:06:00+00', '2025-02-02 16:10:00+00', '2025-02-02 16:05:00+00'),
    (3, 3, 3, 'web_push', 'sent', '{"promo":"PROMO10"}', '2025-02-03 16:05:00+00', '2025-02-03 16:06:00+00', NULL, '2025-02-03 16:05:00+00'),
    (4, 4, 4, 'email', 'sent', '{"ticket_id":4}', '2025-02-04 16:05:00+00', '2025-02-04 16:06:00+00', '2025-02-04 16:12:00+00', '2025-02-04 16:05:00+00'),
    (5, 5, 5, 'email', 'sent', '{"order_id":5}', '2025-02-05 16:05:00+00', '2025-02-05 16:06:00+00', '2025-02-05 16:15:00+00', '2025-02-05 16:05:00+00'),
    (6, 6, 6, 'web_push', 'sent', '{"events":[7]}', '2025-02-06 16:05:00+00', '2025-02-06 16:06:00+00', NULL, '2025-02-06 16:05:00+00'),
    (7, 7, 7, 'email', 'sent', '{"wishlist_event":7}', '2025-02-07 16:05:00+00', '2025-02-07 16:06:00+00', '2025-02-07 16:18:00+00', '2025-02-07 16:05:00+00'),
    (8, 8, 8, 'email', 'sent', '{"ticket_id":8}', '2025-02-08 16:05:00+00', '2025-02-08 16:06:00+00', '2025-02-08 16:20:00+00', '2025-02-08 16:05:00+00'),
    (9, 9, 9, 'email', 'sent', '{"wallet_id":9}', '2025-02-09 16:05:00+00', '2025-02-09 16:06:00+00', '2025-02-09 16:22:00+00', '2025-02-09 16:05:00+00'),
    (10, 10, 10, 'email', 'sent', '{"subscription_id":10}', '2025-02-10 16:05:00+00', '2025-02-10 16:06:00+00', '2025-02-10 16:25:00+00', '2025-02-10 16:05:00+00');

INSERT INTO notification_history (id, notification_id, status, message, created_at)
VALUES
    (1, 1, 'sent', 'Notification envoyée.', '2025-02-01 16:06:00+00'),
    (2, 2, 'sent', 'Notification envoyée.', '2025-02-02 16:06:00+00'),
    (3, 3, 'sent', 'Notification envoyée.', '2025-02-03 16:06:00+00'),
    (4, 4, 'sent', 'Notification envoyée.', '2025-02-04 16:06:00+00'),
    (5, 5, 'sent', 'Notification envoyée.', '2025-02-05 16:06:00+00'),
    (6, 6, 'sent', 'Notification envoyée.', '2025-02-06 16:06:00+00'),
    (7, 7, 'sent', 'Notification envoyée.', '2025-02-07 16:06:00+00'),
    (8, 8, 'sent', 'Notification envoyée.', '2025-02-08 16:06:00+00'),
    (9, 9, 'sent', 'Notification envoyée.', '2025-02-09 16:06:00+00'),
    (10, 10, 'sent', 'Notification envoyée.', '2025-02-10 16:06:00+00');

-- ============================================================
-- Promotion Codes & Applications
-- ============================================================
INSERT INTO promotion_codes (id, organizer_profile_id, code, promotion_type, value, max_usage_total, max_usage_per_user, starts_at, ends_at, metadata, created_at)
VALUES
    (1, 1, 'PROMO10', 'percent', 10, 500, 1, '2025-02-01 00:00:00+00', '2025-03-31 23:59:59+00', '{"event_id":1}', '2025-02-01 08:00:00+00'),
    (2, 2, 'CONF5', 'percent', 5, 300, 1, '2025-02-02 00:00:00+00', '2025-04-04 23:59:59+00', '{"event_id":2}', '2025-02-02 08:00:00+00'),
    (3, 3, 'PHOTO15', 'percent', 15, 200, 1, '2025-02-03 00:00:00+00', '2025-03-19 23:59:59+00', '{"event_id":3}', '2025-02-03 08:00:00+00'),
    (4, 4, 'TRAIL20', 'percent', 20, 400, 1, '2025-02-04 00:00:00+00', '2025-04-30 23:59:59+00', '{"event_id":4}', '2025-02-04 08:00:00+00'),
    (5, 5, 'FEST6', 'amount', 6000, 600, 1, '2025-02-05 00:00:00+00', '2025-06-11 23:59:59+00', '{"event_id":5}', '2025-02-05 08:00:00+00'),
    (6, 6, 'RUN5', 'percent', 5, 300, 1, '2025-02-06 00:00:00+00', '2025-04-11 23:59:59+00', '{"event_id":6}', '2025-02-06 08:00:00+00'),
    (7, 7, 'BIZ7', 'percent', 7, 250, 1, '2025-02-07 00:00:00+00', '2025-03-27 23:59:59+00', '{"event_id":7}', '2025-02-07 08:00:00+00'),
    (8, 8, 'ROCK9', 'percent', 9, 350, 1, '2025-02-08 00:00:00+00', '2025-04-17 23:59:59+00', '{"event_id":8}', '2025-02-08 08:00:00+00'),
    (9, 9, 'EXPO3', 'amount', 3000, 300, 1, '2025-02-09 00:00:00+00', '2025-05-04 23:59:59+00', '{"event_id":9}', '2025-02-09 08:00:00+00'),
    (10, 10, 'TRAIL12', 'percent', 12, 500, 1, '2025-02-10 00:00:00+00', '2025-06-30 23:59:59+00', '{"event_id":10}', '2025-02-10 08:00:00+00');

INSERT INTO promotion_applications (id, promotion_id, order_id, user_id, discount_amount, applied_at)
VALUES
    (1, 1, 1, 1, 20000, '2025-02-01 16:00:00+00'),
    (2, 2, 2, 2, 4500, '2025-02-02 16:00:00+00'),
    (3, 3, 3, 3, 9000, '2025-02-03 16:00:00+00'),
    (4, 4, 4, 4, 18000, '2025-02-04 16:00:00+00'),
    (5, 5, 5, 5, 6000, '2025-02-05 16:00:00+00'),
    (6, 6, 6, 6, 2000, '2025-02-06 16:00:00+00'),
    (7, 7, 7, 7, 4900, '2025-02-07 16:00:00+00'),
    (8, 8, 8, 8, 8100, '2025-02-08 16:00:00+00'),
    (9, 9, 9, 9, 3000, '2025-02-09 16:00:00+00'),
    (10, 10, 10, 10, 11400, '2025-02-10 16:00:00+00');

-- ============================================================
-- Pricing Rules
-- ============================================================
INSERT INTO pricing_rules (id, ticket_type_id, rule_type, threshold_value, value, starts_at, ends_at, metadata, created_at)
VALUES
    (1, 1, 'tier', 100, 140000, '2025-02-01 00:00:00+00', '2025-03-01 00:00:00+00', '{"note":"early bird"}', '2025-02-01 09:00:00+00'),
    (2, 2, 'tier', 50, 75000, '2025-02-05 00:00:00+00', '2025-03-01 00:00:00+00', '{"note":"early bird"}', '2025-02-02 09:00:00+00'),
    (3, 3, 'tier', 20, 55000, '2025-02-05 00:00:00+00', '2025-03-10 00:00:00+00', '{"note":"early bird"}', '2025-02-03 09:00:00+00'),
    (4, 4, 'tier', 200, 85000, '2025-02-10 00:00:00+00', '2025-03-20 00:00:00+00', '{"note":"early bird"}', '2025-02-04 09:00:00+00'),
    (5, 5, 'tier', 300, 110000, '2025-02-12 00:00:00+00', '2025-03-25 00:00:00+00', '{"note":"early bird"}', '2025-02-05 09:00:00+00'),
    (6, 6, 'tier', 150, 38000, '2025-02-15 00:00:00+00', '2025-03-30 00:00:00+00', '{"note":"early bird"}', '2025-02-06 09:00:00+00'),
    (7, 7, 'tier', 120, 65000, '2025-02-18 00:00:00+00', '2025-03-15 00:00:00+00', '{"note":"early bird"}', '2025-02-07 09:00:00+00'),
    (8, 8, 'tier', 250, 85000, '2025-02-20 00:00:00+00', '2025-04-01 00:00:00+00', '{"note":"early bird"}', '2025-02-08 09:00:00+00'),
    (9, 9, 'tier', 60, 28000, '2025-02-22 00:00:00+00', '2025-04-15 00:00:00+00', '{"note":"early bird"}', '2025-02-09 09:00:00+00'),
    (10, 10, 'tier', 180, 90000, '2025-02-24 00:00:00+00', '2025-05-01 00:00:00+00', '{"note":"early bird"}', '2025-02-10 09:00:00+00');

-- ============================================================
-- Facturation billets & paiements
-- ============================================================
INSERT INTO ticket_invoices (id, order_id, customer_id, currency, subtotal_amount, tax_amount, total_amount, status, issued_at, due_at, paid_at, metadata, created_at, updated_at)
VALUES
    (1, 1, 1, 'MGA', 150000, 50000, 200000, 'paid', '2025-02-01 16:04:00+00', '2025-02-08 16:04:00+00', '2025-02-01 16:05:00+00', '{"order":"1"}', '2025-02-01 16:04:00+00', '2025-02-01 16:05:00+00'),
    (2, 2, 2, 'MGA', 80000, 10000, 90000, 'paid', '2025-02-02 16:04:00+00', '2025-02-09 16:04:00+00', '2025-02-02 16:05:00+00', '{"order":"2"}', '2025-02-02 16:04:00+00', '2025-02-02 16:05:00+00'),
    (3, 3, 3, 'MGA', 60000, 6000, 66000, 'paid', '2025-02-03 16:04:00+00', '2025-02-10 16:04:00+00', '2025-02-03 16:05:00+00', '{"order":"3"}', '2025-02-03 16:04:00+00', '2025-02-03 16:05:00+00'),
    (4, 4, 4, 'MGA', 90000, 9000, 99000, 'paid', '2025-02-04 16:04:00+00', '2025-02-11 16:04:00+00', '2025-02-04 16:05:00+00', '{"order":"4"}', '2025-02-04 16:04:00+00', '2025-02-04 16:05:00+00'),
    (5, 5, 5, 'MGA', 120000, 12000, 132000, 'paid', '2025-02-05 16:04:00+00', '2025-02-12 16:04:00+00', '2025-02-05 16:05:00+00', '{"order":"5"}', '2025-02-05 16:04:00+00', '2025-02-05 16:05:00+00'),
    (6, 6, 6, 'MGA', 40000, 4000, 44000, 'paid', '2025-02-06 16:04:00+00', '2025-02-13 16:04:00+00', '2025-02-06 16:05:00+00', '{"order":"6"}', '2025-02-06 16:04:00+00', '2025-02-06 16:05:00+00'),
    (7, 7, 7, 'MGA', 70000, 7000, 77000, 'paid', '2025-02-07 16:04:00+00', '2025-02-14 16:04:00+00', '2025-02-07 16:05:00+00', '{"order":"7"}', '2025-02-07 16:04:00+00', '2025-02-07 16:05:00+00'),
    (8, 8, 8, 'MGA', 90000, 9000, 99000, 'paid', '2025-02-08 16:04:00+00', '2025-02-15 16:04:00+00', '2025-02-08 16:05:00+00', '{"order":"8"}', '2025-02-08 16:04:00+00', '2025-02-08 16:05:00+00'),
    (9, 9, 9, 'MGA', 30000, 3000, 33000, 'paid', '2025-02-09 16:04:00+00', '2025-02-16 16:04:00+00', '2025-02-09 16:05:00+00', '{"order":"9"}', '2025-02-09 16:04:00+00', '2025-02-09 16:05:00+00'),
    (10, 10, 10, 'MGA', 95000, 9500, 104500, 'paid', '2025-02-10 16:04:00+00', '2025-02-17 16:04:00+00', '2025-02-10 16:05:00+00', '{"order":"10"}', '2025-02-10 16:04:00+00', '2025-02-10 16:05:00+00');

INSERT INTO ticket_payments (id, invoice_id, provider, provider_reference, status, amount, currency, paid_at, metadata, created_at, updated_at)
VALUES
    (1, 1, 'orange', 'OR-1001', 'paid', 200000, 'MGA', '2025-02-01 16:05:00+00', '{"method":"mobile"}', '2025-02-01 16:05:00+00', '2025-02-01 16:05:00+00'),
    (2, 2, 'airtel', 'AI-1002', 'paid', 90000, 'MGA', '2025-02-02 16:05:00+00', '{"method":"mobile"}', '2025-02-02 16:05:00+00', '2025-02-02 16:05:00+00'),
    (3, 3, 'telma', 'TE-1003', 'paid', 66000, 'MGA', '2025-02-03 16:05:00+00', '{"method":"mobile"}', '2025-02-03 16:05:00+00', '2025-02-03 16:05:00+00'),
    (4, 4, 'orange', 'OR-1004', 'paid', 99000, 'MGA', '2025-02-04 16:05:00+00', '{"method":"mobile"}', '2025-02-04 16:05:00+00', '2025-02-04 16:05:00+00'),
    (5, 5, 'airtel', 'AI-1005', 'paid', 132000, 'MGA', '2025-02-05 16:05:00+00', '{"method":"mobile"}', '2025-02-05 16:05:00+00', '2025-02-05 16:05:00+00'),
    (6, 6, 'telma', 'TE-1006', 'paid', 44000, 'MGA', '2025-02-06 16:05:00+00', '{"method":"mobile"}', '2025-02-06 16:05:00+00', '2025-02-06 16:05:00+00'),
    (7, 7, 'orange', 'OR-1007', 'paid', 77000, 'MGA', '2025-02-07 16:05:00+00', '{"method":"mobile"}', '2025-02-07 16:05:00+00', '2025-02-07 16:05:00+00'),
    (8, 8, 'airtel', 'AI-1008', 'paid', 99000, 'MGA', '2025-02-08 16:05:00+00', '{"method":"mobile"}', '2025-02-08 16:05:00+00', '2025-02-08 16:05:00+00'),
    (9, 9, 'telma', 'TE-1009', 'paid', 33000, 'MGA', '2025-02-09 16:05:00+00', '{"method":"mobile"}', '2025-02-09 16:05:00+00', '2025-02-09 16:05:00+00'),
    (10, 10, 'orange', 'OR-1010', 'paid', 104500, 'MGA', '2025-02-10 16:05:00+00', '{"method":"mobile"}', '2025-02-10 16:05:00+00', '2025-02-10 16:05:00+00');

INSERT INTO ticket_payment_history (id, payment_id, status_from, status_to, changed_at, metadata)
VALUES
    (1, 1, 'initiated', 'paid', '2025-02-01 16:05:00+00', '{"note":"Paiement réussi"}'),
    (2, 2, 'initiated', 'paid', '2025-02-02 16:05:00+00', '{"note":"Paiement réussi"}'),
    (3, 3, 'initiated', 'paid', '2025-02-03 16:05:00+00', '{"note":"Paiement réussi"}'),
    (4, 4, 'initiated', 'paid', '2025-02-04 16:05:00+00', '{"note":"Paiement réussi"}'),
    (5, 5, 'initiated', 'paid', '2025-02-05 16:05:00+00', '{"note":"Paiement réussi"}'),
    (6, 6, 'initiated', 'paid', '2025-02-06 16:05:00+00', '{"note":"Paiement réussi"}'),
    (7, 7, 'initiated', 'paid', '2025-02-07 16:05:00+00', '{"note":"Paiement réussi"}'),
    (8, 8, 'initiated', 'paid', '2025-02-08 16:05:00+00', '{"note":"Paiement réussi"}'),
    (9, 9, 'initiated', 'paid', '2025-02-09 16:05:00+00', '{"note":"Paiement réussi"}'),
    (10, 10, 'initiated', 'paid', '2025-02-10 16:05:00+00', '{"note":"Paiement réussi"}');

-- ------------------------------------------------------------
-- Paiements abonnements organisateurs
-- ------------------------------------------------------------
INSERT INTO subscription_invoices (id, subscription_id, customer_id, currency, subtotal_amount, tax_amount, total_amount, status, issued_at, due_at, paid_at, metadata, created_at, updated_at)
VALUES
    (1, 1, 2, 'MGA', 50000, 10000, 60000, 'paid', '2025-01-15 12:00:00+00', '2025-01-22 12:00:00+00', '2025-01-15 12:05:00+00', '{"plan":"BASIC"}', '2025-01-15 12:00:00+00', '2025-01-15 12:05:00+00'),
    (2, 2, 5, 'MGA', 100000, 20000, 120000, 'paid', '2025-01-16 12:00:00+00', '2025-01-23 12:00:00+00', '2025-01-16 12:05:00+00', '{"plan":"PRO"}', '2025-01-16 12:00:00+00', '2025-01-16 12:05:00+00'),
    (3, 3, 9, 'MGA', 200000, 40000, 240000, 'paid', '2025-01-17 12:00:00+00', '2025-01-24 12:00:00+00', '2025-01-17 12:05:00+00', '{"plan":"PREMIUM"}', '2025-01-17 12:00:00+00', '2025-01-17 12:05:00+00'),
    (4, 4, 1, 'MGA', 40000, 8000, 48000, 'paid', '2025-01-18 12:00:00+00', '2025-01-25 12:00:00+00', '2025-01-18 12:05:00+00', '{"plan":"STARTER"}', '2025-01-18 12:00:00+00', '2025-01-18 12:05:00+00'),
    (5, 5, 3, 'MGA', 150000, 30000, 180000, 'paid', '2025-01-19 12:00:00+00', '2025-01-26 12:00:00+00', '2025-01-19 12:05:00+00', '{"plan":"BUSINESS"}', '2025-01-19 12:00:00+00', '2025-01-19 12:05:00+00'),
    (6, 6, 4, 'MGA', 250000, 50000, 300000, 'paid', '2025-01-20 12:00:00+00', '2025-01-27 12:00:00+00', '2025-01-20 12:05:00+00', '{"plan":"CORP"}', '2025-01-20 12:00:00+00', '2025-01-20 12:05:00+00'),
    (7, 7, 6, 'MGA', 180000, 36000, 216000, 'paid', '2025-01-21 12:00:00+00', '2025-01-28 12:00:00+00', '2025-01-21 12:05:00+00', '{"plan":"EVENTPLUS"}', '2025-01-21 12:00:00+00', '2025-01-21 12:05:00+00'),
    (8, 8, 7, 'MGA', 300000, 60000, 360000, 'paid', '2025-01-22 12:00:00+00', '2025-01-29 12:00:00+00', '2025-01-22 12:05:00+00', '{"plan":"ULTIMATE"}', '2025-01-22 12:00:00+00', '2025-01-22 12:05:00+00'),
    (9, 9, 8, 'MGA', 90000, 18000, 108000, 'paid', '2025-01-23 12:00:00+00', '2025-01-30 12:00:00+00', '2025-01-23 12:05:00+00', '{"plan":"ARTIST"}', '2025-01-23 12:00:00+00', '2025-01-23 12:05:00+00'),
    (10, 10, 10, 'MGA', 120000, 24000, 144000, 'paid', '2025-01-24 12:00:00+00', '2025-01-31 12:00:00+00', '2025-01-24 12:05:00+00', '{"plan":"SPORT"}', '2025-01-24 12:00:00+00', '2025-01-24 12:05:00+00');

INSERT INTO subscription_payments (id, invoice_id, provider, provider_reference, status, amount, currency, paid_at, metadata, created_at, updated_at)
VALUES
    (1, 1, 'orange', 'SUB-OR-1001', 'paid', 60000, 'MGA', '2025-01-15 12:05:00+00', '{"method":"mobile"}', '2025-01-15 12:05:00+00', '2025-01-15 12:05:00+00'),
    (2, 2, 'airtel', 'SUB-AI-1002', 'paid', 120000, 'MGA', '2025-01-16 12:05:00+00', '{"method":"mobile"}', '2025-01-16 12:05:00+00', '2025-01-16 12:05:00+00'),
    (3, 3, 'telma', 'SUB-TE-1003', 'paid', 240000, 'MGA', '2025-01-17 12:05:00+00', '{"method":"mobile"}', '2025-01-17 12:05:00+00', '2025-01-17 12:05:00+00'),
    (4, 4, 'orange', 'SUB-OR-1004', 'paid', 48000, 'MGA', '2025-01-18 12:05:00+00', '{"method":"mobile"}', '2025-01-18 12:05:00+00', '2025-01-18 12:05:00+00'),
    (5, 5, 'airtel', 'SUB-AI-1005', 'paid', 180000, 'MGA', '2025-01-19 12:05:00+00', '{"method":"mobile"}', '2025-01-19 12:05:00+00', '2025-01-19 12:05:00+00'),
    (6, 6, 'telma', 'SUB-TE-1006', 'paid', 300000, 'MGA', '2025-01-20 12:05:00+00', '{"method":"mobile"}', '2025-01-20 12:05:00+00', '2025-01-20 12:05:00+00'),
    (7, 7, 'orange', 'SUB-OR-1007', 'paid', 216000, 'MGA', '2025-01-21 12:05:00+00', '{"method":"mobile"}', '2025-01-21 12:05:00+00', '2025-01-21 12:05:00+00'),
    (8, 8, 'airtel', 'SUB-AI-1008', 'paid', 360000, 'MGA', '2025-01-22 12:05:00+00', '{"method":"mobile"}', '2025-01-22 12:05:00+00', '2025-01-22 12:05:00+00'),
    (9, 9, 'telma', 'SUB-TE-1009', 'paid', 108000, 'MGA', '2025-01-23 12:05:00+00', '{"method":"mobile"}', '2025-01-23 12:05:00+00', '2025-01-23 12:05:00+00'),
    (10, 10, 'orange', 'SUB-OR-1010', 'paid', 144000, 'MGA', '2025-01-24 12:05:00+00', '{"method":"mobile"}', '2025-01-24 12:05:00+00', '2025-01-24 12:05:00+00');

INSERT INTO subscription_invoice_items (id, invoice_id, plan_id, description, quantity, unit_price, total_amount, metadata)
VALUES
    (1, 1, 1, 'Licence mensuelle Basic', 1, 50000, 50000, '{"periode":"2025-01"}'),
    (2, 2, 2, 'Licence mensuelle Pro', 1, 100000, 100000, '{"periode":"2025-01"}'),
    (3, 3, 3, 'Licence mensuelle Premium', 1, 200000, 200000, '{"periode":"2025-01"}'),
    (4, 4, 4, 'Licence mensuelle Starter', 1, 40000, 40000, '{"periode":"2025-01"}'),
    (5, 5, 5, 'Licence mensuelle Business', 1, 150000, 150000, '{"periode":"2025-01"}'),
    (6, 6, 6, 'Licence mensuelle Corporate', 1, 250000, 250000, '{"periode":"2025-01"}'),
    (7, 7, 7, 'Licence mensuelle Event Plus', 1, 180000, 180000, '{"periode":"2025-01"}'),
    (8, 8, 8, 'Licence mensuelle Ultimate', 1, 300000, 300000, '{"periode":"2025-01"}'),
    (9, 9, 9, 'Licence mensuelle Artist', 1, 90000, 90000, '{"periode":"2025-01"}'),
    (10, 10, 10, 'Licence mensuelle Sport', 1, 120000, 120000, '{"periode":"2025-01"}');

INSERT INTO subscription_payment_history (id, payment_id, status_from, status_to, changed_at, metadata)
VALUES
    (1, 1, 'initiated', 'paid', '2025-01-15 12:05:00+00', '{"note":"Paiement abonnement réussi"}'),
    (2, 2, 'initiated', 'paid', '2025-01-16 12:05:00+00', '{"note":"Paiement abonnement réussi"}'),
    (3, 3, 'initiated', 'paid', '2025-01-17 12:05:00+00', '{"note":"Paiement abonnement réussi"}'),
    (4, 4, 'initiated', 'paid', '2025-01-18 12:05:00+00', '{"note":"Paiement abonnement réussi"}'),
    (5, 5, 'initiated', 'paid', '2025-01-19 12:05:00+00', '{"note":"Paiement abonnement réussi"}'),
    (6, 6, 'initiated', 'paid', '2025-01-20 12:05:00+00', '{"note":"Paiement abonnement réussi"}'),
    (7, 7, 'initiated', 'paid', '2025-01-21 12:05:00+00', '{"note":"Paiement abonnement réussi"}'),
    (8, 8, 'initiated', 'paid', '2025-01-22 12:05:00+00', '{"note":"Paiement abonnement réussi"}'),
    (9, 9, 'initiated', 'paid', '2025-01-23 12:05:00+00', '{"note":"Paiement abonnement réussi"}'),
    (10, 10, 'initiated', 'paid', '2025-01-24 12:05:00+00', '{"note":"Paiement abonnement réussi"}');

COMMIT;
