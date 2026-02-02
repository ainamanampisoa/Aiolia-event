#!/bin/bash

# Script pour démarrer le scheduler en arrière-plan
# Ce script exécute la commande qui envoie automatiquement les notifications

cd /home/aina/Documents/MyProject/Aiolia-event/Aiolia-event-front

# Boucle infinie qui exécute la commande toutes les heures
while true; do
    echo "$(date): Exécution de l'envoi des notifications..."
    php bin/console app:send-event-reminders >> var/log/reminders.log 2>&1
    echo "$(date): Attente de 1 heure avant la prochaine exécution..."
    sleep 3600  # Attendre 1 heure (3600 secondes)
done
