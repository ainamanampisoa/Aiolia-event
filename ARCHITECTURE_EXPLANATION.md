
# 🏗️ Architecture N-Tier Aiolia Event - Explication Détaillée

## 📋 Vue d'ensemble

L'architecture **Aiolia Event** suit un modèle **N-Tier (multi-couches)** avec deux applications distinctes :
- **FrontOffice** : Application publique pour les utilisateurs finaux
- **BackOffice** : Application d'administration pour les organisateurs

## 🎯 Composants Principaux

### 1. FrontOffice (Aiolia-event-front)

#### Couche Présentation
- **13 Controllers** : Gestion des routes HTTP et actions utilisateur
- **37 Templates Twig** : Interface utilisateur responsive (desktop/tablet/mobile)
- **Responsabilités** : Validation des entrées, rendu des vues, gestion des erreurs HTTP

#### Couche Métier
- **14 Services** : Logique applicative réutilisable
  - `PaymentService` : Gestion des paiements MVola
  - `CacheService` : Gestion du cache Redis
  - `CloudinaryService` : Upload et gestion d'images
  - `NotificationService` : Envoi de notifications
  - `WalletService` : Gestion du portefeuille utilisateur
  - Et 9 autres services...
- **Responsabilités** : Règles métier, validation, orchestration, transactions

#### Couche Persistance
- **17 Repositories** : Accès optimisé aux données
- **9 Entities Doctrine** : Modèles ORM pour les tables principales
- **Approche hybride** : Doctrine ORM pour 9 tables + SQL direct pour 45 autres tables
- **Responsabilités** : Requêtes SQL, mapping objet-relationnel, gestion des transactions

### 2. BackOffice (Aiolia-event-back)

#### Couche Présentation
- **14 Controllers** : Gestion des routes admin
- **42 Templates Twig** : Interface d'administration
- **Responsabilités** : Gestion CRUD, validation admin, rapports

#### Couche Métier
- **7 Services** : Logique métier spécifique admin
  - `EventService` : Gestion complète des événements
  - `MediaService` : Gestion des médias via Cloudinary
  - `CloudinaryService` : Upload d'images
  - `AuditLogService` : Traçabilité des actions
  - Et 3 autres services...

#### Couche Persistance
- **7 Repositories** : Accès aux données admin
- **7 Entities Doctrine** : Modèles BackOffice

### 3. Base de Données PostgreSQL

- **Schéma** : `aiolia`
- **54 tables** au total
- **Partagée** entre FrontOffice et BackOffice
- **Avantages** : Cohérence des données, transactions ACID, performance optimisée

### 4. Redis Cache

- **Utilisation** : Uniquement par le FrontOffice
- **Pools de cache** :
  - `cache.events` : Événements populaires (TTL: 1 heure)
  - `cache.search` : Résultats de recherche (TTL: 30 minutes)
  - `cache.stats` : Statistiques (TTL: 30 minutes)
  - `cache.sessions` : Sessions utilisateur (TTL: 24 heures)
- **Protocole** : TCP/IP via Predis
- **Avantages** : Performance ultra-rapide, réduction de charge sur PostgreSQL

### 5. Services Externes

#### MVola API
- **Usage** : Uniquement FrontOffice
- **Service** : `MvolaPaymentClient`
- **Protocole** : HTTPS (API REST)
- **Flux** :
  1. FrontOffice → PaymentService → MvolaPaymentClient → MVola API
  2. MVola → Callback/Webhook → MvolaController (FrontOffice)
- **Fonctionnalités** : Paiement mobile money, remboursements, vérification de statut

#### Cloudinary
- **Usage** : FrontOffice ET BackOffice
- **Services** : `CloudinaryService` (dans les deux applications)
- **Protocole** : HTTPS (API REST)
- **Fonctionnalités** :
  - Upload d'images/vidéos/documents
  - Optimisation automatique
  - CDN global
  - Transformations à la volée (redimensionnement, compression)

## 🔄 Flux de Données

### Flux Standard (Achat de billet)

1. **Utilisateur** → FrontOffice (Présentation)
   - Requête HTTP : `/ticket/purchase`
   - Controller : `TicketController::purchase()`

2. **Présentation** → Métier
   - Appel : `PaymentService::processPayment($order)`
   - Validation métier

3. **Métier** → Service Externe (MVola)
   - Appel : `MvolaPaymentClient::initiateTransaction()`
   - Communication HTTPS avec MVola API

4. **Métier** → Persistance
   - Appel : `OrderRepository::save($order)`
   - Sauvegarde en base de données

5. **Persistance** → PostgreSQL
   - Requête SQL via Doctrine ORM
   - Transaction ACID

6. **Métier** → Redis (si applicable)
   - Mise en cache des résultats
   - Invalidation du cache si nécessaire

7. **MVola** → FrontOffice (Callback)
   - Webhook HTTPS : `/api/mvola/callback`
   - Controller : `MvolaController::callback()`
   - Mise à jour du statut de transaction

### Flux avec Cache

1. **Utilisateur** → FrontOffice : Requête événements
2. **Présentation** → Métier : `EventController::list()`
3. **Métier** → CacheService : Vérification cache Redis
4. **Si cache hit** : Retour immédiat depuis Redis
5. **Si cache miss** : Métier → Persistance → PostgreSQL
6. **Métier** → Redis : Mise en cache du résultat
7. **Métier** → Présentation : Retour des données
8. **Présentation** → Utilisateur : Rendu Twig

## ✅ Corrections Apportées au Diagramme

### Problèmes Corrigés :

1. **❌ Avant** : Deux instances Redis identiques
   - **✅ Après** : Une seule instance Redis utilisée uniquement par FrontOffice

2. **❌ Avant** : Cloudinary connecté directement à Redis
   - **✅ Après** : Cloudinary connecté aux couches Métier (FrontOffice et BackOffice) via HTTPS

3. **❌ Avant** : Flèches "API/HTTPS" sans destination claire
   - **✅ Après** : Toutes les connexions sont explicites avec protocoles et destinations

4. **❌ Avant** : Duplications et ambiguïtés
   - **✅ Après** : Architecture claire, une seule instance de chaque composant

## 🔐 Sécurité

- **Isolation** : FrontOffice et BackOffice déployés séparément
- **HTTPS** : Toutes les communications externes en HTTPS
- **Authentification** : OAuth 2.0 pour MVola, credentials pour Cloudinary
- **Base de données** : Protégée par firewall, accès via Doctrine ORM sécurisé

## 📊 Performance

- **Cache Redis** : Réduction de 80-90% des requêtes PostgreSQL pour les données fréquentes
- **CDN Cloudinary** : Images servies depuis le CDN global
- **Optimisation** : Requêtes SQL optimisées, index sur tables critiques
- **Scalabilité** : Chaque couche peut être mise à l'échelle indépendamment

## 🚀 Déploiement

- **FrontOffice** : Serveur 1 (web public)
- **BackOffice** : Serveur 2 (admin sécurisé)
- **PostgreSQL** : Serveur 3 (base de données)
- **Redis** : Serveur 4 (cache) ou même serveur que FrontOffice
- **Services externes** : Hébergés par MVola et Cloudinary
