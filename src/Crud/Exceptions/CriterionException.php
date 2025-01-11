<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Exceptions;

use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Crud\Interfaces\Repositories\CriterionInterface;

/**
 * Class CriterionException.
 *
 * This exception is thrown in cases where invalid criteria are provided during
 * query operations, particularly when dealing with criterion types, classes, or
 * array signatures that are incorrect or unsupported.
 */
class CriterionException extends Exception
{
    /**
     * Throws an exception if the provided criterion is of an incorrect type.
     *
     * This method validates the type of the given criterion and raises an exception if
     * the type is not allowed. The type is compared using `gettype()`, and if it's an object,
     * the class name is retrieved using `get_class()`.
     *
     * @param mixed $criterion The criterion whose type is being checked.
     *
     * @return static The new exception instance with a formatted message.
     */
    public static function wrongCriterionType($criterion): self
    {
        // Get the type of the criterion (e.g., string, integer, object, etc.)
        $type = gettype($criterion);

        // For objects, retrieve the class name; otherwise, cast the value to a string
        $value = $type === 'object' ? get_class($criterion) : (string)$criterion;

        // Return a new exception with a message indicating the type and value of the criterion
        return static::make(
            sprintf('Given criterion with type %s and value %s is not allowed', $type, $value),
        );
    }

    /**
     * Throws an exception if the given class does not implement the CriterionInterface contract.
     *
     * This method checks if the provided class name implements the required `CriterionInterface`.
     * If the class does not implement the interface, it throws an exception with the class name
     * and the interface that should have been implemented.
     *
     * @param string $criterionClassName The class name that is expected to implement CriterionInterface.
     *
     * @return static The new exception instance with a message indicating the missing contract implementation.
     */
    public static function classNotImplementContract(string $criterionClassName): self
    {
        // Return a new exception indicating that the class does not implement the CriterionInterface contract
        return static::make(
            sprintf('Given class %s does not implement the %s contract', $criterionClassName, CriterionInterface::class),
        );
    }

    /**
     * Throws an exception if the array signature for criterion instantiation is incorrect.
     *
     * This method checks if the array passed for criterion instantiation follows the expected
     * signature. It validates if the array is either sequential with two elements or associative
     * with one element.
     *
     * @param array $criterion The array that is being validated for correct signature.
     *
     * @return static The new exception instance with a message indicating the incorrect array signature.
     */
    public static function wrongArraySignature(array $criterion): self
    {
        // Get the length of the array to validate its structure
        $arrayLength = count($criterion);

        // Return a new exception with a message explaining the correct array structure
        return static::make(
            sprintf(
                'Array signature for criterion instantiation must contain only two elements in case of a sequential array and one element in case of an associative array. ' .
                'Array with length "%d" given.',
                $arrayLength,
            ),
        );
    }
}
