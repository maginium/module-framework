<?php

declare(strict_types=1);

namespace Maginium\Framework\Hashing\Facades;

use Maginium\Framework\Hashing\Interfaces\HashInterface;
use Maginium\Framework\Support\Facade;

/**
 * Hash Facade.
 *
 * Provides a static interface to the hashing methods defined in the HashInterface.
 *
 * @method static string make(string $data, string $algorithm = HashAlgorithm::SHA3_256, ?string $key = null) Generate a hash for the given data using the specified algorithm.
 * @method static bool verify(string $data, string $hash, string $algorithm = HashAlgorithm::SHA3_256) Verify that the given data matches the given hash.
 * @method static array info(string $hashedValue) Get information about the given hashed value.
 * @method static string secure(string $value, array $options = []) Generate a secure bcrypt hash.
 * @method static bool check(string $value, string $hashedValue, array $options = []) Check the given plain value against a hash.
 * @method static bool needsRehash(string $hashedValue, array $options = []) Check if the given hash needs to be rehashed using the provided options.
 * @method static bool isHashed(string $value) Determine if the given string is a hashed value.
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
