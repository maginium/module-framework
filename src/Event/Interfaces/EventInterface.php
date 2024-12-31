<?php

declare(strict_types=1);

namespace Maginium\Framework\Event\Interfaces;

/**
 * Interface EventInterface.
 *
 * Provides an interface for dispatching events and retrieving observers.
 */
interface EventInterface
{
    /**
     * Dispatches an event.
     *
     * @param string $eventName The name of the event.
     * @param array $data Optional. Data associated with the event.
     */
    public function dispatch(string $eventName, array $data = []): void;

    /**
     * Dispatches an event immediately without queueing.
     *
     * @param string $eventName The name of the event.
     * @param array $data Optional. Data associated with the event.
     */
    public function dispatchNow(string $eventName, array $data = []): void;

    /**
     * Dispatches the event with the given arguments if the given truth test passes.
     *
     * @param bool $boolean The condition to check.
     * @param mixed ...$arguments The event name and data associated with the event.
     */
    public function dispatchIf(bool $boolean, ...$arguments): void;

    /**
     * Dispatches the event with the given arguments unless the given truth test passes.
     *
     * @param bool $boolean The condition to check.
     * @param mixed ...$arguments The event name and data associated with the event.
     */
    public function dispatchUnless(bool $boolean, ...$arguments): void;

    /**
     * Checks if there are Observers for a specific event.
     *
     * @param string $eventName The name of the event.
     */
    public function hasObservers(string $eventName): bool;

    /**
     * Retrieve all event Observers for a specific event.
     *
     * @param string $eventName The name of the event.
     */
    public function getObservers(string $eventName): array;
}
