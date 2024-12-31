<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Maginium\Framework\Support\Facade;
use Maginium\Framework\Url\Interfaces\UrlInterface;

/**
 * @method static string getBaseUrl(array $params = [])
 * @method static string getCurrentUrl()
 * @method static string getRouteUrl(string $routePath = null, array $routeParams = null)
 * @method static \Maginium\Framework\UrlInterface addSessionParam()
 * @method static \Maginium\Framework\UrlInterface addQueryParams(array $data)
 * @method static \Maginium\Framework\UrlInterface setQueryParam(string $key, mixed $data)
 * @method static string getUrl(string $routePath = null, array $routeParams = null)
 * @method static string escape(string $value)
 * @method static string getDirectUrl(string $url, array $params = [])
 * @method static string sessionUrlVar(string $html)
 * @method static bool isOwnOriginUrl()
 * @method static string getRedirectUrl(string $url)
 * @method static \Maginium\Framework\UrlInterface setScope(mixed $params)
 *
 * @see UrlGenerator
 */
class Url extends Facade
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
        return UrlInterface::class;
    }
}
