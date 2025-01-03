<?php

declare(strict_types=1);

namespace Maginium\Framework\Dto\Reflection;

use Maginium\Framework\Dto\Attributes\CastWith;
use Maginium\Framework\Dto\Attributes\DefaultCast;
use Maginium\Framework\Dto\Attributes\MapFrom;
use Maginium\Framework\Dto\DataTransferObject;
use Maginium\Framework\Dto\Interfaces\CasterInterface;
use Maginium\Framework\Dto\Interfaces\ValidatorInterface;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Reflection;
use Maginium\Framework\Support\Validator;
use ReflectionAttribute;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;

/**
 * Represents a property of a DataTransferObject with additional functionality
 * such as validation, casting, and resolving mapped property names.
 *
 * The `DataTransferObjectProperty` class provides methods to get and set values
 * on DataTransferObject properties, handle custom casting logic, and resolve
 * any validation or mapping attributes associated with the property.
 */
class DataTransferObjectProperty
{
    /**
     * The name of the property in the data transfer object.
     * This value can either be the property name as declared in the DTO or a mapped name if specified by the `MapFrom` attribute.
     *
     * @var string
     */
    public string $name;

    /**
     * The DataTransferObject instance the property belongs to.
     *
     * @var DataTransferObject
     */
    private DataTransferObject $dataTransferObject;

    /**
     * The ReflectionProperty instance representing the property.
     *
     * @var ReflectionProperty
     */
    private ReflectionProperty $reflectionProperty;

    /**
     * The custom caster used to cast the property value, if any.
     *
     * @var CasterInterface|null
     */
    private ?CasterInterface $caster;

    /**
     * Constructor for the DataTransferObjectProperty.
     *
     * Initializes the property with a DataTransferObject and ReflectionProperty.
     *
     * @param DataTransferObject $dataTransferObject The DataTransferObject the property belongs to.
     * @param ReflectionProperty $reflectionProperty The reflection of the property.
     */
    public function __construct(
        DataTransferObject $dataTransferObject,
        ReflectionProperty $reflectionProperty,
    ) {
        $this->dataTransferObject = $dataTransferObject;
        $this->reflectionProperty = $reflectionProperty;

        // Resolve the name for mapped properties, if any.
        $this->name = $this->resolveMappedProperty();

        // Resolve the caster for custom casting, if any.
        $this->caster = $this->resolveCaster();
    }

    /**
     * Sets the value of the property, applying a caster if defined.
     *
     * @param mixed $value The value to set for the property.
     */
    public function setValue(mixed $value): void
    {
        // Apply casting if a caster is defined.
        if ($this->caster && $value !== null) {
            $value = $this->caster->cast($value);
        }

        // Set the property value on the DataTransferObject.
        $this->reflectionProperty->setValue($this->dataTransferObject, $value);
    }

    /**
     * Retrieves the validators for the property, if any.
     *
     * @return ValidatorInterface[] An array of Validator instances for this property.
     */
    public function getValidators(): array
    {
        // Get attributes for the Validator class on the property.
        $attributes = $this->reflectionProperty->getAttributes(
            ValidatorInterface::class,
            ReflectionAttribute::IS_INSTANCEOF,
        );

        // Map the attributes to Validator instances.
        return Arr::each(
            fn(ReflectionAttribute $attribute) => $attribute->newInstance(),
            $attributes,
        );
    }

    /**
     * Check if a property has a specific attribute.
     *
     * @param string $attributeClass The fully qualified class name of the attribute.
     *
     * @return bool True if the property has the specified attribute, false otherwise.
     */
    public function hasAttribute(string $attributeClass): bool
    {
        // Retrieve all attributes for the property
        $attributes = $this->reflectionProperty->getAttributes($attributeClass);

        // Return true if the attribute exists, otherwise false
        return ! Validator::isEmpty($attributes);
    }

    /**
     * Gets the name of the property.
     *
     * @return mixed The name of the property.
     */
    public function getName(): mixed
    {
        return $this->reflectionProperty->getName();
    }

    /**
     * Gets the value of the property.
     *
     * @return mixed The value of the property.
     */
    public function getValue(): mixed
    {
        return $this->reflectionProperty->getValue($this->dataTransferObject);
    }

    /**
     * Gets the default value of the property, if defined.
     *
     * @return mixed The default value of the property.
     */
    public function getDefaultValue(): mixed
    {
        return $this->reflectionProperty->getDefaultValue();
    }

    /**
     * Gets the default value of the property, if defined.
     *
     * @return mixed The default value of the property.
     */
    public function getType(): string
    {
        return $this->reflectionProperty->getType()->getName();
    }

    /**
     * Resolves the caster for the property by checking for a `CastWith` attribute,
     * or inferring it based on the property's type or default settings.
     *
     * @return CasterInterface|null The caster for the property, or null if no caster is found.
     */
    private function resolveCaster(): ?CasterInterface
    {
        // Look for a `CastWith` attribute on the property.
        $attributes = $this->reflectionProperty->getAttributes(CastWith::class);

        // If no `CastWith` attribute, try to resolve from the type.
        if (! count($attributes)) {
            $attributes = $this->resolveCasterFromType();
        }

        // If no caster found yet, try resolving a default caster.
        if (! count($attributes)) {
            return $this->resolveCasterFromDefaults();
        }

        /** @var CastWith $attribute */
        $attribute = $attributes[0]->newInstance();

        // Create the caster from the `CastWith` attribute.
        return new $attribute->casterClass(
            Arr::each(fn($type) => $this->resolveTypeName($type), $this->extractTypes()),
            ...$attribute->args,
        );
    }

    /**
     * Resolves the caster from the property type if it is a valid class.
     *
     * @return ReflectionAttribute[] An array of `CastWith` attributes found in the type.
     */
    private function resolveCasterFromType(): array
    {
        foreach ($this->extractTypes() as $type) {
            $name = $this->resolveTypeName($type);

            // If the type is a class, check for a `CastWith` attribute on the class.
            if (! class_exists($name)) {
                continue;
            }

            $reflectionClass = Reflection::getClass($name);

            // Look for the `CastWith` attribute in the class or its parents.
            do {
                $attributes = $reflectionClass->getAttributes(CastWith::class);
                $reflectionClass = $reflectionClass->getParentClass();
            } while (! count($attributes) && $reflectionClass);

            // If a caster is found, return the attributes.
            if (Validator::isEmpty($attributes)) {
                return $attributes;
            }
        }

        return [];
    }

    /**
     * Resolves the default caster from the `DefaultCast` attributes found in the class hierarchy.
     *
     * @return CasterInterface|null A default caster, if found; otherwise, null.
     */
    private function resolveCasterFromDefaults(): ?CasterInterface
    {
        $defaultCastAttributes = [];
        $class = $this->reflectionProperty->getDeclaringClass();

        // Traverse the class hierarchy to find `DefaultCast` attributes.
        do {
            Arr::push($defaultCastAttributes, ...$class->getAttributes(DefaultCast::class));
            $class = $class->getParentClass();
        } while ($class !== false);

        // If no `DefaultCast` attributes found, return null.
        if (! count($defaultCastAttributes)) {
            return null;
        }

        // Iterate through each default cast and check if it applies to this property.
        foreach ($defaultCastAttributes as $defaultCastAttribute) {
            /** @var DefaultCast $defaultCast */
            $defaultCast = $defaultCastAttribute->newInstance();

            if ($defaultCast->accepts($this->reflectionProperty)) {
                return $defaultCast->resolveCaster();
            }
        }

        return null;
    }

    /**
     * Resolves the mapped name for the property using the `MapFrom` attribute, if defined.
     *
     * @return string|int The resolved mapped name of the property.
     */
    private function resolveMappedProperty(): string|int
    {
        $attributes = $this->reflectionProperty->getAttributes(MapFrom::class);

        // If no mapping is defined, return the original property name.
        if (! count($attributes)) {
            return $this->reflectionProperty->name;
        }

        // Otherwise, return the mapped name from the `MapFrom` attribute.
        return $attributes[0]->newInstance()->name;
    }

    /**
     * Extracts and returns the types associated with the property.
     *
     * @return ReflectionNamedType[] Array of ReflectionNamedType instances.
     */
    private function extractTypes(): array
    {
        $type = $this->reflectionProperty->getType();

        // If the property has no type, return an empty array.
        if (! $type) {
            return [];
        }

        return match ($type::class) {
            ReflectionNamedType::class => [$type],
            ReflectionUnionType::class => $type->getTypes(),
        };
    }

    /**
     * Resolves the full class name for the given ReflectionType.
     *
     * @param ReflectionType $type The ReflectionType to resolve.
     *
     * @return string The resolved class name.
     */
    private function resolveTypeName(ReflectionType $type): string
    {
        if ($type instanceof ReflectionNamedType) {
            return match ($type->getName()) {
                'self' => $this->dataTransferObject::class,
                'parent' => get_parent_class($this->dataTransferObject),
                default => $type->getName(),
            };
        }

        // Handle union types or other types if needed
        if ($type instanceof ReflectionUnionType) {
            // In case of union types, you may want to handle each individual type separately
            return implode('|', Arr::each(fn(ReflectionNamedType $namedType) => $namedType->getName(), $type->getTypes()));
        }

        // Default fallback if the type doesn't match expected ones
        return (string)$type;
    }
}
