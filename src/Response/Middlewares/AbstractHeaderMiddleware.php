<?php

declare(strict_types=1);

namespace Maginium\Framework\Response\Middlewares;

use Magento\Store\Api\Data\StoreInterface;
use Magento\Webapi\Controller\Rest;
use Maginium\Foundation\Abstracts\AbstractMiddleware;
use Maginium\Foundation\Exceptions\NotFoundException;
use Maginium\Framework\Response\Interfaces\ResponseInterface;
use Maginium\Framework\Support\Facades\Config;
use Maginium\Framework\Support\Facades\Log;
use Maginium\Framework\Support\Facades\Request as RequestFacade;
use Maginium\Framework\Support\Facades\StoreManager;
use Maginium\Framework\Support\Php;
use Maginium\Framework\Support\Str;
use Maginium\Framework\Support\Validator;

/**
 * Custom middleware that adds headers and logs requests.
 */
abstract class AbstractHeaderMiddleware extends AbstractMiddleware
{
    /**
     * Perform optional post-dispatch logic.
     *
     * @param ResponseInterface $response The processed response.
     */
    protected function after($response): void
    {
        // Get header name
        $name = Str::studly($this->getName(), true);

        // Get header value
        $value = $this->getValue();

        // Modify the response by adding a custom header
        $response->setHeader($name, $value, true);
    }

    /**
     * Retrieves the name of the header to be added.
     *
     * @return string|null The header name.
     */
    abstract protected function getName(): ?string;

    /**
     * Retrieves the value of the header to be added.
     *
     * @return mixed The header value.
     */
    abstract protected function getValue(): mixed;

    /**
     * Retrieves the store based on the request path.
     *
     * @return StoreInterface The store instance.
     */
    protected function getStore(): StoreInterface
    {
        // Retrieve the store code from the REST URL path
        $urlPath = RequestFacade::getPathInfo();

        // Split the URL path by directory separator
        $urlParts = Php::explode(SP, trim($urlPath, SP));

        $apiVersions = Config::getArray('API_VERSIONS');

        // Determine the store based on the URL parts
        if (! Validator::isEmpty($urlParts[1])) {
            $storeCode = Str::lower($urlParts[1]);

            // Check if the path after '/rest' is 'api', indicating no specific store code
            if ($storeCode !== 'api' || ! Php::inArray($storeCode, $apiVersions)) {
                return StoreManager::getDefaultStoreView();
            }

            try {
                // Attempt to retrieve the store based on the provided store code
                return StoreManager::getStore($storeCode);
            } catch (NotFoundException $e) {
                // Log a warning if the provided store code is invalid
                Log::warning("Invalid store code provided: {$storeCode}. Using default store.");

                return StoreManager::getDefaultStoreView();
            }
        } else {
            // If no specific store code is provided, use the default store view
            return StoreManager::getDefaultStoreView();
        }
    }
}
