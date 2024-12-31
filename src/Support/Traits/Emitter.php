<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Traits;

use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Validator;

/**
 * Emitter adds event-related features to any class.
 *
 * This trait allows classes to bind events to callbacks, trigger those events,
 * and manage event listeners, including one-time listeners.
 */
trait Emitter
{
    /**
     * @var array Collection of events that are bound to fire once only.
     */
    protected $emitterSingleEventCollection = [];

    /**
     * @var array Collection of all registered events and their callbacks.
     */
    protected $emitterEventCollection = [];

    /**
     * @var array Cached sorted collection of event listeners for efficiency.
     */
    protected $emitterEventSorted = [];

    /**
     * bindEvent creates a new event binding.
     *
     * This method allows you to bind a callback function to a specific event.
     * The callback can be executed later when the event is fired.
     *
     * @param string $event The name of the event to bind.
     * @param callable $callback The callback function to be executed when the event is triggered.
     * @param int $priority The priority of the event listener (default is 0).
     *
     * @return void
     */
    public function bindEvent(string $event, callable $callback, int $priority = 0): void
    {
        // Add the callback to the event collection under the specified priority.
        $this->emitterEventCollection[$event][$priority][] = $callback;

        // Clear the sorted cache for the event to ensure it's re-sorted when fired.
        unset($this->emitterEventSorted[$event]);
    }

    /**
     * bindEventOnce creates a new event binding that fires only once.
     *
     * This method binds a callback to an event that will only be triggered once.
     * After being executed, the listener will be removed automatically.
     *
     * @param string $event The name of the event to bind.
     * @param callable $callback The callback function to be executed when the event is triggered.
     * @param int $priority The priority of the event listener (default is 0).
     *
     * @return void
     */
    public function bindEventOnce(string $event, callable $callback, int $priority = 0): void
    {
        // Add the callback to the single event collection under the specified priority.
        $this->emitterSingleEventCollection[$event][$priority][] = $callback;

        // Clear the sorted cache for the event to ensure it's re-sorted when fired.
        unset($this->emitterEventSorted[$event]);
    }

    /**
     * unbindEvent destroys an event binding.
     *
     * This method allows you to remove a specific event binding or all bindings if no event is provided.
     * It can also accept an array of event names to unbind multiple events at once.
     *
     * @param string|array|null $event The event name(s) to unbind or null to unbind all.
     *
     * @return void
     */
    public function unbindEvent($event = null): void
    {
        // If an array of events is provided, recursively unbind each event.
        if (Validator::isArray($event)) {
            foreach ($event as $_event) {
                $this->unbindEvent($_event);
            }

            return;
        }

        // If no specific event is provided, unset all collections.
        if ($event === null) {
            unset($this->emitterSingleEventCollection, $this->emitterEventCollection, $this->emitterEventSorted);

            return;
        }

        // Unset the specific event from all collections.
        unset($this->emitterSingleEventCollection[$event], $this->emitterEventCollection[$event], $this->emitterEventSorted[$event]);
    }

    /**
     * fireEvent triggers and calls the listeners for a specified event.
     *
     * This method executes all callbacks associated with an event,
     * passing any parameters to the callbacks. It can also halt execution
     * after the first non-null response if specified.
     *
     * @param string $event Event name
     * @param array $params Event parameters to be passed to the callbacks
     * @param bool $halt Whether to halt after the first non-null result
     *
     * @return array|null Collection of results from the event callbacks, or a single result if halted.
     */
    public function fireEvent(string $event, array $params = [], bool $halt = false)
    {
        // Ensure parameters are always an array.
        if (! Validator::isArray($params)) {
            $params = [$params];
        }

        // Micro optimization: check if the event has any listeners registered.
        if (
            ! isset($this->emitterEventCollection[$event]) &&
            ! isset($this->emitterSingleEventCollection[$event])
        ) {
            // If no listeners are found, return null or an empty array based on halt parameter.
            return $halt ? null : [];
        }

        // Check if the event has already been sorted, if not, sort the listeners.
        if (! isset($this->emitterEventSorted[$event])) {
            $this->emitterEventSorted[$event] = $this->emitterEventSortEvents($event);
        }

        // Initialize an array to hold the results from callbacks.
        $result = [];

        // Iterate over the sorted callbacks for the event.
        foreach ($this->emitterEventSorted[$event] as $callback) {
            // Call the callback with the provided parameters and store the response.
            $response = $callback(...$params);

            // If a non-null response is returned and halting is enabled, return the response immediately.
            if ($response !== null && $halt) {
                return $response;
            }

            // If the response is false, break out of the loop (indicating to stop further callbacks).
            if ($response === false) {
                break;
            }

            // If the response is not null, add it to the results array.
            if ($response !== null) {
                $result[] = $response;
            }
        }

        // If there are any one-time event listeners for this event, remove them after execution.
        if (isset($this->emitterSingleEventCollection[$event])) {
            unset($this->emitterSingleEventCollection[$event], $this->emitterEventSorted[$event]);
        }

        // Return the results, or null if halted.
        return $halt ? null : $result;
    }

    /**
     * emitterEventSortEvents sorts the listeners for a given event by priority.
     *
     * This method collects all callbacks for an event, combines them by priority,
     * and sorts them in descending order (higher priorities first) for execution.
     *
     * @param string $eventName The name of the event to sort.
     * @param array $combined An array to combine event callbacks (default is empty).
     *
     * @return array Sorted array of callbacks.
     */
    protected function emitterEventSortEvents(string $eventName, array $combined = []): array
    {
        // Check if there are any regular event listeners for the specified event.
        if (isset($this->emitterEventCollection[$eventName])) {
            foreach ($this->emitterEventCollection[$eventName] as $priority => $callbacks) {
                // Merge callbacks by their priority level.
                $combined[$priority] = Arr::merge($combined[$priority] ?? [], $callbacks);
            }
        }

        // Check if there are any single-use event listeners for the specified event.
        if (isset($this->emitterSingleEventCollection[$eventName])) {
            foreach ($this->emitterSingleEventCollection[$eventName] as $priority => $callbacks) {
                // Merge callbacks by their priority level.
                $combined[$priority] = Arr::merge($combined[$priority] ?? [], $callbacks);
            }
        }

        // Sort the combined array by priority in descending order.
        krsort($combined);

        // Return a flattened array of all callbacks sorted by priority.
        return call_user_func_array('array_merge', $combined);
    }
}
