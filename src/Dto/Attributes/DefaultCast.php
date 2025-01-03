<?php

declare(strict_types=1);

namespace Maginium\Framework\Dto\Attributes;

use Attribute;
use Maginium\Framework\Actions\Concerns\AsAction;
use Maginium\Framework\Dto\Casters\Caster;
use Maginium\Framework\Dto\Interfaces\CasterInterface;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;

/**
 * Custom attribute used to define the default caster for a property in a DTO.
 *
 * The `DefaultCast` attribute is applied to a class or property to specify the default
 * caster class responsible for transforming the property value. The attribute ensures
 * that a specific caster is used based on the property type.
 *
 * This attribute can be repeated to apply different casters to different types.
 *
 * @example
 * #[DefaultCast('SomeClass', 'SomeCaster')]
 * public SomeClass $someProperty;
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class DefaultCast
{
    // Using the AsAction trait to add common action functionality
    use AsAction;

    /**
     * The fully-qualified name of the target class.
     *
     * This property holds the name of the class that the caster is responsible
     * for transforming. This is the target type that the caster will handle.
     *
     * @var string
     */
    private string $targetClass;

    /**
     * The fully-qualified name of the caster class.
     *
     * This property holds the name of the caster class that will be used to
     * transform the property value. The caster class must implement the `Caster` interface.
     *
     * @var string
     */
    private string $casterClass;

    /**
     * Constructor to initialize the `DefaultCast` attribute.
     *
     * The constructor ensures that the provided `casterClass` and `targetClass`
     * are properly set. These values are used to map a specific class to a caster.
     *
     * @param string $targetClass The fully-qualified name of the target class.
     * @param string $casterClass The fully-qualified name of the caster class.
     */
    public function __construct(
        string $targetClass,
        string $casterClass,
    ) {
        $this->targetClass = $targetClass;
        $this->casterClass = $casterClass;
    }

    /**
     * Determines if the attribute is applicable to the provided property.
     *
     * This method checks whether the type of the given property matches the target class
     * specified in the attribute. It supports both individual class types and union types.
     *
     * @param ReflectionProperty $property The property to check.
     *
     * @return bool True if the property type matches the target class, false otherwise.
     */
    public function accepts(ReflectionProperty $property): bool
    {
        // Get the type of the property
        $type = $property->getType();

        /** @var ReflectionNamedType[]|null $types */
        // Determine if the type is a single named type or a union of types
        $types = match ($type::class) {
            ReflectionNamedType::class => [$type], // Single type
            ReflectionUnionType::class => $type->getTypes(), // Union of types
            default => null, // Other types are unsupported
        };

        // If no valid types are found, return false
        if (! $types) {
            return false;
        }

        // Check if any of the types match the target class
        foreach ($types as $type) {
            if ($type->getName() !== $this->targetClass) {
                continue;
            }

            // If a match is found, return true
            return true;
        }

        // Return false if no match was found
        return false;
    }

    /**
     * Resolves the caster class specified in the attribute.
     *
     * This method instantiates and returns an instance of the caster class
     * that was provided in the attribute's constructor.
     *
     * @return CasterInterface An instance of the caster class.
     */
    public function resolveCaster(): CasterInterface
    {
        // Return a new instance of the caster class
        return new $this->casterClass;
    }
}
