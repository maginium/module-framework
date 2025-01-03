<?php

declare(strict_types=1);

namespace Maginium\Framework\Dto\Attributes\Validation;

use Attribute;
use Maginium\Country\Helpers\Countries as CountriesHelper;
use Maginium\Framework\Actions\Concerns\AsObject;
use Maginium\Framework\Dto\Interfaces\ValidatorInterface;
use Maginium\Framework\Dto\Validation\ValidationResult;
use Maginium\Framework\Support\Str;
use Maginium\Framework\Support\Validator;

/**
 * The `PhoneNumber` attribute is a custom validation attribute that ensures the value of a property is a valid phone number
 * based on country dial codes. The attribute checks that the phone number begins with a valid country dial code,
 * followed by a number length between 10 and 15 digits (excluding the country code).
 *
 * This validation is useful for ensuring phone numbers are correctly formatted with the appropriate country code
 * and meet length requirements.
 *
 * Example usage:
 *
 * #[PhoneNumber]
 * public string $phoneNumber;
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class PhoneNumber implements ValidatorInterface
{
    use AsObject;

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
        // Ensure the value is a string. If not, return an invalid validation result with an error message.
        if (! Validator::isString($value)) {
            return ValidationResult::invalid('This field must be a valid phone number.');
        }

        // Remove the '+' symbol from the beginning of the value if present.
        // This ensures we can correctly match country dial codes without the '+'.
        $value = Str::trim($value, '+');

        // Trim any leading whitespace that might exist in the phone number.
        $value = Str::ltrim($value);

        // Retrieve the list of all country dial codes and their valid lengths from the CountriesHelper.
        $countriesDialCodes = CountriesHelper::getDialCodes();

        // Iterate through each country's dial code to validate the phone number.
        foreach ($countriesDialCodes as $dialCode) {
            // Check if the phone number starts with the country dial code.
            if (str_starts_with($value, (string)$dialCode)) {
                // Remove the country dial code from the phone number to check the remaining digits.
                $phoneNumberWithoutCode = Str::substr($value, Str::length($dialCode));

                // Get the length of the remaining phone number after removing the country code.
                $numberLength = Str::length($phoneNumberWithoutCode);

                // Validate that the phone number length is between 10 and 15 digits.
                if ($numberLength < 9 || $numberLength > 15) {
                    // If the phone number has an invalid length, return an invalid validation result with an error message.
                    return ValidationResult::invalid(
                        'The phone number must have between 10 to 15 digits after the country dial code.',
                    );
                }

                // If the phone number is valid, return a valid validation result.
                return ValidationResult::valid();
            }
        }

        // If the phone number doesn't start with any valid country dial code, return an invalid validation result.
        return ValidationResult::invalid('This field must be a valid phone number starting with a valid country dial code.');
    }
}
