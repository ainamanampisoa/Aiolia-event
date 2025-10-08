# 🎻 Guide d'Installation Backend - Symfony 7 + Admin

Ce guide détaille l'installation et la configuration du backend Symfony 7 pour Aiolia Event.

---

## 📋 Prérequis

- **PHP** : 8.2 ou supérieur
- **Composer** : 2.5+
- **MySQL** : 8.0+ ou MariaDB 10.5+
- **Extensions PHP** :
  ```bash
  sudo apt install php8.2-cli php8.2-fpm php8.2-mysql php8.2-xml \
    php8.2-mbstring php8.2-curl php8.2-zip php8.2-intl \
    php8.2-redis php8.2-gd php8.2-bcmath
  ```

---

## 🚀 Installation

### 1. Créer le Dossier Backend

```bash
cd /home/aina/Documents/MyProject/Aiolia-event
mkdir backend
cd backend
```

### 2. Installer Symfony 7

```bash
# Option 1 : Avec Symfony CLI (recommandé)
symfony new . --version=7.0 --webapp

# Option 2 : Avec Composer
composer create-project symfony/skeleton:"7.0.*" .
composer require webapp
```

### 3. Installer les Bundles Nécessaires

```bash
# ORM & Database
composer require symfony/orm-pack
composer require symfony/maker-bundle --dev

# Security & JWT
composer require symfony/security-bundle
composer require lexik/jwt-authentication-bundle
composer require gesdinet/jwt-refresh-token-bundle

# OAuth2 (Google, Facebook)
composer require knpuniversity/oauth2-client-bundle
composer require league/oauth2-google
composer require league/oauth2-facebook

# API REST
composer require nelmio/api-doc-bundle
composer require nelmio/cors-bundle

# Validation & Serialization
composer require symfony/validator
composer require symfony/serializer-pack

# Email
composer require symfony/mailer
composer require symfony/sendgrid-mailer

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

# Admin Interface (EasyAdmin)
composer require easycorp/easyadmin-bundle

# Development
composer require symfony/profiler-pack --dev
composer require symfony/debug-bundle --dev
composer require doctrine/doctrine-fixtures-bundle --dev
composer require symfony/test-pack --dev
composer require phpunit/phpunit --dev
```

---

## ⚙️ Configuration

### 1. Configuration de la Base de Données

Créer `.env.local` :

```bash
cp .env .env.local
nano .env.local
```

Ajouter :

```env
# Database
DATABASE_URL="mysql://root:password@127.0.0.1:3306/aiolia_event?serverVersion=8.0&charset=utf8mb4"

# App
APP_ENV=dev
APP_SECRET=your_random_secret_here

# CORS (pour React frontend)
CORS_ALLOW_ORIGIN=^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$

# JWT
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_jwt_passphrase_here
JWT_TOKEN_TTL=3600

# Redis
REDIS_URL=redis://localhost:6379

# Email
MAILER_DSN=smtp://user:pass@smtp.gmail.com:587
MAILER_FROM=noreply@aiolia-event.com

# AWS S3
AWS_S3_KEY=your_key
AWS_S3_SECRET=your_secret
AWS_S3_REGION=eu-west-1
AWS_S3_BUCKET=aiolia-event-bucket

# Mobile Money API
ORANGE_MONEY_API_KEY=your_key
ORANGE_MONEY_API_SECRET=your_secret
ORANGE_MONEY_API_URL=https://api.orange.com/oauth/v2
AIRTEL_MONEY_API_KEY=your_key
AIRTEL_MONEY_API_URL=https://openapiuat.airtel.africa
TELMA_MONEY_API_KEY=your_key

# OAuth
OAUTH_GOOGLE_CLIENT_ID=your_client_id
OAUTH_GOOGLE_CLIENT_SECRET=your_secret
OAUTH_FACEBOOK_CLIENT_ID=your_client_id
OAUTH_FACEBOOK_CLIENT_SECRET=your_secret

# Admin
ADMIN_EMAIL=admin@aiolia-event.com
ADMIN_PASSWORD=AdminPassword123!
```

### 2. Générer les Clés JWT

```bash
mkdir -p config/jwt
openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout

# Sécuriser les clés
chmod 600 config/jwt/*.pem
```

### 3. Créer la Base de Données

```bash
# Option 1 : Importer le schéma SQL existant (RECOMMANDÉ)
mysql -u root -p < ../database/schema.sql
mysql -u root -p aiolia_event < ../database/triggers.sql
mysql -u root -p aiolia_event < ../database/procedures.sql
mysql -u root -p aiolia_event < ../database/seeds.sql
mysql -u root -p aiolia_event < ../database/indexes_optimization.sql

# Option 2 : Utiliser Doctrine (après avoir créé les entités)
php bin/console doctrine:database:create
php bin/console doctrine:schema:create
```

### 4. Générer les Entités depuis la BDD

```bash
# Générer les entités automatiquement
php bin/console doctrine:mapping:import "App\Entity" attribute --path=src/Entity

# Générer les getters/setters
php bin/console make:entity --regenerate App

# Créer les repositories
php bin/console make:repository
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
            pattern: ^/api/auth/login
            stateless: true
            json_login:
                check_path: /api/auth/login
                username_path: email
                password_path: password
                success_handler: lexik_jwt_authentication.handler.authentication_success
                failure_handler: lexik_jwt_authentication.handler.authentication_failure

        api:
            pattern: ^/api
            stateless: true
            jwt: ~

        admin:
            pattern: ^/admin
            lazy: true
            provider: app_user_provider
            form_login:
                login_path: admin_login
                check_path: admin_login
                default_target_path: admin_dashboard
            logout:
                path: admin_logout
                target: admin_login

    access_control:
        # Public API endpoints
        - { path: ^/api/auth/login, roles: PUBLIC_ACCESS }
        - { path: ^/api/auth/register, roles: PUBLIC_ACCESS }
        - { path: ^/api/events$, roles: PUBLIC_ACCESS, methods: [GET] }
        - { path: ^/api/events/[^/]+$, roles: PUBLIC_ACCESS, methods: [GET] }
        
        # Authenticated API endpoints
        - { path: ^/api/users/me, roles: ROLE_USER }
        - { path: ^/api/orders, roles: ROLE_USER }
        - { path: ^/api/tickets, roles: ROLE_USER }
        
        # Organizer API endpoints
        - { path: ^/api/events, roles: ROLE_ORGANIZER, methods: [POST, PUT, PATCH, DELETE] }
        - { path: ^/api/admin, roles: ROLE_ORGANIZER }
        
        # Admin interface
        - { path: ^/admin/login, roles: PUBLIC_ACCESS }
        - { path: ^/admin, roles: ROLE_ORGANIZER }

    role_hierarchy:
        ROLE_CO_ORGANIZER: ROLE_USER
        ROLE_ORGANIZER: ROLE_CO_ORGANIZER
        ROLE_ADMIN: ROLE_ORGANIZER
```

### config/packages/lexik_jwt_authentication.yaml

```yaml
lexik_jwt_authentication:
    secret_key: '%env(resolve:JWT_SECRET_KEY)%'
    public_key: '%env(resolve:JWT_PUBLIC_KEY)%'
    pass_phrase: '%env(JWT_PASSPHRASE)%'
    token_ttl: '%env(int:JWT_TOKEN_TTL)%'
```

### config/packages/nelmio_cors.yaml

```yaml
nelmio_cors:
    defaults:
        origin_regex: true
        allow_origin: ['%env(CORS_ALLOW_ORIGIN)%']
        allow_methods: ['GET', 'OPTIONS', 'POST', 'PUT', 'PATCH', 'DELETE']
        allow_headers: ['Content-Type', 'Authorization']
        expose_headers: ['Link']
        max_age: 3600
    paths:
        '^/api/':
            allow_origin: ['*']
            allow_headers: ['*']
            allow_methods: ['POST', 'PUT', 'GET', 'DELETE', 'PATCH']
            max_age: 3600
```

---

## 🎨 Configuration de l'Admin (EasyAdmin)

### src/Controller/Admin/DashboardController.php

```php
<?php
namespace App\Controller\Admin;

use App\Entity\Event;
use App\Entity\User;
use App\Entity\Order;
use App\Entity\Ticket;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin_dashboard')]
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Aiolia Event - Administration')
            ->setFaviconPath('favicon.ico');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        
        yield MenuItem::section('Gestion');
        yield MenuItem::linkToCrud('Événements', 'fa fa-calendar', Event::class);
        yield MenuItem::linkToCrud('Utilisateurs', 'fa fa-users', User::class);
        yield MenuItem::linkToCrud('Commandes', 'fa fa-shopping-cart', Order::class);
        yield MenuItem::linkToCrud('Billets', 'fa fa-ticket', Ticket::class);
        
        yield MenuItem::section('Statistiques');
        yield MenuItem::linkToRoute('Rapports', 'fa fa-chart-bar', 'admin_reports');
        
        yield MenuItem::section('Système');
        yield MenuItem::linkToRoute('Paramètres', 'fa fa-cog', 'admin_settings');
        yield MenuItem::linkToLogout('Déconnexion', 'fa fa-sign-out');
    }
}
```

---

## 🛣️ Configuration des Routes

### config/routes/api.yaml

```yaml
# API Auth
api_auth_login:
    path: /api/auth/login
    controller: Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler

api_auth_register:
    path: /api/auth/register
    controller: App\Controller\Api\AuthController::register
    methods: [POST]

api_auth_me:
    path: /api/auth/me
    controller: App\Controller\Api\AuthController::me
    methods: [GET]

# API Events
api_events:
    path: /api/events
    controller: App\Controller\Api\EventController
    methods: [GET, POST]

api_event_show:
    path: /api/events/{slug}
    controller: App\Controller\Api\EventController::show
    methods: [GET]

# API Orders
api_orders:
    path: /api/orders
    controller: App\Controller\Api\OrderController
    methods: [GET, POST]

# API Tickets
api_tickets:
    path: /api/tickets
    controller: App\Controller\Api\TicketController
    methods: [GET]

# API Users
api_user_profile:
    path: /api/users/me
    controller: App\Controller\Api\UserController::profile
    methods: [GET, PUT]
```

---

## 🧪 Tests

### Créer un Test API

```bash
php bin/console make:test

# Exemple de test
php bin/phpunit tests/Api/AuthControllerTest.php
```

### tests/Api/AuthControllerTest.php

```php
<?php
namespace App\Tests\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthControllerTest extends WebTestCase
{
    public function testRegister(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'first_name' => 'Test',
            'last_name' => 'User',
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());
    }

    public function testLogin(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ]));

        $this->assertResponseIsSuccessful();
        
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $data);
    }
}
```

---

## 🚀 Lancer le Serveur

### Développement

```bash
# Serveur Symfony
symfony server:start

# OU
php -S localhost:8000 -t public/

# Workers pour queues asynchrones
php bin/console messenger:consume async -vv
```

### Production

```bash
# Optimiser
composer install --no-dev --optimize-autoloader
APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear
php bin/console cache:warmup --env=prod

# Assets (si nécessaire)
php bin/console assets:install

# Lancer avec supervisord ou systemd
```

---

## 📚 Commandes Utiles

```bash
# Créer un admin
php bin/console app:create-admin

# Calculer les statistiques
php bin/console app:calculate-statistics

# Nettoyer les paniers expirés
php bin/console app:clean-expired-carts

# Envoyer les rappels d'événements
php bin/console app:send-event-reminders

# Vider le cache
php bin/console cache:clear

# Lister les routes
php bin/console debug:router

# Lancer les tests
php bin/phpunit
```

---

## ✅ Checklist

- [ ] Symfony 7 installé
- [ ] Base de données créée
- [ ] Entités générées
- [ ] JWT configuré
- [ ] CORS configuré
- [ ] Contrôleurs API créés
- [ ] Admin EasyAdmin configuré
- [ ] Tests créés
- [ ] Documentation API générée

---

**Backend prêt !** 🎉 Passez maintenant au [Frontend Setup](FRONTEND_SETUP.md)


