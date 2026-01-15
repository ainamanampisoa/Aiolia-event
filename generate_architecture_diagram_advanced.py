#!/usr/bin/env python3
"""
Script avancé pour générer un diagramme d'architecture Aiolia Event
Supporte plusieurs formats de sortie : Mermaid, Graphviz (DOT), et génération directe d'image
"""

import os
import sys
from pathlib import Path

def generate_graphviz_dot():
    """Génère le code Graphviz DOT pour le diagramme"""
    
    dot_code = """digraph ArchitectureAioliaEvent {
    rankdir=TB;
    node [shape=box, style=rounded];
    
    // Styles
    node [fontname="Arial", fontsize=10];
    
    // Utilisateurs
    User [label="👤 Utilisateur\\nDesktop/Tablet/Mobile", 
          shape=ellipse, style=filled, fillcolor="#E8F4F8"];
    Admin [label="👤 Administrateur\\nDesktop", 
           shape=ellipse, style=filled, fillcolor="#F8E8E8"];
    
    // FrontOffice - Subgraph
    subgraph cluster_frontoffice {
        label="🎨 FrontOffice (Aiolia-event-front)";
        style=filled;
        fillcolor="#E8F0FF";
        
        FO_Presentation [label="📱 Couche Présentation\\n13 Controllers\\n37 Templates Twig",
                         style=filled, fillcolor="#4A90E2", fontcolor=white];
        FO_Metier [label="⚙️ Couche Métier\\n14 Services\\n- PaymentService\\n- CacheService\\n- CloudinaryService",
                   style=filled, fillcolor="#5BA3F5", fontcolor=white];
        FO_Persistance [label="💾 Couche Persistance\\n17 Repositories\\n9 Entities (Doctrine)",
                        style=filled, fillcolor="#6BB6FF", fontcolor=white];
        
        FO_Presentation -> FO_Metier [label="Appels"];
        FO_Metier -> FO_Persistance [label="Requêtes"];
    }
    
    // BackOffice - Subgraph
    subgraph cluster_backoffice {
        label="🔧 BackOffice (Aiolia-event-back)";
        style=filled;
        fillcolor="#FFE8E8";
        
        BO_Presentation [label="📱 Couche Présentation\\n14 Controllers\\n42 Templates Twig",
                         style=filled, fillcolor="#E74C3C", fontcolor=white];
        BO_Metier [label="⚙️ Couche Métier\\n7 Services\\n- EventService\\n- MediaService\\n- CloudinaryService",
                   style=filled, fillcolor="#EC7063", fontcolor=white];
        BO_Persistance [label="💾 Couche Persistance\\n7 Repositories\\n7 Entities (Doctrine)",
                        style=filled, fillcolor="#F1948A", fontcolor=white];
        
        BO_Presentation -> BO_Metier [label="Appels"];
        BO_Metier -> BO_Persistance [label="Requêtes"];
    }
    
    // Base de données
    PostgreSQL [label="🗄️ PostgreSQL\\nBase de données 'aiolia'\\n54 tables",
                shape=cylinder, style=filled, fillcolor="#27AE60", fontcolor=white];
    
    // Redis Cache
    Redis [label="🔴 Redis Cache\\n- Événements (TTL: 1h)\\n- Recherche (TTL: 30min)\\n- Statistiques (TTL: 30min)\\n- Sessions (TTL: 24h)",
           shape=cylinder, style=filled, fillcolor="#F39C12", fontcolor=white];
    
    // Services externes
    MVola [label="💳 MVola API\\nPaiement Mobile Money",
           shape=box3d, style=filled, fillcolor="#9B59B6", fontcolor=white];
    Cloudinary [label="☁️ Cloudinary\\nGestion d'images\\nCDN & Optimisation",
                shape=box3d, style=filled, fillcolor="#9B59B6", fontcolor=white];
    
    // Connexions Utilisateurs
    User -> FO_Presentation [label="Requêtes HTTP", style=bold];
    Admin -> BO_Presentation [label="Requêtes HTTP", style=bold];
    
    // Connexions Base de données
    FO_Persistance -> PostgreSQL [label="SQL\\nDoctrine ORM", color="#27AE60", style=bold];
    BO_Persistance -> PostgreSQL [label="SQL\\nDoctrine ORM", color="#27AE60", style=bold];
    
    // Connexions Redis (uniquement FrontOffice)
    FO_Metier -> Redis [label="TCP/IP\\nPredis", color="#F39C12", style=bold];
    Redis -> FO_Persistance [label="Cache miss", style=dashed, color="#F39C12"];
    
    // Connexions Services externes
    FO_Metier -> MVola [label="HTTPS\\nAPI REST", color="#9B59B6", style=bold];
    MVola -> FO_Presentation [label="Callback/Webhook\\nHTTPS", style=dashed, color="#9B59B6"];
    
    FO_Metier -> Cloudinary [label="HTTPS\\nAPI REST", color="#9B59B6", style=bold];
    BO_Metier -> Cloudinary [label="HTTPS\\nAPI REST", color="#9B59B6", style=bold];
    
    // Légende
    subgraph cluster_legend {
        label="Légende";
        style=dashed;
        node [shape=plaintext, style=solid, fillcolor=white, fontcolor=black];
        L1 [label="Flèche pleine: Communication directe"];
        L2 [label="Flèche pointillée: Communication asynchrone/callback"];
    }
}
"""
    return dot_code

def generate_image_with_python():
    """Tente de générer l'image directement avec Python"""
    
    print("🖼️  Tentative de génération d'image avec Python...")
    
    # Option 1: graphviz (pip install graphviz)
    try:
        import graphviz
        print("✅ Module graphviz trouvé")
        
        dot_code = generate_graphviz_dot()
        output_dir = Path(__file__).parent
        
        # Créer le graph
        graph = graphviz.Source(dot_code)
        graph.format = 'png'
        graph.render(output_dir / 'architecture_diagram', cleanup=True)
        
        print(f"✅ Image générée : {output_dir}/architecture_diagram.png")
        return True
    except ImportError:
        print("⚠️  Module graphviz non installé (pip install graphviz)")
    except Exception as e:
        print(f"⚠️  Erreur avec graphviz : {e}")
    
    # Option 2: pygraphviz (pip install pygraphviz)
    try:
        import pygraphviz as pgv
        print("✅ Module pygraphviz trouvé")
        
        dot_code = generate_graphviz_dot()
        output_dir = Path(__file__).parent
        
        graph = pgv.AGraph(dot_code)
        graph.layout(prog='dot')
        graph.draw(output_dir / 'architecture_diagram.png')
        
        print(f"✅ Image générée : {output_dir}/architecture_diagram.png")
        return True
    except ImportError:
        print("⚠️  Module pygraphviz non installé")
    except Exception as e:
        print(f"⚠️  Erreur avec pygraphviz : {e}")
    
    # Option 3: matplotlib + networkx (pour un rendu basique)
    try:
        import matplotlib.pyplot as plt
        import matplotlib.patches as mpatches
        print("✅ Module matplotlib trouvé")
        print("⚠️  Rendu basique avec matplotlib (recommandé: utiliser graphviz)")
        # On ne génère pas avec matplotlib car le rendu serait moins bon
        return False
    except ImportError:
        pass
    
    print("\n💡 Pour générer l'image automatiquement, installez :")
    print("   pip install graphviz")
    print("   (Nécessite aussi l'installation système de Graphviz)")
    print("\n   Ou utilisez Mermaid Live Editor : https://mermaid.live")
    return False

def save_all_formats():
    """Sauvegarde tous les formats de diagramme"""
    
    output_dir = Path(__file__).parent
    
    # Sauvegarder Graphviz DOT
    dot_code = generate_graphviz_dot()
    dot_file = output_dir / "architecture_diagram.dot"
    with open(dot_file, 'w', encoding='utf-8') as f:
        f.write(dot_code)
    print(f"✅ Diagramme Graphviz créé : {dot_file}")
    
    # Tenter de générer l'image
    if generate_image_with_python():
        print("\n✅ Image générée automatiquement !")
    else:
        print("\n📝 Pour générer l'image manuellement :")
        print("   1. Installer Graphviz : sudo apt-get install graphviz")
        print("   2. Générer : dot -Tpng architecture_diagram.dot -o architecture_diagram.png")
        print("   3. Ou utiliser Mermaid Live Editor avec architecture_diagram.mmd")

if __name__ == "__main__":
    save_all_formats()
