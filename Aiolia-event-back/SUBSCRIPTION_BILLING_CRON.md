# Configuration CRON pour la Gestion d'Abonnements et Facturation Mensuelle

## Vue d'ensemble

Ce document décrit la configuration des tâches CRON pour la gestion automatique des abonnements et de la facturation mensuelle.

## Cycle de facturation type

- **7 jours avant fin du mois** : Rappel du prochain paiement
- **1er jour du mois** : Génération automatique de la facture
- **J+4** : Avertissement si facture impayée
- **J+7** : Dernier rappel
- **J+10** : Suspension du compte (optionnel)

## Tâches CRON à configurer

### 1. Génération des factures mensuelles

**Commande :** `php bin/console app:generate-monthly-invoices`

**Planification :** `0 1 1 * *` (Chaque 1er du mois à 1h du matin)

**Description :**
- Parcourt tous les abonnements actifs dont la date de renouvellement est aujourd'hui
- Génère une facture pour le mois en cours
- Met à jour `renouvellement_le` au mois suivant
- Envoie notification "Nouvelle facture disponible"

**Exemple de configuration CRON :**
```bash
0 1 1 * * cd /path/to/project && php bin/console app:generate-monthly-invoices >> /var/log/cron-invoices.log 2>&1
```

### 2. Rappel pré-facturation (7 jours avant)

**Commande :** `php bin/console app:pre-billing-reminder`

**Planification :** `0 10 24-31 * *` (Tous les jours entre le 24 et 31 du mois à 10h)

**Description :**
- Vérifie si on est exactement 7 jours avant la fin du mois
- Filtre uniquement les abonnements avec `auto_renew = true`
- Envoie notification de rappel avec montant à venir

**Exemple de configuration CRON :**
```bash
0 10 24-31 * * cd /path/to/project && php bin/console app:pre-billing-reminder >> /var/log/cron-reminder.log 2>&1
```

### 3. Avertissement factures impayées (J+4)

**Commande :** `php bin/console app:overdue-invoice-warning`

**Planification :** `0 9 * * *` (Tous les jours à 9h)

**Description :**
- Recherche les factures avec status "issued" ou "draft"
- Dont la date d'échéance (`dueAt`) est dépassée de 4 jours exactement
- Marque comme "overdue"
- Envoie avertissement avec lien de paiement urgent

**Exemple de configuration CRON :**
```bash
0 9 * * * cd /path/to/project && php bin/console app:overdue-invoice-warning >> /var/log/cron-overdue.log 2>&1
```

### 4. Dernier rappel (J+7)

**Commande :** `php bin/console app:final-reminder`

**Planification :** `0 14 * * *` (Tous les jours à 14h)

**Description :**
- Factures impayées depuis 7 jours
- Notification "Dernier rappel avant suspension"
- Ton plus ferme avec conséquences

**Exemple de configuration CRON :**
```bash
0 14 * * * cd /path/to/project && php bin/console app:final-reminder >> /var/log/cron-final-reminder.log 2>&1
```

### 5. Suspension automatique (J+10)

**Commande :** `php bin/console app:suspend-subscriptions`

**Planification :** `0 18 * * *` (Tous les jours à 18h)

**Description :**
- Factures impayées depuis 10 jours
- Change status abonnement en "paused"
- Notification de suspension
- Désactive l'accès aux fonctionnalités premium

**Exemple de configuration CRON :**
```bash
0 18 * * * cd /path/to/project && php bin/console app:suspend-subscriptions >> /var/log/cron-suspend.log 2>&1
```

## Configuration complète du crontab

Pour configurer toutes les tâches d'un coup, ajoutez ces lignes à votre crontab :

```bash
# Génération des factures mensuelles (1er du mois à 1h)
0 1 1 * * cd /path/to/Aiolia-event-back && php bin/console app:generate-monthly-invoices >> /var/log/cron-invoices.log 2>&1

# Rappel pré-facturation (7 jours avant fin du mois, entre le 24 et 31 à 10h)
0 10 24-31 * * cd /path/to/Aiolia-event-back && php bin/console app:pre-billing-reminder >> /var/log/cron-reminder.log 2>&1

# Avertissement factures impayées J+4 (tous les jours à 9h)
0 9 * * * cd /path/to/Aiolia-event-back && php bin/console app:overdue-invoice-warning >> /var/log/cron-overdue.log 2>&1

# Dernier rappel J+7 (tous les jours à 14h)
0 14 * * * cd /path/to/Aiolia-event-back && php bin/console app:final-reminder >> /var/log/cron-final-reminder.log 2>&1

# Suspension automatique J+10 (tous les jours à 18h)
0 18 * * * cd /path/to/Aiolia-event-back && php bin/console app:suspend-subscriptions >> /var/log/cron-suspend.log 2>&1
```

## Installation

### Linux/Unix

1. Éditez le crontab :
```bash
crontab -e
```

2. Ajoutez les lignes ci-dessus en remplaçant `/path/to/Aiolia-event-back` par le chemin réel de votre projet.

3. Vérifiez la configuration :
```bash
crontab -l
```

### Windows (Task Scheduler)

Pour Windows, utilisez le Planificateur de tâches. Voir `WINDOWS_TASK_SCHEDULER.md` pour plus de détails.

## Services créés

### SubscriptionNotificationService

Service centralisé pour gérer toutes les notifications liées aux abonnements :
- `sendPreBillingReminder()` : Rappel pré-facturation
- `sendInvoiceGenerated()` : Notification de nouvelle facture
- `sendOverdueWarning()` : Avertissement facture impayée
- `sendFinalReminder()` : Dernier rappel
- `sendSuspensionNotification()` : Notification de suspension

### MonthlyInvoiceGenerationService

Service pour générer automatiquement les factures mensuelles :
- `generateMonthlyInvoices()` : Génère les factures pour tous les abonnements actifs

## Templates d'emails

Tous les templates d'emails sont disponibles dans `templates/emails/subscription/` :
- `pre_billing_reminder.html.twig` : Rappel pré-facturation
- `invoice_generated.html.twig` : Nouvelle facture disponible
- `overdue_warning.html.twig` : Avertissement facture impayée
- `final_reminder.html.twig` : Dernier rappel
- `suspension_notification.html.twig` : Notification de suspension

## Tests manuels

Pour tester les commandes manuellement :

```bash
# Génération des factures
php bin/console app:generate-monthly-invoices

# Rappel pré-facturation
php bin/console app:pre-billing-reminder

# Avertissement factures impayées
php bin/console app:overdue-invoice-warning

# Dernier rappel
php bin/console app:final-reminder

# Suspension
php bin/console app:suspend-subscriptions
```

## Logs

Les logs sont écrits dans `/var/log/` par défaut. Assurez-vous que les permissions sont correctes :

```bash
sudo touch /var/log/cron-invoices.log
sudo touch /var/log/cron-reminder.log
sudo touch /var/log/cron-overdue.log
sudo touch /var/log/cron-final-reminder.log
sudo touch /var/log/cron-suspend.log
sudo chmod 666 /var/log/cron-*.log
```

## Protection contre les doublons

Le système est protégé contre les doublons de factures à plusieurs niveaux :

1. **Contrainte unique en base de données** : La table `factures_abonnements` a une contrainte unique `uq_factures_abonnements_abonnement_mois` sur `(id_abonnement, mois_facturation)` qui garantit qu'il ne peut y avoir qu'une seule facture par abonnement et par mois.

2. **Vérifications multiples dans le code PHP** : Le service `MonthlyInvoiceGenerationService` effectue 4 vérifications avant de créer une facture :
   - Vérification via `OrganizerSubscriptionRepository`
   - Vérification directe via `SubscriptionInvoiceRepository`
   - Vérification du numéro de facture
   - Vérification finale avant persistance (pour éviter les race conditions)

3. **Gestion des exceptions** : Si une contrainte unique est violée (race condition), le système récupère automatiquement la facture existante.

4. **Fonctions SQL** : Les fonctions PostgreSQL dans `Base/data.sql` vérifient également les doublons avant insertion et gèrent les exceptions de contrainte unique.

## Notes importantes

1. **Fuseau horaire** : Assurez-vous que le serveur utilise le bon fuseau horaire (Indian/Antananarivo pour Madagascar).

2. **Permissions** : Les commandes doivent être exécutables par l'utilisateur CRON.

3. **Variables d'environnement** : Si vous utilisez des variables d'environnement, assurez-vous qu'elles sont disponibles dans le contexte CRON.

4. **Base de données** : Les commandes nécessitent un accès à la base de données. Vérifiez les permissions.

5. **Email** : Assurez-vous que la configuration SMTP est correcte dans `.env` pour l'envoi des notifications.

6. **Contrainte unique** : La contrainte unique `uq_factures_abonnements_abonnement_mois` est définie dans `Base/schema.sql`. Si vous avez une base de données existante, vous devrez ajouter cette contrainte manuellement ou recréer la base.

## Dépannage

### Les commandes ne s'exécutent pas

1. Vérifiez les logs CRON : `grep CRON /var/log/syslog`
2. Vérifiez les permissions des fichiers
3. Testez les commandes manuellement
4. Vérifiez le chemin absolu dans le crontab

### Les emails ne sont pas envoyés

1. Vérifiez la configuration SMTP dans `.env`
2. Testez l'envoi d'email manuellement
3. Vérifiez les logs Symfony : `var/log/dev.log` ou `var/log/prod.log`

### Les factures ne sont pas générées

1. Vérifiez que les abonnements ont `renouvellement_le` défini correctement
2. Vérifiez que les abonnements sont actifs
3. Vérifiez les logs de la commande

