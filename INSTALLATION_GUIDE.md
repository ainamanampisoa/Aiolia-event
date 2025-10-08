# 🚀 Guide d'Installation - Aiolia Event

## 📋 Architecture du Projet

```
┌──────────────────────────────────────────────────────────────┐
│                     AIOLIA EVENT                             │
└──────────────────────────────────────────────────────────────┘

┌─────────────────────┐              ┌─────────────────────┐
│   FRONTOFFICE       │              │   BACKOFFICE        │
│   React.js          │◄────API─────►│   Symfony 7         │
│                     │    REST      │                     │
│ 👥 Utilisateurs     │              │ 👨‍💼 Admin           │
│ • Voir événements   │              │ • Interface Admin   │
│ • Acheter billets   │              │ • CRUD Tout         │
│ • Mon profil        │              │ • API REST          │
│ • Mon panier        │              │ • Statistiques      │
│                     │              │ • Rapports          │
└─────────────────────┘              └─────────────────────┘
         │                                    │
         └────────────┬───────────────────────┘
                      ▼
              ┌───────────────┐
              │   MySQL 8.0   │
              │   60+ Tables  │
              └───────────────┘
```

---

## 📁 Structure des Dossiers

```bash
Aiolia-event/
├── frontoffice/      # ⚛️ React.js (Interface Utilisateurs)
├── backoffice/       # 🎻 Symfony 7 (Admin + API)
├── database/         # 📊 SQL Scripts
└── docs/             # 📚 Documentation
```

---

## 🚀 Installation (3 étapes - 15 minutes)

### Étape 1 : Base de Données (5 min)

```bash
cd /home/aina/Documents/MyProject/Aiolia-event

# Créer la base
mysql -u root -p -e "CREATE DATABASE aiolia_event CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Importer les scripts
mysql -u root -p < database/schema.sql
mysql -u root -p aiolia_event < database/triggers.sql
mysql -u root -p aiolia_event < database/procedures.sql
mysql -u root -p aiolia_event < database/seeds.sql
```

✅ **Base de données prête** : 60+ tables, triggers, procédures

---

### Étape 2 : BackOffice Symfony 7 (5 min)

```bash
# Créer le dossier
mkdir backoffice
cd backoffice

# Installer Symfony 7
symfony new . --version=7.0 --webapp

# Installer les bundles
composer require symfony/orm-pack
composer require symfony/maker-bundle --dev
composer require lexik/jwt-authentication-bundle
composer require nelmio/cors-bundle
composer require easycorp/easyadmin-bundle
composer require endroid/qr-code

# Configurer
cp .env .env.local
```

**Éditer `.env.local`** :

```env
DATABASE_URL="mysql://root:password@127.0.0.1:3306/aiolia_event?serverVersion=8.0"
CORS_ALLOW_ORIGIN=http://localhost:3000
JWT_PASSPHRASE=your_secret_passphrase
```

```bash
# Générer les clés JWT
mkdir -p config/jwt
openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout

# Générer les entités depuis la BDD
php bin/console doctrine:mapping:import "App\Entity" attribute --path=src/Entity
php bin/console make:entity --regenerate App

# Lancer le serveur
symfony server:start -d
```

✅ **BackOffice prêt** :
- Interface Admin : http://localhost:8000/admin
- API REST : http://localhost:8000/api

---

### Étape 3 : FrontOffice React.js (5 min)

```bash
cd ..
mkdir frontoffice
cd frontoffice

# Créer le projet React avec Vite
npm create vite@latest . -- --template react
npm install

# Installer les dépendances
npm install react-router-dom axios
npm install @reduxjs/toolkit react-redux
npm install @mui/material @mui/icons-material @emotion/react @emotion/styled
npm install react-hot-toast
npm install date-fns

# OU avec Tailwind CSS
npm install -D tailwindcss postcss autoprefixer
npx tailwindcss init -p

# Configurer l'API
echo "VITE_API_URL=http://localhost:8000/api" > .env.local

# Lancer
npm run dev
```

✅ **FrontOffice prêt** : http://localhost:5173

---

## ✅ Vérification de l'Installation

### Test 1 : Base de Données

```bash
mysql -u root -p aiolia_event -e "SHOW TABLES;"
# Devrait afficher 60+ tables
```

### Test 2 : BackOffice API

```bash
# Tester l'API
curl http://localhost:8000/api/events
# Devrait retourner du JSON
```

### Test 3 : FrontOffice

Ouvrir http://localhost:5173 dans le navigateur
→ La page React devrait s'afficher

---

## 🎯 Prochaines Étapes

### BackOffice : Créer l'Interface Admin

**src/Controller/Admin/DashboardController.php** :

```php
<?php
namespace App\Controller\Admin;

use App\Entity\Event;
use App\Entity\User;
use App\Entity\Order;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Aiolia Event - Administration');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Événements', 'fa fa-calendar', Event::class);
        yield MenuItem::linkToCrud('Utilisateurs', 'fa fa-users', User::class);
        yield MenuItem::linkToCrud('Commandes', 'fa fa-shopping-cart', Order::class);
    }
}
```

Générer les CRUD :

```bash
cd backoffice
php bin/console make:admin:crud
# Choisir Event, User, Order, etc.
```

### BackOffice : Créer les API Controllers

**src/Controller/Api/EventController.php** :

```php
<?php
namespace App\Controller\Api;

use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/events', name: 'api_events_')]
class EventController extends AbstractController
{
    public function __construct(private EventRepository $eventRepository) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $events = $this->eventRepository->findAll();
        
        return $this->json($events, 200, [], ['groups' => ['event:read']]);
    }

    #[Route('/{slug}', name: 'show', methods: ['GET'])]
    public function show(string $slug): JsonResponse
    {
        $event = $this->eventRepository->findOneBy(['slug' => $slug]);
        
        if (!$event) {
            return $this->json(['error' => 'Événement non trouvé'], 404);
        }
        
        return $this->json($event, 200, [], ['groups' => ['event:read']]);
    }
}
```

### FrontOffice : Créer les Pages React

**src/App.jsx** :

```jsx
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import { Provider } from 'react-redux';
import { store } from './store';

// Pages
import Home from './pages/Home';
import EventsPage from './pages/Events/EventsPage';
import EventDetailPage from './pages/Events/EventDetailPage';
import LoginPage from './pages/Auth/LoginPage';

function App() {
  return (
    <Provider store={store}>
      <BrowserRouter>
        <Routes>
          <Route path="/" element={<Home />} />
          <Route path="/events" element={<EventsPage />} />
          <Route path="/events/:slug" element={<EventDetailPage />} />
          <Route path="/login" element={<LoginPage />} />
        </Routes>
      </BrowserRouter>
    </Provider>
  );
}

export default App;
```

**src/services/api.js** :

```javascript
import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
  timeout: 10000,
});

// Intercepteur pour ajouter le token JWT
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

export default api;
```

---

## 📊 URLs & Accès

| Service | URL | Utilisateurs |
|---------|-----|--------------|
| **FrontOffice** | http://localhost:5173 | 👥 Utilisateurs/Clients |
| **BackOffice Admin** | http://localhost:8000/admin | 👨‍💼 Admin/Organisateurs |
| **API REST** | http://localhost:8000/api | 🔌 FrontOffice React |

---

## 🔐 Comptes de Test

Après avoir exécuté `seeds.sql`, vous aurez :

| Email | Mot de passe | Rôle |
|-------|--------------|------|
| admin@aiolia-event.com | Password123! | Admin |
| organizer@aiolia-event.com | Password123! | Organisateur |
| user@aiolia-event.com | Password123! | Utilisateur |

⚠️ **À changer en production !**

---

## 📚 Documentation Complète

| Document | Description |
|----------|-------------|
| [ARCHITECTURE_FRONTOFFICE_BACKOFFICE.md](ARCHITECTURE_FRONTOFFICE_BACKOFFICE.md) | Architecture détaillée |
| [database/CONCEPTION_SQL.md](database/CONCEPTION_SQL.md) | Conception SQL |
| [database/UML_DIAGRAMS.md](database/UML_DIAGRAMS.md) | Diagrammes UML |

---

## 🎉 Félicitations !

Vous avez maintenant :

✅ Un **FrontOffice React.js** pour les utilisateurs  
✅ Un **BackOffice Symfony 7** pour l'admin + API  
✅ Une **base de données complète** (60+ tables)  
✅ Tout est prêt pour le développement !

**Bon développement !** 🚀


