<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Traits;

use Magento\Framework\Exception\LocalizedException;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Php;
use Maginium\Framework\Support\Validator;

/**
 * Universal data container with array access implementation.
 */
trait DataObject
{
    /**
     * Setter/Getter underscore transformation cache.
     *
     * @var array
     */
    protected static $_underscoreCache = [];

    /**
     * Stores the current key being worked with.
     *
     * @var string
     */
    protected $currentKey = null;

    /**
     * Object attributes.
     *
     * @var array
     */
    protected $_data = [];

    /**
     * Constructor.
     *
     * By default is looking for first argument as array and assigns it as object attributes
     * This behavior may change in child classes
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->_data = $data;
    }

    /**
     * Build and return the key dynamically by joining parts with a dot.
     *
     * @param mixed ...$keys The keys to be joined.
     *
     * @return $this
     */
    public function key(...$keys): self
    {
        // Join all parts of the key with a dot and store it
        $this->currentKey = implode('.', $keys);

        return $this;  // Allow method chaining
    }

    /**
     * Apply a callback function to each item in the data and return a new DataObject.
     *
     * This method iterates over each item in the DataObject, applies the
     * provided callback function, and returns a new instance with modified data.
     *
     * @param callable $callback The callback function to apply to each item.
     *                           The function should accept two parameters:
     *                           - The item value (as a DataObject instance)
     *                           - The item key
     *
     * @return self A new DataObject instance with the modified data.
     */
    public function each(callable $callback): self
    {
        $modifiedData = [];

        // Iterate through each item, wrapping it in DataObject if it's an array
        foreach ($this->getData() as $key => $value) {
            // Wrap each item in DataObject if it's an array
            if (Validator::isArray($value)) {
                $value = self::make($value);
            }

            // Apply the callback and capture the result
            $result = $callback($value, $key);

            // Only add non-null results to the modified data
            if ($result !== null) {
                $modifiedData[$key] = $result instanceof self ? $result->getData() : $result;
            }
        }

        // Return a new DataObject instance with the modified data
        return self::make($modifiedData);
    }

    /**
     * Checks if the specified key exists in the data, supporting dot notation.
     *
     * If $key is empty, checks whether there's any data in the object.
     * Otherwise checks if the specified attribute is set.
     *
     * @param string $key
     *
     * @return bool
     */
    public function hasData($key = '')
    {
        // If the key contains a dot, we need to check for nested keys
        if (str_contains($key, '.')) {
            // Process the key with dot notation (e.g., 'a.b.c') as nested keys
            return $this->getDataByDotNotation($key) !== null;
        }

        // Check if the key is empty or not a string, then check if the object has any data
        if (empty($key) || ! is_string($key)) {
            return ! empty($this->_data);
        }

        // Otherwise, check if the simple key exists in the data
        return Arr::keyExists($key, $this->_data);
    }

    /**
     * Retrieve data from the object.
     *
     * The $key parameter can be a string, an array of keys, or a dot-notation string to retrieve nested data.
     * If no $key is provided, the entire data object is returned.
     * If the $key is an array, it retrieves data for each key specified in the array.
     * If the $key contains dot notation (e.g., 'a.b.c'), it retrieves nested data.
     * The optional $index parameter retrieves a specific value within an array or string.
     *
     * @param string|array $key The key(s) to retrieve data from the object.
     * @param int|null $index The index to fetch a specific item from an array or string (optional).
     *
     * @return mixed The data corresponding to the provided key(s) or the entire data object if no key is provided.
     */
    public function getData($key = '', $index = null)
    {
        // If currentKey is set, prepend it to the provided key (if any)
        if ($this->currentKey !== null) {
            // If $key is not empty, concatenate currentKey and the provided $key
            $key = $key ? $this->currentKey . '.' . $key : $this->currentKey;

            // Reset currentKey to avoid using it again in subsequent operations
            $this->currentKey = null;
        }

        // If no key is provided, return the entire data object
        if ($key === '') {
            return $this->_data;
        }

        // If the key is an array, recursively retrieve data for each key in the array
        if (is_array($key)) {
            $result = [];

            // Loop through each key in the array and get the corresponding data
            foreach ($key as $k) {
                $result[$k] = $this->getData($k);
            }

            // Return the associative array of results for each key
            return $result;
        }

        // Try to retrieve data directly from the _data property using the key
        $data = $this->_data[$key] ?? null;

        // If no data is found for the key and the key contains a '/' (which implies nested keys)
        if ($data === null && str_contains($key, SP)) {
            // Process the key with slashes (e.g., 'a/b/c') as nested keys and retrieve the data
            $data = $this->getDataByPath($key);
        }

        // If no data is found for the key and the key contains a '.' (dot notation), process it as nested keys
        if ($data === null && str_contains($key, '.')) {
            // Process the key with dot notation (e.g., 'a.b.c') as nested keys and retrieve the data
            $data = $this->getDataByDotNotation($key);
        }

        // If an index is specified, process the data accordingly
        if ($index !== null) {
            // If the data is an array, return the element at the specified index
            if (is_array($data)) {
                $data = $data[$index] ?? null;
            }
            // If the data is a string, split it by new lines and return the element at the specified index
            elseif (is_string($data)) {
                $data = explode(PHP_EOL, $data);
                $data = $data[$index] ?? null;
            }
            // If the data is an instance of DataObject, recursively get the data for the index
            elseif ($data instanceof self) {
                $data = $data->getData($index);
            } else {
                // If none of the above conditions match, set data to null
                $data = null;
            }
        }

        // Return the data (could be a nested array or value) for the specified key
        return $data;
    }

    /**
     * Overwrite or merge data in the object.
     *
     * This method allows setting data in the object using a key-value pair.
     * It supports dot notation for nested keys, merges arrays when applicable,
     * and handles overwriting data when necessary.
     *
     * @param string|array $key The key for the data (or an array to overwrite all data).
     * @param mixed $value The value to assign to the specified key (optional if $key is an array).
     *
     * @return $this Returns the current instance for method chaining.
     */
    public function setData($key, $value = null)
    {
        // Use currentKey if it's already set, appending the new key if applicable
        if ($this->currentKey !== null) {
            $key = $this->currentKey . ($key ? '.' . $key : '');

            // Reset currentKey after use
            $this->currentKey = null;
        }

        // If $key is an array, completely overwrite the existing data
        if (is_array($key)) {
            $this->_data = $key;
        } else {
            // Handle dot notation in the key for nested data
            if (str_contains($key, '.')) {
                // Split the key into its parts
                $keys = explode('.', $key);

                // Extract the first key and the remaining path
                $firstKey = Arr::shift($keys);
                $remainingKey = implode('.', $keys);

                // Initialize the firstKey as an empty array if it doesn't exist
                if (! isset($this->_data[$firstKey])) {
                    $this->_data[$firstKey] = [];
                }

                // Recursively assign the nested data
                $this->_data[$firstKey] = $this->_setNestedData($this->_data[$firstKey], $remainingKey, $value);
            } else {
                // Handle non-nested keys: merge or directly assign the value
                if (isset($this->_data[$key]) && is_array($this->_data[$key]) && is_array($value)) {
                    // Merge existing data and new value if both are arrays
                    $this->_data[$key] = Php::mergeArrays($this->_data[$key], $value);
                } else {
                    // Overwrite the existing value or set a new one
                    $this->_data[$key] = $value;
                }
            }
        }

        // Allow method chaining
        return $this;
    }

    /**
     * Add data to the object.
     *
     * Retains previous data in the object.
     *
     * @param array $arr
     *
     * @return $this
     */
    public function addData(array $arr)
    {
        if ($this->_data === []) {
            $this->setData($arr);

            return $this;
        }

        foreach ($arr as $index => $value) {
            $this->setData($index, $value);
        }

        return $this;
    }

    /**
     * Unset data from the object.
     *
     * @param null|string|array $key
     *
     * @return $this
     */
    public function unsetData($key = null)
    {
        if ($key === null) {
            $this->setData([]);
        } elseif (is_string($key)) {
            if (isset($this->_data[$key]) || array_key_exists($key, $this->_data)) {
                unset($this->_data[$key]);
            }
        } elseif ($key === (array)$key) {
            foreach ($key as $element) {
                $this->unsetData($element);
            }
        }

        return $this;
    }

    /**
     * Get object data by path.
     *
     * Method consider the path as chain of keys: a/b/c => ['a']['b']['c']
     *
     * @param string $path
     *
     * @return mixed
     */
    public function getDataByPath($path)
    {
        $keys = explode('/', (string)$path);

        $data = $this->_data;

        foreach ($keys as $key) {
            if ((array)$data === $data && isset($data[$key])) {
                $data = $data[$key];
            } elseif ($data instanceof \Magento\Framework\DataObject) {
                $data = $data->getDataByKey($key);
            } else {
                return;
            }
        }

        return $data;
    }

    /**
     * Get object data by particular key.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getDataByKey($key)
    {
        return $this->_getData($key);
    }

    /**
     * Set object data with calling setter method.
     *
     * @param string $key
     * @param mixed $args
     *
     * @return $this
     */
    public function setDataUsingMethod($key, $args = [])
    {
        $method = 'set' . ($key !== null ? str_replace('_', '', ucwords($key, '_')) : '');
        $this->{$method}($args);

        return $this;
    }

    /**
     * Get object data by key with calling getter method.
     *
     * @param string $key
     * @param mixed $args
     *
     * @return mixed
     */
    public function getDataUsingMethod($key, $args = null)
    {
        $method = 'get' . ($key !== null ? str_replace('_', '', ucwords($key, '_')) : '');

        return $this->{$method}($args);
    }

    /**
     * Convert object data into string with predefined format.
     *
     * Will use $format as an template and substitute {{key}} for attributes
     *
     * @param string $format
     *
     * @return string
     */
    public function toString($format = '')
    {
        if (empty($format)) {
            $result = implode(', ', $this->getData());
        } else {
            preg_match_all('/\{\{([a-z0-9_]+)\}\}/is', $format, $matches);

            foreach ($matches[1] as $var) {
                $data = $this->getData($var) ?? '';
                $format = str_replace('{{' . $var . '}}', $data, $format);
            }
            $result = $format;
        }

        return $result;
    }

    /**
     * Checks whether the object is empty.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return (bool)(empty($this->_data));
    }

    /**
     * Convert object data into string with defined keys and values.
     *
     * Example: key1="value1" key2="value2" ...
     *
     * @param   array $keys array of accepted keys
     * @param   string $valueSeparator separator between key and value
     * @param   string $fieldSeparator separator between key/value pairs
     * @param   string $quote quoting sign
     *
     * @return  string
     */
    public function serialize($keys = [], $valueSeparator = '=', $fieldSeparator = ' ', $quote = '"')
    {
        $data = [];

        if (empty($keys)) {
            $keys = array_keys($this->_data);
        }

        foreach ($this->_data as $key => $value) {
            if (in_array($key, $keys)) {
                $data[] = $key . $valueSeparator . $quote . $value . $quote;
            }
        }
        $res = implode($fieldSeparator, $data);

        return $res;
    }

    /**
     * Present object data as string in debug mode.
     *
     * @param mixed $data
     * @param array $objects
     *
     * @return array
     */
    public function debug($data = null, &$objects = [])
    {
        if ($data === null) {
            $hash = spl_object_hash($this);

            if (! empty($objects[$hash])) {
                return '*** RECURSION ***';
            }
            $objects[$hash] = true;
            $data = $this->getData();
        }
        $debug = [];

        foreach ($data as $key => $value) {
            if (is_scalar($value)) {
                $debug[$key] = $value;
            } elseif (is_array($value)) {
                $debug[$key] = $this->debug($value, $objects);
            } elseif ($value instanceof \Magento\Framework\DataObject) {
                $debug[$key . ' (' . get_class($value) . ')'] = $value->debug(null, $objects);
            }
        }

        return $debug;
    }

    /**
     * Get value from _data array without parse key.
     *
     * @param   string $key
     *
     * @return  mixed
     */
    protected function _getData($key)
    {
        if (isset($this->_data[$key])) {
            return $this->_data[$key];
        }
    }

    /**
     * Converts field names for setters and getters.
     *
     * $this->setMyField($value) === $this->setData('my_field', $value)
     * Uses cache to eliminate unnecessary preg_replace
     *
     * @param string $name
     *
     * @return string
     */
    protected function _underscore($name)
    {
        if (isset(self::$_underscoreCache[$name])) {
            return self::$_underscoreCache[$name];
        }

        $result = mb_strtolower(
            trim(
                preg_replace(
                    '/([A-Z]|[0-9]+)/',
                    '_$1',
                    lcfirst(
                        mb_substr(
                            $name,
                            3,
                        ),
                    ),
                ),
                '_',
            ),
        );

        self::$_underscoreCache[$name] = $result;

        return $result;
    }

    /**
     * Retrieve nested data by dot notation (e.g., 'a.b.c').
     *
     * This method processes keys written in dot notation to traverse
     * nested arrays or objects and retrieve the associated data.
     *
     * @param string $key The key in dot notation format.
     *
     * @return mixed The data corresponding to the key, or null if not found.
     */
    private function getDataByDotNotation($key)
    {
        // Split the key into individual parts by the '.' delimiter
        $keys = explode('.', $key);

        // Start with the root data
        $currentData = $this->_data;

        // Traverse each part of the key to reach the nested data
        foreach ($keys as $part) {
            if (is_array($currentData) && isset($currentData[$part])) {
                // If current data is an array, move to the next nested level
                $currentData = $currentData[$part];
            } elseif (is_object($currentData) && isset($currentData->{$part})) {
                // If current data is an object, access its property
                $currentData = $currentData->{$part};
            } else {
                // If the key doesn't exist in the data, return null
                return;
            }
        }

        // Return the final data after traversal
        return $currentData;
    }

    /**
     * Handle nested data assignment recursively with merging.
     *
     * This method is used internally to manage data assignment for nested keys.
     * It supports merging arrays or overwriting values depending on the types.
     *
     * @param array|object $data The data to be modified.
     * @param string $key The key in dot notation for the nested path.
     * @param mixed $value The value to be assigned to the specified key.
     *
     * @return array|object The modified data after the assignment.
     */
    private function _setNestedData($data, $key, $value)
    {
        // Split the key into parts for nested assignment
        $keys = explode('.', $key);

        // Extract the first key
        $firstKey = Arr::shift($keys);

        // Combine the rest
        $remainingKey = implode('.', $keys);

        // Initialize the firstKey as an empty array if it doesn't exist
        if (! isset($data[$firstKey])) {
            $data[$firstKey] = [];
        }

        if ($remainingKey) {
            // If there's more key path left, recursively handle deeper levels
            $data[$firstKey] = $this->_setNestedData($data[$firstKey], $remainingKey, $value);
        } else {
            // Merge or overwrite the value based on its type
            if (is_array($data[$firstKey]) && is_array($value)) {
                // Merge arrays if both the current data and value are arrays
                $data[$firstKey] = Php::mergeArrays($data[$firstKey], $value);
            } else {
                // Otherwise, overwrite with the new value
                $data[$firstKey] = $value;
            }
        }

        // Return the modified data
        return $data;
    }

    /**
     * Set/Get attribute wrapper.
     *
     * @param string $method
     * @param array $args
     *
     * @throws LocalizedException
     *
     * @return mixed
     */
    public function __call($method, $args)
    {
        // Compare 3 first letters of the method name
        switch ($method[0] . ($method[1] ?? '') . ($method[2] ?? '')) {
            case 'get':
                if (isset($args[0]) && $args[0] !== null) {
                    return $this->getData(
                        self::$_underscoreCache[$method] ?? $this->_underscore($method),
                        $args[0],
                    );
                }

                return $this->getData(
                    self::$_underscoreCache[$method] ?? $this->_underscore($method),
                    $args[0] ?? null,
                );

            case 'set':
                return $this->setData(
                    self::$_underscoreCache[$method] ?? $this->_underscore($method),
                    $args[0] ?? null,
                );

            case 'uns':
                return $this->unsetData(
                    self::$_underscoreCache[$method] ?? $this->_underscore($method),
                );

            case 'has':
                return isset(
                    $this->_data[
                        self::$_underscoreCache[$method] ?? $this->_underscore($method)
                    ],
                );
        }

        parent::__call($method, $args);
    }

    /**
     * Export only scalar and arrays properties for var_dump.
     *
     * @return array
     */
    public function __debugInfo()
    {
        return array_filter(
            $this->_data,
            fn($v) => is_scalar($v) || is_array($v),
        );
    }
}
