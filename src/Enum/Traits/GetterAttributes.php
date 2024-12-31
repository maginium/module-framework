<?php

declare(strict_types=1);

namespace Maginium\Framework\Enum\Traits;

use Maginium\Foundation\Exceptions\LocalizedException;
use Maginium\Framework\Enum\Exceptions\InvalidEnumMemberException;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Php;
use Maginium\Framework\Support\Str;
use Maginium\Framework\Support\Validator;

/**
 * Trait GetterAttributes.
 *
 * Provides methods to retrieve enum values and keys, along with random selections,
 * and attributes associated with enum members.
 *
 * @property mixed $enumValues The enum values associated with the class.
 */
trait GetterAttributes
{
    /**
     * Get all or a custom set of the enum keys.
     *
     * If no specific values are provided, returns all keys. Otherwise, maps the provided
     * values to their corresponding enum keys.
     *
     * @param  mixed|null  $values  The values for which keys should be retrieved.
     *
     * @return array Array of enum keys.
     */
    public static function getKeys(mixed $values = null): array
    {
        if ($values === null) {
            // Return all keys if no specific values are provided
            return Arr::keys(static::getConstants());
        }

        // Map keys for the provided values
        return Arr::each(
            [static::class, 'getKey'],
            Validator::isArray($values) ? $values : func_get_args(),
        );
    }

    /**
     * Get all or a custom set of the enum values.
     *
     * If no specific keys are provided, returns all values. Otherwise, maps the provided
     * keys to their corresponding enum values.
     *
     * @param  string|array<string>|null  $keys  The keys for which values should be retrieved.
     *
     * @return mixed[] Array of enum values.
     */
    public static function getValues(string|array|null $keys = null): array
    {
        if ($keys === null) {
            // Return all values if no specific keys are provided
            return Arr::values(static::getConstants());
        }

        // Map values for the provided keys
        return Arr::each(
            [static::class, 'getValue'],
            Validator::isArray($keys) ? $keys : func_get_args(),
        );
    }

    /**
     * Get the key for a single enum value.
     *
     * This method searches for the key associated with the provided value.
     * If the value is not a valid enum member, it will return null.
     *
     * @param  mixed  $value  The enum value to retrieve the key for.
     *
     * @throws InvalidEnumMemberException If the provided value is not a valid enum member.
     *
     * @return string|null The corresponding enum key, or null if not found.
     */
    public static function getKey(mixed $value): ?string
    {
        // Get all constants for the current enum class
        $constants = static::getConstants();

        // Normalize the constants: keys to lowercase, values to lowercase
        $lowercaseConstants = array_map(
            fn($val, $key) => [mb_strtolower($key) => Str::lower($val)],
            $constants,
            array_keys($constants),
        );

        // Flatten the array since array_map creates an array of arrays
        $lowercaseConstants = Arr::merge(...$lowercaseConstants);

        // Normalize the value for case-insensitive comparison
        $value = Str::lower($value);

        // Search for the key associated with the provided value
        $foundKey = Arr::search($value, $lowercaseConstants, true);

        // Return null if the value is not found among enum members
        return $foundKey ?: null;
    }

    /**
     * Get the value for a single enum key.
     *
     * This method is case-insensitive and retrieves the associated enum value
     * for the provided key.
     *
     * @param  string  $key  The enum key to retrieve the value for.
     *
     * @return mixed The corresponding enum value, or null if the key is invalid.
     */
    public static function getValue(string $key): mixed
    {
        // Get all constants for the current enum class
        $constants = static::getConstants();

        // Normalize the constants: keys to lowercase while preserving values
        $lowercaseConstants = array_map(
            fn($val, $constKey) => [$constKey => $val],
            $constants,
            array_keys(array_change_key_case($constants, CASE_LOWER)),
        );

        // Convert to a single associative array
        $lowercaseConstants = Arr::merge(...$lowercaseConstants);

        // Convert the provided key to lowercase for case-insensitive comparison
        $key = Str::lower($key);

        // Retrieve the associated enum value or null if the key doesn't exist
        return $lowercaseConstants[$key] ?? null;
    }

    /**
     * Get a random key from the enum.
     *
     * This method returns a random enum key from the available keys.
     *
     * @return string A random enum key.
     */
    public static function getRandomKey(): string
    {
        // Get all keys of the enum
        $keys = static::getKeys();

        // Return a random key from the $keys array
        return $keys[Arr::rand($keys)];
    }

    /**
     * Get a random value from the enum.
     *
     * This method returns a random enum value from the available values.
     *
     * @return mixed A random enum value.
     */
    public static function getRandomValue(): mixed
    {
        // Get all values of the enum
        $values = static::getValues();

        // Return a random value from the $values array
        return $values[Arr::rand($values)];
    }

    /**
     * Get a random instance of the enum.
     *
     * This method returns a new instance of the enum with a random value.
     *
     * @return static A random enum instance.
     */
    public static function getRandomInstance(): static
    {
        // Return a new instance with a random enum value
        return new static(static::getRandomValue());
    }

    /**
     * Retrieves a user-friendly name for the given input string.
     *
     * This method normalizes the case, converts underscores to spaces, and capitalizes words.
     * If the class is localizable, the returned string is translated using the localization system.
     *
     * @param  string  $name  The input name string to be transformed.
     *
     * @return string The friendly, possibly translated name.
     */
    protected static function getFriendlyName(string $name): string
    {
        // Normalize case: Convert to lowercase if the name is entirely uppercase
        if (ctype_upper(Php::pregReplace('/[^a-zA-Z]/', '', $name))) {
            $name = Str::lower($name);  // Convert to lowercase if the name is all uppercase
        }

        // Convert snake_case to a space-separated string with proper capitalization
        $name = Str::capital(Str::replace('_', ' ', Str::snake($name)));

        // If the class is localizable, return the translated name
        if (static::isLocalizable()) {
            return __($name)->render();
        }

        // Return the friendly name without translation
        return $name;
    }

    /**
     * Retrieves the value of an attribute and translates it if necessary.
     *
     * This method fetches an attribute value based on the provided class and type.
     * If the current class is localizable, it returns the translated value; otherwise, it returns the raw value.
     *
     * @param  mixed  $value  The input value from which to retrieve the attribute.
     * @param  string  $attributeClass  The class to use for the attribute lookup.
     * @param  string  $type  The type of the attribute to retrieve.
     *
     * @return string|null The translated attribute value, or the raw value if not localizable, or null if not found.
     */
    private static function getAttributeValue(mixed $value, string $attributeClass, string $type): ?string
    {
        // Retrieve the attribute value using the specified class and type
        $attributeValue = static::getAttribute($value, $attributeClass, $type);

        // If the class is localizable, return the translated value
        if (static::isLocalizable()) {
            return $attributeValue ? __($attributeValue)->render() : null;
        }

        // Return the raw attribute value, or null if not found
        return $attributeValue ?: null;
    }

    /**
     * Get the attribute value of a specified type from its PHP attribute.
     *
     * This method uses reflection to retrieve the specified attribute associated with a specific value
     * by inspecting its PHP attribute. The attribute is expected to be declared on the constant
     * corresponding to the provided value.
     *
     * @param  mixed  $value  The value to retrieve the attribute for.
     * @param  string  $attributeClass  The attribute class to look for (e.g., Description::class or Label::class).
     * @param  string  $attributeType  The type of attribute being retrieved (e.g., 'description' or 'label').
     *
     * @return string|null The attribute value if available, or null if not found.
     */
    private static function getAttribute(mixed $value, string $attributeClass, string $attributeType): ?string
    {
        // Retrieve the reflection object for the current class.
        $reflection = static::getReflection();

        // Get the constant name associated with the provided value.
        $constantName = static::getKey($value);

        // Use reflection to access the constant definition by its name.
        $constReflection = $reflection->getReflectionConstant($constantName);

        if ($constReflection === false) {
            // If the reflection for the constant is not found, return null.
            return null;
        }

        // Retrieve all attributes of the specified type declared on the constant.
        $attributes = $constReflection->getAttributes($attributeClass);

        // Ensure there is at least one attribute.
        if (Validator::isEmpty($attributes)) {
            // No attribute found, return null.
            return null;
        }

        // Instantiate the attribute instance from the first attribute.
        /** @var Enum $attributesInstance */
        $attributesInstance = $attributes[0]->newInstance();

        // Return the appropriate result based on the number of attributes found.
        return match (Php::count($attributes)) {
            1 => $attributesInstance->{$attributeType}, // Return the attribute value from the first attribute.
            default => // If more than one attribute is found, throw an exception.
                throw LocalizedException::make(
                    __('You cannot use more than 1 %1 attribute on %2', [$attributeType, class_basename(static::class) . '::' . $constantName]),
                ),
        };
    }
}
