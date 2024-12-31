<?php

declare(strict_types=1);

namespace Maginium\Framework\Enum\Exceptions;

use Maginium\Foundation\Exceptions\LocalizedException;
use Maginium\Framework\Enum\Enum;
use Maginium\Framework\Support\Php;
use Throwable;

/**
 * Exception thrown when an invalid value is used to construct an Enum instance.
 */
class InvalidEnumMemberException extends LocalizedException
{
    /**
     * InvalidEnumMemberException constructor.
     *
     * @param  mixed  $invalidValue  The invalid value used for construction.
     * @param  class-string<Enum<mixed>>  $enum  The class name of the Enum.
     * @param  Throwable|null  $cause  The original exception that caused this exception (optional).
     * @param  string|int|null  $code  The error code associated with the exception (optional).
     * @param  string[]|null  $context  Additional context or data related to the exception (optional).
     */
    public function __construct(
        mixed $invalidValue,
        string $enum,
        ?Throwable $cause = null,
        string|int|null $code = null,
    ) {
        // Determine the type of the invalid key
        $invalidValueType = gettype($invalidValue);

        // Retrieve valid keys from the Enum class
        $enumKeys = Php::implode(', ', $enum::getKeys());

        // Get the name of the Enum class for the error message
        $enumClassName = class_basename($enum);

        // Construct the error message providing detailed information
        $message = __(
            'Cannot construct an instance of %1 using the key (%2) `%3`. Possible keys are [%4].',
            $enumClassName,    // Class name of the Enum
            $invalidValueType, // Type of the invalid key
            $invalidValue,       // The invalid key value
            $enumKeys,          // List of valid keys
        );

        // Call the parent constructor to initialize the exception with the constructed message and other parameters
        parent::__construct(
            $message, // The error message
            $cause,     // The original exception that caused this exception (if any)
            $code,       // The error code
        );
    }
}
