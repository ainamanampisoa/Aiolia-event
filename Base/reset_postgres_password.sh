#!/bin/bash
# Script pour réinitialiser le mot de passe PostgreSQL
# À exécuter avec sudo

echo "Réinitialisation du mot de passe PostgreSQL..."
echo "Vous pouvez soit :"
echo "1. Modifier le fichier pg_hba.conf pour permettre l'authentification locale sans mot de passe"
echo "2. Utiliser sudo -u postgres psql pour accéder directement"
echo ""
echo "Option rapide :"
echo "sudo -u postgres psql -d aiolia_event -f /home/aina/Documents/MyProject/Aiolia-event/Base/migration_remove_order_statuses_superuser.sql"

