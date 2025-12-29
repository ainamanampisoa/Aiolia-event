# 📖 Livre du Projet : Administration

L'espace d'administration est réservé aux administrateurs de la plateforme Aiolia-event. C'est le centre de contrôle qui permet de gérer tous les aspects de la plateforme : utilisateurs, événements, transactions, et système. Les administrateurs ont la responsabilité de maintenir la qualité, la sécurité et la conformité de la plateforme.

---

## 👥 a. Gestion des utilisateurs

La gestion des utilisateurs est l'une des fonctionnalités principales de l'administration. Elle permet de superviser tous les comptes, de valider les demandes de rôles, et de maintenir la sécurité de la plateforme.

### L'expérience utilisateur
**Page de gestion des utilisateurs** :
- **Liste complète** : Tous les utilisateurs sont listés avec leurs informations essentielles
- **Recherche avancée** : Recherche multi-critères :
  - Par nom (avec autocomplete)
  - Par email
  - Par rôle (utilisateur, organisateur, co-organisateur, admin)
  - Par statut du compte (actif, en attente, suspendu, rejeté)
  - Tri par date d'inscription, nom, email

**Actions disponibles** :
- **Voir les détails** : Accéder à une vue complète de l'utilisateur
- **Modifier le rôle** : Changer le rôle d'un utilisateur (avec validation)
- **Suspendre/Activer** : Désactiver temporairement un compte ou le réactiver
- **Supprimer** : Supprimer définitivement un compte (avec confirmation)

**Détails utilisateur** :
- Informations complètes (nom, email, téléphone, date d'inscription)
- Historique des commandes et transactions
- Historique des événements créés (si organisateur)
- Historique des actions administratives sur ce compte
- Statistiques d'activité

### Sous le capot 🛠️
La gestion des utilisateurs est gérée par AdminController::users() :
1. **Récupération** : Les utilisateurs sont récupérés depuis users avec filtres et pagination
2. **Recherche** : La recherche utilise des requêtes SQL optimisées avec index
3. **Actions** : Chaque action est enregistrée dans audit_logs pour traçabilité complète
4. **Sécurité** : Toutes les actions sensibles nécessitent une confirmation et sont loggées

---

## ✅ b. Validation des demandes de rôles

Les utilisateurs peuvent demander à devenir organisateur ou co-organisateur. Ces demandes doivent être validées par un administrateur pour garantir la qualité et la sécurité de la plateforme.

### L'expérience utilisateur
**Page de validation** :
- **Demandes en attente** : Liste de toutes les demandes non traitées
- Pour chaque demande :
  - Informations de l'utilisateur (nom, email, date d'inscription)
  - Rôle demandé (organisateur ou co-organisateur)
  - Raison fournie par l'utilisateur
  - Date de la demande
  - Actions : Approuver ou Rejeter

**Processus de validation** :
1. L'administrateur consulte la demande
2. Il peut voir l'historique de l'utilisateur (commandes, activité)
3. Il approuve ou rejette avec un commentaire optionnel
4. L'utilisateur reçoit une notification du résultat

**Historique des validations** :
- Toutes les demandes (en attente, approuvées, rejetées) sont archivées
- Filtres par statut pour navigation facile
- Recherche par nom d'utilisateur ou email

### Les coulisses techniques ⚙️
La validation est gérée par le système de validation des utilisateurs :
1. **Demande** : Lors de l'inscription, si l'utilisateur choisit "organisateur" ou "co-organisateur", une entrée est créée dans user_validation_requests
2. **Traitement** : L'administrateur approuve ou rejette via l'interface
3. **Mise à jour** : Le rôle de l'utilisateur est mis à jour dans users et le statut dans user_validation_requests
4. **Notification** : L'utilisateur est notifié du résultat (email et notification in-app)
5. **Audit** : L'action est enregistrée dans audit_logs

---

## 🎪 c. Gestion des événements

Les administrateurs peuvent superviser tous les événements de la plateforme, modérer le contenu, et gérer les cas particuliers (annulations, problèmes, etc.).

### L'expérience utilisateur
**Liste des événements** :
- Tous les événements sont listés avec leurs informations essentielles
- Filtres par :
  - Statut (brouillon, publié, annulé, terminé)
  - Organisateur
  - Catégorie
  - Date
  - Nombre de billets vendus

**Actions disponibles** :
- **Voir les détails** : Consulter toutes les informations de l'événement
- **Modérer** : Modifier le contenu si nécessaire (avec notification à l'organisateur)
- **Annuler avec remboursement** : Annuler un événement et déclencher le remboursement automatique
- **Suspendre** : Masquer temporairement un événement (pour modération)
- **Supprimer** : Supprimer définitivement un événement (avec confirmation)

**Annulation avec remboursement** :
- L'administrateur peut annuler un événement pour cause d'urgence
- Le système déclenche automatiquement le processus de remboursement :
  - Tous les billets sont marqués comme "refunded"
  - Les remboursements sont initiés via Mvola
  - Les utilisateurs sont notifiés
  - Les organisateurs sont informés

### Sous le capot 🛠️
La gestion des événements utilise AdminController::cancelEvent() :
1. **Vérification** : Le système vérifie que l'événement peut être annulé
2. **Remboursement** : RefundService::refundEventTickets() est appelé pour traiter tous les remboursements
3. **Mise à jour** : Le statut de l'événement est mis à jour à "cancelled"
4. **Notifications** : Tous les participants et l'organisateur sont notifiés
5. **Audit** : L'action est enregistrée dans audit_logs

---

## 📊 d. Statistiques et rapports globaux

Les administrateurs ont accès à des statistiques globales de la plateforme pour comprendre les tendances et prendre des décisions éclairées.

### L'expérience utilisateur
**Tableau de bord administratif** :
- **Utilisateurs** :
  - Nombre total d'utilisateurs
  - Nouveaux utilisateurs (par jour/semaine/mois)
  - Répartition par rôle
  - Taux de croissance

- **Événements** :
  - Nombre total d'événements
  - Événements publiés vs brouillons
  - Répartition par catégorie
  - Taux de remplissage moyen

- **Transactions** :
  - Revenus totaux
  - Nombre de transactions
  - Taux de succès des paiements
  - Évolution des revenus dans le temps

- **Performance** :
  - Événements les plus populaires
  - Organisateurs les plus actifs
  - Villes les plus actives
  - Périodes de forte activité

**Rapports** :
- Rapports détaillés exportables (CSV, PDF)
- Rapports personnalisés par période
- Comparaisons entre périodes

### Les coulisses techniques ⚙️
Les statistiques sont calculées depuis toutes les tables pertinentes :
- Agrégation des données depuis users, events, orders, tickets
- Calculs en temps réel ou via des vues matérialisées pour performance
- Mise en cache pour les statistiques fréquemment consultées

---

## 🔍 e. Audit et traçabilité

Toutes les actions administratives sont enregistrées pour garantir la traçabilité et la sécurité.

### L'expérience utilisateur
**Historique des actions** :
- Toutes les actions effectuées dans le système sont enregistrées
- Filtres par :
  - Type d'action (création, modification, suppression, validation)
  - Utilisateur (qui a effectué l'action)
  - Période (date début/fin)
  - Ressource concernée (utilisateur, événement, etc.)

**Informations enregistrées** :
- Date et heure précise
- Utilisateur qui a effectué l'action
- Type d'action
- Détails en JSON (données avant/après modification)
- Adresse IP
- User agent (navigateur)

**Statistiques d'audit** :
- Nombre d'actions par type
- Utilisateurs les plus actifs
- Actions par période

### Sous le capot 🛠️
L'audit est géré par le système audit_logs :
1. **Enregistrement** : Chaque action sensible déclenche un enregistrement dans audit_logs
2. **Détails** : Les données avant et après modification sont stockées en JSON
3. **Consultation** : Les logs sont consultables via l'interface d'administration
4. **Rétention** : Les logs sont conservés selon la politique de rétention (généralement 1-2 ans)

---

## 🛡️ f. Sécurité et modération

Les administrateurs sont responsables de la sécurité et de la modération du contenu de la plateforme.

### L'expérience utilisateur
**Modération de contenu** :
- Signalement de contenu inapproprié
- Révision des événements signalés
- Actions : approuver, modifier, suspendre, supprimer

**Sécurité** :
- Surveillance des activités suspectes
- Gestion des comptes compromis
- Blocage d'adresses IP si nécessaire
- Révision des transactions suspectes

**Conformité** :
- Vérification de la conformité des événements
- Respect des conditions générales d'utilisation
- Gestion des réclamations

### Sous le capot 🛠️
La sécurité utilise plusieurs mécanismes :
- Détection automatique d'activités suspectes (tentatives de connexion multiples, transactions anormales)
- Alertes pour les administrateurs
- Système de signalement pour les utilisateurs
- Modération manuelle et automatique

---

## 🎭 Scénario d'utilisation : La journée d'un administrateur

Suivons **Admin Rija**, un administrateur de la plateforme, dans sa journée de travail.

### 1. Validation des demandes
Rija commence sa journée en consultant les demandes de rôles en attente. Il voit 3 nouvelles demandes :
- 2 demandes d'organisateur
- 1 demande de co-organisateur

Il examine chaque demande, vérifie l'historique des utilisateurs, et approuve 2 demandes (les raisons sont convaincantes) et rejette 1 (raison insuffisante). Les utilisateurs sont notifiés automatiquement.

### 2. Gestion d'un événement problématique
Rija reçoit un signalement concernant un événement avec un contenu inapproprié. Il consulte l'événement, confirme le problème, et suspend temporairement l'événement. Il contacte l'organisateur pour demander des modifications.

### 3. Annulation d'urgence
Un organisateur contacte le support : son événement doit être annulé en urgence (problème de lieu). Rija accède à l'interface d'administration, annule l'événement et déclenche le remboursement automatique. Tous les participants sont notifiés et remboursés automatiquement.

### 4. Consultation des statistiques
En fin de journée, Rija consulte les statistiques de la semaine :
- 150 nouveaux utilisateurs
- 25 nouveaux événements publiés
- 500 transactions réussies
- Revenus totaux : 25 000 000 MGA

Il génère un rapport hebdomadaire pour l'équipe.

### 5. Audit de sécurité
Rija consulte l'historique d'audit et remarque plusieurs tentatives de connexion suspectes sur un compte. Il suspend temporairement le compte et contacte l'utilisateur pour vérification.

---

> [!TIP]
> **Le saviez-vous ?**
> Toutes les actions administratives sont tracées dans l'historique d'audit. Cela garantit la transparence et permet de résoudre rapidement tout problème ou litige.

