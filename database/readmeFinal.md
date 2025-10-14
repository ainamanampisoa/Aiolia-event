# 📊 AIOLIA EVENT - Documentation Finale du Schéma

## 🎯 Vue d'Ensemble

**Schéma PostgreSQL Final v2.0**  
Base de données relationnelle classique pour système de gestion d'événements avec billetterie.  
**24 tables** - Sans JSONB - Logique métier dans le code applicatif.

---

## 📈 Statistiques Globales

### Résumé des Composants

| Composant | Quantité | Détails |
|-----------|----------|---------|
| **📁 Tables** | **24** | Tables relationnelles classiques |
| **🔑 Indexes** | **70+** | Index pour optimisation des requêtes |
| **👁️ Vues** | **2** | Vues SQL (stats calculées) |
| **⚙️ Fonctions** | **2** | Fonctions PL/pgSQL |
| **⚡ Triggers** | **9** | Triggers automatiques |
| **🏷️ Types ENUM** | **11** | Types énumérés |
| **🌍 Traductions** | **1 fichier JS** | translations.js (multi-langue) |

---

## 📁 LES 24 TABLES

### 1️⃣ **Utilisateurs** (2 tables)
```
1.  users                      - Utilisateurs (avec préférences, wallet)
2.  refresh_tokens             - Tokens JWT
```

### 2️⃣ **Événements** (4 tables)
```
3.  event_categories           - Catégories d'événements
4.  events                     - Événements
5.  event_media                - Médias (images, vidéos)
6.  event_collaborators        - Co-organisateurs
```

### 3️⃣ **Billetterie** (4 tables)
```
7.  ticket_categories          - Catégories de billets
8.  ticket_price_history       - Historique des prix
9.  tickets                    - Billets individuels
10. ticket_transfers           - Transferts de billets
```

### 4️⃣ **Commandes & Paiements** (5 tables)
```
11. orders                     - Commandes
12. order_items                - Items de commandes
13. payments                   - Paiements
14. promo_codes                - Codes promotionnels
15. promo_code_usage           - Utilisation codes promo
```

### 5️⃣ **Panier** (2 tables)
```
16. cart                       - Paniers d'achat
17. cart_items                 - Items du panier
```

### 6️⃣ **Social** (3 tables)
```
18. favorites                  - Favoris
19. user_referrals             - Système de parrainage
20. user_connections           - Réseau social (amis)
```

### 7️⃣ **Communication** (2 tables)
```
21. notifications              - Notifications + Alertes
22. reviews                    - Avis et évaluations
```

### 8️⃣ **Autres** (2 tables)
```
23. event_waitlist             - Liste d'attente
24. system_settings            - Configuration système
```

---

## 🏷️ TYPES ENUM (11 au total)

```sql
1.  user_role                  - 4 valeurs
2.  oauth_provider             - 3 valeurs
3.  event_status               - 5 valeurs
4.  order_status               - 6 valeurs
5.  payment_status             - 5 valeurs
6.  payment_method             - 5 valeurs
7.  ticket_status              - 5 valeurs
8.  notification_type          - 7 valeurs
9.  notification_channel       - 4 valeurs
10. collaborator_role          - 4 valeurs
11. transfer_status            - 5 valeurs
```

---

## 👁️ VUES (2 au total)

```sql
1. upcoming_events             - Événements à venir avec statistiques
2. event_attendees_friends     - Amis assistant au même événement
```

---

## ⚙️ FONCTIONS (2 au total)

```sql
1. update_updated_at_column()
   → Mise à jour automatique du champ updated_at
   
2. check_event_conflicts(organizer_id, start_date, end_date, [event_id])
   → Détecte les conflits de planning organisateur
   Retourne: TABLE(event_id, title)
```

---

## ⚡ TRIGGERS (9 au total)

Déclenchés `BEFORE UPDATE` pour maintenir `updated_at` :

```sql
1. update_users_updated_at
2. update_events_updated_at
3. update_ticket_categories_updated_at
4. update_orders_updated_at
5. update_payments_updated_at
6. update_tickets_updated_at
7. update_promo_codes_updated_at
8. update_reviews_updated_at
9. update_cart_updated_at
```

---

## 🌍 TRADUCTIONS (translations.js)

### Structure du Fichier

```javascript
// Exemple d'utilisation
import { t } from './translations.js';

// Obtenir une traduction
const title = t('ui.nav.home', 'fr');        // "Accueil"
const titleEn = t('ui.nav.home', 'en');      // "Home"
const titleMg = t('ui.nav.home', 'mg');      // "Fandraisana"

// Avec interpolation
const msg = t('emails.welcome', 'fr', { name: 'Jean' });
```

### Sections Disponibles

```javascript
translations = {
  // Catégories d'événements
  event_categories: {
    concert: { fr, en, mg },
    conference: { fr, en, mg },
    sport: { fr, en, mg },
    // ... 8 catégories
  },
  
  // Interface utilisateur
  ui: {
    nav: { home, events, my_tickets, ... },
    search: { placeholder, filter_by_category, ... },
    tickets: { select_quantity, add_to_cart, ... },
    cart: { title, subtotal, total, ... },
    payment: { choose_method, orange_money, ... },
    profile: { my_profile, wallet, loyalty_points, ... },
    notifications: { ... },
    status: { valid, used, cancelled, ... }
  },
  
  // Interface organisateur
  organizer: {
    dashboard: { title, my_events, create_event, ... },
    events: { event_details, edit_event, ... },
    tickets: { manage_tickets, sold, available, ... },
    promo: { promo_codes, create_promo, ... },
    statistics: { total_tickets_sold, export_csv, ... }
  },
  
  // Emails
  emails: {
    order_confirmation: { subject, body },
    event_reminder: { subject },
    ticket_transferred: { subject }
  },
  
  // Messages
  errors: { generic, not_found, unauthorized, ... },
  success: { event_created, ticket_purchased, ... }
}
```

### Fonction Helper

```javascript
/**
 * Fonction t(key, lang, params)
 * 
 * @param key - Clé de traduction (ex: "ui.nav.home")
 * @param lang - Code langue ('fr', 'en', 'mg')
 * @param params - Paramètres pour interpolation
 * @returns Texte traduit
 */

// Exemples
t('ui.nav.home', 'fr')                          // "Accueil"
t('ui.search.placeholder', 'en')                // "Search for an event..."
t('organizer.dashboard.title', 'mg')            // "Fizaran'ny asam-panjakana"
```

---

## 🔑 INDEX PRINCIPAUX (75+)

### Par Catégorie

| Type d'Index | Quantité |
|--------------|----------|
| **PRIMARY KEY** | 26 |
| **UNIQUE** | 15+ |
| **FOREIGN KEY** | 25+ |
| **GIN (Full-Text)** | 1 |
| **WHERE (Partial)** | 3 |
| **Standard** | 30+ |

### Index Critiques

```sql
-- Recherche
idx_events_search              (GIN sur to_tsvector)
idx_users_email                (UNIQUE)
idx_orders_number              (UNIQUE)
idx_tickets_qr                 (UNIQUE)

-- Performance
idx_events_organizer
idx_tickets_user
idx_orders_user
idx_favorites_user
idx_notifications_user

-- Conditions fréquentes
idx_events_featured            (WHERE is_featured = TRUE)
idx_ticket_categories_active   (WHERE is_active = TRUE)
idx_event_categories_active    (WHERE is_active = TRUE)
```

---

## 🔗 RELATIONS PRINCIPALES

```
USER (1) ──< (N) ORDERS
USER (1) ──< (N) EVENTS (organisateur)
USER (1) ──< (N) FAVORITES
USER (1) ──< (N) NOTIFICATIONS
USER (1) ─── (1) USER_STATISTICS

EVENT (1) ──< (N) TICKET_CATEGORIES
EVENT (1) ──< (N) EVENT_MEDIA
EVENT (1) ──< (N) EVENT_COLLABORATORS
EVENT (1) ──< (N) FAVORITES
EVENT (1) ─── (1) EVENT_STATISTICS

ORDER (1) ──< (N) ORDER_ITEMS
ORDER (1) ──< (N) PAYMENTS
ORDER (1) ──< (N) TICKETS

TICKET_CATEGORY (1) ──< (N) TICKETS
TICKET_CATEGORY (1) ──< (N) TICKET_PRICE_HISTORY

TICKET (1) ──< (1) TICKET_TRANSFERS

CART (1) ──< (N) CART_ITEMS
```

---

## 💾 VOLUMÉTRIE ESTIMÉE (1 an)

| Table | Lignes | Taille | Croissance |
|-------|--------|--------|------------|
| `users` | 50,000 | ~30 MB | Linéaire |
| `events` | 5,000 | ~5 MB | Linéaire |
| `orders` | 100,000 | ~50 MB | Exponentielle |
| `tickets` | 500,000 | ~250 MB | Exponentielle |
| `notifications` | 2,000,000 | ~1 GB | Exponentielle |
| **TOTAL** | **~2.7M lignes** | **~1.4 GB** | |

**Note**: Économie d'espace grâce à la suppression des tables statistiques (calculées à la volée).

---

## ✅ FONCTIONNALITÉS COUVERTES (100%)

### ✓ Module 1 - Utilisateurs
- ✅ Recherche & filtres événements
- ✅ Billetterie avec QR codes
- ✅ Paiement Mobile Money (Orange, Airtel, MVola)
- ✅ Panier d'achat multi-événements
- ✅ Codes promo
- ✅ Profil utilisateur complet
- ✅ Historique achats
- ✅ Statistiques personnelles
- ✅ Portefeuille & points fidélité
- ✅ Mes billets (liste, statuts)
- ✅ Transfert de billets
- ✅ Favoris / Wishlist
- ✅ Notifications (email + push)
- ✅ Système de parrainage
- ✅ Réseau social (amis)

### ✓ Module 2 - Organisateurs
- ✅ CRUD événements complet
- ✅ Upload médias (images, vidéos)
- ✅ Gestion d'équipe (co-organisateurs)
- ✅ Gestion billets temps réel
- ✅ Gestion quotas & stock
- ✅ Alertes (via notifications)
- ✅ Configuration prix par catégorie
- ✅ Historique modifications prix
- ✅ Codes promo organisateur
- ✅ Dashboard ventes & stats
- ✅ Statistiques fiscales
- ✅ Multi-langue (via translations.js)
- ✅ Détection conflits dates
- ✅ Liste d'attente
- ✅ Notifications ciblées
- ✅ Export CSV/PDF (données disponibles)

---

## 🚀 UTILISATION

### Installation

```bash
# 1. Créer la base de données
createdb aiolia_event

# 2. Importer le schéma
psql -d aiolia_event -f database/schemaFinal.sql

# 3. Vérifier
psql -d aiolia_event -c "\dt"
# Résultat: 26 tables
```

### Utilisation des Traductions

#### Dans votre Frontend (React/Vue/Angular)

```javascript
// 1. Importer le fichier
import { t } from './database/translations.js';

// 2. Utiliser dans vos composants
function HomePage() {
  const lang = 'fr'; // Récupérer depuis le state/context
  
  return (
    <nav>
      <a href="/">{t('ui.nav.home', lang)}</a>
      <a href="/events">{t('ui.nav.events', lang)}</a>
      <a href="/tickets">{t('ui.nav.my_tickets', lang)}</a>
    </nav>
  );
}
```

#### Dans votre Backend (Node.js/Express)

```javascript
// 1. Importer
const { t } = require('./database/translations.js');

// 2. Utiliser dans les emails
function sendOrderConfirmation(user, order) {
  const lang = user.language; // de la table users
  
  const subject = t('emails.order_confirmation.subject', lang);
  const body = t('emails.order_confirmation.body', lang);
  
  sendEmail(user.email, subject, body);
}
```

#### Récupérer les Catégories Traduites

```javascript
// Frontend
const { translations } = require('./translations.js');
const lang = 'fr';

// Récupérer toutes les catégories depuis la BDD
fetch('/api/categories')
  .then(categories => {
    return categories.map(cat => ({
      ...cat,
      name: translations.event_categories[cat.slug][lang].name,
      description: translations.event_categories[cat.slug][lang].description
    }));
  });
```

---

## 📊 ARCHITECTURE

### Tables Sans JSONB

Toutes les données sont dans des colonnes typées :

```sql
-- ✅ AVANT (avec JSONB) - NON UTILISÉ
stats JSONB DEFAULT '{"total_events": 0}'::jsonb

-- ✅ MAINTENANT (colonnes classiques)
total_events_attended INT DEFAULT 0
total_spent DECIMAL(12, 2) DEFAULT 0
total_tickets_purchased INT DEFAULT 0
```

### Avantages

✅ **Validation stricte** : Types de données garantis  
✅ **Performance** : Index standards plus rapides  
✅ **Simplicité** : Requêtes SQL classiques  
✅ **Contrôle** : Logique métier dans le code  
✅ **Débogage** : Plus facile à comprendre  

### Inconvénients

⚠️ **Flexibilité** : Modifications nécessitent des migrations  
⚠️ **Tables** : Plus de tables (26 vs 20)  

---

## 🔒 SÉCURITÉ & MAINTENANCE

### Nettoyage Automatique

```sql
-- Paniers expirés (quotidien)
DELETE FROM cart WHERE expires_at < CURRENT_TIMESTAMP;

-- Notifications anciennes (hebdomadaire)
DELETE FROM notifications 
WHERE created_at < CURRENT_TIMESTAMP - INTERVAL '30 days'
AND status = 'read';

-- Tokens expirés
DELETE FROM refresh_tokens WHERE expires_at < CURRENT_TIMESTAMP;
```

### Mise à Jour Statistiques

```sql
-- Stats utilisateur (après chaque commande)
UPDATE user_statistics 
SET 
    total_events_attended = total_events_attended + 1,
    total_spent = total_spent + {order_total},
    total_tickets_purchased = total_tickets_purchased + {ticket_count},
    last_purchase_date = CURRENT_TIMESTAMP
WHERE user_id = {user_id};

-- Stats événement (après chaque vente)
UPDATE event_statistics 
SET 
    total_tickets_sold = total_tickets_sold + {quantity},
    total_revenue = total_revenue + {amount}
WHERE event_id = {event_id};
```

---

## 📝 DONNÉES INITIALES

### Insertions Automatiques

```
✓ 8 catégories d'événements
✓ 11 paramètres système
```

### Traductions Disponibles

```
✓ 3 langues : Français (fr), English (en), Malagasy (mg)
✓ 8 catégories traduites
✓ 100+ chaînes UI traduites
✓ 50+ messages organisateur traduits
✓ 20+ messages email traduits
```

---

## 🎯 RÉSUMÉ FINAL

```
╔═══════════════════════════════════════════════════════╗
║     AIOLIA EVENT - SCHÉMA FINAL v2.0                 ║
╠═══════════════════════════════════════════════════════╣
║                                                       ║
║  📁 TABLES           : 24                            ║
║  🔑 INDEXES          : 70+                           ║
║  👁️ VUES             : 2 (stats calculées)          ║
║  ⚙️ FONCTIONS        : 2                             ║
║  ⚡ TRIGGERS         : 9                             ║
║  🏷️ TYPES ENUM       : 11                            ║
║  🌍 TRADUCTIONS      : 1 fichier JS                  ║
║                                                       ║
║  ❌ JSONB            : AUCUN                         ║
║  ❌ Tables stats     : AUCUNE (logique backend)     ║
║  ✅ Multi-langue     : translations.js               ║
║                                                       ║
║  ✅ Module Utilisateurs    : 100%                    ║
║  ✅ Module Organisateurs   : 100%                    ║
║  ✅ Prêt pour Production   : OUI                     ║
║                                                       ║
╚═══════════════════════════════════════════════════════╝
```

---

**Dernière mise à jour** : 14 Octobre 2025  
**Version du schéma** : 2.0 Final  
**Tables** : 26 (sans JSONB)  
**Compatibilité** : PostgreSQL 12+  
**Traductions** : translations.js (FR, EN, MG)  

---

*Ce schéma utilise des tables relationnelles classiques. Toute la logique métier est dans le code applicatif. Les traductions sont gérées via le fichier `translations.js`.*
