<?php

declare(strict_types=1);

namespace Maginium\Framework\Cache\Events;

/**
 * Class CacheHit.
 *
 * Represents a cache hit event, including the value retrieved from the cache.
 * Extends the base CacheEvent class, providing additional context about the cached value.
 */
class CacheHit extends CacheEvent
{
    /**
     * The value that was retrieved from the cache.
     */
    private mixed $value;

    /**
     * Create a new cache hit event instance.
     *
     * @param  string|null  $storeName  The name of the cache store.
     * @param  string  $key  The key that was accessed.
     * @param  mixed  $value  The value that was retrieved.
     * @param  string[]  $tags  The tags assigned to the cache key.
     */
    public function __construct(?string $storeName, string $key, mixed $value, array $tags = [])
    {
        parent::__construct($storeName, $key, $tags);

        $this->value = $value;
    }
}
