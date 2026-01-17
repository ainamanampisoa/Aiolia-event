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
        
        if (!this.sidebar) {
            console.warn('Sidebar non trouvé');
            return;
        }
        
        // Afficher les labels normalement avec les icônes
        
        // Restaurer l'état sauvegardé
        if (this.isCollapsed) {
            this.collapse();
        } else {
            this.expand();
        }
        
        // Event listener sur le bouton toggle (si présent)
        if (this.sidebarToggle) {
            this.sidebarToggle.addEventListener('click', () => {
                this.toggle();
            });
        }
        
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
// MOBILE SIDEBAR TOGGLE
// ============================================

class MobileSidebarManager {
    constructor() {
        this.sidebar = document.getElementById('adminSidebar');
        this.toggleButton = document.getElementById('sidebarToggleMobile');
        this.overlay = document.getElementById('sidebarOverlay');
        this.isOpen = false;
        
        this.init();
    }
    
    init() {
        if (!this.sidebar || !this.toggleButton) {
            return;
        }
        
        // Event listeners
        this.toggleButton.addEventListener('click', () => this.toggle());
        
        if (this.overlay) {
            this.overlay.addEventListener('click', () => this.close());
        }
        
        // Fermer au redimensionnement si on passe en desktop
        window.addEventListener('resize', () => {
            if (window.innerWidth > 992 && this.isOpen) {
                this.close();
            }
        });
        
        // Fermer avec Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.close();
            }
        });
    }
    
    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }
    
    open() {
        if (!this.sidebar) return;
        
        this.sidebar.classList.add('sidebar-open');
        if (this.overlay) {
            this.overlay.classList.add('active');
        }
        if (this.toggleButton) {
            this.toggleButton.setAttribute('aria-expanded', 'true');
            this.toggleButton.setAttribute('aria-label', 'Fermer le menu');
        }
        document.body.style.overflow = 'hidden';
        this.isOpen = true;
    }
    
    close() {
        if (!this.sidebar) return;
        
        this.sidebar.classList.remove('sidebar-open');
        if (this.overlay) {
            this.overlay.classList.remove('active');
        }
        if (this.toggleButton) {
            this.toggleButton.setAttribute('aria-expanded', 'false');
            this.toggleButton.setAttribute('aria-label', 'Ouvrir le menu');
        }
        document.body.style.overflow = '';
        this.isOpen = false;
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

// Initialiser le gestionnaire mobile
let mobileSidebarManager;
function initMobileSidebarManager() {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            mobileSidebarManager = new MobileSidebarManager();
        });
    } else {
        mobileSidebarManager = new MobileSidebarManager();
    }
}
initMobileSidebarManager();

// Exposer globalement
window.SidebarManager = SidebarManager;
window.sidebarManager = sidebarManager;
window.MobileSidebarManager = MobileSidebarManager;
window.mobileSidebarManager = mobileSidebarManager;

// Export pour modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SidebarManager;
}

