<?php

declare(strict_types=1);

namespace Maginium\Framework\Dto\Casters;

use Maginium\Foundation\Exceptions\LogicException;
use Maginium\Framework\Dto\Interfaces\CasterInterface;

/**
 * Caster class for casting values to backed enums.
 *
 * The `EnumCaster` is responsible for transforming a value into a corresponding backed enum instance.
 * It ensures that the provided value is either an instance of the specified enum type or a valid value
 * that can be cast into one. If the value is not valid, an exception will be thrown.
 *
 * This caster can be used for any backed enum class, and ensures that only valid enum values are accepted.
 *
 * @example
 * $caster = new EnumCaster([BackedEnum::class], 'MyEnum');
 * $enumInstance = $caster->cast('value');
 */
class EnumCaster implements CasterInterface
{
    /**
     * Constructor for initializing the EnumCaster.
     *
     * @param array $types The types of values that can be cast. In most cases, this will be a single value,
     *        but it's provided as an array for flexibility in case multiple types need to be handled.
     * @param string $enumType The fully qualified name of the enum class to which the value should be cast.
     */
    public function __construct(
        private array $types,
        private string $enumType,
    ) {
    }

    /**
     * Cast the provided value to the backed enum instance.
     *
     * This method will first check if the provided value is already an instance of the target enum class.
     * If not, it will attempt to cast the value to an enum instance using the `tryFrom` method, which attempts
     * to create an enum instance from the given value.
     *
     * If the value is invalid or not compatible with the enum, a `LogicException` will be thrown.
     *
     * @param mixed $value The value to be cast into the enum instance.
     *
     * @throws LogicException If the value cannot be cast to the backed enum.
     *
     * @return mixed The enum instance if the value is valid; otherwise, an exception is thrown.
     */
    public function cast(mixed $value): mixed
    {
        // If the value is already an instance of the enum type, return it as is
        if ($value instanceof $this->enumType) {
            return $value;
        }

        // Check if the enum type is a valid backed enum class
        if (! is_subclass_of($this->enumType, 'BackedEnum')) {
            throw LogicException::make(
                "Caster [EnumCaster] may only be used to cast backed enums. Received [{$this->enumType}].",
            );
        }

        // Attempt to cast the value using the enum's `tryFrom` method
        $castedValue = $this->enumType::tryFrom($value);

        // If the casting failed, throw an exception
        if ($castedValue === null) {
            throw LogicException::make(
                "Couldn't cast enum [{$this->enumType}] with value [{$value}]",
            );
        }

        return $castedValue;
    }
}
