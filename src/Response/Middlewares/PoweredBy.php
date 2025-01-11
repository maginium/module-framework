<?php

declare(strict_types=1);

namespace Maginium\Framework\Response\Middlewares;

use Maginium\Foundation\Abstracts\Middleware\AbstractHeaderMiddleware;
use Maginium\Framework\Config\Enums\ConfigDrivers;
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
     * Gets the value of the "Powered-By" header to be added.
     *
     * @return string|null The formatted header value or null if not set.
     */
    protected function getValue(): ?string
    {
        // Get the "AUTHOR" value from the environment configuration.
        // This value may represent the application author or source.
        $author = Config::driver(ConfigDrivers::ENV)->getString(path: 'AUTHOR');

        // Format the author value into a headline-style string.
        $poweredBy = Str::headline($author);

        // Return the formatted value as the header value.
        return $poweredBy;
    }
}
