# 🎤 Speech et Guide de Démonstration - Aiolia Event Front

## 📋 Table des matières
1. [Speech d'introduction](#speech-dintroduction)
2. [Étapes de démonstration](#étapes-de-démonstration)
3. [Points clés à mettre en avant](#points-clés-à-mettre-en-avant)
4. [Gestion des questions](#gestion-des-questions)

---

## 🎯 Speech d'introduction

### Introduction (30 secondes)

> **"Bonjour, je vais vous présenter Aiolia Event, une plateforme de vente de billets d'événements développée avec Symfony. Cette application permet aux utilisateurs de découvrir, acheter et gérer leurs billets pour des événements à Madagascar, avec un système de paiement intégré via MVola et un jeu de fidélisation innovant."**

### Architecture (1 minute)

> **"Notre application suit une architecture 3-tier :**
> - **Présentation** : Controllers et Templates Twig pour l'interface utilisateur
> - **Métier** : Services qui gèrent toute la logique métier (achats, paiements, statistiques)
> - **Persistance** : Repositories et Entités Doctrine pour l'accès aux données
>
> **Nous avons deux applications distinctes :**
> - **FrontOffice** (Aiolia-event-front) : Pour les utilisateurs finaux
> - **BackOffice** (Aiolia-event-back) : Pour l'administration
>
> **L'infrastructure utilise PostgreSQL comme base de données, Redis pour le cache, et s'intègre avec des services externes comme MVola pour les paiements et Cloudinary pour le stockage d'images."**

### Parcours utilisateur (30 secondes)

> **"Je vais maintenant vous montrer le parcours complet d'un utilisateur, depuis sa première visite sur la plateforme jusqu'à l'utilisation du jeu Ticket-Chance, en passant par l'inscription, la recherche d'événements, l'achat de billets et la consultation de ses statistiques personnelles."**

---

## 📝 Étapes de démonstration

### 🏠 ÉTAPE 1 : Page d'accueil (Mode déconnecté) - 2 minutes

#### Actions à effectuer :
1. **Ouvrir la page d'accueil** (`/`)
2. **Montrer les éléments visuels** :
   - Hero banner avec logo et slogan
   - Statistiques globales (nombre d'événements, billets vendus, utilisateurs)
   - Les 6 prochains événements avec images, dates, lieux, prix
   - Design responsive et moderne

#### Paroles à dire :
> **"Voici la page d'accueil d'Aiolia Event. Elle est accessible à tous, même sans compte. On peut voir les statistiques de la plateforme et les prochains événements. Les visiteurs peuvent explorer les événements, mais pour acheter, ils devront créer un compte."**

#### Points techniques à mentionner :
- ✅ Page accessible sans authentification
- ✅ Données chargées depuis PostgreSQL via EventRepository
- ✅ Statistiques calculées en temps réel
- ✅ Design responsive (mobile, tablette, desktop)

---

### 🔐 ÉTAPE 2 : Inscription - 2 minutes

#### Actions à effectuer :
1. **Cliquer sur "S'inscrire"** dans le menu
2. **Remplir le formulaire** :
   - Prénom : "Demo"
   - Nom : "User"
   - Email : "demo@aiolia.mg"
   - Téléphone : "+261 34 00 000 00" (avec drapeau 🇲🇬)
   - Mot de passe : "demo1234" (minimum 8 caractères)
3. **Valider le formulaire**
4. **Montrer le message de succès**
5. **Redirection vers la page de connexion** (email pré-rempli)

#### Paroles à dire :
> **"L'inscription est simple et rapide. Le formulaire valide l'unicité de l'email, le format du téléphone malgache, et la complexité du mot de passe. Une fois inscrit, l'utilisateur reçoit un email de bienvenue et peut se connecter immédiatement."**

#### Points techniques à mentionner :
- ✅ Validation côté serveur (AuthService)
- ✅ Hachage sécurisé du mot de passe (bcrypt)
- ✅ Email de bienvenue automatique (UserMailer)
- ✅ Normalisation du numéro de téléphone (+261)

---

### 🔑 ÉTAPE 3 : Connexion - 1 minute

#### Actions à effectuer :
1. **Saisir l'email** (déjà pré-rempli)
2. **Saisir le mot de passe**
3. **Cliquer sur "Se connecter"**
4. **Montrer la redirection vers l'accueil**
5. **Montrer le message de bienvenue** ("Bienvenue, Demo User !")

#### Paroles à dire :
> **"La connexion utilise un système hybride : sessions PHP pour la navigation web et tokens JWT pour les API. Une fois connecté, l'utilisateur est redirigé vers l'accueil avec un message de bienvenue personnalisé."**

#### Points techniques à mentionner :
- ✅ Authentification par session Symfony
- ✅ Génération de tokens JWT (access + refresh)
- ✅ Stockage du profil utilisateur en session
- ✅ Protection CSRF sur les formulaires

---

### 🎪 ÉTAPE 4 : Découverte des événements - 3 minutes

#### Actions à effectuer :
1. **Naviguer vers "Événements"** (`/events`)
2. **Montrer la liste des événements** groupés par catégorie
3. **Effectuer une recherche** :
   - Taper "concert" dans la barre de recherche
   - Filtrer par catégorie "Musique"
   - Sélectionner la ville "Antananarivo"
   - Définir un budget max de 100 000 MGA
4. **Montrer les résultats filtrés**
5. **Cliquer sur un événement** pour voir les détails

#### Paroles à dire :
> **"La recherche est puissante et permet de filtrer par texte, catégorie, ville, prix et dates. Les résultats sont triés par pertinence et date. Chaque recherche est sauvegardée dans l'historique de l'utilisateur pour faciliter les recherches futures."**

#### Points techniques à mentionner :
- ✅ Recherche full-text sur titre et description
- ✅ Filtres multiples combinables
- ✅ Tri dynamique (date, prix, popularité)
- ✅ Historique de recherche sauvegardé (SearchHistoryRepository)

---

### 📄 ÉTAPE 5 : Détails d'un événement - 2 minutes

#### Actions à effectuer :
1. **Afficher la page de détails** (`/events/{id}`)
2. **Montrer les informations** :
   - Grande image de couverture
   - Description complète
   - Date, heure, lieu
   - Organisateur
3. **Montrer les types de billets** :
   - VIP : 150 000 MGA
   - Standard Adulte : 50 000 MGA
   - Standard Enfant : 25 000 MGA
4. **Montrer les icônes d'accessibilité** (PMR, malentendants)
5. **Ajouter aux favoris** (cliquer sur le cœur ❤️)
6. **Sélectionner des quantités** (2 adultes, 1 enfant)
7. **Cliquer sur "Ajouter au panier"**

#### Paroles à dire :
> **"La page de détails affiche toutes les informations nécessaires : description, types de billets avec prix, accessibilité. L'utilisateur peut ajouter l'événement à ses favoris et sélectionner les quantités de billets avant d'ajouter au panier."**

#### Points techniques à mentionner :
- ✅ Gestion des favoris (WishlistRepository)
- ✅ Calcul automatique des prix (adultes + enfants)
- ✅ Validation des disponibilités en temps réel
- ✅ Événements similaires suggérés

---

### 🛒 ÉTAPE 6 : Panier et paiement - 4 minutes

#### Actions à effectuer :
1. **Afficher le panier** (`/cart`)
2. **Montrer le récapitulatif** :
   - Items avec images, quantités, prix unitaire
   - Sous-totaux par ligne
   - Total général
3. **Cliquer sur "Procéder au paiement"**
4. **Afficher la page de paiement** (`/checkout/payment`)
5. **Montrer le formulaire** :
   - Récapitulatif de la commande
   - Méthode de paiement : MVola
   - Nom, email, téléphone
   - Checkbox CGU
   - Timer de 15 minutes
6. **Remplir le formulaire** et cliquer sur "Payer"
7. **Expliquer le processus MVola** :
   - Initiation de la transaction
   - Notification push sur le téléphone
   - Confirmation par l'utilisateur
   - Callback automatique au serveur
8. **Afficher la page de confirmation** avec le numéro de commande

#### Paroles à dire :
> **"Le panier est synchronisé entre la session et la base de données, ce qui permet de le conserver même après déconnexion. Le paiement utilise MVola, le système de paiement mobile de Telma Madagascar. Une fois le paiement confirmé sur le téléphone, un callback automatique crée les billets et envoie un email de confirmation."**

#### Points techniques à mentionner :
- ✅ Synchronisation panier Session ↔ BDD (CartSyncService)
- ✅ Intégration API MVola (MvolaPaymentClient)
- ✅ Gestion des callbacks asynchrones
- ✅ Création automatique des billets après paiement
- ✅ Génération de QR codes uniques pour chaque billet

---

### 🎟️ ÉTAPE 7 : Mes billets - 2 minutes

#### Actions à effectuer :
1. **Naviguer vers "Mes billets"** (`/my-tickets`)
2. **Montrer la liste des billets** avec filtres (À venir, Passés, Annulés)
3. **Cliquer sur un billet** pour voir les détails
4. **Montrer les détails** :
   - Informations de l'événement
   - QR code unique
   - Date et lieu
5. **Télécharger le PDF** (`/my-tickets/{id}/pdf`)
6. **Montrer le PDF généré** avec QR code
7. **Ajouter au calendrier** (Google, Apple, Outlook)

#### Paroles à dire :
> **"Chaque billet acheté est accessible dans 'Mes billets' avec un QR code unique pour la validation à l'entrée. L'utilisateur peut télécharger un PDF professionnel et ajouter l'événement à son calendrier personnel."**

#### Points techniques à mentionner :
- ✅ Génération PDF avec Dompdf
- ✅ QR codes avec Endroid/QrCode
- ✅ Export calendrier (iCal format)
- ✅ Validation QR code à l'entrée

---

### 📊 ÉTAPE 8 : Statistiques personnelles - 3 minutes

#### Actions à effectuer :
1. **Naviguer vers "Statistiques"** (`/profile/stats`)
2. **Montrer les graphiques** :
   - **Budget par catégorie** : Graphique en donut (Chart.js) avec pourcentages
   - **Top 3 événements achetés** : Liste détaillée avec barres de progression
   - **Répartition par catégorie** : Graphique en barres avec budgets
3. **Expliquer les données** :
   - Total dépensé
   - Répartition par type d'événement
   - Événements favoris
4. **Naviguer vers "Historique financier"** (`/profile/financial-history`)
5. **Montrer le graphique mensuel** (Chart.js bar chart)
6. **Expliquer les fonctionnalités** :
   - Vue détaillée par mois
   - Export PDF disponible
   - Filtres par période

#### Paroles à dire :
> **"Les statistiques personnelles utilisent Chart.js pour des visualisations interactives. L'utilisateur peut voir sa répartition de dépenses par catégorie, ses événements favoris, et son historique financier mensuel. Toutes les données sont calculées en temps réel depuis la base de données."**

#### Points techniques à mentionner :
- ✅ Visualisations Chart.js (doughnut, bar charts)
- ✅ Calculs en temps réel (UserStatsRepository)
- ✅ Export PDF des statistiques
- ✅ Filtres par période personnalisables

---

### 🎰 ÉTAPE 9 : Ticket-Chance (Jeu de fidélisation) - 4 minutes

#### Actions à effectuer :
1. **Naviguer vers "Ticket Chance"** (`/profile/ticket-chance`)
2. **Vérifier l'éligibilité** :
   - Si l'utilisateur a dépensé < 100 000 MGA :
     - Montrer la barre de progression
     - Expliquer : "Débloquez le jeu en dépensant 100 000 MGA"
     - Montrer le pourcentage de progression
   - Si l'utilisateur a dépensé ≥ 100 000 MGA :
     - Montrer la roue de la fortune
     - Expliquer les règles
3. **Lancer la roue** (si éligible)
4. **Montrer l'animation** de rotation
5. **Afficher le résultat** :
   - Popup de félicitations
   - Prix gagné (ex: Réduction 10%)
   - Code promo généré (ex: CHANCE-ABC123)
6. **Expliquer les gains possibles** :
   - Réduction 10% : 30% de chance (le plus commun)
   - Réduction 5 000 MGA : 20% de chance
   - Réduction 20% : 15% de chance
   - Rejouez ! : 15% de chance
   - Réduction 10 000 MGA : 12% de chance
   - Réduction 50% : 3% de chance (rare)
   - Upgrade VIP : 3% de chance (rare)
   - Billet gratuit : 2% de chance (très rare)
7. **Montrer l'historique des gains**
8. **Expliquer les règles** :
   - 1 partie gratuite par semaine
   - Maximum 2 tirages par jour
   - Tirages bonus après achats ≥ 50 000 MGA

#### Paroles à dire :
> **"Ticket-Chance est notre jeu de fidélisation. Il se déverrouille automatiquement après 100 000 MGA d'achats. Le jeu utilise une roue de la fortune avec 8 prix différents, chacun ayant une probabilité spécifique. Les gains génèrent automatiquement des codes promo uniques que l'utilisateur peut utiliser sur ses prochains achats. C'est un excellent outil pour encourager la fidélité et les achats répétés."**

#### Points techniques à mentionner :
- ✅ Déverrouillage automatique (TicketChanceService)
- ✅ Tirage pondéré par probabilités
- ✅ Génération automatique de codes promo
- ✅ Gestion des limites (quotidiennes, hebdomadaires)
- ✅ Animation JavaScript fluide

---

## 🎯 Points clés à mettre en avant

### Architecture et technologies
- ✅ **Architecture 3-tier** bien structurée
- ✅ **Symfony 6+** avec les meilleures pratiques
- ✅ **PostgreSQL** pour la persistance
- ✅ **Redis** pour le cache et les sessions
- ✅ **Chart.js** pour les visualisations
- ✅ **Intégration MVola** pour les paiements

### Expérience utilisateur
- ✅ **Interface moderne et responsive**
- ✅ **Parcours utilisateur fluide** (inscription → achat → jeu)
- ✅ **Recherche avancée** avec filtres multiples
- ✅ **Statistiques personnalisées** avec graphiques interactifs
- ✅ **Jeu de fidélisation innovant** (Ticket-Chance)

### Sécurité et performance
- ✅ **Authentification sécurisée** (sessions + JWT)
- ✅ **Hachage des mots de passe** (bcrypt)
- ✅ **Protection CSRF** sur les formulaires
- ✅ **Validation côté serveur** de toutes les données
- ✅ **Cache Redis** pour améliorer les performances

### Fonctionnalités métier
- ✅ **Gestion complète du panier** (session + BDD)
- ✅ **Paiement mobile intégré** (MVola)
- ✅ **Génération de billets** avec QR codes
- ✅ **Export PDF** pour billets et factures
- ✅ **Système de favoris** et historique de recherche
- ✅ **Statistiques avancées** pour les utilisateurs

---

## ❓ Gestion des questions

### Questions fréquentes et réponses

#### Q1 : "Comment gérez-vous la sécurité des paiements ?"
**R :** "Nous utilisons l'API officielle de MVola avec des callbacks sécurisés en HTTPS. Les transactions sont validées côté serveur, et nous ne stockons jamais les informations de paiement sensibles. Chaque transaction génère un `serverCorrelationId` unique pour éviter les doublons."

#### Q2 : "Comment fonctionne le cache Redis ?"
**R :** "Redis est utilisé pour mettre en cache les événements populaires, les résultats de recherche, et les statistiques. Cela réduit significativement la charge sur la base de données et améliore les temps de réponse, surtout lors des pics de trafic."

#### Q3 : "Quelle est la différence entre FrontOffice et BackOffice ?"
**R :** "Le FrontOffice est l'application publique pour les utilisateurs finaux (achat de billets, profil, statistiques). Le BackOffice est l'interface d'administration pour gérer les événements, les utilisateurs, les commandes, et les statistiques globales de la plateforme."

#### Q4 : "Comment gérez-vous la disponibilité des billets en temps réel ?"
**R :** "Chaque ajout au panier vérifie la disponibilité en base de données. Les billets sont réservés temporairement pendant le processus de paiement (15 minutes). Si le paiement échoue ou expire, les billets sont libérés automatiquement."

#### Q5 : "Le jeu Ticket-Chance peut-il être manipulé ?"
**R :** "Non, le tirage est effectué côté serveur avec un algorithme de probabilités pondérées. Chaque tirage est enregistré en base de données avec un timestamp. Les limites quotidiennes et hebdomadaires sont également vérifiées côté serveur pour éviter les abus."

#### Q6 : "Comment gérez-vous les pics de trafic ?"
**R :** "Nous utilisons Redis pour le cache, la pagination pour limiter les requêtes, et des index optimisés en base de données. Le code est structuré pour être scalable, et nous pouvons facilement ajouter un load balancer et plusieurs instances de l'application si nécessaire."

#### Q7 : "Les données sont-elles exportables ?"
**R :** "Oui, les utilisateurs peuvent exporter leur historique d'achats en CSV, leurs statistiques en PDF, et leurs billets en PDF. Les administrateurs ont également accès à des exports complets depuis le BackOffice."

---

## ⏱️ Timing recommandé

| Étape | Durée | Total cumulé |
|-------|-------|--------------|
| Introduction + Architecture | 2 min | 2 min |
| Étape 1 : Accueil | 2 min | 4 min |
| Étape 2 : Inscription | 2 min | 6 min |
| Étape 3 : Connexion | 1 min | 7 min |
| Étape 4 : Événements | 3 min | 10 min |
| Étape 5 : Détails | 2 min | 12 min |
| Étape 6 : Panier/Paiement | 4 min | 16 min |
| Étape 7 : Mes billets | 2 min | 18 min |
| Étape 8 : Statistiques | 3 min | 21 min |
| Étape 9 : Ticket-Chance | 4 min | 25 min |
| Questions/Réponses | 5 min | **30 min** |

**Total : 25-30 minutes** (avec questions)

---

## 📌 Checklist avant la démonstration

- [ ] Vérifier que l'application est démarrée et accessible
- [ ] S'assurer qu'il y a des événements en base de données
- [ ] Tester le compte de démonstration (email + mot de passe)
- [ ] Vérifier que MVola est configuré (sandbox ou production)
- [ ] S'assurer que Redis est actif
- [ ] Vérifier que les graphiques Chart.js se chargent correctement
- [ ] Tester le jeu Ticket-Chance (vérifier l'éligibilité)
- [ ] Préparer des captures d'écran de secours
- [ ] Avoir un téléphone avec MVola pour démonstration du paiement
- [ ] Préparer des exemples de données (événements, commandes, statistiques)

---

## 🎬 Script de transition entre les étapes

### Transition 1 → 2
> **"Maintenant, pour pouvoir acheter des billets, l'utilisateur doit créer un compte. Passons à l'inscription."**

### Transition 2 → 3
> **"Une fois inscrit, l'utilisateur peut se connecter avec ses identifiants."**

### Transition 3 → 4
> **"Connecté, l'utilisateur peut maintenant explorer tous les événements disponibles."**

### Transition 4 → 5
> **"En cliquant sur un événement, l'utilisateur accède à tous les détails et peut sélectionner ses billets."**

### Transition 5 → 6
> **"Une fois les billets sélectionnés, ils sont ajoutés au panier et l'utilisateur peut procéder au paiement."**

### Transition 6 → 7
> **"Après le paiement, les billets sont créés automatiquement et accessibles dans 'Mes billets'."**

### Transition 7 → 8
> **"L'utilisateur peut également consulter ses statistiques personnelles pour voir ses habitudes d'achat."**

### Transition 8 → 9
> **"Enfin, pour récompenser les utilisateurs actifs, nous avons mis en place un jeu de fidélisation : Ticket-Chance."**

---

## 📝 Notes finales

- **Parler clairement** et à un rythme modéré
- **Montrer les fonctionnalités** plutôt que de simplement les décrire
- **Expliquer les choix techniques** quand c'est pertinent
- **Répondre aux questions** de manière concise
- **Mettre en avant l'expérience utilisateur** et la qualité du code
- **Rester dans le timing** (25-30 minutes maximum)

**Bonne démonstration ! 🚀**
