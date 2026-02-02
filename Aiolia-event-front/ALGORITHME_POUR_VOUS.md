# 🧠 Algorithme de Recommandation "Pour Vous"

## 📋 Vue d'ensemble

L'algorithme de recommandation **"Pour vous"** permet de suggérer des événements personnalisés à chaque utilisateur en se basant sur ses préférences historiques (favoris et achats passés). Il utilise un système de **filtrage collaboratif basé sur les catégories**.

---

## 🎯 Objectif

Suggérer des événements pertinents et nouveaux pour l'utilisateur en se basant sur :
- Ses événements favoris (wishlist)
- Ses achats passés (commandes payées)
- L'exclusion des événements déjà connus (favoris ou achetés)

---

## 🔄 Flux de l'algorithme

### Étape 1 : Récupération des catégories d'intérêt

L'algorithme commence par identifier les **catégories d'événements** qui intéressent l'utilisateur.

#### Source 1 : Catégories des favoris (Wishlist)
```sql
SELECT DISTINCT cl.category_id
FROM aiolia.wishlist_items wi
JOIN aiolia.wishlists w ON w.id = wi.wishlist_id
JOIN aiolia.event_category_links cl ON cl.event_id = wi.event_id
WHERE w.user_id = :userId
```

**Logique** :
- Récupère tous les événements dans la wishlist de l'utilisateur
- Extrait les catégories associées à ces événements
- Utilise `DISTINCT` pour éviter les doublons

#### Source 2 : Catégories des achats passés
```sql
SELECT DISTINCT cl.category_id
FROM aiolia.order_items oi
JOIN aiolia.orders o ON o.id = oi.order_id
JOIN aiolia.ticket_types tt ON tt.id = oi.ticket_type_id
JOIN aiolia.event_category_links cl ON cl.event_id = tt.event_id
WHERE o.user_id = :userId AND o.status = 'paid'
```

**Logique** :
- Récupère tous les tickets achetés par l'utilisateur
- Ne considère que les commandes **payées** (`status = 'paid'`)
- Extrait les catégories des événements achetés
- Utilise `DISTINCT` pour éviter les doublons

#### Union des deux sources
Les deux requêtes sont combinées avec un `UNION` pour obtenir une liste unique de catégories d'intérêt.

**Exemple** :
- Utilisateur a mis en favoris : 3 événements "Musique", 2 événements "Sport"
- Utilisateur a acheté : 1 événement "Musique", 1 événement "Culture"
- **Résultat** : Catégories d'intérêt = [Musique, Sport, Culture]

---

### Étape 2 : Gestion du cas "nouvel utilisateur"

Si l'utilisateur n'a **aucune catégorie d'intérêt** (pas de favoris, pas d'achats), l'algorithme utilise un **fallback**.

#### Fallback : Événements populaires/récents
```php
if (empty($categoryIds)) {
    $fallback = $this->findUpcomingEventsForHome($limit);
    return [
        'events' => $fallback,
        'is_fallback' => true,
        'category_ids' => []
    ];
}
```

**Logique du fallback** :
- Récupère les événements à venir les plus récents
- Aucune personnalisation (affichage générique)
- Permet de découvrir la plateforme même sans historique

---

### Étape 3 : Recherche d'événements recommandés

Une fois les catégories d'intérêt identifiées, l'algorithme recherche des événements correspondants.

#### Critères de sélection

**1. Catégories correspondantes**
```sql
JOIN aiolia.event_category_links cl2 ON cl2.event_id = e.id
WHERE cl2.category_id IN ($placeholdersStr)
```
- L'événement doit appartenir à **au moins une** des catégories d'intérêt

**2. Événements publiés et publics**
```sql
WHERE e.status = 'published'
  AND e.visibility = 'public'
```
- Seuls les événements publiés et visibles publiquement sont considérés

**3. Événements à venir**
```sql
AND e.starts_at >= NOW()
```
- Seuls les événements futurs sont recommandés (pas d'événements passés)

**4. Exclusion des favoris**
```sql
AND e.id NOT IN (
    SELECT event_id FROM aiolia.wishlist_items wi 
    JOIN aiolia.wishlists w ON w.id = wi.wishlist_id 
    WHERE w.user_id = :userId
)
```
- **Ne pas recommander** les événements déjà en favoris (l'utilisateur les connaît déjà)

**5. Exclusion des achats passés**
```sql
AND e.id NOT IN (
    SELECT tt.event_id FROM aiolia.order_items oi
    JOIN aiolia.orders o ON o.id = oi.order_id
    JOIN aiolia.ticket_types tt ON tt.id = oi.ticket_type_id
    WHERE o.user_id = :userId AND o.status = 'paid'
)
```
- **Ne pas recommander** les événements déjà achetés (l'utilisateur y a déjà participé)

#### Tri des résultats
```sql
ORDER BY e.starts_at ASC
LIMIT :limit
```
- Tri par **date de début** (événements les plus proches en premier)
- Limite par défaut : **12 événements**

---

### Étape 4 : Gestion du cas "aucun résultat"

Si aucune recommandation personnalisée n'est trouvée (ex: toutes les catégories d'intérêt n'ont pas d'événements futurs), l'algorithme utilise à nouveau le **fallback**.

```php
if (empty($rows)) {
    $fallback = $this->findUpcomingEventsForHome($limit);
    return [
        'events' => $fallback,
        'is_fallback' => true,
        'category_ids' => $categoryIds,
        'note' => 'Personalized categories returned no future events'
    ];
}
```

---

## 📊 Données retournées

### Structure de réponse

```php
[
    'events' => [
        [
            'id' => 123,
            'slug' => 'concert-rock-2025',
            'title' => 'Concert Rock 2025',
            'subtitle' => 'Le plus grand concert de l\'année',
            'summary' => 'Description de l\'événement...',
            'venue_name' => 'Stade de Mahamasina',
            'city' => 'Antananarivo',
            'category_label' => 'Musique',
            'image_url' => 'https://...',
            'starts_at' => DateTimeImmutable,
            'min_price' => 5000.0,
            'max_price' => 50000.0
        ],
        // ... autres événements
    ],
    'is_fallback' => false,  // true si fallback utilisé
    'category_ids' => [1, 3, 5]  // IDs des catégories utilisées
]
```

### Informations incluses

Chaque événement retourné contient :
- **Informations de base** : ID, slug, titre, sous-titre, résumé
- **Localisation** : Nom du lieu, ville
- **Catégorie** : Label de la catégorie principale
- **Image** : URL de l'image de couverture
- **Dates** : Date de début (DateTimeImmutable)
- **Prix** : Prix minimum et maximum des tickets

---

## 🎨 Affichage dans l'interface

### Route
- **URL** : `/events/for-you`
- **Nom de route** : `events_for_you`
- **Controller** : `EventController::forYou()`

### Template
- **Fichier** : `templates/event/for_you.html.twig`
- **Groupement** : Les événements sont groupés par catégorie pour l'affichage

### Indicateur de fallback
Le template peut afficher un message différent si `is_fallback = true` pour indiquer que les recommandations ne sont pas personnalisées.

---

## 🔍 Exemple concret

### Scénario utilisateur

**Profil utilisateur** :
- **Favoris** :
  - Événement A : Catégorie "Musique"
  - Événement B : Catégorie "Musique"
  - Événement C : Catégorie "Sport"
- **Achats passés** :
  - Événement D : Catégorie "Musique" (déjà acheté)
  - Événement E : Catégorie "Culture" (déjà acheté)

### Exécution de l'algorithme

**Étape 1** : Catégories d'intérêt
- Favoris → [Musique, Sport]
- Achats → [Musique, Culture]
- **Union** → [Musique, Sport, Culture]

**Étape 2** : Recherche d'événements
- Cherche événements futurs dans [Musique, Sport, Culture]
- Exclut : Événement A, B, C (favoris), D, E (achetés)
- Résultat : Événements F, G, H, I... (nouveaux événements dans ces catégories)

**Étape 3** : Tri et limite
- Tri par date (plus proche en premier)
- Limite à 12 événements

**Résultat final** : 12 événements recommandés dans les catégories Musique, Sport, Culture, que l'utilisateur n'a pas encore vus ou achetés.

---

## ⚡ Optimisations et performances

### Requêtes SQL optimisées

1. **UNION au lieu de plusieurs requêtes** : Combine les sources de catégories en une seule requête
2. **DISTINCT** : Évite les doublons de catégories
3. **NOT IN avec sous-requêtes** : Exclusion efficace des événements déjà connus
4. **LIMIT** : Limite le nombre de résultats pour la performance
5. **Index suggérés** :
   - `wishlist_items(event_id)`
   - `wishlists(user_id)`
   - `orders(user_id, status)`
   - `order_items(ticket_type_id)`
   - `event_category_links(event_id, category_id)`
   - `events(status, visibility, starts_at)`

### Gestion des erreurs

```php
try {
    $rows = $this->connection->executeQuery($sql, $params, $types)->fetchAllAssociative();
    // ... traitement
} catch (\Exception $e) {
    error_log('Error fetching recommendations for user: ' . $e->getMessage());
    return [
        'events' => [],
        'is_fallback' => true,
        'error' => $e->getMessage()
    ];
}
```

En cas d'erreur, l'algorithme retourne un tableau vide avec `is_fallback = true` pour éviter de casser l'interface.

---

## 🔄 Évolutions possibles

### Améliorations suggérées

1. **Pondération des catégories**
   - Donner plus de poids aux catégories des achats (engagement plus fort)
   - Réduire le poids des catégories uniquement en favoris

2. **Score de pertinence**
   - Calculer un score basé sur :
     - Nombre d'événements dans la catégorie (favoris + achats)
     - Date récente des interactions
     - Fréquence des interactions

3. **Filtrage par localisation**
   - Prioriser les événements dans la même ville/région
   - Utiliser la géolocalisation si disponible

4. **Machine Learning**
   - Analyser les patterns d'achat
   - Recommandations basées sur des utilisateurs similaires (collaborative filtering avancé)

5. **Cache des recommandations**
   - Mettre en cache les recommandations par utilisateur
   - Invalider le cache lors d'un nouvel achat ou ajout en favoris

6. **Diversité des recommandations**
   - S'assurer qu'il y a une variété de catégories dans les résultats
   - Éviter de recommander uniquement une seule catégorie

---

## 📝 Code source

### Méthode principale
- **Fichier** : `src/Repository/EventRepository.php`
- **Méthode** : `findRecommendationsForUserDetailed(int $userId, int $limit = 12)`
- **Lignes** : 1366-1522

### Controller
- **Fichier** : `src/Controller/EventController.php`
- **Méthode** : `forYou(Request $request)`
- **Lignes** : 157-193

### Méthode simplifiée
- **Méthode** : `findRecommendationsForUser(int $userId, int $limit = 12)`
- **Retourne** : Uniquement le tableau d'événements (sans métadonnées)

---

## 🎯 Points clés à retenir

1. **Basé sur les catégories** : L'algorithme utilise les catégories comme proxy des préférences
2. **Exclusion intelligente** : Ne recommande pas ce que l'utilisateur connaît déjà
3. **Fallback robuste** : Fonctionne même pour les nouveaux utilisateurs
4. **Performance** : Requêtes SQL optimisées avec index
5. **Évolutif** : Architecture permettant d'ajouter facilement des critères

---

## 📊 Schéma de l'algorithme

```
┌─────────────────────────────────────┐
│  Utilisateur                        │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│  Étape 1 : Récupération catégories │
│  - Favoris (wishlist)               │
│  - Achats passés (orders)           │
└──────────────┬──────────────────────┘
               │
               ▼
        ┌──────┴──────┐
        │ Catégories │
        │ trouvées ? │
        └──────┬──────┘
               │
        ┌──────┴──────┐
        │             │
       OUI           NON
        │             │
        ▼             ▼
┌──────────────┐  ┌──────────────────┐
│ Étape 2 :    │  │ Fallback :       │
│ Recherche    │  │ Événements       │
│ événements   │  │ populaires       │
│ dans         │  │                  │
│ catégories   │  └──────────────────┘
└──────┬───────┘
       │
       ▼
┌──────────────────────┐
│ Exclusion :          │
│ - Déjà en favoris   │
│ - Déjà achetés      │
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│ Tri par date         │
│ (plus proche)        │
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│ Limite : 12          │
│ événements           │
└──────┬───────────────┘
       │
       ▼
┌──────────────────────┐
│ Résultat final       │
└──────────────────────┘
```

---

**Date de création** : Décembre 2025  
**Version** : 1.0  
**Auteur** : Équipe de développement Aiolia Event
