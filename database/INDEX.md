# 📚 Index de la Documentation - Base de Données Aiolia Event

Ce document est votre point d'entrée pour naviguer dans toute la documentation de la base de données.

---

## 🗂️ Structure des Fichiers

### 📄 Documentation

| Fichier | Description | Public Cible |
|---------|-------------|--------------|
| **[README.md](README.md)** | Guide de démarrage et maintenance | Tous |
| **[CONCEPTION_SQL.md](CONCEPTION_SQL.md)** | Analyse complète de la conception | Architectes, Développeurs |
| **[ARCHITECTURE_DIAGRAM.md](ARCHITECTURE_DIAGRAM.md)** | Diagrammes visuels de l'architecture | Tous |
| **[MIGRATION_GUIDE.md](MIGRATION_GUIDE.md)** | Guide de migration et déploiement | DevOps, DBA |
| **[INDEX.md](INDEX.md)** | Ce fichier - Index de navigation | Tous |

### 🔧 Scripts SQL

| Fichier | Taille | Description | Ordre d'Exécution |
|---------|--------|-------------|-------------------|
| **[schema.sql](schema.sql)** | ~80 KB | Schéma complet (60+ tables) | 1️⃣ |
| **[triggers.sql](triggers.sql)** | ~15 KB | 30+ triggers automatiques | 2️⃣ |
| **[procedures.sql](procedures.sql)** | ~20 KB | 15+ procédures stockées | 3️⃣ |
| **[seeds.sql](seeds.sql)** | ~10 KB | Données de base et test | 4️⃣ |
| **[indexes_optimization.sql](indexes_optimization.sql)** | ~8 KB | Index de performance | 5️⃣ (optionnel) |

---

## 🚀 Démarrage Rapide

### Pour les Développeurs

```bash
# 1. Lire d'abord
cat README.md

# 2. Installer la base de données
mysql -u root -p < schema.sql
mysql -u root -p < triggers.sql
mysql -u root -p < procedures.sql
mysql -u root -p < seeds.sql

# 3. Consulter la conception
open CONCEPTION_SQL.md
```

### Pour les Architectes

```bash
# 1. Comprendre l'architecture
open CONCEPTION_SQL.md

# 2. Visualiser les diagrammes
open ARCHITECTURE_DIAGRAM.md

# 3. Consulter les scripts
ls -lh *.sql
```

### Pour les DevOps

```bash
# 1. Guide de migration
open MIGRATION_GUIDE.md

# 2. Scripts d'optimisation
cat indexes_optimization.sql

# 3. Configuration serveur
grep "my.cnf" README.md
```

---

## 📊 Statistiques du Projet

### Base de Données

- **Tables** : 60+
- **Colonnes totales** : 500+
- **Triggers** : 30+
- **Procédures stockées** : 15+
- **Contraintes FK** : 100+
- **Index** : 150+
- **Vues** : 3

### Modules

| Module | Tables | Description |
|--------|--------|-------------|
| Authentification | 4 | Users, tokens, permissions |
| Événements | 4 | Events, categories, media, teams |
| Billets | 5 | Tickets, categories, pricing, transfers |
| Codes Promo | 4 | Promo codes, usage tracking |
| Commandes | 4 | Orders, items, payments, invoices |
| Panier | 2 | Cart, cart items |
| Interactions | 3 | Favorites, search, views |
| Fidélité | 3 | Wallet, transactions, rules |
| Social | 3 | Referrals, friendships, friend events |
| Gamification | 2 | Game participations, settings |
| Notifications | 2 | Notifications, preferences |
| Avis | 2 | Reviews, votes |
| Statistiques | 3 | Event, user, daily stats |
| Infrastructure | 5 | Reports, audit, settings, translations |
| **TOTAL** | **60+** | |

---

## 🎯 Parcours par Rôle

### 👨‍💻 Je suis Développeur Backend

**Ordre de lecture recommandé :**

1. ✅ [README.md](README.md) - Installation et configuration
2. ✅ [schema.sql](schema.sql) - Structure des tables
3. ✅ [procedures.sql](procedures.sql) - Logique métier
4. ✅ [CONCEPTION_SQL.md](CONCEPTION_SQL.md) - Section "Exemples de Requêtes"
5. ✅ [triggers.sql](triggers.sql) - Automatisations

**Focus sur :**
- API endpoints à créer
- Validation des données
- Gestion des transactions
- Optimisation des requêtes

### 🎨 Je suis Développeur Frontend

**Ordre de lecture recommandé :**

1. ✅ [README.md](README.md) - Vue d'ensemble
2. ✅ [ARCHITECTURE_DIAGRAM.md](ARCHITECTURE_DIAGRAM.md) - Flux de données
3. ✅ [CONCEPTION_SQL.md](CONCEPTION_SQL.md) - Section "Fonctionnalités"

**Focus sur :**
- Modèles de données pour l'UI
- Flux utilisateur
- États et validations
- Notifications et feedbacks

### 🏗️ Je suis Architecte

**Ordre de lecture recommandé :**

1. ✅ [CONCEPTION_SQL.md](CONCEPTION_SQL.md) - Analyse complète
2. ✅ [ARCHITECTURE_DIAGRAM.md](ARCHITECTURE_DIAGRAM.md) - Diagrammes
3. ✅ [indexes_optimization.sql](indexes_optimization.sql) - Performance
4. ✅ [README.md](README.md) - Section "Monitoring"

**Focus sur :**
- Scalabilité
- Performance
- Sécurité
- Résilience

### 🛠️ Je suis DevOps / DBA

**Ordre de lecture recommandé :**

1. ✅ [MIGRATION_GUIDE.md](MIGRATION_GUIDE.md) - Déploiement
2. ✅ [README.md](README.md) - Maintenance et monitoring
3. ✅ [indexes_optimization.sql](indexes_optimization.sql) - Tuning
4. ✅ [schema.sql](schema.sql) - Structure complète

**Focus sur :**
- Migration sans downtime
- Backup et restauration
- Monitoring et alertes
- Optimisation des performances

### 👔 Je suis Product Owner

**Ordre de lecture recommandé :**

1. ✅ [README.md](README.md) - Vue d'ensemble du projet
2. ✅ [CONCEPTION_SQL.md](CONCEPTION_SQL.md) - Section "Fonctionnalités"
3. ✅ [ARCHITECTURE_DIAGRAM.md](ARCHITECTURE_DIAGRAM.md) - Flux métier

**Focus sur :**
- Fonctionnalités disponibles
- User stories supportées
- KPIs trackés
- Évolutions possibles

---

## 🔍 Recherche Rapide

### Par Fonctionnalité

| Fonctionnalité | Fichier à consulter | Section |
|----------------|---------------------|---------|
| Authentification JWT | [schema.sql](schema.sql) | Table `users`, `refresh_tokens` |
| Paiement Mobile Money | [schema.sql](schema.sql) | Table `payments` |
| QR Code billets | [schema.sql](schema.sql) | Table `tickets` |
| Tarification dynamique | [procedures.sql](procedures.sql) | `apply_dynamic_pricing()` |
| Programme fidélité | [triggers.sql](triggers.sql) | `after_order_loyalty_points` |
| Mini-jeu | [schema.sql](schema.sql) | Table `game_participations` |
| Notifications | [schema.sql](schema.sql) | Table `notifications` |
| Statistiques | [schema.sql](schema.sql) | Table `event_statistics` |
| Codes promo | [schema.sql](schema.sql) | Table `promo_codes` |
| Liste d'attente | [procedures.sql](procedures.sql) | `notify_waiting_list()` |

### Par Table

| Table | Module | Description | Volumétrie |
|-------|--------|-------------|------------|
| `users` | Auth | Comptes utilisateurs | 10K-1M |
| `events` | Events | Événements | 1K-100K |
| `tickets` | Tickets | Billets individuels | 100K-10M |
| `orders` | Orders | Commandes | 50K-5M |
| `payments` | Payments | Transactions | 50K-5M |
| `notifications` | Notify | Notifications | 500K-50M |
| `event_views` | Analytics | Historique vues | 1M-100M |
| `audit_logs` | Audit | Logs traçabilité | 1M-100M |

---

## 📖 Guides Pratiques

### Comment...

#### ...installer la base de données ?

👉 Voir [README.md - Installation](README.md#installation)

#### ...migrer en production ?

👉 Voir [MIGRATION_GUIDE.md](MIGRATION_GUIDE.md)

#### ...optimiser les performances ?

👉 Voir [indexes_optimization.sql](indexes_optimization.sql) et [CONCEPTION_SQL.md - Performance](CONCEPTION_SQL.md#performance)

#### ...ajouter une nouvelle table ?

👉 Voir [MIGRATION_GUIDE.md - Migrations Versionnées](MIGRATION_GUIDE.md#migrations-versionnées)

#### ...débugger une requête lente ?

👉 Voir [README.md - Troubleshooting](README.md#troubleshooting)

#### ...comprendre un trigger ?

👉 Voir [triggers.sql](triggers.sql) - Tous les triggers sont documentés

#### ...utiliser une procédure stockée ?

👉 Voir [procedures.sql](procedures.sql) - Exemples d'utilisation inclus

---

## 🎓 Tutoriels

### Tutoriel 1 : Créer un Événement Complet

```sql
-- 1. Créer l'événement
INSERT INTO events (organizer_id, category_id, title, slug, ...)
VALUES (1, 1, 'Mon Événement', 'mon-evenement', ...);

-- 2. Ajouter des catégories de billets
INSERT INTO ticket_categories (event_id, name, price, quantity_total)
VALUES (LAST_INSERT_ID(), 'VIP', 100000, 100);

-- 3. Créer un code promo
INSERT INTO promo_codes (code, discount_type, discount_value, ...)
VALUES ('EARLY20', 'percentage', 20, ...);

-- 4. Publier l'événement
UPDATE events SET status = 'published' WHERE id = LAST_INSERT_ID();
```

### Tutoriel 2 : Acheter un Billet

```sql
-- 1. Ajouter au panier
CALL add_to_cart(user_id, ticket_category_id, quantity);

-- 2. Créer la commande
CALL create_order_from_cart(user_id, 'PROMO_CODE', @order_id, @success, @msg);

-- 3. Traiter le paiement
INSERT INTO payments (order_id, payment_method, amount, ...)
VALUES (@order_id, 'orange_money', 50000, ...);

-- 4. Compléter la commande
CALL complete_order(@order_id, LAST_INSERT_ID());

-- 5. Les billets sont automatiquement générés !
SELECT * FROM tickets WHERE order_id = @order_id;
```

### Tutoriel 3 : Scanner un Billet

```sql
-- Scanner le QR code
CALL checkin_ticket(
    'AIOLIA-xxxx-xxxx-xxxx',
    organizer_user_id,
    @success,
    @message
);

SELECT @success, @message;
```

---

## 🔧 Maintenance

### Tâches Quotidiennes

- ✅ Vérifier les backups : [README.md - Backup](README.md#backup)
- ✅ Monitorer les requêtes lentes : [README.md - Monitoring](README.md#monitoring)
- ✅ Vérifier l'espace disque

### Tâches Hebdomadaires

- ✅ Analyser les statistiques de performance
- ✅ Optimiser les tables fragmentées
- ✅ Vérifier les logs d'erreur

### Tâches Mensuelles

- ✅ Archiver les logs anciens
- ✅ Mettre à jour les index si nécessaire
- ✅ Audit de sécurité

---

## ⚠️ Points d'Attention

### Performance

⚠️ **Tables à surveiller** (croissance rapide) :
- `event_views` - Peut dépasser 100M de lignes
- `audit_logs` - Archiver après 1 an
- `notifications` - Nettoyer les anciennes

👉 Voir [indexes_optimization.sql](indexes_optimization.sql)

### Sécurité

⚠️ **Données sensibles** :
- ❌ Ne JAMAIS stocker de cartes bancaires en clair
- ✅ Hashes bcrypt pour mots de passe
- ✅ Tokens JWT avec expiration courte
- ✅ HTTPS obligatoire

👉 Voir [README.md - Sécurité](README.md#sécurité)

### Scalabilité

⚠️ **Goulots d'étranglement potentiels** :
- Table `tickets` si millions de billets
- Calcul de statistiques en temps réel
- Envoi de notifications en masse

👉 Voir [CONCEPTION_SQL.md - Scalabilité](CONCEPTION_SQL.md#scalabilité)

---

## 📞 Support & Ressources

### Support Technique

- 📧 **Email** : dev-support@aiolia-event.com
- 💬 **Slack** : #database-support
- 🎫 **Tickets** : https://jira.aiolia-event.com

### Ressources Externes

- [Documentation MySQL 8.0](https://dev.mysql.com/doc/refman/8.0/en/)
- [SQL Performance Explained](https://use-the-index-luke.com/)
- [Database Design Best Practices](https://www.sqlshack.com/database-design-best-practices/)

### Communauté

- 💬 **Discord** : https://discord.gg/aiolia-dev
- 🐦 **Twitter** : @aiolia_event
- 📺 **YouTube** : Aiolia Event Tech

---

## 📝 Changelog

### Version 1.0.0 (Octobre 2025)

- ✅ Schéma initial complet (60+ tables)
- ✅ 30+ triggers automatiques
- ✅ 15+ procédures stockées
- ✅ Documentation complète
- ✅ Scripts d'optimisation
- ✅ Guide de migration

### À venir (Version 1.1.0)

- ⏳ Support multi-devises
- ⏳ Intégration blockchain (NFT tickets)
- ⏳ IA pour recommandations avancées
- ⏳ Streaming d'événements

---

## 🎯 Prochaines Étapes

### Pour démarrer immédiatement

1. ✅ Lire [README.md](README.md)
2. ✅ Installer la base : Exécuter les scripts SQL dans l'ordre
3. ✅ Tester avec les données de seed
4. ✅ Consulter [CONCEPTION_SQL.md](CONCEPTION_SQL.md) pour comprendre la logique

### Pour aller plus loin

1. ✅ Optimiser avec [indexes_optimization.sql](indexes_optimization.sql)
2. ✅ Préparer la production avec [MIGRATION_GUIDE.md](MIGRATION_GUIDE.md)
3. ✅ Visualiser l'architecture avec [ARCHITECTURE_DIAGRAM.md](ARCHITECTURE_DIAGRAM.md)

---

## 🏆 Contributeurs

Un grand merci à tous ceux qui ont contribué à cette conception !

- **Équipe Architecture** : Design de la base de données
- **Équipe DevOps** : Scripts d'optimisation et déploiement
- **Équipe QA** : Tests et validation
- **Communauté** : Retours et suggestions

---

**Dernière mise à jour** : Octobre 2025  
**Version** : 1.0.0  
**Maintenu par** : Équipe Aiolia Event

---

*Ce document est vivant et sera mis à jour régulièrement. N'hésitez pas à proposer des améliorations !*

