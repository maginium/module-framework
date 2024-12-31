<?php

declare(strict_types=1);

namespace Maginium\Framework\Cache\Events;

/**
 * Class KeyForgetFailed.
 *
 * Represents an event triggered when a failure occurs while attempting to forget (delete) a cache key.
 * Extends the base CacheEvent class without additional properties.
 */
class KeyForgetFailed extends CacheEvent
{
}
