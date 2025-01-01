<?php

declare(strict_types=1);

namespace Maginium\Framework\Request\Middlewares;

use Maginium\Framework\Support\Facades\Log;
use Maginium\Framework\Support\Facades\Request;
use Maginium\Framework\Support\Validator;

/**
 * Class StoreId.
 *
 * Middleware for appending the current store to REST API requests.
 */
class StoreId extends AbstractHeaderMiddleware
{
    /**
     * Header name for the X-Store header.
     *
     * @var string
     */
    private const HEADER_NAME = 'x-store-id';

    /**
     * StoreId constructor.
     * The store manager instance.
     */
    public function __construct()
    {
        // Set Logger class name for logging purposes
        Log::setClassName(static::class);
    }

    /**
     * Retrieves the name of the header to be added.
     *
     * @return string The header name.
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
        // Attempt to retrieve the store id from the primary header
        $storeId = Request::header(self::HEADER_NAME);

        // If the header is not empty, return the store id
        if (! Validator::isEmpty($storeId)) {
            return $storeId;
        }

        // Use the getStore method to get the store
        $store = $this->getStore();

        // Retrieve the default store id for the determined store
        $storeId = $store->getId();

        // Add store information to the log context for debugging and tracing purposes
        Log::withContext(['store-info' => ['store-id' => (int)$storeId]]);

        // Return the determined store id to be added as a header value
        return (string)$storeId;
    }
}
