<?php

declare(strict_types=1);

namespace Maginium\Framework\Response\Helpers;

use Maginium\Framework\Support\Arr;

/**
 * Class responsible for formatting and parsing stack traces.
 *
 * This utility processes stack traces into a structured format,
 * extracting key details such as file, line number, class, method, and arguments.
 */
class StackTraceFormatter
{
    /**
     * Format a stack trace array into a structured format.
     *
     * This method takes a raw stack trace (an array of strings) and processes each
     * frame using the `parseStackTraceFrame` method to convert it into a structured array.
     *
     * @param array|null $trace The stack trace array. Each element is a string representing a stack frame.
     *
     * @return array|null The formatted stack trace. Each element is a structured array with details of a frame.
     */
    public static function formatStackTrace(?array $trace): array
    {
        // Check if the trace is valid; return an empty array if null
        if ($trace) {
            // Use Arr::map to process each frame with the parseStackTraceFrame method
            return Arr::map($trace, [static::class, 'parseStackTraceFrame']);
        }

        // Return an empty array if no trace is provided
        return [];
    }

    /**
     * Parse a stack trace frame string into a structured array.
     *
     * This method extracts information from a single frame of the stack trace string.
     * It uses a regex pattern to identify components such as file, line number, class,
     * method, and arguments. If parsing fails, it returns the raw frame for debugging.
     *
     * @param string $frame The frame string to parse (e.g., "#0 file.php(123): Class->method(args)").
     *
     * @return array The structured frame data, including keys like 'file', 'line', 'class', 'type', 'function', and 'args'.
     */
    protected static function parseStackTraceFrame(string $frame): array
    {
        // Regex pattern to extract details from a stack trace frame
        $pattern = '/^#\d+\s+(?<file>.+?)\((?<line>\d+)\):\s+(?<class>[^\s]+)(?<type>::|->)(?<function>[^(]+)\((?<args>.*)\)\s*$/';

        // Match the frame string against the regex pattern
        if (preg_match($pattern, $frame, $matches)) {
            return [
                'file' => $matches['file'], // Path to the file where the error occurred
                'line' => (int)$matches['line'], // Line number of the error in the file
                'class' => $matches['class'], // Class name involved in the stack trace
                'type' => $matches['type'], // Method call type ('->' for instance, '::' for static)
                'function' => $matches['function'], // Function or method being called
                'args' => static::parseArguments($matches['args']), // Parsed arguments for the function
            ];
        }

        // Fallback: Return the raw frame if parsing fails
        return [
            'raw' => $frame, // Include the raw frame string for debugging
        ];
    }

    /**
     * Parse the arguments part of a stack trace frame into a structured array.
     *
     * This method processes the arguments string from a stack trace frame to identify
     * objects and potentially other argument types. Currently, it focuses on objects,
     * but additional parsing logic can be added for other types.
     *
     * @param string $argsString The arguments string to parse (e.g., "Object(ClassName)").
     *
     * @return array The structured arguments data, with each argument represented as an associative array.
     */
    protected static function parseArguments(string $argsString): array
    {
        $args = []; // Initialize an empty array to hold parsed arguments
        $pattern = '/Object\(([^)]+)\)/'; // Regex pattern to identify objects in the arguments string

        // Match all occurrences of the pattern in the arguments string
        if (preg_match_all($pattern, $argsString, $matches)) {
            // Map each matched object into a structured array with type and class details
            $args = Arr::map(
                $matches[1],
                fn($arg) => ['type' => 'Object', 'class' => $arg],
            );
        }

        // Additional parsing logic for other argument types can be added here as needed

        return $args; // Return the structured arguments array
    }
}
