# 📊 Documentation de la Base de Données Aiolia Event

## Vue d'ensemble

Cette base de données PostgreSQL gère l'ensemble du système de billetterie d'événements Aiolia Event. Elle comprend **19 tables** organisées en modules fonctionnels.

---

## 📑 Table des matières

- [Types ENUM](#types-enum)
- [Tables principales](#tables-principales)
  - [1. Authentification & Utilisateurs](#1-authentification--utilisateurs)
  - [2. Catégories & Événements](#2-catégories--événements)
  - [3. Billets & Catégories](#3-billets--catégories)
  - [4. Commandes & Paiements](#4-commandes--paiements)
  - [5. Codes Promo](#5-codes-promo)
  - [6. Panier d'achat](#6-panier-dachat)
  - [7. Favoris](#7-favoris)
  - [8. Notifications](#8-notifications)
  - [9. Avis & Évaluations](#9-avis--évaluations)
  - [10. Statistiques](#10-statistiques)
  - [11. Configuration système](#11-configuration-système)
- [Vues SQL](#vues-sql)
- [Triggers](#triggers)

---

## Types ENUM

Le schéma utilise des types ENUM PostgreSQL pour garantir l'intégrité des données :

- **`user_role`** : `user`, `co_organizer`, `organizer`, `admin`
- **`oauth_provider`** : `google`, `facebook`, `local`
- **`event_status`** : `draft`, `published`, `ongoing`, `completed`, `cancelled`
- **`order_status`** : `pending`, `processing`, `completed`, `failed`, `cancelled`, `refunded`
- **`payment_status`** : `pending`, `processing`, `paid`, `failed`, `refunded`
- **`payment_method`** : `orange_money`, `airtel_money`, `mvola`, `bank_card`, `bank_transfer`
- **`ticket_status`** : `valid`, `used`, `cancelled`, `refunded`, `transferred`
- **`notification_type`** : `order_confirmation`, `payment_success`, `event_reminder`, `ticket_transferred`, `new_event`, `promotion`
- **`notification_channel`** : `email`, `push`, `sms`, `in_app`
- **`notification_status`** : `pending`, `sent`, `failed`, `read`

---

## Tables principales

### 1. Authentification & Utilisateurs

#### 📌 **`users`** - Utilisateurs de la plateforme

Gère tous les utilisateurs de la plateforme.

**Champs principaux :**
- `id` : Identifiant unique auto-incrémenté (BIGSERIAL)
- `email` : Email unique de l'utilisateur (connexion)
- `password_hash` : Mot de passe crypté
- `first_name`, `last_name` : Nom et prénom
- `phone` : Numéro de téléphone
- `photo_url` : URL de la photo de profil
- `role` : Rôle de l'utilisateur (user, co_organizer, organizer, admin)
- `email_verified` : Statut de vérification de l'email
- `oauth_provider` : Type de connexion (Google, Facebook, ou local)
- `oauth_provider_id` : ID du fournisseur OAuth
- `is_active` : Statut du compte
- `created_at`, `updated_at` : Horodatage
- `last_login_at` : Dernière connexion

**Index :**
- `idx_users_email` : Recherche rapide par email
- `idx_users_role` : Filtrage par rôle

**Utilité :** C'est le cœur de l'authentification. Tous les autres modules y font référence.

---

#### 📌 **`refresh_tokens`** - Tokens de rafraîchissement JWT

Stocke les tokens de rafraîchissement pour l'authentification JWT.

**Champs principaux :**
- `id` : Identifiant unique
- `user_id` : Référence vers l'utilisateur
- `token` : Token unique de rafraîchissement (500 caractères)
- `expires_at` : Date d'expiration du token
- `is_revoked` : Si le token est révoqué (déconnexion)
- `created_at` : Date de création

**Index :**
- `idx_refresh_tokens_user` : Par utilisateur
- `idx_refresh_tokens_token` : Par token

**Utilité :** Permet de maintenir les utilisateurs connectés de façon sécurisée et de révoquer l'accès si nécessaire.

---

### 2. Catégories & Événements

#### 📌 **`event_categories`** - Catégories d'événements

Définit les différents types d'événements disponibles sur la plateforme.

**Champs principaux :**
- `id` : Identifiant unique (SERIAL)
- `name` : Nom de la catégorie (Concert, Sport, etc.)
- `slug` : Identifiant URL-friendly (concert, sport)
- `description` : Description de la catégorie
- `icon` : Icône pour l'affichage dans l'interface
- `is_active` : Si la catégorie est active
- `created_at` : Date de création

**Index :**
- `idx_event_categories_slug` : Recherche par slug

**Utilité :** Organise les événements par type pour faciliter la navigation et la recherche.

**Données par défaut :** Concert, Conférence, Sport, Festival, Théâtre, Formation, Networking, Autre

---

#### 📌 **`events`** - Événements

Table centrale qui stocke tous les événements de la plateforme.

**Champs principaux :**
- `id` : Identifiant unique (BIGSERIAL)
- `organizer_id` : Référence vers l'utilisateur organisateur
- `category_id` : Type d'événement
- `title` : Titre de l'événement
- `slug` : URL-friendly identifier
- `description` : Description complète
- `short_description` : Description courte (500 caractères)
- `location` : Lieu de l'événement
- `address` : Adresse complète
- `latitude`, `longitude` : Coordonnées GPS
- `start_date`, `end_date` : Dates de début et fin
- `timezone` : Fuseau horaire (défaut: Indian/Antananarivo)
- `status` : État de l'événement (draft, published, ongoing, completed, cancelled)
- `is_featured` : Si l'événement est mis en avant
- `total_capacity` : Capacité totale de l'événement
- `views_count` : Nombre de vues
- `created_at`, `updated_at` : Horodatage
- `published_at` : Date de publication

**Index :**
- `idx_events_organizer` : Par organisateur
- `idx_events_category` : Par catégorie
- `idx_events_dates` : Par dates (composite)
- `idx_events_status` : Par statut
- `idx_events_slug` : Par slug
- `idx_events_search` : Recherche plein texte (GIN) sur titre et description

**Utilité :** Table centrale qui représente chaque événement avec toutes ses informations essentielles.

---

#### 📌 **`event_media`** - Médias d'événements

Stocke les images, vidéos et documents associés à un événement.

**Champs principaux :**
- `id` : Identifiant unique
- `event_id` : Référence vers l'événement
- `media_type` : Type de média (image, video, document)
- `file_url` : URL du fichier stocké
- `file_name` : Nom du fichier
- `is_primary` : Si c'est l'image/média principal
- `display_order` : Ordre d'affichage dans la galerie
- `uploaded_by` : Référence vers l'utilisateur qui a uploadé
- `created_at` : Date d'upload

**Index :**
- `idx_event_media_event` : Par événement

**Utilité :** Gère la galerie multimédia de chaque événement (photos de couverture, galerie d'images, vidéos promotionnelles).

---

### 3. Billets & Catégories

#### 📌 **`ticket_categories`** - Catégories de billets

Définit les différents types de billets disponibles pour un événement.

**Champs principaux :**
- `id` : Identifiant unique
- `event_id` : Référence vers l'événement
- `name` : Nom du type de billet (VIP, Standard, Gratuit, etc.)
- `description` : Description des avantages
- `price` : Prix du billet
- `currency` : Devise (défaut: MGA - Ariary malgache)
- `quantity_total` : Nombre total de billets disponibles
- `quantity_sold` : Nombre de billets vendus
- `quantity_reserved` : Billets en cours de réservation (dans les paniers)
- `min_purchase` : Nombre minimum de billets par achat
- `max_purchase` : Nombre maximum de billets par achat
- `sale_start_date`, `sale_end_date` : Période de vente
- `is_active` : Si la vente est active
- `display_order` : Ordre d'affichage
- `created_at`, `updated_at` : Horodatage

**Contrainte :** `quantity_sold + quantity_reserved <= quantity_total`

**Index :**
- `idx_ticket_categories_event` : Par événement

**Utilité :** Permet de créer différents tarifs et types de places pour un même événement (Early Bird, VIP, Standard, etc.).

---

#### 📌 **`tickets`** - Billets individuels

Représente chaque billet individuel acheté (e-ticket).

**Champs principaux :**
- `id` : Identifiant unique
- `ticket_category_id` : Type de billet
- `order_id` : Référence vers la commande d'origine
- `user_id` : Propriétaire actuel du billet
- `ticket_number` : Numéro unique du billet
- `qr_code_data` : Données encodées dans le QR code
- `qr_code_image_url` : URL de l'image du QR code
- `status` : État du billet (valid, used, cancelled, refunded, transferred)
- `check_in_at` : Horodatage du scan à l'entrée
- `check_in_by` : Référence vers l'utilisateur qui a scanné
- `created_at`, `updated_at` : Horodatage

**Index :**
- `idx_tickets_category` : Par catégorie
- `idx_tickets_order` : Par commande
- `idx_tickets_user` : Par utilisateur
- `idx_tickets_qr` : Par QR code (scan rapide)
- `idx_tickets_status` : Par statut

**Utilité :** Chaque billet individuel avec son QR code unique pour le contrôle d'accès à l'événement.

---

### 4. Commandes & Paiements

#### 📌 **`orders`** - Commandes

Représente une commande complète passée par un utilisateur.

**Champs principaux :**
- `id` : Identifiant unique
- `user_id` : Référence vers le client
- `order_number` : Numéro unique de commande
- `status` : État de la commande (pending, processing, completed, failed, cancelled, refunded)
- `subtotal` : Sous-total avant réductions
- `discount_amount` : Montant de la réduction appliquée
- `total_amount` : Montant total à payer
- `currency` : Devise (défaut: MGA)
- `payment_status` : État du paiement
- `payment_method` : Moyen de paiement utilisé
- `billing_email`, `billing_phone` : Informations de facturation
- `completed_at` : Date de finalisation de la commande
- `created_at`, `updated_at` : Horodatage

**Index :**
- `idx_orders_user` : Par utilisateur
- `idx_orders_number` : Par numéro de commande
- `idx_orders_status` : Par statut

**Utilité :** Trace toutes les commandes passées sur la plateforme avec leur cycle de vie complet.

---

#### 📌 **`order_items`** - Articles de commande

Détaille le contenu d'une commande (quels billets et en quelle quantité).

**Champs principaux :**
- `id` : Identifiant unique
- `order_id` : Référence vers la commande
- `ticket_category_id` : Type de billet acheté
- `quantity` : Nombre de billets de ce type
- `unit_price` : Prix unitaire au moment de l'achat
- `total_price` : Prix total pour cette ligne (quantity × unit_price)
- `created_at` : Date d'ajout

**Index :**
- `idx_order_items_order` : Par commande

**Utilité :** Permet à une commande de contenir plusieurs types de billets différents (ex: 2 VIP + 3 Standard).

---

#### 📌 **`payments`** - Paiements

Gère les transactions de paiement avec les fournisseurs (Mobile Money, banque).

**Champs principaux :**
- `id` : Identifiant unique
- `order_id` : Référence vers la commande
- `payment_method` : Méthode utilisée (orange_money, airtel_money, mvola, bank_card, bank_transfer)
- `amount` : Montant payé
- `currency` : Devise
- `status` : État du paiement (pending, processing, completed, failed, refunded)
- `transaction_id` : ID de transaction du fournisseur de paiement
- `reference_number` : Numéro de référence
- `phone_number` : Numéro de téléphone pour Mobile Money
- `provider_response` : Réponse complète du provider (JSONB)
- `error_message` : Message d'erreur en cas d'échec
- `completed_at` : Date de finalisation du paiement
- `created_at`, `updated_at` : Horodatage

**Index :**
- `idx_payments_order` : Par commande
- `idx_payments_transaction` : Par ID de transaction
- `idx_payments_status` : Par statut

**Utilité :** Trace toutes les tentatives de paiement, leur résultat et permet la réconciliation avec les fournisseurs.

---

### 5. Codes Promo

#### 📌 **`promo_codes`** - Codes promotionnels

Gère les codes de réduction et promotions.

**Champs principaux :**
- `id` : Identifiant unique
- `code` : Code unique (ex: NOEL2024, BIENVENUE)
- `description` : Description de la promotion
- `discount_type` : Type de réduction (percentage ou fixed_amount)
- `discount_value` : Valeur de la réduction (ex: 20 pour 20% ou 5000 pour 5000 MGA)
- `currency` : Devise (si fixed_amount)
- `max_uses` : Nombre maximum d'utilisations (NULL = illimité)
- `current_uses` : Nombre d'utilisations actuelles
- `max_uses_per_user` : Limite par utilisateur
- `valid_from`, `valid_until` : Période de validité
- `is_active` : Si le code est actif
- `min_purchase_amount` : Montant minimum d'achat requis
- `created_by` : Référence vers l'utilisateur créateur
- `created_at`, `updated_at` : Horodatage

**Index :**
- `idx_promo_codes_code` : Par code
- `idx_promo_codes_active` : Par statut actif

**Utilité :** Permet de créer des promotions, réductions et campagnes marketing.

---

#### 📌 **`promo_code_usage`** - Utilisation des codes promo

Trace l'historique d'utilisation des codes promotionnels.

**Champs principaux :**
- `id` : Identifiant unique
- `promo_code_id` : Référence vers le code utilisé
- `user_id` : Référence vers l'utilisateur
- `order_id` : Référence vers la commande
- `discount_applied` : Montant de réduction effectivement appliqué
- `used_at` : Date et heure d'utilisation

**Index :**
- `idx_promo_usage_code` : Par code promo
- `idx_promo_usage_user` : Par utilisateur

**Utilité :** Empêche la réutilisation abusive des codes et permet le suivi des performances des campagnes.

---

### 6. Panier d'achat

#### 📌 **`cart`** - Panier

Panier d'achat d'un utilisateur (connecté ou invité).

**Champs principaux :**
- `id` : Identifiant unique
- `user_id` : Référence vers l'utilisateur (NULL si non connecté)
- `session_id` : ID de session pour utilisateurs anonymes
- `created_at`, `updated_at` : Horodatage
- `expires_at` : Date d'expiration du panier (réservation temporaire)

**Index :**
- `idx_cart_user` : Par utilisateur
- `idx_cart_session` : Par session

**Utilité :** Permet aux utilisateurs de préparer leur commande avant validation et paiement.

---

#### 📌 **`cart_items`** - Articles du panier

Contenu du panier (billets sélectionnés).

**Champs principaux :**
- `id` : Identifiant unique
- `cart_id` : Référence vers le panier
- `ticket_category_id` : Type de billet sélectionné
- `quantity` : Nombre de billets
- `added_at` : Date d'ajout au panier

**Contrainte :** UNIQUE(cart_id, ticket_category_id) - Un type de billet par panier

**Index :**
- `idx_cart_items_cart` : Par panier

**Utilité :** Stocke les billets sélectionnés avant validation de la commande.

---

### 7. Favoris

#### 📌 **`favorites`** - Favoris

Événements mis en favoris par les utilisateurs.

**Champs principaux :**
- `id` : Identifiant unique
- `user_id` : Référence vers l'utilisateur
- `event_id` : Référence vers l'événement favori
- `created_at` : Date d'ajout aux favoris

**Contrainte :** UNIQUE(user_id, event_id) - Un événement ne peut être favori qu'une fois par utilisateur

**Index :**
- `idx_favorites_user` : Par utilisateur
- `idx_favorites_event` : Par événement

**Utilité :** Permet aux utilisateurs de sauvegarder et retrouver facilement leurs événements préférés.

---

### 8. Notifications

#### 📌 **`notifications`** - Notifications

Système de notifications multi-canaux.

**Champs principaux :**
- `id` : Identifiant unique
- `user_id` : Destinataire de la notification
- `type` : Type de notification (order_confirmation, payment_success, event_reminder, etc.)
- `title` : Titre de la notification
- `message` : Contenu du message
- `channel` : Canal d'envoi (email, push, sms, in_app)
- `status` : État (pending, sent, failed, read)
- `reference_type` : Type d'élément lié (order, event, ticket, etc.)
- `reference_id` : ID de l'élément lié
- `metadata` : Données supplémentaires (JSONB)
- `sent_at` : Date d'envoi
- `read_at` : Date de lecture
- `created_at` : Date de création

**Index :**
- `idx_notifications_user` : Par utilisateur
- `idx_notifications_type` : Par type
- `idx_notifications_status` : Par statut

**Utilité :** Communique avec les utilisateurs par email, notifications push, SMS ou in-app.

---

### 9. Avis & Évaluations

#### 📌 **`reviews`** - Avis

Évaluations et commentaires des événements par les participants.

**Champs principaux :**
- `id` : Identifiant unique
- `event_id` : Référence vers l'événement évalué
- `user_id` : Auteur de l'avis
- `order_id` : Référence vers la commande (preuve d'achat)
- `rating` : Note de 1 à 5 étoiles
- `title` : Titre de l'avis
- `comment` : Commentaire détaillé
- `is_verified_purchase` : Si c'est un achat vérifié
- `is_published` : Si l'avis est publié publiquement
- `organizer_response` : Réponse de l'organisateur
- `organizer_response_at` : Date de réponse
- `helpful_count` : Nombre de votes "utile"
- `created_at`, `updated_at` : Horodatage

**Contrainte :** UNIQUE(user_id, event_id) - Un utilisateur ne peut laisser qu'un seul avis par événement

**Index :**
- `idx_reviews_event` : Par événement
- `idx_reviews_user` : Par utilisateur
- `idx_reviews_rating` : Par note

**Utilité :** Système de notation et feedback pour améliorer la qualité des événements et aider les utilisateurs à choisir.

---

### 10. Statistiques

#### 📌 **`event_statistics`** - Statistiques des événements

Agrège les métriques et statistiques d'un événement.

**Champs principaux :**
- `id` : Identifiant unique
- `event_id` : Référence vers l'événement (UNIQUE)
- `total_views` : Nombre total de vues
- `total_favorites` : Nombre de fois mis en favori
- `total_tickets_sold` : Nombre total de billets vendus
- `total_revenue` : Revenu total généré
- `average_ticket_price` : Prix moyen du billet
- `conversion_rate` : Taux de conversion visiteurs → acheteurs (%)
- `average_rating` : Note moyenne des avis
- `total_reviews` : Nombre total d'avis
- `last_calculated_at` : Date de dernière mise à jour des stats

**Index :**
- `idx_event_statistics_event` : Par événement

**Utilité :** Dashboard et analytics pour les organisateurs. Permet de suivre les performances d'un événement.

---

### 11. Configuration système

#### 📌 **`system_settings`** - Paramètres système

Configuration globale de l'application.

**Champs principaux :**
- `id` : Identifiant unique
- `setting_key` : Clé unique du paramètre
- `setting_value` : Valeur du paramètre
- `setting_type` : Type de données (string, number, boolean, json)
- `description` : Description du paramètre
- `is_public` : Si accessible via API publique
- `updated_by` : Référence vers l'utilisateur qui a modifié
- `updated_at` : Date de dernière modification

**Index :**
- `idx_system_settings_key` : Par clé

**Paramètres par défaut :**
- `site_name` : Nom du site (Aiolia Event)
- `site_email` : Email de contact
- `default_currency` : Devise par défaut (MGA)
- `ticket_reservation_timeout` : Durée de réservation du panier (15 minutes)
- `max_tickets_per_order` : Nombre maximum de billets par commande (10)
- `enable_mobile_money` : Activation des paiements Mobile Money

**Utilité :** Centralise tous les paramètres configurables de l'application sans nécessiter de redéploiement.

---

## Vues SQL

### 📊 **`upcoming_events`** - Événements à venir enrichis

Vue SQL qui combine plusieurs tables pour afficher facilement les événements à venir avec leurs statistiques.

**Colonnes retournées :**
- Toutes les colonnes de la table `events`
- `category_name` : Nom de la catégorie
- `organizer_name` : Nom complet de l'organisateur (prénom + nom)
- `total_tickets_sold` : Billets vendus
- `total_revenue` : Revenu généré
- `average_rating` : Note moyenne
- `favorites_count` : Nombre de favoris

**Filtres :**
- Statut : `published` uniquement
- Date : Événements dont `start_date > CURRENT_TIMESTAMP`

**Tri :** Par date de début (ASC)

**Utilité :** Simplifie les requêtes pour l'affichage de la page d'accueil et la liste des événements.

---

## Triggers

### 🔄 Fonction `update_updated_at_column()`

Fonction PostgreSQL qui met à jour automatiquement le champ `updated_at` à chaque modification d'une ligne.

**Tables concernées :**
- `users`
- `events`
- `ticket_categories`
- `orders`
- `payments`
- `tickets`
- `promo_codes`
- `reviews`

**Exemple :**
```sql
CREATE TRIGGER update_users_updated_at 
BEFORE UPDATE ON users
FOR EACH ROW 
EXECUTE FUNCTION update_updated_at_column();
```

**Utilité :** Automatise la gestion de l'horodatage des modifications sans intervention manuelle dans le code applicatif.

---

## 🔐 Sécurité

### Contraintes d'intégrité

- **Clés étrangères avec CASCADE ou RESTRICT** : Empêche la suppression accidentelle de données liées
- **UNIQUE constraints** : Garantit l'unicité des emails, codes promo, numéros de commande
- **CHECK constraints** : Valide les données (rating entre 1-5, quantités de billets cohérentes)

### Index de performance

- **Index sur clés étrangères** : Accélère les jointures
- **Index composites** : Optimise les requêtes fréquentes (dates, statuts)
- **Index GIN** : Recherche plein texte performante sur les événements
- **Index UNIQUE** : Garantit l'unicité tout en accélérant les recherches

---

## 📈 Statistiques

### Résumé du schéma

- **19 tables** au total
- **1 vue SQL** pré-calculée
- **8 triggers** automatiques
- **10 types ENUM** personnalisés
- **Supports** :
  - ✅ Multi-devises (MGA par défaut)
  - ✅ Multi-canaux de paiement (Mobile Money + Cartes)
  - ✅ Multi-canaux de notification (Email, Push, SMS, In-app)
  - ✅ Recherche plein texte
  - ✅ Géolocalisation (latitude/longitude)
  - ✅ OAuth (Google, Facebook)
  - ✅ Système de favoris
  - ✅ Codes promotionnels
  - ✅ Avis et évaluations
  - ✅ Statistiques et analytics

---

## 🚀 Installation

Pour créer la base de données, exécutez :

```bash
psql -U postgres -d aiolia_event -f schema.sql
```

Ou avec Docker :

```bash
docker exec -i postgres_container psql -U postgres -d aiolia_event < schema.sql
```

---

## 📝 Notes de développement

### Bonnes pratiques

1. **Toujours utiliser des transactions** pour les opérations critiques (commandes, paiements)
2. **Vérifier les contraintes** avant insertion (quantités de billets, validité des codes promo)
3. **Mettre à jour les statistiques** de façon asynchrone ou par batch
4. **Logger les actions critiques** (paiements, annulations, transferts)
5. **Nettoyer régulièrement** les paniers expirés et tokens révoqués

### Évolutions futures possibles

- 🔄 Système de transfert de billets entre utilisateurs
- 📊 Rapports et exports avancés
- 🎮 Gamification (points de fidélité, défis)
- 👥 Système d'amis et événements partagés
- 📋 Liste d'attente pour événements complets
- 🌍 Support multi-langues (traductions)
- 📧 Templates d'emails personnalisables
- 🎟️ Tarification dynamique selon la demande

---

## 📞 Support

Pour toute question sur le schéma de base de données, contactez l'équipe de développement.

**Version :** 1.0  
**Dernière mise à jour :** 2025  
**Base de données :** PostgreSQL 14+

