# 🎻 Guide de Configuration Symfony 7 - Aiolia Event

Ce guide vous accompagne dans la mise en place complète du projet Aiolia Event avec **Symfony 7**.

---

## 🎯 Pourquoi Symfony 7 ?

### ✅ Avantages pour Aiolia Event

1. **Maturité & Stabilité**
   - Framework entreprise éprouvé
   - LTS (Long Term Support) disponible
   - Communauté très active

2. **Performance**
   - PHP 8.2+ avec typage strict
   - Cache optimisé
   - Support natif d'APCu et Redis

3. **Doctrine ORM**
   - Mapping objet-relationnel puissant
   - Migrations versionnées automatiques
   - QueryBuilder pour requêtes complexes

4. **Sécurité**
   - JWT avec LexikJWTAuthenticationBundle
   - CSRF protection native
   - OAuth2 avec KnpUOAuth2ClientBundle

5. **Écosystème Riche**
   - 6000+ bundles disponibles
   - API Platform pour REST/GraphQL
   - Messenger pour queues asynchrones

---

## 📋 Prérequis

- **PHP** : 8.2 ou supérieur
- **Composer** : 2.5+
- **MySQL** : 8.0+ ou MariaDB 10.5+
- **Node.js** : 18+ (pour Webpack Encore)
- **Extensions PHP** :
  ```bash
  sudo apt install php8.2-cli php8.2-fpm php8.2-mysql php8.2-xml \
    php8.2-mbstring php8.2-curl php8.2-zip php8.2-intl \
    php8.2-redis php8.2-gd php8.2-bcmath
  ```

---

## 🚀 Installation

### 1. Créer le Projet Symfony

```bash
# Installer Symfony CLI (recommandé)
curl -sS https://get.symfony.com/cli/installer | bash

# Créer le projet
cd /home/aina/Documents/MyProject
symfony new Aiolia-event --version=7.0 --webapp

# OU avec Composer
composer create-project symfony/skeleton:"7.0.*" Aiolia-event
cd Aiolia-event
composer require webapp
```

### 2. Installer les Bundles Nécessaires

```bash
# ORM & Database
composer require symfony/orm-pack
composer require symfony/maker-bundle --dev

# Security & JWT
composer require symfony/security-bundle
composer require lexik/jwt-authentication-bundle
composer require gesdinet/jwt-refresh-token-bundle

# OAuth (Google, Facebook)
composer require knpuniversity/oauth2-client-bundle
composer require league/oauth2-google
composer require league/oauth2-facebook

# API
composer require api-platform/core
# OU pour API REST classique
composer require nelmio/api-doc-bundle
composer require friendsofsymfony/rest-bundle

# Validation
composer require symfony/validator
composer require symfony/serializer

# Email
composer require symfony/mailer
composer require symfony/sendgrid-mailer
# OU
composer require symfony/google-mailer

# Cache & Performance
composer require symfony/cache
composer require predis/predis

# Queue & Async
composer require symfony/messenger

# Upload & Storage
composer require oneup/flysystem-bundle
composer require league/flysystem-aws-s3-v3

# Utilities
composer require symfony/uid
composer require endroid/qr-code
composer require knplabs/knp-paginator-bundle
composer require stof/doctrine-extensions-bundle

# Development
composer require symfony/profiler-pack --dev
composer require symfony/debug-bundle --dev
composer require doctrine/doctrine-fixtures-bundle --dev
composer require symfony/test-pack --dev
```

### 3. Configuration de la Base de Données

Éditez `.env` :

```env
# Database
DATABASE_URL="mysql://root:password@127.0.0.1:3306/aiolia_event?serverVersion=8.0&charset=utf8mb4"

# JWT
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_jwt_passphrase

# Redis
REDIS_URL=redis://localhost:6379

# Email
MAILER_DSN=smtp://user:pass@smtp.gmail.com:587

# AWS S3 (pour upload de médias)
AWS_S3_KEY=your_key
AWS_S3_SECRET=your_secret
AWS_S3_REGION=eu-west-1
AWS_S3_BUCKET=aiolia-event-bucket

# Mobile Money (API)
ORANGE_MONEY_API_KEY=your_key
ORANGE_MONEY_API_SECRET=your_secret
AIRTEL_MONEY_API_KEY=your_key
TELMA_MONEY_API_KEY=your_key

# OAuth
OAUTH_GOOGLE_CLIENT_ID=your_id
OAUTH_GOOGLE_CLIENT_SECRET=your_secret
OAUTH_FACEBOOK_CLIENT_ID=your_id
OAUTH_FACEBOOK_CLIENT_SECRET=your_secret
```

### 4. Générer les Clés JWT

```bash
mkdir -p config/jwt
openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
```

### 5. Créer la Base de Données

```bash
# Créer la base
php bin/console doctrine:database:create

# Ou utiliser les scripts SQL fournis
mysql -u root -p < database/schema.sql
mysql -u root -p aiolia_event < database/triggers.sql
mysql -u root -p aiolia_event < database/procedures.sql
mysql -u root -p aiolia_event < database/seeds.sql
```

---

## 📁 Structure du Projet Symfony

```
Aiolia-event/
├── assets/                     # Frontend (JS, CSS, images)
├── bin/
│   └── console                # Commandes Symfony
├── config/
│   ├── packages/              # Configuration bundles
│   ├── routes/                # Routes API
│   └── services.yaml          # Services
├── migrations/                # Migrations Doctrine
├── public/
│   └── index.php             # Point d'entrée
├── src/
│   ├── Controller/           # Contrôleurs API
│   ├── Entity/               # Entités Doctrine (60+ classes)
│   ├── Repository/           # Repositories
│   ├── Service/              # Services métier
│   ├── Security/             # Authentification
│   ├── EventListener/        # Event listeners
│   ├── Command/              # Commandes console
│   ├── DataFixtures/         # Fixtures (données de test)
│   └── Kernel.php
├── templates/                # Templates Twig (si besoin)
├── tests/                    # Tests
├── var/                      # Cache, logs
├── vendor/                   # Dépendances
├── .env                      # Configuration
└── composer.json
```

---

## 🏗️ Architecture Recommandée

### Couches de l'Application

```
┌─────────────────────────────────────────┐
│         API Layer (REST/GraphQL)        │
│  Controllers + API Platform Resources   │
└─────────────┬───────────────────────────┘
              │
┌─────────────▼───────────────────────────┐
│          Service Layer                  │
│  Business Logic + Domain Services       │
└─────────────┬───────────────────────────┘
              │
┌─────────────▼───────────────────────────┐
│       Repository Layer                  │
│  Data Access + Query Builders           │
└─────────────┬───────────────────────────┘
              │
┌─────────────▼───────────────────────────┐
│         Entity Layer                    │
│  Doctrine Entities (ORM)                │
└─────────────┬───────────────────────────┘
              │
┌─────────────▼───────────────────────────┐
│         Database (MySQL)                │
│  60+ Tables + Triggers + Procedures     │
└─────────────────────────────────────────┘
```

### Pattern Recommandé : Hexagonal Architecture

```
src/
├── Application/           # Use Cases / Application Services
│   ├── Command/          # Commands (CQRS)
│   ├── Query/            # Queries (CQRS)
│   └── Handler/          # Handlers
│
├── Domain/               # Domain Logic (Pure Business)
│   ├── Entity/          # Domain Entities
│   ├── ValueObject/     # Value Objects
│   ├── Repository/      # Repository Interfaces
│   └── Service/         # Domain Services
│
└── Infrastructure/       # Technical Implementation
    ├── Controller/      # HTTP Controllers
    ├── Repository/      # Doctrine Repositories
    ├── Persistence/     # Database
    ├── Security/        # Auth
    └── External/        # External APIs (Mobile Money)
```

---

## 🔐 Configuration de la Sécurité

### config/packages/security.yaml

```yaml
security:
    password_hashers:
        Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface: 'auto'
        
    providers:
        app_user_provider:
            entity:
                class: App\Entity\User
                property: email

    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false

        login:
            pattern: ^/api/login
            stateless: true
            json_login:
                check_path: /api/login_check
                success_handler: lexik_jwt_authentication.handler.authentication_success
                failure_handler: lexik_jwt_authentication.handler.authentication_failure

        api:
            pattern: ^/api
            stateless: true
            jwt: ~

        main:
            lazy: true
            provider: app_user_provider

    access_control:
        - { path: ^/api/login, roles: PUBLIC_ACCESS }
        - { path: ^/api/register, roles: PUBLIC_ACCESS }
        - { path: ^/api/events, roles: PUBLIC_ACCESS, methods: [GET] }
        - { path: ^/api, roles: IS_AUTHENTICATED_FULLY }

    role_hierarchy:
        ROLE_CO_ORGANIZER: ROLE_USER
        ROLE_ORGANIZER: ROLE_CO_ORGANIZER
        ROLE_ADMIN: ROLE_ORGANIZER
```

---

## 📝 Création des Entités

Je vais créer les entités principales dans un fichier séparé. Voici la commande pour générer une entité :

```bash
# Générer une entité
php bin/console make:entity User

# Générer le CRUD complet
php bin/console make:crud User
```

---

## 🔄 Migrations

```bash
# Créer une migration depuis les entités
php bin/console make:migration

# Exécuter les migrations
php bin/console doctrine:migrations:migrate

# Ou importer directement le schéma SQL existant
mysql -u root -p aiolia_event < database/schema.sql

# Puis synchroniser Doctrine
php bin/console doctrine:schema:update --force
```

---

## 🧪 Tests

```bash
# Tests unitaires
php bin/phpunit

# Tests API
php bin/console --env=test doctrine:database:create
php bin/console --env=test doctrine:schema:create
php bin/phpunit tests/Api
```

---

## 🚀 Lancement

### Développement

```bash
# Démarrer le serveur Symfony
symfony server:start

# Ou avec le serveur PHP natif
php -S localhost:8000 -t public/

# Workers pour queues asynchrones
php bin/console messenger:consume async
```

### Production

```bash
# Optimiser pour production
composer install --no-dev --optimize-autoloader
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

# Assets
npm run build
```

---

## 📦 Bundles Recommandés Supplémentaires

```bash
# Charts & Statistics
composer require symfony/ux-chartjs

# Export PDF
composer require dompdf/dompdf
composer require tecnickcom/tcpdf

# QR Code
composer require endroid/qr-code

# Excel Export
composer require phpoffice/phpspreadsheet

# Image Processing
composer require intervention/image

# Pagination
composer require knplabs/knp-paginator-bundle

# Rate Limiting
composer require symfony/rate-limiter
```

---

## 🔧 Commandes Utiles

```bash
# Créer une entité
php bin/console make:entity

# Créer un contrôleur
php bin/console make:controller

# Créer une commande
php bin/console make:command

# Créer un service
php bin/console make:service

# Créer des fixtures
php bin/console make:fixtures

# Charger les fixtures
php bin/console doctrine:fixtures:load

# Vider le cache
php bin/console cache:clear

# Lister les routes
php bin/console debug:router

# Lister les services
php bin/console debug:container

# Vérifier la configuration
php bin/console debug:config

# Créer un utilisateur admin
php bin/console app:create-admin
```

---

## 📚 Ressources

- [Documentation Symfony 7](https://symfony.com/doc/current/index.html)
- [Doctrine ORM](https://www.doctrine-project.org/projects/orm.html)
- [API Platform](https://api-platform.com/)
- [JWT Authentication Bundle](https://github.com/lexik/LexikJWTAuthenticationBundle)
- [Symfony Best Practices](https://symfony.com/doc/current/best_practices.html)

---

**Prochaine étape** : Je vais créer les entités Doctrine complètes pour vous !


