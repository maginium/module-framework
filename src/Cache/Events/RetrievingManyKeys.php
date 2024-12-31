<?php

declare(strict_types=1);

namespace Maginium\Framework\Cache\Events;

/**
 * Class RetrievingManyKeys.
 *
 * Represents an event triggered when multiple cache keys are being retrieved.
 */
class RetrievingManyKeys extends CacheEvent
{
    /**
     * The keys that are being retrieved.
     *
     * @var string[]
     */
    public array $keys;

    /**
     * Create a new retrieving many keys event instance.
     *
     * @param  string[]  $keys  The keys being retrieved from the cache.
     * @param  string|null  $storeName  The name of the cache store.
     * @param  string[]  $tags  The tags associated with the cache keys.
     */
    public function __construct(
        array $keys,
        ?string $storeName,
        array $tags = [],
    ) {
        parent::__construct($storeName, $keys[0] ?? '', $tags);

        $this->keys = $keys;
    }
}
