<?php

declare(strict_types=1);

namespace Maginium\Framework\Log;

use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Framework\Log\Enums\LogLevel;
use Maginium\Framework\Log\Interfaces\FactoryInterface;
use Maginium\Framework\Support\Facades\Container;
use Maginium\Framework\Support\Facades\Log;

/*
 * Helper function to log messages at various levels.
 *
 * This function allows for logging messages with different log levels (e.g., info, debug, error).
 * It acts as a convenient wrapper to the logger facade.
 *
 * @param string|null $level The log level (e.g., 'info', 'error', 'debug'). If null, defaults to 'debug'.
 * @param string|null $message The log message to be logged.
 * @param array $context Optional contextual data for the log entry.
 *
 * @return FactoryInterface|Logger|null
 *
 * @throws InvalidArgumentException If the provided level is invalid.
 */
if (! function_exists('Maginium\Framework\Log\log')) {
    function log(string $level = LogLevel::DEBUG, ?string $message = null, array $context = [])
    {
        // Validate the log level.
        if (! LogLevel::hasKey($level)) {
            throw InvalidArgumentException::make(__('Invalid log level "%s" provided.', $level));
        }

        // If message is null, return the logger instance.
        if ($message === null) {
            // Return the FactoryInterface instance for the level
            return Log::channel()->{$level}();
        }

        // Log the message using the specified level and context.
        Log::$level($message, $context);

        return null;
    }
}

if (! function_exists('logger')) {
    /**
     * Log a debug message to the logs.
     *
     * @param  string|null  $message
     *
     * @return ($message is null ? FactoryInterface : null)
     */
    function logger($message = null, array $context = [])
    {
        if ($message === null) {
            return Container::resolve(FactoryInterface::class);
        }

        return Log::debug($message, $context);
    }
}
