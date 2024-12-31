<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Maginium\Framework\Http\Interfaces\HttpInterface;
use Maginium\Framework\Http\Interfaces\HttpServiceInterface;
use Maginium\Framework\Support\Facade;

/**
 * Facade for interacting with the Http service.
 *
 * This class acts as a simplified interface to access the HttpServiceInterface.
 * By extending AbstractFacade, it inherits basic functionality for service access.
 *
 *
 * @method static mixed get(string $url, array $headers = [], array $options = [])
 *     Perform an HTTP GET request.
 *     Parameters:
 *     - $url: The URL to make the GET request to.
 *     - $headers: Optional headers to include in the request.
 *     - $options: Optional additional options for the request.
 *     Returns:
 *     - mixed: Response data from the GET request.
 * @method static mixed post(string $url, $data = [], array $headers = [], array $options = [])
 *     Perform an HTTP POST request.
 *     Parameters:
 *     - $url: The URL to make the POST request to.
 *     - $data: Optional data to send with the POST request.
 *     - $headers: Optional headers to include in the request.
 *     - $options: Optional additional options for the request.
 *     Returns:
 *     - mixed: Response data from the POST request.
 * @method static mixed put(string $url, $data = [], array $headers = [], array $options = [])
 *     Perform an HTTP PUT request.
 *     Parameters:
 *     - $url: The URL to make the PUT request to.
 *     - $data: Optional data to send with the PUT request.
 *     - $headers: Optional headers to include in the request.
 *     - $options: Optional additional options for the request.
 *     Returns:
 *     - mixed: Response data from the PUT request.
 * @method static mixed delete(string $url, array $headers = [], array $options = [])
 *     Perform an HTTP DELETE request.
 *     Parameters:
 *     - $url: The URL to make the DELETE request to.
 *     - $headers: Optional headers to include in the request.
 *     - $options: Optional additional options for the request.
 *     Returns:
 *     - mixed: Response data from the DELETE request.
 * @method static mixed makeRequest(string $url, string $method, $data = [], array $headers = [], array $options = [])
 *     Make a custom HTTP request.
 *     Parameters:
 *     - $url: The URL to make the request to.
 *     - $method: The HTTP method (GET, POST, PUT, DELETE, etc.).
 *     - $data: Optional data to send with the request.
 *     - $headers: Optional headers to include in the request.
 *     - $options: Optional additional options for the request.
 *     Returns:
 *     - mixed: Response data from the custom HTTP request.
 *
 * @see HttpServiceInterface
 */
class Http extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string The key to access the service.
     */
    protected static function getAccessor(): string
    {
        return HttpInterface::class;
    }
}
