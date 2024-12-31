<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Maginium\Framework\Event\Interfaces\EventInterface;
use Maginium\Framework\Support\Facade;

/**
 * Facade for interacting with the Event service.
 *
 * This class acts as a simplified interface to access the EventInterface.
 * By extending AbstractFacade, it inherits basic functionality for service access.
 *
 *
 * @method static void dispatch(string $eventName, array $data = [])
 *     Dispatches an event.
 *     Parameters:
 *     - string $eventName: The name of the event to dispatch.
 *     - array $data: Optional data to pass to the event handlers.
 *     Returns:
 *     - void
 * @method static void dispatchNow(string $eventName, array $data = [])
 *     Dispatches an event immediately without queuing.
 *     Parameters:
 *     - string $eventName: The name of the event to dispatch immediately.
 *     - array $data: Optional data to pass to the event handlers.
 *     Returns:
 *     - void
 * @method static void dispatchIf(bool $boolean, string $eventName, array $data = [])
 *     Dispatches the event if the given condition is true.
 *     Parameters:
 *     - bool $boolean: The condition to check.
 *     - string $eventName: The name of the event to dispatch.
 *     - array $data: Optional data to pass to the event handlers.
 *     Returns:
 *     - void
 * @method static void dispatchUnless(bool $boolean, string $eventName, array $data = [])
 *     Dispatches the event unless the given condition is true.
 *     Parameters:
 *     - bool $boolean: The condition to check.
 *     - string $eventName: The name of the event to dispatch.
 *     - array $data: Optional data to pass to the event handlers.
 *     Returns:
 *     - void
 * @method static bool hasObservers(string $eventName)
 *     Checks if there are observers for a specific event.
 *     Parameters:
 *     - string $eventName: The name of the event to check for observers.
 *     Returns:
 *     - bool: True if there are observers registered for the event; false otherwise.
 * @method static array getObservers(string $eventName)
 *     Retrieves all event observers for a specific event.
 *     Parameters:
 *     - string $eventName: The name of the event to retrieve observers for.
 *     Returns:
 *     - array: Array of event observer instances.
 *
 * @see EventInterface
 */
class Event extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string The key to access the service.
     */
    protected static function getAccessor(): string
    {
        return EventInterface::class;
    }
}
