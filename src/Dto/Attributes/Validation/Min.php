<?php

declare(strict_types=1);

namespace Maginium\Framework\Dto\Attributes\Validation;

use Attribute;
use Maginium\Framework\Actions\Concerns\AsAction;
use Maginium\Framework\Dto\Interfaces\ValidatorInterface;
use Maginium\Framework\Dto\Validation\ValidationResult;

/**
 * The `Min` attribute is a custom validation attribute that ensures the value of a property is greater than or equal to a specified minimum value.
 * This is useful for validating numerical inputs or any other field where a minimum threshold is required.
 *
 * Example usage:
 *
 * #[Min(10)]
 * public int $age;
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Min implements ValidatorInterface
{
    // Using the AsAction trait to add common action functionality
    use AsAction;

    /**
     * The minimum allowed value for the validation.
     *
     * @var int
     */
    private int $min;

    /**
     * Constructor to initialize the `Min` attribute with the specified minimum value.
     *
     * @param int $min The minimum value for the validation.
     */
    public function __construct(int $min)
    {
        $this->min = $min;
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
        // Ensure the value is greater than or equal to the minimum allowed value.
        // If the value is less than the minimum, return an invalid result with a specific error message.
        if ($value < $this->min) {
            return ValidationResult::invalid("This field must be greater than or equal to {$this->min}.");
        }

        // If the value is valid (greater than or equal to the minimum), return a valid result.
        return ValidationResult::valid();
    }
}
