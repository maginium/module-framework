<?php

declare(strict_types=1);

namespace Maginium\Framework\Enum;

use Maginium\Framework\Enum\Exceptions\InvalidEnumMemberException;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Validator;

/**
 * Class FlaggedEnum.
 *
 * This abstract class extends the Enum class and represents an enumeration where multiple
 * values (flags) can be combined using bitwise operations. It supports setting flags via
 * integers, other enum instances, or an array of values. This class introduces a special
 * constant `NONE` representing the absence of any flags.
 *
 * @method static static NONE() Retrieve the NONE flag (no value).
 *
 * @extends Enum<int> Extends the Enum class, parameterized with an integer type.
 */
abstract class FlaggedEnum extends Enum
{
    /**
     * Represents no specific value or state.
     *
     * @var int The integer value of the none member.
     */
    public const NONE = 0;

    /**
     * Construct a FlaggedEnum instance.
     *
     * Initializes the enum with the provided flags. If an array of flags is given, it sets them; otherwise, it attempts
     * to construct the parent Enum with the provided value.
     *
     * @param  int|self|array<int|self>  $flags  The flags to initialize the enum with.
     */
    public function __construct(mixed $flags = [])
    {
        // Unset key and label properties as they should not be accessed directly.
        unset($this->key, $this->label, $this->key, $this->description);

        // Unset key and description properties as they should not be accessed directly.

        // Check if the provided flags are an array.
        if (Validator::isArray($flags)) {
            // If flags are an array, set the flags.
            $this->setFlags($flags);
        } else {
            // Attempt to construct the parent Enum with the provided flags.
            try {
                parent::__construct($flags);
            } catch (InvalidEnumMemberException) {
                // If the value is invalid, assign it directly to the value property.
                $this->value = $flags;
            }
        }
    }

    /**
     * Create an instance from the given value.
     *
     * @param  int|static|array<int|static>  $enumValue  The value to create the instance from.
     *
     * @return static The created instance.
     */
    public static function fromValue(mixed $enumValue): static
    {
        // Delegate to the parent fromValue method to create the instance.
        return parent::fromValue($enumValue);
    }

    /**
     * Attempt to instantiate a new Enum using the given key or value.
     *
     * @param  mixed  $enumKeyOrValue  The key or value to coerce.
     *
     * @return static|null The coerced instance or null if not found.
     */
    public static function coerce(mixed $enumKeyOrValue): ?static
    {
        // Check if the provided value is an integer.
        if (Validator::isInt($enumKeyOrValue)) {
            // If it is an integer, return the instance created from that value.
            return static::fromValue($enumKeyOrValue);
        }

        // Otherwise, delegate to the parent coercion method.
        return parent::coerce($enumKeyOrValue);
    }

    /**
     * Return a FlaggedEnum instance with defined flags.
     *
     * @param  array<int|static>  $flags  The flags to set on the instance.
     *
     * @return static The created instance with the specified flags.
     */
    public static function flags(array $flags): static
    {
        // Create and return an instance from the specified flags.
        return static::fromValue($flags);
    }

    /**
     * Set the flags for the enum to the given array of flags.
     *
     * This method combines multiple flags into a single value using bitwise OR.
     *
     * @param  array<int|static>  $flags  The array of flags to set.
     *
     * @return static The current instance for method chaining.
     */
    public function setFlags(array $flags): static
    {
        // Combine the provided flags into the value property using Arr::reduce.
        $this->value = Arr::reduce(
            $flags,
            static fn(int $carry, int|self $flag): int => $carry
                | ($flag instanceof self // Check if the flag is an instance of self
                    ? $flag->value // Use the value of the instance if it is
                    : $flag), // Otherwise, use the flag directly
            0, // Initial carry value is 0 (no flags set)
        );

        // Return the current instance for method chaining.
        return $this;
    }

    /**
     * Add the given flag to the enum.
     *
     * This method uses bitwise OR to add the specified flag to the current value.
     *
     * @param  int|static  $flag  The flag to add.
     *
     * @return static The current instance for method chaining.
     */
    public function addFlag(int|self $flag): static
    {
        // Use bitwise OR to add the flag value to the current value.
        $this->value |= ($flag instanceof self
            ? $flag->value // Use the value of the flag if it is an instance of self

            // Otherwise, use the flag directly
            : $flag);

        // Return the current instance for method chaining.
        return $this;
    }

    /**
     * Add the given flags to the enum.
     *
     * This method calls addFlag for each flag in the provided array.
     *
     * @param  array<int|static>  $flags  The flags to add.
     *
     * @return static The current instance for method chaining.
     */
    public function addFlags(array $flags): static
    {
        // Loop through each flag and add it to the enum.
        foreach ($flags as $flag) {
            // Add each flag using the addFlag method.
            $this->addFlag($flag);
        }

        // Return the current instance for method chaining.
        return $this;
    }

    /**
     * Add all flags to the enum.
     *
     * This method adds all possible flags defined in the enum.
     *
     * @return static The current instance for method chaining.
     */
    public function addAllFlags(): static
    {
        // Create a new instance of the static class and add all defined flags to it.
        return (new static)->addFlags(self::getValues());
    }

    /**
     * Remove the given flag from the enum.
     *
     * This method uses bitwise AND NOT to remove the specified flag from the current value.
     *
     * @param  int|static  $flag  The flag to remove.
     *
     * @return static The current instance for method chaining.
     */
    public function removeFlag(int|self $flag): static
    {
        // Use bitwise AND NOT to remove the flag value from the current value.
        $this->value &= ~($flag instanceof self
            ? $flag->value // Use the value of the flag if it is an instance of self

            // Otherwise, use the flag directly
            : $flag);

        // Return the current instance for method chaining.
        return $this;
    }

    /**
     * Remove the given flags from the enum.
     *
     * This method calls removeFlag for each flag in the provided array.
     *
     * @param  array<int|static>  $flags  The flags to remove.
     *
     * @return static The current instance for method chaining.
     */
    public function removeFlags(array $flags): static
    {
        // Loop through each flag and remove it from the enum.
        foreach ($flags as $flag) {
            // Remove each flag using the removeFlag method.
            $this->removeFlag($flag);
        }

        // Return the current instance for method chaining.
        return $this;
    }

    /**
     * Remove all flags from the enum.
     *
     * This resets the enum to the NONE state, effectively removing all flags.
     *
     * @return static The current instance for method chaining.
     */
    public function removeAllFlags(): static
    {
        // Return the NONE instance, which represents no flags set.
        return static::NONE();
    }

    /**
     * Check if the enum has the specified flag.
     *
     * This method checks if the current value contains the specified flag using bitwise AND.
     *
     * @param  int|static  $flag  The flag to check for.
     *
     * @return bool True if the flag is set; otherwise, false.
     */
    public function hasFlag(int|self $flag): bool
    {
        // Determine the value of the flag to check.
        $flagValue = ($flag instanceof self
            ? $flag->value // Use the value of the flag if it is an instance of self

            // Otherwise, use the flag directly
            : $flag);

        // If the flag value is 0, it cannot be set.
        if ($flagValue === 0) {
            // Flag is not set, return false.
            return false;
        }

        // Use bitwise AND to check if the flag is set in the current value.
        // Return true if the flag is set.
        return ($this->value & $flagValue) === $flagValue;
    }

    /**
     * Get the current value of the enum.
     *
     * @return int The current value of the enum.
     */
    public function value(): int
    {
        // Return the current value.
        return $this->value;
    }
}
