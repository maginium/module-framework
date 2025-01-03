<?php

declare(strict_types=1);

namespace Maginium\Framework\Dto\Traits;

use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Framework\Dto\DataTransferObject;
use Maginium\Framework\Support\Reflection;
use Spatie\LaravelData\Exceptions\InvalidDataClass;

/**
 * Trait WithData.
 *
 * This trait provides functionality to retrieve a Data Transfer Object (DTO) instance based on the current model.
 * It allows easy conversion of the current object to a DTO using the associated data class.
 *
 * @template T The Data Transfer Object (DTO) type that this trait is handling.
 *
 * @method DataTransferObject|string withData(?array $data = []) Create and return a DTO instance or the DTO class name based on the provided data.
 * @method DataTransferObject getData() Retrieve the associated DTO instance populated with the current model's data.
 */
trait WithData
{
    /**
     * Create and return a Data Transfer Object (DTO) instance based on the model's configuration.
     *
     * This method retrieves the DTO class associated with the model and initializes it with
     * the provided data, if any. If no data is provided, the DTO class name is returned.
     *
     * @param array|null $data Optional data to populate the DTO instance.
     *
     * @throws InvalidArgumentException If the DTO class does not exist.
     *
     * @return DataTransferObject|string The populated DTO instance or the DTO class name.
     */
    public function withData(?array $data = []): DataTransferObject|string
    {
        // Retrieve the DTO class from the model's configuration.
        /** @var string $dtoClass */
        $dtoClass = $this->model->create()->dtoClass;

        // Ensure the DTO class is valid and exists.
        if (! Reflection::exists($dtoClass)) {
            throw InvalidArgumentException::make(__(
                'The DTO class "%s" does not exist in the model configuration.',
                $dtoClass,
            ));
        }

        // Return a populated DTO instance if data is provided, otherwise return the class name.
        /** @var DataTransferObject $dtoClass */
        return $data ? $dtoClass::from($data) : $dtoClass;
    }

    /*
     * Retrieves the associated Data Transfer Object (DTO) for the current model.
     *
     * This method determines the appropriate data class to use based on the class or method
     * name and ensures that the class implements the `DataTransferObject` interface. If the class is
     * invalid, an `InvalidDataClass` exception is thrown.
     *
     * @throws InvalidDataClass If the determined data class does not implement `DataTransferObject`.
     *
     * @return DataTransferObject The DTO instance populated with the current model's data.
     */
    // public function getData(): DataTransferObject
    // {
    //     // Determine the data class either from a property or a method
    //     $dtoClass = match (true) {
    //         // If the dtoClass property exists, use it
    //         property_exists($this, 'dtoClass') && isset($this->dtoClass) => $this->dtoClass,
    //         // If the dtoClass method exists, call it to retrieve the class name
    //         Reflection::methodExists($this->factory(), 'dtoClass') => $this->factory()->dtoClass(),
    //         // Default to null if neither exists
    //         default => null,
    //     };

    //     // Ensure the determined data class implements the DataTransferObject interface
    //     if (! is_a($dtoClass, DataTransferObject::class, true)) {
    //         // Throw an exception if the class is invalid or does not implement DataTransferObject
    //         throw InvalidDataClass::make($dtoClass);
    //     }

    //     // Return the DTO instance created from the current model's data
    //     return $dtoClass::from($this);
    // }
}
