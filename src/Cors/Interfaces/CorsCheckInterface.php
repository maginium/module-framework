<?php

declare(strict_types=1);

namespace Maginium\Framework\Cors\Interfaces;

/**
 * Interface CorsCheckInterface.
 */
interface CorsCheckInterface
{
    /**
     * Constant for the Access-Control-Allow-Methods header.
     *
     * @var string
     */
    public const HEADER_ALLOW_METHODS = 'Access-Control-Allow-Methods';

    /**
     * Constant for the Access-Control-Allow-Headers header.
     *
     * @var string
     */
    public const HEADER_ALLOW_HEADERS = 'Access-Control-Allow-Headers';

    /**
     * Constant for the Access-Control-Request-Method header.
     *
     * @var string
     */
    public const HEADER_REQUEST_METHOD = 'Access-Control-Request-Method';

    /**
     * Constant for the Access-Control-Request-Headers header.
     *
     * @var string
     */
    public const HEADER_REQUEST_HEADERS = 'Access-Control-Request-Headers';

    /**
     * Checks and sets CORS headers for preflight OPTIONS requests.
     *
     * @return string[] An empty string as the response body is not needed.
     */
    public function check(): array;
}
