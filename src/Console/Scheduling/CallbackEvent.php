<?php

declare(strict_types=1);

namespace Maginium\Framework\Console\Scheduling;

use Closure;
use DateTimeZone;
use Illuminate\Support\Reflector;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Foundation\Exceptions\LogicException;
use Maginium\Foundation\Exceptions\RuntimeException;
use Maginium\Framework\Console\Interfaces\EventMutexInterface;
use Maginium\Framework\Container\Interfaces\ContainerInterface;
use Throwable;

class CallbackEvent extends Event
{
    /**
     * The callback to call.
     *
     * @var Closure|callable
     */
    protected $callback;

    /**
     * The parameters to pass to the method.
     *
     * @var array
     */
    protected array $parameters;

    /**
     * The result of the callback's execution.
     *
     * @var mixed
     */
    protected mixed $result;

    /**
     * The exception that was thrown when calling the callback, if any.
     *
     * @var Throwable|null
     */
    protected $exception;

    /**
     * Create a new event instance.
     *
     * Initializes the CallbackEvent instance, setting up the necessary properties for the callback, its parameters, and mutex.
     *
     * @param  EventMutexInterface  $mutex A mutex to lock events and prevent overlapping executions.
     * @param  string|callable  $callback The callback function or string to execute.
     * @param  array  $parameters The parameters to pass to the callback.
     * @param  DateTimeZone|string|null  $timezone The timezone for the event execution.
     *
     * @throws InvalidArgumentException If the callback is not a valid string or callable.
     *
     * @return void
     */
    public function __construct(EventMutexInterface $mutex, $callback, array $parameters = [], $timezone = null)
    {
        // Check if the callback is either a string or callable, throw exception otherwise.
        if (! is_string($callback) && ! Reflector::isCallable($callback)) {
            throw InvalidArgumentException::make(
                'Invalid scheduled callback event. Must be a string or callable.',
            );
        }

        $this->mutex = $mutex;
        $this->callback = $callback;
        $this->timezone = $timezone;
        $this->parameters = $parameters;
    }

    /**
     * Run the callback.
     *
     * Executes the scheduled callback using the provided container.
     *
     * @param  ContainerInterface  $container The container for resolving dependencies.
     *
     * @return int Returns 0 if the callback succeeds, 1 if it fails.
     */
    protected function execute($container)
    {
        try {
            // Check if the callback is an object method or a function.
            // For an object method, we invoke it using the container.
            $this->result = is_object($this->callback)
                ? $container->call([$this->callback, '__invoke'], $this->parameters) // Call method on object.
                : $container->call($this->callback, $this->parameters); // Call function.

            // Return success code (0) if no error, otherwise failure (1).
            return $this->result === false ? 1 : 0;
        } catch (Throwable $e) {
            // If an exception is caught, store it and return failure.
            $this->exception = $e;

            return 1;
        }
    }

    /**
     * Execute the event, including any defined callbacks.
     *
     * This method runs the event and checks for exceptions, throwing if necessary.
     *
     * @param  ContainerInterface  $container The dependency injection container.
     *
     * @throws Throwable If the event fails to execute due to an exception.
     *
     * @return mixed The result of the callback execution.
     */
    public function run($container): mixed
    {
        // Call the parent run method to perform any general event actions.
        parent::run($container);

        // If an exception was set during execution, throw it.
        if ($this->exception) {
            throw $this->exception;
        }

        // Return the result of the callback execution.
        return $this->result;
    }

    /**
     * Determine if the event should skip because another process is overlapping.
     *
     * This checks if the event description exists and if the parent method indicates skipping.
     *
     * @return bool Returns true if the event should skip, false otherwise.
     */
    public function shouldSkipDueToOverlapping()
    {
        return $this->description && parent::shouldSkipDueToOverlapping();
    }

    /**
     * Indicate that the callback should run in the background.
     *
     * Throws an exception since scheduled closures cannot run in the background.
     *
     * @throws RuntimeException Always thrown to prevent background execution of callbacks.
     *
     * @return void
     */
    public function runInBackground()
    {
        throw RuntimeException::make('Scheduled closures can not be run in the background.');
    }

    /**
     * Do not allow the event to overlap each other.
     *
     * Ensures the event cannot run concurrently on different processes.
     *
     * @param  int  $expiresAt The expiration time for the lock, in minutes.
     *
     * @throws LogicException If no event description is set before calling withoutOverlapping.
     *
     * @return $this The current CallbackEvent instance.
     */
    public function withoutOverlapping($expiresAt = 1440)
    {
        // Check if the description is set, as it's required to prevent overlapping.
        if (! isset($this->description)) {
            throw LogicException::make(
                "A scheduled event name is required to prevent overlapping. Use the 'name' method before 'withoutOverlapping'.",
            );
        }

        // Call the parent method to handle locking.
        return parent::withoutOverlapping($expiresAt);
    }

    /**
     * Allow the event to only run on one server for each cron expression.
     *
     * Ensures the event runs only on a single server even if the cron expression matches multiple servers.
     *
     * @throws LogicException If no event description is set before calling onOneServer.
     *
     * @return $this The current CallbackEvent instance.
     */
    public function onOneServer()
    {
        // Ensure an event description is set before using this method.
        if (! isset($this->description)) {
            throw LogicException::make(
                "A scheduled event name is required to only run on one server. Use the 'name' method before 'onOneServer'.",
            );
        }

        // Call the parent method to handle server-specific locking.
        return parent::onOneServer();
    }

    /**
     * Get the summary of the event for display.
     *
     * Returns a string summary of the event, either the description or callback information.
     *
     * @return string The summary description of the event.
     */
    public function getSummaryForDisplay()
    {
        // Return the description if it exists, otherwise return the callback as a string.
        if (is_string($this->description)) {
            return $this->description;
        }

        // Return the callback if it's a string, otherwise just return 'Callback'.
        return is_string($this->callback) ? $this->callback : 'Callback';
    }

    /**
     * Get the mutex name for the scheduled command.
     *
     * Generates a unique mutex name based on the event's description.
     *
     * @return string The generated mutex name.
     */
    public function mutexName()
    {
        // Return the unique mutex name by hashing the event's description.
        return 'framework/schedule-' . sha1($this->description ?? '');
    }

    /**
     * Clear the mutex for the event.
     *
     * Removes the mutex lock if the description exists.
     *
     * @return void
     */
    protected function removeMutex()
    {
        // If a description is set, clear the mutex using the parent method.
        if ($this->description) {
            parent::removeMutex();
        }
    }
}
