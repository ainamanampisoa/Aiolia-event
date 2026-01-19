# ANALYSE DES TABLES AIOLIA-EVENTS PAR MODULE

**Date d'analyse :** 2025-12-17  
**Source :** dbdiagram.io (50 tables) vs schema.sql (58 tables) vs Entity PHP (46 tables)

---

## 📊 RÉSUMÉ COMPARATIF

- **dbdiagram.io :** 53 tables
- **schema.sql :** 58 tables (dont `listes_attente_billets` créée dans Events.sql)
- **Entity PHP :** 46 tables
- **Tables communes (dbdiagram + schema) :** 52 tables

---

## 📦 MODULE 1 : UTILISATEURS & SESSIONS (2 tables)

| Table | dbdiagram | schema.sql | Entity PHP | Utilisée |
|-------|-----------|------------|------------|----------|
| `utilisateurs` | ✅ | ✅ | ✅ | ✅ OUI |
| `jetons_rafraichissement` | ✅ | ✅ | ❌ | ❌ NON (Entity RefreshToken mappe vers refresh_tokens) |

**Statut :** ⚠️ **2/2 tables dans la BD** (1 avec Entity complète, 1 **non utilisée dans le backend** - voir incohérence ci-dessous)

---

## 📦 MODULE 2 : AUDIT (1 table)

| Table | dbdiagram | schema.sql | Entity PHP | Utilisée |
|-------|-----------|------------|------------|----------|
| `journaux_audit` | ✅ | ✅ | ✅ | ✅ OUI |

**Statut :** ✅ **1/1 table utilisée**

---

## 📦 MODULE 3 : PROFILS (2 tables)

| Table | dbdiagram | schema.sql | Entity PHP | Utilisée |
|-------|-----------|------------|------------|----------|
| `profils_admin` | ✅ | ✅ | ✅ | ✅ OUI |
| `profils_organisateurs` | ✅ | ✅ | ✅ | ✅ OUI |

**Note :** `profils_utilisateurs` existe dans schema.sql mais pas dans dbdiagram

**Statut :** ✅ **2/2 tables utilisées** (+ 1 table supplémentaire dans schema.sql)

---

## 📦 MODULE 4 : ABONNEMENTS (5 tables)

| Table | dbdiagram | schema.sql | Entity PHP | Utilisée |
|-------|-----------|------------|------------|----------|
| `plans_abonnements` | ✅ | ✅ | ✅ | ✅ OUI |
| `abonnements_organisateurs` | ✅ | ✅ | ✅ | ✅ OUI |
| `factures_abonnements` | ✅ | ✅ | ✅ | ✅ OUI |
| `elements_factures_abonnements` | ✅ | ✅ | ❌ | ❌ NON |
| `historique_paiements_abonnements` | ✅ | ✅ | ❌ | ❌ NON |

**Statut :** ⚠️ **5/5 tables dans la BD** (3 avec Entity complète, 2 **non utilisées dans le backend**)

---

## 📦 MODULE 5 : LIEUX (2 tables)

| Table | dbdiagram | schema.sql | Entity PHP | Utilisée |
|-------|-----------|------------|------------|----------|
| `lieux` | ✅ | ✅ | ✅ | ✅ OUI |
| `espaces_lieux` | ✅ | ✅ | ✅ | ✅ OUI |

**Statut :** ✅ **2/2 tables utilisées**

---

## 📦 MODULE 6 : ÉVÉNEMENTS (12 tables)

| Table | dbdiagram | schema.sql | Entity PHP | Utilisée |
|-------|-----------|------------|------------|----------|
| `categories_evenements` | ✅ | ✅ | ✅ | ✅ OUI |
| `types_evenements` | ✅ | ✅ | ✅ | ✅ OUI |
| `evenements` | ✅ | ✅ | ✅ | ✅ OUI |
| `liens_categories_evenements` | ✅ | ✅ | ❌ | ❌ NON |
| `tags_evenements` | ✅ | ✅ | ✅ | ✅ OUI |
| `liens_tags_evenements` | ✅ | ✅ | ❌ | ❌ NON |
| `sessions_evenements` | ✅ | ✅ | ✅ | ✅ OUI |
| `medias_evenements` | ✅ | ✅ | ✅ | ✅ OUI |
| `vues_evenements` | ✅ | ✅ | ❌ | ❌ NON |
| `organisateurs_evenements` | ✅ | ✅ | ✅ | ✅ OUI |
| `langues` | ✅ | ✅ | ✅ | ✅ OUI |
| `liens_langues_evenements` | ✅ | ✅ | ✅ | ✅ OUI |

**Note :** `types_accessibilite` et `liens_accessibilite_evenements` existent dans schema.sql mais pas dans dbdiagram

**Statut :** ✅ **12/12 tables utilisées** (+ 2 tables supplémentaires dans schema.sql)

---

## 📦 MODULE 7 : BILLETTERIE (7 tables)

| Table | dbdiagram | schema.sql | Entity PHP | Utilisée |
|-------|-----------|------------|------------|----------|
| `configuration_segments_billets` | ✅ | ✅ | ✅ | ✅ OUI |
| `configuration_categories_billets` | ✅ | ✅ | ✅ | ✅ OUI |
| `types_billets` | ✅ | ✅ | ✅ | ✅ OUI |
| `inventaire_billets` | ✅ | ✅ | ✅ | ✅ OUI |
| `historique_prix_billets` | ✅ | ✅ | ✅ | ✅ OUI |
| `listes_attente_billets` | ✅ | ✅ | ✅ | ✅ OUI |
| `billets` | ✅ | ✅ | ✅ | ✅ OUI |

**Statut :** ✅ **7/7 tables utilisées**

---

## 📦 MODULE 8 : PANIERS (2 tables)

| Table | dbdiagram | schema.sql | Entity PHP | Utilisée |
|-------|-----------|------------|------------|----------|
| `paniers` | ✅ | ✅ | ✅ | ✅ OUI |
| `elements_paniers` | ✅ | ✅ | ✅ | ✅ OUI |

**Statut :** ✅ **2/2 tables utilisées**

---

## 📦 MODULE 9 : COMMANDES (3 tables)

| Table | dbdiagram | schema.sql | Entity PHP | Utilisée |
|-------|-----------|------------|------------|----------|
| `commandes` | ✅ | ✅ | ✅ | ✅ OUI |
| `elements_commandes` | ✅ | ✅ | ✅ | ✅ OUI |
| `historique_statuts_commandes` | ✅ | ✅ | ❌ | ❌ NON |

**Statut :** ⚠️ **3/3 tables dans la BD** (2 avec Entity complète, 1 **non utilisée dans le backend**)

---

## 📦 MODULE 10 : PAIEMENTS & FACTURES (3 tables)

| Table | dbdiagram | schema.sql | Entity PHP | Utilisée |
|-------|-----------|------------|------------|----------|
| `modes_paiement` | ✅ | ✅ | ✅ | ✅ OUI |
| `factures_billets` | ✅ | ✅ | ✅ | ✅ OUI |
| `historique_paiements_billets` | ✅ | ✅ | ❌ | ❌ NON |

**Statut :** ⚠️ **3/3 tables dans la BD** (2 avec Entity complète, 1 **non utilisée dans le backend**)

---

## 📦 MODULE 11 : TRANSACTIONS MOBILES (1 table)

| Table | dbdiagram | schema.sql | Entity PHP | Utilisée |
|-------|-----------|------------|------------|----------|
| `transactions_paiement_mobile` | ✅ | ✅ | ❌ | ❌ NON |

**Statut :** ❌ **1/1 table dans la BD** (0 avec Entity, **non utilisée dans le backend**)

---

## 📦 MODULE 12 : PROMOTIONS (3 tables)

| Table | dbdiagram | schema.sql | Entity PHP | Utilisée |
|-------|-----------|------------|------------|----------|
| `codes_promotionnels` | ✅ | ✅ | ✅ | ✅ OUI |
| `applications_promotions` | ✅ | ✅ | ✅ | ✅ OUI |
| `regles_tarification` | ✅ | ✅ | ✅ | ✅ OUI |

**Statut :** ✅ **3/3 tables utilisées**

---

## 📦 MODULE 13 : FAVORIS & RÉSEAUX (6 tables)

| Table | dbdiagram | schema.sql | Entity PHP | Utilisée |
|-------|-----------|------------|------------|----------|
| `listes_souhaits` | ✅ | ✅ | ❌ | ❌ NON |
| `elements_listes_souhaits` | ✅ | ✅ | ❌ | ❌ NON |
| `participations_loteries_billets` | ✅ | ✅ | ❌ | ❌ NON |
| `connexions_utilisateurs` | ✅ | ✅ | ❌ | ❌ NON |
| `invitations_evenements` | ✅ | ✅ | ❌ | ❌ NON |
| `cache_recommandations` | ✅ | ✅ | ❌ | ❌ NON |

**Statut :** ❌ **6/6 tables dans la BD** (0 avec Entity, **toutes non utilisées dans le backend**)

---

## 📦 MODULE 14 : NOTIFICATIONS (4 tables)

| Table | dbdiagram | schema.sql | Entity PHP | Utilisée |
|-------|-----------|------------|------------|----------|
| `modeles_notifications` | ✅ | ✅ | ❌ | ❌ NON |
| `notifications` | ✅ | ✅ | ❌ | ❌ NON |
| `historique_notifications` | ✅ | ✅ | ❌ | ❌ NON |
| `transferts_billets` | ✅ | ✅ | ❌ | ❌ NON |

**Statut :** ❌ **4/4 tables dans la BD** (0 avec Entity, **toutes non utilisées dans le backend**)

---

## 📊 TABLEAUX COMPARATIFS : dbdiagram vs schema.sql

### 📋 Tableau 1 : Les 53 tables du dbdiagram

| Table | Description / Module |
|-------|---------------------|
| `abonnements_organisateurs` | Abonnements des organisateurs (Module 4) |
| `applications_promotions` | Applications des codes promotionnels (Module 12) |
| `billets` | Billets émis pour les événements (Module 7) |
| `cache_recommandations` | Cache des recommandations d'événements (Module 13) |
| `categories_evenements` | Catégories d'événements (Module 6) |
| `codes_promotionnels` | Codes promotionnels (Module 12) |
| `commandes` | Commandes de billets (Module 9) |
| `configuration_categories_billets` | Configuration catégories de billets (Module 7) |
| `configuration_segments_billets` | Configuration segments de billets (Module 7) |
| `connexions_utilisateurs` | Connexions/réseaux entre utilisateurs (Module 13) |
| `elements_commandes` | Éléments des commandes (Module 9) |
| `elements_factures_abonnements` | Éléments de factures d'abonnements (Module 4) |
| `elements_listes_souhaits` | Éléments des listes de souhaits (Module 13) |
| `elements_paniers` | Éléments des paniers (Module 8) |
| `espaces_lieux` | Espaces dans les lieux (Module 5) |
| `evenements` | Événements (Module 6) |
| `factures_abonnements` | Factures d'abonnements (Module 4) |
| `factures_billets` | Factures de billets (Module 10) |
| `historique_paiements_abonnements` | Historique paiements abonnements (Module 4) |
| `historique_paiements_billets` | Historique paiements billets (Module 10) |
| `historique_prix_billets` | Historique des prix de billets (Module 7) |
| `historique_statuts_commandes` | Historique statuts commandes (Module 9) |
| `inventaire_billets` | Inventaire des billets (Module 7) |
| `invitations_evenements` | Invitations à des événements (Module 13) |
| `jetons_rafraichissement` | Jetons de rafraîchissement (Module 1) |
| `journaux_audit` | Journaux d'audit (Module 2) |
| `langues` | Langues supportées (Module 6) |
| `liens_categories_evenements` | Liens catégories-événements (Module 6) |
| `liens_langues_evenements` | Liens langues-événements (Module 6) |
| `liens_tags_evenements` | Liens tags-événements (Module 6) |
| `lieux` | Lieux d'événements (Module 5) |
| `listes_attente_billets` | Listes d'attente pour billets (Module 7) |
| `listes_souhaits` | Listes de souhaits / wishlists (Module 13) |
| `medias_evenements` | Médias des événements (Module 6) |
| `modeles_notifications` | Modèles de notifications (Module 14) |
| `modes_paiement` | Modes de paiement (Module 10) |
| `notifications` | Notifications envoyées (Module 14) |
| `organisateurs_evenements` | Organisateurs par événement (Module 6) |
| `participations_loteries_billets` | Participations loteries (Module 13) |
| `paniers` | Paniers d'achat (Module 8) |
| `plans_abonnements` | Plans d'abonnements (Module 4) |
| `profils_admin` | Profils administrateurs (Module 3) |
| `profils_organisateurs` | Profils organisateurs (Module 3) |
| `regles_tarification` | Règles de tarification (Module 12) |
| `sessions_evenements` | Sessions d'événements (Module 6) |
| `tags_evenements` | Tags d'événements (Module 6) |
| `transactions_paiement_mobile` | Transactions paiement mobile (Module 11) |
| `transferts_billets` | Transferts de billets (Module 14) |
| `types_billets` | Types de billets (Module 7) |
| `types_evenements` | Types d'événements (Module 6) |
| `utilisateurs` | Utilisateurs (Module 1) |
| `vues_evenements` | Statistiques de vues d'événements (Module 6) |
| `historique_notifications` | Historique des notifications (Module 14) |

**Total : 53 tables**

---

### 📋 Tableau 2 : Tables dans schema.sql mais absentes du dbdiagram

| Table | Description / Module |
|-------|---------------------|
| `historique_recherches_utilisateurs` | Historique des recherches utilisateurs |
| `liens_accessibilite_evenements` | Liens accessibilité des événements (Module 6) |
| `preferences_utilisateurs` | Préférences des utilisateurs |
| `profils_utilisateurs` | Profils utilisateurs (Module 3) |
| `statistiques_evenements_utilisateurs` | Statistiques événements par utilisateur |
| `types_accessibilite` | Types d'accessibilité (Module 6) |

**Total : 6 tables**

**Note :** Ces tables existent dans le schéma SQL mais ne sont pas documentées dans le dbdiagram. Elles devraient être ajoutées au dbdiagram pour une documentation complète.

---

## ❌ TABLES NON UTILISÉES DANS AIOLIA-EVENTS-BACK (sans Entity PHP)

**Total : 19 tables** du schema.sql n'ont **pas d'Entity PHP** correspondante dans le backend Symfony.

Ces tables existent dans la base de données mais ne sont **pas directement utilisables via Doctrine ORM**.

### Liste complète des tables sans Entity PHP (tableau à deux colonnes) :

| Table | Description |
|-------|-------------|
| `jetons_rafraichissement` | Jetons de rafraîchissement (Module 1) ⚠️ Incohérence: Entity RefreshToken mappe vers `refresh_tokens` |
| `elements_factures_abonnements` | Éléments de factures d'abonnements (Module 4) |
| `historique_paiements_abonnements` | Historique des paiements d'abonnements (Module 4) |
| `liens_categories_evenements` | Table de liaison catégories-événements (Module 6) |
| `liens_tags_evenements` | Table de liaison tags-événements (Module 6) |
| `vues_evenements` | Statistiques de vues d'événements (Module 6) |
| `historique_statuts_commandes` | Historique des changements de statut des commandes (Module 9) |
| `historique_paiements_billets` | Historique des paiements de billets (Module 10) |
| `transactions_paiement_mobile` | Transactions de paiement mobile (Module 11) |
| `listes_souhaits` | Listes de souhaits / wishlists (Module 13) |
| `elements_listes_souhaits` | Éléments des listes de souhaits (Module 13) |
| `participations_loteries_billets` | Participations aux loteries de billets (Module 13) |
| `connexions_utilisateurs` | Connexions/réseaux sociaux entre utilisateurs (Module 13) |
| `invitations_evenements` | Invitations à des événements (Module 13) |
| `cache_recommandations` | Cache des recommandations d'événements (Module 13) |
| `modeles_notifications` | Modèles de notifications (Module 14) |
| `notifications` | Notifications envoyées (Module 14) |
| `historique_notifications` | Historique des notifications (Module 14) |
| `transferts_billets` | Transferts de billets entre utilisateurs (Module 14) |

### Impact sur le backend :

⚠️ **Ces 19 tables ne peuvent pas être utilisées directement via Doctrine ORM** dans le backend Symfony.

**Solutions possibles :**
- Utiliser des requêtes SQL natives via `EntityManager::getConnection()`
- Créer les Entity PHP manquantes pour une intégration complète
- Utiliser des Repository personnalisés avec requêtes DQL/SQL

### ⚠️ Incohérences détectées :

- **`jetons_rafraichissement`** (dans schema.sql) vs **`refresh_tokens`** (dans Entity RefreshToken)
  - La table `jetons_rafraichissement` existe dans schema.sql mais n'a pas d'Entity
  - L'Entity `RefreshToken` référence `refresh_tokens` qui n'existe pas dans schema.sql
  - **Action requise :** Aligner le nom de la table ou créer une migration pour renommer

---

## ✅ CONCLUSION

### Tables utilisées dans aiolia-events :

**Total : 53 tables** (selon dbdiagram) / **58 tables** (selon schema.sql)

- ✅ **Tables avec Entity PHP complète :** 39 tables
- ❌ **Tables sans Entity PHP (non utilisées dans le backend) :** 19 tables
  - Tables de liaison (liens_categories_evenements, liens_tags_evenements)
  - Tables historiques (historique_paiements_abonnements, historique_paiements_billets, historique_statuts_commandes, historique_notifications)
  - Tables fonctionnelles (listes_souhaits, notifications, transferts_billets, cache_recommandations, etc.)
  - Tables de sessions (jetons_rafraichissement - note: Entity RefreshToken mappe vers refresh_tokens)

### Recommandations :

1. **Créer les Entity PHP manquantes** pour les 19 tables sans Entity (priorité selon besoins fonctionnels)
2. **Vérifier la cohérence** entre dbdiagram, schema.sql et Entity PHP
3. **Ajouter les tables manquantes** du schema.sql dans le dbdiagram si nécessaire
4. **Décider** si certaines tables doivent être utilisées via Doctrine ORM ou uniquement via requêtes SQL natives

---

**Note :** 
- Les tables marquées "❌ NON" dans les tableaux existent dans la base de données mais n'ont **pas d'Entity PHP** correspondante dans le backend Symfony.
- Ces tables ne sont **pas directement utilisables via Doctrine ORM** et nécessitent soit :
  - Des requêtes SQL natives via `EntityManager::getConnection()`
  - La création d'Entity PHP pour une intégration complète
- Voir la section "TABLES NON UTILISÉES" ci-dessous pour la liste complète et détaillée.

Répartition par module
Module 1 - Utilisateurs & Sessions : 2 tables
Module 2 - Audit : 1 table
Module 3 - Profils : 2 tables (+ 1 supplémentaire dans schema.sql)
Module 4 - Abonnements : 5 tables
Module 5 - Lieux : 2 tables
Module 6 - Événements : 12 tables (+ 2 supplémentaires dans schema.sql)
Module 7 - Billetterie : 7 tables
Module 8 - Paniers : 2 tables
Module 9 - Commandes : 3 tables
Module 10 - Paiements & Factures : 3 tables
Module 11 - Transactions Mobiles : 1 table
Module 12 - Promotions : 3 tables
Module 13 - Favoris & Réseaux : 6 tables
Module 14 - Notifications : 4 tables