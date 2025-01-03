<?php

declare(strict_types=1);

namespace Maginium\Framework\Dto\Exceptions;

use Exception;
use Maginium\Framework\Dto\Interfaces\CasterInterface;

/**
 * Exception thrown when a class does not implement the required `Caster` interface.
 *
 * This exception is thrown when an attempt is made to use a class as a caster, but that class does not implement
 * the `Caster` interface. The `Caster` interface is expected to define necessary methods for transforming data
 * when mapping between different object types.
 */
class InvalidCasterClassException extends Exception
{
    /**
     * Factory method to create a new instance of the `InvalidCasterClassException`.
     *
     * This method creates and returns an exception instance with a detailed message indicating
     * that the provided class does not implement the `Caster` interface, which is required for it
     * to be used as a caster.
     *
     * @param string $className The name of the class that does not implement `Caster`.
     *
     * @return self A new instance of the `InvalidCasterClassException`.
     */
    public static function make(string $className): self
    {
        // Define the expected class, which is the `Caster` interface.
        $expected = CasterInterface::class;

        // Create and return a new instance of the exception with the appropriate message
        return new self(
            __("Class `%1` doesn't implement %2 and can't be used as a caster", $className, $expected)->render(),
        );
    }
}
