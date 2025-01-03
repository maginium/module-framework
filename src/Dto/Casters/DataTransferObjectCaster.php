<?php

declare(strict_types=1);

namespace Maginium\Framework\Dto\Casters;

use Maginium\Framework\Dto\DataTransferObject;
use Maginium\Framework\Dto\Interfaces\CasterInterface;

/**
 * Caster class for casting values to instances of a Data Transfer Object (DTO).
 *
 * The `DataTransferObjectCaster` is responsible for transforming a value into a corresponding
 * DTO instance. If the value is already an instance of a class in the provided class names list,
 * it will return that value. Otherwise, it will instantiate the first class in the list, passing the
 * provided value to the DTO constructor.
 *
 * @example
 * $caster = new DataTransferObjectCaster([SomeDTO::class]);
 * $dto = $caster->cast($someValue);
 */
class DataTransferObjectCaster implements CasterInterface
{
    /**
     * Constructor to initialize the `DataTransferObjectCaster`.
     *
     * @param array $classNames List of class names of DTOs to cast values to.
     *        The first class name in the list will be used to instantiate a new DTO
     *        if no matching class is found.
     */
    public function __construct(
        private array $classNames,
    ) {
    }

    /**
     * Cast the provided value to a Data Transfer Object (DTO).
     *
     * This method will iterate over the provided list of class names and check if the value
     * is an instance of any of the classes in the list. If a match is found, it returns
     * the value as is. If no match is found, the first class in the list is used to instantiate
     * a new DTO with the given value.
     *
     * @param mixed $value The value to be cast to a DTO.
     *
     * @return DataTransferObject The resulting DTO instance.
     */
    public function cast(mixed $value): DataTransferObject
    {
        // Iterate through the class names to check if the value is already an instance
        foreach ($this->classNames as $className) {
            if ($value instanceof $className) {
                return $value;
            }
        }

        // If no match found, create and return a new instance of the first class in the list
        return new $this->classNames[0]($value);
    }
}
