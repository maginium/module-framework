<?php

declare(strict_types=1);

namespace Maginium\Framework\Hashing;

use Magento\Framework\Encryption\EncryptorInterface;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Foundation\Exceptions\RuntimeException;
use Maginium\Framework\Config\Enums\ConfigDrivers;
use Maginium\Framework\Hashing\Enums\HashAlgorithm;
use Maginium\Framework\Hashing\Interfaces\HashInterface;
use Maginium\Framework\Support\Facades\Config;
use Maginium\Framework\Support\Validator;

/**
 * Class HashManager.
 *
 * The `HashManager` class provides general-purpose utilities for performing hashing
 * and HMAC operations. It serves as a central point for managing secure hash
 * generation and verification, making it easier to implement cryptographic functions
 * throughout the application.
 */
class HashManager implements HashInterface
{
    /**
     * The encryptor instance for performing hashing and HMAC operations.
     *
     * @var EncryptorInterface
     */
    protected EncryptorInterface $encryptor;

    /**
     * Constructor to inject the EncryptorInterface dependency.
     *
     * @param EncryptorInterface $encryptor The encryptor instance.
     */
    public function __construct(EncryptorInterface $encryptor)
    {
        $this->encryptor = $encryptor;
    }

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
    public function make(string $data, string $algorithm = HashAlgorithm::SHA256, ?string $key = null): string
    {
        // List of supported algorithms from PHP
        $supportedAlgorithms = hash_algos();

        // Check if the provided algorithm is supported
        if (! in_array($algorithm, $supportedAlgorithms, true)) {
            throw Exception::make("Hash algorithm '{$algorithm}' is not supported.");
        }

        // Warning about MD5 usage
        if ($algorithm === HashAlgorithm::MD5) {
            // Step 1: Apply a salt to the input data to mitigate pre-computed attacks
            $salt = $this->generateSalt();
            $dataWithSalt = $salt . $data;

            // Step 2: Generate the MD5 hash
            $md5Hash = md5($dataWithSalt);

            // Step 3: Include the salt in the output to allow verification
            return $salt . ':' . $md5Hash;
        }

        // If the algorithm is HMAC, ensure a key is provided
        if ($algorithm === HashAlgorithm::HMAC && $key === null) {
            throw Exception::make('A key must be provided for HMAC hashing.');
        }

        // Generate the hash using the specified algorithm
        if ($algorithm === HashAlgorithm::HMAC) {
            // Use HMAC with the specified key; defaulting to SHA-256 for HMAC
            return hash_hmac(HashAlgorithm::SHA256, $data, $key);
        }

        // Generate the hash using Magento's encryptor for other algorithms
        return $this->encryptor->hash($data);
    }

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
    public function verify(string $data, string $hash, string $algorithm = HashAlgorithm::SHA256): bool
    {
        // Check if the provided algorithm is supported
        if (! Validator::inArray($algorithm, hash_algos(), true)) {
            throw Exception::make("Hash algorithm '{$algorithm}' is not supported.");
        }

        // Generate a hash from the provided data and compare it with the given hash
        return $this->encryptor->validateHash($hash, $this->make($data, $algorithm));
    }

    /**
     * Generate a secure bcrypt hash.
     *
     * @param string $value The value to hash.
     * @param array $options The options for hashing.
     *
     * @throws RuntimeException If bcrypt hashing is not supported.
     *
     * @return string The generated bcrypt hash.
     */
    public function secure(string $value, array $options = []): string
    {
        // Generate a bcrypt hash with the specified options
        $hash = password_hash($value, PASSWORD_BCRYPT, [
            'cost' => self::cost($options),
        ]);

        // Check if bcrypt hashing was successful
        if ($hash === false) {
            throw RuntimeException::make('Bcrypt hashing not supported.');
        }

        return $hash;
    }

    /**
     * Check the given plain value against a hash.
     *
     * @param string $value The plain value.
     * @param string $hashedValue The hashed value.
     * @param array $options The options for verification.
     *
     * @return bool True if the plain value matches the hashed value, false otherwise.
     */
    public function check(string $value, string $hashedValue, array $options = []): bool
    {
        // Check if the hashed value is empty
        if (Validator::isEmpty($hashedValue)) {
            return false;
        }

        // Verify the plain value against the hashed value
        return $this->encryptor->isValidHash($value, $hashedValue);
    }

    /**
     * Check if the given hash has been hashed using the given options.
     *
     * @param string $hashedValue The hashed value.
     * @param array $options The options for hashing.
     *
     * @return bool True if the hash needs to be rehashed, false otherwise.
     */
    public function needsRehash(string $hashedValue, array $options = []): bool
    {
        // Check if the hashed value needs to be rehashed with the specified options
        return password_needs_rehash($hashedValue, PASSWORD_BCRYPT, [
            'cost' => self::cost($options),
        ]);
    }

    /**
     * Determine if the given string is a hashed value.
     *
     * @param string $value The string to check.
     *
     * @return bool True if the string is a hashed value, false otherwise.
     */
    public function isHashed(string $value): bool
    {
        // Check if the provided value is a hashed value by examining its information
        return $this->info($value)['algo'] !== null;
    }

    /**
     * Get information about the given hashed value.
     *
     * @param string $hashedValue The hashed value.
     *
     * @return array Information about the hash.
     */
    public function info(string $hashedValue): array
    {
        // Return information about the hashed value
        return password_get_info($hashedValue);
    }

    /**
     * Extract the cost value from the options array or the configuration.
     *
     * @param array $options Optional options array to specify cost.
     *
     * @return int The cost value, defaults to the configuration value or 10 if not specified.
     */
    protected function cost(array $options = []): int
    {
        // Check if a cost is provided in the options; if not, retrieve from configuration
        return $options['cost'] ?? Config::driver(ConfigDrivers::ENV)->getInt('BCRYPT_ROUNDS', 10);
    }

    /**
     * Generate a random salt for hashing.
     *
     * @return string The generated salt.
     */
    private function generateSalt(): string
    {
        // Generate a 16-character hex salt
        return bin2hex(random_bytes(8));
    }
}
