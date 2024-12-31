<?php

declare(strict_types=1);

namespace Maginium\Framework\Actions\Concerns;

use Maginium\Framework\Support\Facades\Container;

/**
 * Trait for decorating action instances.
 *
 * This trait provides a set of methods to decorate an action instance. It allows checking if the action
 * has specific traits, properties, and methods, and provides functionality to call those methods or retrieve
 * properties dynamically. The goal is to enhance and extend the behavior of an action object while maintaining
 * flexibility and enabling method chaining.
 *
 * @method self setAction(mixed $action) Set the action instance for decoration.
 * @method bool hasTrait(string $trait) Check if the action has a specific trait.
 * @method bool hasProperty(string $property) Check if the action has a specific property.
 * @method mixed getProperty(string $property) Retrieve the value of a property from the action.
 * @method bool hasMethod(string $method) Check if the action has a specific method.
 * @method mixed callMethod(string $method, array $parameters = []) Call a method on the action with dynamic parameters.
 * @method mixed resolveAndCallMethod(string $method, array $extraArguments = []) Resolve and call a method on the action using the container.
 * @method mixed fromActionMethod(string $method, array $methodParameters = [], mixed $default = null) Attempt to call a method on the action or return a default value.
 * @method mixed fromActionProperty(string $property, mixed $default = null) Retrieve a property from the action or return a default value.
 * @method mixed fromActionMethodOrProperty(string $method, string $property, mixed $default = null, array $methodParameters = []) Attempt to call a method or retrieve a property from the action.
 */
trait DecorateActions
{
    /**
     * The action instance that this trait is decorating.
     *
     * @var mixed
     */
    protected mixed $action;

    /**
     * Set the action instance for decoration.
     *
     * This method sets the action to be decorated and allows for method chaining.
     * It initializes the `$action` property, which is used throughout the trait methods.
     *
     * @param  mixed  $action  The action instance to decorate.
     *
     * @return self  The instance of the class for method chaining.
     */
    public function setAction($action): self
    {
        // Store the action instance to the $action property
        $this->action = $action;

        // Return the current instance to allow method chaining
        return $this;
    }

    /**
     * Check if the action has a specific trait.
     *
     * This method checks if the provided trait exists in the class of the action.
     * It uses `class_uses_recursive` to check all traits in the class hierarchy.
     *
     * @param  string  $trait  The name of the trait to check.
     *
     * @return bool  True if the trait is used, false otherwise.
     */
    protected function hasTrait(string $trait): bool
    {
        // Check if the class of the action has the specified trait
        return in_array($trait, class_uses_recursive($this->action));
    }

    /**
     * Check if the action has a specific property.
     *
     * This method checks if the specified property exists in the action object.
     * It uses `property_exists` to verify the existence of the property.
     *
     * @param  string  $property  The name of the property to check.
     *
     * @return bool  True if the property exists, false otherwise.
     */
    protected function hasProperty(string $property): bool
    {
        // Check if the specified property exists in the action object
        return property_exists($this->action, $property);
    }

    /**
     * Retrieve the value of a property from the action.
     *
     * This method gets the value of the specified property from the action instance.
     * It assumes the property exists; use `hasProperty` to check before calling this method.
     *
     * @param  string  $property  The name of the property to retrieve.
     *
     * @return mixed  The value of the property.
     */
    protected function getProperty(string $property)
    {
        // Return the value of the property from the action
        return $this->action->{$property};
    }

    /**
     * Check if the action has a specific method.
     *
     * This method checks if the provided method exists in the action class.
     * It uses `method_exists` to verify the existence of the method.
     *
     * @param  string  $method  The name of the method to check.
     *
     * @return bool  True if the method exists, false otherwise.
     */
    protected function hasMethod(string $method): bool
    {
        // Check if the specified method exists in the action class
        return method_exists($this->action, $method);
    }

    /**
     * Call a method on the action with the provided parameters.
     *
     * This method invokes a method on the action instance using `call_user_func_array`.
     * The method name and parameters are dynamically passed.
     *
     * @param  string  $method  The name of the method to call.
     * @param  array  $parameters  The parameters to pass to the method.
     *
     * @return mixed  The result of the method call.
     */
    protected function callMethod(string $method, array $parameters = [])
    {
        // Call the specified method on the action instance with the given parameters
        return call_user_func_array([$this->action, $method], $parameters);
    }

    /**
     * Resolve and call a method on the action using the container.
     *
     * This method resolves the action from the container and then calls the specified method
     * with the provided parameters. It allows for dependency injection if needed.
     *
     * @param  string  $method  The name of the method to call.
     * @param  array  $extraArguments  The extra arguments to pass to the method.
     *
     * @return mixed  The result of the method call.
     */
    protected function resolveAndCallMethod(string $method, array $extraArguments = [])
    {
        // Resolve the action instance from the container
        $instance = Container::resolve($this->action);

        // Call the specified method on the resolved instance with the extra arguments
        return call_user_func_array([$instance, $method], $extraArguments);
    }

    /**
     * Attempt to call a method on the action or return a default value.
     *
     * This method tries to call a method on the action instance. If the method does not exist,
     * it returns the provided default value.
     *
     * @param  string  $method  The name of the method to call.
     * @param  array  $methodParameters  The parameters to pass to the method.
     * @param  mixed  $default  The default value to return if the method does not exist.
     *
     * @return mixed  The result of the method call or the default value.
     */
    protected function fromActionMethod(string $method, array $methodParameters = [], $default = null)
    {
        // Check if the method exists, and call it if so
        return $this->hasMethod($method)
            ? $this->callMethod($method, $methodParameters)  // Call the method if it exists
            : value($default);  // Return the default value if the method does not exist
    }

    /**
     * Retrieve a property from the action or return a default value.
     *
     * This method retrieves the value of a property from the action instance.
     * If the property does not exist, it returns the provided default value.
     *
     * @param  string  $property  The name of the property to retrieve.
     * @param  mixed  $default  The default value to return if the property does not exist.
     *
     * @return mixed  The value of the property or the default value.
     */
    protected function fromActionProperty(string $property, $default = null)
    {
        // Check if the property exists, and return its value if so
        return $this->hasProperty($property)
            ? $this->getProperty($property)  // Return the property value if it exists
            : value($default);  // Return the default value if the property does not exist
    }

    /**
     * Attempt to retrieve a value from the action's method or property.
     *
     * This method checks if the specified method or property exists in the action instance.
     * If the method exists, it is called; otherwise, the property is retrieved. If neither
     * exist, the default value is returned.
     *
     * @param  string  $method  The name of the method to check and call if it exists.
     * @param  string  $property  The name of the property to check and return if the method does not exist.
     * @param  mixed  $default  The default value to return if neither the method nor property exists.
     * @param  array  $methodParameters  The parameters to pass to the method if it exists.
     *
     * @return mixed  The result of the method or property, or the default value.
     */
    protected function fromActionMethodOrProperty(string $method, string $property, $default = null, array $methodParameters = [])
    {
        // Check if the method exists, and call it if so
        if ($this->hasMethod($method)) {
            return $this->callMethod($method, $methodParameters);
        }

        // If the method doesn't exist, check for the property and return its value
        if ($this->hasProperty($property)) {
            return $this->getProperty($property);
        }

        // Return the default value if neither the method nor the property exists
        return value($default);
    }
}
