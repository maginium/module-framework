<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Laminas\Http\Headers;
use Maginium\Framework\Request\Interfaces\RequestInterface;
use Maginium\Framework\Support\Facade;

/**
 * Facade for interacting with the Request service.
 *
 * @method static string getActionName() Retrieve the action name from the request.
 * @method static $this setActionName(string $name) Set the action name for the request. Parameters: - $name: The name of the action.
 * @method static mixed getParam(string $key, mixed $defaultValue = null) Retrieve a request parameter by key. Parameters: - $key: The key of the parameter to retrieve. - $defaultValue: Default value to return if the parameter does not exist.
 * @method static $this setParams(array $params) Set multiple request parameters from a key-value array. Parameters: - $params: An array of key-value pairs representing parameters.
 * @method static array getParams() Retrieve all request parameters as an array.
 * @method static ?string getCookie(?string $name, ?string $default) Retrieve a cookie value by name from the request.
 * @method static bool isSecure() Check if the request was delivered over HTTPS.
 * @method static string getOriginalPathInfo() Retrieve the original path info from the request.
 * @method static string getPathInfo() Retrieve the path info from the request.
 * @method static self setPathInfo(?string $pathInfo = null) Set the path info for the request. Parameters: - $pathInfo: Optional path info to set.
 * @method static bool isDirectAccessFrontendName(string $code) Check if the request is for a direct access frontend name. Parameters: - $code: The code of the frontend name to check.
 * @method static string getBasePath() Retrieve the base path from the request.
 * @method static ?string getFrontName() Retrieve the frontend name from the request.
 * @method static self setRouteName(string $route) Set the route name for the request. Parameters: - $route: The name of the route to set.
 * @method static ?string getRouteName() Retrieve the route name from the request.
 * @method static self setControllerModule(string $module) Set the controller module for the request. Parameters: - $module: The name of the module to set.
 * @method static string getControllerModule() Retrieve the controller module from the request.
 * @method static self initForward() Initialize forward for the request.
 * @method static array|string|null getBeforeForwardInfo(?string $name = null) Retrieve information before forwarding the request. Parameters: - $name: Optional name of the information to retrieve.
 * @method static string getDistroBaseUrl() Retrieve the base URL from the distribution server.
 * @method static string getDistroBaseUrlPath(array $server) Retrieve the base URL path from the distribution server. Parameters: - $server: The server environment variables array.
 * @method static string getUrlNoScript(string $url) Retrieve the URL without the script part. Parameters: - $url: The full URL to process.
 * @method static string getFullActionName(string $delimiter = '_') Retrieve the full action name from the request. Parameters: - $delimiter: Optional delimiter to use between module, controller, and action.
 * @method static bool isSafeMethod() Check if the request method is safe (GET, HEAD, OPTIONS).
 * @method static void _resetState() Reset the internal state of the request object.
 * @method static Headers getHeaders() Retrieve all the headers from the request. Returns an instance of the `Headers` class, which provides access to request headers. The `Headers` object can be used to interact with header names and values, including standard headers like "X-Request-ID", "Content-Type", etc.
 * @method static mixed query(string $key, mixed $default = null) Retrieve a query parameter by key. Parameters: - $key: The key of the query parameter to retrieve. - $default: Default value to return if the parameter does not exist.
 * @method static $this setUser(Customer|User|null $user) Set the current user. Parameters: - $user: The user object to set.
 * @method static mixed getUser() Retrieve the current user.
 * @method static $this setLanguage(string $language) Set the current language/locale. Parameters: - $language: The language/locale to set.
 * @method static ?string getLanguage() Retrieve the current language/locale.
 * @method static void _resetState() Reset the internal state of the request object.
 * @method static mixed input(string $key, mixed $default = null) Retrieve an input parameter by key. Parameters: - $key: The key of the input parameter to retrieve. - $default: Default value to return if the parameter does not exist.
 * @method static array all() Retrieve all request input as an array.
 * @method static mixed header(string $key, mixed $default = null) Retrieve a header by key. Parameters: - $key: The header key to retrieve. - $default: Default value to return if the header does not exist.
 * @method static mixed cookie(string $key, mixed $default = null) Retrieve a cookie value by key. Parameters: - $key: The cookie key to retrieve. - $default: Default value to return if the cookie does not exist.
 * @method static mixed json(mixed $default = null) Retrieve the request JSON body. Parameters: - $default: Default value to return if no JSON is available.
 * @method static array headers() Retrieve all headers from the request.
 * @method static \Magento\Framework\App\RequestInterface capture()
 * @method static \Magento\Framework\App\RequestInterface instance()
 * @method static string method()
 * @method static string root()
 * @method static string url()
 * @method static string fullUrl()
 * @method static string fullUrlWithQuery(array $query)
 * @method static string fullUrlWithoutQuery(array|string $keys)
 * @method static string path()
 * @method static string decodedPath()
 * @method static string|null segment(int $index, string|null $default = null)
 * @method static array segments()
 * @method static bool is(mixed ...$patterns)
 * @method static bool routeIs(mixed ...$patterns)
 * @method static bool fullUrlIs(mixed ...$patterns)
 * @method static string host()
 * @method static string httpHost()
 * @method static string schemeAndHttpHost()
 * @method static string|null ip()
 * @method static array ips()
 * @method static string|null userAgent()
 * @method static \Magento\Framework\App\RequestInterface merge(array $input)
 * @method static \Magento\Framework\App\RequestInterface mergeIfMissing(array $input)
 * @method static \Magento\Framework\App\RequestInterface replace(array $input)
 * @method static mixed get(string $key, mixed $default = null)
 * @method static \Symfony\Component\HttpFoundation\InputBag|mixed json(string|null $key = null, mixed $default = null)
 * @method static \Magento\Framework\App\RequestInterface createFrom(\Magento\Framework\App\RequestInterface $from, \Magento\Framework\App\RequestInterface|null $to = null)
 * @method static \Magento\Framework\App\RequestInterface createFromBase(\Symfony\Component\HttpFoundation\Request $request)
 * @method static \Magento\Framework\App\RequestInterface duplicate(array|null $query = null, array|null $request = null, array|null $attributes = null, array|null $cookies = null, array|null $files = null, array|null $server = null)
 * @method static bool hasSession(bool $skipIfUninitialized = false)
 * @method static \Symfony\Component\HttpFoundation\Session\SessionInterface getSession()
 * @method static \Illuminate\Contracts\Session\Session session()
 * @method static void setLaravelSession(\Illuminate\Contracts\Session\Session $session)
 * @method static void setRequestLocale(string $locale)
 * @method static void setDefaultRequestLocale(string $locale)
 * @method static mixed user()
 * @method static \Illuminate\Routing\Route|object|string|null route(string|null $param = null, mixed $default = null)
 * @method static string fingerprint()
 * @method static \Magento\Framework\App\RequestInterface setJson(\Symfony\Component\HttpFoundation\InputBag $json)
 * @method static \Closure getUserResolver()
 * @method static \Magento\Framework\App\RequestInterface setUserResolver(\Closure $callback)
 * @method static \Closure getRouteResolver()
 * @method static \Magento\Framework\App\RequestInterface setRouteResolver(\Closure $callback)
 * @method static array toArray()
 * @method static void initialize(array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], string|resource|null $content = null)
 * @method static \Magento\Framework\App\RequestInterface createFromGlobals()
 * @method static \Magento\Framework\App\RequestInterface create(string $uri, string $method = 'GET', array $parameters = [], array $cookies = [], array $files = [], array $server = [], string|resource|null $content = null)
 * @method static void setFactory(callable|null $callable)
 * @method static void overrideGlobals()
 * @method static void setTrustedProxies(array $proxies, int $trustedHeaderSet)
 * @method static string[] getTrustedProxies()
 * @method static int getTrustedHeaderSet()
 * @method static void setTrustedHosts(array $hostPatterns)
 * @method static string[] getTrustedHosts()
 * @method static string normalizeQueryString(string|null $qs)
 * @method static void enableHttpMethodParameterOverride()
 * @method static bool getHttpMethodParameterOverride()
 * @method static bool hasPreviousSession()
 * @method static void setSession(\Symfony\Component\HttpFoundation\Session\SessionInterface $session)
 * @method static array getClientIps()
 * @method static string|null getClientIp()
 * @method static string getScriptName()
 * @method static string getPathInfo()
 * @method static string getBasePath()
 * @method static string getBaseUrl()
 * @method static string getScheme()
 * @method static int|string|null getPort()
 * @method static string|null getUser()
 * @method static string|null getPassword()
 * @method static string|null getUserInfo()
 * @method static string getHttpHost()
 * @method static string getRequestUri()
 * @method static string getSchemeAndHttpHost()
 * @method static string getUri()
 * @method static string getUriForPath(string $path)
 * @method static string getRelativeUriForPath(string $path)
 * @method static string|null getQueryString()
 * @method static bool isSecure()
 * @method static string getHost()
 * @method static void setMethod(string $method)
 * @method static string getMethod()
 * @method static string getRealMethod()
 * @method static string|null getMimeType(string $format)
 * @method static string[] getMimeTypes(string $format)
 * @method static string|null getFormat(string|null $mimeType)
 * @method static void setFormat(string|null $format, string|string[] $mimeTypes)
 * @method static string|null getRequestFormat(string|null $default = 'html')
 * @method static void setRequestFormat(string|null $format)
 * @method static string|null getContentTypeFormat()
 * @method static void setDefaultLocale(string $locale)
 * @method static string getDefaultLocale()
 * @method static void setLocale(string $locale)
 * @method static string getLocale()
 * @method static bool isMethod(string $method)
 * @method static bool isMethodSafe()
 * @method static bool isMethodIdempotent()
 * @method static bool isMethodCacheable()
 * @method static string|null getProtocolVersion()
 * @method static string|resource getContent(bool $asResource = false)
 * @method static \Symfony\Component\HttpFoundation\InputBag getPayload()
 * @method static array getETags()
 * @method static bool isNoCache()
 * @method static string|null getPreferredFormat(string|null $default = 'html')
 * @method static string|null getPreferredLanguage(string[] $locales = null)
 * @method static string[] getLanguages()
 * @method static string[] getCharsets()
 * @method static string[] getEncodings()
 * @method static string[] getAcceptableContentTypes()
 * @method static bool isXmlHttpRequest()
 * @method static bool preferSafeContent()
 * @method static bool isFromTrustedProxy()
 * @method static array filterPrecognitiveRules(array $rules)
 * @method static bool isAttemptingPrecognition()
 * @method static bool isPrecognitive()
 * @method static bool isJson()
 * @method static bool expectsJson()
 * @method static bool wantsJson()
 * @method static bool accepts(string|array $contentTypes)
 * @method static string|null prefers(string|array $contentTypes)
 * @method static bool acceptsAnyContentType()
 * @method static bool acceptsJson()
 * @method static bool acceptsHtml()
 * @method static bool matchesType(string $actual, string $type)
 * @method static string format(string $default = 'html')
 * @method static string|array|null old(string|null $key = null, \Illuminate\Database\Eloquent\Model|string|array|null $default = null)
 * @method static void flash()
 * @method static void flashOnly(array|mixed $keys)
 * @method static void flashExcept(array|mixed $keys)
 * @method static void flush()
 * @method static string|array|null server(string|null $key = null, string|array|null $default = null)
 * @method static bool hasHeader(string $key)
 * @method static string|array|null header(string|null $key = null, string|array|null $default = null)
 * @method static string|null bearerToken()
 * @method static bool exists(string|array $key)
 * @method static bool has(string|array $key)
 * @method static bool hasAny(string|array $keys)
 * @method static \Magento\Framework\App\RequestInterface|mixed whenHas(string $key, callable $callback, callable|null $default = null)
 * @method static bool filled(string|array $key)
 * @method static bool isNotFilled(string|array $key)
 * @method static bool anyFilled(string|array $keys)
 * @method static \Magento\Framework\App\RequestInterface|mixed whenFilled(string $key, callable $callback, callable|null $default = null)
 * @method static bool missing(string|array $key)
 * @method static \Magento\Framework\App\RequestInterface|mixed whenMissing(string $key, callable $callback, callable|null $default = null)
 * @method static array keys()
 * @method static array all(array|mixed|null $keys = null)
 * @method static mixed input(string|null $key = null, mixed $default = null)
 * @method static \Maginium\Framework\Support\Stringable str(string $key, mixed $default = null)
 * @method static \Maginium\Framework\Support\Stringable string(string $key, mixed $default = null)
 * @method static bool boolean(string|null $key = null, bool $default = false)
 * @method static int integer(string $key, int $default = 0)
 * @method static float float(string $key, float $default = 0)
 * @method static \Maginium\Framework\Support\Carbon|null date(string $key, string|null $format = null, string|null $tz = null)
 * @method static object|null enum(string $key, string $enumClass)
 * @method static \Maginium\Framework\Support\Collection collect(array|string|null $key = null)
 * @method static array only(array|mixed $keys)
 * @method static array except(array|mixed $keys)
 * @method static string|array|null query(string|null $key = null, string|array|null $default = null)
 * @method static string|array|null post(string|null $key = null, string|array|null $default = null)
 * @method static bool hasCookie(string $key)
 * @method static string|array|null cookie(string|null $key = null, string|array|null $default = null)
 * @method static array allFiles()
 * @method static bool hasFile(string $key)
 * @method static \Illuminate\Http\UploadedFile|\Illuminate\Http\UploadedFile[]|array|null file(string|null $key = null, mixed $default = null)
 * @method static \Magento\Framework\App\RequestInterface dump(mixed $keys = [])
 * @method static never dd(mixed ...$args)
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 * @method static array validate(array $rules, ...$params)
 * @method static array validateWithBag(string $errorBag, array $rules, ...$params)
 * @method static bool hasValidSignature(bool $absolute = true)
 *
 * @see RequestInterface
 */
class Request extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string The key to access the service.
     */
    protected static function getAccessor(): string
    {
        return RequestInterface::class;
    }
}
