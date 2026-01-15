# 🏗️ Génération du Diagramme d'Architecture Aiolia Event

## 📋 Fichiers Générés

- `architecture_diagram.mmd` : Code Mermaid du diagramme
- `ARCHITECTURE_EXPLANATION.md` : Explication détaillée de l'architecture
- `generate_image.sh` : Script pour générer l'image

## 🖼️ Comment Générer l'Image

### Méthode 1 : Mermaid Live Editor (Recommandé - Gratuit)

1. Allez sur https://mermaid.live
2. Ouvrez le fichier `architecture_diagram.mmd`
3. Copiez tout le contenu dans l'éditeur
4. Cliquez sur "Actions" → "Download PNG" ou "Download SVG"

### Méthode 2 : Mermaid CLI

```bash
# Installer Mermaid CLI
npm install -g @mermaid-js/mermaid-cli

# Générer l'image
mmdc -i architecture_diagram.mmd -o architecture_diagram.png -w 2400 -H 1800
```

### Méthode 3 : VS Code

1. Installer l'extension "Markdown Preview Mermaid Support"
2. Ouvrir `architecture_diagram.mmd`
3. Utiliser la commande "Export Diagram" depuis la palette

### Méthode 4 : Python (mermaid-py)

```bash
pip install mermaid-py
python3 -c "from mermaid import Mermaid; m = Mermaid('architecture_diagram.mmd'); m.to_png('architecture_diagram.png')"
```

### Méthode 5 : Script Automatique

```bash
chmod +x generate_image.sh
./generate_image.sh
```

## 📊 Structure du Diagramme

Le diagramme représente :
- **FrontOffice** : Application publique (bleu)
- **BackOffice** : Application admin (rouge)
- **PostgreSQL** : Base de données partagée (vert)
- **Redis** : Cache FrontOffice uniquement (orange)
- **MVola** : API paiement (violet)
- **Cloudinary** : Service d'images (violet)

## 🔄 Corrections Apportées

1. ✅ **Une seule instance Redis** (utilisée uniquement par FrontOffice)
2. ✅ **Cloudinary connecté aux services Métier** (pas directement à Redis)
3. ✅ **Toutes les connexions explicites** avec protocoles (HTTPS, TCP/IP, SQL)
4. ✅ **Pas de duplications** - architecture claire et précise

## 📖 Documentation

Voir `ARCHITECTURE_EXPLANATION.md` pour une explication détaillée de chaque composant.
