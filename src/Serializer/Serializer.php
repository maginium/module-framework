<?php

declare(strict_types=1);

namespace Maginium\Framework\Serializer;

use Magento\Framework\Serialize\Serializer\Serialize as BaseSerialize;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Framework\Serializer\Interfaces\SerializerInterface;

/**
 * Serializer.
 *
 * This class provides methods to serialize data into various formats
 * and unserialize encoded data, extending the base serialization methods.
 */
class Serializer extends BaseSerialize implements SerializerInterface
{
    /**
     * Serialize data into a string format.
     *
     * This method takes a variable of mixed type (string, int, float, bool, array, or null),
     * and converts it into a serialized string. If the serialization fails,
     * an InvalidArgumentException is thrown.
     *
     * @param  mixed  $data  Data to be serialized. Acceptable types are
     *                       string, int, float, bool, array, or null.
     *
     * @throws InvalidArgumentException If the data cannot be serialized.
     *
     * @return string The serialized string if successful.
     * @return false If serialization fails.
     */
    public function serialize(mixed $data): string|false
    {
        try {
            // Attempt to serialize the provided data into a string.
            $serializedData = parent::serialize($data);

            // Return the successfully serialized string.
            return $serializedData;
        } catch (Exception $e) {
            // Throw an InvalidArgumentException with a clear error message.
            throw new $e;
        }
    }

    /**
     * Unserialize a string back into its original data format.
     *
     * This method takes a serialized string and converts it back to its
     * original data type. If the unserialization fails due to invalid input,
     * an InvalidArgumentException is thrown with an error message.
     *
     * @param  string  $string  String to be unserialized.
     *
     * @throws InvalidArgumentException If the string cannot be unserialized
     *                                  into its original data format.
     *
     * @return mixed The original data, which can be string, int, float,
     *               bool, array, or null.
     */
    public function unserialize($string): mixed
    {
        try {
            // Attempt to unserialize the provided string.
            $unserializedData = parent::unserialize($string);

            // Return the successfully unserialized data.
            return $unserializedData;
        } catch (Exception $e) {
            // Throw an InvalidArgumentException with a clear error message.
            throw new $e;
        }
    }
}
