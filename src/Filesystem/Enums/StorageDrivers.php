<?php

declare(strict_types=1);

namespace Maginium\Framework\Filesystem\Enums;

use Maginium\Framework\Enum\Attributes\Description;
use Maginium\Framework\Enum\Attributes\Label;
use Maginium\Framework\Enum\Enum;

/**
 * Enum representing different storage drivers.
 *
 * This enum defines various storage drivers used in the system, including local and cloud-based storage
 * drivers like S3. The drivers can be used to specify the storage solution in use for different storage
 * and file management operations.
 *
 * @method static self LOCAL() Represents the local storage driver.
 * @method static self S3() Represents the S3 cloud storage driver.
 */
class StorageDrivers extends Enum
{
    /**
     * Represents the local storage driver.
     */
    #[Label('Local')]
    #[Description('Represents the local storage driver.')]
    public const LOCAL = 'local';

    /**
     * Represents the S3 cloud storage driver.
     */
    #[Label('S3')]
    #[Description('Represents the S3 cloud storage driver.')]
    public const S3 = 's3';
}
