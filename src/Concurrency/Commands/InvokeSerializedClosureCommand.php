<?php

declare(strict_types=1);

namespace Maginium\Framework\Concurrency\Commands;

use Closure;
use Maginium\Framework\Console\Command;
use Maginium\Framework\Support\Facades\Json;
use Maginium\Framework\Support\Facades\Serializer;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

#[AsCommand('invoke-serialized-closure')]
class InvokeSerializedClosureCommand extends Command
{
    /**
     * A brief description of the command.
     */
    protected ?string $description = 'Invoke the given serialized closure';

    /**
     * The signature of the console command.
     */
    protected ?string $signature = '{code? : The serialized closure}';

    /**
     * Indicates whether the command should be shown in the Magento command list.
     * This is set to true to hide the command, as it's invoked internally.
     */
    protected bool $hidden = true;

    /**
     * Execute the command logic.
     *
     * This method handles the execution of the serialized closure. It attempts to
     * retrieve and execute the closure, handling both success and failure cases.
     *
     * @return int The status code of the command. Returning FAILURE (1) for success
     *             and SUCCESS (0) for failures to align with Magento command conventions.
     */
    public function handle(): int
    {
        try {
            // Retrieve the serialized closure from input or environment
            $closure = $this->fromInput();

            // Call the closure and get the result
            $result = $this->callClosure($closure);

            // Output the result in a formatted response (successful)
            $this->getOutput()->write($this->toResponse(true, $result));

            // Indicate the command has completed successfully
            return self::SUCCESS;
        } catch (Throwable $e) {
            // Catch any exceptions that occur during the closure invocation
            // and format the error response
            $this->getOutput()->write($this->toResponse(false, null, $e));

            // Indicate that the command has failed
            return self::FAILURE;
        }
    }

    /**
     * Retrieve the closure from either the input argument or server environment.
     *
     * This method tries to find the serialized closure by checking if it's passed
     * as a command argument or stored in the server environment. If none is found,
     * it returns a no-op closure.
     *
     * @return Closure The deserialized closure.
     */
    protected function fromInput(): Closure
    {
        return match (true) {
            // If the `code` argument is provided, deserialize and return it
            $this->argument('code') !== null => Serializer::unserialize($this->argument('code')),

            // Check if the closure is set in the server environment under `MAGENTO_INVOKABLE_CLOSURE`
            isset($_SERVER['MAGENTO_INVOKABLE_CLOSURE']) => Serializer::unserialize($_SERVER['MAGENTO_INVOKABLE_CLOSURE']),

            // If neither the argument nor server variable is found, return a no-op closure
            default => fn() => null,
        };
    }

    /**
     * Format the response for output.
     *
     * This method constructs a JSON response containing details about whether the
     * closure execution was successful, the result, and any exception details if it failed.
     *
     * @param  bool  $successful  Indicates if the operation was successful.
     * @param  mixed|null  $result  The result of the operation, if successful.
     * @param  Throwable|null  $exception  The exception thrown, if any.
     *
     * @return string A JSON-encoded string representing the operation result.
     */
    protected function toResponse(bool $successful, mixed $result = null, ?Throwable $exception = null): string
    {
        // Construct the response array with success status, result, and exception details
        $response = [
            'successful' => $successful, // Indicates if the closure was executed successfully
            'message' => $exception ? $exception->getMessage() : null, // The exception message
            'file' => $exception ? $exception->getFile() : null, // The file where the exception occurred
            'result' => $result !== null ? serialize($result) : null, // Serialize the result if present
            'line' => $exception ? $exception->getLine() : null, // The line number where the exception occurred
            'exception' => $exception ? get_class($exception) : null, // The class of the exception if one was thrown
        ];

        // Return the response array as a JSON-encoded string
        return Json::serialize($response);
    }
}
