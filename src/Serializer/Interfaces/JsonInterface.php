<?php

declare(strict_types=1);

namespace Maginium\Framework\Serializer\Interfaces;

use Maginium\Foundation\Exceptions\InvalidArgumentException;

/**
 * Interface for JSON Serialization and Deserialization.
 *
 * This interface defines methods for encoding and decoding data to and from
 * JSON format, as well as validating and checking if a string is a valid JSON.
 */
interface JsonInterface
{
    /**
     * Encode data into a JSON string.
     *
     * This method takes a variable of mixed type (string, int, float, bool, array, or null),
     * and converts it into a JSON formatted string. If the encoding fails,
     * an InvalidArgumentException is thrown.
     *
     * @param  mixed  $data  Data to be serialized. Acceptable types are
     *                       string, int, float, bool, array, or null.
     *
     * @throws InvalidArgumentException If the data cannot be encoded into JSON.
     *
     * @return string The JSON encoded string if successful.
     * @return false If encoding fails.
     */
    public function encode(mixed $data): string|false;

    /**
     * Decode a JSON string back into its original data format.
     *
     * This method takes a JSON encoded string and converts it back to its
     * original data type. If the decoding fails due to invalid JSON,
     * an InvalidArgumentException is thrown with an error message.
     *
     * @param  string  $string  JSON string to be unserialized.
     *
     * @throws InvalidArgumentException If the string cannot be decoded
     *                                  into its original data format.
     *
     * @return mixed The original data, which can be string, int, float,
     *               bool, array, or null.
     */
    public function decode(string $string): mixed;

    /**
     * Check if a string is a valid JSON formatted string.
     *
     * @param  string  $json  The string to check.
     *
     * @return bool True if the string is valid JSON, false otherwise.
     */
    public function isValid(string $json): bool;
}
