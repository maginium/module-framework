<?php

declare(strict_types=1);

namespace Maginium\Framework\Log;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Phrase;
use Maginium\Framework\Support\Facades\Filesystem;
use Maginium\Framework\Support\Facades\Json;
use Maginium\Framework\Support\Path;

class LogWriter
{
    /**
     * @var string The log message to be written to the log file
     */
    protected $message;

    /**
     * @var mixed Additional data to be logged alongside the message
     */
    protected $data;

    /**
     * @var string The name of the log file
     */
    protected $fileName;

    /**
     * @var string The directory path where the log file will be stored
     */
    protected $path;

    /**
     * @var array Additional context information to provide more details in the log
     */
    protected $context;

    /**
     * LogWriter Constructor.
     * Initializes default log path and empty context.
     */
    public function __construct()
    {
        // Default path for logs if not specified
        $this->path = 'log';

        // Default context is empty (no additional details in the log)
        $this->context = [];
    }

    /**
     * Factory method to create a new LogWriter instance with the specified parameters.
     *
     * @param  string  $message  The message to log
     * @param  mixed  $data  The additional data to log
     * @param  string  $fileName  The log file name
     * @param  string  $path  The path where the log file will be stored
     * @param  array  $context  The context information to log
     *
     * @return LogWriter
     */
    public static function make(): static
    {
        // Return the newly created instance
        return new static;
    }

    /**
     * Set the message to log.
     * This is the primary message that will be logged.
     *
     * @param  Phrase|string  $message  The message to log
     */
    public function setMessage(Phrase|string $message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get the message to log.
     *
     * @return string The log message
     */
    public function getMessage(): string
    {
        return $this->message instanceof Phrase ? $this->message->render() : $this->message;
    }

    /**
     * Set the data to log.
     * This can be any type of additional data that needs to be logged.
     *
     * @param  mixed  $data  Additional data to be logged
     */
    public function setData($data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get the data to log.
     *
     * @return mixed The data associated with the log entry
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set the file name to log.
     * Defines the name of the log file where messages will be written.
     *
     * @param  string  $fileName  The name of the log file
     */
    public function setFileName(string $fileName): self
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * Get the file name to log.
     *
     * @return string The log file name
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * Set the path where the log file will be saved.
     * Allows custom path for log storage.
     *
     * @param  string  $path  The directory path for the log file
     */
    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get the path where the log file will be saved.
     *
     * @return string The directory path for the log file
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Set the context for the log.
     * Context can contain additional information (like request data, user info, etc.)
     * that will be attached to the log entry.
     *
     * @param  array  $context  The context information to log
     */
    public function setContext(array $context): self
    {
        $this->context = Json::encode($context);

        return $this;
    }

    /**
     * Get the context for the log.
     *
     * @return array The context array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Write the log message to the specified file with context information.
     * The method ensures the log directory exists and creates it if necessary.
     * If the log file doesn't exist, it will create a new file.
     * The log entry is appended with a newline character.
     *
     * @throws FileSystemException If message or file name is not set
     */
    public function log(): void
    {
        // Ensure that both message and file name are set before logging
        if (! $this->message || ! $this->fileName) {
            throw new FileSystemException(__('Message and file name must be set.'));
        }

        // Define the full path for the log directory based on the configured path
        $fullPath = Path::join(DirectoryList::VAR_DIR, '/', $this->getPath());

        // Check if the directory exists, if not, create it with necessary permissions
        if (! Filesystem::isDirectory($fullPath)) {
            Filesystem::makeDirectory($fullPath);
        }

        // Generate the full file path where the log file will be stored
        $filePath = Path::join($fullPath, $this->getFileName() . '.log');

        // Check if the log file exists; if not, create an empty file
        if (! Filesystem::exists($filePath)) {
            Filesystem::put($filePath, '');
        }

        // Prepare the log message, including the context data
        $logMessage = $this->getMessage() . ' ' . $this->getData() . ' ' . $this->formatContext() . "\n";

        // Write the prepared log message to the file, appending it
        Filesystem::append($filePath, $logMessage);
    }

    /**
     * Convert the context array to a JSON string.
     * This method formats the context data as a string for inclusion in the log.
     *
     * @return string The formatted context in JSON format
     */
    protected function formatContext(): string
    {
        // Encode the context array to a nicely formatted JSON string
        return Json::encode($this->context, JSON_PRETTY_PRINT);
    }
}
