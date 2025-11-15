// ==============================================================
// AIOLIA EVENT - GESTIONNAIRE DE LANGUE (FR / EN)
// ==============================================================
// Ce module gère :
//   - le chargement et la persistance de la langue utilisateur
//   - l'application des traductions via des attributs data-i18n
//   - une API simple pour interagir avec les autres scripts
// Utilisation côté template :
//   - Ajouter data-i18n="ma.cle" sur l'élément à traduire
//   - (optionnel) data-i18n-target="placeholder|value|html|aria-label"
//   - (optionnel) data-i18n-attributes="aria-label,title" pour appliquer
//     la même traduction sur plusieurs attributs
// ==============================================================

'use strict';

(function(window, document) {
    const DEFAULT_TRANSLATIONS = {
        fr: {
            'app.language.name.fr': 'Français',
            'app.language.name.en': 'English',
            'settings.title': '⚙️ Paramètres',
            'settings.subtitle': 'Personnalisez votre expérience Aiolia Event',
            'settings.sections.appearance.title': 'Apparence',
            'settings.sections.appearance.description': "Personnalisez l\'interface selon vos préférences",
            'settings.controls.dark_mode.title': 'Mode sombre',
            'settings.controls.dark_mode.description': 'Activez le thème sombre pour réduire la fatigue oculaire',
            'settings.controls.color_theme.title': 'Thème de couleur',
            'settings.controls.color_theme.description': 'Choisissez votre couleur d\'accentuation',
            'settings.sections.locale.title': 'Langue et région',
            'settings.sections.locale.description': 'Configurez votre langue et format régional',
            'settings.controls.language.title': "Langue de l'interface",
            'settings.controls.language.description': "Choisissez la langue d'affichage de l'application",
            'settings.controls.language.option.fr': '🇫🇷 Français',
            'settings.controls.language.option.en': '🇬🇧 English',
            'settings.controls.currency.title': 'Devise',
            'settings.controls.currency.description': "Monnaie d'affichage des prix",
            'settings.controls.currency.option.eur': '€ Euro (EUR)',
            'settings.controls.currency.option.mga': 'Ar Ariary (MGA)',
            'settings.controls.currency.option.usd': '$ Dollar (USD)',
            'settings.toast.language': 'Langue changée : {language} 🌍',
            'settings.toast.currency': 'Devise changée : {currency} 💱',
            'settings.colors.blue': 'Bleu',
            'settings.colors.green': 'Vert',
            'settings.colors.purple': 'Violet',
            'settings.colors.red': 'Rouge',
            'settings.colors.orange': 'Orange',
            'layout.sidebar.dashboard': 'Tableau Bord',
            'layout.sidebar.analytics_section': 'Analytics',
            'layout.sidebar.analytics_reports': 'Rapports Analytics',
            'layout.sidebar.analytics_stats': 'Statistiques Données',
            'layout.sidebar.admin_section': 'Administration',
            'layout.sidebar.users': 'Gestion Utilisateurs',
            'layout.sidebar.billing': 'Facturation & Paiement',
            'layout.sidebar.account_section': 'Compte',
            'layout.sidebar.profile': 'Mon Profil',
            'layout.sidebar.settings': 'Paramètres Système',
            'layout.header.search_placeholder': 'Rechercher...',
            'layout.header.notifications_alert': 'Panneau de notifications à implémenter',
            'layout.header.settings': 'Paramètres',
            'layout.header.help_support': 'Aide & Support',
            'layout.header.logout': 'Déconnexion',
            'layout.header.search_label': 'Rechercher',
            'layout.header.toggle_theme': 'Basculer le thème',
            'layout.header.notifications': 'Notifications',
            'layout.footer.help': 'Aide',
            'layout.footer.terms': 'Conditions',
            'layout.footer.privacy': 'Confidentialité',
            'layout.footer.contact': 'Contact',
            'layout.footer.rights': 'Tous droits réservés.',
            'layout.footer.copyright': '&copy; 2025 Aiolia Event.'
            ,'reports.meta.title': 'Rapports - Aiolia Event'
            ,'reports.header.title': '📄 Rapports'
            ,'reports.header.subtitle': 'Rapports fiscaux et exports de données'
            ,'reports.actions.export_pdf': 'Exporter PDF'
            ,'reports.actions.export_csv': 'Exporter CSV'
            ,'reports.kpi.organizers': 'Organisateurs'
            ,'reports.kpi.users': 'Utilisateurs'
            ,'reports.kpi.active_subscriptions': 'Abonnements actifs'
            ,'reports.kpi.subscription_revenue': 'Revenus abonnements'
            ,'reports.sections.tax_stats.title': '💰 Statistiques fiscales'
            ,'reports.sections.tax_stats.subtitle': 'Revenus et taxes du mois en cours'
            ,'reports.stats.gross_revenue': 'Revenus bruts'
            ,'reports.stats.vat': 'TVA (18%)'
            ,'reports.stats.platform_commission': 'Commissions plateforme (5%)'
            ,'reports.stats.net_revenue': 'Revenus nets'
            ,'reports.sections.monthly_reports.title': '📊 Rapports mensuels (abonnements)'
            ,'reports.sections.monthly_reports.subtitle': 'Synthèse des abonnements et revenus par mois'
            ,'reports.months.october_2025': 'Octobre 2025'
            ,'reports.months.september_2025': 'Septembre 2025'
            ,'reports.months.august_2025': 'Août 2025'
            ,'reports.labels.organizers': 'Organisateurs'
            ,'reports.labels.active_subscriptions': 'Abonnements actifs'
            ,'reports.labels.subscription_revenue': 'Revenus abonnements'
            ,'reports.actions.download': 'Télécharger'
            ,'reports.sections.custom.title': '⚙️ Générer un rapport personnalisé'
            ,'reports.sections.custom.subtitle': 'Créez un rapport sur mesure avec les critères de votre choix'
            ,'reports.form.start_period': 'Période de début'
            ,'reports.form.end_period': 'Période de fin'
            ,'reports.form.report_type': 'Type de rapport'
            ,'reports.form.export_format': 'Format d\'export'
            ,'reports.form.option.full_report': 'Rapport complet'
            ,'reports.form.option.revenue_only': 'Revenus uniquement'
            ,'reports.form.option.sales_by_event': 'Ventes par événement'
            ,'reports.form.option.tax_stats': 'Statistiques fiscales'
            ,'reports.form.option.pdf': 'PDF'
            ,'reports.form.option.csv': 'CSV'
            ,'reports.form.option.excel': 'Excel'
            ,'reports.actions.generate': 'Générer le rapport'
            ,'validation.meta.title': 'Validation des comptes - Aiolia Event'
            ,'validation.header.title': 'Validation des comptes'
            ,'validation.header.subtitle': 'Gérez les demandes de validation de rôle des utilisateurs'
            ,'validation.stats.summary.title': 'Résumé des comptes'
            ,'validation.stats.total_pending': 'Comptes en attente'
            ,'validation.stats.organizers': 'Organisateurs'
            ,'validation.stats.users': 'Utilisateurs'
            ,'validation.pending.section.title': 'Comptes en attente de validation'
            ,'validation.pending.section.subtitle': 'Liste des comptes nécessitant une validation manuelle'
            ,'validation.pending.empty.title': 'Aucun compte en attente'
            ,'validation.pending.empty.subtitle': 'Tous les comptes ont été traités.'
            ,'validation.table.headers.user': 'Utilisateur'
            ,'validation.table.headers.role': 'Rôle demandé'
            ,'validation.table.headers.status': 'Statut'
            ,'validation.table.headers.submitted_at': 'Soumis le'
            ,'validation.table.headers.actions': 'Actions'
            ,'validation.table.button.view': 'Voir le profil'
            ,'validation.table.button.approve': 'Approuver'
            ,'validation.table.button.reject': 'Rejeter'
            ,'validation.table.status.pending': 'En attente'
            ,'validation.table.status.approved': 'Approuvé'
            ,'validation.table.status.rejected': 'Rejeté'
            ,'validation.confirm.title': 'Confirmer l\'action'
            ,'validation.confirm.approve': 'Approuver le compte de {name} ?'
            ,'validation.confirm.reject': 'Rejeter le compte de {name} ?'
            ,'validation.confirm.approve.button': 'Approuver'
            ,'validation.confirm.reject.button': 'Rejeter'
            ,'validation.confirm.cancel': 'Annuler'
            ,'pagination.previous': 'Précédent'
            ,'pagination.next': 'Suivant'
            ,'profile.meta.title': 'Mon profil - Aiolia Event'
            ,'profile.header.title': '👤 Mon profil'
            ,'profile.header.subtitle': 'Gérez vos informations personnelles'
            ,'profile.avatar.alt': 'Photo de {firstName} {lastName}'
            ,'profile.avatar.change': 'Changer la photo de profil'
            ,'profile.details.email': 'Email'
            ,'profile.details.phone': 'Téléphone'
            ,'profile.details.phone.unset': 'Non renseigné'
            ,'profile.details.role': 'Rôle'
            ,'profile.details.member_since': 'Membre depuis'
            ,'profile.details.last_login': 'Dernière connexion'
            ,'profile.sidebar.account_settings': 'Paramètres du compte'
            ,'profile.sidebar.edit_profile': 'Modifier le profil'
            ,'profile.sidebar.change_password': 'Changer le mot de passe'
            ,'profile.edit.meta.title': 'Modifier mon profil - Aiolia Event'
            ,'profile.edit.header.title': '✏️ Modifier mon profil'
            ,'profile.edit.actions.back': 'Retour'
            ,'profile.edit.form.first_name.label': 'Prénom'
            ,'profile.edit.form.last_name.label': 'Nom'
            ,'profile.edit.form.email.label': 'Email'
            ,'profile.edit.form.email.help': "L'email ne peut pas être modifié"
            ,'profile.edit.form.phone.label': 'Téléphone'
            ,'profile.edit.form.submit': 'Enregistrer'
            ,'profile.edit.form.cancel': 'Annuler'
            ,'profile.password.meta.title': 'Changer le mot de passe - Aiolia Event'
            ,'profile.password.header.title': '🔐 Changer le mot de passe'
            ,'profile.password.form.current.label': 'Mot de passe actuel'
            ,'profile.password.form.current.placeholder': 'Entrez votre mot de passe actuel'
            ,'profile.password.form.new.label': 'Nouveau mot de passe'
            ,'profile.password.form.new.placeholder': 'Entrez un nouveau mot de passe'
            ,'profile.password.form.confirm.label': 'Confirmer le nouveau mot de passe'
            ,'profile.password.form.confirm.placeholder': 'Confirmez le mot de passe'
            ,'profile.password.form.submit': 'Changer le mot de passe'
            ,'profile.password.form.cancel': 'Annuler'
        },
        en: {
            'app.language.name.fr': 'French',
            'app.language.name.en': 'English',
            'settings.title': '⚙️ Settings',
            'settings.subtitle': 'Customize your Aiolia Event experience',
            'settings.sections.appearance.title': 'Appearance',
            'settings.sections.appearance.description': 'Personalize the interface to match your preferences',
            'settings.controls.dark_mode.title': 'Dark mode',
            'settings.controls.dark_mode.description': 'Enable the dark theme to reduce eye strain',
            'settings.controls.color_theme.title': 'Color theme',
            'settings.controls.color_theme.description': 'Choose your accent color',
            'settings.sections.locale.title': 'Language & region',
            'settings.sections.locale.description': 'Configure your language and regional formats',
            'settings.controls.language.title': 'Interface language',
            'settings.controls.language.description': 'Choose the display language for the application',
            'settings.controls.language.option.fr': '🇫🇷 French',
            'settings.controls.language.option.en': '🇬🇧 English',
            'settings.controls.currency.title': 'Currency',
            'settings.controls.currency.description': 'Currency used to display prices',
            'settings.controls.currency.option.eur': '€ Euro (EUR)',
            'settings.controls.currency.option.mga': 'Ar Ariary (MGA)',
            'settings.controls.currency.option.usd': '$ Dollar (USD)',
            'settings.toast.language': 'Language changed: {language} 🌍',
            'settings.toast.currency': 'Currency changed: {currency} 💱',
            'settings.colors.blue': 'Blue',
            'settings.colors.green': 'Green',
            'settings.colors.purple': 'Purple',
            'settings.colors.red': 'Red',
            'settings.colors.orange': 'Orange',
            'layout.sidebar.dashboard': 'Dashboard',
            'layout.sidebar.analytics_section': 'Analytics',
            'layout.sidebar.analytics_reports': 'Analytics Reports',
            'layout.sidebar.analytics_stats': 'Data Statistics',
            'layout.sidebar.admin_section': 'Administration',
            'layout.sidebar.users': 'User Management',
            'layout.sidebar.billing': 'Billing & Payment',
            'layout.sidebar.account_section': 'Account',
            'layout.sidebar.profile': 'My Profile',
            'layout.sidebar.settings': 'System Settings',
            'layout.header.search_placeholder': 'Search...',
            'layout.header.notifications_alert': 'Notification panel to implement',
            'layout.header.settings': 'Settings',
            'layout.header.help_support': 'Help & Support',
            'layout.header.logout': 'Log out',
            'layout.header.search_label': 'Search',
            'layout.header.toggle_theme': 'Toggle theme',
            'layout.header.notifications': 'Notifications',
            'layout.footer.help': 'Help',
            'layout.footer.terms': 'Terms',
            'layout.footer.privacy': 'Privacy',
            'layout.footer.contact': 'Contact',
            'layout.footer.rights': 'All rights reserved.',
            'layout.footer.copyright': '&copy; 2025 Aiolia Event.'
            ,'reports.meta.title': 'Reports - Aiolia Event'
            ,'reports.header.title': '📄 Reports'
            ,'reports.header.subtitle': 'Tax reports and data exports'
            ,'reports.actions.export_pdf': 'Export PDF'
            ,'reports.actions.export_csv': 'Export CSV'
            ,'reports.kpi.organizers': 'Organizers'
            ,'reports.kpi.users': 'Users'
            ,'reports.kpi.active_subscriptions': 'Active subscriptions'
            ,'reports.kpi.subscription_revenue': 'Subscription revenue'
            ,'reports.sections.tax_stats.title': '💰 Tax statistics'
            ,'reports.sections.tax_stats.subtitle': 'Current month revenue and taxes'
            ,'reports.stats.gross_revenue': 'Gross revenue'
            ,'reports.stats.vat': 'VAT (18%)'
            ,'reports.stats.platform_commission': 'Platform commission (5%)'
            ,'reports.stats.net_revenue': 'Net revenue'
            ,'reports.sections.monthly_reports.title': '📊 Monthly reports (subscriptions)'
            ,'reports.sections.monthly_reports.subtitle': 'Monthly overview of subscriptions and revenue'
            ,'reports.months.october_2025': 'October 2025'
            ,'reports.months.september_2025': 'September 2025'
            ,'reports.months.august_2025': 'August 2025'
            ,'reports.labels.organizers': 'Organizers'
            ,'reports.labels.active_subscriptions': 'Active subscriptions'
            ,'reports.labels.subscription_revenue': 'Subscription revenue'
            ,'reports.actions.download': 'Download'
            ,'reports.sections.custom.title': '⚙️ Generate a custom report'
            ,'reports.sections.custom.subtitle': 'Create a tailored report with your own criteria'
            ,'reports.form.start_period': 'Start period'
            ,'reports.form.end_period': 'End period'
            ,'reports.form.report_type': 'Report type'
            ,'reports.form.export_format': 'Export format'
            ,'reports.form.option.full_report': 'Full report'
            ,'reports.form.option.revenue_only': 'Revenue only'
            ,'reports.form.option.sales_by_event': 'Sales by event'
            ,'reports.form.option.tax_stats': 'Tax statistics'
            ,'reports.form.option.pdf': 'PDF'
            ,'reports.form.option.csv': 'CSV'
            ,'reports.form.option.excel': 'Excel'
            ,'reports.actions.generate': 'Generate report'
            ,'validation.meta.title': 'Account Validation - Aiolia Event'
            ,'validation.header.title': 'Account Validation'
            ,'validation.header.subtitle': 'Manage user role validation requests'
            ,'validation.stats.summary.title': 'Account overview'
            ,'validation.stats.total_pending': 'Pending accounts'
            ,'validation.stats.organizers': 'Organizers'
            ,'validation.stats.users': 'Users'
            ,'validation.pending.section.title': 'Accounts pending validation'
            ,'validation.pending.section.subtitle': 'List of accounts requiring manual validation'
            ,'validation.pending.empty.title': 'No accounts pending'
            ,'validation.pending.empty.subtitle': 'All accounts have been processed.'
            ,'validation.table.headers.user': 'User'
            ,'validation.table.headers.role': 'Requested role'
            ,'validation.table.headers.status': 'Status'
            ,'validation.table.headers.submitted_at': 'Submitted on'
            ,'validation.table.headers.actions': 'Actions'
            ,'validation.table.button.view': 'View profile'
            ,'validation.table.button.approve': 'Approve'
            ,'validation.table.button.reject': 'Reject'
            ,'validation.table.status.pending': 'Pending'
            ,'validation.table.status.approved': 'Approved'
            ,'validation.table.status.rejected': 'Rejected'
            ,'validation.confirm.title': 'Confirm action'
            ,'validation.confirm.approve': 'Approve {name}\'s account?'
            ,'validation.confirm.reject': 'Reject {name}\'s account?'
            ,'validation.confirm.approve.button': 'Approve'
            ,'validation.confirm.reject.button': 'Reject'
            ,'validation.confirm.cancel': 'Cancel'
            ,'pagination.previous': 'Previous'
            ,'pagination.next': 'Next'
            ,'profile.meta.title': 'My Profile - Aiolia Event'
            ,'profile.header.title': '👤 My Profile'
            ,'profile.header.subtitle': 'Manage your personal information'
            ,'profile.avatar.alt': 'Photo of {firstName} {lastName}'
            ,'profile.avatar.change': 'Change profile photo'
            ,'profile.details.email': 'Email'
            ,'profile.details.phone': 'Phone'
            ,'profile.details.phone.unset': 'Not provided'
            ,'profile.details.role': 'Role'
            ,'profile.details.member_since': 'Member since'
            ,'profile.details.last_login': 'Last login'
            ,'profile.sidebar.account_settings': 'Account settings'
            ,'profile.sidebar.edit_profile': 'Edit profile'
            ,'profile.sidebar.change_password': 'Change password'
            ,'profile.edit.meta.title': 'Edit My Profile - Aiolia Event'
            ,'profile.edit.header.title': '✏️ Edit my profile'
            ,'profile.edit.actions.back': 'Back'
            ,'profile.edit.form.first_name.label': 'First name'
            ,'profile.edit.form.last_name.label': 'Last name'
            ,'profile.edit.form.email.label': 'Email'
            ,'profile.edit.form.email.help': 'Email cannot be changed'
            ,'profile.edit.form.phone.label': 'Phone'
            ,'profile.edit.form.submit': 'Save'
            ,'profile.edit.form.cancel': 'Cancel'
            ,'profile.password.meta.title': 'Change password - Aiolia Event'
            ,'profile.password.header.title': '🔐 Change password'
            ,'profile.password.form.current.label': 'Current password'
            ,'profile.password.form.current.placeholder': 'Enter your current password'
            ,'profile.password.form.new.label': 'New password'
            ,'profile.password.form.new.placeholder': 'Enter a new password'
            ,'profile.password.form.confirm.label': 'Confirm new password'
            ,'profile.password.form.confirm.placeholder': 'Confirm the password'
            ,'profile.password.form.submit': 'Change password'
            ,'profile.password.form.cancel': 'Cancel'
        }
    };

    class LanguageManager {
        constructor(options = {}) {
            this.config = {
                storageKey: options.storageKey || 'aiolia-language',
                defaultLanguage: options.defaultLanguage || 'fr',
                selector: options.selector || '[data-i18n]',
                availableLanguages: options.availableLanguages || ['fr', 'en'],
                translations: options.translations || DEFAULT_TRANSLATIONS
            };

            this.translations = this.config.translations;
            this.availableLanguages = this.config.availableLanguages;
            this._mergeGlobalTranslations();
            this.currentLanguage = this._loadInitialLanguage();
            this._syncHtmlLangAttribute();

            this._onLanguageChangeCallbacks = new Set();

            this._initAutoApply();
        }

        // --------------------------------------------------------------
        // INITIALISATION & DÉTECTION
        // --------------------------------------------------------------

        _loadInitialLanguage() {
            const stored = this._readFromStorage();
            if (stored && this._isSupported(stored)) {
                return stored;
            }

            const browserLang = (navigator.language || navigator.userLanguage || '').slice(0, 2).toLowerCase();
            if (this._isSupported(browserLang)) {
                return browserLang;
            }

            return this.config.defaultLanguage;
        }

        _initAutoApply() {
            const apply = () => this.applyTranslations();

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', apply, { once: true });
            } else {
                apply();
            }

            window.addEventListener('load', () => this.applyTranslations());
        }

        _isSupported(lang) {
            return !!lang && this.availableLanguages.includes(lang);
        }

        _syncHtmlLangAttribute() {
            document.documentElement.setAttribute('lang', this.currentLanguage);
        }

        _readFromStorage() {
            try {
                return localStorage.getItem(this.config.storageKey);
            } catch (error) {
                console.warn('[LanguageManager] Lecture stockage impossible:', error);
                return null;
            }
        }

        _writeToStorage(lang) {
            try {
                localStorage.setItem(this.config.storageKey, lang);
            } catch (error) {
                console.warn('[LanguageManager] Sauvegarde stockage impossible:', error);
            }
        }

        // --------------------------------------------------------------
        // API PUBLIQUE
        // --------------------------------------------------------------

        getLanguage() {
            return this.currentLanguage;
        }

        setLanguage(lang, options = {}) {
            const targetLang = this._isSupported(lang) ? lang : this.config.defaultLanguage;
            const previousLang = this.currentLanguage;

            if (targetLang === previousLang) {
                if (options.persist !== false) {
                    this._writeToStorage(targetLang);
                }
                return targetLang;
            }

            this.currentLanguage = targetLang;

            if (options.persist !== false) {
                this._writeToStorage(targetLang);
            }

            this._syncHtmlLangAttribute();
            this.applyTranslations();
            this._dispatchLanguageChange(previousLang, targetLang);
            return targetLang;
        }

        registerLanguageSelector(element) {
            if (!element) return;

            element.value = this.getLanguage();
            element.addEventListener('change', (event) => {
                const newLang = event.target.value;
                const appliedLang = this.setLanguage(newLang, { persist: true });

                if (event.target.value !== appliedLang) {
                    event.target.value = appliedLang;
                }
            });
        }

        applyTranslations(root = document) {
            const extraSelector = '[data-i18n-fr], [data-i18n-en], [data-i18n-attributes]';
            const selector = `${this.config.selector}, ${extraSelector}`;
            const elements = root.querySelectorAll(selector);
            elements.forEach(element => this._applyTranslationToElement(element));
        }

        translate(key, params = {}, lang = this.getLanguage()) {
            const base = this.getTranslation(key, lang);
            return this._interpolate(base, params);
        }

        getTranslation(key, lang = this.getLanguage()) {
            if (!key) return '';

            const langTranslations = this.translations[lang] || {};
            const defaultTranslations = this.translations[this.config.defaultLanguage] || {};

            return langTranslations[key] || defaultTranslations[key] || '';
        }

        onLanguageChange(callback) {
            if (typeof callback === 'function') {
                this._onLanguageChangeCallbacks.add(callback);
            }

            return () => this._onLanguageChangeCallbacks.delete(callback);
        }

        extendTranslations(newTranslations = {}) {
            Object.keys(newTranslations).forEach(lang => {
                if (!this.translations[lang]) {
                    this.translations[lang] = {};
                }
                Object.assign(this.translations[lang], newTranslations[lang]);
            });

            this.applyTranslations();
        }

        // --------------------------------------------------------------
        // INTERNES
        // --------------------------------------------------------------

        _applyTranslationToElement(element) {
            const key = element.dataset.i18n;
            const params = this._parseParams(element.dataset.i18nParams);
            const target = element.dataset.i18nTarget || 'text';
            const attributes = (element.dataset.i18nAttributes || '').split(',').map(attr => attr.trim()).filter(Boolean);
            const hasChildElements = element.children && element.children.length > 0;

            this._ensureDefaultDataset(element, target);

            let translation = '';
            let translationSource = 'none';

            if (key) {
                const value = this.translate(key, params);
                if (value) {
                    translation = value;
                    translationSource = 'i18n';
                }
            }

            if (translationSource === 'none') {
                const datasetValue = this._getDatasetTranslation(element, this.getLanguage());
                if (datasetValue) {
                    translation = datasetValue;
                    translationSource = 'dataset';
                }
            }

            if (translationSource === 'none' && this.getLanguage() !== this.config.defaultLanguage) {
                const fallbackValue = this._getDatasetTranslation(element, this.config.defaultLanguage);
                if (fallbackValue) {
                    translation = fallbackValue;
                    translationSource = 'dataset-default';
                }
            }

            if (translationSource === 'none') {
                translation = this._getOriginalValue(element, target) || '';
                translationSource = 'original';
            }

            translation = this._interpolate(translation, params);

            const shouldSkipTextUpdate = (target === 'text' && translationSource === 'original' && hasChildElements && !translation);

            if (!shouldSkipTextUpdate) {
                switch (target) {
                    case 'html':
                        element.innerHTML = translation;
                        break;
                    case 'placeholder':
                        element.setAttribute('placeholder', translation);
                        break;
                    case 'value':
                        element.value = translation;
                        break;
                    default:
                        element.textContent = translation;
                }
            }

            attributes.forEach(attr => {
                const attrTranslation = this._getAttributeTranslation(element, attr, params, translation);
                element.setAttribute(attr, attrTranslation);
            });
        }

        _mergeGlobalTranslations() {
            const globalTranslations = (window.AioliaTranslations && window.AioliaTranslations.translations) || null;
            if (!globalTranslations) {
                return;
            }

            Object.keys(globalTranslations).forEach(lang => {
                if (!this.translations[lang]) {
                    this.translations[lang] = {};
                }
                Object.assign(this.translations[lang], globalTranslations[lang]);
                if (!this.availableLanguages.includes(lang)) {
                    this.availableLanguages.push(lang);
                }
            });
        }

        _parseParams(paramsString) {
            if (!paramsString) {
                return {};
            }

            try {
                return JSON.parse(paramsString);
            } catch (error) {
                console.warn('[LanguageManager] Impossible de parser les paramètres i18n:', error);
                return {};
            }
        }

        _langDatasetKey(lang) {
            if (!lang) return null;
            return `i18n${lang.charAt(0).toUpperCase()}${lang.slice(1)}`;
        }

        _getDatasetTranslation(element, lang) {
            const key = this._langDatasetKey(lang);
            if (!key) return null;
            return element.dataset[key] || null;
        }

        _ensureDefaultDataset(element, target) {
            const defaultLangKey = this._langDatasetKey(this.config.defaultLanguage);
            const hasDefaultDataset = defaultLangKey && element.dataset[defaultLangKey];

            if (!hasDefaultDataset) {
                const originalValue = this._getOriginalValue(element, target);
                if (originalValue && defaultLangKey) {
                    element.dataset[defaultLangKey] = originalValue;
                }
            }

            if (!element.dataset.i18nOriginal) {
                const originalValue = this._getOriginalValue(element, target);
                if (originalValue) {
                    element.dataset.i18nOriginal = originalValue;
                }
            }
        }

        _getOriginalValue(element, target) {
            if (element.dataset.i18nOriginal) {
                return element.dataset.i18nOriginal;
            }

            switch (target) {
                case 'html':
                    return element.innerHTML;
                case 'placeholder':
                    return element.getAttribute('placeholder') || '';
                case 'value':
                    return element.value || '';
                default:
                    return element.textContent || '';
            }
        }

        _getAttributeTranslation(element, attribute, params, fallback) {
            const lang = this.getLanguage();
            const sanitized = this._sanitizeAttributeName(attribute);
            let translation = null;

            if (sanitized) {
                const attrKeyProp = `i18nAttr${sanitized}Key`;
                const attrKey = element.dataset[attrKeyProp];
                if (attrKey) {
                    translation = this.translate(attrKey, params, lang);
                }
            }

            if (!translation) {
                const datasetKey = this._buildAttributeDatasetKey(attribute, lang);
                translation = datasetKey ? element.dataset[datasetKey] : null;

                if (!translation && lang !== this.config.defaultLanguage) {
                    const defaultKey = this._buildAttributeDatasetKey(attribute, this.config.defaultLanguage);
                    translation = defaultKey ? element.dataset[defaultKey] : null;
                }
            }

            if (!translation) {
                translation = fallback;
            }

            return this._interpolate(translation || '', params);
        }

        _buildAttributeDatasetKey(attribute, lang) {
            const sanitized = this._sanitizeAttributeName(attribute);
            if (!sanitized || !lang) {
                return null;
            }

            const langKey = lang.charAt(0).toUpperCase() + lang.slice(1);
            return `i18nAttr${sanitized}${langKey}`;
        }

        _sanitizeAttributeName(attribute) {
            if (!attribute) {
                return '';
            }

            return attribute
                .split('-')
                .map(part => part.replace(/[^a-zA-Z0-9]/g, ''))
                .filter(Boolean)
                .map(part => part.charAt(0).toUpperCase() + part.slice(1))
                .join('');
        }

        _interpolate(template, params = {}) {
            return Object.keys(params).reduce((acc, key) => {
                const value = params[key];
                const placeholder = new RegExp(`\\{${key}\\}`, 'g');
                return acc.replace(placeholder, value);
            }, template);
        }

        _dispatchLanguageChange(previousLang, currentLang) {
            const detail = { previous: previousLang, current: currentLang };

            this._onLanguageChangeCallbacks.forEach(callback => {
                try {
                    callback(detail);
                } catch (error) {
                    console.error('[LanguageManager] Callback erreur:', error);
                }
            });

            const customEvent = new CustomEvent('aiolia:language-changed', { detail });
            document.dispatchEvent(customEvent);
        }
    }

    // --------------------------------------------------------------
    // INITIALISATION GLOBALE
    // --------------------------------------------------------------

    const existingInstance = window.languageManager;

    if (existingInstance instanceof LanguageManager) {
        existingInstance.extendTranslations(DEFAULT_TRANSLATIONS);
    } else {
        const manager = new LanguageManager();
        window.languageManager = manager;
        window.LanguageManager = LanguageManager;
    }

})(window, document);


