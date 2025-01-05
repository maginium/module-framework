<?php

declare(strict_types=1);

namespace Maginium\Framework\Swagger\Interceptors\Webapi;

use Magento\Webapi\Model\Config;
use Magento\Webapi\Model\ServiceMetadata as BaseServiceMetadata;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Framework\Support\Arr;

/**
 * Custom implementation of ServiceMetadata to modify API class pattern.
 * Extends the base ServiceMetadata functionality to handle dynamic API service naming.
 */
class ServiceMetadata extends BaseServiceMetadata
{
    /**
     * Default pattern for Web API interface names.
     * Used to match Magento's default service class naming conventions.
     */
    public const DEFAULT_SERVICE_CLASS_PATTERN = '/^(.+?)\\\\(.+?)\\\\(Service|Services|Actions)\\\\(V\d+)+(\\\\.+)Interface$/';

    /**
     * List of API class directory names to build dynamic patterns.
     * This allows the system to handle additional naming conventions.
     *
     * @var array
     */
    private array $apiClassPatterns = ['Api', 'Controllers', 'Interfaces'];

    /**
     * Translate service interface name into service name.
     * Converts a given interface name into a format suitable for Web API service identification.
     *
     * @param string $interfaceName The fully qualified name of the interface.
     * @param string $version The version of the API (e.g., 'V1').
     * @param bool $preserveVersion Indicates if the version should be part of the service name.
     *
     * @throws InvalidArgumentException If the interface name does not match expected patterns.
     *
     * @return string The computed service name.
     */
    public function getServiceName($interfaceName, $version, $preserveVersion = true)
    {
        // Build a dynamic API class pattern using the configured class directories.
        $apiClassPattern = $this->buildApiClassPattern();
        $matches = []; // Initialize an array to store regex matches.

        // Check if the interface name matches Magento's default service class pattern.
        if ($this->isMatchingPattern($interfaceName, Config::SERVICE_CLASS_PATTERN, $matches)) {
            // Build the service name parts from the matches.
            $serviceNameParts = $this->buildServiceNameParts($matches, $version, $preserveVersion);
        }
        // Check if the interface name matches the dynamic API class pattern.
        elseif ($this->isMatchingPattern($interfaceName, $apiClassPattern, $matches)) {
            $serviceNameParts = $this->buildServiceNameParts($matches, $version, $preserveVersion);
        }
        // Throw an exception if no patterns match the interface name.
        else {
            throw InvalidArgumentException::make(sprintf('The service interface name "%s" is invalid.', $interfaceName));
        }

        // Concatenate the service name parts into a single string and make the first character lowercase.
        return lcfirst(implode('', $serviceNameParts));
    }

    /**
     * Builds the dynamic regex pattern for matching API class names.
     *
     * @return string The constructed regex pattern for dynamic API class names.
     */
    private function buildApiClassPattern(): string
    {
        // Combine the API class patterns into a regex alternation group (e.g., 'Api|Controllers|Interfaces').
        $patternPart = implode('|', $this->apiClassPatterns);

        // Return the full regex pattern for matching dynamic API class names.
        return "/^(.+?)\\\\(.+?)\\\\({$patternPart})(\\\\.+)Interface$/";
    }

    /**
     * Checks if the given string matches a pattern and extracts matches.
     *
     * @param string $input The input string to be tested against the pattern.
     * @param string $pattern The regex pattern to match.
     * @param array $matches Reference to the array that will store the regex matches.
     *
     * @return bool True if the pattern matches; false otherwise.
     */
    private function isMatchingPattern(string $input, string $pattern, array &$matches): bool
    {
        // Perform the regex match and return true if successful.
        return preg_match($pattern, $input, $matches) === 1;
    }

    /**
     * Builds the service name parts from regex matches.
     * Constructs the service name based on module information and optional version preservation.
     *
     * @param array $matches The regex matches containing parsed interface name components.
     * @param string $version The API version to include in the service name.
     * @param bool $preserveVersion Whether to include the version in the final service name.
     *
     * @return array The constructed parts of the service name.
     */
    private function buildServiceNameParts(array $matches, string $version, bool $preserveVersion): array
    {
        // Extract module namespace and name from regex matches.
        [$moduleNamespace, $moduleName] = $this->extractModuleInfo($matches);

        // Normalize the class name by removing unnecessary suffixes or prefixes.
        $className = $this->normalizeClassName($matches[3], $matches[4]);

        // Split the normalized class name into parts using namespace separators.
        $serviceNameParts = explode('\\', trim($className, '\\'));

        // Avoid duplication of module name in the service name.
        if ($moduleName === $serviceNameParts[0]) {
            $moduleName = '';
        }

        // Build the parent service name by combining the namespace, module name, and class name parts.
        $parentServiceName = $moduleNamespace . $moduleName . Arr::shift($serviceNameParts);
        Arr::unshift($serviceNameParts, $parentServiceName);

        // Append the version to the service name parts if required.
        if ($preserveVersion) {
            $serviceNameParts[] = $version;
        }

        return $serviceNameParts; // Return the final array of service name parts.
    }

    /**
     * Extracts module namespace and name from regex matches.
     *
     * @param array $matches The regex matches containing parsed interface name components.
     *
     * @return array An array containing the module namespace and name.
     */
    private function extractModuleInfo(array $matches): array
    {
        // Extract the module namespace and name from the regex matches.
        $moduleNamespace = $matches[1];
        $moduleName = $matches[2];

        // Exclude the 'Magento' namespace from the module name for simplicity.
        if ($moduleNamespace === 'Magento') {
            $moduleNamespace = '';
        }

        return [$moduleNamespace, $moduleName]; // Return the extracted namespace and name.
    }

    /**
     * Normalizes the class name by handling Interface suffix.
     *
     * @param string $className The main class name component from regex matches.
     * @param string|null $suffix The optional suffix indicating the type of interface.
     *
     * @return string The normalized class name.
     */
    private function normalizeClassName(string $className, ?string $suffix): string
    {
        // Append the suffix only if it's not already part of the class name.
        return $suffix === 'Interface' ? $className : $className . $suffix;
    }
}
