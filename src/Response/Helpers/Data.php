<?php

declare(strict_types=1);

namespace Maginium\Framework\Response\Helpers;

use Maginium\Framework\Support\Facades\Date;
use Maginium\Framework\Support\Facades\Request;
use Maginium\Framework\Support\Facades\Uuid;
use Maginium\Framework\Support\Str;
use Maginium\Framework\Support\Validator;

/**
 * Class Data.
 *
 * Helper class for retrieving request ID and timestamp from request headers.
 *
 * @method static mixed getNestedData(array $data) Recursively get nested data from the response array.
 * @method static string|null getRequestId(mixed $request) Get the request ID from the request headers.
 * @method static string|null getTimestamp(mixed $request) Get the timestamp from the request headers.
 */
class Data
{
    /**
     * Header name for the X-Request-ID header.
     */
    private const REQUEST_ID_HEADER = 'x-request-id';

    /**
     * Header name for the X-Amazon-Request-ID header.
     */
    private const AMAZON_REQUEST_ID_HEADER = 'x-amazon-request-id';

    /**
     * Header name for the X-Timestamp header.
     */
    private const TIMESTAMP_HEADER = 'x-timestamp';

    /**
     * Recursively get nested data from the response array.
     *
     * This method traverses through the array, checking if any value is itself an array.
     * If so, it calls itself recursively to further explore the nested array.
     *
     * @param array $data The array containing response information.
     *
     * @return mixed The processed data with all nested arrays fully resolved.
     */
    public static function getNestedData(array $data)
    {
        $responseData = $data;

        // Iterate through each element of the array to identify and process nested arrays
        foreach ($data as $key => $value) {
            if (Validator::isArray($value)) {
                // Recurse if the value is an array to get deeper nested data
                $responseData[$key] = self::getNestedData($value);
            }
        }

        // Return the fully processed data, with all nested arrays expanded
        return $responseData;
    }

    /**
     * Get the request ID from the request headers.
     *
     * This method attempts to extract the request ID from the incoming HTTP request headers.
     * It checks for headers such as "X-Request-ID" or "X-Amazon-Request-ID", regardless of case.
     * If no matching header is found, it generates a new UUID to uniquely identify the request.
     *
     * @return string|null The request ID, or a generated UUID if not found.
     */
    public static function getRequestId(): ?string
    {
        // Retrieve all headers from the request
        $headers = Request::getHeaders();

        // Define the request ID headers to check, in a case-insensitive manner
        $requestIdHeaders = [
            Str::lower(self::REQUEST_ID_HEADER),        // Standard request ID header
            Str::lower(self::AMAZON_REQUEST_ID_HEADER), // Amazon request ID header
        ];

        // Loop through each header in the request
        foreach ($headers as $header) {
            // Retrieve the field name of the header in lowercase for comparison
            $fieldName = Str::lower($header->getFieldName());

            // Check if the header name matches any of the predefined request ID headers
            if (in_array($fieldName, $requestIdHeaders, true)) {
                // If a matching header is found, return its value
                return $header->getFieldValue();
            }
        }

        // If no matching request ID header is found, generate a new UUID as a fallback
        return Uuid::generate();
    }

    /**
     * Get the timestamp from the request headers.
     *
     * This method retrieves the timestamp from the request headers, which is often used
     * to record the time of the request. It performs a case-insensitive search for the
     * timestamp header. If no timestamp is found, it will return the current timestamp.
     *
     * @return string|null The timestamp from the header, or the current timestamp if not found.
     */
    public static function getTimestamp(): ?string
    {
        // Retrieve all headers from the request
        $headers = Request::getHeaders();

        // Define the timestamp header to check in a case-insensitive manner
        $timestampHeader = Str::lower(self::TIMESTAMP_HEADER);

        // Iterate through each header in the request
        foreach ($headers as $header) {
            // Retrieve the field name of the header in lowercase for comparison
            $fieldName = Str::lower($header->getFieldName());

            // If the timestamp header is found (case-insensitively), return its value
            if ($fieldName === $timestampHeader) {
                return $header->getFieldValue();
            }
        }

        // If no timestamp header is found, return the current timestamp
        return Date::now()->toDateTimeString();
    }
}
