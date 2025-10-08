# 🏗️ Architecture FrontOffice & BackOffice - Aiolia Event

## 📋 Vue d'Ensemble

```
┌────────────────────────────────────────────────────────────────┐
│                    AIOLIA EVENT - ARCHITECTURE                 │
└────────────────────────────────────────────────────────────────┘

┌─────────────────────┐                    ┌─────────────────────┐
│   FRONTOFFICE       │                    │    BACKOFFICE       │
│   (React.js)        │◄──────────────────►│   (Symfony 7)       │
│                     │    API REST/JSON   │                     │
│ 👥 Utilisateurs     │                    │ 👨‍💼 Admin/Organisateurs│
│                     │                    │                     │
│ • Voir événements   │                    │ • Interface Admin   │
│ • Acheter billets   │                    │ • CRUD Événements   │
│ • Mon profil        │                    │ • Gestion Users     │
│ • Mon panier        │                    │ • Statistiques      │
│ • Mes billets       │                    │ • Rapports          │
│ • Paiement          │                    │ • API REST          │
│                     │                    │                     │
│ Port: 3000          │                    │ Port: 8000          │
└─────────┬───────────┘                    └──────────┬──────────┘
          │                                           │
          │                                           │
          │          ┌──────────────────┐            │
          └─────────►│   MySQL 8.0      │◄───────────┘
                     │   60+ Tables     │
                     └──────────────────┘
```

---

## 📁 Structure du Projet

```
Aiolia-event/
│
├── frontoffice/              ⚛️  REACT.JS - Interface Utilisateurs
│   │
│   ├── public/
│   │   ├── index.html
│   │   ├── favicon.ico
│   │   └── assets/
│   │
│   ├── src/
│   │   ├── components/       # Composants UI
│   │   │   ├── common/
│   │   │   │   ├── Navbar.jsx
│   │   │   │   ├── Footer.jsx
│   │   │   │   ├── Button.jsx
│   │   │   │   └── Card.jsx
│   │   │   │
│   │   │   ├── Event/
│   │   │   │   ├── EventCard.jsx
│   │   │   │   ├── EventList.jsx
│   │   │   │   ├── EventDetail.jsx
│   │   │   │   └── EventFilters.jsx
│   │   │   │
│   │   │   ├── Ticket/
│   │   │   │   ├── TicketCard.jsx
│   │   │   │   ├── TicketSelector.jsx
│   │   │   │   └── QRCodeDisplay.jsx
│   │   │   │
│   │   │   ├── Cart/
│   │   │   │   ├── CartDrawer.jsx
│   │   │   │   ├── CartItem.jsx
│   │   │   │   └── CartSummary.jsx
│   │   │   │
│   │   │   └── User/
│   │   │       ├── ProfileCard.jsx
│   │   │       ├── Wallet.jsx
│   │   │       └── Statistics.jsx
│   │   │
│   │   ├── pages/            # Pages utilisateurs
│   │   │   ├── Home.jsx                  # Page d'accueil
│   │   │   ├── Events/
│   │   │   │   ├── EventsPage.jsx       # Liste événements
│   │   │   │   └── EventDetailPage.jsx  # Détail événement
│   │   │   ├── Auth/
│   │   │   │   ├── LoginPage.jsx        # Connexion
│   │   │   │   └── RegisterPage.jsx     # Inscription
│   │   │   ├── User/
│   │   │   │   ├── ProfilePage.jsx      # Profil
│   │   │   │   ├── MyTicketsPage.jsx    # Mes billets
│   │   │   │   ├── OrdersPage.jsx       # Mes commandes
│   │   │   │   ├── WalletPage.jsx       # Portefeuille
│   │   │   │   └── FavoritesPage.jsx    # Favoris
│   │   │   └── Checkout/
│   │   │       ├── CartPage.jsx         # Panier
│   │   │       └── CheckoutPage.jsx     # Paiement
│   │   │
│   │   ├── services/         # Services API
│   │   │   ├── api.js                   # Config Axios
│   │   │   ├── auth.service.js
│   │   │   ├── event.service.js
│   │   │   ├── ticket.service.js
│   │   │   ├── order.service.js
│   │   │   └── payment.service.js
│   │   │
│   │   ├── store/            # Redux Store
│   │   │   ├── index.js
│   │   │   └── slices/
│   │   │       ├── authSlice.js
│   │   │       ├── cartSlice.js
│   │   │       └── eventSlice.js
│   │   │
│   │   ├── hooks/            # Custom Hooks
│   │   │   ├── useAuth.js
│   │   │   ├── useCart.js
│   │   │   └── useEvents.js
│   │   │
│   │   ├── utils/            # Utilitaires
│   │   ├── styles/           # Styles CSS
│   │   ├── App.jsx
│   │   └── main.jsx
│   │
│   ├── .env.local
│   ├── package.json
│   ├── vite.config.js
│   └── README.md
│
│
├── backoffice/               🎻  SYMFONY 7 - Admin + API
│   │
│   ├── bin/
│   │   └── console
│   │
│   ├── config/
│   │   ├── packages/
│   │   │   ├── doctrine.yaml
│   │   │   ├── security.yaml
│   │   │   ├── lexik_jwt_authentication.yaml
│   │   │   ├── nelmio_cors.yaml
│   │   │   └── easy_admin.yaml
│   │   ├── routes/
│   │   │   ├── api.yaml       # Routes API pour frontoffice
│   │   │   └── admin.yaml     # Routes admin
│   │   └── services.yaml
│   │
│   ├── public/
│   │   ├── index.php
│   │   └── uploads/           # Médias uploadés
│   │
│   ├── src/
│   │   │
│   │   ├── Controller/
│   │   │   │
│   │   │   ├── Api/          # 🔌 API REST (pour FrontOffice React)
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── EventController.php
│   │   │   │   ├── TicketController.php
│   │   │   │   ├── OrderController.php
│   │   │   │   ├── PaymentController.php
│   │   │   │   └── UserController.php
│   │   │   │
│   │   │   └── Admin/        # 👨‍💼 Interface Admin (EasyAdmin)
│   │   │       ├── DashboardController.php
│   │   │       ├── EventCrudController.php
│   │   │       ├── UserCrudController.php
│   │   │       ├── OrderCrudController.php
│   │   │       ├── TicketCrudController.php
│   │   │       └── StatisticsController.php
│   │   │
│   │   ├── Entity/           # 60+ Entités Doctrine
│   │   │   ├── User.php
│   │   │   ├── Event.php
│   │   │   ├── Ticket.php
│   │   │   ├── Order.php
│   │   │   └── ... (56 autres)
│   │   │
│   │   ├── Repository/
│   │   │   ├── UserRepository.php
│   │   │   ├── EventRepository.php
│   │   │   └── ...
│   │   │
│   │   ├── Service/          # Logique métier
│   │   │   ├── Auth/
│   │   │   │   └── AuthenticationService.php
│   │   │   ├── Event/
│   │   │   │   ├── EventService.php
│   │   │   │   └── EventSearchService.php
│   │   │   ├── Ticket/
│   │   │   │   ├── TicketService.php
│   │   │   │   ├── QrCodeService.php
│   │   │   │   └── DynamicPricingService.php
│   │   │   ├── Payment/
│   │   │   │   ├── PaymentService.php
│   │   │   │   ├── OrangeMoneyService.php
│   │   │   │   ├── AirtelMoneyService.php
│   │   │   │   └── TelmaMoneyService.php
│   │   │   └── Statistics/
│   │   │       └── StatisticsService.php
│   │   │
│   │   ├── Security/
│   │   │   ├── Voter/
│   │   │   │   ├── EventVoter.php
│   │   │   │   └── OrderVoter.php
│   │   │   └── UserProvider.php
│   │   │
│   │   ├── EventListener/
│   │   ├── Command/          # Commandes CRON
│   │   ├── DataFixtures/
│   │   └── Kernel.php
│   │
│   ├── templates/            # 🎨 Templates Twig (Interface Admin)
│   │   ├── admin/
│   │   │   ├── dashboard.html.twig
│   │   │   ├── login.html.twig
│   │   │   └── layout.html.twig
│   │   └── base.html.twig
│   │
│   ├── tests/
│   ├── var/
│   ├── vendor/
│   ├── .env.local
│   ├── composer.json
│   └── README.md
│
│
├── database/                 📊  SQL & Documentation
│   ├── schema.sql
│   ├── triggers.sql
│   ├── procedures.sql
│   ├── seeds.sql
│   ├── CONCEPTION_SQL.md
│   └── ...
│
├── docs/                     📚  Documentation
│   └── ...
│
└── README.md
```

---

## 🎯 Rôles & Responsabilités

### 🌐 FrontOffice (React.js)

**Pour qui ?** 👥 **Utilisateurs finaux** (clients)

**Fonctionnalités** :
- ✅ Parcourir les événements
- ✅ Rechercher et filtrer
- ✅ Voir les détails d'un événement
- ✅ Ajouter au panier
- ✅ Passer commande
- ✅ Payer (Mobile Money)
- ✅ Voir mes billets (avec QR code)
- ✅ Gérer mon profil
- ✅ Consulter mon portefeuille
- ✅ Historique des commandes
- ✅ Favoris
- ✅ Jouer au mini-jeu
- ✅ Parrainer des amis

**Technologies** :
- React 18+
- Vite (build tool)
- Tailwind CSS / Material-UI
- Redux Toolkit
- React Router
- Axios

**Accès** :
- URL : `https://aiolia-event.com`
- Public (inscription ouverte)

---

### 🎛️ BackOffice (Symfony 7)

**Pour qui ?** 👨‍💼 **Administrateurs & Organisateurs**

**Fonctionnalités** :

#### 1. 📊 Interface Admin (EasyAdmin + Twig)
- ✅ Dashboard avec statistiques
- ✅ CRUD Événements
- ✅ CRUD Utilisateurs
- ✅ Gestion des commandes
- ✅ Gestion des billets
- ✅ Scanner QR codes (check-in)
- ✅ Rapports et exports (PDF, CSV)
- ✅ Gestion des codes promo
- ✅ Configuration prix dynamiques
- ✅ Gestion équipe (co-organisateurs)
- ✅ Statistiques avancées
- ✅ Liste d'attente
- ✅ Notifications

#### 2. 🔌 API REST (pour FrontOffice)
- ✅ Authentification JWT
- ✅ CRUD Événements (lecture publique)
- ✅ CRUD Billets
- ✅ CRUD Commandes
- ✅ Paiement Mobile Money
- ✅ Profil utilisateur
- ✅ Panier
- ✅ Favoris
- ✅ Recherche

**Technologies** :
- Symfony 7.0
- Doctrine ORM
- EasyAdmin Bundle
- Twig
- JWT Authentication
- API REST

**Accès** :
- Interface Admin : `https://admin.aiolia-event.com` ou `https://aiolia-event.com/admin`
- API REST : `https://api.aiolia-event.com` ou `https://aiolia-event.com/api`
- Authentification requise (rôles : ORGANIZER, ADMIN)

---

## 🔄 Flux de Communication

```
┌───────────────────────────────────────────────────────────────┐
│                  FLUX DE COMMUNICATION                        │
└───────────────────────────────────────────────────────────────┘

1️⃣ UTILISATEUR (FrontOffice React)
   │
   │ GET /api/events
   ▼
   BackOffice API REST
   │
   │ SELECT * FROM events
   ▼
   MySQL Database
   │
   │ JSON Response
   ▼
   FrontOffice React
   │
   │ Affiche les événements
   ▼
   Utilisateur voit la liste


2️⃣ ADMIN (BackOffice Symfony)
   │
   │ Accède à /admin/events
   ▼
   Interface EasyAdmin
   │
   │ Formulaire de création
   ▼
   Crée un événement
   │
   │ INSERT INTO events
   ▼
   MySQL Database
   │
   │ Événement disponible via API
   ▼
   FrontOffice peut l'afficher
```

---

## 🔐 Authentification & Sécurité

### Pour le FrontOffice (Utilisateurs)

**Workflow** :
```
1. User remplit formulaire login sur React
   ↓
2. POST /api/auth/login vers BackOffice
   ↓
3. BackOffice vérifie credentials
   ↓
4. Retourne JWT token
   ↓
5. React stocke token (localStorage)
   ↓
6. Requêtes suivantes incluent :
   Authorization: Bearer {token}
```

### Pour le BackOffice (Admin)

**Workflow** :
```
1. Admin accède à /admin
   ↓
2. Formulaire login Symfony classique
   ↓
3. Session Symfony créée
   ↓
4. Accès interface EasyAdmin
```

---

## ⚙️ Configuration

### FrontOffice `.env.local`

```env
# API BackOffice
VITE_API_URL=http://localhost:8000/api
VITE_API_TIMEOUT=10000

# OAuth
VITE_GOOGLE_CLIENT_ID=your_client_id
VITE_FACEBOOK_APP_ID=your_app_id

# Features
VITE_ENABLE_DARK_MODE=true
```

### BackOffice `.env.local`

```env
# Database
DATABASE_URL="mysql://root:password@127.0.0.1:3306/aiolia_event?serverVersion=8.0"

# CORS (pour FrontOffice React)
CORS_ALLOW_ORIGIN=http://localhost:3000

# JWT (pour API REST)
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_passphrase

# Admin credentials
ADMIN_EMAIL=admin@aiolia-event.com
ADMIN_PASSWORD=AdminPassword123!

# Mobile Money
ORANGE_MONEY_API_KEY=your_key
AIRTEL_MONEY_API_KEY=your_key
TELMA_MONEY_API_KEY=your_key
```

---

## 🚀 Démarrage

### 1. Base de Données

```bash
mysql -u root -p < database/schema.sql
mysql -u root -p aiolia_event < database/triggers.sql
mysql -u root -p aiolia_event < database/procedures.sql
mysql -u root -p aiolia_event < database/seeds.sql
```

### 2. BackOffice (Symfony 7)

```bash
cd backoffice
symfony new . --version=7.0 --webapp
composer require easycorp/easyadmin-bundle lexik/jwt-authentication-bundle
php bin/console doctrine:mapping:import "App\Entity" attribute
symfony server:start -d
```

**Accès** :
- Interface Admin : http://localhost:8000/admin
- API REST : http://localhost:8000/api

### 3. FrontOffice (React.js)

```bash
cd frontoffice
npm create vite@latest . -- --template react
npm install
npm install react-router-dom axios @reduxjs/toolkit react-redux
npm run dev
```

**Accès** :
- Application : http://localhost:3000

---

## 📊 Comparaison FrontOffice vs BackOffice

| Aspect | FrontOffice (React) | BackOffice (Symfony) |
|--------|---------------------|----------------------|
| **Utilisateurs** | 👥 Clients | 👨‍💼 Admin/Organisateurs |
| **Interface** | SPA moderne | Interface admin classique |
| **Technologie** | React.js | Symfony + EasyAdmin |
| **Authentification** | JWT via API | Session Symfony |
| **URL** | aiolia-event.com | admin.aiolia-event.com |
| **Fonctions** | Consulter, Acheter | Gérer, Administrer |
| **Responsive** | Mobile-first | Desktop-first |
| **Performances** | Ultra-rapide (SPA) | Standard |
| **SEO** | SSR possible | N/A |
| **Déploiement** | CDN (Vercel) | VPS (Platform.sh) |

---

## ✅ Avantages de cette Architecture

### Pour le FrontOffice (React)
✅ **Performance** : SPA ultra-rapide  
✅ **UX** : Expérience fluide et moderne  
✅ **Mobile** : Responsive natif  
✅ **PWA** : Peut devenir une Progressive Web App  
✅ **SEO** : SSR avec Next.js si besoin  

### Pour le BackOffice (Symfony)
✅ **Rapidité de développement** : EasyAdmin = CRUD en 5 minutes  
✅ **Sécurité** : Framework mature et sécurisé  
✅ **Performance** : Doctrine ORM optimisé  
✅ **API REST** : Symfony excellentpour les APIs  
✅ **Admin interface** : Professionnelle et complète  

### Globalement
✅ **Séparation des préoccupations** : Chaque partie a son rôle  
✅ **Scalabilité** : Peut déployer séparément  
✅ **Maintenance** : Code organisé et clair  
✅ **Équipe** : Front-end et back-end peuvent travailler en parallèle  

---

## 🎯 Résumé

```
┌─────────────────────────────────────────────────────────┐
│                    AIOLIA EVENT                         │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  FrontOffice (React.js)                                │
│  ├─ Pour : Utilisateurs (clients)                      │
│  ├─ Fonction : Consulter, Acheter, Gérer son compte   │
│  └─ URL : https://aiolia-event.com                     │
│                                                         │
│  BackOffice (Symfony 7)                                │
│  ├─ Pour : Admin & Organisateurs                       │
│  ├─ Fonction : Gérer tout le système + API REST       │
│  ├─ URL Admin : https://admin.aiolia-event.com        │
│  └─ URL API : https://api.aiolia-event.com            │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

---

**Cette architecture est PARFAITE pour votre projet !** 🎉


