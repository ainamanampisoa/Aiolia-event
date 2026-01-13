// ============================================
// ADMIN SIDEBAR TOGGLE
// ============================================

'use strict';

class SidebarManager {
    constructor() {
        this.sidebar = document.getElementById('adminSidebar');
        this.sidebarToggle = document.getElementById('sidebarToggle');
        this.body = document.body;
        this.isCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';
        
        this.init();
    }
    
    init() {
        console.log('📋 Initialisation Sidebar Manager');
        
        if (!this.sidebar || !this.sidebarToggle) {
            console.warn('Sidebar ou toggle button non trouvé');
            return;
        }
        
        // Afficher les labels normalement avec les icônes
        
        // Restaurer l'état sauvegardé
        if (this.isCollapsed) {
            this.collapse();
        } else {
            this.expand();
        }
        
        // Event listener sur le bouton toggle
        this.sidebarToggle.addEventListener('click', () => {
            this.toggle();
        });
        
        // Gérer le redimensionnement de la fenêtre
        window.addEventListener('resize', () => {
            this.handleResize();
        });
        
        console.log('✅ Sidebar Manager initialisé');
    }
    
    
    toggle() {
        if (this.isCollapsed) {
            this.expand();
        } else {
            this.collapse();
        }
    }
    
    collapse() {
        if (!this.sidebar) return;
        
        this.sidebar.classList.add('sidebar-collapsed');
        this.body.classList.add('sidebar-collapsed');
        this.isCollapsed = true;
        
        // Sauvegarder l'état
        localStorage.setItem('sidebar-collapsed', 'true');
        
        // Mettre à jour l'aria-label du bouton
        if (this.sidebarToggle) {
            this.sidebarToggle.setAttribute('aria-label', 'Afficher la sidebar');
            this.sidebarToggle.setAttribute('aria-expanded', 'false');
        }
        
        console.log('📁 Sidebar repliée');
    }
    
    expand() {
        if (!this.sidebar) return;
        
        this.sidebar.classList.remove('sidebar-collapsed');
        this.body.classList.remove('sidebar-collapsed');
        this.isCollapsed = false;
        
        // Sauvegarder l'état
        localStorage.setItem('sidebar-collapsed', 'false');
        
        // Mettre à jour l'aria-label du bouton
        if (this.sidebarToggle) {
            this.sidebarToggle.setAttribute('aria-label', 'Masquer la sidebar');
            this.sidebarToggle.setAttribute('aria-expanded', 'true');
        }
        
        console.log('📂 Sidebar dépliée');
    }
    
    handleResize() {
        // Sur mobile/tablette, replier automatiquement la sidebar si nécessaire
        if (window.innerWidth < 768 && !this.isCollapsed) {
            // Optionnel : replier automatiquement sur mobile
            // this.collapse();
        }
    }
}

// ============================================
// INITIALISATION
// ============================================

let sidebarManager;

function initSidebarManager() {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            sidebarManager = new SidebarManager();
        });
    } else {
        sidebarManager = new SidebarManager();
    }
}

// Initialiser
initSidebarManager();

// Exposer globalement
window.SidebarManager = SidebarManager;
window.sidebarManager = sidebarManager;

// Export pour modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SidebarManager;
}

