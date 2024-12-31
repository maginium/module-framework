<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Stdlib\Cookie\CookieMetadata;
use Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Magento\Framework\Stdlib\Cookie\SensitiveCookieMetadata;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Maginium\Framework\Support\Facade;

/**
 * Cookie.
 *
 * This facade combines cookie functionality with Magento's cookie management.
 *
 * @method static string|null get(string $name, mixed $default = null)
 *     Retrieve a value from a cookie.
 *     Parameters:
 *     - string $name: The name of the cookie.
 *     - mixed $default: The default value to return if the cookie does not exist.
 *     Returns:
 *     - string|null: The value of the cookie or null if not found.
 * @method static void put(string $name, mixed $value, int $minutes = 0)
 *     Set a value in a public cookie with the given name and value.
 *     Parameters:
 *     - string $name: The name of the cookie.
 *     - mixed $value: The value of the cookie.
 *     - int $minutes: The number of minutes until the cookie expires.
 * @method static void forget(string $name)
 *     Deletes a cookie with the given name.
 *     Parameters:
 *     - string $name: The name of the cookie.
 * @method static void setSensitiveCookie(string $name, string $value, SensitiveCookieMetadata $metadata = null)
 *     Set a value in a private cookie with the given name and value pairing.
 *     Parameters:
 *     - string $name: The name of the cookie.
 *     - string $value: The value of the cookie.
 *     - SensitiveCookieMetadata $metadata: Metadata for the sensitive cookie.
 *     Throws:
 *     - FailureToSendException: Cookie couldn't be sent to the browser.
 *     - CookieSizeLimitReachedException: Thrown when the cookie is too big to store additional data.
 *     - InputException: If the cookie name is empty or contains invalid characters.
 * @method static void setPublicCookie(string $name, string $value, PublicCookieMetadata $metadata = null)
 *     Set a value in a public cookie with the given name and value pairing.
 *     Parameters:
 *     - string $name: The name of the cookie.
 *     - string $value: The value of the cookie.
 *     - PublicCookieMetadata $metadata: Metadata for the public cookie.
 *     Throws:
 *     - FailureToSendException: If cookie couldn't be sent to the browser.
 *     - CookieSizeLimitReachedException: Thrown when the cookie is too big to store additional data.
 *     - InputException: If the cookie name is empty or contains invalid characters.
 * @method static void deleteCookie(string $name, CookieMetadata $metadata = null)
 *     Deletes a cookie with the given name.
 *     Parameters:
 *     - string $name: The name of the cookie.
 *     - CookieMetadata $metadata: Metadata for the cookie deletion.
 *     Throws:
 *     - FailureToSendException: If cookie couldn't be sent to the browser.
 *     - InputException: If the cookie name is empty or contains invalid characters.
 */
class Cookie extends Facade
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
        return CookieManagerInterface::class;
    }
}
