# Architecture Modulaire - Aiolia-event-front

## Vue d'ensemble

Ce document décrit l'organisation du projet en **6 modules fonctionnels** principaux.

---

## Module 1: Authentification et Sécurité (Auth & Security)

### Fonctionnalités
- Connexion/Déconnexion
- Inscription
- Gestion des tokens JWT
- Refresh tokens
- Gestion des rôles et permissions
- Validation des emails

### Composants
**Controllers:**
- `AuthController.php`

**Services:**
- `Security/AuthService.php`
- `Security/AuthTokenService.php`

**Repositories:**
- `UserRepository.php`
- `RefreshTokenRepository.php`
- `UserRoleRepository.php`
- `UserRoleAssignmentRepository.php`

**Entities:**
- `User.php`
- `RefreshToken.php`
- `UserRole.php`
- `UserRoleAssignment.php`

---

## Module 2: Gestion des Événements (Events Management)

### Fonctionnalités
- Création/Modification/Suppression d'événements
- Consultation des événements
- Recherche et filtrage d'événements
- Favoris d'événements
- Historique de recherche
- Catégories d'événements
- Annulation et remboursements d'événements

### Composants
**Controllers:**
- `EventController.php`
- `OrganizerController.php`

**Services:**
- `EventReminderService.php`
- `RefundService.php`
- `CacheService.php`

**Repositories:**
- `EventRepository.php`
- `EventFavoriteRepository.php`
- `WishlistRepository.php`
- `SearchHistoryRepository.php`
- `PromotionRepository.php`

**Entities:**
- `Event.php`
- `EventFavorite.php`
- `Promotion.php`

**Commands:**
- `SendEventRemindersCommand.php`
- `ProcessEventRefundsCommand.php`

**Listeners:**
- `EventCancellationListener.php`

---

## Module 3: Gestion des Billets et Réservations (Tickets & Reservations)

### Fonctionnalités
- Réservation de billets
- Catégories de billets
- Gestion du panier
- Synchronisation du panier
- Historique des commandes
- Génération de QR codes

### Composants
**Controllers:**
- `TicketController.php`

**Services:**
- `CartSyncService.php`

**Repositories:**
- `TicketRepository.php`
- `TicketCategoryRepository.php`
- `OrderRepository.php`

**Entities:**
- `Ticket.php`
- `TicketCategory.php`

---

## Module 4: Paiements et Portefeuille (Payments & Wallet)

### Fonctionnalités
- Paiement MVola
- Gestion du portefeuille (Wallet)
- Rechargement du portefeuille
- Transferts entre utilisateurs
- Transactions du portefeuille
- Points de fidélité
- Historique financier

### Composants
**Controllers:**
- `MvolaController.php`
- `ProfileController.php` (partie Wallet)

**Services:**
- `PaymentService.php`
- `MvolaPaymentClient.php`
- `WalletService.php`
- `LoyaltyPointsService.php`

**Repositories:**
- `WalletRepository.php`
- `WalletTransactionRepository.php`
- `OrderRepository.php` (partie paiements)

---

## Module 5: Profil Utilisateur et Statistiques (User Profile & Stats)

### Fonctionnalités
- Gestion du profil utilisateur
- Paramètres utilisateur
- Statistiques personnelles
- Historique d'achats
- Historique financier détaillé
- Calendrier des événements
- Upload d'avatar
- Graphiques de dépenses
- Répartition des dépenses

### Composants
**Controllers:**
- `ProfileController.php` (partie profil)

**Services:**
- `ActivityService.php`
- `CloudinaryService.php`

**Repositories:**
- `UserRepository.php` (partie profil)
- `UserStatsRepository.php`
- `OrderRepository.php` (partie historique)
- `ActivityRepository.php`
- `WalletTransactionRepository.php`

**Templates:**
- `profile/` (tous les templates de profil)

---

## Module 6: Notifications, Jeux et Social (Notifications, Games & Social)

### Fonctionnalités
- Notifications utilisateur
- Envoi d'emails
- Ticket Chance (jeu)
- Partage social
- Interactions sociales

### Composants
**Controllers:**
- `NotificationController.php`
- `GameController.php`
- `SocialController.php`

**Services:**
- `NotificationService.php`
- `Notification/UserMailer.php`
- `TicketChanceService.php`

**Repositories:**
- `NotificationRepository.php`

**Messages:**
- `Message/SendRemindersMessage.php`

**MessageHandlers:**
- `MessageHandler/SendRemindersHandler.php`

**Scheduler:**
- `Scheduler/MainSchedule.php`

---

## Modules Transversaux

### Configuration et Locale
- `LocaleController.php`
- `EventListener/LocaleListener.php`
- `EventListener/ThemeListener.php`

### Administration
- `AdminController.php`

### Pages Statiques
- `HomeController.php`
- `AboutController.php`

---

## Structure Recommandée des Répertoires

```
Aiolia-event-front/
├── src/
│   ├── Module/
│   │   ├── Auth/
│   │   │   ├── Controller/
│   │   │   ├── Service/
│   │   │   ├── Repository/
│   │   │   └── Entity/
│   │   ├── Events/
│   │   ├── Tickets/
│   │   ├── Payments/
│   │   ├── Profile/
│   │   └── Notifications/
│   ├── Shared/
│   │   ├── Service/
│   │   └── Repository/
│   └── Kernel.php
```

---

## Avantages de cette Organisation

1. **Séparation des responsabilités** : Chaque module a une responsabilité claire
2. **Maintenabilité** : Plus facile de localiser et modifier une fonctionnalité
3. **Réutilisabilité** : Les composants peuvent être réutilisés entre modules
4. **Testabilité** : Plus facile de tester chaque module indépendamment
5. **Scalabilité** : Facile d'ajouter de nouvelles fonctionnalités dans le bon module
6. **Collaboration** : Les développeurs peuvent travailler sur différents modules sans conflits

---

## Migration Progressive

Pour migrer vers cette structure modulaire :

1. **Phase 1** : Créer la structure de répertoires par module
2. **Phase 2** : Déplacer les fichiers existants vers les modules correspondants
3. **Phase 3** : Mettre à jour les namespaces et imports
4. **Phase 4** : Vérifier que tout fonctionne correctement
5. **Phase 5** : Refactoriser les dépendances entre modules si nécessaire

---

## Dépendances entre Modules

```
Auth ──→ Events ──→ Tickets ──→ Payments
 │         │          │            │
 │         │          │            └──→ Profile
 │         │          │
 │         └──────────┴──→ Profile
 │                           │
 └───────────────────────────┴──→ Notifications
```

---

*Document créé le: 2025-01-16*
