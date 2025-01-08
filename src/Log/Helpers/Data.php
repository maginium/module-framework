<?php

declare(strict_types=1);

namespace Maginium\Framework\Log\Helpers;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Magento\Framework\Phrase;
use Maginium\Framework\Config\Enums\ConfigDrivers;
use Maginium\Framework\Log\Enums\LogEmoji;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Facades\Config;
use Maginium\Framework\Support\Facades\Request;
use Maginium\Framework\Support\Facades\Uuid;
use Maginium\Framework\Support\Php;
use Maginium\Framework\Support\Str;
use Maginium\Framework\Support\Stringable;
use Maginium\Framework\Support\Validator;
use Throwable;

/**
 * Class Data.
 *
 * Helper class for logger operations.
 */
class Data
{
    /**
     * Header name for the X-Request-ID header.
     */
    private const REQUEST_ID_HEADER = 'x-request-id';

    /**
     * Build the log message.
     *
     * This method ensures that the message is converted into a string format that can be logged.
     * It handles different types of messages, including arrays, objects implementing Jsonable or Arrayable,
     * and exceptions.
     *
     * @param  Arrayable|Jsonable|Stringable|array|string  $message  The log message, which can be an array, string, exception, etc.
     *
     * @return string The formatted log message as a string.
     */
    public static function buildLogMessage($message): string
    {
        // If the message is an array, convert it to a string representation.
        if (is_array($message)) {
            return var_export($message, true);
        }

        // If the message is a Jsonable object, convert it to a JSON string.
        if ($message instanceof Jsonable) {
            return $message->toJson();
        }

        // If the message is an Arrayable object, convert it to a string by exporting it as an array.
        if ($message instanceof Arrayable) {
            return var_export($message->toArray(), true);
        }

        // If the message is an instance of Throwable (e.g., Exception), extract the exception message.
        if ($message instanceof Throwable) {
            $message = $message->getMessage();
        }

        // If the message is a Phrase object, convert it to a JSON string.
        if ($message instanceof Phrase) {
            $message = $message->render();
        }

        // Return the message as a string (cast other types to string).
        return (string)$message;
    }

    /**
     * Formats the log message with additional context details.
     *
     * @param  string  $level  The severity level of the log entry (e.g., INFO, WARN, ERROR).
     * @param  string  $message  The main log message.
     * @param  string|null  $className  The name of the class or module responsible for generating the log entry.
     * @param  array  $options  Additional context options to include in the log (e.g., reqId, txId, userId).
     *
     * @return string The formatted log message.
     */
    public static function formatMessage(string $level, string $message, ?string $className, array $options = []): string
    {
        // Generate a unique request ID if not provided.
        $options['reqId'] ??= Request::getParam(self::REQUEST_ID_HEADER) ?? Uuid::generate();

        // Ensure options have default values if not provided.
        $options['ip'] ??= null;
        $options['txId'] ??= null;
        $options['role'] ??= null;
        $options['action'] ??= null;
        $options['userId'] ??= null;
        $options['execTime'] ??= null;

        // Default to 'production' if not set.
        $options['env'] ??= Config::driver(ConfigDrivers::ENV)->getString('APP_ENV', 'production');

        // Get the emoji for the log level.
        $emoji = self::applyLogEmoji($level);

        // Construct the log message as a string.
        $logString = Str::format('%s %s |', $emoji, Str::upper($level));

        // Include className if it's not null or empty.
        if (! Validator::isEmpty($className)) {
            $logString .= Str::format(' %s |', $className);
        }

        // Append optional context fields to the log string.
        if (! Validator::isEmpty($options['reqId'])) {
            $logString .= Str::format(' [reqId: %s]', $options['reqId']);
        }

        if (! Validator::isEmpty($options['txId'])) {
            $logString .= Str::format(' [txId: %s]', $options['txId']);
        }

        if (! Validator::isEmpty($options['userId']) || ! Validator::isEmpty($options['role']) || ! Validator::isEmpty($options['ip'])) {
            $logString .= Str::format(' [userId: %s, role: %s, ip: %s]', $options['userId'], $options['role'], $options['ip']);
        }

        // Environment (e.g., production, staging).
        if (! Validator::isEmpty($options['env'])) {
            $logString .= Str::format(' [env: %s]', $options['env']);
        }

        // Specific action or method.
        if (! Validator::isEmpty($options['action'])) {
            $logString .= Str::format(' [action: %s]', $options['action']);
        }

        // Execution time, if applicable.
        if (! Validator::isEmpty($options['execTime'])) {
            $logString .= Str::format(' [execTime: %s]', $options['execTime']);
        }

        // Append the main log message.
        $logString .= Str::format(' | %s', $message);

        // Return the fully constructed log string.
        return $logString;
    }

    /**
     * Apply log emoji to message based on log level.
     *
     * @param  string  $level  The log level
     *
     * @return string The emoji corresponding to the log level.
     */
    public static function applyLogEmoji(string $level): string
    {
        // Get the emoji for the given log level and prepend it to the message
        return LogEmoji::getValue($level) ?? '';
    }

    /**
     * Add technical metadata to the context for a given class name.
     *
     * This method checks if the specified class, trait, or interface exists
     * and builds a metadata structure that represents the class hierarchy.
     *
     * @param  string  $className  The name of the class to analyze.
     *
     * @return array|null Returns an array containing class metadata or null if the class doesn't exist.
     */
    public static function addClassesMetadata($className): ?array
    {
        // Check if the class, trait, or interface exists before proceeding
        if (Php::isClassExists($className) || trait_exists($className) || interface_exists($className)) {
            // Get all parent classes for the given class name
            $parentClasses = Arr::values(class_parents($className));

            // Initialize an array to hold the namespace chaining structure
            $namespaceChaining = [];

            // Iterate through each parent class to build the namespace hierarchy
            foreach ($parentClasses as $baseName) {
                // Split the class name into its namespace parts
                $namespaceParts = Php::explode('\\', $baseName);

                // Get the first part as the namespace; default to 'Global' if empty
                $namespace = ! Validator::isEmpty($namespaceParts[0]) ? $namespaceParts[0] : 'Global';

                // Ensure the namespace is initialized in the chaining array
                if (! isset($namespaceChaining[$namespace])) {
                    $namespaceChaining[$namespace] = [];
                }

                // Reference to the current position in the namespace chaining structure
                $currentNamespace = &$namespaceChaining[$namespace];

                // Build the namespace structure from the parts
                for ($i = 1; $i < Php::count($namespaceParts); $i++) {
                    $part = $namespaceParts[$i];

                    // Check if we are at the last part of the namespace
                    if ($i === Php::count($namespaceParts) - 1) {
                        // Initialize an array for the last part if it doesn't exist
                        if (! isset($currentNamespace[$part])) {
                            $currentNamespace[$part] = [];
                        }
                        // Add the base class name to the array for the last part
                        $currentNamespace[$part][] = $baseName;
                    } else {
                        // Initialize the current part in the hierarchy if it doesn't exist
                        if (! isset($currentNamespace[$part])) {
                            $currentNamespace[$part] = [];
                        }
                        // Update the reference to the current part for the next iteration
                        $currentNamespace = &$currentNamespace[$part];
                    }
                }
            }

            // Return the constructed technical metadata array
            return [
                'classname' => class_basename($className), // Base name of the class
                'class-chaining' => $namespaceChaining,     // Hierarchical structure of namespaces
            ];
        }

        // Return null if the class, trait, or interface does not exist
        return null;
    }

    /**
     * Prepare the log context from the provided context array.
     *
     * This method extracts relevant information from the provided context
     * array and formats it into a structured array that will be used for logging.
     * It ensures that all necessary fields are present, providing default values
     * where applicable, which helps maintain consistency across log messages.
     *
     * @param  array  $context  The provided context information that may include:
     *                          - 'reqId': The unique request ID (string|null)
     *                          - 'txId': The transaction ID (string|null)
     *                          - 'userId': The ID of the user making the request (string|null)
     *                          - 'role': The role of the user (string|null)
     *                          - 'ip': The IP address of the user (string|null)
     *                          - 'env': The environment in which the application is running (string, defaults to 'production')
     *                          - 'action': The action being logged (string|null)
     *                          - 'execTime': The execution time for the action (string|null)
     *
     * @return array The formatted log context.
     */
    public static function extractOptions(array $context): array
    {
        return [
            // Extract the 'reqId' from the context, or set it to null if not provided
            'reqId' => $context['reqId'] ?? null,

            // Extract the 'txId' from the context, or set it to null if not provided
            'txId' => $context['txId'] ?? null,

            // Extract the 'userId' from the context, or set it to null if not provided
            'userId' => $context['userId'] ?? null,

            // Extract the 'role' from the context, or set it to null if not provided
            'role' => $context['role'] ?? null,

            // Extract the 'ip' from the context, or set it to null if not provided
            'ip' => $context['ip'] ?? null,

            // Extract the 'env' from the context, or default to 'production' if not provided
            'env' => $context['env'] ?? null,

            // Extract the 'action' from the context, or set it to null if not provided
            'action' => $context['action'] ?? null,

            // Extract the 'execTime' from the context, or set it to null if not provided
            'execTime' => $context['execTime'] ?? null,
        ];
    }
}
