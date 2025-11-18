-- ============================================================
-- Script pour exécuter mydata.sql manuellement, étape par étape
-- Copiez et exécutez chaque section une par une dans psql
-- ============================================================

SET search_path TO aiolia, public;

-- ============================================================
-- ÉTAPE 1: Vérifier l'état actuel
-- ============================================================
SELECT 'Événements existants' AS info, COUNT(*)::TEXT AS valeur FROM events;
SELECT 'Types de billets existants' AS info, COUNT(*)::TEXT AS valeur FROM ticket_types;
SELECT 'Utilisateurs existants' AS info, COUNT(*)::TEXT AS valeur FROM users WHERE email = 'admin@aiolia.com';

-- ============================================================
-- ÉTAPE 2: Exécuter mydata.sql (copiez-collez la commande)
-- ============================================================
-- Dans le terminal (pas dans psql), exécutez:
-- psql -h localhost -U aiolia_user -d aiolia_event -f /home/aina/Documents/MyProject/Aiolia-event/Base/mydata.sql

-- ============================================================
-- ÉTAPE 3: Vérifier après exécution
-- ============================================================
SELECT 'Événements après insertion' AS info, COUNT(*)::TEXT AS valeur FROM events;
SELECT 'Types de billets après insertion' AS info, COUNT(*)::TEXT AS valeur FROM ticket_types;

-- Liste des événements créés
SELECT id, slug, title, status, organizer_id 
FROM events 
ORDER BY id;

-- Types de billets avec leurs catégories d'âge
SELECT 
    e.slug AS event_slug,
    e.title AS event_title,
    tt.name AS ticket_name,
    tt.age_category,
    tt.base_price,
    inv.total_quantity AS available
FROM ticket_types tt
JOIN events e ON e.id = tt.event_id
LEFT JOIN ticket_inventory inv ON inv.ticket_type_id = tt.id
ORDER BY e.slug, tt.age_category;

-- ============================================================
-- ÉTAPE 4: Rattacher les événements à votre utilisateur
-- ============================================================
-- Exécutez ensuite myscript.sql ou setup_user_events.sql

