# 🎭 Le Parcours de Miora : Une Aventure sur Aiolia-Event

*Un récit complet qui illustre toutes les fonctionnalités de la plateforme à travers l'expérience d'une utilisatrice.*

---

## 📖 Prologue : La Découverte

**Miora**, 28 ans, passionnée de musique et de culture, cherche des événements à Antananarivo. Un ami lui parle d'**Aiolia-Event**, la nouvelle plateforme de billetterie malgache. Curieuse, elle ouvre son navigateur...

---

## Chapitre 1 : 🏠 La Page d'Accueil

Miora arrive sur la page d'accueil d'Aiolia-Event. Elle est immédiatement séduite par le design moderne et les couleurs chaleureuses.

### Ce qu'elle voit :

- **Une bannière héro** avec une recherche rapide
- **Les 6 prochains événements** mis en avant avec de belles images
- **Des statistiques impressionnantes** : "Plus de 10 000 billets vendus !"
- **Un calendrier** des événements à venir
- **Une section** "Vous êtes organisateur ?" pour créer ses propres événements

### Ce qu'elle peut faire :

- 🔍 Rechercher un événement directement
- 👀 Parcourir les événements vedettes
- 📅 Consulter le calendrier
- ℹ️ Accéder aux pages À propos et Contact

> **Route** : `/`
> **Fichiers** : `HomeController.php`, `templates/home/index.html.twig`

---

## Chapitre 2 : 📝 L'Inscription

Miora clique sur un événement qui l'intéresse. Un message lui indique qu'elle doit être connectée pour voir les détails. Elle décide de créer un compte.

### Le formulaire d'inscription :

Elle remplit les informations suivantes :
- **Prénom** : Miora
- **Nom** : Rasoarivelo
- **Email** : miora@example.com
- **Téléphone** : Elle remarque le **drapeau malgache 🇲🇬** et l'indicatif **+261** déjà présents. Elle tape simplement `32 00 000 00`
- **Mot de passe** : Un mot de passe sécurisé (minimum 8 caractères)

### Ce qui se passe en coulisses :

1. Le système vérifie que l'email n'existe pas déjà
2. Le mot de passe est **hashé** avec bcrypt (jamais stocké en clair !)
3. Le numéro de téléphone est normalisé au format international
4. Un compte est créé avec le statut "Actif"
5. Un **email de bienvenue** est envoyé automatiquement

### Après validation :

Miora est redirigée vers la page de connexion avec un message vert :
> ✅ "Inscription réussie ! Vous pouvez vous connecter avec vos identifiants."

Son email est pré-rempli dans le formulaire de connexion.

> **Route** : `/register`
> **Fichiers** : `AuthController.php`, `AuthService.php`, `UserMailer.php`

---

## Chapitre 3 : 🔐 La Connexion

Miora entre son email et son mot de passe fraîchement créés.

### Ce qui se passe :

1. Le système vérifie les identifiants
2. Un **token JWT** est généré (access token + refresh token)
3. Une **session PHP** est créée avec son profil
4. Un flag `just_logged_in` est positionné

### Après connexion :

Elle est redirigée vers la page d'accueil. Un message de bienvenue personnalisé s'affiche :
> 👋 "Bienvenue, Miora !"

Son nom apparaît désormais en haut de l'écran. Elle fait maintenant partie de la communauté Aiolia-Event !

> **Route** : `/login`
> **Fichiers** : `AuthController.php`, `AuthTokenService.php`

---

## Chapitre 4 : 🎪 La Découverte des Événements

Miora explore maintenant la liste complète des événements.

### Les filtres disponibles :

- **Catégorie** : Concert, Sport, Conférence, Festival, Business...
- **Ville** : Antananarivo, Toamasina, Mahajanga, Fianarantsoa...
- **Prix** : Fourchette min-max en MGA
- **Date** : Du ... au ...
- **Tri** : Par date, prix croissant, prix décroissant, popularité

### Sa recherche :

Miora tape "jazz" dans la barre de recherche, sélectionne "Concert" comme catégorie et "Antananarivo" comme ville. Elle trouve 3 résultats !

### Les événements groupés par catégorie :

Les résultats s'affichent, organisés par catégorie. Chaque carte événement montre :
- 📷 Une image attractive
- 📌 Le titre de l'événement
- 📅 La date et l'heure
- 📍 Le lieu
- 💰 La fourchette de prix
- ❤️ Un cœur pour ajouter aux favoris

### Historique de recherche :

Comme Miora est connectée, sa recherche "jazz" est automatiquement sauvegardée dans son historique. Elle pourra la retrouver plus tard !

> **Route** : `/events`, `/events?q=jazz&category=concert&city=antananarivo`
> **Fichiers** : `EventController.php`, `SearchHistoryRepository.php`

---

## Chapitre 5 : ❤️ Les Favoris

Miora voit un événement "Festival Jazz Antananarivo" qui lui plaît, mais elle n'est pas encore prête à acheter. Elle clique sur le **cœur** de la carte.

### Ce qui se passe :

1. Le cœur passe de vide à **plein (rouge)**
2. L'événement est ajouté à ses **favoris** en base de données
3. Une activité est logguée : "Événement ajouté aux favoris"

### Plus tard :

Miora pourra retrouver tous ses favoris dans son profil à `/profile/favorites`. Elle recevra aussi des alertes si des billets se libèrent !

> **Routes** : `POST /events/{id}/favorite`, `DELETE /events/{id}/favorite`
> **Fichiers** : `EventController.php`, `WishlistRepository.php`

---

## Chapitre 6 : 🎵 Les Détails d'un Événement

Miora clique sur "Concert Jazz Antananarivo" pour voir les détails.

### Ce qu'elle découvre :

- **Grande image de couverture** de l'événement
- **Description complète** avec le programme
- **Date et heure** : Samedi 15 février 2025 à 19h00
- **Lieu** : Palais des Sports, Antananarivo
- **Organisateur** : Jazz Madagascar Association

### Les types de billets disponibles :

| Type | Prix Adulte | Prix Enfant | Disponibilité |
|------|-------------|-------------|---------------|
| 🌟 VIP | 150 000 MGA | 75 000 MGA | 20 places |
| 🎫 Standard | 50 000 MGA | 25 000 MGA | 200 places |
| 🎁 Early Bird | 35 000 MGA | 17 500 MGA | Épuisé |

### Accessibilité :

Des icônes indiquent les services disponibles :
- ♿ Accès PMR
- 👂 Boucle magnétique
- 🅿️ Parking adapté

### Événements similaires :

En bas de page, une section suggère d'autres concerts de jazz à venir.

### Son choix :

Miora décide d'acheter **2 billets Standard Adulte** et **1 billet Standard Enfant** (pour sa nièce).

> **Route** : `/events/42`
> **Fichiers** : `EventController.php`, `templates/event/details.html.twig`

---

## Chapitre 7 : 🛒 Le Panier

Miora clique sur "Ajouter au panier". Elle est redirigée vers son panier.

### Ce qu'elle voit :

```
┌─────────────────────────────────────────────────────────────┐
│ 🛒 Mon Panier                                               │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ 🎵 Concert Jazz Antananarivo                                │
│    📅 15 février 2025 - 19:00                               │
│    📍 Palais des Sports, Antananarivo                       │
│                                                             │
│    Billets Standard :                                       │
│    • 2 × Adulte = 100 000 MGA                              │
│    • 1 × Enfant = 25 000 MGA                               │
│                                                             │
│    Sous-total : 125 000 MGA                                │
│                                           [❌ Supprimer]    │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│    Total : 125 000 MGA                                      │
│                                                             │
│    [🛒 Procéder au paiement]                               │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### Synchronisation du panier :

Le panier de Miora est synchronisé :
- ✅ En **session PHP** (pour la navigation immédiate)
- ✅ En **base de données** (pour la persistance)

Si elle se déconnecte et se reconnecte, son panier sera toujours là !

> **Route** : `/cart`
> **Fichiers** : `TicketController.php`, `CartSyncService.php`

---

## Chapitre 8 : 💳 Le Paiement

Miora clique sur "Procéder au paiement".

### La page de paiement :

Elle voit un récapitulatif de sa commande et doit remplir :

1. **Méthode de paiement** :
   - 📱 MVola (Telma) ← Elle choisit celle-ci
   - 🍊 Orange Money
   - 📶 Airtel Money

2. **Informations** :
   - Nom : Miora Rasoarivelo
   - Email : miora@example.com
   - Téléphone MVola : 034 00 000 00

3. **Conditions** :
   - ☑️ J'accepte les conditions générales d'utilisation

### Timer de sécurité :

Un compte à rebours de **15 minutes** s'affiche. Si le temps expire, les billets sont remis en vente.

### Elle clique sur "Payer 125 000 MGA"

### Ce qui se passe en coulisses :

```
┌─────────────────┐
│    Aiolia       │ ──► Création commande (status: pending)
│    Event        │ ──► Création transaction MVola
└────────┬────────┘
         │
         │ Appel API MVola
         ▼
┌─────────────────┐
│   API MVola     │ ──► Retourne serverCorrelationId
└────────┬────────┘
         │
         │ Notification push
         ▼
┌─────────────────┐
│  📱 Téléphone   │ ──► "MVola: Confirmez le paiement de 125 000 MGA"
│    de Miora     │
└────────┬────────┘
         │
         │ Miora entre son code PIN
         ▼
┌─────────────────┐
│   MVola envoie  │ ──► PUT /api/mvola/callback
│    callback     │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│    Aiolia       │ ──► Mise à jour transaction (status: paid)
│    Event        │ ──► Création des 3 billets avec QR codes
│                 │ ──► Mise à jour commande (status: paid)
│                 │ ──► Vidage du panier
│                 │ ──► Envoi email de confirmation
└─────────────────┘
```

> **Routes** : `/checkout/payment`, `POST /checkout/process`, `PUT /api/mvola/callback`
> **Fichiers** : `TicketController.php`, `PaymentService.php`, `MvolaController.php`

---

## Chapitre 9 : ✅ La Confirmation

Le paiement est réussi ! Miora voit la page de confirmation.

### Ce qu'elle voit :

```
┌─────────────────────────────────────────────────────────────┐
│                                                             │
│                    🎉 Paiement Réussi !                     │
│                                                             │
│    Votre commande CMD-000042 a été confirmée.              │
│                                                             │
│    📧 Un email avec vos billets a été envoyé à             │
│       miora@example.com                                     │
│                                                             │
│    Récapitulatif :                                          │
│    • 3 billets achetés                                      │
│    • Total : 125 000 MGA                                    │
│                                                             │
│    [📱 Voir mes billets]    [🏠 Retour à l'accueil]        │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

> **Route** : `/checkout/confirmation`
> **Fichiers** : `TicketController.php`, `templates/ticket/confirmation.html.twig`

---

## Chapitre 10 : 🎟️ Mes Billets

Miora accède à "Mes billets" pour voir ses achats.

### Les filtres disponibles :

- **À venir** : Événements futurs (sélectionné par défaut)
- **Passés** : Événements terminés
- **Annulés** : Billets annulés/remboursés

### Ses billets :

Elle voit 3 cartes de billets pour le Concert Jazz :

```
┌─────────────────────────────────────────────────────────────┐
│ 🎫 Billet #001                              [CONCERT]       │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ 🎵 Concert Jazz Antananarivo                                │
│                                                             │
│ 📅 Samedi 15 février 2025                                   │
│ ⏰ 19:00                                                    │
│ 📍 Palais des Sports, Antananarivo                          │
│                                                             │
│ Type : Standard Adulte                                      │
│ Prix : 50 000 MGA                                           │
│                                                             │
│ [📅 Calendrier]  [📄 PDF]  [ℹ️ Détails]                    │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### L'ajout au calendrier :

Miora clique sur l'icône **📅 Calendrier**. Un menu déroulant apparaît :

- 📅 **Google Calendar** → Ouvre un nouvel onglet avec l'événement pré-rempli
- 🍎 **Apple Calendar** → Télécharge un fichier .ics
- 📧 **Outlook** → Télécharge un fichier .ics

Elle choisit Google Calendar. L'événement est ajouté à son agenda avec :
- Titre : "Concert Jazz Antananarivo - Aiolia Event"
- Date : 15 février 2025, 19:00 - 23:00
- Lieu : Palais des Sports, Antananarivo
- Description : Détails du concert + lien vers le billet

> **Route** : `/my-tickets`
> **Fichiers** : `TicketController.php`, `templates/ticket/my_tickets.html.twig`

---

## Chapitre 11 : 📄 Le Billet PDF

Le jour J approche. Miora veut imprimer son billet. Elle clique sur "📄 PDF".

### Le PDF généré :

```
┌─────────────────────────────────────────────────────────────┐
│                                                             │
│  [Logo Aiolia]              BILLET D'ENTRÉE    #AE-2025-042│
│                                                             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────┐                                            │
│  │             │   🎵 CONCERT JAZZ ANTANANARIVO             │
│  │   [Image    │                                            │
│  │   Concert]  │   📅 Samedi 15 février 2025                │
│  │             │   ⏰ 19:00                                  │
│  └─────────────┘   📍 Palais des Sports, Antananarivo       │
│                                                             │
│  Participant : Miora Rasoarivelo                            │
│  Type : Standard Adulte                                     │
│  Prix : 50 000 MGA                                          │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│      ┌─────────────┐                                        │
│      │             │   Présentez ce QR Code à l'entrée      │
│      │  [QR CODE]  │   ou montrez-le sur votre smartphone   │
│      │             │                                        │
│      └─────────────┘                                        │
│                                                             │
│  Code : AE-2025-042-STD-001                                │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│  Conditions : Non remboursable • Non transférable           │
│  Contact : support@aiolia-event.com • +261 34 00 000 00    │
└─────────────────────────────────────────────────────────────┘
```

### Technologies utilisées :

- **Dompdf** : Génération du PDF
- **Endroid QR Code** : Génération du QR code unique
- **Base64** : Images encodées pour le PDF

> **Route** : `/my-tickets/42/pdf`
> **Fichiers** : `TicketController.php`, `templates/ticket/pdf.html.twig`

---

## Chapitre 12 : 👤 Le Profil Utilisateur

Miora explore son espace personnel.

### Le tableau de bord (`/profile`) :

```
┌─────────────────────────────────────────────────────────────┐
│                                                             │
│  👤 Miora Rasoarivelo                                       │
│  📧 miora@example.com                                       │
│  📅 Membre depuis 2025                                      │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  📊 Statistiques                                            │
│  ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐           │
│  │    3    │ │    2    │ │    0    │ │   125   │           │
│  │ Billets │ │ Favoris │ │ Panier  │ │ Points  │           │
│  └─────────┘ └─────────┘ └─────────┘ └─────────┘           │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  📜 Activités récentes                                      │
│  • 🎫 Billet acheté - Concert Jazz (il y a 2 heures)       │
│  • ❤️ Favori ajouté - Festival Gasy (hier)                 │
│  • 🔍 Recherche "jazz antananarivo" (hier)                 │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  🔗 Accès rapide                                            │
│  [🎫 Mes billets] [📜 Historique] [💰 Wallet] [⚙️ Params]  │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

> **Route** : `/profile`
> **Fichiers** : `ProfileController.php`, `templates/profile/index.html.twig`

---

## Chapitre 13 : 📜 L'Historique d'Achats

Miora consulte son historique pour retrouver ses factures.

### Ce qu'elle voit :

- **Filtres** : Tous, Confirmés, Remboursés, Annulés
- **Recherche** : Par numéro de commande ou événement
- **Statistiques** :
  - Total dépensé : 125 000 MGA
  - Billets achetés : 3
  - Commandes confirmées : 1

### Les actions disponibles :

- 📄 **Télécharger la facture** (PDF)
- 🗑️ **Supprimer de l'historique**
- 📊 **Exporter en CSV** (pour sa comptabilité)

### Le graphique des dépenses :

Un graphique affiche ses dépenses sur les 12 derniers mois.

> **Route** : `/profile/history`
> **Fichiers** : `ProfileController.php`, `OrderRepository.php`

---

## Chapitre 14 : 💰 Le Wallet

Miora découvre qu'elle a un portefeuille virtuel !

### Son wallet :

```
┌─────────────────────────────────────────────────────────────┐
│                                                             │
│  💰 Mon Portefeuille                                        │
│                                                             │
│  ┌─────────────────────┐ ┌─────────────────────┐            │
│  │ Solde disponible    │ │ Points fidélité     │            │
│  │                     │ │                     │            │
│  │    0 MGA            │ │    125 pts          │            │
│  │                     │ │    Niveau Bronze    │            │
│  └─────────────────────┘ └─────────────────────┘            │
│                                                             │
│  [💳 Recharger]  [↗️ Transférer]                            │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  📜 Transactions récentes                                   │
│  • +125 pts - Achat Concert Jazz (aujourd'hui)             │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### Fonctionnalités :

- **Recharger** : Ajouter du crédit via MVola/Orange Money
- **Transférer** : Envoyer de l'argent à un autre utilisateur
- **Points fidélité** : Gagnés à chaque achat (1 pt = 1 000 MGA dépensés)
- **Niveaux** : Bronze → Silver → Gold → Platinum

> **Route** : `/profile/wallet`
> **Fichiers** : `ProfileController.php`, `WalletService.php`, `LoyaltyPointsService.php`

---

## Chapitre 15 : 🎰 Ticket Chance

Miora découvre le mini-jeu "Ticket Chance" !

### La roue de la fortune :

```
              🎁 Bonus
         ╱            ╲
      -50%              -5%
       │                  │
    VIP ⭐────────────────-10%
       │                  │
      🎟️               -15%
         ╲            ╱
              -20%
```

### Les règles du jeu :

1. **1 partie gratuite par semaine** (reset le lundi)
2. **Parties bonus** après chaque achat
3. **Maximum 5 parties par jour** (anti-abus)
4. **Gains valables 30 jours**

### Les prix possibles :

| Prix | Probabilité |
|------|-------------|
| Réduction 5% | 25% |
| Réduction 10% | 15% |
| Réduction 15% | 10% |
| Réduction 20% | 5% |
| Réduction 50% | 2% |
| Billet gratuit | 3% |
| Upgrade VIP | 5% |
| Partie bonus | 35% |

### Miora tente sa chance :

Elle clique sur "Lancer la roue". La roue tourne pendant 3 secondes et s'arrête sur... **-10% !**

Un popup s'affiche :
> 🎉 Félicitations ! Vous avez gagné une réduction de 10% !
> Code : **CHANCE-ABC123**
> Valable jusqu'au 28 janvier 2025

> **Route** : `/profile/ticket-chance`
> **Fichiers** : `ProfileController.php`, `TicketChanceService.php`

---

## Chapitre 16 : 📊 Les Statistiques Personnelles

Miora est curieuse de voir ses habitudes d'achat.

### Ce qu'elle découvre :

```
┌─────────────────────────────────────────────────────────────┐
│                                                             │
│  📊 Mes Statistiques                  [📅 90 derniers jours]│
│                                                             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐           │
│  │    3    │ │ 125k    │ │    1    │ │    1    │           │
│  │ Billets │ │   MGA   │ │ Événem. │ │Commande │           │
│  └─────────┘ └─────────┘ └─────────┘ └─────────┘           │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  📈 Dépenses mensuelles                                     │
│                                                             │
│  150k │                    ┌───┐                            │
│       │                    │   │                            │
│  100k │                    │   │                            │
│       │                    │   │                            │
│   50k │                    │   │                            │
│       └────────────────────┴───┴────────                   │
│         Oct   Nov   Déc   Jan   Fév                        │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  🎯 Répartition par catégorie                               │
│                                                             │
│  Concert ████████████████████████████████████ 100%         │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  💡 Insights                                                │
│  • Votre mois le plus actif : Janvier 2025                 │
│  • Catégorie préférée : Concert                            │
│  • Suggestion : Découvrez les festivals de jazz !          │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### Export :

Elle peut exporter ses statistiques en CSV pour son usage personnel.

> **Route** : `/profile/stats`
> **Fichiers** : `ProfileController.php`, `UserStatsRepository.php`

---

## Chapitre 17 : 🔔 Les Notifications

Miora reçoit des notifications tout au long de son parcours.

### Types de notifications :

| Type | Exemple |
|------|---------|
| 🎟️ Ticket | "Vos billets pour Concert Jazz sont disponibles" |
| 💳 Paiement | "Paiement de 125 000 MGA confirmé" |
| ⏰ Rappel | "Rappel : Concert Jazz demain à 19:00" |
| 🎁 Offre | "Nouvelle offre : -20% sur les festivals !" |

### La page notifications :

```
┌─────────────────────────────────────────────────────────────┐
│                                                             │
│  🔔 Mes Notifications                [Tout marquer comme lu]│
│                                                             │
│  [Tous] [Non lus (2)] [Archives]                           │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  🔴 🎟️ Vos billets sont disponibles !                      │
│     Concert Jazz Antananarivo                               │
│     Téléchargez vos billets avant l'événement.             │
│     Il y a 2 heures                                         │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  🔴 💳 Paiement confirmé                                    │
│     Votre paiement de 125 000 MGA a été reçu.              │
│     Il y a 2 heures                                         │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### Notifications Push :

Miora a activé les notifications push. Même quand elle n'est pas sur le site, elle reçoit :
- Un rappel 24h avant l'événement
- Un rappel 2h avant l'événement

> **Route** : `/notifications`
> **Fichiers** : `NotificationController.php`, `push-notifications.js`, `service-worker.js`

---

## Chapitre 18 : ⚙️ Les Paramètres

Miora personnalise son expérience.

### Informations personnelles :

- Prénom : Miora
- Nom : Rasoarivelo
- Téléphone : +261 32 00 000 00
- Langue : 🇫🇷 Français

### Préférences de notifications :

- ☑️ Alertes billets disponibles
- ☑️ Rappels d'événement
- ☐ Newsletters et offres

### Apparence :

- ○ ☀️ **Clair** - Interface claire et lumineuse
- ● 🌙 **Sombre** - Mode sombre confortable

Miora choisit le **mode sombre**. L'interface passe instantanément en couleurs foncées !

### Sécurité :

- Mot de passe : Dernière modification il y a 30 jours
- Double authentification : Désactivée

### Photo de profil :

Miora clique sur "Modifier" et upload une photo. L'image est envoyée vers **Cloudinary** et son avatar est mis à jour partout sur le site.

> **Route** : `/profile/settings`
> **Fichiers** : `ProfileController.php`, `ThemeListener.php`, `CloudinaryService.php`

---

## Chapitre 19 : 🌍 Le Multilingue

L'ami anglophone de Miora, **John**, visite aussi le site.

### Changement de langue :

John clique sur le sélecteur de langue en bas de page :
- 🇫🇷 Français (défaut)
- 🇬🇧 **English** ← Il choisit celle-ci

### Ce qui se passe :

1. La route `/locale/en` est appelée
2. La session stocke `_locale = en`
3. La page se recharge en anglais
4. Tous les textes sont traduits :
   - "Événements" → "Events"
   - "Mon panier" → "My cart"
   - "Se connecter" → "Log in"

### Persistance :

À sa prochaine visite, John verra le site en anglais automatiquement.

> **Route** : `/locale/{locale}`
> **Fichiers** : `LocaleController.php`, `LocaleListener.php`, `translations/messages.en.yaml`

---

## Chapitre 20 : 📅 Le Jour J

C'est le 15 février. Miora reçoit ses rappels :

### 24h avant (14 février, 19:00) :

> 🔔 **Rappel : Concert Jazz Antananarivo demain !**
> L'événement commence demain à 19:00 au Palais des Sports.
> N'oubliez pas d'apporter vos billets !

### 2h avant (15 février, 17:00) :

> 🔔 **C'est bientôt l'heure !**
> Le Concert Jazz Antananarivo commence dans 2 heures.
> Préparez-vous à vivre un moment inoubliable !

### À l'entrée :

Miora présente son billet PDF (ou l'ouvre sur son téléphone). Le contrôleur scanne le **QR code**. Le système valide :
- ✅ Billet authentique
- ✅ Non utilisé
- ✅ Événement correspondant

Elle entre dans la salle avec sa nièce. Le concert commence... 🎷🎶

---

## Épilogue : Le Retour

### Après le concert :

Miora est enchantée ! Elle retourne sur Aiolia-Event et :

1. **Ajoute l'événement aux favoris** (pour se souvenir)
2. **Consulte ses statistiques** : "1 concert, 125 000 MGA dépensés"
3. **Joue à Ticket Chance** : Elle gagne une partie bonus !
4. **Recherche le prochain festival de jazz**

### Ses billets passent dans "Passés" :

Dans `/my-tickets?filter=past`, elle retrouve ses anciens billets comme souvenirs numériques.

### Elle recommande la plateforme :

Miora parle d'Aiolia-Event à ses amis. Ils s'inscrivent à leur tour, et le cycle continue...

---

## 📚 Récapitulatif des Fonctionnalités

| # | Fonctionnalité | Description |
|---|----------------|-------------|
| 1 | 🏠 Accueil | Page d'accueil avec événements vedettes |
| 2 | 📝 Inscription | Création de compte avec email de bienvenue |
| 3 | 🔐 Connexion | Authentification avec JWT |
| 4 | 🎪 Événements | Liste, recherche, filtres avancés |
| 5 | ❤️ Favoris | Sauvegarde d'événements |
| 6 | 📋 Détails | Informations complètes, types de billets |
| 7 | 🛒 Panier | Gestion multi-événements, synchronisation |
| 8 | 💳 Paiement | MVola, Orange Money, Airtel Money |
| 9 | ✅ Confirmation | Récapitulatif de commande |
| 10 | 🎟️ Mes billets | Liste, filtres, actions |
| 11 | 📅 Calendrier | Ajout Google/Apple/Outlook |
| 12 | 📄 PDF | Billet imprimable avec QR code |
| 13 | 👤 Profil | Tableau de bord personnel |
| 14 | 📜 Historique | Commandes, factures, export |
| 15 | 💰 Wallet | Solde, points, transferts |
| 16 | 🎰 Ticket Chance | Roue de la fortune |
| 17 | 📊 Statistiques | Analyses personnelles |
| 18 | 🔔 Notifications | In-app, push, emails |
| 19 | ⚙️ Paramètres | Préférences personnelles |
| 20 | 🌙 Mode sombre | Thème clair/sombre |
| 21 | 🌍 Multilingue | Français, Anglais |
| 22 | 🔍 Historique recherche | Recherches sauvegardées |

---

## 🛠️ Technologies Utilisées

| Composant | Technologie |
|-----------|-------------|
| Backend | Symfony 6.x (PHP 8.2) |
| Frontend | Twig + JavaScript |
| Base de données | PostgreSQL |
| Paiement | MVola API |
| Images | Cloudinary |
| PDF | Dompdf |
| QR Code | Endroid QR Code |
| Push | Service Worker |
| Authentification | JWT + Session |
| i18n | Symfony Translation |

---

*Fin du parcours de Miora. Merci d'avoir lu cette aventure sur Aiolia-Event !* 🎉

