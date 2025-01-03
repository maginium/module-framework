<?php

declare(strict_types=1);

namespace Maginium\Framework\Dto\Attributes\Validation;

use Attribute;
use Maginium\Framework\Actions\Concerns\AsAction;
use Maginium\Framework\Dto\Interfaces\ValidatorInterface;
use Maginium\Framework\Dto\Validation\ValidationResult;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Reflection;
use Maginium\Framework\Support\Validator;

/**
 * The `Enum` attribute is a custom validation attribute that ensures a value is part of a specified enum.
 * This is useful for validating fields where the value must be one of a set of predefined options, which can either
 * come from an enum class or a simple array of allowed values.
 *
 * Example usage:
 *
 * #[Enum(MyEnum::class)]
 * public string $status;
 *
 * Or, alternatively:
 *
 * #[Enum(['active', 'inactive', 'pending'])]
 * public string $status;
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Enum implements ValidatorInterface
{
    // Using the AsAction trait to add common action functionality
    use AsAction;

    /**
     * The enum values that the field must be part of.
     *
     * This can either be:
     * - An array of valid values (e.g., `['active', 'inactive', 'pending']`)
     * - A string representing an enum class implementing EnumInterface (e.g., `MyEnum::class`)
     *
     * @var array<string>|string|BaseEnum The list of valid values in the enum or the enum class implementing EnumInterface.
     */
    private array|string $enum;

    /**
     * Constructor to initialize the Enum validation attribute.
     *
     * This constructor accepts either an array of values or the name of an enum class. It sets the enum property
     * to the provided argument to be used later in the validation process.
     *
     * @param array<string>|string $enum The enum values to validate against or an enum class implementing EnumInterface.
     */
    public function __construct(array|string $enum)
    {
        $this->enum = $enum;
    }

    /**
     * Validate the given value.
     *
     * This method should be implemented to perform the necessary validation checks
     * on the provided value. It should return a `ValidationResult` object that
     * indicates whether the value is valid or invalid.
     *
     * @param mixed $value The value to validate. The type can vary depending on the
     *                     implementation of the validator.
     *
     * @return ValidationResult The result of the validation, indicating whether
     *                          the value is valid or invalid.
     */
    public function validate(mixed $value): ValidationResult
    {
        // Check if $this->enum is a string, indicating an enum class that implements EnumInterface.
        if (Validator::isString($this->enum)) {
            // Check if the class is a native enum (unit or backed enum)
            if (Reflection::isEnum($this->enum)) {
                // Handle native enum (backed or unit enums)
                if (! $this->enum::tryFrom($value)) {
                    return ValidationResult::invalid(sprintf(
                        "The provided value '%s' is invalid. Valid values are: %s.",
                        (string)$value,
                        implode(', ', Arr::each(fn($case) => $case->name, $this->enum::cases())),
                    ));
                }
            } else {
                // If it's not a native enum, validate the custom enum
                if (! $this->enum::hasKey((string)$value) && ! $this->enum::hasValue($value)) {
                    return ValidationResult::invalid(sprintf(
                        "The provided value '%s' is invalid. Valid values are: %s.",
                        (string)$value,
                        implode(', ', $this->enum::getKeys()),
                    ));
                }
            }
        }

        // If the value is valid, return a valid result.
        return ValidationResult::valid();
    }
}
