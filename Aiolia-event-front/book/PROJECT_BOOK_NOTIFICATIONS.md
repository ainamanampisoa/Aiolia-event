# 📖 Livre du Projet : Système de Notifications

Le système de notifications d'Aiolia-event est conçu pour maintenir les utilisateurs informés en temps réel de toutes les activités importantes liées à leur compte, leurs billets et les événements qui les intéressent. C'est un canal de communication essentiel qui améliore l'expérience utilisateur en fournissant des informations pertinentes au bon moment.

---

## 🔔 a. Vue d'ensemble du système

Le système de notifications est multi-canal et intelligent, permettant aux utilisateurs de recevoir des informations importantes via différents moyens selon leurs préférences.

### Types de notifications
**Notifications transactionnelles** :
- Confirmation de commande
- Confirmation de paiement
- Génération de billets
- Remboursements
- Échecs de paiement

**Notifications événementielles** :
- Rappel d'événement à venir (24h avant, 1h avant)
- Changements d'événement (annulation, modification)
- Nouveaux événements dans les catégories favorites
- Événements favoris qui approchent

**Notifications sociales** :
- Invitations d'amis
- Messages de la communauté
- Partages de billets

**Notifications système** :
- Mises à jour de la plateforme
- Nouvelles fonctionnalités
- Messages de maintenance

### L'expérience utilisateur
Les notifications sont accessibles via :
- **Centre de notifications** : Une page dédiée listant toutes les notifications
- **Badge de notification** : Un indicateur visuel (badge avec nombre) en haut de l'interface
- **Notifications push** : Pour les utilisateurs ayant activé les notifications du navigateur
- **Emails** : Pour les notifications importantes (confirmations de paiement, etc.)

---

## 📬 b. Centre de notifications

Le centre de notifications est l'endroit central où l'utilisateur peut consulter toutes ses notifications, les marquer comme lues, ou les supprimer.

### L'expérience utilisateur
**Affichage des notifications** :
- Les notifications sont listées par ordre chronologique (plus récentes en premier)
- Chaque notification affiche :
  - Un icône indiquant le type
  - Le titre et le message
  - La date et l'heure
  - Un indicateur visuel si non lue (badge ou couleur différente)

**Fonctionnalités** :
- **Marquer comme lu** : Cliquer sur une notification la marque comme lue
- **Marquer tout comme lu** : Un bouton permet de marquer toutes les notifications comme lues d'un coup
- **Filtrage** : Filtrer par type (toutes, non lues, lues) ou par catégorie
- **Suppression** : Supprimer une notification individuelle ou toutes les notifications lues
- **Actions** : Certaines notifications contiennent des actions (ex: "Voir mes billets", "Voir l'événement")

### Sous le capot 🛠️
Le centre de notifications est géré par NotificationController::index() :
1. **Récupération** : NotificationRepository::findUserNotifications() récupère les notifications de l'utilisateur
2. **Comptage** : Le nombre de notifications non lues est calculé pour le badge
3. **Pagination** : Les notifications sont paginées pour une performance optimale
4. **Mise à jour** : Les actions (marquer comme lu, supprimer) sont traitées via des endpoints API

---

## 📧 c. Notifications par email

Les notifications par email sont utilisées pour les communications importantes qui nécessitent une confirmation ou un suivi.

### Types d'emails
**Emails transactionnels** :
- Confirmation d'inscription
- Confirmation de commande
- Confirmation de paiement avec détails des billets
- Facture PDF en pièce jointe
- Notification de remboursement

**Emails événementiels** :
- Rappel d'événement (24h avant, 1h avant)
- Notification d'annulation d'événement
- Notification de modification d'événement

**Emails de sécurité** :
- Changement de mot de passe
- Connexion depuis un nouvel appareil
- Tentative de connexion suspecte

### Les coulisses techniques ⚙️
Les emails sont envoyés via NotificationService::sendEmail() :
- Utilisation d'un service d'email (SMTP, SendGrid, etc.)
- Templates d'email en Twig pour la cohérence visuelle
- Support HTML et texte brut
- Pièces jointes pour les PDF (factures, billets)

---

## 📱 d. Notifications push (navigateur)

Les notifications push du navigateur permettent d'informer l'utilisateur même quand il n'est pas sur le site.

### L'expérience utilisateur
**Activation** :
- Lors de la première visite, le navigateur demande la permission pour les notifications
- L'utilisateur peut accepter ou refuser
- Les préférences peuvent être modifiées dans les paramètres du profil

**Réception** :
- Les notifications apparaissent comme des notifications système du navigateur
- Elles restent visibles même si l'utilisateur est sur un autre onglet ou application
- Un clic sur la notification redirige vers la page concernée

**Types de notifications push** :
- Rappels d'événements
- Confirmations de paiement
- Notifications importantes (annulations, etc.)

### Sous le capot 🛠️
Les notifications push utilisent l'API Web Notifications du navigateur :
- Service Worker pour gérer les notifications en arrière-plan
- API Push pour envoyer les notifications même si le navigateur est fermé
- Stockage des préférences utilisateur pour gérer les abonnements

---

## 🔔 e. Notifications en temps réel

Le système peut envoyer des notifications en temps réel via WebSocket ou Server-Sent Events (SSE) pour une expérience interactive.

### L'expérience utilisateur
**Notifications instantanées** :
- Lorsqu'une notification est créée, elle apparaît immédiatement dans l'interface
- Le badge de notification se met à jour automatiquement
- Une animation subtile attire l'attention sans être intrusive

**Cas d'usage** :
- Confirmation de paiement en temps réel
- Mise à jour du statut d'une commande
- Nouveaux messages ou invitations

### Les coulisses techniques ⚙️
Les notifications en temps réel peuvent utiliser :
- **WebSocket** : Connexion bidirectionnelle pour des mises à jour instantanées
- **Server-Sent Events (SSE)** : Pour des notifications unidirectionnelles depuis le serveur
- **Polling** : Vérification périodique (moins optimal mais plus compatible)

---

## ⚙️ f. Gestion des préférences

Les utilisateurs peuvent personnaliser leurs préférences de notification pour recevoir uniquement les informations qui les intéressent.

### L'expérience utilisateur
**Paramètres de notification** :
Dans les paramètres du profil, l'utilisateur peut :
- Activer/désactiver les notifications par email
- Activer/désactiver les notifications push
- Choisir les types de notifications à recevoir :
  - Transactions (commandes, paiements)
  - Événements (rappels, changements)
  - Social (invitations, messages)
  - Système (mises à jour, maintenance)

**Granularité** :
- Pour les rappels d'événements : choisir quand recevoir les rappels (24h avant, 1h avant, les deux, ou aucun)
- Pour les événements favoris : recevoir ou non des notifications pour les nouveaux événements similaires

### Sous le capot 🛠️
Les préférences sont stockées dans user_preferences :
- Chaque type de notification peut être activé/désactivé individuellement
- Les préférences sont respectées lors de l'envoi de notifications
- Les préférences par défaut sont appliquées pour les nouveaux utilisateurs

---

## 🎭 Scénario d'utilisation : Les notifications de Lala

Suivons **Lala**, une utilisatrice active qui a acheté des billets pour plusieurs événements.

### 1. Achat de billets
Lala achète 2 billets pour un concert. Immédiatement :
- Une notification apparaît dans le centre de notifications : "Commande confirmée - 2 billets pour Concert Jazz"
- Un email de confirmation est envoyé avec les détails et les billets en PDF
- Le badge de notification affiche "1" en haut de l'écran

### 2. Rappel d'événement
24 heures avant le concert, Lala reçoit :
- Une notification push : "Rappel : Concert Jazz demain à 20h"
- Un email de rappel avec les informations pratiques
- La notification apparaît aussi dans le centre de notifications

### 3. Annulation d'événement
Malheureusement, un autre événement auquel Lala avait acheté des billets est annulé. Elle reçoit :
- Une notification push urgente : "Événement annulé - Remboursement en cours"
- Un email détaillé expliquant la raison de l'annulation et le processus de remboursement
- Une notification dans le centre de notifications avec un lien vers les détails

### 4. Gestion des notifications
Lala consulte régulièrement son centre de notifications. Elle :
- Marque les notifications lues comme "lues" pour garder une interface propre
- Supprime les anciennes notifications qui ne sont plus pertinentes
- Configure ses préférences pour ne recevoir que les rappels 1h avant les événements (pas 24h)

---

> [!TIP]
> **Le saviez-vous ?**
> Vous pouvez personnaliser complètement vos notifications dans les paramètres de votre profil. Activez uniquement ce qui vous intéresse pour une expérience sur mesure !




