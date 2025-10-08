# 🎻 Guide Complet des Entités Symfony 7 - Aiolia Event

Ce guide présente toutes les entités Doctrine nécessaires pour le projet Aiolia Event.

---

## 📊 Vue d'Ensemble

### Statistiques
- **60+ entités Doctrine** à créer
- **20 modules fonctionnels**
- **Relations complexes** (OneToMany, ManyToOne, ManyToMany)
- **Enums PHP 8.2+** pour les statuts
- **Lifecycle Callbacks** pour automatisation

---

## 🏗️ Entités Créées

### ✅ Entités Principales

| Entité | Fichier | Statut | Relations |
|--------|---------|--------|-----------|
| **User** | `symfony/entities/User.php` | ✅ Créé | Wallet, Orders, Tickets, Events |
| **Event** | `symfony/entities/Event.php` | ✅ Créé | Category, TicketCategories, Media |

---

## 📝 Liste Complète des Entités à Créer

### 1. Module Authentification & Utilisateurs

```bash
php bin/console make:entity User                    # ✅ Fait
php bin/console make:entity RefreshToken
php bin/console make:entity Permission
php bin/console make:entity RolePermission
```

**RefreshToken.php** (Exemple) :
```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'refresh_tokens')]
class RefreshToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 500, unique: true)]
    private ?string $token = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $expiresAt = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isRevoked = false;

    // Getters & Setters...
}
```

### 2. Module Événements

```bash
php bin/console make:entity Event                   # ✅ Fait
php bin/console make:entity EventCategory
php bin/console make:entity EventMedia
php bin/console make:entity EventTeam
php bin/console make:entity EventStatistics
```

**EventCategory.php** (Exemple) :
```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity]
#[ORM\Table(name: 'event_categories')]
class EventCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 100, unique: true)]
    #[Gedmo\Slug(fields: ['name'])]
    private ?string $slug = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $icon = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $displayOrder = 0;

    // Getters & Setters...
}
```

### 3. Module Billets

```bash
php bin/console make:entity TicketCategory
php bin/console make:entity Ticket
php bin/console make:entity TicketPriceHistory
php bin/console make:entity DynamicPricingRule
php bin/console make:entity TicketTransfer
```

**Ticket.php** (Exemple) :
```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'tickets')]
#[ORM\HasLifecycleCallbacks]
class Ticket
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TicketCategory::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?TicketCategory $ticketCategory = null;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'tickets')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $order = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'tickets')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $ticketNumber = null;

    #[ORM\Column(length: 500, unique: true)]
    private ?string $qrCodeData = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $qrCodeImageUrl = null;

    #[ORM\Column(length: 20, enumType: TicketStatus::class, options: ['default' => 'valid'])]
    private TicketStatus $status = TicketStatus::VALID;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $originalOwner = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $currentOwner = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $checkInAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $checkInBy = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    #[ORM\PrePersist]
    public function generateTicketNumber(): void
    {
        if (!$this->ticketNumber) {
            $this->ticketNumber = 'TKT-' . strtoupper(Uuid::v4()->toRfc4122());
        }
        if (!$this->qrCodeData) {
            $this->qrCodeData = 'AIOLIA-' . Uuid::v4()->toRfc4122() . '-' . $this->ticketNumber;
        }
        if (!$this->originalOwner && $this->user) {
            $this->originalOwner = $this->user;
        }
        if (!$this->currentOwner && $this->user) {
            $this->currentOwner = $this->user;
        }
    }

    // Getters & Setters...
}

enum TicketStatus: string
{
    case VALID = 'valid';
    case USED = 'used';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';
    case TRANSFERRED = 'transferred';
}
```

### 4. Module Commandes & Paiements

```bash
php bin/console make:entity Order
php bin/console make:entity OrderItem
php bin/console make:entity Payment
php bin/console make:entity Invoice
```

### 5. Module Codes Promo

```bash
php bin/console make:entity PromoCode
php bin/console make:entity PromoCodeEvent
php bin/console make:entity PromoCodeTicketCategory
php bin/console make:entity PromoCodeUsage
```

### 6. Module Panier

```bash
php bin/console make:entity Cart
php bin/console make:entity CartItem
```

### 7. Module Portefeuille & Fidélité

```bash
php bin/console make:entity Wallet
php bin/console make:entity WalletTransaction
php bin/console make:entity LoyaltyRule
```

### 8. Module Notifications

```bash
php bin/console make:entity Notification
php bin/console make:entity NotificationPreference
```

### 9. Module Avis & Reviews

```bash
php bin/console make:entity Review
php bin/console make:entity ReviewVote
```

### 10. Autres Modules

```bash
php bin/console make:entity Favorite
php bin/console make:entity SearchHistory
php bin/console make:entity EventView
php bin/console make:entity Referral
php bin/console make:entity GameParticipation
php bin/console make:entity GameSettings
php bin/console make:entity Friendship
php bin/console make:entity WaitingList
php bin/console make:entity Report
php bin/console make:entity AuditLog
php bin/console make:entity SystemSetting
php bin/console make:entity Translation
php bin/console make:entity UserStatistics
php bin/console make:entity DailySalesStats
```

---

## 🎯 Traits Réutilisables

Créez des traits pour éviter la duplication de code :

### TimestampableTrait.php

```php
<?php
namespace App\Entity\Trait;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait TimestampableTrait
{
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }
}
```

### SoftDeletableTrait.php

```php
<?php
namespace App\Entity\Trait;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait SoftDeletableTrait
{
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $deletedAt = null;

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeInterface $deletedAt): static
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function softDelete(): static
    {
        $this->deletedAt = new \DateTime();
        return $this;
    }

    public function restore(): static
    {
        $this->deletedAt = null;
        return $this;
    }
}
```

---

## 🔧 Configuration Doctrine

### config/packages/doctrine.yaml

```yaml
doctrine:
    dbal:
        url: '%env(resolve:DATABASE_URL)%'
        charset: utf8mb4
        default_table_options:
            charset: utf8mb4
            collate: utf8mb4_unicode_ci

    orm:
        auto_generate_proxy_classes: true
        naming_strategy: doctrine.orm.naming_strategy.underscore_number_aware
        auto_mapping: true
        mappings:
            App:
                is_bundle: false
                dir: '%kernel.project_dir%/src/Entity'
                prefix: 'App\Entity'
                alias: App
        
        # Filtres pour Soft Delete
        filters:
            softdeleteable:
                class: Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter
                enabled: true

when@test:
    doctrine:
        dbal:
            # "TEST_TOKEN" est remplacé par un ID unique par worker PHPUnit
            dbname_suffix: '_test%env(default::TEST_TOKEN)%'

when@prod:
    doctrine:
        orm:
            auto_generate_proxy_classes: false
            proxy_dir: '%kernel.build_dir%/doctrine/orm/Proxies'
            query_cache_driver:
                type: pool
                pool: doctrine.system_cache_pool
            result_cache_driver:
                type: pool
                pool: doctrine.result_cache_pool

    framework:
        cache:
            pools:
                doctrine.result_cache_pool:
                    adapter: cache.app
                doctrine.system_cache_pool:
                    adapter: cache.system
```

---

## 📝 Exemple de Repository

### src/Repository/EventRepository.php

```php
<?php
namespace App\Repository;

use App\Entity\Event;
use App\Entity\EventStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    public function save(Event $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Event $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouve tous les événements publiés à venir
     */
    public function findUpcoming(int $limit = 10): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.status = :status')
            ->andWhere('e.startDate > :now')
            ->setParameter('status', EventStatus::PUBLISHED)
            ->setParameter('now', new \DateTime())
            ->orderBy('e.startDate', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche d'événements avec filtres
     */
    public function searchWithFilters(array $filters = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.category', 'c')
            ->leftJoin('e.statistics', 's')
            ->andWhere('e.status = :status')
            ->setParameter('status', EventStatus::PUBLISHED);

        // Filtre par recherche texte
        if (!empty($filters['query'])) {
            $qb->andWhere('e.title LIKE :query OR e.description LIKE :query OR e.location LIKE :query')
                ->setParameter('query', '%' . $filters['query'] . '%');
        }

        // Filtre par catégorie
        if (!empty($filters['category'])) {
            $qb->andWhere('c.slug = :category')
                ->setParameter('category', $filters['category']);
        }

        // Filtre par localisation
        if (!empty($filters['location'])) {
            $qb->andWhere('e.location LIKE :location')
                ->setParameter('location', '%' . $filters['location'] . '%');
        }

        // Filtre par date
        if (!empty($filters['start_date'])) {
            $qb->andWhere('e.startDate >= :start_date')
                ->setParameter('start_date', new \DateTime($filters['start_date']));
        }

        if (!empty($filters['end_date'])) {
            $qb->andWhere('e.endDate <= :end_date')
                ->setParameter('end_date', new \DateTime($filters['end_date']));
        }

        // Filtre par prix (via les catégories de billets)
        if (isset($filters['min_price']) || isset($filters['max_price'])) {
            $qb->leftJoin('e.ticketCategories', 'tc');
            
            if (isset($filters['min_price'])) {
                $qb->andWhere('tc.price >= :min_price')
                    ->setParameter('min_price', $filters['min_price']);
            }
            
            if (isset($filters['max_price'])) {
                $qb->andWhere('tc.price <= :max_price')
                    ->setParameter('max_price', $filters['max_price']);
            }
        }

        // Tri
        $orderBy = $filters['sort_by'] ?? 'start_date';
        $orderDirection = $filters['sort_direction'] ?? 'ASC';

        switch ($orderBy) {
            case 'popularity':
                $qb->orderBy('e.viewsCount', $orderDirection);
                break;
            case 'rating':
                $qb->orderBy('s.averageRating', $orderDirection);
                break;
            case 'price':
                $qb->orderBy('tc.price', $orderDirection);
                break;
            default:
                $qb->orderBy('e.startDate', $orderDirection);
        }

        return $qb;
    }

    /**
     * Événements recommandés pour un utilisateur
     */
    public function findRecommendedForUser(int $userId, int $limit = 10): array
    {
        // Logique de recommandation basée sur :
        // - Catégories favorites
        // - Historique d'achat
        // - Recherches récentes
        // - Événements populaires

        return $this->createQueryBuilder('e')
            ->andWhere('e.status = :status')
            ->andWhere('e.startDate > :now')
            ->setParameter('status', EventStatus::PUBLISHED)
            ->setParameter('now', new \DateTime())
            // Logique de scoring complexe ici
            ->orderBy('e.viewsCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les événements d'un organisateur
     */
    public function findByOrganizer(int $organizerId): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organizer = :organizer')
            ->setParameter('organizer', $organizerId)
            ->orderBy('e.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
```

---

## 🎯 Commandes Console Utiles

### Créer toutes les entités automatiquement

```bash
#!/bin/bash
# generate_all_entities.sh

# Tableau des entités à créer
entities=(
    "RefreshToken"
    "Permission"
    "RolePermission"
    "EventCategory"
    "EventMedia"
    "EventTeam"
    "EventStatistics"
    "TicketCategory"
    "Ticket"
    "TicketPriceHistory"
    "DynamicPricingRule"
    "TicketTransfer"
    "Order"
    "OrderItem"
    "Payment"
    "Invoice"
    "PromoCode"
    "PromoCodeUsage"
    "Cart"
    "CartItem"
    "Wallet"
    "WalletTransaction"
    "LoyaltyRule"
    "Notification"
    "NotificationPreference"
    "Review"
    "ReviewVote"
    "Favorite"
    "SearchHistory"
    "EventView"
    "Referral"
    "GameParticipation"
    "GameSettings"
    "Friendship"
    "FriendEvent"
    "WaitingList"
    "Report"
    "AuditLog"
    "SystemSetting"
    "Translation"
    "UserStatistics"
    "DailySalesStats"
)

for entity in "${entities[@]}"
do
    echo "Création de l'entité $entity..."
    php bin/console make:entity "$entity"
done

echo "✅ Toutes les entités ont été créées !"
```

---

## 📚 Ressources

- [Documentation Doctrine](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/)
- [Symfony Entities](https://symfony.com/doc/current/doctrine.html)
- [Doctrine Annotations Reference](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/annotations-reference.html)
- [API Platform](https://api-platform.com/docs/core/)

---

**Prochaines étapes** : Création des contrôleurs, services et API endpoints !


