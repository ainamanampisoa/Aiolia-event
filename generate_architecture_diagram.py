#!/usr/bin/env python3
"""
Script pour générer un diagramme d'architecture N-Tier pour Aiolia Event
Utilise Mermaid pour créer un diagramme qui peut être rendu en image
"""

import os
from pathlib import Path

def generate_mermaid_diagram():
    """Génère le code Mermaid pour le diagramme d'architecture"""
    
    diagram = """graph TB
    %% Styles
    classDef frontend fill:#4A90E2,stroke:#2E5C8A,stroke-width:2px,color:#fff
    classDef backend fill:#E74C3C,stroke:#A93226,stroke-width:2px,color:#fff
    classDef database fill:#27AE60,stroke:#1E8449,stroke-width:2px,color:#fff
    classDef cache fill:#F39C12,stroke:#B9770E,stroke-width:2px,color:#fff
    classDef external fill:#9B59B6,stroke:#6C3483,stroke-width:2px,color:#fff
    classDef layer fill:#34495E,stroke:#1B2631,stroke-width:2px,color:#fff
    
    %% Utilisateurs
    User[👤 Utilisateur<br/>Desktop/Tablet/Mobile]
    Admin[👤 Administrateur<br/>Desktop]
    
    %% FrontOffice - Couche Présentation
    subgraph FrontOffice["🎨 FrontOffice (Aiolia-event-front)"]
        direction TB
        FO_Presentation["📱 Couche Présentation<br/>13 Controllers<br/>37 Templates Twig"]
        FO_Metier["⚙️ Couche Métier<br/>14 Services<br/>- PaymentService<br/>- CacheService<br/>- CloudinaryService<br/>- NotificationService"]
        FO_Persistance["💾 Couche Persistance<br/>17 Repositories<br/>9 Entities (Doctrine)"]
        
        FO_Presentation -->|Appels| FO_Metier
        FO_Metier -->|Requêtes| FO_Persistance
    end
    
    %% BackOffice - Couche Présentation
    subgraph BackOffice["🔧 BackOffice (Aiolia-event-back)"]
        direction TB
        BO_Presentation["📱 Couche Présentation<br/>14 Controllers<br/>42 Templates Twig"]
        BO_Metier["⚙️ Couche Métier<br/>7 Services<br/>- EventService<br/>- MediaService<br/>- CloudinaryService<br/>- AuditLogService"]
        BO_Persistance["💾 Couche Persistance<br/>7 Repositories<br/>7 Entities (Doctrine)"]
        
        BO_Presentation -->|Appels| BO_Metier
        BO_Metier -->|Requêtes| BO_Persistance
    end
    
    %% Base de données
    PostgreSQL[("🗄️ PostgreSQL<br/>Base de données 'aiolia'<br/>54 tables")]
    
    %% Redis Cache (uniquement FrontOffice)
    Redis[("🔴 Redis Cache<br/>- Événements (TTL: 1h)<br/>- Recherche (TTL: 30min)<br/>- Statistiques (TTL: 30min)<br/>- Sessions (TTL: 24h)")]
    
    %% Services externes
    MVola[("💳 MVola API<br/>Paiement Mobile Money<br/>HTTPS")]
    Cloudinary[("☁️ Cloudinary<br/>Gestion d'images<br/>CDN & Optimisation<br/>HTTPS")]
    
    %% Connexions Utilisateurs
    User -->|Requêtes HTTP| FO_Presentation
    Admin -->|Requêtes HTTP| BO_Presentation
    
    %% Connexions Base de données
    FO_Persistance -->|SQL<br/>Doctrine ORM| PostgreSQL
    BO_Persistance -->|SQL<br/>Doctrine ORM| PostgreSQL
    
    %% Connexions Redis (uniquement FrontOffice)
    FO_Metier -->|TCP/IP<br/>Predis| Redis
    Redis -.->|Cache miss| FO_Persistance
    
    %% Connexions Services externes
    FO_Metier -->|HTTPS<br/>API REST| MVola
    MVola -.->|Callback/Webhook<br/>HTTPS| FO_Presentation
    
    FO_Metier -->|HTTPS<br/>API REST| Cloudinary
    BO_Metier -->|HTTPS<br/>API REST| Cloudinary
    
    %% Application des styles
    class FO_Presentation,FO_Metier,FO_Persistance frontend
    class BO_Presentation,BO_Metier,BO_Persistance backend
    class PostgreSQL database
    class Redis cache
    class MVola,Cloudinary external
    class User,Admin layer
"""
    return diagram

def generate_architecture_explanation():
    """Génère une explication détaillée de l'architecture"""
    
    explanation = """
# 🏗️ Architecture N-Tier Aiolia Event - Explication Détaillée

## 📋 Vue d'ensemble

L'architecture **Aiolia Event** suit un modèle **N-Tier (multi-couches)** avec deux applications distinctes :
- **FrontOffice** : Application publique pour les utilisateurs finaux
- **BackOffice** : Application d'administration pour les organisateurs

## 🎯 Composants Principaux

### 1. FrontOffice (Aiolia-event-front)

#### Couche Présentation
- **13 Controllers** : Gestion des routes HTTP et actions utilisateur
- **37 Templates Twig** : Interface utilisateur responsive (desktop/tablet/mobile)
- **Responsabilités** : Validation des entrées, rendu des vues, gestion des erreurs HTTP

#### Couche Métier
- **14 Services** : Logique applicative réutilisable
  - `PaymentService` : Gestion des paiements MVola
  - `CacheService` : Gestion du cache Redis
  - `CloudinaryService` : Upload et gestion d'images
  - `NotificationService` : Envoi de notifications
  - `WalletService` : Gestion du portefeuille utilisateur
  - Et 9 autres services...
- **Responsabilités** : Règles métier, validation, orchestration, transactions

#### Couche Persistance
- **17 Repositories** : Accès optimisé aux données
- **9 Entities Doctrine** : Modèles ORM pour les tables principales
- **Approche hybride** : Doctrine ORM pour 9 tables + SQL direct pour 45 autres tables
- **Responsabilités** : Requêtes SQL, mapping objet-relationnel, gestion des transactions

### 2. BackOffice (Aiolia-event-back)

#### Couche Présentation
- **14 Controllers** : Gestion des routes admin
- **42 Templates Twig** : Interface d'administration
- **Responsabilités** : Gestion CRUD, validation admin, rapports

#### Couche Métier
- **7 Services** : Logique métier spécifique admin
  - `EventService` : Gestion complète des événements
  - `MediaService` : Gestion des médias via Cloudinary
  - `CloudinaryService` : Upload d'images
  - `AuditLogService` : Traçabilité des actions
  - Et 3 autres services...

#### Couche Persistance
- **7 Repositories** : Accès aux données admin
- **7 Entities Doctrine** : Modèles BackOffice

### 3. Base de Données PostgreSQL

- **Schéma** : `aiolia`
- **54 tables** au total
- **Partagée** entre FrontOffice et BackOffice
- **Avantages** : Cohérence des données, transactions ACID, performance optimisée

### 4. Redis Cache

- **Utilisation** : Uniquement par le FrontOffice
- **Pools de cache** :
  - `cache.events` : Événements populaires (TTL: 1 heure)
  - `cache.search` : Résultats de recherche (TTL: 30 minutes)
  - `cache.stats` : Statistiques (TTL: 30 minutes)
  - `cache.sessions` : Sessions utilisateur (TTL: 24 heures)
- **Protocole** : TCP/IP via Predis
- **Avantages** : Performance ultra-rapide, réduction de charge sur PostgreSQL

### 5. Services Externes

#### MVola API
- **Usage** : Uniquement FrontOffice
- **Service** : `MvolaPaymentClient`
- **Protocole** : HTTPS (API REST)
- **Flux** :
  1. FrontOffice → PaymentService → MvolaPaymentClient → MVola API
  2. MVola → Callback/Webhook → MvolaController (FrontOffice)
- **Fonctionnalités** : Paiement mobile money, remboursements, vérification de statut

#### Cloudinary
- **Usage** : FrontOffice ET BackOffice
- **Services** : `CloudinaryService` (dans les deux applications)
- **Protocole** : HTTPS (API REST)
- **Fonctionnalités** :
  - Upload d'images/vidéos/documents
  - Optimisation automatique
  - CDN global
  - Transformations à la volée (redimensionnement, compression)

## 🔄 Flux de Données

### Flux Standard (Achat de billet)

1. **Utilisateur** → FrontOffice (Présentation)
   - Requête HTTP : `/ticket/purchase`
   - Controller : `TicketController::purchase()`

2. **Présentation** → Métier
   - Appel : `PaymentService::processPayment($order)`
   - Validation métier

3. **Métier** → Service Externe (MVola)
   - Appel : `MvolaPaymentClient::initiateTransaction()`
   - Communication HTTPS avec MVola API

4. **Métier** → Persistance
   - Appel : `OrderRepository::save($order)`
   - Sauvegarde en base de données

5. **Persistance** → PostgreSQL
   - Requête SQL via Doctrine ORM
   - Transaction ACID

6. **Métier** → Redis (si applicable)
   - Mise en cache des résultats
   - Invalidation du cache si nécessaire

7. **MVola** → FrontOffice (Callback)
   - Webhook HTTPS : `/api/mvola/callback`
   - Controller : `MvolaController::callback()`
   - Mise à jour du statut de transaction

### Flux avec Cache

1. **Utilisateur** → FrontOffice : Requête événements
2. **Présentation** → Métier : `EventController::list()`
3. **Métier** → CacheService : Vérification cache Redis
4. **Si cache hit** : Retour immédiat depuis Redis
5. **Si cache miss** : Métier → Persistance → PostgreSQL
6. **Métier** → Redis : Mise en cache du résultat
7. **Métier** → Présentation : Retour des données
8. **Présentation** → Utilisateur : Rendu Twig

## ✅ Corrections Apportées au Diagramme

### Problèmes Corrigés :

1. **❌ Avant** : Deux instances Redis identiques
   - **✅ Après** : Une seule instance Redis utilisée uniquement par FrontOffice

2. **❌ Avant** : Cloudinary connecté directement à Redis
   - **✅ Après** : Cloudinary connecté aux couches Métier (FrontOffice et BackOffice) via HTTPS

3. **❌ Avant** : Flèches "API/HTTPS" sans destination claire
   - **✅ Après** : Toutes les connexions sont explicites avec protocoles et destinations

4. **❌ Avant** : Duplications et ambiguïtés
   - **✅ Après** : Architecture claire, une seule instance de chaque composant

## 🔐 Sécurité

- **Isolation** : FrontOffice et BackOffice déployés séparément
- **HTTPS** : Toutes les communications externes en HTTPS
- **Authentification** : OAuth 2.0 pour MVola, credentials pour Cloudinary
- **Base de données** : Protégée par firewall, accès via Doctrine ORM sécurisé

## 📊 Performance

- **Cache Redis** : Réduction de 80-90% des requêtes PostgreSQL pour les données fréquentes
- **CDN Cloudinary** : Images servies depuis le CDN global
- **Optimisation** : Requêtes SQL optimisées, index sur tables critiques
- **Scalabilité** : Chaque couche peut être mise à l'échelle indépendamment

## 🚀 Déploiement

- **FrontOffice** : Serveur 1 (web public)
- **BackOffice** : Serveur 2 (admin sécurisé)
- **PostgreSQL** : Serveur 3 (base de données)
- **Redis** : Serveur 4 (cache) ou même serveur que FrontOffice
- **Services externes** : Hébergés par MVola et Cloudinary
"""
    
    return explanation

def save_files():
    """Sauvegarde les fichiers générés"""
    
    # Créer le répertoire si nécessaire
    output_dir = Path(__file__).parent
    output_dir.mkdir(exist_ok=True)
    
    # Générer le diagramme Mermaid
    mermaid_code = generate_mermaid_diagram()
    mermaid_file = output_dir / "architecture_diagram.mmd"
    with open(mermaid_file, 'w', encoding='utf-8') as f:
        f.write(mermaid_code)
    print(f"✅ Diagramme Mermaid créé : {mermaid_file}")
    
    # Générer l'explication
    explanation = generate_architecture_explanation()
    explanation_file = output_dir / "ARCHITECTURE_EXPLANATION.md"
    with open(explanation_file, 'w', encoding='utf-8') as f:
        f.write(explanation)
    print(f"✅ Explication créée : {explanation_file}")
    
    # Créer un script pour générer l'image
    image_script = """#!/bin/bash
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
"""
    
    script_file = output_dir / "generate_image.sh"
    with open(script_file, 'w', encoding='utf-8') as f:
        f.write(image_script)
    os.chmod(script_file, 0o755)
    print(f"✅ Script de génération créé : {script_file}")
    
    # Créer un README avec instructions
    readme = """# 🏗️ Génération du Diagramme d'Architecture Aiolia Event

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
"""
    
    readme_file = output_dir / "README_ARCHITECTURE_DIAGRAM.md"
    with open(readme_file, 'w', encoding='utf-8') as f:
        f.write(readme)
    print(f"✅ README créé : {readme_file}")
    
    print("\n" + "="*60)
    print("✅ Tous les fichiers ont été générés avec succès !")
    print("="*60)
    print("\n📝 Prochaines étapes :")
    print("   1. Ouvrez architecture_diagram.mmd dans Mermaid Live Editor")
    print("   2. Ou utilisez : ./generate_image.sh")
    print("   3. Consultez ARCHITECTURE_EXPLANATION.md pour les détails")

if __name__ == "__main__":
    save_files()
