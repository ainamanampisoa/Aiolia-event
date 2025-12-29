# 📖 Livre du Projet : Gestion du Profil Utilisateur

Le profil utilisateur est l'espace personnel où chaque membre de la communauté Aiolia-event peut gérer ses informations, suivre son activité et personnaliser son expérience. C'est bien plus qu'un simple tableau de bord : c'est le centre de contrôle qui permet à l'utilisateur de prendre le contrôle de sa relation avec la plateforme.

---

## 👤 a. Vue d'ensemble du profil

La page de profil est le point d'entrée vers toutes les fonctionnalités personnelles. Elle offre une vue complète de l'activité de l'utilisateur et un accès rapide aux différentes sections.

### L'expérience utilisateur
Dès l'accès au profil, l'utilisateur découvre :
- **Informations personnelles** : Photo de profil, nom, email, téléphone
- **Statistiques rapides** : Nombre de billets achetés, événements favoris, montant total dépensé
- **Navigation intuitive** : Des onglets ou un menu latéral permettent d'accéder rapidement aux différentes sections :
  - Historique d'achat
  - Portefeuille
  - Favoris
  - Calendrier
  - Paramètres
  - Statistiques

### Sous le capot 🛠️
Le profil est géré par ProfileController::index() qui agrège les données de plusieurs sources :
- Informations utilisateur depuis la table users
- Statistiques calculées depuis les tables orders, tickets, wishlist
- Activité récente depuis user_activity

---

## 📜 b. Historique d'achat

L'historique d'achat est la mémoire de toutes les transactions de l'utilisateur. Il permet de retrouver rapidement une commande passée, de consulter les détails d'un achat ou de télécharger une facture.

### L'expérience utilisateur
L'historique d'achat présente les commandes de manière claire et organisée :

**Affichage des commandes** :
- Chaque commande est présentée sous forme de carte avec :
  - Le numéro de commande (format : CMD-YYYYMMDD-XXXXXX)
  - La date et l'heure de la commande
  - Le nom de l'événement avec son image
  - Le nombre de billets achetés
  - Le montant total payé
  - Le statut de la commande (payée, en attente, annulée, remboursée)
  - Le mode de paiement utilisé

**Fonctionnalités de recherche et filtrage** :
- **Recherche textuelle** : Permet de chercher par nom d'événement ou numéro de commande
- **Filtres par statut** : Afficher uniquement les commandes payées, en attente, annulées ou remboursées
- **Filtres par mode de paiement** : Filtrer par Mvola, wallet, etc.
- **Pagination** : Les commandes sont paginées pour une navigation fluide même avec un long historique

**Actions disponibles** :
- **Télécharger la facture PDF** : Chaque commande peut générer une facture au format PDF
- **Supprimer de l'historique** : L'utilisateur peut retirer une commande de son historique (sans affecter les billets)
- **Voir les détails** : Accéder à une vue détaillée de la commande avec tous les billets associés

**Statistiques** :
- Un panneau de statistiques affiche :
  - Le nombre total de commandes
  - Le montant total dépensé
  - La moyenne par commande
  - Un graphique d'évolution des dépenses sur une période (6, 12 ou 24 mois)

### Les coulisses techniques ⚙️
L'historique est géré par ProfileController::history() :
1. **Récupération** : OrderRepository::findUserOrders() récupère les commandes avec pagination
2. **Filtrage** : Les filtres sont appliqués au niveau SQL pour optimiser les performances
3. **Calcul des statistiques** : calculatePurchaseStats() agrège les données pour les statistiques
4. **Génération PDF** : OrderRepository::generateInvoicePdf() crée les factures avec DomPDF
5. **Export** : Les données peuvent être exportées en CSV ou PDF pour archivage personnel

---

## 💰 c. Portefeuille électronique (Wallet)

Le portefeuille électronique est une fonctionnalité innovante qui permet aux utilisateurs de stocker de l'argent directement sur la plateforme, facilitant les achats futurs et offrant une expérience de paiement encore plus fluide.

### L'expérience utilisateur
Le portefeuille offre plusieurs fonctionnalités :

**Affichage du solde** :
- Le solde actuel est affiché de manière proéminente
- La devise (MGA) est clairement indiquée
- Un historique des transactions récentes est visible

**Recharge du portefeuille** :
- L'utilisateur peut recharger son portefeuille via Mvola
- Plusieurs montants prédéfinis sont proposés (10 000, 25 000, 50 000, 100 000 MGA)
- Un montant personnalisé peut également être saisi
- Le processus de recharge est similaire à un achat de billet, avec confirmation par Mvola

**Utilisation du portefeuille** :
- Lors du paiement, l'utilisateur peut choisir de payer avec son portefeuille si le solde est suffisant
- Le paiement est instantané, sans avoir besoin de saisir à nouveau les informations de paiement
- Si le solde est insuffisant, l'utilisateur peut compléter avec Mvola

**Transfert de fonds** :
- Les utilisateurs peuvent transférer de l'argent de leur portefeuille vers le portefeuille d'un autre utilisateur
- Il suffit de connaître l'email ou le numéro de téléphone du destinataire
- Les transferts sont instantanés et tracés dans l'historique

**Historique des transactions** :
- Toutes les transactions sont enregistrées : recharges, achats, transferts, remboursements
- Chaque transaction affiche : date, type, montant, statut, référence
- Des filtres permettent de voir uniquement les recharges, les dépenses, ou les transferts

### Les coulisses techniques ⚙️
Le portefeuille est géré par WalletService :
1. **Stockage** : Le solde est stocké dans la table wallets avec un lien vers l'utilisateur
2. **Transactions** : Chaque opération crée une entrée dans wallet_transactions pour traçabilité complète
3. **Sécurité** : Toutes les transactions sont validées et vérifiées pour éviter les fraudes
4. **Intégration Mvola** : Les recharges utilisent l'API Mvola de la même manière que les achats de billets
5. **Points de fidélité** : Le système de points de fidélité est lié au portefeuille, offrant des avantages supplémentaires

---

## ⭐ d. Favoris et liste de souhaits

Les favoris permettent à l'utilisateur de sauvegarder les événements qui l'intéressent pour les retrouver facilement plus tard, créant une expérience de découverte personnalisée.

### L'expérience utilisateur
**Gestion des favoris** :
- Sur chaque page d'événement, une icône cœur permet d'ajouter ou de retirer l'événement des favoris
- La page "Mes favoris" liste tous les événements sauvegardés
- Les événements sont organisés par date (prochains événements en premier)
- Chaque événement favori affiche ses informations essentielles et un bouton pour réserver

**Fonctionnalités** :
- **Filtrage** : Filtrer par catégorie ou date
- **Suppression** : Retirer un événement des favoris en un clic
- **Notification** : Si un événement favori approche, l'utilisateur peut recevoir une notification

### Sous le capot 🛠️
Les favoris sont gérés par WishlistRepository :
- Stockage dans la table wishlist liant user_id et event_id
- Synchronisation automatique avec l'affichage des événements pour montrer l'état "favori"

---

## 📅 e. Calendrier personnel

Le calendrier personnel offre une vue d'ensemble de tous les événements auxquels l'utilisateur participe, facilitant la planification et l'organisation.

### L'expérience utilisateur
**Vue calendrier** :
- Affichage mensuel avec les événements marqués
- Les événements avec billets sont mis en évidence
- Un clic sur un événement affiche les détails et les billets associés

**Fonctionnalités** :
- Navigation entre les mois
- Filtrage par statut (à venir, passés, annulés)
- Export vers Google Calendar ou iCal pour synchronisation avec d'autres calendriers

### Les coulisses techniques ⚙️
Le calendrier utilise TicketRepository::findUserTickets() pour récupérer les billets et les événements associés, puis les organise par date pour l'affichage calendrier.

---

## 📊 f. Statistiques et analyses

Les statistiques offrent une vue analytique de l'activité de l'utilisateur, lui permettant de comprendre ses habitudes de consommation et ses préférences.

### L'expérience utilisateur
**Tableau de bord statistiques** :
- **Graphique de dépenses** : Évolution des dépenses sur 6, 12 ou 24 mois
- **Répartition par catégorie** : Graphique en camembert montrant les catégories d'événements préférées
- **Événements les plus fréquentés** : Liste des événements avec le plus de billets achetés
- **Tendances** : Comparaison avec les périodes précédentes

**Export des données** :
- Les statistiques peuvent être exportées en PDF pour archivage
- Les données brutes peuvent être téléchargées en CSV pour analyse personnelle

### Sous le capot 🛠️
Les statistiques sont calculées par ProfileController::stats() :
- Agrégation des données depuis orders, tickets, events
- Calculs de moyennes, totaux, et tendances
- Génération de graphiques avec des bibliothèques JavaScript (Chart.js, etc.)

---

## ⚙️ g. Paramètres et préférences

Les paramètres permettent à l'utilisateur de personnaliser son expérience et de gérer ses informations personnelles.

### L'expérience utilisateur
**Informations personnelles** :
- Modification du nom, prénom, email, téléphone
- Upload d'une photo de profil
- Modification du mot de passe

**Préférences** :
- Langue de l'interface (français, malagasy, anglais)
- Notifications : choisir les types de notifications à recevoir (email, push, SMS)
- Confidentialité : gérer la visibilité du profil

**Sécurité** :
- Historique des connexions
- Gestion des sessions actives
- Authentification à deux facteurs (si activée)

### Les coulisses techniques ⚙️
Les paramètres sont gérés par ProfileController::settings() :
- Validation des données avant mise à jour
- Hachage sécurisé pour les mots de passe
- Upload sécurisé des images de profil (validation, redimensionnement)
- Enregistrement des préférences dans la table user_preferences

---

## 🎭 Scénario d'utilisation : Le parcours de Mamy

Suivons **Mamy**, une passionnée de culture qui utilise régulièrement Aiolia-event pour découvrir de nouveaux événements.

### 1. Consultation de l'historique
Mamy veut retrouver la facture d'un concert auquel elle a assisté il y a trois mois. Elle accède à son profil, puis à "Historique d'achat". Elle utilise la recherche pour trouver "jazz" et trouve rapidement la commande. Elle clique sur l'icône PDF pour télécharger la facture.

### 2. Recharge du portefeuille
Mamy prévoit d'acheter plusieurs billets ce mois-ci. Pour faciliter les paiements, elle décide de recharger son portefeuille avec 100 000 MGA. Elle va dans "Portefeuille", sélectionne le montant, et confirme via Mvola. Quelques secondes plus tard, son solde est mis à jour.

### 3. Utilisation du portefeuille
Lors de son prochain achat, Mamy choisit de payer avec son portefeuille. Le paiement est instantané, sans avoir à saisir à nouveau ses informations. Elle apprécie la simplicité.

### 4. Consultation des statistiques
Curieuse de connaître ses habitudes, Mamy consulte ses statistiques. Elle découvre qu'elle a dépensé 450 000 MGA cette année, principalement sur des événements musicaux (60%) et des conférences (30%). Le graphique montre une augmentation de ses dépenses au cours des derniers mois.

### 5. Gestion des favoris
Mamy a ajouté plusieurs événements à ses favoris. Elle consulte régulièrement sa liste pour voir les nouveaux événements qui s'ajoutent et décider lesquels réserver.

---

> [!TIP]
> **Le saviez-vous ?**
> Votre portefeuille peut recevoir des remboursements automatiques si un événement est annulé. Les fonds sont crédités instantanément et vous pouvez les utiliser pour un autre achat ou les retirer.




