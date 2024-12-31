<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Magento\Framework\Session\SessionManagerInterface;
use Maginium\Framework\Support\Facade;

/**
 * Session Facade.
 *
 * @method static SessionManagerInterface start()
 * @method static void writeClose()
 * @method static bool isSessionExists()
 * @method static string getSessionId()
 * @method static string getName()
 * @method static SessionManagerInterface setName(string $name)
 * @method static void destroy(array $options = null)
 * @method static $this clearStorage()
 * @method static string getCookieDomain()
 * @method static string getCookiePath()
 * @method static int getCookieLifetime()
 * @method static SessionManagerInterface setSessionId(?string $sessionId)
 * @method static SessionManagerInterface regenerateId()
 * @method static void expireSessionCookie()
 * @method static string getSessionIdForHost(string $urlHost)
 * @method static bool isValidForHost(string $host)
 * @method static bool isValidForPath(string $path)
 * @method static bool shouldBlock()
 *
 * @see SessionManager
 */
class Session extends Facade
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
        return SessionManagerInterface::class;
    }
}
