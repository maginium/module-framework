<?php

declare(strict_types=1);

namespace Maginium\Framework\Enum\Exceptions;

use InvalidArgumentException;
use Maginium\Foundation\Enums\HttpStatusCode;
use Maginium\Framework\Enum\Enum;
use Maginium\Framework\Support\Php;

/**
 * Exception thrown when an invalid value is used to construct an Enum instance.
 */
class InvalidEnumMemberException extends InvalidArgumentException
{
    /**
     * InvalidEnumMemberException constructor.
     *
     * @param  mixed  $invalidValue  The invalid value used for construction.
     * @param  class-string<Enum<mixed>>  $enum  The class name of the Enum.
     */
    public static function make(
        mixed $invalidValue,
        string $enum,
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

        // Create and return a new instance of the exception with the appropriate message
        return new self($message->render(), HttpStatusCode::BAD_REQUEST);
    }
}
