<?php

declare(strict_types=1);

namespace Maginium\Framework\Log\Handlers\File;

use Maginium\Framework\Log\Enums\LogLevel;

/**
 * Class Notice.
 *
 * Represents a Notice log level handler.
 */
class Notice extends Base
{
    /**
     * The name of the log file (relative to the Magento root directory).
     * Subclasses must define this property to specify the target log file.
     */
    protected string $fileName = '/var/log/notice.log';

    /**
     * The log level for this handler, corresponding to one of the Monolog\Logger constants (e.g., DEBUG, INFO, CRITICAL).
     * Subclasses must define this property to specify the log level.
     */
    protected int $type = LogLevel::NOTICE;
}
