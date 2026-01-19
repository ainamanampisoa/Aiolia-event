# 🎨 Exemple de Code : Cartes/Badges pour le Profil Passion

## 📝 Code HTML/Twig

```twig
{# Profil Passion avec Cartes Colorées #}
<section class="stats-charts" style="margin-top: 32px;">
    <div class="chart-card wow fadeInUp" data-wow-delay="280ms" style="grid-column: 1 / -1; background: linear-gradient(135deg, #ffffff 0%, #f8faff 100%); overflow: hidden; position: relative;">
        <div style="position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; background: radial-gradient(circle, rgba(74, 144, 226, 0.05) 0%, transparent 70%); border-radius: 50%;"></div>
        <header>
            <div>
                <h2 style="font-size: 22px; color: #1F2D3D; margin-bottom: 4px;">Votre Profil Passion</h2>
                <span class="chart-period">Découvrez l'équilibre de vos centres d'intérêt</span>
            </div>
            <div class="passion-badge" style="background: rgba(74, 144, 226, 0.1); color: #4A90E2; padding: 6px 16px; border-radius: 999px; font-weight: 700; font-size: 13px;">
                <i class="fas fa-star" style="margin-right: 6px;"></i>Analyse personnalisée
            </div>
        </header>
        
        <div class="passion-cards-container" style="padding: 24px 0;">
            {% if passionProfile.labels|length > 0 %}
                {% set categoryIcons = {
                    'Musique': 'fas fa-music',
                    'Concert': 'fas fa-music',
                    'Sport': 'fas fa-running',
                    'Culture': 'fas fa-theater-masks',
                    'Art': 'fas fa-palette',
                    'Business': 'fas fa-briefcase',
                    'Théâtre': 'fas fa-theater-masks',
                    'Festival': 'fas fa-calendar-alt',
                    'Conférence': 'fas fa-microphone',
                    'Autres': 'fas fa-star'
                } %}
                
                {% set categoryColors = [
                    {'bg': 'rgba(74, 144, 226, 0.1)', 'border': '#4A90E2', 'text': '#4A90E2', 'bar': 'linear-gradient(90deg, #4A90E2 0%, #6BCBFF 100%)'},
                    {'bg': 'rgba(80, 200, 120, 0.1)', 'border': '#50C878', 'text': '#50C878', 'bar': 'linear-gradient(90deg, #50C878 0%, #7DD87D 100%)'},
                    {'bg': 'rgba(255, 107, 107, 0.1)', 'border': '#FF6B6B', 'text': '#FF6B6B', 'bar': 'linear-gradient(90deg, #FF6B6B 0%, #FF8E8E 100%)'},
                    {'bg': 'rgba(255, 165, 0, 0.1)', 'border': '#FFA500', 'text': '#FFA500', 'bar': 'linear-gradient(90deg, #FFA500 0%, #FFB84D 100%)'},
                    {'bg': 'rgba(155, 89, 182, 0.1)', 'border': '#9B59B6', 'text': '#9B59B6', 'bar': 'linear-gradient(90deg, #9B59B6 0%, #B57EDC 100%)'},
                    {'bg': 'rgba(116, 185, 255, 0.1)', 'border': '#74B9FF', 'text': '#74B9FF', 'bar': 'linear-gradient(90deg, #74B9FF 0%, #95C9FF 100%)'}
                ] %}
                
                {% for label in passionProfile.labels %}
                    {% set percentage = passionProfile.data[loop.index0] %}
                    {% set colorIndex = (loop.index0 % categoryColors|length) %}
                    {% set color = categoryColors[colorIndex] %}
                    {% set icon = categoryIcons[label]|default('fas fa-star') %}
                    
                    {% set description = '' %}
                    {% if percentage >= 40 %}
                        {% set description = 'Votre passion principale' %}
                    {% elseif percentage >= 25 %}
                        {% set description = 'Deuxième centre d\'intérêt' %}
                    {% elseif percentage >= 15 %}
                        {% set description = 'Intérêt modéré' %}
                    {% else %}
                        {% set description = 'Intérêt naissant' %}
                    {% endif %}
                    
                    <div class="passion-card" style="background: #ffffff; border-radius: 16px; padding: 20px; margin-bottom: 16px; border: 2px solid {{ color.border }}; box-shadow: 0 4px 20px rgba(0,0,0,0.08); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); position: relative; overflow: hidden;">
                        {# Fond coloré subtil #}
                        <div style="position: absolute; top: 0; left: 0; right: 0; height: 4px; background: {{ color.bar }};"></div>
                        
                        <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 12px;">
                            {# Icône #}
                            <div style="width: 48px; height: 48px; border-radius: 12px; background: {{ color.bg }}; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <i class="{{ icon }}" style="font-size: 22px; color: {{ color.text }};"></i>
                            </div>
                            
                            {# Titre et description #}
                            <div style="flex: 1; min-width: 0;">
                                <h3 style="margin: 0 0 4px; font-size: 18px; font-weight: 700; color: #1F2D3D;">
                                    {{ label }}
                                </h3>
                                <p style="margin: 0; font-size: 13px; color: rgba(31, 45, 61, 0.6);">
                                    {{ description }}
                                </p>
                            </div>
                            
                            {# Badge de pourcentage #}
                            <div style="background: {{ color.bg }}; color: {{ color.text }}; padding: 8px 16px; border-radius: 20px; font-weight: 700; font-size: 16px; white-space: nowrap;">
                                {{ percentage }}%
                            </div>
                        </div>
                        
                        {# Barre de progression #}
                        <div style="width: 100%; height: 10px; background: rgba(31, 45, 61, 0.1); border-radius: 6px; overflow: hidden; position: relative;">
                            <div class="passion-progress-bar" style="height: 100%; width: {{ percentage }}%; background: {{ color.bar }}; border-radius: 6px; transition: width 1s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 2px 8px rgba(0,0,0,0.15);"></div>
                        </div>
                    </div>
                {% endfor %}
                
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(31, 45, 61, 0.1);">
                    <p style="margin: 0; font-size: 13px; color: rgba(31, 45, 61, 0.5); font-style: italic; text-align: center;">
                        <i class="fas fa-info-circle" style="margin-right: 6px;"></i>
                        Basé sur vos {{ stats.total_tickets|default(0) }} billets réservés
                    </p>
                </div>
            {% else %}
                <div class="no-data-message" style="height: 300px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 30px;">
                    <i class="fas fa-chart-pie" style="font-size: 48px; color: rgba(0,0,0,0.1); margin-bottom: 16px;"></i>
                    <p style="color: rgba(0,0,0,0.4); font-size: 14px; margin: 0;">
                        Continuez à explorer pour débloquer votre profil passion
                    </p>
                </div>
            {% endif %}
        </div>
    </div>
</section>
```

## 🎨 CSS Additionnel

```css
/* Styles pour les cartes de passion */
.passion-card {
    position: relative;
}

.passion-card:hover {
    transform: translateY(-4px) scale(1.01);
    box-shadow: 0 8px 30px rgba(0,0,0,0.12) !important;
    border-color: currentColor !important;
}

.passion-progress-bar {
    animation: slideInProgress 1.2s ease-out;
}

@keyframes slideInProgress {
    from {
        width: 0;
    }
}

/* Responsive */
@media (max-width: 768px) {
    .passion-card {
        padding: 16px !important;
    }
    
    .passion-card h3 {
        font-size: 16px !important;
    }
}
```

## ✨ Avantages de cette Solution

1. ✅ **Design moderne** : Cartes avec bordures colorées, icônes, badges
2. ✅ **Très informatif** : Affiche catégorie, pourcentage, description
3. ✅ **Interactif** : Effets hover, animations de barres
4. ✅ **Cohérent** : Style similaire aux "Top 3 événements"
5. ✅ **Mobile-friendly** : S'adapte bien aux petits écrans
6. ✅ **Pas de dépendance Chart.js** : Code HTML/CSS pur
7. ✅ **Personnalisable** : Facile d'ajouter des icônes, couleurs, descriptions

## 🎯 Résultat Visuel

```
┌─────────────────────────────────────────────────────┐
│  Votre Profil Passion                               │
├─────────────────────────────────────────────────────┤
│                                                      │
│  ┌──────────────────────────────────────────────┐ │
│  │ 🎵  Musique              [Votre passion]  45%│ │
│  │     principale                                │ │
│  │     [████████████████████████████]            │ │
│  └──────────────────────────────────────────────┘ │
│                                                      │
│  ┌──────────────────────────────────────────────┐ │
│  │ 🏃  Sport                [Deuxième]       30%│ │
│  │     centre d'intérêt                          │ │
│  │     [████████████████████]                    │ │
│  └──────────────────────────────────────────────┘ │
│                                                      │
│  ┌──────────────────────────────────────────────┐ │
│  │ 🎭  Culture              [Intérêt]       15%│ │
│  │     modéré                                    │ │
│  │     [████████]                                │ │
│  └──────────────────────────────────────────────┘ │
│                                                      │
└─────────────────────────────────────────────────────┘
```

---

**Date :** 2025-01-13  
**Option :** Cartes/Badges Colorées
