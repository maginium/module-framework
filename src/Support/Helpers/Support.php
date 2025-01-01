<?php

declare(strict_types=1);

use Illuminate\Contracts\Support\Htmlable;
use Maginium\Framework\Defer\DeferredCallback;
use Maginium\Framework\Defer\DeferredCallbackCollection;
use Maginium\Framework\Request\Interfaces\RequestInterface;
use Maginium\Framework\Request\Request;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Collection;
use Maginium\Framework\Support\Facades\Container;
use Maginium\Framework\Support\Facades\Event;
use Maginium\Framework\Support\Facades\Log;
use Maginium\Framework\Support\Str;
use Maginium\Framework\Support\Validator;

/*
 * trace_log writes a trace message to a log file.
 *
 * This function accepts any number of messages and logs them
 * to the specified logging level. If the message is an instance
 * of Exception, it will be logged as an error; otherwise, it will
 * be logged as an informational message. If the message is an
 * array or an object, it will be converted to a string using
 * print_r().
 *
 * @return void
 */
if (! function_exists('trace_log')) {
    function trace_log(): void
    {
        // Retrieve all arguments passed to the function.
        $messages = func_get_args();

        // Iterate through each message to process it.
        foreach ($messages as $message) {
            // Default logging level is set to 'info'.
            $level = 'info';

            // Check if the message is an exception.
            if ($message instanceof \Exception) {
                // Set the logging level to 'error' for exceptions.
                $level = 'error';
            } elseif (Validator::isArray($message) || is_object($message)) {
                // Convert arrays and objects to a string for logging.
                $message = print_r($message, true);
            }

            // Log the message at the determined level.
            Log::$level($message);
        }
    }
}

/*
 * traceLog is an alias for trace_log().
 *
 * This function allows for a camel-cased alternative to the
 * trace_log function. It simply calls trace_log with all
 * arguments passed to it.
 *
 * @return void
 */
if (! function_exists('traceLog')) {
    function traceLog(): void
    {
        // Call the trace_log function with all arguments.
        call_user_func_array('trace_log', func_get_args());
    }
}

/*
 * trace_sql begins to monitor all SQL output.
 *
 * This function sets up event listeners to log SQL queries,
 * bindings, execution time, and query name whenever a SQL
 * query is executed. It defines constants to manage the event
 * logging behavior.
 *
 * @return void
 */
if (! function_exists('trace_sql')) {
    function trace_sql(): void
    {
        // Define a constant to disable event logging if not already defined.
        if (! defined('MAGENTO_NO_EVENT_LOGGING')) {
            define('MAGENTO_NO_EVENT_LOGGING', 1);
        }

        // Define a constant to enable SQL tracing if not already defined.
        if (! defined('MAGENTO_TRACING_SQL')) {
            define('MAGENTO_TRACING_SQL', 1);
        } else {
            // If SQL tracing is already defined, exit the function.
            return;
        }

        // Listen for SQL query events.
        Event::listen('magento.query', function($query, $bindings, $time, $name): void {
            // Prepare the data for logging.
            $data = compact('bindings', 'time', 'name');

            // Format bindings for logging.
            foreach ($bindings as $i => $binding) {
                if ($binding instanceof \DateTime) {
                    $bindings[$i] = $binding->format('\'Y-m-d H:i:s\'');
                } elseif (Validator::isString($binding)) {
                    $bindings[$i] = "'{$binding}'";
                }
            }

            // Prepare the SQL query for logging.
            $query = str_replace(['%', '?'], ['%%', '%s'], $query);
            $query = vsprintf($query, $bindings);

            // Log the formatted query.
            Log::info($query);
        });
    }
}

/*
 * traceSql is an alias for trace_sql().
 *
 * This function serves as a camel-cased alternative to the
 * trace_sql function, invoking trace_sql with no arguments.
 *
 * @return void
 */
if (! function_exists('traceSql')) {
    function traceSql(): void
    {
        // Call the trace_sql function.
        trace_sql();
    }
}

/*
 * traceBack is an alias for trace_back().
 *
 * This function allows for a camel-cased alternative to the
 * trace_back function, calling it with a default distance
 * of 25.
 *
 * @param int $distance The number of stack frames to include in the backtrace.
 * @return void
 */
if (! function_exists('traceBack')) {
    function traceBack(int $distance = 25): void
    {
        // Call the trace_back function.
        trace_back($distance);
    }
}

/*
 * trace_back logs a simple backtrace from the call point.
 *
 * This function captures the backtrace information from the
 * stack and logs it using the trace_log function.
 *
 * @param int $distance The number of stack frames to include in the backtrace.
 * @return void
 */
if (! function_exists('trace_back')) {
    function trace_back(int $distance = 25): void
    {
        // Capture and log the backtrace information.
        trace_log(debug_backtrace(2, $distance));
    }
}

/*
 * e encodes HTML special characters in a string.
 *
 * This function converts special HTML characters in the given
 * value to their HTML models, ensuring safe output in HTML
 * contexts. If the value is an instance of Htmlable, it will
 * call the toHtml() method on it.
 *
 * @param Htmlable|string $value The value to be encoded.
 * @param bool $doubleEncode Whether to encode existing HTML models.
 *
 * @return string The encoded string.
 */
if (! function_exists('e')) {
    function e($value, $doubleEncode = false)
    {
        // Check if the value is an Htmlable instance.
        if ($value instanceof Htmlable) {
            return $value->toHtml();
        }

        // Encode the value using htmlspecialchars.
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', $doubleEncode);
    }
}

/*
 * trans translates the given message.
 *
 * This function is used to retrieve a translated message based
 * on the provided ID and parameters. It currently wraps the
 * __() function for translation.
 *
 * @param string|null $id The translation key.
 * @param array $parameters An array of parameters to replace in the message.
 * @param string|null $locale The locale for the translation.
 *
 * @return string The translated message.
 */
if (! function_exists('trans')) {
    function trans($id = null, $parameters = [], $locale = null)
    {
        // TODO: REFACTOR HERE
        return __($id, $parameters, $locale);
    }
}

/*
 * array_build builds a new array using a callback.
 *
 * This function creates a new array from an existing array by
 * applying the provided callback to each element.
 *
 * @param array $array The input array.
 * @param callable $callback The callback used to build the new array.
 *
 * @return array The resulting array after applying the callback.
 */
if (! function_exists('array_build')) {
    function array_build($array, callable $callback)
    {
        // Use the Arr::build method to apply the callback to each
        // element of the input array, returning a new array.
        return Arr::build($array, $callback);
    }
}

/*
 * collect creates a collection from the given value.
 *
 * This function wraps the given value in a Collection instance,
 * providing a fluent interface for working with arrays and objects.
 *
 * @param mixed $value The value to be wrapped in a collection.
 *
 * @return Collection A new Collection instance.
 */
if (! function_exists('collect')) {
    function collect($value = null)
    {
        // Create and return a new Collection instance, passing
        // the provided value to its constructor.
        return Collection::make($value);
    }
}

/*
 * array_add adds an element to an array using "dot" notation if it doesn't exist.
 *
 * This function checks if the specified key exists in the input array.
 * If the key does not exist, it adds the provided value to the array at
 * the specified key using dot notation for nested keys.
 *
 * @param  array  $array The input array to which the value will be added.
 * @param  string  $key The key at which to add the value, supporting
 *                      dot notation for nested arrays.
 * @param  mixed  $value The value to be added to the array at
 *                       the specified key.
 *
 * @return array The updated array with the new value added if it
 *               did not already exist.
 */
if (! function_exists('array_add')) {
    function array_add($array, $key, $value)
    {
        // Return the array with the new value added if the key did not exist
        return Arr::add($array, $key, $value);
    }
}

/*
 * array_collapse collapses an array of arrays into a single array.
 *
 * This function takes an array of arrays and combines them into a
 * single flat array, effectively merging all items from the
 * sub-arrays into one array. This is useful for simplifying
 * nested structures.
 *
 * @param  array  $array The array of arrays to collapse.
 *
 * @return array A single array containing all items from the
 *               provided sub-arrays.
 */
if (! function_exists('array_collapse')) {
    function array_collapse($array)
    {
        // Return a single array containing all items from the sub-arrays
        return Arr::collapse($array);
    }
}

/*
 * array_divide divides an array into two arrays: one with keys and
 * another with values.
 *
 * This function separates the keys and values of the input array
 * into two separate arrays. The first array will contain all the
 * keys, and the second will contain all the corresponding values.
 *
 * @param  array  $array The input array to divide.
 *
 * @return array An array containing two arrays: the first with
 *               keys and the second with values.
 */
if (! function_exists('array_divide')) {
    function array_divide($array)
    {
        // Return an array containing two arrays: keys and values
        return Arr::divide($array);
    }
}

/*
 * array_dot flattens a multi-dimensional associative array using dots.
 *
 * This function converts a multi-dimensional associative array into
 * a flat array where nested keys are represented using dot notation.
 * This is useful for transforming complex data structures into a
 * more manageable format.
 *
 * @param  array  $array The input array to flatten.
 * @param  string  $prepend A string to prepend to each key in the
 *                          resulting flattened array.
 *
 * @return array The flattened array with keys represented in dot
 *               notation.
 */
if (! function_exists('array_dot')) {
    function array_dot($array, $prepend = '')
    {
        // Return the flattened array with keys in dot notation
        return Arr::dot($array, $prepend);
    }
}

/*
 * array_except retrieves all elements from an array except for
 * specified keys.
 *
 * This function creates a new array that contains all the elements
 * of the input array except for those specified by the keys.
 * This is useful for excluding sensitive or unnecessary data.
 *
 * @param  array  $array The input array to filter.
 * @param  array|string  $keys The keys to exclude from the
 *                             resulting array.
 *
 * @return array The array containing all elements except those
 *               specified by the keys.
 */
if (! function_exists('array_except')) {
    function array_except($array, $keys)
    {
        // Return the array containing all elements except the specified keys
        return Arr::except($array, $keys);
    }
}

/*
 * array_first returns the first element in an array passing a
 * given truth test.
 *
 * This function iterates through the input array and returns the
 * first element that satisfies the provided callback condition.
 * If no callback is provided, it returns the first element in the
 * array.
 *
 * @param  array  $array The input array to search through.
 * @param  callable|null  $callback An optional callback function
 *                                  to apply to each element.
 * @param  mixed  $default The default value to return if no
 *                         matching element is found.
 *
 * @return mixed The first matching element or the default value
 *               if none is found.
 */
if (! function_exists('array_first')) {
    function array_first($array, ?callable $callback = null, $default = null)
    {
        // Return the first matching element or the default if none found
        return Arr::first($array, $callback, $default);
    }
}

/*
 * array_flatten flattens a multi-dimensional array into a single level.
 *
 * This function reduces the input array's depth to a single level,
 * effectively merging all nested elements into one array. The depth
 * parameter controls how many levels of nesting to flatten.
 *
 * @param  array  $array The input array to flatten.
 * @param  int  $depth The maximum depth to flatten; defaults to
 *                     PHP_INT_MAX for complete flattening.
 *
 * @return array The flattened array with all nested elements
 *               merged into one level.
 */
if (! function_exists('array_flatten')) {
    function array_flatten($array, $depth = PHP_INT_MAX)
    {
        // Return the flattened array with nested elements merged into one level
        return Arr::flatten($array, $depth);
    }
}

/*
 * array_forget removes one or many array items from a given array
 * using "dot" notation.
 *
 * This function removes specified keys from the input array,
 * allowing for nested keys to be targeted using dot notation.
 * This is useful for cleaning up arrays by removing unnecessary
 * elements.
 *
 * @param  array  $array The array from which to remove items.
 * @param  array|string  $keys The keys to remove from the array.
 *
 * @return void This function does not return a value; it modifies
 *              the array in place.
 */
if (! function_exists('array_forget')) {
    function array_forget(&$array, $keys): void
    {
        // Remove the specified keys from the array, modifying it in place
        Arr::forget($array, $keys);
    }
}

/*
 * array_get retrieves an item from an array using "dot" notation.
 *
 * This function allows for easy access to nested array elements
 * using dot notation. If the specified key does not exist, it can
 * return a default value.
 *
 * @param  \ArrayAccess|array  $array The array or object to
 *                                    retrieve the item from.
 * @param  string|int  $key The key to look for, supporting
 *                          dot notation for nested keys.
 * @param  mixed  $default The default value to return if the key
 *                         does not exist.
 *
 * @return mixed The value found at the specified key, or the
 *               default value if not found.
 */
if (! function_exists('array_get')) {
    function array_get($array, $key, $default = null)
    {
        // Return the value found at the specified key or the default if not found
        return Arr::get($array, $key, $default);
    }
}

/*
 * array_has checks if an item or items exist in an array using
 * "dot" notation.
 *
 * This function verifies if the specified keys exist within the
 * input array, supporting dot notation for nested keys. It returns
 * true if any of the keys exist, or false otherwise.
 *
 * @param  \ArrayAccess|array  $array The array to check for
 *                                    existence of keys.
 * @param  string|array  $keys The key or keys to check for.
 *
 * @return bool True if any of the specified keys exist in the
 *              array; otherwise, false.
 */
if (! function_exists('array_has')) {
    function array_has($array, $keys)
    {
        // Return true if any of the specified keys exist in the array
        return Arr::has($array, $keys);
    }
}

/*
 * array_last returns the last element in an array passing a
 * given truth test.
 *
 * This function iterates through the input array in reverse and
 * returns the last element that satisfies the provided callback
 * condition. If no callback is provided, it returns the last
 * element in the array.
 *
 * @param  array  $array The input array to search through.
 * @param  callable|null  $callback An optional callback function
 *                                  to apply to each element.
 * @param  mixed  $default The default value to return if no
 *                         matching element is found.
 *
 * @return mixed The last matching element or the default value
 *               if none is found.
 */
if (! function_exists('array_last')) {
    function array_last($array, ?callable $callback = null, $default = null)
    {
        // Return the last matching element or the default if none found
        return Arr::last($array, $callback, $default);
    }
}

/*
 * array_only retrieves a subset of the items from the given array.
 *
 * This function extracts the specified keys from the input array
 * and returns a new array containing only those items. This is
 * useful for filtering out only the necessary elements.
 *
 * @param  array  $array The input array to filter.
 * @param  array|string  $keys The keys to retrieve from the
 *                             array.
 *
 * @return array An array containing only the specified keys and
 *               their corresponding values.
 */
if (! function_exists('array_only')) {
    function array_only($array, $keys)
    {
        // Return an array containing only the specified keys and values
        return Arr::only($array, $keys);
    }
}

/*
 * array_pluck retrieves all of the values for a given key from an
 * array.
 *
 * This function extracts the values associated with a specified key
 * from each item in the input array, effectively creating a new
 * array of these values. This is useful for transforming complex
 * arrays into simpler lists.
 *
 * @param  array  $array The input array to pluck values from.
 * @param  string  $key The key to retrieve values for.
 * @param  string|null  $index An optional key to index the values
 *                             by in the resulting array.
 *
 * @return array An array containing the values associated with the
 *               specified key.
 */
if (! function_exists('array_pluck')) {
    function array_pluck($array, $key, $index = null)
    {
        // Return an array containing the values associated with the specified key
        return Arr::pluck($array, $key, $index);
    }
}

/*
 * array_push_recursive pushes an item onto the end of an array
 * recursively.
 *
 * This function adds an item to the end of the input array, and if
 * the item being added is an array itself, it will recursively push
 * its values onto the end of the existing array. This is useful for
 * merging nested arrays.
 *
 * @param  array  $array The input array to push the item onto.
 * @param  mixed  $value The value to add to the array.
 *
 * @return array The updated array with the new item added.
 */
if (! function_exists('array_push_recursive')) {
    function array_push_recursive(array &$array, $value)
    {
        // Add the item to the end of the array and return the updated array
        return Arr::pushRecursive($array, $value);
    }
}

/*
 * array_set sets an item in an array using "dot" notation.
 *
 * This function adds or updates a value at a specified key in the
 * input array, supporting dot notation for nested arrays. If the
 * key does not exist, it will create the necessary structure to
 * store the value.
 *
 * @param  array  $array The array in which to set the value.
 * @param  string  $key The key at which to set the value, supporting
 *                      dot notation for nested arrays.
 * @param  mixed  $value The value to set at the specified key.
 *
 * @return array The updated array with the new value set at the
 *               specified key.
 */
if (! function_exists('array_set')) {
    function array_set(array &$array, $key, $value)
    {
        // Set the value at the specified key and return the updated array
        return Arr::set($array, $key, $value);
    }
}

/*
 * array_sort recursively sorts the given array.
 *
 * This function sorts the input array recursively by its keys,
 * arranging them in a specific order. This is useful for normalizing
 * arrays for comparison or output.
 *
 * @param  array  $array The input array to sort.
 *
 * @return array The sorted array with keys arranged in order.
 */
if (! function_exists('array_sort')) {
    function array_sort($array)
    {
        // Return the sorted array with keys arranged in order
        return Arr::sort($array);
    }
}

/*
 * array_sort_recursive sorts an array with keys recursively.
 *
 * This function sorts the input array and all nested arrays within
 * it, arranging keys in a specific order at every level. This is
 * useful for ensuring consistent ordering throughout complex data
 * structures.
 *
 * @param  array  $array The input array to sort.
 *
 * @return array The sorted array with keys arranged in order
 *               recursively.
 */
if (! function_exists('array_sort_recursive')) {
    function array_sort_recursive($array)
    {
        // Return the recursively sorted array with keys arranged in order
        return Arr::sortRecursive($array);
    }
}

/*
 * array_where filters the array using a callback.
 *
 * This function applies a given callback function to each element
 * in the input array, returning a new array containing only the
 * elements for which the callback returns true. This is useful for
 * filtering data based on specific criteria.
 *
 * @param  array  $array The input array to filter.
 * @param  callable|null  $callback An optional callback function
 *                                  to apply to each element.
 *
 * @return array An array containing the elements that satisfy the
 *               provided callback condition.
 */
if (! function_exists('array_where')) {
    function array_where($array, ?callable $callback = null)
    {
        // Return an array containing only the elements that satisfy the callback
        return Arr::where($array, $callback);
    }
}

/*
 * array_values_recursive retrieves all values from an array
 * recursively.
 *
 * This function extracts all values from the input array and its
 * nested arrays, returning a flat array of values. This is useful for
 * collecting data from complex structures into a simple list.
 *
 * @param  array  $array The input array to retrieve values from.
 *
 * @return array A flat array containing all values from the input
 *               array and its nested arrays.
 */
if (! function_exists('array_values_recursive')) {
    function array_values_recursive($array)
    {
        // Return a flat array containing all values from the input array and its nested arrays
        return Arr::valuesRecursive($array);
    }
}

/*
 * array_wrap wraps the given value in an array if it is not already
 * an array.
 *
 * This function checks if the provided value is an array. If it is,
 * the function returns it as-is; if not, it wraps the value in a
 * new array. This is useful for ensuring consistent array handling
 * for input values.
 *
 * @param  mixed  $value The value to wrap in an array if necessary.
 *
 * @return array The value wrapped in an array if it was not an array;
 *               otherwise, the original array.
 */
if (! function_exists('array_wrap')) {
    function array_wrap($value)
    {
        // Return the value wrapped in an array if it was not already an array
        return Arr::wrap($value);
    }
}

/*
 * array_unique_recursive removes duplicate values from an array
 * recursively.
 *
 * This function checks the input array for duplicate values and
 * removes them, applying the uniqueness check at every level of
 * nested arrays. This is useful for cleaning up data structures
 * that may contain repeated values.
 *
 * @param  array  $array The input array to clean up.
 *
 * @return array The array with duplicate values removed recursively.
 */
if (! function_exists('array_unique_recursive')) {
    function array_unique_recursive($array)
    {
        // Return the array with duplicate values removed recursively
        return Arr::uniqueRecursive($array);
    }
}

/*
 * array_pluck plucks an array of values from an array.
 *
 * This function retrieves a list of values from a specified array
 * based on the provided key or keys. If a key is not given, it
 * defaults to using the value specified.
 *
 * @param  array  $array  The input array from which to pluck values.
 * @param  string|array  $value  The key of the values to pluck.
 * @param  string|array|null  $key  Optional. The key to use for indexing the plucked values.
 *
 * @return array  The plucked values from the array.
 */
if (! function_exists('array_pluck')) {
    function array_pluck($array, $value, $key = null)
    {
        // Returns the plucked values.
        return Arr::pluck($array, $value, $key);
    }
}

/*
 * array_prepend pushes an item onto the beginning of an array.
 *
 * This function adds a value to the start of the given array.
 * If a key is provided, the value is inserted with that key;
 * otherwise, it is added as the next numeric index.
 *
 * @param  array  $array  The array to which the value will be prepended.
 * @param  mixed  $value  The value to prepend to the array.
 * @param  mixed  $key  Optional. The key to associate with the value.
 *
 * @return array  The modified array with the new value at the beginning.
 */
if (! function_exists('array_prepend')) {
    function array_prepend($array, $value, $key = null)
    {
        // Returns the modified array.
        return Arr::prepend($array, $value, $key);
    }
}

/*
 * array_pull gets a value from the array and removes it.
 *
 * This function retrieves and removes a value from the array
 * based on the specified key. If the key does not exist,
 * it returns a default value if provided.
 *
 * @param  array  $array  The array from which to pull the value.
 * @param  string  $key  The key of the value to pull.
 * @param  mixed  $default  Optional. The default value to return if the key does not exist.
 *
 * @return mixed  The pulled value from the array, or the default value if the key was not found.
 */
if (! function_exists('array_pull')) {
    function array_pull(&$array, $key, $default = null)
    {
        // Returns the pulled value or default.
        return Arr::pull($array, $key, $default);
    }
}

/*
 * array_random gets a random value from an array.
 *
 * This function selects one or more random values from the
 * specified array. If a number is not provided, it defaults
 * to selecting a single random value.
 *
 * @param  array  $array  The array from which to get the random value(s).
 * @param  int|null  $num  Optional. The number of random values to retrieve.
 *
 * @return mixed  A random value or an array of random values from the input array.
 */
if (! function_exists('array_random')) {
    function array_random($array, $num = null)
    {
        // Returns the random value(s).
        return Arr::random($array, $num);
    }
}

/*
 * array_set sets an array item to a given value using "dot" notation.
 *
 * This function sets a specified key in the array to a given
 * value. If no key is provided, the entire array will be replaced.
 *
 * @param  array  $array  The array in which to set the value.
 * @param  string  $key  The key at which to set the value.
 * @param  mixed  $value  The value to set in the array.
 *
 * @return array  The modified array with the new value set.
 */
if (! function_exists('array_set')) {
    function array_set(&$array, $key, $value)
    {
        // Returns the modified array.
        return Arr::set($array, $key, $value);
    }
}

/*
 * array_sort sorts the array by the given callback or attribute name.
 *
 * This function sorts the provided array based on a specified
 * callback function or attribute name. It can be used for custom sorting.
 *
 * @param  array  $array  The array to be sorted.
 * @param  callable|string|null  $callback  Optional. A callback to use for sorting or an attribute name.
 *
 * @return array  The sorted array.
 */
if (! function_exists('array_sort')) {
    function array_sort($array, $callback = null)
    {
        // Returns the sorted array.
        return Arr::sort($array, $callback);
    }
}

/*
 * array_sort_recursive recursively sorts an array by keys and values.
 *
 * This function sorts the array and all nested arrays recursively.
 *
 * @param  array  $array  The array to be sorted recursively.
 *
 * @return array  The recursively sorted array.
 */
if (! function_exists('array_sort_recursive')) {
    function array_sort_recursive($array)
    {
        // Returns the recursively sorted array.
        return Arr::sortRecursive($array);
    }
}

/*
 * array_where filters the array using the given callback.
 *
 * This function applies a callback to each item in the array
 * and returns only those items for which the callback returns true.
 *
 * @param  array  $array  The array to be filtered.
 * @param  callable  $callback  The callback to use for filtering.
 *
 * @return array  The filtered array containing only the items that match the criteria.
 */
if (! function_exists('array_where')) {
    function array_where($array, callable $callback)
    {
        // Returns the filtered array.
        return Arr::where($array, $callback);
    }
}

/*
 * array_wrap wraps a value in an array if it is not already an array.
 *
 * This function ensures that the given value is returned as an array,
 * wrapping it if necessary.
 *
 * @param  mixed  $value  The value to wrap in an array.
 *
 * @return array  An array containing the value.
 */
if (! function_exists('array_wrap')) {
    function array_wrap($value)
    {
        // Returns the wrapped value as an array.
        return Arr::wrap($value);
    }
}

/*
 * camel_case converts a value to camel case.
 *
 * This function transforms a given string to camel case format,
 * suitable for variable names and identifiers.
 *
 * @param  string  $value  The value to convert to camel case.
 *
 * @return string  The camel case representation of the input value.
 */
if (! function_exists('camel_case')) {
    function camel_case($value)
    {
        // Returns the camel case string.
        return Str::camel($value);
    }
}

/*
 * ends_with determines if a given string ends with a given substring.
 *
 * This function checks if the specified string ends with one or more
 * given substrings.
 *
 * @param  string  $haystack  The string to check.
 * @param  string|array  $needles  The substring(s) to search for at the end of the haystack.
 *
 * @return bool  True if the haystack ends with any of the needles, false otherwise.
 */
if (! function_exists('ends_with')) {
    function ends_with($haystack, $needles)
    {
        // Returns true or false based on the check.
        return Str::endsWith($haystack, $needles);
    }
}

/*
 * kebab_case converts a string to kebab case.
 *
 * This function transforms a given string to kebab case format,
 * which is often used in URLs.
 *
 * @param  string  $value  The value to convert to kebab case.
 *
 * @return string  The kebab case representation of the input value.
 */
if (! function_exists('kebab_case')) {
    function kebab_case($value)
    {
        // Returns the kebab case string.
        return Str::kebab($value);
    }
}

/*
 * snake_case converts a string to snake case.
 *
 * This function transforms a given string to snake case format,
 * suitable for database columns and file names.
 *
 * @param  string  $value  The value to convert to snake case.
 * @param  string  $delimiter  Optional. The delimiter to use in the snake case string.
 *
 * @return string  The snake case representation of the input value.
 */
if (! function_exists('snake_case')) {
    function snake_case($value, $delimiter = '_')
    {
        // Returns the snake case string.
        return Str::snake($value, $delimiter);
    }
}

/*
 * starts_with determines if a given string starts with a given substring.
 *
 * This function checks if the specified string starts with one or more
 * given substrings.
 *
 * @param  string  $haystack  The string to check.
 * @param  string|array  $needles  The substring(s) to search for at the start of the haystack.
 *
 * @return bool  True if the haystack starts with any of the needles, false otherwise.
 */
if (! function_exists('starts_with')) {
    function starts_with($haystack, $needles)
    {
        // Returns true or false based on the check.
        return Str::startsWith($haystack, $needles);
    }
}

/*
 * str_after returns the remainder of a string after a given value.
 *
 * This function extracts the portion of a string that comes
 * after the specified value.
 *
 * @param  string  $haystack  The string to search within.
 * @param  string  $needles  The value to search for.
 *
 * @return string  The remaining portion of the string after the specified value.
 */
if (! function_exists('str_after')) {
    function str_after($haystack, $needles)
    {
        // Returns the portion of the string after the needles.
        return Str::after($haystack, $needles);
    }
}

/*
 * str_before returns the portion of a string before a given value.
 *
 * This function extracts the portion of a string that comes
 * before the specified value.
 *
 * @param  string  $haystack  The string to search within.
 * @param  string  $needles  The value to search for.
 *
 * @return string  The portion of the string before the specified value.
 */
if (! function_exists('str_before')) {
    function str_before($haystack, $needles)
    {
        // Returns the portion of the string before the needles.
        return Str::before($haystack, $needles);
    }
}

/*
 * str_finish caps a string with a single instance of a given value.
 *
 * @param  string  $value  The string to be capped.
 * @param  string  $cap    The value to cap the string with.
 *
 * @return string  The modified string with the cap.
 */
if (! function_exists('str_finish')) {
    function str_finish($value, $cap)
    {
        // Returns the string with the specified cap.
        return Str::finish($value, $cap);
    }
}

/*
 * str_is determines if a given string matches a given pattern.
 *
 * @param  string|array  $pattern  The pattern to match against.
 * @param  string  $value         The string to evaluate.
 *
 * @return bool  True if the string matches the pattern, false otherwise.
 */
if (! function_exists('str_is')) {
    function str_is($pattern, $value)
    {
        // Returns true if the value matches the pattern.
        return Str::is($pattern, $value);
    }
}

/*
 * str_limit limits the number of characters in a string.
 *
 * @param  string  $value  The string to limit.
 * @param  int     $limit  The maximum number of characters.
 * @param  string  $end    The string to append if the limit is exceeded.
 *
 * @return string  The limited string with an ellipsis if applicable.
 */
if (! function_exists('str_limit')) {
    function str_limit($value, $limit = 100, $end = '...')
    {
        // Returns the truncated string with an ellipsis if it exceeds the limit.
        return Str::limit($value, $limit, $end);
    }
}

/*
 * str_plural gets the plural form of an English word.
 *
 * @param  string  $value  The word to pluralize.
 * @param  int     $count  The count to determine if pluralization is necessary.
 *
 * @return string  The pluralized form of the word.
 */
if (! function_exists('str_plural')) {
    function str_plural($value, $count = 2)
    {
        // Returns the plural form based on the count.
        return Str::plural($value, $count);
    }
}

/*
 * str_random generates a more truly "random" alpha-numeric string.
 *
 * @param  int  $length  The length of the random string.
 *
 * @throws \RuntimeException  If the random string generation fails.
 *
 * @return string  The generated random string.
 */
if (! function_exists('str_random')) {
    function str_random($length = 16)
    {
        // Returns a random string of the specified length.
        return Str::random($length);
    }
}

/*
 * str_replace_array replaces a given value in the string sequentially with an array.
 *
 * @param  string  $search   The placeholder string to search for.
 * @param  array   $replace  The array of values to replace the placeholders.
 * @param  string  $subject  The string subject in which to perform replacements.
 *
 * @return string  The modified string with placeholders replaced by array values.
 */
if (! function_exists('str_replace_array')) {
    function str_replace_array($search, array $replace, $subject)
    {
        // Returns the modified string after replacement.
        return Str::replaceArray($search, $replace, $subject);
    }
}

/*
 * str_replace_first replaces the first occurrence of a given value in the string.
 *
 * @param  string  $search   The value to search for.
 * @param  string  $replace  The value to replace with.
 * @param  string  $subject  The string to perform the replacement on.
 *
 * @return string  The modified string with the first occurrence replaced.
 */
if (! function_exists('str_replace_first')) {
    function str_replace_first($search, $replace, $subject)
    {
        // Returns the modified string after replacing the first occurrence.
        return Str::replaceFirst($search, $replace, $subject);
    }
}

/*
 * str_replace_last replaces the last occurrence of a given value in the string.
 *
 * @param  string  $search   The value to search for.
 * @param  string  $replace  The value to replace with.
 * @param  string  $subject  The string to perform the replacement on.
 *
 * @return string  The modified string with the last occurrence replaced.
 */
if (! function_exists('str_replace_last')) {
    function str_replace_last($search, $replace, $subject)
    {
        // Returns the modified string after replacing the last occurrence.
        return Str::replaceLast($search, $replace, $subject);
    }
}

/*
 * str_singular gets the singular form of an English word.
 *
 * @param  string  $value  The word to singularize.
 *
 * @return string  The singular form of the word.
 */
if (! function_exists('str_singular')) {
    function str_singular($value)
    {
        // Returns the singular form of the given word.
        return Str::singular($value);
    }
}

/*
 * str_slug generates a URL friendly "slug" from a given string.
 *
 * @param  string  $title      The string to convert to a slug.
 * @param  string  $separator  The character to use as a separator.
 * @param  string  $language   The language code to use for slug generation.
 *
 * @return string  The generated slug.
 */
if (! function_exists('str_slug')) {
    function str_slug($title, $separator = '-', $language = 'en')
    {
        // Returns the URL-friendly slug generated from the title.
        return Str::slug($title, $separator, $language);
    }
}

/*
 * str_start begins a string with a single instance of a given value.
 *
 * @param  string  $value  The string to prepend to.
 * @param  string  $prefix  The value to prepend to the string.
 *
 * @return string  The modified string with the prefix.
 */
if (! function_exists('str_start')) {
    function str_start($value, $prefix)
    {
        // Returns the string with the prefix added at the start.
        return Str::start($value, $prefix);
    }
}

/*
 * studly_case converts a value to studly caps case.
 *
 * @param  string  $value  The string to convert.
 *
 * @return string  The studly case representation of the input value.
 */
if (! function_exists('studly_case')) {
    function studly_case($value)
    {
        // Returns the string converted to studly caps case.
        return Str::studly($value);
    }
}

/*
 * title_case converts a value to title case.
 *
 * @param  string  $value  The string to convert.
 *
 * @return string  The title case representation of the input value.
 */
if (! function_exists('title_case')) {
    function title_case($value)
    {
        // Returns the string converted to title case.
        return Str::title($value);
    }
}

/*
 * Return a scalar value for the given value that might be an enum.
 *
 * @internal
 *
 * @template TValue
 * @template TDefault
 *
 * @param  TValue  $value
 * @param  TDefault|callable(TValue): TDefault  $default
 *
 * @return ($value is \empty ? TDefault : mixed)
 */
if (! function_exists('Maginium\Framework\Support\enum_value')) {
    function enum_value($value, $default = null)
    {
        return transform($value, fn($value) => match (true) {
            $value instanceof \BackedEnum => $value->value,
            $value instanceof \UnitEnum => $value->name,

            default => $value,
        }, $default ?? $value);
    }
}

/*
 * Defer execution of the given callback.
 *
 * @param  callable|null  $callback
 * @param  string|null  $name
 * @param  bool  $always
 *
 * @return DeferredCallback
 */
if (! function_exists('Maginium\Framework\Support\defer')) {
    function defer(?callable $callback = null, ?string $name = null, bool $always = false)
    {
        // Return the current DeferredCallbackCollection if no callback is provided.
        if ($callback === null) {
            return Container::make(DeferredCallback::class);
        }

        // Create a new DeferredCallback instance.
        $deferredCallback = Container::make(DeferredCallback::class, [
            'callback' => $callback,
            'name' => $name,
            'always' => $always,
        ]);

        // Optionally execute the callback.
        $deferredCallback->invoke(); // Ensure the `invoke` method exists and properly executes the callback.

        // Return the DeferredCallback instance.
        return tap(
            $deferredCallback,
            fn($deferred) => Container::get(DeferredCallbackCollection::class)[] = $deferred,
        );
    }
}

/*
 * Get an instance of the current request or retrieve input from the request.
 *
 * This function serves two purposes:
 * 1. If no `$key` is provided, it returns the entire request object instance.
 * 2. If a `$key` is provided, it attempts to fetch that specific input item from the request.
 *    If the input is not found, the `$default` value will be returned (if provided).
 *
 * @param  list<string>|string|null  $key    The key(s) of the input item(s) to retrieve.
 *                                             If null, returns the entire request object.
 * @param  mixed  $default           The default value to return if the key is not found.
 *                                    Ignored if no key is provided.
 * @return mixed|array<string, mixed> The request instance or the value(s) from the request.
 *
 * @see Request
 */
if (! function_exists('request')) {
    function request($key = null, $default = null)
    {
        // Retrieve the current request instance from the container
        /** @var Request $instance */
        $instance = Container::make(RequestInterface::class);

        // If no key is provided, return the entire request object instance
        if ($key === null) {
            return $instance;
        }

        // If an array of keys is provided, return only those keys from the request
        if (is_array($key)) {
            return $instance->only($key);
        }

        // If a single key is provided, attempt to get its value from the request
        $value = $instance->__get($key);

        // If the value is found, return it, otherwise return the default value (if provided)
        return $value === null ? value($default) : $value;
    }
}
