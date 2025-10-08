# 🏗️ Architecture du Projet Aiolia Event

## 📋 Vue d'Ensemble

**Architecture Découplée** : Frontend React.js + Backend Symfony 7

```
Aiolia-event/
├── frontend/          # Application React.js (Module Utilisateur)
├── backend/           # API Symfony 7 + Admin
└── database/          # Schéma SQL & Documentation
```

---

## 🎯 Pourquoi Cette Architecture ?

### ✅ **Avantages**

1. **Séparation des Préoccupations**
   - Frontend et Backend totalement indépendants
   - Équipes peuvent travailler en parallèle
   - Déploiements séparés

2. **Scalabilité**
   - Scaler le frontend et backend indépendamment
   - CDN pour le frontend
   - Load balancer pour le backend

3. **Flexibilité Technologique**
   - React.js : Meilleur pour UX utilisateur
   - Symfony 7 : Parfait pour logique métier complexe

4. **Performance**
   - SPA (Single Page Application) ultra-rapide
   - API REST optimisée
   - Cache efficace

5. **Maintenance**
   - Codebase claire et organisée
   - Tests séparés
   - CI/CD indépendants

---

## 📁 Structure Complète du Projet

```
Aiolia-event/
│
├── frontend/                          # 🎨 APPLICATION REACT.JS
│   ├── public/
│   │   ├── index.html
│   │   ├── favicon.ico
│   │   └── manifest.json
│   │
│   ├── src/
│   │   ├── components/               # Composants réutilisables
│   │   │   ├── common/
│   │   │   │   ├── Header.jsx
│   │   │   │   ├── Footer.jsx
│   │   │   │   ├── Navbar.jsx
│   │   │   │   ├── Button.jsx
│   │   │   │   ├── Card.jsx
│   │   │   │   └── Modal.jsx
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
│   │   │   │   └── QRCode.jsx
│   │   │   │
│   │   │   ├── Cart/
│   │   │   │   ├── CartItem.jsx
│   │   │   │   ├── CartSummary.jsx
│   │   │   │   └── CartDrawer.jsx
│   │   │   │
│   │   │   └── User/
│   │   │       ├── ProfileCard.jsx
│   │   │       ├── Wallet.jsx
│   │   │       └── Statistics.jsx
│   │   │
│   │   ├── pages/                    # Pages principales
│   │   │   ├── Home.jsx
│   │   │   ├── Events/
│   │   │   │   ├── EventsPage.jsx
│   │   │   │   └── EventDetailPage.jsx
│   │   │   ├── Auth/
│   │   │   │   ├── LoginPage.jsx
│   │   │   │   └── RegisterPage.jsx
│   │   │   ├── User/
│   │   │   │   ├── ProfilePage.jsx
│   │   │   │   ├── MyTicketsPage.jsx
│   │   │   │   ├── OrdersPage.jsx
│   │   │   │   ├── WalletPage.jsx
│   │   │   │   └── FavoritesPage.jsx
│   │   │   ├── Checkout/
│   │   │   │   ├── CartPage.jsx
│   │   │   │   └── CheckoutPage.jsx
│   │   │   └── NotFound.jsx
│   │   │
│   │   ├── services/                 # Services API
│   │   │   ├── api.js               # Configuration Axios
│   │   │   ├── auth.service.js
│   │   │   ├── event.service.js
│   │   │   ├── ticket.service.js
│   │   │   ├── order.service.js
│   │   │   └── payment.service.js
│   │   │
│   │   ├── store/                    # Redux/Zustand Store
│   │   │   ├── index.js
│   │   │   ├── slices/
│   │   │   │   ├── authSlice.js
│   │   │   │   ├── cartSlice.js
│   │   │   │   ├── eventSlice.js
│   │   │   │   └── userSlice.js
│   │   │   └── actions/
│   │   │
│   │   ├── hooks/                    # Custom Hooks
│   │   │   ├── useAuth.js
│   │   │   ├── useCart.js
│   │   │   ├── useEvents.js
│   │   │   └── useDebounce.js
│   │   │
│   │   ├── utils/                    # Utilitaires
│   │   │   ├── formatters.js
│   │   │   ├── validators.js
│   │   │   └── constants.js
│   │   │
│   │   ├── styles/                   # Styles globaux
│   │   │   ├── globals.css
│   │   │   ├── variables.css
│   │   │   └── tailwind.css
│   │   │
│   │   ├── App.jsx                   # Composant racine
│   │   ├── index.jsx                 # Point d'entrée
│   │   └── router.jsx                # Configuration routes
│   │
│   ├── .env.example
│   ├── .env.local
│   ├── package.json
│   ├── tailwind.config.js
│   ├── vite.config.js               # ou webpack.config.js
│   └── README.md
│
│
├── backend/                          # 🎻 API SYMFONY 7 + ADMIN
│   ├── bin/
│   │   └── console
│   │
│   ├── config/
│   │   ├── packages/
│   │   │   ├── doctrine.yaml
│   │   │   ├── security.yaml
│   │   │   ├── lexik_jwt_authentication.yaml
│   │   │   ├── nelmio_cors.yaml
│   │   │   └── api_platform.yaml
│   │   ├── routes/
│   │   │   ├── api.yaml
│   │   │   └── admin.yaml
│   │   └── services.yaml
│   │
│   ├── public/
│   │   ├── index.php
│   │   └── uploads/                 # Médias uploadés
│   │
│   ├── src/
│   │   ├── Controller/
│   │   │   ├── Api/                 # API REST pour frontend
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── EventController.php
│   │   │   │   ├── TicketController.php
│   │   │   │   ├── OrderController.php
│   │   │   │   ├── PaymentController.php
│   │   │   │   └── UserController.php
│   │   │   │
│   │   │   └── Admin/               # Interface admin Symfony
│   │   │       ├── DashboardController.php
│   │   │       ├── EventCrudController.php
│   │   │       ├── UserCrudController.php
│   │   │       └── OrderCrudController.php
│   │   │
│   │   ├── Entity/                  # 60+ Entités Doctrine
│   │   │   ├── User.php
│   │   │   ├── Event.php
│   │   │   ├── Ticket.php
│   │   │   ├── Order.php
│   │   │   └── ...
│   │   │
│   │   ├── Repository/
│   │   │   ├── UserRepository.php
│   │   │   ├── EventRepository.php
│   │   │   └── ...
│   │   │
│   │   ├── Service/                 # Logique métier
│   │   │   ├── Auth/
│   │   │   ├── Event/
│   │   │   ├── Ticket/
│   │   │   ├── Payment/
│   │   │   ├── Notification/
│   │   │   └── Statistics/
│   │   │
│   │   ├── Security/
│   │   │   ├── Voter/
│   │   │   └── UserProvider.php
│   │   │
│   │   ├── EventListener/
│   │   ├── Command/
│   │   ├── DataFixtures/
│   │   └── Kernel.php
│   │
│   ├── templates/                   # Templates Twig (Admin)
│   │   ├── admin/
│   │   │   ├── dashboard.html.twig
│   │   │   ├── events.html.twig
│   │   │   └── users.html.twig
│   │   └── base.html.twig
│   │
│   ├── tests/
│   │   ├── Unit/
│   │   ├── Integration/
│   │   └── Api/
│   │
│   ├── var/                         # Cache, logs
│   ├── vendor/                      # Dépendances Composer
│   ├── .env
│   ├── .env.local
│   ├── composer.json
│   └── README.md
│
│
├── database/                         # 📊 BASE DE DONNÉES
│   ├── schema.sql                   # ✅ Créé
│   ├── triggers.sql                 # ✅ Créé
│   ├── procedures.sql               # ✅ Créé
│   ├── seeds.sql                    # ✅ Créé
│   ├── indexes_optimization.sql     # ✅ Créé
│   ├── CONCEPTION_SQL.md            # ✅ Créé
│   ├── MIGRATION_GUIDE.md           # ✅ Créé
│   ├── UML_DIAGRAMS.md              # ✅ Créé
│   └── README.md                    # ✅ Créé
│
├── docs/                            # 📚 DOCUMENTATION
│   ├── api/                         # Documentation API
│   │   ├── openapi.yaml
│   │   └── postman_collection.json
│   ├── deployment/                  # Guide déploiement
│   └── user-guide/                  # Guide utilisateur
│
├── docker/                          # 🐳 DOCKER (optionnel)
│   ├── docker-compose.yml
│   ├── nginx/
│   ├── php/
│   └── mysql/
│
├── .gitignore
├── README.md                        # ✅ Créé
├── PROJECT_ARCHITECTURE.md          # Ce fichier
└── SETUP_GUIDE.md                   # Guide d'installation
```

---

## 🔄 Flux de Communication

```
┌─────────────────────────────────────────────────────────────┐
│                    ARCHITECTURE SYSTÈME                     │
└─────────────────────────────────────────────────────────────┘

┌──────────────────┐
│  UTILISATEURS    │
│  (Navigateur)    │
└────────┬─────────┘
         │
         │ HTTPS
         v
┌──────────────────────────────────────┐
│      FRONTEND (React.js)             │
│      Port: 3000                      │
│                                      │
│  - SPA (Single Page App)             │
│  - React Router                      │
│  - Redux/Zustand Store               │
│  - Axios pour API calls              │
│  - Tailwind CSS / Material-UI        │
└────────┬─────────────────────────────┘
         │
         │ HTTP/REST
         │ (API calls)
         v
┌──────────────────────────────────────┐
│   BACKEND (Symfony 7)                │
│   Port: 8000                         │
│                                      │
│  API REST (/api/*)                   │
│  ├─ /api/auth/*                      │
│  ├─ /api/events/*                    │
│  ├─ /api/tickets/*                   │
│  ├─ /api/orders/*                    │
│  └─ /api/users/*                     │
│                                      │
│  Admin Interface (/admin/*)          │
│  ├─ Dashboard                        │
│  ├─ CRUD Events                      │
│  ├─ CRUD Users                       │
│  └─ Reports                          │
└────────┬─────────────────────────────┘
         │
         │ Doctrine ORM
         v
┌──────────────────────────────────────┐
│      MySQL 8.0                       │
│      Port: 3306                      │
│                                      │
│  - 60+ tables                        │
│  - Triggers automatiques             │
│  - Procédures stockées               │
└──────────────────────────────────────┘
```

---

## 🚀 Avantages de Cette Architecture

### 1. **Frontend React.js (Module Utilisateur)**

✅ **Expérience Utilisateur Exceptionnelle**
- Navigation ultra-rapide (SPA)
- Interactivité en temps réel
- Animations fluides
- Progressive Web App (PWA) possible

✅ **Performance**
- Virtual DOM de React
- Code splitting automatique
- Lazy loading des composants
- Build optimisé pour production

✅ **Écosystème Riche**
- Composants UI prêts (Material-UI, Ant Design)
- State management (Redux, Zustand)
- Routing (React Router)
- Forms (React Hook Form, Formik)

### 2. **Backend Symfony 7 (API + Admin)**

✅ **API REST Professionnelle**
- Endpoints sécurisés (JWT)
- Validation automatique
- Sérialisation JSON
- Documentation auto-générée (OpenAPI)

✅ **Interface Admin Puissante**
- EasyAdmin pour CRUD rapide
- Tableaux de bord personnalisables
- Gestion des utilisateurs
- Rapports et statistiques

✅ **Logique Métier Robuste**
- Services réutilisables
- Events & Listeners
- Commands pour tâches CRON
- Tests unitaires & fonctionnels

---

## 🔐 Sécurité & Authentification

### Workflow JWT

```
┌──────────────┐
│   React.js   │
└──────┬───────┘
       │
       │ 1. POST /api/auth/login
       │    { email, password }
       v
┌──────────────┐
│  Symfony 7   │
│              │
│  Vérifie     │
│  credentials │
└──────┬───────┘
       │
       │ 2. Retourne JWT + Refresh Token
       │    { token, refresh_token }
       v
┌──────────────┐
│   React.js   │
│              │
│  Stocke dans │
│  localStorage│
│  ou cookies  │
└──────┬───────┘
       │
       │ 3. Requêtes suivantes
       │    Authorization: Bearer {token}
       v
┌──────────────┐
│  Symfony 7   │
│              │
│  Valide JWT  │
│  Retourne    │
│  données     │
└──────────────┘
```

---

## 🛠️ Variables d'Environnement

### Frontend (.env.local)

```env
# API Backend URL
REACT_APP_API_URL=http://localhost:8000/api
REACT_APP_API_TIMEOUT=10000

# OAuth
REACT_APP_GOOGLE_CLIENT_ID=your_client_id
REACT_APP_FACEBOOK_APP_ID=your_app_id

# Features
REACT_APP_ENABLE_DARK_MODE=true
REACT_APP_ENABLE_PWA=true

# Environment
NODE_ENV=development
```

### Backend (.env.local)

```env
# Database
DATABASE_URL="mysql://root:password@127.0.0.1:3306/aiolia_event?serverVersion=8.0&charset=utf8mb4"

# CORS (pour React)
CORS_ALLOW_ORIGIN=^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$

# JWT
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_passphrase

# Redis
REDIS_URL=redis://localhost:6379

# Email
MAILER_DSN=smtp://user:pass@smtp.gmail.com:587

# AWS S3
AWS_S3_KEY=your_key
AWS_S3_SECRET=your_secret
AWS_S3_BUCKET=aiolia-event-bucket

# Mobile Money
ORANGE_MONEY_API_KEY=your_key
AIRTEL_MONEY_API_KEY=your_key
TELMA_MONEY_API_KEY=your_key

# Environment
APP_ENV=dev
APP_SECRET=your_app_secret
```

---

## 📦 Déploiement

### Frontend (React.js)

```bash
# Build production
cd frontend
npm run build

# Déployer sur :
# - Vercel (recommandé)
# - Netlify
# - AWS S3 + CloudFront
# - Firebase Hosting
```

### Backend (Symfony 7)

```bash
# Build production
cd backend
composer install --no-dev --optimize-autoloader
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

# Déployer sur :
# - VPS (DigitalOcean, AWS EC2)
# - Platform.sh (recommandé pour Symfony)
# - Heroku
# - Docker + Kubernetes
```

---

## ✅ Checklist de Setup

### Phase 1 : Backend
- [ ] Créer dossier `backend/`
- [ ] Installer Symfony 7
- [ ] Importer la base de données
- [ ] Créer les entités
- [ ] Configurer JWT
- [ ] Créer les contrôleurs API
- [ ] Configurer CORS

### Phase 2 : Frontend
- [ ] Créer dossier `frontend/`
- [ ] Installer React.js (Vite ou CRA)
- [ ] Configurer Tailwind CSS / Material-UI
- [ ] Créer les services API
- [ ] Configurer Redux/Zustand
- [ ] Créer les composants
- [ ] Créer les pages

### Phase 3 : Intégration
- [ ] Tester authentification
- [ ] Tester CRUD événements
- [ ] Tester paiement
- [ ] Tests E2E

---

## 🎯 Prochaine Étape

Je vais maintenant créer les guides détaillés pour :
1. ✅ Setup Backend Symfony 7
2. ✅ Setup Frontend React.js
3. ✅ Configuration CORS
4. ✅ Guide d'intégration

Voulez-vous que je commence par le **Backend** ou le **Frontend** ?


