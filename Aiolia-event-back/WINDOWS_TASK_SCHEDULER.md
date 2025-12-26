# Configuration du Planificateur de tâches Windows pour Aiolia Event Back

Ce guide explique comment configurer l'exécution automatique des commandes Symfony sous Windows.

## 🪟 Alternatives à CRON sous Windows

### Option 1 : Planificateur de tâches Windows (recommandé)

Le Planificateur de tâches Windows est l'équivalent de CRON sous Linux.

### Option 2 : Script PowerShell avec boucle

Un script qui s'exécute en continu et vérifie l'heure.

### Option 3 : WSL (Windows Subsystem for Linux)

Utiliser Linux dans Windows et installer CRON normalement.

---

## 📋 Méthode 1 : Planificateur de tâches Windows (GUI)

### Étape 1 : Ouvrir le Planificateur de tâches

1. Appuyez sur `Windows + R`
2. Tapez `taskschd.msc` et appuyez sur Entrée
3. Ou recherchez "Planificateur de tâches" dans le menu Démarrer

### Étape 2 : Créer une tâche de base

1. Cliquez sur **"Créer une tâche..."** (à droite)
2. Onglet **"Général"** :
   - **Nom** : `Aiolia - Mise à jour statut événements`
   - **Description** : `Met à jour le statut des événements tous les jours à minuit`
   - Cochez **"Exécuter que l'utilisateur soit connecté ou non"**
   - Cochez **"Exécuter avec les privilèges les plus élevés"**

### Étape 3 : Configurer le déclencheur

1. Onglet **"Déclencheurs"**
2. Cliquez sur **"Nouveau..."**
3. Configurez :
   - **Commencer la tâche** : `Selon une planification`
   - **Paramètres** : `Quotidiennement`
   - **Heure de début** : `00:00:00` (minuit)
   - **Répéter la tâche toutes les** : `1 jours`
   - Cochez **"Activer"**
4. Cliquez sur **"OK"**

### Étape 4 : Configurer l'action

1. Onglet **"Actions"**
2. Cliquez sur **"Nouveau..."**
3. Configurez :
   - **Action** : `Démarrer un programme`
   - **Programme/script** : `C:\php\php.exe` (ou le chemin vers votre PHP)
   - **Ajouter des arguments** : `bin/console app:update-event-status`
   - **Démarrer dans** : `C:\chemin\vers\Aiolia-event-back` (votre répertoire du projet)
4. Cliquez sur **"OK"**

### Étape 5 : Configurer les conditions (optionnel)

1. Onglet **"Conditions"**
2. Décochez **"Démarrer la tâche uniquement si l'ordinateur est branché sur secteur"** (si vous voulez que ça fonctionne sur batterie)
3. Cochez **"Réveiller l'ordinateur pour exécuter cette tâche"** (si nécessaire)

### Étape 6 : Configurer les paramètres

1. Onglet **"Paramètres"**
2. Cochez **"Autoriser l'exécution de la tâche à la demande"**
3. Cochez **"Si la tâche échoue, redémarrer toutes les"** : `1 heure`
4. **Nombre de nouvelles tentatives** : `3`

### Étape 7 : Enregistrer

1. Cliquez sur **"OK"**
2. Entrez votre mot de passe Windows si demandé
3. La tâche est maintenant programmée !

---

## 💻 Méthode 2 : Planificateur de tâches (Ligne de commande)

### Créer la tâche via PowerShell (en tant qu'administrateur)

```powershell
# Ouvrir PowerShell en tant qu'administrateur
# Appuyez sur Windows + X, puis sélectionnez "Windows PowerShell (Admin)"

# Variables à adapter
$taskName = "Aiolia-UpdateEventStatus"
$phpPath = "C:\php\php.exe"  # Chemin vers PHP
$projectPath = "C:\Users\VotreNom\Documents\GitHub\Aiolia-event\Aiolia-event-back"
$command = "bin/console app:update-event-status"

# Créer la tâche
$action = New-ScheduledTaskAction -Execute $phpPath -Argument $command -WorkingDirectory $projectPath
$trigger = New-ScheduledTaskTrigger -Daily -At "00:00"
$principal = New-ScheduledTaskPrincipal -UserId "$env:USERDOMAIN\$env:USERNAME" -LogonType S4U -RunLevel Highest
$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable

Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Principal $principal -Settings $settings -Description "Met à jour le statut des événements tous les jours à minuit"
```

### Vérifier la tâche

```powershell
# Voir toutes les tâches
Get-ScheduledTask | Where-Object {$_.TaskName -like "*Aiolia*"}

# Voir les détails d'une tâche
Get-ScheduledTask -TaskName "Aiolia-UpdateEventStatus" | Get-ScheduledTaskInfo

# Exécuter la tâche manuellement
Start-ScheduledTask -TaskName "Aiolia-UpdateEventStatus"
```

### Supprimer la tâche

```powershell
Unregister-ScheduledTask -TaskName "Aiolia-UpdateEventStatus" -Confirm:$false
```

---

## 🔧 Méthode 3 : Script batch avec boucle (simple mais moins fiable)

Créez un fichier `run-scheduler.bat` :

```batch
@echo off
:loop
for /f "tokens=2 delims==" %%I in ('wmic os get localdatetime /value') do set datetime=%%I
set hour=%datetime:~8,2%
set minute=%datetime:~10,2%

REM Exécuter à minuit (00:00)
if "%hour%%minute%"=="0000" (
    echo [%date% %time%] Execution de la commande...
    cd /d C:\chemin\vers\Aiolia-event-back
    php bin/console app:update-event-status
    timeout /t 60 /nobreak >nul
)

REM Attendre 1 minute avant de vérifier à nouveau
timeout /t 60 /nobreak >nul
goto loop
```

**Note** : Cette méthode nécessite que le script tourne en permanence.

---

## 🐧 Méthode 4 : WSL (Windows Subsystem for Linux)

Si vous avez WSL installé, vous pouvez utiliser CRON normalement :

1. **Installer WSL** (si pas déjà fait) :
```powershell
wsl --install
```

2. **Dans WSL, installer CRON** :
```bash
sudo apt update
sudo apt install cron
sudo service cron start
```

3. **Configurer CRON comme sous Linux** :
```bash
crontab -e
# Ajouter la ligne :
0 0 * * * cd /mnt/c/chemin/vers/Aiolia-event-back && php bin/console app:update-event-status
```

---

## 📊 Comparaison des méthodes

| Méthode | Avantages | Inconvénients |
|---------|-----------|---------------|
| **Planificateur de tâches (GUI)** | Facile, interface graphique, fiable | Nécessite Windows |
| **Planificateur de tâches (CLI)** | Automatisable, scriptable | Nécessite PowerShell admin |
| **Script batch** | Simple, pas de configuration | Moins fiable, doit tourner en continu |
| **WSL + CRON** | Identique à Linux, très fiable | Nécessite WSL installé |

---

## ✅ Recommandation

**Utilisez le Planificateur de tâches Windows (Méthode 1 ou 2)** : c'est l'équivalent natif de CRON et la méthode la plus fiable sous Windows.

---

## 🔍 Vérification et dépannage

### Voir l'historique d'exécution

1. Ouvrez le Planificateur de tâches
2. Trouvez votre tâche dans la liste
3. Cliquez dessus
4. En bas, onglet **"Historique"** pour voir les exécutions

### Vérifier les logs

Les erreurs peuvent être vues dans :
- **Observateur d'événements Windows** : `eventvwr.msc`
- **Historique de la tâche** dans le Planificateur de tâches

### Tester manuellement

```powershell
# Exécuter la tâche immédiatement
Start-ScheduledTask -TaskName "Aiolia-UpdateEventStatus"
```

---

## 📚 Ressources

- [Documentation Microsoft - Planificateur de tâches](https://docs.microsoft.com/fr-fr/windows/win32/taskschd/task-scheduler-start-page)
- [Documentation PowerShell - Scheduled Tasks](https://docs.microsoft.com/fr-fr/powershell/module/scheduledtasks/)

