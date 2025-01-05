<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Enums;

use Maginium\Framework\Enum\Attributes\Description;
use Maginium\Framework\Enum\Attributes\Label;
use Maginium\Framework\Enum\Enum;

/**
 * Enum for attribute storage types.
 *
 * This enum defines the supported attribute storage types in Magento 2.
 * Each type corresponds to a different data storage format, which dictates
 * the nature and restrictions of the data that can be stored in the attribute.
 *
 * @method static self VARCHAR() Represents varchar storage type, used for short text values.
 * @method static self INT() Represents integer storage type, used for storing whole numbers.
 * @method static self DECIMAL() Represents decimal storage type, for decimal numbers with precision.
 * @method static self TEXT() Represents text storage type, used for larger text values.
 * @method static self DATETIME() Represents datetime storage type, used for date and time values.
 */
class AttributeType extends Enum
{
    /**
     * Represents a variable-length string up to 255 characters.
     *
     * VARCHAR is typically used for shorter text fields like names or single words.
     */
    #[Label('Varchar')]
    #[Description('Stores a variable-length string up to 255 characters.')]
    public const VARCHAR = 'varchar';

    /**
     * Represents an integer data type.
     *
     * INT is used to store whole numbers, such as quantities or IDs.
     */
    #[Label('Integer')]
    #[Description('Stores integer values.')]
    public const INT = 'int';

    /**
     * Represents a decimal data type with fixed precision and scale.
     *
     * DECIMAL is useful for storing precise decimal numbers, such as prices or measurements.
     */
    #[Label('Decimal')]
    #[Description('Stores decimal values with fixed precision and scale.')]
    public const DECIMAL = 'decimal';

    /**
     * Represents a long text storage type up to 64 KB in length.
     *
     * TEXT is used for larger bodies of text, such as descriptions or comments.
     */
    #[Label('Text')]
    #[Description('Stores long text up to 64 KB in length.')]
    public const TEXT = 'text';

    /**
     * Represents a datetime data type.
     *
     * DATETIME is used to store dates and times, such as creation or expiration dates.
     */
    #[Label('Datetime')]
    #[Description('Stores date and time values.')]
    public const DATETIME = 'datetime';
}
