<?php

declare(strict_types=1);

namespace Maginium\Framework\Enum\Exceptions;

use InvalidArgumentException;
use Maginium\Foundation\Enums\HttpStatusCodes;
use Maginium\Framework\Enum\Enum;
use Maginium\Framework\Support\Php;
use Throwable;

/**
 * Exception thrown when an invalid key is used to construct an Enum instance.
 */
class InvalidEnumKeyException extends InvalidArgumentException
{
    /**
     * InvalidEnumKeyException constructor.
     *
     * @param  mixed  $invalidKey  The invalid key used for construction.
     * @param  class-string<Enum<mixed>>  $enumClass  The class name of the Enum.
     * @param  Throwable|null  $cause  The original exception that caused this exception (optional).
     * @param  string|int|null  $code  The error code associated with the exception (optional).
     * @param  string[]|null  $context  Additional context or data related to the exception (optional).
     */
    public static function make(
        mixed $invalidKey,
        string $enumClass,
    ) {
        // Determine the type of the invalid key
        $invalidValueType = gettype($invalidKey);

        // Retrieve valid keys from the Enum class
        $enumKeys = Php::implode(', ', $enumClass::getKeys());

        // Get the name of the Enum class for the error message
        $enumClassName = class_basename($enumClass);

        // Construct the error message providing detailed information
        $message = __(
            'Cannot construct an instance of %1 using the key (%2) `%3`. Possible keys are [%4].',
            $enumClassName,    // Class name of the Enum
            $invalidValueType, // Type of the invalid key
            $invalidKey,       // The invalid key value
            $enumKeys,          // List of valid keys
        );

        // Create and return a new instance of the exception with the appropriate message
        return new self($message->render(), HttpStatusCodes::BAD_REQUEST);
    }
}
