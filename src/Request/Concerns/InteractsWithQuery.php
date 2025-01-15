<?php

declare(strict_types=1);

namespace Maginium\Framework\Request\Concerns;

use Laminas\Stdlib\ParametersInterface;
use Maginium\Framework\Request\Request;

/**
 * Trait providing methods for interacting with query strings.
 *
 * This trait includes functionality for:
 * - Normalizing query strings to ensure consistent key-value sorting and encoding.
 * - Parsing query strings into associative arrays, with special handling for brackets and null byte truncation.
 * - Retrieving specific query parameters from the request.
 * - Generating a normalized query string from the request.
 */
trait InteractsWithQuery
{
    /**
     * Normalizes a query string.
     *
     * This method takes a query string and ensures that:
     * - Keys and values are alphabetically sorted.
     * - Encoding/escaping is consistent as per RFC3986.
     * - Unnecessary delimiters and inconsistencies are removed.
     *
     * @param ?string $qs The raw query string to normalize.
     *
     * @return string The normalized query string.
     */
    public static function normalizeQueryString(?string $qs): string
    {
        // If the query string is null or empty, return an empty string.
        if ('' === ($qs ?? '')) {
            return '';
        }

        // Parse the query string into an associative array.
        $qs = static::parseQuery($qs);

        // Sort the query parameters alphabetically by key.
        ksort($qs);

        // Rebuild the query string with consistent encoding.
        return http_build_query($qs, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Parses a query string into an array, preserving dots in variable names.
     *
     * Unlike PHP's native `parse_str()`, this method:
     * - Does not interpret brackets (`[]`) unless explicitly told to do so.
     * - Handles null byte truncation for keys and values.
     *
     * @param string $query The query string to parse.
     * @param bool $ignoreBrackets Whether to ignore brackets in keys.
     * @param string $separator The separator used in the query string (default is '&').
     *
     * @return array The parsed query string as an associative array.
     */
    private static function parseQuery(string $query, bool $ignoreBrackets = false, string $separator = '&'): array
    {
        $q = [];

        // Split the query string into key-value pairs using the separator.
        foreach (explode($separator, $query) as $v) {
            // Remove any data after a null byte to prevent security issues.
            if (false !== $i = mb_strpos($v, "\0")) {
                $v = mb_substr($v, 0, $i);
            }

            // Check if the key-value pair contains an '=' sign.
            if (false === $i = mb_strpos($v, '=')) {
                $k = urldecode($v); // Decode the key.
                $v = ''; // Default value for keys without explicit values.
            } else {
                $k = urldecode(mb_substr($v, 0, $i)); // Decode the key.
                $v = mb_substr($v, $i); // Get the value part.
            }

            // Handle null byte truncation in keys.
            if (false !== $i = mb_strpos($k, "\0")) {
                $k = mb_substr($k, 0, $i);
            }

            // Trim any leading spaces from the key.
            $k = ltrim($k, ' ');

            // Handle the case where brackets should be ignored.
            if ($ignoreBrackets) {
                $q[$k][] = urldecode(mb_substr($v, 1)); // Decode the value.

                continue;
            }

            // Handle keys with or without brackets.
            if (false === $i = mb_strpos($k, '[')) {
                $q[] = bin2hex($k) . $v; // Convert key to hex to preserve structure.
            } else {
                $q[] = bin2hex(mb_substr($k, 0, $i)) . rawurlencode(mb_substr($k, $i)) . $v;
            }
        }

        // If brackets are ignored, return the parsed query array as is.
        if ($ignoreBrackets) {
            return $q;
        }

        // Rebuild the query string from the processed array.
        parse_str(implode('&', $q), $q);

        $query = [];

        // Convert hex-encoded keys back to their original form.
        foreach ($q as $k => $v) {
            if (false !== $i = mb_strpos((string)$k, '_')) {
                $query[substr_replace($k, hex2bin(mb_substr($k, 0, $i)) . '[', 0, 1 + $i)] = $v;
            } else {
                $query[hex2bin((string)$k)] = $v;
            }
        }

        return $query;
    }

    /**
     * Retrieve the query string parameter from the request.
     *
     * This method allows you to fetch specific values from the query string
     * by providing the key. If the key does not exist, a default value is returned.
     *
     * @param ?string $key The key of the query string parameter to retrieve.
     * @param ?mixed $default The default value to return if the key is not found.
     *
     * @return mixed|ParametersInterface The value of the query parameter or default value if not found.
     */
    public function query($key = null, $default = null): mixed
    {
        // Use the getQuery method to retrieve the requested key or all parameters.
        return $this->getQuery($key, $default);
    }

    /**
     * Retrieve all request parameters as a DataObject.
     *
     * This method extracts all parameters from the current HTTP request, formats them
     * into a DataObject instance, and returns it. This allows for cleaner access and
     * manipulation of request data within the application.
     *
     * @return array The request parameters array.
     */
    public function queries(): array
    {
        // Fetch all parameters from the current request
        return $this->getParams();
    }

    /**
     * Set a query string parameter for the current request.
     * This method allows setting a value for a specific query parameter key.
     *
     * @param string $key The key of the query string parameter.
     * @param mixed $value The value to set for the query parameter.
     *
     * @return Request
     */
    public function setParam($key, $value): Request
    {
        // Set param via parent method
        parent::setParam($key, $value);

        return $this;
    }

    /**
     * Generates the normalized query string for the request.
     *
     * This method uses the `normalizeQueryString` function to:
     * - Sort keys and values.
     * - Ensure consistent encoding for the query string.
     *
     * @return string|null The normalized query string or null if it is empty.
     */
    public function getQueryString(): ?string
    {
        // Retrieve the raw query string from the server parameters.
        $qs = static::normalizeQueryString($this->getServer()->get('QUERY_STRING'));

        // Return null if the query string is empty; otherwise, return the normalized string.
        return $qs === '' ? null : $qs;
    }
}
