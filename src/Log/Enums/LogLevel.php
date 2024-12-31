<?php

declare(strict_types=1);

namespace Maginium\Framework\Log\Enums;

use Maginium\Framework\Enum\Attributes\Description;
use Maginium\Framework\Enum\Attributes\Label;
use Maginium\Framework\Enum\Enum;
use Monolog\Logger;

/**
 * Enum for log levels.
 *
 * This enum defines various log levels used by the Monolog logger.
 *
 * @method static self DEBUG() Represents debug log level with detailed information, typically of interest only when diagnosing problems.
 * @method static self INFO() Represents info log level with interesting events such as user logins and SQL logs.
 * @method static self NOTICE() Represents notice log level for normal but significant events.
 * @method static self WARNING() Represents warning log level for exceptional occurrences that are not errors.
 * @method static self ERROR() Represents error log level for runtime errors that do not require immediate action but should typically be logged and monitored.
 * @method static self CRITICAL() Represents critical log level for conditions that require immediate attention.
 * @method static self ALERT() Represents alert log level where action must be taken immediately.
 * @method static self EMERGENCY() Represents emergency log level for situations where the system is unusable.
 */
class LogLevel extends Enum
{
    /**
     * Represents debug log level.
     *
     * @var int
     */
    #[Label('Debug')]
    #[Description('Detailed information, typically of interest only when diagnosing problems.')]
    public const DEBUG = Logger::DEBUG;

    /**
     * Represents info log level.
     *
     * @var int
     */
    #[Label('Info')]
    #[Description('Interesting events. Examples: User logs in, SQL logs.')]
    public const INFO = Logger::INFO;

    /**
     * Represents notice log level.
     *
     * @var int
     */
    #[Label('Notice')]
    #[Description('Normal but significant events.')]
    public const NOTICE = Logger::NOTICE;

    /**
     * Represents warning log level.
     *
     * @var int
     */
    #[Label('Warning')]
    #[Description('Exceptional occurrences that are not errors.')]
    public const WARNING = Logger::WARNING;

    /**
     * Represents error log level.
     *
     * @var int
     */
    #[Label('Error')]
    #[Description('Runtime errors that do not require immediate action but should typically be logged and monitored.')]
    public const ERROR = Logger::ERROR;

    /**
     * Represents critical log level.
     *
     * @var int
     */
    #[Label('Critical')]
    #[Description('Critical conditions that require immediate attention.')]
    public const CRITICAL = Logger::CRITICAL;

    /**
     * Represents alert log level.
     *
     * @var int
     */
    #[Label('Alert')]
    #[Description('Action must be taken immediately.')]
    public const ALERT = Logger::ALERT;

    /**
     * Represents emergency log level.
     *
     * @var int
     */
    #[Label('Emergency')]
    #[Description('Emergency situations where the system is unusable.')]
    public const EMERGENCY = Logger::EMERGENCY;
}
