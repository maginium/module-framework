<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch\Helpers;

/**
 * Trait Utilities.
 *
 * This trait provides utility methods to assist with various string manipulation tasks.
 * It includes the `_escape` method, which escapes special characters in a given string to make it safe
 * for usage in contexts like queries or regex patterns.
 */
trait Utilities
{
    /**
     * Escape special characters in a string.
     *
     * This method takes a string and escapes specific special characters that could interfere
     * with queries or patterns. It handles characters like `"` (double quote), `\\` (backslash),
     * `~` (tilde), `^` (caret), and `/` (forward slash). Additionally, if the string starts with a hyphen,
     * it will escape the leading hyphen to ensure it is treated correctly in the context.
     *
     * @param  string  $value The string to escape.
     *
     * @return string The escaped string.
     */
    public static function _escape($value): string
    {
        // Define an array of special characters to escape
        $specialChars = ['"', '\\', '~', '^', '/'];

        // Loop through each special character and replace it with an escaped version
        foreach ($specialChars as $char) {
            // Escape the character by prefixing it with a backslash
            $value = str_replace($char, '\\' . $char, $value);
        }

        // If the string starts with a hyphen, escape the hyphen as well
        if (str_starts_with($value, '-')) {
            $value = '\\' . $value;
        }

        // Return the escaped string
        return $value;
    }
}
