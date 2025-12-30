# ⚙️ Module 11 : Mode Sombre et Multilingue

## Description

Ce module gère les préférences d'apparence (thème clair/sombre) et la localisation (multilingue) de l'application. Il permet aux utilisateurs de personnaliser leur expérience visuelle et linguistique.

---

## 📂 Fichiers concernés

### Mode Sombre (Thème)

| Type | Fichier |
|------|---------|
| Listener | `src/EventListener/ThemeListener.php` |
| JavaScript | `public/js/theme-manager.js` |
| Template | `templates/base.html.twig` |
| Paramètres | `templates/profile/settings.html.twig` |

### Multilingue (i18n)

| Type | Fichier |
|------|---------|
| Contrôleur | `src/Controller/LocaleController.php` |
| Listener | `src/EventListener/LocaleListener.php` |
| Traductions FR | `translations/messages.fr.yaml` |
| Traductions EN | `translations/messages.en.yaml` |
| Paramètres | `templates/profile/settings.html.twig` |

---

## 🎨 Mode Sombre

### Fonctionnement

1. **Stockage** : Le thème est stocké dans :
   - `localStorage` (priorité - persistance côté client)
   - Table `user_preferences` en BDD (préférences serveur)

2. **Application** : 
   - Classes CSS : `theme-light` ou `theme-dark` sur `<html>` et `<body>`
   - Attribut data : `data-theme="light|dark"`

3. **Détection système** :
   - Écoute `prefers-color-scheme: dark`
   - S'applique uniquement si pas de préférence utilisateur

### ThemeManager (JavaScript)

```javascript
class ThemeManager {
    constructor() {
        this.STORAGE_KEY = 'aiolia_theme';
        this.THEME_LIGHT = 'light';
        this.THEME_DARK = 'dark';
    }
    
    // Basculer entre les thèmes
    toggleTheme() {
        const newTheme = this.currentTheme === this.THEME_LIGHT 
            ? this.THEME_DARK 
            : this.THEME_LIGHT;
        this.applyTheme(newTheme);
        return newTheme;
    }
    
    // Appliquer un thème
    applyTheme(theme) {
        document.documentElement.classList.remove('theme-light', 'theme-dark');
        document.body.classList.remove('theme-light', 'theme-dark');
        document.documentElement.classList.add(`theme-${theme}`);
        document.body.classList.add(`theme-${theme}`);
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem(this.STORAGE_KEY, theme);
    }
}

// Instance globale
window.themeManager = new ThemeManager();
```

### ThemeListener (PHP)

```php
class ThemeListener implements EventSubscriberInterface
{
    public function onKernelController(ControllerEvent $event): void
    {
        // Récupérer les préférences utilisateur
        $preferences = [
            'appearance' => ['theme' => 'light'], // Par défaut
        ];

        if ($isAuthenticated) {
            $preferences = $this->fetchUserPreferences($userId);
        }

        // Passer à Twig pour tous les templates
        $this->twig->addGlobal('preferences', $preferences);
    }
}
```

### Utilisation dans les templates

```twig
{# base.html.twig #}
<html data-user-theme="{{ preferences.appearance.theme|default('light') }}">

{# Appel JavaScript #}
<script>
    const userTheme = '{{ preferences.appearance.theme|default("light") }}';
    if (window.themeManager) {
        window.themeManager.setTheme(userTheme);
    }
</script>
```

### CSS Variables

```css
/* Theme light (défaut) */
:root, .theme-light {
    --bg-primary: #ffffff;
    --bg-secondary: #f5f5f5;
    --text-primary: #333333;
    --text-secondary: #666666;
    --accent-color: #e74c3c;
}

/* Theme dark */
.theme-dark {
    --bg-primary: #1a1a2e;
    --bg-secondary: #16213e;
    --text-primary: #eaeaea;
    --text-secondary: #b0b0b0;
    --accent-color: #ff6b6b;
}

/* Utilisation */
body {
    background-color: var(--bg-primary);
    color: var(--text-primary);
}
```

---

## 🌍 Multilingue

### Langues supportées

| Code | Langue | Fichier |
|------|--------|---------|
| `fr` | Français | `messages.fr.yaml` |
| `en` | Anglais | `messages.en.yaml` |
| `mg` | Malgache | (prévu) |

### LocaleController

```php
#[Route('/locale/{locale}', name: 'set_locale', methods: ['GET'])]
public function setLocale(string $locale, Request $request): RedirectResponse
{
    $allowedLocales = ['fr', 'en'];

    if (!in_array($locale, $allowedLocales, true)) {
        $locale = 'fr';
    }

    // Stocker la locale dans la session
    $session->set('_locale', $locale);

    // Revenir sur la page précédente
    return $this->redirect($request->headers->get('referer') ?? '/');
}
```

### LocaleListener

```php
class LocaleListener implements EventSubscriberInterface
{
    public function onKernelRequest(RequestEvent $event): void
    {
        $sessionUser = $session->get('user');
        
        if ($isAuthenticated) {
            // Récupérer le language_code de l'utilisateur
            $languageCode = $this->connection->executeQuery(
                'SELECT language_code FROM aiolia.users WHERE id = :userId',
                ['userId' => $userId]
            )->fetchOne();
            
            // Convertir fr-FR -> fr
            $locale = $this->convertLanguageCodeToLocale($languageCode);
            $request->setLocale($locale);
        } else {
            // Utiliser la locale de la session ou 'fr' par défaut
            $request->setLocale($session->get('_locale', 'fr'));
        }
    }
    
    private function convertLanguageCodeToLocale(string $languageCode): string
    {
        $parts = explode('-', $languageCode);
        $lang = strtolower($parts[0] ?? 'fr');
        
        $supportedLocales = ['fr', 'en', 'mg'];
        return in_array($lang, $supportedLocales) ? $lang : 'fr';
    }
}
```

### Utilisation dans les templates

```twig
{# Texte traduit #}
{{ 'common.events'|trans }}
{{ 'settings.dark_theme'|trans }}

{# Avec paramètres #}
{{ 'events.search_results_count'|trans({'count': results|length}) }}

{# Changement de langue #}
<a href="{{ path('set_locale', {locale: 'fr'}) }}">🇫🇷 Français</a>
<a href="{{ path('set_locale', {locale: 'en'}) }}">🇬🇧 English</a>
```

### Structure des fichiers de traduction

```yaml
# messages.fr.yaml
common:
  events: "évènements"
  theme: "Thème"
  language: "Langue"

settings:
  light_theme: "Clair"
  light_theme_desc: "Interface claire et lumineuse"
  dark_theme: "Sombre"
  dark_theme_desc: "Mode sombre confortable"
```

```yaml
# messages.en.yaml
common:
  events: "events"
  theme: "Theme"
  language: "Language"

settings:
  light_theme: "Light"
  light_theme_desc: "Bright and clear interface"
  dark_theme: "Dark"
  dark_theme_desc: "Comfortable dark mode"
```

---

## 📋 Scénarios d'utilisation

### Scénario 1 : Changer le thème

1. **Lova** accède aux paramètres (`/profile/settings`)
2. Elle voit la section "Apparence"
3. Elle clique sur "Sombre"
4. L'interface passe instantanément en mode sombre
5. Le choix est sauvegardé dans `localStorage` et en BDD
6. À sa prochaine visite, le mode sombre est appliqué

### Scénario 2 : Changer la langue

1. **John** (anglophone) accède au site
2. Le site s'affiche en français par défaut
3. Il clique sur le drapeau 🇬🇧 English dans le footer
4. La route `/locale/en` est appelée
5. La session stocke `_locale = en`
6. La page se recharge en anglais
7. À sa prochaine visite, l'anglais est conservé

### Scénario 3 : Préférence système

1. **Hery** a configuré son OS en mode sombre
2. Il visite Aiolia-event pour la première fois
3. Le `ThemeManager` détecte `prefers-color-scheme: dark`
4. Le site s'affiche automatiquement en mode sombre
5. S'il change manuellement, sa préférence prend le dessus

---

## 📱 Routes

| Route | Méthode | Description |
|-------|---------|-------------|
| `/locale/{locale}` | GET | Changer la langue (fr, en) |
| `/profile/settings` | GET | Page des paramètres |
| `/profile/settings/update` | POST | Sauvegarder les préférences |

---

## 🎨 Interface des paramètres

### Section Apparence

```
┌─────────────────────────────────────────┐
│ Apparence                               │
├─────────────────────────────────────────┤
│ ○ ☀️ Clair                              │
│   Interface claire et lumineuse         │
│                                         │
│ ● 🌙 Sombre                             │
│   Mode sombre confortable               │
└─────────────────────────────────────────┘
```

### Section Langue

```
┌─────────────────────────────────────────┐
│ Langue                                  │
├─────────────────────────────────────────┤
│ ▼ Français                              │
│   ├─ 🇫🇷 Français                        │
│   └─ 🇬🇧 English                         │
└─────────────────────────────────────────┘
```

---

## 📊 Structure de données

### Table `user_preferences`

```sql
CREATE TABLE aiolia.user_preferences (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES aiolia.users(id),
    preference_key VARCHAR(50), -- 'appearance', 'notifications', 'security'
    preference_value JSONB,     -- {"theme": "dark"}
    updated_at TIMESTAMP DEFAULT NOW()
);
```

### Exemple de données

```sql
INSERT INTO aiolia.user_preferences VALUES
(1, 42, 'appearance', '{"theme": "dark"}'),
(2, 42, 'notifications', '{"ticket_alerts": true, "newsletters": false}');
```

### Table `users` (champ langue)

```sql
ALTER TABLE aiolia.users ADD COLUMN language_code VARCHAR(10) DEFAULT 'fr-FR';
-- Valeurs possibles: 'fr-FR', 'en-US', 'mg-MG'
```

---

## ⚙️ Configuration

### `config/packages/translation.yaml`

```yaml
framework:
    default_locale: fr
    translator:
        default_path: '%kernel.project_dir%/translations'
        fallbacks:
            - fr
```

### `config/services.yaml`

```yaml
services:
    App\EventListener\ThemeListener:
        tags:
            - { name: kernel.event_subscriber }
    
    App\EventListener\LocaleListener:
        tags:
            - { name: kernel.event_subscriber, priority: 20 }
```

---

## 🔗 Dépendances

- **ThemeListener** : Injection des préférences dans Twig
- **LocaleListener** : Gestion de la locale par requête
- **LocaleController** : Changement de langue
- **TranslatorInterface** : Service de traduction Symfony
- **localStorage** : Stockage côté client du thème


