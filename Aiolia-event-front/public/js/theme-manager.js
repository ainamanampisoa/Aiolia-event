/**
 * Gestionnaire de thème pour Aiolia Event
 * Gère l'application et la persistance du thème (clair/sombre)
 */

class ThemeManager {
    constructor() {
        this.STORAGE_KEY = 'aiolia_theme';
        this.THEME_LIGHT = 'light';
        this.THEME_DARK = 'dark';
        this.currentTheme = null;
        
        this.init();
    }
    
    /**
     * Initialise le gestionnaire de thème
     */
    init() {
        // Récupérer le thème depuis localStorage (priorité car plus fiable)
        const storedTheme = localStorage.getItem(this.STORAGE_KEY);
        
        // Récupérer le thème depuis les préférences utilisateur (passé via data attribute)
        const htmlElement = document.documentElement;
        const userTheme = htmlElement.getAttribute('data-user-theme');
        
        // Déterminer le thème à utiliser
        // Priorité : localStorage > préférence utilisateur > light par défaut
        // localStorage a la priorité car il reflète le dernier choix de l'utilisateur
        if (storedTheme && (storedTheme === this.THEME_LIGHT || storedTheme === this.THEME_DARK)) {
            this.currentTheme = storedTheme;
        } else if (userTheme && (userTheme === this.THEME_LIGHT || userTheme === this.THEME_DARK)) {
            this.currentTheme = userTheme;
            // Synchroniser localStorage avec les préférences serveur
            try {
                localStorage.setItem(this.STORAGE_KEY, userTheme);
            } catch(e) {
                console.warn('Impossible de sauvegarder dans localStorage:', e);
            }
        } else {
            this.currentTheme = this.THEME_LIGHT;
        }
        
        // Appliquer le thème immédiatement (même si déjà appliqué par le script inline)
        this.applyTheme(this.currentTheme);
        
        // Écouter les changements de préférences système (optionnel)
        this.watchSystemPreference();
    }
    
    /**
     * Applique le thème spécifié
     * @param {string} theme - 'light' ou 'dark'
     */
    applyTheme(theme) {
        if (theme !== this.THEME_LIGHT && theme !== this.THEME_DARK) {
            console.warn(`Thème invalide: ${theme}. Utilisation du thème clair par défaut.`);
            theme = this.THEME_LIGHT;
        }
        
        this.currentTheme = theme;
        const htmlElement = document.documentElement;
        const bodyElement = document.body;
        
        // Retirer les classes existantes
        htmlElement.classList.remove('theme-light', 'theme-dark');
        bodyElement.classList.remove('theme-light', 'theme-dark');
        
        // Ajouter la classe du thème
        htmlElement.classList.add(`theme-${theme}`);
        bodyElement.classList.add(`theme-${theme}`);
        
        // Ajouter un attribut data pour faciliter le ciblage CSS
        htmlElement.setAttribute('data-theme', theme);
        
        // Sauvegarder dans localStorage
        localStorage.setItem(this.STORAGE_KEY, theme);
        
        // Déclencher un événement personnalisé
        document.dispatchEvent(new CustomEvent('themeChanged', {
            detail: { theme: theme }
        }));
    }
    
    /**
     * Bascule entre le thème clair et sombre
     */
    toggleTheme() {
        const newTheme = this.currentTheme === this.THEME_LIGHT 
            ? this.THEME_DARK 
            : this.THEME_LIGHT;
        this.applyTheme(newTheme);
        return newTheme;
    }
    
    /**
     * Définit le thème explicitement
     * @param {string} theme - 'light' ou 'dark'
     */
    setTheme(theme) {
        this.applyTheme(theme);
    }
    
    /**
     * Récupère le thème actuel
     * @returns {string} 'light' ou 'dark'
     */
    getTheme() {
        return this.currentTheme;
    }
    
    /**
     * Écoute les changements de préférence système (optionnel)
     * Peut être utilisé pour détecter les changements de préférence système
     */
    watchSystemPreference() {
        if (window.matchMedia) {
            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            
            // Ne s'applique que si l'utilisateur n'a pas de préférence explicite
            const handleChange = (e) => {
                // Si l'utilisateur a une préférence sauvegardée, ne pas écouter le système
                const storedTheme = localStorage.getItem(this.STORAGE_KEY);
                if (!storedTheme) {
                    // Pas de préférence utilisateur, suivre le système
                    this.applyTheme(e.matches ? this.THEME_DARK : this.THEME_LIGHT);
                }
            };
            
            // Écouter les changements
            if (mediaQuery.addEventListener) {
                mediaQuery.addEventListener('change', handleChange);
            } else {
                // Support pour les anciens navigateurs
                mediaQuery.addListener(handleChange);
            }
        }
    }
}

// Créer une instance globale
let themeManager;

// Fonction d'initialisation qui peut être appelée plusieurs fois
function initThemeManager() {
    if (!themeManager) {
        themeManager = new ThemeManager();
        window.themeManager = themeManager;
        console.log('ThemeManager initialisé avec le thème:', themeManager.getTheme());
    }
    return themeManager;
}

// Auto-initialisation au chargement du DOM
// Le thème est déjà appliqué par le script inline dans base.html.twig
// On initialise themeManager pour qu'il soit disponible globalement
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        initThemeManager();
        console.log('ThemeManager initialisé après DOMContentLoaded');
    });
} else {
    // DOM déjà chargé, initialiser immédiatement
    initThemeManager();
    console.log('ThemeManager initialisé immédiatement (DOM déjà chargé)');
}

