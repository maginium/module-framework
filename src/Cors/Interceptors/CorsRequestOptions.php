<?php

declare(strict_types=1);

namespace Maginium\Framework\Cors\Interceptors;

use Magento\Framework\Webapi\Rest\Request as MagentoRequest;
use Maginium\Foundation\Exceptions\InputException;
use Maginium\Framework\Http\Enums\HttpMethod;

/**
 * Class CorsRequestOptions.
 */
class CorsRequestOptions
{
    /**
     * Intercepts the retrieval of the HTTP method.
     *
     * This method is designed to be an around plugin for Magento's Request::getMethod().
     * It checks if the HTTP method of the incoming request is within the allowed methods.
     * If the method is not allowed, an InputException is thrown, preventing further processing.
     *
     * @param MagentoRequest $subject The intercepted object, representing the incoming request.
     *
     * @throws InputException If the HTTP method is not in the list of allowed methods.
     *
     * @return string The HTTP method of the request if it is valid.
     */
    public function aroundGetHttpMethod(MagentoRequest $subject): string
    {
        // Check if the request's HTTP method is allowed
        if (! in_array($subject->getMethod(), HttpMethod::getValues(), true)) {
            // If not allowed, throw an exception with an appropriate message
            InputException::make(__('Invalid HTTP method.'));
        }

        // Return the HTTP method if it is valid
        return $subject->getMethod();
    }
}
