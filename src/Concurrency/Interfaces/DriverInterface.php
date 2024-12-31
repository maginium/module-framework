<?php

declare(strict_types=1);

namespace Maginium\Framework\Concurrency\Interfaces;

use Closure;
use Maginium\Framework\Defer\DeferredCallback;
use Throwable;

/**
 * Interface Driver.
 *
 * This interface defines a contract for concurrency drivers that handle
 * the execution of tasks either concurrently or deferred. Implementing
 * classes must provide mechanisms to run tasks and to defer their
 * execution until a later time.
 */
interface DriverInterface
{
    /**
     * Execute the given tasks concurrently and return an array of results.
     *
     * This method accepts either a single task (Closure) or an array
     * of tasks. Each task will be executed in parallel, and the results
     * will be returned as an array. If any task throws an exception, it will
     * be propagated.
     *
     * @param  Closure|array  $tasks  A single Closure or an array of Closures representing the tasks to execute.
     *
     * @throws Throwable If any of the tasks throw an exception during execution.
     *
     * @return array An array containing the results of the executed tasks.
     */
    public function run(Closure|array $tasks): array;

    /**
     * Schedule the execution of the given tasks to occur at a later time.
     *
     * This method defers the execution of the specified tasks, allowing
     * them to run asynchronously after the current execution context has completed.
     *
     * @param  Closure|array  $tasks  A single Closure or an array of Closures representing the tasks to defer.
     *
     * @return DeferredCallback A callback that can be executed to run the deferred tasks.
     */
    public function defer(Closure|array $tasks): DeferredCallback;
}
