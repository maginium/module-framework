<?php

declare(strict_types=1);

namespace Maginium\Framework\Support;

use Countable;
use Magento\Framework\Validator\EmailAddress as EmailValidator;
use Maginium\Country\Helpers\Countries as CountriesHelper;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Config\Enums\ConfigDrivers;
use Maginium\Framework\Support\Facades\Config;
use Maginium\Framework\Support\Facades\Container;
use Maginium\Framework\Support\Facades\Date;
use Maginium\Framework\Support\Facades\DisposableEmail;
use Maginium\Framework\Support\Facades\Json;

/**
 * Class Validator.
 *
 * General-purpose validator class.
 */
class Validator
{
    /**
     * Email constructor initializes the DisposableEmailFilter.
     * It adds disposable email domains to a blacklist and optionally whitelists specific domains.
     *
     * The constructor can be customized to include additional blacklisted or whitelisted domains
     * based on application needs. By default, common disposable email domains are added to the blacklist.
     * Additionally, the application's main domain is whitelisted to ensure that emails from the same domain
     * are considered valid.
     */
    public function __construct()
    {
        // Add common disposable email domains to the blacklist.
        // These domains belong to throwaway email services that should not be accepted.
        DisposableEmail::blacklistedDomains()->addMultiple([
            'test.com',         // Common throwaway domain used for testing.
            'example.com',      // Reserved domain often used for demonstrations.
            'maildrop.cc',      // Disposable email service for temporary addresses.
            'mailinator.com',   // Popular disposable email service.
            '10minute-mail.org', // A service that provides temporary emails for 10 minutes.
        ]);

        // Get the app domain from the configuration to ensure that emails from the app's own domain are valid.
        $appDomain = Config::driver(ConfigDrivers::ENV)->getString('APP_DOMAIN');

        // Optionally, add the application's domain to the whitelist.
        // This ensures that users can register with their own email addresses from the app's domain.
        DisposableEmail::whitelistedDomains()->add($appDomain);
    }

    /**
     * Check if a value is a string.
     *
     * @param mixed $value The value to check.
     *
     * @return bool True if the value is a string, false otherwise.
     */
    public static function isString($value): bool
    {
        return is_string($value);
    }

    /**
     * Check if a value is an integer.
     *
     * @param mixed $value The value to check.
     *
     * @return bool True if the value is an integer, false otherwise.
     */
    public static function isInt($value): bool
    {
        return is_int($value);
    }

    /**
     * Check if a value is a float.
     *
     * @param mixed $value The value to check.
     *
     * @return bool True if the value is a float, false otherwise.
     */
    public static function isFloat($value): bool
    {
        return is_float($value);
    }

    /**
     * Check if a value is numeric.
     *
     * @param mixed $value The value to check.
     *
     * @return bool True if the value is numeric, false otherwise.
     */
    public static function isNumeric($value): bool
    {
        return is_numeric($value);
    }

    /**
     * Check if a value is an array.
     *
     * @param mixed $value The value to check.
     *
     * @return bool True if the value is an array, false otherwise.
     */
    public static function isArray($value): bool
    {
        return is_array($value);
    }

    /**
     * Checks if a value exists in an array.
     *
     * @param mixed $needle The searched value.
     * @param string[] $haystack The array.
     * @param bool $strict [optional] If set to TRUE, the in_array() function will also check the types of the needle in the haystack.
     *
     * @return bool Returns true if needle is found in the array, false otherwise.
     */
    public static function inArray($needle, array $haystack, bool $strict = false): bool
    {
        return in_array($needle, $haystack, $strict);
    }

    /**
     * Check if a value is a boolean.
     *
     * @param mixed $value The value to check.
     *
     * @return bool True if the value is a boolean, false otherwise.
     */
    public static function isBool($value): bool
    {
        return is_bool($value);
    }

    /**
     * Check if a value is null.
     *
     * @param mixed $value The value to check.
     *
     * @return bool True if the value is null, false otherwise.
     */
    public static function isNull($value): bool
    {
        return $value === null;
    }

    /**
     * Validates if the provided value is a valid email address.
     *
     * This method uses PHP's built-in `filter_var` to check for basic email format,
     * then further validates it using an EmailValidator service, and checks if the
     * email is not disposable.
     *
     * @param string $email The email address to validate.
     *
     * @return bool Returns `true` if the email is valid, `false` otherwise.
     */
    public static function isEmail(string $email): bool
    {
        // Resolve the email validator from the container.
        $emailValidator = Container::resolve(EmailValidator::class);

        // Check for basic email format validation using PHP's filter_var function.
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            // Return false immediately if the format is invalid.
            return false;
        }

        // Check if the email is disposable.
        if (DisposableEmail::isDisposableEmailAddress($email)) {
            // Return false if the email is disposable.
            return false;
        }

        // Further validate the email using the email validator service.
        return $emailValidator->isValid($email);
    }

    /**
     * Check if a value is a valid URL.
     *
     * @param string $value The value to check.
     *
     * @return bool True if the value is a valid URL, false otherwise.
     */
    public static function isUrl($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Check if a value is a valid IP address.
     *
     * @param string $value The value to check.
     *
     * @return bool True if the value is a valid IP address, false otherwise.
     */
    public static function isIp($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Checks if the value is a valid date.
     *
     * @param mixed $value The value to check.
     *
     * @return bool Returns true if the value is a valid date, false otherwise.
     */
    public static function isDate(mixed $value): bool
    {
        if ($value instanceof Carbon) {
            return true;
        }

        // If the value is a string, attempt to create a Carbon instance
        if (is_string($value)) {
            try {
                // Try to parse the string into a valid Carbon date
                $date = Date::parse($value);

                return $date->isValid();
            } catch (Exception $e) {
                // Return false if the date string is invalid
                return false;
            }
        }

        return false;
    }

    /**
     * Check if a value is a valid JSON string.
     *
     * @param string $value The value to check.
     *
     * @return bool True if the value is a valid JSON string, false otherwise.
     */
    public static function isJson($value): bool
    {
        return Json::isValid($value);
    }

    /**
     * Validates whether a given value is a valid phone number.
     *
     * This method checks if the provided phone number matches the format for any country
     * dial code, ensuring the number's length is between 10 and 15 digits (excluding the
     * dial code). It also trims whitespace and handles the optional '+' symbol in the dial code.
     *
     * @param string $value The phone number to validate.
     *
     * @return bool True if the phone number is valid, false otherwise.
     */
    public static function isPhoneNumber(string $value): bool
    {
        // Remove the '+' symbol from the beginning of the phone number if present
        // to simplify country code matching.
        $value = Str::trim($value, '+');

        // Trim any leading whitespace from the phone number.
        $value = Str::ltrim($value);

        // Early return false if the phone number contains any non-numeric characters.
        if (! ctype_digit($value)) {
            return false;
        }

        // Get the list of country dial codes and their valid lengths from the CountriesHelper.
        $countriesDialCodes = CountriesHelper::getDialCodes();

        // Iterate through each country's dial code to check for validity.
        foreach ($countriesDialCodes as $dialCode) {
            // Check if the phone number starts with the current country's dial code.
            if (str_starts_with($value, (string)$dialCode)) {
                // Remove the country dial code from the phone number.
                $phoneNumberWithoutCode = Str::substr($value, Str::length($dialCode));

                // Get the length of the phone number after removing the dial code.
                $numberLength = Str::length($phoneNumberWithoutCode);

                // Validate the length of the phone number (between 10 and 15 digits).
                if ($numberLength >= 9 && $numberLength <= 15) {
                    // Return true if the phone number is valid.
                    return true;
                }
            }
        }

        // If no valid match is found or the length is invalid, return false.
        return false;
    }

    /**
     * Check if a value is a valid postal code.
     *
     * @param string $value The value to check.
     *
     * @return bool True if the value is a valid postal code, false otherwise.
     */
    public static function isPostalCode($value): bool
    {
        // Simple regex for postal code (adjust pattern as needed)
        return preg_match('/^[A-Za-z0-9\s\-]+$/', $value);
    }

    /**
     * Check if a value is a valid hexadecimal color code.
     *
     * @param string $value The value to check.
     *
     * @return bool True if the value is a valid hexadecimal color code, false otherwise.
     */
    public static function isHexColor($value): bool
    {
        return preg_match('/^#?([a-f0-9]{6}|[a-f0-9]{3})$/i', $value);
    }

    /**
     * Check if a value is a valid credit card number.
     *
     * @param string $value The value to check.
     *
     * @return bool True if the value is a valid credit card number, false otherwise.
     */
    public static function isCreditCard($value): bool
    {
        // Remove all non-numeric characters
        $value = preg_replace('/\D/', '', $value);
        $length = mb_strlen($value);
        $sum = 0;
        $shouldDouble = false;

        // Loop through the digits in reverse
        for ($i = $length - 1; $i >= 0; $i--) {
            $digit = (int)$value[$i];

            if ($shouldDouble) {
                $digit *= 2;

                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
            $shouldDouble = ! $shouldDouble;
        }

        // The number is valid if the sum is divisible by 10
        return $sum % 10 === 0;
    }

    /**
     * Check if a value is a valid UUID.
     *
     * @param string $value The value to check.
     *
     * @return bool True if the value is a valid UUID, false otherwise.
     */
    public static function isUuid($value): bool
    {
        return preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $value);
    }

    /**
     * Check if a value is within a specified range.
     *
     * @param mixed $value The value to check.
     * @param mixed $min The minimum value.
     * @param mixed $max The maximum value.
     *
     * @return bool True if the value is within the range, false otherwise.
     */
    public static function isInRange($value, $min, $max): bool
    {
        return $value >= $min && $value <= $max;
    }

    /**
     * Check if a value is a valid password (at least 8 characters, one uppercase, one lowercase, one number).
     *
     * @param string $value The value to check.
     *
     * @return bool True if the value is a valid password, false otherwise.
     */
    public static function isValidPassword($value): bool
    {
        return preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).{8,}$/', $value);
    }

    /**
     * Determine if a value is considered empty.
     *
     * This method checks whether a given value is empty. It considers
     * `null`, empty strings, empty arrays, empty collections, data objects with no properties
     * or countable elements as empty.
     *
     * If the value is `false` or `0`, it will explicitly return those values.
     *
     * @param mixed $value The value to evaluate.
     *
     * @return bool|int|null True if the value is empty, false otherwise, or count for countable elements.
     */
    public static function isEmpty(mixed $value): bool|int|null
    {
        // Check if the value is explicitly false
        if ($value === false) {
            return true;
        }

        // Check if the value is explicitly 0
        if ($value === 0) {
            return 0;
        }

        // Check for null or empty strings
        if ($value === null || $value === '') {
            return true;
        }

        // Check for empty arrays or countable objects
        if (static::isArray($value) || $value instanceof Countable) {
            $count = Php::count($value);

            return $count === 0 ? true : false;
        }

        // Check for empty Collection or DataObject (using instanceof to check for specific classes/interfaces)
        if ($value instanceof Collection || $value instanceof DataObject) {
            // return count if not empty
            return $value->isEmpty() ? true : $value->count();
        }

        // Check for empty objects (objects with no properties)
        return static::isObject($value) && empty((array)$value) ? true : null;
    }

    /**
     * Checks if the value is a boolean.
     *
     * @param mixed $value The value to check.
     *
     * @return bool Returns true if the value is a boolean, false otherwise.
     */
    public static function isBoolean(mixed $value): bool
    {
        return is_bool($value);
    }

    /**
     * Checks if the value is defined (not null).
     *
     * @param mixed $value The value to check.
     *
     * @return bool Returns true if the value is defined, false otherwise.
     */
    public static function isDefined(mixed $value): bool
    {
        return isset($value);
    }

    /**
     * Checks if two objects are dirty (i.e., if they have different properties).
     *
     * @param object $obj1 The first object to compare.
     * @param object $obj2 The second object to compare.
     *
     * @return bool Returns true if the objects are different, false otherwise.
     */
    public static function isDirty(object $obj1, object $obj2): bool
    {
        // Iterate over each property in obj2
        foreach ($obj2 as $key => $value) {
            // Check if the corresponding property in obj1 is an object
            if (is_object($obj1->{$key})) {
                // Recursively check for changes in nested objects
                if (static::isDirty($obj1->{$key}, $value)) {
                    // Return true if changes are found
                    return true;
                }

                // Skip to the next iteration if no changes were found
                continue;
            }

            // Compare the values directly if not an object
            if ($obj1->{$key} !== $value) {
                // Return true if values differ
                return true;
            }
        }

        // No changes found; return false
        return false;
    }

    /**
     * Checks if the value is an object.
     *
     * @param mixed $value The value to check.
     *
     * @return bool Returns true if the value is an object, false otherwise.
     */
    public static function isObject(mixed $value): bool
    {
        return is_object($value);
    }

    /**
     * Checks if the given value is an instance of DataObject.
     *
     * @param mixed $value The value to check.
     *
     * @return bool Returns true if the value is an instance of DataObject, false otherwise.
     */
    public static function isDataObject(mixed $value): bool
    {
        return $value instanceof DataObject;
    }

    /**
     * Checks if the value is a valid URL.
     *
     * @param mixed $value The value to check.
     *
     * @return bool Returns true if the value is a valid URL, false otherwise.
     */
    public static function isValidUrl(mixed $value): bool
    {
        return is_string($value) && filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Check if a given string is a valid XML.
     *
     * This method attempts to parse the input string using SimpleXML. If parsing
     * fails, the method returns false, indicating that the string is not valid XML.
     *
     * @param string $string The input string to be checked.
     *
     * @return bool Returns true if the string is valid XML, otherwise false.
     */
    public static function isXML(string $string): bool
    {
        // Suppress errors and try to load the XML string
        // The '@' operator is used to prevent warnings from being outputted
        $xml = @simplexml_load_string($string);

        // Check if the result of simplexml_load_string is false
        // If false, it means the string is not valid XML
        return ! ($xml === false);
    }

    /**
     * Check if the given word is plural.
     *
     * @param string $word The word to check.
     *
     * @return bool True if the word is plural, false otherwise.
     */
    public static function isPlural(string $word): bool
    {
        // Trim the word to avoid issues with spaces
        $word = Str::trim($word);

        // Handle common pluralization rules
        if (empty($word)) {
            // An empty string is not plural
            return false;
        }

        // Rules for plural detection
        $lastChar = mb_substr($word, -1);
        $lastTwoChars = mb_substr($word, -2);
        $lastThreeChars = mb_substr($word, -3);

        // Basic plural forms
        if ($lastChar === 's') {
            // Special cases for words ending in 's'
            if (in_array($lastTwoChars, ['es', 'ss'])) {
                // Ends with 'es' or 'ss' (e.g., boxes, buses, classes)
                return true;
            }
        }

        // Check for more complex plural forms
        if ($lastTwoChars === 'es' && ! in_array($lastThreeChars, ['oes', 'ies'])) {
            // Ends with 'es' but not 'oes' or 'ies'
            return true;
        }

        return (bool)($lastThreeChars === 'ies');
    }
}
