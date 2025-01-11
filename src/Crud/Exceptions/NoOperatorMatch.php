<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Exceptions;

use InvalidArgumentException;

/**
 * Class NoOperatorMatch.
 *
 * Custom exception to be thrown when no matching operator is found for a request.
 *
 * This exception is used when a user or system provides a filter or request with an operator
 * that does not match any of the supported operators. The exception message includes a list of
 * supported operators, helping to debug or guide the user to use a valid operator.
 */
class NoOperatorMatch extends InvalidArgumentException
{
    /**
     * Factory method to create a new instance of the exception.
     *
     * This method generates an exception with a detailed message that lists the supported operators
     * when no matching operator is found in the filters provided.
     *
     * @param array $filters An array of supported operators.
     *
     * @return NoOperatorMatch The created exception instance.
     */
    public static function make(array $filters)
    {
        // Construct the exception message that details the error and lists the supported operators.
        return new static(
            'No operator matches your request. Supported operators: ' . implode(', ', $filters),
        );
    }
}
