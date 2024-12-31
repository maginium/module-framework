<?php

declare(strict_types=1);

namespace Maginium\Framework\Config\Enums;

use Maginium\Framework\Enum\Attributes\Description;
use Maginium\Framework\Enum\Attributes\Label;
use Maginium\Framework\Enum\Enum;

/**
 * Enum representing different configuration scopes.
 *
 * @method static self STORES() Represents the scope of stores.
 * @method static self WEBSITES() Represents the scope of websites.
 * @method static self DEFAULT() Represents the default scope.
 */
class ConfigScopes extends Enum
{
    /**
     * Represents the scope of stores.
     */
    #[Label('Stores')]
    #[Description('Represents the scope of stores.')]
    public const STORES = 'store';

    /**
     * Represents the scope of websites.
     */
    #[Label('Websites')]
    #[Description('Represents the scope of websites.')]
    public const WEBSITES = 'website';

    /**
     * Represents the default scope.
     */
    #[Label('Default')]
    #[Description('Represents the default scope.')]
    public const DEFAULT = 'default';
}
