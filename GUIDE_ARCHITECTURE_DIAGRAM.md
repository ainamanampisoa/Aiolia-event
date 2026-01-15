# 🏗️ Guide Complet - Diagramme d'Architecture Aiolia Event

## 📋 Vue d'ensemble

Ce guide explique comment générer et utiliser les diagrammes d'architecture pour le projet **Aiolia Event**. Les diagrammes représentent l'architecture N-Tier avec toutes les corrections apportées.

## ✅ Corrections Apportées

### Problèmes de l'image originale :

1. **❌ Duplication Redis** : Deux instances identiques
   - **✅ Corrigé** : Une seule instance Redis utilisée uniquement par FrontOffice

2. **❌ Cloudinary → Redis** : Connexion directe incorrecte
   - **✅ Corrigé** : Cloudinary connecté aux couches Métier (FrontOffice et BackOffice) via HTTPS

3. **❌ Flèches ambiguës** : "API/HTTPS" sans destination
   - **✅ Corrigé** : Toutes les connexions sont explicites avec protocoles et destinations

4. **❌ Architecture floue** : Duplications et ambiguïtés
   - **✅ Corrigé** : Architecture claire, une seule instance de chaque composant

## 📁 Fichiers Générés

### Diagrammes

1. **`architecture_diagram.mmd`** - Format Mermaid
   - ✅ Compatible avec Mermaid Live Editor
   - ✅ Supporté par VS Code, IntelliJ, GitHub
   - ✅ Facile à modifier et versionner

2. **`architecture_diagram.dot`** - Format Graphviz
   - ✅ Génération d'images haute qualité
   - ✅ Support PNG, SVG, PDF
   - ✅ Contrôle total du rendu

### Scripts

1. **`generate_architecture_diagram.py`** - Script principal
   - Génère les fichiers Mermaid et documentation
   - Usage : `python3 generate_architecture_diagram.py`

2. **`generate_architecture_diagram_advanced.py`** - Script avancé
   - Génère aussi le format Graphviz
   - Tente de générer l'image automatiquement
   - Usage : `python3 generate_architecture_diagram_advanced.py`

3. **`generate_image.sh`** - Script bash pour Mermaid
   - Vérifie les outils disponibles
   - Donne des instructions alternatives

4. **`generate_image_from_dot.sh`** - Script bash pour Graphviz
   - Génère PNG, SVG, PDF depuis le fichier DOT
   - Usage : `./generate_image_from_dot.sh`

### Documentation

1. **`ARCHITECTURE_EXPLANATION.md`** - Explication détaillée
   - Description de chaque composant
   - Flux de données
   - Avantages et sécurité

2. **`README_ARCHITECTURE_DIAGRAM.md`** - Guide rapide
   - Instructions de génération
   - Méthodes alternatives

## 🖼️ Méthodes de Génération d'Image

### Méthode 1 : Mermaid Live Editor (⭐ Recommandé - Gratuit)

**Avantages** : Aucune installation, interface web, export direct

1. Allez sur https://mermaid.live
2. Ouvrez le fichier `architecture_diagram.mmd`
3. Copiez tout le contenu dans l'éditeur
4. Le diagramme s'affiche automatiquement
5. Cliquez sur "Actions" → "Download PNG" ou "Download SVG"

**Résultat** : Image haute qualité en quelques secondes

---

### Méthode 2 : Mermaid CLI

**Avantages** : Automatisation, intégration CI/CD

```bash
# Installation
npm install -g @mermaid-js/mermaid-cli

# Génération
mmdc -i architecture_diagram.mmd -o architecture_diagram.png -w 2400 -H 1800 -b transparent
```

**Options** :
- `-w 2400` : Largeur en pixels
- `-H 1800` : Hauteur en pixels
- `-b transparent` : Fond transparent
- `-o output.png` : Fichier de sortie

---

### Méthode 3 : Graphviz (⭐ Haute Qualité)

**Avantages** : Rendu professionnel, contrôle total, formats multiples

```bash
# Installation
sudo apt-get install graphviz  # Ubuntu/Debian
brew install graphviz          # macOS
choco install graphviz         # Windows

# Génération PNG
dot -Tpng architecture_diagram.dot -o architecture_diagram.png -Gdpi=300

# Génération SVG (vectoriel)
dot -Tsvg architecture_diagram.dot -o architecture_diagram.svg

# Génération PDF
dot -Tpdf architecture_diagram.dot -o architecture_diagram.pdf

# Ou utiliser le script
./generate_image_from_dot.sh
```

**Résultat** : Images de qualité professionnelle

---

### Méthode 4 : VS Code

**Avantages** : Intégré à l'éditeur

1. Installer l'extension "Markdown Preview Mermaid Support"
2. Ouvrir `architecture_diagram.mmd`
3. Utiliser la commande "Export Diagram" depuis la palette (Ctrl+Shift+P)

---

### Méthode 5 : IntelliJ / WebStorm

**Avantages** : Support natif

1. Ouvrir `architecture_diagram.mmd`
2. Clic droit → "Export Diagram" → Choisir le format

---

### Méthode 6 : Python (Automatique)

**Avantages** : Script automatisé

```bash
# Installation des dépendances
pip install graphviz

# Génération automatique
python3 generate_architecture_diagram_advanced.py
```

**Note** : Nécessite aussi l'installation système de Graphviz

---

## 🎨 Structure du Diagramme

### Composants

| Composant | Couleur | Description |
|-----------|--------|--------------|
| **FrontOffice** | 🔵 Bleu | Application publique |
| **BackOffice** | 🔴 Rouge | Application admin |
| **PostgreSQL** | 🟢 Vert | Base de données |
| **Redis** | 🟠 Orange | Cache (FrontOffice uniquement) |
| **Services Externes** | 🟣 Violet | MVola, Cloudinary |

### Connexions

| Type de Ligne | Signification |
|---------------|---------------|
| **Flèche pleine** | Communication directe/synchrone |
| **Flèche pointillée** | Communication asynchrone/callback |
| **Label** | Protocole utilisé (HTTPS, TCP/IP, SQL) |

### Flux de Données

1. **Utilisateur** → FrontOffice (Présentation)
2. **Présentation** → Métier
3. **Métier** → Persistance
4. **Persistance** → PostgreSQL
5. **Métier** → Redis (cache)
6. **Métier** → Services Externes (MVola, Cloudinary)
7. **Services Externes** → FrontOffice (callbacks)

## 📊 Architecture Détaillée

### FrontOffice (Aiolia-event-front)

- **Présentation** : 13 Controllers, 37 Templates
- **Métier** : 14 Services (Payment, Cache, Cloudinary, etc.)
- **Persistance** : 17 Repositories, 9 Entities
- **Cache** : Redis (événements, recherche, stats, sessions)
- **Services externes** : MVola API, Cloudinary

### BackOffice (Aiolia-event-back)

- **Présentation** : 14 Controllers, 42 Templates
- **Métier** : 7 Services (Event, Media, Cloudinary, etc.)
- **Persistance** : 7 Repositories, 7 Entities
- **Services externes** : Cloudinary uniquement

### Base de Données

- **PostgreSQL** : Schéma `aiolia`, 54 tables
- **Partagée** : FrontOffice et BackOffice
- **ORM** : Doctrine pour 9 tables principales
- **SQL direct** : Pour 45 autres tables

### Cache Redis

- **Utilisation** : FrontOffice uniquement
- **Pools** :
  - `cache.events` : TTL 1h
  - `cache.search` : TTL 30min
  - `cache.stats` : TTL 30min
  - `cache.sessions` : TTL 24h

### Services Externes

- **MVola** : Paiement mobile money (FrontOffice uniquement)
- **Cloudinary** : Gestion d'images (FrontOffice + BackOffice)

## 🔄 Exemple de Flux : Achat de Billet

1. **Utilisateur** clique sur "Acheter"
2. **FrontOffice (Présentation)** : `TicketController::purchase()`
3. **FrontOffice (Métier)** : `PaymentService::processPayment()`
4. **FrontOffice (Métier)** → **MVola API** : Initiation transaction
5. **FrontOffice (Métier)** → **FrontOffice (Persistance)** : Sauvegarde commande
6. **FrontOffice (Persistance)** → **PostgreSQL** : INSERT dans `orders`
7. **MVola** → **FrontOffice (Présentation)** : Callback de confirmation
8. **FrontOffice (Métier)** → **Redis** : Mise en cache des résultats
9. **FrontOffice (Présentation)** : Rendu de la page de confirmation

## 🛠️ Personnalisation

### Modifier le Diagramme Mermaid

Éditez `architecture_diagram.mmd` :

```mermaid
%% Ajouter un nouveau composant
NewService[("🆕 Nouveau Service")]

%% Ajouter une connexion
FO_Metier -->|HTTPS| NewService
```

### Modifier le Diagramme Graphviz

Éditez `architecture_diagram.dot` :

```dot
// Ajouter un nouveau nœud
NewService [label="🆕 Nouveau Service", 
            style=filled, fillcolor="#9B59B6"];

// Ajouter une connexion
FO_Metier -> NewService [label="HTTPS", style=bold];
```

### Régénérer les Fichiers

```bash
# Régénérer depuis le script Python
python3 generate_architecture_diagram.py

# Ou régénérer avec Graphviz
python3 generate_architecture_diagram_advanced.py
```

## 📝 Notes Importantes

1. **Redis** : Utilisé uniquement par FrontOffice (pas par BackOffice)
2. **Cloudinary** : Connecté aux services Métier, pas directement à Redis
3. **MVola** : Uniquement FrontOffice, avec callbacks vers `MvolaController`
4. **PostgreSQL** : Partagée entre FrontOffice et BackOffice pour cohérence
5. **Séparation** : FrontOffice et BackOffice sont des applications distinctes

## 🚀 Prochaines Étapes

1. ✅ Générer l'image avec votre méthode préférée
2. ✅ Intégrer dans la documentation du projet
3. ✅ Mettre à jour le diagramme lors des changements d'architecture
4. ✅ Partager avec l'équipe pour validation

## 📚 Ressources

- **Mermaid** : https://mermaid.js.org/
- **Graphviz** : https://graphviz.org/
- **Mermaid Live Editor** : https://mermaid.live
- **Documentation Architecture** : `ARCHITECTURE_EXPLANATION.md`

---

**Créé le** : 2025-01-13  
**Version** : 1.0  
**Auteur** : Équipe Aiolia Event
