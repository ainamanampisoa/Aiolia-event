# 📖 Livre du Projet : Espace Organisateur

L'espace organisateur est dédié aux créateurs d'événements qui souhaitent promouvoir et gérer leurs événements sur Aiolia-event. C'est un outil complet qui permet de créer, modifier, suivre et analyser les performances de ses événements, offrant un contrôle total sur l'expérience proposée aux participants.

---

## 🎪 a. Vue d'ensemble de l'espace organisateur

L'espace organisateur est accessible aux utilisateurs ayant le rôle "organisateur" ou "co-organisateur". C'est le centre de commande pour tous les aspects de la gestion d'événements.

### L'expérience utilisateur
**Tableau de bord** :
- Vue d'ensemble des événements créés
- Statistiques clés : nombre total d'événements, billets vendus, revenus
- Événements à venir et passés
- Notifications importantes (nouveaux achats, questions participants, etc.)

**Navigation** :
- **Mes événements** : Liste de tous les événements créés
- **Créer un événement** : Formulaire de création
- **Statistiques** : Analyses détaillées des performances
- **Rapports** : Export de données pour analyse externe

### Sous le capot 🛠️
L'espace organisateur est géré par OrganizerController qui :
- Vérifie les permissions (rôle organisateur)
- Récupère les événements de l'organisateur connecté
- Calcule les statistiques depuis les tables events, orders, tickets

---

## ➕ b. Création d'un événement

La création d'événement est le point de départ pour tout organisateur souhaitant promouvoir son événement sur la plateforme.

### L'expérience utilisateur
**Formulaire de création** :
Le formulaire guide l'organisateur étape par étape :

**Informations de base** :
- Titre de l'événement
- Description détaillée (éditeur de texte riche)
- Catégorie (Musique, Sport, Conférence, etc.)
- Tags pour améliorer la découvrabilité

**Dates et lieu** :
- Date et heure de début
- Date et heure de fin (si applicable)
- Lieu : ville, adresse complète
- Carte interactive pour sélectionner l'emplacement précis

**Visuels** :
- Image principale (upload avec prévisualisation)
- Galerie d'images supplémentaires
- Vidéo de présentation (optionnelle, lien YouTube/Vimeo)

**Types de billets** :
- Ajouter plusieurs types de billets (VIP, Standard, Early Bird, etc.)
- Pour chaque type :
  - Nom du billet
  - Prix (adulte et enfant si applicable)
  - Nombre de billets disponibles
  - Description du billet
  - Date de début et fin de vente (pour les promotions)

**Accessibilité** :
- Sélectionner les types d'accessibilité disponibles (fauteuil roulant, audio-description, etc.)

**Paramètres avancés** :
- Statut de publication (brouillon, publié)
- Limite d'âge (si applicable)
- Conditions d'annulation
- Informations de contact

**Validation** :
- Le système valide toutes les informations
- Des suggestions sont proposées pour améliorer la visibilité
- L'événement peut être sauvegardé en brouillon ou publié immédiatement

### Les coulisses techniques ⚙️
La création est gérée par OrganizerController::createEvent() :
1. **Validation** : Toutes les données sont validées (dates cohérentes, prix positifs, etc.)
2. **Upload d'images** : Les images sont uploadées et optimisées (redimensionnement, compression)
3. **Création** : L'événement est créé dans events avec le statut "draft" ou "published"
4. **Types de billets** : Les types de billets sont créés dans ticket_types liés à l'événement
5. **Accessibilité** : Les liens d'accessibilité sont créés dans event_accessibility_links
6. **Notification** : L'organisateur reçoit une confirmation de création

---

## ✏️ c. Modification d'un événement

Les organisateurs peuvent modifier leurs événements à tout moment, avec des restrictions selon l'état de l'événement (billets vendus, date passée, etc.).

### L'expérience utilisateur
**Modifications possibles** :
- **Avant publication** : Toutes les modifications sont possibles sans restriction
- **Après publication (sans billets vendus)** : La plupart des modifications sont possibles
- **Avec billets vendus** : Certaines modifications sont restreintes :
  - La date ne peut pas être modifiée (sauf cas exceptionnel avec validation admin)
  - Le lieu peut être modifié avec notification aux acheteurs
  - Les prix des billets existants ne peuvent pas être modifiés (nouveaux types possibles)
  - Les types de billets vendus ne peuvent pas être supprimés

**Processus** :
- L'organisateur accède à "Mes événements" et sélectionne l'événement à modifier
- Le formulaire de modification est pré-rempli avec les données actuelles
- Les modifications sont sauvegardées
- Si l'événement est publié et a des participants, une notification peut être envoyée

### Sous le capot 🛠️
La modification est gérée par OrganizerController::updateEvent() :
1. **Vérification des permissions** : L'organisateur doit être le propriétaire de l'événement
2. **Validation des modifications** : Le système vérifie si les modifications sont autorisées
3. **Mise à jour** : Les données sont mises à jour dans events
4. **Historique** : Les modifications importantes sont enregistrées pour traçabilité
5. **Notifications** : Si nécessaire, les participants sont notifiés des changements

---

## 📊 d. Statistiques et analyses

Les organisateurs ont accès à des statistiques détaillées pour comprendre la performance de leurs événements et optimiser leurs stratégies.

### L'expérience utilisateur
**Tableau de bord statistiques** :
- **Vue d'ensemble** :
  - Nombre total de billets vendus
  - Taux de remplissage (% de billets vendus)
  - Revenus totaux
  - Nombre de participants uniques

- **Évolution dans le temps** :
  - Graphique des ventes par jour/semaine
  - Pic de ventes (quand les billets se sont le mieux vendus)
  - Prévision de ventes (basée sur les tendances)

- **Répartition par type de billet** :
  - Nombre de billets vendus par type
  - Revenus par type de billet
  - Taux de remplissage par type

- **Démographie** :
  - Répartition géographique des acheteurs (par ville)
  - Tranches d'âge (si disponible)
  - Canaux d'acquisition (recherche, favoris, partage, etc.)

**Fonctionnalités avancées** :
- Comparaison entre événements
- Export des données (CSV, PDF)
- Rapports personnalisés

### Les coulisses techniques ⚙️
Les statistiques sont calculées par OrganizerController::getDashboard() :
1. **Agrégation** : Les données sont agrégées depuis orders, tickets, events
2. **Calculs** : Taux, moyennes, totaux sont calculés en temps réel
3. **Visualisation** : Les graphiques sont générés avec des bibliothèques JavaScript (Chart.js, etc.)
4. **Performance** : Les requêtes sont optimisées avec des index et des vues matérialisées si nécessaire

---

## 📈 e. Rapports et exports

Les organisateurs peuvent générer des rapports détaillés pour analyse externe ou archivage.

### L'expérience utilisateur
**Types de rapports** :
- **Rapport de ventes** : Liste complète des billets vendus avec détails (acheteur, type, prix, date)
- **Rapport financier** : Revenus, commissions, revenus nets
- **Rapport de participants** : Liste des participants avec leurs informations (pour contact post-événement, si autorisé)
- **Rapport de performance** : Analyse complète avec graphiques et recommandations

**Export** :
- Format CSV pour analyse dans Excel
- Format PDF pour présentation ou archivage
- Les rapports peuvent être générés pour un événement spécifique ou pour tous les événements

### Sous le capot 🛠️
Les rapports sont générés par OrganizerController::exportReport() :
1. **Récupération des données** : Toutes les données pertinentes sont récupérées
2. **Formatage** : Les données sont formatées selon le type de rapport
3. **Génération** : CSV ou PDF est généré avec les données formatées
4. **Téléchargement** : Le fichier est proposé en téléchargement à l'organisateur

---

## 🎫 f. Gestion des billets et promotions

Les organisateurs peuvent gérer les types de billets et créer des promotions pour stimuler les ventes.

### L'expérience utilisateur
**Gestion des types de billets** :
- Ajouter de nouveaux types de billets même après publication
- Modifier la disponibilité (augmenter ou diminuer le nombre)
- Désactiver temporairement un type de billet

**Promotions** :
- Créer des codes promotionnels (ex: "EARLYBIRD20" pour 20% de réduction)
- Définir les conditions (dates de validité, nombre d'utilisations, types de billets concernés)
- Suivre l'utilisation des promotions (nombre de codes utilisés, économies accordées)

### Les coulisses techniques ⚙️
La gestion des promotions est gérée par OrganizerController::createPromotion() :
1. **Création** : Le code promotionnel est créé dans promotions
2. **Validation** : Le système valide les codes lors de l'achat
3. **Application** : La réduction est appliquée au calcul du total
4. **Suivi** : L'utilisation est enregistrée pour statistiques

---

## 🎭 Scénario d'utilisation : L'événement de Rado

Suivons **Rado**, un organisateur qui crée son premier événement sur Aiolia-event.

### 1. Création de l'événement
Rado souhaite organiser un concert de musique traditionnelle. Il accède à l'espace organisateur et clique sur "Créer un événement". Il remplit le formulaire :
- Titre : "Concert de Vakoka Malagasy"
- Description détaillée avec le programme
- Date : Dans 2 mois
- Lieu : Salle de spectacle à Antananarivo
- Image principale : Photo du groupe
- Types de billets :
  - VIP : 50 000 MGA (100 places)
  - Standard : 25 000 MGA (300 places)

Il publie l'événement.

### 2. Suivi des ventes
Au fil des semaines, Rado consulte régulièrement ses statistiques :
- Semaine 1 : 50 billets vendus (12.5% de remplissage)
- Semaine 2 : 120 billets vendus (30%)
- Semaine 3 : 200 billets vendus (50%)

Il voit que les billets VIP se vendent mieux que prévu.

### 3. Création d'une promotion
Pour accélérer les ventes des billets Standard, Rado crée une promotion "EARLYBIRD" offrant 15% de réduction jusqu'à 3 semaines avant l'événement. Les ventes de billets Standard augmentent.

### 4. Le jour de l'événement
Rado utilise l'application de validation pour scanner les codes QR à l'entrée. Tous les billets sont validés sans problème. L'événement est un succès avec 350 participants sur 400 billets vendus.

### 5. Analyse post-événement
Après l'événement, Rado génère un rapport complet :
- 350 billets vendus au total
- Revenus : 8 750 000 MGA
- Taux de remplissage : 87.5%
- Participants principalement d'Antananarivo (80%)

Il utilise ces données pour planifier son prochain événement.

---

> [!TIP]
> **Le saviez-vous ?**
> Les événements avec des images de qualité et des descriptions détaillées ont un taux de conversion jusqu'à 3 fois supérieur. Prenez le temps de soigner la présentation de votre événement !




