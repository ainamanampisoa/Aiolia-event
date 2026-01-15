<?php

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Service de gestion du cache Redis pour optimiser les performances.
 */
class CacheService
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly CacheItemPoolInterface $cacheEvents,
        private readonly CacheItemPoolInterface $cacheSearch,
        private readonly CacheItemPoolInterface $cacheStats
    ) {
    }

    /**
     * Récupère ou met en cache les événements à venir.
     * 
     * @param callable $callback Fonction qui retourne les événements si non en cache
     * @param int $limit Nombre d'événements à récupérer
     * @param int $ttl Durée de vie du cache en secondes (défaut: 1 heure)
     * @return array
     */
    public function getCachedUpcomingEvents(callable $callback, int $limit = 6, int $ttl = 3600): array
    {
        $cacheKey = "upcoming_events_{$limit}_" . date('Y-m-d-H');
        
        return $this->cacheEvents->get(
            $cacheKey,
            function (ItemInterface $item) use ($callback, $ttl) {
                $item->expiresAfter($ttl);
                return $callback();
            }
        );
    }

    /**
     * Récupère ou met en cache les résultats de recherche.
     * 
     * @param string $searchTerm Terme de recherche
     * @param callable $callback Fonction qui retourne les résultats si non en cache
     * @param int $ttl Durée de vie du cache en secondes (défaut: 30 minutes)
     * @return array
     */
    public function getCachedSearchResults(string $searchTerm, callable $callback, int $ttl = 1800): array
    {
        $cacheKey = 'search_' . md5(strtolower(trim($searchTerm)));
        
        return $this->cacheSearch->get(
            $cacheKey,
            function (ItemInterface $item) use ($callback, $ttl) {
                $item->expiresAfter($ttl);
                return $callback();
            }
        );
    }

    /**
     * Récupère ou met en cache les statistiques.
     * 
     * @param string $statsType Type de statistiques (home, event, user)
     * @param string|int $identifier Identifiant (event_id, user_id, etc.)
     * @param callable $callback Fonction qui retourne les stats si non en cache
     * @param int $ttl Durée de vie du cache en secondes (défaut: 30 minutes)
     * @return array
     */
    public function getCachedStats(string $statsType, string|int $identifier, callable $callback, int $ttl = 1800): array
    {
        $cacheKey = "stats_{$statsType}_{$identifier}_" . date('Y-m-d-H');
        
        return $this->cacheStats->get(
            $cacheKey,
            function (ItemInterface $item) use ($callback, $ttl) {
                $item->expiresAfter($ttl);
                return $callback();
            }
        );
    }

    /**
     * Invalide le cache des événements.
     * 
     * @param int|null $eventId ID de l'événement spécifique (optionnel)
     * @return void
     */
    public function invalidateEventsCache(?int $eventId = null): void
    {
        if ($eventId !== null) {
            // Invalider le cache pour un événement spécifique
            $this->cacheEvents->deleteItem("event_{$eventId}");
        }
        
        // Invalider tous les caches d'événements
        $this->cacheEvents->clear();
    }

    /**
     * Invalide le cache de recherche.
     * 
     * @return void
     */
    public function invalidateSearchCache(): void
    {
        $this->cacheSearch->clear();
    }

    /**
     * Invalide le cache des statistiques.
     * 
     * @param string|null $statsType Type de statistiques (optionnel)
     * @return void
     */
    public function invalidateStatsCache(?string $statsType = null): void
    {
        if ($statsType !== null) {
            // Invalider un type spécifique de stats
            $this->cacheStats->deleteItem("stats_{$statsType}_*");
        } else {
            $this->cacheStats->clear();
        }
    }

    /**
     * Récupère une valeur du cache générique.
     * 
     * @param string $key Clé du cache
     * @param callable $callback Fonction qui retourne la valeur si non en cache
     * @param int|null $ttl Durée de vie en secondes (null = pas d'expiration)
     * @return mixed
     */
    public function get(string $key, callable $callback, ?int $ttl = null): mixed
    {
        return $this->cache->get(
            $key,
            function (ItemInterface $item) use ($callback, $ttl) {
                if ($ttl !== null) {
                    $item->expiresAfter($ttl);
                }
                return $callback();
            }
        );
    }

    /**
     * Supprime une clé du cache.
     * 
     * @param string $key Clé à supprimer
     * @return bool
     */
    public function delete(string $key): bool
    {
        return $this->cache->delete($key);
    }

    /**
     * Vide tout le cache.
     * 
     * @return bool
     */
    public function clear(): bool
    {
        return $this->cache->clear();
    }
}
