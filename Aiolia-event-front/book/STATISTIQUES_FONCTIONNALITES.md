# 📊 STATISTIQUES DISPONIBLES DANS L'APPLICATION AIOLIA EVENT

**Date de compilation** : Décembre 2024  
**Source** : Analyse du code source Aiolia-event-front

---

## 📑 TABLE DES MATIÈRES

1. [Statistiques de la Page d'Accueil](#1-statistiques-de-la-page-daccueil)
2. [Statistiques du Profil Utilisateur](#2-statistiques-du-profil-utilisateur)
3. [Statistiques Financières](#3-statistiques-financières)
4. [Statistiques Détaillées](#4-statistiques-détaillées)
5. [Statistiques d'Analyse Personnelle](#5-statistiques-danalyse-personnelle)
6. [Statistiques en Temps Réel](#6-statistiques-en-temps-réel)
7. [Fonctionnalités d'Export](#7-fonctionnalités-dexport)

---

## 1. STATISTIQUES DE LA PAGE D'ACCUEIL

### État numéro 1 : Headlines (Chiffres Clés)

**Emplacement** : Page d'accueil publique  
**Repository** : `EventRepository::findHeadlineStats()`  
**Controller** : `HomeController`

**Tableau 1 : Statistiques affichées publiquement**

| Indicateur | Description | Source de données | Affichage |
|------------|-------------|-------------------|-----------|
| **Total événements** | Nombre total d'événements sur la plateforme | `COUNT(*) FROM events` | Compteur animé |
| **Billets vendus** | Somme de tous les billets vendus | `SUM(sold_quantity) FROM ticket_inventory` | Compteur animé |
| **Organisateurs** | Nombre d'organisateurs actifs | `COUNT(*) FROM organizer_profiles` | Compteur animé |

*Ces statistiques sont visibles par tous les visiteurs (connectés ou non)*

---

## 2. STATISTIQUES DU PROFIL UTILISATEUR

### État numéro 2 : Dashboard Utilisateur

**Emplacement** : `/profile`  
**Repository** : `UserStatsRepository::findUserStats()`  
**Controller** : `ProfileController::index()`

**Tableau 2 : Statistiques du dashboard personnel**

| Indicateur | Description | Calcul | Type |
|------------|-------------|--------|------|
| **Billets actifs** | Billets valides pour événements futurs | `COUNT(tickets) WHERE status='valid' AND starts_at > NOW()` | Entier |
| **Événements favoris** | Nombre d'événements en wishlist | `COUNT(wishlist_items)` | Entier |
| **Panier actif** | Nombre d'événements dans le panier | `COUNT(DISTINCT event_id FROM cart_items)` | Entier |
| **Points de fidélité** | Solde de points disponibles | `wallets.points_balance` | Entier |

*Source : `UserStatsRepository.php` lignes 404-519*

---

## 3. STATISTIQUES FINANCIÈRES

### État numéro 3 : Historique Financier

**Emplacement** : `/profile/financial-history`  
**Repository** : `OrderRepository::findFinancialHistory()`  
**Controller** : `ProfileController::financialHistory()`

**Tableau 3 : Données financières disponibles**

| Catégorie | Statistiques | Filtres disponibles | Période |
|-----------|--------------|---------------------|---------|
| **Dépenses globales** | Total dépensé (MGA) | Année, mois, période | All/Year/Month |
| **Transactions** | Nombre de commandes | Année, mois | All/Year/Month |
| **Montant moyen** | Panier moyen par commande | Année, mois | All/Year/Month |
| **Économies** | Total économisé avec promos | Année, mois | All/Year/Month |

*Source : `ProfileController.php` lignes 1085-1139*

### État numéro 4 : Dépenses Mensuelles

**Repository** : `OrderRepository::findMonthlyFinancialData()`

**Tableau 4 : Analyse mensuelle des dépenses**

| Donnée | Description | Visualisation |
|--------|-------------|---------------|
| **Par mois** | Total dépensé chaque mois | Graphique à barres |
| **Nombre de transactions** | Transactions par mois | Liste détaillée |
| **Évolution** | Tendance des dépenses | Courbe temporelle |
| **Plage** | 6 premiers ou 6 derniers mois | Sélecteur |

*Graphique interactif disponible avec navigation par période*

### État numéro 5 : Méthodes de Paiement

**Repository** : `UserStatsRepository::findPaymentMethodDistribution()`

**Tableau 5 : Répartition des modes de paiement utilisés**

| Indicateur | Description | Calcul | Affichage |
|------------|-------------|--------|-----------|
| **Méthode** | Nom du provider (M-Vola, Orange Money, Airtel Money) | Extraction depuis `orders.notes` | Liste |
| **Nombre** | Nombre de fois utilisé | `COUNT(orders)` par méthode | Nombre |
| **Pourcentage** | Part d'utilisation | `(count / total) × 100` | % |

*Source : `UserStatsRepository.php` lignes 586-682*

**Filtres disponibles :**
- Par année
- Par mois
- Par période personnalisée

---

## 4. STATISTIQUES DÉTAILLÉES

### État numéro 6 : Page Statistiques Personnelles

**Emplacement** : `/profile/stats`  
**Repository** : `UserStatsRepository` (multiples méthodes)  
**Controller** : `ProfileController::stats()`

**Tableau 6 : Vue d'ensemble des statistiques**

| Section | Méthode Repository | Données fournies |
|---------|-------------------|------------------|
| **Statistiques globales** | `findUserStatistics()` | Total billets, dépenses, événements, commandes, panier moyen |
| **Dépenses mensuelles** | `fetchMonthlyExpenses()` | Dépenses par mois avec graphique |
| **Répartition par catégorie** | `findEventTypeDistribution()` | Distribution par type d'événement |
| **Top événements** | `findTopPurchasedEvents()` | Top 3 événements achetés |
| **Insights** | `fetchStatsInsights()` | Analyse intelligente du comportement |
| **Comparaison annuelle** | `fetchYearComparison()` | Évolution année par année |

*Source : `ProfileController.php` lignes 816-876*

### État numéro 7 : Statistiques Générales

**Tableau 7 : Métriques principales de l'utilisateur**

| Statistique | Description | Format | Période filtrable |
|-------------|-------------|--------|-------------------|
| **Total de billets** | Nombre total de billets achetés | Entier | Oui (30/90/365/all) |
| **Total dépensé** | Montant total des achats | "XXX XXX MGA" | Oui |
| **Événements uniques** | Nombre d'événements différents | Entier | Oui |
| **Panier moyen** | Montant moyen par commande | "XXX XXX MGA" | Oui |
| **Nombre de commandes** | Total de commandes effectuées | Entier | Oui |

*Source : `UserStatsRepository.php` lignes 17-128*

**Filtres de période disponibles :**
- 30 derniers jours
- 90 derniers jours
- 365 derniers jours (1 an)
- Toutes les données (all)

### État numéro 8 : Répartition par Type d'Événement

**Repository** : `UserStatsRepository::findEventTypeDistribution()`

**Tableau 8 : Distribution des achats par catégorie**

| Donnée | Description | Calcul |
|--------|-------------|--------|
| **Catégorie** | Type d'événement (Concert, Sport, etc.) | `event_categories.label` |
| **Pourcentage** | Part des dépenses | `(total_amount / total) × 100` |
| **Nombre de commandes** | Commandes par catégorie | `COUNT(orders)` |

*Affichage : Graphique circulaire (pie chart)*

*Source : `UserStatsRepository.php` lignes 133-173*

### État numéro 9 : Top Événements Achetés

**Repository** : `UserStatsRepository::findTopPurchasedEvents()`

**Tableau 9 : Classement des événements préférés**

| Colonne | Description | Type |
|---------|-------------|------|
| **Titre** | Nom de l'événement | Texte |
| **Catégorie** | Type d'événement | Badge |
| **Achats** | Nombre de fois acheté | Entier |
| **Total billets** | Nombre de billets pour cet événement | Entier |
| **Total dépensé** | Montant dépensé pour cet événement | MGA formaté |
| **Premier achat** | Date du premier achat | Date |
| **Dernier achat** | Date du dernier achat | Date |

*Limite : Top 3 (configurable jusqu'à 100)*

*Source : `UserStatsRepository.php` lignes 178-231*

---

## 5. STATISTIQUES D'ANALYSE PERSONNELLE

### État numéro 10 : Insights Intelligents

**Repository** : `ProfileController::fetchStatsInsights()`

**Tableau 10 : Analyses comportementales**

| Insight | Description | Méthode | Affichage |
|---------|-------------|---------|-----------|
| **Mois le plus actif** | Mois avec le plus d'achats | `findMostActiveMonth()` | Badge avec date |
| **Catégorie préférée** | Catégorie la plus achetée | `findFavoriteCategory()` | Badge coloré |
| **Nombre de types** | Diversité des catégories | `countEventTypes()` | Nombre |
| **Total économisé** | Économies avec codes promo | `calculateTotalSavedWithPromos()` | MGA formaté |
| **Événements à venir** | Événements futurs avec billets | `countUpcomingEvents()` | Nombre |

*Source : `UserStatsRepository.php` lignes 236-540*

### État numéro 11 : Mois le Plus Actif

**Repository** : `UserStatsRepository::findMostActiveMonth()`

**Données retournées :**
```php
[
    'month' => 'Février 2024',
    'count' => 5,          // Nombre de commandes
    'total' => '125 000 MGA'  // Total dépensé
]
```

*Source : `UserStatsRepository.php` lignes 236-272*

### État numéro 12 : Catégorie Préférée

**Repository** : `UserStatsRepository::findFavoriteCategory()`

**Données retournées :**
```php
[
    'category' => 'Concert',
    'count' => 15  // Nombre de billets achetés
]
```

*Source : `UserStatsRepository.php` lignes 330-368*

### État numéro 13 : Économies avec Promotions

**Repository** : `UserStatsRepository::calculateTotalSavedWithPromos()`

**Calcul :**
```sql
SUM(discount_amount) FROM orders 
WHERE status = 'paid' AND discount_amount > 0
```

*Retour : Montant total économisé en MGA*

*Source : `UserStatsRepository.php` lignes 277-297*

### État numéro 14 : Recommandations

**Repository** : `UserStatsRepository::findRecommendedCategories()`

**Tableau 11 : Catégories recommandées**

| Donnée | Description |
|--------|-------------|
| **Catégories** | Basées sur l'historique d'achat |
| **Limite** | Maximum 5 catégories |
| **Utilisation** | Pour suggestions d'événements |

*Source : `UserStatsRepository.php` lignes 373-399*

---

## 6. STATISTIQUES EN TEMPS RÉEL

### État numéro 15 : Compteurs Dynamiques

**Tableau 12 : Statistiques actualisées automatiquement**

| Statistique | Fréquence de mise à jour | Source |
|-------------|-------------------------|--------|
| **Billets actifs** | En temps réel | `TicketRepository` |
| **Favoris** | En temps réel | `WishlistRepository` |
| **Panier** | En temps réel | Session + DB |
| **Points fidélité** | À chaque transaction | `WalletRepository` |
| **Notifications non lues** | En temps réel | `NotificationRepository` |

### État numéro 16 : Statistiques par Événement

**Repository** : `TicketRepository::countTicketsSoldForEvent()`

**Pour chaque événement :**
- Nombre de billets vendus
- Nombre d'ajouts aux favoris (`EventFavoriteRepository::countFavoritesForEvent()`)
- Taux de remplissage (calculé)

*Source : `TicketRepository.php` ligne 108 et `EventFavoriteRepository.php` ligne 51*

---

## 7. FONCTIONNALITÉS D'EXPORT

### État numéro 17 : Export des Statistiques

**Emplacement** : `/profile/stats/export`  
**Controller** : `ProfileController::exportStats()`

**Tableau 13 : Formats d'export disponibles**

| Format | Contenu | Utilisation |
|--------|---------|-------------|
| **CSV** | Toutes les statistiques | Analyse externe (Excel, etc.) |
| **Période** | Données filtrées | Rapports personnalisés |

*Source : `ProfileController.php` lignes 878-920*

**Données exportées :**
- Statistiques globales
- Dépenses mensuelles
- Répartition par catégorie
- Top événements
- Insights

### État numéro 18 : Export Financier PDF

**Emplacement** : `/profile/financial-history/export-pdf`  
**Controller** : `ProfileController::exportFinancialHistoryPdf()`  
**Bibliothèque** : Dompdf

**Tableau 14 : Contenu du PDF financier**

| Section | Données incluses |
|---------|------------------|
| **En-tête** | Informations utilisateur, date de génération |
| **Résumé** | Total dépensé, nombre de transactions, panier moyen |
| **Graphique** | Dépenses mensuelles (6 mois) |
| **Méthodes paiement** | Répartition avec pourcentages |
| **Liste détaillée** | Historique des transactions |

*Source : `ProfileController.php` lignes 1141-1201*

**Personnalisation :**
- Filtrage par année
- Filtrage par mois
- Choix de la période (year/month/all)
- Choix de la plage mensuelle (6 premiers/6 derniers mois)

---

## 8. STATISTIQUES TECHNIQUES

### État numéro 19 : Métriques de Recherche

**Repository** : `EventRepository::countSearchResults()`

**Utilisé pour :**
- Nombre de résultats de recherche
- Validation des filtres
- Optimisation des requêtes

**Paramètres de filtrage :**
- Texte de recherche
- Catégorie
- Ville
- Prix min/max
- Dates début/fin

*Source : `EventRepository.php` ligne 1097*

### État numéro 20 : Compteurs Utilisateur

**Repository** : `OrderRepository::countUserOrders()`

**Filtres disponibles :**
- Recherche par texte
- Filtre par statut (paid, pending, cancelled, failed, all)
- Filtre par méthode de paiement

*Source : `OrderRepository.php` ligne 123*

---

## 9. ARCHITECTURE DES STATISTIQUES

### État numéro 21 : Organisation du Code

**Tableau 15 : Répartition des responsabilités**

| Repository | Rôle | Nombre de méthodes statistiques |
|------------|------|--------------------------------|
| **UserStatsRepository** | Statistiques utilisateur | 12 méthodes |
| **EventRepository** | Statistiques événements | 2 méthodes |
| **OrderRepository** | Statistiques commandes | 3 méthodes |
| **TicketRepository** | Statistiques billets | 1 méthode |
| **EventFavoriteRepository** | Statistiques favoris | 1 méthode |
| **NotificationRepository** | Statistiques notifications | 1 méthode |
| **WalletRepository** | Statistiques wallet | 1 méthode |

**Total : 21 méthodes statistiques dans 7 repositories**

---

## 10. RÉSUMÉ FONCTIONNEL

### Tableau 16 : Synthèse des fonctionnalités statistiques

| Catégorie | Nombre d'indicateurs | Pages affichées | Export possible |
|-----------|---------------------|-----------------|-----------------|
| **Page d'accueil** | 3 | 1 (publique) | Non |
| **Dashboard profil** | 4 | 1 (privée) | Non |
| **Statistiques détaillées** | 15+ | 1 (privée) | Oui (CSV) |
| **Historique financier** | 8+ | 1 (privée) | Oui (PDF) |
| **Insights** | 5 | 1 (privée) | Oui (CSV) |
| **En temps réel** | 6 | Toutes | Non |

---

## 🎯 POINTS CLÉS

### Fonctionnalités Implémentées ✅

1. **Statistiques publiques** sur la page d'accueil (headlines)
2. **Dashboard personnel** avec 4 compteurs en temps réel
3. **Page statistiques complète** avec analyses détaillées
4. **Historique financier** avec graphiques et filtres
5. **Export CSV** des statistiques personnelles
6. **Export PDF** de l'historique financier
7. **Insights intelligents** basés sur le comportement
8. **Comparaison temporelle** avec filtres de période
9. **Répartition par catégories** avec visualisation
10. **Top événements** personnalisé

### Données Calculées 📊

- **15+ métriques utilisateur** différentes
- **3 types de graphiques** (barres, circulaire, temporel)
- **4 périodes de filtrage** (30/90/365/all jours)
- **2 formats d'export** (CSV et PDF)
- **12 méthodes** de calcul statistique

### Technologies Utilisées 🛠️

- **Doctrine DBAL** pour requêtes SQL optimisées
- **Agrégations SQL** (SUM, COUNT, AVG, GROUP BY)
- **Filtrage temporel** avec DateTimeImmutable
- **Dompdf** pour génération PDF
- **Twig** pour templating
- **JSON** pour structuration des données

---

## 📈 AMÉLIORATIONS POSSIBLES (NON IMPLÉMENTÉES)

**Statistiques organisateur** : L'`OrganizerController` contient des TODOs pour :
- Dashboard organisateur
- Statistiques par événement
- Revenus générés
- Ventes de billets
- Promotions actives

*Ces fonctionnalités sont planifiées mais pas encore implémentées*

---

*Document compilé le : Décembre 2024*  
*Source : Analyse complète du code source Aiolia-event-front*  
*Repositories analysés : 7*  
*Controllers analysés : 5*  
*Méthodes statistiques identifiées : 21+*

