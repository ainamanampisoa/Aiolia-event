# Système de Validation des Utilisateurs

## Vue d'ensemble

Ce système permet de gérer les demandes d'inscription en tant qu'organisateur. Les administrateurs doivent valider ces demandes avant que les utilisateurs puissent obtenir ce rôle.

## Fonctionnalités

### 1. Inscription avec demande de rôle
- Les utilisateurs peuvent s'inscrire en choisissant leur type de compte :
  - **Utilisateur** : Compte activé immédiatement
  - **Organisateur** : Nécessite une validation admin

- Pour les rôles nécessitant une validation, l'utilisateur doit fournir une raison.

### 2. Page de validation pour les admins
**Route** : `/admin/validation/pending`

Affiche toutes les demandes en attente avec :
- Informations de l'utilisateur
- Rôle demandé
- Raison fournie
- Actions : Approuver ou Rejeter

### 3. Historique des validations
**Route** : `/admin/validation/history`

Affiche l'historique de toutes les demandes avec filtres par statut :
- En attente
- Approuvées
- Rejetées

### 4. Gestion des utilisateurs
**Route** : `/admin/users`

Page complète de gestion avec :
- **Recherche multi-critères** :
  - Nom (avec autocomplete)
  - Email
  - Rôle
  - Statut du compte
  - Tri par date, nom, email
  
- **Actions disponibles** :
  - Voir les détails d'un utilisateur
  - Modifier le rôle
  - Suspendre/Activer le compte
  - Supprimer l'utilisateur

### 5. Détails utilisateur
**Route** : `/admin/users/{id}`

Affiche :
- Informations complètes de l'utilisateur
- Historique de toutes les actions effectuées sur ce compte
- Actions de gestion disponibles

### 6. Historique global des actions
**Route** : `/admin/users/audit/history`

Affiche toutes les actions effectuées dans le système avec :
- **Filtres** :
  - Type d'action
  - Utilisateur
  - Période (date début/fin)
  
- **Statistiques** :
  - Nombre d'actions par type
  
- **Informations enregistrées** :
  - Date et heure
  - Utilisateur qui a effectué l'action
  - Type d'action
  - Détails en JSON
  - Adresse IP
  - User agent

## Structure de la base de données

### Table `users`
Nouveau champ ajouté :
- `account_status` : 'active', 'pending_validation', 'rejected', 'suspended'

### Table `user_validation_requests`
```sql
- id
- user_id (FK users)
- requested_role ('organizer')
- status ('pending', 'approved', 'rejected')
- reason (texte fourni par l'utilisateur)
- admin_comment (commentaire de l'admin)
- validated_by (FK users - admin qui a traité)
- created_at
- validated_at
```

### Table `audit_logs`
```sql
- id
- performed_by (FK users - qui a fait l'action)
- action (type d'action)
- entity_type (type d'entité: User, Event, etc.)
- entity_id (ID de l'entité)
- details (JSON avec détails supplémentaires)
- ip_address
- user_agent
- created_at
```

## Actions enregistrées dans l'audit log

- `user_created` : Création d'un utilisateur
- `user_updated` : Modification d'un utilisateur
- `user_deleted` : Suppression d'un utilisateur
- `user_validated` : Validation d'une demande
- `user_rejected` : Rejet d'une demande
- `role_changed` : Changement de rôle
- `event_created` : Création d'événement
- `event_updated` : Modification d'événement
- `event_deleted` : Suppression d'événement
- `login` : Connexion
- `logout` : Déconnexion
- `password_reset` : Réinitialisation de mot de passe

## Installation

### 1. Exécuter la migration SQL
```bash
mysql -u votre_user -p votre_database < database/user_validation_system.sql
```

### 2. Générer les migrations Doctrine (optionnel si vous utilisez Doctrine)
```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

### 3. Vérifier les routes
```bash
php bin/console debug:router | grep admin
```

## Utilisation

### Identifiants de test

**Important** : Pour vous connecter, utilisez l'**email** comme identifiant et le mot de passe correspondant.

#### Administrateurs (5 comptes disponibles)
| Email | Mot de passe | Statut | ID |
|-------|--------------|--------|-----|
| admin01@yopmail.com | Admin#Test123 | Actif | 76 |
| admin02@yopmail.com | Admin#Test123 | Actif | 77 |
| admin03@yopmail.com | Admin#Test123 | Actif | 78 |
| admin04@yopmail.com | Admin#Test123 | Actif | 79 |
| admin05@yopmail.com | Admin#Test123 | Actif | 80 |

#### Organisateurs (60 comptes disponibles)
| Email | Mot de passe | Statut | ID | Notes |
|-------|--------------|--------|-----|-------|
| organisateur01@yopmail.com | Org#Test123 | Validé | 1 | Actif |
| organisateur02@yopmail.com | Org#Test123 | Validé | 2 | Actif |
| organisateur03@yopmail.com | Org#Test123 | Validé | 3 | Actif |
| organisateur04@yopmail.com | Org#Test123 | Validé | 4 | Actif |
| ... | ... | ... | ... | ... |
| organisateur46@yopmail.com | Org#Test123 | Validé | 46 | En pause (almost_late) |
| organisateur47@yopmail.com | Org#Test123 | Validé | 47 | En pause (almost_late) |
| organisateur48@yopmail.com | Org#Test123 | Validé | 48 | En pause (almost_late) |
| organisateur49@yopmail.com | Org#Test123 | Validé | 49 | En pause (almost_late) |
| organisateur50@yopmail.com | Org#Test123 | Validé | 50 | En pause (almost_late) |
| organisateur51@yopmail.com | Org#Test123 | Validé | 51 | En pause (almost_late) |
| organisateur52@yopmail.com | Org#Test123 | Validé | 52 | En pause (almost_late) |
| organisateur53@yopmail.com | Org#Test123 | En attente | 53 | Non validé |
| ... | ... | ... | ... | ... |
| organisateur60@yopmail.com | Org#Test123 | En attente | 60 | Non validé |

**Notes importantes** :
- Les organisateurs 1 à 52 sont validés et peuvent se connecter
- Les organisateurs 53 à 60 sont en attente de validation (statut = 0)
- Les organisateurs 46 à 52 sont en pause (abonnement non payé avant le 11ème jour)
- Tous les organisateurs en pause **peuvent se connecter**, mais avec des accès limités (voir section ci-dessous)

#### Statuts d'abonnement des organisateurs

Les organisateurs ont **trois statuts d'abonnement possibles** :

1. **`pending` (En attente)** : L'organisateur a créé son compte mais n'a pas encore souscrit d'abonnement, ou son abonnement est en attente d'activation
2. **`active` (Actif)** : L'organisateur a un abonnement actif et à jour. Il peut utiliser toutes les fonctionnalités de la plateforme
3. **`paused` (En pause)** : L'abonnement est en pause (généralement à cause d'un non-paiement). L'organisateur peut se connecter mais avec des accès limités

**Règles de transition entre statuts** :
- `pending` → `active` : Lorsque l'organisateur souscrit à un plan d'abonnement
- `active` → `paused` : Automatiquement si la facture du mois courant n'est pas payée avant le 11ème jour du mois suivant
- `paused` → `active` : Lorsque l'organisateur paie toutes les factures en retard et la facture du mois courant

### Règles d'accès pour les organisateurs en pause

**Important** : Seuls les **organisateurs qui paient un abonnement** sont concernés par les règles de pause. Les administrateurs et les utilisateurs simples ne sont **pas** affectés par ces règles.

#### Connexion autorisée
✅ **Tous les organisateurs en pause peuvent se connecter** à leur compte, mais avec des accès limités.

#### Tableau des actions autorisées/interdites

| Action | Possible en pause ? | Description |
|--------|---------------------|------------|
| **Accéder au dashboard** | ✅ **Oui** (En lecture seule) | L'organisateur peut voir son dashboard, mais en mode lecture seule |
| **Voir ses événements** | ✅ **Oui** | L'organisateur peut consulter la liste de ses événements existants |
| **Créer/modifier un événement** | ❌ **Non** | Impossible de créer ou modifier des événements pendant la pause |
| **Publier un événement** | ❌ **Non** | Les événements ne peuvent pas être publiés pendant la pause |
| **Vendre des billets** | ❌ **Non** | Les ventes de billets sont suspendues pendant la pause |
| **Voir les statistiques** | ✅ **Oui** | L'organisateur peut consulter ses statistiques existantes |
| **Reprendre le service** | ✅ **Oui** | L'organisateur peut reprendre son service en payant les factures en retard |

#### Conditions de mise en pause automatique

Un organisateur est automatiquement mis en pause si :
- Il ne paie pas son abonnement du mois courant **avant le 11ème jour du mois suivant**
- Exemple : Si la facture de septembre n'est pas payée avant le 11 octobre, le compte est mis en pause le 11 octobre

#### Reprise du service

Pour reprendre le service, l'organisateur doit :
1. Payer toutes les factures en retard (statut `overdue`)
2. Payer la facture du mois courant (statut `issued`)
3. Le compte reprendra automatiquement au statut `active` lors de la génération des factures du mois suivant

**Note** : Ces règles d'accès seront implémentées dans le module organisateur (front-end). Pour l'instant, seule la logique de facturation et de mise en pause automatique est active dans le module admin.

### Pour les utilisateurs

1. **S'inscrire** : Aller sur `/register`
2. **Choisir le type de compte** :
   - Utilisateur : Accès immédiat
   - Organisateur : Fournir une raison et attendre la validation

3. **Se connecter** : Aller sur `/login`
   - Si le compte est en attente, un message informatif s'affiche

### Pour les administrateurs

1. **Voir les demandes en attente** :
   ```
   Menu Admin → Validation → Demandes en attente
   ```

2. **Traiter une demande** :
   - Cliquer sur "Approuver" ou "Rejeter"
   - Ajouter un commentaire (obligatoire pour le rejet)
   - Confirmer

3. **Gérer les utilisateurs** :
   ```
   Menu Admin → Utilisateurs → Gestion
   ```
   - Rechercher un utilisateur
   - Modifier son rôle
   - Voir son historique
   - Suspendre/Supprimer

4. **Consulter l'historique** :
   ```
   Menu Admin → Historique → Actions
   ```
   - Filtrer par action, utilisateur, période
   - Voir les détails de chaque action

## Sécurité

- Toutes les routes admin sont protégées par `#[IsGranted('ROLE_ADMIN')]`
- Les actions sensibles sont enregistrées dans l'audit log avec IP et User Agent
- Les utilisateurs ne peuvent pas supprimer leur propre compte
- Les mots de passe sont hashés avec `UserPasswordHasher`

## API (si nécessaire)

Pour créer des endpoints API pour le frontend React :

### Endpoints suggérés :
- `GET /api/admin/validation/pending` : Liste des demandes en attente
- `POST /api/admin/validation/{id}/approve` : Approuver une demande
- `POST /api/admin/validation/{id}/reject` : Rejeter une demande
- `GET /api/admin/users` : Liste des utilisateurs avec filtres
- `GET /api/admin/users/{id}` : Détails d'un utilisateur
- `GET /api/admin/audit` : Historique des actions

## Personnalisation

### Modifier les rôles disponibles
Éditez `src/Form/RegistrationFormType.php` :
```php
'choices' => [
    'Utilisateur' => 'user',
    'Organisateur' => 'organizer',
    // Ajouter d'autres rôles ici
],
```

### Ajouter de nouveaux types d'actions dans l'audit
Éditez `src/Service/AuditLogService.php` :
```php
public const ACTION_CUSTOM = 'custom_action';
```

### Modifier les templates
Templates situés dans :
- `templates/admin/validation/` : Validation
- `templates/admin/users/` : Gestion utilisateurs

## Dépannage

### Les demandes n'apparaissent pas
- Vérifier que la table `user_validation_requests` existe
- Vérifier les logs : `var/log/dev.log`

### Erreurs d'autorisation
- Vérifier que l'utilisateur connecté a le rôle `ROLE_ADMIN`
- Vérifier `config/packages/security.yaml`

### Autocomplete ne fonctionne pas
- Vérifier que jQuery et Select2 sont chargés
- Vérifier la route `/admin/users/autocomplete`

## Support

Pour toute question ou problème, consultez :
- Documentation Symfony : https://symfony.com/doc
- Documentation Doctrine : https://www.doctrine-project.org/

---------
sudo cp /home/fifah/Documents/GitHub/Aiolia-event/Base/schema.sql /tmp/schema.sql
sudo chown postgres:postgres /tmp/schema.sql

sudo cp /home/fifah/Documents/GitHub/Aiolia-event/Base/logic.sql /tmp/logic.sql
sudo chown postgres:postgres /tmp/logic.sql

sudo cp /home/fifah/Documents/GitHub/Aiolia-event/Base/data.sql /tmp/data.sql
sudo chown postgres:postgres /tmp/data.sql

sudo -i -u postgres
psql

\i /tmp/schema.sql
\i /tmp/logic.sql
\i /tmp/data.sql


psql -U aiolia_user -d aiolia_event -h 127.0.0.1 -p 5432

psql -d aiolia_event

-----------
sudo cp ~/Documents/MyProject/Aiolia-event/Base/schema.sql /tmp/
sudo chown postgres:postgres /tmp/schema.sql

sudo cp ~/Documents/MyProject/Aiolia-event/Base/script.sql /tmp/
sudo chown postgres:postgres /tmp/script.sql

\i /tmp/script.sql

mail : https://mailtrap.io/home

explication : https://chatgpt.com/c/691c09da-a1b0-8328-b7a2-1b815d4289f6