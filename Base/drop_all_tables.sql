-- ===================================================================
-- Script pour supprimer toutes les tables et objets de la base
-- Utilisation : psql -U postgres -d aiolia_event -f drop_all_tables.sql
-- ===================================================================

-- Déconnecter toutes les sessions actives sur la base
SELECT pg_terminate_backend(pid)
FROM pg_stat_activity
WHERE datname = 'aiolia_event' AND pid <> pg_backend_pid();

-- Attendre un peu pour que les connexions se terminent
SELECT pg_sleep(1);

-- Supprimer toutes les tables dans le bon ordre (CASCADE pour gérer les dépendances)
DROP SCHEMA IF EXISTS aiolia CASCADE;
CREATE SCHEMA aiolia;

-- Supprimer les types personnalisés s'ils existent
DROP TYPE IF EXISTS aiolia.user_role_enum CASCADE;
DROP TYPE IF EXISTS aiolia.auth_provider_enum CASCADE;
DROP TYPE IF EXISTS aiolia.event_status_enum CASCADE;
DROP TYPE IF EXISTS aiolia.event_visibility_enum CASCADE;
DROP TYPE IF EXISTS aiolia.ticket_status_enum CASCADE;
DROP TYPE IF EXISTS aiolia.ticket_transfer_status_enum CASCADE;
DROP TYPE IF EXISTS aiolia.order_status_enum CASCADE;
DROP TYPE IF EXISTS aiolia.payment_status_enum CASCADE;
DROP TYPE IF EXISTS aiolia.notification_channel_enum CASCADE;
DROP TYPE IF EXISTS aiolia.notification_status_enum CASCADE;
DROP TYPE IF EXISTS aiolia.promotion_type_enum CASCADE;
DROP TYPE IF EXISTS aiolia.pricing_rule_type_enum CASCADE;
DROP TYPE IF EXISTS aiolia.wallet_transaction_type_enum CASCADE;
DROP TYPE IF EXISTS aiolia.wallet_transaction_status_enum CASCADE;
DROP TYPE IF EXISTS aiolia.referral_reward_type_enum CASCADE;
DROP TYPE IF EXISTS aiolia.invite_status_enum CASCADE;
DROP TYPE IF EXISTS aiolia.subscription_status_enum CASCADE;
DROP TYPE IF EXISTS aiolia.cart_status_enum CASCADE;
DROP TYPE IF EXISTS aiolia.ticket_chance_status_enum CASCADE;
DROP TYPE IF EXISTS aiolia.organizer_type_enum CASCADE;
DROP TYPE IF EXISTS aiolia.age_category_enum CASCADE;
DROP TYPE IF EXISTS aiolia.currency_code CASCADE;

-- Supprimer les séquences
DROP SEQUENCE IF EXISTS aiolia.invoice_number_seq CASCADE;

-- Message de confirmation
SELECT 'Toutes les tables et objets ont été supprimés. Vous pouvez maintenant exécuter schema.sql' AS message;


