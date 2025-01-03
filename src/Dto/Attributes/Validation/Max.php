<?php

declare(strict_types=1);

namespace Maginium\Framework\Dto\Attributes\Validation;

use Attribute;
use Maginium\Framework\Actions\Concerns\AsAction;
use Maginium\Framework\Dto\Interfaces\ValidatorInterface;
use Maginium\Framework\Dto\Validation\ValidationResult;

/**
 * The `Max` attribute is a custom validation attribute that ensures the value of a property is less than or equal to a specified maximum value.
 * This is useful for validating numerical inputs or any other field where a maximum threshold is required.
 *
 * Example usage:
 *
 * #[Max(100)]
 * public int $score;
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Max implements ValidatorInterface
{
    // Using the AsAction trait to add common action functionality
    use AsAction;

    /**
     * The maximum allowed value for the validation.
     *
     * @var int
     */
    private int $max;

    /**
     * Constructor to initialize the `Max` attribute with the specified maximum value.
     *
     * @param int $max The maximum value for the validation.
     */
    public function __construct(int $max)
    {
        $this->max = $max;
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
        // Ensure the value is less than or equal to the maximum allowed value.
        // If the value exceeds the maximum, return an invalid result with a specific error message.
        if ($value > $this->max) {
            return ValidationResult::invalid("This field must be less than or equal to {$this->max}.");
        }

        // If the value is valid (less than or equal to the maximum), return a valid result.
        return ValidationResult::valid();
    }
}
