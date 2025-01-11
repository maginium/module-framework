<?php

declare(strict_types=1);

namespace Maginium\Framework\Serializer\Interfaces;

use Magento\Framework\Serialize\SerializerInterface as BaseSerializerInterface;
use Maginium\Foundation\Exceptions\InvalidArgumentException;

/**
 * SerializerInterface.
 *
 * This interface defines the methods required for a serializer,
 * ensuring that any implementing class provides the necessary
 * serialization and unserialization functionalities.
 */
interface SerializerInterface extends BaseSerializerInterface
{
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
    public function unserialize($string, bool $allowedClasses = false): mixed;
}
