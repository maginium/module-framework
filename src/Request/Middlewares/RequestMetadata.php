<?php

declare(strict_types=1);

namespace Maginium\Framework\Request\Middlewares;

use Laminas\Http\Headers;
use Maginium\Foundation\Abstracts\Middleware\AbstractMiddleware;
use Maginium\Framework\Request\Interfaces\RequestInterface;
use Maginium\Framework\Support\Facades\Log;

/**
 * Middleware class for handling and logging detailed request metadata.
 * This includes details such as HTTP method, URL, headers, cookies, request body, and client IP address.
 * It also logs the response time to track the performance of the request handling.
 */
class RequestMetadata extends AbstractMiddleware
{
    /**
     * Perform pre-dispatch logic to collect and log request metadata.
     * This method is called before the actual request is dispatched.
     * It tracks the start time, collects request details, calculates response time,
     * and logs the metadata.
     *
     * @param RequestInterface $request The incoming HTTP request.
     */
    protected function before($request): void
    {
        // Start tracking the request processing time using microtime to get a high-resolution timestamp.
        $startTime = microtime(true);

        // Collect all relevant request details such as method, URL, query parameters, headers, IP, cookies, and body.
        $requestDetails = $this->collectRequestDetails($request);

        // Calculate the response time by subtracting the start time from the current time.
        $responseTime = microtime(true) - $startTime;

        // Log the request metadata along with the calculated response time.
        // The log provides valuable insights for debugging and performance monitoring.
        $this->logRequestMetadata($requestDetails, $responseTime);
    }

    /**
     * Collect and return the essential request details such as HTTP method, URL, query parameters, headers,
     * IP address, cookies, and raw body.
     *
     * @param RequestInterface $request The incoming HTTP request.
     *
     * @return array An associative array containing request details like method, URL, query params, etc.
     */
    private function collectRequestDetails(RequestInterface $request): array
    {
        // Return the collected request details as an associative array.
        return [
            'url' => $request->url(), // Full URL of the request.
            'ipAddress' => $request->ip(), // Client IP address (useful for security, analytics).
            'method' => $request->method(), // HTTP method (GET, POST, etc.)
            'cookies' => $request->cookies(), // Cookies sent by the client.
            'body' => $request->getRawBody(), // The raw body content of the request (if any).
            'headers' => $request->headers(), // Headers formatted as key-value pairs.
            'queryParams' => $request->query(), // Query parameters (e.g., ?id=123&category=books)
        ];
    }

    /**
     * Log the collected request metadata along with the calculated response time.
     * This method uses the Log facade to log the information in a structured way,
     * making it easier to track the request's lifecycle and performance.
     *
     * @param array $requestDetails The details of the request to be logged.
     * @param float $responseTime The time taken to process the request (in seconds).
     */
    private function logRequestMetadata(array $requestDetails, float $responseTime): void
    {
        // Use the Log facade to log the request details and response time as contextual information.
        // This will allow us to trace performance and request details easily in the logs.
        Log::withContext([
            'technical-metadata' => [
                'request-details' => $requestDetails, // Includes method, URL, headers, cookies, etc.
                'response-time' => number_format($responseTime, 4), // Format response time to 4 decimal places for readability.
            ],
        ]);
    }
}
