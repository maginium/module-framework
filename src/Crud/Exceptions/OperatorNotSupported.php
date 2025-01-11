<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Exceptions;

use InvalidArgumentException;

/**
 * Class OperatorNotSupported.
 *
 * Custom exception thrown when an operator is not supported for a specific field.
 *
 * This exception is used when an operator provided in a request does not match any of the
 * supported operators for the specified field. The exception message includes the operator,
 * the field, and a list of supported operators to help guide the user or developer.
 */
class OperatorNotSupported extends InvalidArgumentException
{
    /**
     * Factory method to create a new instance of the exception.
     *
     * This method generates an exception with a detailed message indicating that the provided
     * operator is not supported for the specified field. It also lists the supported operators
     * for that field.
     *
     * @param string $field The name of the field the operator was applied to.
     * @param string $operator The operator that is not supported for the field.
     * @param array $supportedOperators An array of operators that are supported for the field.
     *
     * @return OperatorNotSupported The created exception instance.
     */
    public static function make(string $field, string $operator, array $supportedOperators)
    {
        // Construct the exception message that details the error and lists the supported operators.
        return new static(
            "The operator {$operator} is not supported for the field {$field}. Supported operators: "
            . implode(', ', $supportedOperators),
        );
    }
}
