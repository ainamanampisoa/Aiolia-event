# 📋 Guide des Routes - Aiolia Event Frontend

## 🔐 Authentification

| Fichier | URL | Description |
|---------|-----|-------------|
| `Login.jsx` | `/login` | Page de connexion (email/password + "Se souvenir de moi") |
| `Register.jsx` | `/register` | Inscription utilisateur (prénom, nom, email, téléphone, mot de passe) |
| `BecomeOrganizer.jsx` | `/become-organizer` | Inscription organisateur (infos personnelles + organisation + documents légaux) |

---

## 🏠 Page d'accueil publique

| Fichier | URL | Description |
|---------|-----|-------------|
| `Landing.jsx` | `/` | Page d'accueil publique (bannière + événements + comment acheter + calendrier + stats) |

## 🎉 Événements (après connexion)

| Fichier | URL | Description |
|---------|-----|-------------|
| `EventList.jsx` | `/events` | Liste complète des événements avec filtres avancés (type, localisation, date, prix) |
| `EventDetails.jsx` | `/events/:id` | Détails complets d'un événement (description, vidéo, map, billets, similaires) |

---

## 🛒 Panier & Paiement

| Fichier | URL | Description |
|---------|-----|-------------|
| `Cart.jsx` | `/cart` | Panier d'achat (liste billets, code promo, récapitulatif) |
| `Checkout.jsx` | `/checkout` | Page de paiement Mobile Money (MVola, Orange, Airtel) |
| `OrderConfirmation.jsx` | `/order-confirmation/:orderId` | Confirmation d'achat (QR codes, téléchargement PDF, facture) |

---

## 👤 Profil & Compte Utilisateur

| Fichier | URL | Description |
|---------|-----|-------------|
| `Profile.jsx` | `/profile` | Profil utilisateur (infos perso, photo, changement mot de passe) |
| `MyTickets.jsx` | `/my-tickets` | Mes billets (à venir, passés, annulés + QR codes + partage) |
| `Wallet.jsx` | `/wallet` | Portefeuille numérique (points de fidélité + historique transactions) |
| `Statistics.jsx` | `/statistics` | Statistiques personnelles (événements assistés, dépenses, catégories) |
| `History.jsx` | `/history` | Historique achats et recherches |
| `Favorites.jsx` | `/favorites` | Favoris/Wishlist (événements sauvegardés) |
| `Calendar.jsx` | `/calendar` | Vue calendrier (événements réservés + rappels) |

---

## 🎮 Mini-jeux & Gamification

| Fichier | URL | Description |
|---------|-----|-------------|
| `TicketChance.jsx` | `/ticket-chance` | Mini-jeu roue de la fortune (réductions, points, billets gratuits) |

---

## 📊 Résumé

### **Total : 17 pages principales**

**Par catégorie :**
- 🏠 Page d'accueil : 1 page
- 🔐 Authentification : 3 pages
- 🎉 Événements : 2 pages  
- 🛒 Panier & Paiement : 3 pages
- 👤 Profil utilisateur : 7 pages
- 🎮 Jeux : 1 page

---

## 🎨 Composants Réutilisables

| Composant | Emplacement | Description |
|-----------|-------------|-------------|
| `Header.jsx` | `/components/layout/` | En-tête (logo, menu, panier, connexion) |
| `Footer.jsx` | `/components/layout/` | Pied de page (liens, mentions légales) |
| `EventCard.jsx` | `/components/common/` | Carte événement (date, lieu, prix, sélecteur billets) |
| `TicketSelector.jsx` | `/components/common/` | Sélecteur de billets (adultes, enfants, quantité) |

---

## 🔄 Contexts React

| Context | Fichier | Description |
|---------|---------|-------------|
| `AuthContext` | `/contexts/AuthContext.jsx` | Gestion authentification globale (user, login, logout) |
| `CartContext` | `/contexts/CartContext.jsx` | Gestion panier global (items, total, codes promo) |

---

## 🛠️ Services API

| Service | Fichier | Description |
|---------|---------|-------------|
| `api.js` | `/services/api.js` | Configuration Axios + intercepteurs JWT |
| `authService.js` | `/services/authService.js` | API auth (login, register, logout, refresh) |

---

## 🚀 Pour démarrer le projet

```bash
cd Aiolia-event-front
npm run dev
```

Puis ouvrez : **http://localhost:5173**

---

## 📝 Notes importantes

- ✅ Tous les designs sont basés sur le template HTML du dossier `vente-ticket`
- ✅ Les CSS et assets sont déjà copiés dans `/public/`
- ✅ Inscription séparée : Utilisateurs (`/register`) et Organisateurs (`/become-organizer`)
- ⚠️ Les appels API sont simulés (TODO: connecter avec Symfony backend)
- ⚠️ Les QR codes utilisent une API externe gratuite (https://api.qrserver.com)
- ⚠️ Les paiements Mobile Money sont simulés (TODO: intégration réelle)
- ⚠️ Validation des organisateurs : processus manuel sous 48h (TODO: automatiser)

---

**Bon développement ! 🎉**

