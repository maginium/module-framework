<?php

declare(strict_types=1);

namespace Maginium\Framework\Log\Enums;

use Maginium\Framework\Enum\Attributes\Description;
use Maginium\Framework\Enum\Attributes\Label;
use Maginium\Framework\Enum\Enum;

/**
 * Enum representing different log drivers.
 *
 * @method static self DEFAULT() Represents the default log driver.
 * @method static self SLACK() Represents the Slack log driver.
 * @method static self CLOUDWATCH() Represents the CloudWatch log driver.
 * @method static self SENTRY() Represents the Sentry log driver.
 */
class LogDrivers extends Enum
{
    /**
     * Represents the default log driver.
     */
    #[Label('Default Log Driver')]
    #[Description('Represents the default log driver for handling logs in the system.')]
    public const DEFAULT = 'default';

    /**
     * Represents the Slack log driver.
     */
    #[Label('Slack Log Driver')]
    #[Description('Represents the Slack log driver for sending logs to a Slack channel via webhook.')]
    public const SLACK = 'slack';

    /**
     * Represents the CloudWatch log driver.
     */
    #[Label('CloudWatch Log Driver')]
    #[Description('Represents the AWS CloudWatch log driver for storing and managing logs in the cloud.')]
    public const CLOUDWATCH = 'cloudwatch';
}
