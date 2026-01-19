# 🔧 Exemple de Code : Remplacer le Radar par des Barres Horizontales

## 📝 Modifications à apporter dans `stats.html.twig`

### 1. Modifier le canvas (ligne ~266)

**AVANT :**
```html
<canvas id="passionRadarChart" style="max-height: 450px;"></canvas>
```

**APRÈS :**
```html
<canvas id="passionBarChart" style="max-height: 450px;"></canvas>
```

---

### 2. Modifier le JavaScript (lignes ~287-356)

**AVANT (Radar) :**
```javascript
// Initialisation du Radar Chart (Profil Passion)
const radarCtx = document.getElementById('passionRadarChart');
if (radarCtx) {
    const passionData = {{ passionProfile|json_encode|raw }};
    
    new Chart(radarCtx, {
        type: 'radar',
        data: {
            labels: passionData.labels,
            datasets: [{
                label: 'Intensité Passion',
                data: passionData.data,
                fill: true,
                backgroundColor: 'rgba(74, 144, 226, 0.2)',
                borderColor: '#4A90E2',
                pointBackgroundColor: '#4A90E2',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: '#4A90E2',
                borderWidth: 3,
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#1F2D3D',
                    padding: 12,
                    titleFont: { size: 14, weight: 'bold' },
                    bodyFont: { size: 13 },
                    callbacks: {
                        label: function(context) {
                            return ` Engagement: ${context.raw}%`;
                        }
                    }
                }
            },
            scales: {
                r: {
                    angleLines: {
                        display: true,
                        color: 'rgba(31, 45, 61, 0.1)'
                    },
                    grid: {
                        color: 'rgba(31, 45, 61, 0.1)'
                    },
                    ticks: {
                        display: false,
                        stepSize: 20
                    },
                    pointLabels: {
                        font: {
                            size: 13,
                            weight: '600',
                            family: "'Inter', sans-serif"
                        },
                        color: '#1F2D3D'
                    },
                    suggestedMin: 0,
                    suggestedMax: 100
                }
            }
        }
    });
}
```

**APRÈS (Barres Horizontales) :**
```javascript
// Initialisation du Bar Chart Horizontal (Profil Passion)
const barCtx = document.getElementById('passionBarChart');
if (barCtx) {
    const passionData = {{ passionProfile|json_encode|raw }};
    
    // Couleurs pour chaque barre
    const colors = [
        'rgba(74, 144, 226, 0.8)',   // Bleu
        'rgba(80, 200, 120, 0.8)',   // Vert
        'rgba(255, 107, 107, 0.8)',  // Rouge
        'rgba(255, 165, 0, 0.8)',    // Orange
        'rgba(155, 89, 182, 0.8)',   // Violet
        'rgba(116, 185, 255, 0.8)'   // Bleu clair
    ];
    
    new Chart(barCtx, {
        type: 'bar',
        data: {
            labels: passionData.labels,
            datasets: [{
                label: 'Engagement (%)',
                data: passionData.data,
                backgroundColor: passionData.labels.map((_, index) => 
                    colors[index % colors.length]
                ),
                borderColor: passionData.labels.map((_, index) => 
                    colors[index % colors.length].replace('0.8', '1')
                ),
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false
            }]
        },
        options: {
            indexAxis: 'y', // Barres horizontales
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#1F2D3D',
                    padding: 12,
                    titleFont: { size: 14, weight: 'bold' },
                    bodyFont: { size: 13 },
                    callbacks: {
                        label: function(context) {
                            return ` Engagement: ${context.raw}%`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        },
                        color: 'rgba(31, 45, 61, 0.6)',
                        font: {
                            size: 12
                        }
                    },
                    grid: {
                        color: 'rgba(31, 45, 61, 0.1)'
                    }
                },
                y: {
                    ticks: {
                        color: '#1F2D3D',
                        font: {
                            size: 13,
                            weight: '600',
                            family: "'Inter', sans-serif"
                        }
                    },
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}
```

---

## 🎨 Avantages du Nouveau Code

### ✅ **Meilleure lisibilité**
- Les labels sont horizontaux, faciles à lire
- Les valeurs sont clairement visibles

### ✅ **Comparaison facilitée**
- On compare facilement les longueurs des barres
- Les pourcentages sont évidents

### ✅ **Cohérence**
- Utilise Chart.js de manière standard
- Style cohérent avec le reste de l'application

### ✅ **Responsive**
- S'adapte bien aux petits écrans
- Les barres horizontales sont plus lisibles sur mobile

---

## 📊 Résultat Visuel Attendu

```
Musique        ████████████████████████████ 45%
Sport          ████████████████ 30%
Culture        ████████ 15%
Art            ████ 10%
Théâtre        ██ 5%
```

Au lieu du Radar actuel qui forme une forme polygonale.

---

## 🔄 Étapes de Migration

1. ✅ Remplacer `passionRadarChart` par `passionBarChart` dans le HTML
2. ✅ Remplacer le code JavaScript du Radar par le code des Barres
3. ✅ Tester avec différentes données
4. ✅ Vérifier le responsive sur mobile
5. ✅ Mettre à jour le texte d'aide si nécessaire

---

**Date :** 2025-01-13
**Type de graphique :** Barres Horizontales (Chart.js `bar` avec `indexAxis: 'y'`)
