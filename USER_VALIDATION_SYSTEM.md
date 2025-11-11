# Système de Validation des Utilisateurs

## Vue d'ensemble

Ce système permet de gérer les demandes d'inscription en tant qu'organisateur ou co-organisateur. Les administrateurs doivent valider ces demandes avant que les utilisateurs puissent obtenir les rôles correspondants.

## Fonctionnalités

### 1. Inscription avec demande de rôle
- Les utilisateurs peuvent s'inscrire en choisissant leur type de compte :
  - **Utilisateur** : Compte activé immédiatement
  - **Organisateur** : Nécessite une validation admin
  - **Co-organisateur** : Nécessite une validation admin

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
- requested_role ('organizer' ou 'co_organizer')
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

### Pour les utilisateurs

1. **S'inscrire** : Aller sur `/register`
2. **Choisir le type de compte** :
   - Utilisateur : Accès immédiat
   - Organisateur/Co-organisateur : Fournir une raison et attendre la validation

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
    'Co-organisateur' => 'co_organizer',
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

sudo cp /home/fifah/Documents/GitHub/Aiolia-event/Base/test_data.sql /tmp/test_data.sql
sudo chown postgres:postgres /tmp/test_data.sql

sudo -i -u postgres
psql

\i /tmp/schema.sql
\i /tmp/logic.sql
\i /tmp/test_data.sql


psql -U aiolia_user -d aiolia_event -h 127.0.0.1 -p 5432

psql -d aiolia_event

-----------
sudo cp ~/Documents/MyProject/Aiolia-event/Base/schema.sql /tmp/
sudo chown postgres:postgres /tmp/schema.sql

sudo cp ~/Documents/MyProject/Aiolia-event/Base/script.sql /tmp/
sudo chown postgres:postgres /tmp/script.sql

\i /tmp/script.sql