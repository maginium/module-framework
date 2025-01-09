<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Traits;

use Closure;
use Maginium\Foundation\Enums\CacheTTL;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Facades\Cache;
use Maginium\Framework\Support\Facades\Json;
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
     * The repository cache lifetime in minutes.
     *
     * @var int|null Cache expiration time; `null` means default configuration.
     */
    protected ?int $cacheLifetime = CacheTTL::HOUR;

    /**
     * The repository cache driver.
     *
     * @var string|null Cache driver name; `null` uses the default driver.
     */
    protected ?string $cacheDriver = null;

    /**
     * Determines if cache clearing is enabled.
     *
     * @var bool True if cache clearing is allowed; false otherwise.
     */
    protected bool $cacheClearEnabled = true;

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
            } else {
                // For cache drivers without tags, manually forget cache keys.
                foreach ($this->flushCacheKeys() as $cacheKey) {
                    Cache::forget($cacheKey);
                }
            }

            // Dispatch an event indicating that the cache has been flushed for the repository.
            $this->getContainer('events')->dispatch("{$this->getRepositoryId()}.entity.cache.flushed", [$this]);
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
     * Retrieve all stored cache keys from the keys file.
     *
     * @param string $file Path to the cache keys file.
     *
     * @return array List of stored cache keys.
     */
    protected function getCacheKeys(string $file): array
    {
        // Check if the cache keys file exists.
        // If not, create it and initialize it with an empty JSON array.
        if (! file_exists($file)) {
            file_put_contents($file, Json::encode([]));
        }

        // Read the content of the cache keys file and decode it into an array.
        // If decoding fails or the file is empty, return an empty array.
        return Json::decode(file_get_contents($file)) ?: [];
    }

    /**
     * Flush all cache keys for the repository.
     *
     * @return array List of flushed cache keys.
     */
    protected function flushCacheKeys(): array
    {
        // Initialize an empty array to store flushed keys.
        $flushedKeys = [];

        // Get the fully qualified class name of the current repository.
        $calledClass = static::class;

        // Retrieve cache configuration.
        $config = $this->getContainer('config')->get('rinvex.repository.cache');

        // Get the cache keys from the keys file.
        $cacheKeys = $this->getCacheKeys($config['keys_file']);

        // Check if the current repository has any associated cache keys.
        if (isset($cacheKeys[$calledClass]) && is_array($cacheKeys[$calledClass])) {
            // Loop through each cache key and add it to the list of flushed keys.
            foreach ($cacheKeys[$calledClass] as $cacheKey) {
                $flushedKeys[] = "{$calledClass}@{$cacheKey}";
            }

            // Remove the current repository's cache keys from the file.
            unset($cacheKeys[$calledClass]);

            // Update the keys file.
            file_put_contents($config['keys_file'], Json::encode($cacheKeys));
        }

        // Return the list of flushed keys.
        return $flushedKeys;
    }

    /**
     * Execute and cache the result of a given callback.
     *
     * @param string $class Class name of the repository.
     * @param string $method Method name being executed.
     * @param array $args Method arguments to generate the cache key.
     * @param Closure $closure Closure to execute and cache its result.
     *
     * @return mixed Result of the closure execution.
     */
    protected function cacheCallback(string $class, string $method, array $args, Closure $closure)
    {
        // Retrieve the unique identifier for the repository.
        $repositoryId = $this->getRepositoryId();

        // Get the cache lifetime setting.
        $lifetime = $this->getCacheLifetime();

        // Generate a unique hash for the current method and arguments.
        $hash = $this->generateCacheHash($args);

        // Create a unique cache key based on the class, method, and hash.
        $cacheKey = "{$class}@{$method}.{$hash}";

        // Cache the result using tags.
        $result = $lifetime === -1
            ? Cache::rememberForever($cacheKey, $closure, [$repositoryId])
            : Cache::remember($cacheKey, $lifetime, $closure, [$repositoryId]);

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
        $this->cacheDriver = null;

        // Return the current instance for method chaining.
        return $this;
    }
}
