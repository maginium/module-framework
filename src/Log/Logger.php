<?php

declare(strict_types=1);

namespace Maginium\Framework\Log;

use Illuminate\Contracts\Support\Jsonable;
use Magento\Framework\Exception\FileSystemException;
use Maginium\Foundation\Exceptions\BadMethodCallException;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Framework\Log\Enums\LogLevel;
use Maginium\Framework\Log\Helpers\Data as DataHelper;
use Maginium\Framework\Log\Interfaces\LoggerInterface;
use Maginium\Framework\Support\Php;
use Maginium\Framework\Support\Reflection;
use Maginium\Framework\Support\Str;
use Maginium\Framework\Support\Stringable as IlluminateStringable;
use Psr\Log\LoggerInterface as PsrLogInterface;
use Stringable;

/**
 * Class Logger.
 *
 * The `Logger` class serves as an extension of the Monolog logger, providing
 * an interface for managing log operations within the application. It encapsulates
 * logging functionalities, including context management and custom logger configurations.
 */
class Logger implements LoggerInterface
{
    /**
     * @var string Class name
     */
    protected $className;

    /**
     * @var string Default name
     */
    protected $defaultName = 'monolog';

    /**
     * Any context to be added to logs.
     *
     * @var array
     */
    protected $context = [];

    /**
     * The underlying logger implementation.
     *
     * @var PsrLogInterface
     */
    protected $logger;

    /**
     * Create a new log writer instance.
     */
    public function __construct(
        PsrLogInterface $logger,
        ?string $name = null,
    ) {
        $this->logger = $logger;

        // Use the provided name or fall back to the default name.
        $this->className = $name ?? $this->defaultName;
    }

    /**
     * Gets the class name for the logger.
     */
    public function getClassName(): ?string
    {
        return $this->className;
    }

    /**
     * Sets the class name for the logger.
     */
    public function setClassName(string $className): void
    {
        $this->className = class_basename($className);
    }

    /**
     * Add context to all future logs.
     *
     * This method allows you to add additional context information to be included in all future log entries.
     * The context is applied globally to any logs written after this method is called.
     *
     * @param  array  $context  The context data to be added to logs.
     *
     * @return LoggerInterface The current logger instance with the added context.
     */
    public function withContext(array $context = []): self
    {
        $this->context = Php::recursiveArrayMerge($this->context, $context);

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Flush the existing context array.
     *
     * This method removes all previously set context data. Future logs will not contain any context until it is set again.
     *
     * @return LoggerInterface The current logger instance without any context.
     */
    public function withoutContext(): self
    {
        $this->context = [];

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Dynamically pass log calls into the writer.
     *
     * @param  string  $level  The log level (e.g., 'error', 'info', etc.)
     * @param  Stringable|Jsonable|IlluminateStringable|array|string  $message  The message to log, can be of various types
     * @param  array  $context  Additional context information to include with the log message
     */
    public function write($level, $message, array $context = []): void
    {
        //  Convert the log level to its corresponding numeric value
        //  This uses a custom LogLevel class that converts the string representation of the log level to a numeric value
        $level = LogLevel::getValue(Str::upper($level));

        //  Write the log message using the converted log level, the message, and the context
        $this->writeLog($level, $message, $context);
    }

    /**
     * Write a log entry to a file using a LogWriter.
     *
     * This method uses the LogWriter instance to write the log entry to a file.
     * It is useful when you want to ensure logs are stored in files in addition to other outputs.
     *
     * @param  LogWriter  $logWriter  The LogWriter instance used to write the log.
     */
    public function writeToFile(LogWriter $logWriter): void
    {
        // Delegate the logging operation to the LogWriter instance
        try {
            // Call the log method to handle the logging process
            $logWriter->log();
        } catch (FileSystemException $e) {
            // Handle errors such as missing message or file name
            throw new FileSystemException(__('Failed to write log: %1', $e->getMessage()));
        }
    }

    /**
     * Logs with an arbitrary level.
     *
     * This method allows logging at any level specified by the user. It acts as a
     * generic logging function where the level, message, and context can be defined.
     *
     * @param  mixed  $level  The log level to use (e.g., DEBUG, INFO, ERROR, etc.)
     * @param  string|Stringable  $message  The message to log, which can be a string or an object implementing __toString().
     * @param  mixed[]  $context  Optional context data to provide additional information for the log entry.
     *
     * @throws InvalidArgumentException Throws an exception if the log level is invalid.
     */
    public function log($level, $message, array $context = []): void
    {
        // Delegate the actual logging to the writeLog method, passing along level, message, and context.
        $this->writeLog($level, $message, $context);
    }

    /**
     * Adds a log record at the DEBUG level.
     *
     * This method provides a way to log debugging information. It is designed to be
     * compatible with common logging interfaces, making it easier to integrate with
     * other logging systems.
     *
     * @param  Arrayable|Jsonable|Stringable||Phrase|array|string  $message
     * @param  mixed[]  $context  Optional context data to add more detail to the log.
     */
    public function debug($message, array $context = []): void
    {
        // Call writeLog with the DEBUG level and the provided message and context.
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Adds a log record at the INFO level.
     *
     * This method allows for logging informational messages, which may be useful for
     * tracking application behavior during runtime. It is compatible with standard interfaces.
     *
     * @param  Arrayable|Jsonable|Stringable||Phrase|array|string  $message
     * @param  mixed[]  $context  Optional context data to provide additional details for the log entry.
     */
    public function info($message, array $context = []): void
    {
        // Call writeLog with the INFO level, using the provided message and context.
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Adds a log record at the NOTICE level.
     *
     * This method is intended for logging notices, which may indicate normal but
     * significant events. It maintains compatibility with common logging interfaces.
     *
     * @param  Arrayable|Jsonable|Stringable||Phrase|array|string  $message
     * @param  mixed[]  $context  Optional context data to provide more insight into the log entry.
     */
    public function notice($message, array $context = []): void
    {
        // Log a notice level message using writeLog.
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Adds a log record at the WARNING level.
     *
     * This method is used to log warning messages, which indicate potential issues
     * or important alerts in the application. It ensures compatibility with common
     * interfaces.
     *
     * @param  Arrayable|Jsonable|Stringable||Phrase|array|string  $message
     * @param  mixed[]  $context  Optional context data for additional log entry details.
     */
    public function warning($message, array $context = []): void
    {
        // Call writeLog to log a message with a WARNING level.
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Adds a log record at the ERROR level.
     *
     * This method is intended for logging error messages that represent significant
     * problems in the application. It provides compatibility with standard logging interfaces.
     *
     * @param  Arrayable|Jsonable|Stringable||Phrase|array|string  $message
     * @param  mixed[]  $context  Optional context data to enhance the log entry.
     */
    public function error($message, array $context = []): void
    {
        // Log an error message using writeLog.
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Adds a log record at the CRITICAL level.
     *
     * This method is used to log critical messages that indicate serious errors or
     * failures that require immediate attention. It supports compatibility with
     * common logging interfaces.
     *
     * @param  Arrayable|Jsonable|Stringable||Phrase|array|string  $message
     * @param  mixed[]  $context  Optional context data to add more detail to the log entry.
     */
    public function critical($message, array $context = []): void
    {
        // Call writeLog to log a critical message.
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Adds a log record at the ALERT level.
     *
     * This method is intended for logging alert messages that signal immediate action
     * is needed. It ensures compatibility with common logging interfaces.
     *
     * @param  Arrayable|Jsonable|Stringable||Phrase|array|string  $message
     * @param  mixed[]  $context  Optional context data to enhance the log entry.
     */
    public function alert($message, array $context = []): void
    {
        // Log an alert message using writeLog.
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Adds a log record at the EMERGENCY level.
     *
     * This method is used to log emergency messages that indicate the system is
     * unusable or in a critical state. It provides compatibility with standard interfaces.
     *
     * @param  Arrayable|Jsonable|Stringable||Phrase|array|string  $message
     * @param  mixed[]  $context  Optional context data to provide additional insight for the log entry.
     */
    public function emergency($message, array $context = []): void
    {
        // Call writeLog to log an emergency message.
        $this->writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Write a message to the log with additional context.
     *
     * This method handles logging messages at various levels and
     * includes the option to send logs to external services like Sentry and Slack.
     *
     * @param  string  $level  The numeric log level (e.g., debug, info, error)
     * @param  Stringable|Jsonable|IlluminateStringable|array|string|Exception  $message  The message to log, can be of various types
     * @param  array  $context  Additional context information to include with the log message
     */
    protected function writeLog(string $level, $message, array $context): void
    {
        // Format the message (handle the different types like Exception, Array, String, etc.)
        $message = DataHelper::buildLogMessage($message);

        // Prepare the log context with essential information
        $options = DataHelper::extractOptions($context);

        // Format the log message with the class name and additional context
        $formattedMessage = DataHelper::formatMessage(
            $level,
            $message,
            $this->getClassName(),
            $options,
        );

        // Merge the provided context with existing context for a comprehensive log
        $mergedContext = Php::recursiveArrayMerge($this->context, $context);

        // Retrieve the class name for logger's name
        $loggerName = $this->getClassName();

        // Add technical metadata about the logger's class for future reference
        $technicalMetadata = DataHelper::addClassesMetadata($loggerName);

        // Set the context for the logger, including technical metadata
        self::withContext(['technical-metadata' => $technicalMetadata]);

        // Log the formatted message with the merged context
        $this->logger->{$level}(
            $formattedMessage,
            $mergedContext
        );
    }

    /**
     * Dynamically proxies method calls to the underlying logger.
     *
     * This magic method intercepts calls to undefined instance methods and forwards them
     * to the actual logging methods, allowing for dynamic handling of logging
     * operations without the need to define each method explicitly.
     *
     * @param  string  $method  The name of the method being called.
     * @param  array  $parameters  The parameters to pass to the method.
     *
     * @throws BadMethodCallException If the method does not exist on the logger.
     *
     * @return mixed The return value of the proxied method call.
     */
    public function __call(string $method, array $parameters)
    {
        // Check if the method exists in the logger class
        if (! Reflection::methodExists($this->logger, $method)) {
            throw BadMethodCallException::make(__("Method '%1' does not exist.", $method));
        }

        // Forward the method call to the corresponding logger method
        return $this->logger->{$method}(...$parameters);
    }
}
