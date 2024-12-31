<?php

declare(strict_types=1);

namespace Maginium\Framework\Concurrency;

use Closure;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Framework\Concurrency\Interfaces\DriverInterface;
use Maginium\Framework\Defer\DeferredCallback;
use Maginium\Framework\Defer\Interfaces\DeferInterface;
use Maginium\Framework\Support\Arr;
use Throwable;

/**
 * Class SyncDriver.
 *
 * This class implements the Driver interface for executing tasks synchronously.
 * It provides methods to run multiple tasks concurrently and to defer their
 * execution until the current context has finished.
 */
class SyncDriver implements DriverInterface
{
    /**
     * The defer instance for handling deferred execution.
     */
    protected DeferInterface $defer;

    /**
     * SyncDriver constructor.
     *
     * @param  DeferInterface  $defer  The defer instance for deferring tasks.
     */
    public function __construct(DeferInterface $defer)
    {
        $this->defer = $defer;
    }

    /**
     * Execute the given tasks concurrently and return an array of results.
     *
     * This method accepts either a single Closure or an array of Closures,
     * executes each task, and collects the results in an array. If any task
     * throws an exception, it will be propagated.
     *
     * @param  Closure|array  $tasks  A task or an array of tasks to be executed.
     *
     * @throws Throwable If any of the tasks throw an exception.
     *
     * @return array The results of the executed tasks.
     */
    public function run(Closure|array $tasks): array
    {
        // Wrap tasks in an array if a single task is provided
        return collect(Arr::wrap($tasks))->map(function($task) {
            // Ensure each task is callable before executing
            if (! is_callable($task)) {
                throw InvalidArgumentException::make('Each task must be a callable.');
            }

            // Execute the task and return the result
            return $task();
        })->all(); // Collect results into an array
    }

    /**
     * Schedule the given tasks to run in the background after the current task has finished.
     *
     * This method wraps the provided tasks in a deferred callback, allowing them
     * to be executed asynchronously once the current execution context is completed.
     *
     * @param  Closure|array  $tasks  A task or an array of tasks to be deferred.
     *
     * @return DeferredCallback A callback that defers the execution of the tasks.
     */
    public function defer(Closure|array $tasks): DeferredCallback
    {
        // Create a deferred callback to execute the tasks
        return $this->defer->execute(function() use ($tasks): void {
            // Ensure the tasks are wrapped in an array for consistent handling
            collect(Arr::wrap($tasks))->each(function($task): void {
                // Ensure each task is callable before executing
                if (! is_callable($task)) {
                    throw InvalidArgumentException::make('Each task must be a callable.');
                }

                // Execute the task
                $task();
            });
        });
    }
}
