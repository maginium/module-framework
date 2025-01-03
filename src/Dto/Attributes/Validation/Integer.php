<?php

declare(strict_types=1);

namespace Maginium\Framework\Dto\Attributes\Validation;

use Attribute;
use Maginium\Framework\Actions\Concerns\AsAction;
use Maginium\Framework\Dto\Interfaces\ValidatorInterface;
use Maginium\Framework\Dto\Validation\ValidationResult;
use Maginium\Framework\Support\Validator;

/**
 * The `Integer` attribute is a custom validation attribute that ensures the value of a property is an integer.
 * This is particularly useful for validating fields that are expected to hold integer values, such as age, count, or other numeric quantities.
 *
 * Example usage:
 *
 * #[Integer]
 * public int $age;
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Integer implements ValidatorInterface
{
    // Using the AsAction trait to add common action functionality
    use AsAction;

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
        // Ensure the value is an integer by using the Validator helper method.
        if (! Validator::isInt($value)) {
            // If the value is not an integer, return an invalid result with an appropriate error message.
            return ValidationResult::invalid('This field must be an integer.');
        }

        // If the value is a valid integer, return a valid result.
        return ValidationResult::valid();
    }
}
