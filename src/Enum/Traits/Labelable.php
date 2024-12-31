<?php

declare(strict_types=1);

namespace Maginium\Framework\Enum\Traits;

use Maginium\Framework\Enum\Attributes\Label;
use Maginium\Framework\Support\Validator;

/**
 * Trait Labelable.
 *
 * Provides methods to retrieve labels for enum values and the enum class.
 * The label can be fetched from attributes or fallback mechanisms.
 */
trait Labelable
{
    /**
     * The label of one of the enum members.
     */
    public string $label;

    /**
     * Get the label for a given enum value.
     *
     * This method attempts to retrieve the label for an enum value.
     * It first checks if a label attribute is available for the enum value.
     * If not, it falls back to a friendly name or other mechanisms.
     *
     * @param  mixed  $value  The enum value to retrieve the label for.
     *
     * @return string|null The label of the enum value, or null if no label is found.
     */
    public static function getLabel(mixed $value): ?string
    {
        // Ensure the provided value is a valid string (or can be processed as such)
        if (! Validator::isString($value)) {
            return null;
        }

        // First, attempt to fetch a label from the enum's attributes
        $label = static::getAttributeLabel($value);

        // If no label is found in the attributes, attempt to get a friendly name
        return $label ?? static::getFriendlyName(static::getKey($value));
    }

    /**
     * Get the label of the enum class.
     *
     * This method attempts to retrieve the class-level label attribute.
     * If none is found, it falls back to using the enum's short name as a friendly fallback.
     *
     * @return string The label of the enum class.
     */
    public static function getClassLabel(): string
    {
        // Try to retrieve a class-level label using the class attribute
        return static::getClassAttributeLabel() ?? static::getFriendlyName(static::getReflection()->getShortName());
    }

    /**
     * Get the label for a specific enum value based on its attribute.
     *
     * This method fetches the label using the `Label` attribute of the enum value.
     *
     * @param  mixed  $value  The enum value to retrieve the label for.
     *
     * @return string|null The label associated with the enum value, or null if not found.
     */
    protected static function getAttributeLabel(mixed $value): ?string
    {
        // Fetch the label using the attribute helper method
        return static::getAttributeValue($value, Label::class, 'label');
    }

    /**
     * Get the class-level label from the `Label` attribute.
     *
     * This method retrieves the class-level label if it exists.
     * If no label is found at the class level, it returns null.
     *
     * @return string|null The class-level label, or null if not found.
     */
    protected static function getClassAttributeLabel(): ?string
    {
        // Fetch the class-level label attribute value
        return static::getAttribute(null, Label::class, 'label');
    }
}
