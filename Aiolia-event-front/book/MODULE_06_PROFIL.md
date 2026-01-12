# 👤 Module 06 : Profil Utilisateur

## Description

Le module Profil offre un espace personnel complet à chaque utilisateur. Il regroupe le tableau de bord, l'historique des achats, le wallet, les favoris, les statistiques personnelles, l'historique de recherche et les paramètres du compte.

---

## 📂 Fichiers concernés

| Type | Fichier |
|------|---------|
| Contrôleur | `src/Controller/ProfileController.php` |
| Repository | `src/Repository/UserRepository.php` |
| Repository | `src/Repository/OrderRepository.php` |
| Repository | `src/Repository/UserStatsRepository.php` |
| Repository | `src/Repository/WishlistRepository.php` |
| Repository | `src/Repository/SearchHistoryRepository.php` |
| Repository | `src/Repository/ActivityRepository.php` |
| Service | `src/Service/WalletService.php` |
| Service | `src/Service/LoyaltyPointsService.php` |
| Service | `src/Service/CloudinaryService.php` |
| Templates | `templates/profile/*.html.twig` |

---

## 🎯 Fonctionnalités

### 1. Tableau de bord (`/profile`)
- Informations utilisateur
- Statistiques rapides (billets, commandes, panier)
- Activités récentes

### 2. Historique des achats (`/profile/history`)
- Liste des commandes paginée
- Filtres par statut et méthode de paiement
- Recherche par numéro de commande
- Export CSV
- Téléchargement de factures PDF

### 3. Wallet (`/profile/wallet`)
- Solde en MGA
- Points de fidélité
- Historique des transactions
- Recharge du wallet
- Transfert entre utilisateurs

### 4. Favoris (`/profile/favorites`)
- Liste des événements favoris
- Retrait des favoris
- Accès rapide aux détails

### 5. Calendrier (`/profile/calendar`)
- Vue calendrier des événements achetés
- Synchronisation avec calendriers externes

### 6. Historique de recherche (`/profile/search-history`)
- Liste des recherches effectuées
- Nombre de résultats par recherche
- Suppression individuelle ou totale

### 7. Statistiques (`/profile/stats`)
- Total dépensé
- Répartition par catégorie
- Top événements achetés
- Graphiques mensuels
- Insights personnalisés

### 8. Historique financier (`/profile/financial-history`)
- Vue détaillée des dépenses
- Graphiques par mois
- Export PDF

### 9. Paramètres (`/profile/settings`)
- Informations personnelles
- Préférences (langue, notifications, thème)
- Photo de profil (upload Cloudinary)

---

## 🔄 Flux tableau de bord

```
┌─────────────────┐
│  /profile       │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ProfileController│
│    index()      │
└────────┬────────┘
         │
         ├──► UserRepository::findUserInfo()
         ├──► UserStatsRepository::findUserStats()
         ├──► ActivityRepository::findRecentActivities()
         │
         ▼
┌─────────────────┐
│ profile/index.  │
│   html.twig     │
└─────────────────┘
```

---

## 📋 Scénarios d'utilisation

### Scénario 1 : Consultation du tableau de bord

1. **Lova** se connecte et accède à son profil
2. Elle voit son avatar et son nom
3. Les statistiques s'affichent :
   - 5 billets achetés
   - 3 événements à venir
   - 150 000 MGA dépensés
4. Elle consulte ses activités récentes :
   - "Billet acheté pour Concert Jazz" (il y a 2h)
   - "Événement ajouté aux favoris" (hier)

### Scénario 2 : Historique des achats

1. **Aina** accède à `/profile/history`
2. Elle voit toutes ses commandes passées
3. Elle filtre par "Payées" pour voir les confirmées
4. Elle recherche "CMD-000123"
5. Elle clique sur une commande pour voir les détails
6. Elle télécharge la facture en PDF
7. Elle exporte tout l'historique en CSV pour sa comptabilité

### Scénario 3 : Utilisation du Wallet

1. **Nirina** accède à son wallet
2. Son solde affiche : 50 000 MGA
3. Ses points de fidélité : 1 250 pts (niveau Silver)
4. Elle décide de recharger 100 000 MGA
5. Elle sélectionne MVola et confirme
6. Son nouveau solde : 150 000 MGA
7. Elle peut utiliser ce solde pour ses prochains achats

### Scénario 4 : Statistiques personnelles

1. **Tojo** consulte ses statistiques
2. Il voit qu'il a dépensé 500 000 MGA cette année
3. Le graphique montre un pic en décembre (fêtes)
4. La répartition indique : 60% Concerts, 30% Sport, 10% Autres
5. Son événement le plus acheté : "Festival Gasy" (4 billets)
6. Les insights suggèrent : "Vous adorez les concerts !"

### Scénario 5 : Modification du profil

1. **Mamy** accède aux paramètres
2. Elle change sa photo de profil (upload vers Cloudinary)
3. Elle met à jour son numéro de téléphone
4. Elle active les notifications push
5. Elle passe en mode sombre
6. Elle change la langue en anglais
7. Toutes les modifications sont sauvegardées

---

## 🛠️ Points techniques

### Récupération des statistiques utilisateur

```php
private function fetchUserStats(int $userId, array $sessionCartItems = []): array
{
    return $this->userStatsRepository->findUserStats($userId, $sessionCartItems);
}
```

### Gestion du Wallet

```php
// Recharge
$transactionId = $this->walletService->rechargeWallet(
    $userId, 
    $amount, 
    $paymentMethod, 
    $reference
);

// Transfert
$result = $this->walletService->transferToWallet(
    $fromUserId, 
    $toUserId, 
    $amount, 
    $description
);

// Solde
$balance = $this->walletService->getWalletBalance($userId);
// Retourne: ['balance' => 150000, 'currency' => 'MGA', 'points' => 1250]
```

### Upload avatar vers Cloudinary

```php
$uploadResult = $this->cloudinaryService->uploadUploadedFile(
    $uploadedFile,
    'avatars',
    ['public_id' => 'user_' . $userId . '_' . time()]
);

$newAvatarUrl = $uploadResult['secure_url'];
$this->userRepository->updateAvatarUrl($userId, $newAvatarUrl);
```

### Insights dynamiques

```php
private function fetchStatsInsights(int $userId, ?\DateTimeImmutable $dateFrom = null): array
{
    $insights = [
        'moments' => [],
        'suggestions' => [],
    ];

    // Mois le plus actif
    $mostActiveMonth = $this->getMostActiveMonth($userId, $dateFrom);
    if ($mostActiveMonth) {
        $insights['moments'][] = [
            'icon' => 'fas fa-calendar-star',
            'text' => "Votre mois le plus actif était <strong>{$mostActiveMonth['month']}</strong>"
        ];
    }

    // Économies avec codes promo
    $totalSaved = $this->getTotalSavedWithPromos($userId, $dateFrom);
    if ($totalSaved > 0) {
        $insights['moments'][] = [
            'icon' => 'fas fa-tag',
            'text' => "Vous avez économisé <strong>" . number_format($totalSaved, 0, ',', ' ') . " MGA</strong>"
        ];
    }

    return $insights;
}
```

---

## 📱 Routes

| Route | Méthode | Description |
|-------|---------|-------------|
| `/profile` | GET | Tableau de bord |
| `/profile/history` | GET | Historique achats |
| `/profile/history/export` | GET | Export CSV |
| `/profile/history/invoice/{id}` | GET | Télécharger facture |
| `/profile/history/{id}/delete` | DELETE | Supprimer commande |
| `/profile/wallet` | GET | Page wallet |
| `/profile/favorites` | GET | Événements favoris |
| `/profile/calendar` | GET | Calendrier événements |
| `/profile/search-history` | GET | Historique recherche |
| `/profile/stats` | GET | Statistiques |
| `/profile/stats/export` | GET | Export stats CSV |
| `/profile/financial-history` | GET | Historique financier |
| `/profile/financial-history/export-pdf` | GET | Export PDF |
| `/profile/settings` | GET | Paramètres |
| `/profile/settings/update` | POST | Sauvegarder paramètres |
| `/profile/upload-avatar` | POST | Upload photo profil |

### API Wallet

| Route | Méthode | Description |
|-------|---------|-------------|
| `/api/wallet/recharge` | POST | Recharger wallet |
| `/api/wallet/transfer` | POST | Transférer |
| `/api/wallet/transactions` | GET | Liste transactions |

---

## 🎨 Éléments d'interface

### Tableau de bord

| Élément | Description |
|---------|-------------|
| Avatar | Photo de profil (ou initiales) |
| Stats cards | Billets, commandes, dépenses |
| Timeline | Activités récentes |
| Quick actions | Liens vers sous-sections |

### Page Wallet

| Élément | Description |
|---------|-------------|
| Solde principal | Grand chiffre en MGA |
| Points fidélité | Badge avec niveau |
| Boutons action | Recharger, Transférer |
| Historique | Tableau des transactions |
| Progression | Barre mensuelle de recharge |

### Page Statistiques

| Élément | Description |
|---------|-------------|
| Cartes KPI | Total dépensé, billets, événements |
| Graphique barres | Dépenses par mois |
| Camembert | Répartition par catégorie |
| Top 3 | Événements les plus achetés |
| Insights | Messages personnalisés |

---

## 🔗 Dépendances

- **UserRepository** : Informations utilisateur
- **OrderRepository** : Commandes et historique
- **UserStatsRepository** : Statistiques personnelles
- **WishlistRepository** : Favoris
- **SearchHistoryRepository** : Historique recherche
- **ActivityRepository** : Activités récentes
- **WalletService** : Gestion du wallet
- **LoyaltyPointsService** : Points de fidélité
- **CloudinaryService** : Upload images
- **Dompdf** : Génération PDF

