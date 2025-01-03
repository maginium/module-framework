<?php

declare(strict_types=1);

namespace Maginium\Framework\Dto\Attributes\Validation;

use Attribute;
use Maginium\Framework\Actions\Concerns\AsAction;
use Maginium\Framework\Dto\Interfaces\ValidatorInterface;
use Maginium\Framework\Dto\Validation\ValidationResult;
use Maginium\Framework\Support\Validator;

/**
 * The `Numeric` attribute is a custom validation attribute that ensures the value of a property is numeric.
 * This validation checks if the value is a valid number (either an integer or a floating point number).
 *
 * Example usage:
 *
 * #[Numeric]
 * public float $price;
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Numeric implements ValidatorInterface
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
        // Check if the value is numeric. If not, return an invalid validation result with an error message.
        if (! Validator::isNumeric($value)) {
            return ValidationResult::invalid('This field must be a numeric value.');
        }

        // If the value is numeric, return a valid validation result.
        return ValidationResult::valid();
    }
}
