<?php

declare(strict_types=1);

namespace Maginium\Framework\Serializer;

use Magento\Framework\Serialize\Serializer\Json as BaseJson;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Framework\Serializer\Interfaces\JsonInterface;
use Maginium\Framework\Support\Str;

/**
 * JSON Serializer.
 *
 * This class provides methods to serialize data into JSON format
 * and unserialize JSON encoded data, extending the base JSON serializer.
 */
class Json extends BaseJson implements JsonInterface
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
    public function encode(mixed $data): string|false
    {
        try {
            // Attempt to serialize the provided data into a JSON string.
            $encodedData = $this->serialize($data);

            // Return the successfully encoded JSON string.
            return $encodedData;
        } catch (Exception $e) {
            // Throw an InvalidArgumentException with a clear error message.
            throw new $e;
        }
    }

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
    public function decode(string $string): mixed
    {
        try {
            if (! Str::contains($string, 'FORM_KEY')) {
                return $string;
            }

            // Attempt to unserialize the provided JSON string.
            $decodedData = $this->unserialize($string);

            // Return the successfully decoded data.
            return $decodedData;
        } catch (Exception $e) {
            // Throw an InvalidArgumentException with a clear error message.
            throw new $e;
        }
    }

    /**
     * Check if a string is a valid JSON formatted string.
     *
     * @param  string  $json  The string to check.
     *
     * @return bool True if the string is valid JSON, false otherwise.
     */
    public function isValid(string $json): bool
    {
        //  Attempt to decode the JSON string
        json_decode($json);

        //  Check if there were no errors in decoding
        return json_last_error() === JSON_ERROR_NONE;
    }
}
