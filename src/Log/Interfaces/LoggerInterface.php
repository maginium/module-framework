<?php

declare(strict_types=1);

namespace Maginium\Framework\Log\Interfaces;

use Illuminate\Contracts\Support\Jsonable;
use Maginium\Framework\Log\LogWriter;
use Maginium\Framework\Support\Stringable as IlluminateStringable;
use Psr\Log\LoggerInterface as PsrLoggerInterface;
use Stringable;

/**
 * Interface LogInterface.
 *
 * This interface defines the methods for logging operations within the application.
 * It includes methods for logging at various levels, managing log context,
 * and registering event listeners related to log messages.
 */
interface LoggerInterface extends PsrLoggerInterface
{
    /**
     * Gets the class name for the logger.
     *
     * This method returns the class name associated with the logger instance.
     *
     * @return string|null The class name, or null if not set.
     */
    public function getClassName(): ?string;

    /**
     * Sets the class name for the logger.
     *
     * This method sets the class name that will be associated with the logger instance.
     *
     * @param  string  $className  The class name to set.
     */
    public function setClassName(string $className): void;

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
    public function withContext(array $context = []): self;

    /**
     * Flush the existing context array.
     *
     * This method removes all previously set context data. Future logs will not contain any context until it is set again.
     *
     * @return LoggerInterface The current logger instance without any context.
     */
    public function withoutContext(): self;

    /**
     * Dynamically pass log calls into the writer.
     *
     * @param  string  $level  The log level (e.g., 'error', 'info', etc.)
     * @param  Stringable|Jsonable|IlluminateStringable|array|string  $message  The message to log, can be of various types
     * @param  array  $context  Additional context information to include with the log message
     */
    public function write($level, $message, array $context = []): void;

    /**
     * Write a log entry to a file using a LogWriter.
     *
     * This method uses the LogWriter instance to write the log entry to a file.
     * It is useful when you want to ensure logs are stored in files in addition to other outputs.
     *
     * @param  LogWriter  $logWriter  The LogWriter instance used to write the log.
     */
    public function writeToFile(LogWriter $logWriter): void;
}
