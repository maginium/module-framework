<?php

declare(strict_types=1);

namespace Maginium\Framework\Console\Scheduling;

use Illuminate\Console\Scheduling\CacheEventMutex as BaseCacheEventMutex;
use Maginium\Framework\Cache\Interfaces\FactoryInterface;
use Maginium\Framework\Console\Interfaces\EventMutexInterface;

/**
 * Class CacheEventMutex.
 *
 * This class extends the functionality of the base CacheEventMutex to implement
 * the EventMutexInterface, allowing for more flexibility in how event mutexes are handled.
 * It overrides the necessary methods to create, check existence, and forget event mutexes
 * while maintaining compatibility with the base implementation.
 */
class CacheEventMutex extends BaseCacheEventMutex implements EventMutexInterface
{
    /**
     * Create a new overlapping strategy.
     *
     * @param  FactoryInterface  $cache
     *
     * @return void
     */
    public function __construct(FactoryInterface $cache)
    {
        parent::__construct($cache);
    }

    /**
     * Attempt to obtain an event mutex for the given event.
     *
     * This method overrides the parent method and calls the base class's `create` method
     * to attempt acquiring a mutex for the event. It ensures that the event mutex is created
     * if it doesn't already exist, preventing the same event from running concurrently.
     *
     * @param  Event  $event The event for which the mutex is being created
     *
     * @return bool Returns `true` if the mutex was created successfully, otherwise `false`
     */
    public function create($event): bool
    {
        // Delegate to the parent method to attempt creating the mutex
        return parent::create($event);
    }

    /**
     * Determine if an event mutex exists for the given event.
     *
     * This method checks if an event mutex already exists for the provided event.
     * It overrides the parent method to check the cache or storage mechanism to see
     * if the event is already locked, preventing multiple executions.
     *
     * @param  Event  $event The event for which the mutex existence is being checked
     *
     * @return bool Returns `true` if the mutex exists, `false` otherwise
     */
    public function exists($event): bool
    {
        // Delegate to the parent method to check if the mutex exists
        return parent::exists($event);
    }

    /**
     * Clear the event mutex for the given event.
     *
     * This method removes the event mutex from storage, allowing the event to be
     * executed again. It overrides the parent method to handle the mutex removal.
     *
     * @param  Event  $event The event whose mutex is to be cleared
     *
     * @return void
     */
    public function forget($event): void
    {
        // Delegate to the parent method to remove the event mutex
        parent::forget($event);
    }
}
