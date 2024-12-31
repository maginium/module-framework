<?php

declare(strict_types=1);

use Maginium\Foundation\Enums\FileExtension;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Path;
use Maginium\Framework\Support\Str;
use Maginium\Framework\Support\Validator;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\PhpExecutableFinder;

/*
 * URL constants as defined in the PHP Manual under "Constants usable with
 * http_build_url()".
 *
 * These constants can be used as flags in the http_build_url() function to control
 * the behavior of URL manipulation.
 *
 * @see http://us2.php.net/manual/en/http.constants.php#http.constants.url
 */

// Define constant for replacing parts of a URL.
if (! defined('HTTP_URL_REPLACE')) {
    // Replace parts of the URL
    define('HTTP_URL_REPLACE', 1);
}

/**
 * HTTP_URL_JOIN_PATH constant.
 *
 * This constant is used to specify that the paths of two URLs
 * should be joined together. When this flag is set, the path
 * of the second URL will be appended to the path of the first URL.
 *
 * @var int
 */
if (! defined('HTTP_URL_JOIN_PATH')) {
    // Define constant for joining paths of two URLs.
    define('HTTP_URL_JOIN_PATH', 2);
}

/**
 * HTTP_URL_JOIN_QUERY constant.
 *
 * This constant indicates that the query strings of two URLs
 * should be joined. When this flag is set, the query string of
 * the second URL will be combined with the query string of the
 * first URL.
 *
 * @var int
 */
if (! defined('HTTP_URL_JOIN_QUERY')) {
    // Define constant for joining query strings of two URLs.
    define('HTTP_URL_JOIN_QUERY', 4);
}

/**
 * HTTP_URL_STRIP_USER constant.
 *
 * This constant is used to specify that the user information
 * (username) should be stripped from the URL. When this flag
 * is set, the user info will not be included in the final
 * constructed URL.
 *
 * @var int
 */
if (! defined('HTTP_URL_STRIP_USER')) {
    // Define constant for stripping the user information from a URL.
    define('HTTP_URL_STRIP_USER', 8);
}

/**
 * HTTP_URL_STRIP_PASS constant.
 *
 * This constant indicates that the password should be removed
 * from the URL. When this flag is set, the password will not
 * be included in the final constructed URL.
 *
 * @var int
 */
if (! defined('HTTP_URL_STRIP_PASS')) {
    // Define constant for stripping the password from a URL.
    define('HTTP_URL_STRIP_PASS', 16);
}

/**
 * HTTP_URL_STRIP_AUTH constant.
 *
 * This constant specifies that all authentication information,
 * including both the username and password, should be stripped
 * from the URL. When this flag is set, the resulting URL will
 * not contain any authentication credentials.
 *
 * @var int
 */
if (! defined('HTTP_URL_STRIP_AUTH')) {
    // Define constant for stripping both user info and password from a URL.
    define('HTTP_URL_STRIP_AUTH', 32);
}

/**
 * HTTP_URL_STRIP_PORT constant.
 *
 * This constant is used to specify that the port number should
 * be removed from the URL. When this flag is set, the final
 * constructed URL will not include the port.
 *
 * @var int
 */
if (! defined('HTTP_URL_STRIP_PORT')) {
    // Define constant for stripping the port from a URL.
    define('HTTP_URL_STRIP_PORT', 64);
}

/**
 * HTTP_URL_STRIP_PATH constant.
 *
 * This constant indicates that the path component of the URL
 * should be stripped. When this flag is set, the path will not
 * be included in the final constructed URL.
 *
 * @var int
 */
if (! defined('HTTP_URL_STRIP_PATH')) {
    // Define constant for stripping the path from a URL.
    define('HTTP_URL_STRIP_PATH', 128);
}

/**
 * HTTP_URL_STRIP_QUERY constant.
 *
 * This constant specifies that the query string of the URL
 * should be removed. When this flag is set, the final constructed
 * URL will not contain any query parameters.
 *
 * @var int
 */
if (! defined('HTTP_URL_STRIP_QUERY')) {
    // Define constant for stripping the query string from a URL.
    define('HTTP_URL_STRIP_QUERY', 256);
}

/**
 * HTTP_URL_STRIP_FRAGMENT constant.
 *
 * This constant indicates that the fragment (also known as the
 * hash) should be stripped from the URL. When this flag is set,
 * the resulting URL will not include any fragment information.
 *
 * @var int
 */
if (! defined('HTTP_URL_STRIP_FRAGMENT')) {
    // Define constant for stripping the fragment from a URL.
    define('HTTP_URL_STRIP_FRAGMENT', 512);
}

/**
 * HTTP_URL_STRIP_ALL constant.
 *
 * This constant specifies that all components of the URL should
 * be removed. When this flag is set, none of the URL parts will
 * be included in the final constructed URL.
 *
 * @var int
 */
if (! defined('HTTP_URL_STRIP_ALL')) {
    // Define constant for stripping all parts of a URL.
    define('HTTP_URL_STRIP_ALL', 1024);
}

/**
 * SP constant.
 *
 * This constant defines the directory separator used by the
 * operating system. It is set to DIRECTORY_SEPARATOR to ensure
 * compatibility across different platforms.
 *
 * @var string
 */
if (! defined('SP')) {
    // Define a constant for the directory separator if it is not already defined.
    define('SP', DIRECTORY_SEPARATOR);
}

/**
 * MAGENTO_BIN constant.
 *
 * This constant defines the path to the Magento binary. It is
 * used to execute Magento commands from the command line.
 *
 * @var string
 */
if (! defined('MAGENTO_BIN')) {
    // Define a constant for the Magento binary path if it is not already defined.
    define('MAGENTO_BIN', 'bin/magento');
}

// Define the http_build_url function if it doesn't exist.
if (! function_exists('http_build_url')) {
    /**
     * Build a URL from its components.
     *
     * This function combines parts of a URL based on the specified flags,
     * allowing for replacement, joining paths, or joining query strings.
     *
     * @see https://github.com/jakeasmith/http_build_url
     *
     * @param mixed $url     (part(s) of) a URL as a string or an associative array
     *                       like that returned by parse_url()
     * @param mixed $replace same as the first argument, represents parts to merge
     * @param int   $flags   a bitmask of HTTP_URL constants; HTTP_URL_REPLACE is the default
     * @param array $newUrl  if set, will be populated with the parts of the composed URL
     *                       as parse_url() would return
     *
     * @return string The constructed URL as a string.
     */
    function http_build_url($url, $replace = [], $flags = HTTP_URL_REPLACE, &$newUrl = []): string
    {
        // If $url is a string, parse it into its components
        if (Validator::isString($url)) {
            $url = parse_url($url);
        }

        // If $replace is a string, parse it into its components
        if (Validator::isString($replace)) {
            $replace = parse_url($replace);
        }

        // Define the segments of the URL we care about
        $urlSegments = ['scheme', 'host', 'user', 'pass', 'port', 'path', 'query', 'fragment'];

        // Set flags for stripping segments if requested
        if ($flags & HTTP_URL_STRIP_ALL) {
            $flags |= HTTP_URL_STRIP_USER
                   | HTTP_URL_STRIP_PASS
                   | HTTP_URL_STRIP_PORT
                   | HTTP_URL_STRIP_PATH
                   | HTTP_URL_STRIP_QUERY
                   | HTTP_URL_STRIP_FRAGMENT;
        } elseif ($flags & HTTP_URL_STRIP_AUTH) {
            $flags |= HTTP_URL_STRIP_USER
                   | HTTP_URL_STRIP_PASS;
        }

        // Filter $url and $replace arrays to keep only known segments

        // Normalize keys to lowercase
        Arr::changeKeyCase($url, CASE_LOWER);

        // Normalize keys to lowercase
        Arr::changeKeyCase($replace, CASE_LOWER);

        // Keep only valid segments that are set
        $url = Arr::filter($url, fn($value, $key) => in_array($key, $urlSegments) && isset($value), ARRAY_FILTER_USE_BOTH);
        $replace = Arr::filter($replace, fn($value, $key) => in_array($key, $urlSegments) && isset($value), ARRAY_FILTER_USE_BOTH);

        // If the replace flag is set, replace the relevant parts of the URL
        if ($flags & HTTP_URL_REPLACE) {
            $url = Arr::replace($url, $replace);
        } else {
            // Process joining paths if requested
            if (($flags & HTTP_URL_JOIN_PATH) && isset($replace['path'])) {
                // Current URL path
                $urlPath = (isset($url['path'])) ? explode(SP, trim($url['path'], SP)) : [];

                // Path to join
                $joinedPath = explode(SP, trim($replace['path'], SP));

                // Merge the current path with the new path

                // Set the new path
                $url['path'] = SP . implode(SP, Arr::merge($urlPath, $joinedPath));
            }

            // Process joining query strings if requested
            if (($flags & HTTP_URL_JOIN_QUERY) && isset($replace['query'])) {
                // Initialize arrays for URL queries
                $urlQuery = $joinedQuery = [];

                // Parse the current and new queries into associative arrays
                parse_str($url['query'] ?? '', $urlQuery);
                parse_str($replace['query'] ?? '', $joinedQuery);

                // Merge the current and new queries, and build a query string

                // Set the new query
                $url['query'] = http_build_query(Arr::replaceRecursive($urlQuery, $joinedQuery));
            }
        }

        // Strip segments as necessary based on flags
        foreach ($urlSegments as $segment) {
            // Get the constant name
            $strip = 'HTTP_URL_STRIP_' . mb_strtoupper($segment);

            if (! defined($strip)) {
                // Skip if the constant is not defined
                continue;
            }

            // Unset the segment if the corresponding flag is set
            if ($flags & constant($strip)) {
                unset($url[$segment]);
            }
        }

        // Populate $newUrl with the remaining parts of the URL
        $newUrl = $url;

        // Build the final URL string from its components
        $urlString = '';

        // Append scheme if present
        if (! Validator::isEmpty($url['scheme'])) {
            // Add scheme (e.g., http://)
            $urlString .= $url['scheme'] . '://';
        }

        // Append user info if present
        if (! Validator::isEmpty($url['user'])) {
            // Add username
            $urlString .= $url['user'];

            // Append password if present
            if (! Validator::isEmpty($url['pass'])) {
                // Add password
                $urlString .= ':' . $url['pass'];
            }

            // Add '@' after user info
            $urlString .= '@';
        }

        // Append host if present
        if (! Validator::isEmpty($url['host'])) {
            // Add host
            $urlString .= $url['host'];
        }

        // Append port if present
        if (! Validator::isEmpty($url['port'])) {
            // Add port
            $urlString .= ':' . $url['port'];
        }

        // Append path if present
        if (! Validator::isEmpty($url['path'])) {
            // Add path with separator
            $urlString .= ((mb_substr($url['path'], 0, 1) !== SP) ? SP : '') . $url['path'];
        }

        // Append query string if present
        if (! Validator::isEmpty($url['query'])) {
            // Add query string
            $urlString .= '?' . $url['query'];
        }

        // Append fragment if present
        if (! Validator::isEmpty($url['fragment'])) {
            // Add fragment
            $urlString .= '#' . $url['fragment'];
        }

        // Return the constructed URL
        return $urlString;
    }
}

if (! function_exists('join_paths')) {
    /**
     * Join the given paths together.
     *
     * @param  string|null  $basePath
     * @param  string  ...$paths
     *
     * @return string
     */
    function join_paths(string ...$paths)
    {
        // Initialize an empty result path.
        $result = '';

        foreach ($paths as $path) {
            // Check if the path represents a file extension
            $extension = FileExtension::getKey($path);

            if ($extension) {
                // Directly concatenate if a file extension is found.
                $result .= $path;
            } else {
                // Otherwise, append the path using the separator.
                $result = rtrim($result, SP) . SP . Str::ltrim($path, SP);
            }
        }

        return $result;
    }
}

if (! function_exists('Maginium\Framework\Support\php_binary')) {
    /**
     * Determine the PHP Binary.
     *
     * @return string
     */
    function php_binary()
    {
        return (new PhpExecutableFinder)->find(false) ?: 'php';
    }
}

if (! function_exists('Maginium\Framework\Support\node_binary')) {
    /**
     * Determine the Node Binary.
     *
     * This function attempts to find the Node binary using the ExecutableFinder.
     *
     * @return string The path to the Node binary, or null if not found.
     */
    function node_binary(): ?string
    {
        // Find the Node binary
        return (new ExecutableFinder)->find('node');
    }
}

if (! function_exists('base_path')) {
    /**
     * Get the path to the base of the install.
     *
     * @param  string  $path
     *
     * @return string
     */
    function base_path($path = '')
    {
        return Path::join(BP, $path);
    }
}

if (! function_exists('var_path')) {
    /**
     * Get the path to the storage folder.
     *
     * @param  string  $path
     *
     * @return string
     */
    function var_path($path = '')
    {
        return Path::join(base_path('var'), $path);
    }
}

if (! function_exists('temp_path')) {
    /**
     * temp_path gets the path to the temporary storage folder.
     *
     * @param  string  $path
     *
     * @return string
     */
    function temp_path($path = '')
    {
        return Path::join(base_path('pub'), $path ? SP . $path : $path);
    }
}

if (! function_exists('storage_path')) {
    /**
     * Get the path to the storage folder.
     *
     * @param  string  $path
     *
     * @return string
     */
    function storage_path($path = '')
    {
        return Path::join(base_path('pub'), $path);
    }
}

if (! function_exists('media_path')) {
    /**
     * Get the path to the storage folder.
     *
     * @param  string  $path
     *
     * @return string
     */
    function media_path($path = '')
    {
        return Path::join(storage_path('media'), $path);
    }
}

if (! function_exists('join')) {
    /**
     * Join multiple paths together to create a single path.
     *
     * @param  string  ...$paths One or more paths to join.
     *
     * @return string The joined path.
     */
    function join(string ...$paths): string
    {
        return Path::join(...$paths);
    }
}
