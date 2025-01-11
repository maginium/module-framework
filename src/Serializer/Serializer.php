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
     * Safely unserializes a string into its original data format.
     *
     * This method takes a serialized string and converts it back into its
     * original data type. It offers an option to allow or disallow classes during
     * the unserialization process for security and flexibility.
     *
     * @param string $string The serialized string to be unserialized.
     * @param bool $allowedClasses Whether to allow class instances during unserialization.
     *                             `true` allows all classes; `false` disallows all classes.
     *
     * @throws InvalidArgumentException If the unserialization fails due to invalid input.
     *
     * @return mixed The unserialized data, which can be any valid PHP data type.
     */
    public function unserialize($string, bool $allowedClasses = false): mixed
    {
        try {
            // If $allowedClasses is true, allow all classes during unserialization.
            // This enables object deserialization, which can be useful but poses security risks.
            if ($allowedClasses) {
                // Standard PHP unserialize function without restrictions.
                return unserialize($string);
            }

            // If $allowedClasses is false, proceed with stricter unserialization.
            // Use parent::unserialize to leverage any overridden or specialized behavior.
            $unserializedData = parent::unserialize($string);

            // Return the successfully unserialized data.
            return $unserializedData;
        } catch (Exception $e) {
            // Throw an InvalidArgumentException with a clear error message.

            throw $e;
        }
    }
}
