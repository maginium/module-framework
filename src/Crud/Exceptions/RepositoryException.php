<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Exceptions;

use Maginium\Foundation\Exceptions\Exception;

class RepositoryException extends Exception
{
    /**
     * Throws an exception when a list is not found in a given object.
     *
     * @param string $list   The name of the list that was not found.
     * @param object $object The object in which the list was expected.
     *
     * @return static
     */
    public static function listNotFound(string $list, object $object): self
    {
        // Generate the exception message using sprintf for clarity and formatting
        $message = sprintf('Given list "%s" not found in %s class', $list, get_class($object));

        // Return a new RepositoryException with the formatted message
        return static::make($message);
    }
}
