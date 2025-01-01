<?php

declare(strict_types=1);

namespace Maginium\Framework\Request\Interfaces;

use Magento\Customer\Model\Customer as MagentoCustomer;
use Magento\Framework\App\RequestInterface as BaseRequestInterface;
use Magento\User\Model\User;
use Maginium\Customer\Models\Customer;
use Maginium\Framework\Support\Collection;
use Maginium\Framework\Support\Stringable;

/**
 * Interface for an enhanced HTTP request with extended capabilities.
 *
 * Extends Magento's base HTTP request functionality to include:
 * - User and language state management.
 * - JSON request body parsing and interaction.
 * - Handling of content types and MIME formats.
 */
interface RequestInterface extends BaseRequestInterface
{
    /**
     * Retrieves the MIME types associated with a specific format.
     *
     * This method checks the available formats and returns the corresponding MIME types
     * for a given format. If the formats are not initialized, it will initialize them first.
     *
     * @param string $format The format (e.g., "json", "html") whose MIME types are needed.
     *
     * @return string[] An array of MIME types associated with the given format.
     * If the format is not recognized, an empty array is returned.
     */
    public static function getMimeTypes(string $format): array;

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
    public static function matchesType(string $actual, string $type): bool;

    /**
     * Retrieve the query string parameter from the request.
     * This method fetches a value from the query string based on the provided key.
     *
     * @param ?string $key The key of the query string parameter.
     * @param ?mixed $default The default value to return if the key does not exist.
     *
     * @return mixed|ParametersInterface The value of the query parameter or default value if not found.
     */
    public function query($key = null, $default = null): mixed;

    /**
     * Merge new input into the current request's input array.
     *
     * @param  array  $input
     *
     * @return $this
     */
    public function merge(array $input);

    /**
     * Check if code declared as direct access frontend name.
     *
     * This means what this url can be used without store code.
     *
     * @param   string $code
     *
     * @return  bool
     */
    public function isDirectAccessFrontendName($code);

    /**
     * Set a query string parameter for the current request.
     * This method allows setting a value for a specific query parameter key.
     *
     * @param string $key The key of the query string parameter.
     * @param mixed $value The value to set for the query parameter.
     *
     * @return $this
     */
    public function setParam(string $key, $value): self;

    /**
     * Set flag indicating whether or not request has been dispatched.
     *
     * @param bool $flag
     *
     * @return RequestInterface
     */
    public function setDispatched($flag = true);

    /**
     * Gets the current user associated with the request.
     *
     * This method retrieves the user that was set via the setUser method.
     * If no user is set, it returns null.
     *
     * @return Customer|User|null The current user object associated with the request, or null if not set.
     */
    public function user(): mixed;

    /**
     * Sets the current user for the request.
     *
     * This method allows you to assign a user (either a Customer or User model) to the
     * request object, enabling further functionality like user-based processing.
     *
     * @param MagentoCustomer|Customer|User|null $user The user object to set as the current user, or null to reset.
     */
    public function setUser(MagentoCustomer|Customer|User|null $user): void;

    /**
     * Sets the language or locale for the request.
     *
     * This method allows you to set the language/locale that will be used for the request,
     * such as "en" for English, "fr" for French, etc.
     *
     * @param string $language The language code (e.g., "en", "fr") to set.
     */
    public function setLanguage(string $language): void;

    /**
     * Gets the current language or locale for the request.
     *
     * This method retrieves the language or locale that was set via the setLanguage method.
     * If no language is set, it returns null.
     *
     * @return string|null The current language/locale, or null if not set.
     */
    public function getLanguage(): ?string;

    /**
     * Resets the state of the request, including user and language.
     *
     * This method clears any data associated with the request, including the user
     * and language settings. It also calls the parent `_resetState` method to reset
     * the base state of the request.
     */
    public function _resetState(): void;

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
    public function json(mixed $default = null): mixed;

    /**
     * Retrieve a specific header from the request.
     * If the header does not exist, the provided default value will be returned.
     *
     * @param string $key The header key.
     * @param mixed $default The default value to return if the header does not exist.
     *
     * @return mixed The header value, or the default value if the header is not found.
     */
    public function header(string $key, mixed $default = null): mixed;

    /**
     * Set an HTTP header for the current request.
     * This method allows setting a specific header key and its value.
     *
     * @param string $key The header key.
     * @param string $value The value of the header.
     *
     * @return $this
     */
    public function setHeader(string $key, string $value): self;

    /**
     * Retrieves all headers from the request.
     *
     * This method returns an associative array containing all the headers from the
     * incoming HTTP request. The headers are returned as key-value pairs.
     *
     * @return array<string, string[]> An associative array of request headers, with each
     *                                header name as the key and the values as an array.
     */
    public function headers(): array;

    /**
     * Retrieves the parsed body of the request.
     *
     * This method returns the processed (decoded) content of the request body.
     * The content is expected to be JSON or another format that has been decoded
     * into a native PHP structure (array or object).
     *
     * @return mixed The parsed body data, such as an array or object from a JSON request body.
     */
    public function getBody(): mixed;

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
    public function setBody($value): self;

    /**
     * Retrieves the raw body of the request as a string.
     *
     * This method returns the entire raw content of the HTTP request body as a string.
     * It is useful for handling raw data, such as JSON payloads, form data, or binary streams.
     *
     * @return string The raw request body content.
     */
    public function getRawBody(): string;

    /**
     * Retrieves a list of acceptable content types from the client request.
     *
     * This method extracts the `Accept` header from the HTTP request and parses it to determine
     * which MIME types the client prefers. The types are returned in order of preference, as specified
     * by the client.
     *
     * @return string[] An array of acceptable MIME types, ordered by client preference.
     */
    public function getAcceptableContentTypes(): array;

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
    public function getMimeType(string $format): ?string;

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
    public function getFormat(?string $mimeType): ?string;

    /**
     * Return the Request instance.
     *
     * @return $this
     */
    public function instance();

    /**
     * Get the request method.
     *
     * @return string
     */
    public function method();

    /**
     * Get the root URL for the application.
     *
     * @return string
     */
    public function root();

    /**
     * Get the full URL (including protocol, host, and path) for the request, excluding the query string.
     *
     * @return string
     */
    public function url();

    /**
     * Get the full URL for the request.
     *
     * @return string
     */
    public function fullUrl();

    /**
     * Get the full URL for the request with the added query string parameters.
     *
     * @param  array  $query
     *
     * @return string
     */
    public function fullUrlWithQuery(array $query);

    /**
     * Get the full URL for the request without the given query string parameters.
     *
     * @param  array|string  $keys
     *
     * @return string
     */
    public function fullUrlWithoutQuery($keys);

    /**
     * Get the current path info for the request.
     *
     * @return string
     */
    public function path();

    /**
     * Get the current decoded path info for the request.
     *
     * @return string
     */
    public function decodedPath();

    /**
     * Get a segment from the URI (1 based index).
     *
     * @param  int  $index
     * @param  string|null  $default
     *
     * @return string|null
     */
    public function segment($index, $default = null);

    /**
     * Get all of the segments for the request path.
     *
     * @return array
     */
    public function segments();

    /**
     * Determine if the current request URI matches a pattern.
     *
     * @param  mixed  ...$patterns
     *
     * @return bool
     */
    public function is(...$patterns);

    /**
     * Determine if the current request URL and query string match a pattern.
     *
     * @param  mixed  ...$patterns
     *
     * @return bool
     */
    public function fullUrlIs(...$patterns);

    /**
     * Get the host name.
     *
     * @return string
     */
    public function host();

    /**
     * Get the HTTP host being requested.
     *
     * @return string
     */
    public function httpHost();

    /**
     * Get the scheme and HTTP host.
     *
     * @return string
     */
    public function schemeAndHttpHost();

    /**
     * Determine if the request is the result of a prefetch call.
     *
     * @return bool
     */
    public function prefetch();

    /**
     * Determine if the request is over HTTPS.
     *
     * @return bool
     */
    public function secure();

    /**
     * Get the client IP address.
     *
     * @return string|null
     */
    public function ip();

    /**
     * Get the client user agent.
     *
     * @return string|null
     */
    public function userAgent();

    /**
     * Merge new input into the request's input, but only when that key is missing from the request.
     *
     * @param  array  $input
     *
     * @return $this
     */
    public function mergeIfMissing(array $input);

    /**
     * Replace the input values for the current request.
     *
     * @param  array  $input
     *
     * @return $this
     */
    public function replace(array $input);

    /**
     * Get all of the input and files for the request.
     *
     * @return array
     */
    public function toArray(): array;

    /**
     * Determine if the given offset exists.
     *
     * @param  string  $offset
     *
     * @return bool
     */
    public function offsetExists($offset): bool;

    /**
     * Get the value at the given offset.
     *
     * @param  string  $offset
     *
     * @return mixed
     */
    public function offsetGet($offset): mixed;

    /**
     * Set the value at the given offset.
     *
     * @param  string  $offset
     * @param  mixed  $value
     *
     * @return void
     */
    public function offsetSet($offset, $value): void;

    /**
     * Remove the value at the given offset.
     *
     * @param  string  $offset
     *
     * @return void
     */
    public function offsetUnset($offset): void;

    /**
     * Generates the normalized query string for the Request.
     *
     * It builds a normalized query string, where keys/value pairs are alphabetized
     * and have consistent escaping.
     */
    public function getQueryString(): ?string;

    /**
     * Retrieve input values from the request.
     * If a specific key is provided, it returns the value for that key.
     * If no key is provided, it returns all input values.
     *
     * @param string|null $key The key of the input value. If null, returns all inputs.
     * @param mixed $default The default value to return if the key does not exist.
     *
     * @return mixed The value of the input key, all inputs if key is null, or default value if key is not found.
     */
    public function input(?string $key = null, $default = null): mixed;

    /**
     * Get all of the input and files for the request.
     *
     * This method combines both input data (e.g., form fields) and files (e.g., uploaded files)
     * into a single array. If a specific set of keys is provided, it returns only those keys
     * from the combined input and files. If no keys are provided, it returns all of the combined data.
     *
     * @param  array|mixed|null  $keys The specific keys to retrieve from the input and files data.
     *                                 If null or no keys are provided, all data will be returned.
     *
     * @return array The combined input and file data for the request, or the specific keys if provided.
     */
    public function all($keys = null);

    /**
     * Retrieve all cookies from the request.
     *
     * This method retrieves all cookies sent in the request. It can be used to access
     * any cookie that is included in the request header.
     *
     * @return array An associative array of cookies where the keys are cookie names
     *               and the values are their corresponding cookie values.
     */
    public function cookies();

    /**
     * Retrieve a specific cookie from the request.
     * If the cookie does not exist, the provided default value will be returned.
     *
     * @param string $key The cookie key.
     * @param mixed $default The default value to return if the cookie does not exist.
     *
     * @return mixed The cookie value, or the default value if the cookie is not found.
     */
    public function cookie(string $key, mixed $default = null): mixed;

    /**
     * Retrieve a server variable from the request.
     * This method allows retrieval of server variables, such as `$_SERVER`.
     *
     * @param string|null $key The server variable key.
     * @param string|array|null $default The default value if the key does not exist.
     *
     * @return string|array|null The server variable value, or the default value if not found.
     */
    public function server($key = null, $default = null);

    /**
     * Determine if a specific header is set in the request.
     * This checks whether the header key exists in the request.
     *
     * @param string $key The header key.
     *
     * @return bool Returns true if the header is set, false otherwise.
     */
    public function hasHeader($key): bool;

    /**
     * Retrieve the bearer token from the Authorization header.
     * This method is used to extract the bearer token from the `Authorization` header if present.
     *
     * @return string|null The bearer token if found, otherwise null.
     */
    public function bearerToken(): ?string;

    /**
     * Determine if the request contains a specific input key.
     * This method checks if the specified input key exists in the request's input data.
     *
     * @param string|array $key The key or keys to check for in the request input.
     *
     * @return bool Returns true if the key(s) exist in the input, false otherwise.
     */
    public function exists($key): bool;

    /**
     * Determine if the request contains a specific input key.
     * This method checks if the input key exists within the request's input data.
     * It can handle both single and multiple keys.
     *
     * @param string|array $key The key or keys to check for in the request input.
     *
     * @return bool Returns true if the key(s) exist in the input, false otherwise.
     */
    public function has($key): bool;

    /**
     * Determine if the request contains any of the given inputs.
     *
     * This method checks if any of the specified input keys exist in the request's data.
     * It can accept a single key or an array of keys.
     *
     * @param  string|array  $keys A single key or an array of keys to check in the request.
     *
     * @return bool True if any of the input keys exist, otherwise false.
     */
    public function hasAny($keys);

    /**
     * Apply the callback if the request contains the given input item key.
     *
     * This method executes the provided callback if the specified input key exists in the request.
     * If the key is missing, an optional default callback can be executed.
     *
     * @param  string  $key The input key to check in the request.
     * @param  callable  $callback The callback to execute if the key exists.
     * @param  callable|null  $default An optional callback to execute if the key does not exist.
     *
     * @return $this|mixed The current instance or the result of the callback.
     */
    public function whenHas($key, callable $callback, ?callable $default = null);

    /**
     * Determine if the request contains a non-empty value for an input item.
     *
     * This method checks if a specified input key exists in the request and has a non-empty value.
     * It can handle a single key or an array of keys.
     *
     * @param  string|array  $key The key(s) to check for a non-empty value in the request.
     *
     * @return bool True if the input key(s) contain non-empty values, otherwise false.
     */
    public function filled($key);

    /**
     * Determine if the request contains an empty value for an input item.
     *
     * This method checks if a specified input key exists in the request and has an empty value.
     * It can handle a single key or an array of keys.
     *
     * @param  string|array  $key The key(s) to check for an empty value in the request.
     *
     * @return bool True if the input key(s) contain empty values, otherwise false.
     */
    public function isNotFilled($key);

    /**
     * Determine if the request contains a non-empty value for any of the given inputs.
     *
     * This method checks if any of the specified input keys have a non-empty value in the request.
     * It can handle a single key or an array of keys.
     *
     * @param  string|array  $keys The key(s) to check for a non-empty value.
     *
     * @return bool True if any of the input keys contain non-empty values, otherwise false.
     */
    public function anyFilled($keys);

    /**
     * Apply the callback if the request contains a non-empty value for the given input item key.
     *
     * This method executes the provided callback if the specified key exists in the request and contains a non-empty value.
     * If the key is empty, an optional default callback can be executed.
     *
     * @param  string  $key The input key to check for a non-empty value.
     * @param  callable  $callback The callback to execute if the key contains a non-empty value.
     * @param  callable|null  $default An optional callback to execute if the key is empty.
     *
     * @return $this|mixed The current instance or the result of the callback.
     */
    public function whenFilled($key, callable $callback, ?callable $default = null);

    /**
     * Determine if the request is missing a given input item key.
     *
     * This method checks if the specified input key is absent from the request.
     * It can handle a single key or an array of keys.
     *
     * @param  string|array  $key The key(s) to check for absence in the request.
     *
     * @return bool True if the key(s) are missing, otherwise false.
     */
    public function missing($key);

    /**
     * Apply the callback if the request is missing the given input item key.
     *
     * This method executes the provided callback if the specified input key is missing from the request.
     * If the key is present, an optional default callback can be executed.
     *
     * @param  string  $key The input key to check for absence.
     * @param  callable  $callback The callback to execute if the key is missing.
     * @param  callable|null  $default An optional callback to execute if the key is present.
     *
     * @return $this|mixed The current instance or the result of the callback.
     */
    public function whenMissing($key, callable $callback, ?callable $default = null);

    /**
     * Get the keys for all of the input and files.
     *
     * This method merges the keys from both the input data and files
     * associated with the request to return all available keys. It combines
     * the keys from the input data (such as form fields) and file inputs.
     *
     * @return array The keys of all input and files.
     */
    public function keys();

    /**
     * Retrieve input from the request as a Stringable instance.
     *
     * This method fetches input associated with the given key and
     * converts it into a Stringable instance, allowing chainable
     * string operations. If the input is not found, the provided
     * default value is returned.
     *
     * @param  string  $key The key to retrieve input for.
     * @param  mixed  $default The default value to return if the key is not found.
     *
     * @return Stringable The input as a Stringable instance.
     */
    public function str($key, $default = null);

    /**
     * Retrieve input from the request as a Stringable instance.
     *
     * Similar to the `str` method, this retrieves the input associated
     * with the given key and converts it into a Stringable instance.
     *
     * @param  string  $key The key to retrieve input for.
     * @param  mixed  $default The default value to return if the key is not found.
     *
     * @return Stringable The input as a Stringable instance.
     */
    public function string($key, $default = null);

    /**
     * Retrieve input as a boolean value.
     *
     * This method interprets the input value as a boolean. It returns true
     * for values such as "1", "true", "on", and "yes", and false for other
     * values or when the input is not present.
     *
     * @param  string|null  $key The key to retrieve input for.
     * @param  bool  $default The default boolean value if the key is not found.
     *
     * @return bool The interpreted boolean value.
     */
    public function boolean($key = null, $default = false);

    /**
     * Retrieve input as an integer value.
     *
     * This method converts the input value associated with the given key
     * into an integer. If the value is not present, the provided default
     * integer value is returned.
     *
     * @param  string  $key The key to retrieve input for.
     * @param  int  $default The default integer value if the key is not found.
     *
     * @return int The input as an integer.
     */
    public function integer($key, $default = 0);

    /**
     * Retrieve input as a float value.
     *
     * This method converts the input value associated with the given key
     * into a float. If the value is not present, the provided default
     * float value is returned.
     *
     * @param  string  $key The key to retrieve input for.
     * @param  float  $default The default float value if the key is not found.
     *
     * @return float The input as a float.
     */
    public function float($key, $default = 0.0);

    /**
     * Retrieve input from the request as a Carbon instance.
     *
     * This method converts the input value associated with the given key
     * into a Carbon instance (date/time). The format and timezone can
     * be specified, or the default format will be used. If the input is
     * empty, null is returned.
     *
     * @param  string  $key The key to retrieve input for.
     * @param  string|null  $format The date format to use (optional).
     * @param  string|null  $tz The timezone to use (optional).
     *
     * @throws InvalidFormatException If the format is invalid.
     *
     * @return Carbon|null The input as a Carbon instance, or null if empty.
     */
    public function date($key, $format = null, $tz = null);

    /**
     * Retrieve input from the request as an enum.
     *
     * This method attempts to convert the input value into an enum instance.
     * The enum class must implement the `tryFrom` method, and the input
     * value should correspond to one of the enum cases. If any condition
     * is not met, it returns null.
     *
     * @template TEnum The enum class type.
     *
     * @param  string  $key The key to retrieve input for.
     * @param  class-string<TEnum>  $enumClass The enum class to map the input value to.
     *
     * @return TEnum|null The input as an enum instance, or null if conversion fails.
     */
    public function enum($key, $enumClass);

    /**
     * Retrieve input from the request as a collection.
     *
     * This method converts the input into a Collection instance. It can
     * retrieve a specific key's input or, if no key is provided, return
     * all input as a collection.
     *
     * @param  array|string|null  $key The key(s) to retrieve input for, or null for all input.
     *
     * @return Collection The input as a collection.
     */
    public function collect($key = null);

    /**
     * Get a subset containing the provided keys with values from the input data.
     *
     * This method retrieves a subset of the input array, containing only
     * the values for the specified keys. If a key is not present, it is
     * excluded from the result. Useful for filtering out unwanted keys.
     *
     * @param  array|mixed  $keys The keys of the input data to retrieve.
     *
     * @return array The filtered input data with only the specified keys.
     */
    public function only($keys);

    /**
     * Get all of the input except for a specified array of items.
     *
     * This method retrieves all input values, but excludes the ones specified
     * by the `$keys` parameter. This can be useful when you need to process
     * a subset of the input data.
     *
     * @param  array|mixed  $keys The keys of the input data to exclude.
     *
     * @return array The filtered input data excluding the specified keys.
     */
    public function except($keys);

    /**
     * Retrieve a request payload item from the request.
     *
     * This method fetches an item from the request's payload. If a `$key`
     * is provided, it retrieves the corresponding value. Otherwise, it returns
     * all of the request's payload.
     *
     * @param  string|null  $key The key to retrieve from the request (optional).
     * @param  string|array|null  $default The default value to return if the key is not found (optional).
     *
     * @return string|array|null The retrieved payload item or the default value.
     */
    public function post($key = null, $default = null);

    /**
     * Determine if a cookie is set on the request.
     *
     * This method checks if a cookie with the specified `$key` exists in the request.
     *
     * @param  string  $key The key of the cookie to check.
     *
     * @return bool Returns true if the cookie exists, false otherwise.
     */
    public function hasCookie($key);

    /**
     * Get an array of all of the files on the request.
     *
     * This method retrieves all files associated with the request. It ensures
     * that the files are converted into the proper format if needed.
     *
     * @return array The list of all uploaded files in the request.
     */
    public function allFiles();

    /**
     * Determine if the uploaded data contains a file.
     *
     * This method checks if the request contains files. If the provided `$key`
     * corresponds to a file or a set of files, it will return true if at least
     * one valid file is found.
     *
     * @param  string  $key The key to check for a file in the request.
     *
     * @return bool Returns true if a valid file is uploaded, false otherwise.
     */
    public function hasFile($key);

    /**
     * Retrieve a file from the request.
     *
     * This method retrieves a file or a set of files associated with the given
     * `$key` from the request. It can return a single file or an array of files,
     * depending on the request.
     *
     * @param  string|null  $key The key of the file(s) to retrieve.
     * @param  mixed  $default The default value to return if no file is found.
     *
     * @return UploadedFile|\Maginium\Framework\Request\UploadedFile[]|array|null The retrieved file(s).
     */
    public function file($key = null, $default = null);

    /**
     * Dump the items.
     *
     * This method is used for debugging purposes. It dumps the values for the
     * specified keys, or all of the input data if no keys are provided.
     *
     * @param  mixed  $keys The keys of the data to dump.
     *
     * @return $this The current instance for method chaining.
     */
    public function dump($keys = []);

    /**
     * Determines if the request is sending JSON.
     *
     * This method checks the `Content-Type` header of the request to see if it indicates JSON content.
     * It supports both exact matches and types with suffixes (e.g., "application/json" or "application/*+json").
     *
     * @return bool True if the request is sending JSON, false otherwise.
     */
    public function isJson(): bool;

    /**
     * Determines if the current request probably expects a JSON response.
     *
     * This method checks if the request can accept JSON responses. It considers both the `Accept` header
     * and whether the client explicitly requests JSON.
     *
     * @return bool True if the request expects a JSON response, false otherwise.
     */
    public function expectsJson(): bool;

    /**
     * Determines if the current request is asking for JSON.
     *
     * This method inspects the `Accept` header to determine if the client is asking for a JSON response.
     *
     * @return bool True if the request is asking for JSON, false otherwise.
     */
    public function wantsJson(): bool;

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
    public function accepts($contentTypes): bool;

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
    public function prefers($contentTypes);

    /**
     * Determines if the current request accepts any content type.
     *
     * This method checks if the `Accept` header of the request
     * that any content type is acceptable.
     *
     * @return bool True if the request accepts any content type, false otherwise.
     */
    public function f(): bool;

    /**
     * Determines whether a request accepts JSON.
     *
     * This method is a shorthand for checking if the request accepts "application/json".
     *
     * @return bool True if the request accepts JSON, false otherwise.
     */
    public function acceptsJson(): bool;

    /**
     * Determines whether a request accepts HTML.
     *
     * This method is a shorthand for checking if the request accepts "text/html".
     *
     * @return bool True if the request accepts HTML, false otherwise.
     */
    public function acceptsHtml(): bool;

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
    public function format(string $default = 'html'): string;

    /**
     * Filters the given array of rules based on the `Precognition-Validate-Only` header.
     *
     * This method checks if the `Precognition-Validate-Only` header is present and filters
     * the provided validation rules accordingly. If the header is absent, the original rules are returned.
     *
     * @param array<string, mixed> $rules The array of validation rules to filter.
     *
     * @return array<string, mixed> The filtered array of rules.
     */
    public function filterPrecognitiveRules(array $rules): array;

    /**
     * Determines if the request is attempting to be precognitive.
     *
     * A request is considered to be "attempting precognition" if it includes a `Precognition` header
     * explicitly set to "true".
     *
     * @return bool True if the request is attempting precognition, false otherwise.
     */
    public function isAttemptingPrecognition(): bool;

    /**
     * Determines if the request is precognitive.
     *
     * This method checks if the `precognitive` attribute is set in the request's attributes.
     * The attribute is typically set during request processing to identify precognitive requests.
     *
     * @return bool True if the request is precognitive, false otherwise.
     */
    public function isPrecognitive(): bool;
}
