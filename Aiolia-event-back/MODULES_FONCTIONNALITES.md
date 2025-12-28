# 📚 Modules et Fonctionnalités - Aiolia Event Backend

**Documentation des modules développés pour la plateforme Aiolia Event**

---

## 📋 Table des Matières

1. [Module 1 : Gestion des Utilisateurs (Admin)](#module-1--gestion-des-utilisateurs-admin)
2. [Module 2 : Module Facturation (Admin)](#module-2--module-facturation-admin)
3. [Module 4 : Gestion des Événements (Organisateur)](#module-4--gestion-des-événements-organisateur)
5. [Module 5 : Gestion des Billets/Tickets (Organisateur)](#module-5--gestion-des-billets-tickets-organisateur)
6. [Module 6 : Module Paiements et Abonnements (Organisateur)](#module-6--module-paiements-et-abonnements-organisateur)
7. [Module 7 : Module Promotions et Codes Promo (Organisateur)](#module-7--module-promotions-et-codes-promo-organisateur)
8. [Module 8 : Module Rapports et Statistiques (Organisateur)](#module-8--module-rapports-et-statistiques-organisateur)
9. [Module 11 : Module Paramètres Utilisateur (Thème, Mode Sombre, Multi-langue)](#module-11--module-paramètres-utilisateur-thème-mode-sombre-multi-langue)
10. [2.3 État d'Analyse et Statistiques](#23-état-danalyse-et-statistiques)
    - [2.3.1 État numéro 1 : Statistiques Administrateur](#231-état-numéro-1--statistiques-administrateur)
    - [2.3.2 État numéro 2 : Statistiques Organisateur par Événement](#232-état-numéro-2--statistiques-organisateur-par-événement)
    - [2.3.3 Statistique numéro 1 : Statistiques de Ventes de Billets](#233-statistique-numéro-1--statistiques-de-ventes-de-billets)

---

## Module 1 : Gestion des Utilisateurs (Admin)

### Description des fonctionnalités du module

Le module de gestion des utilisateurs permet aux administrateurs de gérer l'ensemble des comptes utilisateurs de la plateforme. Il offre une interface complète pour la recherche, la validation, la modification des rôles, l'activation/désactivation des comptes, et le suivi de l'historique des actions (audit).

**Fonctionnalités principales :**
- Liste des utilisateurs avec recherche multi-critères (nom, email, rôle, statut)
- Filtres avancés (rôle, statut, date de création)
- Pagination (5 utilisateurs par page)
- Détails complets d'un utilisateur (informations, audit, événements)
- Modification du rôle utilisateur (USER, ORGANIZER, ADMIN)
- Activation/Désactivation de comptes
- Suppression de comptes (avec protection CSRF)
- Validation des comptes organisateurs (approbation/rejet)
- Historique d'audit complet de toutes les actions
- Autocomplete pour recherche rapide (JSON, minimum 2 caractères)
- Statistiques globales (total utilisateurs, par rôle, par statut)

### Pourquoi a-t-on développé ce module pour l'entreprise

Ce module a été développé pour répondre aux besoins critiques de gestion et de sécurité de la plateforme :

1. **Sécurité et contrôle** : Permet aux administrateurs de contrôler qui peut accéder à la plateforme et avec quels privilèges
2. **Validation des organisateurs** : Système de validation manuelle pour s'assurer que seuls des organisateurs légitimes peuvent créer des événements
3. **Traçabilité** : Journalisation complète de toutes les actions pour répondre aux exigences de conformité et de sécurité
4. **Gestion efficace** : Interface centralisée pour gérer tous les utilisateurs sans avoir à accéder directement à la base de données
5. **Support client** : Permet au support de résoudre rapidement les problèmes liés aux comptes utilisateurs

### Scénario d'utilisation clé

**Quel scénario ?**
Un nouvel organisateur s'inscrit sur la plateforme et soumet son profil pour validation. L'administrateur doit examiner les informations, vérifier la légitimité, puis approuver ou rejeter la demande. Par ailleurs, l'administrateur peut modifier les rôles des utilisateurs pour gérer les permissions.

**Dessin écran :**
- **Liste validation** : `admin_validation_pending_list.png` - Liste des demandes en attente de validation
- **Détails profil** : `admin_validation_detail.png` - Détails du profil organisateur à valider avec toutes les informations
- **Approbation** : `admin_validation_approve.png` - Confirmation d'approbation avec envoi d'email automatique
- **Rejet** : `admin_validation_reject.png` - Formulaire de rejet avec commentaire obligatoire
- **Liste utilisateurs** : `admin_users_list.png` - Liste des utilisateurs avec actions disponibles et filtres
- **Modification rôle** : `admin_users_change_role.png` - Formulaire de sélection du nouveau rôle avec protection CSRF

**Importance :**
Critique - Ce processus garantit la qualité et la légitimité des organisateurs sur la plateforme, protégeant ainsi les utilisateurs finaux contre les événements frauduleux ou de mauvaise qualité. La gestion des rôles permet une flexibilité dans l'évolution des comptes utilisateurs.

**Comment ?**
1. **Validation organisateur** :
   - L'organisateur crée son compte avec le statut `pending`
   - L'administrateur accède à `/admin/validation/pending` pour voir les demandes
   - L'admin examine les détails du profil (nom, téléphone, type d'organisation)
   - L'admin approuve via `/admin/validation/{id}/approve` (POST) → statut passe à `active` + email automatique envoyé
   - Ou l'admin rejette via `/admin/validation/{id}/reject` (POST) avec commentaire → statut passe à `rejected` + email avec raison
   - Toutes les actions sont journalisées dans l'audit log
2. **Modification de rôle** :
   - L'admin accède à `/admin/users` et recherche l'utilisateur
   - Clic sur l'utilisateur pour voir les détails (`/admin/users/{id}`)
   - Clic sur "Modifier le rôle" → formulaire avec sélection (USER, ORGANIZER, ADMIN)
   - Soumission du formulaire (POST `/admin/users/{id}/change-role`) avec protection CSRF
   - Le système met à jour le rôle dans la base de données
   - Un email de notification est envoyé à l'utilisateur
   - L'action est journalisée dans l'audit log

**Diagramme de séquence :**
```
Organisateur → Inscription → Statut "pending"
    ↓
Admin → Consultation liste validation
    ↓
Admin → Examen détails profil
    ↓
Admin → Décision (Approuver/Rejeter)
    ↓
Système → Mise à jour statut
    ↓
Système → Envoi email notification
    ↓
Système → Journalisation audit log
    ↓
(OU)
Admin → Consultation liste utilisateurs
    ↓
Admin → Sélection utilisateur
    ↓
Admin → Modification rôle
    ↓
Système → Mise à jour rôle + notification + audit
```

---

## Module 2 : Module Facturation (Admin)

### Description des fonctionnalités du module

Le module de facturation gère l'ensemble des factures générées par la plateforme, incluant les factures d'abonnement mensuelles des organisateurs et les factures de billets. Il permet le suivi, la consultation, le filtrage et l'export des factures.

**Fonctionnalités principales :**
- Liste complète des factures (abonnements + billets) avec pagination (7 par page)
- Filtres avancés (statut, recherche texte, mois, année)
- Statistiques globales (total factures, montant total, par statut)
- Détails d'une facture (lignes, HT, TVA, TTC)
- Export PDF des factures
- Envoi automatique d'emails après paiement
- Génération automatique des factures mensuelles (CRON)
- Marquage automatique des factures en retard
- Mise en pause automatique des abonnements non payés

### Pourquoi a-t-on développé ce module pour l'entreprise

Ce module est essentiel pour la viabilité économique de la plateforme :

1. **Monétisation** : Permet de facturer les organisateurs pour l'utilisation de la plateforme via des abonnements mensuels
2. **Traçabilité financière** : Suivi complet de tous les revenus et factures pour la comptabilité
3. **Automatisation** : Génération et suivi automatiques réduisant le travail manuel
4. **Conformité fiscale** : Calcul automatique de la TVA (20%) et génération de factures conformes
5. **Gestion des impayés** : Système automatique de détection et gestion des factures en retard

### Scénario d'utilisation clé

**Quel scénario ?**
L'administrateur consulte la liste des factures d'abonnement pour suivre les paiements, identifier les factures en retard, et vérifier l'état financier de la plateforme. Parallèlement, le système génère automatiquement les factures mensuelles via CRON.

**Dessin écran :**
- **Liste factures** : `admin_billing_invoices_list.png` - Liste des factures avec filtres (statut, mois, année, recherche) et statistiques
- **Détails facture** : `admin_billing_invoice_detail.png` - Détails d'une facture avec lignes HT/TVA/TTC et informations organisateur
- **Export PDF** : `admin_billing_invoice_pdf.png` - Export PDF de la facture généré via DomPDF
- **Statistiques** : `admin_billing_stats.png` - Widgets de statistiques (total factures, montant total, par statut)
- **Génération CRON** : `cron_generate_invoices.png` - Log d'exécution de la commande CRON pour génération automatique

**Importance :**
Critique - Ce module est au cœur de la monétisation de la plateforme et permet de suivre la santé financière de l'entreprise. L'automatisation garantit que toutes les factures sont générées à temps, sans erreur humaine.

**Comment ?**
1. **Consultation factures** :
   - L'admin accède à `/admin/billing/invoices`
   - Application de filtres (statut: paid/overdue/issued, mois, année, recherche)
   - Consultation de la liste paginée avec statistiques en en-tête
   - Clic sur une facture pour voir les détails (`/admin/billing/subscription-invoice/{id}`)
   - Visualisation des lignes de facture, calculs HT/TVA/TTC
   - Export PDF si nécessaire
   - Suivi des factures en retard via filtre "overdue"
2. **Génération automatique** :
   - CRON s'exécute les 27-31 de chaque mois à 02:00 (`0 2 27-31 * *`)
   - Commande `php bin/console app:generate-monthly-invoices` est exécutée
   - Le service récupère tous les abonnements actifs
   - Pour chaque abonnement, vérification si facture existe déjà (protection doublons)
   - Calcul du montant selon le plan (mensuel/trimestriel/annuel) + TVA (20%)
   - Création de la facture avec statut `draft` puis `issued`
   - Si abonnement en pause, création facture à 0 Ar avec statut `suspendue`

**Diagramme de séquence :**
```
Admin → Accès page facturation
    ↓
Admin → Application filtres
    ↓
Système → Requête BDD + calcul stats
    ↓
Admin → Consultation détails + export PDF
    ↓
(OU)
CRON → Exécution commande (27-31 du mois)
    ↓
Service → Récupération abonnements actifs
    ↓
Service → Calcul montant + TVA + création factures
    ↓
Système → Journalisation
```

---

---

## Module 4 : Gestion des Événements (Organisateur)

### Description des fonctionnalités du module

Le module de gestion des événements permet aux organisateurs de créer, modifier, publier et gérer leurs événements avec une interface complète intégrant la gestion des médias (images, vidéos) et des lieux (venues). Ce module centralise toutes les fonctionnalités nécessaires à la création d'événements attractifs et professionnels, incluant l'upload et l'optimisation automatique des médias via Cloudinary, la gestion des lieux réutilisables avec leurs espaces et capacités, la configuration des billets, méthodes de paiement, langues et accessibilités. Il offre également des fonctionnalités avancées comme la duplication d'événements, l'export PDF/CSV, les statistiques par événement et la vue calendrier.

### Pourquoi a-t-on développé ce module pour l'entreprise

Ce module est le cœur fonctionnel de la plateforme car il permet aux organisateurs de créer et gérer leurs événements facilement, génère des ventes de billets (source de revenus), offre une interface intuitive pour les organisateurs non techniques, fournit une configuration avancée pour répondre à tous types d'événements, et améliore l'efficacité grâce à la duplication et aux templates. L'intégration des médias et lieux directement dans le processus de création d'événement simplifie le workflow et garantit une présentation visuelle de qualité.

### Scénario d'utilisation clé

**Quel scénario ?**
Un organisateur souhaite créer un nouvel événement (concert, conférence, etc.). Il remplit le formulaire de création avec toutes les informations nécessaires, sélectionne ou crée un lieu, upload des médias (images, vidéos), configure les billets, et publie l'événement.

**Dessin écran :**
- **Étape 1 - Informations de base** : `organisateur_events_create_step1.png` - Formulaire avec titre, description, dates, sélection de lieu existant ou création nouveau lieu
- **Étape 2 - Configuration billets** : `organisateur_events_create_step2.png` - Configuration des types de billets, catégories, segments, prix et quantités
- **Étape 3 - Upload médias** : `organisateur_events_create_step3.png` - Interface d'upload avec drag & drop pour images principales et galerie (max 5 images supplémentaires), optimisation automatique via Cloudinary
- **Étape 4 - Configuration finale** : `organisateur_events_create_step4.png` - Méthodes de paiement, langues supportées, accessibilités
- **Confirmation** : `organisateur_events_create_success.png` - Confirmation de création avec lien vers l'événement et aperçu des médias uploadés

**Importance :**
Critique - C'est la fonctionnalité principale qui permet aux organisateurs de proposer leurs événements sur la plateforme avec une présentation visuelle professionnelle.

**Comment ?**
1. L'organisateur accède à `/organisateur/events` et clique sur "Créer un événement"
2. **Étape 1 - Informations de base** : Remplissage du titre, description, dates, sélection d'un lieu existant dans la liste déroulante ou création d'un nouveau lieu avec adresse, capacité et espaces
3. **Étape 2 - Configuration billets** : Définition des types de billets (catégories, segments, prix, quantités disponibles)
4. **Étape 3 - Upload médias** : Upload de l'image principale (obligatoire) et jusqu'à 5 images supplémentaires pour la galerie, avec drag & drop. Les images sont automatiquement optimisées et stockées sur Cloudinary CDN
5. **Étape 4 - Configuration finale** : Sélection des méthodes de paiement acceptées, langues supportées (FR, EN, MG), et types d'accessibilité
6. Validation du formulaire avec vérifications (dates cohérentes, prix valides, lieu valide, etc.)
7. Création de l'événement en base de données avec statut "draft"
8. Génération automatique d'un slug unique pour l'URL
9. L'organisateur peut publier l'événement (statut "published") ou le garder en brouillon

**Diagramme de séquence :**
```
Organisateur → Accès création événement
    ↓
Organisateur → Remplissage informations de base + sélection/création lieu
    ↓
Système → Validation données + création lieu si nouveau
    ↓
Organisateur → Configuration billets
    ↓
Organisateur → Upload médias (image principale + galerie)
    ↓
Système → Upload vers Cloudinary
    ↓
Cloudinary → Optimisation automatique + génération thumbnails
    ↓
Système → Configuration méthodes paiement, langues, accessibilités
    ↓
Système → Validation complète
    ↓
Système → Création événement en BDD avec statut "draft"
    ↓
Système → Génération slug unique
    ↓
Organisateur → Publication (optionnel) → statut "published"
```

---

## Module 5 : Gestion des Billets/Tickets (Organisateur)

### Description des fonctionnalités du module

Le module de gestion des billets permet aux organisateurs de suivre les ventes de billets, consulter l'historique des transactions, et gérer l'inventaire des billets par catégorie et segment.

**Fonctionnalités principales :**
- Historique des ventes avec pagination (10 par page)
- Filtres avancés (événement, statut, catégorie, segment)
- Statistiques globales (total vendus, revenus, panier moyen)
- Détails de chaque vente (acheteur, quantité, prix, date)
- Vue des billets disponibles par événement
- Gestion de l'inventaire (quantités par catégorie/segment)
- Export des données de ventes

### Pourquoi a-t-on développé ce module pour l'entreprise

Ce module est essentiel pour la gestion opérationnelle :

1. **Suivi des ventes** : Permet aux organisateurs de suivre en temps réel leurs ventes de billets
2. **Gestion de l'inventaire** : Contrôle des quantités disponibles par catégorie
3. **Analyse des performances** : Statistiques pour comprendre quels billets se vendent le mieux
4. **Transparence** : Historique complet pour la comptabilité et la traçabilité
5. **Décisions stratégiques** : Données pour ajuster les prix et quantités

### Scénario d'utilisation clé

#### Scénario 1 : Consultation de l'historique des ventes

**Quel scénario ?**
L'organisateur consulte l'historique des ventes de billets pour un événement spécifique ou tous ses événements, avec filtres et statistiques.

**Dessin écran :**
- `organisateur_tickets_history.png` : Liste des ventes avec filtres
- `organisateur_tickets_stats.png` : Widgets de statistiques (total vendus, revenus, panier moyen)
- `organisateur_tickets_filters.png` : Interface de filtrage (événement, statut, catégorie, segment)
- `organisateur_tickets_detail.png` : Détails d'une vente spécifique

**Importance :**
Élevée - Permet à l'organisateur de suivre ses revenus et de prendre des décisions basées sur les données de ventes.

**Comment ?**
1. Accès à `/organisateur/tickets`
2. Sélection d'un événement spécifique (optionnel) via filtre
3. Application de filtres supplémentaires (statut, catégorie, segment)
4. Consultation de la liste paginée des ventes (10 par page)
5. Visualisation des statistiques en en-tête :
   - Total billets vendus
   - Revenus totaux
   - Panier moyen
6. Pour chaque vente : détails (acheteur, quantité, prix unitaire, total, date)
7. Export possible des données (CSV/PDF)

**Diagramme de séquence :**
```
Organisateur → Accès historique ventes
    ↓
Organisateur → Sélection événement (optionnel)
    ↓
Organisateur → Application filtres
    ↓
Système → Requête BDD via vue v_ticket_sales_history
    ↓
Service → Calcul statistiques globales
    ↓
Service → Pagination résultats
    ↓
Système → Retour liste paginée + stats
    ↓
Organisateur → Consultation données
    ↓
Organisateur → Export (optionnel)
```

---

## Module 6 : Module Paiements et Abonnements (Organisateur)

### Description des fonctionnalités du module

Le module de paiement et abonnement permet aux organisateurs de souscrire à un plan d'abonnement, effectuer les paiements via Mobile Money (Mvola), et gérer leur abonnement.

**Fonctionnalités principales :**
- Affichage des 9 plans d'abonnement disponibles (Basic/Pro/Enterprise × Mensuel/Trimestriel/Annuel)
- Comparaison des plans avec fonctionnalités
- Souscription à un plan
- Paiement via Mvola (Mobile Money)
- Gestion de l'abonnement actif
- Historique des paiements
- Mise à jour des informations de paiement
- Gestion de la pause/reprise d'abonnement

### Pourquoi a-t-on développé ce module pour l'entreprise

Ce module est la source de revenus récurrents de la plateforme :

1. **Monétisation** : Permet de facturer les organisateurs pour l'utilisation de la plateforme
2. **Paiement local** : Intégration Mvola pour faciliter les paiements à Madagascar
3. **Flexibilité** : 9 plans différents pour s'adapter à tous les budgets
4. **Automatisation** : Génération automatique des factures mensuelles
5. **Gestion simplifiée** : Interface intuitive pour les organisateurs

### Scénario d'utilisation clé

#### Scénario 1 : Souscription à un plan d'abonnement

**Quel scénario ?**
Un nouvel organisateur ou un organisateur existant souhaite souscrire ou changer de plan d'abonnement. Il consulte les plans disponibles, choisit celui qui lui convient, et effectue le paiement via Mvola.

**Dessin écran :**
- `organisateur_abonnement_plans.png` : Affichage des 9 plans avec comparaison
- `organisateur_abonnement_selection.png` : Sélection d'un plan avec détails (prix, période, fonctionnalités)
- `organisateur_abonnement_paiement_mvola.png` : Interface de paiement Mvola avec numéro masqué
- `organisateur_abonnement_confirmation.png` : Confirmation de souscription avec détails

**Importance :**
Critique - C'est le processus de monétisation principal de la plateforme.

**Comment ?**
1. Accès à `/organisateur/paiement-abonnement`
2. Consultation des 3 niveaux (Basic, Pro, Enterprise) avec 3 périodes chacune
3. Comparaison des fonctionnalités et prix
4. Sélection d'un plan (ex: Pro Mensuel à 350 000 Ar)
5. Vérification du numéro Mvola (masqué pour sécurité)
6. Initiation du paiement via API Mvola
7. L'organisateur confirme le paiement sur son téléphone
8. Vérification du paiement par le système
9. Activation de l'abonnement avec date de début
10. Génération automatique de la première facture
11. Envoi d'email de confirmation

**Diagramme de séquence :**
```
Organisateur → Accès page abonnements
    ↓
Système → Récupération plans disponibles
    ↓
Organisateur → Consultation plans + comparaison
    ↓
Organisateur → Sélection plan
    ↓
Système → Vérification numéro Mvola
    ↓
Organisateur → Confirmation paiement
    ↓
Système → Appel API Mvola
    ↓
Mvola → Traitement paiement
    ↓
Mvola → Retour statut paiement
    ↓
Système → Vérification paiement réussi
    ↓
Système → Création abonnement actif
    ↓
Système → Génération facture
    ↓
Système → Envoi email confirmation
```

---

## Module 7 : Module Promotions et Codes Promo (Organisateur)

### Description des fonctionnalités du module

Le module de promotions permet aux organisateurs de créer et gérer des codes promotionnels pour leurs événements, avec configuration de réductions, dates de validité et limites d'utilisation.

**Fonctionnalités principales :**
- Liste des codes promo avec pagination (4 par page)
- Création de code promo (pourcentage ou montant fixe)
- Modification de code promo
- Suppression de code promo
- Filtres par dates (début/fin)
- Statistiques (codes actifs, expirant bientôt)
- Application des codes aux événements
- Suivi de l'utilisation des codes

### Pourquoi a-t-on développé ce module pour l'entreprise

Ce module améliore l'expérience utilisateur et augmente les ventes :

1. **Stimulation des ventes** : Permet aux organisateurs d'offrir des réductions pour booster les ventes
2. **Flexibilité marketing** : Outil puissant pour les campagnes promotionnelles
3. **Fidélisation** : Codes promo pour récompenser les clients fidèles
4. **Gestion simple** : Interface intuitive pour créer et gérer les promotions
5. **Suivi** : Statistiques pour mesurer l'efficacité des promotions

### Scénario d'utilisation clé

**Quel scénario ?**
Un organisateur souhaite lancer une promotion pour un événement. Il crée un code promo avec une réduction (ex: 20% ou 10 000 Ar), définit les dates de validité et l'applique à un ou plusieurs événements.

**Dessin écran :**
- **Liste codes promo** : `organisateur_promotions_list.png` - Liste des codes promo existants avec pagination et filtres par dates
- **Création code** : `organisateur_promotions_create.png` - Formulaire de création de code promo avec champs (code, type réduction, valeur, dates, limite)
- **Application événements** : `organisateur_promotions_apply.png` - Interface pour appliquer le code à un ou plusieurs événements
- **Statistiques** : `organisateur_promotions_stats.png` - Statistiques affichant codes actifs, expirant bientôt, et taux d'utilisation

**Importance :**
Élevée - Outil marketing essentiel pour augmenter les ventes et attirer de nouveaux clients.

**Comment ?**
1. Accès à `/organisateur/promotions`
2. Clic sur "Créer un code promo"
3. Remplissage du formulaire :
   - Code (ex: "ETE2024")
   - Type de réduction (pourcentage ou montant fixe)
   - Valeur (ex: 20% ou 10 000 Ar)
   - Date de début
   - Date de fin
   - Limite d'utilisation (optionnel)
4. Validation et création du code
5. Application du code à un ou plusieurs événements
6. Le code devient actif et peut être utilisé par les clients
7. Suivi de l'utilisation dans les statistiques

**Diagramme de séquence :**
```
Organisateur → Accès page promotions
    ↓
Organisateur → Clic "Créer code promo"
    ↓
Organisateur → Remplissage formulaire
    ↓
Système → Validation données
    ↓
Système → Vérification unicité code
    ↓
Système → Création code promo en BDD
    ↓
Organisateur → Application à événements
    ↓
Système → Liaison code ↔ événements
    ↓
Système → Code actif → utilisable par clients
    ↓
Organisateur → Suivi utilisation via stats
```

---

## Module 8 : Module Rapports et Statistiques (Organisateur)

### Description des fonctionnalités du module

Le module de rapports fournit aux organisateurs des analyses détaillées sur leurs événements, ventes, et performances avec graphiques interactifs et exports CSV/PDF. Il permet de suivre l'évolution des ventes dans le temps, analyser la répartition par catégorie de billets, comparer les performances entre événements, et exporter les données pour analyse externe.

### Pourquoi a-t-on développé ce module pour l'entreprise

Ce module aide les organisateurs à optimiser leurs événements en fournissant des données pour comprendre ce qui fonctionne, identifier les meilleurs moments, prix et catégories, générer des rapports pour investisseurs ou partenaires, et améliorer continuellement leurs performances. Il différencie la plateforme avec des analytics avancées.

### Scénario d'utilisation clé

**Quel scénario ?**
L'organisateur consulte les rapports de ventes pour analyser les performances de ses événements, identifier les tendances et exporter les données pour analyse externe.

**Dessin écran :**
- **Dashboard principal** : `organisateur_reports_dashboard.png` - Vue d'ensemble avec graphiques et statistiques globales
- **Graphique ventes** : `organisateur_reports_sales_chart.png` - Courbe d'évolution des ventes dans le temps avec filtres par période
- **Export données** : `organisateur_reports_export.png` - Options d'export CSV/PDF avec sélection de critères

**Importance :**
Élevée - Permet aux organisateurs de prendre des décisions éclairées pour leurs futurs événements.

**Comment ?**
1. Accès à `/organisateur/reports`
2. Application de filtres (dates, événement spécifique)
3. Consultation des graphiques :
   - Évolution des ventes dans le temps
   - Répartition par catégorie de billets
   - Comparaison entre événements
4. Analyse des statistiques clés (revenus, nombre de billets, panier moyen)
5. Export des données en CSV ou PDF si nécessaire

**Diagramme de séquence :**
```
Organisateur → Accès page rapports
    ↓
Organisateur → Application filtres dates
    ↓
Service → Récupération données ventes
    ↓
Service → Calcul statistiques
    ↓
Service → Préparation données graphiques
    ↓
Système → Génération graphiques Chart.js
    ↓
Organisateur → Consultation dashboard
    ↓
Organisateur → Export CSV/PDF (optionnel)
    ↓
Système → Génération fichier export
```

---

## 2.3 État d'Analyse et Statistiques

### 2.3.1 État numéro 1 : Statistiques Administrateur

### Description des fonctionnalités du module

Le module de statistiques administrateur fournit une vue d'ensemble complète des performances de la plateforme avec 4 widgets de statistiques clés (organisateurs actifs, nouveaux organisateurs, abonnement le plus utilisé, prévision CA) et 4 graphiques Chart.js interactifs (courbe nouveaux organisateurs sur 6 mois, histogramme répartition abonnements, courbe prévision CA, top 10 payeurs). Les filtres de dates permettent d'analyser les données sur différentes périodes.

### Pourquoi a-t-on développé ce module pour l'entreprise

Ce module permet la prise de décision stratégique basée sur des données en visualisant l'évolution du nombre d'organisateurs et des revenus, identifiant les plans d'abonnement les plus populaires, estimant le chiffre d'affaires pour planification, identifiant les meilleurs clients pour stratégie de fidélisation, et fournissant une vue d'ensemble rapide pour la direction.

### Scénario d'utilisation clé

**Quel scénario ?**
L'administrateur ou la direction consulte le tableau de bord statistiques pour évaluer les performances de la plateforme, identifier les tendances et prendre des décisions stratégiques.

**Dessin écran :**
- **Page statistiques** : `admin_statistics_dashboard.png` - Vue complète avec widgets et graphiques
- **Widgets statistiques** : `admin_statistics_widgets.png` - Les 4 widgets de statistiques clés en en-tête
- **Graphiques interactifs** : `admin_statistics_charts.png` - Les 4 graphiques Chart.js avec interactions
- **Filtres dates** : `admin_statistics_filters.png` - Interface de filtrage par dates (début/fin)

**Importance :**
Élevée - Fournit les données nécessaires pour comprendre la santé de l'entreprise et guider les décisions stratégiques.

**Comment ?**
1. Accès à `/admin/reports/statistiques` (redirection automatique après login admin)
2. Consultation des 4 widgets en haut de page :
   - Organisateurs actifs (ce mois)
   - Nouveaux organisateurs (ce mois)
   - Abonnement le plus utilisé
   - Prévision du chiffre d'affaires
3. Consultation des 4 graphiques :
   - Courbe nouveaux organisateurs (6 derniers mois)
   - Histogramme répartition abonnements (Basic/Pro/Enterprise)
   - Courbe prévision CA (6 mois)
   - Top 10 Payeurs (barres horizontales)
4. Application de filtres de dates si nécessaire
5. Tous les calculs sont effectués en temps réel via le repository SQL

**Diagramme de séquence :**
```
Admin → Accès page statistiques
    ↓
Système → Récupération paramètres filtres dates
    ↓
Service → Appel repository pour widgets
    ↓
Repository → Requêtes SQL (organisateurs actifs, nouveaux, etc.)
    ↓
Service → Appel repository pour graphiques
    ↓
Repository → Requêtes SQL (données 6 mois, top payeurs, etc.)
    ↓
Service → Formatage données (montants en Ar)
    ↓
Système → Passage données au template
    ↓
Template → Rendu widgets + graphiques Chart.js
    ↓
Admin → Visualisation interactive
```

---

### 2.3.2 État numéro 2 : Statistiques Organisateur par Événement

### Description des fonctionnalités du module

Le module de statistiques par événement permet aux organisateurs de suivre les performances de chaque événement individuellement avec des métriques détaillées : nombre de vues, favoris ajoutés, participants inscrits, billets vendus, revenus générés, taux de conversion, et comparaison avec d'autres événements. Ces statistiques sont affichées directement dans la liste des événements et accessibles en détail pour chaque événement.

### Pourquoi a-t-on développé ce module pour l'entreprise

Ce module permet aux organisateurs de comprendre quels événements fonctionnent le mieux, identifier les facteurs de succès, optimiser leurs stratégies marketing, prendre des décisions basées sur des données, et améliorer continuellement leurs événements futurs.

### Scénario d'utilisation clé

**Quel scénario ?**
L'organisateur consulte les statistiques de ses événements dans la liste principale pour identifier rapidement les événements les plus performants et accéder aux détails complets.

**Dessin écran :**
- **Liste avec stats** : `organisateur_events_list_stats.png` - Liste des événements avec statistiques en temps réel (vues, favoris, participants) affichées pour chaque événement
- **Détails statistiques** : `organisateur_events_stats_detail.png` - Page de détails avec graphiques de performance, évolution des ventes, répartition par catégorie de billets
- **Comparaison événements** : `organisateur_events_stats_comparison.png` - Vue comparative entre plusieurs événements avec indicateurs clés

**Importance :**
Élevée - Permet aux organisateurs d'optimiser leurs événements en comprenant ce qui fonctionne.

**Comment ?**
1. Accès à `/organisateur/events`
2. Consultation de la liste avec statistiques affichées pour chaque événement :
   - Nombre de vues
   - Nombre de favoris
   - Nombre de participants
   - Billets vendus
3. Clic sur un événement pour voir les statistiques détaillées
4. Analyse des graphiques de performance et tendances
5. Comparaison avec d'autres événements si nécessaire

**Diagramme de séquence :**
```
Organisateur → Accès liste événements
    ↓
Système → Récupération événements + calcul stats
    ↓
Service → Calcul statistiques par événement (vues, favoris, participants)
    ↓
Système → Affichage liste avec stats en temps réel
    ↓
Organisateur → Clic sur événement pour détails
    ↓
Système → Récupération stats détaillées
    ↓
Service → Génération graphiques de performance
    ↓
Organisateur → Consultation et analyse
```

---

### 2.3.3 Statistique numéro 1 : Statistiques de Ventes de Billets

### Description des fonctionnalités du module

Le module de statistiques de ventes de billets fournit aux organisateurs une analyse complète de leurs ventes avec des métriques clés : total billets vendus, revenus totaux, panier moyen, répartition par catégorie et segment de billets, évolution des ventes dans le temps, et identification des meilleures périodes de vente. Ces statistiques sont accessibles depuis la page de gestion des tickets avec filtres par événement, statut, catégorie et segment.

### Pourquoi a-t-on développé ce module pour l'entreprise

Ce module permet aux organisateurs de suivre leurs revenus en temps réel, comprendre quels types de billets se vendent le mieux, identifier les meilleures périodes pour lancer des promotions, optimiser les prix et quantités, et prendre des décisions stratégiques basées sur des données de ventes réelles.

### Scénario d'utilisation clé

**Quel scénario ?**
L'organisateur consulte l'historique des ventes de billets pour analyser les performances, identifier les tendances et prendre des décisions pour optimiser ses futurs événements.

**Dessin écran :**
- **Historique ventes** : `organisateur_tickets_history.png` - Liste des ventes avec filtres (événement, statut, catégorie, segment) et pagination
- **Widgets statistiques** : `organisateur_tickets_stats.png` - Widgets affichant total vendus, revenus totaux, panier moyen
- **Filtres avancés** : `organisateur_tickets_filters.png` - Interface de filtrage avec sélection événement, statut, catégorie, segment
- **Détails vente** : `organisateur_tickets_detail.png` - Détails d'une vente spécifique avec informations acheteur, quantité, prix

**Importance :**
Élevée - Permet à l'organisateur de suivre ses revenus et de prendre des décisions basées sur les données de ventes.

**Comment ?**
1. Accès à `/organisateur/tickets`
2. Sélection d'un événement spécifique (optionnel) via filtre
3. Application de filtres supplémentaires (statut, catégorie, segment)
4. Consultation de la liste paginée des ventes (10 par page)
5. Visualisation des statistiques en en-tête :
   - Total billets vendus
   - Revenus totaux
   - Panier moyen
6. Pour chaque vente : détails (acheteur, quantité, prix unitaire, total, date)
7. Export possible des données (CSV/PDF)

**Diagramme de séquence :**
```
Organisateur → Accès historique ventes
    ↓
Organisateur → Sélection événement (optionnel)
    ↓
Organisateur → Application filtres
    ↓
Système → Requête BDD via vue v_ticket_sales_history
    ↓
Service → Calcul statistiques globales
    ↓
Service → Pagination résultats
    ↓
Système → Retour liste paginée + stats
    ↓
Organisateur → Consultation données
    ↓
Organisateur → Export (optionnel)
```

---

## Module 11 : Module Paramètres Utilisateur (Thème, Mode Sombre, Multi-langue)

### Description des fonctionnalités du module

Le module de paramètres utilisateur permet à tous les utilisateurs (admin, organisateur, utilisateur) de personnaliser leur expérience sur la plateforme en configurant le thème, le mode sombre, la couleur d'accentuation et la langue de l'interface.

**Fonctionnalités principales :**
- **Gestion du thème** : Basculer entre mode clair et mode sombre
- **Couleurs d'accentuation** : Choix parmi 5 couleurs (bleu, vert, violet, rouge, orange)
- **Multi-langue** : Support de 3 langues (Français, English, Malagasy)
- **Persistance** : Sauvegarde des préférences dans localStorage
- **Application immédiate** : Changements appliqués sans rechargement de page
- **Pré-chargement** : Évite le flash de contenu lors du chargement
- **API de validation** : Endpoint pour valider les préférences utilisateur
- **Traductions dynamiques** : Système de traduction basé sur attributs `data-i18n`
- **Synchronisation** : Synchronisation automatique entre header et page paramètres

### Pourquoi a-t-on développé ce module pour l'entreprise

Ce module améliore significativement l'expérience utilisateur :

1. **Accessibilité** : Mode sombre pour réduire la fatigue oculaire, surtout en usage prolongé
2. **Personnalisation** : Permet aux utilisateurs d'adapter l'interface à leurs préférences
3. **Internationalisation** : Support multi-langue essentiel pour Madagascar (FR, EN, MG)
4. **Engagement** : Interface personnalisable augmente l'engagement des utilisateurs
5. **Professionnalisme** : Démontre l'attention portée aux détails et à l'expérience utilisateur
6. **Flexibilité** : Système extensible pour ajouter facilement de nouvelles langues ou thèmes

### Scénario d'utilisation clé

**Quel scénario ?**
Un utilisateur souhaite personnaliser son expérience sur la plateforme en configurant le thème (mode clair/sombre), la couleur d'accentuation, et la langue de l'interface. Il peut effectuer ces changements depuis la page paramètres ou utiliser le bouton de bascule rapide dans le header pour le thème.

**Dessin écran :**
- **Section apparence** : `settings_appearance_section.png` - Section apparence avec mode sombre (toggle switch) et thème de couleur (5 boutons)
- **Toggle mode sombre** : `settings_dark_mode_toggle.png` - Toggle switch pour activer/désactiver le mode sombre avec feedback visuel
- **Sélecteur couleurs** : `settings_color_picker.png` - Sélecteur de couleurs d'accentuation (bleu, vert, violet, rouge, orange) avec état actif
- **Section langue** : `settings_language_section.png` - Section langue avec sélecteur déroulant (FR, EN, MG)
- **Menu langue** : `settings_language_select.png` - Menu déroulant avec options de langue et drapeaux
- **Bouton header** : `header_theme_button.png` - Bouton de bascule rapide du thème dans le header avec icône soleil/lune
- **Notification** : `settings_theme_applied.png` - Notification toast confirmant les changements appliqués

**Importance :**
Élevée - Améliore le confort d'utilisation, permet une personnalisation qui augmente l'engagement, et est essentiel pour l'adoption à Madagascar avec support multi-langue.

**Comment ?**
1. **Configuration thème et couleur** :
   - L'utilisateur accède à `/settings` → section "Apparence"
   - Activation du mode sombre via toggle → application immédiate, sauvegarde localStorage, notification
   - Sélection d'une couleur d'accentuation → application immédiate, sauvegarde localStorage, notification
   - Le thème est pré-chargé au prochain chargement (évite le flash)
2. **Changement de langue** :
   - Section "Langue et région" → sélection dans le menu déroulant (FR/EN/MG)
   - Le `LanguageManager` applique les traductions via attributs `data-i18n`
   - Mise à jour immédiate sans rechargement, sauvegarde localStorage, notification
3. **Bascule rapide thème** :
   - Clic sur le bouton dans le header → bascule automatique (light ↔ dark)
   - Application immédiate, mise à jour icône, sauvegarde

**Diagramme de séquence :**
```
Utilisateur → Accès page paramètres
    ↓
Utilisateur → Configuration thème/couleur OU langue
    ↓
ThemeManager/LanguageManager → Détection changement
    ↓
Manager → Application au DOM
    ↓
Manager → Sauvegarde localStorage
    ↓
Manager → Notification toast
    ↓
(OU)
Utilisateur → Clic bouton thème (header)
    ↓
ThemeManager → Bascule rapide + sauvegarde
```

---

## 📊 Résumé des Modules

| # | Module | Type | Priorité | Complexité |
|---|--------|------|----------|------------|
| 1 | Gestion des Utilisateurs | Admin | Critique | Élevée |
| 2 | Facturation | Admin | Critique | Élevée |
| 4 | Gestion des Événements (avec Médias et Lieux) | Organisateur | Critique | Très élevée |
| 5 | Gestion des Billets | Organisateur | Critique | Élevée |
| 6 | Paiements et Abonnements | Organisateur | Critique | Élevée |
| 7 | Promotions et Codes Promo | Organisateur | Élevée | Moyenne |
| 8 | Rapports et Statistiques | Organisateur | Élevée | Moyenne |
| 11 | Paramètres Utilisateur (Thème/Langue) | Tous | Élevée | Moyenne |
| 2.3.1 | Statistiques Administrateur | Admin | Élevée | Moyenne |
| 2.3.2 | Statistiques Organisateur par Événement | Organisateur | Élevée | Moyenne |
| 2.3.3 | Statistiques de Ventes de Billets | Organisateur | Élevée | Moyenne |

---

**Documentation version** : 1.0.0  
**Dernière mise à jour** : 2025  
**Auteur** : Aiolia Event Development Team

