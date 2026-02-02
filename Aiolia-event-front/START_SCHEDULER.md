# Guide pour automatiser les notifications d'événements

## Solution recommandée : Cron Job

La solution la plus simple et fiable est d'utiliser un cron job qui exécute la commande toutes les heures.

### Configuration du Cron Job

1. Ouvrez le crontab :
```bash
crontab -e
```

2. Ajoutez cette ligne pour exécuter la commande toutes les heures :
```cron
0 * * * * cd /home/aina/Documents/MyProject/Aiolia-event/Aiolia-event-front && php bin/console app:send-event-reminders >> /var/log/aiolia-reminders.log 2>&1
```

Cette ligne signifie :
- `0 * * * *` : Toutes les heures à la minute 0 (ex: 00:00, 01:00, 02:00, etc.)
- `cd ...` : Se placer dans le répertoire du projet
- `php bin/console app:send-event-reminders` : Exécuter la commande
- `>> /var/log/aiolia-reminders.log 2>&1` : Rediriger les logs vers un fichier

### Alternative : Toutes les 30 minutes

Si vous voulez vérifier plus fréquemment (pour ne pas manquer les événements) :

```cron
*/30 * * * * cd /home/aina/Documents/MyProject/Aiolia-event/Aiolia-event-front && php bin/console app:send-event-reminders >> /var/log/aiolia-reminders.log 2>&1
```

### Vérification

1. Vérifiez que le cron job est bien configuré :
```bash
crontab -l
```

2. Vérifiez les logs :
```bash
tail -f /var/log/aiolia-reminders.log
```

3. Testez manuellement la commande :
```bash
cd /home/aina/Documents/MyProject/Aiolia-event/Aiolia-event-front
php bin/console app:send-event-reminders -vv
```

## Configuration actuelle

- **Fréquence** : Toutes les heures (configurable via cron)
- **Commande** : `app:send-event-reminders`
- **Notifications** : 24h et 2h avant les événements
- **Filtres** : 
  - Seulement les événements publiés
  - Seulement les utilisateurs avec des tickets
  - Seulement les utilisateurs qui ont activé les rappels dans leurs paramètres

## Notes importantes

- Le cron job s'exécute automatiquement en arrière-plan
- Les notifications sont envoyées uniquement aux utilisateurs qui ont activé les rappels
- Les notifications sont envoyées uniquement aux utilisateurs qui ont des tickets pour l'événement
- Les notifications ne sont envoyées qu'une seule fois par événement (vérification de doublon)
