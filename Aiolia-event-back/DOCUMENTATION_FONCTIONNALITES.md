# 📚 Documentation Essentielle - Aiolia Event

**Plateforme de gestion d'événements et de billetterie pour Madagascar**

---

## 📋 Table des Matières

1. [📦 Packages et Dépendances](#packages-et-dépendances)
2. [💰 Module Facturation](#module-facturation)
3. [👥 Module Gestion des Utilisateurs](#module-gestion-des-utilisateurs)
4. [📊 Module Statistiques](#module-statistiques)
5. [📋 Plans d'Abonnement](#plans-dabonnement)

---

## 📦 Packages et Dépendances

### Vue d'ensemble

Cette section liste tous les packages PHP installés dans le projet via Composer, leurs versions et leurs usages spécifiques.

### 📚 Packages Principaux (Production)

#### Framework et Core

| Package | Version | Usage |
|---------|---------|-------|
| **symfony/framework-bundle** | 7.3.* | Framework Symfony principal |
| **symfony/console** | 7.3.* | Commandes CLI Symfony |
| **symfony/runtime** | 7.3.* | Runtime Symfony |
| **symfony/flex** | ^2 | Plugin Composer pour Symfony |

#### Base de Données et ORM

| Package | Version | Usage |
|---------|---------|-------|
| **doctrine/orm** | ^3.5 | ORM Doctrine pour la gestion des entités |
| **doctrine/dbal** | ^4.3 | Abstraction de base de données |
| **doctrine/doctrine-bundle** | ^2.16 | Intégration Doctrine avec Symfony |
| **doctrine/doctrine-migrations-bundle** | ^3.4 | Gestion des migrations de base de données |

#### Templates et Vues

| Package | Version | Usage |
|---------|---------|-------|
| **twig/twig** | ^2.12\|^3.0 | Moteur de templates Twig |
| **twig/extra-bundle** | ^2.12\|^3.0 | Extensions Twig supplémentaires |
| **symfony/twig-bundle** | 7.3.* | Intégration Twig avec Symfony |

#### Sécurité et Authentification

| Package | Version | Usage |
|---------|---------|-------|
| **symfony/security-bundle** | 7.3.* | Système de sécurité et authentification |
| **symfony/validator** | 7.3.* | Validation des données |

#### Formulaires

| Package | Version | Usage |
|---------|---------|-------|
| **symfony/form** | 7.3.* | Gestion des formulaires Symfony |
| **symfony/property-access** | 7.3.* | Accès aux propriétés d'objets |
| **symfony/property-info** | 7.3.* | Métadonnées des propriétés |

#### Communication et Notifications

| Package | Version | Usage |
|---------|---------|-------|
| **symfony/mailer** | 7.3.* | Envoi d'emails |
| **symfony/mime** | 7.3.* | Gestion des types MIME |
| **symfony/notifier** | 7.3.* | Notifications multi-canal (Email, SMS, Push) |
| **symfony/http-client** | 7.3.* | Client HTTP pour requêtes externes |

#### Assets et Frontend

| Package | Version | Usage |
|---------|---------|-------|
| **symfony/asset** | 7.3.* | Gestion des assets (CSS, JS, images) |
| **symfony/asset-mapper** | 7.3.* | Mapper d'assets moderne |
| **symfony/stimulus-bundle** | ^2.30 | Intégration Stimulus.js |
| **symfony/ux-turbo** | ^2.30 | Intégration Turbo (Hotwire) |

#### Internationalisation

| Package | Version | Usage |
|---------|---------|-------|
| **symfony/translation** | 7.3.* | Système de traduction |
| **symfony/intl** | 7.3.* | Internationalisation (dates, nombres, devises) |

#### Utilitaires

| Package | Version | Usage |
|---------|---------|-------|
| **symfony/string** | 7.3.* | Manipulation de chaînes |
| **symfony/process** | 7.3.* | Exécution de processus système |
| **symfony/serializer** | 7.3.* | Sérialisation/désérialisation (JSON, XML) |
| **symfony/yaml** | 7.3.* | Parsing de fichiers YAML |
| **symfony/expression-language** | 7.3.* | Langage d'expression pour règles métier |
| **symfony/web-link** | 7.3.* | Gestion des liens HTTP |
| **symfony/dotenv** | 7.3.* | Chargement des variables d'environnement |
| **symfony/doctrine-messenger** | 7.3.* | Intégration Doctrine avec Messenger |

#### Logging

| Package | Version | Usage |
|---------|---------|-------|
| **symfony/monolog-bundle** | ^3.0 | Intégration Monolog pour les logs |

### 🎨 Packages Spécialisés

#### Génération de PDF

| Package | Version | Usage | Fichiers |
|---------|---------|--------|----------|
| **dompdf/dompdf** | ^3.1 | Génération de PDF depuis HTML | `InvoicePdfService.php`, `EventsController.php` |

**Utilisation** :
- Génération de factures PDF (`src/Service/Organisateur/InvoicePdfService.php`)
- Export PDF des événements (`src/Controller/Organisateur/EventsController.php`)
- Conversion HTML vers PDF avec support SVG

#### Génération de QR Codes

| Package | Version | Usage | Fichiers |
|---------|---------|--------|----------|
| **endroid/qr-code** | ^6.0 | Génération de codes QR | `QrCodeService.php` |

**Utilisation** :
- Génération de QR codes pour les billets (`src/Service/QrCodeService.php`)
- Validation des QR codes avec checksum SHA256
- Export en PNG, base64 ou fichier

#### Traitement d'Images

| Package | Version | Usage | Fichiers |
|---------|---------|--------|----------|
| **imagine/imagine** | ^1.5 | Manipulation d'images (redimensionnement, crop, etc.) | `SvgToImageConverter.php` |

**Utilisation** :
- Conversion SVG vers images (`src/Service/Organisateur/SvgToImageConverter.php`)
- Redimensionnement d'images
- Traitement des images avant upload

#### Stockage Cloud (Images/Vidéos)

| Package | Version | Usage | Fichiers |
|---------|---------|--------|----------|
| **cloudinary/cloudinary_php** | ^3.1 | Upload et gestion de médias sur Cloudinary | `CloudinaryService.php`, `MediaService.php` |

**Utilisation** :
- Upload d'images et vidéos (`src/Service/Admin/CloudinaryService.php`)
- Génération de thumbnails automatiques
- Optimisation automatique des images
- Gestion des avatars utilisateurs (`src/Controller/ProfileController.php`)
- Stockage des médias d'événements (`src/Service/Organisateur/MediaService.php`)

**Configuration** :
- Variables d'environnement requises : `CLOUDINARY_CLOUD_NAME`, `CLOUDINARY_API_KEY`, `CLOUDINARY_API_SECRET`

### 📝 Documentation et Analyse

| Package | Version | Usage |
|---------|---------|-------|
| **phpdocumentor/reflection-docblock** | ^5.6 | Parsing des docblocks PHP |
| **phpstan/phpdoc-parser** | ^2.3 | Parser de docblocks pour PHPStan |

### 🧪 Packages de Développement

| Package | Version | Usage |
|---------|---------|-------|
| **phpunit/phpunit** | ^12.4 | Framework de tests unitaires |
| **symfony/maker-bundle** | ^1.64 | Génération de code (entités, contrôleurs, etc.) |
| **symfony/debug-bundle** | 7.3.* | Outils de débogage Symfony |
| **symfony/web-profiler-bundle** | 7.3.* | Profiler web Symfony |
| **symfony/browser-kit** | 7.3.* | Tests fonctionnels (navigateur simulé) |
| **symfony/css-selector** | 7.3.* | Sélecteurs CSS pour tests |
| **symfony/stopwatch** | 7.3.* | Mesure de performance |

### 📦 Extensions PHP Requises

| Extension | Usage |
|-----------|-------|
| **ext-ctype** | Vérification de types de caractères |
| **ext-iconv** | Conversion de jeux de caractères |

### 🔧 Configuration des Packages

#### DomPDF

**Fichier** : `src/Service/Organisateur/InvoicePdfService.php`

```php
$options = new Options();
$options->set('defaultFont', 'DejaVu Sans');
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);
```

#### Endroid QR Code

**Fichier** : `src/Service/QrCodeService.php`

**Méthodes disponibles** :
- `generateQrCodeUrl()` : Génère une URL d'image QR
- `generateQrCodeBase64()` : Génère un QR code en base64
- `generateQrCodeFile()` : Sauvegarde un QR code dans un fichier
- `generateChecksum()` : Génère un checksum SHA256 pour validation

#### Cloudinary

**Fichier** : `src/Service/Admin/CloudinaryService.php`

**Fonctionnalités** :
- Upload d'images avec optimisation automatique
- Upload de vidéos
- Génération de thumbnails
- Transformation d'images (redimensionnement, crop, etc.)
- Suppression de fichiers

**Variables d'environnement** :
```env
CLOUDINARY_CLOUD_NAME=votre_cloud_name
CLOUDINARY_API_KEY=votre_api_key
CLOUDINARY_API_SECRET=votre_api_secret
```

#### Imagine

**Fichier** : `src/Service/Organisateur/SvgToImageConverter.php`

**Fonctionnalités** :
- Manipulation d'images (redimensionnement, crop)
- Conversion de formats
- Traitement avant upload

### 📊 Résumé des Usages par Module

#### Module Facturation
- **dompdf/dompdf** : Génération de factures PDF

#### Module Billets
- **endroid/qr-code** : Génération de QR codes pour les billets

#### Module Médias
- **cloudinary/cloudinary_php** : Stockage et optimisation d'images/vidéos
- **imagine/imagine** : Traitement d'images avant upload

#### Module Utilisateurs
- **cloudinary/cloudinary_php** : Gestion des avatars utilisateurs

#### Module Événements
- **cloudinary/cloudinary_php** : Upload des images d'événements
- **dompdf/dompdf** : Export PDF des événements

### 🚀 Installation des Packages

Pour installer tous les packages :

```bash
cd Aiolia-event-back
composer install
```

Pour installer un package spécifique :

```bash
# PDF
composer require dompdf/dompdf

# QR Code
composer require endroid/qr-code

# Images
composer require imagine/imagine

# Cloud Storage
composer require cloudinary/cloudinary_php
```

### 📝 Notes Importantes

- ✅ **Tous les packages** sont versionnés avec des contraintes de compatibilité
- ✅ **Symfony 7.3** : Tous les packages Symfony sont en version 7.3.*
- ✅ **PHP 8.2+** : Requis pour tous les packages
- ✅ **Stabilité** : Tous les packages sont en stabilité "stable"
- ⚠️ **Cloudinary** : Nécessite une configuration dans `.env.local`
- ⚠️ **DomPDF** : Nécessite des polices pour le support UTF-8 (DejaVu Sans par défaut)

---

## 💰 Module Facturation

### Vue d'ensemble

Le système de facturation automatique gère les abonnements mensuels des organisateurs. Les factures sont générées automatiquement, suivies et envoyées par email lorsqu'elles sont payées.

### Fonctionnalités principales

#### 1. Génération automatique des factures mensuelles

**Commande** : `php bin/console app:generate-monthly-invoices`

**CRON** : `0 2 27-31 * *` (5 derniers jours du mois)

**Fichiers** :
- `src/Command/GenerateMonthlyInvoicesCommand.php`
- `src/Service/SubscriptionInvoiceGenerationService.php`

#### 2. Marquage automatique des factures en retard

**Commande** : `php bin/console app:mark-overdue-invoices`

**CRON** : `0 3 10-15 * *` (10-15 du mois)

**Fichier** : `src/Command/MarkOverdueInvoicesCommand.php`

#### 3. Mise en pause automatique des abonnements non payés

**Commande** : `php bin/console app:auto-pause-unpaid-subscriptions`

**CRON** : `0 0 11 * *` (11ème jour du mois)

**Règle** : Si non payé avant le 11ème jour, l'abonnement passe en pause automatiquement.

**Fichier** : `src/Command/AutoPauseUnpaidSubscriptionsCommand.php`

#### 4. Mise à jour automatique du statut des événements

**Commande** : `php bin/console app:update-event-status`

**CRON** : `0 0 * * *` (une fois par jour à minuit)

**Règle** : Vérifie les événements "à venir" et met à jour leur statut s'ils sont maintenant en cours (live). Un événement est considéré comme "live" si sa date de début est passée et qu'il n'est pas encore terminé.

**Fichier** : `src/Command/UpdateEventStatusCommand.php`

**Protection** : La commande utilise un fichier de verrouillage pour s'assurer qu'elle ne s'exécute qu'une seule fois par jour, même si elle est appelée plusieurs fois.

#### 5. Statuts des factures

- `draft` → `issued` → `paid` (après paiement)
- `issued` → `overdue` (si non payée après échéance)
- `pending` (facture prépayée)
- `suspendue` (facture de mois en pause, 0 Ar)

#### 6. Envoi automatique après paiement

**Fichier** : `src/EventSubscriber/SubscriptionInvoiceSubscriber.php`

**Service** : `src/Service/InvoiceEmailService.php`

Lorsqu'une facture passe au statut `paid`, un email est automatiquement envoyé.

### Fichiers principaux

- **Contrôleur** : `src/Controller/Admin/BillingController.php`
- **Entité** : `src/Entity/SubscriptionInvoice.php`
- **Templates** : `templates/admin/billing/*.html.twig`

---

## 👥 Module Gestion des Utilisateurs

### Vue d'ensemble

Gestion complète des comptes utilisateurs (organisateurs, utilisateurs, admins) avec validation, modification de rôles et audit.

### Fonctionnalités principales

#### Routes principales

- `/admin/users` - Liste avec recherche, filtres (rôle, statut), pagination (5/page)
- `/admin/users/{id}` - Détails utilisateur (infos, audit, événements)
- `/admin/users/{id}/change-role` - Modification du rôle (POST)
- `/admin/users/{id}/toggle-status` - Activation/Désactivation (POST)
- `/admin/users/{id}/delete` - Suppression (POST, protection CSRF)
- `/admin/users/audit/history` - Historique des actions avec filtres
- `/admin/users/{id}/events` - Événements d'un organisateur
- `/admin/users/autocomplete` - Recherche rapide (JSON, min 2 caractères)

#### Module Validation

- `/admin/validation/pending` - Liste des comptes en attente
- `/admin/validation/{id}/approve` - Approuver (POST, envoi email automatique)
- `/admin/validation/{id}/reject` - Rejeter (POST, commentaire obligatoire)

### Services

- **AuditLogService** : Journalisation de toutes les actions (qui, quoi, quand, détails)
- **UserNotificationService** : Envoi automatique d'emails (validation, rejet, changement rôle/statut)

### Fichiers principaux

- **Contrôleurs** : `UserManagementController.php`, `UserValidationController.php`
- **Services** : `AuditLogService.php`, `UserNotificationService.php`
- **Templates** : `templates/admin/users/*.html.twig`, `templates/admin/validation/*.html.twig`

---

## 📊 Module Statistiques

### Vue d'ensemble

Module d'analyse des performances avec widgets, graphiques et filtres de dates.

### Fonctionnalités principales

#### Route : `/admin/reports/statistiques`

#### 4 Widgets
1. **Organisateurs actifs** (ce mois)
2. **Nouveaux organisateurs** (ce mois)
3. **Abonnement le plus utilisé**
4. **Prévision du chiffre d'affaires**

#### 4 Graphiques Chart.js
1. **Courbe nouveaux organisateurs** (6 derniers mois)
2. **Histogramme répartition abonnements** (Basic/Pro/Enterprise)
3. **Courbe prévision CA** (6 mois)
4. **Top 10 Payeurs** (graphique barres horizontales)

#### Filtres
- Date de début
- Date de fin

### Fichiers principaux

- **Contrôleur** : `src/Controller/Admin/StatisticsController.php`
- **Service** : `src/Service/Admin/StatisticsService.php`
- **Repository** : `src/Repository/Admin/StatisticsRepository.php`
- **Template** : `templates/Admin/reports/statistiques.html.twig`

### Notes techniques

- Toutes les requêtes SQL sont dans le repository
- Le service orchestre uniquement les appels au repository
- Utilisation de Chart.js pour les graphiques
- Formatage automatique des montants en Ariary

---

## 📋 Plans d'Abonnement

### 🎯 Vue d'ensemble

**9 offres d'abonnement** : **Basic**, **Pro** et **Enterprise**, chacune en **3 périodes** (mensuel, trimestriel, annuel).

**Principe** : Choix libre parmi les 9 offres, **indépendamment du type d'organisation** (individual, company, non_profit, collective).

### 📊 Tarifs (HT, TVA 20% calculée automatiquement)

| Plan | Mensuel | Trimestriel (-6.7%) | Annuel (-10%) | Plan ID |
|------|---------|---------------------|---------------|---------|
| **BASIC** | 150 000 Ar | 420 000 Ar (140k/mois) | 1 620 000 Ar (135k/mois) | 1, 2, 3 |
| **PRO** ⭐ | 350 000 Ar | 980 000 Ar (327k/mois) | 3 780 000 Ar (315k/mois) | 4, 5, 6 |
| **ENTERPRISE** | 600 000 Ar | 1 680 000 Ar (560k/mois) | 6 480 000 Ar (540k/mois) | 7, 8, 9 |

**Calcul prix mensuel** : Annuel = prix/12, Trimestriel = prix/3, Mensuel = prix

### Comparaison rapide

| Fonctionnalité | Basic | Pro ⭐ | Enterprise |
|---|---|---|---|
| **Prix mensuel (HT)** | 150k Ar | 350k Ar | 600k Ar |
| **Événements/mois** | 3 | 15 | Illimité |
| **Support** | Email | Chat + Email | Téléphone + Chat + Email |
| **Statistiques** | Base | Avancées | Complètes |
| **API** | Non | Limitée | Complète |
| **White-label** | Non | Partiel | Complet |

### Notes importantes

- ✅ Tous les prix sont **HT**, TVA 20% calculée automatiquement
- ✅ Réductions : Trimestriel -6.7%, Annuel -10%
- ✅ Choix libre indépendamment du type d'organisation
- ✅ Calcul automatique du prix mensuel selon période

### Fichiers

- `Base/schema.sql` : Table `plans_abonnements`
- `Base/data.sql` : Insertion des 9 plans
- `src/Service/SubscriptionInvoiceGenerationService.php` : Génération factures

---

## 🔧 Règles Métier Importantes

### Facturation

#### Règle de génération des factures
- **Période** : 5 derniers jours du mois (27-31)
- **Statut initial** : `draft` (brouillon)
- **Protection doublons** : Vérification si facture existe déjà pour le mois
- **Calcul prix** : Basé sur le plan choisi par l'organisateur
  - Mensuel : prix du plan
  - Trimestriel : prix / 3
  - Annuel : prix / 12
- **TVA** : Calculée automatiquement (20% par défaut)

#### Règle de mise en pause automatique
- **Déclenchement** : Le 11ème jour du mois à 00:00
- **Condition** : Facture du mois courant non payée avant le 11ème jour
- **Action** : Abonnement passe en statut `paused`
- **Conséquence** : Factures suivantes à 0 Ar jusqu'à reprise

#### Règle des factures en pause
- **Montant** : 0 Ar (sous-total, TVA, total)
- **Statut** : `suspendue`
- **Génération** : Automatique chaque mois tant que l'abonnement est en pause
- **Reprise** : Facture du mois suivant générée normalement

#### Règle de marquage en retard
- **Période** : Entre le 10ème et 15ème jour du mois
- **Condition** : Facture en statut `draft` ou `issued` avec échéance dépassée
- **Action** : Statut passe à `overdue`
- **Calcul retard** : Basé sur le 10ème jour du mois (date limite de paiement)

### Gestion des Utilisateurs

#### Règles de validation
- **Statut initial organisateur** : `pending` (en attente)
- **Statut initial utilisateur** : `active` (actif immédiatement)
- **Validation** : Admin approuve → statut `active` + email automatique
- **Rejet** : Admin rejette → statut `rejected` + email avec raison

#### Règles d'audit
- **Toutes les actions** sont journalisées (qui, quoi, quand, détails)
- **Actions tracées** :
  - Changement de rôle
  - Changement de statut
  - Validation/Rejet
  - Suppression
  - Modification

#### Protection CSRF
- **Tous les formulaires** protégés par token CSRF
- **Actions sensibles** : Suppression, changement rôle, validation/rejet

### Statistiques

#### Calculs importants
- **Organisateurs actifs** : Compte les abonnements actifs pour le mois
- **Nouveaux organisateurs** : Compte les profils créés dans le mois
- **Abonnement le plus utilisé** : Plan avec le plus d'abonnements actifs
- **Prévision CA** : Somme des prix mensuels des abonnements actifs

#### Filtres de dates
- **Application** : Sur tous les graphiques et statistiques
- **Format** : YYYY-MM-DD
- **Par défaut** : Sans filtre (toutes les données)

---

## 🗄️ Fonctions SQL Importantes (`Base/logic.sql`)

### Fonctions PL/pgSQL

#### `generate_monthly_subscription_invoices(target_month DATE)`
- **Rôle** : Génère les factures d'abonnement pour un mois donné
- **Utilisation** : Appelée par `SubscriptionInvoiceGenerationService`
- **Statuts créés** : `issued`, `pending`, `suspendue` selon le cas
- **Protection** : Vérifie les doublons avant création

#### `update_overdue_invoices_status()`
- **Rôle** : Marque les factures en retard
- **Utilisation** : Commande `app:mark-overdue-invoices`
- **Action** : Met à jour le statut vers `overdue` et calcule `days_overdue`

#### `auto_pause_unpaid_subscriptions()`
- **Rôle** : Met en pause les abonnements non payés
- **Utilisation** : Commande `app:auto-pause-unpaid-subscriptions`
- **Condition** : Facture courante non payée avant le 11ème jour
- **Action** : Statut `paused` + enregistrement de `mis_en_pause_le`

### Vues importantes

#### `vw_subscription_payment_summary`
- **Rôle** : Synthèse des paiements d'abonnements
- **Utilisation** : Module Facturation (`BillingController`)

#### `vw_subscription_invoices_overdue`
- **Rôle** : Liste des factures en retard
- **Utilisation** : Filtre "En retard" dans la vue facturation

#### `vw_subscription_invoice_items`
- **Rôle** : Détail des lignes d'une facture
- **Utilisation** : Affichage HT/TVA/TTC dans `BillingController::showSubscriptionInvoice()`

### Triggers

#### `trg_wallet_transactions_apply`
- **Table** : `transactions_portefeuilles`
- **Fonction** : `wallet_transactions_apply()`
- **Effet** : Applique la transaction et met à jour le solde/points

#### `trg_order_items_adjust_inventory`
- **Table** : `elements_commandes`
- **Fonction** : `order_items_adjust_inventory()`
- **Effet** : Empêche les dépassements de stock et réserve les quantités

#### `trg_tickets_record_stats`
- **Table** : `billets`
- **Fonction** : `tickets_record_stats()`
- **Effet** : Met à jour les statistiques utilisateur/billets

---

## ⚙️ Configuration CRON Recommandée

### Commandes essentielles

```bash
# Générer les factures mensuelles (5 derniers jours du mois)
0 2 27-31 * * cd /chemin/vers/Aiolia-event-back && php bin/console app:generate-monthly-invoices

# Marquer les factures en retard (10-15 du mois)
0 3 10-15 * * cd /chemin/vers/Aiolia-event-back && php bin/console app:mark-overdue-invoices

# Mettre en pause les abonnements non payés (le 11ème jour du mois)
0 0 11 * * cd /chemin/vers/Aiolia-event-back && php bin/console app:auto-pause-unpaid-subscriptions

# Mettre à jour le statut des événements (une fois par jour à minuit)
0 0 * * * cd /chemin/vers/Aiolia-event-back && php bin/console app:update-event-status
```

### Ordre d'exécution important

1. **27-31 du mois** : Génération des factures du mois suivant
2. **11 du mois** : Mise en pause des abonnements non payés
3. **10-15 du mois** : Marquage des factures en retard

---

## 🔐 Sécurité et Permissions

### Rôles

- **ROLE_ADMIN** : Accès complet à tous les modules admin
- **ROLE_ORGANIZER** : Accès au module organisateur
- **ROLE_USER** : Accès utilisateur standard

### Protection des routes

- **Routes admin** : Protégées par `#[IsGranted('ROLE_ADMIN')]`
- **Routes authentifiées** : Protégées par `IS_AUTHENTICATED_REMEMBERED`
- **CSRF** : Tous les formulaires protégés

### Validation des données

- **Côté serveur** : Validation Symfony sur tous les formulaires
- **Hashage mots de passe** : Utilisation de `UserPasswordHasherInterface`
- **Journalisation** : Toutes les actions sensibles tracées

---

## 📊 Architecture des Requêtes

### Principe de séparation

- **Repository** : Toutes les requêtes SQL natives
- **Service** : Orchestration et logique métier (sans SQL)
- **Contrôleur** : Appels aux services uniquement

### Exemple de structure

```
Contrôleur → Service → Repository → Base de données
```

**Repository** (`StatisticsRepository.php`) :
- `countActiveOrganizersForMonth()` : Requête SQL native
- `getTopPayers()` : Requête SQL native

**Service** (`StatisticsService.php`) :
- `getStatistics()` : Appelle les méthodes du repository
- Aucune requête SQL directe

**Contrôleur** (`StatisticsController.php`) :
- Appelle `$statisticsService->getStatistics()`
- Passe les données au template

---

## 🎯 Règles de Calcul des Statistiques

### Organisateurs actifs (mois courant)

```sql
COUNT(DISTINCT po.id)
WHERE u.statut = 1
  AND u.role = 'organizer'
  AND ao.statut = 'active'
  AND ao.commence_le <= fin_mois
  AND (ao.annule_le IS NULL OR ao.annule_le >= début_mois)
  AND (ao.mis_en_pause_le IS NULL OR ao.mis_en_pause_le >= fin_mois OR ao.repris_le <= début_mois)
```

### Nouveaux organisateurs (mois courant)

```sql
COUNT(DISTINCT po.id)
WHERE u.role = 'organizer'
  AND u.statut = 1
  AND po.cree_le >= début_mois
  AND po.cree_le <= fin_mois
```

### Abonnement le plus utilisé

```sql
SELECT sp.niveau, COUNT(DISTINCT ao.id) as count
GROUP BY sp.niveau
ORDER BY count DESC
LIMIT 1
```

### Prévision CA (mois)

```sql
SUM(
  CASE 
    WHEN sp.periode_facturation = 'yearly' THEN sp.prix / 12
    WHEN sp.periode_facturation = 'quarterly' THEN sp.prix / 3
    ELSE sp.prix
  END
)
WHERE ao.statut = 'active'
```

### Top Payeurs

```sql
SELECT po.nom_affichage, SUM(fa.montant_total) as total_paid
WHERE fa.statut = 'paid'
GROUP BY po.id
ORDER BY total_paid DESC
LIMIT 10
```

---

## 📝 Notes Techniques Importantes

### Base de données

- **Schéma** : `aiolia` (PostgreSQL)
- **Tables principales** :
  - `utilisateurs` : Comptes utilisateurs
  - `profils_organisateurs` : Profils organisateurs
  - `abonnements_organisateurs` : Abonnements actifs
  - `plans_abonnements` : Plans disponibles (9 plans)
  - `factures_abonnements` : Factures générées
  - `paiements_abonnements` : Historique des paiements

### Services transversaux

- **InvoiceEmailService** : Envoi emails de factures
- **AuditLogService** : Journalisation des actions
- **UserNotificationService** : Notifications email
- **CloudinaryService** : Upload d'images
- **StatisticsService** : Calculs statistiques

### EventSubscribers

- **SubscriptionInvoiceSubscriber** : Détecte paiement → envoi email automatique

### Commandes Symfony

- `app:generate-monthly-invoices` : Génération factures
- `app:mark-overdue-invoices` : Marquage en retard
- `app:auto-pause-unpaid-subscriptions` : Mise en pause
- `app:update-event-status` : Mise à jour du statut des événements (vérifie les événements à venir et les met à jour s'ils sont en cours/live)

---

## 🚨 Points d'Attention

### Facturation

- ⚠️ **Ne pas exécuter plusieurs fois** la génération de factures le même mois (protection doublons)
- ⚠️ **Ordre CRON important** : Génération → Pause → Marquage retard
- ⚠️ **Factures en pause** : Générées automatiquement à 0 Ar chaque mois

### Statistiques

- ⚠️ **Requêtes SQL** : Toujours dans le repository, jamais dans le service
- ⚠️ **Performance** : Utiliser des index sur les colonnes fréquemment requêtées
- ⚠️ **Filtres dates** : Appliqués sur toutes les statistiques si fournis

### Sécurité

- ⚠️ **CSRF** : Obligatoire sur tous les formulaires POST
- ⚠️ **Validation** : Toujours côté serveur, jamais uniquement côté client
- ⚠️ **Audit** : Toutes les actions sensibles doivent être journalisées

### Données de test

- ⚠️ **Contrainte unique** : `uq_utilisateurs_nom_complet` sur (prenom, nom)
- ⚠️ **Noms uniques** : Utiliser des préfixes différents (NomOrg, NomUser, NomAdmin)
- ⚠️ **Format** : 3 chiffres pour les IDs (LPAD avec 3)

---

## 📚 Références Rapides

### Routes principales

- `/admin/reports/statistiques` : Page statistiques (redirection après login)
- `/admin/billing/invoices` : Liste des factures
- `/admin/users` : Liste des utilisateurs
- `/admin/validation/pending` : Demandes en attente

### Commandes essentielles

```bash
# Génération factures
php bin/console app:generate-monthly-invoices

# Marquage retard
php bin/console app:mark-overdue-invoices

# Mise en pause
php bin/console app:auto-pause-unpaid-subscriptions

# Mise à jour statut événements
php bin/console app:update-event-status
```

### Fichiers clés

- **Facturation** : `BillingController.php`, `SubscriptionInvoiceGenerationService.php`
- **Utilisateurs** : `UserManagementController.php`, `UserValidationController.php`
- **Statistiques** : `StatisticsController.php`, `StatisticsService.php`, `StatisticsRepository.php`
- **Plans** : `Base/schema.sql`, `Base/data.sql`

---

**Documentation version** : 1.0.0  
**Dernière mise à jour** : 2025  
**Auteur** : Aiolia Event Development Team
