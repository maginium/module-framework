<?php

declare(strict_types=1);

namespace Maginium\Framework\Dto\Attributes\Validation;

use Attribute;
use Maginium\Framework\Actions\Concerns\AsAction;
use Maginium\Framework\Dto\Interfaces\ValidatorInterface;
use Maginium\Framework\Dto\Validation\ValidationResult;
use Maginium\Framework\Support\Validator;

/**
 * The `In` attribute is a custom validation attribute that ensures the value of a property is one of the specified options.
 * This is useful for validating fields where the value must be restricted to a predefined set of allowed values, such as enumerations or status codes.
 *
 * Example usage:
 *
 * #[In(['active', 'inactive', 'pending'])]
 * public string $status;
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class In implements ValidatorInterface
{
    // Using the AsAction trait to add common action functionality
    use AsAction;

    /**
     * The `options` array defines the set of valid values.
     *
     * The `options` array defines the set of valid values that the property being validated can have. The validation ensures
     * that the property value is one of these predefined options.
     *
     * @var array  An array of allowed values for the validation.
     */
    private array $options;

    /**
     * Constructor to initialize the `In` validation attribute with the allowed options.
     *
     * The `options` array defines the set of valid values that the property being validated can have. The validation ensures
     * that the property value is one of these predefined options.
     *
     * @param array $options An array of allowed values for the validation.
     */
    public function __construct(array $options)
    {
        $this->options = $options;
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
        // Check if the value is in the allowed options array, using strict comparison.
        if (! Validator::inArray($value, $this->options, true)) {
            // If the value is not one of the allowed options, return an invalid result.
            return ValidationResult::invalid('This field must be one of the allowed values.');
        }

        // If the value is valid, return a valid result.
        return ValidationResult::valid();
    }
}
