<?php

declare(strict_types=1);

namespace Maginium\Framework\Actions\Concerns;

use Maginium\Framework\Request\Interfaces\RequestInterface;
use Maginium\Framework\Support\Arr;

/**
 * Trait for managing attributes, providing various methods to get, set, and manipulate attributes.
 * This trait allows for handling the attributes of an action, filling them from requests,
 * merging attributes, and accessing or modifying them as needed.
 */
trait WithAttributes
{
    /**
     * The array of attributes.
     *
     * @var array
     */
    protected array $attributes = [];

    /**
     * Retrieve the value of a specific attribute.
     *
     * This method checks the provided key against the current set of attributes
     * and returns the value if found, or a default value if the attribute does not exist.
     *
     * @param  string  $key  The key of the attribute to retrieve.
     * @param  mixed  $default  The default value if the attribute is not found. Defaults to `null`.
     *
     * @return mixed  The value of the attribute or the default value.
     */
    public function get($key, $default = null)
    {
        // Use the Arr helper to get the value from the attributes array, or return the default if not found
        return Arr::get($this->attributes, $key, $default);
    }

    /**
     * Set the raw attributes for the current instance.
     *
     * This method allows for setting the attributes directly, replacing any existing ones.
     *
     * @param  array  $attributes  The attributes to set.
     *
     * @return static  The instance of the class for chaining.
     */
    public function setRawAttributes(array $attributes): static
    {
        // Set the attributes directly
        $this->attributes = $attributes;

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Merge the provided attributes with the current attributes.
     *
     * This method takes the provided attributes and merges them with the existing attributes,
     * updating any existing values.
     *
     * @param  array  $attributes  The attributes to merge with the current attributes.
     *
     * @return static  The instance of the class for chaining.
     */
    public function fill(array $attributes): static
    {
        // Merge the provided attributes with the current attributes
        $this->attributes = Arr::merge($this->attributes, $attributes);

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Fill the attributes from the request data.
     *
     * This method extracts parameters and all data from the given request object
     * and merges them into the current attributes.
     *
     * @param  RequestInterface  $request  The request object to retrieve parameters from.
     *
     * @return static  The instance of the class for chaining.
     */
    public function fillFromRequest(RequestInterface $request): static
    {
        // Merge request parameters and all data into the current attributes
        $this->attributes = Arr::merge(
            $this->attributes,
            $request->getParams() ?? [],  // Get any extra parameters from the request
            $request->all(),  // Get all data from the request
        );

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Retrieve all attributes.
     *
     * This method returns all the current attributes as an associative array.
     *
     * @return array  The array of all attributes.
     */
    public function all(): array
    {
        // Return all attributes as an array
        return $this->attributes;
    }

    /**
     * Retrieve only the specified attributes.
     *
     * This method allows for retrieving a subset of attributes, based on the provided keys.
     * If multiple keys are provided, only those attributes will be returned.
     *
     * @param  array|string  $keys  The keys of the attributes to retrieve.
     *
     * @return array  The specified attributes.
     */
    public function only($keys): array
    {
        // Use Arr::only to return only the specified keys from the attributes array
        return Arr::only($this->attributes, is_array($keys) ? $keys : func_get_args());
    }

    /**
     * Retrieve all attributes except the specified ones.
     *
     * This method allows for retrieving a set of attributes, excluding the ones specified
     * by the provided keys.
     *
     * @param  array|string  $keys  The keys of the attributes to exclude.
     *
     * @return array  The remaining attributes after excluding the specified ones.
     */
    public function except($keys): array
    {
        // Use Arr::except to return all attributes except the specified keys
        return Arr::except($this->attributes, is_array($keys) ? $keys : func_get_args());
    }

    /**
     * Determine if the specified attribute exists.
     *
     * This method checks if an attribute with the given key exists in the current attributes.
     *
     * @param  string  $key  The key of the attribute to check.
     *
     * @return bool  True if the attribute exists, otherwise false.
     */
    public function has($key): bool
    {
        // Use Arr::has to check if the key exists in the attributes
        return Arr::has($this->attributes, $key);
    }

    /**
     * Set the value of a specific attribute.
     *
     * This method sets the value of the specified attribute. If the attribute exists,
     * it will be updated; if it doesn't, it will be added.
     *
     * @param  string  $key  The key of the attribute to set.
     * @param  mixed  $value  The value to set the attribute to.
     *
     * @return static  The instance of the class for chaining.
     */
    public function set(string $key, mixed $value): static
    {
        // Use Arr::set to set the value of the attribute
        Arr::set($this->attributes, $key, $value);

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Magic method to retrieve the value of an attribute.
     *
     * This method is called when accessing an attribute as a property, providing
     * a more convenient syntax to retrieve the value.
     *
     * @param  string  $key  The key of the attribute to retrieve.
     *
     * @return mixed  The value of the attribute.
     */
    public function __get($key)
    {
        // Delegate to the get method to retrieve the attribute
        return $this->get($key);
    }

    /**
     * Magic method to set the value of an attribute.
     *
     * This method is called when setting an attribute as a property, allowing
     * a more convenient syntax for setting the value.
     *
     * @param  string  $key  The key of the attribute to set.
     * @param  mixed  $value  The value to set the attribute to.
     */
    public function __set($key, $value)
    {
        // Delegate to the set method to set the attribute
        $this->set($key, $value);
    }

    /**
     * Magic method to check if an attribute is set.
     *
     * This method is called when checking if an attribute is set, providing
     * a more convenient syntax for checking attribute existence.
     *
     * @param  string  $key  The key of the attribute to check.
     *
     * @return bool  True if the attribute is set, otherwise false.
     */
    public function __isset($key): bool
    {
        // Check if the attribute is not null, indicating it is set
        return $this->get($key) !== null;
    }
}
