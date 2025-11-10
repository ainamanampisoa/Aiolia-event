// ============================================
// THEME MANAGER - Gestion du mode sombre et thème
// ============================================

'use strict';

// === Remplace/colle ceci dans ton fichier, en remplacement des méthodes correspondantes ===

class ThemeManager {
    constructor() {
        // Configuration (idem)
        this.config = {
            THEME_STORAGE_KEY: 'aiolia-theme',
            COLOR_STORAGE_KEY: 'aiolia-accent-color',
            DEFAULT_THEME: 'light',
            DEFAULT_COLOR: 'blue'
        };

        // Couleurs (idem)
        this.colors = {
            blue: '#3498DB',
            green: '#27AE60',
            purple: '#9B59B6',
            red: '#E74C3C',
            orange: '#F39C12'
        };

        // État
        this.currentTheme = null;
        this.isInitialized = false;

        // Éléments DOM — garantir des références valides tout de suite
        this.elements = {
            html: document.documentElement,
            body: document.body,
            themeToggle: null,
            darkModeToggle: null,
            themeIcon: null,
            colorOptions: null
        };

        // Observer pour détecter overrides externes (optionnel mais utile)
        this.externalObserver = null;

        // Notifications & animations
        this.toastElement = null;
        this.toastTimeout = null;
        this.currentAccentColor = null;

        this.init();
    }

    init() {
        console.log('🎨 Initialisation Theme Manager (robuste)');

        // Charger et appliquer le thème *en utilisant les éléments DOM natifs* (sécurisé)
        this.loadAndApplyTheme();

        // Attendre la DOM ready pour récupérer les contrôles interactifs
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.initDOMElements();
            });
        } else {
            this.initDOMElements();
        }

        console.log('✅ Theme Manager initialisé (immediate theme:', this.currentTheme, ')');
    }

    // ----------------------------
    // Chargement + application
    // ----------------------------
    loadAndApplyTheme() {
        // Priorité à ce qui est déjà posé dans le body/html par le framework (si présent)
        const frameworkTheme = (document.body && document.body.getAttribute('data-theme')) ||
                               (document.documentElement && document.documentElement.getAttribute('data-theme'));

        const savedTheme = frameworkTheme || this.getStoredTheme() || this.config.DEFAULT_THEME;
        console.log('📖 loadAndApplyTheme -> frameworkTheme:', frameworkTheme, ' / stored:', this.getStoredTheme());
        this.currentTheme = savedTheme;
        // Appliquer immédiatement
        this.applyThemeToDOM(savedTheme);
    }

    getStoredTheme() {
        try {
            return localStorage.getItem(this.config.THEME_STORAGE_KEY) || this.config.DEFAULT_THEME;
        } catch (e) {
            console.warn('Erreur lecture thème:', e);
            return this.config.DEFAULT_THEME;
        }
    }

    applyThemeToDOM(theme) {
        // Utiliser toujours document.documentElement / document.body directement (plus fiable)
        const html = document.documentElement;
        const body = document.body;

        if (!html || !body) {
            console.warn('⚠️ applyThemeToDOM: document elements non disponibles');
            return;
        }

        // Retirer ancien thème
        html.removeAttribute('data-theme');
        body.removeAttribute('data-theme');
        html.classList.remove('dark-mode', 'light-mode');
        body.classList.remove('dark-mode', 'light-mode');

        // Appliquer le thème demandé
        if (theme === 'dark') {
            html.setAttribute('data-theme', 'dark');
            body.setAttribute('data-theme', 'dark');
            html.classList.add('dark-mode');
            body.classList.add('dark-mode');
            console.log('🌙 Mode sombre appliqué au DOM');
        } else {
            html.setAttribute('data-theme', 'light');
            body.setAttribute('data-theme', 'light');
            html.classList.add('light-mode');
            body.classList.add('light-mode');
            console.log('☀️ Mode clair appliqué au DOM');
        }

        this.currentTheme = theme;

        // Petit délai d'animation / repaint puis dispatch
        requestAnimationFrame(() => {
            // Sauvegarde (au cas où cette méthode est appelée directement)
            try { localStorage.setItem(this.config.THEME_STORAGE_KEY, theme); } catch(e) {}

            // Émettre l'événement pour que d'autres scripts (header) mettent à jour l'icône
            document.dispatchEvent(new CustomEvent('aiolia:theme-changed', { detail: { theme } }));
            console.log('📣 aiolia:theme-changed dispatché ->', theme);
        });
    }

    // ----------------------------
    // setTheme / toggle
    // ----------------------------
    setTheme(theme, showNotification = false) {
        if (theme === this.currentTheme) {
            console.log('⏭️ setTheme: thème déjà actif', theme);
            return;
        }

        console.log('🔄 setTheme', this.currentTheme, '→', theme);
        this.applyThemeToDOM(theme);      // applique + dispatch
        this.saveTheme(theme);
        this.syncUIState();               // synchroniser bouton/icône
        if (showNotification) {
            const msg = theme === 'dark' ? 'Mode sombre activé 🌙' : 'Mode clair activé ☀️';
            this.showNotification(msg);
        }
        // Animation si présent
        if (this.elements.themeToggle) this.animateButton(this.elements.themeToggle);
    }

    toggleTheme() {
        const newTheme = this.currentTheme === 'dark' ? 'light' : 'dark';
        this.setTheme(newTheme, true);
    }

    saveTheme(theme) {
        try {
            localStorage.setItem(this.config.THEME_STORAGE_KEY, theme);
            console.log('💾 Thème sauvegardé:', theme);
        } catch (e) {
            console.warn('Erreur sauvegarde thème:', e);
        }
    }

    // ----------------------------
    // DOM init + events
    // ----------------------------
    initDOMElements() {
        // assigner éléments interactifs
        this.elements.themeToggle = document.getElementById('themeToggle');
        this.elements.darkModeToggle = document.getElementById('darkModeToggle');
        this.elements.themeIcon = document.getElementById('themeIcon');
        this.elements.colorOptions = document.querySelectorAll('.color-option');

        // marquer initialisé avant de brancher les handlers pour ne pas ignorer le premier changement volontaire
        this.isInitialized = true;

        // synchroniser état UI (icone + toggles) sans déclencher events
        this.syncUIState();

        // load color
        this.loadSavedColor();

        // initialiser events
        this.initEvents();

        // démarrer observer externe pour détecter overrides (si un autre script modifie data-theme)
        this.initExternalObserver();

        console.log('✅ Éléments DOM initialisés et events attachés');
    }

    initEvents() {
        // header button
        if (this.elements.themeToggle) {
            this.elements.themeToggle.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.toggleTheme();
            });
        }

        // settings toggle (checkbox)
        if (this.elements.darkModeToggle) {
            this.elements.darkModeToggle.addEventListener('change', (e) => {
                if (!this.isInitialized) {
                    console.log('⏭️ Ignorer change pendant init');
                    return;
                }
                const newTheme = e.target.checked ? 'dark' : 'light';
                if (newTheme !== this.currentTheme) this.setTheme(newTheme, true);
            });
        }

        // couleurs
        if (this.elements.colorOptions) {
            this.elements.colorOptions.forEach(opt => {
                opt.addEventListener('click', () => {
                    const c = opt.getAttribute('data-color');
                    if (c) this.setAccentColor(c, true);
                });
            });
        }

        // keyboard shortcut
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'D') {
                e.preventDefault();
                this.toggleTheme();
            }
        });
    }

    // Synchronisation UI (icone + checkbox)
    syncUIState() {
        const theme = this.currentTheme || this.getStoredTheme() || this.config.DEFAULT_THEME;

        // icon
        if (this.elements.themeIcon) {
            const iconClass = theme === 'dark' ? 'fa-moon' : 'fa-sun';
            this.elements.themeIcon.classList.remove('fa-moon', 'fa-sun');
            this.elements.themeIcon.classList.add('fas', iconClass);
            this.elements.themeIcon.dataset.themeIcon = iconClass === 'fa-moon' ? 'moon' : 'sun';
            console.log('🔄 Icône synchronisée ->', iconClass);
        }

        // checkbox toggle
        if (this.elements.darkModeToggle) {
            this.elements.darkModeToggle.checked = (theme === 'dark');
            console.log('🔄 Toggle checkbox synchronisé ->', this.elements.darkModeToggle.checked);
        }

        // couleurs
        const accentKey = this.currentAccentColor || this.getStoredAccentColor();
        if (this.elements.colorOptions && accentKey) {
            this.elements.colorOptions.forEach(option => {
                const optionKey = option.getAttribute('data-color');
                if (optionKey === accentKey) {
                    option.classList.add('active');
                } else {
                    option.classList.remove('active');
                }
            });
        }
    }

    // ----------------------------
    // External mutation observer
    // ----------------------------
    initExternalObserver() {
        // Si déjà présent, skip
        if (this.externalObserver) return;

        const target = document.documentElement;
        if (!target) return;

        this.externalObserver = new MutationObserver(mutations => {
            for (const m of mutations) {
                if (m.type === 'attributes' && (m.attributeName === 'data-theme')) {
                    const externalTheme = document.documentElement.getAttribute('data-theme') || document.body.getAttribute('data-theme');
                    if (externalTheme && externalTheme !== this.currentTheme) {
                        console.warn('⚠️ Theme overridden par un script externe ->', externalTheme);
                        // Ré-synchroniser notre état sans écraser le choix externe,
                        // mais on peut le prendre en compte :
                        this.currentTheme = externalTheme;
                        this.syncUIState();
                        document.dispatchEvent(new CustomEvent('aiolia:theme-changed', { detail: { theme: externalTheme } }));
                    }
                }
            }
        });

        this.externalObserver.observe(target, { attributes: true, attributeFilter: ['data-theme'] });
        console.log('🔎 MutationObserver pour overrides externes activé');
    }

    // ... le reste des méthodes (couleurs, lighten/darken, notifications, animateButton, etc.) restent inchangées ...
    // Assure-toi que saveTheme est appelé cohérent (applyThemeToDOM effectue un set localStorage en requestAnimationFrame).

    // ----------------------------
    // Couleurs d'accent
    // ----------------------------
    loadSavedColor() {
        const storedKey = this.getStoredAccentColor();
        this.applyAccentColor(storedKey, { persist: false });
    }

    getStoredAccentColor() {
        try {
            return localStorage.getItem(this.config.COLOR_STORAGE_KEY) || this.config.DEFAULT_COLOR;
        } catch (error) {
            console.warn('Erreur lecture couleur accent:', error);
            return this.config.DEFAULT_COLOR;
        }
    }

    saveAccentColor(colorKey) {
        try {
            localStorage.setItem(this.config.COLOR_STORAGE_KEY, colorKey);
            console.log('💾 Couleur sauvegardée:', colorKey);
        } catch (error) {
            console.warn('Erreur sauvegarde couleur accent:', error);
        }
    }

    setAccentColor(colorKey, showNotification = false) {
        const appliedKey = this.applyAccentColor(colorKey);
        if (!appliedKey) return;

        this.saveAccentColor(appliedKey);
        if (showNotification) {
            const label = colorKey.charAt(0).toUpperCase() + colorKey.slice(1);
            this.showNotification(`Couleur d'accent mise à jour : ${label} 🎨`);
        }
    }

    applyAccentColor(colorKey, options = {}) {
        if (!colorKey) {
            colorKey = this.config.DEFAULT_COLOR;
        }

        const baseColor = this.colors[colorKey] || colorKey;
        if (!baseColor) {
            console.warn('Couleur inconnue, utilisation de la valeur par défaut');
            return null;
        }

        const normalizedBase = this.normalizeHex(baseColor);
        const accentLight = this.lightenColor(normalizedBase, 0.18);
        const accentDark = this.darkenColor(normalizedBase, 0.16);

        const root = document.documentElement;
        root.style.setProperty('--accent', normalizedBase);
        root.style.setProperty('--accent-light', accentLight);
        root.style.setProperty('--accent-dark', accentDark);

        this.currentAccentColor = colorKey;
        this.syncUIState();

        if (options.persist !== false) {
            this.saveAccentColor(colorKey);
        }

        return colorKey;
    }

    normalizeHex(hex) {
        if (!hex) return '#000000';
        let value = hex.trim();
        if (!value.startsWith('#')) {
            value = `#${value}`;
        }
        if (value.length === 4) {
            value = `#${value[1]}${value[1]}${value[2]}${value[2]}${value[3]}${value[3]}`;
        }
        return value.toUpperCase();
    }

    hexToRgb(hex) {
        const normalized = this.normalizeHex(hex).replace('#', '');
        const bigint = parseInt(normalized, 16);
        return {
            r: (bigint >> 16) & 255,
            g: (bigint >> 8) & 255,
            b: bigint & 255
        };
    }

    rgbToHex({ r, g, b }) {
        const toHex = (value) => {
            const clamped = Math.max(0, Math.min(255, Math.round(value)));
            return clamped.toString(16).padStart(2, '0');
        };
        return `#${toHex(r)}${toHex(g)}${toHex(b)}`.toUpperCase();
    }

    lightenColor(hex, amount = 0.2) {
        const { r, g, b } = this.hexToRgb(hex);
        return this.rgbToHex({
            r: r + (255 - r) * amount,
            g: g + (255 - g) * amount,
            b: b + (255 - b) * amount
        });
    }

    darkenColor(hex, amount = 0.2) {
        const { r, g, b } = this.hexToRgb(hex);
        return this.rgbToHex({
            r: r * (1 - amount),
            g: g * (1 - amount),
            b: b * (1 - amount)
        });
    }

    // ----------------------------
    // Notifications & animations
    // ----------------------------
    ensureToastElement() {
        if (this.toastElement && document.body.contains(this.toastElement)) {
            return this.toastElement;
        }

        const toast = document.createElement('div');
        toast.className = 'toast-notification';
        document.body.appendChild(toast);
        this.toastElement = toast;
        return toast;
    }

    showNotification(message, options = {}) {
        const { duration = 3500 } = options;
        const toast = this.ensureToastElement();

        toast.textContent = message;
        toast.classList.add('show');

        if (this.toastTimeout) {
            clearTimeout(this.toastTimeout);
        }

        this.toastTimeout = setTimeout(() => {
            this.hideNotification();
        }, duration);
    }

    hideNotification() {
        if (!this.toastElement) return;
        this.toastElement.classList.remove('show');
        this.toastElement.classList.add('hiding');

        setTimeout(() => {
            if (this.toastElement) {
                this.toastElement.classList.remove('hiding');
            }
        }, 300);
    }

    animateButton(button) {
        if (!button) return;
        button.classList.add('theme-toggle-anim');
        setTimeout(() => {
            button.classList.remove('theme-toggle-anim');
        }, 350);
    }

    // ----------------------------
    // API publique
    // ----------------------------
    getTheme() {
        return this.currentTheme || this.getStoredTheme();
    }

    getAccentColor() {
        return this.currentAccentColor || this.getStoredAccentColor();
    }

    forceSync() {
        console.log('🔁 Synchronisation forcée du ThemeManager');
        this.loadAndApplyTheme();
        this.syncUIState();
        this.applyAccentColor(this.getStoredAccentColor(), { persist: false });
    }

    destroy() {
        if (this.externalObserver) {
            this.externalObserver.disconnect();
            this.externalObserver = null;
        }
        if (this.toastTimeout) {
            clearTimeout(this.toastTimeout);
            this.toastTimeout = null;
        }
        this.toastElement = null;
        console.log('🧹 ThemeManager détruit');
    }
}


// ============================================
// INITIALISATION AUTOMATIQUE
// ============================================

let themeManager;

function initThemeManager() {
    // Créer une seule instance globale
    if (!window.themeManager) {
        themeManager = new ThemeManager();
        window.themeManager = themeManager;
        window.ThemeManager = ThemeManager;
        console.log('🎯 ThemeManager global créé');
    } else {
        // Si déjà existant, forcer la resynchronisation
        console.log('🔄 ThemeManager existant, resynchronisation...');
        window.themeManager.forceSync();
    }
}

// Initialiser au chargement
initThemeManager();

// Réinitialiser lors de la navigation (pour les SPA ou Turbo)
window.addEventListener('load', () => {
    if (window.themeManager && window.themeManager.isInitialized) {
        window.themeManager.forceSync();
    }
});

// Export pour modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ThemeManager;
}