# 🎪 Module 03 : Événements

## Description

Le module Événements est le cœur de la plateforme Aiolia-event. Il permet aux utilisateurs de découvrir, rechercher, filtrer et consulter les détails des événements disponibles. Il inclut également la gestion des favoris et l'historique de recherche.

---

## 📂 Fichiers concernés

| Type | Fichier |
|------|---------|
| Contrôleur | `src/Controller/EventController.php` |
| Repository | `src/Repository/EventRepository.php` |
| Repository | `src/Repository/WishlistRepository.php` |
| Repository | `src/Repository/SearchHistoryRepository.php` |
| Service | `src/Service/ActivityService.php` |
| Templates | `templates/event/list.html.twig` |
| Templates | `templates/event/details.html.twig` |

---

## 🎯 Fonctionnalités

### 1. Liste des événements
- **Route** : `/events`
- **Affichage** : Événements groupés par catégorie
- **Pagination** : Chargement dynamique
- **Tri** : Par date, prix, popularité

### 2. Recherche avancée
- **Critères** :
  - Texte libre (titre, description)
  - Catégorie (Concert, Sport, Conférence...)
  - Ville/Région
  - Fourchette de prix
  - Plage de dates
- **Tri** : Date, prix croissant/décroissant

### 3. Détails d'un événement
- **Route** : `/events/{id}`
- **Informations** : Titre, description, lieu, date, horaires
- **Types de billets** : VIP, Standard, Enfant avec prix
- **Accessibilité** : Icônes d'accessibilité (PMR, malentendants...)
- **Événements similaires** : Suggestions basées sur la catégorie

### 4. Gestion des favoris
- **Ajouter** : POST `/events/{id}/favorite`
- **Retirer** : DELETE `/events/{id}/favorite`
- **Affichage** : Cœur plein/vide sur les cartes

### 5. Historique de recherche
- Sauvegarde automatique des recherches (utilisateurs connectés)
- Accessible dans le profil utilisateur

---

## 🔄 Flux de recherche

```
┌─────────────────┐
│   Formulaire    │
│   de recherche  │
└────────┬────────┘
         │ GET /events?q=concert&category=music
         ▼
┌─────────────────┐
│ EventController │
│  listEvents()   │
└────────┬────────┘
         │
         ├──► Extraction des paramètres (q, category, city, price...)
         ├──► EventRepository::searchEventsWithFilters()
         ├──► Sauvegarde historique (si connecté)
         ├──► Récupération favoris utilisateur
         │
         ▼
┌─────────────────┐
│  event/list.    │
│   html.twig     │
└─────────────────┘
```

---

## 🔄 Flux détails événement

```
┌─────────────────┐
│  Clic sur un    │
│   événement     │
└────────┬────────┘
         │ GET /events/42
         ▼
┌─────────────────┐
│ EventController │
│   showEvent()   │
└────────┬────────┘
         │
         ├──► Vérification authentification
         ├──► EventRepository::findEventDetailsById()
         ├──► EventRepository::findTicketTypesByEventId()
         ├──► EventRepository::findEventAccessibility()
         ├──► EventRepository::findEventTags()
         ├──► EventRepository::findSimilarEvents()
         │
         ▼
┌─────────────────┐
│ event/details.  │
│   html.twig     │
└─────────────────┘
```

---

## 📋 Scénarios d'utilisation

### Scénario 1 : Recherche d'un concert

1. **Faly** accède à la page des événements
2. Il tape "jazz" dans la barre de recherche
3. Il filtre par catégorie "Concert"
4. Il sélectionne la ville "Antananarivo"
5. Il définit un budget max de 100 000 MGA
6. Les résultats s'affichent, triés par date
7. Sa recherche est sauvegardée dans son historique

### Scénario 2 : Consultation des détails

1. **Lalao** clique sur "Festival Musique Antananarivo"
2. Elle voit la grande image de couverture
3. Elle lit la description complète de l'événement
4. Elle consulte les types de billets :
   - VIP : 150 000 MGA
   - Standard Adulte : 50 000 MGA
   - Standard Enfant : 25 000 MGA
5. Elle voit les icônes d'accessibilité (PMR disponible)
6. Elle parcourt les événements similaires en bas de page
7. Elle clique sur "Ajouter au panier"

### Scénario 3 : Gestion des favoris

1. **Rina** parcourt la liste des événements
2. Elle clique sur le cœur d'un événement qui lui plaît
3. Le cœur devient plein (rouge)
4. L'événement est ajouté à ses favoris
5. Elle peut retrouver tous ses favoris dans son profil
6. Elle peut cliquer à nouveau pour retirer des favoris

---

## 🛠️ Points techniques

### Paramètres de recherche

```php
$query = trim((string) $request->query->get('q', ''));
$category = trim((string) $request->query->get('category', ''));
$city = trim((string) $request->query->get('city', ''));
$priceMin = $request->query->get('price_min');
$priceMax = $request->query->get('price_max');
$dateFrom = trim((string) $request->query->get('date_from', ''));
$dateTo = trim((string) $request->query->get('date_to', ''));
$sortBy = $request->query->get('sort_by', 'date');
$sortOrder = $request->query->get('sort_order', 'asc');
```

### Groupement par catégorie

```php
private function groupEventsByCategory(array $events): array
{
    $grouped = [];
    foreach ($events as $event) {
        $label = $event['category_label'] ?? 'Autres';
        $grouped[$label][] = $event;
    }
    ksort($grouped, SORT_NATURAL | SORT_FLAG_CASE);
    return $grouped;
}
```

### Gestion des types de billets

```php
// Grouper les billets par nom (VIP, Gold, Silver...)
$groupedTicketTypes = $this->groupTicketTypesByName($ticketTypes);

// Détecter si l'événement a des billets adultes ET enfants
$hasAnyAdultTickets = false;
$hasAnyChildTickets = false;

foreach ($ticketTypes as $ticket) {
    $ageCategory = $ticket['age_category'] ?? 'all';
    if ($ageCategory === 'adult' || $ageCategory === 'all') {
        $hasAnyAdultTickets = true;
    }
    if ($ageCategory === 'child' || $ageCategory === 'all') {
        $hasAnyChildTickets = true;
    }
}
```

---

## 📱 Routes

| Route | Méthode | Description |
|-------|---------|-------------|
| `/events` | GET | Liste des événements avec filtres |
| `/events/{id}` | GET | Détails d'un événement |
| `/events/{id}/favorite` | POST | Ajouter aux favoris |
| `/events/{id}/favorite` | DELETE | Retirer des favoris |
| `/api/events` | GET | API liste des événements |
| `/api/events/{id}` | GET | API détails événement |
| `/api/events/search` | GET | API recherche avancée |
| `/api/events/categories` | GET | API liste des catégories |

---

## 🎨 Éléments d'interface

### Page liste

| Élément | Description |
|---------|-------------|
| Barre de recherche | Champ texte avec loupe |
| Filtres latéraux | Catégorie, ville, prix, date |
| Cartes événements | Image, titre, date, lieu, prix, cœur |
| Tri dropdown | Date, prix croissant, prix décroissant |
| Pagination | Boutons numérotés ou scroll infini |

### Page détails

| Élément | Description |
|---------|-------------|
| Image couverture | Grande image en haut |
| Informations | Titre, date, heure, lieu, organisateur |
| Description | Texte complet avec formatage |
| Types de billets | Tableau avec nom, prix, disponibilité |
| Sélecteur quantité | Input adultes + input enfants |
| Bouton panier | "Ajouter au panier" (CTA principal) |
| Accessibilité | Icônes PMR, malentendants, etc. |
| Tags | Badges catégorie, sous-catégorie |
| Événements similaires | Carousel ou grille |

---

## 🔗 Dépendances

- **EventRepository** : Requêtes BDD événements
- **WishlistRepository** : Gestion des favoris
- **SearchHistoryRepository** : Historique de recherche
- **ActivityService** : Log des activités utilisateur

