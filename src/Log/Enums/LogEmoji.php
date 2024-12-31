<?php

declare(strict_types=1);

namespace Maginium\Framework\Log\Enums;

use Maginium\Foundation\Enums\Emojis;
use Maginium\Framework\Enum\Attributes\Description;
use Maginium\Framework\Enum\Attributes\Label;
use Maginium\Framework\Enum\Enum;

/**
 * Enum for log emojis.
 *
 * This enum defines emojis used for different log levels to visually indicate the type of log.
 *
 * @method static self DEBUG() Represents debug level logs with a bug emoji.
 * @method static self INFO() Represents informational logs with an info emoji.
 * @method static self NOTICE() Represents notice level logs with a push pin emoji.
 * @method static self WARNING() Represents warning level logs with a warning emoji.
 * @method static self ERROR() Represents error level logs with a cross mark emoji.
 * @method static self CRITICAL() Represents critical level logs with a police car light emoji.
 * @method static self ALERT() Represents alert level logs with a police car light emoji.
 * @method static self EMERGENCY() Represents emergency level logs with a police car light emoji.
 */
class LogEmoji extends Enum
{
    /**
     * Emoji for DEBUG level logs.
     *
     * @var string
     */
    #[Label('Debug')]
    #[Description('Emoji used for debug level logs, indicating detailed information for debugging.')]
    public const Debug = Emojis::BUG;

    /**
     * Emoji for INFO level logs.
     *
     * @var string
     */
    #[Label('Info')]
    #[Description('Emoji used for informational logs, providing general information about system operations.')]
    public const INFO = Emojis::INFO;

    /**
     * Emoji for NOTICE level logs.
     *
     * @var string
     */
    #[Label('Notice')]
    #[Description('Emoji used for notice level logs, typically for important but non-critical messages.')]
    public const NOTICE = Emojis::PUSH_PIN;

    /**
     * Emoji for WARNING level logs.
     *
     * @var string
     */
    #[Label('Warning')]
    #[Description('Emoji used for warning level logs, highlighting potential issues or caution.')]
    public const WARNING = Emojis::WARNING;

    /**
     * Emoji for ERROR level logs.
     *
     * @var string
     */
    #[Label('Error')]
    #[Description('Emoji used for error level logs, indicating errors or problems that need attention.')]
    public const ERROR = Emojis::CROSS_MARK;

    /**
     * Emoji for CRITICAL level logs.
     *
     * @var string
     */
    #[Label('Critical')]
    #[Description('Emoji used for critical level logs, representing severe issues that require immediate attention.')]
    public const CRITICAL = Emojis::POLICE_CAR_LIGHT;

    /**
     * Emoji for ALERT level logs.
     *
     * @var string
     */
    #[Label('Alert')]
    #[Description('Emoji used for alert level logs, indicating urgent issues that need immediate action.')]
    public const ALERT = Emojis::POLICE_CAR_LIGHT;

    /**
     * Emoji for EMERGENCY level logs.
     *
     * @var string
     */
    #[Label('Emergency')]
    #[Description('Emoji used for emergency level logs, signifying critical problems that could cause system failure.')]
    public const EMERGENCY = Emojis::POLICE_CAR_LIGHT;
}
