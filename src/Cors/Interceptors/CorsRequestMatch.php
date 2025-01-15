<?php

declare(strict_types=1);

namespace Maginium\Framework\Cors\Interceptors;

use Closure;
use Magento\Framework\Controller\Router\Route\Factory;
use Magento\Framework\Webapi\Exception;
use Magento\Framework\Webapi\Rest\Request as MagentoRequest;
use Magento\Webapi\Controller\Rest\Router;
use Magento\Webapi\Controller\Rest\Router\Route;
use Maginium\Foundation\Enums\HttpMethods;
use Maginium\Framework\Cors\Interfaces\CorsCheckInterface;

/**
 * Class CorsRequestMatch.
 *
 * Plugin to handle CORS requests in Magento's REST API routing.
 */
class CorsRequestMatch
{
    /**
     * @var Factory
     */
    protected Factory $routeFactory;

    /**
     * CorsRequestMatch constructor.
     *
     * @param Factory $routeFactory The factory to create route instances.
     */
    public function __construct(
        Factory $routeFactory,
    ) {
        $this->routeFactory = $routeFactory;
    }

    /**
     * Around plugin to handle CORS OPTIONS requests.
     *
     * This method intercepts the matching process for CORS preflight OPTIONS requests
     * and processes them separately by creating a CORS-specific route.
     * For other HTTP methods, it proceeds with the normal routing process.
     *
     * @param Router $subject Magento's REST API router instance.
     * @param callable $proceed The next callable in the chain.
     * @param MagentoRequest $request The incoming request object.
     *
     * @throws Exception If an error occurs during routing.
     *
     * @return Route The matched route instance, or a CORS-specific route for OPTIONS requests.
     */
    public function aroundMatch(
        Router $subject,
        Closure $next,
        MagentoRequest $request,
    ): Route {
        // Try to match the route normally
        try {
            $route = $next($request);
        } catch (Exception $e) {
            // Handle OPTIONS request separately for CORS preflight
            if ($request->getHttpMethod() === HttpMethods::OPTIONS) {
                return $this->createCorsRoute();
            }

            // Re-throw the exception for other HTTP methods
            throw $e;
        }

        // Return the matched route instance if no exception was thrown
        return $route;
    }

    /**
     * Creates a route object for the CORS check endpoint.
     *
     * This method creates a specific route for handling CORS checks by defining
     * the endpoint path, service class, method, and ACL resources.
     *
     * @return Route The created route instance for the CORS check endpoint.
     */
    protected function createCorsRoute(): Route
    {
        // Create and configure the CORS-specific route
        /** @var Route $route */
        $route = $this->routeFactory->createRoute(
            Route::class, // Route class name
            '/V1/cors/check', // Endpoint path for the CORS check route
        );

        // Set properties for the CORS route
        $route->setServiceClass(CorsCheckInterface::class)  // The service class for handling the CORS check
            ->setServiceMethod('check')  // The method within the service class to call
            ->setSecure(false)  // Whether the route requires HTTPS (false for preflight OPTIONS requests)
            ->setAclResources(['anonymous'])  // The ACL resources associated with the route
            ->setParameters([]);  // Additional parameters for the route (empty for this case)

        // Return the created route instance
        return $route;
    }
}
