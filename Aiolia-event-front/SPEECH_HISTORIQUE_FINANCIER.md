# 🎤 Speech - Page Historique Financier Détaillé

## 📋 Introduction

Bonjour, je vais vous présenter la page **Historique financier détaillé** de la plateforme Aiolia Event. Cette page permet aux utilisateurs de suivre et d'analyser leurs dépenses liées aux événements, avec des outils avancés de visualisation et de filtrage.

---

## 🎯 Vue d'ensemble

La page **Historique financier détaillé** est accessible depuis le profil utilisateur via la route `/profile/financial-history`. Elle offre une vue complète et interactive de l'historique financier de l'utilisateur, avec des statistiques en temps réel, des filtres avancés et des comparaisons temporelles.

---

## 📊 Section 1 : Cartes de Statistiques

### Description générale

En haut de la page, nous avons **quatre cartes de statistiques principales** qui donnent un aperçu immédiat de la situation financière de l'utilisateur. Ces cartes sont conçues avec un design moderne et des animations au survol.

### Les quatre cartes

#### 1. **Carte "Total dépenses"** (Bleu foncé)
- **Icône** : Graphique linéaire (`fa-chart-line`)
- **Contenu** :
  - Affiche le total des dépenses selon la période sélectionnée (année, mois, ou tout)
  - Montant formaté en MGA (Ariary malgache)
  - Indicateur de variation par rapport à l'année précédente (si période = année)
  - Badge avec flèche haut/bas et pourcentage de variation
- **Couleur** : Bleu foncé (#1F2D3D)
- **Animation** : Au survol, l'icône change de couleur et devient blanche sur fond coloré

#### 2. **Carte "Dépenses ce mois"** (Bleu)
- **Icône** : Calendrier (`fa-calendar-alt`)
- **Contenu** :
  - Montant total dépensé dans le mois en cours
  - Date du mois actuel (ex: "Janvier 2025")
- **Couleur** : Bleu (#4A90E2)
- **Utilité** : Permet de voir rapidement les dépenses du mois courant

#### 3. **Carte "Nombre de commandes"** (Vert)
- **Icône** : Panier d'achat (`fa-shopping-cart`)
- **Contenu** :
  - Nombre total de commandes passées
  - Panier moyen affiché en petit texte (montant moyen par commande)
- **Couleur** : Vert (#50C878)
- **Utilité** : Comprendre la fréquence d'achat et la valeur moyenne des commandes

#### 4. **Carte "Remboursements reçus"** (Rouge)
- **Icône** : Flèche de retour (`fa-undo`)
- **Contenu** :
  - Montant total des remboursements reçus
  - Nombre d'événements concernés par les remboursements
- **Couleur** : Rouge (#E74C3C)
- **Utilité** : Suivre les remboursements liés aux annulations d'événements

### Design des cartes

- **Bordure gauche colorée** : Chaque carte a une bordure gauche de 4px dans sa couleur distinctive
- **Effet hover** : Au survol, l'icône change de couleur et devient blanche sur fond coloré
- **Responsive** : Les cartes s'adaptent automatiquement sur mobile (1 colonne) et desktop (4 colonnes)
- **Animation** : Effet "fadeInUp" avec délai progressif pour un chargement élégant

---

## 🔍 Section 2 : Filtres Avancés

### Description générale

La section **Filtres avancés** permet aux utilisateurs d'affiner leur recherche selon plusieurs critères. Elle est repliable/dépliable pour une interface épurée.

### Structure des filtres

#### Filtres de base (toujours visibles)

1. **Filtre par période**
   - Options : Année, Mois, Tout
   - Permet de sélectionner la période d'analyse
   - Soumission automatique au changement

2. **Filtre par année**
   - Liste déroulante avec les 6 dernières années
   - Année actuelle sélectionnée par défaut

3. **Filtre par mois** (visible uniquement si période = "Mois")
   - Liste des 12 mois de l'année
   - Option "Tous les mois" disponible

#### Filtres avancés (section repliable)

La section filtres avancés s'ouvre/ferme via un bouton avec icône chevron. Elle s'ouvre automatiquement si des filtres sont déjà actifs.

##### 1. **Méthode de paiement**
- **Badge MVola** : Affichage d'un badge orange indiquant "MVola - Méthode de paiement exclusive"
- **Note** : MVola est la seule méthode de paiement disponible sur la plateforme
- **Design** : Badge avec gradient orange (#F39C12 → #D35400) et icône mobile
- **Information** : "100% sécurisé" affiché dans les statistiques

##### 2. **Filtre par plage de montant**
- **Montant minimum** : Champ numérique pour définir le montant minimum (en MGA)
- **Montant maximum** : Champ numérique pour définir le montant maximum (en MGA)
- **Validation** : 
  - Valeurs positives uniquement
  - Pas de montant minimum supérieur au maximum
  - Pas de valeurs négatives
- **Placeholder** : Exemples (10000, 100000)

##### 3. **Filtre par catégorie d'événement**
- **Liste dynamique** : Récupérée depuis la base de données
- **Options** : 
  - "Toutes" (par défaut)
  - Liste des catégories disponibles (ex: Musique, Sport, Culture, etc.)
- **Mise à jour automatique** : Les nouvelles catégories apparaissent automatiquement

### Fonctionnalités des filtres

- **Conservation des filtres de base** : Les filtres avancés conservent les sélections de période, année et mois
- **Bouton "Appliquer"** : Soumet le formulaire avec tous les filtres
- **Bouton "Réinitialiser"** : Remet tous les filtres à zéro
- **Auto-expansion** : La section s'ouvre automatiquement si des filtres sont actifs
- **Animation** : Effet "slideDown" lors de l'ouverture

### Utilisation pratique

**Exemple de scénario** :
> "Je veux voir toutes mes dépenses entre 50 000 et 200 000 MGA pour les événements de catégorie 'Musique' en 2024."

L'utilisateur peut :
1. Sélectionner "Année" et "2024"
2. Ouvrir les filtres avancés
3. Saisir 50000 dans "Montant minimum"
4. Saisir 200000 dans "Montant maximum"
5. Sélectionner "Musique" dans la catégorie
6. Cliquer sur "Appliquer"

---

## 📈 Section 3 : Comparatif avec Période Précédente

### Description générale

La section **Comparaison avec la période précédente** affiche automatiquement une comparaison entre la période sélectionnée et la période précédente équivalente. Cette fonctionnalité permet de visualiser l'évolution des dépenses.

### Logique de comparaison

#### Comparaison mensuelle
- Si l'utilisateur sélectionne un mois spécifique (ex: Janvier 2025)
- La comparaison se fait avec le mois précédent (ex: Décembre 2024)
- **Métrique** : Mois actuel vs Mois précédent

#### Comparaison annuelle
- Si l'utilisateur sélectionne une année (ex: 2025)
- La comparaison se fait avec l'année précédente (ex: 2024)
- **Métrique** : Année actuelle vs Année précédente

### Indicateurs affichés

La section affiche **deux cartes de comparaison** :

#### 1. **Carte "Commandes"**
- **Valeur actuelle** : Nombre de commandes de la période sélectionnée
- **Badge de variation** :
  - **Hausse** (rouge) : Si le nombre de commandes a augmenté
  - **Baisse** (vert) : Si le nombre de commandes a diminué
- **Pourcentage de variation** : Arrondi à 1 décimale (ex: +15.3% ou -8.7%)
- **Valeur précédente** : "vs [nombre] commandes" de la période précédente

#### 2. **Carte "Dépenses"**
- **Valeur actuelle** : Montant total dépensé (formaté avec espaces, ex: "150 000 MGA")
- **Badge de variation** :
  - **Hausse** (rouge) : Si les dépenses ont augmenté
  - **Baisse** (vert) : Si les dépenses ont diminué
- **Pourcentage de variation** : Arrondi à 1 décimale
- **Valeur précédente** : "vs [montant] MGA" de la période précédente

### Design de la section

- **Fond dégradé** : Gradient violet foncé (#1F2D3D → #34495E)
- **Texte blanc** : Contraste élevé pour une bonne lisibilité
- **Cartes semi-transparentes** : Effet "glassmorphism" avec backdrop-filter blur
- **Badges colorés** :
  - **Hausse** : Fond rouge semi-transparent, texte rouge clair
  - **Baisse** : Fond vert semi-transparent, texte vert clair
- **Icônes** : Flèches haut/bas selon la tendance
- **Responsive** : Les cartes passent en 1 colonne sur mobile

### Exemple d'affichage

```
┌─────────────────────────────────────┐
│ Comparaison avec la période         │
│ précédente                           │
├─────────────────────────────────────┤
│ Commandes                            │
│ 25  [↑ +12.5%]                      │
│ vs 22 commandes                      │
├─────────────────────────────────────┤
│ Dépenses                             │
│ 450 000 MGA  [↓ -8.3%]              │
│ vs 490 000 MGA                       │
└─────────────────────────────────────┘
```

### Utilité business

Cette fonctionnalité permet à l'utilisateur de :
- **Identifier les tendances** : Comprendre si ses dépenses augmentent ou diminuent
- **Prendre des décisions** : Ajuster son budget en fonction des tendances
- **Suivre l'évolution** : Voir l'impact de ses habitudes d'achat sur le temps

---

## 🎨 Autres éléments de la page

### Section "Analyses et recommandations" (Insights)

Si des insights sont générés automatiquement, une section apparaît avec des cartes colorées :
- **Insight Success** (vert) : Informations positives (ex: remboursements reçus)
- **Insight Warning** (bleu) : Alertes ou recommandations
- **Insight Info** (gris) : Informations générales (ex: panier moyen)

### Graphique de répartition mensuelle

- **Type** : Graphique en barres (Chart.js)
- **Période** : 12 derniers mois (approche glissante)
- **Couleurs** : Dégradé de couleurs variées pour chaque barre
- **Interactivité** : Tooltip au survol avec montant formaté
- **Formatage** : Montants en k MGA ou M MGA selon la valeur

### Section "Méthode de paiement"

- **Badge MVola** : Carte avec gradient orange/jaune
- **Statistiques** :
  - Nombre de transactions
  - Badge "100% sécurisé"
- **Note informative** : Tous les paiements sont effectués via MVola

---

## 📱 Responsive Design

### Mobile (< 768px)
- Cartes de statistiques en 1 colonne
- Filtres en stack vertical
- Graphique adapté à la largeur de l'écran
- Comparaison en 1 colonne

### Tablet et Desktop
- Cartes en grid 4 colonnes
- Filtres en ligne horizontale
- Graphique pleine largeur
- Comparaison en 2 colonnes

---

## 🎯 Points clés à mentionner dans le speech

1. **Vue d'ensemble immédiate** : Les 4 cartes de statistiques donnent un aperçu rapide
2. **Filtrage puissant** : Les filtres avancés permettent des analyses très précises
3. **Comparaison temporelle** : Le comparatif avec la période précédente montre l'évolution
4. **Design moderne** : Interface élégante avec animations et effets visuels
5. **Données en temps réel** : Toutes les statistiques sont calculées depuis la base de données
6. **Export disponible** : Possibilité d'exporter les données en CSV ou PDF

---

## 💡 Exemple de speech complet

> "Bonjour, je vais vous présenter la page **Historique financier détaillé** de notre plateforme.
> 
> **En haut de la page**, vous pouvez voir **quatre cartes de statistiques** qui vous donnent un aperçu immédiat de votre situation financière :
> - La première carte affiche le **total de vos dépenses** selon la période sélectionnée, avec un indicateur de variation par rapport à l'année précédente si vous êtes en vue annuelle.
> - La deuxième carte montre vos **dépenses du mois en cours**.
> - La troisième carte indique le **nombre de commandes** que vous avez passées, ainsi que votre panier moyen.
> - La quatrième carte affiche le **total des remboursements** que vous avez reçus.
> 
> **Ensuite, nous avons une section de filtres avancés** qui vous permet d'affiner votre recherche. Vous pouvez filtrer par :
> - **Période** : Année, Mois, ou Tout
> - **Année et mois** spécifiques
> - **Plage de montant** : Définir un montant minimum et maximum
> - **Catégorie d'événement** : Filtrer par type d'événement (Musique, Sport, etc.)
> 
> La section filtres avancés est repliable pour garder une interface épurée, mais s'ouvre automatiquement si vous avez déjà des filtres actifs.
> 
> **Enfin, nous avons inclus un comparatif par rapport à la période précédente** avec des indicateurs de progression. Cette section compare automatiquement :
> - Si vous êtes en vue mensuelle, elle compare le mois sélectionné avec le mois précédent
> - Si vous êtes en vue annuelle, elle compare l'année sélectionnée avec l'année précédente
> 
> Pour chaque métrique (commandes et dépenses), vous voyez :
> - La valeur actuelle
> - Un badge coloré indiquant si c'est une hausse (rouge) ou une baisse (vert)
> - Le pourcentage de variation précis
> - La valeur de la période précédente pour référence
> 
> Cette fonctionnalité vous permet de **visualiser l'évolution de vos dépenses** et de **prendre des décisions éclairées** sur votre budget événementiel.
> 
> Toutes ces données sont calculées en temps réel depuis votre historique de transactions, et vous pouvez également exporter vos données en CSV ou PDF pour une analyse plus approfondie."

---

## 📝 Notes techniques

- **Route** : `/profile/financial-history`
- **Controller** : `ProfileController::financialHistory()`
- **Template** : `templates/profile/financial.html.twig`
- **Repository** : `OrderRepository::findFinancialHistory()`
- **Authentification** : Requise (redirection vers login si non authentifié)
- **Performance** : Requêtes optimisées avec filtres SQL dynamiques

---

**Date de création** : Décembre 2025  
**Version** : 1.0  
**Auteur** : Équipe de développement Aiolia Event
