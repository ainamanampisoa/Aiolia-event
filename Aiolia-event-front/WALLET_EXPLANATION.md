# 📱 Explication du Portefeuille Numérique

## 🎯 Qu'est-ce qu'un Portefeuille Numérique ?

Le **portefeuille numérique** (ou "wallet") est un compte virtuel lié à votre compte Aiolia Event qui permet de :
- 💰 Stocker de l'argent (en MGA - Ariary malgache)
- ⭐ Accumuler des points de fidélité
- 💳 Effectuer des paiements rapides pour les billets
- 📊 Suivre toutes vos transactions financières

---

## 💵 Les Deux Types de Soldes

### 1. **Solde en Argent (MGA)**
- Argent réel que vous avez ajouté (rechargé) dans votre wallet
- Utilisé pour acheter des billets d'événements
- Limite maximale : 5 000 000 MGA
- Limite de recharge mensuelle : 1 000 000 MGA/mois

### 2. **Points de Fidélité**
- Points gagnés lors de vos achats (1 point pour 100 MGA dépensés)
- Peuvent être convertis en réduction (100 points = 1 000 MGA)
- Déterminent votre niveau de fidélité (Bronze, Argent, Or, Platine, Diamant)

---

## 🔄 Types de Transactions : Débits et Crédits

### 📈 **CRÉDIT** (Argent qui ENTRE dans votre wallet)

Les crédits **augmentent** votre solde. Exemples :

#### 1. **Recharge du Wallet** (`credit` + `recharge`)
- **Exemple** : Vous rechargez 100 000 MGA via Mobile Money
- **Résultat** : Votre solde passe de 50 000 à 150 000 MGA
- **Description** : "Recharge via Mobile Money - Ref: 123456"

#### 2. **Remboursement** (`credit` + `refund`)
- **Exemple** : Un événement est annulé, vous êtes remboursé de 50 000 MGA
- **Résultat** : Votre solde augmente de 50 000 MGA
- **Description** : "Remboursement - Événement Jazz Night"

#### 3. **Transfert Reçu** (`credit` + `transfer`)
- **Exemple** : Un ami vous transfère 30 000 MGA
- **Résultat** : Vous recevez 30 000 MGA sur votre wallet
- **Description** : "Transfert de l'utilisateur #123"

### 📉 **DÉBIT** (Argent qui SORT de votre wallet)

Les débits **diminuent** votre solde. Exemples :

#### 1. **Achat de Billet** (`debit` + `order`)
- **Exemple** : Vous achetez un billet à 40 000 MGA
- **Résultat** : Votre solde passe de 150 000 à 110 000 MGA
- **Description** : "Achat billet - Concert Jazz"

#### 2. **Transfert Envoyé** (`debit` + `transfer`)
- **Exemple** : Vous transférez 25 000 MGA à un ami
- **Résultat** : Votre solde diminue de 25 000 MGA
- **Description** : "Transfert vers utilisateur #456"

---

## ⭐ Transactions de Points de Fidélité

### 🟢 **Points Crédités** (`points_credit`)
Quand vous **gagnez** des points :

- **Exemple** : Vous achetez un billet à 200 000 MGA
- **Résultat** : Vous gagnez 2 000 points (200 000 ÷ 100 = 2 000)
- **Description** : "Points gagnés - Achat billet #789"

### 🔴 **Points Débités** (`points_debit`)
Quand vous **utilisez** des points :

- **Exemple** : Vous utilisez 500 points pour une réduction de 5 000 MGA
- **Résultat** : Vos points passent de 2 000 à 1 500 points
- **Description** : "Points utilisés - Réduction sur commande"

---

## 📊 Exemple Concret d'Utilisation

### Scénario : Marie achète un billet de concert

**Jour 1 - Recharge**
- Marie recharge 200 000 MGA via Orange Money
- ✅ **Transaction CRÉDIT** : +200 000 MGA
- 💰 Solde : 0 → 200 000 MGA

**Jour 2 - Achat**
- Marie achète un billet de concert à 80 000 MGA
- ✅ **Transaction DÉBIT** : -80 000 MGA
- ⭐ **Transaction POINTS_CREDIT** : +800 points (80 000 ÷ 100)
- 💰 Solde : 200 000 → 120 000 MGA
- ⭐ Points : 0 → 800 points

**Jour 3 - Remboursement**
- Le concert est annulé, Marie est remboursée
- ✅ **Transaction CRÉDIT** : +80 000 MGA (remboursement)
- 💰 Solde : 120 000 → 200 000 MGA
- ⭐ Points : 800 points (conservés)

**Jour 4 - Utilisation des points**
- Marie achète un autre billet à 100 000 MGA
- Elle utilise 500 points pour une réduction de 5 000 MGA
- ✅ **Transaction POINTS_DEBIT** : -500 points
- ✅ **Transaction DÉBIT** : -95 000 MGA (100 000 - 5 000 de réduction)
- 💰 Solde : 200 000 → 105 000 MGA
- ⭐ Points : 800 → 300 points
- ⭐ Nouveau gain : +950 points (95 000 ÷ 100)
- ⭐ Total points : 300 → 1 250 points

---

## 🔍 Statuts des Transactions

Chaque transaction a un **statut** :

- ✅ **`completed`** (Confirmée) : Transaction réussie et terminée
- ⏳ **`pending`** (En attente) : Transaction en cours de traitement
- ❌ **`cancelled`** (Annulée) : Transaction annulée
- ⚠️ **`failed`** (Échouée) : Transaction qui a échoué

---

## 💡 Règles Importantes

### Limites de Sécurité
- 🚫 **Solde maximum** : 5 000 000 MGA (pour éviter le stockage d'argent)
- 🚫 **Recharge mensuelle max** : 1 000 000 MGA/mois
- ✅ **Vérification de solde** : Impossible de débiter plus que ce que vous avez

### Points de Fidélité
- 🎁 **Attribution** : 1 point pour chaque 100 MGA dépensés
- 💰 **Conversion** : 100 points = 1 000 MGA de réduction
- 📊 **Limite d'utilisation** : Maximum 50% du montant peut être payé avec des points

---

## 🎯 Utilisation Pratique

### Pour l'utilisateur :
1. **Recharger** son wallet (Mobile Money, Orange Money, etc.)
2. **Acheter** des billets rapidement sans ressaisir ses informations
3. **Gagner** des points automatiquement à chaque achat
4. **Utiliser** ses points pour des réductions sur les prochains achats
5. **Transférer** de l'argent à d'autres utilisateurs
6. **Consulter** l'historique de toutes ses transactions

### Avantages :
- ⚡ **Paiements plus rapides** (pas besoin de ressaisir les infos de paiement)
- 🎁 **Système de fidélité** intégré (points automatiques)
- 📊 **Traçabilité complète** de toutes les transactions
- 🔒 **Sécurisé** avec des limites et des contrôles

---

## 📝 Résumé Visuel

```
WALLET (Portefeuille)
├── 💰 Solde MGA
│   ├── CRÉDIT (+) : Recharge, Remboursement, Transfert reçu
│   └── DÉBIT (-) : Achat billet, Transfert envoyé
│
└── ⭐ Points de Fidélité
    ├── POINTS_CREDIT (+) : Gagnés lors des achats
    └── POINTS_DEBIT (-) : Utilisés pour réduction
```

**En résumé** :
- **CRÉDIT** = Argent qui ENTRE → Solde augmente 📈
- **DÉBIT** = Argent qui SORT → Solde diminue 📉
- **POINTS_CREDIT** = Points qui ENTRE → Points augmentent ⭐
- **POINTS_DEBIT** = Points qui SORT → Points diminuent ⭐

---

## 🔗 Fichiers Techniques

- **Service** : `WalletService.php` - Logique métier (recharge, débit, crédit)
- **Repository** : `WalletRepository.php` - Accès base de données (wallet)
- **Repository** : `WalletTransactionRepository.php` - Gestion des transactions
- **Template** : `wallet.html.twig` - Interface utilisateur
- **Base de données** : Tables `wallets` et `wallet_transactions`

