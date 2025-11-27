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
            ,'billing.header.title': 'Facturation & Paiement'
            ,'billing.header.subtitle': 'Gérez les factures et les paiements'
            ,'billing.filters.title': 'Filtres de recherche'
            ,'billing.filters.search': 'Recherche'
            ,'billing.filters.status': 'Statut'
            ,'billing.filters.status.all': 'Tous les statuts'
            ,'billing.filters.status.draft': 'Brouillon'
            ,'billing.filters.status.issued': 'Émise'
            ,'billing.filters.status.paid': 'Payée'
            ,'billing.filters.status.overdue': 'En retard'
            ,'billing.filters.status.void': 'Annulée'
            ,'billing.filters.status.refunded': 'Remboursée'
            ,'billing.filters.status.suspended': 'Suspendue'
            ,'billing.filters.status.pending': 'En attente'
            ,'billing.filters.date_from': 'Date de début'
            ,'billing.filters.date_to': 'Date de fin'
            ,'billing.actions.search': 'Rechercher'
            ,'billing.actions.reset': 'Réinitialiser'
            ,'billing.table.number': 'Numéro'
            ,'billing.table.customer': 'Client'
            ,'billing.table.plan': 'Offre'
            ,'billing.table.amount': 'Montant'
            ,'billing.table.status': 'Statut'
            ,'billing.table.period': 'Période'
            ,'billing.table.actions': 'Actions'
            ,'billing.table.empty': 'Aucune facture trouvée'
            ,'billing.invoice.title': 'Facture {number}'
            ,'billing.invoice.type.ticket': 'Facture de billet'
            ,'billing.invoice.type.subscription': 'Facture d\'abonnement'
            ,'billing.invoice.actions.download_pdf': 'Télécharger PDF'
            ,'billing.invoice.actions.resend_email': 'Renvoyer par email'
            ,'billing.invoice.actions.report_delay': 'Signaler le retard'
            ,'billing.invoice.actions.back': 'Retour'
            ,'billing.invoice.label': 'Facture'
            ,'billing.invoice.billing_month': 'Mois de facturation : {month} {year}'
            ,'billing.invoice.issued_on': 'Émise le {date}'
            ,'billing.invoice.pause_month': 'Mois en pause'
            ,'billing.invoice.status.paid': 'Payée'
            ,'billing.invoice.status.overdue': 'En retard'
            ,'billing.invoice.status.pending': 'En attente'
            ,'billing.invoice.status.suspended': 'Suspendue'
            ,'billing.invoice.status.cancelled': 'Annulée'
            ,'billing.invoice.status.refunded': 'Remboursée'
            ,'billing.invoice.overdue.days': '{days} jour{s} de retard'
            ,'billing.invoice.customer.title': 'Organisateur'
            ,'billing.invoice.customer.title_alt': 'Client'
            ,'billing.invoice.customer.name': 'Nom'
            ,'billing.invoice.customer.organizer_id': 'ID Organisateur'
            ,'billing.invoice.customer.email': 'Email'
            ,'billing.invoice.customer.phone': 'Téléphone'
            ,'billing.invoice.customer.active_plan': 'Offre active'
            ,'billing.invoice.customer.plan_name': 'Nom du plan'
            ,'billing.invoice.customer.plan_change': 'Changement de plan'
            ,'billing.invoice.customer.plan_change.yes': 'Oui'
            ,'billing.invoice.billing_info.title': 'Informations de facturation'
            ,'billing.invoice.billing_info.type': 'Type'
            ,'billing.invoice.billing_info.type.ticket': 'Billet'
            ,'billing.invoice.billing_info.type.subscription': 'Abonnement'
            ,'billing.invoice.billing_info.due_date': 'Date d\'échéance'
            ,'billing.invoice.billing_info.paid_on': 'Payée le'
            ,'billing.invoice.billing_info.created_on': 'Créée le'
            ,'billing.invoice.billing_info.updated': 'Mise à jour'
            ,'billing.invoice.summary.title': 'Résumé financier'
            ,'billing.invoice.summary.amount_excl_vat': 'Montant HT'
            ,'billing.invoice.summary.vat': 'TVA (20%)'
            ,'billing.invoice.summary.amount_incl_vat': 'Montant TTC'
            ,'billing.invoice.summary.subtotal': 'Sous-total'
            ,'billing.invoice.summary.total': 'Total à payer'
            ,'billing.invoice.prepaid': 'Facture prépayée'
            ,'billing.invoice.history.title': 'Historique des paiements précédents'
            ,'billing.invoice.additional.title': 'Informations supplémentaires'
            ,'billing.invoice.additional.reference': 'Référence unique'
            ,'billing.invoice.additional.payment_method': 'Mode de paiement'
            ,'billing.invoice.additional.usage_period': 'Période d\'utilisation'
            ,'billing.invoice.additional.pause_month_note': 'Mois en pause - Facture à 0 Ar'
            ,'billing.plan.tier.basic': 'Basic'
            ,'billing.plan.tier.pro': 'Pro'
            ,'billing.plan.tier.enterprise': 'Enterprise'
            ,'billing.plan.period.monthly': 'Mensuel'
            ,'billing.plan.period.quarterly': 'Trimestriel'
            ,'billing.plan.period.yearly': 'Annuel'
            ,'billing.payment_method.espace': 'Espace'
            ,'billing.payment_method.orange': 'Orange Money'
            ,'billing.payment_method.airtel': 'Airtel Money'
            ,'billing.payment_method.telma': 'Telma Money'
            ,'billing.payment_method.bank_transfer': 'Virement bancaire'
            ,'users.header.title': 'Gestion des utilisateurs'
            ,'users.header.subtitle': 'Gérez et administrez les comptes utilisateurs de votre plateforme'
            ,'users.stats.total_users': 'Total utilisateurs'
            ,'users.stats.pending': 'En attente'
            ,'users.stats.active_organizers': 'Organisateurs actifs'
            ,'users.stats.total_organizers': 'Total organisateurs'
            ,'users.filters.title': 'Recherche multicritère'
            ,'users.filters.search.label': 'Recherche (Nom, Email, Téléphone)'
            ,'users.filters.search.placeholder': 'Rechercher par nom, email ou téléphone...'
            ,'users.filters.role': 'Rôle'
            ,'users.filters.role.all': 'Tous les rôles'
            ,'users.filters.role.user': 'Utilisateur'
            ,'users.filters.role.organizer': 'Organisateur'
            ,'users.filters.role.admin': 'Administrateur'
            ,'users.filters.status': 'Statut'
            ,'users.filters.status.all': 'Tous les statuts'
            ,'users.filters.status.active': 'Actif'
            ,'users.filters.status.pending': 'En attente'
            ,'users.filters.status.paused': 'En pause'
            ,'users.filters.status.rejected': 'Rejeté'
            ,'users.filters.sort_by': 'Trier par'
            ,'users.filters.sort.created_at': 'Date création'
            ,'users.filters.sort.email': 'Email'
            ,'users.filters.sort.first_name': 'Prénom'
            ,'users.filters.sort.last_name': 'Nom'
            ,'users.filters.order': 'Ordre'
            ,'users.filters.order.desc': 'Décroissant'
            ,'users.filters.order.asc': 'Croissant'
            ,'users.actions.search': 'Rechercher'
            ,'users.actions.reset': 'Réinitialiser'
            ,'users.table.user': 'Utilisateur'
            ,'users.table.role': 'Rôle'
            ,'users.table.status': 'Statut'
            ,'users.table.activity': 'Activité'
            ,'users.table.actions': 'Actions'
            ,'users.table.empty': 'Aucun utilisateur trouvé'
            ,'users.role.admin': 'Administrateur'
            ,'users.role.organizer': 'Organisateur'
            ,'users.role.user': 'Utilisateur'
            ,'users.status.active': 'Actif'
            ,'users.status.pending': 'En attente'
            ,'users.status.rejected': 'Rejeté'
            ,'users.status.paused': 'En pause'
            ,'users.status.inactive': 'Inactif'
            ,'users.actions.view': 'Voir le profil'
            ,'users.actions.payments': 'Paiements'
            ,'users.actions.events': 'Événements'
            ,'users.show.title': 'Fiche utilisateur'
            ,'users.show.active_plan': 'Offre active'
            ,'users.payments.title': 'Historique des paiements'
            ,'users.payments.subtitle': 'Fiche utilisateur'
            ,'users.payments.prepaid_months': 'Mois restants prépayés'
            ,'users.payments.next_payment': 'Prochain paiement prévu'
            ,'users.payments.current_pause': 'Pause actuelle'
            ,'users.payments.active_plan': 'Offre active'
            ,'users.payments.pause.yes': 'Oui'
            ,'users.payments.pause.no': 'Non'
            ,'users.payments.pause.none': 'Aucun'
            ,'users.payments.table.billing_month': 'Mois de facturation'
            ,'users.payments.table.amount': 'Montant'
            ,'users.payments.table.plan': 'Plan'
            ,'users.payments.table.status': 'Statut'
            ,'users.payments.table.payment_date': 'Date de paiement'
            ,'users.payments.table.reference': 'Référence'
            ,'users.payments.table.actions': 'Actions'
            ,'users.payments.status.paid': 'Payé'
            ,'users.payments.status.issued': 'Émise'
            ,'users.payments.status.overdue': 'En retard'
            ,'users.payments.status.pending': 'En attente'
            ,'users.payments.status.suspended': 'Suspendue'
            ,'users.payments.status.not_paid': 'Non payé'
            ,'users.payments.actions.details': 'Détails'
            ,'users.payments.empty': 'Aucun paiement enregistré pour cet utilisateur'
            ,'common.back': 'Retour'
            ,'common.search': 'Rechercher'
            ,'common.reset': 'Réinitialiser'
            ,'common.actions': 'Actions'
            ,'common.status': 'Statut'
            ,'common.name': 'Nom'
            ,'common.email': 'Email'
            ,'common.phone': 'Téléphone'
            ,'common.yes': 'Oui'
            ,'common.no': 'Non'
            ,'common.none': 'Aucun'
            ,'common.na': 'N/A'
            ,'common.details': 'Détails'
            ,'common.view': 'Voir'
            ,'common.edit': 'Modifier'
            ,'common.delete': 'Supprimer'
            ,'common.cancel': 'Annuler'
            ,'common.save': 'Enregistrer'
            ,'common.close': 'Fermer'
            ,'validation.pagination.info': 'Page {current} sur {total} - Affichage de {showing} compte(s) sur {total_count} total'
            ,'stats.meta.title': 'Statistiques - Aiolia Event'
            ,'stats.header.title': 'Statistiques'
            ,'stats.header.subtitle': 'Analysez les performances et les tendances de votre plateforme'
            ,'stats.widgets.active_organizers': 'Organisateurs actifs'
            ,'stats.widgets.new_organizers': 'Nouveaux organisateurs'
            ,'stats.widgets.most_used_subscription': 'Offre la plus utilisée'
            ,'stats.widgets.revenue_forecast': 'Chiffre d\'affaire'
            ,'stats.filters.title': 'Filtres de recherche'
            ,'stats.filters.month': 'Mois'
            ,'stats.filters.month.all': 'Tous les mois'
            ,'stats.filters.month.january': 'Janvier'
            ,'stats.filters.month.february': 'Février'
            ,'stats.filters.month.march': 'Mars'
            ,'stats.filters.month.april': 'Avril'
            ,'stats.filters.month.may': 'Mai'
            ,'stats.filters.month.june': 'Juin'
            ,'stats.filters.month.july': 'Juillet'
            ,'stats.filters.month.august': 'Août'
            ,'stats.filters.month.september': 'Septembre'
            ,'stats.filters.month.october': 'Octobre'
            ,'stats.filters.month.november': 'Novembre'
            ,'stats.filters.month.december': 'Décembre'
            ,'stats.filters.year': 'Année'
            ,'stats.actions.search': 'Rechercher'
            ,'stats.actions.reset': 'Réinitialiser'
            ,'stats.charts.active_organizers': 'Organisateurs actifs'
            ,'stats.charts.subscription_distribution': 'Répartition des abonnements'
            ,'stats.charts.revenue_curve': 'Courbe CA (HT/TTC/TVA)'
            ,'stats.charts.labels.ca_ht': 'CA HT (Ar)'
            ,'stats.charts.labels.tva': 'TVA (Ar)'
            ,'stats.charts.labels.ca_ttc': 'CA TTC (Ar)'
            ,'stats.charts.labels.basic': 'Basic'
            ,'stats.charts.labels.pro': 'Pro'
            ,'stats.charts.labels.enterprise': 'Enterprise'
            ,'stats.charts.labels.invoices': 'facture'
            ,'stats.charts.labels.invoices_plural': 'factures'
            ,'stats.charts.labels.no_invoices': 'Aucune facture'
            ,'stats.charts.labels.subscription_count': 'Nombre d\'abonnements'
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
            ,'billing.header.title': 'Billing & Payment'
            ,'billing.header.subtitle': 'Manage invoices and payments'
            ,'billing.filters.title': 'Search filters'
            ,'billing.filters.search': 'Search'
            ,'billing.filters.status': 'Status'
            ,'billing.filters.status.all': 'All statuses'
            ,'billing.filters.status.draft': 'Draft'
            ,'billing.filters.status.issued': 'Issued'
            ,'billing.filters.status.paid': 'Paid'
            ,'billing.filters.status.overdue': 'Overdue'
            ,'billing.filters.status.void': 'Void'
            ,'billing.filters.status.refunded': 'Refunded'
            ,'billing.filters.status.suspended': 'Suspended'
            ,'billing.filters.status.pending': 'Pending'
            ,'billing.filters.date_from': 'Start date'
            ,'billing.filters.date_to': 'End date'
            ,'billing.actions.search': 'Search'
            ,'billing.actions.reset': 'Reset'
            ,'billing.table.number': 'Number'
            ,'billing.table.customer': 'Customer'
            ,'billing.table.plan': 'Plan'
            ,'billing.table.amount': 'Amount'
            ,'billing.table.status': 'Status'
            ,'billing.table.period': 'Period'
            ,'billing.table.actions': 'Actions'
            ,'billing.table.empty': 'No invoices found'
            ,'billing.invoice.title': 'Invoice {number}'
            ,'billing.invoice.type.ticket': 'Ticket invoice'
            ,'billing.invoice.type.subscription': 'Subscription invoice'
            ,'billing.invoice.actions.download_pdf': 'Download PDF'
            ,'billing.invoice.actions.resend_email': 'Resend email'
            ,'billing.invoice.actions.report_delay': 'Report delay'
            ,'billing.invoice.actions.back': 'Back'
            ,'billing.invoice.label': 'Invoice'
            ,'billing.invoice.billing_month': 'Billing month: {month} {year}'
            ,'billing.invoice.issued_on': 'Issued on {date}'
            ,'billing.invoice.pause_month': 'Pause month'
            ,'billing.invoice.status.paid': 'Paid'
            ,'billing.invoice.status.overdue': 'Overdue'
            ,'billing.invoice.status.pending': 'Pending'
            ,'billing.invoice.status.suspended': 'Suspended'
            ,'billing.invoice.status.cancelled': 'Cancelled'
            ,'billing.invoice.status.refunded': 'Refunded'
            ,'billing.invoice.overdue.days': '{days} day{s} overdue'
            ,'billing.invoice.customer.title': 'Organizer'
            ,'billing.invoice.customer.title_alt': 'Customer'
            ,'billing.invoice.customer.name': 'Name'
            ,'billing.invoice.customer.organizer_id': 'Organizer ID'
            ,'billing.invoice.customer.email': 'Email'
            ,'billing.invoice.customer.phone': 'Phone'
            ,'billing.invoice.customer.active_plan': 'Active plan'
            ,'billing.invoice.customer.plan_name': 'Plan name'
            ,'billing.invoice.customer.plan_change': 'Plan change'
            ,'billing.invoice.customer.plan_change.yes': 'Yes'
            ,'billing.invoice.billing_info.title': 'Billing information'
            ,'billing.invoice.billing_info.type': 'Type'
            ,'billing.invoice.billing_info.type.ticket': 'Ticket'
            ,'billing.invoice.billing_info.type.subscription': 'Subscription'
            ,'billing.invoice.billing_info.due_date': 'Due date'
            ,'billing.invoice.billing_info.paid_on': 'Paid on'
            ,'billing.invoice.billing_info.created_on': 'Created on'
            ,'billing.invoice.billing_info.updated': 'Updated'
            ,'billing.invoice.summary.title': 'Financial summary'
            ,'billing.invoice.summary.amount_excl_vat': 'Amount excl. VAT'
            ,'billing.invoice.summary.vat': 'VAT (20%)'
            ,'billing.invoice.summary.amount_incl_vat': 'Amount incl. VAT'
            ,'billing.invoice.summary.subtotal': 'Subtotal'
            ,'billing.invoice.summary.total': 'Total to pay'
            ,'billing.invoice.prepaid': 'Prepaid invoice'
            ,'billing.invoice.history.title': 'Previous payment history'
            ,'billing.invoice.additional.title': 'Additional information'
            ,'billing.invoice.additional.reference': 'Unique reference'
            ,'billing.invoice.additional.payment_method': 'Payment method'
            ,'billing.invoice.additional.usage_period': 'Usage period'
            ,'billing.invoice.additional.pause_month_note': 'Pause month - 0 Ar invoice'
            ,'billing.plan.tier.basic': 'Basic'
            ,'billing.plan.tier.pro': 'Pro'
            ,'billing.plan.tier.enterprise': 'Enterprise'
            ,'billing.plan.period.monthly': 'Monthly'
            ,'billing.plan.period.quarterly': 'Quarterly'
            ,'billing.plan.period.yearly': 'Yearly'
            ,'billing.payment_method.espace': 'Espace'
            ,'billing.payment_method.orange': 'Orange Money'
            ,'billing.payment_method.airtel': 'Airtel Money'
            ,'billing.payment_method.telma': 'Telma Money'
            ,'billing.payment_method.bank_transfer': 'Bank transfer'
            ,'users.header.title': 'User management'
            ,'users.header.subtitle': 'Manage and administer your platform user accounts'
            ,'users.stats.total_users': 'Total users'
            ,'users.stats.pending': 'Pending'
            ,'users.stats.active_organizers': 'Active organizers'
            ,'users.stats.total_organizers': 'Total organizers'
            ,'users.filters.title': 'Multi-criteria search'
            ,'users.filters.search.label': 'Search (Name, Email, Phone)'
            ,'users.filters.search.placeholder': 'Search by name, email or phone...'
            ,'users.filters.role': 'Role'
            ,'users.filters.role.all': 'All roles'
            ,'users.filters.role.user': 'User'
            ,'users.filters.role.organizer': 'Organizer'
            ,'users.filters.role.admin': 'Administrator'
            ,'users.filters.status': 'Status'
            ,'users.filters.status.all': 'All statuses'
            ,'users.filters.status.active': 'Active'
            ,'users.filters.status.pending': 'Pending'
            ,'users.filters.status.paused': 'Paused'
            ,'users.filters.status.rejected': 'Rejected'
            ,'users.filters.sort_by': 'Sort by'
            ,'users.filters.sort.created_at': 'Creation date'
            ,'users.filters.sort.email': 'Email'
            ,'users.filters.sort.first_name': 'First name'
            ,'users.filters.sort.last_name': 'Last name'
            ,'users.filters.order': 'Order'
            ,'users.filters.order.desc': 'Descending'
            ,'users.filters.order.asc': 'Ascending'
            ,'users.actions.search': 'Search'
            ,'users.actions.reset': 'Reset'
            ,'users.table.user': 'User'
            ,'users.table.role': 'Role'
            ,'users.table.status': 'Status'
            ,'users.table.activity': 'Activity'
            ,'users.table.actions': 'Actions'
            ,'users.table.empty': 'No users found'
            ,'users.role.admin': 'Administrator'
            ,'users.role.organizer': 'Organizer'
            ,'users.role.user': 'User'
            ,'users.status.active': 'Active'
            ,'users.status.pending': 'Pending'
            ,'users.status.rejected': 'Rejected'
            ,'users.status.paused': 'Paused'
            ,'users.status.inactive': 'Inactive'
            ,'users.actions.view': 'View profile'
            ,'users.actions.payments': 'Payments'
            ,'users.actions.events': 'Events'
            ,'users.show.title': 'User profile'
            ,'users.show.active_plan': 'Active plan'
            ,'users.payments.title': 'Payment history'
            ,'users.payments.subtitle': 'User profile'
            ,'users.payments.prepaid_months': 'Prepaid months remaining'
            ,'users.payments.next_payment': 'Next scheduled payment'
            ,'users.payments.current_pause': 'Current pause'
            ,'users.payments.active_plan': 'Active plan'
            ,'users.payments.pause.yes': 'Yes'
            ,'users.payments.pause.no': 'No'
            ,'users.payments.pause.none': 'None'
            ,'users.payments.table.billing_month': 'Billing month'
            ,'users.payments.table.amount': 'Amount'
            ,'users.payments.table.plan': 'Plan'
            ,'users.payments.table.status': 'Status'
            ,'users.payments.table.payment_date': 'Payment date'
            ,'users.payments.table.reference': 'Reference'
            ,'users.payments.table.actions': 'Actions'
            ,'users.payments.status.paid': 'Paid'
            ,'users.payments.status.issued': 'Issued'
            ,'users.payments.status.overdue': 'Overdue'
            ,'users.payments.status.pending': 'Pending'
            ,'users.payments.status.suspended': 'Suspended'
            ,'users.payments.status.not_paid': 'Not paid'
            ,'users.payments.actions.details': 'Details'
            ,'users.payments.empty': 'No payment recorded for this user'
            ,'common.back': 'Back'
            ,'common.search': 'Search'
            ,'common.reset': 'Reset'
            ,'common.actions': 'Actions'
            ,'common.status': 'Status'
            ,'common.name': 'Name'
            ,'common.email': 'Email'
            ,'common.phone': 'Phone'
            ,'common.yes': 'Yes'
            ,'common.no': 'No'
            ,'common.none': 'None'
            ,'common.na': 'N/A'
            ,'common.details': 'Details'
            ,'common.view': 'View'
            ,'common.edit': 'Edit'
            ,'common.delete': 'Delete'
            ,'common.cancel': 'Cancel'
            ,'common.save': 'Save'
            ,'common.close': 'Close'
            ,'validation.pagination.info': 'Page {current} of {total} - Showing {showing} account(s) out of {total_count} total'
            ,'stats.meta.title': 'Statistics - Aiolia Event'
            ,'stats.header.title': 'Statistics'
            ,'stats.header.subtitle': 'Analyze your platform performance and trends'
            ,'stats.widgets.active_organizers': 'Active organizers'
            ,'stats.widgets.new_organizers': 'New organizers'
            ,'stats.widgets.most_used_subscription': 'Most used plan'
            ,'stats.widgets.revenue_forecast': 'Revenue'
            ,'stats.filters.title': 'Search filters'
            ,'stats.filters.month': 'Month'
            ,'stats.filters.month.all': 'All months'
            ,'stats.filters.month.january': 'January'
            ,'stats.filters.month.february': 'February'
            ,'stats.filters.month.march': 'March'
            ,'stats.filters.month.april': 'April'
            ,'stats.filters.month.may': 'May'
            ,'stats.filters.month.june': 'June'
            ,'stats.filters.month.july': 'July'
            ,'stats.filters.month.august': 'August'
            ,'stats.filters.month.september': 'September'
            ,'stats.filters.month.october': 'October'
            ,'stats.filters.month.november': 'November'
            ,'stats.filters.month.december': 'December'
            ,'stats.filters.year': 'Year'
            ,'stats.actions.search': 'Search'
            ,'stats.actions.reset': 'Reset'
            ,'stats.charts.active_organizers': 'Active organizers'
            ,'stats.charts.subscription_distribution': 'Subscription distribution'
            ,'stats.charts.revenue_curve': 'Revenue curve (HT/TTC/VAT)'
            ,'stats.charts.labels.ca_ht': 'Revenue HT (Ar)'
            ,'stats.charts.labels.tva': 'VAT (Ar)'
            ,'stats.charts.labels.ca_ttc': 'Revenue TTC (Ar)'
            ,'stats.charts.labels.basic': 'Basic'
            ,'stats.charts.labels.pro': 'Pro'
            ,'stats.charts.labels.enterprise': 'Enterprise'
            ,'stats.charts.labels.invoices': 'invoice'
            ,'stats.charts.labels.invoices_plural': 'invoices'
            ,'stats.charts.labels.no_invoices': 'No invoices'
            ,'stats.charts.labels.subscription_count': 'Number of subscriptions'
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


