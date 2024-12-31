<?php

declare(strict_types=1);

namespace Maginium\Framework\Enum\Traits;

use Maginium\Framework\Enum\Attributes\Description;
use Maginium\Framework\Support\Validator;

/**
 * Trait Descriptionable.
 *
 * Provides methods to retrieve descriptions for enum values and the enum class.
 * The description can be fetched from attributes or fallback mechanisms.
 */
trait Descriptionable
{
    /**
     * The description of one of the enum members.
     */
    public string $description;

    /**
     * Get the description for a given enum value.
     *
     * This method attempts to retrieve the description for an enum value.
     * It first checks if a description attribute is available for the enum value.
     * If not, it falls back to a friendly name or other mechanisms.
     *
     * @param  mixed  $value  The enum value to retrieve the description for.
     *
     * @return string|null The description of the enum value, or null if no description is found.
     */
    public static function getDescription(mixed $value): ?string
    {
        // Ensure the provided value is a valid string (or can be processed as such)
        if (! Validator::isString($value)) {
            return null;
        }

        // First, attempt to fetch a description from the enum's attributes
        $description = static::getAttributeDescription($value);

        // If no description is found in the attributes, attempt to get a friendly name
        return $description ?? static::getFriendlyName(static::getKey($value));
    }

    /**
     * Get the description of the enum class.
     *
     * This method attempts to retrieve the class-level description attribute.
     * If none is found, it falls back to using the enum's short name as a friendly fallback.
     *
     * @return string The description of the enum class.
     */
    public static function getClassDescription(): string
    {
        // Try to retrieve a class-level description using the class attribute
        return static::getClassAttributeDescription() ?? static::getFriendlyName(static::getReflection()->getShortName());
    }

    /**
     * Get the description for a specific enum value based on its attribute.
     *
     * This method fetches the description using the `Description` attribute of the enum value.
     *
     * @param  mixed  $value  The enum value to retrieve the description for.
     *
     * @return string|null The description associated with the enum value, or null if not found.
     */
    protected static function getAttributeDescription(mixed $value): ?string
    {
        // Fetch the description using the attribute helper method
        return static::getAttributeValue($value, Description::class, 'description');
    }

    /**
     * Get the class-level description from the `Description` attribute.
     *
     * This method retrieves the class-level description if it exists.
     * If no description is found at the class level, it returns null.
     *
     * @return string|null The class-level description, or null if not found.
     */
    protected static function getClassAttributeDescription(): ?string
    {
        // Fetch the class-level description attribute value
        return static::getAttribute(null, Description::class, 'description');
    }
}
