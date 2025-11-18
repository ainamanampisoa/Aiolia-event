#!/bin/bash
# Script pour exécuter mydata.sql étape par étape
# Usage: ./execute_mydata_step_by_step.sh

DB_HOST="localhost"
DB_USER="aiolia_user"
DB_NAME="aiolia_event"
SQL_FILE="/home/aina/Documents/MyProject/Aiolia-event/Base/mydata.sql"

echo "=========================================="
echo "Exécution de mydata.sql étape par étape"
echo "=========================================="
echo ""

# Vérifier si le fichier existe
if [ ! -f "$SQL_FILE" ]; then
    echo "❌ Erreur: Le fichier $SQL_FILE n'existe pas!"
    exit 1
fi

echo "📁 Fichier: $SQL_FILE"
echo "🔌 Connexion: $DB_USER@$DB_HOST/$DB_NAME"
echo ""
read -p "Appuyez sur Entrée pour continuer ou Ctrl+C pour annuler..."

echo ""
echo "🚀 Exécution du script SQL..."
echo ""

# Exécuter le script SQL
psql -h "$DB_HOST" -U "$DB_USER" -d "$DB_NAME" -f "$SQL_FILE"

# Vérifier le résultat
if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Script exécuté avec succès!"
    echo ""
    echo "📊 Vérification des données insérées:"
    echo ""
    
    # Vérifier les événements
    echo "Événements créés:"
    psql -h "$DB_HOST" -U "$DB_USER" -d "$DB_NAME" -c "SELECT COUNT(*) as total FROM aiolia.events;"
    
    echo ""
    echo "Types de billets créés:"
    psql -h "$DB_HOST" -U "$DB_USER" -d "$DB_NAME" -c "SELECT COUNT(*) as total FROM aiolia.ticket_types;"
    
    echo ""
    echo "Liste des événements:"
    psql -h "$DB_HOST" -U "$DB_USER" -d "$DB_NAME" -c "SELECT id, slug, title, status FROM aiolia.events ORDER BY id;"
    
    echo ""
    echo "Types de billets avec catégories d'âge:"
    psql -h "$DB_HOST" -U "$DB_USER" -d "$DB_NAME" -c "SELECT tt.id, e.slug, tt.name, tt.age_category, tt.base_price FROM aiolia.ticket_types tt JOIN aiolia.events e ON e.id = tt.event_id ORDER BY e.slug, tt.age_category;"
    
else
    echo ""
    echo "❌ Erreur lors de l'exécution du script!"
    echo "Vérifiez les messages d'erreur ci-dessus."
    exit 1
fi

