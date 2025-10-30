// Admin Dashboard JavaScript
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Admin Dashboard JavaScript chargé');
    
    // ============================================
    // 1. DROPDOWN MENU UTILISATEUR
    // ============================================
    function initUserDropdown() {
        const userInfo = document.querySelector('.user-info');
        const dropdownMenu = document.querySelector('.dropdown-menu');
        
        if (!userInfo || !dropdownMenu) {
            console.log('❌ Éléments dropdown non trouvés');
            return;
        }
        
        console.log('✅ Dropdown utilisateur initialisé');
        
        // Clic sur le profil utilisateur
        userInfo.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('👤 Clic sur profil utilisateur');
            
            // Fermer tous les autres dropdowns
            document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                menu.classList.remove('show');
            });
            
            // Toggle le dropdown actuel
            dropdownMenu.classList.toggle('show');
            console.log('📋 Menu utilisateur:', dropdownMenu.classList.contains('show') ? 'ouvert' : 'fermé');
        });
        
        // Fermer en cliquant à l'extérieur
        document.addEventListener('click', function(e) {
            if (!userInfo.contains(e.target) && !dropdownMenu.contains(e.target)) {
                dropdownMenu.classList.remove('show');
            }
        });
        
        // Gestion des liens du menu
        const dropdownLinks = dropdownMenu.querySelectorAll('.dropdown-item');
        dropdownLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                console.log('🔗 Lien cliqué:', this.textContent.trim());
                dropdownMenu.classList.remove('show');
            });
        });
    }
    
    // Initialiser le dropdown utilisateur
    initUserDropdown();
    
    // ============================================
    // 2. SIDEBAR TOGGLE (Bouton hamburger)
    // ============================================
    function initSidebarToggle() {
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('adminSidebar');
        const body = document.body;
        
        if (!sidebarToggle || !sidebar) {
            console.log('❌ Éléments sidebar non trouvés');
            return;
        }
        
        console.log('✅ Toggle sidebar initialisé');
        
        // Clic sur le bouton hamburger
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('🍔 Clic sur bouton hamburger');
            
            // Toggle des classes
            sidebar.classList.toggle('sidebar-collapsed');
            body.classList.toggle('sidebar-collapsed');
            
            // Sauvegarder l'état
            const isCollapsed = sidebar.classList.contains('sidebar-collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed.toString());
            console.log('📊 Sidebar:', isCollapsed ? 'réduite (icônes seulement)' : 'étendue (complet)');
        });
        
        // Restaurer l'état au chargement
        const savedState = localStorage.getItem('sidebarCollapsed');
        if (savedState === 'true') {
            sidebar.classList.add('sidebar-collapsed');
            body.classList.add('sidebar-collapsed');
            console.log('🔄 Sidebar restaurée en mode réduit');
        } else {
            sidebar.classList.remove('sidebar-collapsed');
            body.classList.remove('sidebar-collapsed');
            console.log('🔄 Sidebar en mode étendu par défaut');
        }
    }
    
    // Initialiser le toggle sidebar
    initSidebarToggle();
    
    // ============================================
    // 3. FERMER SIDEBAR SUR MOBILE
    // ============================================
    if (sidebar) {
        // Fermer en cliquant à l'extérieur sur mobile
        document.addEventListener('click', function(event) {
            if (window.innerWidth <= 768 && sidebar.classList.contains('mobile-open')) {
                if (!sidebar.contains(event.target) && event.target !== sidebarToggle) {
                    sidebar.classList.remove('mobile-open');
                }
            }
        });
    }
    
    // ============================================
    // 4. TOOLTIPS POUR SIDEBAR RÉDUITE
    // ============================================
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        const navText = link.querySelector('.nav-text');
        if (navText) {
            link.setAttribute('title', navText.textContent.trim());
        }
    });
    
    // ============================================
    // 5. ANIMATIONS SMOOTH
    // ============================================
    const cards = document.querySelectorAll('.stat-card, .quick-action-card, .chart-card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.05}s`;
    });
    
    // ============================================
    // 6. MODE SOMBRE
    // ============================================
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');
    const body = document.body;
    
    if (themeToggle && themeIcon) {
        // Charger le thème sauvegardé
        const savedTheme = localStorage.getItem('theme') || 'light';
        body.setAttribute('data-theme', savedTheme);
        
        // Mettre à jour l'icône
        if (savedTheme === 'dark') {
            themeIcon.className = 'fas fa-moon';
        } else {
            themeIcon.className = 'fas fa-sun';
        }
        
        // Toggle du thème
        themeToggle.addEventListener('click', function() {
            const currentTheme = body.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            body.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            // Mettre à jour l'icône
            if (newTheme === 'dark') {
                themeIcon.className = 'fas fa-moon';
            } else {
                themeIcon.className = 'fas fa-sun';
            }
        });
    }
    
    // ============================================
    // 7. FOOTER INTERACTIONS
    // ============================================
    const debugToggle = document.getElementById('debugToggle');
    const debugInfo = document.getElementById('debugInfo');
    
    if (debugToggle && debugInfo) {
        debugToggle.addEventListener('click', function() {
            const isVisible = debugInfo.style.display !== 'none';
            debugInfo.style.display = isVisible ? 'none' : 'flex';
            
            // Animation du bouton
            this.style.transform = 'scale(0.9)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 150);
        });
    }
    
    // Mise à jour des statistiques en temps réel (simulation)
    function updateFooterStats() {
        const onlineUsers = document.getElementById('onlineUsers');
        const uptime = document.getElementById('uptime');
        const memoryUsage = document.getElementById('memoryUsage');
        const responseTime = document.getElementById('responseTime');
        
        if (onlineUsers) {
            // Simulation d'utilisateurs en ligne
            const baseUsers = 12;
            const variation = Math.floor(Math.random() * 5) - 2;
            onlineUsers.textContent = Math.max(1, baseUsers + variation);
        }
        
        if (uptime) {
            // Simulation d'uptime
            const uptimeValue = (99.9 + Math.random() * 0.1).toFixed(1);
            uptime.textContent = uptimeValue + '%';
        }
        
        if (memoryUsage) {
            // Simulation d'utilisation mémoire
            const memory = (4.0 + Math.random() * 0.5).toFixed(1);
            memoryUsage.textContent = memory + ' MiB';
        }
        
        if (responseTime) {
            // Simulation du temps de réponse
            const response = Math.floor(20 + Math.random() * 15);
            responseTime.textContent = response + ' ms';
        }
    }
    
    // Mise à jour toutes les 30 secondes
    setInterval(updateFooterStats, 30000);
    
    // Animation d'entrée du footer
    const footer = document.querySelector('.admin-footer');
    if (footer) {
        footer.style.opacity = '0';
        footer.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            footer.style.opacity = '1';
            footer.style.transform = 'translateY(0)';
            footer.style.transition = 'all 0.6s ease';
        }, 500);
    }
    
    console.log('✅ Admin Dashboard JavaScript chargé');
    console.log('🔧 Sidebar state:', localStorage.getItem('sidebarCollapsed'));
    console.log('🔧 Sidebar classes:', document.getElementById('adminSidebar')?.className);
    console.log('🌙 Theme state:', localStorage.getItem('theme'));
});
