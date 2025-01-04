<?php

declare(strict_types=1);

namespace Maginium\Framework\Cors\Interceptors;

use Magento\Framework\App\FrontControllerInterface;
use Maginium\Framework\Cors\Helpers\Data as CorsHelper;
use Maginium\Framework\Request\Interfaces\RequestInterface;
use Maginium\Framework\Support\Facades\Response;
use Maginium\Framework\Support\Str;

/**
 * Class CorsHeaders.
 */
class CorsHeaders
{
    /**
     * The Access-Control-Max-Age header name.
     */
    private const HEADER_MAX_AGE = 'Access-Control-Max-Age';

    /**
     * The Access-Control-Allow-Origin header name.
     */
    private const HEADER_ALLOW_ORIGIN = 'Access-Control-Allow-Origin';

    /**
     * The Access-Control-Allow-Credentials header name.
     */
    private const HEADER_ALLOW_CREDENTIALS = 'Access-Control-Allow-Credentials';

    /**
     * The AMP-Access-Control-Allow-Source-Origin header name.
     */
    private const HEADER_AMP_ALLOW_SOURCE_ORIGIN = 'AMP-Access-Control-Allow-Source-Origin';

    /**
     * Intercepts the dispatch of a request to set CORS headers.
     *
     * @param FrontControllerInterface $subject The front controller that handles request dispatching.
     * @param RequestInterface $request The request object being dispatched.
     */
    public function beforeDispatch(
        FrontControllerInterface $subject,
        RequestInterface $request,
    ): void {
        // Retrieve the origin URL from the helper
        if ($originUrl = CorsHelper::getOriginUrl()) {
            // Set CORS-related headers
            $this->setAccessControlAllowOriginHeader($originUrl);
            $this->setAccessControlAllowCredentialsHeader();
            $this->setAmpAccessControlAllowSourceOriginHeader($originUrl);
            $this->setAccessControlMaxAgeHeader();
        }
    }

    /**
     * Sets the Access-Control-Allow-Origin header.
     *
     * @param string $originUrl The origin URL to be allowed for CORS requests.
     */
    private function setAccessControlAllowOriginHeader(string $originUrl): void
    {
        // Remove trailing slash from origin URL and set the Access-Control-Allow-Origin header
        Response::setHeader(self::HEADER_ALLOW_ORIGIN, Str::rtrim($originUrl, '/'), true);
    }

    /**
     * Sets the Access-Control-Allow-Credentials header if credentials are allowed.
     */
    private function setAccessControlAllowCredentialsHeader(): void
    {
        // Check if credentials are allowed and set the Access-Control-Allow-Credentials header accordingly
        if (CorsHelper::getAllowCredentials()) {
            Response::setHeader(self::HEADER_ALLOW_CREDENTIALS, 'true', true);
        }
    }

    /**
     * Sets the AMP-Access-Control-Allow-Source-Origin header if AMP support is enabled.
     *
     * @param string $originUrl The origin URL to be allowed for AMP CORS requests.
     */
    private function setAmpAccessControlAllowSourceOriginHeader(string $originUrl): void
    {
        // Check if AMP support is enabled and set the AMP-Access-Control-Allow-Source-Origin header accordingly
        if (CorsHelper::getEnableAmp()) {
            Response::setHeader(self::HEADER_AMP_ALLOW_SOURCE_ORIGIN, Str::rtrim($originUrl, '/'), true);
        }
    }

    /**
     * Sets the Access-Control-Max-Age header if a max age is configured.
     */
    private function setAccessControlMaxAgeHeader(): void
    {
        // Retrieve the max age for CORS requests from the helper and set the Access-Control-Max-Age header
        $maxAge = (int)CorsHelper::getMaxAge();

        // Only set the header if max age is greater than 0
        if ($maxAge > 0) {
            Response::setHeader(self::HEADER_MAX_AGE, (string)$maxAge, true);
        }
    }
}
