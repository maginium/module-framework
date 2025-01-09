<?php

declare(strict_types=1);

namespace Maginium\Framework\Support;

use Illuminate\Support\Str as BaseStr;
use Magento\Framework\Phrase;
use Override;
use voku\helper\ASCII;

/**
 * Class Str.
 *
 * This class provides string manipulation utilities, extending the functionality
 * of built-in Str class. It includes methods for creating slugs,
 * converting characters to ASCII, generating ordinal numbers, and more.
 */
class Str extends BaseStr
{
    /**
     * Formats a string or phrase with the provided arguments.
     *
     * Supports multiple placeholder formats including:
     * - Numeric placeholders: `%1`, `%2`, etc.
     * - String placeholders: `%s`, `%d`, etc.
     * - Named placeholders: `{name}`, `{for}`, etc.
     *
     * @param string|Phrase $phrase The phrase or Phrase instance with placeholders.
     * @param mixed ...$args The arguments to replace the placeholders.
     *
     * @return string The formatted string.
     */
    public static function format(string|Phrase $phrase, ...$args): string
    {
        // If the phrase is an instance of Phrase, call render() to get the string
        if ($phrase instanceof Phrase) {
            $phrase = $phrase->render();
        }

        // Replace named placeholders: {name}, {for}, etc.
        $phrase = preg_replace_callback('/\{(\w+)\}/', function($matches) use ($args) {
            $key = $matches[1];

            return Arr::exists($args, $key) ? $args[$key] : $matches[0];
        }, $phrase);

        // Replace numeric placeholders: %1, %2, etc. using sprintf
        // First, we prepare the arguments for the numeric placeholders
        $numericArgs = Arr::slice($args, 0, count($args)); // Ensure we're only passing the correct number of args
        $phrase = preg_replace_callback('/%(\d+)/', function($matches) use ($numericArgs) {
            $index = (int)$matches[1] - 1; // Convert 1-based index to 0-based

            return $numericArgs[$index] ?? $matches[0];
        }, $phrase);

        // Sequential placeholders: %s, %d, etc. using sprintf
        $phrase = sprintf($phrase, ...$args);

        return $phrase;
    }

    /**
     * Capitalizes the first letter of each word in the given string, using custom separators.
     *
     * @param string $string The input string that needs to be formatted.
     * @param string $separators A string of characters that will be treated as word separators.
     *                            Defaults to whitespace characters: " \t\r\n\f\v".
     *
     * @return string The formatted string with the first letter of each word capitalized.
     */
    public static function ucwords($string, string $separators = " \t\r\n\f\v"): string
    {
        return ucwords($string, $separators);
    }

    /**
     * Make a string's first character uppercase.
     *
     * @param string $string The input string that needs to be formatted.
     *
     * @return string The formatted string with the first letter.
     */
    public static function capital($string)
    {
        return static::ucfirst($string);
    }

    /**
     * Convert a string to StudlyCase format.
     *
     * This method capitalizes the first letter of each word in the given string.
     * Optionally, it can preserve hyphens and underscores between words, if specified.
     *
     * @param string $value The input string to be converted to StudlyCase.
     * @param bool $preserveSeparators Optional flag to preserve hyphens and underscores. Default is false.
     *
     * @return string The StudlyCase formatted string.
     */
    #[Override]
    public static function studly($value, bool $preserveSeparators = false)
    {
        // Generate a cache key based on the input value and the preserveSeparators flag.
        $key = $value . ($preserveSeparators ? '_preserve' : '');

        // If the result for this key already exists in the cache, return it immediately.
        if (isset(static::$studlyCache[$key])) {
            return static::$studlyCache[$key];
        }

        // Check if we need to preserve hyphens and underscores as separators.
        if ($preserveSeparators) {
            // Use preg_split to split the string by hyphens and underscores, keeping the separators.
            // PREG_SPLIT_DELIM_CAPTURE ensures that the separators remain in the result array.
            $words = preg_split('/([-_])/', $value, -1, PREG_SPLIT_DELIM_CAPTURE);

            // Capitalize each word in the array, ignoring hyphens and underscores.
            $studlyWords = Arr::each(function($word) {
                // If the word is a separator (hyphen or underscore), keep it as-is.
                // Otherwise, capitalize the first letter of the word.
                return in_array($word, ['-', '_']) ? $word : static::ucfirst($word);
            }, $words);

            // Join the array back into a single string, keeping separators intact.
            // Store the result in the cache and return it.
            return static::$studlyCache[$key] = implode('', $studlyWords);
        }

        // Default behavior: remove hyphens and underscores, then convert to StudlyCase.
        // Replace hyphens and underscores with spaces to separate words.
        $words = explode(' ', static::replace(['-', '_'], ' ', $value));

        // Capitalize the first letter of each word.
        $studlyWords = Arr::each(fn($word) => static::ucfirst($word), $words);

        // Join the array back into a single string without separators.
        // Store the result in the cache and return it.
        return static::$studlyCache[$key] = implode('', $studlyWords);
    }

    /**
     * Trim whitespace from the beginning and end of a string.
     *
     * This method provides a simple wrapper around PHP's built-in trim function,
     * allowing for easy whitespace removal from strings within this utility class.
     *
     * @param string $string The string to be trimmed.
     * @param string $characters The characters to remove from the beginning and end of the string.
     *
     * @return string The trimmed string with whitespace removed from both ends.
     */
    public static function trim(string $string, string $characters = " \n\r\t\v\0"): string
    {
        return trim($string, $characters);
    }

    /**
     * Trim whitespace from the beginning of a string.
     *
     * This method provides a simple wrapper around PHP's built-in ltrim function,
     * allowing for easy removal of whitespace from the start of the string.
     *
     * @param string $string The string to be left-trimmed.
     * @param string $characters The characters to remove from the beginning of the string.
     *
     * @return string The left-trimmed string with whitespace removed from the start.
     */
    public static function ltrim(string $string, string $characters = " \n\r\t\v\0"): string
    {
        return ltrim($string, $characters);
    }

    /**
     * Trim whitespace from the end of a string.
     *
     * This method provides a simple wrapper around PHP's built-in rtrim function,
     * allowing for easy removal of whitespace from the end of the string.
     *
     * @param string $string The string to be right-trimmed.
     * @param string $characters The characters to remove from the end of the string.
     *
     * @return string The right-trimmed string with whitespace removed from the end.
     */
    public static function rtrim(string $string, string $characters = " \n\r\t\v\0"): string
    {
        return rtrim($string, $characters);
    }

    /**
     * Generate a URL-friendly slug from a given title.
     *
     * This method replaces slashes with spaces, and then utilizes the parent
     * slug method to create a standardized URL slug with the specified separator.
     *
     * @param  string  $title       The title to convert into a slug.
     * @param  string  $separator   The character to use as a separator (default is '-').
     * @param  string|null  $language The language to use for slug generation (default is 'en').
     * @param  array<string, string>  $dictionary An array of replacements for special characters (default is ['@' => 'at']).
     *
     * @return string The generated slug.
     */
    public static function slug($title, $separator = '-', $language = 'en', $dictionary = ['@' => 'at'])
    {
        // Replace backslashes and spaces with a single space.
        $title = str_replace(['\\', SP], ' ', (string)$title);

        // Call the parent slug method to generate the slug.
        return parent::slug($title, $separator, $language, $dictionary);
    }

    /**
     * Convert a string to its ASCII representation.
     *
     * This method transliterates characters in the string to their ASCII
     * equivalents when the specified language is not found.
     *
     * @param  string  $value     The string to convert to ASCII.
     * @param  string  $language  The language for transliteration (default is 'en').
     *
     * @return string The ASCII representation of the input string.
     */
    public static function ascii($value, $language = 'en')
    {
        // Use the ASCII helper to convert the string to ASCII format.
        return ASCII::to_ascii((string)$value, $language, true, false, true);
    }

    /**
     * Convert a number to its ordinal English form.
     *
     * This method converts numbers like 1, 2, 3, 4 into their ordinal forms:
     * 1 becomes "1st", 2 becomes "2nd", etc.
     *
     * @param int $number The number to convert to its ordinal value.
     *
     * @return string The ordinal representation of the given number.
     */
    public static function ordinal($number)
    {
        // Handle special cases for numbers ending in 11, 12, or 13.
        if (in_array($number % 100, range(11, 13))) {
            return $number . 'th';
        }

        // Determine the appropriate suffix based on the last digit.
        switch ($number % 10) {
            case 1:
                return $number . 'st';

            case 2:
                return $number . 'nd';

            case 3:
                return $number . 'rd';

            default:
                return $number . 'th';
        }
    }

    /**
     * Normalize line endings to a standard format.
     *
     * This method converts all types of line breaks in a string to the
     * standard CRLF (\r\n) format.
     *
     * @param string $string The string with potential varying line endings.
     *
     * @return string The normalized string with standard line breaks.
     */
    public static function normalizeEol($string)
    {
        // Replace all line break sequences with CRLF.
        return preg_replace('~\R~u', "\r\n", $string);
    }

    /**
     * Normalize a class name by removing leading backslashes.
     *
     * This method ensures that a class name does not start with a backslash,
     * which is common in PHP class namespaces.
     *
     * @param string|object $name The class name or an object instance.
     *
     * @return string The normalized class name.
     */
    public static function normalizeClassName($name)
    {
        // If the input is an object, get its class name.
        if (is_object($name)) {
            $name = get_class($name);
        }

        // Trim leading backslashes from the class name.
        return ltrim($name, '\\');
    }

    /**
     * Generate a class ID from a class name or object.
     *
     * This method creates a unique identifier for a class by converting its
     * name to lowercase and replacing backslashes with underscores.
     *
     * @param string|object $name The class name or an object instance.
     *
     * @return string The generated class ID.
     */
    public static function getClassId($name)
    {
        // If the input is an object, get its class name.
        if (is_object($name)) {
            $name = get_class($name);
        }

        // Normalize the class name and convert it to a class ID.
        $name = ltrim($name, '\\');
        $name = str_replace('\\', '_', $name);

        // Return the class ID in lowercase.
        return mb_strtolower($name);
    }

    /**
     * Get the namespace of a given class name.
     *
     * This method extracts the namespace portion of a class name, allowing
     * the caller to separate the class name from its namespace.
     *
     * @param string|object $name The class name or an object instance.
     *
     * @return string The namespace of the class.
     */
    public static function getClassNamespace($name)
    {
        // Normalize the class name to prepare for namespace extraction.
        $name = static::normalizeClassName($name);

        // Return the namespace by finding the last backslash position.
        return self::substr($name, 0, mb_strrpos($name, '\\'));
    }

    /**
     * Count preceding symbols in a string.
     *
     * This method checks how many consecutive instances of a specified symbol
     * appear at the beginning of the given string.
     *
     * @param string $string The string to analyze.
     * @param string $symbol The symbol to count occurrences of.
     *
     * @return int The count of preceding symbols.
     */
    public static function getPrecedingSymbols(string $string, string $symbol): int
    {
        // Calculate the length difference to find the number of preceding symbols.
        return self::length($string) - mb_strlen(ltrim($string, $symbol));
    }

    /**
     * Limit the length of a string by truncating the middle.
     *
     * This method reduces the length of a string to a specified limit by
     * removing characters from the middle and inserting a marker.
     *
     * @param string $value The original string to limit.
     * @param int $limit The maximum length of the output string (default is 100).
     * @param string $marker The string to insert in the middle (default is '...').
     *
     * @return string The modified string with a limited length.
     */
    public static function limitMiddle($value, $limit = 100, $marker = '...')
    {
        // If the string is already within the limit, return it as-is.
        if (mb_strwidth($value, 'UTF-8') <= $limit) {
            return $value;
        }

        // Adjust the limit to account for the marker length.
        if ($limit > 3) {
            // Reserve space for the marker.
            $limit -= 3;
        }

        // Calculate how much to keep from the start and end of the string.
        $limitStart = (int)floor($limit / 2); // Cast to int
        $limitEnd = (int)($limit - $limitStart); // Cast to int

        // Trim the start and end of the string according to the calculated limits.
        $valueStart = self::rtrim(mb_strimwidth($value, 0, $limitStart, '', 'UTF-8'));
        $valueEnd = self::ltrim(mb_strimwidth($value, $limitEnd * -1, $limitEnd, '', 'UTF-8'));

        // Return the concatenated result with the marker.
        return $valueStart . $marker . $valueEnd;
    }

    /**
     * Checks if the given string is capitalized (first letter uppercase, rest lowercase).
     *
     * @param string $value The string to check.
     *
     * @return bool True if the string is capitalized, false otherwise.
     */
    public static function isCapitalized(string $value): bool
    {
        // Capitalized means the first letter is uppercase, and the rest is lowercase
        return ucfirst(mb_strtolower($value)) === $value;
    }

    /**
     * Checks if the given string is in title case (each word's first letter is uppercase).
     *
     * @param string $value The string to check.
     *
     * @return bool True if the string is in title case, false otherwise.
     */
    public static function isTitleCase(string $value): bool
    {
        // Check if each word starts with an uppercase letter and is followed by lowercase letters
        return preg_match('/^[A-Z][a-z]*(\s[A-Z][a-z]*)*$/', $value) === 1;
    }

    /**
     * Checks if the given string is in lowercase (all letters are lowercase).
     *
     * @param string $value The string to check.
     *
     * @return bool True if the string is lowercase, false otherwise.
     */
    public static function isLowercase(string $value): bool
    {
        // Compare the original string to the string converted to lowercase
        return mb_strtolower($value, 'UTF-8') === $value;
    }

    /**
     * Checks if the given string is in uppercase (all letters are uppercase).
     *
     * @param string $value The string to check.
     *
     * @return bool True if the string is uppercase, false otherwise.
     */
    public static function isUppercase(string $value): bool
    {
        // Compare the original string to the string converted to uppercase
        return mb_strtoupper($value, 'UTF-8') === $value;
    }

    /**
     * Checks if the given string is in camel case (no spaces, starting with a lowercase letter).
     *
     * @param string $value The string to check.
     *
     * @return bool True if the string is camel case, false otherwise.
     */
    public static function isCamelCase(string $value): bool
    {
        // Camel case starts with a lowercase letter and has no spaces, with each subsequent word's first letter uppercase
        return preg_match('/^[a-z][a-zA-Z0-9]*$/', $value) === 1;
    }

    /**
     * Checks if the given string is in snake case (lowercase letters, separated by underscores).
     *
     * @param string $value The string to check.
     *
     * @return bool True if the string is snake case, false otherwise.
     */
    public static function isSnakeCase(string $value): bool
    {
        // Snake case consists of lowercase letters separated by underscores (no spaces, and no leading or trailing underscores)
        return preg_match('/^[a-z0-9_]+$/', $value) === 1;
    }

    /**
     * Checks if the given string is in kebab case (lowercase letters, separated by hyphens).
     *
     * @param string $value The string to check.
     *
     * @return bool True if the string is kebab case, false otherwise.
     */
    public static function isKebabCase(string $value): bool
    {
        // Kebab case consists of lowercase letters separated by hyphens (no spaces, and no leading or trailing hyphens)
        return preg_match('/^[a-z0-9-]+$/', $value) === 1;
    }

    /**
     * Determine if a string is plural.
     *
     * This method uses simple heuristics to determine if the given string is plural.
     * Note: This implementation assumes English pluralization rules and may not handle all edge cases.
     *
     * @param string $string The string to check.
     *
     * @return bool True if the string is plural, false if singular.
     */
    public static function isPlural(string $string): bool
    {
        // Remove whitespace and lowercase the string for consistent comparison
        $string = trim(mb_strtolower($string));

        // Check common plural endings (basic English rules)
        $pluralEndings = ['s', 'es', 'ies'];

        foreach ($pluralEndings as $ending) {
            if (str_ends_with($string, $ending)) {
                return true;
            }
        }

        // Return false if no plural ending is detected
        return false;
    }
}
