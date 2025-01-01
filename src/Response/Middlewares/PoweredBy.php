<?php

declare(strict_types=1);

namespace Maginium\Framework\Response\Middlewares;

use Maginium\Framework\Support\Facades\Config;
use Maginium\Framework\Support\Str;

/**
 * Class PoweredBy.
 *
 * Middlewares for appending a unique request ID to REST API requests.
 */
class PoweredBy extends AbstractHeaderMiddleware
{
    /**
     * Header name for the X-Powered-By header.
     *
     * @var string
     */
    private const HEADER_NAME = 'x-powered-by';

    /**
     * Retrieves the name of the header to be added.
     *
     * @return string|null The header name.
     */
    protected function getName(): string
    {
        return self::HEADER_NAME;
    }

    /**
     * Retrieves the value of the header to be added.
     *
     * @return string|null The header value.
     */
    protected function getValue(): ?string
    {
        // Retrieve the "AUTHOR" configuration value from the application config
        // This could be used to identify the source or author of the application
        $poweredBy = Str::headline(Config::getString('AUTHOR'));

        // Return the value retrieved from the config as the header value
        return $poweredBy;
    }
}
