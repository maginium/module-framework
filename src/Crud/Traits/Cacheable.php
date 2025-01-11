<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Traits;

use Closure;
use Laravel\SerializableClosure\SerializableClosure as BaseSerializableClosure;
use Maginium\Foundation\Enums\CacheTTL;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Facades\Cache;
use Maginium\Framework\Support\Facades\Event;
use Maginium\Framework\Support\Facades\Json;
use Maginium\Framework\Support\Facades\SerializableClosure;
use Maginium\Framework\Support\Reflection;

/**
 * Trait Cacheable.
 *
 * Provides caching functionality for repositories, enabling the storage,
 * retrieval, and flushing of cache with customizable drivers and lifetimes.
 */
trait Cacheable
{
    /**
     * Constant for default cache tags.
     */
    public const CACHE_TAG = 'repository_cache';

    /**
     * Constant for cache tags related to entities.
     */
    public const ENTITY_CACHE_TAG = 'entity_cache';

    /**
     * Determines if cache clearing is enabled.
     *
     * @var bool True if cache clearing is allowed; false otherwise.
     */
    protected bool $cacheClearEnabled = true;

    /**
     * The repository cache lifetime in minutes.
     *
     * @var int|null Cache expiration time; `null` means default configuration.
     */
    protected ?int $cacheLifetime = CacheTTL::HOUR;

    /**
     * Set the cache lifetime.
     *
     * @param int|null $cacheLifetime Cache duration in minutes or `null` for default.
     *
     * @return $this Self instance for method chaining.
     */
    public function setCacheLifetime(?int $cacheLifetime): static
    {
        // Set the cache lifetime to the provided value or null if unset.
        $this->cacheLifetime = $cacheLifetime;

        // Return the current instance to allow method chaining.
        return $this;
    }

    /**
     * Get the cache lifetime.
     *
     * @return int Cache lifetime in minutes or default value from the configuration.
     */
    public function getCacheLifetime(): int
    {
        // Return the cache lifetime if it is explicitly set; otherwise, retrieve the default value from configuration.
        return $this->cacheLifetime;
    }

    /**
     * Enable or disable cache clearing.
     *
     * @param bool $status Enable (`true`) or disable (`false`) cache clearing.
     *
     * @return $this Self instance for method chaining.
     */
    public function enableCacheClear(bool $status = true): static
    {
        // Update the cache clearing status based on the provided value.
        $this->cacheClearEnabled = $status;

        // Return the current instance to allow method chaining.
        return $this;
    }

    /**
     * Check if cache clearing is enabled.
     *
     * @return bool True if cache clearing is enabled; false otherwise.
     */
    public function isCacheClearEnabled(): bool
    {
        // Return whether cache clearing is enabled or not.
        return $this->cacheClearEnabled;
    }

    /**
     * Forget all cache related to the repository.
     *
     * @return $this Self instance for method chaining.
     */
    public function forgetCache(): static
    {
        // Proceed with cache clearing only if a cache lifetime is defined.
        if ($this->getCacheLifetime()) {
            // Check if the cache driver supports tagged cache clearing.
            if (Reflection::methodExists(Cache::getStore(), 'tags')) {
                // Use cache tags to flush all cache entries related to the repository.
                Cache::tags($this->getRepositoryId())->flush();
            }

            // Dispatch an event indicating that the cache has been flushed for the repository.
            Event::dispatch("{$this->getRepositoryId()}.entity.cache.flushed", [$this]);
        }

        // Return the current instance to allow method chaining.
        return $this;
    }

    /**
     * Generate a unique hash for a cache query.
     *
     * @param array $args Query parameters to include in the hash.
     *
     * @return string Unique hash string.
     */
    protected function generateCacheHash(array $args): string
    {
        // Merge the provided query parameters with the repository's state
        // and generate a unique MD5 hash for identifying the cache entry.
        return md5(Json::encode(Arr::merge($args, [
            $this->getRepositoryId(), // Unique identifier for the repository.
            $this->getModel(), // The model associated with the repository.
            $this->getCacheLifetime(), // The cache lifetime setting.
            $this->relations, // Relations that might affect the query result.
            $this->where, // WHERE conditions applied to the query.
            $this->whereIn, // WHERE IN conditions applied to the query.
            $this->whereNotIn, // WHERE NOT IN conditions applied to the query.
            $this->offset, // Offset for pagination.
            $this->limit, // Limit for pagination.
            $this->orderBy, // Order by clauses applied to the query.
        ])));
    }

    /**
     * Execute and cache the result of a given callback.
     *
     * @param string $class Class name of the repository.
     * @param string $method Method name being executed.
     * @param array $args Method arguments to generate the cache key.
     * @param Closure $closure Closure to execute and cache its result.
     * @param array|null $tags Optional array of cache tags.
     *
     * @return mixed Result of the closure execution.
     */
    protected function cacheCallback(string $class, string $method, array $args, Closure $closure, ?array $tags = null)
    {
        // Retrieve the unique identifier for the repository.
        $repositoryId = $this->getRepositoryId();

        // Get the total cache lifetime (e.g., 3600 seconds).
        $lifetime = $this->getCacheLifetime();

        // Construct the value as SerializableClosure
        $callback = fn(): BaseSerializableClosure => SerializableClosure::make($closure);

        // Set default tags if none are provided, including the new `ENTITY_CACHE_TAG`
        $tags ??= [self::CACHE_TAG, self::ENTITY_CACHE_TAG, $repositoryId];

        // If the lifetime is indefinite (-1), use rememberForever.
        if ($lifetime === -1) {
            return Cache::tags($tags)->rememberForever("{$class}@{$method}.{$this->generateCacheHash($args)}", $callback);
        }

        // Split the total lifetime into fresh and stale periods.
        $freshPeriod = (int)($lifetime * 0.75); // 75% fresh
        $stalePeriod = $lifetime - $freshPeriod; // Remaining 25% stale

        $lifetimeArray = [$freshPeriod, $stalePeriod];

        // Generate a unique cache key.
        $cacheKey = "{$class}@{$method}.{$this->generateCacheHash($args)}";

        // Cache the result using the flexible method.
        $result = Cache::tags($tags)->flexible($cacheKey, $lifetimeArray, $callback);

        // Reset the cached repository state to default values after caching.
        $this->resetCachedRepository();

        // Return the result of the cached closure execution.
        return $result;
    }

    /**
     * Reset cached repository state.
     *
     * This resets cache-specific properties such as cache lifetime and driver.
     *
     * @return $this Self instance for method chaining.
     */
    protected function resetCachedRepository(): static
    {
        // Reset the repository to its default state.
        $this->resetRepository();

        // Clear the cache-specific properties to avoid interference with future operations.
        $this->cacheLifetime = null;

        // Return the current instance for method chaining.
        return $this;
    }
}
