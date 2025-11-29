# Architecture : Portefeuille Numérique et Points de Fidélité

## 📋 Vue d'ensemble

Cette architecture gère deux fonctionnalités principales :
1. **Portefeuille numérique** : Solde en MGA pour recharger et payer
2. **Points de fidélité** : Système de récompense basé sur les achats

---

## 🗄️ Structure de la Base de Données

### Tables existantes (déjà créées)
- `wallets` : Stocke le solde et les points de chaque utilisateur
- `wallet_transactions` : Historique de toutes les transactions

### Enums existants
- `wallet_transaction_type_enum` : `'credit'`, `'debit'`, `'points_credit'`, `'points_debit'`
- `wallet_transaction_status_enum` : `'pending'`, `'completed'`, `'cancelled'`, `'failed'`

---

## 🏗️ Architecture Proposée

### 1. **Repositories** (Couche d'accès aux données)

#### `WalletRepository.php`
```php
- findWalletByUserId(int $userId): array|null
- createWallet(int $userId, string $currency = 'MGA'): int
- updateBalance(int $walletId, float $amount, int $points = 0): void
- getBalance(int $userId): array ['balance' => float, 'points' => int]
```

#### `WalletTransactionRepository.php`
```php
- createTransaction(array $data): int
- findUserTransactions(int $userId, ?string $type = null, int $limit = 50): array
- findTransactionById(int $transactionId): array|null
- updateTransactionStatus(int $transactionId, string $status): void
- getTransactionHistory(int $userId, ?\DateTime $startDate = null, ?\DateTime $endDate = null): array
```

---

### 2. **Services** (Logique métier)

#### `WalletService.php`
**Responsabilités** :
- Gestion du portefeuille (création, récupération)
- Recharges de wallet
- Vérification de solde suffisant
- Transactions atomiques (débit/crédit)

**Méthodes principales** :
```php
- getOrCreateWallet(int $userId): int // Retourne wallet_id
- getWalletBalance(int $userId): array
- rechargeWallet(int $userId, float $amount, string $paymentMethod, ?string $reference = null): int // transaction_id
- debitWallet(int $userId, float $amount, string $description, string $relatedEntity, ?int $relatedId = null): int
- creditWallet(int $userId, float $amount, string $description, string $relatedEntity, ?int $relatedId = null): int
- hasSufficientBalance(int $userId, float $requiredAmount): bool
- transferToWallet(int $fromUserId, int $toUserId, float $amount, string $description): int
```

#### `LoyaltyPointsService.php`
**Responsabilités** :
- Calcul et attribution de points
- Règles d'attribution (ex: 1 point par 100 MGA dépensés)
- Conversion points → réduction
- Gestion des niveaux de fidélité

**Méthodes principales** :
```php
- calculatePointsForPurchase(float $amount): int
- awardPoints(int $userId, int $points, string $reason, ?int $orderId = null): void
- deductPoints(int $userId, int $points, string $reason): bool
- getCurrentPoints(int $userId): int
- getLoyaltyTier(int $userId): string // 'bronze', 'silver', 'gold', 'platinum', 'diamond'
- convertPointsToDiscount(int $points): float // Ex: 100 points = 1000 MGA
- canUsePoints(int $userId, int $points): bool
```

---

### 3. **Controllers** (Points d'entrée HTTP)

#### `WalletController.php` (nouveau fichier ou intégration dans ProfileController)
**Routes** :
```php
- GET  /profile/wallet              → Affichage du portefeuille (déjà existant, à compléter)
- POST /api/wallet/recharge         → Recharger le wallet
- POST /api/wallet/transfer         → Transférer vers un autre wallet
- GET  /api/wallet/transactions     → Historique des transactions
- POST /api/wallet/pay-with-wallet  → Payer une commande avec le wallet
```

#### Intégration dans `PaymentService.php` (modification)
**Ajout** :
- Support du paiement par wallet
- Attribution automatique de points après paiement réussi
- Remboursement automatique en points en cas d'annulation

---

## 🔄 Flux de Fonctionnement

### 1. **Recharge du Wallet**
```
1. Utilisateur clique "Recharger"
2. WalletController::recharge() reçoit montant + méthode de paiement
3. WalletService::rechargeWallet() :
   - Crée transaction type 'credit' status 'pending'
   - Appelle API Mobile Money (Orange, Airtel, etc.)
   - Si succès → Met à jour balance + status 'completed'
   - Si échec → status 'failed'
4. Retourne transaction_id + nouveau solde
```

### 2. **Paiement avec Wallet**
```
1. Utilisateur sélectionne "Payer avec Wallet" au checkout
2. PaymentService::processPayment() vérifie :
   - WalletService::hasSufficientBalance()
3. Si OK :
   - Débit du wallet (WalletService::debitWallet())
   - Création de la commande
   - Attribution de points (LoyaltyPointsService::awardPoints())
   - Transaction type 'debit' + points_credit
4. Si solde insuffisant → Erreur
```

### 3. **Attribution de Points de Fidélité**
```
1. Après paiement réussi :
   - LoyaltyPointsService::calculatePointsForPurchase($amount)
   - Ex: 5000 MGA = 50 points (1 point / 100 MGA)
2. LoyaltyPointsService::awardPoints()
   - Crée transaction type 'points_credit'
   - Met à jour points_balance dans wallets
3. Vérifie changement de tier si nécessaire
```

### 4. **Utilisation de Points pour Réduction**
```
1. Utilisateur choisit "Utiliser X points" au checkout
2. Vérification : LoyaltyPointsService::canUsePoints()
3. Calcul réduction : LoyaltyPointsService::convertPointsToDiscount()
   - Ex: 100 points = 1000 MGA de réduction
4. Déduction des points lors du paiement
5. Création transaction type 'points_debit'
```

### 5. **Remboursement**
```
1. Annulation de commande → remboursement
2. Si payé avec wallet → WalletService::creditWallet()
3. Si points utilisés → Restauration des points
4. Transaction type 'credit' + points_credit
```

---

## 💡 Règles Métier

### Points de Fidélité
- **Attribution** : 1 point pour chaque 100 MGA dépensés
- **Conversion** : 100 points = 1000 MGA de réduction (10%)
- **Limite d'utilisation** : Max 50% du montant avec points
- **Expiration** : Points valables 12 mois
- **Tiers** :
  - Bronze : 0-499 points
  - Silver : 500-1999 points (5% réduction permanente)
  - Gold : 2000-4999 points (10% réduction permanente)
  - Platinum : 5000-9999 points (15% réduction permanente)
  - Diamond : 10000+ points (20% réduction permanente)

### Portefeuille
- **Limite de recharge** : Max 1 000 000 MGA/mois
- **Limite de solde** : Max 5 000 000 MGA
- **Frais de transaction** : 0% (gratuit)
- **Réversibilité** : Les remboursements sont automatiques

---

## 📁 Structure des Fichiers

```
Aiolia-event-front/src/
├── Entity/
│   ├── Wallet.php (à créer si Doctrine ORM)
│   └── WalletTransaction.php (à créer si Doctrine ORM)
│
├── Repository/
│   ├── WalletRepository.php (nouveau)
│   └── WalletTransactionRepository.php (nouveau)
│
├── Service/
│   ├── WalletService.php (nouveau)
│   └── LoyaltyPointsService.php (nouveau)
│
├── Controller/
│   ├── WalletController.php (nouveau, ou intégrer dans ProfileController)
│   └── PaymentService.php (modifier)
│
└── templates/profile/
    └── wallet.html.twig (modifier pour données dynamiques)
```

---

## 🔗 Intégrations Nécessaires

### 1. **Modification de PaymentService**
```php
// Dans processPayment(), ajouter support wallet :
if ($paymentData['method'] === 'wallet') {
    $walletService->debitWallet(...);
}

// Après paiement réussi :
$points = $loyaltyService->calculatePointsForPurchase($totalAmount);
$loyaltyService->awardPoints($userId, $points, 'Purchase', $orderId);
```

### 2. **Modification de ProfileController**
```php
// Dans wallet() :
- Récupérer solde via WalletRepository
- Récupérer transactions via WalletTransactionRepository
- Passer données au template
```

### 3. **Ajout dans le template de paiement**
- Option "Payer avec Wallet" si solde suffisant
- Option "Utiliser X points" pour réduction

---

## ✅ Points d'Attention

1. **Transactions atomiques** : Utiliser des transactions DB pour éviter les inconsistances
2. **Concurrence** : Gérer les cas où plusieurs opérations simultanées modifient le wallet
3. **Audit** : Toutes les opérations doivent être tracées dans `wallet_transactions`
4. **Sécurité** : Vérifier l'utilisateur avant chaque opération sur son wallet
5. **Validation** : Vérifier les montants (positifs, dans les limites, etc.)

---

## 🚀 Plan d'Implémentation (Ordre recommandé)

1. ✅ **Repositories** : Créer WalletRepository et WalletTransactionRepository
2. ✅ **Services** : Créer WalletService et LoyaltyPointsService
3. ✅ **Controller** : Créer/modifier WalletController
4. ✅ **Template** : Rendre wallet.html.twig dynamique
5. ✅ **Intégration Paiement** : Modifier PaymentService pour supporter wallet
6. ✅ **Points automatiques** : Ajouter attribution après paiement
7. ✅ **Tests** : Tester tous les scénarios (recharge, paiement, remboursement)

---

## 📝 Notes Supplémentaires

- Les wallets sont créés automatiquement au premier achat ou recharge
- Les transactions en attente expirent après 24h si non complétées
- L'historique des transactions est limité à 1000 entrées par utilisateur
- Les remboursements créent automatiquement une transaction inverse

