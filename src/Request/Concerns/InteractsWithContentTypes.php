<?php

declare(strict_types=1);

namespace Maginium\Framework\Request\Concerns;

use Maginium\Framework\Support\Str;
use Maginium\Framework\Support\Validator;

/**
 * Trait InteractsWithContentTypes.
 *
 * Provides functionality for handling content types in requests and responses.
 * This includes methods for checking if the request is sending/accepting JSON,
 * negotiating content types, and determining the format of the response.
 */
trait InteractsWithContentTypes
{
    /**
     * Determines if the given content types match.
     *
     * This method checks whether the actual content type matches the specified type.
     * It supports matching content types with suffixes, e.g., "application/json" and "application/*+json".
     *
     * @param string $actual The actual content type (e.g., "application/json").
     * @param string $type The content type to compare against (e.g., "application/*+json").
     *
     * @return bool True if the content types match, false otherwise.
     */
    public static function matchesType(string $actual, string $type): bool
    {
        // If the content types are exactly the same, return true.
        if ($actual === $type) {
            return true;
        }

        // Split the actual content type into primary type and sub-type.
        $split = explode('/', $actual);

        // Check if the content type has both type and sub-type parts, then match using regex for suffix.
        return isset($split[1]) && preg_match('#' . preg_quote($split[0], '#') . '/.+\+' . preg_quote($split[1], '#') . '#', $type);
    }

    /**
     * Determines if the request is sending JSON.
     *
     * This method checks the `Content-Type` header of the request to see if it indicates JSON content.
     * It supports both exact matches and types with suffixes (e.g., "application/json" or "application/*+json").
     *
     * @return bool True if the request is sending JSON, false otherwise.
     */
    public function isJson(): bool
    {
        // Check if the "Content-Type" header contains "/json" or "+json".
        return Str::contains($this->header('CONTENT_TYPE') ?? '', ['/json', '+json']);
    }

    /**
     * Determines if the current request probably expects a JSON response.
     *
     * This method checks if the request can accept JSON responses. It considers both the `Accept` header
     * and whether the client explicitly requests JSON.
     *
     * @return bool True if the request expects a JSON response, false otherwise.
     */
    public function expectsJson(): bool
    {
        // Checks if the request accepts any content type or specifically requests JSON.
        return $this->acceptsAnyContentType() || $this->wantsJson();
    }

    /**
     * Determines if the current request is asking for JSON.
     *
     * This method inspects the `Accept` header to determine if the client is asking for a JSON response.
     *
     * @return bool True if the request is asking for JSON, false otherwise.
     */
    public function wantsJson(): bool
    {
        // Retrieve the list of acceptable content types from the request.
        $acceptable = $this->getAcceptableContentTypes();

        // Check if the first acceptable content type indicates JSON.
        return isset($acceptable[0]) && Str::contains(mb_strtolower($acceptable[0]), ['/json', '+json']);
    }

    /**
     * Determines whether the current request accepts a given content type.
     *
     * This method checks if the `Accept` header of the request includes the specified content type(s).
     * It supports both single and multiple content types.
     *
     * @param string|array $contentTypes The content type(s) to check (e.g., "application/json" or ["application/json", "text/html"]).
     *
     * @return bool True if the request accepts the given content type(s), false otherwise.
     */
    public function accepts($contentTypes): bool
    {
        // Get the list of acceptable content types from the request.
        $accepts = $this->getAcceptableContentTypes();

        // If no acceptable types are specified, assume all types are accepted.
        if (Validator::isEmpty($accepts)) {
            return true;
        }

        // Convert the content types to an array if it's a single string.
        $types = (array)$contentTypes;

        // Check each acceptable content type against the provided types.
        foreach ($accepts as $accept) {
            if ($accept === '*/*' || $accept === '*') {
                return true;
            }

            foreach ($types as $type) {
                // Normalize both the accept and type to lowercase.
                $accept = mb_strtolower($accept);
                $type = mb_strtolower($type);

                // Match the types or check if a wildcard match is possible.
                if ($this->matchesType($accept, $type) || $accept === strtok($type, '/') . '/*') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Returns the most suitable content type from the given array based on content negotiation.
     *
     * This method checks the `Accept` header and matches it against the provided content types,
     * returning the most preferred match.
     *
     * @param string|array $contentTypes The content type(s) to negotiate (e.g., "application/json" or ["application/json", "text/html"]).
     *
     * @return string|null The preferred content type, or null if no suitable match is found.
     */
    public function prefers($contentTypes): mixed
    {
        // Get the list of acceptable content types from the request.
        $accepts = $this->getAcceptableContentTypes();

        // Convert the content types to an array if it's a single string.
        $contentTypes = (array)$contentTypes;

        // Loop through the acceptable types to find the most suitable match.
        foreach ($accepts as $accept) {
            // If "*" is in the acceptable types, return the first content type.
            if (in_array($accept, ['*/*', '*'])) {
                return $contentTypes[0];
            }

            foreach ($contentTypes as $contentType) {
                // Get the MIME type for the content type, if available.
                $type = $contentType;

                if (null !== ($mimeType = $this->getMimeType($contentType))) {
                    $type = $mimeType;
                }

                // Normalize both the accept and type to lowercase for comparison.
                $accept = mb_strtolower($accept);
                $type = mb_strtolower($type);

                // Check if the types match or if a wildcard match is possible.
                if ($this->matchesType($type, $accept) || $accept === strtok($type, '/') . '/*') {
                    return $contentType;
                }
            }
        }

        // If no suitable match is found, return null.
        return null;
    }

    /**
     * Determines if the current request accepts any content type.
     *
     * This method checks if the `Accept` header of the request
     * that any content type is acceptable.
     *
     * @return bool True if the request accepts any content type, false otherwise.
     */
    public function f(): bool
    {
        // Get the list of acceptable content types from the request.
        $acceptable = $this->getAcceptableContentTypes();

        // If the `Accept` header is empty or allows any content type, return true.
        return count($acceptable) === 0 || (
            isset($acceptable[0]) && ($acceptable[0] === '*/*' || $acceptable[0] === '*')
        );
    }

    /**
     * Determines whether a request accepts JSON.
     *
     * This method is a shorthand for checking if the request accepts "application/json".
     *
     * @return bool True if the request accepts JSON, false otherwise.
     */
    public function acceptsJson(): bool
    {
        return $this->accepts('application/json');
    }

    /**
     * Determines whether a request accepts HTML.
     *
     * This method is a shorthand for checking if the request accepts "text/html".
     *
     * @return bool True if the request accepts HTML, false otherwise.
     */
    public function acceptsHtml(): bool
    {
        return $this->accepts('text/html');
    }

    /**
     * Gets the data format expected in the response.
     *
     * This method determines the format of the response based on the `Accept` header. If no suitable
     * match is found, it defaults to "html".
     *
     * @param string $default The default format if no match is found.
     *
     * @return string The format for the response (e.g., "json", "html").
     */
    public function format(string $default = 'html'): string
    {
        // Loop through the acceptable content types and return the matching format.
        foreach ($this->getAcceptableContentTypes() as $type) {
            if ($format = $this->getFormat($type)) {
                return $format;
            }
        }

        // Return the default format if no match is found.
        return $default;
    }
}
