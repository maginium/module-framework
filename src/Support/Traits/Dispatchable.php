<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Traits;

use Maginium\Framework\Support\Facades\Event;

/**
 * Trait Dispatcher.
 *
 * Provides convenient methods to dispatch events using the Magento 2 Event Manager.
 */
trait Dispatchable
{
    /**
     * Dispatches the event with the given arguments if the given truth test passes.
     *
     * @param bool $boolean The condition to check.
     * @param mixed ...$arguments The event name and data associated with the event.
     *
     * @return void
     */
    public static function dispatchIf(bool $boolean, ...$arguments)
    {
        if ($boolean) {
            [$eventName, $data] = $arguments;

            return Event::dispatchNow($eventName, $data);
        }
    }

    /**
     * Dispatches the event with the given arguments unless the given truth test passes.
     *
     * @param bool $boolean The condition to check.
     * @param mixed ...$arguments The event name and data associated with the event.
     *
     * @return void
     */
    public static function dispatchUnless(bool $boolean, ...$arguments)
    {
        if (! $boolean) {
            [$eventName, $data] = $arguments;

            return Event::dispatchNow($eventName, $data);
        }
    }

    /**
     * Dispatches an event using the Event facade.
     *
     * @param string $eventName The name of the event.
     * @param array<string> $data Optional. Data associated with the event.
     */
    public function dispatch(string $eventName, array $data = []): void
    {
        Event::dispatch($eventName, $data);
    }

    /**
     * Dispatches an event immediately without queueing using the Event facade.
     *
     * @param string $eventName The name of the event.
     * @param array<string> $data Optional. Data associated with the event.
     */
    public function dispatchNow(string $eventName, array $data = []): void
    {
        Event::dispatchNow($eventName, $data);
    }

    /**
     * Checks if there are Observers for a specific event using the Event facade.
     *
     * @param string $eventName The name of the event.
     */
    public function hasObservers(string $eventName): bool
    {
        return Event::hasObservers($eventName);
    }

    /**
     * Retrieves all event Observers for a specific event using the Event facade.
     *
     * @param string $eventName The name of the event.
     *
     * @return array<string>
     */
    public function getObservers(string $eventName): array
    {
        return Event::getObservers($eventName);
    }
}
