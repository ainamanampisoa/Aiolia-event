#!/bin/bash
# Script pour générer une image PNG/SVG à partir du fichier Graphviz DOT

echo "🖼️  Génération de l'image d'architecture depuis Graphviz DOT..."

# Vérifier si Graphviz est installé
if ! command -v dot &> /dev/null; then
    echo "❌ Graphviz n'est pas installé"
    echo ""
    echo "📦 Installation :"
    echo "   Ubuntu/Debian: sudo apt-get install graphviz"
    echo "   macOS: brew install graphviz"
    echo "   Windows: choco install graphviz"
    exit 1
fi

# Générer PNG
if [ -f "architecture_diagram.dot" ]; then
    echo "📊 Génération PNG..."
    dot -Tpng architecture_diagram.dot -o architecture_diagram.png -Gdpi=300
    echo "✅ Image PNG générée : architecture_diagram.png"
    
    echo "📊 Génération SVG..."
    dot -Tsvg architecture_diagram.dot -o architecture_diagram.svg
    echo "✅ Image SVG générée : architecture_diagram.svg"
    
    echo "📊 Génération PDF..."
    dot -Tpdf architecture_diagram.dot -o architecture_diagram.pdf
    echo "✅ Document PDF généré : architecture_diagram.pdf"
    
    echo ""
    echo "✅ Tous les formats ont été générés avec succès !"
else
    echo "❌ Fichier architecture_diagram.dot non trouvé"
    echo "   Exécutez d'abord : python3 generate_architecture_diagram_advanced.py"
    exit 1
fi
