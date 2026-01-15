# 🏗️ Architecture N-Tier du Projet Aiolia Event

## 📋 Table des matières

1. [Vue d'ensemble](#vue-densemble)
2. [Architecture N-Tier](#architecture-n-tier)
3. [FrontOffice (Aiolia-event-front)](#frontoffice-aiolia-event-front)
4. [BackOffice (Aiolia-event-back)](#backoffice-aiolia-event-back)
5. [Services externes](#services-externes)
6. [Communication entre les couches](#communication-entre-les-couches)
7. [Avantages de cette architecture](#avantages-de-cette-architecture)

---

## 🎯 Vue d'ensemble

Le projet **Aiolia Event** suit une **architecture N-tier (multi-couches)** qui sépare clairement les différentes responsabilités de l'application. Cette architecture est appliquée à **deux applications distinctes** :

- **FrontOffice** (`Aiolia-event-front`) : Application destinée aux utilisateurs finaux
- **BackOffice** (`Aiolia-event-back`) : Application d'administration pour les organisateurs et administrateurs

Les deux applications partagent la **même base de données PostgreSQL** mais sont déployées séparément, ce qui permet une **scalabilité indépendante** et une **sécurité renforcée**.

---

## 🏛️ Architecture N-Tier

### Définition

L'architecture N-tier sépare les fonctionnalités de l'application en **couches distinctes**, chacune ayant une responsabilité précise :

| Couche | Responsabilité | Composants Symfony |
|--------|----------------|-------------------|
| **Présentation** | Interface utilisateur et interactions | Controllers + Templates (Twig) |
| **Métier** | Logique applicative et règles métier | Services |
| **Persistance** | Stockage et accès aux données | Repositories + Entities (Doctrine) |

### Principe de séparation

Chaque couche peut être **déployée sur des machines distinctes**, ce qui favorise :
- ✅ **Scalabilité** : Mise à l'échelle indépendante de chaque couche
- ✅ **Sécurité** : Isolation des couches sensibles
- ✅ **Maintenance** : Gestion indépendante des composants
- ✅ **Testabilité** : Tests unitaires par couche

---

## 🎨 FrontOffice (Aiolia-event-front)

### Vue d'ensemble

Le **FrontOffice** est l'application publique destinée aux utilisateurs finaux. Il permet de :
- Découvrir et rechercher des événements
- Acheter des billets
- Gérer son profil et son historique
- Participer aux jeux et fonctionnalités sociales

### Architecture en couches

#### 1️⃣ **Couche de Présentation**

**Composants :**
- **13 Contrôleurs** : Gestion des routes et actions HTTP
- **37 Templates Twig** : Interface utilisateur (HTML/CSS/JS)

**Exemples de contrôleurs :**
```php
// src/Controller/EventController.php
class EventController extends AbstractController
{
    #[Route('/events', name: 'event_list')]
    public function list(EventRepository $eventRepository): Response
    {
        $events = $eventRepository->findUpcomingEvents();
        return $this->render('event/list.html.twig', ['events' => $events]);
    }
}
```

**Responsabilités :**
- ✅ Recevoir les requêtes HTTP
- ✅ Valider les données d'entrée
- ✅ Appeler les services métier
- ✅ Rendre les templates avec les données
- ✅ Gérer les erreurs HTTP

**Templates organisés par domaine :**
```
templates/
├── home/          # Page d'accueil
├── event/         # Liste et détails d'événements
├── ticket/        # Panier, achat, billets
├── profile/       # Profil utilisateur, historique, statistiques
├── auth/          # Connexion, inscription
├── organizer/     # Espace organisateur
└── emails/        # Templates d'emails
```

#### 2️⃣ **Couche Métier**

**Composants :**
- **14 Services** : Logique métier réutilisable

**Exemples de services :**

```php
// src/Service/PaymentService.php
class PaymentService
{
    public function __construct(
        private MvolaPaymentClient $mvolaClient,
        private OrderRepository $orderRepository
    ) {}
    
    public function processPayment(Order $order): array
    {
        // Logique métier de paiement
        $result = $this->mvolaClient->initiateTransaction(...);
        // Traitement des résultats
        return $result;
    }
}
```

**Services principaux :**
- `PaymentService` : Gestion des paiements MVola
- `NotificationService` : Envoi de notifications
- `WalletService` : Gestion du portefeuille utilisateur
- `TicketChanceService` : Logique du jeu Ticket Chance
- `LoyaltyPointsService` : Système de points de fidélité
- `EventReminderService` : Rappels automatiques
- `CloudinaryService` : Gestion des images

**Responsabilités :**
- ✅ Implémenter la logique métier
- ✅ Valider les règles business
- ✅ Orchestrer les appels aux repositories
- ✅ Gérer les transactions
- ✅ Appeler les services externes (APIs)

#### 3️⃣ **Couche de Persistance**

**Composants :**
- **9 Entités Doctrine** : Modèles des tables principales
- **17 Repositories** : Accès optimisé aux données

**Exemples d'entités :**
```php
// src/Entity/Event.php
#[Entity]
class Event
{
    #[Id, GeneratedValue, Column]
    private ?int $id = null;
    
    #[Column]
    private string $title;
    
    #[OneToMany(mappedBy: 'event', targetEntity: Ticket::class)]
    private Collection $tickets;
}
```

**Exemples de repositories :**
```php
// src/Repository/OrderRepository.php
class OrderRepository
{
    public function findSpendingChartData(int $userId, int $months = 12): array
    {
        $sql = "SELECT ... FROM aiolia.orders WHERE user_id = :user_id";
        return $this->connection->executeQuery($sql, ['user_id' => $userId])
            ->fetchAllAssociative();
    }
}
```

**Responsabilités :**
- ✅ Accès à la base de données
- ✅ Requêtes SQL optimisées
- ✅ Mapping objet-relationnel (ORM)
- ✅ Gestion des transactions
- ✅ Cache des requêtes fréquentes

**Approche hybride :**
- **Doctrine ORM** pour les 9 tables principales (User, Event, Ticket, etc.)
- **SQL direct** pour les 45 autres tables (logs, statistiques, cache)

---

## 🔧 BackOffice (Aiolia-event-back)

### Vue d'ensemble

Le **BackOffice** est l'application d'administration destinée aux organisateurs et administrateurs. Il permet de :
- Gérer les événements (CRUD complet)
- Valider les utilisateurs
- Consulter les statistiques et rapports
- Gérer les médias et promotions

### Architecture en couches

#### 1️⃣ **Couche de Présentation**

**Composants :**
- **14 Contrôleurs** : Gestion des routes admin
- **42 Templates Twig** : Interface d'administration

**Exemples de contrôleurs :**
```php
// src/Controller/EventController.php
#[Route('/events')]
class EventController extends AbstractController
{
    #[Route('/new', name: 'app_event_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $event = new Event();
        $form = $this->createForm(EventType::class, $event);
        // ...
    }
}
```

**Templates organisés :**
```
templates/
├── admin/         # Dashboard admin, gestion utilisateurs
├── event/         # CRUD événements
├── reports/       # Statistiques et rapports
└── settings/      # Paramètres système
```

#### 2️⃣ **Couche Métier**

**Composants :**
- **7 Services** : Logique métier spécifique au BackOffice

**Services principaux :**
- `EventService` : Gestion complète des événements
- `MediaService` : Gestion des médias (Cloudinary)
- `AuditLogService` : Traçabilité des actions
- `UserStatsService` : Calculs statistiques
- `SlugService` : Génération de slugs

#### 3️⃣ **Couche de Persistance**

**Composants :**
- **7 Entités Doctrine** : Modèles BackOffice
- **7 Repositories** : Accès aux données

**Entités BackOffice :**
- `Event` : Événements
- `EventCategory` : Catégories
- `EventMedia` : Médias associés
- `User` : Utilisateurs
- `UserValidationRequest` : Demandes de validation
- `AuditLog` : Logs d'audit
- `Role` : Rôles utilisateurs

---

## 🌐 Services externes

### Intégration via API HTTP

En complément des trois couches classiques, l'application exploite des **services spécialisés externes**, accessibles via des **API HTTP**. Cette approche permet :

- ✅ **Extensibilité** : Ajout de services sans modifier le cœur de l'application
- ✅ **Indépendance** : Services déployés séparément
- ✅ **Sécurité** : Communication sécurisée via HTTPS
- ✅ **Scalabilité** : Services externes gèrent leur propre charge

### Services externes utilisés

#### 1. **MVola Payment API** (Paiement mobile)

**Service :** `MvolaPaymentClient` (FrontOffice uniquement)

**Localisation :** `Aiolia-event-front/src/Service/MvolaPaymentClient.php`

**Communication :**
```php
// src/Service/MvolaPaymentClient.php
class MvolaPaymentClient
{
    private HttpClientInterface $httpClient;
    
    public function initiateTransaction(array $payload): array
    {
        $response = $this->httpClient->request('POST', 
            'https://devapi.mvola.mg/merchant/v1/payments/initiate',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload
            ]
        );
        return $response->toArray();
    }
}
```

**Flux :**
1. **FrontOffice** → `PaymentService` → `MvolaPaymentClient`
2. `MvolaPaymentClient` → API MVola (HTTPS)
3. MVola → Callback → `MvolaController` (webhook dans FrontOffice)

**Connexion :** FrontOffice (Couche Métier) → MVola API via HTTPS

**Avantages :**
- ✅ Paiement sécurisé sans stocker les données bancaires
- ✅ Conformité PCI-DSS (déléguée à MVola)
- ✅ Mises à jour automatiques de l'API

#### 2. **Cloudinary** (Gestion d'images)

**Service :** `CloudinaryService` (FrontOffice ET BackOffice)

**Localisation :**
- FrontOffice : `Aiolia-event-front/src/Service/CloudinaryService.php`
- BackOffice : `Aiolia-event-back/src/Service/CloudinaryService.php`

**Communication :**
```php
// src/Service/CloudinaryService.php
class CloudinaryService
{
    public function uploadImage(string $filePath): array
    {
        $response = $this->httpClient->request('POST',
            'https://api.cloudinary.com/v1_1/{cloud_name}/image/upload',
            [
                'body' => [
                    'file' => fopen($filePath, 'r'),
                    'upload_preset' => $this->uploadPreset,
                ]
            ]
        );
        return $response->toArray();
    }
}
```

**Connexions :**
- **FrontOffice** (Couche Métier) → Cloudinary API via HTTPS
- **BackOffice** (Couche Métier) → Cloudinary API via HTTPS

**Avantages :**
- ✅ Optimisation automatique des images
- ✅ CDN global pour performance
- ✅ Transformations à la volée (redimensionnement, compression)

#### 3. **Redis** (Cache et sessions)

**Service :** `CacheService` (FrontOffice)

**Localisation :** `Aiolia-event-front/src/Service/CacheService.php`

**Communication :**
```php
// src/Service/CacheService.php
class CacheService
{
    public function getCachedUpcomingEvents(callable $callback, int $limit = 6, int $ttl = 3600): array
    {
        return $this->cacheEvents->get(
            "upcoming_events_{$limit}",
            function (ItemInterface $item) use ($callback, $ttl) {
                $item->expiresAfter($ttl);
                return $callback();
            }
        );
    }
}
```

**Connexions :**
- **FrontOffice** (Couche Métier) → Redis via Predis (TCP/IP)
- Utilisé pour : Cache des événements, résultats de recherche, statistiques

**Pools de cache :**
- `cache.events` : Événements populaires (TTL: 1 heure)
- `cache.search` : Résultats de recherche (TTL: 30 minutes)
- `cache.stats` : Statistiques (TTL: 30 minutes)
- `cache.sessions` : Sessions utilisateur (TTL: 24 heures)

**Avantages :**
- ✅ Performance ultra-rapide (microsecondes)
- ✅ Réduction de la charge sur PostgreSQL
- ✅ Cache intelligent avec TTL automatique
- ✅ Invalidation sélective du cache

#### 4. **Base de données PostgreSQL** (Partagée)

**Communication :**
- FrontOffice et BackOffice partagent la **même base de données**
- Accès via **Doctrine ORM** ou **SQL direct**
- Schéma : `aiolia` (54 tables)

**Avantages :**
- ✅ Cohérence des données entre FrontOffice et BackOffice
- ✅ Transactions ACID
- ✅ Performance optimisée avec index

---

## 🔄 Communication entre les couches

### Flux de données standard

```
┌─────────────────────────────────────────────────────────┐
│                    COUCHE PRÉSENTATION                   │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │ Controllers  │  │  Templates    │  │   Routes     │ │
│  └──────┬───────┘  └──────────────┘  └──────────────┘ │
└─────────┼───────────────────────────────────────────────┘
          │ Appel des services
          ▼
┌─────────────────────────────────────────────────────────┐
│                      COUCHE MÉTIER                       │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │ PaymentSvc   │  │ Notification  │  │  WalletSvc   │ │
│  │              │  │     Svc      │  │              │ │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘ │
└─────────┼─────────────────┼─────────────────┼───────────┘
          │                 │                 │
          │ Appel repos      │ Appel repos     │ Appel repos
          ▼                 ▼                 ▼
┌─────────────────────────────────────────────────────────┐
│                   COUCHE PERSISTANCE                    │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │ Repositories │  │   Entities    │  │   Doctrine   │ │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘ │
└─────────┼─────────────────┼─────────────────┼───────────┘
          │                 │                 │
          ▼                 ▼                 ▼
    ┌──────────────────────────────────────┐
    │     PostgreSQL Database (aiolia)     │
    │           54 tables                   │
    └──────────────────────────────────────┘
          ▲
          │ Cache (via Redis)
          │
    ┌──────────────────────────────────────┐
    │         Redis Cache                  │
    │  (Events, Search, Stats, Sessions)   │
    └──────────────────────────────────────┘
```

### Exemple concret : Achat de billet

**1. Couche Présentation (Controller)**
```php
// TicketController.php
#[Route('/ticket/purchase', name: 'ticket_purchase')]
public function purchase(Request $request, PaymentService $paymentService)
{
    $order = $this->getOrderFromSession();
    $result = $paymentService->processPayment($order);
    return $this->render('ticket/success.html.twig', ['result' => $result]);
}
```

**2. Couche Métier (Service)**
```php
// PaymentService.php
public function processPayment(Order $order): array
{
    // Validation métier
    if ($order->getTotalAmount() <= 0) {
        throw new InvalidArgumentException('Montant invalide');
    }
    
    // Appel service externe
    $mvolaResult = $this->mvolaClient->initiateTransaction([
        'amount' => $order->getTotalAmount(),
        // ...
    ]);
    
    // Sauvegarde via repository
    $this->orderRepository->save($order);
    
    return $mvolaResult;
}
```

**3. Couche Persistance (Repository)**
```php
// OrderRepository.php
public function save(Order $order): void
{
    $this->entityManager->persist($order);
    $this->entityManager->flush();
}
```

**4. Service Externe (MVola)**
```php
// MvolaPaymentClient.php
public function initiateTransaction(array $payload): array
{
    $response = $this->httpClient->request('POST', 
        'https://devapi.mvola.mg/merchant/v1/payments/initiate',
        ['json' => $payload]
    );
    return $response->toArray();
}
```

---

## ✅ Avantages de cette architecture

### 1. **Séparation des responsabilités**

Chaque couche a un rôle précis :
- **Présentation** : Interface utilisateur uniquement
- **Métier** : Logique applicative pure
- **Persistance** : Accès données uniquement

### 2. **Réutilisabilité**

Les services peuvent être réutilisés :
- Par plusieurs contrôleurs
- Par des commandes CLI
- Par des API REST (futur)

### 3. **Testabilité**

Tests unitaires par couche :
```php
// Test du service métier (sans dépendre du controller)
public function testPaymentService()
{
    $mockRepository = $this->createMock(OrderRepository::class);
    $service = new PaymentService($mockRepository);
    // Test de la logique métier
}
```

### 4. **Scalabilité**

Déploiement indépendant :
- FrontOffice sur serveur 1
- BackOffice sur serveur 2
- Base de données sur serveur 3
- Services externes (MVola, Cloudinary) sur leurs propres serveurs

### 5. **Sécurité**

Isolation des couches sensibles :
- BackOffice accessible uniquement aux admins
- FrontOffice accessible publiquement
- Base de données protégée par firewall
- Services externes via HTTPS uniquement

### 6. **Maintenance**

Modifications isolées :
- Changer un template n'affecte pas la logique métier
- Modifier un service n'affecte pas la base de données
- Ajouter une fonctionnalité = ajouter un service

### 7. **Extensibilité**

Ajout de services externes sans modifier le cœur :
- Nouveau service de paiement ? → Nouveau `PaymentClient`
- Nouveau service d'email ? → Nouveau `EmailService`
- Le reste de l'application reste inchangé

---

## 📊 Résumé de l'architecture

| Composant | FrontOffice | BackOffice | Partagé |
|-----------|-------------|------------|---------|
| **Contrôleurs** | 13 | 14 | - |
| **Templates** | 37 | 42 | - |
| **Services** | 14 | 7 | - |
| **Repositories** | 17 | 7 | - |
| **Entités** | 9 | 7 | - |
| **Base de données** | - | - | ✅ (PostgreSQL) |
| **Cache** | ✅ Redis | - | - |
| **Services externes** | ✅ MVola API<br>✅ Cloudinary | ✅ Cloudinary | - |

---

## 🎯 Conclusion

L'architecture N-tier du projet **Aiolia Event** respecte les **bonnes pratiques** de développement :

1. ✅ **Séparation claire** des responsabilités
2. ✅ **Réutilisabilité** des composants
3. ✅ **Testabilité** facilitée
4. ✅ **Scalabilité** indépendante
5. ✅ **Sécurité** renforcée
6. ✅ **Extensibilité** via services externes

Cette architecture permet une **maintenance aisée**, une **évolution progressive** et une **performance optimale** de l'application.

---

**Document créé le :** 2025-01-13  
**Version :** 1.0  
**Auteur :** Équipe Aiolia Event
