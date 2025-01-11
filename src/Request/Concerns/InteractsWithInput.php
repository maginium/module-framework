<?php

declare(strict_types=1);

namespace Maginium\Framework\Request\Concerns;

use Carbon\Exceptions\InvalidFormatException;
use Maginium\Framework\Request\Interfaces\RequestInterface;
use Maginium\Framework\Request\UploadedFile;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Carbon;
use Maginium\Framework\Support\Collection;
use Maginium\Framework\Support\Facades\Date;
use Maginium\Framework\Support\Facades\Json;
use Maginium\Framework\Support\Stringable;
use Maginium\Framework\Support\Traits\Dumpable;
use Maginium\Framework\Support\Validator;
use SplFileInfo;
use stdClass;

trait InteractsWithInput
{
    // Add dumping functions
    use Dumpable;

    /**
     * Retrieve input values from the request.
     * If a specific key is provided, it returns the value for that key.
     * If no key is provided, it returns all input values.
     *
     * Special handling for Magento 2's `formkey` input: skips decoding.
     *
     * @param string|null $key The key of the input value. If null, returns all inputs.
     * @param mixed $default The default value to return if the key does not exist.
     *
     * @return mixed The value of the input key, all inputs if key is null, or default value if key is not found.
     */
    public function input(?string $key = null, $default = null): mixed
    {
        // Check if the key is specifically `formkey` (Magento 2)
        if ($key === 'formkey') {
            // Get raw input directly for `formkey`, or return the default if empty
            $rawInput = $this->getContent();

            return Validator::isEmpty($rawInput) ? $default : $rawInput;
        }

        // Get the raw body content of the request
        $content = $this->getContent();

        // If content is empty, return null
        if (Validator::isEmpty($content)) {
            return null;
        }

        // Decode the content if it's not already an array
        if (! Validator::isArray($content)) {
            // Attempt to decode JSON if it's not an array
            $content = Json::decode($content);
        }

        // Ensure that the content is properly decoded and is an array
        if (! Validator::isArray($content)) {
            // Return default value if content is not an array
            return $default;
        }

        // Convert the content into a collection for easier manipulation (optional, but helps with Laravel-style collections)
        $input = Collection::make($content);

        // If no specific key is provided, return all input data as an array
        if ($key === null) {
            return $input->toArray(); // Return all input data as an array
        }

        // If a key is provided, return the value of the key, or the default value if not found
        return $input->get($key, $default);
    }

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
    public function all($keys = null): mixed
    {
        // Combine input data (from $this->input()) and file data (from $this->allFiles()) into one array.
        // $input = \Maginium\Framework\Support\Arr::replaceRecursive($this->input(), $this->allFiles());
        $input = $this->input();

        // If no specific keys are provided, return all combined data.
        if (! $keys) {
            return $input;
        }

        // Initialize an empty array to store the results for the specified keys.
        $results = [];

        // Loop through the keys to retrieve the corresponding values from the combined input and file data.
        // If $keys is not an array, convert it into an array using func_get_args().
        foreach (Validator::isArray($keys) ? $keys : func_get_args() as $key) {
            // Retrieve the value associated with the key and set it in the results array.
            Arr::set($results, $key, Arr::get($input, $key));
        }

        // Return the results containing only the specified keys, or the entire input data if no keys were specified.
        return $results;
    }

    /**
     * Retrieve a specific header from the request.
     * If the header does not exist, the provided default value will be returned.
     *
     * @param string $key The header key.
     * @param mixed $default The default value to return if the header does not exist.
     *
     * @return mixed The header value, or the default value if the header is not found.
     */
    public function header(string $key, mixed $default = null): mixed
    {
        return $this->getHeader($key, $default);
    }

    /**
     * Set an HTTP header for the current request.
     * This method allows setting a specific header key and its value.
     *
     * @param string $key The header key.
     * @param string $value The value of the header.
     *
     * @return $this
     */
    public function setHeader(string $key, string $value): RequestInterface
    {
        $this->getHeaders()->addHeaderLine($key, $value);

        return $this;
    }

    /**
     * Retrieve all cookies from the request.
     *
     * This method retrieves all cookies sent in the request. It parses the "Cookie" header
     * into an associative array where the keys are cookie names and the values are cookie values.
     *
     * @return array An associative array of cookies where the keys are cookie names
     *               and the values are their corresponding cookie values.
     */
    public function cookies(): array
    {
        $cookies = [];

        // Retrieve the raw Cookie header string
        $cookieHeader = $this->header('Cookie', '');

        // If the Cookie header is not empty, parse it into an associative array
        if (! empty($cookieHeader)) {
            // Split cookies by ';' delimiter
            $cookiePairs = explode(';', $cookieHeader);

            // Loop through each cookie and split by '=' to get key-value pairs
            foreach ($cookiePairs as $cookiePair) {
                $cookiePair = trim($cookiePair); // Remove extra spaces
                [$key, $value] = explode('=', $cookiePair, 2);
                $cookies[$key] = $value;
            }
        }

        return $cookies;
    }

    /**
     * Retrieve a specific cookie from the request.
     * If the cookie does not exist, the provided default value will be returned.
     *
     * @param string $key The cookie key.
     * @param mixed $default The default value to return if the cookie does not exist.
     *
     * @return mixed The cookie value, or the default value if the cookie is not found.
     */
    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->getCookie($key, $default);
    }

    /**
     * Retrieve a server variable from the request.
     * This method allows retrieval of server variables, such as `$_SERVER`.
     *
     * @param string|null $key The server variable key.
     * @param string|array|null $default The default value if the key does not exist.
     *
     * @return string|array|null The server variable value, or the default value if not found.
     */
    public function server($key = null, $default = null): mixed
    {
        return $this->retrieveItem('server', $key, $default);
    }

    /**
     * Determine if a specific header is set in the request.
     * This checks whether the header key exists in the request.
     *
     * @param string $key The header key.
     *
     * @return bool Returns true if the header is set, false otherwise.
     */
    public function hasHeader($key): bool
    {
        return $this->header($key) !== null;
    }

    /**
     * Retrieve the bearer token from the Authorization header.
     * This method is used to extract the bearer token from the `Authorization` header if present.
     *
     * @return string|null The bearer token if found, otherwise null.
     */
    public function bearerToken(): ?string
    {
        // Retrieve the "Authorization" header value
        $header = $this->header('Authorization', '');

        // Find the position of "Bearer " in the header value
        $position = mb_strrpos($header, 'Bearer ');

        // If "Bearer " is found in the header, extract the token
        if ($position !== false) {
            $header = mb_substr($header, $position + 7);

            // If there's a comma in the token, return the part before it
            return str_contains($header, ',') ? mb_strstr($header, ',', true) : $header;
        }

        // If no bearer token is found, return null
        return null;
    }

    /**
     * Determine if the request contains a specific input key.
     * This method checks if the specified input key exists in the request's input data.
     *
     * @param string|array $key The key or keys to check for in the request input.
     *
     * @return bool Returns true if the key(s) exist in the input, false otherwise.
     */
    public function exists($key): bool
    {
        return $this->has($key);
    }

    /**
     * Determine if the request contains a specific input key.
     * This method checks if the input key exists within the request's input data.
     * It can handle both single and multiple keys.
     *
     * @param string|array $key The key or keys to check for in the request input.
     *
     * @return bool Returns true if the key(s) exist in the input, false otherwise.
     */
    public function has($key): bool
    {
        // If multiple keys are passed, treat them as an array
        $keys = Validator::isArray($key) ? $key : func_get_args();

        // Retrieve all request input data
        $input = $this->all();

        // Iterate through each key and check if it exists in the input
        foreach ($keys as $value) {
            // If any key is missing, return false
            if (! Arr::has($input, $value)) {
                return false;
            }
        }

        // Return true if all keys are found
        return true;
    }

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
    public function hasAny($keys): bool
    {
        // If $keys is a single value, convert it into an array of keys.
        $keys = Validator::isArray($keys) ? $keys : func_get_args();

        // Retrieve all the request input.
        $input = $this->all();

        // Use the helper function to check if any of the keys are present in the input.
        return Arr::hasAny($input, $keys);
    }

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
    public function whenHas($key, callable $callback, ?callable $default = null): mixed
    {
        // Check if the given key exists in the request input.
        if ($this->has($key)) {
            // Call the callback with the value of the key.
            return $callback(data_get($this->all(), $key)) ?: $this;
        }

        // If the key does not exist and a default callback is provided, call the default callback.
        if ($default) {
            return $default();
        }

        // If neither the key exists nor a default is provided, return the current instance.
        return $this;
    }

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
    public function filled($key): bool
    {
        // If the argument is an array, check each key; otherwise, use the arguments passed.
        $keys = Validator::isArray($key) ? $key : func_get_args();

        // Iterate over each key and check if it's non-empty.
        foreach ($keys as $value) {
            if ($this->isEmptyString($value)) {
                return false;
            }
        }

        return true;
    }

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
    public function isNotFilled($key): bool
    {
        // If the argument is an array, check each key; otherwise, use the arguments passed.
        $keys = Validator::isArray($key) ? $key : func_get_args();

        // Iterate over each key and check if it's not empty.
        foreach ($keys as $value) {
            if (! $this->isEmptyString($value)) {
                return false;
            }
        }

        return true;
    }

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
    public function anyFilled($keys): bool
    {
        // If the argument is a single value, convert it into an array of keys.
        $keys = Validator::isArray($keys) ? $keys : func_get_args();

        // Iterate over each key and check if any one of them has a non-empty value.
        foreach ($keys as $key) {
            if ($this->filled($key)) {
                return true;
            }
        }

        return false;
    }

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
    public function whenFilled($key, callable $callback, ?callable $default = null): mixed
    {
        // Check if the key has a non-empty value.
        if ($this->filled($key)) {
            // Call the callback with the value of the key.
            return $callback(data_get($this->all(), $key)) ?: $this;
        }

        // If the key is empty and a default callback is provided, call the default callback.
        if ($default) {
            return $default();
        }

        // If the key is empty and no default callback is provided, return the current instance.
        return $this;
    }

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
    public function missing($key): bool
    {
        // If the argument is an array, check each key; otherwise, use the arguments passed.
        $keys = Validator::isArray($key) ? $key : func_get_args();

        // Check if the key(s) are missing in the request.
        return ! $this->has($keys);
    }

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
    public function whenMissing($key, callable $callback, ?callable $default = null): mixed
    {
        // Check if the key is missing from the request.
        if ($this->missing($key)) {
            // Call the callback with the value of the key (which will be null as it's missing).
            return $callback(data_get($this->all(), $key)) ?: $this;
        }

        // If the key is present and a default callback is provided, call the default callback.
        if ($default) {
            return $default();
        }

        // If the key is present and no default callback is provided, return the current instance.
        return $this;
    }

    /**
     * Get the keys for all of the input and files.
     *
     * This method merges the keys from both the input data and files
     * associated with the request to return all available keys. It combines
     * the keys from the input data (such as form fields) and file inputs.
     *
     * @return array The keys of all input and files.
     */
    public function keys(): array
    {
        // Merges the keys from input data and files, then returns them as a single array
        return Arr::merge(Arr::keys($this->input()), $this->files->keys());
    }

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
    public function str($key, $default = null): Stringable
    {
        // Delegates to the 'string' method, which also returns a Stringable instance
        return $this->string($key, $default);
    }

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
    public function string($key, $default = null): Stringable
    {
        // Converts the input to a Stringable instance using the global 'str' helper function
        return str($this->input($key, $default));
    }

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
    public function boolean($key = null, $default = false): bool
    {
        // Filters and validates the input value as a boolean using PHP's filter_var function
        return filter_var($this->input($key, $default), FILTER_VALIDATE_BOOLEAN);
    }

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
    public function integer($key, $default = 0): int
    {
        // Casts the input value to an integer, with the option for a default value
        return (int)$this->input($key, $default);
    }

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
    public function float($key, $default = 0.0): float
    {
        // Casts the input value to a float, with the option for a default value
        return (float)$this->input($key, $default);
    }

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
    public function date($key, $format = null, $tz = null): ?Carbon
    {
        // If no input for the key, return null
        if ($this->isNotFilled($key)) {
            return null;
        }

        // If a format is not provided, parse the input into a Carbon instance with the default format
        if ($format === null) {
            return Date::parse($this->input($key), $tz);
        }

        // If a format is provided, create the Carbon instance using the specified format
        return Date::createFromFormat($format, $this->input($key), $tz);
    }

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
    public function enum($key, $enumClass): mixed
    {
        // Check if the input exists, the enum class is valid, and the 'tryFrom' method exists
        if ($this->isNotFilled($key) ||
            ! enum_exists($enumClass) ||
            ! method_exists($enumClass, 'tryFrom')) {
            return null;
        }

        // Attempt to convert the input value to the corresponding enum instance
        return $enumClass::tryFrom($this->input($key));
    }

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
    public function collect($key = null): Collection
    {
        // If a key is provided, retrieve the input for that key or all input if no key is given
        return collect(Validator::isArray($key) ? $this->only($key) : $this->input($key));
    }

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
    public function only($keys): array
    {
        // Initialize an empty results array
        $results = [];

        // Get all input data
        $input = $this->all();

        // Placeholder object to mark missing values
        $placeholder = new stdClass;

        // Loop through the provided keys and filter the input
        foreach (Validator::isArray($keys) ? $keys : func_get_args() as $key) {
            // Get the value for the key, or use the placeholder if the key is not present
            $value = data_get($input, $key, $placeholder);

            // If the value is found, add it to the results array
            if ($value !== $placeholder) {
                Arr::set($results, $key, $value);
            }
        }

        // Return the filtered result
        return $results;
    }

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
    public function except($keys): array
    {
        // If $keys is not an array, convert it into an array.
        $keys = Validator::isArray($keys) ? $keys : func_get_args();

        // Retrieve all input data.
        $results = $this->all();

        // Remove the specified keys from the results.
        Arr::forget($results, $keys);

        // Return the filtered results.
        return $results;
    }

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
    public function post($key = null, $default = null): mixed
    {
        // Call a method to retrieve the item from the 'request' source.
        return $this->retrieveItem('request', $key, $default);
    }

    /**
     * Determine if a cookie is set on the request.
     *
     * This method checks if a cookie with the specified `$key` exists in the request.
     *
     * @param  string  $key The key of the cookie to check.
     *
     * @return bool Returns true if the cookie exists, false otherwise.
     */
    public function hasCookie($key): bool
    {
        // Check if the specified cookie is present by retrieving it and checking for null.
        return $this->cookie($key) !== null;
    }

    /**
     * Get an array of all of the files on the request.
     *
     * This method retrieves all files associated with the request. It ensures
     * that the files are converted into the proper format if needed.
     *
     * @return array The list of all uploaded files in the request.
     */
    public function allFiles(): array
    {
        // Retrieve all files from the request.
        $files = $this->files->all();

        // If files have not been converted yet, do so now.
        return $this->convertedFiles ??= $this->convertUploadedFiles($files);
    }

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
    public function hasFile($key): bool
    {
        // Retrieve files for the given key. If it's not an array, make it an array.
        if (! Validator::isArray($files = $this->file($key))) {
            $files = [$files];
        }

        // Iterate through the files to check if any are valid.
        foreach ($files as $file) {
            if ($this->isValidFile($file)) {
                return true;
            }
        }

        // Return false if no valid file is found.
        return false;
    }

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
     * @return UploadedFile|UploadedFile[]|array|null The retrieved file(s).
     */
    public function file($key = null, $default = null): mixed
    {
        // Retrieve the files corresponding to the given key, or return the default value if not found.
        return data_get($this->allFiles(), $key, $default);
    }

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
    public function dump($keys = []): static
    {
        // If $keys is not an array, convert it into an array.
        $keys = Validator::isArray($keys) ? $keys : func_get_args();

        // Dump the input data for the specified keys (or all data if no keys provided).
        dump(Validator::isEmpty($keys) ? $this->only($keys) : $this->all());

        // Return the current instance for chaining.
        return $this;
    }

    /**
     * Determine if the given input key is an empty string for "filled".
     *
     * This method checks whether the input associated with the given `$key`
     * is an empty string (after trimming), and returns true if it is empty.
     *
     * @param  string  $key The key to check for an empty string.
     *
     * @return bool Returns true if the value is an empty string, false otherwise.
     */
    protected function isEmptyString($key): bool
    {
        // Retrieve the value associated with the key from the input.
        $value = $this->input($key);

        // Check if the value is not a boolean or an array and if it is an empty string.
        return ! is_bool($value) && ! Validator::isArray($value) && trim((string)$value) === '';
    }

    /**
     * Check that the given file is a valid file instance.
     *
     * This method checks if the provided `$file` is an instance of `SplFileInfo`
     * and has a valid path, ensuring that it is a valid file.
     *
     * @param  mixed  $file The file to check.
     *
     * @return bool Returns true if the file is a valid `SplFileInfo` instance with a valid path.
     */
    protected function isValidFile($file): bool
    {
        // Check if the file is an instance of SplFileInfo and has a valid path.
        return $file instanceof SplFileInfo && $file->getPath() !== '';
    }
}
