# 📚 Documentation Aiolia-Event Frontend

Bienvenue dans la documentation technique et fonctionnelle de **Aiolia-Event Frontend**, la plateforme de billetterie événementielle de Madagascar.

---

## 🎯 Vue d'ensemble

Aiolia-Event est une application web moderne permettant aux utilisateurs de :
- Découvrir et rechercher des événements
- Acheter des billets en ligne via MVola
- Gérer leurs billets et leur profil
- Participer à des jeux et gagner des réductions

---

## 📋 Index des Modules

| # | Module | Description | Fichier |
|---|--------|-------------|---------|
| 01 | 🏠 [Accueil](./MODULE_01_ACCUEIL.md) | Page d'accueil, événements vedettes | `MODULE_01_ACCUEIL.md` |
| 02 | 🔐 [Authentification](./MODULE_02_AUTHENTIFICATION.md) | Connexion, inscription, JWT | `MODULE_02_AUTHENTIFICATION.md` |
| 03 | 🎪 [Événements](./MODULE_03_EVENEMENTS.md) | Liste, recherche, détails, favoris | `MODULE_03_EVENEMENTS.md` |
| 04 | 🎟️ [Billets](./MODULE_04_BILLETS.md) | Panier, achat, mes billets, PDF | `MODULE_04_BILLETS.md` |
| 05 | 💳 [Paiement MVola](./MODULE_05_PAIEMENT_MVOLA.md) | Intégration paiement mobile | `MODULE_05_PAIEMENT_MVOLA.md` |
| 06 | 👤 [Profil](./MODULE_06_PROFIL.md) | Dashboard, historique, wallet | `MODULE_06_PROFIL.md` |
| 07 | 🔔 [Notifications](./MODULE_07_NOTIFICATIONS.md) | In-app, push, emails | `MODULE_07_NOTIFICATIONS.md` |
| 08 | 🎰 [Ticket Chance](./MODULE_08_TICKET_CHANCE.md) | Jeu roue de la fortune | `MODULE_08_TICKET_CHANCE.md` |
| 09 | 🎪 [Organisateur](./MODULE_09_ORGANISATEUR.md) | Gestion événements, admin | `MODULE_09_ORGANISATEUR.md` |
| 10 | 👥 [Social](./MODULE_10_SOCIAL.md) | Invitations, partage, calendrier | `MODULE_10_SOCIAL.md` |

---

## 🏗️ Architecture Technique

### Stack technologique

| Composant | Technologie |
|-----------|-------------|
| Framework | Symfony 6.x |
| Template Engine | Twig |
| Base de données | PostgreSQL |
| Authentification | JWT + Session PHP |
| Paiement | MVola API (Telma Madagascar) |
| Images | Cloudinary |
| PDF | Dompdf |
| QR Code | Endroid QR Code |
| CSS | Custom + Bootstrap |

### Structure du projet

```
Aiolia-event-front/
├── bin/                    # Commandes Symfony
├── config/                 # Configuration
│   ├── packages/          # Bundles configuration
│   └── routes/            # Routes
├── public/                 # Assets publics
│   ├── css/               # Styles
│   ├── js/                # Scripts
│   └── vente-ticket/      # Maquette HTML
├── src/
│   ├── Controller/        # Contrôleurs
│   ├── Entity/            # Entités Doctrine
│   ├── Repository/        # Repositories
│   ├── Service/           # Services métier
│   ├── EventListener/     # Listeners
│   └── Command/           # Commandes CLI
├── templates/              # Templates Twig
├── translations/           # Fichiers i18n
└── book/                   # Documentation (vous êtes ici!)
```

---

## 🔑 Fonctionnalités Clés

### Pour les Utilisateurs

| Fonctionnalité | Description |
|----------------|-------------|
| 🔍 Recherche avancée | Par catégorie, ville, prix, date |
| ❤️ Favoris | Sauvegarder ses événements préférés |
| 🛒 Panier | Gestion multi-événements |
| 💳 Paiement MVola | Paiement mobile sécurisé |
| 📱 Billets PDF | QR code, téléchargeable |
| 📅 Calendrier | Ajouter à Google/Apple/Outlook |
| 🔔 Notifications | Rappels automatiques |
| 🎰 Jeux | Roue de la fortune |
| 💰 Wallet | Solde rechargeable |
| 📊 Statistiques | Analyse des achats |

### Pour les Organisateurs

| Fonctionnalité | Description |
|----------------|-------------|
| 📝 Création événement | Formulaire complet |
| 💹 Dashboard | Suivi des ventes |
| 🏷️ Promotions | Codes promo |
| 📈 Rapports | Export CSV/PDF |

### Pour les Admins

| Fonctionnalité | Description |
|----------------|-------------|
| ❌ Annulation | Avec remboursement auto |
| 👥 Utilisateurs | Gestion des comptes |
| ✅ Validation | Approbation organisateurs |

---

## 🗺️ Routes Principales

### Pages publiques

| Route | Description |
|-------|-------------|
| `/` | Page d'accueil |
| `/events` | Liste des événements |
| `/events/{id}` | Détails événement |
| `/login` | Connexion |
| `/register` | Inscription |

### Pages authentifiées

| Route | Description |
|-------|-------------|
| `/cart` | Panier |
| `/checkout/payment` | Paiement |
| `/my-tickets` | Mes billets |
| `/profile` | Tableau de bord |
| `/profile/wallet` | Wallet |
| `/profile/ticket-chance` | Jeu |
| `/notifications` | Notifications |

### API

| Route | Description |
|-------|-------------|
| `/api/auth/*` | Authentification |
| `/api/events/*` | Événements |
| `/api/tickets/*` | Billets/Panier |
| `/api/notifications/*` | Notifications |
| `/api/wallet/*` | Wallet |
| `/api/mvola/*` | Paiement |

---

## 📱 Responsive Design

L'application est conçue en "mobile-first" et s'adapte à tous les écrans :

| Breakpoint | Largeur | Cible |
|------------|---------|-------|
| Mobile | < 768px | Smartphones |
| Tablet | 768px - 1024px | Tablettes |
| Desktop | > 1024px | Ordinateurs |

---

## 🌍 Internationalisation

L'application supporte deux langues :

| Code | Langue | Fichier |
|------|--------|---------|
| `fr` | Français | `translations/messages.fr.yaml` |
| `en` | Anglais | `translations/messages.en.yaml` |

---

## 🔒 Sécurité

| Mesure | Description |
|--------|-------------|
| HTTPS | Obligatoire en production |
| JWT | Tokens sécurisés pour l'API |
| CSRF | Protection sur les formulaires |
| Password Hashing | Bcrypt |
| Rate Limiting | Protection anti-brute force |

---

## 📞 Contact

Pour toute question technique, contactez l'équipe de développement.

---

*Documentation générée pour Aiolia-Event Frontend v1.0*

