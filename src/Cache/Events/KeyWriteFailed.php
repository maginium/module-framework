<?php

declare(strict_types=1);

namespace Maginium\Framework\Cache\Events;

/**
 * Class KeyWriteFailed.
 *
 * Represents an event triggered when a cache key write operation fails.
 * Includes additional details about the value that was to be written and its intended expiration time.
 */
class KeyWriteFailed extends CacheEvent
{
    /**
     * The value that would have been written to the cache.
     */
    private mixed $value;

    /**
     * The number of seconds the key should have been valid.
     */
    private ?int $seconds;

    /**
     * Create a new key write failed event instance.
     *
     * @param  string  $key  The key for the cache entry that failed to write.
     * @param  mixed  $value  The value that was to be stored in the cache.
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
