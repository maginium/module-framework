<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Exceptions;

use Maginium\Foundation\Exceptions\Exception;

/**
 * Class RepositoryException.
 *
 * Custom exception thrown when an error occurs related to a repository operation.
 * This can be used to handle situations where a specific list or resource is not found
 * in a repository or object. For example, if you attempt to retrieve a list that doesn't
 * exist or isn't available in the given object, this exception will be triggered.
 */
class RepositoryException extends Exception
{
    /**
     * Throws an exception when a list is not found in a given object.
     *
     * This static method is used to generate an exception specifically when a certain list
     * is expected but is not found in the provided object. The exception message is dynamically
     * generated, giving detailed information about the list that was not found and the class
     * in which it was expected.
     *
     * @param string $list   The name of the list that was not found.
     * @param object $object The object in which the list was expected.
     *
     * @return static Returns a new instance of the RepositoryException with the error message.
     */
    public static function listNotFound(string $list, object $object): self
    {
        // Generate the exception message using sprintf for clarity and proper formatting
        // The message includes the list name and the class where it was expected
        $message = sprintf('Given list "%s" not found in %s class', $list, get_class($object));

        // Return a new RepositoryException instance with the formatted message
        return static::make($message);
    }
}
