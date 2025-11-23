# Documentation - Génération de Factures d'Abonnements

## Vue d'ensemble

Ce document décrit le système de génération automatique des factures d'abonnements et les règles de gestion des paiements et des pauses automatiques.

## Règles de Génération des Factures

### 1. Création des Factures

**Règle principale** : Les factures sont créées automatiquement le **1er du mois** pour tous les organisateurs avec abonnements actifs.

#### Conditions de création
- ✅ L'abonnement doit être actif (`statut = 'active'`)
- ✅ Le plan d'abonnement doit être actif
- ✅ Aucune facture n'existe déjà pour ce mois et cet abonnement
- ✅ L'abonnement n'est pas annulé

#### Dates importantes
- **Date d'émission** : 1er du mois à 00:00:00
- **Date d'échéance** : 10ème jour du mois à 23:59:59
- **Statut initial** : `issued` (émise)

### 2. Gestion des Factures en Retard

**Règle** : Si une facture n'est pas payée dans les **10 jours** (après l'échéance), deux actions automatiques sont déclenchées :

1. **Statut de la facture** → `overdue` (en retard)
2. **Abonnement de l'organisateur** → `paused` (mis en pause automatiquement)

#### Processus de mise en pause automatique

Lorsqu'une facture devient en retard :
- Le statut de la facture passe à `overdue`
- L'abonnement de l'organisateur est automatiquement mis en pause
- La date de mise en pause est enregistrée (`mis_en_pause_le`)
- Les métadonnées incluent :
  - `auto_paused_reason`: 'invoice_overdue'
  - `auto_paused_at`: Date et heure de la pause
  - `overdue_invoice_id`: ID de la facture en retard
  - `overdue_invoice_number`: Numéro de la facture
  - `overdue_invoice_month`: Mois de facturation (format YYYY-MM)

## Architecture des Services

### 1. SubscriptionInvoiceGenerationService

Service principal pour la génération automatique des factures.

#### Méthodes principales

##### `generateMonthlyInvoices(\DateTimeInterface $targetMonth): array`

Génère les factures pour un mois donné.

**Paramètres** :
- `$targetMonth` : Le mois pour lequel générer les factures (1er du mois)

**Retour** :
```php
[
    'created' => int,    // Nombre de factures créées
    'skipped' => int,    // Nombre de factures ignorées (déjà existantes)
    'errors' => array    // Liste des erreurs rencontrées
]
```

**Fonctionnement** :
1. Récupère tous les abonnements actifs via `OrganizerSubscriptionRepository`
2. Pour chaque abonnement :
   - Vérifie si une facture existe déjà pour ce mois via `SubscriptionInvoiceRepository`
   - Si aucune facture n'existe, crée une nouvelle facture avec :
     - Date d'émission : 1er du mois
     - Date d'échéance : 10ème jour du mois
     - Statut : `issued`

##### `markOverdueInvoices(\DateTimeInterface $currentDate): array`

Marque les factures en retard et met en pause les organisateurs concernés.

**Paramètres** :
- `$currentDate` : La date actuelle (doit être après le 10ème jour du mois)

**Retour** :
```php
[
    'updated' => int,    // Nombre de factures marquées comme en retard
    'paused' => int,     // Nombre d'organisateurs mis en pause
    'errors' => array    // Liste des erreurs rencontrées
]
```

**Fonctionnement** :
1. Récupère toutes les factures en retard via `SubscriptionInvoiceRepository::findOverdueInvoices()`
2. Pour chaque facture en retard :
   - Change le statut à `overdue`
   - Récupère l'abonnement associé
   - Si l'abonnement est actif, le met en pause automatiquement via `OrganizerSubscriptionRepository::pauseSubscription()`
   - Enregistre les métadonnées de la pause automatique

### 2. PrepaidSubscriptionPaymentService

Service pour gérer les paiements en avance (prépayés).

#### Méthodes principales

##### `processPrepaidPayment(...): array`

Traite un paiement en avance pour plusieurs mois.

**Fonctionnement** :
- Utilise `SubscriptionInvoiceRepository` pour vérifier les factures existantes
- Utilise `OrganizerSubscriptionRepository` pour mettre à jour le crédit prépayé
- Crée ou met à jour les factures prépayées via les entités Doctrine

## Repositories

### 1. OrganizerSubscriptionRepository

Repository pour les opérations sur les abonnements organisateurs.

#### Méthodes disponibles

- `findActiveSubscriptionsForInvoiceGeneration(): array`
  - Récupère tous les abonnements actifs pour la génération de factures
  
- `pauseSubscription(int $subscriptionId, \DateTimeInterface $pausedDate, array $metadata = []): void`
  - Met en pause un abonnement avec métadonnées
  
- `findSubscription(int $subscriptionId): ?array`
  - Trouve un abonnement par ID
  
- `updatePrepaidCredit(int $subscriptionId, int $numberOfMonths): void`
  - Met à jour le crédit prépayé
  
- `getRemainingPrepaidMonths(int $subscriptionId): int`
  - Récupère le nombre de mois prépayés restants

### 2. SubscriptionInvoiceRepository

Repository pour les opérations sur les factures d'abonnements.

#### Méthodes disponibles

- `findInvoiceForMonth(string $subscriptionId, \DateTimeInterface $billingMonth): ?SubscriptionInvoice`
  - Trouve une facture existante pour un mois donné
  
- `findOverdueInvoices(\DateTimeInterface $currentDate): array`
  - Trouve toutes les factures en retard (non payées après l'échéance)
  
- `findInvoiceBySubscriptionAndMonth(int $subscriptionId, string $billingMonth): ?SubscriptionInvoice`
  - Trouve une facture par abonnement et mois (format 'Y-m-01')

## Statuts des Factures

### Statuts disponibles

- `draft` : Brouillon (non utilisé pour les factures auto-générées)
- `issued` : Émise (statut initial le 1er du mois)
- `paid` : Payée
- `partially_paid` : Partiellement payée
- `overdue` : En retard (après le 10ème jour si non payée)
- `void` : Annulée
- `refunded` : Remboursée
- `pending` : En attente (pour factures prépayées)

### Statuts des Abonnements

- `active` : Actif
- `paused` : En pause (automatique ou manuel)
- `cancelled` : Annulé

## Flux de Génération Mensuelle

### Étape 1 : Génération des Factures (1er du mois)

```
1. Appel de generateMonthlyInvoices() avec le mois courant
2. Récupération des abonnements actifs
3. Pour chaque abonnement :
   - Vérification de l'existence d'une facture pour ce mois
   - Si inexistante : création d'une nouvelle facture
     - Date d'émission : 1er du mois
     - Date d'échéance : 10ème jour
     - Statut : issued
```

### Étape 2 : Vérification des Factures en Retard (après le 10ème jour)

```
1. Appel de markOverdueInvoices() après le 10ème jour
2. Récupération des factures non payées après l'échéance
3. Pour chaque facture en retard :
   - Changement du statut à overdue
   - Mise en pause automatique de l'abonnement
   - Enregistrement des métadonnées
```

## Exemple d'Utilisation

### Génération des factures du mois courant

```php
$invoiceService = new SubscriptionInvoiceGenerationService(...);
$currentMonth = new \DateTimeImmutable('first day of this month');
$results = $invoiceService->generateMonthlyInvoices($currentMonth);

// Résultats :
// - created: nombre de factures créées
// - skipped: nombre de factures déjà existantes
// - errors: liste des erreurs
```

### Marquage des factures en retard

```php
$currentDate = new \DateTimeImmutable();
$results = $invoiceService->markOverdueInvoices($currentDate);

// Résultats :
// - updated: nombre de factures marquées comme en retard
// - paused: nombre d'organisateurs mis en pause
// - errors: liste des erreurs
```

## Commandes CRON Recommandées

### Génération des factures (1er du mois à 00:00)

```bash
# À exécuter le 1er de chaque mois à 00:00
php bin/console app:generate-monthly-invoices
```

### Marquage des factures en retard (11ème jour à 00:00)

```bash
# À exécuter le 11 de chaque mois à 00:00
php bin/console app:mark-overdue-invoices
```

## Notes Importantes

1. **Pas de requêtes SQL directes** : Les services utilisent uniquement les repositories pour accéder aux données
2. **Transactions** : Les opérations critiques sont effectuées dans des transactions
3. **Logging** : Toutes les opérations importantes sont loggées
4. **Idempotence** : La génération de factures peut être appelée plusieurs fois sans créer de doublons
5. **Mise en pause automatique** : Seuls les abonnements actifs sont mis en pause (pas ceux déjà en pause manuellement)

## Métadonnées des Factures

### Factures auto-générées

```json
{
    "month": 11,
    "year": 2025,
    "billing_period": "monthly",
    "auto_generated": true
}
```

### Factures en retard (mise en pause automatique)

```json
{
    "auto_paused_reason": "invoice_overdue",
    "auto_paused_at": "2025-11-11 00:00:00",
    "overdue_invoice_id": 123,
    "overdue_invoice_number": "INV-2025-001234",
    "overdue_invoice_month": "2025-11"
}
```

## Gestion des Erreurs

Toutes les erreurs sont capturées et enregistrées dans le tableau `errors` retourné par les méthodes. Les erreurs sont également loggées si un logger est disponible.

Les erreurs courantes incluent :
- Utilisateur introuvable pour un abonnement
- Plan d'abonnement introuvable ou inactif
- Erreurs de persistance Doctrine
- Erreurs de calcul des montants

