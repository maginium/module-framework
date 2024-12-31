<?php

declare(strict_types=1);

namespace Maginium\Framework\Cache\Events;

/**
 * Class KeyWritten.
 *
 * Represents an event triggered when a cache key is successfully written.
 * Includes details about the written value and its intended expiration time.
 */
class KeyWritten extends CacheEvent
{
    /**
     * The value that was written to the cache.
     */
    private mixed $value;

    /**
     * The number of seconds the key should be valid.
     */
    private ?int $seconds;

    /**
     * Create a new key written event instance.
     *
     * @param  string  $key  The key for the cache entry that was written.
     * @param  mixed  $value  The value that was stored in the cache.
     * @param  string|null  $storeName  The name of the cache store.
     * @param  string[]  $tags  The tags associated with the cache key.
     * @param  int|null  $seconds  The time-to-live (TTL) for the cache entry, in seconds.
     */
    public function __construct(
        string $key,
        mixed $value,
        ?string $storeName,
        array $tags = [],
        ?int $seconds = null,
    ) {
        parent::__construct($storeName, $key, $tags);

        $this->value = $value;
        $this->seconds = $seconds;
    }
}
