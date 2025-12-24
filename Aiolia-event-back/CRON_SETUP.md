# Configuration CRON pour Aiolia Event Back

Ce guide explique comment configurer l'exécution automatique des commandes Symfony.

## 🚀 Installation rapide

### Méthode 1 : Utiliser le script d'installation (recommandé)

```bash
cd /home/fifah/Documents/GitHub/Aiolia-event/Aiolia-event-back
./install-cron.sh
```

Le script va :
- Créer le répertoire de logs si nécessaire
- Sauvegarder votre crontab actuel
- Installer la configuration CRON
- Afficher la configuration installée

### Méthode 2 : Installation manuelle

1. **Éditer le crontab** :
```bash
crontab -e
```

2. **Ajouter cette ligne** (pour la commande de mise à jour des événements) :
```bash
0 0 * * * cd /home/fifah/Documents/GitHub/Aiolia-event/Aiolia-event-back && php bin/console app:update-event-status >> /var/log/aiolia/cron.log 2>&1
```

3. **Sauvegarder et quitter** (dans vim/nano : `Ctrl+X`, puis `Y`, puis `Entrée`)

## 📋 Commandes configurées

| Commande | Planning | Description |
|----------|----------|-------------|
| `app:update-event-status` | `0 0 * * *` (tous les jours à minuit) | Met à jour le statut des événements |
| `app:generate-monthly-invoices` | `0 2 27-31 * *` (5 derniers jours du mois à 2h) | Génère les factures mensuelles |
| `app:mark-overdue-invoices` | `0 3 10-15 * *` (10-15 du mois à 3h) | Marque les factures en retard |
| `app:auto-pause-unpaid-subscriptions` | `0 0 11 * *` (11ème jour du mois à minuit) | Met en pause les abonnements non payés |

## 📝 Format du planning CRON

```
* * * * *
│ │ │ │ │
│ │ │ │ └─── Jour de la semaine (0-7, 0 et 7 = dimanche)
│ │ │ └───── Mois (1-12)
│ │ └─────── Jour du mois (1-31)
│ └───────── Heure (0-23)
└─────────── Minute (0-59)
```

### Exemples

- `0 0 * * *` : Tous les jours à minuit (00:00)
- `0 2 * * *` : Tous les jours à 2h du matin
- `0 0 1 * *` : Le 1er de chaque mois à minuit
- `*/15 * * * *` : Toutes les 15 minutes

## 📊 Vérification

### Voir les tâches CRON configurées

```bash
crontab -l
```

### Voir les logs d'exécution

```bash
tail -f /var/log/aiolia/cron.log
```

### Tester une commande manuellement

```bash
cd /home/fifah/Documents/GitHub/Aiolia-event/Aiolia-event-back
php bin/console app:update-event-status
```

## 🔧 Dépannage

### Le CRON ne s'exécute pas

1. **Vérifier que le service CRON est actif** :
```bash
sudo systemctl status cron
# ou
sudo systemctl status crond
```

2. **Vérifier les permissions** :
```bash
# S'assurer que le répertoire de logs existe et est accessible
sudo mkdir -p /var/log/aiolia
sudo chmod 755 /var/log/aiolia
```

3. **Vérifier les chemins** :
```bash
# Vérifier que PHP est accessible
which php
php -v
```

### Voir les erreurs CRON

Les erreurs sont généralement dans :
```bash
# Logs système
sudo tail -f /var/log/syslog | grep CRON

# Logs de l'application
tail -f /var/log/aiolia/cron.log
```

### Modifier le planning

1. Éditer le crontab :
```bash
crontab -e
```

2. Modifier la ligne correspondante
3. Sauvegarder

### Désinstaller

```bash
# Supprimer toutes les tâches CRON
crontab -r

# Ou supprimer seulement une ligne spécifique
crontab -e
# Supprimer la ligne souhaitée
```

## 📚 Ressources

- [Documentation CRON](https://crontab.guru/)
- [Documentation Symfony Console](https://symfony.com/doc/current/console.html)

