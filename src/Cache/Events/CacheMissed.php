<?php

declare(strict_types=1);

namespace Maginium\Framework\Cache\Events;

/**
 * Class CacheMissed.
 *
 * Represents a cache miss event, indicating that the requested cache key was not found in the cache store.
 * Extends the base CacheEvent class without additional properties.
 */
class CacheMissed extends CacheEvent
{
}
