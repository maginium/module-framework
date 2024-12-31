<?php

declare(strict_types=1);

namespace Maginium\Framework\Config\Enums;

use Maginium\Framework\Enum\Attributes\Description;
use Maginium\Framework\Enum\Attributes\Label;
use Maginium\Framework\Enum\Enum;

/**
 * Enum representing different configuration drivers.
 *
 * @method static self ENV() Represents the environment configuration driver.
 * @method static self DEPLOYMENT() Represents the deployment configuration driver.
 * @method static self SCOPE() Represents the scope configuration driver.
 */
class ConfigDrivers extends Enum
{
    /**
     * Represents the environment configuration driver.
     */
    #[Label('Environment')]
    #[Description('Represents the environment configuration driver.')]
    public const ENV = 'env';

    /**
     * Represents the deployment configuration driver.
     */
    #[Label('Deployment')]
    #[Description('Represents the deployment configuration driver.')]
    public const DEPLOYMENT = 'deployment';

    /**
     * Represents the scope configuration driver.
     */
    #[Label('Scope')]
    #[Description('Represents the scope configuration driver.')]
    public const SCOPE = 'scope';
}
