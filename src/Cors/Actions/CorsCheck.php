<?php

declare(strict_types=1);

namespace Maginium\Framework\Cors\Actions;

use Maginium\Framework\Actions\Concerns\AsAction;
use Maginium\Framework\Cors\Interfaces\CorsCheckInterface;
use Maginium\Framework\Support\Facades\Request;
use Maginium\Framework\Support\Facades\Response;

/**
 * Class CorsCheck.
 * Handles CORS preflight requests by setting appropriate response headers.
 */
class CorsCheck implements CorsCheckInterface
{
    // Using the AsAction trait to add common action functionality
    use AsAction;

    /**
     * Checks and sets CORS headers for preflight OPTIONS requests.
     *
     * This method retrieves the allowed methods and headers from the incoming request
     * and sets the corresponding CORS headers in the response.
     *
     * @return array An empty string as the response body is not needed for preflight requests.
     */
    public function check(): array
    {
        // Retrieve the allowed method for the preflight request.
        $allowedMethods = $this->getAllowedMethods();

        if ($allowedMethods) {
            // Set the 'Access-Control-Allow-Methods' header with the allowed methods.
            Response::setHeader(self::HEADER_ALLOW_METHODS, $allowedMethods, true);
        }

        // Retrieve the allowed headers for the preflight request.
        $allowedHeaders = $this->getAllowedHeaders();

        if ($allowedHeaders) {
            // Set the 'Access-Control-Allow-Headers' header with the allowed headers.
            Response::setHeader(self::HEADER_ALLOW_HEADERS, $allowedHeaders, true);
        }

        // Return an empty array since no response body is needed for preflight requests.
        return $this->response()->setPayload([])->toArray();
    }

    /**
     * Retrieve the allowed methods from the 'Access-Control-Request-Method' header.
     *
     * This method extracts the allowed methods from the request and returns them.
     * The methods are set in the 'Access-Control-Allow-Methods' response header.
     *
     * @return string|null The allowed methods or null if not provided.
     */
    private function getAllowedMethods(): ?string
    {
        // Retrieve the value of the 'Access-Control-Request-Method' header from the incoming request.
        return Request::header(self::HEADER_REQUEST_METHOD) ?? null;
    }

    /**
     * Retrieve the allowed headers from the 'Access-Control-Request-Headers' header.
     *
     * This method extracts the allowed headers from the request and returns them.
     * The headers are set in the 'Access-Control-Allow-Headers' response header.
     *
     * @return string|null The allowed headers or null if not provided.
     */
    private function getAllowedHeaders(): ?string
    {
        // Retrieve the value of the 'Access-Control-Request-Headers' header from the incoming request.
        return Request::header(self::HEADER_REQUEST_HEADERS) ?? null;
    }
}
