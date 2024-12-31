<?php

declare(strict_types=1);

namespace Maginium\Framework\Log\Handlers\File;

use Maginium\Framework\Support\Path;
use Maginium\Framework\Support\Str;
use Monolog\Handler\FilterHandler;
use Monolog\Handler\StreamHandlerFactory;

/**
 * Class Base.
 *
 * Abstract base class for log level handlers, ensuring consistency and reducing duplication.
 * This class encapsulates the logic for creating log handlers that filter messages by a specific log level.
 */
abstract class Base extends FilterHandler
{
    /**
     * The name of the log file (relative to the Magento root directory).
     * Subclasses must define this property to specify the target log file.
     */
    protected string $fileName;

    /**
     * The log level for this handler, corresponding to one of the Monolog\Logger constants (e.g., DEBUG, INFO, CRITICAL).
     * Subclasses must define this property to specify the log level.
     */
    protected int $type;

    /**
     * Base constructor.
     *
     * Initializes the handler for a specific log level and log file.
     *
     * @param  StreamHandlerFactory  $streamHandlerFactory  Factory to create StreamHandler instances.
     */
    public function __construct(StreamHandlerFactory $streamHandlerFactory)
    {
        // Ensure the file name includes the /var/log directory.
        // If not, prepend it to the file name.
        if (! Str::contains($this->fileName, '/var/log')) {
            $this->fileName = Path::join('/var/log', $this->fileName);
        }

        // Combine the Magento base path (BP) and the log file name to create an absolute file path.
        $logFilePath = Path::join(BP, $this->fileName);

        // Create a stream handler with the resolved log file path and the specific logger level.
        $streamHandler = $streamHandlerFactory->create([
            'stream' => $logFilePath,
            'level' => $this->type,
        ]);

        // Initialize the parent FilterHandler.
        // The FilterHandler filters log messages based on the configured log level.
        parent::__construct($streamHandler, $this->type, $this->type);
    }
}
