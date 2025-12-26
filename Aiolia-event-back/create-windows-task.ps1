# Script PowerShell pour créer la tâche planifiée Windows
# Exécuter en tant qu'administrateur : clic droit > Exécuter avec PowerShell (en tant qu'administrateur)

param(
    [string]$TaskName = "Aiolia-UpdateEventStatus",
    [string]$PhpPath = "",
    [string]$ProjectPath = "",
    [string]$Schedule = "Daily",
    [string]$Time = "00:00"
)

Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "Création de la tâche planifiée Windows" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""

# Vérifier les privilèges administrateur
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $isAdmin) {
    Write-Host "❌ Erreur : Ce script doit être exécuté en tant qu'administrateur" -ForegroundColor Red
    Write-Host "Clic droit sur le fichier > Exécuter avec PowerShell (en tant qu'administrateur)" -ForegroundColor Yellow
    exit 1
}

# Détecter PHP si non spécifié
if ([string]::IsNullOrEmpty($PhpPath)) {
    Write-Host "Recherche de PHP..." -ForegroundColor Yellow
    $phpFound = Get-Command php -ErrorAction SilentlyContinue
    if ($phpFound) {
        $PhpPath = $phpFound.Source
        Write-Host "✓ PHP trouvé : $PhpPath" -ForegroundColor Green
    } else {
        Write-Host "❌ PHP non trouvé. Veuillez spécifier le chemin avec -PhpPath" -ForegroundColor Red
        Write-Host "Exemple : -PhpPath 'C:\php\php.exe'" -ForegroundColor Yellow
        exit 1
    }
}

# Détecter le répertoire du projet si non spécifié
if ([string]::IsNullOrEmpty($ProjectPath)) {
    $ProjectPath = $PSScriptRoot
    Write-Host "✓ Répertoire du projet : $ProjectPath" -ForegroundColor Green
}

# Vérifier que le répertoire existe
if (-not (Test-Path $ProjectPath)) {
    Write-Host "❌ Erreur : Le répertoire du projet n'existe pas : $ProjectPath" -ForegroundColor Red
    exit 1
}

# Vérifier que la commande existe
$consolePath = Join-Path $ProjectPath "bin\console"
if (-not (Test-Path $consolePath)) {
    Write-Host "❌ Erreur : Le fichier bin/console n'existe pas dans : $ProjectPath" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "Configuration de la tâche :" -ForegroundColor Cyan
Write-Host "  Nom : $TaskName" -ForegroundColor White
Write-Host "  PHP : $PhpPath" -ForegroundColor White
Write-Host "  Projet : $ProjectPath" -ForegroundColor White
Write-Host "  Planning : $Schedule à $Time" -ForegroundColor White
Write-Host ""

# Vérifier si la tâche existe déjà
$existingTask = Get-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue
if ($existingTask) {
    Write-Host "⚠ La tâche '$TaskName' existe déjà." -ForegroundColor Yellow
    $response = Read-Host "Voulez-vous la supprimer et la recréer ? (O/N)"
    if ($response -eq "O" -or $response -eq "o") {
        Unregister-ScheduledTask -TaskName $TaskName -Confirm:$false
        Write-Host "✓ Tâche existante supprimée" -ForegroundColor Green
    } else {
        Write-Host "Opération annulée." -ForegroundColor Yellow
        exit 0
    }
}

# Créer l'action (commande à exécuter)
$command = "bin/console app:update-event-status"
$action = New-ScheduledTaskAction -Execute $PhpPath -Argument $command -WorkingDirectory $ProjectPath

# Créer le déclencheur
switch ($Schedule.ToLower()) {
    "daily" {
        $trigger = New-ScheduledTaskTrigger -Daily -At $Time
    }
    "weekly" {
        $trigger = New-ScheduledTaskTrigger -Weekly -DaysOfWeek Monday -At $Time
    }
    default {
        $trigger = New-ScheduledTaskTrigger -Daily -At $Time
    }
}

# Créer le principal (utilisateur)
$principal = New-ScheduledTaskPrincipal -UserId "$env:USERDOMAIN\$env:USERNAME" -LogonType S4U -RunLevel Highest

# Créer les paramètres
$settings = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -StartWhenAvailable `
    -MultipleInstances IgnoreNew `
    -RestartCount 3 `
    -RestartInterval (New-TimeSpan -Hours 1)

# Description
$description = "Met à jour le statut des événements Aiolia Event. Vérifie les événements à venir et met à jour leur statut s'ils sont en cours (live)."

# Enregistrer la tâche
try {
    Register-ScheduledTask `
        -TaskName $TaskName `
        -Action $action `
        -Trigger $trigger `
        -Principal $principal `
        -Settings $settings `
        -Description $description | Out-Null

    Write-Host "✓ Tâche créée avec succès !" -ForegroundColor Green
    Write-Host ""
    Write-Host "Informations de la tâche :" -ForegroundColor Cyan
    Get-ScheduledTask -TaskName $TaskName | Get-ScheduledTaskInfo | Format-List
    Write-Host ""
    Write-Host "Pour tester la tâche maintenant :" -ForegroundColor Yellow
    Write-Host "  Start-ScheduledTask -TaskName '$TaskName'" -ForegroundColor White
    Write-Host ""
    Write-Host "Pour voir l'historique :" -ForegroundColor Yellow
    Write-Host "  Ouvrir le Planificateur de tâches Windows (taskschd.msc)" -ForegroundColor White
} catch {
    Write-Host "❌ Erreur lors de la création de la tâche : $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

