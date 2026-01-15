# 🔴 Configuration Redis pour Aiolia Event

## 📋 Prérequis

1. **Redis installé** sur votre système
2. **Predis** installé via Composer (déjà fait)

## 🚀 Installation de Redis

### Ubuntu/Debian
```bash
sudo apt-get update
sudo apt-get install redis-server
sudo systemctl start redis-server
sudo systemctl enable redis-server
```

### macOS
```bash
brew install redis
brew services start redis
```

### Vérifier que Redis fonctionne
```bash
redis-cli ping
# Devrait répondre: PONG
```

## ⚙️ Configuration

### 1. Variables d'environnement

Ajoutez dans votre fichier `.env` :

```env
# Redis Configuration
REDIS_URL=redis://localhost:6379/0
```

Ou avec authentification (si configuré) :
```env
REDIS_URL=redis://:password@localhost:6379/0
```

### 2. Configuration Symfony

La configuration est déjà faite dans :
- `config/packages/cache.yaml` : Configuration des pools de cache
- `config/packages/redis.yaml` : Configuration des services Redis

### 3. Pools de cache disponibles

- `cache.app` : Cache principal de l'application
- `cache.events` : Cache pour les événements (TTL: 1 heure)
- `cache.search` : Cache pour les résultats de recherche (TTL: 30 minutes)
- `cache.stats` : Cache pour les statistiques (TTL: 30 minutes)
- `cache.sessions` : Cache pour les sessions (TTL: 24 heures)

## 💻 Utilisation

### Dans un Controller

```php
use App\Service\CacheService;

class MyController extends AbstractController
{
    public function __construct(
        private readonly CacheService $cacheService
    ) {}
    
    public function index(): Response
    {
        // Récupérer avec cache
        $events = $this->cacheService->getCachedUpcomingEvents(
            fn() => $this->eventRepository->findUpcomingEvents(),
            6,
            3600 // 1 heure
        );
    }
}
```

### Dans un Service

```php
use App\Service\CacheService;

class MyService
{
    public function __construct(
        private readonly CacheService $cacheService
    ) {}
    
    public function getData(): array
    {
        return $this->cacheService->get(
            'my_cache_key',
            fn() => $this->expensiveOperation(),
            1800 // 30 minutes
        );
    }
}
```

## 🧹 Invalidation du cache

```php
// Invalider le cache des événements
$this->cacheService->invalidateEventsCache();

// Invalider le cache d'un événement spécifique
$this->cacheService->invalidateEventsCache($eventId);

// Invalider le cache de recherche
$this->cacheService->invalidateSearchCache();

// Invalider tout le cache
$this->cacheService->clear();
```

## 🔍 Vérification

### Tester la connexion Redis

```bash
# Dans le terminal
redis-cli
> PING
PONG
> KEYS aiolia_front:*
# Affiche toutes les clés du cache
```

### Vérifier dans Symfony

```bash
php bin/console cache:pool:list
# Liste tous les pools de cache disponibles
```

## 📊 Monitoring

### Statistiques Redis

```bash
redis-cli INFO stats
```

### Espace utilisé

```bash
redis-cli INFO memory
```

## 🛠️ Dépannage

### Redis ne démarre pas

```bash
# Vérifier les logs
sudo journalctl -u redis-server

# Vérifier la configuration
redis-cli CONFIG GET "*"
```

### Erreur de connexion

1. Vérifier que Redis est démarré : `redis-cli ping`
2. Vérifier l'URL dans `.env` : `REDIS_URL=redis://localhost:6379/0`
3. Vérifier les permissions : Redis doit être accessible

### Cache ne fonctionne pas

1. Vider le cache Symfony : `php bin/console cache:clear`
2. Vérifier les logs : `var/log/dev.log`
3. Tester manuellement : `redis-cli GET "aiolia_front:upcoming_events_6_2025-01-13-14"`

## 📝 Notes

- Le préfixe `aiolia_front:` est automatiquement ajouté à toutes les clés
- Le cache est automatiquement invalidé après le TTL défini
- Les données sont sérialisées automatiquement par Symfony
- Redis doit être démarré avant de lancer l'application

## 🔐 Sécurité (Production)

Pour la production, configurez :

1. **Authentification Redis** :
```env
REDIS_URL=redis://:strong_password@localhost:6379/0
```

2. **Firewall** : Limiter l'accès Redis au localhost uniquement

3. **Persistance** : Configurer la persistance Redis si nécessaire

4. **Monitoring** : Utiliser Redis Sentinel ou Cluster pour la haute disponibilité
