# 🚀 GUIDE DE DÉMARRAGE RAPIDE - SCHÉMA OPTIMISÉ

## 📦 Installation

### 1. Créer la base de données

```bash
# Créer la base
createdb aiolia_event

# Importer le schéma optimisé
psql aiolia_event < database/schemaOptimized.sql
```

### 2. Vérifier l'installation

```sql
-- Vérifier le nombre de tables (doit être 34)
SELECT COUNT(*) FROM information_schema.tables 
WHERE table_schema = 'public' AND table_type = 'BASE TABLE';

-- Vérifier les vues (doit être 4)
SELECT COUNT(*) FROM information_schema.views 
WHERE table_schema = 'public';

-- Vérifier les fonctions (doit inclure au moins 5)
SELECT COUNT(*) FROM pg_proc p
JOIN pg_namespace n ON p.pronamespace = n.oid
WHERE n.nspname = 'public';
```

---

## 🎯 EXEMPLES D'UTILISATION

### 1️⃣ Créer un Utilisateur

```sql
INSERT INTO users (email, password_hash, first_name, last_name, role) 
VALUES (
    'jean.dupont@example.com',
    '$2y$10$...', -- Hash bcrypt du mot de passe
    'Jean',
    'Dupont',
    'organizer'
);
```

---

### 2️⃣ Créer un Événement avec Tarification Dynamique

```sql
-- Insérer l'événement
INSERT INTO events (
    organizer_id, category_id, title, slug, 
    description, location, start_date, end_date,
    status, total_capacity, enable_dynamic_pricing
) VALUES (
    1,  -- ID de l'organisateur
    1,  -- ID catégorie (Concert)
    'Festival Jazz Tana 2025',
    'festival-jazz-tana-2025',
    'Le plus grand festival de jazz de Madagascar',
    'Analakely, Antananarivo',
    '2025-12-15 18:00:00',
    '2025-12-15 23:00:00',
    'published',
    500,
    true  -- Activer tarification dynamique
) RETURNING id;

-- Supposons que l'événement créé a l'ID 1

-- Créer catégorie de billet STANDARD (sans tarification dynamique)
INSERT INTO ticket_categories (
    event_id, name, description, price, quantity_total,
    enable_dynamic_pricing
) VALUES (
    1, 'Standard', 'Accès général', 50000, 300, false
);

-- Créer catégorie de billet VIP (avec tarification dynamique)
INSERT INTO ticket_categories (
    event_id, name, description, price, quantity_total,
    enable_dynamic_pricing,
    pricing_tier_1_threshold, pricing_tier_1_price,
    pricing_tier_2_threshold, pricing_tier_2_price,
    pricing_tier_3_threshold, pricing_tier_3_price
) VALUES (
    1, 'VIP', 'Accès VIP avec cocktail', 120000, 200,
    true,
    50,  150000,  -- 25% vendus → +25%
    100, 180000,  -- 50% vendus → +50%
    150, 200000   -- 75% vendus → +67%
);
```

---

### 3️⃣ Obtenir le Prix Actuel (avec Tarification Dynamique)

```sql
-- Méthode 1: Utiliser la fonction
SELECT get_dynamic_price(2);  -- ID de la catégorie VIP
-- Retourne: 120000 (si 0 billets vendus)

-- Méthode 2: Requête complète
SELECT 
    tc.name,
    tc.price as prix_base,
    tc.quantity_sold,
    tc.quantity_total,
    get_dynamic_price(tc.id) as prix_actuel,
    ROUND(tc.quantity_sold::DECIMAL / tc.quantity_total * 100, 2) as pourcentage_vendu
FROM ticket_categories tc
WHERE tc.event_id = 1;

/*
Résultat:
name     | prix_base | quantity_sold | quantity_total | prix_actuel | pourcentage_vendu
---------|-----------|---------------|----------------|-------------|------------------
Standard | 50000     | 0             | 300            | 50000       | 0.00
VIP      | 120000    | 0             | 200            | 120000      | 0.00
*/
```

---

### 4️⃣ Créer une Commande

```sql
-- 1. Créer la commande
INSERT INTO orders (
    user_id, order_number, status, 
    subtotal, total_amount, currency
) VALUES (
    1,  -- ID utilisateur
    'ORD-' || TO_CHAR(NOW(), 'YYYYMMDD') || '-' || LPAD(nextval('orders_id_seq')::TEXT, 6, '0'),
    'pending',
    240000,  -- 2 billets VIP @ 120000
    240000,
    'MGA'
) RETURNING id;

-- Supposons order_id = 1

-- 2. Ajouter les items
INSERT INTO order_items (order_id, ticket_category_id, quantity, unit_price, total_price)
VALUES (1, 2, 2, 120000, 240000);

-- 3. Créer les billets individuels
INSERT INTO tickets (ticket_category_id, order_id, user_id, ticket_number, qr_code_data)
VALUES 
    (2, 1, 1, 'TKT-20251014-000001', 'QR-' || md5(random()::TEXT || clock_timestamp()::TEXT)),
    (2, 1, 1, 'TKT-20251014-000002', 'QR-' || md5(random()::TEXT || clock_timestamp()::TEXT));

-- 4. Mettre à jour les quantités vendues
UPDATE ticket_categories 
SET quantity_sold = quantity_sold + 2 
WHERE id = 2;

-- 5. Confirmer le paiement
UPDATE orders SET status = 'completed', payment_status = 'paid', completed_at = NOW()
WHERE id = 1;
```

**Note**: Les statistiques de l'événement seront mises à jour automatiquement grâce aux triggers!

---

### 5️⃣ Créer un Code Promo

```sql
-- Code promo global (tous événements)
INSERT INTO promo_codes (
    code, description, discount_type, discount_value,
    max_uses, max_uses_per_user,
    valid_from, valid_until,
    created_by, applicable_to
) VALUES (
    'WELCOME2025',
    'Réduction de bienvenue 15%',
    'percentage',
    15.00,
    1000,  -- 1000 utilisations max
    1,     -- 1 fois par utilisateur
    '2025-01-01',
    '2025-12-31',
    1,     -- ID admin
    'all'
) RETURNING id;

-- Code promo spécifique à un événement
INSERT INTO promo_codes (
    code, description, discount_type, discount_value,
    max_uses, max_uses_per_user,
    valid_from, valid_until,
    created_by, applicable_to
) VALUES (
    'JAZZ50',
    'Festival Jazz - 50% sur billets VIP',
    'percentage',
    50.00,
    50,
    1,
    '2025-10-01',
    '2025-12-14',
    1,
    'specific_events'
) RETURNING id;

-- Supposons promo_code_id = 2

-- Lier le code promo à l'événement
INSERT INTO event_promo_codes (promo_code_id, event_id)
VALUES (2, 1);
```

---

### 6️⃣ Jouer au Mini-Jeu "Ticket Chance"

```sql
-- 1. Vérifier si l'utilisateur peut jouer (max 3 fois/jour)
SELECT COUNT(*) FROM mini_game_participations
WHERE user_id = 1 
  AND DATE(played_at) = CURRENT_DATE;

-- Si < 3, continuer

-- 2. Sélectionner une récompense aléatoire selon probabilités
WITH random_reward AS (
    SELECT id, reward_type, reward_value, loyalty_points, promo_code_id
    FROM mini_game_rewards
    WHERE is_active = true
      AND (max_claims IS NULL OR current_claims < max_claims)
      AND (valid_from IS NULL OR valid_from <= NOW())
      AND (valid_until IS NULL OR valid_until >= NOW())
    ORDER BY 
        -- Algorithme de sélection pondéré
        -LOG(1.0 - RANDOM()) / NULLIF(probability, 0)
    LIMIT 1
)
-- 3. Créer la participation
INSERT INTO mini_game_participations (user_id, is_winner, reward_id, expires_at)
SELECT 
    1,  -- user_id
    true,
    id,
    NOW() + INTERVAL '30 days'
FROM random_reward
RETURNING id, (SELECT reward_type FROM random_reward) as reward_type;

-- Supposons participation_id = 1

-- 4. Créer la réclamation
INSERT INTO mini_game_reward_claims (
    participation_id, user_id, reward_id, expires_at
)
SELECT 1, 1, reward_id, NOW() + INTERVAL '30 days'
FROM mini_game_participations
WHERE id = 1;

-- 5. Mettre à jour les compteurs
UPDATE mini_game_rewards
SET current_claims = current_claims + 1
WHERE id = (SELECT reward_id FROM mini_game_participations WHERE id = 1);
```

---

### 7️⃣ Tracker l'Activité Utilisateur (pour suggestions)

```sql
-- Utilisateur consulte un événement
INSERT INTO user_activity_log (user_id, activity_type, event_id, category_id)
SELECT 1, 'view', 1, category_id FROM events WHERE id = 1;

-- Utilisateur recherche
INSERT INTO user_search_history (user_id, search_query, results_count)
VALUES (1, 'concert jazz', 5);

-- Utilisateur ajoute aux favoris
INSERT INTO user_activity_log (user_id, activity_type, event_id, category_id)
SELECT 1, 'favorite', 1, category_id FROM events WHERE id = 1;

INSERT INTO favorites (user_id, event_id)
VALUES (1, 1);
```

---

### 8️⃣ Obtenir les Suggestions Personnalisées

```sql
-- Top 10 suggestions pour l'utilisateur 1
SELECT 
    event_id,
    title,
    slug,
    start_date,
    relevance_score
FROM user_event_suggestions
WHERE user_id = 1
ORDER BY relevance_score DESC
LIMIT 10;

/*
Résultat:
event_id | title                    | relevance_score
---------|--------------------------|----------------
1        | Festival Jazz Tana 2025  | 85
5        | Concert Blues Live       | 72
3        | Jazz Sous Les Étoiles    | 68
...
*/
```

---

### 9️⃣ Envoyer une Notification avec Template

```sql
-- 1. Créer la notification depuis un template
WITH template AS (
    SELECT * FROM notification_templates 
    WHERE template_key = 'order_confirmation_email'
)
INSERT INTO notifications (
    user_id, type, title, message, channel, template_id
)
SELECT 
    1,  -- user_id
    type,
    REPLACE(REPLACE(subject_template, '{{order_number}}', 'ORD-20251014-000001'), 
            '{{user_name}}', 'Jean Dupont'),
    REPLACE(REPLACE(REPLACE(REPLACE(
        body_template,
        '{{order_number}}', 'ORD-20251014-000001'),
        '{{user_name}}', 'Jean Dupont'),
        '{{total_amount}}', '240 000'),
        '{{currency}}', 'MGA'),
    channel,
    id
FROM template;
```

---

### 🔟 Consulter les Statistiques

```sql
-- Statistiques d'un événement (temps réel grâce aux triggers)
SELECT 
    id,
    title,
    tickets_sold,
    revenue_total,
    average_rating,
    reviews_count
FROM events
WHERE id = 1;

-- Statistiques d'un utilisateur
SELECT * FROM user_statistics WHERE user_id = 1;

-- Dashboard organisateur: Événements avec le plus de revenus
SELECT 
    id,
    title,
    tickets_sold,
    revenue_total,
    ROUND((tickets_sold::DECIMAL / total_capacity * 100), 2) as taux_remplissage
FROM events
WHERE organizer_id = 1
  AND status = 'published'
ORDER BY revenue_total DESC;
```

---

## 🔍 REQUÊTES UTILES

### Recherche d'Événements

```sql
-- Recherche full-text
SELECT id, title, start_date, location
FROM events
WHERE to_tsvector('french', title || ' ' || COALESCE(description, '')) 
      @@ to_tsquery('french', 'jazz | concert')
  AND status = 'published'
  AND start_date > NOW()
ORDER BY start_date;

-- Recherche par catégorie, date et localisation
SELECT e.*, ec.name as category_name
FROM events e
JOIN event_categories ec ON e.category_id = ec.id
WHERE ec.slug = 'concert'
  AND e.start_date BETWEEN '2025-12-01' AND '2025-12-31'
  AND to_tsvector('french', e.location || ' ' || COALESCE(e.address, ''))
      @@ to_tsquery('french', 'Antananarivo')
  AND e.status = 'published';
```

### Vérifier les Conflits de Dates

```sql
-- Vérifier si un organisateur a des événements qui se chevauchent
SELECT * FROM check_event_conflicts(
    1,                          -- organizer_id
    '2025-12-15 18:00:00',     -- start_date
    '2025-12-15 23:00:00'      -- end_date
);
```

### Rapport de Ventes

```sql
-- Ventes par catégorie de billet
SELECT 
    e.title as evenement,
    tc.name as categorie,
    tc.quantity_total,
    tc.quantity_sold,
    tc.quantity_total - tc.quantity_sold as restants,
    ROUND((tc.quantity_sold::DECIMAL / tc.quantity_total * 100), 2) as pourcentage_vendu,
    tc.quantity_sold * get_dynamic_price(tc.id) as revenue_estime
FROM ticket_categories tc
JOIN events e ON tc.event_id = e.id
WHERE e.organizer_id = 1
ORDER BY e.start_date, tc.display_order;
```

### Top Utilisateurs (Dépenses)

```sql
-- Top 10 utilisateurs par dépenses
SELECT 
    u.id,
    u.email,
    u.first_name || ' ' || u.last_name as nom_complet,
    u.total_amount_spent,
    u.total_events_attended,
    u.loyalty_points,
    u.loyalty_tier
FROM users u
ORDER BY u.total_amount_spent DESC
LIMIT 10;
```

### Liste d'Attente

```sql
-- Utilisateurs en liste d'attente pour un événement
SELECT 
    ew.position,
    u.email,
    u.first_name || ' ' || u.last_name as nom,
    tc.name as categorie_billet,
    ew.quantity_requested,
    ew.joined_at
FROM event_waitlist ew
JOIN users u ON ew.user_id = u.id
LEFT JOIN ticket_categories tc ON ew.ticket_category_id = tc.id
WHERE ew.event_id = 1
  AND ew.status = 'waiting'
ORDER BY ew.position;
```

---

## ⚙️ CONFIGURATION SYSTÈME

### Paramètres Recommandés

```sql
-- Voir tous les paramètres
SELECT * FROM system_settings ORDER BY setting_key;

-- Modifier un paramètre
UPDATE system_settings 
SET setting_value = '20', updated_at = NOW()
WHERE setting_key = 'ticket_reservation_timeout';

-- Ajouter un nouveau paramètre
INSERT INTO system_settings (
    setting_key, setting_value, setting_type, 
    description, is_public
) VALUES (
    'max_search_history_items', 
    '100', 
    'number',
    'Nombre max d''entrées dans l''historique de recherche par utilisateur',
    false
);
```

---

## 🧹 MAINTENANCE

### Nettoyage Automatique

```sql
-- Supprimer l'historique de recherche > 90 jours
DELETE FROM user_search_history
WHERE created_at < NOW() - INTERVAL '90 days';

-- Supprimer les logs d'activité > 180 jours
DELETE FROM user_activity_log
WHERE created_at < NOW() - INTERVAL '180 days';

-- Supprimer les paniers expirés
DELETE FROM cart
WHERE expires_at < NOW();

-- Révoquer les tokens expirés
UPDATE refresh_tokens
SET is_revoked = true
WHERE expires_at < NOW() AND is_revoked = false;

-- Expirer les transferts de billets en attente
UPDATE ticket_transfers
SET status = 'expired'
WHERE status = 'pending' 
  AND expires_at < NOW();
```

### Vacuum et Analyse

```sql
-- Analyser les tables pour optimiser les requêtes
ANALYZE users;
ANALYZE events;
ANALYZE tickets;
ANALYZE orders;

-- Vacuum complet (à faire pendant maintenance)
VACUUM FULL ANALYZE;
```

---

## 📊 MONITORING

### Requêtes Lentes

```sql
-- Activer pg_stat_statements (à faire une fois)
CREATE EXTENSION IF NOT EXISTS pg_stat_statements;

-- Top 10 requêtes les plus lentes
SELECT 
    query,
    calls,
    total_exec_time,
    mean_exec_time,
    max_exec_time
FROM pg_stat_statements
ORDER BY mean_exec_time DESC
LIMIT 10;
```

### Statistiques Tables

```sql
-- Taille des tables
SELECT 
    schemaname,
    tablename,
    pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) AS size
FROM pg_tables
WHERE schemaname = 'public'
ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC;
```

---

## 🎓 BONNES PRATIQUES

### 1. Utiliser les Transactions

```sql
BEGIN;

-- Créer commande
INSERT INTO orders (...) VALUES (...) RETURNING id;

-- Créer items
INSERT INTO order_items (...) VALUES (...);

-- Créer billets
INSERT INTO tickets (...) VALUES (...);

-- Mettre à jour stock
UPDATE ticket_categories SET quantity_sold = quantity_sold + 2 WHERE id = 2;

COMMIT;  -- ou ROLLBACK en cas d'erreur
```

### 2. Vérifier les Contraintes

```sql
-- Vérifier stock disponible avant achat
SELECT 
    quantity_total - quantity_sold - quantity_reserved as disponible
FROM ticket_categories
WHERE id = 2;

-- Vérifier validité code promo
SELECT * FROM promo_codes
WHERE code = 'WELCOME2025'
  AND is_active = true
  AND valid_from <= NOW()
  AND valid_until >= NOW()
  AND (max_uses IS NULL OR current_uses < max_uses);
```

### 3. Logger les Actions Critiques

```sql
-- Logger une modification de prix
INSERT INTO audit_log (
    user_id, action, entity_type, entity_id, 
    old_values, new_values, ip_address
) VALUES (
    1,
    'update',
    'ticket_categories',
    2,
    '{"price": 120000}'::jsonb,
    '{"price": 150000}'::jsonb,
    '192.168.1.1'
);
```

---

## 🚨 DÉPANNAGE

### Problème: Statistiques événement incorrectes

```sql
-- Recalculer manuellement
UPDATE events SET
    tickets_sold = (
        SELECT COUNT(*) FROM tickets t
        JOIN ticket_categories tc ON t.ticket_category_id = tc.id
        WHERE tc.event_id = events.id AND t.status NOT IN ('cancelled', 'refunded')
    ),
    revenue_total = (
        SELECT COALESCE(SUM(oi.total_price), 0) FROM order_items oi
        JOIN ticket_categories tc ON oi.ticket_category_id = tc.id
        JOIN orders o ON oi.order_id = o.id
        WHERE tc.event_id = events.id AND o.status = 'completed'
    )
WHERE id = 1;
```

### Problème: Tarification dynamique ne se met pas à jour

```sql
-- Vérifier la configuration
SELECT * FROM ticket_categories WHERE id = 2;

-- Tester la fonction
SELECT get_dynamic_price(2);

-- Vérifier les seuils
SELECT 
    quantity_sold,
    pricing_tier_1_threshold,
    pricing_tier_2_threshold,
    pricing_tier_3_threshold
FROM ticket_categories
WHERE id = 2;
```

---

## 📞 SUPPORT

Pour toute question ou problème:
1. Consultez la documentation complète dans `OPTIMISATIONS.md`
2. Vérifiez les comparaisons dans `COMPARAISON-SCHEMAS.md`
3. Ouvrez une issue sur le repository GitHub

---

**Le schéma est prêt à l'emploi!** 🚀

Bon développement! 🎉

