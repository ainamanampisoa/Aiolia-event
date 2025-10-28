# 🎫 Aiolia Event

**Plateforme complète de gestion d'événements et de billetterie pour Madagascar**

[![Symfony](https://img.shields.io/badge/Symfony-7.0-000000?logo=symfony)](https://symfony.com/)
[![React](https://img.shields.io/badge/React-18+-61DAFB?logo=react)](https://reactjs.org/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql)](https://www.mysql.com/)
[![License](https://img.shields.io/badge/license-Proprietary-blue)]()

---

## 📋 Description

Aiolia Event est une **solution moderne et complète** pour la gestion d'événements, la billetterie en ligne, et l'engagement des utilisateurs. La plateforme intègre des fonctionnalités avancées comme la tarification dynamique, le paiement Mobile Money, la gamification, et un système de fidélité.

### 🏗️ Architecture Découplée

```
┌─────────────────┐        ┌─────────────────┐        ┌─────────────────┐
│   FRONTEND      │◄──────►│    BACKEND      │◄──────►│    DATABASE     │
│   React.js      │  API   │   Symfony 7     │  ORM   │    MySQL 8.0    │
│   Port: 3000    │  REST  │   Port: 8000    │Doctrine│   60+ tables    │
└─────────────────┘        └─────────────────┘        └─────────────────┘
```

**Avantages** :
- ✅ Frontend et Backend totalement découplés
- ✅ Déploiements indépendants
- ✅ Scalabilité maximale
- ✅ Équipes peuvent travailler en parallèle

### 🎯 Objectifs

- ✅ Simplifier la création et gestion d'événements
- ✅ Faciliter l'achat de billets en ligne
- ✅ Intégrer les méthodes de paiement locales (Orange Money, Airtel Money, Telma)
- ✅ Offrir une expérience utilisateur exceptionnelle
- ✅ Fournir des analytics détaillées aux organisateurs
- ✅ Engager les utilisateurs avec gamification et récompenses

---

## ✨ Fonctionnalités Principales

### 👥 Module Utilisateurs

- 🔐 Authentification JWT + OAuth (Google, Facebook)
- 👤 Gestion de profil complet (photo, infos personnelles)
- 🎭 Système de rôles (Utilisateur / Co-organisateur / Organisateur / Admin)
- 📊 Statistiques personnelles (événements assistés, dépenses)
- 💰 Historique financier détaillé avec graphiques
- 🎟️ Mes billets (à venir / passés / annulés)
- ⭐ Favoris et Wishlist
- 📜 Historique d'achat et de recherche
- 🔔 Notifications multi-canal (Email, Push, SMS)
- 👥 Événements entre amis
- 🎮 Mini-jeu "Ticket Chance"
- 🌙 Mode sombre

### 🎪 Module Événements (Utilisateurs)

- 📝 Liste et détails d'événements
- 🔍 Recherche avancée avec filtres (type, localisation, date, prix)
- 🎫 Sélection de billets (quantité, catégorie)
- 🛒 Panier persistant (même après déconnexion)
- 💳 Intégration Mobile Money (Orange, Airtel, Telma)
- 🎁 Codes promo
- 📧 Confirmation d'achat (page + email)
- 📱 Export PDF des billets avec QR code
- 🔄 Partage et transfert de billets
- 📄 Téléchargement des factures
- 📅 Vue calendrier
- 🤖 Suggestions personnalisées "Pour vous"
- 🎯 Réservation avec QR code unique

### 🎬 Module Organisateurs

- 📝 CRUD événements riches (texte, images, vidéos)
- 👤 Profil organisateur
- 📸 Upload et stockage de médias (cloud)
- 👥 Gestion d'équipe (co-organisateurs, permissions)
- 🎫 Gestion de billets en temps réel
- 📊 Quotas par catégorie avec alertes stock bas
- 💰 Configuration prix par catégorie
- 📈 Tarification dynamique automatique
- 🎁 Configuration de promotions et codes promo
- ⭐ Annonces Premium (mise en avant payante)
- 📊 Dashboard de ventes
- 📈 Statistiques avancées (taux conversion, panier moyen)
- 📉 Graphiques comparatifs
- 📧 Notifications ciblées
- 📤 Export CSV/PDF des rapports
- 🔍 Recherche multi-critères
- 📋 Gestion de liste d'attente
- ⏰ Rappels automatiques
- 📅 Vue calendrier
- 📋 Duplication d'événement (template)
- 🌐 Support multi-langue
- 💼 Statistiques fiscales (TVA, revenus nets)

### 💎 Fonctionnalités Avancées

- 💰 **Portefeuille numérique** avec points de fidélité
- 🎁 **Programme de parrainage** avec récompenses
- 🎮 **Mini-jeu gamifié** pour gagner des réductions
- 📊 **Analytics en temps réel**
- 🔔 **Notifications intelligentes**
- 🎯 **Recommandations personnalisées**
- ⏳ **Liste d'attente automatique**
- 🔄 **Transfert de billets**
- 📈 **Tarification dynamique**

---

## 🏗️ Architecture

### 📁 Structure du Projet

```
Aiolia-event/
├── frontend/                  ⚛️ React.js Application (Module Utilisateur)
│   ├── src/
│   │   ├── components/       # Composants réutilisables
│   │   ├── pages/            # Pages principales
│   │   ├── services/         # Services API
│   │   ├── store/            # Redux Store
│   │   └── hooks/            # Custom Hooks
│   ├── public/
│   └── package.json
│
├── backend/                   🎻 Symfony 7 API + Admin
│   ├── src/
│   │   ├── Controller/       # API & Admin
│   │   ├── Entity/           # 60+ Entités Doctrine
│   │   ├── Repository/       # Repositories
│   │   ├── Service/          # Services métier
│   │   └── Security/         # Authentification
│   ├── config/
│   ├── templates/            # Templates Twig (Admin)
│   └── composer.json
│
├── database/                  📊 SQL & Documentation
│   ├── schema.sql            # 60+ tables
│   ├── triggers.sql          # 30+ triggers
│   ├── procedures.sql        # 15+ procédures
│   ├── seeds.sql             # Données de test
│   └── *.md                  # Documentation complète
│
└── docs/                      📚 Documentation
    ├── PROJECT_ARCHITECTURE.md
    ├── BACKEND_SETUP.md
    ├── FRONTEND_SETUP.md
    └── QUICK_START.md
```

### Stack Technologique

#### Backend (Symfony 7)
- **Language** : PHP 8.2+
- **Framework** : Symfony 7.0
- **ORM** : Doctrine
- **API** : REST + JWT Authentication
- **Admin** : EasyAdmin Bundle
- **Base de données** : MySQL 8.0+
- **Cache** : Redis
- **Queue** : Symfony Messenger
- **Storage** : AWS S3 (Flysystem)

#### Frontend (React.js)
- **Framework** : React 18+
- **Build Tool** : Vite
- **UI Library** : Tailwind CSS + Material-UI
- **State Management** : Redux Toolkit
- **Routing** : React Router v6
- **HTTP Client** : Axios
- **Charts** : Recharts
- **Forms** : React Hook Form

#### Mobile (Future)
- **Framework** : React Native
- **Notifications** : Firebase Cloud Messaging

#### DevOps
- **Containerisation** : Docker
- **CI/CD** : GitHub Actions
- **Monitoring** : Grafana + Prometheus
- **Logs** : ELK Stack

### Architecture de la Base de Données

📊 **Architecture complète documentée** dans [`/database/CONCEPTION_SQL.md`](database/CONCEPTION_SQL.md)

#### Modules Principaux (20 modules)

1. ✅ Authentification & Utilisateurs
2. ✅ Catégories & Événements
3. ✅ Billets & QR Codes
4. ✅ Codes Promo
5. ✅ Commandes & Paiements
6. ✅ Panier d'achat
7. ✅ Favoris & Interactions
8. ✅ Portefeuille & Fidélité
9. ✅ Parrainage
10. ✅ Mini-Jeu
11. ✅ Social (Amis)
12. ✅ Liste d'Attente
13. ✅ Notifications
14. ✅ Avis & Évaluations
15. ✅ Statistiques
16. ✅ Rapports
17. ✅ Audit & Logs
18. ✅ Configuration
19. ✅ Multi-langue
20. ✅ Vues Optimisées

#### Statistiques

- **60+ tables** structurées
- **30+ triggers** automatiques
- **15+ procédures stockées** pour la logique métier
- **100+ contraintes** de clés étrangères
- **Index optimisés** pour performances maximales

---

## 🚀 Installation Rapide (15 minutes)

### Prérequis

- **MySQL** 8.0+ ou MariaDB 10.5+
- **PHP** 8.2+ avec extensions (xml, mbstring, curl, zip, intl, redis, gd, bcmath)
- **Composer** 2.5+
- **Node.js** 18+ (LTS)
- **npm** 9+ ou **yarn**
- **Redis** (optionnel mais recommandé)

### 📚 Guide Complet

👉 **[QUICK_START.md](QUICK_START.md)** - Guide détaillé pas à pas

### Installation Express

#### 1. Base de Données (5 min)

```bash
# Cloner le repository
git clone https://github.com/votre-org/aiolia-event.git
cd aiolia-event

# Créer et importer la base de données
mysql -u root -p -e "CREATE DATABASE aiolia_event CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p < database/schema.sql
mysql -u root -p aiolia_event < database/triggers.sql
mysql -u root -p aiolia_event < database/procedures.sql
mysql -u root -p aiolia_event < database/seeds.sql
```

#### 2. Backend Symfony 7 (5 min)

```bash
# Installer Symfony
mkdir backend && cd backend
symfony new . --version=7.0 --webapp

# Installer les dépendances essentielles
composer require symfony/orm-pack symfony/maker-bundle --dev
composer require lexik/jwt-authentication-bundle
composer require nelmio/cors-bundle easycorp/easyadmin-bundle

# Configurer
cp .env .env.local
# Éditer .env.local avec vos paramètres

# Générer clés JWT
mkdir -p config/jwt
openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout

# Générer les entités depuis la BDD
php bin/console doctrine:mapping:import "App\Entity" attribute
php bin/console make:entity --regenerate App

# Lancer
symfony server:start -d
```

**✅ Backend disponible sur http://localhost:8000**

#### 3. Frontend React.js (5 min)

```bash
cd ..
mkdir frontend && cd frontend

# Créer le projet React avec Vite
npm create vite@latest . -- --template react
npm install

# Installer les dépendances essentielles
npm install react-router-dom axios @reduxjs/toolkit react-redux
npm install -D tailwindcss postcss autoprefixer
npx tailwindcss init -p

# Configurer
echo "REACT_APP_API_URL=http://localhost:8000/api" > .env.local

# Lancer
npm run dev
```

**✅ Frontend disponible sur http://localhost:5173**

### 🎉 C'est Prêt !

```
✅ Backend API : http://localhost:8000
✅ Frontend App : http://localhost:5173
✅ Admin Panel : http://localhost:8000/admin
```

### 📖 Guides Détaillés

| Guide | Description |
|-------|-------------|
| [PROJECT_ARCHITECTURE.md](PROJECT_ARCHITECTURE.md) | Architecture complète du projet |
| [BACKEND_SETUP.md](BACKEND_SETUP.md) | Installation détaillée du backend |
| [FRONTEND_SETUP.md](FRONTEND_SETUP.md) | Installation détaillée du frontend |
| [QUICK_START.md](QUICK_START.md) | Guide de démarrage rapide |

---

## 📖 Documentation

### Documentation Complète

- 📊 **[Conception SQL Complète](database/CONCEPTION_SQL.md)** - Architecture détaillée de la BDD
- 🗂️ **[README Database](database/README.md)** - Guide d'installation et maintenance
- 🔄 **[Guide de Migration](database/MIGRATION_GUIDE.md)** - Procédures de migration
- ⚡ **[Optimisation & Index](database/indexes_optimization.sql)** - Performance tuning

### Scripts SQL

| Fichier | Description |
|---------|-------------|
| `schema.sql` | Schéma complet (60+ tables) |
| `triggers.sql` | 30+ triggers automatiques |
| `procedures.sql` | 15+ procédures stockées |
| `seeds.sql` | Données de base et de test |
| `indexes_optimization.sql` | Index de performance |

---

## 🧪 Tests

### Tests de la Base de Données

```bash
# Tester les procédures stockées
mysql -u root -p aiolia_event < tests/test_procedures.sql

# Tester les triggers
mysql -u root -p aiolia_event < tests/test_triggers.sql
```

### Tests de l'API

```bash
# Tests unitaires
npm run test

# Tests d'intégration
npm run test:integration

# Tests E2E
npm run test:e2e

# Coverage
npm run test:coverage
```

---

## 🔒 Sécurité

### Mesures de Sécurité Implémentées

- ✅ **Mots de passe hashés** avec bcrypt (10+ rounds)
- ✅ **JWT avec expiration** courte (15 min)
- ✅ **Refresh tokens révocables**
- ✅ **OAuth 2.0** pour Google/Facebook
- ✅ **Permissions granulaires** par rôle
- ✅ **Logs d'audit** complets
- ✅ **Rate limiting** sur API
- ✅ **Input validation** côté serveur
- ✅ **HTTPS obligatoire** en production
- ✅ **CORS configuré** correctement
- ✅ **SQL injection** protection (requêtes préparées)
- ✅ **XSS protection**
- ✅ **CSRF tokens**

### Conformité RGPD

- ✅ Droit à l'oubli
- ✅ Export des données utilisateur
- ✅ Consentement explicite
- ✅ Anonymisation des logs

---

## 📈 Performance

### Optimisations Implémentées

- ✅ **Index stratégiques** sur toutes les requêtes fréquentes
- ✅ **Statistiques pré-calculées** pour éviter calculs lourds
- ✅ **Vues optimisées** pour requêtes complexes
- ✅ **Cache Redis** pour données chaudes
- ✅ **CDN** pour médias statiques
- ✅ **Lazy loading** pour images
- ✅ **Pagination** systématique
- ✅ **Compression** des réponses API

### Benchmarks Cibles

- 🎯 **Temps de réponse API** : < 200ms (95th percentile)
- 🎯 **Temps de chargement page** : < 2s
- 🎯 **Concurrent users** : 10,000+
- 🎯 **Transactions/seconde** : 1,000+

---

## 🛠️ Maintenance

### Jobs CRON Recommandés

```bash
# Recalcul des statistiques (chaque heure)
0 * * * * php artisan stats:calculate

# Statistiques quotidiennes (chaque jour à 1h)
0 1 * * * php artisan stats:daily

# Nettoyage des paniers expirés (toutes les 15 min)
*/15 * * * * php artisan cart:cleanup

# Notifications de rappel (chaque jour à 10h)
0 10 * * * php artisan notifications:event-reminders

# Archivage des logs (chaque mois)
0 2 1 * * php artisan logs:archive
```

### Backup Automatique

```bash
# Backup quotidien à 2h du matin
0 2 * * * /usr/local/bin/backup-database.sh
```

---

## 🤝 Contribution

Nous accueillons les contributions ! Veuillez suivre ces étapes :

1. **Fork** le projet
2. **Créer** une branche feature (`git checkout -b feature/AmazingFeature`)
3. **Commit** vos changements (`git commit -m 'Add AmazingFeature'`)
4. **Push** vers la branche (`git push origin feature/AmazingFeature`)
5. **Ouvrir** une Pull Request

### Guidelines

- ✅ Code propre et commenté
- ✅ Tests unitaires pour nouvelles fonctionnalités
- ✅ Documentation mise à jour
- ✅ Respecter les conventions de nommage
- ✅ Pas de code commenté dans les commits

---

## 📊 Roadmap

### Phase 1 : MVP (3 mois)
- [x] Conception de la base de données
- [ ] API Backend (authentification, événements, billets)
- [ ] Interface utilisateur (recherche, achat, profil)
- [ ] Intégration paiement Mobile Money
- [ ] Dashboard organisateur basique

### Phase 2 : Fonctionnalités Avancées (3 mois)
- [ ] Tarification dynamique
- [ ] Programme de fidélité
- [ ] Mini-jeu gamifié
- [ ] Notifications multi-canal
- [ ] Analytics avancées
- [ ] Application mobile (React Native)

### Phase 3 : Scale & Optimisation (2 mois)
- [ ] Optimisation performances
- [ ] Mise en cache avancée
- [ ] CDN global
- [ ] Multi-région
- [ ] Tests de charge

### Phase 4 : Fonctionnalités Premium (ongoing)
- [ ] Live streaming d'événements
- [ ] Chat en direct
- [ ] Réalité augmentée pour visualisation
- [ ] Blockchain pour billets NFT
- [ ] IA pour recommandations

---

## 📞 Support

### Contacts

- 📧 **Email** : support@aiolia-event.com
- 🌐 **Website** : https://aiolia-event.com
- 💬 **Discord** : https://discord.gg/aiolia-event
- 🐛 **Issues** : https://github.com/aiolia-event/aiolia-event/issues

### Documentation

- 📚 **Wiki** : https://github.com/aiolia-event/wiki
- 📖 **API Docs** : https://api.aiolia-event.com/docs
- 🎓 **Tutorials** : https://aiolia-event.com/tutorials

---

## 👥 Équipe

- **Project Lead** : [Votre Nom]
- **Backend Lead** : [Nom]
- **Frontend Lead** : [Nom]
- **DevOps** : [Nom]
- **UI/UX Designer** : [Nom]

---

## 📄 Licence

© 2025 Aiolia Event. Tous droits réservés.

Ce projet est sous licence propriétaire. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

---

## 🙏 Remerciements

- Tous les contributeurs qui ont participé au projet
- La communauté open-source pour les outils utilisés
- Les utilisateurs pour leurs retours et suggestions

---

## 📊 Statistiques du Projet

![GitHub stars](https://img.shields.io/github/stars/aiolia-event/aiolia-event?style=social)
![GitHub forks](https://img.shields.io/github/forks/aiolia-event/aiolia-event?style=social)
![GitHub issues](https://img.shields.io/github/issues/aiolia-event/aiolia-event)
![GitHub pull requests](https://img.shields.io/github/issues-pr/aiolia-event/aiolia-event)
![License](https://img.shields.io/badge/license-Proprietary-blue)

---

**Fait avec ❤️ à Madagascar pour Madagascar** 🇲🇬

---

## 🎨 Captures d'Écran

_(À ajouter une fois l'interface développée)_

---

**Dernière mise à jour** : Octobre 2025  
**Version** : 1.0.0
sudo lsof -i :5173
sudo kill -9 12345
