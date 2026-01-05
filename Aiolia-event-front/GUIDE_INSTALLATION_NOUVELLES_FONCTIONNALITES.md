# 🚀 Guide d'Installation - Nouvelles Fonctionnalités Historique Financier

## 📋 Prérequis

- Serveur web configuré (Apache/Nginx)
- PHP 8.0 ou supérieur
- PostgreSQL 12 ou supérieur
- Symfony 6.x
- Composer installé
- Accès à la base de données

## 🔧 Installation

### Étape 1 : Appliquer les migrations

```bash
# Se placer dans le répertoire du projet
cd Aiolia-event-front

# Appliquer la migration pour la table user_preferences
psql -U votre_utilisateur -d votre_base -f migrations/add_user_preferences_budget.sql

# Ou via PHP/PDO si vous préférez
php bin/console doctrine:migrations:execute --up
```

### Étape 2 : Vérifier la base de données

Connectez-vous à PostgreSQL et vérifiez :

```sql
-- Vérifier que la table existe
\dt aiolia.user_preferences

-- Vérifier la structure
\d aiolia.user_preferences

-- Vérifier les index
\di aiolia.*user_preferences*
```

Résultat attendu :
```
                                Table "aiolia.user_preferences"
     Column      |            Type             | Collation | Nullable |      Default
-----------------+-----------------------------+-----------+----------+-------------------
 id              | integer                     |           | not null | nextval('...')
 user_id         | integer                     |           | not null |
 preference_key  | character varying(100)      |           | not null |
 preference_value| text                        |           |          |
 created_at      | timestamp without time zone |           |          | now()
 updated_at      | timestamp without time zone |           |          | now()
```

### Étape 3 : Vider le cache Symfony

```bash
# Cache production
php bin/console cache:clear --env=prod

# Cache développement
php bin/console cache:clear --env=dev

# Warmup cache
php bin/console cache:warmup
```

### Étape 4 : Vérifier les routes

```bash
# Lister toutes les routes pour vérifier les nouvelles
php bin/console debug:router | grep financial
php bin/console debug:router | grep budget
```

Vous devriez voir :
```
profile_financial                GET      /profile/financial-history
profile_financial_export_csv     GET      /profile/financial-history/export-csv
profile_financial_export_pdf     GET      /profile/financial-history/export-pdf
api_budget_update               POST     /api/profile/budget/update
```

### Étape 5 : Vérifier les permissions

```bash
# Permissions répertoires
chmod -R 755 src/Controller
chmod -R 755 src/Repository
chmod -R 755 templates/profile

# Permissions cache et logs
chmod -R 777 var/cache
chmod -R 777 var/log
```

### Étape 6 : Test fonctionnel

#### Test 1 : Accéder à la page
```bash
# En développement
https://votre-domaine.local/profile/financial-history

# En production
https://votre-domaine.com/profile/financial-history
```

#### Test 2 : Configurer un budget

1. Se connecter avec un compte utilisateur
2. Aller sur l'historique financier
3. Cliquer "Définir mon budget"
4. Entrer un montant (ex: 500000)
5. Enregistrer

Vérifier en base :
```sql
SELECT * FROM aiolia.user_preferences 
WHERE user_id = VOTRE_USER_ID 
AND preference_key = 'monthly_budget';
```

#### Test 3 : Export CSV

1. Sur la page historique financier
2. Cliquer sur "Export CSV"
3. Vérifier que le fichier se télécharge
4. Ouvrir avec Excel/LibreOffice
5. Vérifier les données

#### Test 4 : Filtres avancés

1. Cliquer "Filtres avancés"
2. Sélectionner une méthode de paiement (ex: MVola)
3. Définir un montant minimum (ex: 10000)
4. Appliquer
5. Vérifier que les données sont filtrées

## 🐛 Dépannage

### Problème : Table user_preferences n'existe pas

**Solution** :
```sql
-- Exécuter manuellement la création
CREATE TABLE aiolia.user_preferences (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES aiolia.users(id) ON DELETE CASCADE,
    preference_key VARCHAR(100) NOT NULL,
    preference_value TEXT,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    CONSTRAINT unique_user_preference UNIQUE (user_id, preference_key)
);
```

### Problème : Route api_budget_update introuvable

**Solution** :
```bash
# Vider le cache des routes
php bin/console cache:clear
php bin/console router:match /api/profile/budget/update
```

### Problème : Erreur 500 lors de l'export CSV

**Vérifier** :
1. Permissions d'écriture dans var/
2. Logs : `tail -f var/log/dev.log`
3. Format des données en base

**Solution** :
```bash
# Vérifier les logs
tail -100 var/log/dev.log

# Tester la route directement
curl -X GET "https://votre-domaine.com/profile/financial-history/export-csv?year=2025"
```

### Problème : Modal budget ne s'ouvre pas

**Vérifier** :
1. Console navigateur (F12)
2. Erreurs JavaScript
3. Font Awesome chargé

**Solution** :
```javascript
// Dans la console navigateur
console.log(typeof openBudgetModal);
// Devrait retourner "function"
```

### Problème : Filtres ne fonctionnent pas

**Vérifier** :
```sql
-- Tester la requête avec filtres
SELECT o.id, o.total_amount, o.notes::json->>'payment_method' as method
FROM aiolia.orders o
WHERE o.user_id = VOTRE_USER_ID
  AND (o.status = 'paid' OR o.status = 'pending')
  AND o.notes::json->>'payment_method' = 'mvola';
```

### Problème : Catégories vides dans le filtre

**Vérifier** :
```sql
-- Vérifier que les événements ont des catégories
SELECT DISTINCT category 
FROM aiolia.events 
WHERE category IS NOT NULL AND category != '';
```

**Solution** :
```sql
-- Ajouter des catégories si manquantes
UPDATE aiolia.events 
SET category = 'Concert' 
WHERE category IS NULL OR category = '';
```

## 📊 Vérification de la Performance

### Test de charge

```bash
# Avec Apache Bench
ab -n 100 -c 10 https://votre-domaine.com/profile/financial-history

# Avec curl + time
time curl https://votre-domaine.com/profile/financial-history/export-csv?year=2025
```

### Optimisation des requêtes

```sql
-- Vérifier les index
EXPLAIN ANALYZE 
SELECT * FROM aiolia.user_preferences 
WHERE user_id = 1 AND preference_key = 'monthly_budget';

-- Devrait utiliser l'index idx_user_preferences_user_id
```

## 🔒 Sécurité

### Vérifications recommandées

1. **Authentification** :
```php
// Vérifier dans ProfileController
if (!$isAuthenticated) {
    return $this->redirectToRoute('login');
}
```

2. **Validation des entrées** :
```php
// Budget ne peut pas être négatif
if ($monthlyBudget < 0) {
    return new JsonResponse(['error' => 'Invalid'], 400);
}
```

3. **Protection CSRF** :
```yaml
# config/packages/framework.yaml
framework:
    csrf_protection: true
```

## 📱 Test Responsive

### Breakpoints à tester

- **Mobile** : 375px, 414px, 768px
- **Tablet** : 768px, 1024px
- **Desktop** : 1280px, 1920px

### Outils recommandés

- Chrome DevTools (F12 > Toggle Device Toolbar)
- Firefox Responsive Design Mode
- BrowserStack pour tests multi-devices

## 🎨 Personnalisation

### Modifier les couleurs

Dans `financial.html.twig`, section `<style>` :

```css
/* Changer le thème principal */
.btn-save {
    background: linear-gradient(135deg, #VOTRE_COULEUR_1 0%, #VOTRE_COULEUR_2 100%);
}

/* Changer les alertes */
.insight-card.insight-warning {
    background: #VOTRE_COULEUR_FOND;
    border-left-color: #VOTRE_COULEUR_BORDURE;
}
```

### Modifier les montants suggérés

Dans le modal budget :

```html
<button type="button" onclick="setBudget(VOTRE_MONTANT)">VOTRE_LABEL</button>
```

### Ajouter des insights personnalisés

Dans `ProfileController.php`, méthode `generateFinancialInsights()` :

```php
// Ajouter votre logique
if (VOTRE_CONDITION) {
    $insights[] = [
        'type' => 'info',
        'icon' => 'fas fa-VOTRE_ICON',
        'title' => 'Votre titre',
        'message' => 'Votre message',
    ];
}
```

## ✅ Checklist de Validation

- [ ] Table user_preferences créée
- [ ] Index créés
- [ ] Cache Symfony vidé
- [ ] Routes accessibles
- [ ] Budget configurable
- [ ] Export CSV fonctionnel
- [ ] Export PDF fonctionnel
- [ ] Filtres avancés fonctionnels
- [ ] Comparaison temporelle affichée
- [ ] Insights visibles
- [ ] Modal budget opérationnelle
- [ ] Transactions récentes affichées
- [ ] Responsive mobile OK
- [ ] Responsive tablet OK
- [ ] Responsive desktop OK
- [ ] Console navigateur sans erreur
- [ ] Logs serveur propres

## 📞 Support

En cas de problème persistant :

1. Consulter les logs : `var/log/dev.log`
2. Vérifier la console navigateur (F12)
3. Tester les requêtes SQL manuellement
4. Vérifier les permissions fichiers
5. Consulter la documentation Symfony

## 🎓 Ressources

- [Documentation Symfony](https://symfony.com/doc/current/index.html)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)
- [Font Awesome Icons](https://fontawesome.com/icons)
- [MDN Web Docs](https://developer.mozilla.org/)

---

**Bonne installation ! 🚀**


