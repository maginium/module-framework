<?php

declare(strict_types=1);

namespace Maginium\Framework\Dto\Exceptions;

use Exception;
use Maginium\Framework\Dto\Interfaces\NameMapperInterface;

/**
 * Exception thrown when a class does not implement the required `NameMapper` interface.
 *
 * This exception is thrown when an attempt is made to use a class as a name mapper, but that class does not implement
 * the `NameMapperInterface`. The `NameMapperInterface` is expected to define the necessary methods to handle the transformation
 * of data when mapping between different object types.
 */
class InvalidNameMapperClassException extends Exception
{
    /**
     * Factory method to create a new instance of the `InvalidNameMapperClassException`.
     *
     * This method creates and returns an exception instance with a detailed message indicating
     * that the provided class does not implement the `NameMapperInterface`, which is required for it
     * to be used as a name mapper.
     *
     * @param string $className The name of the class that does not implement `NameMapperInterface`.
     *
     * @return self A new instance of the `InvalidNameMapperClassException`.
     */
    public static function make(string $className): self
    {
        // Define the expected class, which is the `NameMapperInterface`.
        $expected = NameMapperInterface::class;

        // Create and return a new instance of the exception with the appropriate message.
        return new self(
            __("Class `%1` doesn't implement %2 and can't be used as a name mapper", $className, $expected)->render(),
        );
    }
}
