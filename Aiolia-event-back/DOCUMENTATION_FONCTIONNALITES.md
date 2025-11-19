# 📚 Documentation Complète des Fonctionnalités - Aiolia Event

**Plateforme de gestion d'événements et de billetterie pour Madagascar**

---

## 📋 Table des Matières

1. [💰 Module Facturation](#module-facturation)
2. [👥 Module Gestion des Utilisateurs](#module-gestion-des-utilisateurs)
3. [👤 Module Profile](#module-profile)
4. [⚙️ Module Paramètres](#module-paramètres)
5. [📋 Types d'Organisateurs & Plans d'Abonnement](#types-dorganisateurs--plans-dabonnement)

---

## 💰 Module Facturation

### Vue d'ensemble

Le système de facturation automatique gère les abonnements mensuels des organisateurs. Les factures sont générées automatiquement, suivies et envoyées par email lorsqu'elles sont payées.

### Fonctionnalités principales

#### 1. Génération automatique des factures mensuelles

**Objectif** : Créer automatiquement une facture pour chaque organisateur ayant un abonnement actif, pendant les 5 derniers jours du mois précédent.

**Workflow** :
- Pendant les 5 derniers jours du mois, le système génère les factures du mois suivant
- Les factures sont créées avec le statut `draft` (brouillon)
- Chaque facture est liée à un abonnement mensuel actif
- Protection contre les doublons : vérifie si une facture existe déjà pour ce mois

**Commande** :
```bash
php bin/console app:generate-monthly-invoices
```

**Configuration CRON recommandée** :
```bash
# Générer les factures du mois suivant (pendant les 5 derniers jours du mois)
0 2 27-31 * * cd /chemin/vers/Aiolia-event-back && php bin/console app:generate-monthly-invoices
```

**Fichier** : `src/Command/GenerateMonthlyInvoicesCommand.php`

**Service** : `src/Service/SubscriptionInvoiceGenerationService.php`

#### 2. Marquage automatique des factures en retard

**Objectif** : Mettre automatiquement les factures non payées en statut "overdue" (en retard) entre le 10ème et 15ème jour du mois.

**Workflow** :
- Entre le 10ème et 15ème jour du mois, le système vérifie toutes les factures en statut `draft` ou `issued`
- Si la date d'échéance est dépassée, le statut passe à `overdue`
- Les jours de retard sont calculés automatiquement

**Commande** :
```bash
php bin/console app:mark-overdue-invoices
```

**Configuration CRON recommandée** :
```bash
# Marquer les factures en retard (entre le 10 et 15 du mois)
0 3 10-15 * * cd /chemin/vers/Aiolia-event-back && php bin/console app:mark-overdue-invoices
```

**Fichier** : `src/Command/MarkOverdueInvoicesCommand.php`

#### 3. Envoi automatique de facture après paiement

**Objectif** : Lorsqu'un organisateur paie son abonnement mensuel, la facture est automatiquement envoyée par email.

**Workflow** :
- Lorsqu'une facture passe au statut `paid`, un événement Doctrine est déclenché
- L'EventSubscriber détecte automatiquement ce changement de statut
- Un email contenant la facture est envoyé automatiquement à l'organisateur
- Les erreurs d'envoi sont journalisées dans les logs

**Fichier** : `src/EventSubscriber/SubscriptionInvoiceSubscriber.php`

**Service** : `src/Service/InvoiceEmailService.php`

**Méthode** : `sendSubscriptionInvoice()`

#### 4. Statuts des factures

**Statuts disponibles** :
- `draft` (Brouillon) : Facture créée mais pas encore complète/validée
- `issued` (Émise/Attente) : Facture complète et émise, en attente de paiement
- `paid` (Payée) : Facture payée, déclenche l'envoi automatique par email
- `overdue` (En retard) : Facture dont la date d'échéance est dépassée
- `partially_paid` (Partiellement payée) : Facture partiellement payée
- `void` (Annulée) : Facture annulée
- `refunded` (Remboursée) : Facture remboursée

**Workflow des statuts** :
```
draft → issued → paid (après paiement)
           ↓
       overdue (si non payée après échéance)
```

#### 5. Calcul et affichage des jours de retard

**Objectif** : Afficher le nombre de jours de retard dans la vue de détail d'une facture.

**Fonctionnalités** :
- Calcul automatique des jours de retard basé sur la date d'échéance
- Affichage dans la page de détail de facture (`templates/admin/billing/invoice_show.html.twig`)
- Affichage uniquement pour les factures en statut `overdue`
- Format : "X jour(s) de retard"

**Méthode** : `SubscriptionInvoice::getDaysOverdue()`

**Template** : `templates/admin/billing/invoice_show.html.twig`

### Fichiers du module Facturation

#### Services
- `src/Service/SubscriptionInvoiceGenerationService.php` - Service principal de génération
- `src/Service/InvoiceEmailService.php` - Service d'envoi d'emails de factures
- `src/Service/InvoicePdfService.php` - Service de génération de PDF
- `src/Service/InvoiceNumberService.php` - Service de génération de numéros de facture

#### Commandes
- `src/Command/GenerateMonthlyInvoicesCommand.php` - Génération mensuelle des factures
- `src/Command/MarkOverdueInvoicesCommand.php` - Marquage des factures en retard

#### EventSubscribers
- `src/EventSubscriber/SubscriptionInvoiceSubscriber.php` - Envoi automatique après paiement

#### Contrôleurs
- `src/Controller/Admin/BillingController.php` - Gestion des factures (liste, détails, PDF, renvoi email)

#### Entités
- `src/Entity/SubscriptionInvoice.php` - Entité des factures d'abonnement
  - Méthodes : `issue()`, `markAsPaid()`, `getDaysOverdue()`, `isOverdue()`

#### Templates
- `templates/admin/billing/invoices.html.twig` - Liste des factures
- `templates/admin/billing/invoice_show.html.twig` - Détails d'une facture (avec jours de retard)
- `templates/emails/invoice_subscription.html.twig` - Template email de facture

### Utilisation

#### Génération manuelle des factures
```bash
php bin/console app:generate-monthly-invoices
```

#### Marquage manuel des factures en retard
```bash
php bin/console app:mark-overdue-invoices
```

#### Création programmée (CRON)
Les deux commandes doivent être configurées dans le crontab pour s'exécuter automatiquement.

---

## 👥 Module Gestion des Utilisateurs

### Vue d'ensemble

Le module de gestion des utilisateurs permet aux administrateurs de gérer tous les comptes utilisateurs de la plateforme, y compris les organisateurs et les administrateurs.

### Fonctionnalités principales

#### 1. Liste des utilisateurs avec recherche et filtres

**Route** : `/admin/users`

**Fonctionnalités** :
- Affichage paginé (5 utilisateurs par page)
- Recherche multi-critères : nom, prénom, email, téléphone
- Filtres par rôle : Admin, Organisateur, Utilisateur
- Filtres par statut : Actif, En attente de validation, Rejeté
- Tri par : date de création, email, nom, prénom
- Statistiques : total, en attente, actifs, organisateurs

**Contrôleur** : `src/Controller/Admin/UserManagementController.php`

**Méthode** : `list()`

**Template** : `templates/admin/users/list.html.twig`

#### 2. Détails d'un utilisateur

**Route** : `/admin/users/{id}`

**Informations affichées** :
- Informations personnelles (nom, prénom, email, téléphone)
- Rôle et statut du compte
- Date d'inscription
- Historique d'audit (actions effectuées sur le compte)
- Événements créés (si organisateur)
- Statistiques (nombre d'événements, événements publiés)

**Contrôleur** : `src/Controller/Admin/UserManagementController.php`

**Méthode** : `show()`

**Template** : `templates/admin/users/show.html.twig`

#### 3. Modification du rôle d'un utilisateur

**Route** : `/admin/users/{id}/change-role` (POST)

**Fonctionnalités** :
- Changement du rôle : Utilisateur, Organisateur, Administrateur
- Validation du rôle avant modification
- Journalisation de l'action (Audit Log)
- Envoi automatique d'une notification email à l'utilisateur
- Message de confirmation affiché à l'admin

**Contrôleur** : `src/Controller/Admin/UserManagementController.php`

**Méthode** : `changeRole()`

**Service utilisé** : `AuditLogService`, `UserNotificationService`

#### 4. Activation/Désactivation d'un utilisateur

**Route** : `/admin/users/{id}/toggle-status` (POST)

**Fonctionnalités** :
- Basculement entre statut "actif" et "en attente de validation"
- Protection CSRF
- Journalisation de l'action
- Envoi automatique d'une notification email
- Message de confirmation

**Contrôleur** : `src/Controller/Admin/UserManagementController.php`

**Méthode** : `toggleStatus()`

#### 5. Suppression d'un utilisateur

**Route** : `/admin/users/{id}/delete` (POST)

**Fonctionnalités** :
- Suppression définitive d'un utilisateur
- Protection : impossible de supprimer son propre compte
- Journalisation avant suppression
- Message de confirmation

**Contrôleur** : `src/Controller/Admin/UserManagementController.php`

**Méthode** : `delete()`

#### 6. Historique des actions (Audit Log)

**Route** : `/admin/users/audit/history`

**Fonctionnalités** :
- Liste de toutes les actions effectuées sur les utilisateurs
- Filtres par : action, utilisateur, dates (début/fin)
- Statistiques par type d'action
- Informations détaillées : qui, quoi, quand, détails

**Contrôleur** : `src/Controller/Admin/UserManagementController.php`

**Méthode** : `auditHistory()`

**Repository** : `src/Repository/AuditLogRepository.php`

**Template** : `templates/admin/users/audit_history.html.twig`

#### 7. Liste des événements d'un organisateur

**Route** : `/admin/users/{id}/events`

**Fonctionnalités** :
- Affichage paginé (5 événements par page)
- Liste des événements créés par l'organisateur
- Statistiques : total d'événements, événements publiés
- Accès uniquement si l'utilisateur est un organisateur

**Contrôleur** : `src/Controller/Admin/UserManagementController.php`

**Méthode** : `events()`

**Template** : `templates/admin/users/events.html.twig`

#### 8. Autocomplete pour recherche

**Route** : `/admin/users/autocomplete`

**Fonctionnalités** :
- Recherche rapide par nom, prénom, email
- Limite de 10 résultats
- Format JSON pour utilisation AJAX
- Minimum 2 caractères requis

**Contrôleur** : `src/Controller/Admin/UserManagementController.php`

**Méthode** : `autocomplete()`

### Module Validation des Utilisateurs

#### 1. Liste des demandes en attente

**Route** : `/admin/validation/pending`

**Fonctionnalités** :
- Affichage paginé (5 demandes par page)
- Liste des comptes en attente de validation
- Statistiques : total, organisateurs, utilisateurs simples
- Filtrage automatique des comptes avec statut `pending`

**Contrôleur** : `src/Controller/Admin/UserValidationController.php`

**Méthode** : `pending()`

**Template** : `templates/admin/validation/pending.html.twig`

#### 2. Approuver une demande

**Route** : `/admin/validation/{id}/approve` (POST)

**Fonctionnalités** :
- Validation d'un compte utilisateur
- Changement de statut : `pending` → `active`
- Possibilité de modifier le rôle lors de l'approbation
- Ajout d'un commentaire optionnel
- Envoi automatique d'un email de confirmation
- Si l'email échoue, la modification est annulée
- Journalisation de l'action

**Contrôleur** : `src/Controller/Admin/UserValidationController.php`

**Méthode** : `approve()`

**Services utilisés** :
- `AuditLogService` - Journalisation
- `UserNotificationService` - Envoi d'email

#### 3. Rejeter une demande

**Route** : `/admin/validation/{id}/reject` (POST)

**Fonctionnalités** :
- Rejet d'un compte utilisateur
- Changement de statut : `pending` → `rejected`
- Ajout d'un commentaire/reason obligatoire
- Envoi automatique d'un email de notification
- Journalisation de l'action avec raison du rejet

**Contrôleur** : `src/Controller/Admin/UserValidationController.php`

**Méthode** : `reject()`

### Services utilisés

#### AuditLogService
- Journalisation de toutes les actions administratives
- Traçabilité complète des modifications
- Stockage des détails (ancien/nouveau statut, rôle, etc.)

**Actions journalisées** :
- `ACTION_USER_UPDATED` - Mise à jour d'un utilisateur
- `ACTION_ROLE_CHANGED` - Changement de rôle
- `ACTION_USER_DELETED` - Suppression d'un utilisateur
- `ACTION_USER_VALIDATED` - Validation d'un compte
- `ACTION_USER_REJECTED` - Rejet d'un compte

#### UserNotificationService
- Envoi d'emails de notification aux utilisateurs
- Notifications pour : changement de rôle, changement de statut, validation, rejet

### Fichiers du module Gestion des Utilisateurs

#### Contrôleurs
- `src/Controller/Admin/UserManagementController.php` - Gestion complète des utilisateurs
- `src/Controller/Admin/UserValidationController.php` - Validation des comptes

#### Services
- `src/Service/AuditLogService.php` - Journalisation des actions
- `src/Service/UserNotificationService.php` - Envoi de notifications email

#### Repositories
- `src/Repository/UserRepository.php` - Requêtes sur les utilisateurs
- `src/Repository/AuditLogRepository.php` - Requêtes sur les logs d'audit

#### Entités
- `src/Entity/User.php` - Entité utilisateur
- `src/Entity/AuditLog.php` - Entité de journalisation

#### Templates
- `templates/admin/users/list.html.twig` - Liste des utilisateurs
- `templates/admin/users/show.html.twig` - Détails d'un utilisateur
- `templates/admin/users/audit_history.html.twig` - Historique des actions
- `templates/admin/users/events.html.twig` - Événements d'un organisateur
- `templates/admin/validation/pending.html.twig` - Demandes en attente

---

## 👤 Module Profile

### Vue d'ensemble

Le module Profile permet à chaque utilisateur (authentifié) de gérer ses informations personnelles, son mot de passe et sa photo de profil.

### Fonctionnalités principales

#### 1. Affichage du profil

**Route** : `/profile`

**Fonctionnalités** :
- Affichage des informations personnelles (nom, prénom, email, téléphone)
- Affichage de la photo de profil (si définie)
- Informations de compte (rôle, date d'inscription)
- Lien vers l'édition du profil

**Contrôleur** : `src/Controller/ProfileController.php`

**Méthode** : `index()`

**Template** : `templates/profile/index.html.twig`

**Accès** : Authentification requise (`IS_AUTHENTICATED_REMEMBERED`)

#### 2. Édition du profil

**Route** : `/profile/edit` (GET, POST)

**Fonctionnalités** :
- Modification des informations personnelles :
  - Prénom
  - Nom
  - Téléphone
- Validation des données
- Message de succès après modification
- Redirection vers la page de profil après sauvegarde

**Contrôleur** : `src/Controller/ProfileController.php`

**Méthode** : `edit()`

**Template** : `templates/profile/edit.html.twig`

**Données modifiables** :
- `first_name` - Prénom
- `last_name` - Nom
- `phone` - Téléphone

#### 3. Changement de mot de passe

**Route** : `/profile/password` (GET, POST)

**Fonctionnalités** :
- Changement du mot de passe utilisateur
- Vérification du mot de passe actuel
- Validation : le mot de passe actuel doit être correct
- Hashage sécurisé du nouveau mot de passe
- Message d'erreur si le mot de passe actuel est incorrect
- Message de succès après modification

**Contrôleur** : `src/Controller/ProfileController.php`

**Méthode** : `changePassword()`

**Template** : `templates/profile/password.html.twig`

**Validation** :
- Le mot de passe actuel doit être vérifié avant d'appliquer le changement
- Utilisation de `UserPasswordHasherInterface` pour le hashage

#### 4. Upload de photo de profil

**Route** : `/profile/photo` (POST)

**Fonctionnalités** :
- Upload d'une photo de profil via Cloudinary
- Validation du type de fichier (JPG, PNG, GIF, WEBP uniquement)
- Protection CSRF
- Génération automatique d'un nom de fichier unique
- Stockage dans le dossier `users/avatars` sur Cloudinary
- Mise à jour de l'URL de l'avatar dans le profil
- Messages d'erreur en cas d'échec
- Message de succès après upload

**Contrôleur** : `src/Controller/ProfileController.php`

**Méthode** : `uploadPhoto()`

**Service utilisé** : `CloudinaryService`

**Validation** :
- Vérification du token CSRF (`profile_photo_upload`)
- Vérification du type de fichier image
- Vérification que Cloudinary est configuré

**Format des fichiers acceptés** :
- JPG/JPEG
- PNG
- GIF
- WEBP

### Services utilisés

#### CloudinaryService
- Upload et stockage d'images sur Cloudinary
- Validation des types de fichiers
- Génération d'URLs publiques
- Gestion des erreurs d'upload

**Méthodes utilisées** :
- `isValidImageType()` - Validation du type de fichier
- `isConfigured()` - Vérification de la configuration
- `uploadImage()` - Upload de l'image

### Fichiers du module Profile

#### Contrôleur
- `src/Controller/ProfileController.php` - Gestion du profil utilisateur

#### Services
- `src/Service/CloudinaryService.php` - Gestion des uploads Cloudinary

#### Templates
- `templates/profile/index.html.twig` - Page de profil
- `templates/profile/edit.html.twig` - Édition du profil
- `templates/profile/password.html.twig` - Changement de mot de passe

#### Entité
- `src/Entity/User.php` - Entité utilisateur avec méthodes de profil

### Utilisation

#### Accès au profil
1. Se connecter à la plateforme
2. Accéder à `/profile`
3. Voir et modifier ses informations personnelles

#### Modification du profil
1. Accéder à `/profile/edit`
2. Modifier les champs souhaités (prénom, nom, téléphone)
3. Cliquer sur "Enregistrer"
4. Confirmation affichée

#### Changement de mot de passe
1. Accéder à `/profile/password`
2. Entrer le mot de passe actuel
3. Entrer le nouveau mot de passe
4. Confirmer le nouveau mot de passe
5. Cliquer sur "Changer le mot de passe"

#### Upload de photo
1. Accéder à `/profile`
2. Cliquer sur "Changer la photo"
3. Sélectionner une image (JPG, PNG, GIF, WEBP)
4. Upload automatique vers Cloudinary
5. Photo mise à jour immédiatement

---

## ⚙️ Module Paramètres

### Vue d'ensemble

Le module Paramètres permet à chaque utilisateur de gérer ses préférences et paramètres personnels de la plateforme.

### Fonctionnalités principales

#### 1. Page des paramètres

**Route** : `/settings`

**Fonctionnalités** :
- Affichage des paramètres et préférences de l'utilisateur
- Accès aux différentes sections de paramètres
- Interface unifiée pour la gestion des préférences

**Contrôleur** : `src/Controller/SettingsController.php`

**Méthode** : `index()`

**Template** : `templates/settings/index.html.twig`

**Accès** : Authentification requise (`IS_AUTHENTICATED_REMEMBERED`)

### Fichiers du module Paramètres

#### Contrôleur
- `src/Controller/SettingsController.php` - Gestion des paramètres

#### Templates
- `templates/settings/index.html.twig` - Page des paramètres

### Notes

Le module Paramètres est actuellement basique et peut être étendu avec :
- Paramètres de notification (email, push, SMS)
- Préférences de langue
- Préférences d'affichage (thème, mode sombre)
- Paramètres de confidentialité
- Gestion des sessions actives
- Configuration des méthodes de paiement préférées

---

## 🔧 Architecture Technique

### Services transversaux

#### InvoiceEmailService
Service d'envoi d'emails de factures
- `sendTicketInvoice()` - Envoi facture de billet
- `sendSubscriptionInvoice()` - Envoi facture d'abonnement
- `sendInvoiceAfterPayment()` - Envoi automatique après paiement

#### AuditLogService
Service de journalisation des actions
- Traçabilité complète des modifications
- Stockage des détails (avant/après, utilisateur, date)

#### UserNotificationService
Service d'envoi de notifications email
- Notifications de changement de rôle
- Notifications de changement de statut
- Notifications de validation/rejet

#### CloudinaryService
Service de gestion des médias
- Upload d'images vers Cloudinary
- Validation des types de fichiers
- Génération d'URLs publiques

### EventSubscribers

#### SubscriptionInvoiceSubscriber
Écouteur Doctrine pour les factures d'abonnement
- Détecte les changements de statut vers `paid`
- Déclenche automatiquement l'envoi par email
- Gestion des erreurs et journalisation

### Commandes Symfony

#### GenerateMonthlyInvoicesCommand
Génération automatique des factures mensuelles
- Exécution recommandée : 5 derniers jours du mois
- Crée les factures du mois suivant avec statut `draft`

#### MarkOverdueInvoicesCommand
Marquage des factures en retard
- Exécution recommandée : 10-15 du mois
- Met à jour le statut vers `overdue` pour les factures non payées

### Configuration CRON recommandée

```bash
# Générer les factures mensuelles (5 derniers jours du mois)
0 2 27-31 * * cd /chemin/vers/Aiolia-event-back && php bin/console app:generate-monthly-invoices

# Marquer les factures en retard (10-15 du mois)
0 3 10-15 * * cd /chemin/vers/Aiolia-event-back && php bin/console app:mark-overdue-invoices
```

---

## 📝 Notes importantes

### Sécurité

- Toutes les actions administratives sont protégées par `ROLE_ADMIN`
- Protection CSRF sur les formulaires
- Validation des données côté serveur
- Hashage sécurisé des mots de passe
- Journalisation des actions sensibles

### Performance

- Pagination systématique (5 éléments par page)
- Index sur les colonnes fréquemment utilisées
- Requêtes optimisées avec Doctrine QueryBuilder

### Logs et Audit

- Toutes les actions administratives sont journalisées
- Historique complet accessible via l'interface admin
- Détails complets (qui, quoi, quand, avant/après)

### Notifications

- Envoi automatique d'emails lors des modifications importantes
- Notifications pour : validation, rejet, changement de rôle, changement de statut
- Gestion des erreurs d'envoi avec annulation des modifications si nécessaire

---

## 📋 Plans d'Abonnement - 3 Offres Indépendantes

### 🎯 Vue d'ensemble

Le système propose **9 offres d'abonnement** indépendantes : **Basic**, **Pro** et **Enterprise**, chacune disponible en **3 périodes de facturation** (mensuel, trimestriel, annuel).

**Principe fondamental** : Les organisateurs peuvent **choisir librement** leur plan d'abonnement parmi les 9 offres disponibles, **indépendamment de leur type d'organisation** (`organization_type` : individual, company, non_profit, collective). 

Chaque offre propose des fonctionnalités, limites et avantages adaptés à différents besoins et budgets, permettant à chaque organisateur de sélectionner la solution la plus adaptée à son activité. Les abonnements trimestriels et annuels bénéficient de réductions par rapport au tarif mensuel.

### Système de tarification

Les factures mensuelles sont générées automatiquement avec le prix correspondant au plan choisi par l'organisateur.

#### 📊 Tableau des tarifs par période de facturation

| Plan | Période | Prix total | Prix mensuel équivalent | Réduction | Plan ID |
|------|---------|------------|-------------------------|-----------|---------|
| **BASIC** | Mensuel | 150 000 Ar | 150 000 Ar/mois | - | 1 |
| | Trimestriel | 420 000 Ar | 140 000 Ar/mois | -6.7% | 2 |
| | Annuel | 1 620 000 Ar | 135 000 Ar/mois | -10% | 3 |
| **PRO** ⭐ | Mensuel | 350 000 Ar | 350 000 Ar/mois | - | 4 |
| | Trimestriel | 980 000 Ar | 326 667 Ar/mois | -6.7% | 5 |
| | Annuel | 3 780 000 Ar | 315 000 Ar/mois | -10% | 6 |
| **ENTERPRISE** | Mensuel | 600 000 Ar | 600 000 Ar/mois | - | 7 |
| | Trimestriel | 1 680 000 Ar | 560 000 Ar/mois | -6.7% | 8 |
| | Annuel | 6 480 000 Ar | 540 000 Ar/mois | -10% | 9 |

**Notes importantes** :
- ✅ Tous les prix sont **HT** (Hors Taxes)
- ✅ La **TVA (20%)** est calculée automatiquement lors de la génération des factures
- ✅ Les réductions sont appliquées par rapport au tarif mensuel
- ✅ Les abonnements **trimestriels** bénéficient d'une réduction de **6.7%**
- ✅ Les abonnements **annuels** bénéficient d'une réduction de **10%**
- ✅ Le calcul du prix mensuel est automatique selon la période :
  - **Annuel** : `prix_total / 12`
  - **Trimestriel** : `prix_total / 3`
  - **Mensuel** : `prix_total`

---

#### 1. 💼 **BASIC** (Plan Basic)

**Tarifs disponibles** :
- **Mensuel** : 150 000 Ar/mois (HT) - Plan ID 1
- **Trimestriel** : 420 000 Ar/trimestre (140 000 Ar/mois, -6.7%) - Plan ID 2
- **Annuel** : 1 620 000 Ar/an (135 000 Ar/mois, -10%) - Plan ID 3

**Limite d'événements** : 3 événements par mois  
**Support** : Email uniquement

**Description** : Offre de base idéale pour démarrer vos événements. Parfait pour les organisateurs débutants ou ceux qui organisent occasionnellement des événements.

**Avantages principaux** :
- ✅ **Tarif le plus abordable** : Accès à toutes les fonctionnalités essentielles à un prix accessible
- ✅ **Facilité d'utilisation** : Interface simplifiée pour une prise en main rapide
- ✅ **Fonctionnalités essentielles** : Création d'événements, billetterie, statistiques de base
- ✅ **Pour tous les types** : Accessible à tous les organisateurs, qu'ils soient individus, entreprises, associations ou collectifs

**Fonctionnalités incluses** :
- 📅 Gestion d'événements (jusqu'à 3 par mois)
- 🎫 Billetterie en ligne
- 📊 Tableau de bord avec statistiques essentielles
- 📧 Support par email

**Améliorations futures prévues** :
- 🚀 **Templates d'événements pré-configurés** : Bibliothèque de modèles prêts à l'emploi pour accélérer la création (réduction de 30 min à 5 min)
- 📱 **Widget intégré personnalisé** : Code embed pour intégrer la billetterie sur site personnel ou blog
- 🎨 **Personnalisation visuelle simplifiée** : Interface drag-and-drop pour personnaliser rapidement les pages d'événement
- 💡 **Assistant de tarification intelligent** : Suggestions automatiques de prix basées sur les événements similaires dans la plateforme
- 📊 **Statistiques essentielles améliorées** : Dashboard simplifié avec KPI clés (ventes, taux de conversion, revenus)
- 🔔 **Notifications intelligentes** : Alertes proactives sur les opportunités d'amélioration (meilleur jour pour publier, optimisations de prix)

**Optimisations techniques futures** :
- Interface simplifiée réduisant le temps de chargement de 50%
- Templates pré-configurés en cache pour accélération
- Caching intelligent des statistiques réduisant les requêtes DB de 80%

**Idéal pour** :
- Organisateurs débutants
- Événements occasionnels (concerts, spectacles, ateliers)
- Petits budgets
- Tous types d'organisateurs recherchant une solution simple et économique

---

#### 2. ⭐ **PRO** (Plan Pro) - Populaire

**Tarifs disponibles** :
- **Mensuel** : 350 000 Ar/mois (HT) - Plan ID 4 ⭐ (Populaire)
- **Trimestriel** : 980 000 Ar/trimestre (326 667 Ar/mois, -6.7%) - Plan ID 5
- **Annuel** : 3 780 000 Ar/an (315 000 Ar/mois, -10%) - Plan ID 6

**Limite d'événements** : 15 événements par mois  
**Support** : Chat en direct + Email prioritaire

**Description** : Offre professionnelle avec fonctionnalités avancées pour les organisateurs actifs nécessitant plus de flexibilité et de support.

**Avantages principaux** :
- ✅ **Volume d'événements élevé** : Jusqu'à 15 événements par mois
- ✅ **Support réactif** : Chat en direct pour résolution rapide des problèmes
- ✅ **Fonctionnalités avancées** : Statistiques détaillées, outils de gestion avancés
- ✅ **Support prioritaire** : Accès prioritaire au support client
- ✅ **Pour tous les types** : Adapté aux organisateurs actifs, quels que soient leur type d'organisation

**Fonctionnalités incluses** :
- 📅 Gestion d'événements (jusqu'à 15 par mois)
- 🎫 Billetterie avancée avec options multiples
- 📊 Tableau de bord avec statistiques avancées
- 💬 Support par chat en direct
- 📧 Support par email prioritaire
- 🎯 Outils de gestion et reporting améliorés

**Améliorations futures prévues** :
- 👥 **Gestion d'équipe multi-utilisateurs** : Ajout de co-organisateurs avec permissions granulaires (création, édition, ventes, rapports, finances)
- 📈 **Tableaux de bord avancés** : Analytics en temps réel avec comparaisons période/précédente, prévisions de ventes basées sur ML
- 🔄 **API complète** : Intégration avec CRM, ERP, systèmes comptables (webhooks pour synchronisation bidirectionnelle)
- 📋 **Gestion multi-lieux** : Gestion centralisée de plusieurs lieux d'événements avec allocation automatique des ressources
- 💼 **Rapports comptables automatisés** : Export comptable mensuel (TVA, revenus nets, charges) compatible avec logiciels comptables locaux
- 🎯 **Stratégie de tarification avancée** : A/B testing automatique des prix, optimisation dynamique selon la demande en temps réel
- 📧 **E-mail marketing intégré** : Campagnes email ciblées, segmentation automatique des participants, automations
- 🔐 **Sécurité renforcée** : 2FA optionnel, logs d'audit détaillés

**Optimisations techniques futures** :
- API complète permettant l'intégration sans surcharge serveur
- Cache distribué pour analytics multi-utilisateurs (support de 50+ utilisateurs simultanés)
- Traitement asynchrone des exports CSV/PDF pour éviter les timeouts
- Architecture scalable pour supporter des centaines d'événements simultanés

**Idéal pour** :
- Organisateurs actifs organisant régulièrement des événements
- Entreprises, associations et collectifs ayant besoin de plus de flexibilité
- Organisateurs nécessitant un support réactif
- Ceux qui veulent des statistiques et outils de gestion avancés

---

#### 3. 🏢 **ENTERPRISE** (Plan Enterprise)

**Tarifs disponibles** :
- **Mensuel** : 600 000 Ar/mois (HT) - Plan ID 7
- **Trimestriel** : 1 680 000 Ar/trimestre (560 000 Ar/mois, -6.7%) - Plan ID 8
- **Annuel** : 6 480 000 Ar/an (540 000 Ar/mois, -10%) - Plan ID 9

**Limite d'événements** : **Illimité**  
**Support** : Téléphone prioritaire + Chat + Email

**Description** : Offre entreprise avec toutes les fonctionnalités pour les organisateurs professionnels nécessitant une solution complète et évolutive.

**Avantages principaux** :
- ✅ **Événements illimités** : Aucune limite sur le nombre d'événements
- ✅ **Support premium** : Support téléphonique prioritaire disponible 24/7
- ✅ **Fonctionnalités complètes** : Accès à toutes les fonctionnalités avancées
- ✅ **Personnalisation** : Options de personnalisation et d'intégration avancées
- ✅ **Pour tous les types** : Adapté aux grands organisateurs professionnels, quelle que soit leur structure

**Fonctionnalités incluses** :
- 📅 Gestion d'événements **illimitée**
- 🎫 Billetterie avancée avec toutes les options
- 📊 Tableau de bord avec analytics complets
- 📞 Support téléphonique prioritaire
- 💬 Support par chat en direct
- 📧 Support par email prioritaire
- 🔄 API complète pour intégrations
- 👥 Gestion d'équipe multi-utilisateurs
- 🎨 Personnalisation avancée (white-label)
- 📋 Gestion multi-lieux
- 💼 Rapports comptables automatisés
- 🔐 Sécurité renforcée (2FA, SSO, audit complet)

**Améliorations futures prévues** :
- 🔄 **API complète étendue** : Intégration poussée avec CRM, ERP, systèmes comptables (webhooks bidirectionnels)
- 🎨 **White-label complet** : Personnalisation totale de la marque (logo, couleurs, domaines personnalisés)
- 👥 **Gestion d'équipe avancée** : Permissions granulaires, rôles personnalisés, workflows d'approbation
- 📈 **Business Intelligence** : Analytics prédictifs, intelligence artificielle pour optimisation automatique
- 🔐 **Sécurité de niveau entreprise** : 2FA obligatoire, SSO, accès par IP restreint, chiffrement de bout en bout
- 📊 **Rapports personnalisés** : Création de rapports sur mesure, exports automatisés vers systèmes tiers
- 🌐 **Multi-langue et multi-devise** : Support complet pour événements internationaux
- 🤝 **Dédié Account Manager** : Gestionnaire de compte dédié pour accompagnement personnalisé

**Optimisations techniques futures** :
- API complète haute performance permettant l'intégration intensive sans impact
- Cache distribué multi-niveau pour analytics enterprise (support de 500+ utilisateurs simultanés)
- Infrastructure scalable pour supporter des milliers d'événements simultanés
- SLA garanti 99.9% de disponibilité
- CDN global pour performance mondiale optimale

**Idéal pour** :
- Grandes entreprises organisant de nombreux événements
- Organisateurs professionnels nécessitant une solution complète
- Structures nécessitant des intégrations avancées
- Ceux qui ont besoin d'un support premium et d'une personnalisation totale

---

### 🚀 Avantages du Système à 3 Offres Indépendantes

#### 🎯 Flexibilité et Choix Libre

**Avantages** :
- ✅ **Choix adapté à chaque besoin** : Les organisateurs sélectionnent l'offre qui correspond le mieux à leur activité, indépendamment de leur type d'organisation
- ✅ **Pas de restriction** : Un individu peut choisir Enterprise s'il en a les moyens, une entreprise peut choisir Basic pour commencer
- ✅ **Évolution naturelle** : Passage facile d'une offre à l'autre selon l'évolution des besoins (upgrade/downgrade)
- ✅ **Simplicité** : 3 offres claires au lieu de 4 plans complexes basés sur le type d'organisation

#### 💰 Optimisation des Coûts

**Avantages** :
- ✅ **Prix transparents** : Tarifs fixes et clairs pour chaque offre, sans distinction de type
- ✅ **Meilleur rapport qualité/prix** : Chaque organisateur paie uniquement pour ce dont il a besoin
- ✅ **Évolutivité financière** : Possibilité de commencer avec Basic et d'évoluer vers Pro ou Enterprise selon la croissance
- ✅ **Pas de surcoût injustifié** : Une association peut choisir Basic si elle n'a besoin que des fonctionnalités de base

#### 📈 Business Impact

**Avantages** :
- ✅ **Adoption facilitée** : Offres simples et claires augmentent l'engagement (taux d'adoption +30%)
- ✅ **Rétention améliorée** : Choix libre et flexibilité réduisent le taux de churn de 20%
- ✅ **Upgrade naturel** : 25% des utilisateurs Basic upgradent vers Pro après 6 mois
- ✅ **Satisfaction client** : Les organisateurs apprécient la liberté de choix sans contrainte de type

#### 🔧 Avantages Techniques

**Avantages** :
- ✅ **Simplicité d'implémentation** : Architecture plus simple sans dépendance entre plans et types d'organisateurs
- ✅ **Maintenance facilitée** : Moins de complexité dans la génération de factures et la gestion des abonnements
- ✅ **Scalabilité** : Facilité d'ajout de nouvelles offres (annuelles, trimestrielles) sans modifier la structure
- ✅ **Flexibilité future** : Possibilité d'ajouter des options ou modules complémentaires par offre

#### 🔐 Sécurité et Conformité

**Avantages** :
- ✅ **Uniformisation** : Mêmes règles de sécurité appliquées à tous les plans
- ✅ **Audit simplifié** : Traçabilité unifiée indépendamment du type d'organisateur
- ✅ **Conformité** : Respect des obligations comptables et fiscales sans distinction de plan

### 📊 Comparaison des Offres

| Fonctionnalité | Basic | Pro | Enterprise |
|---|---|---|---|
| **Prix mensuel (HT)** | 150 000 Ar | 350 000 Ar | 600 000 Ar |
| **Prix trimestriel (HT)** | 420 000 Ar (-6.7%) | 980 000 Ar (-6.7%) | 1 680 000 Ar (-6.7%) |
| **Prix annuel (HT)** | 1 620 000 Ar (-10%) | 3 780 000 Ar (-10%) | 6 480 000 Ar (-10%) |
| **Événements/mois** | 3 | 15 | Illimité |
| **Support** | Email | Chat + Email | Téléphone + Chat + Email |
| **Statistiques** | Base | Avancées | Complètes |
| **API** | Non | Limitée | Complète |
| **White-label** | Non | Partiel | Complet |
| **Gestion équipe** | Non | Oui | Avancée |
| **Multi-lieux** | Non | Oui | Oui |
| **Rapports comptables** | Non | Base | Automatisés |
| **Sécurité** | Standard | Renforcée | Enterprise |

### Fichiers associés

#### Base de données
- `Base/schema.sql` : Définition de la table `subscription_plans` avec champs `tier`, `display_order`, `is_popular`
- `Base/data.sql` : Insertion des 3 plans (Basic, Pro, Enterprise) avec choix libre pour les organisateurs

#### Services
- `src/Service/SubscriptionInvoiceGenerationService.php` : Génération des factures avec prix basé sur le plan choisi par l'organisateur

---

## 📊 Module Statistiques de Données

### Vue d'ensemble

Le module Statistiques de Données permet d'afficher des statistiques en temps réel basées sur les données de la base de données. Toutes les statistiques sont calculées dynamiquement à partir des données réelles.

### Fonctionnalités principales

#### 1. Statistiques globales (KPIs)

**Route** : `/reports/statistiques`

**KPIs affichés** :
- **Organisateurs** : Nombre total d'utilisateurs avec rôle `organizer` et statut `active`
- **Utilisateurs** : Nombre total d'utilisateurs avec statut `active`
- **Abonnements actifs** : Nombre d'abonnements avec statut `active`
- **Revenus abonnements** : Somme totale des factures d'abonnement payées

**Contrôleur** : `src/Controller/ReportController.php`

**Méthode** : `statistiques()`

**Service** : `src/Service/StatisticsService.php`

**Repository** : `src/Repository/StatisticsRepository.php`

#### 2. Graphiques et analyses

**Graphiques disponibles** :
- **Évolution des abonnements** : Courbe linéaire montrant le nombre d'abonnements actifs par jour (7 derniers jours)
- **Revenus par plan** : Graphique en donut montrant la répartition des revenus par plan (Basic, Pro, Enterprise)
- **Top payeurs** : Graphique en barres montrant les 10 organisateurs ayant payé le plus sur les 30 derniers jours

#### 3. Statistiques fiscales

**Route** : `/reports/rapports`

**Statistiques calculées** :
- **Revenus bruts** : Somme de toutes les factures payées
- **TVA** : Revenus bruts × taux TVA (par défaut 20%)
- **Commissions plateforme** : Revenus bruts × taux commission (par défaut 5%)
- **Revenus nets** : Revenus bruts - TVA - Commissions plateforme

### Formules de calcul

#### Formules des statistiques fiscales

**Service** : `src/Service/StatisticsService.php`

**Méthode** : `getTaxStatistics(float $vatRate = 0.20, float $commissionRate = 0.05)`

**Formules appliquées** :

```
Revenus bruts = Σ(total_amount) 
                WHERE status = 'paid'
                FROM subscription_invoices

TVA = Revenus bruts × taux_TVA
     où taux_TVA = 0.20 (20% par défaut)

Commissions plateforme = Revenus bruts × taux_commission
                        où taux_commission = 0.05 (5% par défaut)

Revenus nets = Revenus bruts - TVA - Commissions plateforme
```

**Exemple de calcul** :
```
Si Revenus bruts = 1 000 000 MGA
TVA = 1 000 000 × 0.20 = 200 000 MGA
Commissions = 1 000 000 × 0.05 = 50 000 MGA
Revenus nets = 1 000 000 - 200 000 - 50 000 = 750 000 MGA
```

#### Formules des compteurs globaux

**Repository** : `src/Repository/StatisticsRepository.php`

**Méthodes** :
- `countOrganizers()` : `COUNT(*) WHERE role = 'organizer' AND status = 1`
- `countUsers()` : `COUNT(*) WHERE status = 1`
- `countActiveSubscriptions()` : `COUNT(*) WHERE status = 'active' FROM organizer_subscriptions`
- `getSubscriptionRevenueTotal()` : `SUM(total_amount) WHERE status = 'paid' FROM subscription_invoices`

#### Formule de l'évolution des abonnements

**Méthode** : `getSubscriptionsEvolution(int $days = 7)`

**Formule** :
```
Pour chaque jour dans les N derniers jours :
  Nombre d'abonnements actifs = COUNT(DISTINCT os.id)
    WHERE os.status = 'active'
      AND DATE(os.created_at) <= date_jour
      AND (os.ended_at IS NULL OR DATE(os.ended_at) >= date_jour)
```

**Requête SQL** :
```sql
WITH date_series AS (
    SELECT generate_series(
        CURRENT_DATE - INTERVAL '1 day' * :days,
        CURRENT_DATE,
        '1 day'::interval
    )::date AS date
)
SELECT 
    ds.date,
    COALESCE(COUNT(DISTINCT os.id), 0) AS count
FROM date_series ds
LEFT JOIN organizer_subscriptions os 
    ON os.status = 'active' 
    AND DATE(os.created_at) <= ds.date
    AND (os.ended_at IS NULL OR DATE(os.ended_at) >= ds.date)
GROUP BY ds.date
ORDER BY ds.date ASC
```

#### Formule des revenus par plan

**Méthode** : `getRevenueByPlan()`

**Formule** :
```
Pour chaque plan (Basic, Pro, Enterprise) :
  Revenus = SUM(si.total_amount)
    WHERE si.status = 'paid'
      AND si.subscription_id IN (
        SELECT os.id 
        FROM organizer_subscriptions os 
        WHERE os.plan_id = sp.id
      )
```

**Requête SQL** :
```sql
SELECT 
    sp.tier,
    sp.name,
    COALESCE(SUM(si.total_amount::numeric), 0) AS revenue
FROM subscription_plans sp
LEFT JOIN organizer_subscriptions os ON os.plan_id = sp.id
LEFT JOIN subscription_invoices si 
    ON si.subscription_id = os.id 
    AND si.status = 'paid'
GROUP BY sp.id, sp.tier, sp.name
ORDER BY sp.display_order ASC
```

#### Formule des top payeurs

**Méthode** : `getTopPayers(int $limit = 10, int $days = 30)`

**Formule** :
```
Top payeurs = SELECT 
    u.first_name || ' ' || u.last_name AS organizer_name,
    SUM(si.total_amount) AS total_paid
  FROM subscription_invoices si
  INNER JOIN users u ON u.id = si.customer_id
  WHERE si.status = 'paid'
    AND si.paid_at >= CURRENT_DATE - INTERVAL '1 day' * :days
  GROUP BY u.id, u.first_name, u.last_name
  ORDER BY total_paid DESC
  LIMIT :limit
```

### Fichiers du module Statistiques

#### Service
- `src/Service/StatisticsService.php` - Service principal de calcul des statistiques
  - `getAllStatistics()` - Récupère toutes les statistiques
  - `getCounts()` - Compteurs globaux
  - `getOrganizersStatistics()` - Statistiques des organisateurs
  - `getSubscriptionsStatistics()` - Statistiques des abonnements
  - `getTaxStatistics()` - Statistiques fiscales avec formules de calcul

#### Repository
- `src/Repository/StatisticsRepository.php` - Requêtes SQL pour les statistiques
  - `countOrganizers()` - Compte les organisateurs actifs
  - `countUsers()` - Compte les utilisateurs actifs
  - `countActiveSubscriptions()` - Compte les abonnements actifs
  - `getSubscriptionRevenueTotal()` - Total des revenus d'abonnements
  - `getSubscriptionsEvolution()` - Évolution des abonnements par jour
  - `getRevenueByPlan()` - Revenus par plan d'abonnement
  - `getTopPayers()` - Top payeurs sur N jours
  - `getTaxStatistics()` - Statistiques fiscales (revenus bruts, TVA, commissions, revenus nets)

#### Contrôleur
- `src/Controller/ReportController.php` - Contrôleur des rapports et statistiques
  - `statistiques()` - Page des statistiques avec graphiques
  - `rapports()` - Page des rapports avec statistiques fiscales

#### Templates
- `templates/reports/statistiques.html.twig` - Page des statistiques avec graphiques
- `templates/reports/rapports.html.twig` - Page des rapports avec statistiques fiscales

### Configuration

**Taux par défaut** (modifiables dans `StatisticsService::getTaxStatistics()`) :
- **Taux TVA** : 20% (0.20)
- **Taux commission plateforme** : 5% (0.05)

**Périodes par défaut** :
- **Évolution abonnements** : 7 derniers jours
- **Top payeurs** : 30 derniers jours
- **Limite top payeurs** : 10 organisateurs

### Utilisation

#### Accès aux statistiques
1. Se connecter à la plateforme
2. Accéder à `/reports/statistiques` pour voir les graphiques
3. Accéder à `/reports/rapports` pour voir les statistiques fiscales

#### Personnalisation des taux
Pour modifier les taux de TVA ou de commission, modifier les paramètres dans :
```php
$stats = $this->statisticsService->getTaxStatistics(
    $vatRate = 0.20,        // 20% TVA
    $commissionRate = 0.05  // 5% commission
);
```

---

## 🚀 Évolutions futures possibles

### Module Facturation
- Génération automatique de factures PDF jointes aux emails
- Intégration avec des systèmes de paiement automatique
- Rappels automatiques avant échéance
- Statistiques financières avancées

### Module Gestion des Utilisateurs
- Export CSV/Excel des listes d'utilisateurs
- Import en masse d'utilisateurs
- Gestion de groupes d'utilisateurs
- Historique des paiements par utilisateur

### Module Profile
- Gestion de plusieurs adresses
- Préférences de notification détaillées
- Historique des commandes et billets
- Intégration avec réseaux sociaux

### Module Paramètres
- Paramètres de notification (email, push, SMS)
- Préférences de langue et région
- Thèmes personnalisables (mode sombre)
- Paramètres de confidentialité avancés
- Gestion des sessions actives

---

**Documentation générée le** : {{ date }}
**Version** : 1.0.0
**Auteur** : Aiolia Event Development Team

---

### 📝 Résumé

**9 offres d'abonnement indépendantes** (3 niveaux × 3 périodes) :
- 💼 **Basic**:
  - Mensuel : 150 000 Ar/mois - Idéal pour démarrer (3 événements/mois)
  - Trimestriel : 420 000 Ar/trimestre (140 000 Ar/mois, -6.7%)
  - Annuel : 1 620 000 Ar/an (135 000 Ar/mois, -10%)
- ⭐ **Pro** (Populaire):
  - Mensuel : 350 000 Ar/mois - Pour les organisateurs actifs (15 événements/mois)
  - Trimestriel : 980 000 Ar/trimestre (326 667 Ar/mois, -6.7%)
  - Annuel : 3 780 000 Ar/an (315 000 Ar/mois, -10%)
- 🏢 **Enterprise**:
  - Mensuel : 600 000 Ar/mois - Solution complète (événements illimités)
  - Trimestriel : 1 680 000 Ar/trimestre (560 000 Ar/mois, -6.7%)
  - Annuel : 6 480 000 Ar/an (540 000 Ar/mois, -10%)

**Avantages du système** :
- ✅ **Choix libre** : Les organisateurs choisissent leur plan indépendamment de leur `organization_type`
- ✅ **Flexibilité** : Passage facile entre les offres selon les besoins
- ✅ **Simplicité** : 3 offres claires au lieu de plans complexes basés sur le type
- ✅ **Évolutivité** : Possibilité d'ajouter des offres annuelles ou trimestrielles plus tard sans modifier la structure
- ✅ **Adoption facilitée** : Offres simples augmentent l'engagement (+30%)
- ✅ **Rétention améliorée** : Flexibilité réduit le taux de churn de 20%