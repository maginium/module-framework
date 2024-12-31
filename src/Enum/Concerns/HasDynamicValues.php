<?php

declare(strict_types=1);

namespace Maginium\Framework\Enum\Concerns;

use Maginium\Framework\Support\Arr;
use Override;

/**
 * Trait HasDynamicValues.
 *
 * This trait provides functionality for adding and managing dynamic enum values
 * in classes that use it. It supports both statically defined constants and dynamically
 * added values.
 */
trait HasDynamicValues
{
    /**
     * Store dynamically defined enum values.
     *
     * @var array<string, mixed> An associative array of enum values where keys are the enum names.
     */
    protected static array $enumValues = [];

    /**
     * Optional initialization method.
     *
     * Can be overridden by child classes to set up any necessary data or operations
     * when the class is first accessed.
     */
    protected static function init(): void
    {
        // Default implementation does nothing, can be overridden by child classes.
    }

    /**
     * Define a new enum value dynamically.
     *
     * Adds a new value to the enum if it doesn't already exist.
     *
     * @param  string  $key  The key for the new enum value.
     * @param  mixed  $value  The value of the new enum entry.
     */
    protected static function defineEnumValue(string $key, mixed $value): void
    {
        if (! isset(static::$enumValues[$key])) {
            static::$enumValues[$key] = $value;
        }
    }

    /**
     * Get all constants defined on the class excluding private constants.
     *
     * This method retrieves all constants defined within the class, excluding any private constants.
     * It also merges the dynamically defined enum values into the result.
     *
     * @return array<string, mixed> The list of constants including dynamically defined values.
     */
    #[Override]
    protected static function getConstants(): array
    {
        // Initialize the enum if necessary (e.g., to load any dynamic values).
        static::init();

        // Get all constants from the class using reflection
        $constants = static::getReflection()->getConstants();

        // Filter out private constants using reflection
        $constants = Arr::filter($constants, fn($name) => ! static::isPrivateConstant($name), ARRAY_FILTER_USE_KEY);

        // Merge constants with the dynamically defined enum values
        return Arr::merge($constants, static::$enumValues);
    }

    /**
     * Initialize an Enum instance.
     *
     * This method is called during instance construction. It invokes the init method
     * to allow for optional initialization.
     */
    protected function _construct(): void
    {
        // Call the init method to perform optional setup tasks.
        static::init();
    }
}
