<?php

declare(strict_types=1);

namespace Maginium\Framework\Response\Middlewares;

use Maginium\Foundation\Abstracts\Middleware\AbstractMiddleware;
use Maginium\Framework\Response\Interfaces\ResponseInterface;
use Maginium\Framework\Support\Facades\Json;
use Maginium\Framework\Support\Validator;

/**
 * Custom middleware that adds headers and logs requests.
 */
class TransformResponse extends AbstractMiddleware
{
    /**
     * Perform optional post-dispatch logic.
     *
     * @param ResponseInterface $response The processed response.
     */
    protected function after($response): void
    {
        // Retrieve the content of the response body
        $content = $response->getBody();

        // Ensure the content is a valid, non-empty string
        if (Validator::isEmpty($content) || ! Validator::isString($content) || Validator::isXML($content)) {
            // Early exit if content is empty or not a string
            return;
        }

        // Attempt to decode the JSON content
        $decodedContent = Json::decode($content);

        // Validate that the decoded content is an array and contains elements
        if (Validator::isEmpty($decodedContent) || ! Validator::isArray($decodedContent)) {
            // Early exit if decoding fails or the content is empty
            return;
        }

        // Extract the first element from the decoded content
        $firstElement = $decodedContent[0] ?? null;

        if ($firstElement !== null) {
            // Re-encode the first element and update the response body
            $encodedContent = Json::encode($firstElement);

            // Set the updated body content back to the response
            $response->setBody($encodedContent);
        }
    }
}
