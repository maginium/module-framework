<?php

declare(strict_types=1);

namespace Maginium\Framework\Dto\Attributes;

use Attribute;
use Maginium\Framework\Actions\Concerns\AsAction;
use Maginium\Framework\Dto\Exceptions\InvalidNameMapperClassException;
use Maginium\Framework\Dto\Interfaces\NameMapperInterface;

/**
 * The `MapInputName` attribute is used to map the name of a property or class
 * in a Data Transfer Object (DTO) to a custom input name during serialization and deserialization.
 *
 * This attribute allows you to specify a custom mapper class name, which can be used
 * to rename or transform a property when mapping incoming data to the correct property.
 *
 * It can be applied either at the class level (to map the class itself) or at the
 * property level (to map individual properties). This flexibility allows for better
 * control over the mapping process.
 *
 * The mapper class name can be a string (for custom names) or an integer (for specific
 * mapping cases such as array indices).
 *
 * @example
 * // Class-level usage:
 * #[MapInputName(mapperClass: 'user_data')]
 * class UserDto {
 *     // Class is mapped to 'user_data'
 * }
 *
 * // Property-level usage:
 * class UserDto {
 *     #[MapInputName(mapperClass: 'user_name')]
 *     public string $name; // Maps 'name' property to 'user_name' input field
 * }
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
class MapInputName
{
    // Using the AsAction trait to add common action functionality
    use AsAction;

    /**
     * The name or index to map the class or property.
     *
     * This can either be a string (e.g., 'user_name') or an integer (e.g., 0 for array indices).
     */
    public string|int $mapperClass;

    /**
     * Constructor for the MapInputName attribute.
     *
     * The constructor checks if the provided mapper class is valid by ensuring that
     * it implements the required caster class interface. If invalid, an exception is thrown.
     *
     * @param string|int $mapperClass The name or index to map to the class or property.
     *
     * @throws InvalidNameMapperClassException Thrown if the provided class does not implement the required Caster interface.
     */
    public function __construct(string|int $mapperClass)
    {
        // Ensure the provided class implements the Caster interface for valid mapping
        if (! is_subclass_of($mapperClass, NameMapperInterface::class)) {
            InvalidNameMapperClassException::make($mapperClass);
        }

        $this->mapperClass = $mapperClass;
    }
}
