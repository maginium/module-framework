<?php

declare(strict_types=1);

namespace Maginium\Framework\Uuid\Enums;

use Maginium\Framework\Enum\Attributes\Description;
use Maginium\Framework\Enum\Attributes\Label;
use Maginium\Framework\Enum\Enum;

/**
 * Enum for UUID versions.
 *
 * @method static self V1() UUID version 1.
 * @method static self V3() UUID version 3.
 * @method static self V4() UUID version 4.
 * @method static self V5() UUID version 5.
 */
class UuidVersion extends Enum
{
    /**
     * UUID version 1.
     *
     * This version uses a combination of timestamp and MAC address for generation.
     */
    #[Label('Version 1')]
    #[Description('Uses a combination of timestamp and MAC address for generation.')]
    public const V1 = 1;

    /**
     * UUID version 3.
     *
     * This version uses an MD5 hash of a namespace identifier and a name for generation.
     */
    #[Label('Version 3')]
    #[Description('Uses an MD5 hash of a namespace identifier and a name for generation.')]
    public const V3 = 3;

    /**
     * UUID version 4.
     *
     * This version uses random or pseudo-random numbers for generation.
     */
    #[Label('Version 4')]
    #[Description('Uses random or pseudo-random numbers for generation.')]
    public const V4 = 4;

    /**
     * UUID version 5.
     *
     * This version uses a SHA-1 hash of a namespace identifier and a name for generation.
     */
    #[Label('Version 5')]
    #[Description('Uses a SHA-1 hash of a namespace identifier and a name for generation.')]
    public const V5 = 5;
}
