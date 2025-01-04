<?php

declare(strict_types=1);

namespace Maginium\Framework\Url\Facades;

use Maginium\Framework\Support\Facade;
use Maginium\Framework\Url\Interfaces\UrlInterface;

/**
 * Facade for interacting with URL-related functionalities.
 *
 * @method static mixed getUseSession() Retrieve the session usage status.
 * @method static string getBaseUrl(array $params = []) Retrieve the base URL considering scope, type, and secure flag.
 * @method static string getCurrentUrl() Retrieve the current URL from the request.
 * @method static string getRouteUrl(string|null $routePath = null, array|null $routeParams = null) Generate a URL for the given route path and parameters.
 * @method static void addSessionParam() Add session-specific parameters to the current URL.
 * @method static void addQueryParams(array $data) Append multiple query parameters to the URL.
 * @method static void setQueryParam(string $key, mixed $data) Set or update a single query parameter in the URL.
 * @method static string getUrl(string|null $routePath = null, array|null $routeParams = null) Get a fully resolved URL for a route.
 * @method static string escape(string $value) Escape a string to make it safe for URL usage.
 * @method static string getDirectUrl(string $url, array $params = []) Retrieve a direct URL with optional parameters.
 * @method static string sessionUrlVar(string $html) Add session variables to URLs within HTML content.
 * @method static bool isOwnOriginUrl() Check if the current URL originates from the same application.
 * @method static string getRedirectUrl(string $url) Generate a valid redirect URL, ensuring it conforms to security rules.
 * @method static void setScope(mixed $params) Set the scope for URL generation.
 * @method static string getBackendUrl(?string $route = '') Generate the backend URL for a specified route.
 *
 * @see UrlInterface
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
