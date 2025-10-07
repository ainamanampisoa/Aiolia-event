# 🎫 Aiolia Event

**Plateforme complète de gestion d'événements et de billetterie pour Madagascar**

---

## 📋 Description

Aiolia Event est une solution moderne et complète pour la gestion d'événements, la billetterie en ligne, et l'engagement des utilisateurs. La plateforme intègre des fonctionnalités avancées comme la tarification dynamique, le paiement Mobile Money, la gamification, et un système de fidélité.

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

### Stack Technologique Recommandée

#### Backend
- **Language** : Node.js (Express) ou PHP (Laravel) ou Python (Django/FastAPI)
- **API** : REST ou GraphQL
- **Authentication** : JWT + OAuth 2.0
- **Base de données** : MySQL 8.0+ ou MariaDB 10.5+
- **Cache** : Redis
- **Queue** : RabbitMQ ou AWS SQS
- **Storage** : AWS S3 ou Google Cloud Storage

#### Frontend
- **Framework** : React.js ou Vue.js ou Next.js
- **UI Library** : Material-UI ou Tailwind CSS
- **State Management** : Redux ou Zustand
- **Charts** : Chart.js ou Recharts
- **Maps** : Google Maps API ou Mapbox

#### Mobile (Optionnel)
- **Framework** : React Native ou Flutter
- **Notifications** : Firebase Cloud Messaging

#### DevOps
- **Containerisation** : Docker
- **Orchestration** : Kubernetes ou Docker Swarm
- **CI/CD** : GitHub Actions ou GitLab CI
- **Monitoring** : Grafana + Prometheus
- **Logs** : ELK Stack (Elasticsearch, Logstash, Kibana)

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

## 🚀 Installation

### Prérequis

- MySQL 8.0+ ou MariaDB 10.5+
- Node.js 18+ (si backend Node.js)
- PHP 8.1+ (si backend PHP)
- Composer (si PHP)
- Redis (recommandé)

### Installation de la Base de Données

```bash
# 1. Cloner le repository
git clone https://github.com/votre-org/aiolia-event.git
cd aiolia-event

# 2. Créer la base de données
mysql -u root -p -e "CREATE DATABASE aiolia_event CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 3. Importer le schéma
cd database
mysql -u root -p aiolia_event < schema.sql
mysql -u root -p aiolia_event < triggers.sql
mysql -u root -p aiolia_event < procedures.sql
mysql -u root -p aiolia_event < seeds.sql
mysql -u root -p aiolia_event < indexes_optimization.sql
```

### Configuration Backend

```bash
# Copier le fichier d'environnement
cp .env.example .env

# Configurer les variables
nano .env
```

**Variables essentielles** :
```env
# Database
DB_HOST=localhost
DB_PORT=3306
DB_NAME=aiolia_event
DB_USER=root
DB_PASSWORD=your_password

# JWT
JWT_SECRET=your_super_secret_key
JWT_EXPIRES_IN=15m
REFRESH_TOKEN_EXPIRES_IN=7d

# Mobile Money
ORANGE_MONEY_API_KEY=your_key
ORANGE_MONEY_API_SECRET=your_secret
AIRTEL_MONEY_API_KEY=your_key
TELMA_MONEY_API_KEY=your_key

# Storage
AWS_S3_BUCKET=your_bucket
AWS_ACCESS_KEY_ID=your_key
AWS_SECRET_ACCESS_KEY=your_secret

# Email
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your_email@gmail.com
SMTP_PASSWORD=your_password

# Redis
REDIS_HOST=localhost
REDIS_PORT=6379
```

### Démarrage

```bash
# Installer les dépendances
npm install  # ou composer install

# Démarrer en développement
npm run dev  # ou php artisan serve

# Démarrer en production
npm run start  # ou php artisan serve --env=production
```

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
