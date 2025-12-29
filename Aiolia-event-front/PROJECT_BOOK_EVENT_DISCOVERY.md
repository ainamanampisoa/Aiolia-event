# 📖 Livre du Projet : Découverte et Recherche d'Événements

Une fois connecté, l'utilisateur découvre l'essence même de la plateforme : les événements. Ce module est le cœur battant d'Aiolia-event, où chaque visiteur peut explorer, filtrer et découvrir les expériences qui l'attendent. La recherche d'événements a été pensée pour être à la fois intuitive et puissante, permettant à chacun de trouver exactement ce qu'il cherche, que ce soit un concert de jazz, un festival de danse ou une conférence technologique.

---

## 🔍 a. Parcourir les événements

La page de découverte des événements est le point de départ de toute aventure sur Aiolia-event. Dès l'arrivée sur cette page, l'utilisateur est accueilli par une galerie riche et dynamique présentant tous les événements disponibles, organisés par catégories pour faciliter la navigation.

### L'expérience utilisateur
L'interface de découverte a été conçue pour être visuellement attrayante et informative. Chaque événement est présenté sous forme de carte élégante affichant :
- **L'image de l'événement** : Une photographie évocatrice qui capture l'essence de l'expérience
- **Le titre et la description** : Des informations claires qui donnent envie d'en savoir plus
- **La date et l'heure** : Affichées de manière proéminente pour une planification immédiate
- **Le lieu** : La ville et l'adresse pour une localisation rapide
- **Le prix** : Une fourchette de prix visible pour une décision rapide
- **Les icônes d'accessibilité** : Des indicateurs visuels pour les personnes à besoins spécifiques

L'utilisateur peut parcourir librement ces événements, même sans être connecté, ce qui lui permet de prendre le temps de découvrir ce que la plateforme a à offrir avant de s'engager.

### Sous le capot 🛠️
Techniquement, la liste des événements est gérée par le EventController::listEvents(). 
1. **Récupération des événements** : La méthode EventRepository::findAllPublishedEvents() récupère tous les événements publiés et actifs, triés par défaut par date croissante.
2. **Groupement par catégorie** : Les événements sont automatiquement organisés par catégorie pour une navigation intuitive, permettant à l'utilisateur de se concentrer sur ses centres d'intérêt.
3. **Gestion des favoris** : Si l'utilisateur est connecté, le système vérifie automatiquement quels événements sont dans sa liste de favoris et affiche une icône cœur pour les événements sauvegardés.
4. **Performance** : Les requêtes sont optimisées pour charger rapidement les informations essentielles, garantissant une expérience fluide même avec un grand nombre d'événements.

---

## 🔎 b. Recherche et filtrage avancés

La recherche est l'outil qui transforme une simple liste d'événements en une expérience personnalisée. L'utilisateur peut affiner sa découverte selon ses préférences précises, que ce soit par mots-clés, catégorie, localisation, prix ou dates.

### L'expérience utilisateur
La barre de recherche et les filtres sont conçus pour être à la fois puissants et simples d'utilisation :

**Recherche textuelle** :
- L'utilisateur peut saisir des mots-clés dans un champ de recherche intuitif
- La recherche s'effectue sur le titre, la description et les tags de l'événement
- Les résultats sont affichés en temps réel, offrant un retour immédiat

**Filtres disponibles** :
- **Par catégorie** : Musique, Sport, Conférence, Festival, etc.
- **Par ville** : Un menu déroulant liste toutes les villes disponibles
- **Par prix** : Des curseurs permettent de définir une fourchette de prix minimale et maximale
- **Par dates** : Des sélecteurs de date permettent de filtrer les événements entre deux dates précises

**Tri des résultats** :
- Par date (croissant ou décroissant)
- Par prix (du moins cher au plus cher, ou inversement)
- Par popularité (basé sur le nombre de réservations)

**Historique de recherche** : Pour les utilisateurs connectés, chaque recherche textuelle est automatiquement sauvegardée dans leur historique, leur permettant de retrouver facilement leurs recherches précédentes.

### Les coulisses techniques ⚙️
Le système de recherche repose sur EventRepository::searchEventsWithFilters(), une méthode sophistiquée qui combine plusieurs critères :
- **Recherche textuelle** : Utilise des requêtes SQL avec ILIKE pour une recherche insensible à la casse sur plusieurs champs
- **Filtres combinés** : Tous les filtres peuvent être utilisés simultanément, créant une recherche très précise
- **Optimisation** : Les requêtes sont construites dynamiquement pour n'interroger que les champs nécessaires, garantissant des performances optimales
- **Sauvegarde de l'historique** : Pour les utilisateurs connectés, SearchHistoryRepository::saveSearch() enregistre chaque recherche avec ses filtres associés, permettant un suivi personnalisé

---

## 📄 c. Consultation des détails d'un événement

Une fois qu'un événement a attiré l'attention de l'utilisateur, la page de détails lui offre une vue complète et immersive de l'expérience qui l'attend. Cette page est le point de décision où l'utilisateur confirme son intérêt et passe à l'action.

### L'expérience utilisateur
La page de détails d'un événement est une vitrine complète qui présente :

**Informations principales** :
- **Galerie d'images** : Plusieurs photographies de l'événement pour une immersion visuelle
- **Description détaillée** : Un texte riche qui raconte l'histoire de l'événement, son programme et ses points forts
- **Informations pratiques** : Date, heure de début et de fin, adresse complète avec possibilité d'ouvrir dans Google Maps
- **Accessibilité** : Des icônes claires indiquant les types d'accessibilité disponibles (accès fauteuil roulant, audio-description, etc.)

**Types de billets disponibles** :
- Chaque type de billet est présenté avec son prix, sa disponibilité et sa description
- Distinction claire entre billets adultes et enfants si applicable
- Affichage des promotions actives pour chaque type de billet
- Indication en temps réel du nombre de places restantes

**Fonctionnalités interactives** :
- **Bouton "Ajouter aux favoris"** : Permet de sauvegarder l'événement pour le retrouver plus tard
- **Bouton "Réserver"** : Redirige vers la sélection des billets et le processus d'achat
- **Événements similaires** : Une section suggère d'autres événements de la même catégorie pour encourager la découverte

**Sécurité** : La consultation des détails nécessite une connexion, garantissant que seuls les membres authentifiés peuvent accéder aux informations complètes et procéder à une réservation.

### Sous le capot 🛠️
La page de détails est gérée par EventController::showEvent() qui orchestre plusieurs opérations :
1. **Récupération des données** : EventRepository::findEventDetailsById() charge toutes les informations de l'événement, y compris les types de billets, les tags et les informations d'accessibilité
2. **Gestion des types de billets** : Les billets sont groupés par nom (VIP, Gold, Silver, etc.) et distingués par catégorie d'âge (adulte/enfant/tous) pour une présentation claire
3. **Calcul des prix** : Le système calcule automatiquement les prix minimum et maximum pour afficher une fourchette de prix
4. **Événements similaires** : EventRepository::findSimilarEvents() suggère des événements de la même catégorie pour enrichir l'expérience de découverte
5. **Accessibilité** : EventRepository::findEventAccessibility() récupère les types d'accessibilité liés à l'événement pour afficher les icônes appropriées

---

## ❤️ d. Gestion des favoris

Les favoris permettent à l'utilisateur de créer sa propre collection d'événements qui l'intéressent, sans avoir à les réserver immédiatement. C'est un outil de curation personnelle qui enrichit l'expérience utilisateur.

### L'expérience utilisateur
L'ajout aux favoris est une action simple et intuitive :
- **Icône cœur** : Sur chaque carte d'événement et sur la page de détails, une icône cœur permet d'ajouter ou de retirer l'événement des favoris
- **Feedback visuel** : L'icône change d'état immédiatement pour confirmer l'action (cœur plein = favori, cœur vide = non favori)
- **Page dédiée** : Les utilisateurs peuvent consulter tous leurs favoris dans une page dédiée accessible depuis leur profil
- **Persistance** : Les favoris sont sauvegardés et persistent entre les sessions, permettant à l'utilisateur de retrouver ses événements préférés à tout moment

### Les coulisses techniques ⚙️
La gestion des favoris utilise le WishlistRepository :
- **Ajout** : EventController::addToFavorites() crée une entrée dans la table wishlist liant l'utilisateur à l'événement
- **Suppression** : EventController::removeFromFavorites() retire l'association
- **Vérification** : Le système vérifie automatiquement quels événements sont dans les favoris de l'utilisateur lors du chargement des listes pour afficher l'état correct de l'icône cœur
- **Sécurité** : Seuls les utilisateurs authentifiés peuvent gérer leurs favoris, garantissant la confidentialité de leurs préférences

---

## 🎭 Scénario d'utilisation : Le parcours de Toavina

Pour illustrer la puissance de ce module, suivons **Toavina**, un étudiant passionné de musique qui souhaite découvrir les prochains concerts à Antananarivo.

### 1. La découverte libre
Toavina arrive sur la page des événements sans être connecté. Il parcourt librement les différentes catégories, découvrant des concerts, des festivals et des spectacles. Il remarque un concert de jazz qui l'intéresse particulièrement.

### 2. La recherche ciblée
Voulant explorer davantage, Toavina utilise la barre de recherche pour chercher "jazz". Il applique ensuite le filtre "Antananarivo" pour ne voir que les événements dans sa ville. Les résultats s'affichent instantanément, lui montrant plusieurs concerts de jazz à venir.

### 3. L'exploration détaillée
Intrigué par un concert en particulier, Toavina se connecte à son compte pour consulter les détails. La page de détails lui révèle :
- Une galerie d'images du lieu et des artistes précédents
- Une description passionnante du concert avec la liste des musiciens
- Les types de billets disponibles avec leurs prix (VIP à 50 000 MGA, Standard à 25 000 MGA)
- Les informations d'accessibilité (accès fauteuil roulant disponible)
- Des événements similaires qui pourraient aussi l'intéresser

### 4. La sauvegarde pour plus tard
Toavina n'est pas encore sûr de pouvoir y assister, mais il ne veut pas perdre cet événement. Il clique sur l'icône cœur pour l'ajouter à ses favoris. L'icône se remplit immédiatement, confirmant que l'événement est sauvegardé.

### 5. Le retour et la décision
Quelques jours plus tard, Toavina revient sur la plateforme. Il consulte sa page de favoris et retrouve le concert de jazz. Cette fois, il est prêt à réserver. Il clique sur "Réserver" et est redirigé vers la sélection des billets.

---

> [!TIP]
> **Le saviez-vous ?**
> Les recherches des utilisateurs connectés sont automatiquement sauvegardées dans leur historique. Cela leur permet de retrouver facilement des événements qu'ils ont consultés précédemment, même s'ils ne se souviennent plus exactement du nom.

