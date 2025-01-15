<?php

declare(strict_types=1);

namespace Maginium\Framework\Mail\Enum;

use Maginium\Framework\Enum\Attributes\Description;
use Maginium\Framework\Enum\Attributes\Label;
use Maginium\Framework\Enum\Enum;

/**
 * Enum representing various theme options.
 *
 * This enum defines the different themes that can be applied.
 */
class Theme extends Enum
{
    /**
     * Represents the light theme.
     *
     * @var string
     */
    #[Label('Light')]
    #[Description('A light theme with a bright background and dark text.')]
    public const LIGHT = 'light';

    /**
     * Represents the dark theme.
     *
     * @var string
     */
    #[Label('Dark')]
    #[Description('A dark theme with a dark background and light text.')]
    public const DARK = 'dark';

    /**
     * Represents the system theme.
     *
     * @var string
     */
    #[Label('System')]
    #[Description('A theme that matches the system’s current theme preference.')]
    public const SYSTEM = 'system';
}
