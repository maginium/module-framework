<?php

declare(strict_types=1);

namespace Maginium\Framework\Cache\Events;

/**
 * Class WritingManyKeys.
 *
 * Represents an event triggered when multiple cache keys are about to be written.
 */
class WritingManyKeys extends CacheEvent
{
    /**
     * The keys that are being written.
     *
     * @var array
     */
    public $keys;

    /**
     * The values that are being written for the keys.
     *
     * @var array
     */
    public $values;

    /**
     * The number of seconds the keys should be valid.
     *
     * @var int|null
     */
    public $seconds;

    /**
     * Create a new event instance.
     *
     * @param  array  $keys  The cache keys being written.
     * @param  array  $values  The values to be written for the respective keys.
     * @param  string|null  $storeName  The name of the cache store.
     * @param  array  $tags  Tags associated with the cache keys.
     * @param  int|null  $seconds  The expiration time in seconds (optional).
     */
    public function __construct(
        array $keys,
        array $values,
        ?string $storeName,
        array $tags = [],
        ?int $seconds = null,
    ) {
        parent::__construct($storeName, $keys[0] ?? '', $tags);

        $this->keys = $keys;
        $this->values = $values;
        $this->seconds = $seconds;
    }
}
