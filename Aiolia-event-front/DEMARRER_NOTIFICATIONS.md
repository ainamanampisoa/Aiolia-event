# 🚀 Comment démarrer les notifications automatiques (UNE SEULE FOIS)

## 🎯 Ce que vous voulez
Exécuter **UNE SEULE COMMANDE** qui tourne en arrière-plan et envoie automatiquement les notifications.

## ✅ Solution : Script en arrière-plan

### Option 1 : Utiliser le script fourni (RECOMMANDÉ)

#### Étape 1 : Démarrer le script en arrière-plan
```bash
cd /home/aina/Documents/MyProject/Aiolia-event/Aiolia-event-front
nohup ./start_notifications.sh > /dev/null 2>&1 &
```

#### Étape 2 : Vérifier que ça fonctionne
```bash
ps aux | grep start_notifications
```
Vous devriez voir le processus en cours d'exécution.

#### Étape 3 : Voir les logs en temps réel
```bash
tail -f /home/aina/Documents/MyProject/Aiolia-event/Aiolia-event-front/var/log/reminders.log
```

#### Pour arrêter le script
```bash
pkill -f start_notifications.sh
```

---

### Option 2 : Commande directe en arrière-plan

#### Démarrer (UNE SEULE FOIS)
```bash
cd /home/aina/Documents/MyProject/Aiolia-event/Aiolia-event-front
nohup bash -c 'while true; do php bin/console app:send-event-reminders >> var/log/reminders.log 2>&1; sleep 3600; done' > /dev/null 2>&1 &
```

#### Vérifier que ça fonctionne
```bash
ps aux | grep "app:send-event-reminders"
```

#### Voir les logs
```bash
tail -f /home/aina/Documents/MyProject/Aiolia-event/Aiolia-event-front/var/log/reminders.log
```

#### Arrêter
```bash
pkill -f "app:send-event-reminders"
```

---

## 📋 Explication

### Ce qui se passe :
1. ✅ Vous exécutez **UNE SEULE FOIS** la commande
2. ✅ Elle démarre en **arrière-plan** (vous pouvez fermer le terminal)
3. ✅ Elle **tourne en continu** et exécute la commande toutes les heures
4. ✅ Les notifications sont envoyées **automatiquement**

### Fréquence :
- Par défaut : **Toutes les heures**
- Pour changer : Modifiez `sleep 3600` (3600 secondes = 1 heure)
  - 30 minutes : `sleep 1800`
  - 15 minutes : `sleep 900`

---

## 🔍 Vérifications

### Voir si le processus tourne
```bash
ps aux | grep -E "start_notifications|send-event-reminders" | grep -v grep
```

### Voir les logs en temps réel
```bash
tail -f /home/aina/Documents/MyProject/Aiolia-event/Aiolia-event-front/var/log/reminders.log
```

### Tester manuellement (sans attendre 1 heure)
```bash
cd /home/aina/Documents/MyProject/Aiolia-event/Aiolia-event-front
php bin/console app:send-event-reminders -vv
```

---

## ⚠️ Important

- Le script continue de tourner même si vous fermez le terminal (grâce à `nohup`)
- Pour arrêter complètement, utilisez `pkill` comme indiqué ci-dessus
- Les logs sont sauvegardés dans `var/log/reminders.log`

---

## 🎬 Résumé rapide

**Pour démarrer (UNE SEULE FOIS) :**
```bash
cd /home/aina/Documents/MyProject/Aiolia-event/Aiolia-event-front
nohup ./start_notifications.sh > /dev/null 2>&1 &
```

**Pour voir les logs :**
```bash
tail -f /home/aina/Documents/MyProject/Aiolia-event/Aiolia-event-front/var/log/reminders.log
```

**Pour arrêter :**
```bash
pkill -f start_notifications.sh
```

C'est tout ! 🎉
