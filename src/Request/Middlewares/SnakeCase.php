<?php

declare(strict_types=1);

namespace Maginium\Framework\Request\Middlewares;

use Maginium\Foundation\Abstracts\AbstractMiddleware;
use Maginium\Framework\Request\Interfaces\RequestInterface;
use Maginium\Framework\Support\CaseConverter;
use Maginium\Framework\Support\Validator;

/**
 * Middleware class for converting the keys of the request data to snake_case.
 *
 * This middleware listens to the incoming HTTP requests and checks if the request content
 * is a valid JSON string. If so, it converts the keys of the decoded data to snake_case
 * using a provided case converter service and sets the converted data back as the request's content.
 */
class SnakeCase extends AbstractMiddleware
{
    /**
     * @var CaseConverter The service responsible for converting the case of keys.
     */
    private CaseConverter $keyCaseConverter;

    /**
     * Constructor to initialize dependencies.
     *
     * @param CaseConverter $keyCaseConverter An instance of the CaseConverter service.
     * This service is responsible for converting data keys between different cases.
     */
    public function __construct(CaseConverter $keyCaseConverter)
    {
        // Store the CaseConverter instance for later use
        $this->keyCaseConverter = $keyCaseConverter;
    }

    /**
     * Perform optional pre-dispatch logic.
     *
     * @param RequestInterface $request The incoming HTTP request.
     */
    protected function before($request): void
    {
        // Get the content of the incoming request (it might be JSON or other types)
        $content = $request->getBody();

        // Check if the decoded content is an array (valid structure for key conversion)
        if (! Validator::isArray($content)) {
            // If the content is not an array, log the issue or handle appropriately
            // In this case, we simply return the original response without modification
            return;
        }

        // Convert all keys in the array to snake_case using the CaseConverter
        $convertedData = $this->keyCaseConverter->convert(
            CaseConverter::CASE_SNAKE, // The target case format
            $content, // The data to be converted
        );

        // Set the converted data as the new content of the request
        $request->setBody($convertedData);
    }
}
