<?php

declare(strict_types=1);

namespace Maginium\Framework\Cache\Concerns;

use Magento\Framework\Cache\FrontendInterface;

/**
 * Trait HasLegacyCode.
 *
 * Provides legacy cache management methods for interacting with a caching system.
 * This trait encapsulates common cache operations such as loading, saving,
 * removing, and cleaning cached data, making it reusable across different classes.
 *
 * Consuming classes must implement the `store()` method to provide access
 * to the underlying cache system.
 */
trait HasLegacyCode
{
    /**
     * Placeholder for accessing the underlying cache store instance.
     *
     * Implementing classes must provide this method to enable interaction with
     * the cache system.
     *
     * @return mixed The cache store instance.
     */
    abstract protected function store();

    /**
     * Save data to the cache with a specific identifier and optional tags and lifetime.
     *
     * @param string $data The data to be cached.
     * @param string $identifier The unique identifier for the cached data.
     * @param array $tags Optional tags for organizing the cached data.
     * @param int|null $lifeTime The lifetime of the cached data in seconds (null for default).
     *
     * @return bool True if the data was successfully cached, false otherwise.
     */
    public function save(string $data, string $identifier, array $tags = [], ?int $lifeTime = null): bool
    {
        return $this->store()->put($data, $identifier, $lifeTime, $tags);
    }

    /**
     * Remove cached data using the specified identifier.
     *
     * @param string $identifier The unique identifier for the cached data.
     *
     * @return bool True if the data was successfully removed, false otherwise.
     */
    public function remove(string $identifier): bool
    {
        return $this->store()->forget($identifier);
    }

    /**
     * Retrieve the cache frontend API object.
     *
     * This provides access to the underlying cache system's frontend
     * interface for managing cached data.
     *
     * @return FrontendInterface The cache frontend instance.
     */
    public function getFrontend(): FrontendInterface
    {
        return $this->store()->getFrontend();
    }

    /**
     * Load data from the cache using the specified identifier.
     *
     * @param string $identifier The unique identifier for the cached data.
     *
     * @return mixed The cached data, or null if no data is found.
     */
    public function load(string $identifier): mixed
    {
        return $this->store()->get($identifier);
    }

    /**
     * Clean cached data associated with specific tags.
     *
     * This removes all cached data linked to the provided tags.
     *
     * @param array $tags The tags identifying the data to be removed.
     *
     * @return bool True if the operation was successful, false otherwise.
     */
    public function clean(array $tags = []): bool
    {
        return $this->store()->cleanTags($tags);
    }
}
