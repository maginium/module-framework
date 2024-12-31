<?php

declare(strict_types=1);

namespace Maginium\Framework\Concurrency;

use Closure;
use Maginium\Framework\Concurrency\Interfaces\DriverInterface;
use Maginium\Framework\Defer\DeferredCallback;
use Maginium\Framework\Defer\Interfaces\DeferInterface;
use Maginium\Framework\Support\Arr;
use Spatie\Fork\Fork;

/**
 * Class ForkDriver.
 *
 * This class implements the Driver interface for executing tasks concurrently
 * using forking. It leverages the Spatie Fork package to create new processes
 * for running tasks, allowing them to execute in parallel.
 */
class ForkDriver implements DriverInterface
{
    /**
     * The defer instance for handling deferred execution.
     */
    protected DeferInterface $defer;

    /**
     * ForkDriver constructor.
     *
     * @param  DeferInterface  $defer  The defer instance for deferring tasks.
     */
    public function __construct(DeferInterface $defer)
    {
        $this->defer = $defer;
    }

    /**
     * Run the given tasks concurrently and return an array containing the results.
     *
     * This method accepts either a single Closure or an array of Closures.
     * Each task is executed concurrently using process forking, and the results
     * are returned in an array. The method wraps the tasks in an array
     * to ensure uniform handling.
     *
     * @param  Closure|array  $tasks  A single Closure or an array of Closures representing the tasks to execute.
     *
     * @return array An array containing the results of the executed tasks.
     */
    public function run(Closure|array $tasks): array
    {
        // Wrap tasks in an array and run them concurrently using the Fork package
        return Fork::new()->run(...Arr::wrap($tasks));
    }

    /**
     * Start the given tasks in the background after the current task has finished.
     *
     * This method allows for deferred execution of tasks. The tasks will run
     * asynchronously after the current execution context is complete. It
     * returns a DeferredCallback which can be invoked later to execute
     * the specified tasks.
     *
     * @param  Closure|array  $tasks  A single Closure or an array of Closures representing the tasks to defer.
     *
     * @return DeferredCallback A callback that can be executed to run the deferred tasks.
     */
    public function defer(Closure|array $tasks): DeferredCallback
    {
        // Create a deferred callback that runs the specified tasks using the injected defer instance
        return $this->defer->execute(fn() => $this->run($tasks));
    }
}
