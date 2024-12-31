<?php

declare(strict_types=1);

namespace Maginium\Framework\Cache\Events;

/**
 * Class KeyForgotten.
 *
 * Represents an event triggered when a cache key is successfully forgotten (deleted).
 * Extends the base CacheEvent class without additional properties.
 */
class KeyForgotten extends CacheEvent
{
}
