// ============================================
// ADMIN DASHBOARD - JAVASCRIPT AMÉLIORÉ
// ============================================

'use strict';

// Configuration globale
const CONFIG = {
    SIDEBAR_STORAGE_KEY: 'sidebarCollapsed',
    THEME_STORAGE_KEY: 'theme',
    ANIMATION_DURATION: 300,
    DEBOUNCE_DELAY: 250,
    STATS_UPDATE_INTERVAL: 30000
};

// Utilitaires
const Utils = {
    // Debounce function
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    // Throttle function
    throttle(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },

    // Animate element
    animate(element, animation, duration = CONFIG.ANIMATION_DURATION) {
        return new Promise((resolve) => {
            element.style.animation = `${animation} ${duration}ms ease`;
            element.addEventListener('animationend', () => {
                element.style.animation = '';
                resolve();
            }, { once: true });
        });
    },

    // Local storage avec gestion d'erreurs
    storage: {
        get(key, defaultValue = null) {
            try {
                const item = localStorage.getItem(key);
                return item !== null ? JSON.parse(item) : defaultValue;
            } catch (error) {
                console.warn(`Erreur lecture localStorage [${key}]:`, error);
                return defaultValue;
            }
        },
        set(key, value) {
            try {
                localStorage.setItem(key, JSON.stringify(value));
                return true;
            } catch (error) {
                console.warn(`Erreur écriture localStorage [${key}]:`, error);
                return false;
            }
        },
        remove(key) {
            try {
                localStorage.removeItem(key);
                return true;
            } catch (error) {
                console.warn(`Erreur suppression localStorage [${key}]:`, error);
                return false;
            }
        }
    },

    // Logger amélioré
    log: {
        info: (message, ...args) => console.log(`ℹ️ ${message}`, ...args),
        success: (message, ...args) => console.log(`✅ ${message}`, ...args),
        warning: (message, ...args) => console.warn(`⚠️ ${message}`, ...args),
        error: (message, ...args) => console.error(`❌ ${message}`, ...args),
        debug: (message, ...args) => {
            if (window.DEBUG_MODE) {
                console.log(`🔧 ${message}`, ...args);
            }
        }
    }
};

// ============================================
// CLASSE SIDEBAR
// ============================================
class Sidebar {
    constructor() {
        this.sidebar = document.getElementById('adminSidebar');
        this.toggleBtn = document.getElementById('sidebarToggle');
        this.mainContent = document.querySelector('.admin-main');
        this.isCollapsed = Utils.storage.get(CONFIG.SIDEBAR_STORAGE_KEY, false);
        
        if (!this.sidebar || !this.toggleBtn) {
            Utils.log.warning('Éléments sidebar non trouvés');
            return;
        }
        
        this.init();
    }
    
    init() {
        Utils.log.info('Initialisation Sidebar');
        
        // État initial
        if (this.isCollapsed) {
            this.collapse(false);
        }
        
        // Event listeners
        this.toggleBtn.addEventListener('click', () => this.toggle());
        
        // Navigation active
        this.setActiveNavItem();
        
        // Gestion responsive
        this.handleResize = Utils.throttle(() => this.onResize(), 250);
        window.addEventListener('resize', this.handleResize);
        
        // Fermeture mobile
        this.handleClickOutside = (e) => {
            if (window.innerWidth <= 768 && 
                !this.sidebar.contains(e.target) && 
                !this.toggleBtn.contains(e.target) &&
                this.sidebar.classList.contains('sidebar-mobile-open')) {
                this.closeMobile();
            }
        };
        document.addEventListener('click', this.handleClickOutside);
        
        // Tooltips pour sidebar collapsed
        this.initTooltips();
        
        Utils.log.success('Sidebar initialisée');
    }
    
    toggle() {
        if (this.isCollapsed) {
            this.expand();
        } else {
            this.collapse();
        }
    }
    
    collapse(animate = true) {
        if (animate) {
            this.sidebar.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
        }
        
        this.sidebar.classList.add('sidebar-collapsed');
        document.body.classList.add('sidebar-collapsed');
        this.isCollapsed = true;
        Utils.storage.set(CONFIG.SIDEBAR_STORAGE_KEY, true);
        
        // Animation de l'icône
        if (this.toggleBtn.querySelector('i')) {
            this.toggleBtn.querySelector('i').style.transform = 'rotate(180deg)';
        }
        
        Utils.log.debug('Sidebar collapsed');
    }
    
    expand(animate = true) {
        if (animate) {
            this.sidebar.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
        }
        
        this.sidebar.classList.remove('sidebar-collapsed');
        document.body.classList.remove('sidebar-collapsed');
        this.isCollapsed = false;
        Utils.storage.set(CONFIG.SIDEBAR_STORAGE_KEY, false);
        
        // Animation de l'icône
        if (this.toggleBtn.querySelector('i')) {
            this.toggleBtn.querySelector('i').style.transform = 'rotate(0deg)';
        }
        
        Utils.log.debug('Sidebar expanded');
    }
    
    openMobile() {
        this.sidebar.classList.add('sidebar-mobile-open');
        document.body.style.overflow = 'hidden';
    }
    
    closeMobile() {
        this.sidebar.classList.remove('sidebar-mobile-open');
        document.body.style.overflow = '';
    }
    
    onResize() {
        if (window.innerWidth > 768) {
            this.closeMobile();
        }
        
        // Auto-collapse sur tablette
        if (window.innerWidth <= 992 && window.innerWidth > 768) {
            if (!this.isCollapsed) {
                this.collapse(false);
            }
        }
    }
    
    setActiveNavItem() {
        const currentPath = window.location.pathname;
        const navLinks = this.sidebar.querySelectorAll('.nav-link');
        
        navLinks.forEach(link => {
            const linkPath = new URL(link.href).pathname;
            const navItem = link.closest('.nav-item');
            
            if (currentPath === linkPath || currentPath.startsWith(linkPath + '/')) {
                navItem?.classList.add('active');
                link.setAttribute('aria-current', 'page');
            } else {
                navItem?.classList.remove('active');
                link.removeAttribute('aria-current');
            }
        });
    }
    
    initTooltips() {
        const navItems = this.sidebar.querySelectorAll('.nav-item');
        
        navItems.forEach(item => {
            const tooltip = item.querySelector('.nav-tooltip');
            if (tooltip) {
                item.addEventListener('mouseenter', () => {
                    if (this.isCollapsed) {
                        tooltip.style.opacity = '1';
                        tooltip.style.visibility = 'visible';
                    }
                });
                
                item.addEventListener('mouseleave', () => {
                    tooltip.style.opacity = '0';
                    tooltip.style.visibility = 'hidden';
                });
            }
        });
    }
    
    destroy() {
        window.removeEventListener('resize', this.handleResize);
        document.removeEventListener('click', this.handleClickOutside);
    }
}

// ============================================
// CLASSE HEADER
// ============================================
class AdminHeader {
    constructor() {
        this.header = document.querySelector('.admin-header');
        this.themeToggle = document.getElementById('themeToggle');
        this.themeIcon = document.getElementById('themeIcon');
        this.searchInput = document.querySelector('.search-input');
        this.notificationBtn = document.querySelector('.notification-btn');
        this.userDropdown = document.querySelector('.user-dropdown');
        this.userTrigger = document.querySelector('.user-trigger');
        this.dropdownMenu = document.querySelector('.dropdown-menu');
        
        if (!this.header) {
            Utils.log.warning('Header non trouvé');
            return;
        }
        
        this.init();
    }
    
    init() {
        Utils.log.info('Initialisation Header');
        
        // Theme toggle
        if (this.themeToggle) {
            this.initTheme();
        }
        
        // Search
        if (this.searchInput) {
            this.initSearch();
        }
        
        // Notifications
        if (this.notificationBtn) {
            this.initNotifications();
        }
        
        // User dropdown
        if (this.userDropdown) {
            this.initUserDropdown();
        }
        
        // Scroll behavior
        this.initScrollBehavior();
        
        Utils.log.success('Header initialisé');
    }
    
    initTheme() {
        const savedTheme = Utils.storage.get(CONFIG.THEME_STORAGE_KEY, 'light');
        this.setTheme(savedTheme, false);
        
        this.themeToggle.addEventListener('click', () => {
            const currentTheme = document.body.getAttribute('data-theme') || 'light';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            this.setTheme(newTheme, true);
        });
        
        Utils.log.debug('Theme initialisé:', savedTheme);
    }
    
    setTheme(theme, animate = true) {
        document.body.setAttribute('data-theme', theme);
        Utils.storage.set(CONFIG.THEME_STORAGE_KEY, theme);
        
        if (this.themeIcon) {
            const iconClass = theme === 'dark' ? 'fa-moon' : 'fa-sun';
            this.themeIcon.className = `fas ${iconClass}`;
            
            if (animate) {
                this.themeToggle.style.transform = 'scale(1.2) rotate(360deg)';
                setTimeout(() => {
                    this.themeToggle.style.transform = '';
                }, CONFIG.ANIMATION_DURATION);
            }
        }
        
        Utils.log.debug('Theme changé:', theme);
    }
    
    initSearch() {
        const performSearch = Utils.debounce((query) => {
            if (query.length >= 2) {
                Utils.log.debug('Recherche:', query);
                // Implémenter la logique de recherche ici
            }
        }, CONFIG.DEBOUNCE_DELAY);
        
        this.searchInput.addEventListener('input', (e) => {
            performSearch(e.target.value);
        });
        
        // Raccourci clavier (Ctrl/Cmd + K)
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                this.searchInput.focus();
            }
        });
    }
    
    initNotifications() {
        this.notificationBtn.addEventListener('click', () => {
            Utils.log.debug('Notifications ouvertes');
            // Implémenter le panneau de notifications
            this.showNotificationPanel();
        });
    }
    
    showNotificationPanel() {
        // Placeholder pour le panneau de notifications
        console.log('Afficher panneau notifications');
    }
    
    initUserDropdown() {
        // Toggle dropdown
        this.userTrigger.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.toggleDropdown();
        });
        
        // Fermeture au clic extérieur
        document.addEventListener('click', (e) => {
            if (!this.userDropdown.contains(e.target)) {
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
            }
        });
        
        // Gestion des items
        const dropdownItems = this.dropdownMenu.querySelectorAll('.dropdown-item');
        dropdownItems.forEach((item, index) => {
            item.addEventListener('click', () => {
                Utils.log.debug('Menu item clicked:', item.textContent.trim());
                this.closeDropdown();
            });
            
            // Navigation clavier dans le menu
            item.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    const next = dropdownItems[index + 1];
                    if (next) next.focus();
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    const prev = dropdownItems[index - 1];
                    if (prev) prev.focus();
                    else this.userTrigger.focus();
                }
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
        
        // Focus premier item
        setTimeout(() => {
            const firstItem = this.dropdownMenu.querySelector('.dropdown-item');
            if (firstItem) firstItem.focus();
        }, 100);
    }
    
    closeDropdown() {
        this.userTrigger.setAttribute('aria-expanded', 'false');
        this.dropdownMenu.classList.remove('show');
    }
    
    initScrollBehavior() {
        let lastScroll = 0;
        const handleScroll = Utils.throttle(() => {
            const currentScroll = window.pageYOffset;
            
            if (currentScroll > lastScroll && currentScroll > 100) {
                // Scroll down
                this.header.style.transform = 'translateY(-100%)';
            } else {
                // Scroll up
                this.header.style.transform = 'translateY(0)';
            }
            
            lastScroll = currentScroll;
        }, 100);
        
        window.addEventListener('scroll', handleScroll);
    }
}

// ============================================
// CLASSE FOOTER
// ============================================
class AdminFooter {
    constructor() {
        this.footer = document.querySelector('.admin-footer');
        this.debugToggle = document.getElementById('debugToggle');
        this.debugInfo = document.getElementById('debugInfo');
        
        if (!this.footer) {
            Utils.log.warning('Footer non trouvé');
            return;
        }
        
        this.init();
    }
    
    init() {
        Utils.log.info('Initialisation Footer');
        
        // Debug toggle
        if (this.debugToggle && this.debugInfo) {
            this.initDebugMode();
        }
        
        // Stats en temps réel
        this.startStatsUpdate();
        
        // Animation d'entrée
        this.animateEntry();
        
        Utils.log.success('Footer initialisé');
    }
    
    initDebugMode() {
        // État initial
        const debugVisible = Utils.storage.get('debugVisible', false);
        this.debugInfo.style.display = debugVisible ? 'flex' : 'none';
        
        this.debugToggle.addEventListener('click', () => {
            const isVisible = this.debugInfo.style.display !== 'none';
            this.debugInfo.style.display = isVisible ? 'none' : 'flex';
            Utils.storage.set('debugVisible', !isVisible);
            
            // Animation
            this.debugToggle.style.transform = 'scale(0.9)';
            setTimeout(() => {
                this.debugToggle.style.transform = 'scale(1)';
            }, 150);
        });
    }
    
    startStatsUpdate() {
        this.updateStats();
        setInterval(() => this.updateStats(), CONFIG.STATS_UPDATE_INTERVAL);
    }
    
    updateStats() {
        const stats = {
            onlineUsers: this.generateRandomStat(10, 20),
            uptime: (99.5 + Math.random() * 0.5).toFixed(1),
            memory: (3.5 + Math.random() * 1.5).toFixed(1),
            responseTime: Math.floor(15 + Math.random() * 20)
        };
        
        this.updateStatElement('onlineUsers', stats.onlineUsers);
        this.updateStatElement('uptime', stats.uptime + '%');
        this.updateStatElement('memoryUsage', stats.memory + ' MiB');
        this.updateStatElement('responseTime', stats.responseTime + ' ms');
    }
    
    updateStatElement(id, value) {
        const element = document.getElementById(id);
        if (element && element.textContent !== value) {
            element.style.transition = 'all 0.3s ease';
            element.style.transform = 'scale(1.1)';
            element.style.color = 'var(--secondary)';
            
            setTimeout(() => {
                element.textContent = value;
                element.style.transform = 'scale(1)';
                element.style.color = '';
            }, 150);
        }
    }
    
    generateRandomStat(min, max) {
        return Math.floor(Math.random() * (max - min + 1)) + min;
    }
    
    animateEntry() {
        this.footer.style.opacity = '0';
        this.footer.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            this.footer.style.transition = 'all 0.6s ease';
            this.footer.style.opacity = '1';
            this.footer.style.transform = 'translateY(0)';
        }, 500);
    }
}

// ============================================
// INITIALISATION GLOBALE
// ============================================
class AdminDashboard {
    constructor() {
        this.components = {};
        this.init();
    }
    
    init() {
        Utils.log.info('🚀 Initialisation Admin Dashboard');
        
        // Vérifier que le DOM est chargé
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.initComponents());
        } else {
            this.initComponents();
        }
    }
    
    initComponents() {
        try {
            // Initialiser les composants
            this.components.sidebar = new Sidebar();
            this.components.header = new AdminHeader();
            this.components.footer = new AdminFooter();
            
            // Exposer globalement pour debug
            if (window.DEBUG_MODE) {
                window.AdminDashboard = this;
            }
            
            Utils.log.success('✨ Admin Dashboard chargé avec succès');
            this.logSystemInfo();
        } catch (error) {
            Utils.log.error('Erreur initialisation:', error);
        }
    }
    
    logSystemInfo() {
        const info = {
            'Sidebar State': Utils.storage.get(CONFIG.SIDEBAR_STORAGE_KEY, false) ? 'Collapsed' : 'Expanded',
            'Theme': Utils.storage.get(CONFIG.THEME_STORAGE_KEY, 'light'),
            'Viewport': `${window.innerWidth}x${window.innerHeight}`,
            'User Agent': navigator.userAgent.split(' ').slice(-2).join(' ')
        };
        
        console.table(info);
    }
    
    destroy() {
        Object.values(this.components).forEach(component => {
            if (component && typeof component.destroy === 'function') {
                component.destroy();
            }
        });
        Utils.log.info('Dashboard détruit');
    }
}

// Initialisation
const dashboard = new AdminDashboard();

// Export pour modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { AdminDashboard, Sidebar, AdminHeader, AdminFooter, Utils };
}