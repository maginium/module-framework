<?php

declare(strict_types=1);

namespace Maginium\Framework\Cache\Events;

/**
 * Class WritingKey.
 *
 * Represents an event triggered when a cache key is about to be written.
 */
class WritingKey extends CacheEvent
{
    /**
     * The value that will be written.
     *
     * @var mixed
     */
    public $value;

    /**
     * The number of seconds the key should be valid.
     *
     * @var int|null
     */
    public $seconds;

    /**
     * Create a new event instance.
     *
     * @param  string  $key  The cache key to be written.
     * @param  mixed  $value  The value to be written to the cache.
     * @param  string|null  $storeName  The name of the cache store.
     * @param  array  $tags  Tags associated with the cache key.
     * @param  int|null  $seconds  The expiration time in seconds (optional).
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
