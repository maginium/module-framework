<?php

declare(strict_types=1);

namespace Maginium\Framework\Console\Scheduling;

use Closure;
use DateTimeZone;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\TransferException;
use Illuminate\Console\Scheduling\Event as BaseEvent;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Arr;
use Illuminate\Support\Stringable;
use Maginium\Framework\Console\Interfaces\EventMutexInterface;
use Maginium\Framework\Console\Schedule\CommandBuilder;
use Maginium\Framework\Container\Interfaces\ContainerInterface;
use Maginium\Framework\Support\Facades\Date;
use Maginium\Framework\Support\Str;
use Maginium\Framework\Support\Validator;
use Psr\Http\Client\ClientExceptionInterface;
use Throwable;

/**
 * Class Event.
 *
 * Extends the base scheduling event to provide additional features,
 * including callbacks and enhanced error handling for scheduled tasks.
 */
class Event extends BaseEvent
{
    /**
     * The command builder instance.
     *
     * @var CommandBuilder
     */
    protected CommandBuilder $commandBuilder;

    /**
     * Create a new event instance.
     *
     * @param  CommandBuilder  $commandBuilder
     * @param  EventMutexInterface  $mutex
     * @param  string  $command
     * @param  DateTimeZone|string|null  $timezone
     */
    public function __construct(
        string $command,
        EventMutexInterface $mutex,
        CommandBuilder $commandBuilder,
        $timezone = null,
    ) {
        parent::__construct($mutex, $command, $timezone);

        $this->commandBuilder = $commandBuilder;
    }

    /**
     * Execute the event, including any defined callbacks.
     *
     * @param  ContainerInterface  $container The dependency injection container.
     *
     * @throws Throwable If the event fails to execute.
     *
     * @return mixed
     */
    public function run($container)
    {
        // Skip execution if the event should not run due to overlapping conditions.
        if ($this->shouldSkipDueToOverlapping()) {
            return;
        }

        // Start the event execution and retrieve the exit code.
        $exitCode = $this->start($container);

        // If the event is not set to run in the background, finish execution.
        if (! $this->runInBackground) {
            $this->finish($container, $exitCode);
        }
    }

    /**
     * Mark the event as completed and handle post-execution tasks.
     *
     * This method marks the event as completed and performs any necessary post-execution tasks,
     * including invoking registered "after" callbacks. It also ensures that the mutex lock is removed
     * regardless of the execution outcome.
     *
     * @param  ContainerInterface  $container The dependency injection container.
     * @param  int  $exitCode The exit code from the event execution.
     *
     * @return void
     */
    public function finish($container, $exitCode): void
    {
        $this->exitCode = $exitCode;

        try {
            // Execute any "after" callbacks registered for the event.
            $this->callAfterCallbacks($container);
        } finally {
            // Ensure that the mutex lock is removed regardless of outcome.
            $this->removeMutex();
        }
    }

    /**
     * Execute all registered "before" callbacks.
     *
     * This method executes all "before" callbacks registered for the event. These callbacks
     * are invoked before the main task execution begins.
     *
     * @param  ContainerInterface  $container The dependency injection container.
     *
     * @return void
     */
    public function callBeforeCallbacks($container): void
    {
        foreach ($this->beforeCallbacks as $callback) {
            $container->call($callback);
        }
    }

    /**
     * Execute all registered "after" callbacks.
     *
     * This method executes all "after" callbacks registered for the event. These callbacks
     * are invoked after the main task execution completes.
     *
     * @param  ContainerInterface  $container The dependency injection container.
     *
     * @return void
     */
    public function callAfterCallbacks($container): void
    {
        foreach ($this->afterCallbacks as $callback) {
            $container->call($callback);
        }
    }

    /**
     * Register a success callback to be executed if the event completes successfully.
     *
     * This method registers a callback that will be invoked if the event completes successfully.
     * If the callback requires output, it will be handled appropriately.
     *
     * @param  Closure  $callback The callback to execute on success.
     *
     * @return $this
     */
    public function onSuccess(Closure $callback): self
    {        // Determine the callback's parameter types.
        $parameters = $this->closureParameterTypes($callback);

        // If the callback expects output, register with output handling.
        if (Arr::get($parameters, 'output') === Stringable::class) {
            return $this->onSuccessWithOutput($callback);
        }

        // Otherwise, register a standard success callback.
        return $this->then(function($container) use ($callback) {
            if ($this->exitCode === 0) {
                $container->call($callback);
            }
        });
    }

    /**
     * Register a failure callback to be executed if the event fails.
     *
     * This method registers a callback that will be invoked if the event fails. If the callback
     * requires output, it will be handled appropriately.
     *
     * @param  Closure  $callback The callback to execute on failure.
     *
     * @return $this
     */
    public function onFailure(Closure $callback): self
    {        // Determine the callback's parameter types.
        $parameters = $this->closureParameterTypes($callback);

        // If the callback expects output, register with output handling.
        if (Arr::get($parameters, 'output') === Stringable::class) {
            return $this->onFailureWithOutput($callback);
        }

        // Otherwise, register a standard failure callback.
        return $this->then(function($container) use ($callback) {
            if ($this->exitCode !== 0) {
                $container->call($callback);
            }
        });
    }

    /**
     * Determine if the given event should run based on the Cron expression.
     *
     * This method checks whether the event should run by verifying the Cron expression and
     * other conditions such as maintenance mode and environment.
     *
     * @param  Application  $app The application instance.
     *
     * @return bool Returns true if the event is due to run, false otherwise.
     */
    public function isDue($app): bool
    {
        if (! $this->runsInMaintenanceMode() && $app->isDownForMaintenance()) {
            return false;
        }

        return $this->expressionPasses() &&
               $this->runsInEnvironment($app->environment());
    }

    /**
     * Determine if the filters pass for the event.
     *
     * This method checks whether all filters for the event pass. It evaluates all the "before"
     * filters and "reject" filters, returning true if they all pass.
     *
     * @param  Application  $app The application instance.
     *
     * @return bool Returns true if all filters pass, false otherwise.
     */
    public function filtersPass($app): bool
    {
        $this->lastChecked = Date::now();

        foreach ($this->filters as $callback) {
            if (! $app->call($callback)) {
                return false;
            }
        }

        foreach ($this->rejects as $callback) {
            if ($app->call($callback)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the summary of the event for display.
     *
     * @return string
     */
    public function getSummaryForDisplay()
    {
        Validator::isString($this->description);

        if (Validator::isString($this->description)) {
            return $this->description;
        }

        return $this->buildCommand();
    }

    /**
     * Build the command string.
     *
     * @return string
     */
    public function buildCommand(): string
    {
        return $this->commandBuilder->buildCommand($this);
    }

    /**
     * Set the human-friendly description of the event.
     *
     * @param  string  $description
     *
     * @return $this
     */
    public function name($description)
    {
        return $this->description($description);
    }

    /**
     * Set the human-friendly description of the event.
     *
     * @param  string  $description
     *
     * @return $this
     */
    public function description($description)
    {
        $this->description = Str::lower($description);

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Start the event execution, including "before" callbacks and the main task.
     *
     * This method initiates the event execution process by calling all "before" callbacks
     * and then executing the main task. If an exception occurs during the execution, it will
     * be thrown for handling upstream.
     *
     * @param  ContainerInterface  $container The dependency injection container.
     *
     * @throws Throwable If execution fails.
     *
     * @return int The exit code of the execution.
     */
    protected function start($container): int
    {
        try {
            // Execute all "before" callbacks.
            $this->callBeforeCallbacks($container);

            // Run the main task and return the exit code.
            return $this->execute($container);
        } catch (Throwable $exception) {
            // Remove the mutex lock on failure.
            $this->removeMutex();

            // Re-throw the exception for upstream handling.
            throw $exception;
        }
    }

    /**
     * Generate a callback to ping a specified URL.
     *
     * This method generates a closure that performs a GET request to the given URL. If an error
     * occurs during the request, it will be reported using the exception handler.
     *
     * @param  string  $url The URL to ping.
     *
     * @return Closure A closure that performs the ping.
     */
    protected function pingCallback($url): Closure
    {
        return function($container, HttpClient $http) use ($url) {
            try {
                // Perform a GET request to the URL.
                $http->request('GET', $url);
            } catch (ClientExceptionInterface|TransferException $e) {
                // Report any exceptions encountered during the ping.
                $container->make(ExceptionHandler::class)->report($e);
            }
        };
    }

    /**
     * Generate a callback with output handling.
     *
     * This method generates a closure that wraps the provided callback and passes output
     * data to it. If output exists, it will be included in the callback execution.
     *
     * @param  Closure  $callback The callback to execute.
     * @param  bool  $onlyIfOutputExists Whether to invoke the callback only if output exists.
     *
     * @return Closure A closure with output handling logic.
     */
    protected function withOutputCallback($callback, $onlyIfOutputExists = false): Closure
    {
        return function($container) use ($callback, $onlyIfOutputExists) {
            // Retrieve the output from the designated output file, if available.
            $output = $this->output && is_file($this->output) ? file_get_contents($this->output) : '';

            // If output is required but does not exist, return null.
            return $onlyIfOutputExists && empty($output)
                ? null
                : $container->call($callback, ['output' => $container->make(Stringable::class, ['value' => $output])]);
        };
    }
}
