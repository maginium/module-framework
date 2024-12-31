<?php

declare(strict_types=1);

namespace Maginium\Framework\Log\Facades;

use Maginium\Framework\Log\Interfaces\FactoryInterface;
use Maginium\Framework\Support\Facade;

/**
 * Facade for interacting with the Log service.
 *
 * @method static void write($level, $message, array $context = []) Writes a log entry with the specified level, message, and optional context.
 * @method static void emergency(string|\Stringable|\Magento\Framework\Phrase $message, array $context = []) Logs an emergency message with optional context.
 * @method static void alert(string|\Stringable|\Magento\Framework\Phrase $message, array $context = []) Logs an alert message with optional context.
 * @method static void critical(string|\Stringable|\Magento\Framework\Phrase $message, array $context = []) Logs a critical message with optional context.
 * @method static void error(string|\Stringable|\Magento\Framework\Phrase $message, array $context = []) Logs an error message with optional context.
 * @method static void warning(string|\Stringable|\Magento\Framework\Phrase $message, array $context = []) Logs a warning message with optional context.
 * @method static void notice(string|\Stringable|\Magento\Framework\Phrase $message, array $context = []) Logs a notice message with optional context.
 * @method static void info(string|\Stringable|\Magento\Framework\Phrase $message, array $context = []) Logs an informational message with optional context.
 * @method static void debug(string|\Stringable|\Magento\Framework\Phrase $message, array $context = []) Logs a debug message with optional context.
 * @method static void log(mixed $level, string|\Stringable|\Magento\Framework\Phrase $message, array $context = []) Logs a message at the specified level with optional context.
 * @method static void setClassName(string $className) Sets the class name to be included in log messages.
 * @method static string getClassName() Retrieves the currently set class name for log messages.
 * @method static \Maginium\Framework\Log\Interfaces\LoggerInterface withContext(array $context = []) Returns a new logger instance with the specified context.
 * @method static \Maginium\Framework\Log\Interfaces\LoggerInterface withoutContext() Returns a new logger instance without any context.
 * @method static \Psr\Log\LoggerInterface channel(string|null $channel = null) Returns a logger instance for the specified channel.
 * @method static \Psr\Log\LoggerInterface driver(string|null $driver = null) Returns a logger instance for the specified driver.
 * @method static string|null getDefaultDriver() Retrieves the default logging driver.
 * @method static void setDefaultDriver(string $name) Sets the default logging driver for the logger.
 * @method static \Maginium\Framework\Log\Logger|mixed when(\Closure|mixed|null $value = null, callable|null $callback = null, callable|null $default = null) Executes a callback when a specified condition is met.
 * @method static \Maginium\Framework\Log\Logger|mixed unless(\Closure|mixed|null $value = null, callable|null $callback = null, callable|null $default = null) Executes a callback unless a specified condition is met.
 *
 * @see FactoryInterface
 */
class Log extends Facade
{
    /**
     * Get the accessor for the facade.
     *
     * This method must be implemented by subclasses to return the accessor string
     * corresponding to the underlying service or class the facade represents.
     *
     * @return string The accessor for the facade.
     */
    protected static function getAccessor(): string
    {
        return FactoryInterface::class;
    }
}
