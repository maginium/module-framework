<?php

declare(strict_types=1);

namespace Maginium\Framework\Url;

use Magento\Backend\Model\UrlInterface as BackendUrl;
use Magento\Backend\Setup\ConfigOptionsList;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface as AppRequestInterface;
use Magento\Framework\App\Route\ConfigInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Session\Generic;
use Magento\Framework\Session\SidResolverInterface;
use Magento\Framework\Url;
use Magento\Framework\Url\HostChecker;
use Magento\Framework\Url\QueryParamsResolverInterface;
use Magento\Framework\Url\RouteParamsPreprocessorInterface;
use Magento\Framework\Url\RouteParamsResolver;
use Magento\Framework\Url\RouteParamsResolverFactory;
use Magento\Framework\Url\ScopeResolverInterface;
use Magento\Framework\Url\SecurityInfoInterface;
use Maginium\Framework\Request\Interfaces\RequestInterface;
use Maginium\Framework\Support\Facades\Config;
use Maginium\Framework\Support\Path;
use Maginium\Framework\Url\Interfaces\UrlInterface;
use Override;

/**
 * UrlManager.
 *
 * This class is responsible for managing and constructing URLs within the application. It provides
 * methods to build absolute and relative URLs, handle URL components like query parameters and fragments,
 * and resolve the base URL considering various factors like secure connection (HTTPS), scope, and route type.
 *
 * **Properties:**
 * - `request`: The HTTP request instance that holds the incoming request data.
 * - `relative_url`: Boolean indicating if the URL is relative (`true`) or absolute (`false`).
 * - `type`: Specifies the URL type, can be 'link', 'skin', 'js', or 'media'.
 * - `scope`: An instance of `\Magento\Framework\Url\ScopeInterface` that determines the scope of the URL.
 * - `secure`: Boolean flag indicating whether the URL is secure (uses HTTPS).
 *
 * **Components of a URL:**
 * - `scheme`: Defines the URL scheme such as 'http' or 'https'.
 * - `user`: The username in the URL (optional).
 * - `password`: The password associated with the username (optional).
 * - `host`: The domain or host of the URL (e.g., 'localhost').
 * - `port`: The port number, commonly 80 for HTTP and 443 for HTTPS.
 * - `base_path`: The base path of the application (e.g., '/dev/magento/').
 * - `base_script`: The script that serves as the entry point (e.g., 'index.php').
 * - `scopeview_path`: Path that is specific to the scope (optional, e.g., 'scopeview/').
 * - `route_path`: The URL path that includes the route name, controller, action, and parameters.
 * - `route_name`: The name of the route (e.g., 'module').
 * - `controller_name`: The name of the controller (e.g., 'controller').
 * - `action_name`: The action name within the controller (e.g., 'action').
 * - `route_params`: Associative array containing route parameters (e.g., `array('param1' => 'value1')`).
 * - `query`: The query string in the URL (e.g., `'param1=value1&param2=value2'`).
 * - `query_array`: The array representation of the query string (e.g., `array('param1' => 'value1', 'param2' => 'value2')`).
 * - `fragment`: The fragment identifier (e.g., `#fragment-anchor`).
 *
 * **URL Structure Breakdown:**
 *
 * https://user:password@host:443/base_path/[base_script][scopeview_path]route_name/controller_name/action_name/param1/value1?query_param=query_value#fragment
 *
 * Where:
 * - **A**: Authority (`user:password@host:443`) — Credentials, domain, and port.
 * - **B**: Path (`/base_path/[base_script][scopeview_path]route_name/controller_name/action_name/param1/value1`) — The full path to the resource.
 * - **C**: Absolute base URL (`scheme://host:port`) — The base URL including scheme, host, and port.
 * - **D**: Action path (`/controller_name/action_name/param1/value1`) — The specific action path within the route.
 * - **E**: Route parameters (`param1=value1`) — Parameters associated with the route.
 * - **F**: Host URL (`scheme://host`) — The scheme and host.
 * - **G**: Route path (`/module/controller/action/param1/value1`) — The path for the route.
 * - **H**: Full route URL (`route_url`) — The complete URL assembled from all components.
 *
 * @codingStandardsIgnoreStart
 * URL structure diagram:
 * https://user:password@host:443/base_path/[base_script][scopeview_path]route_name/controller_name/action_name/param1/value1?query_param=query_value#fragment
 *       \__________A___________/\____________________________________B_____________________________________/
 * \__________________C___________________/              \__________________D_________________/ \_____E_____/
 * \_____________F______________/                        \___________________________G______________________/
 * \___________________________________________________H____________________________________________________/
 *
 * @codingStandardsIgnoreEnd
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class UrlManager extends Url implements UrlInterface
{
    /**
     * @var BackendUrl
     */
    private BackendUrl $backendUrl;

    /**
     * Constructor for managing backend URL routing and session handling.
     *
     * @param Generic $session Session management object for backend sessions.
     * @param Json $serializer [Optional] JSON serializer for additional encoding/decoding tasks.
     * @param string $scopeType The scope type for the current context.
     * @param BackendUrl $backendUrl Service for managing backend URLs.
     * @param HostChecker $hostChecker [Optional] Service for checking host validity.
     * @param AppRequestInterface $request HTTP request object.
     * @param ConfigInterface $routeConfig Configuration for routing.
     * @param SidResolverInterface $sidResolver Resolver for session ID in URLs.
     * @param ScopeConfigInterface $scopeConfig Configuration for scopes.
     * @param ScopeResolverInterface $scopeResolver Resolver for scope management.
     * @param SecurityInfoInterface $urlSecurityInfo Security-related URL information.
     * @param QueryParamsResolverInterface $queryParamsResolver Resolver for HTTP query parameters.
     * @param RouteParamsResolverFactory $routeParamsResolverFactory Factory for creating route parameters resolvers.
     * @param RouteParamsPreprocessorInterface $routeParamsPreprocessor Preprocessor for resolving route parameters.
     * @param array $data [Optional] Additional data for the parent constructor.
     */
    public function __construct(
        Generic $session,
        Json $serializer,
        string $scopeType,
        BackendUrl $backendUrl,
        HostChecker $hostChecker,
        AppRequestInterface $request,
        ConfigInterface $routeConfig,
        SidResolverInterface $sidResolver,
        ScopeConfigInterface $scopeConfig,
        ScopeResolverInterface $scopeResolver,
        SecurityInfoInterface $urlSecurityInfo,
        QueryParamsResolverInterface $queryParamsResolver,
        RouteParamsResolverFactory $routeParamsResolverFactory,
        RouteParamsPreprocessorInterface $routeParamsPreprocessor,

        // Optional DI
        array $data = [],
    ) {
        // Call parent constructor with the provided arguments
        parent::__construct(
            $routeConfig,
            $request,
            $urlSecurityInfo,
            $scopeResolver,
            $session,
            $sidResolver,
            $routeParamsResolverFactory,
            $queryParamsResolver,
            $scopeConfig,
            $routeParamsPreprocessor,
            $scopeType,
            $data,
            $hostChecker,
            $serializer,
        );

        // Assign backend URL management service
        $this->backendUrl = $backendUrl;
    }

    /**
     * Retrieve the base URL considering various parameters.
     *
     * This method constructs the base URL based on parameters such as scope, type, and secure flag.
     * It resolves route-specific conditions, handles frontend routes, and applies PWA configurations if needed.
     *
     * @param array $params An array of parameters that might include:
     * - `_scope`: (string|null) The scope of the URL (e.g., 'default', 'store', etc.).
     * - `_type`: (string|null) The type of the URL (e.g., 'link', 'skin', or 'direct_link').
     * - `_secure`: (bool|null) Indicates whether the URL should be secure (HTTPS).
     *
     * @return string The constructed base URL.
     */
    #[Override]
    public function getBaseUrl($params = []): string
    {
        // Step 1: Save the original scope to restore it later after processing
        $origScope = $this->_getScope();

        // Step 2: Initialize the route parameters resolver to handle type and secure attributes
        /** @var RouteParamsResolver $routeParamsResolver */
        $routeParamsResolver = $this->getRouteParamsResolver();

        // Step 3: Fetch the current request object to evaluate route-specific conditions
        /** @var RequestInterface $request */
        $request = $this->_getRequest();

        // Step 4: Process incoming parameters to adjust the scope, type, and secure settings
        // Set the scope of the URL, if provided
        if (isset($params['_scope'])) {
            $this->setScope($params['_scope']);
        }

        // Set the URL type (e.g., 'link', 'skin'), if provided
        if (isset($params['_type'])) {
            $routeParamsResolver->setType($params['_type']);
        }

        // Set whether the URL should be secure (HTTPS), if provided
        if (isset($params['_secure'])) {
            $routeParamsResolver->setSecure($params['_secure']);
        }

        // Step 5: Handle frontend routes when the type is 'link'
        // This ensures proper routing when directly accessing frontend pages
        if (
            $this->_getType() === UrlInterface::URL_TYPE_LINK
            && $request->isDirectAccessFrontendName($this->_getRouteFrontName())
        ) {
            $routeParamsResolver->setType(UrlInterface::URL_TYPE_DIRECT_LINK);
        }

        // Step 6: Determine the base URL
        // Check for PWA-specific URL configuration; fallback to default web configuration
        $result = $this->getConfigData(self::PWA_URL) // PWA-specific URL if configured
            ?? $this->getConfigData(self::WEB_URL);  // Default web URL as a fallback

        // Step 7: Restore the original scope to prevent side effects from parameter overrides
        $this->setScope($origScope);

        // Step 8: Reset the route type to its default value to maintain consistency
        $routeParamsResolver->setType(UrlInterface::DEFAULT_URL_TYPE);

        // Return the constructed base URL
        return $result;
    }

    /**
     * Generate the backend URL for a given route.
     *
     * Constructs the full backend URL using the provided route and appends
     * the configured backend front name for proper routing.
     *
     * @param string|null $route The route to generate the URL for. Defaults to an empty string.
     *
     * @return string The constructed backend URL.
     */
    public function getBackendUrl(?string $route = ''): string
    {
        // Fetch the backend front name from the configuration
        $backendFrontName = Config::getString(ConfigOptionsList::CONFIG_PATH_BACKEND_FRONTNAME);

        // Build and return the complete backend URL by joining components
        return Path::join(
            $this->backendUrl->getRouteUrl($route),
            $backendFrontName,
            $route,
        );
    }
}
