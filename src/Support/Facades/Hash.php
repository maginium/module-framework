<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Maginium\Framework\Hashing\Interfaces\HashInterface;
use Maginium\Framework\Support\Facade;

/**
 * Hash Facade.
 *
 * Provides a static interface to the hashing methods defined in the HashInterface.
 *
 * @method static string make(string $data, string $algorithm = HashAlgorithm::SHA3_256, ?string $key = null)
 *     Generate a hash for the given data using the specified algorithm.
 *     Parameters:
 *     - string $data: The data to hash.
 *     - string $algorithm: The hashing algorithm to use (default: 'sha256').
 *     - string|null $key: The secret key to use for HMAC (if applicable).
 *     Returns:
 *     - string: The generated hash.
 * @method static bool verify(string $data, string $hash, string $algorithm = HashAlgorithm::SHA3_256)
 *     Verify that the given data matches the given hash.
 *     Parameters:
 *     - string $data: The data to verify.
 *     - string $hash: The hash to verify against.
 *     - string $algorithm: The hashing algorithm to use (default: 'sha256').
 *     Returns:
 *     - bool: True if the data matches the hash, false otherwise.
 * @method static array info(string $hashedValue)
 *     Get information about the given hashed value.
 *     Parameters:
 *     - string $hashedValue: The hashed value.
 *     Returns:
 *     - array: Information about the hash.
 * @method static string secure(string $value, array $options = [])
 *     Generate a secure bcrypt hash.
 *     Parameters:
 *     - string $value: The value to hash.
 *     - array $options: The options for hashing.
 *     Returns:
 *     - string: The generated bcrypt hash.
 * @method static bool check(string $value, string $hashedValue, array $options = [])
 *     Check the given plain value against a hash.
 *     Parameters:
 *     - string $value: The plain value.
 *     - string $hashedValue: The hashed value.
 *     - array $options: The options for verification.
 *     Returns:
 *     - bool: True if the plain value matches the hashed value, false otherwise.
 * @method static bool needsRehash(string $hashedValue, array $options = [])
 *     Check if the given hash has been hashed using the given options.
 *     Parameters:
 *     - string $hashedValue: The hashed value.
 *     - array $options: The options for hashing.
 *     Returns:
 *     - bool: True if the hash needs to be rehashed, false otherwise.
 * @method static bool isHashed(string $value)
 *     Determine if the given string is a hashed value.
 *     Parameters:
 *     - string $value: The string to check.
 *     Returns:
 *     - bool: True if the string is a hashed value, false otherwise.
 *
 * @see HashInterface
 */
class Hash extends Facade
{
    /**
     * Get the accessor for the facade.
     *
     * This method must be implemented by subclasses to return the accessor string
     * corresponding to the underlying service or class the facade represents.
     *
     * @return string The accessor for the facade.
     */
    protected static function getAccessor(): string
    {
        return HashInterface::class;
    }
}
