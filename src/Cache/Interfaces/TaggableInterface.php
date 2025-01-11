<?php

declare(strict_types=1);

namespace Maginium\Framework\Cache\Interfaces;

/**
 * Interface Taggable.
 *
 * This interface defines the contract for a cache store implementation.
 * It provides methods to interact with the cache, including retrieving and storing items,
 * along with support for cache item expiration and other cache management features.
 * Implementing classes must provide the specific details of how cache operations are performed.
 */
interface TaggableInterface
{
    /**
     * Remove all entries associated with specific tags from the cache.
     *
     * This method will remove all cache entries that are associated with the specified
     * tags. This allows for more granular cache invalidation by removing only the entries
     * associated with certain tags, while leaving other cache entries intact.
     *
     *
     * @return bool Returns true if the cache was successfully flushed for the given tags, false otherwise.
     */
    public function flushTags(array $tags): bool;
}
