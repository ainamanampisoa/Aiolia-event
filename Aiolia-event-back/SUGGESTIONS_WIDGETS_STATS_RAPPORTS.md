# Suggestions pour l'Interface Statistiques/Rapports Combinée

## 📊 Vue d'ensemble

Ce document propose une architecture pour combiner les interfaces **Statistiques** et **Rapports** en une seule interface unifiée, avec des widgets optimisés pour chaque contexte.

---

## 🎯 Interface Statistiques/Rapports Combinée (4 Widgets)

### Widget 1: **Revenus Fiscaux Consolidés** 📈
**Type:** Graphique en courbe (Line Chart) avec filtres avancés

**Contenu:**
- Graphique montrant l'évolution HT, TVA et TTC sur la période sélectionnée
- 3 courbes superposées (HT en bleu, TVA en orange, TTC en vert)
- Filtres: Période (mois/année), Plan d'abonnement, Organisateur spécifique
- Indicateurs clés affichés:
  - Total HT: X MGA
  - Total TVA: Y MGA  
  - Total TTC: Z MGA
  - Variation vs période précédente (%)

**Emplacement:** En haut, largeur complète (grid-column: span 2)

---

### Widget 2: **Répartition des Abonnements** 🥧
**Type:** Graphique en donut (Doughnut Chart) avec tableau détaillé

**Contenu:**
- Donut chart montrant la répartition des revenus par plan (Basic, Pro, Entreprise)
- Tableau en dessous avec:
  - Nombre d'abonnements par plan
  - Revenus par plan
  - Pourcentage de contribution
- Filtres: Période, Organisateur

**Emplacement:** En haut à droite (1 colonne)

---

### Widget 3: **Top 5 Contributeurs Fiscaux** 🏆
**Type:** Graphique en barres horizontales (Bar Chart)

**Contenu:**
- Barres horizontales montrant les 5 organisateurs ayant contribué le plus à la TVA
- Affichage du montant de TVA par organisateur
- Filtres: Période, Plan
- Lien cliquable vers le détail de chaque organisateur

**Emplacement:** En bas à gauche (1 colonne)

---

### Widget 4: **Analyse des Impayés et Retards** ⚠️
**Type:** Graphique combiné (Bar + Line) avec tableau détaillé

**Contenu:**
- Graphique en barres: Nombre de factures en retard par mois
- Graphique en ligne: Montant total en retard (tendance)
- Tableau détaillé des factures impayées:
  - Organisateur
  - N° Facture
  - Montant
  - Date d'échéance
  - Jours de retard
  - Statut (En attente / En retard)
- Filtres: Période, Plan, Organisateur, Statut

**Emplacement:** En bas à droite (1 colonne, peut s'étendre)

---

### Filtres Communs (Barre supérieure)
- **Période:** Mois/Année ou Plage de dates personnalisée
- **Plan:** Tous / Basic / Pro / Entreprise
- **Organisateur:** Tous / Sélection spécifique
- **Boutons d'action:** Rechercher, Réinitialiser, Exporter (PDF/CSV)

---

## 🏠 Dashboard Principal (4 Widgets)

### Widget 1: **Vue d'Ensemble des Organisateurs** 👥
**Type:** Widget avec mini-graphique (Sparkline)

**Contenu:**
- **Valeur principale:** Nombre d'organisateurs actifs
- **Sous-valeurs:** 
  - Nouveaux organisateurs ce mois
  - Taux d'activité global (%)
- **Mini-graphique:** Courbe des nouveaux organisateurs (7 derniers jours)
- **Filtres:** Mois/Année (déjà présent)
- **Icône:** 👥 (users)

**Emplacement:** En haut à gauche

---

### Widget 2: **Performance des Abonnements** 💎
**Type:** Widget avec graphique en barres mini

**Contenu:**
- **Valeur principale:** Plan le plus populaire
- **Sous-valeurs:**
  - Abonnements actifs
  - Nouveaux abonnements ce mois
- **Mini-graphique:** Histogramme des abonnements par plan
- **Filtres:** Mois/Année
- **Icône:** 💎 (crown)

**Emplacement:** En haut, deuxième colonne

---

### Widget 3: **Revenus et Prévision** 💰 ⭐ **NOUVEAU**
**Type:** Widget avec graphique en courbe et prévision

**Contenu:**
- **Valeur principale:** Revenus TTC du mois en cours
- **Sous-valeurs:**
  - Variation vs mois précédent (%)
  - Prévision du mois suivant (basée sur la tendance)
- **Mini-graphique:** 
  - Courbe des revenus TTC (6 derniers mois)
  - Ligne de prévision (prochaine période) en pointillés
- **Filtres:** Mois/Année
- **Icône:** 💰 (money-bill-wave)
- **Couleur:** Vert (succès)

**Calcul de prévision:**
- Moyenne mobile sur 3 mois
- Tendance linéaire simple
- Formule: `Prévision = (Moyenne 3 mois) × (1 + Tendance)`

**Emplacement:** En haut, troisième colonne ⭐ **IDÉAL POUR LA PRÉVISION**

---

### Widget 4: **Taux d'Engagement** 📊
**Type:** Widget avec graphique en courbe

**Contenu:**
- **Valeur principale:** Taux d'activité global (%)
- **Sous-valeurs:**
  - Organisateurs actifs / Total
  - Évolution sur 6 mois
- **Mini-graphique:** Courbe du taux d'activité (6 derniers mois)
- **Filtres:** Mois/Année
- **Icône:** 📊 (chart-line)

**Emplacement:** En haut à droite

---

### Graphiques Principaux du Dashboard (Section inférieure)

1. **Graphique 1:** Courbe des nouveaux organisateurs (large, 2 colonnes)
2. **Graphique 2:** Histogramme des abonnements (1 colonne)
3. **Graphique 3:** Courbe du taux d'activité (1 colonne)

---

## 📍 Où Placer la Prévision de Chiffre d'Affaires?

### ✅ **Recommandation: Widget 3 du Dashboard**

**Pourquoi:**
1. **Visibilité immédiate:** Le dashboard est la première page vue par l'admin
2. **Contexte approprié:** Les revenus sont une métrique clé du tableau de bord
3. **Synergie:** Combine revenus actuels + prévision dans un seul widget
4. **Impact visuel:** Facile à repérer et comprendre rapidement

**Alternative:** Si vous préférez une section dédiée, créer un **Widget 5** séparé sur le dashboard avec:
- Prévision détaillée (3 prochains mois)
- Scénarios (optimiste, réaliste, pessimiste)
- Graphique de prévision avec intervalles de confiance

---

## 🎨 Structure de l'Interface Combinée

```
┌─────────────────────────────────────────────────────────────┐
│  STATISTIQUES & RAPPORTS                                     │
│  [Filtres: Période | Plan | Organisateur] [Rechercher] [Export] │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  ┌─────────────────────────────────────┐  ┌──────────────┐ │
│  │ Widget 1: Revenus Fiscaux (2 cols)   │  │ Widget 2:    │ │
│  │ [Graphique HT/TVA/TTC]               │  │ Abonnements  │ │
│  │                                      │  │ [Donut]      │ │
│  └─────────────────────────────────────┘  └──────────────┘ │
│                                                               │
│  ┌──────────────────┐  ┌──────────────────────────────────┐ │
│  │ Widget 3:        │  │ Widget 4: Impayés (étendable)    │ │
│  │ Top Contributeurs│  │ [Bar + Line + Tableau]          │ │
│  │ [Bar Chart]      │  │                                  │ │
│  └──────────────────┘  └──────────────────────────────────┘ │
│                                                               │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔧 Implémentation Technique

### Services à Créer/Modifier:

1. **`CombinedStatsService`** (nouveau)
   - Combine les données de `StatisticsService` et `DashboardStatsService`
   - Méthode: `getCombinedStats($filters)`

2. **`RevenueForecastService`** (nouveau) ⭐
   - Calcule les prévisions de revenus
   - Méthodes:
     - `getRevenueForecast($months = 3)`: Prévision sur N mois
     - `calculateTrend($period)`: Calcule la tendance
     - `getForecastScenarios()`: Scénarios optimiste/réaliste/pessimiste

3. **Modifier `DashboardStatsService`**
   - Ajouter méthode: `getRevenueForecast($month, $year)`

### Contrôleur:

- **Route unique:** `/admin/stats-reports` (combine les deux)
- **Méthode:** `combinedStatsReports(Request $request)`
- **Paramètres:** Tous les filtres (période, plan, organisateur)

### Templates:

- **Unifier:** `templates/Admin/stats-reports/combined.html.twig`
- **Sections:** Utiliser des includes pour chaque widget

---

## 📋 Checklist d'Implémentation

### Phase 1: Préparation
- [ ] Créer `RevenueForecastService`
- [ ] Créer `CombinedStatsService`
- [ ] Modifier le contrôleur pour la route combinée

### Phase 2: Interface Statistiques/Rapports
- [ ] Widget 1: Revenus Fiscaux Consolidés
- [ ] Widget 2: Répartition des Abonnements
- [ ] Widget 3: Top 5 Contributeurs
- [ ] Widget 4: Analyse des Impayés
- [ ] Système de filtres unifié

### Phase 3: Dashboard
- [ ] Widget 3: Revenus et Prévision ⭐
- [ ] Intégrer la prévision dans les graphiques
- [ ] Ajuster les autres widgets existants

### Phase 4: Tests
- [ ] Tester tous les filtres
- [ ] Vérifier les calculs de prévision
- [ ] Tester l'export PDF/CSV
- [ ] Responsive design

---

## 💡 Notes Importantes

1. **Prévision:** Utiliser une moyenne mobile pondérée pour plus de précision
2. **Performance:** Mettre en cache les calculs de prévision (TTL: 1h)
3. **UX:** Ajouter des tooltips explicatifs sur les prévisions
4. **Accessibilité:** S'assurer que tous les graphiques sont accessibles (ARIA labels)

---

## 🎯 Résumé des Widgets

### Statistiques/Rapports (4 widgets):
1. Revenus Fiscaux Consolidés (2 cols)
2. Répartition des Abonnements (1 col)
3. Top 5 Contributeurs (1 col)
4. Analyse des Impayés (1 col, extensible)

### Dashboard (4 widgets):
1. Vue d'Ensemble Organisateurs
2. Performance des Abonnements
3. **Revenus et Prévision** ⭐ (IDÉAL POUR PRÉVISION)
4. Taux d'Engagement

---

**Date de création:** 2024
**Auteur:** Suggestions pour Aiolia Event

