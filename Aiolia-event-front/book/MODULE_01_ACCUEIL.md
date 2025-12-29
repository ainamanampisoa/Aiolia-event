# 🏠 Module 01 : Page d'Accueil

## Description

La page d'accueil est la vitrine de la plateforme **Aiolia-event**. Elle offre une première impression immersive aux visiteurs, mettant en avant les événements phares et les statistiques clés de la plateforme.

---

## 📂 Fichiers concernés

| Type | Fichier |
|------|---------|
| Contrôleur | `src/Controller/HomeController.php` |
| Template | `templates/home/index.html.twig` |
| Repository | `src/Repository/EventRepository.php` |
| Repository | `src/Repository/WishlistRepository.php` |

---

## 🎯 Fonctionnalités

### 1. Affichage des événements à venir
- **Description** : Affiche les 6 prochains événements triés par date
- **Méthode** : `EventRepository::findUpcomingEventsForHome(6)`
- **Données affichées** : Titre, date, lieu, image, catégorie, prix

### 2. Statistiques globales (Headlines)
- **Description** : Affiche les statistiques clés de la plateforme
- **Méthode** : `EventRepository::findHeadlineStats()`
- **Données** : Nombre d'événements, billets vendus, utilisateurs inscrits

### 3. Gestion des favoris
- **Description** : Pour les utilisateurs connectés, affiche un indicateur ❤️ sur les événements favoris
- **Méthode** : `WishlistRepository::findUserFavoriteEventIds(userId)`
- **Comportement** : L'icône cœur est remplie si l'événement est dans les favoris

### 4. Message de bienvenue post-connexion
- **Description** : Affiche un message de bienvenue personnalisé après la connexion
- **Flag session** : `just_logged_in`
- **Durée** : Affiché une seule fois, puis le flag est supprimé

---

## 🔄 Flux de données

```
┌─────────────────┐
│   Utilisateur   │
│   (Visiteur)    │
└────────┬────────┘
         │ GET /
         ▼
┌─────────────────┐
│ HomeController  │
│    index()      │
└────────┬────────┘
         │
         ├──► EventRepository::findUpcomingEventsForHome(6)
         │
         ├──► EventRepository::findHeadlineStats()
         │
         └──► WishlistRepository::findUserFavoriteEventIds() (si connecté)
         │
         ▼
┌─────────────────┐
│  home/index.    │
│  html.twig      │
└─────────────────┘
```

---

## 📋 Scénario d'utilisation

### Scénario 1 : Visiteur non connecté

1. **Andry** arrive sur la page d'accueil d'Aiolia-event
2. Il découvre une bannière attractive avec le logo et le slogan
3. Il voit les 6 prochains événements avec leurs visuels
4. Les statistiques l'impressionnent : "Plus de 10 000 billets vendus !"
5. Il clique sur un événement qui l'intéresse
6. Il est redirigé vers la page de connexion pour voir les détails

### Scénario 2 : Utilisateur connecté

1. **Miora** se connecte à son compte
2. Elle est redirigée vers la page d'accueil
3. Un message de bienvenue s'affiche : "Bienvenue, Miora !"
4. Elle voit les événements avec des cœurs ❤️ sur ses favoris
5. Elle peut ajouter/retirer des favoris directement depuis l'accueil
6. Elle clique sur "Voir tous les événements" pour explorer davantage

---

## 🛠️ Points techniques

### Gestion de la session

```php
$session = $request->getSession();
if (!$session->isStarted()) {
    $session->start();
}

$sessionUser = $session->get('user');
$isAuthenticated = is_array($sessionUser) && isset($sessionUser['id']);
```

### Flag de connexion récente

```php
// Vérifier si l'utilisateur vient de se connecter
$justLoggedIn = $session->has('just_logged_in') && $session->get('just_logged_in');

// Retirer le flag après l'avoir lu
if ($justLoggedIn) {
    $session->remove('just_logged_in');
}
```

### Ajout du statut favori aux événements

```php
foreach ($events as &$event) {
    $event['isFavorite'] = in_array($event['id'], $favoriteEventIds, true);
}
```

---

## 🎨 Éléments d'interface

| Élément | Description |
|---------|-------------|
| Hero Banner | Grande bannière avec recherche rapide |
| Event Cards | Cartes avec image, titre, date, lieu, prix |
| Stats Section | Compteurs animés des statistiques |
| CTA Buttons | "Voir les détails", "Acheter un billet" |
| Heart Icon | Indicateur de favori (cœur plein/vide) |

---

## 📱 Routes

| Route | Méthode | Description |
|-------|---------|-------------|
| `/` | GET | Page d'accueil principale |

---

## 🔗 Dépendances

- **EventRepository** : Récupération des événements
- **WishlistRepository** : Gestion des favoris utilisateur
- **Session Symfony** : Gestion de l'état de connexion

