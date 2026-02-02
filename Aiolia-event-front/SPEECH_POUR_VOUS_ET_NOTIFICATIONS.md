# 🎤 Speech - Recommandations "Pour Vous" et Système de Notifications

## 📋 Introduction

Bonjour, je vais vous présenter deux fonctionnalités clés de notre plateforme Aiolia Event qui améliorent significativement l'expérience utilisateur : **le système de recommandations "Pour vous"** et **le système de notifications intelligentes**.

---

## 🎯 Partie 1 : Système de Recommandations "Pour Vous"

### Vue d'ensemble

La page **"Pour vous"** est accessible depuis le menu principal et propose des événements personnalisés pour chaque utilisateur. C'est notre système de recommandation intelligent qui apprend des préférences de l'utilisateur.

### Comment ça fonctionne ?

#### Principe de base

L'algorithme analyse **l'historique de l'utilisateur** pour comprendre ses goûts :

1. **Les événements en favoris** : Si un utilisateur met en favoris plusieurs événements de musique, l'algorithme comprend qu'il aime la musique.

2. **Les achats passés** : Si un utilisateur a acheté des billets pour des événements sportifs, l'algorithme sait qu'il s'intéresse au sport.

3. **Les catégories d'intérêt** : L'algorithme combine ces deux sources pour identifier les catégories qui intéressent vraiment l'utilisateur.

#### Exemple concret

Imaginons **Voahirana** :
- Elle a mis en favoris : 3 concerts de musique, 2 festivals culturels
- Elle a acheté : 1 billet pour un match de football, 1 billet pour un concert

L'algorithme identifie donc ses catégories d'intérêt : **Musique, Culture, Sport**.

Ensuite, le système recherche des **nouveaux événements** dans ces catégories, en excluant :
- Les événements déjà en favoris (elle les connaît déjà)
- Les événements déjà achetés (elle y a déjà participé)

Résultat : **12 événements recommandés** dans ses catégories préférées, triés par date (les plus proches en premier).

### Cas particuliers

#### Nouvel utilisateur

Si un utilisateur vient de s'inscrire et n'a pas encore de favoris ni d'achats, l'algorithme utilise un **fallback intelligent** : il affiche les événements à venir les plus récents et populaires. Cela permet de découvrir la plateforme même sans historique.

#### Aucun résultat personnalisé

Si les catégories d'intérêt de l'utilisateur n'ont pas d'événements futurs disponibles, le système bascule automatiquement vers les événements populaires pour garantir qu'il y a toujours du contenu à découvrir.

### Avantages

- **Personnalisation** : Chaque utilisateur voit des recommandations adaptées à ses goûts
- **Découverte** : Permet de trouver de nouveaux événements dans ses domaines d'intérêt
- **Efficacité** : Évite de montrer des événements déjà connus ou achetés
- **Évolutif** : Plus l'utilisateur utilise la plateforme, plus les recommandations s'améliorent

---

## 🔔 Partie 2 : Système de Notifications

### Vue d'ensemble

Le système de notifications d'Aiolia Event est un **canal de communication multi-formats** qui maintient les utilisateurs informés en temps réel de toutes les activités importantes liées à leur compte, leurs billets et les événements qui les intéressent.

### Types de notifications

#### 1. Notifications transactionnelles

**Confirmation de paiement** :
- Quand un utilisateur achète des billets, il reçoit immédiatement une notification
- Titre : "Confirmation : paiement reçu pour [Nom de l'événement]"
- Message : "Votre paiement est confirmé. Nous vous enverrons un rappel le jour de l'événement."

**Billets disponibles** :
- Dès que les billets sont générés après l'achat
- Titre : "Vos billets pour [Nom de l'événement] sont disponibles"
- Message : "Téléchargez vos billets et partagez-les avec vos invités avant l'événement."

**Remboursements** :
- En cas d'annulation d'événement ou de demande de remboursement
- Informe l'utilisateur du montant remboursé et de la raison

#### 2. Notifications événementielles (Rappels)

**Rappel 24 heures avant** :
- Envoyé automatiquement 24 heures avant le début de l'événement
- Titre : "Rappel : [Nom de l'événement] demain à [heure]"
- Message : "L'événement commence demain à [heure]. N'oubliez pas d'apporter vos billets !"

**Rappel 2 heures avant** :
- Envoyé 2 heures avant le début pour un dernier rappel
- Titre : "Rappel : [Nom de l'événement] dans 2h à [heure]"
- Message : "L'événement commence dans 2 heures. Préparez-vous !"

**Comment ça fonctionne ?**
- Une commande Symfony s'exécute automatiquement chaque heure
- Elle détecte tous les événements qui commencent dans 24h ou 2h
- Pour chaque événement, elle trouve tous les utilisateurs qui ont des billets
- Elle vérifie que l'utilisateur a activé les rappels dans ses préférences
- Elle envoie la notification (in-app, email, et push web si activé)

#### 3. Notifications d'offres spéciales

- Quand une promotion ou une offre spéciale est disponible
- Titre : "Nouvelle offre exclusive sur [Nom de l'événement]"
- Message : "Profitez de cette offre spéciale pour réserver vos places."

### Canaux de notification

#### 1. Notifications in-app

**Centre de notifications** (`/notifications`) :
- Page dédiée listant toutes les notifications
- Affichage par ordre chronologique (plus récentes en premier)
- Badge avec nombre de notifications non lues en haut de l'interface

**Fonctionnalités** :
- **Filtrage** : Par type (tous, non lues, lues)
- **Marquer comme lu** : Individuellement ou toutes d'un coup
- **Suppression** : Possibilité d'archiver les anciennes notifications
- **Design** : 
  - Notifications non lues avec fond coloré pour attirer l'attention
  - Icônes différentes selon le type (ticket, rappel, offre, paiement)
  - Formatage du temps : "Il y a 2 heures", "Hier", "Il y a 3 jours"

#### 2. Notifications Push Web

**Fonctionnement** :
- Utilise le Service Worker API du navigateur
- Permission demandée à l'utilisateur lors de sa première visite
- Notifications affichées même quand l'onglet est fermé
- Clic sur la notification redirige vers la page concernée

**Avantages** :
- L'utilisateur reste informé même s'il n'est pas sur le site
- Notifications discrètes mais visibles
- Pas besoin d'application mobile dédiée

#### 3. Emails transactionnels

**Types d'emails** :
- Email de bienvenue après inscription
- Confirmation d'achat avec billets en pièce jointe
- Rappels d'événements (24h et 2h avant)
- Notification d'annulation et remboursement

**Avantages** :
- Archive permanente des informations importantes
- Billets téléchargeables depuis l'email
- Accessible même sans connexion au site

### Gestion des notifications

#### Interface utilisateur

**Page de notifications** :
- **Onglets** : Tous / Non lues / Lues
- **Compteurs** : Nombre de notifications dans chaque catégorie
- **Actions** :
  - Bouton "Tout marquer comme lu" pour nettoyer rapidement
  - Bouton "Voir" pour accéder à l'événement concerné
  - Bouton "Archiver" pour supprimer une notification

**Badge de notification** :
- Affiché en haut de l'interface (dans le header)
- Affiche le nombre de notifications non lues
- Cliquable pour accéder directement au centre de notifications
- Se met à jour en temps réel

#### Préférences utilisateur

Les utilisateurs peuvent activer/désactiver certains types de notifications :
- Rappels d'événements (24h et 2h avant)
- Notifications push web
- Emails transactionnels

Ces préférences sont stockées dans la base de données et respectées par le système.

### Exemple de scénario complet

**Scénario : Voahirana achète des billets pour un concert**

1. **Achat** : Voahirana achète 2 billets pour "Concert Rock 2025" le 15 janvier à 20h

2. **Notification immédiate** :
   - ✅ Notification in-app : "Confirmation : paiement reçu pour Concert Rock 2025"
   - ✅ Email envoyé avec confirmation et billets en pièce jointe
   - ✅ Notification push web (si activée)

3. **Billets disponibles** :
   - ✅ Notification in-app : "Vos billets pour Concert Rock 2025 sont disponibles"
   - ✅ Email avec billets téléchargeables

4. **Rappel 24h avant** (14 janvier à 20h) :
   - ✅ Notification in-app : "Rappel : Concert Rock 2025 demain à 20:00"
   - ✅ Email de rappel
   - ✅ Notification push web

5. **Rappel 2h avant** (15 janvier à 18h) :
   - ✅ Notification in-app : "Rappel : Concert Rock 2025 dans 2h à 20:00"
   - ✅ Email de dernier rappel
   - ✅ Notification push web

6. **Gestion** :
   - Voahirana consulte ses notifications sur `/notifications`
   - Elle marque les anciennes comme lues
   - Le badge de notification se met à jour automatiquement

---

## 🎯 Points clés à retenir

### Recommandations "Pour vous"

1. **Personnalisation intelligente** : Basée sur les favoris et achats passés
2. **Découverte de nouveaux événements** : Dans les catégories d'intérêt
3. **Exclusion intelligente** : Ne montre pas ce que l'utilisateur connaît déjà
4. **Fallback robuste** : Fonctionne même pour les nouveaux utilisateurs

### Système de notifications

1. **Multi-canaux** : In-app, push web, et emails
2. **Temps réel** : Notifications instantanées lors des actions importantes
3. **Rappels automatiques** : 24h et 2h avant les événements
4. **Gestion flexible** : Filtrage, marquage, suppression
5. **Respect des préférences** : L'utilisateur contrôle ce qu'il reçoit

---

## 💡 Avantages business

### Pour l'utilisateur

- **Gain de temps** : Trouve rapidement des événements qui l'intéressent
- **Ne manque rien** : Rappels automatiques pour ne pas oublier ses événements
- **Confort** : Toutes les informations importantes au même endroit
- **Personnalisation** : Expérience adaptée à ses goûts

### Pour la plateforme

- **Engagement** : Utilisateurs plus actifs grâce aux recommandations
- **Rétention** : Rappels réduisent les no-shows
- **Satisfaction** : Meilleure expérience utilisateur
- **Conversion** : Recommandations ciblées augmentent les achats

---

## 🎤 Exemple de speech complet

> "Bonjour, je vais vous présenter deux fonctionnalités clés de notre plateforme qui améliorent significativement l'expérience utilisateur.
> 
> **Premièrement, le système de recommandations "Pour vous"**.
> 
> Cette fonctionnalité propose des événements personnalisés à chaque utilisateur. Comment ça marche ? L'algorithme analyse l'historique de l'utilisateur : ses événements favoris et ses achats passés. Il identifie ainsi les catégories qui l'intéressent vraiment - par exemple, si quelqu'un a mis en favoris plusieurs concerts et a acheté des billets pour des événements sportifs, l'algorithme comprend qu'il aime la musique et le sport.
> 
> Ensuite, le système recherche de nouveaux événements dans ces catégories, en excluant intelligemment les événements déjà en favoris ou déjà achetés. Résultat : 12 événements recommandés, triés par date, que l'utilisateur n'a pas encore vus mais qui correspondent à ses goûts.
> 
> Pour les nouveaux utilisateurs qui n'ont pas encore d'historique, le système affiche automatiquement les événements à venir les plus récents et populaires, permettant ainsi de découvrir la plateforme.
> 
> **Deuxièmement, le système de notifications intelligentes**.
> 
> Notre plateforme envoie des notifications via trois canaux : in-app, push web, et emails. Les notifications couvrent plusieurs types d'événements importants.
> 
> D'abord, les **notifications transactionnelles** : confirmation de paiement dès qu'un achat est effectué, notification quand les billets sont disponibles, et notifications de remboursement en cas d'annulation.
> 
> Ensuite, les **rappels d'événements automatiques** : le système envoie un rappel 24 heures avant l'événement, puis un deuxième rappel 2 heures avant. Ces rappels sont envoyés automatiquement via une commande qui s'exécute chaque heure, détectant tous les événements à venir et notifiant les utilisateurs concernés.
> 
> Enfin, les **notifications d'offres spéciales** pour informer des promotions disponibles.
> 
> Toutes ces notifications sont centralisées dans un **centre de notifications** accessible depuis le menu. L'utilisateur peut filtrer par type, marquer comme lu, ou supprimer les anciennes notifications. Un badge en haut de l'interface affiche le nombre de notifications non lues et se met à jour en temps réel.
> 
> Les utilisateurs peuvent également activer les **notifications push web** pour recevoir des alertes même quand ils ne sont pas sur le site, et configurer leurs préférences pour choisir quels types de notifications ils souhaitent recevoir.
> 
> Ces deux fonctionnalités travaillent ensemble pour offrir une expérience personnalisée et informée : les recommandations aident à découvrir de nouveaux événements, et les notifications garantissent qu'on ne manque jamais une information importante."

---

## 📊 Résumé technique

### Recommandations "Pour vous"

- **Route** : `/events/for-you`
- **Méthode** : `EventRepository::findRecommendationsForUserDetailed()`
- **Algorithme** : Filtrage collaboratif basé sur les catégories
- **Limite** : 12 événements par défaut
- **Tri** : Par date de début (plus proche en premier)

### Notifications

- **Route** : `/notifications`
- **Controller** : `NotificationController`
- **Service** : `NotificationService`, `EventReminderService`
- **Types** : ticket, offer, reminder, payment
- **Canaux** : in_app, web_push, email
- **Rappels** : Commande `app:send-event-reminders` (exécutée chaque heure)

---

**Date de création** : Décembre 2025  
**Version** : 1.0  
**Auteur** : Équipe de développement Aiolia Event
