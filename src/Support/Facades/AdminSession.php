<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Magento\Backend\Model\Auth\Session as AdminSessionManager;
use Maginium\Framework\Support\Facade;

/**
 * Class AdminSession.
 *
 * Facade for interacting with the Admin Session management and encryption services.
 *
 * @method static void _resetState()
 *     Reset the state of the current session.
 * @method static void refreshAcl($user = null)
 *     Refresh the ACL (Access Control List) for a user.
 *     Parameters:
 *     - mixed $user: Optional user to refresh ACL for.
 * @method static bool isAllowed($resource, $privilege = null)
 *     Check if a resource is allowed for the current session.
 *     Parameters:
 *     - mixed $resource: The resource to check.
 *     - mixed|null $privilege: The privilege to check (optional).
 *     Returns:
 *     - bool: True if allowed, false otherwise.
 * @method static bool isLoggedIn()
 *     Determine if the current user is logged in.
 *     Returns:
 *     - bool: True if logged in, false otherwise.
 * @method static void prolong()
 *     Extend the current session's lifetime.
 * @method static bool isFirstPageAfterLogin()
 *     Check if this is the first page after login.
 *     Returns:
 *     - bool: True if it is the first page, false otherwise.
 * @method static void setIsFirstPageAfterLogin($value)
 *     Set the state for the first page after login.
 *     Parameters:
 *     - bool $value: The value to set.
 * @method static void processLogin()
 *     Process login actions for the session.
 * @method static void processLogout()
 *     Process logout actions for the session.
 * @method static bool isValidForPath($path)
 *     Validate the session for a specific path.
 *     Parameters:
 *     - string $path: The path to validate.
 *     Returns:
 *     - bool: True if valid for the path, false otherwise.
 * @method static \Magento\Framework\Acl getAcl()
 *     Retrieve the Access Control List (ACL).
 *     Returns:
 *     - \Magento\Framework\Acl: The ACL instance.
 * @method static void setAcl(\Magento\Framework\Acl $acl)
 *     Set the Access Control List (ACL).
 *     Parameters:
 *     - \Magento\Framework\Acl $acl: The ACL to set.
 * @method static mixed getData($key = '', $clear = false)
 *     Retrieve data from the session.
 *     Parameters:
 *     - string $key: The key of the data to retrieve.
 *     - bool $clear: Whether to clear the data after retrieval.
 *     Returns:
 *     - mixed: The data associated with the specified key.
 * @method static array getHeaders()
 *     Retrieve all headers from the request.
 *     Returns:
 *     - array: An associative array of all request headers.
 * @method static mixed getHeader(string $name, $default = null)
 *     Retrieve a specific request header.
 *     Parameters:
 *     - string $name: The name of the header to retrieve.
 *     - mixed $default: Default value if the header is not found.
 *     Returns:
 *     - mixed: The header value or the default value.
 *
 * @see AdminSessionManager
 */
class AdminSession extends Facade
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
        return AdminSessionManager::class;
    }
}
