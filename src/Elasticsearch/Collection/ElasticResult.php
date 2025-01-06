<?php

declare(strict_types=1);

namespace Maginium\Framework\Elasticsearch\Collection;

/**
 * ElasticResult class is a wrapper for storing and interacting with a single value
 * from an Elasticsearch query. This can be used when the result of a query is expected
 * to be a single value rather than a collection.
 */
class ElasticResult
{
    // Use the ElasticCollectionMeta trait to add query metadata functionality to this class.
    use ElasticCollectionMeta;

    /**
     * Protected property to store the single value returned by the query.
     *
     * @var mixed
     */
    protected mixed $value;

    /**
     * Constructor for the ElasticResult class.
     *
     * @param mixed $value The value to be wrapped. Default is null.
     * This method allows initializing the `value` property with the provided value.
     */
    public function __construct($value = null)
    {
        // Assign the provided value to the protected $value property.
        $this->value = $value;
    }

    /**
     * Magic method that allows this object to be invoked like a function,
     * returning the stored value.
     *
     * @return mixed The stored value.
     *
     * This method provides a convenient way to access the stored value by invoking
     * the object itself. This could be useful for scenarios where you want to treat
     * the result as a callable object.
     */
    public function __invoke()
    {
        // Return the stored value.
        return $this->value;
    }

    /**
     * Set the value of the result.
     *
     * @param mixed $value The value to set.
     *
     * This method allows updating the stored value by passing a new one. It can be used
     * to modify the `value` property after the object has been created.
     */
    public function setValue($value): void
    {
        // Update the stored value.
        $this->value = $value;
    }

    /**
     * Get the stored value.
     *
     * @return mixed The stored value.
     *
     * This method retrieves the current value of the `value` property.
     */
    public function getValue(): mixed
    {
        // Return the current value.
        return $this->value;
    }

    /**
     * Magic method to convert the object to a string.
     *
     * @return string The string representation of the stored value.
     *
     * This method provides a way to convert the object to a string. It is particularly
     * useful when the object is used in string contexts (e.g., echoing the object),
     * returning the string form of the stored value.
     */
    public function __toString()
    {
        // Cast the value to a string and return it.
        return (string)$this->value;
    }
}
