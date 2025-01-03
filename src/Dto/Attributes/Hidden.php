<?php

declare(strict_types=1);

namespace Maginium\Framework\Dto\Attributes;

use Attribute;
use Maginium\Framework\Actions\Concerns\AsAction;

/**
 * The `Hidden` attribute marks a property in a Data Transfer Object (DTO) as hidden.
 * When applied to a property, it indicates that this property should not be included
 * when the DTO is serialized or converted to an array.
 *
 * This can be useful for properties that are internal, sensitive, or not meant to
 * be exposed as part of the DTO's public API. The `Hidden` attribute ensures that
 * these properties are excluded from serialization, providing a way to protect or
 * hide certain properties from being exposed or shared.
 *
 * This attribute is typically used with serialization libraries or custom logic to
 * filter out hidden properties when converting DTOs to arrays, JSON, or other formats.
 *
 * @example
 * class UserDto {
 *     #[Hidden]
 *     public string $password; // This property will be excluded from serialization
 * }
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Hidden
{
    // Using the AsAction trait to add common action functionality
    use AsAction;

    // This class is empty as it is only used for marking properties with the Hidden attribute.
}
