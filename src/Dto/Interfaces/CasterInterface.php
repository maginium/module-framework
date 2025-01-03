<?php

declare(strict_types=1);

namespace Maginium\Framework\Dto\Interfaces;

/**
 * Interface for classes that handle casting of values into specific types or formats.
 *
 * The `CasterInterface` defines the contract for any class responsible for transforming
 * a given value (e.g., an array, object, or primitive data type) into another value or
 * format. This interface is commonly used for handling data transformations in cases
 * such as data transfer objects (DTOs), arrays, or other structures where a value
 * needs to be cast into a different representation.
 */
interface CasterInterface
{
    /**
     * Cast the provided value into a desired format or type.
     *
     * This method will take an input value and transform it into a new value, which could
     * be a different type, structure, or format. The transformation logic will be
     * defined in the implementing class, depending on the use case (e.g., casting an array
     * into a specific object, or converting a string to a specific format).
     *
     * @param mixed $value The value to be cast. This could be of any type such as an array, object, or primitive.
     *
     * @return mixed The casted value, which can be of any type after the transformation.
     */
    public function cast(mixed $value): mixed;
}
