# Comment automatiser l'envoi des notifications d'événements

## 🎯 Objectif
Faire en sorte que les notifications (24h et 2h avant les événements) s'envoient automatiquement sans avoir à exécuter manuellement la commande.

## 📋 Solution : Utiliser un Cron Job

Un cron job est un système qui exécute automatiquement des commandes à des moments précis.

## 🔧 Étapes pour configurer

### Étape 1 : Ouvrir l'éditeur de cron
Dans votre terminal, tapez :
```bash
crontab -e
```

### Étape 2 : Ajouter la ligne d'automatisation
Quand l'éditeur s'ouvre, ajoutez cette ligne à la fin du fichier :

```cron
0 * * * * cd /home/aina/Documents/MyProject/Aiolia-event/Aiolia-event-front && php bin/console app:send-event-reminders >> /home/aina/Documents/MyProject/Aiolia-event/Aiolia-event-front/var/log/reminders.log 2>&1
```

### Étape 3 : Sauvegarder et quitter
- Si vous utilisez `nano` : Appuyez sur `Ctrl+X`, puis `O` (Oui), puis `Entrée`
- Si vous utilisez `vi` : Tapez `:wq` puis `Entrée`

## 📝 Explication de la ligne cron

```
0 * * * *  =  Toutes les heures à la minute 0 (00:00, 01:00, 02:00, etc.)
cd ...     =  Aller dans le dossier du projet
php ...    =  Exécuter la commande pour envoyer les notifications
>> ...     =  Sauvegarder les résultats dans un fichier log
```

## ✅ Vérifier que ça fonctionne

### 1. Vérifier que le cron est bien configuré
```bash
crontab -l
```
Vous devriez voir la ligne que vous avez ajoutée.

### 2. Tester manuellement la commande
```bash
cd /home/aina/Documents/MyProject/Aiolia-event/Aiolia-event-front
php bin/console app:send-event-reminders -vv
```

### 3. Vérifier les logs
```bash
tail -f /home/aina/Documents/MyProject/Aiolia-event/Aiolia-event-front/var/log/reminders.log
```

## ⚙️ Options de fréquence

### Option A : Toutes les heures (recommandé)
```cron
0 * * * * cd /home/aina/Documents/MyProject/Aiolia-event/Aiolia-event-front && php bin/console app:send-event-reminders >> /home/aina/Documents/MyProject/Aiolia-event/Aiolia-event-front/var/log/reminders.log 2>&1
```

### Option B : Toutes les 30 minutes (plus fréquent)
```cron
*/30 * * * * cd /home/aina/Documents/MyProject/Aiolia-event/Aiolia-event-front && php bin/console app:send-event-reminders >> /home/aina/Documents/MyProject/Aiolia-event/Aiolia-event-front/var/log/reminders.log 2>&1
```

### Option C : Toutes les 15 minutes (très fréquent)
```cron
*/15 * * * * cd /home/aina/Documents/MyProject/Aiolia-event/Aiolia-event-front && php bin/console app:send-event-reminders >> /home/aina/Documents/MyProject/Aiolia-event/Aiolia-event-front/var/log/reminders.log 2>&1
```

## 🎬 Résultat attendu

Une fois configuré :
- ✅ Le système vérifie automatiquement toutes les heures s'il y a des événements à 24h ou 2h
- ✅ Les notifications sont envoyées automatiquement aux utilisateurs concernés
- ✅ Vous n'avez plus besoin d'exécuter manuellement la commande
- ✅ Les logs sont sauvegardés dans `var/log/reminders.log`

## ❓ Questions fréquentes

**Q : Est-ce que ça fonctionne même si je ferme mon ordinateur ?**
R : Non, le cron job ne fonctionne que si votre ordinateur est allumé. Pour la production, il faut installer cela sur un serveur qui reste allumé.

**Q : Comment arrêter l'automatisation ?**
R : Exécutez `crontab -e` et supprimez ou commentez la ligne (ajoutez `#` au début).

**Q : Comment voir si ça fonctionne ?**
R : Vérifiez les logs avec `tail -f var/log/reminders.log` ou testez manuellement avec `php bin/console app:send-event-reminders -vv`
