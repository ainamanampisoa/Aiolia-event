// ============================================
// THEME MANAGER - Gestion du mode sombre et thème
// ============================================

'use strict';

class ThemeManager {
    constructor() {
        // Configuration
        this.config = {
            THEME_STORAGE_KEY: 'aiolia-theme',
            COLOR_STORAGE_KEY: 'aiolia-accent-color',
            DEFAULT_THEME: 'light',
            DEFAULT_COLOR: 'blue'
        };

        // Couleurs disponibles
        this.colors = {
            blue: '#3498DB',
            green: '#27AE60',
            purple: '#9B59B6',
            red: '#E74C3C',
            orange: '#F39C12'
        };

        // Éléments DOM
        this.elements = {
            body: document.body,
            themeToggle: document.getElementById('themeToggle'),
            darkModeToggle: document.getElementById('darkModeToggle'),
            themeIcon: document.getElementById('themeIcon'),
            colorOptions: document.querySelectorAll('.color-option')
        };

        this.init();
    }

    init() {
        console.log('🎨 Initialisation Theme Manager');

        // Charger le thème sauvegardé
        this.loadSavedTheme();

        // Charger la couleur sauvegardée
        this.loadSavedColor();

        // Initialiser les événements
        this.initEvents();

        console.log('✅ Theme Manager initialisé');
    }

    // ============================================
    // GESTION DU THÈME CLAIR/SOMBRE
    // ============================================

    loadSavedTheme() {
        const savedTheme = this.getStoredTheme();
        this.setTheme(savedTheme, false);
    }

    getStoredTheme() {
        try {
            return localStorage.getItem(this.config.THEME_STORAGE_KEY) || this.config.DEFAULT_THEME;
        } catch (e) {
            console.warn('Erreur lecture thème:', e);
            return this.config.DEFAULT_THEME;
        }
    }

    setTheme(theme, animate = true) {
        const isDark = theme === 'dark';

        // Appliquer le thème au body
        if (isDark) {
            this.elements.body.setAttribute('data-theme', 'dark');
        } else {
            this.elements.body.removeAttribute('data-theme');
        }

        // Mettre à jour les toggles
        if (this.elements.darkModeToggle) {
            this.elements.darkModeToggle.checked = isDark;
        }

        // Mettre à jour l'icône du header
        if (this.elements.themeIcon) {
            this.updateThemeIcon(isDark, animate);
        }

        // Sauvegarder
        this.saveTheme(theme);

        console.log(`🌓 Thème changé: ${theme}`);
    }

    updateThemeIcon(isDark, animate) {
        const iconClass = isDark ? 'fa-moon' : 'fa-sun';
        this.elements.themeIcon.className = `fas ${iconClass}`;

        if (animate && this.elements.themeToggle) {
            this.animateButton(this.elements.themeToggle);
        }
    }

    toggleTheme() {
        const currentTheme = this.getCurrentTheme();
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        this.setTheme(newTheme, true);
        this.showNotification(
            newTheme === 'dark' ? 'Mode sombre activé 🌙' : 'Mode clair activé ☀️'
        );
    }

    getCurrentTheme() {
        return this.elements.body.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
    }

    saveTheme(theme) {
        try {
            localStorage.setItem(this.config.THEME_STORAGE_KEY, theme);
        } catch (e) {
            console.warn('Erreur sauvegarde thème:', e);
        }
    }

    // ============================================
    // GESTION DE LA COULEUR D'ACCENT
    // ============================================

    loadSavedColor() {
        const savedColor = this.getStoredColor();
        this.setAccentColor(savedColor, false);
    }

    getStoredColor() {
        try {
            return localStorage.getItem(this.config.COLOR_STORAGE_KEY) || this.config.DEFAULT_COLOR;
        } catch (e) {
            console.warn('Erreur lecture couleur:', e);
            return this.config.DEFAULT_COLOR;
        }
    }

    setAccentColor(colorName, animate = true) {
        const colorValue = this.colors[colorName];

        if (!colorValue) {
            console.warn(`Couleur inconnue: ${colorName}`);
            return;
        }

        // Appliquer la couleur
        document.documentElement.style.setProperty('--accent', colorValue);
        document.documentElement.style.setProperty('--accent-light', this.lightenColor(colorValue));
        document.documentElement.style.setProperty('--accent-dark', this.darkenColor(colorValue));

        // Mettre à jour les boutons de sélection
        this.updateColorButtons(colorName);

        // Sauvegarder
        this.saveColor(colorName);

        if (animate) {
            this.showNotification('Couleur du thème changée ! 🎨');
        }

        console.log(`🎨 Couleur d'accent changée: ${colorName}`);
    }

    updateColorButtons(activeColor) {
        this.elements.colorOptions.forEach(option => {
            const color = option.getAttribute('data-color');
            if (color === activeColor) {
                option.classList.add('active');
            } else {
                option.classList.remove('active');
            }
        });
    }

    saveColor(colorName) {
        try {
            localStorage.setItem(this.config.COLOR_STORAGE_KEY, colorName);
        } catch (e) {
            console.warn('Erreur sauvegarde couleur:', e);
        }
    }

    // ============================================
    // UTILITAIRES COULEUR
    // ============================================

    lightenColor(color, percent = 15) {
        const num = parseInt(color.replace('#', ''), 16);
        const amt = Math.round(2.55 * percent);
        const R = (num >> 16) + amt;
        const G = (num >> 8 & 0x00FF) + amt;
        const B = (num & 0x0000FF) + amt;

        return '#' + (
            0x1000000 +
            (R < 255 ? R < 1 ? 0 : R : 255) * 0x10000 +
            (G < 255 ? G < 1 ? 0 : G : 255) * 0x100 +
            (B < 255 ? B < 1 ? 0 : B : 255)
        ).toString(16).slice(1);
    }

    darkenColor(color, percent = 15) {
        const num = parseInt(color.replace('#', ''), 16);
        const amt = Math.round(2.55 * percent);
        const R = (num >> 16) - amt;
        const G = (num >> 8 & 0x00FF) - amt;
        const B = (num & 0x0000FF) - amt;

        return '#' + (
            0x1000000 +
            (R > 0 ? R : 0) * 0x10000 +
            (G > 0 ? G : 0) * 0x100 +
            (B > 0 ? B : 0)
        ).toString(16).slice(1);
    }

    // ============================================
    // ÉVÉNEMENTS
    // ============================================

    initEvents() {
        // Toggle thème du header
        if (this.elements.themeToggle) {
            this.elements.themeToggle.addEventListener('click', () => {
                this.toggleTheme();
            });
        }

        // Toggle mode sombre de la page paramètres
        if (this.elements.darkModeToggle) {
            this.elements.darkModeToggle.addEventListener('change', (e) => {
                const newTheme = e.target.checked ? 'dark' : 'light';
                this.setTheme(newTheme, true);
                this.showNotification(
                    newTheme === 'dark' ? 'Mode sombre activé 🌙' : 'Mode clair activé ☀️'
                );
            });
        }

        // Sélecteurs de couleur
        this.elements.colorOptions.forEach(option => {
            option.addEventListener('click', () => {
                const color = option.getAttribute('data-color');
                this.setAccentColor(color, true);
            });
        });

        // Raccourci clavier (Ctrl/Cmd + Shift + D pour Dark mode)
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'D') {
                e.preventDefault();
                this.toggleTheme();
            }
        });

        console.log('📡 Événements du thème initialisés');
    }

    // ============================================
    // ANIMATIONS & NOTIFICATIONS
    // ============================================

    animateButton(button) {
        button.style.transition = 'transform 0.3s ease';
        button.style.transform = 'scale(1.2) rotate(360deg)';

        setTimeout(() => {
            button.style.transform = 'scale(1) rotate(0deg)';
        }, 300);
    }

    showNotification(message) {
        // Vérifier si une notification existe déjà
        const existingToast = document.querySelector('.toast-notification');
        if (existingToast) {
            existingToast.remove();
        }

        const toast = document.createElement('div');
        toast.className = 'toast-notification';
        toast.textContent = message;
        document.body.appendChild(toast);

        // Animation d'entrée
        setTimeout(() => toast.classList.add('show'), 100);

        // Animation de sortie
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    // ============================================
    // API PUBLIQUE
    // ============================================

    // Obtenir le thème actuel
    getTheme() {
        return this.getCurrentTheme();
    }

    // Obtenir la couleur actuelle
    getColor() {
        return this.getStoredColor();
    }

    // Définir le thème programmatiquement
    applyTheme(theme) {
        if (theme === 'dark' || theme === 'light') {
            this.setTheme(theme, false);
        }
    }

    // Définir la couleur programmatiquement
    applyColor(colorName) {
        if (this.colors[colorName]) {
            this.setAccentColor(colorName, false);
        }
    }

    // Réinitialiser aux valeurs par défaut
    reset() {
        this.setTheme(this.config.DEFAULT_THEME, false);
        this.setAccentColor(this.config.DEFAULT_COLOR, false);
        this.showNotification('Thème réinitialisé 🔄');
    }
}

// ============================================
// INITIALISATION AUTOMATIQUE
// ============================================

let themeManager;

function initThemeManager() {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            themeManager = new ThemeManager();
        });
    } else {
        themeManager = new ThemeManager();
    }
}

// Initialiser
initThemeManager();

// Exposer globalement
window.ThemeManager = ThemeManager;
window.themeManager = themeManager;

// Export pour modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ThemeManager;
}