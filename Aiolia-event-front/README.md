# React + Vite

This template provides a minimal setup to get React working in Vite with HMR and some ESLint rules.

Currently, two official plugins are available:

- [@vitejs/plugin-react](https://github.com/vitejs/vite-plugin-react/blob/main/packages/plugin-react) uses [Babel](https://babeljs.io/) (or [oxc](https://oxc.rs) when used in [rolldown-vite](https://vite.dev/guide/rolldown)) for Fast Refresh
- [@vitejs/plugin-react-swc](https://github.com/vitejs/vite-plugin-react/blob/main/packages/plugin-react-swc) uses [SWC](https://swc.rs/) for Fast Refresh

## React Compiler

The React Compiler is not enabled on this template because of its impact on dev & build performances. To add it, see [this documentation](https://react.dev/learn/react-compiler/installation).

## Expanding the ESLint configuration

If you are developing a production application, we recommend using TypeScript with type-aware lint rules enabled. Check out the [TS template](https://github.com/vitejs/vite/tree/main/packages/create-vite/template-react-ts) for information on how to integrate TypeScript and [`typescript-eslint`](https://typescript-eslint.io) in your project.


Aiolia-event-front/
│
├── 📁 public/                    # Fichiers statiques (accessibles directement)
│   ├── css/                      # Styles du template HTML
│   │   ├── main.css             # Style principal
│   │   ├── animate.min.css      # Animations
│   │   ├── slick.css            # Carousel
│   │   └── jquery-ui.css        # UI components
│   ├── images/                   # Toutes les images (logo, icons, etc.)
│   ├── fonts/                    # Polices Rubik
│   └── js/                       # Scripts jQuery du template
│
├── 📁 src/                       # Code source React
│   │
│   ├── 📄 main.jsx              # 🚀 Point d'entrée - démarre l'app
│   ├── 📄 App.jsx               # 🧠 Composant racine - routing principal
│   ├── 📄 App.css               # Styles de App.jsx
│   ├── 📄 index.css             # Styles globaux
│   │
│   ├── 📁 pages/                # 📄 Pages de l'application
│   │   ├── Auth/                # Pages d'authentification
│   │   │   ├── Login.jsx        # Page connexion
│   │   │   └── Register.jsx     # Page inscription
│   │   └── User/                # Pages utilisateur (à venir)
│   │       └── Profile.jsx      # TODO
│   │
│   ├── 📁 components/           # 🧩 Composants réutilisables
│   │   ├── common/              # Composants génériques
│   │   │   └── Button.jsx       # TODO: Boutons personnalisés
│   │   └── layout/              # Structure de mise en page
│   │       ├── Header.jsx       # En-tête (menu, logo, panier)
│   │       └── Footer.jsx       # Pied de page
│   │
│   ├── 📁 contexts/             # 🌐 Contextes React (état global)
│   │   └── AuthContext.jsx      # Gestion authentification globale
│   │
│   ├── 📁 services/             # 🔌 Services API
│   │   ├── api.js               # Configuration Axios + intercepteurs
│   │   └── authService.js       # API auth (login, register, logout)
│   │
│   ├── 📁 hooks/                # 🪝 Custom hooks (à venir)
│   │   └── useLocalStorage.js   # TODO: Hook pour localStorage
│   │
│   ├── 📁 utils/                # 🛠️ Utilitaires
│   │   └── validators.js        # TODO: Fonctions de validation
│   │
│   ├── 📁 styles/               # 🎨 Styles supplémentaires
│   │   └── variables.css        # TODO: Variables CSS
│   │
│   └── 📁 assets/               # 🖼️ Assets React (images locales)
│       └── react.svg
│
├── 📄 index.html                # HTML principal (charge main.jsx)
├── 📄 vite.config.js            # Configuration Vite
├── 📄 package.json              # Dépendances npm
└── 📄 .env                      # Variables d'environnement