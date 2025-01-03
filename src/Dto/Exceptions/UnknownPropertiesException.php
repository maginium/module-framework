<?php

declare(strict_types=1);

namespace Maginium\Framework\Dto\Exceptions;

use Exception;
use Maginium\Framework\Support\Facades\Json;

/**
 * Exception thrown when unknown properties are provided to a Data Transfer Object (DTO).
 *
 * This exception is thrown when an attempt is made to pass properties to a DTO class that does not recognize them.
 * The exception is typically triggered when an invalid or unsupported property is provided for mapping to a DTO.
 */
class UnknownPropertiesException extends Exception
{
    /**
     * Factory method to create a new instance of the `UnknownPropertiesException`.
     *
     * This method creates and returns an exception instance with a detailed message indicating
     * that the provided DTO class does not recognize the provided properties.
     * The message is formatted with the class name and the list of unknown properties.
     *
     * @param string $dtoClass The name of the DTO class that does not recognize the properties.
     * @param array $fields The list of properties that are unknown to the provided DTO class.
     *
     * @return self A new instance of the `UnknownPropertiesException`.
     */
    public static function make(string $dtoClass, array $fields): self
    {
        // Convert the list of unknown properties to a JSON-encoded string for the exception message.
        $properties = Json::encode($fields);

        // Create and return a new instance of the exception with the appropriate message.
        return new self(
            __('Unknown properties provided to `%1`: %2', $dtoClass, $properties)->render(),
        );
    }
}
