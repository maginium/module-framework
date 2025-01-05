<?php

declare(strict_types=1);

namespace Maginium\Framework\Request;

use Illuminate\Support\Traits\Macroable;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Customer as MagentoCustomer;
use Magento\Framework\App\Request\Http as BaseHttp;
use Magento\User\Api\Data\UserInterface;
use Magento\User\Model\User;
use Maginium\Customer\Models\Customer;
use Maginium\Framework\Request\Interfaces\RequestInterface;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Facades\Json;
use Maginium\Framework\Support\Str;
use Maginium\Framework\Support\Validator;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\InputBag;

/**
 * Represents an enhanced HTTP request with additional functionality.
 *
 * This class extends Magento's base HTTP request to provide custom features such as:
 * - Managing user and language state
 * - Parsing and interacting with JSON request bodies
 * - Handling content types and MIME formats
 */
class Request extends BaseHttp implements RequestInterface
{
    use Concerns\CanBePrecognitive;
    use Concerns\InteractsWithContentTypes;
    use Concerns\InteractsWithInput;
    use Concerns\InteractsWithQuery;
    use Macroable;

    /**
     * @var array<string, string[]>|null Mapping of formats to MIME types.
     */
    protected static ?array $formats = null;

    /**
     * @var array|null List of acceptable content types from the client request.
     */
    protected ?array $acceptableContentTypes = null;

    /**
     * @var MagentoCustomer|Customer|User|null The currently authenticated user, if any.
     */
    private MagentoCustomer|Customer|User|null $user = null;

    /**
     * @var string|null The current language or locale for the request.
     */
    private ?string $language = null;

    /**
     * Retrieves the MIME types associated with a specific format.
     *
     * This method checks the available formats and returns the corresponding MIME types
     * for a given format. If the formats are not initialized, it will initialize them first.
     *
     * @param string $format The format (e.g., "json", "html") whose MIME types are needed.
     *
     * @return array An array of MIME types associated with the given format.
     * If the format is not recognized, an empty array is returned.
     */
    public static function getMimeTypes(string $format): array
    {
        // Ensure that the formats are initialized before accessing them.
        if (static::$formats === null) {
            static::initializeFormats();
        }

        // Return the MIME types for the given format, or an empty array if not found.
        return static::$formats[$format] ?? [];
    }

    /**
     * Initializes the mapping of formats to their associated MIME types.
     *
     * This method defines the mapping between common formats (like JSON, XML, HTML, etc.)
     * and their respective MIME types. The mapping is stored in a static property for reuse.
     */
    protected static function initializeFormats(): void
    {
        static::$formats = [
            'css' => ['text/css'],
            'txt' => ['text/plain'],
            'rdf' => ['application/rdf+xml'],
            'rss' => ['application/rss+xml'],
            'atom' => ['application/atom+xml'],
            'jsonld' => ['application/ld+json'],
            'html' => ['text/html', 'application/xhtml+xml'],
            'json' => ['application/json', 'application/x-json'],
            'xml' => ['text/xml', 'application/xml', 'application/x-xml'],
            'form' => ['application/x-www-form-urlencoded', 'multipart/form-data'],
            'js' => ['application/javascript', 'application/x-javascript', 'text/javascript'],
        ];
    }

    /**
     * Merge new input into the current request's input array.
     *
     * @param  array  $input
     *
     * @return $this
     */
    public function merge(array $input)
    {
        $this->getInputSource()->add($input);

        return $this;
    }

    /**
     * Gets the current user associated with the request.
     *
     * This method retrieves the user that was set via the setUser method.
     * If no user is set, it returns null.
     *
     * @return CustomerInterface|UserInterface|null The current user object associated with the request, or null if not set.
     */
    public function user(): mixed
    {
        // Return the current user object or null if not set.
        return $this->user;
    }

    /**
     * Sets the current user for the request.
     *
     * This method allows you to assign a user (either a Customer or User model) to the
     * request object, enabling further functionality like user-based processing.
     *
     * @param MagentoCustomer|Customer|User|null $user The user object to set as the current user, or null to reset.
     */
    public function setUser(MagentoCustomer|CustomerInterface|User|null $user): void
    {
        // Assign the provided user object to the request's user property.
        $this->user = $user;
    }

    /**
     * Sets the language or locale for the request.
     *
     * This method allows you to set the language/locale that will be used for the request,
     * such as "en" for English, "fr" for French, etc.
     *
     * @param string $language The language code (e.g., "en", "fr") to set.
     */
    public function setLanguage(string $language): void
    {
        // Set the language property to the provided language code.
        $this->language = $language;
    }

    /**
     * Gets the current language or locale for the request.
     *
     * This method retrieves the language or locale that was set via the setLanguage method.
     * If no language is set, it returns null.
     *
     * @return string|null The current language/locale, or null if not set.
     */
    public function getLanguage(): ?string
    {
        // Return the current language code, or null if not set.
        return $this->language;
    }

    /**
     * Resets the state of the request, including user and language.
     *
     * This method clears any data associated with the request, including the user
     * and language settings. It also calls the parent `_resetState` method to reset
     * the base state of the request.
     */
    public function _resetState(): void
    {
        // Clear the user and language properties to reset the state.
        $this->user = null;
        $this->language = null;

        // Call the parent method to ensure the base state is reset.
        parent::_resetState();
    }

    /**
     * Decodes the JSON body of the request.
     *
     * This method parses the raw request body as JSON and returns the resulting
     * data structure. If the body is not valid JSON or empty, it will return a default value.
     *
     * @param mixed $default A default value to return if the JSON body is invalid or empty.
     *
     * @return mixed The parsed JSON data, or the default value if decoding fails.
     */
    public function json(mixed $default = null): mixed
    {
        // Retrieve the raw body content of the request.
        $rawBody = $this->getContent();

        // Attempt to decode the raw body as JSON.
        $jsonData = Json::decode($rawBody);

        // Return the decoded JSON data, or the default value if decoding fails.
        return $jsonData !== null ? $jsonData : $default;
    }

    /**
     * Retrieves all headers from the request.
     *
     * This method returns an associative array containing all the headers from the
     * incoming HTTP request. The headers are returned as key-value pairs.
     *
     * @return array<string, string[]> An associative array of request headers, with each
     *                                header name as the key and the values as an array.
     */
    public function headers(): array
    {
        // Retrieve and return the headers as an array.
        return $this->getHeaders()->toArray();
    }

    /**
     * Retrieves and decodes the body of the request.
     *
     * This method checks the request body content, and if it's a valid JSON string, it decodes it into a
     * native PHP structure (array or object). If the content is not in JSON format, it returns it as-is.
     * It handles various types of request body formats by ensuring that JSON content is properly parsed.
     *
     * @return mixed The parsed body data, typically an array or object if JSON content is provided.
     */
    public function getBody(): mixed
    {
        // Get the raw content of the incoming request
        $content = $this->getContent();

        // If the content is a valid non-empty string (could be JSON), decode it into a PHP array/object
        if (Validator::isString($content) && ! Validator::isEmpty($content)) {
            $content = Json::decode($content);
        }

        // Return the processed content, either as raw or decoded JSON
        return $content;
    }

    /**
     * Set the request body content.
     *
     * This method sets the content of the request body. It expects the input value to be processed (decoded)
     * from a request payload (e.g., a JSON body). The content is then stored, and can be accessed as a native
     * PHP structure (array or object), depending on the format of the input.
     *
     * @param mixed $value The raw request body content (e.g., JSON, form data, etc.).
     *
     * @return RequestInterface The current instance of the response object for method chaining.
     */
    public function setBody($value): RequestInterface
    {
        // Set the content of the body (e.g., decode JSON or set raw content)
        $this->setContent($value);

        // Return the current instance for method chaining
        return $this;
    }

    /**
     * Retrieves the raw body of the request as a string.
     *
     * This method returns the entire raw content of the HTTP request body as a string.
     * It is useful for handling raw data, such as JSON payloads, form data, or binary streams.
     *
     * @return string The raw request body content.
     */
    public function getRawBody(): string
    {
        // Fetch and return the raw request body content.
        return Json::encode($this->getContent());
    }

    /**
     * Retrieves a list of acceptable content types from the client request.
     *
     * This method extracts the `Accept` header from the HTTP request and parses it to determine
     * which MIME types the client prefers. The types are returned in order of preference, as specified
     * by the client.
     *
     * @return array An array of acceptable MIME types, ordered by client preference.
     */
    public function getAcceptableContentTypes(): array
    {
        // Retrieve the value of the "Accept" header from the request.
        $header = $this->header('Accept');

        // Parse the header and return an array of acceptable MIME types,
        // caching the result in `$this->acceptableContentTypes`.
        return $this->acceptableContentTypes ??= Arr::each(
            'strval', // Ensure all keys are converted to strings.
            Arr::keys(AcceptHeader::fromString($header)->all()), // Extract keys from parsed header.
        );
    }

    /**
     * Retrieves the primary MIME type associated with a specific format.
     *
     * This method looks up the first (primary) MIME type for a given format,
     * such as "json", "html", or "xml". If the format is unknown, it returns null.
     *
     * @param string $format The format name (e.g., "json").
     *
     * @return string|null The primary MIME type associated with the format, or null if unknown.
     */
    public function getMimeType(string $format): ?string
    {
        // Ensure formats are initialized before accessing them.
        if (static::$formats === null) {
            static::initializeFormats();
        }

        // Return the first MIME type associated with the format, or null if not found.
        return static::$formats[$format][0] ?? null;
    }

    /**
     * Retrieves the format associated with a given MIME type.
     *
     * This method identifies the format (e.g., "json", "html") that corresponds to a specific
     * MIME type. If the MIME type contains additional parameters (e.g., "text/html; charset=utf-8"),
     * those are stripped before matching. If no match is found, it returns null.
     *
     * @param string|null $mimeType The MIME type to resolve, such as "application/json".
     *
     * @return string|null The format name associated with the MIME type, or null if no match is found.
     */
    public function getFormat(?string $mimeType): ?string
    {
        // Ensure formats are initialized before accessing them.
        if (static::$formats === null) {
            static::initializeFormats();
        }

        // Remove parameters from the MIME type, if present (e.g., "text/html; charset=utf-8" -> "text/html").
        if ($mimeType && false !== $pos = mb_strpos($mimeType, ';')) {
            $mimeType = trim(mb_substr($mimeType, 0, $pos));
        }

        // Search through all formats to find a matching MIME type.
        foreach (static::$formats as $format => $mimeTypes) {
            if (in_array($mimeType, $mimeTypes, true)) {
                // Return the format if a match is found.
                return $format;
            }
        }

        // Return null if no format matches the given MIME type.
        return null;
    }

    /**
     * Return the Request instance.
     *
     * @return $this The current instance of the request.
     */
    public function instance()
    {
        // Simply return the current request instance.
        return $this;
    }

    /**
     * Get the request method.
     *
     * @return string The HTTP method used for the request (e.g., GET, POST).
     */
    public function method()
    {
        // Retrieve the HTTP method using the underlying framework method.
        return $this->getMethod();
    }

    /**
     * Get the root URL for the application.
     *
     * @return string The base URL including the scheme, host, and base path.
     */
    public function root()
    {
        // Combine the scheme, HTTP host, and base URL, trimming any trailing slashes.
        return rtrim($this->getSchemeAndHttpHost() . $this->getBaseUrl(), '/');
    }

    /**
     * Get the full URL (including protocol, host, and path) for the request, excluding the query string.
     *
     * @return string The full URL without the query parameters.
     */
    public function url()
    {
        // Get the URI object containing all components of the URL.
        $uri = $this->getUri();

        // Construct the full URL by combining scheme (e.g., http), host, and path.
        $fullUrl = $uri->getScheme() . '://' . $uri->getHost() . $uri->getPath();

        // Remove any trailing slashes for a cleaner output.
        return rtrim($fullUrl, '/');
    }

    /**
     * Get the full URL for the request.
     *
     * @return string The complete URL, including query parameters.
     */
    public function fullUrl()
    {
        // Retrieve the query string from the request.
        $query = $this->getQueryString();

        // Determine whether to append a '?' for query parameters, considering the base URL and path info.
        $question = $this->getBaseUrl() . $this->getPathInfo() === '/' ? '/?' : '?';

        // Append the query string to the URL if it exists, or return the base URL.
        return $query ? $this->url() . $question . $query : $this->url();
    }

    /**
     * Get the full URL for the request with additional query string parameters.
     *
     * @param array $query The new query parameters to append.
     *
     * @return string The full URL including the new query parameters.
     */
    public function fullUrlWithQuery(array $query)
    {
        // Determine the appropriate '?' or '/?' separator for the query.
        $question = $this->getBaseUrl() . $this->getPathInfo() === '/' ? '/?' : '?';

        // Merge the existing query parameters with the new ones and append to the URL.
        return count($this->query()) > 0
            ? $this->url() . $question . Arr::query(Arr::merge($this->query(), $query))
            : $this->fullUrl() . $question . Arr::query($query);
    }

    /**
     * Get the full URL for the request excluding specific query string parameters.
     *
     * @param array|string $keys The keys to exclude from the query string.
     *
     * @return string The full URL with the specified parameters removed.
     */
    public function fullUrlWithoutQuery($keys)
    {
        // Remove the specified query keys from the existing query.
        $query = Arr::except($this->query(), $keys);

        // Determine the appropriate '?' or '/?' separator for the query.
        $question = $this->getBaseUrl() . $this->getPathInfo() === '/' ? '/?' : '?';

        // Construct the new URL with the remaining query parameters.
        return count($query) > 0
            ? $this->url() . $question . Arr::query($query)
            : $this->url();
    }

    /**
     * Get the current path info for the request.
     *
     * @return string The path portion of the URL.
     */
    public function path()
    {
        // Trim any leading or trailing slashes from the path.
        $pattern = trim($this->getPathInfo(), '/');

        // Return '/' if the path is empty; otherwise, return the trimmed path.
        return $pattern === '' ? '/' : $pattern;
    }

    /**
     * Get the current decoded path info for the request.
     *
     * @return string The decoded path of the URL.
     */
    public function decodedPath()
    {
        // Decode the path to convert any URL-encoded characters into their original form.
        return rawurldecode($this->path());
    }

    /**
     * Get a specific segment from the URI (1-based index).
     *
     * @param int $index The segment index to retrieve (1-based).
     * @param string|null $default A default value to return if the segment is missing.
     *
     * @return string|null The requested segment or the default value.
     */
    public function segment($index, $default = null)
    {
        // Use an array helper to get the specified segment by its index.
        return Arr::get($this->segments(), $index - 1, $default);
    }

    /**
     * Get all of the segments for the request path.
     *
     * @return array An array of path segments.
     */
    public function segments()
    {
        // Split the decoded path into segments by the '/' delimiter.
        $segments = explode('/', $this->decodedPath());

        // Filter out empty segments and return the resulting array.
        return Arr::values(Arr::filter($segments, fn($value) => $value !== ''));
    }

    /**
     * Determine if the current request URI matches one of the given patterns.
     *
     * @param mixed ...$patterns The patterns to check against the URI.
     *
     * @return bool True if any pattern matches, false otherwise.
     */
    public function is(...$patterns)
    {
        // Get the decoded path of the request URI.
        $path = $this->decodedPath();

        // Check if any of the provided patterns match the URI path.
        return collect($patterns)->contains(fn($pattern) => Str::is($pattern, $path));
    }

    /**
     * Determine if the current request URL and query string match a pattern.
     *
     * @param mixed ...$patterns The patterns to check against the full URL.
     *
     * @return bool True if any pattern matches, false otherwise.
     */
    public function fullUrlIs(...$patterns)
    {
        // Get the full URL for the request.
        $url = $this->fullUrl();

        // Check if any of the provided patterns match the full URL.
        return collect($patterns)->contains(fn($pattern) => Str::is($pattern, $url));
    }

    /**
     * Get the host name from the request.
     *
     * @return string The host name (e.g., example.com).
     */
    public function host()
    {
        // Retrieve the host name using the underlying framework method.
        return $this->getHost();
    }

    /**
     * Get the HTTP host being requested, including the port if applicable.
     *
     * @return string The HTTP host with the port (if specified).
     */
    public function httpHost()
    {
        // Retrieve the HTTP host using the framework's method.
        return $this->getHttpHost();
    }

    /**
     * Get the scheme and HTTP host.
     *
     * This method returns the scheme (http or https) combined with the host,
     * e.g., "https://example.com".
     *
     * @return string The scheme and HTTP host of the request.
     */
    public function schemeAndHttpHost()
    {
        // Delegates to the framework's method to fetch the scheme and host.
        return $this->getSchemeAndHttpHost();
    }

    /**
     * Determine if the request is the result of a prefetch call.
     *
     * Checks specific headers (`HTTP_X_MOZ`, `Purpose`, or `Sec-Purpose`)
     * to identify if the request is a browser prefetch request.
     *
     * @return bool True if it's a prefetch request, false otherwise.
     */
    public function prefetch()
    {
        return strcasecmp($this->server->get('HTTP_X_MOZ') ?? '', 'prefetch') === 0 || // Check if the `HTTP_X_MOZ` header is "prefetch".
               strcasecmp($this->headers->get('Purpose') ?? '', 'prefetch') === 0 || // Check if the `Purpose` header is "prefetch".
               strcasecmp($this->headers->get('Sec-Purpose') ?? '', 'prefetch') === 0; // Check if the `Sec-Purpose` header is "prefetch".
    }

    /**
     * Determine if the request is over HTTPS.
     *
     * Uses the framework's method to check if the current request uses a secure protocol.
     *
     * @return bool True if the request is secure, false otherwise.
     */
    public function secure()
    {
        // Checks if HTTPS is being used.
        return $this->isSecure();
    }

    /**
     * Get the client IP address.
     *
     * Retrieves the IP address of the client making the request.
     *
     * @return string|null The client IP address or null if not available.
     */
    public function ip()
    {
        // Fetches the client IP address using the framework's method.
        return $this->getClientIp();
    }

    /**
     * Get the client user agent.
     *
     * Retrieves the value of the `User-Agent` header from the request.
     *
     * @return string|null The user agent string or null if not available.
     */
    public function userAgent()
    {
        // Accesses the `User-Agent` header from the request headers.
        return $this->headers->get('User-Agent');
    }

    /**
     * Merge new input into the request's input, but only when that key is missing from the request.
     *
     * Combines new input values with existing request input while preserving existing keys.
     *
     * @param  array  $input The array of input to merge.
     *
     * @return $this The updated request object.
     */
    public function mergeIfMissing(array $input)
    {
        return $this->merge(
            collect($input)->filter(fn($value, $key) => $this->missing($key))->toArray(), // Only include keys that are missing from the request.
        );
    }

    /**
     * Replace the input values for the current request.
     *
     * Updates the input data for the request, overwriting existing values.
     *
     * @param  array  $input The new input data.
     *
     * @return $this The updated request object.
     */
    public function replace(array $input)
    {
        // Replaces the request's input source with the provided data.
        $this->getInputSource()->replace($input);

        return $this;
    }

    /**
     * Get all of the input and files for the request.
     *
     * Returns all input data (GET, POST, JSON, etc.) along with uploaded files.
     *
     * @return array All input data and files.
     */
    public function toArray(): array
    {
        // Fetches all input and files from the request.
        return $this->all();
    }

    /**
     * Determine if the given offset exists.
     *
     * Checks if a specific key exists in the request's input.
     *
     * @param  string  $offset The key to check.
     *
     * @return bool True if the key exists, false otherwise.
     */
    public function offsetExists($offset): bool
    {
        // Uses a helper to check if the key exists in the request data.
        return Arr::has($this->all(), $offset);
    }

    /**
     * Get the value at the given offset.
     *
     * Retrieves the value for a specific key from the request's input.
     *
     * @param  string  $offset The key to retrieve.
     *
     * @return mixed The value of the specified key.
     */
    public function offsetGet($offset): mixed
    {
        // Uses the magic getter to fetch the value.
        return $this->__get($offset);
    }

    /**
     * Set the value at the given offset.
     *
     * Updates or adds a value for a specific key in the request's input.
     *
     * @param  string  $offset The key to set.
     * @param  mixed   $value  The value to set for the key.
     *
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        // Updates the input source with the new value.
        $this->getInputSource()->set($offset, $value);
    }

    /**
     * Remove the value at the given offset.
     *
     * Deletes a specific key and its value from the request's input.
     *
     * @param  string  $offset The key to remove.
     *
     * @return void
     */
    public function offsetUnset($offset): void
    {
        // Removes the key and its value from the input source.
        $this->getInputSource()->remove($offset);
    }

    /**
     * Get the input source for the request.
     *
     * Determines the appropriate input source based on the request type (e.g., JSON, GET, POST).
     *
     * @return InputBag The input source (e.g., query parameters or request body).
     */
    protected function getInputSource()
    {
        if ($this->isJson()) { // Checks if the request body is JSON.
            // Returns the JSON data as the input source.
            return $this->json();
        }

        return in_array($this->getRealMethod(), ['GET', 'HEAD']) // Checks if the HTTP method is GET or HEAD.
            ? $this->query // Uses query parameters for GET or HEAD requests.

            // Uses the request body for other HTTP methods.
            : $this->request;
    }

    /**
     * Filter the given array of files, removing any empty values.
     *
     * Recursively removes empty file entries from an array of uploaded files.
     *
     * @param  mixed  $files The array of files to filter.
     *
     * @return mixed The filtered array of files.
     */
    protected function filterFiles($files)
    {
        if (! $files) { // If no files are provided, return early.
            return;
        }

        foreach ($files as $key => $file) { // Loop through the files array.
            if (is_array($file)) { // If the file is an array (e.g., multiple uploads), process it recursively.
                $files[$key] = $this->filterFiles($files[$key]);
            }

            if (empty($files[$key])) { // Remove entries that are empty.
                unset($files[$key]);
            }
        }

        // Return the filtered files array.
        return $files;
    }

    /**
     * Check if an input element is set on the request.
     *
     * Determines if a specific key exists in the request data.
     *
     * @param  string  $key The key to check.
     *
     * @return bool True if the key exists, false otherwise.
     */
    public function __isset($key)
    {
        // Returns true if the key is not null.
        return $this->__get($key) !== null;
    }

    /**
     * Get an input element from the request.
     *
     * Retrieves the value of a specific key from the request data.
     *
     * @param  string  $key The key to retrieve.
     *
     * @return mixed The value of the specified key.
     */
    public function __get($key)
    {
        // Fetches the value using a helper function.
        return Arr::get($this->all(), $key);
    }
}
