<?php

declare(strict_types=1);

namespace Maginium\Framework\Support;

use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Support\Facades\Crypt;
use Maginium\Framework\Support\Facades\Log;
use Maginium\Framework\Support\Facades\Random;

/**
 * Class Password.
 *
 * This class provides methods to encrypt, decrypt, and generate random passwords.
 */
class Password
{
    /**
     * Generate a random password.
     *
     * This method generates a random password of a specified length using the
     * random string generator. The default length is set to 12 characters.
     *
     * @param int $len The length of the password (default is 12).
     *
     * @return string|null The generated random password or null on failure.
     */
    public static function generate(int $len = 12): ?string
    {
        try {
            // Generate and return a random string of the specified length.
            return Random::getRandomString($len);
        } catch (Exception $e) {
            // Log any exceptions that occur during the password generation process.
            Log::error(__('Error in %s:%s - %s', __CLASS__, __FUNCTION__, $e->getMessage()));

            // Return null on failure.
            return null;
        }
    }

    /**
     * Encrypt a password using Crypt's encryption service.
     *
     * This method takes a plaintext password, encrypts it using the Encryptor,
     * and returns the encrypted password. If an error occurs, it logs the error
     * message and returns null.
     *
     * @param string $plain The password to encrypt.
     *
     * @return string|null The encrypted password or null on failure.
     */
    public static function encrypt(string $plain): ?string
    {
        try {
            // Encrypt the password using the Encryptor facade.
            return Crypt::encrypt($plain);
        } catch (Exception $e) {
            // Log any exceptions that occur during the encryption process.
            Log::error(__('Error in %s:%s - %s', __CLASS__, __FUNCTION__, $e->getMessage()));

            // Return null on failure.
            return null;
        }
    }

    /**
     * Decrypt an encrypted password using Crypt's encryption service.
     *
     * This method takes an encrypted password, decrypts it using the Encryptor,
     * and returns the original plaintext password. If an error occurs, it logs
     * the error message and returns null.
     *
     * @param string $enc The encrypted password.
     *
     * @return string|null The decrypted password or null on failure.
     */
    public static function decrypt(string $enc): ?string
    {
        try {
            // Decrypt the password using the Encryptor facade.
            return Crypt::decrypt($enc);
        } catch (Exception $e) {
            // Log any exceptions that occur during the decryption process.
            Log::error(__('Error in %s:%s - %s', __CLASS__, __FUNCTION__, $e->getMessage()));

            // Return null on failure.
            return null;
        }
    }
}
