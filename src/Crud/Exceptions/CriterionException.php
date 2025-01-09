<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Exceptions;

use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Crud\Interfaces\CriterionInterface;

class CriterionException extends Exception
{
    /**
     * Throws an exception if the provided criterion is of an incorrect type.
     *
     * @param mixed $criterion The criterion whose type is being checked.
     *
     * @return static
     */
    public static function wrongCriterionType($criterion): self
    {
        // Get the type of the criterion
        $type = gettype($criterion);

        // Get the class name if the criterion is an object, otherwise return the value as-is
        $value = $type === 'object' ? get_class($criterion) : (string)$criterion;

        // Return a new exception with a formatted message
        return static::make(
            sprintf('Given criterion with type %s and value %s is not allowed', $type, $value),
        );
    }

    /**
     * Throws an exception if the given class does not implement the CriterionInterface contract.
     *
     * @param string $criterionClassName The class name that is expected to implement CriterionInterface.
     *
     * @return static
     */
    public static function classNotImplementContract(string $criterionClassName): self
    {
        // Return a new exception indicating the class does not implement the required contract
        return static::make(
            sprintf('Given class %s does not implement the %s contract', $criterionClassName, CriterionInterface::class),
        );
    }

    /**
     * Throws an exception if the array signature for criterion instantiation is incorrect.
     *
     * @param array $criterion The array that is being validated for correct signature.
     *
     * @return static
     */
    public static function wrongArraySignature(array $criterion): self
    {
        // Get the length of the array
        $arrayLength = count($criterion);

        // Return a new exception with a message explaining the incorrect array signature
        return static::make(
            sprintf(
                'Array signature for criterion instantiation must contain only two elements in case of a sequential array and one element in case of an associative array. ' .
                'Array with length "%d" given.',
                $arrayLength,
            ),
        );
    }
}
