<?php

declare(strict_types=1);

namespace Maginium\Framework\Dto\Reflection;

use Maginium\Framework\Dto\Attributes\Strict;
use Maginium\Framework\Dto\Attributes\Validation\Required;
use Maginium\Framework\Dto\DataTransferObject;
use Maginium\Framework\Dto\Exceptions\ValidationException;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Reflection;
use Maginium\Framework\Support\Validator;
use ReflectionClass;
use ReflectionProperty;

/**
 * Class to reflect on a DataTransferObject and manage its properties, validation, and strictness.
 *
 * The `DataTransferObjectClass` handles the reflection of a given `DataTransferObject`. It provides methods
 * to retrieve the object's properties, validate them, and check if the object adheres to strict validation
 * rules defined by the `Strict` attribute.
 */
class DataTransferObjectClass
{
    /**
     * @var ReflectionClass The reflection of the DataTransferObject class.
     */
    private ReflectionClass $reflectionClass;

    /**
     * @var DataTransferObject The instance of the DataTransferObject being reflected upon.
     */
    private DataTransferObject $dataTransferObject;

    /**
     * @var bool|null Indicates whether the DataTransferObject has strict validation enabled.
     */
    private ?bool $isStrict = null;

    /**
     * Constructor for the `DataTransferObjectClass`.
     *
     * Initializes the reflection class and sets the DataTransferObject instance.
     *
     * @param DataTransferObject $dataTransferObject The DataTransferObject to reflect upon.
     */
    public function __construct(DataTransferObject $dataTransferObject)
    {
        $this->dataTransferObject = $dataTransferObject;

        $this->reflectionClass = Reflection::getClass($dataTransferObject);
    }

    /**
     * Retrieves all public properties of the DataTransferObject that are not static.
     *
     * This method filters out any static properties and returns an array of `DataTransferObjectProperty`
     * instances that represent the public properties of the DataTransferObject.
     *
     * @return DataTransferObjectProperty[] Array of DataTransferObjectProperty instances.
     */
    public function getProperties(): array
    {
        // Get all public, non-static properties from the DataTransferObject class.
        $publicProperties = Arr::filter(
            $this->reflectionClass->getProperties(ReflectionProperty::IS_PUBLIC),
            fn(ReflectionProperty $property) => ! $property->isStatic(),
        );

        // Map each property to a DataTransferObjectProperty instance.
        return Arr::each(
            fn(ReflectionProperty $property) => new DataTransferObjectProperty(
                $this->dataTransferObject,
                $property,
            ),
            $publicProperties,
        );
    }

    /**
     * Validates the DataTransferObject properties using the defined validators.
     *
     * This method iterates through each property of the DataTransferObject, checks for
     * the `Required` attribute, and validates properties with associated validators.
     * If validation fails for any property, the validation errors are collected and
     * thrown in a `ValidationException`.
     *
     * @throws ValidationException If validation fails for any property.
     */
    public function validate(): void
    {
        $validationErrors = [];

        // Iterate through all properties and validate each one.
        foreach ($this->getProperties() as $property) {
            $propertyValue = $property->getValue();

            // Skip validation for null/empty properties without the `Required` attribute
            if (Validator::isEmpty($propertyValue) && ! $property->hasAttribute(Required::class)) {
                continue;
            }

            // Retrieve validators associated with the property
            $validators = $property->getValidators();

            foreach ($validators as $validator) {
                $result = $validator->validate($propertyValue);

                // If the validator is valid, continue to the next one
                if ($result->isValid) {
                    continue;
                }

                // Collect validation errors
                $validationErrors[$property->name][] = $result;
            }
        }

        // If validation errors exist, throw an exception with the collected errors
        if (! Validator::isEmpty($validationErrors)) {
            throw ValidationException::make($this->dataTransferObject, $validationErrors);
        }
    }

    /**
     * Checks if the DataTransferObject class is marked as strict.
     *
     * The strictness is determined by the presence of the `Strict` attribute. This method will search through
     * the class and its parent classes for the attribute.
     *
     * @return bool Returns `true` if strict validation is enabled, otherwise `false`.
     */
    public function isStrict(): bool
    {
        // Return cached value of strict if already determined
        if ($this->isStrict !== null) {
            return $this->isStrict;
        }

        $attribute = null;
        $reflectionClass = $this->reflectionClass;

        // Check for the Strict attribute in the current class and its parents
        while ($attribute === null && $reflectionClass !== false) {
            $attribute = $reflectionClass->getAttributes(Strict::class)[0] ?? null;
            $reflectionClass = $reflectionClass->getParentClass();
        }

        // Cache and return the result based on whether the Strict attribute was found.
        return $this->isStrict = $attribute !== null;
    }
}
