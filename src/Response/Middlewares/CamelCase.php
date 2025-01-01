<?php

declare(strict_types=1);

namespace Maginium\Framework\Response\Middlewares;

use Maginium\Foundation\Abstracts\AbstractMiddleware;
use Maginium\Framework\Response\Interfaces\ResponseInterface;
use Maginium\Framework\Support\CaseConverter;
use Maginium\Framework\Support\Facades\Json;
use Maginium\Framework\Support\Validator;

/**
 * Middleware class for converting response data keys to camelCase format.
 *
 * This middleware listens to the outgoing HTTP response and checks if the response content
 * is valid JSON. If so, it converts the keys of the decoded data to camelCase using a provided
 * case converter service before returning the response.
 */
class CamelCase extends AbstractMiddleware
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
     * Perform optional post-dispatch logic.
     *
     * @param ResponseInterface $response The processed response.
     */
    protected function after($response): void
    {
        // Get the content of the response
        $content = $response->getBody();

        // Ensure the content is a valid, non-empty string
        if (Validator::isEmpty($content) || ! Validator::isString($content) || Validator::isXML($content)) {
            // Early exit if content is empty or not a string
            return;
        }

        // Attempt to decode the JSON content
        $decodedContent = Json::decode($content);

        // Check if the decoded content is an array (valid structure for key conversion)
        if (! Validator::isArray($decodedContent)) {
            // If the content is not an array, log the issue or handle appropriately
            // In this case, we simply return the original response without modification
            return;
        }

        // Convert all keys in the array to camelCase using the CaseConverter
        $convertedData = $this->keyCaseConverter->convert(
            CaseConverter::CASE_CAMEL, // The target case format
            $decodedContent, // The data to be converted
        );

        // Convert data to JSON
        $convertedData = Json::encode($convertedData);

        // Set the converted data as the new content of the response
        $response->setBody($convertedData);
    }
}
