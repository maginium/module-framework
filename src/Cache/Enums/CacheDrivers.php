<?php

declare(strict_types=1);

namespace Maginium\Framework\Cache\Enums;

use Maginium\Framework\Enum\Attributes\Description;
use Maginium\Framework\Enum\Attributes\Label;
use Maginium\Framework\Enum\Enum;

/**
 * Enum representing different cache drivers.
 *
 * @method static self REDIS() Represents the Redis cache driver.
 * @method static self ARRAY() Represents the Array cache driver.
 * @method static self MONGO() Represents the MongoDB cache driver.
 * @method static self MEMCACHED() Represents the Memcached cache driver.
 * @method static self FILE() Represents the File cache driver.
 */
class CacheDrivers extends Enum
{
    /**
     * Represents the Redis cache driver.
     */
    #[Label('Redis')]
    #[Description('Represents the Redis cache driver.')]
    public const REDIS = 'redis';

    /**
     * Represents the Array cache driver.
     */
    #[Label('Array')]
    #[Description('Represents the Array cache driver.')]
    public const ARRAY = 'array';

    /**
     * Represents the MongoDB cache driver.
     */
    #[Label('MongoDB')]
    #[Description('Represents the MongoDB cache driver.')]
    public const MONGO = 'mongo';

    /**
     * Represents the Memcached cache driver.
     */
    #[Label('Memcached')]
    #[Description('Represents the Memcached cache driver.')]
    public const MEMCACHED = 'memcached';

    /**
     * Represents the File cache driver.
     */
    #[Label('File')]
    #[Description('Represents the File cache driver.')]
    public const FILE = 'file';
}
