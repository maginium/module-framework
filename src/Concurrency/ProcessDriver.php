<?php

declare(strict_types=1);

namespace Maginium\Framework\Concurrency;

use Closure;
use Illuminate\Process\Factory as ProcessFactory;
use Illuminate\Process\Pool;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Application\Application;
use Maginium\Framework\Concurrency\Interfaces\DriverInterface;
use Maginium\Framework\Defer\DeferredCallback;
use Maginium\Framework\Defer\Interfaces\DeferInterface;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Facades\Json;
use Maginium\Framework\Support\Facades\SerializableClosure;
use Maginium\Framework\Support\Facades\Serializer;

/**
 * Class ProcessDriver.
 *
 * Implements the Driver interface for executing tasks concurrently using process-based concurrency.
 * It utilizes the ProcessFactory to manage task execution in a pool of processes, allowing them
 * to run in parallel and provides support for deferred execution.
 */
class ProcessDriver implements DriverInterface
{
    /**
     * @var ProcessFactory An instance of ProcessFactory to manage process creation.
     */
    protected ProcessFactory $processFactory;

    /**
     * @var DeferInterface An instance of the Defer service for deferring task execution.
     */
    protected DeferInterface $defer;

    /**
     * Create a new process-based concurrency driver.
     *
     * @param  ProcessFactory  $processFactory  An instance of ProcessFactory to manage process creation.
     * @param  DeferInterface  $defer  An instance of the Defer service for deferring tasks.
     */
    public function __construct(ProcessFactory $processFactory, DeferInterface $defer)
    {
        $this->defer = $defer;
        $this->processFactory = $processFactory;
    }

    /**
     * Run the given tasks concurrently and return an array of their results.
     *
     * @param  Closure|array  $tasks  The tasks to run concurrently. Each task is expected to be a closure or an array of closures.
     *
     * @throws Exception If any task fails or throws an exception during execution.
     *
     * @return array The results of the executed tasks.
     */
    public function run(Closure|array $tasks): array
    {
        // Format the command to be executed in the process pool.
        $command = Application::formatCommandString('invoke-serialized-closure');

        // Start a pool of processes to run the tasks concurrently.
        $results = $this->processFactory->pool(function(Pool $pool) use ($tasks, $command): void {
            // Wrap tasks as an array if not already, and loop through them to start processes.
            foreach (Arr::wrap($tasks) as $task) {
                // Add each task to the process pool, passing environment variables for closure serialization.
                $pool->path(BP)->env([
                    'MAGENTO_INVOKABLE_CLOSURE' => SerializableClosure::serialize($task), // Serialize each closure.
                ])->command($command); // Specify the command to execute.
            }
        })->start()->wait(); // Start and wait for all processes to complete.

        // Process the results collected from the pool execution.
        return $results->collect()->map(function($result) {
            // Decode the output from each process.
            $result = Json::decode($result->output());

            // If the task was not successful, throw the reported exception.
            if (! $result['successful']) {
                throw new $result['exception'](
                    $result['message'], // Throw the exception with the provided message.
                );
            }

            // Unserialize and return the result of the task.
            return Serializer::unserialize($result['result']);
        })->all(); // Collect all results as an array.
    }

    /**
     * Start the given tasks in the background after the current task has finished.
     * This is useful for deferring tasks to be run asynchronously once the current execution is complete.
     *
     * @param  Closure|array  $tasks  The tasks to defer for background execution.
     *
     * @return DeferredCallback The deferred callback object for the background tasks.
     */
    public function defer(Closure|array $tasks): DeferredCallback
    {
        // Format the command for serialized closure invocation.
        $command = Application::formatCommandString('invoke-serialized-closure');

        // Use the injected Defer service to run the tasks in the background after the current task is finished.
        return $this->defer->execute(function() use ($tasks, $command): void {
            // Loop through and execute each task in the background.
            foreach (Arr::wrap($tasks) as $task) {
                // Serialize the closure and run the command in the background.
                $this->processFactory->path(BP)->env([
                    'MAGENTO_INVOKABLE_CLOSURE' => SerializableClosure::serialize($task), // Serialize each closure.
                ])->run($command . ' 2>&1 &'); // Run the command in the background with output redirection.
            }
        });
    }
}
