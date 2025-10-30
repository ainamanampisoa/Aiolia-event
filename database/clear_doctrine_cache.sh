#!/bin/bash

# Script pour nettoyer le cache Doctrine après correction des entités
# Date: 2025-10-22

echo "🧹 Nettoyage du cache Doctrine..."

cd /home/fifah/Documents/GitHub/Aiolia-event/Aiolia-event-back

# Nettoyer le cache
php bin/console cache:clear

# Nettoyer le cache Doctrine
php bin/console doctrine:cache:clear-metadata
php bin/console doctrine:cache:clear-query
php bin/console doctrine:cache:clear-result

# Vérifier le schéma de la base de données
php bin/console doctrine:schema:validate

echo "✅ Cache nettoyé avec succès !"
echo ""
echo "📋 Prochaines étapes :"
echo "1. Exécuter le script SQL de correction"
echo "2. Tester la page de gestion des utilisateurs"
echo "3. Vérifier que les liens du sidebar fonctionnent"
