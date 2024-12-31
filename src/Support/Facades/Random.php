<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Magento\Framework\Math\Random as BaseRandom;
use Maginium\Framework\Support\Facade;

/**
 * Class Random.
 *
 * Facade for interacting with the Random service.
 *
 * @method static string getRandomString(int $length, ?string $chars = null)
 *     Get a random string.
 *     Parameters:
 *     - int $length: The length of the random string.
 *     - ?string $chars: The characters to use for the random string (null for default characters).
 *     Returns:
 *     - string: The generated random string.
 * @method static int getRandomNumber(int $min = 0, ?int $max = null)
 *     Return a random number in the specified range.
 *     Parameters:
 *     - int $min: The minimum value (default is 0).
 *     - ?int $max: The maximum value (null for default max value).
 *     Returns:
 *     - int: A random integer value between min and max.
 * @method static string getUniqueHash(string $prefix = '')
 *     Generate a hash from a unique ID.
 *     Parameters:
 *     - string $prefix: The prefix to use for the unique hash.
 *     Returns:
 *     - string: The generated unique hash.
 * @method static string getRandomBytes(int $length)
 *     Generate a base64 encoded binary string.
 *     Parameters:
 *     - int $length: The length of the random bytes.
 *     Returns:
 *     - string: The generated base64 encoded binary string.
 *
 * @see BaseRandom
 */
class Random extends Facade
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
        return BaseRandom::class;
    }
}
