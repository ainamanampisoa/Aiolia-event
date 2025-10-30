// ============================================
// HEADER INTERACTIONS
// ============================================

'use strict';

class HeaderManager {
    constructor() {
        // Éléments DOM
        this.header = document.querySelector('.admin-header');
        this.searchInput = document.querySelector('.search-input');
        this.notificationBtn = document.querySelector('.notification-btn');
        this.userDropdown = document.querySelector('.user-dropdown');
        this.userTrigger = document.querySelector('.user-trigger');
        this.dropdownMenu = document.querySelector('.dropdown-menu');
        
        // Configuration
        this.config = {
            SEARCH_DEBOUNCE: 300,
            SCROLL_THRESHOLD: 100,
            SCROLL_THROTTLE: 10
        };
        
        // État
        this.lastScroll = 0;
        this.searchTimeout = null;
        this.scrollTimeout = null;
        
        this.init();
    }
    
    init() {
        console.log('📋 Initialisation Header Manager');
        
        if (!this.header) {
            console.warn('Header non trouvé');
            return;
        }
        
        // Initialiser les composants
        this.initUserDropdown();
        this.initSearch();
        this.initNotifications();
        this.initScrollBehavior();
        this.animateEntry();
        
        console.log('✅ Header Manager initialisé');
    }
    
    // ============================================
    // USER DROPDOWN
    // ============================================
    
    initUserDropdown() {
        if (!this.userTrigger || !this.dropdownMenu) {
            return;
        }
        
        // Toggle au clic
        this.userTrigger.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.toggleDropdown();
        });
        
        // Fermeture au clic extérieur
        document.addEventListener('click', (e) => {
            if (this.userDropdown && !this.userDropdown.contains(e.target)) {
                this.closeDropdown();
            }
        });
        
        // Navigation clavier
        this.userTrigger.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.toggleDropdown();
            } else if (e.key === 'Escape') {
                this.closeDropdown();
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                this.openDropdown();
            }
        });
        
        // Navigation dans les items
        this.initDropdownNavigation();
        
        console.log('✓ User dropdown initialisé');
    }
    
    initDropdownNavigation() {
        const items = this.dropdownMenu.querySelectorAll('.dropdown-item');
        
        items.forEach((item, index) => {
            item.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    const next = items[index + 1];
                    if (next) {
                        next.focus();
                    }
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    const prev = items[index - 1];
                    if (prev) {
                        prev.focus();
                    } else {
                        this.userTrigger.focus();
                    }
                } else if (e.key === 'Escape') {
                    this.closeDropdown();
                    this.userTrigger.focus();
                } else if (e.key === 'Tab') {
                    if (!e.shiftKey && index === items.length - 1) {
                        this.closeDropdown();
                    }
                }
            });
            
            // Fermer au clic sur un item
            item.addEventListener('click', () => {
                // Laisser le lien naviguer
                this.closeDropdown();
            });
        });
    }
    
    toggleDropdown() {
        const isExpanded = this.userTrigger.getAttribute('aria-expanded') === 'true';
        
        if (isExpanded) {
            this.closeDropdown();
        } else {
            this.openDropdown();
        }
    }
    
    openDropdown() {
        this.userTrigger.setAttribute('aria-expanded', 'true');
        this.dropdownMenu.classList.add('show');
        
        // Focus sur le premier item après l'animation
        setTimeout(() => {
            const firstItem = this.dropdownMenu.querySelector('.dropdown-item');
            if (firstItem) {
                firstItem.focus();
            }
        }, 100);
        
        console.log('📂 Dropdown ouvert');
    }
    
    closeDropdown() {
        this.userTrigger.setAttribute('aria-expanded', 'false');
        this.dropdownMenu.classList.remove('show');
        
        console.log('📁 Dropdown fermé');
    }
    
    // ============================================
    // SEARCH
    // ============================================
    
    initSearch() {
        if (!this.searchInput) {
            return;
        }
        
        // Input avec debounce
        this.searchInput.addEventListener('input', (e) => {
            this.handleSearch(e.target.value);
        });
        
        // Raccourci clavier (Ctrl/Cmd + K)
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                this.searchInput.focus();
                this.searchInput.select();
            }
            
            // Échapper pour sortir
            if (e.key === 'Escape' && document.activeElement === this.searchInput) {
                this.searchInput.blur();
                this.searchInput.value = '';
            }
        });
        
        // Placeholder animé
        this.animateSearchPlaceholder();
        
        console.log('✓ Search initialisé (Raccourci: Ctrl/Cmd + K)');
    }
    
    handleSearch(query) {
        clearTimeout(this.searchTimeout);
        
        if (query.length >= 2) {
            this.searchTimeout = setTimeout(() => {
                this.performSearch(query);
            }, this.config.SEARCH_DEBOUNCE);
        }
    }
    
    performSearch(query) {
        console.log('🔍 Recherche:', query);
        
        // TODO: Implémenter la recherche
        // Exemple:
        // fetch(`/api/search?q=${encodeURIComponent(query)}`)
        //     .then(response => response.json())
        //     .then(data => this.displaySearchResults(data))
        //     .catch(error => console.error('Erreur recherche:', error));
    }
    
    displaySearchResults(results) {
        console.log('Résultats:', results);
        // TODO: Afficher les résultats
    }
    
    animateSearchPlaceholder() {
        const placeholders = [
            'Rechercher...',
            'Rechercher un événement...',
            'Rechercher un utilisateur...',
            'Rechercher des billets...'
        ];
        
        let currentIndex = 0;
        
        setInterval(() => {
            if (document.activeElement !== this.searchInput) {
                currentIndex = (currentIndex + 1) % placeholders.length;
                this.searchInput.placeholder = placeholders[currentIndex];
            }
        }, 3000);
    }
    
    // ============================================
    // NOTIFICATIONS
    // ============================================
    
    initNotifications() {
        if (!this.notificationBtn) {
            return;
        }
        
        this.notificationBtn.addEventListener('click', () => {
            this.showNotificationPanel();
        });
        
        // Simuler la mise à jour du badge
        this.updateNotificationBadge();
        
        console.log('✓ Notifications initialisées');
    }
    
    showNotificationPanel() {
        console.log('🔔 Ouverture panneau notifications');
        
        // TODO: Implémenter le panneau de notifications
        // Pour l'instant, on affiche une alerte
        if (window.themeManager) {
            window.themeManager.showNotification('Panneau de notifications à implémenter 🔔');
        } else {
            alert('Panneau de notifications à venir !');
        }
    }
    
    updateNotificationBadge() {
        const badge = this.notificationBtn?.querySelector('.notification-badge');
        if (!badge) return;
        
        // TODO: Récupérer le nombre réel de notifications depuis l'API
        // fetch('/api/notifications/count')
        //     .then(response => response.json())
        //     .then(data => {
        //         badge.textContent = data.count;
        //         if (data.count === 0) {
        //             badge.style.display = 'none';
        //         }
        //     });
    }
    
    // ============================================
    // SCROLL BEHAVIOR
    // ============================================
    
    initScrollBehavior() {
        if (!this.header) {
            return;
        }
        
        window.addEventListener('scroll', () => {
            clearTimeout(this.scrollTimeout);
            
            this.scrollTimeout = setTimeout(() => {
                this.handleScroll();
            }, this.config.SCROLL_THROTTLE);
        });
        
        console.log('✓ Scroll behavior initialisé');
    }
    
    handleScroll() {
        const currentScroll = window.pageYOffset;
        
        // Masquer le header au scroll down (après le seuil)
        if (currentScroll > this.lastScroll && currentScroll > this.config.SCROLL_THRESHOLD) {
            this.hideHeader();
        } else {
            this.showHeader();
        }
        
        // Ajouter une classe si scrollé
        if (currentScroll > 0) {
            this.header.classList.add('scrolled');
        } else {
            this.header.classList.remove('scrolled');
        }
        
        this.lastScroll = currentScroll;
    }
    
    hideHeader() {
        this.header.style.transform = 'translateY(-100%)';
    }
    
    showHeader() {
        this.header.style.transform = 'translateY(0)';
    }
    
    // ============================================
    // ANIMATIONS
    // ============================================
    
    animateEntry() {
        if (!this.header) return;
        
        this.header.style.opacity = '0';
        this.header.style.transform = 'translateY(-20px)';
        
        setTimeout(() => {
            this.header.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
            this.header.style.opacity = '1';
            this.header.style.transform = 'translateY(0)';
        }, 100);
    }
    
    // ============================================
    // API PUBLIQUE
    // ============================================
    
    openSearch() {
        if (this.searchInput) {
            this.searchInput.focus();
            this.searchInput.select();
        }
    }
    
    clearSearch() {
        if (this.searchInput) {
            this.searchInput.value = '';
            this.searchInput.blur();
        }
    }
    
    setNotificationCount(count) {
        const badge = this.notificationBtn?.querySelector('.notification-badge');
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'flex' : 'none';
        }
    }
    
    // ============================================
    // CLEANUP
    // ============================================
    
    destroy() {
        clearTimeout(this.searchTimeout);
        clearTimeout(this.scrollTimeout);
        console.log('🧹 Header Manager détruit');
    }
}

// ============================================
// INITIALISATION
// ============================================

let headerManager;

function initHeaderManager() {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            headerManager = new HeaderManager();
        });
    } else {
        headerManager = new HeaderManager();
    }
}

// Initialiser
initHeaderManager();

// Exposer globalement
window.HeaderManager = HeaderManager;
window.headerManager = headerManager;

// Export pour modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = HeaderManager;
}