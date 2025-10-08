# 🎻 Guide Complet Symfony 7 - Aiolia Event

**Version**: 1.0.0  
**Framework**: Symfony 7 + PHP 8.2+  
**ORM**: Doctrine  
**Database**: MySQL 8.0+

---

## 📦 Résumé du Projet

### ✅ Ce Qui A Été Créé

| Document | Description | Localisation |
|----------|-------------|--------------|
| **SYMFONY_SETUP.md** | Guide d'installation complet | `/SYMFONY_SETUP.md` |
| **User Entity** | Entité utilisateur complète | `/symfony/entities/User.php` |
| **Event Entity** | Entité événement complète | `/symfony/entities/Event.php` |
| **Entities Guide** | Guide de toutes les entités | `/symfony/SYMFONY_ENTITIES_GUIDE.md` |

### 📊 Architecture Complète

```
Aiolia Event (Symfony 7)
├── Base de données (60+ tables)
│   ├── schema.sql          ✅ Créé
│   ├── triggers.sql        ✅ Créé
│   ├── procedures.sql      ✅ Créé
│   └── seeds.sql           ✅ Créé
│
├── Documentation (200+ pages)
│   ├── CONCEPTION_SQL.md   ✅ Créé
│   ├── README.md           ✅ Créé
│   ├── MIGRATION_GUIDE.md  ✅ Créé
│   └── UML_DIAGRAMS.md     ✅ Créé
│
└── Symfony 7
    ├── Entities (60+)
    │   ├── User.php        ✅ Créé
    │   ├── Event.php       ✅ Créé
    │   └── 58+ autres      📝 À créer
    │
    ├── Controllers         📝 À créer
    ├── Services            📝 À créer
    ├── Repositories        📝 À générer
    └── Tests               📝 À créer
```

---

## 🚀 Démarrage Rapide

### 1. Installation

```bash
# Naviguer vers le projet
cd /home/aina/Documents/MyProject/Aiolia-event

# Créer le projet Symfony
symfony new . --version=7.0 --webapp

# Installer les dépendances
composer require symfony/orm-pack
composer require symfony/maker-bundle --dev
composer require lexik/jwt-authentication-bundle
composer require api-platform/core
# ... (voir SYMFONY_SETUP.md pour la liste complète)
```

### 2. Configuration Base de Données

```bash
# Copier le fichier .env
cp .env .env.local

# Éditer .env.local
nano .env.local
```

Configurer :
```env
DATABASE_URL="mysql://root:password@127.0.0.1:3306/aiolia_event?serverVersion=8.0&charset=utf8mb4"
```

### 3. Créer la Base de Données

```bash
# Option 1 : Importer le schéma SQL existant (RECOMMANDÉ)
mysql -u root -p < database/schema.sql
mysql -u root -p aiolia_event < database/triggers.sql
mysql -u root -p aiolia_event < database/procedures.sql
mysql -u root -p aiolia_event < database/seeds.sql

# Option 2 : Utiliser Doctrine (après avoir créé toutes les entités)
php bin/console doctrine:database:create
php bin/console doctrine:schema:create
```

### 4. Générer les Entités depuis la BDD Existante

```bash
# Générer toutes les entités depuis la base existante
php bin/console doctrine:mapping:import "App\Entity" annotation --path=src/Entity

# Générer les getters/setters
php bin/console make:entity --regenerate App
```

### 5. Configurer JWT

```bash
# Générer les clés JWT
mkdir -p config/jwt
openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout

# Ajouter au .env.local
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_passphrase_here
```

### 6. Lancer le Serveur

```bash
symfony server:start
```

Votre API est maintenant disponible sur `https://127.0.0.1:8000`

---

## 📁 Structure Recommandée

```
Aiolia-event/
│
├── config/
│   ├── packages/
│   │   ├── doctrine.yaml
│   │   ├── security.yaml
│   │   ├── lexik_jwt_authentication.yaml
│   │   └── api_platform.yaml
│   ├── routes/
│   │   └── api.yaml
│   └── services.yaml
│
├── src/
│   ├── Controller/
│   │   ├── Api/
│   │   │   ├── AuthController.php
│   │   │   ├── EventController.php
│   │   │   ├── TicketController.php
│   │   │   ├── OrderController.php
│   │   │   └── UserController.php
│   │   └── Admin/
│   │       └── DashboardController.php
│   │
│   ├── Entity/
│   │   ├── User.php                 ✅
│   │   ├── Event.php                ✅
│   │   ├── Ticket.php
│   │   ├── Order.php
│   │   └── ... (58+ autres)
│   │
│   ├── Repository/
│   │   ├── UserRepository.php
│   │   ├── EventRepository.php
│   │   └── ...
│   │
│   ├── Service/
│   │   ├── Auth/
│   │   │   ├── AuthenticationService.php
│   │   │   └── JwtService.php
│   │   ├── Event/
│   │   │   ├── EventService.php
│   │   │   └── EventSearchService.php
│   │   ├── Ticket/
│   │   │   ├── TicketService.php
│   │   │   ├── QrCodeService.php
│   │   │   └── DynamicPricingService.php
│   │   ├── Payment/
│   │   │   ├── PaymentService.php
│   │   │   ├── OrangeMoneyService.php
│   │   │   ├── AirtelMoneyService.php
│   │   │   └── TelmaMoneyService.php
│   │   ├── Notification/
│   │   │   ├── NotificationService.php
│   │   │   ├── EmailService.php
│   │   │   └── PushNotificationService.php
│   │   └── Statistics/
│   │       └── StatisticsService.php
│   │
│   ├── Security/
│   │   ├── Voter/
│   │   │   ├── EventVoter.php
│   │   │   └── OrderVoter.php
│   │   └── UserProvider.php
│   │
│   ├── EventListener/
│   │   ├── JWTCreatedListener.php
│   │   ├── OrderCompletedListener.php
│   │   └── TicketGeneratedListener.php
│   │
│   ├── Command/
│   │   ├── CreateAdminCommand.php
│   │   ├── CalculateStatisticsCommand.php
│   │   ├── CleanExpiredCartsCommand.php
│   │   └── SendEventRemindersCommand.php
│   │
│   ├── DataFixtures/
│   │   ├── UserFixtures.php
│   │   ├── EventCategoryFixtures.php
│   │   └── EventFixtures.php
│   │
│   ├── DTO/
│   │   ├── Auth/
│   │   │   ├── LoginRequest.php
│   │   │   └── RegisterRequest.php
│   │   ├── Event/
│   │   │   └── CreateEventRequest.php
│   │   └── Order/
│   │       └── CheckoutRequest.php
│   │
│   └── Kernel.php
│
├── tests/
│   ├── Unit/
│   ├── Integration/
│   └── Api/
│
├── database/                        ✅ Complet
│   ├── schema.sql
│   ├── triggers.sql
│   ├── procedures.sql
│   ├── seeds.sql
│   ├── CONCEPTION_SQL.md
│   └── ...
│
├── .env
├── .env.local
├── composer.json
└── symfony.lock
```

---

## 🎯 Exemples de Contrôleurs

### AuthController.php

```php
<?php
namespace App\Controller\Api;

use App\Entity\User;
use App\Service\Auth\AuthenticationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/auth', name: 'api_auth_')]
class AuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator,
        private AuthenticationService $authService
    ) {}

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $user = new User();
        $user->setEmail($data['email'] ?? '');
        $user->setFirstName($data['first_name'] ?? '');
        $user->setLastName($data['last_name'] ?? '');
        $user->setPhone($data['phone'] ?? '');

        // Hash password
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $data['password'] ?? ''
        );
        $user->setPassword($hashedPassword);

        // Validate
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        // Save
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Inscription réussie',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'full_name' => $user->getFullName()
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        // Géré par LexikJWTAuthenticationBundle
        // Configuration dans security.yaml
        return $this->json(['message' => 'Login endpoint']);
    }

    #[Route('/logout', name: 'logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        // Révoquer le refresh token si utilisé
        return $this->json(['message' => 'Déconnexion réussie']);
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'full_name' => $user->getFullName(),
            'role' => $user->getRole()->value,
            'wallet' => [
                'loyalty_points' => $user->getWallet()?->getLoyaltyPoints(),
                'balance' => $user->getWallet()?->getBalance()
            ]
        ]);
    }
}
```

### EventController.php

```php
<?php
namespace App\Controller\Api;

use App\Entity\Event;
use App\Repository\EventRepository;
use App\Service\Event\EventService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/api/events', name: 'api_events_')]
class EventController extends AbstractController
{
    public function __construct(
        private EventRepository $eventRepository,
        private EventService $eventService,
        private PaginatorInterface $paginator
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);

        // Filters
        $filters = [
            'query' => $request->query->get('q'),
            'category' => $request->query->get('category'),
            'location' => $request->query->get('location'),
            'start_date' => $request->query->get('start_date'),
            'end_date' => $request->query->get('end_date'),
            'min_price' => $request->query->get('min_price'),
            'max_price' => $request->query->get('max_price'),
            'sort_by' => $request->query->get('sort_by', 'start_date'),
            'sort_direction' => $request->query->get('sort_direction', 'ASC'),
        ];

        $queryBuilder = $this->eventRepository->searchWithFilters($filters);

        $pagination = $this->paginator->paginate(
            $queryBuilder,
            $page,
            $limit
        );

        return $this->json([
            'data' => $pagination->getItems(),
            'meta' => [
                'current_page' => $pagination->getCurrentPageNumber(),
                'total_pages' => ceil($pagination->getTotalItemCount() / $limit),
                'total_items' => $pagination->getTotalItemCount(),
                'items_per_page' => $limit,
            ]
        ], Response::HTTP_OK, [], ['groups' => ['event:read']]);
    }

    #[Route('/{slug}', name: 'show', methods: ['GET'])]
    public function show(string $slug): JsonResponse
    {
        $event = $this->eventRepository->findOneBy(['slug' => $slug]);

        if (!$event) {
            return $this->json(['error' => 'Événement non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Incrémenter les vues
        $event->incrementViews();
        $this->eventRepository->save($event, true);

        return $this->json($event, Response::HTTP_OK, [], ['groups' => ['event:read']]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ORGANIZER');

        $data = json_decode($request->getContent(), true);

        $event = $this->eventService->createEvent($data, $this->getUser());

        return $this->json($event, Response::HTTP_CREATED, [], ['groups' => ['event:read']]);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(Event $event, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('edit', $event);

        $data = json_decode($request->getContent(), true);

        $updatedEvent = $this->eventService->updateEvent($event, $data);

        return $this->json($updatedEvent, Response::HTTP_OK, [], ['groups' => ['event:read']]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Event $event): JsonResponse
    {
        $this->denyAccessUnlessGranted('delete', $event);

        $this->eventRepository->remove($event, true);

        return $this->json(['message' => 'Événement supprimé'], Response::HTTP_OK);
    }

    #[Route('/{id}/publish', name: 'publish', methods: ['POST'])]
    public function publish(Event $event): JsonResponse
    {
        $this->denyAccessUnlessGranted('publish', $event);

        $event->publish();
        $this->eventRepository->save($event, true);

        return $this->json(['message' => 'Événement publié'], Response::HTTP_OK);
    }
}
```

---

## 🛠️ Exemples de Services

### EventService.php

```php
<?php
namespace App\Service\Event;

use App\Entity\Event;
use App\Entity\User;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class EventService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventRepository $eventRepository,
        private SluggerInterface $slugger
    ) {}

    public function createEvent(array $data, User $organizer): Event
    {
        $event = new Event();
        $event->setOrganizer($organizer);
        $event->setTitle($data['title']);
        $event->setDescription($data['description'] ?? null);
        $event->setLocation($data['location'] ?? null);
        $event->setStartDate(new \DateTime($data['start_date']));
        $event->setEndDate(new \DateTime($data['end_date']));
        
        // Le slug sera généré automatiquement par Gedmo
        
        $this->entityManager->persist($event);
        $this->entityManager->flush();

        return $event;
    }

    public function updateEvent(Event $event, array $data): Event
    {
        if (isset($data['title'])) {
            $event->setTitle($data['title']);
        }
        if (isset($data['description'])) {
            $event->setDescription($data['description']);
        }
        // ... autres champs

        $this->entityManager->flush();

        return $event;
    }
}
```

---

## 🔐 Configuration de Sécurité Complète

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

    access_control:
        # Public endpoints
        - { path: ^/api/auth/login, roles: PUBLIC_ACCESS }
        - { path: ^/api/auth/register, roles: PUBLIC_ACCESS }
        - { path: ^/api/events, roles: PUBLIC_ACCESS, methods: [GET] }
        
        # Authenticated endpoints
        - { path: ^/api/orders, roles: ROLE_USER }
        - { path: ^/api/tickets, roles: ROLE_USER }
        - { path: ^/api/events, roles: ROLE_ORGANIZER, methods: [POST, PUT, DELETE] }
        
        # Admin endpoints
        - { path: ^/api/admin, roles: ROLE_ADMIN }

    role_hierarchy:
        ROLE_CO_ORGANIZER: ROLE_USER
        ROLE_ORGANIZER: ROLE_CO_ORGANIZER
        ROLE_ADMIN: ROLE_ORGANIZER
```

---

## 📚 Ressources & Documentation

### Documentation Créée

| Document | Description | Pages |
|----------|-------------|-------|
| [SYMFONY_SETUP.md](SYMFONY_SETUP.md) | Guide d'installation | ~15 |
| [SYMFONY_ENTITIES_GUIDE.md](symfony/SYMFONY_ENTITIES_GUIDE.md) | Guide des entités | ~25 |
| [CONCEPTION_SQL.md](database/CONCEPTION_SQL.md) | Conception complète | ~50 |
| [UML_DIAGRAMS.md](database/UML_DIAGRAMS.md) | Diagrammes UML | ~45 |

### Ressources Externes

- [Symfony 7 Documentation](https://symfony.com/doc/7.0/index.html)
- [Doctrine ORM](https://www.doctrine-project.org/)
- [API Platform](https://api-platform.com/)
- [JWT Authentication Bundle](https://github.com/lexik/LexikJWTAuthenticationBundle)

---

## ✅ Checklist de Développement

### Phase 1 : Setup (1 semaine)
- [x] Conception SQL complète
- [x] Documentation UML
- [x] Guide d'installation Symfony
- [ ] Installation Symfony 7
- [ ] Configuration base de données
- [ ] Génération clés JWT
- [ ] Configuration Redis

### Phase 2 : Entités (1 semaine)
- [x] User entity
- [x] Event entity
- [ ] 58+ autres entités
- [ ] Repositories personnalisés
- [ ] Fixtures de test

### Phase 3 : API Core (3 semaines)
- [ ] Authentification (login, register, JWT)
- [ ] CRUD Événements
- [ ] CRUD Billets
- [ ] Système de commandes
- [ ] Intégration paiement Mobile Money

### Phase 4 : Fonctionnalités Avancées (4 semaines)
- [ ] Tarification dynamique
- [ ] Programme de fidélité
- [ ] Notifications multi-canal
- [ ] Système de parrainage
- [ ] Mini-jeu
- [ ] Analytics & statistiques

### Phase 5 : Tests & Déploiement (2 semaines)
- [ ] Tests unitaires
- [ ] Tests d'intégration
- [ ] Tests API
- [ ] CI/CD
- [ ] Déploiement production

---

## 🎯 Prochaines Étapes

1. **Installer Symfony 7**
   ```bash
   symfony new Aiolia-event --version=7.0 --webapp
   ```

2. **Importer la base de données existante**
   ```bash
   mysql -u root -p < database/schema.sql
   ```

3. **Générer les entités depuis la BDD**
   ```bash
   php bin/console doctrine:mapping:import "App\Entity" annotation
   ```

4. **Créer les contrôleurs et services**
   - Suivre les exemples fournis
   - Utiliser `make:controller` et `make:service`

5. **Tester l'API**
   ```bash
   symfony server:start
   curl http://localhost:8000/api/events
   ```

---

## 🎉 Félicitations !

Vous avez maintenant :
- ✅ Une architecture de base de données professionnelle
- ✅ 200+ pages de documentation complète
- ✅ Des guides Symfony 7 détaillés
- ✅ Des exemples de code prêts à l'emploi
- ✅ Une roadmap claire pour le développement

**Le projet Aiolia Event est prêt à être développé avec Symfony 7 !** 🚀

---

**Besoin d'aide ?** Consultez la documentation ou les exemples fournis !

