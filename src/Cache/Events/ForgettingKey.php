<?php

declare(strict_types=1);

namespace Maginium\Framework\Cache\Events;

/**
 * Class ForgettingKey.
 *
 * Represents an event triggered when a cache key is about to be forgotten (deleted) from the cache store.
 * Extends the base CacheEvent class without additional properties.
 */
class ForgettingKey extends CacheEvent
{
}
