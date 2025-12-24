#!/bin/bash

# Script d'installation du CRON pour Aiolia Event Back
# Ce script configure l'exécution automatique des commandes Symfony

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CRON_FILE="$SCRIPT_DIR/crontab.example"
LOG_DIR="/var/log/aiolia"

echo "=========================================="
echo "Installation du CRON pour Aiolia Event"
echo "=========================================="
echo ""

# Vérifier si le répertoire de logs existe
if [ ! -d "$LOG_DIR" ]; then
    echo "Création du répertoire de logs : $LOG_DIR"
    sudo mkdir -p "$LOG_DIR"
    sudo chmod 755 "$LOG_DIR"
    echo "✓ Répertoire de logs créé"
else
    echo "✓ Répertoire de logs existe déjà"
fi

# Vérifier si le fichier crontab.example existe
if [ ! -f "$CRON_FILE" ]; then
    echo "❌ Erreur : Le fichier $CRON_FILE n'existe pas"
    exit 1
fi

# Afficher le contenu du fichier CRON
echo ""
echo "Configuration CRON à installer :"
echo "--------------------------------"
cat "$CRON_FILE"
echo ""

# Demander confirmation
read -p "Voulez-vous installer cette configuration CRON ? (o/n) " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[OoYy]$ ]]; then
    echo "Installation annulée."
    exit 0
fi

# Sauvegarder l'ancien crontab s'il existe
if crontab -l > /dev/null 2>&1; then
    echo "Sauvegarde de l'ancien crontab..."
    crontab -l > "$SCRIPT_DIR/crontab.backup.$(date +%Y%m%d_%H%M%S)"
    echo "✓ Ancien crontab sauvegardé"
fi

# Remplacer les chemins dans le fichier CRON
TEMP_CRON=$(mktemp)
sed "s|/home/fifah/Documents/GitHub/Aiolia-event/Aiolia-event-back|$SCRIPT_DIR|g" "$CRON_FILE" > "$TEMP_CRON"

# Installer le nouveau crontab
echo ""
echo "Installation du nouveau crontab..."
crontab "$TEMP_CRON"
rm "$TEMP_CRON"

if [ $? -eq 0 ]; then
    echo "✓ CRON installé avec succès !"
    echo ""
    echo "Configuration installée :"
    crontab -l
    echo ""
    echo "Les commandes s'exécuteront automatiquement selon le planning configuré."
    echo "Les logs seront disponibles dans : $LOG_DIR/cron.log"
else
    echo "❌ Erreur lors de l'installation du CRON"
    exit 1
fi

