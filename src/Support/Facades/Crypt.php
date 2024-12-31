<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Magento\Framework\Encryption\EncryptorInterface;
use Maginium\Framework\Support\Facade;

/**
 * Class Crypt.
 *
 * Facade for interacting with the Encryptor service.
 *
 * @method static string getHash(string $password, bool|int|string $salt = false)
 *     Generate a salted hash.
 *     Parameters:
 *     - string $password: The password to hash.
 *     - bool|int|string $salt: The salt to use (false for no salt, true for random salt, integer for specific length, string for actual salt value).
 *     Returns:
 *     - string: The generated hash.
 * @method static string hash(string $data)
 *     Hash a string.
 *     Parameters:
 *     - string $data: The data to hash.
 *     Returns:
 *     - string: The hashed string.
 * @method static bool validateHash(string $password, string $hash)
 *     Synonym to isValidHash.
 *     Parameters:
 *     - string $password: The password to validate.
 *     - string $hash: The hash to validate against.
 *     Returns:
 *     - bool: True if the password matches the hash, false otherwise.
 * @method static bool isValidHash(string $password, string $hash)
 *     Validate hash against hashing method.
 *     Parameters:
 *     - string $password: The password to validate.
 *     - string $hash: The hash to validate against.
 *     Returns:
 *     - bool: True if the password matches the hash, false otherwise.
 * @method static bool validateHashVersion(string $hash, bool $validateCount = false)
 *     Validate hashing algorithm version.
 *     Parameters:
 *     - string $hash: The hash to validate.
 *     - bool $validateCount: Whether to validate the count.
 *     Returns:
 *     - bool: True if the hash version is valid, false otherwise.
 * @method static string encrypt(string $data)
 *     Encrypt a string.
 *     Parameters:
 *     - string $data: The data to encrypt.
 *     Returns:
 *     - string: The encrypted string.
 * @method static string decrypt(string $data)
 *     Decrypt a string.
 *     Parameters:
 *     - string $data: The data to decrypt.
 *     Returns:
 *     - string: The decrypted string.
 * @method static \Magento\Framework\Encryption\Crypt validateKey(string $key)
 *     Return crypt model, instantiate if it is empty.
 *     Parameters:
 *     - string $key: The key to validate.
 *     Returns:
 *     - \Magento\Framework\Encryption\Crypt: The crypt model.
 * @method static string generateKey(string $cipher)
 *     Create a new encryption key for the given cipher.
 *     Parameters:
 *     - string $cipher: The cipher for which the key is to be generated.
 *     Returns:
 *     - string: The generated encryption key.
 *
 * @see EncryptorInterface
 */
class Crypt extends Facade
{
    /**
     * Create a new encryption key for the given cipher.
     *
     * @param  string  $cipher
     *
     * @return string
     */
    public static function generateKey($cipher)
    {
        return random_bytes(self::$supportedCiphers[mb_strtolower($cipher)]['size'] ?? 32);
    }

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
        return EncryptorInterface::class;
    }
}
