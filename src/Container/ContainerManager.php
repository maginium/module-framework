<?php

declare(strict_types=1);

namespace Maginium\Framework\Container;

use Closure;
use Illuminate\Contracts\Container\BindingResolutionException;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\ObjectManager\ConfigInterface;
use Magento\Framework\ObjectManagerInterface;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Framework\Container\Interfaces\ContainerInterface;
use Maginium\Framework\Container\Interfaces\ContextualAttributeInterface;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Php;
use ReflectionAttribute;
use ReflectionFunction;

/**
 * Class ContainerManager.
 *
 * This class serves as a dependency resolution and management tool, leveraging Magento's Object Manager
 * to handle object creation and dependency injection. It adheres to the `ContainerInterface`, ensuring
 * it provides the necessary contract for container-based operations.
 */
class ContainerManager implements ContainerInterface
{
    /**
     * The contextual attribute handlers.
     *
     * @var array[]
     */
    public $contextualAttributes = [];

    /**
     * The stack of concretions currently being built.
     *
     * @var array[]
     */
    protected $buildStack = [];

    /**
     * The container's method bindings.
     *
     * @var Closure[]
     */
    protected $methodBindings = [];

    /**
     * All of the after resolving attribute callbacks by class type.
     *
     * @var array[]
     */
    protected $afterResolvingAttributeCallbacks = [];

    /**
     * @var ObjectManagerInterface|null Object Manager instance.
     */
    protected ?ObjectManagerInterface $objectManager = null;

    /**
     * Container constructor.
     *
     * @param  ObjectManagerInterface|null  $objectManager  Object Manager instance (optional).
     */
    public function __construct(?ObjectManagerInterface $objectManager = null)
    {
        $this->objectManager = $objectManager ?? ObjectManager::getInstance();
    }

    /**
     * Retrieve a singleton instance of a specified class.
     *
     * @param  string  $className  The class name to retrieve.
     *
     * @throws InvalidArgumentException If $className is null or empty.
     *
     * @return mixed The singleton instance of the specified class.
     */
    public function get(string $className): mixed
    {
        if ($className === null || $className === '') {
            throw InvalidArgumentException::make(__('The class name cannot be null or empty.'));
        }

        return $this->objectManager->get($className);
    }

    /**
     * Returns the current instance of the ContainerManager.
     *
     * @return ContainerInterface The current instance of this class.
     */
    public function getInstance(): ContainerInterface
    {
        return $this;
    }

    /**
     * Determine if the container has a binding or preference for a given abstract type.
     *
     * This method checks if the container has a mapping for the provided abstract class
     * or interface name, ensuring that the name is valid and exists in the container bindings.
     *
     * @param string $abstract The abstract class or interface name to check.
     *
     * @throws InvalidArgumentException If the provided abstract type is null, empty, or invalid.
     *
     * @return bool True if the binding or preference exists, false otherwise.
     */
    public function has(string $abstract): bool
    {
        // Validate the provided abstract class or interface name
        if ($abstract === null || $abstract === '') {
            throw InvalidArgumentException::make(__('The class name cannot be null or empty.'));
        }

        // Retrieve all container bindings
        $bindings = $this->getBindings();

        // Check if the abstract type exists in the bindings array
        return isset($bindings[$abstract]);
    }

    /**
     * Check if a module is active and resolve an instance of a specified class.
     *
     * @param  string|null  $className  The class name to resolve.
     * @param  array|null  $arguments  The class arguments to resolve.
     *
     * @throws InvalidArgumentException If $className is null or empty.
     *
     * @return object|null The resolved instance or null if not resolved.
     */
    public function resolve(?string $className, ?array $arguments = []): ?object
    {
        if ($className === null || $className === '') {
            throw InvalidArgumentException::make(__('$className cannot be null or empty'));
        }

        // Check if the class can be resolved
        if ($this->has($className)) {
            // If the class can be resolved, return a singleton instance
            return $this->get($className);
        }

        // If not, create a new instance
        return $this->make($className, $arguments);
    }

    /**
     * Create a new instance of a specified class.
     *
     * @param  string  $className  The class name to instantiate.
     * @param  mixed  ...$arguments  Optional arguments to pass to the class constructor.
     *
     * @throws InvalidArgumentException If $className is null or empty.
     *
     * @return mixed The instance of the specified class.
     */
    public function make(string $className, ...$arguments): mixed
    {
        // Validate that the class name is not null or empty.
        if ($className === null || $className === '') {
            throw InvalidArgumentException::make(__('The class name cannot be null or empty.'));
        }

        // Call the object manager to create an instance of the specified class with the prepared parameters.
        return $this->objectManager->create($className, ...$arguments);
    }

    /**
     * Check if a module is installed and enabled.
     *
     * @param  string  $moduleName  The name of the module to check.
     *
     * @throws InvalidArgumentException If $moduleName is null or empty.
     *
     * @return bool True if the module is enabled, false otherwise.
     */
    public function isEnabled(string $moduleName): bool
    {
        if ($moduleName === null || $moduleName === '') {
            // Throw an exception if $moduleName is null or empty
            // Throw the exception
            throw InvalidArgumentException::make(__('$moduleName cannot be null or empty'));
        }

        // Get the Module Manager instance
        $moduleManager = $this->get(ModuleManager::class);

        // Check if the module is enabled using Module Manager
        return $moduleManager->isEnabled($moduleName);
    }

    /**
     * Retrieve all bindings in the container.
     *
     * This method returns an associative array of all class bindings
     * available in the object manager.
     *
     * @return array An associative array of class bindings.
     */
    public function getBindings(): array
    {
        // Get the configuration object that contains the preferences
        $config = $this->get(ConfigInterface::class);

        // Retrieve and return an associative array of class bindings from preferences
        return $config->getPreferences();
    }

    /**
     * Determine if the container has a method binding.
     *
     * @param  string  $method The method name to check for binding.
     *
     * @return bool True if the method is bound, otherwise false.
     */
    public function hasMethodBinding($method): bool
    {
        // Check if the given method exists in the methodBindings array.
        return isset($this->methodBindings[$method]);
    }

    /**
     * Bind a callback to resolve with Container::call.
     *
     * @param  array|string  $method The method to bind.
     * @param  Closure  $callback The callback to bind to the method.
     *
     * @return void
     */
    public function bindMethod($method, $callback): void
    {
        // Parse the method to its class@method format and bind it to the callback
        $this->methodBindings[$this->parseBindMethod($method)] = $callback;
    }

    /**
     * Get the method binding for the given method.
     *
     * @param  string  $method The method name to call.
     * @param  mixed  $instance The instance to call the method on.
     *
     * @return mixed The result of the method call.
     */
    public function callMethodBinding($method, $instance): mixed
    {
        // Call the method binding with the provided instance and the container
        return call_user_func($this->methodBindings[$method], $instance, $this);
    }

    /**
     * Call the given Closure / class@method and inject its dependencies.
     *
     * @param  callable|string  $callback The callback to call.
     * @param  array<string, mixed>  $parameters Parameters to inject into the callback.
     * @param  string|null  $defaultMethod The default method to call, if applicable.
     *
     * @throws \InvalidArgumentException If the callback is invalid or the method cannot be found.
     *
     * @return mixed The result of the callback execution.
     */
    public function call($callback, array $parameters = [], $defaultMethod = null): mixed
    {
        // Initialize a flag to track if the class has been pushed to the build stack
        $pushedToBuildStack = false;

        // Determine the class name for the callback and ensure it's not already in the build stack
        if (($className = $this->getClassForCallable($callback)) && ! in_array(
            $className,
            $this->buildStack,
            true,
        )) {
            // Add the class name to the build stack
            $this->buildStack[] = $className;

            // Set the flag indicating that the class was pushed
            $pushedToBuildStack = true;
        }

        // Call the method using BoundMethod and return the result
        $result = BoundMethod::call($this, $callback, $parameters, $defaultMethod);

        // If the class was pushed to the stack, pop it off after the call
        if ($pushedToBuildStack) {
            Arr::pop($this->buildStack);
        }

        // Return the result of the callback call
        return $result;
    }

    /**
     * Resolve a dependency based on an attribute.
     *
     * @param  ReflectionAttribute  $attribute The attribute to resolve from.
     *
     * @return mixed The resolved dependency.
     */
    public function resolveFromAttribute(ReflectionAttribute $attribute): mixed
    {
        // Get the handler registered for this attribute, if any
        $handler = $this->contextualAttributes[$attribute->getName()] ?? null;

        // Create an instance of the attribute
        $instance = $attribute->newInstance();

        // If no handler is found and the instance has a resolve method, call it
        if ($handler === null && method_exists($instance, 'resolve')) {
            $handler = $instance->resolve(...);
        }

        // If no handler is found, throw an exception
        if ($handler === null) {
            throw new BindingResolutionException("Contextual binding attribute [{$attribute->getName()}] has no registered handler.");
        }

        // Return the resolved dependency
        return $handler($instance, $this);
    }

    /**
     * Fire all of the after resolving attribute callbacks.
     *
     * @param  ReflectionAttribute[]  $attributes List of attributes to fire callbacks for.
     * @param  mixed  $object The object being resolved.
     *
     * @return void
     */
    public function fireAfterResolvingAttributeCallbacks(array $attributes, $object): void
    {
        // Iterate through each attribute
        foreach ($attributes as $attribute) {
            // Check if the attribute implements ContextualAttributeInterface
            if (is_a($attribute->getName(), ContextualAttributeInterface::class, true)) {
                // Create an instance of the attribute
                $instance = $attribute->newInstance();

                // If the instance has an 'after' method, call it
                if (method_exists($instance, 'after')) {
                    $instance->after($instance, $object, $this);
                }
            }

            // Get the callbacks for the given attribute type
            $callbacks = $this->getCallbacksForType(
                $attribute->getName(),
                $object,
                $this->afterResolvingAttributeCallbacks,
            );

            // Execute each callback
            foreach ($callbacks as $callback) {
                $callback($attribute->newInstance(), $object, $this);
            }
        }
    }

    /**
     * Get all callbacks for a given type.
     *
     * @param  string  $abstract The type to search for callbacks.
     * @param  object  $object The object to check callbacks for.
     * @param  array  $callbacksPerType All registered callbacks for each type.
     *
     * @return array List of callbacks associated with the type.
     */
    protected function getCallbacksForType($abstract, $object, array $callbacksPerType)
    {
        // Initialize an array to store matching callbacks
        $results = [];

        // Iterate through the callbacks per type
        foreach ($callbacksPerType as $type => $callbacks) {
            // If the type matches or the object is an instance of the type, merge callbacks
            if ($type === $abstract || $object instanceof $type) {
                $results = Arr::merge($results, $callbacks);
            }
        }

        // Return the list of matching callbacks
        return $results;
    }

    /**
     * Get the class name for the given callback, if one can be determined.
     *
     * @param  callable|string  $callback The callback to check for a class.
     *
     * @return string|false The class name if callable, otherwise false.
     */
    protected function getClassForCallable($callback): string|false
    {
        // If the callback is callable and is not an anonymous function
        if (is_callable($callback) &&
            ! ($reflector = new ReflectionFunction($callback(...)))->isAnonymous()) {
            // Return the class name of the callback's closure scope
            return $reflector->getClosureScopeClass()->name ?? false;
        }

        // Return false if no class can be determined
        return false;
    }

    /**
     * Get the method to be bound in class@method format.
     *
     * @param  array|string  $method The method to bind.
     *
     * @return string The method in class@method format.
     */
    protected function parseBindMethod($method): string
    {
        // If the method is an array (class and method), return the class@method format
        if (is_array($method)) {
            return $method[0] . '@' . $method[1];
        }

        // If it's a string, return it as is
        return $method;
    }

    /**
     * Extract module name from the full class name.
     *
     * @param  string  $className  The full class name.
     *
     * @return string The module name extracted from the class name.
     */
    private function extractModuleName(string $className): string
    {
        // Split the class name by namespace separator and extract the first two parts
        $parts = Php::explode('\\', $className, 3);

        // Form the module name by joining the first two parts with an underscore
        return Php::implode('_', Php::arraySlice($parts, 0, 2));
    }
}
