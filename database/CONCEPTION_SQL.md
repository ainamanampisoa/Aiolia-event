# 📊 CONCEPTION SQL - AIOLIA EVENT

## 🎯 Vue d'ensemble

Cette documentation présente l'architecture complète de la base de données pour **Aiolia Event**, une plateforme de gestion d'événements et de billetterie avec des fonctionnalités avancées.

---

## ✅ MON ANALYSE DE LA CONCEPTION

### 🌟 **Points Forts de cette Architecture**

#### 1. **Modularité & Évolutivité**
- ✅ Architecture modulaire avec 20 modules distincts
- ✅ Facilite l'ajout de nouvelles fonctionnalités
- ✅ Chaque module peut évoluer indépendamment
- ✅ Support multi-langue natif via table de traductions

#### 2. **Performance & Optimisation**
- ✅ **Index stratégiques** sur toutes les colonnes fréquemment recherchées
- ✅ **Index composites** pour requêtes complexes
- ✅ **Index FULLTEXT** pour recherche textuelle performante
- ✅ **Vues matérialisées** pour statistiques pré-calculées
- ✅ **Statistiques pré-calculées** pour éviter les calculs lourds en temps réel

#### 3. **Intégrité des Données**
- ✅ **Contraintes de clés étrangères** sur toutes les relations
- ✅ **Constraints CHECK** pour validation des données
- ✅ **Triggers automatiques** pour cohérence des données
- ✅ **Audit trail complet** via table `audit_logs`
- ✅ **Soft deletes** possibles via colonnes de statut

#### 4. **Gestion des Transactions**
- ✅ Workflow complet de commande : pending → processing → completed
- ✅ Gestion des paiements séparée des commandes
- ✅ Support multi-paiement (Mobile Money : Orange, Airtel, Telma)
- ✅ Historique complet des transactions
- ✅ Génération automatique de factures

#### 5. **Fonctionnalités Business Avancées**
- ✅ **Tarification dynamique** basée sur la demande
- ✅ **Programme de fidélité** avec points et tiers
- ✅ **Système de parrainage** avec récompenses
- ✅ **Codes promo** flexibles et ciblés
- ✅ **Liste d'attente** automatique pour événements complets
- ✅ **Transfert de billets** entre utilisateurs
- ✅ **Mini-jeu gamifié** pour engagement utilisateur

#### 6. **Analytics & Reporting**
- ✅ Statistiques en temps réel par événement
- ✅ Statistiques utilisateur détaillées
- ✅ Statistiques de ventes quotidiennes
- ✅ Métriques business (taux de conversion, panier moyen)
- ✅ Génération automatique de rapports
- ✅ Export multi-format (PDF, CSV, Excel)

#### 7. **UX & Engagement**
- ✅ Panier persistant (même après déconnexion)
- ✅ Historique de recherche pour recommandations
- ✅ Favoris/Wishlist
- ✅ Système d'amis pour voir qui va aux mêmes événements
- ✅ Notifications multi-canal (email, push, SMS, in-app)
- ✅ Préférences de notifications personnalisables

#### 8. **Sécurité & Conformité**
- ✅ Gestion avancée des rôles et permissions
- ✅ Tokens JWT avec refresh tokens
- ✅ Support OAuth (Google, Facebook)
- ✅ Logs d'audit pour traçabilité
- ✅ Vérification email et téléphone
- ✅ Historique de prix pour transparence fiscale

---

## 🏗️ ARCHITECTURE DE LA BASE DE DONNÉES

### 📦 Modules Principaux

```
┌─────────────────────────────────────────────────────────────┐
│                    AIOLIA EVENT DATABASE                    │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  1. AUTHENTIFICATION & UTILISATEURS                        │
│     ├── users                                              │
│     ├── refresh_tokens                                     │
│     ├── permissions                                        │
│     └── role_permissions                                   │
│                                                             │
│  2. CATÉGORIES & ÉVÉNEMENTS                                │
│     ├── event_categories                                   │
│     ├── events                                             │
│     ├── event_media                                        │
│     └── event_team                                         │
│                                                             │
│  3. BILLETS                                                │
│     ├── ticket_categories                                  │
│     ├── tickets                                            │
│     ├── ticket_price_history                               │
│     ├── dynamic_pricing_rules                              │
│     └── ticket_transfers                                   │
│                                                             │
│  4. CODES PROMO                                            │
│     ├── promo_codes                                        │
│     ├── promo_code_events                                  │
│     ├── promo_code_ticket_categories                       │
│     └── promo_code_usage                                   │
│                                                             │
│  5. COMMANDES & PAIEMENTS                                  │
│     ├── orders                                             │
│     ├── order_items                                        │
│     ├── payments                                           │
│     └── invoices                                           │
│                                                             │
│  6. PANIER                                                 │
│     ├── cart                                               │
│     └── cart_items                                         │
│                                                             │
│  7. FAVORIS & INTERACTIONS                                 │
│     ├── favorites                                          │
│     ├── search_history                                     │
│     └── event_views                                        │
│                                                             │
│  8. PORTEFEUILLE & FIDÉLITÉ                                │
│     ├── wallet                                             │
│     ├── wallet_transactions                                │
│     └── loyalty_rules                                      │
│                                                             │
│  9. PARRAINAGE                                             │
│     └── referrals                                          │
│                                                             │
│  10. MINI-JEU                                              │
│     ├── game_participations                                │
│     └── game_settings                                      │
│                                                             │
│  11. SOCIAL                                                │
│     ├── friendships                                        │
│     └── friend_events                                      │
│                                                             │
│  12. LISTE D'ATTENTE                                       │
│     └── waiting_list                                       │
│                                                             │
│  13. NOTIFICATIONS                                         │
│     ├── notifications                                      │
│     └── notification_preferences                           │
│                                                             │
│  14. AVIS & ÉVALUATIONS                                    │
│     ├── reviews                                            │
│     └── review_votes                                       │
│                                                             │
│  15. STATISTIQUES                                          │
│     ├── event_statistics                                   │
│     ├── user_statistics                                    │
│     └── daily_sales_stats                                  │
│                                                             │
│  16. RAPPORTS                                              │
│     └── reports                                            │
│                                                             │
│  17. AUDIT & LOGS                                          │
│     └── audit_logs                                         │
│                                                             │
│  18. CONFIGURATION                                         │
│     └── system_settings                                    │
│                                                             │
│  19. MULTI-LANGUE                                          │
│     └── translations                                       │
│                                                             │
│  20. VUES OPTIMISÉES                                       │
│     ├── upcoming_events_with_stats                         │
│     ├── top_rated_events                                   │
│     └── organizer_dashboard                                │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔄 FLUX DE DONNÉES PRINCIPAUX

### 1. 🎫 Flux d'Achat de Billet

```
┌──────────────┐
│  Utilisateur │
└──────┬───────┘
       │ Parcourt les événements
       v
┌──────────────┐
│   events     │ ←──┐
│   +views     │    │ Enregistre vues
└──────┬───────┘    │
       │            │
       v            │
┌──────────────────┐│
│  event_views     ││
└──────────────────┘│
       │            │
       │ Ajoute au panier
       v            │
┌──────────────┐   │
│     cart     │   │
│  cart_items  │   │
└──────┬───────┘   │
       │ Réserve temporairement
       v            │
┌──────────────────┐│
│ticket_categories ││
│ +quantity_reserved│
└──────────────────┘
       │
       │ Crée commande
       v
┌──────────────┐
│    orders    │
│ order_items  │
└──────┬───────┘
       │ Traite paiement
       v
┌──────────────┐
│   payments   │
└──────┬───────┘
       │ Si succès
       v
┌──────────────┐
│   tickets    │ ← Génère billets avec QR
│  (status:    │
│   valid)     │
└──────┬───────┘
       │
       v
┌──────────────┐
│  invoices    │ ← Génère facture
└──────┬───────┘
       │
       v
┌──────────────┐
│notifications │ ← Email + SMS
└──────────────┘
```

### 2. 💰 Flux de Tarification Dynamique

```
┌──────────────────┐
│  Ticket vendu    │
└────────┬─────────┘
         │
         v
┌──────────────────────┐
│ TRIGGER:             │
│ after_ticket_insert  │
└────────┬─────────────┘
         │
         v
┌──────────────────────┐
│ ticket_categories    │
│ quantity_sold++      │
└────────┬─────────────┘
         │
         │ Calcule %
         v
┌──────────────────────┐
│ dynamic_pricing_     │
│ rules                │ ← Trouve règle applicable
└────────┬─────────────┘
         │
         v
┌──────────────────────┐
│ Nouveau prix =       │
│ original_price *     │
│ multiplier           │
└────────┬─────────────┘
         │
         v
┌──────────────────────┐
│ ticket_price_history │ ← Enregistre changement
└──────────────────────┘
```

### 3. 🎁 Flux de Fidélité

```
┌──────────────┐
│ Commande     │
│ complétée    │
└──────┬───────┘
       │
       v
┌─────────────────────┐
│ TRIGGER:            │
│ after_order_loyalty │
│ _points             │
└──────┬──────────────┘
       │
       │ Calcule points
       v
┌──────────────────────┐
│ wallet_transactions  │
│ type: credit         │
└──────┬───────────────┘
       │
       v
┌──────────────────────┐
│ TRIGGER:             │
│ after_wallet_        │
│ transaction          │
└──────┬───────────────┘
       │
       v
┌──────────────────────┐
│ wallet               │
│ loyalty_points++     │
│ total_earned++       │
└──────────────────────┘
```

---

## 📊 STRATÉGIES D'OPTIMISATION

### 1. Index Stratégiques

```sql
-- Recherche d'événements (requête la plus fréquente)
CREATE INDEX idx_events_search ON events(status, start_date, category_id);

-- Recherche full-text
CREATE FULLTEXT INDEX idx_search ON events(title, description, location);

-- Billets par utilisateur
CREATE INDEX idx_tickets_user_status ON tickets(user_id, status);

-- Commandes par utilisateur
CREATE INDEX idx_orders_user_status ON orders(user_id, status, created_at);

-- Notifications non lues
CREATE INDEX idx_notifications_user_unread ON notifications(user_id, status, created_at);
```

### 2. Statistiques Pré-calculées

Au lieu de calculer en temps réel :
```sql
-- ❌ LENT (calcul en temps réel)
SELECT COUNT(*) FROM tickets WHERE event_id = 123;

-- ✅ RAPIDE (statistique pré-calculée)
SELECT total_tickets_sold FROM event_statistics WHERE event_id = 123;
```

Les triggers maintiennent automatiquement ces statistiques à jour.

### 3. Vues Optimisées

```sql
-- Vue pour événements à venir (requête fréquente)
CREATE VIEW upcoming_events_with_stats AS
SELECT e.*, es.total_tickets_sold, es.average_rating
FROM events e
LEFT JOIN event_statistics es ON e.id = es.event_id
WHERE e.status = 'published' AND e.start_date > NOW();
```

---

## 🔐 SÉCURITÉ & PERMISSIONS

### Système de Rôles Hiérarchique

```
┌──────────────────────────────────────────┐
│              ADMIN                       │
│  (Toutes les permissions)                │
└────────────────┬─────────────────────────┘
                 │
      ┌──────────┴──────────┐
      │                     │
┌─────▼──────┐      ┌───────▼────────┐
│ ORGANIZER  │      │ CO-ORGANIZER   │
│            │      │                │
│ • Créer    │      │ • Modifier     │
│ • Éditer   │      │ • Scanner      │
│ • Supprimer│      │ • Stats        │
│ • Publier  │      │                │
│ • Team     │      │                │
│ • Rapports │      │                │
└─────┬──────┘      └────────────────┘
      │
┌─────▼─────────────────────────────┐
│            USER                   │
│  (Utilisateur standard)           │
│  • Voir événements                │
│  • Acheter billets                │
│  • Voir ses commandes             │
└───────────────────────────────────┘
```

### Permissions Granulaires

```sql
-- Vérification des permissions
SELECT * FROM role_permissions 
WHERE role = 'organizer' 
  AND permission_id IN (
    SELECT id FROM permissions WHERE name = 'event.publish'
  );
```

---

## 🎯 FONCTIONNALITÉS AVANCÉES

### 1. Tarification Dynamique

**Principe** : Le prix augmente automatiquement quand un certain % de billets est vendu.

**Exemple** :
```
Prix initial : 50 000 MGA
├── 50% vendus  → +20% = 60 000 MGA
├── 75% vendus  → +30% = 65 000 MGA
└── 90% vendus  → +50% = 75 000 MGA
```

**Avantages** :
- ✅ Maximise les revenus
- ✅ Récompense les acheteurs précoces
- ✅ Gère automatiquement la demande
- ✅ Historique de prix transparent

### 2. Système de Réservation Temporaire

**Problème** : Deux utilisateurs veulent acheter le dernier billet en même temps.

**Solution** : Réservation temporaire via le panier
```sql
-- Quand ajouté au panier
quantity_reserved++  -- Bloque temporairement

-- Après 15 minutes (configurable)
quantity_reserved--  -- Libère automatiquement

-- Après achat confirmé
quantity_sold++      -- Vente définitive
quantity_reserved--  -- Libère la réservation
```

### 3. Liste d'Attente Intelligente

**Fonctionnement** :
1. Événement complet → utilisateur s'inscrit à liste d'attente
2. Annulation ou ajout de billets → notification automatique
3. Système de priorité (ordre d'inscription + score)
4. Expiration automatique (24h pour acheter)

```sql
-- Procédure automatique
CALL notify_waiting_list(ticket_category_id, available_quantity);
```

### 4. Transfert de Billets

**Use case** : Un utilisateur ne peut plus venir, il transfère son billet.

**Workflow** :
```
1. Création du transfert (status: pending)
2. Email envoyé au destinataire avec token unique
3. Destinataire accepte → billet transféré (status: accepted)
4. Historique complet dans ticket_transfers
5. Audit trail dans audit_logs
```

### 5. Gamification : Mini-Jeu "Ticket Chance"

**Objectif** : Engagement utilisateur

**Mécanisme** :
- 1 partie gratuite par jour
- Lots possibles :
  - 30% chance → Code promo
  - 5% chance → Billet gratuit
  - 50% chance → Points de fidélité
  - 15% chance → Rien

**Tables impliquées** :
- `game_participations` : Historique des parties
- `game_settings` : Configuration des probabilités
- `wallet_transactions` : Attribution des points

### 6. Portefeuille Numérique

**Fonctionnalités** :
- Points de fidélité cumulables
- Conversion points → argent (optionnel)
- Historique complet des transactions
- Tiers de fidélité : Bronze → Silver → Gold → Platinum

**Exemples de gains de points** :
- 1 point / 1000 MGA dépensé
- 50 points / parrainage réussi
- 5 points / avis publié
- 10 points / gain au jeu

---

## 📈 ANALYTICS & BUSINESS INTELLIGENCE

### Métriques Clés Trackées

#### Pour les Organisateurs
```sql
-- Dashboard organisateur
SELECT 
    total_events,
    published_events,
    upcoming_events,
    total_tickets_sold,
    total_revenue,
    average_event_rating
FROM organizer_dashboard
WHERE organizer_id = ?;
```

#### Pour les Événements
```sql
-- Statistiques événement
SELECT 
    total_views,
    unique_views,
    total_tickets_sold,
    total_revenue,
    conversion_rate,          -- % visiteurs qui achètent
    average_cart_value,       -- Panier moyen
    refund_rate,              -- Taux de remboursement
    average_rating
FROM event_statistics
WHERE event_id = ?;
```

#### KPIs Business
- **Taux de conversion** : `(acheteurs / visiteurs) * 100`
- **Panier moyen** : `total_revenue / nombre_commandes`
- **LTV (Lifetime Value)** : `total_dépensé / utilisateur`
- **Taux de remplissage** : `billets_vendus / capacité_totale`

### Rapports Automatiques

**Post-événement** :
- Billets vendus par catégorie
- Revenus nets et bruts
- Participants check-in vs no-show
- Taux de satisfaction (avis)
- Comparaison avec événements similaires

**Fiscaux** :
- TVA collectée
- Revenus nets
- Commission plateforme
- Paiement organisateur

---

## ⚠️ POINTS D'ATTENTION & RECOMMANDATIONS

### 1. Performance

**Problèmes potentiels** :
- ⚠️ Table `audit_logs` peut devenir très volumineuse
- ⚠️ Calcul de statistiques en temps réel coûteux
- ⚠️ Recherche full-text sur grandes tables

**Solutions** :
```sql
-- Archivage automatique des logs après 1 an
CREATE EVENT archive_old_audit_logs
ON SCHEDULE EVERY 1 MONTH
DO
  DELETE FROM audit_logs 
  WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);

-- Recalcul des stats en batch (CRON job)
CALL calculate_event_statistics(event_id);

-- Index full-text pour recherche rapide
ALTER TABLE events ADD FULLTEXT INDEX idx_search (title, description, location);
```

### 2. Scalabilité

**Pour forte charge** :
- ✅ Mettre en cache les statistiques (Redis)
- ✅ Queue pour notifications (RabbitMQ/SQS)
- ✅ CDN pour médias (AWS S3 + CloudFront)
- ✅ Read replicas pour requêtes de lecture
- ✅ Sharding par région géographique

### 3. Intégrité des Données

**Transactions critiques** :
```sql
-- Exemple : Achat de billet (atomique)
START TRANSACTION;

-- 1. Créer commande
INSERT INTO orders (...);

-- 2. Vérifier disponibilité
SELECT quantity_available FROM ticket_categories WHERE id = ? FOR UPDATE;

-- 3. Générer billets
INSERT INTO tickets (...);

-- 4. Traiter paiement
INSERT INTO payments (...);

COMMIT;  -- Tout réussit ou tout échoue
```

### 4. Sécurité

**Best practices** :
- ✅ Ne jamais stocker de CB en clair (PCI DSS)
- ✅ Hash bcrypt pour mots de passe
- ✅ Tokens JWT avec expiration courte
- ✅ Refresh tokens révocables
- ✅ Rate limiting sur API
- ✅ Input validation côté serveur
- ✅ HTTPS obligatoire

### 5. RGPD & Confidentialité

**Conformité** :
- ✅ Droit à l'oubli : `DELETE FROM users WHERE id = ?`
- ✅ Export données : Procédure `export_user_data(user_id)`
- ✅ Consentement : Table `user_consents`
- ✅ Anonymisation des logs après N jours

---

## 🚀 DÉPLOIEMENT & MAINTENANCE

### Scripts d'Installation

```bash
# 1. Créer la base
mysql -u root -p < schema.sql

# 2. Créer les triggers
mysql -u root -p aiolia_event < triggers.sql

# 3. Créer les procédures
mysql -u root -p aiolia_event < procedures.sql

# 4. Insérer les données de base
mysql -u root -p aiolia_event < seeds.sql
```

### Jobs CRON Recommandés

```bash
# Recalcul des statistiques (chaque heure)
0 * * * * mysql -e "CALL calculate_all_event_statistics();"

# Statistiques quotidiennes (chaque jour à 1h du matin)
0 1 * * * mysql -e "CALL generate_daily_sales_stats(CURDATE() - INTERVAL 1 DAY);"

# Nettoyage des paniers expirés (chaque 15 min)
*/15 * * * * mysql -e "DELETE FROM cart WHERE expires_at < NOW();"

# Notifications de rappel 24h avant événement
0 10 * * * php /path/to/send_event_reminders.php

# Archivage des logs (chaque mois)
0 0 1 * * mysql -e "DELETE FROM audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);"
```

### Monitoring Recommandé

**Métriques à surveiller** :
- Taille des tables (surtout `audit_logs`, `event_views`, `notifications`)
- Temps de réponse des requêtes lentes
- Taux d'erreur des paiements
- Capacité CPU/RAM/Disk
- Connexions actives

**Alertes** :
- Stock bas de billets
- Paiement échoué après N tentatives
- Événement sans vente depuis N jours
- Erreurs critiques (système de paiement down)

---

## 📚 EXEMPLES DE REQUÊTES MÉTIER

### Tableau de bord organisateur

```sql
SELECT 
    e.title,
    e.start_date,
    es.total_tickets_sold,
    es.total_revenue,
    es.conversion_rate,
    (SELECT SUM(quantity_total - quantity_sold) 
     FROM ticket_categories 
     WHERE event_id = e.id) as remaining_tickets,
    es.average_rating
FROM events e
LEFT JOIN event_statistics es ON es.event_id = e.id
WHERE e.organizer_id = ?
  AND e.status = 'published'
ORDER BY e.start_date DESC;
```

### Top événements par catégorie

```sql
SELECT 
    ec.name as category,
    e.title,
    es.total_tickets_sold,
    es.average_rating,
    es.total_revenue
FROM events e
INNER JOIN event_categories ec ON e.category_id = ec.id
INNER JOIN event_statistics es ON es.event_id = e.id
WHERE e.status = 'published'
  AND e.start_date > NOW()
ORDER BY ec.name, es.total_revenue DESC;
```

### Utilisateurs les plus actifs

```sql
SELECT 
    u.first_name,
    u.last_name,
    us.total_events_attended,
    us.total_spent,
    us.loyalty_tier,
    w.loyalty_points
FROM users u
INNER JOIN user_statistics us ON us.user_id = u.id
INNER JOIN wallet w ON w.user_id = u.id
ORDER BY us.total_spent DESC
LIMIT 50;
```

### Événements bientôt complets

```sql
SELECT 
    e.title,
    tc.name as ticket_category,
    tc.quantity_total,
    tc.quantity_sold,
    ((tc.quantity_total - tc.quantity_sold) * 100.0 / tc.quantity_total) as remaining_percentage
FROM events e
INNER JOIN ticket_categories tc ON tc.event_id = e.id
WHERE e.status = 'published'
  AND e.start_date > NOW()
  AND (tc.quantity_total - tc.quantity_sold) <= (tc.quantity_total * 0.1)
ORDER BY remaining_percentage ASC;
```

---

## 🎨 DIAGRAMME ER (Entité-Relations)

```
┌──────────────┐       ┌──────────────┐       ┌──────────────┐
│    users     │──1:N──│    events    │──1:N──│ticket_       │
│              │       │              │       │categories    │
│ • id         │       │ • id         │       │              │
│ • email      │       │ • title      │       │ • id         │
│ • role       │       │ • organizer_id│      │ • price      │
└──────┬───────┘       └──────┬───────┘       └──────┬───────┘
       │                      │                      │
       │ 1:N                  │ 1:N                  │ 1:N
       │                      │                      │
       v                      v                      v
┌──────────────┐       ┌──────────────┐       ┌──────────────┐
│   orders     │──1:N──│ order_items  │       │   tickets    │
│              │       │              │       │              │
│ • id         │       │ • ticket_cat │       │ • qr_code    │
│ • total      │       └──────────────┘       │ • status     │
└──────┬───────┘                              └──────────────┘
       │ 1:N
       v
┌──────────────┐
│   payments   │
│              │
│ • method     │
│ • status     │
└──────────────┘
```

---

## 💡 CONCLUSION

### Forces de cette Conception

1. ✅ **Complète** : Couvre tous les besoins du cahier des charges
2. ✅ **Scalable** : Architecture modulaire et optimisée
3. ✅ **Performante** : Index stratégiques et statistiques pré-calculées
4. ✅ **Sécurisée** : Permissions granulaires et audit trail
5. ✅ **Flexible** : Support multi-langue, multi-devise, multi-paiement
6. ✅ **Business-ready** : Analytics avancées et rapports automatiques
7. ✅ **User-friendly** : Gamification, recommandations, notifications
8. ✅ **Maintainable** : Triggers et procédures pour logique métier

### Axes d'Amélioration Possibles

1. 🔄 **Microservices** : Séparer en plusieurs BDD (events, payments, users) si très forte charge
2. 📊 **Data warehouse** : ETL vers un DW pour analytics historiques
3. 🔍 **Elasticsearch** : Pour recherche full-text ultra-performante
4. 📱 **NoSQL** : Redis pour cache, MongoDB pour données non structurées (logs)
5. 🌍 **Géo-réplication** : Répliquer la BDD par zone géographique

### Prochaines Étapes Recommandées

1. ✅ Valider le schéma avec les stakeholders
2. ✅ Créer un environnement de dev/staging/prod
3. ✅ Implémenter les APIs backend (REST/GraphQL)
4. ✅ Développer les interfaces frontend
5. ✅ Tests de charge et optimisation
6. ✅ Plan de migration et sauvegarde
7. ✅ Documentation API et guides utilisateur

---

## 📞 SUPPORT

Pour toute question sur cette conception :

- 📧 Email : support@aiolia-event.com
- 📚 Documentation : /docs
- 🐛 Issues : /github/issues

---

**Dernière mise à jour** : Octobre 2025
**Version** : 1.0.0
**Auteur** : Équipe Aiolia Event

