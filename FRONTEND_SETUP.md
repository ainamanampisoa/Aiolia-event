# ⚛️ Guide d'Installation Frontend - React.js

Ce guide détaille l'installation et la configuration du frontend React.js pour Aiolia Event.

---

## 📋 Prérequis

- **Node.js** : 18+ (LTS recommandé)
- **npm** : 9+ ou **yarn** : 1.22+
- **Git**

```bash
# Vérifier les versions
node --version  # v18.0.0+
npm --version   # 9.0.0+
```

---

## 🚀 Installation

### 1. Créer le Projet React

```bash
cd /home/aina/Documents/MyProject/Aiolia-event
mkdir frontend
cd frontend

# Option 1 : Vite (RECOMMANDÉ - ultra rapide)
npm create vite@latest . -- --template react
npm install

# Option 2 : Create React App (classique)
npx create-react-app .

# Option 3 : Next.js (si SEO important)
npx create-next-app@latest .
```

---

## 📦 Installer les Dépendances

```bash
# Routing
npm install react-router-dom

# State Management
npm install @reduxjs/toolkit react-redux
# OU
npm install zustand

# HTTP Client
npm install axios

# UI Framework (choisir un)
npm install @mui/material @mui/icons-material @emotion/react @emotion/styled
# OU
npm install antd

# Forms & Validation
npm install react-hook-form yup @hookform/resolvers

# Date & Time
npm install date-fns

# Styling
npm install -D tailwindcss postcss autoprefixer
npx tailwindcss init -p

# Icons
npm install react-icons

# Notifications
npm install react-hot-toast
# OU
npm install react-toastify

# QR Code
npm install qrcode.react

# Charts (pour statistiques)
npm install recharts

# Image Upload
npm install react-dropzone

# PDF Generation
npm install jspdf html2canvas

# Utilities
npm install lodash
npm install classnames

# Development
npm install -D @types/react @types/react-dom
npm install -D eslint-plugin-react eslint-plugin-react-hooks
npm install -D prettier eslint-config-prettier
```

---

## ⚙️ Configuration

### 1. Variables d'Environnement

Créer `.env.local` :

```env
# API Backend
REACT_APP_API_URL=http://localhost:8000/api
REACT_APP_API_TIMEOUT=10000

# OAuth
REACT_APP_GOOGLE_CLIENT_ID=your_client_id
REACT_APP_FACEBOOK_APP_ID=your_app_id

# Features
REACT_APP_ENABLE_DARK_MODE=true
REACT_APP_ENABLE_PWA=true

# Stripe (si paiement par carte)
REACT_APP_STRIPE_PUBLIC_KEY=pk_test_xxx

# Environment
NODE_ENV=development
```

### 2. Configuration Tailwind CSS

**tailwind.config.js** :

```javascript
/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#f0f9ff',
          100: '#e0f2fe',
          200: '#bae6fd',
          300: '#7dd3fc',
          400: '#38bdf8',
          500: '#0ea5e9',
          600: '#0284c7',
          700: '#0369a1',
          800: '#075985',
          900: '#0c4a6e',
        },
        secondary: {
          // Vos couleurs secondaires
        }
      },
      fontFamily: {
        sans: ['Inter', 'sans-serif'],
      },
    },
  },
  plugins: [],
}
```

**src/index.css** :

```css
@tailwind base;
@tailwind components;
@tailwind utilities;

@layer base {
  body {
    @apply font-sans antialiased;
  }
}

@layer components {
  .btn-primary {
    @apply bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors;
  }
  
  .card {
    @apply bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow;
  }
}
```

---

## 🏗️ Structure du Projet

Créer la structure suivante :

```bash
cd src
mkdir -p components/{common,Event,Ticket,Cart,User,Auth}
mkdir -p pages/{Home,Events,Auth,User,Checkout}
mkdir -p services
mkdir -p store/{slices,actions}
mkdir -p hooks
mkdir -p utils
mkdir -p styles
mkdir -p assets/{images,icons}
```

---

## 🔌 Configuration API (Axios)

### src/services/api.js

```javascript
import axios from 'axios';

const API_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000/api';

const api = axios.create({
  baseURL: API_URL,
  timeout: parseInt(process.env.REACT_APP_API_TIMEOUT) || 10000,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Request interceptor (ajouter le token JWT)
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor (gérer les erreurs)
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error.response?.status === 401) {
      // Token expiré, rediriger vers login
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export default api;
```

### src/services/auth.service.js

```javascript
import api from './api';

export const authService = {
  async register(data) {
    const response = await api.post('/auth/register', data);
    return response.data;
  },

  async login(email, password) {
    const response = await api.post('/auth/login', { email, password });
    if (response.data.token) {
      localStorage.setItem('token', response.data.token);
      localStorage.setItem('user', JSON.stringify(response.data.user));
    }
    return response.data;
  },

  async logout() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
  },

  async getCurrentUser() {
    const response = await api.get('/auth/me');
    return response.data;
  },

  getToken() {
    return localStorage.getItem('token');
  },

  getUser() {
    const user = localStorage.getItem('user');
    return user ? JSON.parse(user) : null;
  },

  isAuthenticated() {
    return !!this.getToken();
  },
};
```

### src/services/event.service.js

```javascript
import api from './api';

export const eventService = {
  async getEvents(filters = {}) {
    const params = new URLSearchParams(filters).toString();
    const response = await api.get(`/events?${params}`);
    return response.data;
  },

  async getEvent(slug) {
    const response = await api.get(`/events/${slug}`);
    return response.data;
  },

  async createEvent(data) {
    const response = await api.post('/events', data);
    return response.data;
  },

  async updateEvent(id, data) {
    const response = await api.put(`/events/${id}`, data);
    return response.data;
  },

  async deleteEvent(id) {
    const response = await api.delete(`/events/${id}`);
    return response.data;
  },

  async searchEvents(query) {
    const response = await api.get(`/events?q=${encodeURIComponent(query)}`);
    return response.data;
  },
};
```

---

## 🗃️ State Management (Redux Toolkit)

### src/store/index.js

```javascript
import { configureStore } from '@reduxjs/toolkit';
import authReducer from './slices/authSlice';
import cartReducer from './slices/cartSlice';
import eventReducer from './slices/eventSlice';

export const store = configureStore({
  reducer: {
    auth: authReducer,
    cart: cartReducer,
    events: eventReducer,
  },
});

export default store;
```

### src/store/slices/authSlice.js

```javascript
import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import { authService } from '../../services/auth.service';

export const login = createAsyncThunk(
  'auth/login',
  async ({ email, password }, { rejectWithValue }) => {
    try {
      return await authService.login(email, password);
    } catch (error) {
      return rejectWithValue(error.response?.data);
    }
  }
);

export const register = createAsyncThunk(
  'auth/register',
  async (userData, { rejectWithValue }) => {
    try {
      return await authService.register(userData);
    } catch (error) {
      return rejectWithValue(error.response?.data);
    }
  }
);

const authSlice = createSlice({
  name: 'auth',
  initialState: {
    user: authService.getUser(),
    token: authService.getToken(),
    isAuthenticated: authService.isAuthenticated(),
    loading: false,
    error: null,
  },
  reducers: {
    logout: (state) => {
      authService.logout();
      state.user = null;
      state.token = null;
      state.isAuthenticated = false;
    },
    clearError: (state) => {
      state.error = null;
    },
  },
  extraReducers: (builder) => {
    builder
      // Login
      .addCase(login.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(login.fulfilled, (state, action) => {
        state.loading = false;
        state.user = action.payload.user;
        state.token = action.payload.token;
        state.isAuthenticated = true;
      })
      .addCase(login.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload?.message || 'Login failed';
      })
      // Register
      .addCase(register.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(register.fulfilled, (state, action) => {
        state.loading = false;
      })
      .addCase(register.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload?.message || 'Registration failed';
      });
  },
});

export const { logout, clearError } = authSlice.actions;
export default authSlice.reducer;
```

### src/store/slices/cartSlice.js

```javascript
import { createSlice } from '@reduxjs/toolkit';

const cartSlice = createSlice({
  name: 'cart',
  initialState: {
    items: JSON.parse(localStorage.getItem('cart')) || [],
    total: 0,
  },
  reducers: {
    addToCart: (state, action) => {
      const existingItem = state.items.find(
        item => item.ticketCategoryId === action.payload.ticketCategoryId
      );
      
      if (existingItem) {
        existingItem.quantity += action.payload.quantity;
      } else {
        state.items.push(action.payload);
      }
      
      state.total = state.items.reduce(
        (sum, item) => sum + (item.price * item.quantity), 
        0
      );
      
      localStorage.setItem('cart', JSON.stringify(state.items));
    },
    
    removeFromCart: (state, action) => {
      state.items = state.items.filter(
        item => item.ticketCategoryId !== action.payload
      );
      
      state.total = state.items.reduce(
        (sum, item) => sum + (item.price * item.quantity), 
        0
      );
      
      localStorage.setItem('cart', JSON.stringify(state.items));
    },
    
    updateQuantity: (state, action) => {
      const item = state.items.find(
        item => item.ticketCategoryId === action.payload.ticketCategoryId
      );
      
      if (item) {
        item.quantity = action.payload.quantity;
      }
      
      state.total = state.items.reduce(
        (sum, item) => sum + (item.price * item.quantity), 
        0
      );
      
      localStorage.setItem('cart', JSON.stringify(state.items));
    },
    
    clearCart: (state) => {
      state.items = [];
      state.total = 0;
      localStorage.removeItem('cart');
    },
  },
});

export const { addToCart, removeFromCart, updateQuantity, clearCart } = cartSlice.actions;
export default cartSlice.reducer;
```

---

## 🪝 Custom Hooks

### src/hooks/useAuth.js

```javascript
import { useSelector, useDispatch } from 'react-redux';
import { login, logout, register } from '../store/slices/authSlice';

export const useAuth = () => {
  const dispatch = useDispatch();
  const { user, isAuthenticated, loading, error } = useSelector((state) => state.auth);

  return {
    user,
    isAuthenticated,
    loading,
    error,
    login: (credentials) => dispatch(login(credentials)),
    logout: () => dispatch(logout()),
    register: (userData) => dispatch(register(userData)),
  };
};
```

### src/hooks/useCart.js

```javascript
import { useSelector, useDispatch } from 'react-redux';
import { addToCart, removeFromCart, updateQuantity, clearCart } from '../store/slices/cartSlice';

export const useCart = () => {
  const dispatch = useDispatch();
  const { items, total } = useSelector((state) => state.cart);

  return {
    items,
    total,
    itemCount: items.reduce((sum, item) => sum + item.quantity, 0),
    addToCart: (item) => dispatch(addToCart(item)),
    removeFromCart: (id) => dispatch(removeFromCart(id)),
    updateQuantity: (id, quantity) => dispatch(updateQuantity({ ticketCategoryId: id, quantity })),
    clearCart: () => dispatch(clearCart()),
  };
};
```

---

## 🎨 Composants Exemples

### src/components/common/Navbar.jsx

```jsx
import React from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../../hooks/useAuth';
import { useCart } from '../../hooks/useCart';

export const Navbar = () => {
  const { user, isAuthenticated, logout } = useAuth();
  const { itemCount } = useCart();

  return (
    <nav className="bg-white shadow-lg">
      <div className="container mx-auto px-4">
        <div className="flex justify-between items-center h-16">
          <Link to="/" className="text-2xl font-bold text-primary-600">
            Aiolia Event
          </Link>

          <div className="flex items-center space-x-6">
            <Link to="/events" className="hover:text-primary-600">
              Événements
            </Link>

            {isAuthenticated ? (
              <>
                <Link to="/cart" className="relative">
                  🛒
                  {itemCount > 0 && (
                    <span className="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs">
                      {itemCount}
                    </span>
                  )}
                </Link>
                <Link to="/profile" className="hover:text-primary-600">
                  {user?.first_name || 'Profil'}
                </Link>
                <button onClick={logout} className="btn-primary">
                  Déconnexion
                </button>
              </>
            ) : (
              <>
                <Link to="/login" className="hover:text-primary-600">
                  Connexion
                </Link>
                <Link to="/register" className="btn-primary">
                  Inscription
                </Link>
              </>
            )}
          </div>
        </div>
      </div>
    </nav>
  );
};
```

### src/components/Event/EventCard.jsx

```jsx
import React from 'react';
import { Link } from 'react-router-dom';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';

export const EventCard = ({ event }) => {
  return (
    <Link to={`/events/${event.slug}`} className="card group">
      <div className="aspect-video overflow-hidden rounded-t-lg -mx-6 -mt-6 mb-4">
        <img
          src={event.image || '/placeholder-event.jpg'}
          alt={event.title}
          className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
        />
      </div>

      <div className="space-y-2">
        <h3 className="text-xl font-semibold group-hover:text-primary-600 transition-colors">
          {event.title}
        </h3>

        <p className="text-gray-600 line-clamp-2">
          {event.short_description}
        </p>

        <div className="flex items-center text-sm text-gray-500">
          <span>📅</span>
          <span className="ml-2">
            {format(new Date(event.start_date), 'PPP', { locale: fr })}
          </span>
        </div>

        <div className="flex items-center text-sm text-gray-500">
          <span>📍</span>
          <span className="ml-2">{event.location}</span>
        </div>

        <div className="flex justify-between items-center pt-4 border-t">
          <span className="text-lg font-bold text-primary-600">
            {event.min_price ? `À partir de ${event.min_price.toLocaleString()} MGA` : 'Gratuit'}
          </span>
          <span className="text-sm text-gray-500">
            {event.available_tickets} billets disponibles
          </span>
        </div>
      </div>
    </Link>
  );
};
```

---

## 🛣️ Router Configuration

### src/App.jsx

```jsx
import React from 'react';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import { Provider } from 'react-redux';
import { Toaster } from 'react-hot-toast';
import { store } from './store';

// Layout
import { Navbar } from './components/common/Navbar';
import { Footer } from './components/common/Footer';

// Pages
import { Home } from './pages/Home';
import { EventsPage } from './pages/Events/EventsPage';
import { EventDetailPage } from './pages/Events/EventDetailPage';
import { LoginPage } from './pages/Auth/LoginPage';
import { RegisterPage } from './pages/Auth/RegisterPage';
import { ProfilePage } from './pages/User/ProfilePage';
import { MyTicketsPage } from './pages/User/MyTicketsPage';
import { CartPage } from './pages/Checkout/CartPage';
import { CheckoutPage } from './pages/Checkout/CheckoutPage';
import { NotFound } from './pages/NotFound';

// Protected Route
import { ProtectedRoute } from './components/common/ProtectedRoute';

function App() {
  return (
    <Provider store={store}>
      <BrowserRouter>
        <div className="flex flex-col min-h-screen">
          <Navbar />
          
          <main className="flex-grow">
            <Routes>
              <Route path="/" element={<Home />} />
              <Route path="/events" element={<EventsPage />} />
              <Route path="/events/:slug" element={<EventDetailPage />} />
              <Route path="/login" element={<LoginPage />} />
              <Route path="/register" element={<RegisterPage />} />
              
              {/* Protected Routes */}
              <Route element={<ProtectedRoute />}>
                <Route path="/profile" element={<ProfilePage />} />
                <Route path="/my-tickets" element={<MyTicketsPage />} />
                <Route path="/cart" element={<CartPage />} />
                <Route path="/checkout" element={<CheckoutPage />} />
              </Route>
              
              <Route path="*" element={<NotFound />} />
            </Routes>
          </main>
          
          <Footer />
        </div>
        
        <Toaster position="top-right" />
      </BrowserRouter>
    </Provider>
  );
}

export default App;
```

---

## 🚀 Lancer l'Application

```bash
# Développement
npm run dev
# ou
npm start

# Build production
npm run build

# Preview du build
npm run preview
```

L'application sera disponible sur `http://localhost:3000` (ou 5173 avec Vite)

---

## ✅ Checklist

- [ ] React installé
- [ ] Dépendances installées
- [ ] Tailwind CSS configuré
- [ ] Services API créés
- [ ] Redux Store configuré
- [ ] Composants de base créés
- [ ] Router configuré
- [ ] Connexion avec backend testée

---

**Frontend prêt !** 🎉 Passez maintenant à l'[intégration Backend/Frontend](INTEGRATION_GUIDE.md)


