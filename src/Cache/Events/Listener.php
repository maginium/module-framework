<?php

declare(strict_types=1);

namespace Maginium\Framework\Cache\Events;

use Maginium\Foundation\Abstracts\Observer\AbstractObserver;

/**
 * Class CacheMissed.
 *
 * Represents a cache miss event, indicating that the requested cache key was not found in the cache store.
 * Extends the base CacheEvent class without additional properties.
 */
class Listener extends AbstractObserver
{
    /**
     * Abstract method for handling the event logic.
     *
     * Subclasses must implement this method to define the specific actions to take
     * when the observer is triggered.
     *
     * @throws Exception If the handling logic encounters an error.
     */
    protected function handle(): void
    {
        dd('asdasd', $this->data->getTags());
    }
}
