<?php

declare(strict_types=1);

namespace Maginium\Framework\Dto\Attributes\Validation;

use Attribute;
use Maginium\Framework\Actions\Concerns\AsAction;
use Maginium\Framework\Dto\Interfaces\ValidatorInterface;
use Maginium\Framework\Dto\Validation\ValidationResult;

/**
 * The `Regex` attribute is a custom validation attribute that ensures a property value matches a given regular expression.
 *
 * This attribute validates that the value of a property matches the specified regular expression pattern.
 * It is particularly useful for validating input values that should conform to a specific format, such as email addresses, phone numbers, or custom string formats.
 *
 * Example usage:
 *
 * #[Regex('/^[a-zA-Z0-9]+$/')]
 * public string $username;
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Regex implements ValidatorInterface
{
    // Using the AsAction trait to add common action functionality
    use AsAction;

    /**
     * The regular expression pattern to match against the value.
     *
     * @var string The regular expression pattern.
     */
    private string $pattern;

    /**
     * Constructor for the Regex attribute.
     *
     * This constructor accepts a regular expression pattern that will be used to validate the value of the property.
     *
     * @param string $pattern The regular expression pattern to validate the property value against.
     */
    public function __construct(string $pattern)
    {
        // Set the regular expression pattern to be used in validation.
        $this->pattern = $pattern;
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
        // Check if the value matches the regex pattern.
        if (! preg_match($this->pattern, (string)$value)) {
            // Return an invalid validation result with an appropriate error message if the value doesn't match the pattern.
            return ValidationResult::invalid('This field must match the required pattern.');
        }

        // If the value matches the pattern, return a valid validation result.
        return ValidationResult::valid();
    }
}
