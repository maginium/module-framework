<?php

declare(strict_types=1);

namespace Spatie\LaravelData\Exceptions;

use Exception;

/**
 * Class InvalidDataClass.
 *
 * Exception thrown when a class fails to meet the expected data class requirements.
 * Specifically, it indicates that the class either does not exist or does not implement the
 * necessary interface for creating a valid Data Transfer Object (DTO).
 */
class InvalidDataClass extends Exception
{
    /**
     * Constructor for the `InvalidDataClass` exception.
     *
     * This static method generates an exception with a detailed message indicating why
     * the Data Transfer Object (DTO) could not be created. The message varies based on
     * whether the class was provided or not, and whether it implements the necessary interface.
     *
     * @param string|null $class The name of the class that was provided or null if no class was given.
     *
     * @return self A new instance of the InvalidDataClass exception with an appropriate message.
     */
    public static function make(?string $class): self
    {
        // Construct the appropriate error message depending on whether a class is provided
        $message = $class === null
            ? __('Could not create a Data object, no data class was given')
            : __("Could not create a Data object, `{$class}` does not implement `Data`");

        // Create and return a new instance of the exception with the provided message and HTTP status code
        return new self(
            $message->render(), // Exception message
        );
    }
}
