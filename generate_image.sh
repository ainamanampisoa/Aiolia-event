#!/bin/bash
# Script pour générer une image à partir du diagramme Mermaid

echo "🖼️  Génération de l'image d'architecture..."

# Option 1: Utiliser Mermaid CLI (nécessite npm install -g @mermaid-js/mermaid-cli)
if command -v mmdc &> /dev/null; then
    echo "Utilisation de Mermaid CLI..."
    mmdc -i architecture_diagram.mmd -o architecture_diagram.png -w 2400 -H 1800 -b transparent
    echo "✅ Image générée : architecture_diagram.png"
    
# Option 2: Utiliser l'API Mermaid en ligne
elif command -v curl &> /dev/null; then
    echo "Utilisation de l'API Mermaid en ligne..."
    # Note: Cette méthode nécessite une connexion internet
    echo "⚠️  Pour générer l'image, utilisez:"
    echo "   1. Allez sur https://mermaid.live"
    echo "   2. Copiez le contenu de architecture_diagram.mmd"
    echo "   3. Exportez en PNG/SVG"
    
# Option 3: Utiliser Python avec mermaid-py
elif command -v python3 &> /dev/null; then
    echo "Tentative avec Python..."
    python3 -c "
import subprocess
import sys

try:
    import mermaid
    print('Module mermaid trouvé')
except ImportError:
    print('⚠️  Installation requise: pip install mermaid-py')
    sys.exit(1)
" 2>/dev/null || echo "⚠️  Module mermaid-py non installé"
fi

echo ""
echo "📝 Alternatives pour générer l'image:"
echo "   1. Mermaid Live Editor: https://mermaid.live (copier-coller architecture_diagram.mmd)"
echo "   2. VS Code: Installer l'extension 'Markdown Preview Mermaid Support'"
echo "   3. IntelliJ/WebStorm: Support natif des fichiers .mmd"
echo "   4. Mermaid CLI: npm install -g @mermaid-js/mermaid-cli"
