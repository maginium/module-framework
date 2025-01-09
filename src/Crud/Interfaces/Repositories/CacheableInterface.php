<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Interfaces\Repositories;

/**
 * Interface CacheableInterface
 * Defines the contract for managing caching in a repository.
 */
interface CacheableInterface
{
    /**
     * Set the cache lifetime for the repository.
     *
     * @param int $cacheLifetime The duration (in seconds) for which cache will be valid.
     *
     * @return $this Self instance for method chaining.
     */
    public function setCacheLifetime(int $cacheLifetime): static;

    /**
     * Get the current cache lifetime for the repository.
     *
     * @return int The cache duration in seconds.
     */
    public function getCacheLifetime(): int;

    /**
     * Enable or disable cache clearing for the repository.
     *
     * @param bool $status True to enable cache clearing, false to disable it.
     *
     * @return $this Self instance for method chaining.
     */
    public function enableCacheClear(bool $status): static;

    /**
     * Determine if cache clearing is enabled for the repository.
     *
     * @return bool True if cache clearing is enabled, false otherwise.
     */
    public function isCacheClearEnabled(): bool;

    /**
     * Clear the repository cache.
     *
     * @return $this Self instance for method chaining.
     */
    public function forgetCache(): static;
}
