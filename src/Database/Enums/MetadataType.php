<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Enums;

use Maginium\Framework\Enum\Attributes\Description;
use Maginium\Framework\Enum\Attributes\Label;
use Maginium\Framework\Enum\Enum;

/**
 * Enum for metadata storage types.
 *
 * This enum defines constants for different metadata storage types supported by the application.
 *
 * @method static self INLINE() Metadata is stored inline as a JSON column within the main record.
 * @method static self EXTERNAL() Metadata is stored in an external table with key-value pairs.
 */
final class MetadataType extends Enum
{
    /**
     * Indicates that the metadata is stored inline as a JSON column.
     */
    #[Label('Inline')]
    #[Description('Indicates that the metadata is stored inline as a JSON column within the main record.')]
    public const INLINE = 'inline';

    /**
     * Indicates that the metadata is stored in an external table.
     */
    #[Label('External')]
    #[Description('Indicates that the metadata is stored in an external table with key-value pairs.')]
    public const EXTERNAL = 'external';
}
