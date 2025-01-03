<?php

declare(strict_types=1);

namespace Maginium\Framework\Hashing\Interfaces;

use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Hashing\Enums\HashAlgorithm;

/**
 * Interface HashInterface.
 *
 * Defines the contract for hashing and HMAC operations.
 */
interface HashInterface
{
    /**
     * Generate a hash for the given data using the specified algorithm.
     *
     * @param string $data The data to hash.
     * @param string $algorithm The hashing algorithm to use (default: 'sha256').
     * @param string|null $key The secret key to use for HMAC (if applicable).
     *
     * @throws Exception If the hashing algorithm is not supported or if MD5 is used inappropriately.
     *
     * @return string The generated hash.
     */
    public function make(string $data, string $algorithm = HashAlgorithm::SHA3_256, ?string $key = null): string;

    /**
     * Verify that the given data matches the given hash.
     *
     * @param string $data The data to verify.
     * @param string $hash The hash to verify against.
     * @param string $algorithm The hashing algorithm to use (default: 'sha256').
     *
     * @throws Exception If the hashing algorithm is not supported.
     *
     * @return bool True if the data matches the hash, false otherwise.
     */
    public function verify(string $data, string $hash, string $algorithm = HashAlgorithm::SHA3_256): bool;

    /**
     * Get information about the given hashed value.
     *
     * @param string $hashedValue The hashed value.
     *
     * @return array Information about the hash.
     */
    public function info(string $hashedValue): array;

    /**
     * Generate a secure bcrypt hash.
     *
     * @param string $value The value to hash.
     * @param array $options The options for hashing.
     *
     * @return string The generated bcrypt hash.
     */
    public function secure(string $value, array $options = []): string;

    /**
     * Check the given plain value against a hash.
     *
     * @param string $value The plain value.
     * @param string $hashedValue The hashed value.
     * @param array $options The options for verification.
     *
     * @return bool True if the plain value matches the hashed value, false otherwise.
     */
    public function check(string $value, string $hashedValue, array $options = []): bool;

    /**
     * Check if the given hash has been hashed using the given options.
     *
     * @param string $hashedValue The hashed value.
     * @param array $options The options for hashing.
     *
     * @return bool True if the hash needs to be rehashed, false otherwise.
     */
    public function needsRehash(string $hashedValue, array $options = []): bool;

    /**
     * Determine if the given string is a hashed value.
     *
     * @param string $value The string to check.
     *
     * @return bool True if the string is a hashed value, false otherwise.
     */
    public function isHashed(string $value): bool;
}
