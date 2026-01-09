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

**Résumé des comptes disponibles** :
- **2 administrateurs** (tous actifs)
- **26 organisateurs** (23 validés, 3 en attente de validation)
- **78 utilisateurs** (tous actifs)
- **Total : 106 comptes**

#### Administrateurs (2 comptes disponibles)
| Email | Mot de passe | Statut | ID |
|-------|--------------|--------|-----|
| admin1@yopmail.com | azerty | Actif | 1 |
| admin2@yopmail.com | azerty | Actif | 2 |

#### Organisateurs (23 comptes validés disponibles sur 26 créés)
| Email | Mot de passe | Statut | ID | Notes |
|-------|--------------|--------|-----|-------|
| organisateur001@yopmail.com | Org#Test123 | Validé | 3 | Actif - Juin 2025 |
| organisateur010@yopmail.com | Org#Test123 | Validé | 12 | Actif - Juin 2025 |
| organisateur011@yopmail.com | Org#Test123 | Validé | 13 | Actif - Juillet 2025 |
| organisateur012@yopmail.com | Org#Test123 | Validé | 14 | Actif - Juillet 2025 |
| organisateur023@yopmail.com | Org#Test123 | Validé | 23 | Actif - Octobre 2025 |
| organisateur024@yopmail.com | Org#Test123 | Validé | 24 | Actif - Octobre 2025 |
| organisateur026@yopmail.com | Org#Test123 | Validé | 26 | Actif - Novembre 2025 |


**Notes importantes** :
- **Total : 23 organisateurs validés** (IDs 3-24, 26) peuvent se connecter
- **3 organisateurs non validés** (IDs 25, 27-28) ne peuvent pas se connecter
- **Répartition par mois de création** :
  - Juin 2025 : 10 organisateurs (IDs 3-12) - Tous validés
  - Juillet 2025 : +2 nouveaux organisateurs (IDs 13-14) - Tous validés
  - Août 2025 : +4 nouveaux organisateurs (IDs 15-18) - Tous validés
  - Septembre 2025 : +4 nouveaux organisateurs (IDs 19-22) - Tous validés
  - Octobre 2025 : +3 nouveaux organisateurs (IDs 23-25) - 2 validés (23-24), 1 non validé (25)
  - Novembre 2025 : +3 nouveaux organisateurs (IDs 26-28) - 1 validé (26), 2 non validés (27-28)
- **Pauses d'abonnement** (selon les critères) :
  - Août 2025 : 2 organisateurs en pause (payant mensuel) qui reprennent en octobre
  - Octobre 2025 : 4 organisateurs en pause (2 payant mensuel, 2 payant trimestre) qui reprennent en décembre
- Tous les organisateurs validés en pause **peuvent se connecter**, mais avec des accès limités (voir section ci-dessous)

#### Détails des abonnements par mois

**Juin 2025** :
- 10 organisateurs actifs, 0 en pause, 0 non validés
- Abonnements mensuels : 5 Basic, 2 Pro, 3 Entreprise
- 10 organisateurs paient mensuellement
- Offre populaire : Basic Mensuel

**Juillet 2025** :
- 12 organisateurs actifs, 0 en pause, 0 non validés
- Abonnements mensuels : 4 Basic, 6 Pro, 2 Entreprise
- 12 organisateurs paient mensuellement
- Offre populaire : Pro Mensuel

**Août 2025** :
- 16 organisateurs actifs, 0 en pause, 0 non validés
- Abonnements mensuels : 4 Basic, 5 Pro, 7 Entreprise
- 16 organisateurs paient mensuellement
- Offre populaire : Entreprise Mensuel
- 2 organisateurs en pause (payant mensuel) qui reprennent en octobre

**Septembre 2025** :
- 18 organisateurs actifs, 2 en pause, 0 non validés
- Abonnements mensuels : 2 Basic, 3 Pro, 4 Entreprise
- Abonnements trimestriels : 3 Basic, 4 Pro, 2 Entreprise
- 9 organisateurs paient mensuellement, 9 organisateurs paient trimestriellement
- Offre populaire : Pro Trimestriel

**Octobre 2025** :
- 22 organisateurs actifs, 0 en pause, 1 non validé
- Abonnements mensuels : 3 Basic, 3 Pro, 2 Entreprise
- Abonnements trimestriels : 1 Basic, 2 Pro, 4 Entreprise
- 6 organisateurs paient mensuellement, 7 organisateurs paient trimestriellement, 9 prépayés
- Offre populaire : Entreprise Trimestriel
- 4 organisateurs en pause (2 payant mensuel, 2 payant trimestre) qui reprennent en décembre

**Novembre 2025** :
- 19 organisateurs actifs, 4 en pause, 3 non validés
- Abonnements mensuels : 0 Basic, 2 Pro, 1 Entreprise
- Abonnements trimestriels : 1 Basic, 1 Pro, 0 Entreprise
- 3 organisateurs paient mensuellement, 2 organisateurs paient trimestriellement, 14 prépayés
- Offre populaire : Pro Mensuel

**Décembre 2025** :
- 23 organisateurs actifs, 0 en pause, 3 non validés
- 0 organisateurs paient mensuellement, 0 organisateurs paient trimestriellement, 7 prépayés
- Offre populaire : Entreprise Trimestriel

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

sudo cp /home/fifah/Documents/GitHub/Aiolia-event/Base/Events.sql /tmp/Events.sql
sudo chown postgres:postgres /tmp/Events.sql

sudo cp /home/fifah/Documents/GitHub/Aiolia-event/Base/factures_janvier_2026.sql /tmp/factures_janvier_2026.sql
sudo chown postgres:postgres /tmp/factures_janvier_2026.sql

sudo -i -u postgres
psql

\i /tmp/schema.sql
\i /tmp/logic.sql
\i /tmp/data.sql
\i /tmp/Events.sql
\i /tmp/factures_janvier_2026.sql

comment est le Gestion quotas billets par catégorie 


psql -U aiolia_user -d aiolia_event -h 127.0.0.1 -p 5432

psql -d aiolia_event

-----------
sudo cp ~/Documents/MyProject/Aiolia-event/Base/schema.sql /tmp/
sudo chown postgres:postgres /tmp/schema.sql

sudo cp ~/Documents/MyProject/Aiolia-event/Base/script.sql /tmp/
sudo chown postgres:postgres /tmp/script.sql

\i /tmp/script.sql
j
mail : https://mailtrap.io/home

Dbdiagrma : user : https://dbdiagram.io/d/69411519137ea0780bded518
            back : https://dbdiagram.io/d/6942755be4bb1dd3a969e46f

explication : https://chatgpt.com/c/691c09da-a1b0-8328-b7a2-1b815d4289f6

cloud : https://cloudinary.com/users/login

📊 Rapports mensuels (abonnements)
Synthèse des abonnements et revenus par mois

redonne moi le @data.sql avec ces critere
# 2 admin
# Abonnement : facture des mois
# 45 utilisateur 
# pour le nombre organisateur: 26
-juin 2025 : 10 (actifs)
-juillet 2025 : + plus 2 new organisateur 
-aout 2025: +  plus 4 new organisateur
-septembre 2025: +  plus 4 new organisateur
-octobre 2025: +  plus 3 new organisateur(1 non valider)
-novembre 2025: + plus 3 new organisateur (2 non valider)
! offre populaire (le plus utiliser): pro trimestre (mensuelle < trimenstre < annulle)

-juin 2025 : 
=> 10 organisateurs actifs ,0 organisateur en pausse ,0 organisateur non valider 
=> 10 factures paye mensuelle mensuelle (5 factures basic , 2 factures pro,3 factures entreprise)

-juillet 2025: 
=> 12 organisateurs actifs ,0 organisateur en pausse ,0 organisateur non valider 
=> 12 factures paye mensuelle(4 factures basic , 6 factures pro,2 factures entreprise)

-aout 2025: 
=> 16 organisateurs actifs ,0 organisateur en pausse ,0 organisateur non valider 
=> 16 factures paye mensuelle ( 4 factures basic , 5 factures pro,7 factures entreprise)

-septembre 2025:  
=> 20 organisateurs actifs , 2 organisateur en pausse ,0 organisateur non valider 
=> 11 facture paye mensuelle (3 factures basic , 3 factures pro,5 factures entreprise)
=> 9 factures paye trimestre(3 factures basic , 4 factures pro,2 factures entreprise)


-octobre 2025: mensuelle (3 factures basic , 3 factures pro,2 factures entreprise)
-octobre 2025: trimestre (1 factures basic , 2 factures pro,4 factures entreprise)
=> 22 organisateurs actifs ,0 organisateur en pausse ,total 1 organisateur  non valider 
=> 6 factures paye mensuelle(2 factures basic , 1 factures pro,2 factures entreprise),
=> 7 factures paye trimestre (1 factures basic , 2 factures pro,4 factures entreprise),

  
-novembre 2025: mesuelle (0 factures basic , 2 factures pro,1 factures entreprise)
-novembre 2025: trimestre (1 factures basic , 1 factures pro,0 factures entreprise)
=> 23 organisateurs actifs ,4 organisateur en pausse ,total 3 organisateur non valider 
=> 5 factures paye mensuelle (1 factures basic , 3 factures pro,1 factures entreprise)
=> 2 factures paye trimestre (1 factures basic , 1 factures pro,0 factures entreprise)

-decembre 2025:
=> 23 organisateurs actifs ,0 organisateur en pausse ,total 3 organisateur non valider 
=> 10 factures paye mensuelle (2 factures basic , 3 factures pro,5 factures entreprise)
=> 4 factures paye trimestre (1 factures basic , 1 factures pro,2 factures entreprise) 




Header:
Authorization: Bearer mon_token
Version: 1.0
X-CorrelationID: mvola-12345678
UserLanguage: mg
UserAccountIdentifier: msisdn;0382795455
partnerName: AioliaEvent
Content-Type: application/json
Cache-Control: no-cache
X-Callback-URL: http://localhost:8000/api/mvola/callback
Body:
{
  "amount": "1000",
  "currency": "Ar",
  "descriptionText": "Test-paiement",
  "requestingOrganisationTransactionReference": "TEST-001",
  "requestDate": "2025-12-08T18:00:00.000Z",
  "originalTransactionReference": "",
  "debitParty": [
    { "key": "msisdn", "value": "0343500003" }
  ],
  "creditParty": [
    { "key": "msisdn", "value": "0382795455" }
  ],
  "metadata": [
    { "key": "partnerName", "value": "AioliaEvent" }
  ]
}


lien slide : https://www.canva.com/design/DAG9tgKvHJM/nMD5IrBJm-ZJvHXvJbUckQ/edit?ui=eyJBIjp7fX0