<?php

declare(strict_types=1);

namespace Maginium\Framework\Container;

use Closure;
use Illuminate\Contracts\Container\BindingResolutionException;
use InvalidArgumentException;
use Maginium\Framework\Container\Interfaces\ContainerInterface;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Reflection;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;

/**
 * Class BoundMethod.
 *
 * This class is responsible for resolving and invoking methods or closures,
 * injecting their required dependencies from the container. It supports both
 * closures and class-method invocations, handling the resolution of parameters
 * and their dependencies automatically.
 *
 * It is primarily used within the container to ensure that the appropriate
 * dependencies are injected into methods when they are called.
 */
class BoundMethod
{
    /**
     * Call the given Closure / class@method and inject its dependencies.
     *
     * This method resolves the dependencies of the given callback or method,
     * and then calls the method, injecting any required parameters from the container.
     * It supports both closure-style callbacks and class-method invocations.
     *
     * @param  ContainerInterface  $container The container to resolve dependencies from.
     * @param  callable|string  $callback The callback or method to call.
     * @param  array  $parameters Optional parameters to pass to the method.
     * @param  string|null  $defaultMethod The default method to call, if no method is specified.
     *
     * @throws ReflectionException If there is an error while reflecting the method.
     * @throws InvalidArgumentException If the callback is invalid or no method is found.
     *
     * @return mixed The result of the method call.
     */
    public static function call(ContainerInterface $container, $callback, array $parameters = [], $defaultMethod = null): mixed
    {
        // Check if the callback is a string and has an invoke method, use it as default method
        if (is_string($callback) && ! $defaultMethod && Reflection::methodExists($callback, '__invoke')) {
            $defaultMethod = '__invoke';
        }

        // If the callback uses "Class@method" syntax or a default method is provided,
        // call the class method.
        if (static::isCallableWithAtSign($callback) || $defaultMethod) {
            return static::callClass($container, $callback, $parameters, $defaultMethod);
        }

        // Otherwise, treat it as a bound method and call it with injected dependencies
        return static::callBoundMethod($container, $callback, fn() => $callback(...Arr::values(array: static::getMethodDependencies($container, $callback, $parameters))));
    }

    /**
     * Call a string reference to a class using Class@method syntax.
     *
     * This method splits the "Class@method" string, retrieves the class, and calls the
     * specified method, resolving dependencies from the container.
     *
     * @param  ContainerInterface  $container The container to resolve dependencies from.
     * @param  string  $target The class and method in "Class@method" format.
     * @param  array  $parameters Optional parameters to pass to the method.
     * @param  string|null  $defaultMethod The default method to call if no method is provided.
     *
     * @throws InvalidArgumentException If the method is not provided.
     *
     * @return mixed The result of the method call.
     */
    protected static function callClass(ContainerInterface $container, $target, array $parameters = [], $defaultMethod = null): mixed
    {
        $segments = explode('@', $target);

        // Split the "Class@method" string into class and method parts
        $method = count($segments) === 2 ? $segments[1] : $defaultMethod;

        // If method is not provided, throw an exception
        if ($method === null) {
            throw new InvalidArgumentException('Method not provided.');
        }

        // Resolve and call the class method with the container
        return static::call(
            $container,
            [$container->resolve($segments[0]), $method],
            $parameters,
        );
    }

    /**
     * Call a method that has been bound to the container.
     *
     * This method resolves a method binding from the container and calls it with
     * the provided callback and dependencies.
     *
     * @param  ContainerInterface  $container The container to resolve dependencies from.
     * @param  callable  $callback The callback or method to call.
     * @param  mixed  $default The default value to return if no method binding exists.
     *
     * @return mixed The result of the method call or the default value if no method binding exists.
     */
    protected static function callBoundMethod(ContainerInterface $container, $callback, $default): mixed
    {
        // If the callback is not an array, simply return the default value
        if (! is_array($callback)) {
            return Util::unwrapIfClosure($default);
        }

        // Normalize the method from the array callback (Class@method format)
        $method = static::normalizeMethod($callback);

        // If a method binding exists, call the bound method with the container
        if ($container->hasMethodBinding($method)) {
            return $container->callMethodBinding($method, $callback[0]);
        }

        // Otherwise, return the default value
        return Util::unwrapIfClosure($default);
    }

    /**
     * Normalize the given callback into a Class@method string.
     *
     * This method ensures that the callback is a valid callable array in the format of [class, method],
     * and returns the corresponding "Class@method" string.
     *
     * @param  callable  $callback The callback to normalize.
     *
     * @throws InvalidArgumentException If the callback is not a valid callable array.
     *
     * @return string A normalized class@method string.
     */
    protected static function normalizeMethod($callback): string
    {
        // Check if the callback is a valid callable array with [class, method] format
        if (! is_array($callback) || count($callback) !== 2 || ! is_string($callback[1])) {
            throw new InvalidArgumentException('Invalid callback provided. Must be an array of [class, method].');
        }

        // Extract the class and method from the callback
        $class = is_string($callback[0]) ? $callback[0] : get_class($callback[0]);

        // Return the normalized "Class@method" string
        return "{$class}@{$callback[1]}";
    }

    /**
     * Get all dependencies for a given method.
     *
     * This method retrieves all dependencies that are required for the given callback,
     * resolving the parameters by checking the container and any available parameters.
     *
     * @param  ContainerInterface  $container  The container used to resolve dependencies.
     * @param  callable|string     $callback  The callback (method or function) to inspect for dependencies.
     * @param  array               $parameters  An array of predefined parameters that might override the default resolution.
     *
     * @throws ReflectionException If reflection fails while retrieving method parameters.
     *
     * @return array  An array of resolved dependencies for the given method.
     */
    protected static function getMethodDependencies(ContainerInterface $container, $callback, array $parameters = [])
    {
        $dependencies = [];

        // Loop through each parameter of the callable (method/function) and resolve dependencies
        foreach (static::getCallReflector($callback)->getParameters() as $parameter) {
            static::addDependencyForCallParameter($container, $parameter, $parameters, $dependencies);
        }

        // Merge additional parameters passed in directly with the resolved dependencies
        return Arr::merge($dependencies, Arr::values($parameters));
    }

    /**
     * Get the proper reflection instance for the given callback.
     *
     * This method creates and returns a reflection instance (ReflectionMethod or ReflectionFunction)
     * depending on the type of the provided callback (i.e., string, callable, or object).
     *
     * @param  callable|string  $callback  The callback (method or function) to reflect upon.
     *
     * @throws ReflectionException If reflection fails for the given callback.
     *
     * @return ReflectionFunctionAbstract  The reflection instance of the provided callback.
     */
    protected static function getCallReflector($callback)
    {
        // Handle string references with class::method syntax
        if (is_string($callback) && str_contains($callback, '::')) {
            $callback = explode('::', $callback);
        } elseif (is_object($callback) && ! $callback instanceof Closure) {
            // If it's an object, assume the callable is the __invoke method
            $callback = [$callback, '__invoke'];
        }

        // Return the correct ReflectionMethod or ReflectionFunction based on the callback type
        return is_array($callback)
            ? new ReflectionMethod($callback[0], $callback[1])
            : new ReflectionFunction($callback);
    }

    /**
     * Get the dependency for the given call parameter.
     *
     * This method resolves and injects the appropriate dependency for a given parameter.
     * It checks if the dependency is provided in the method's parameters or if it can
     * be resolved from the container, including contextual attributes or class names.
     *
     * @param  ContainerInterface  $container  The container used to resolve dependencies.
     * @param  ReflectionParameter $parameter  The reflection parameter for which we need to resolve the dependency.
     * @param  array               $parameters  The predefined parameters passed into the method.
     * @param  array               $dependencies  The array to hold the resolved dependencies.
     *
     * @throws BindingResolutionException If a dependency cannot be resolved.
     *
     * @return void  This method does not return any value. It directly modifies the $dependencies array.
     */
    protected static function addDependencyForCallParameter(
        ContainerInterface $container,
        $parameter,
        array &$parameters,
        &$dependencies,
    ) {
        $pendingDependencies = [];

        // If the parameter is directly passed in the method parameters, use it
        if (Arr::keyExists($paramName = $parameter->getName(), $parameters)) {
            $pendingDependencies[] = $parameters[$paramName];
            unset($parameters[$paramName]);
        }
        // If a contextual attribute is available for the parameter, resolve it from the container
        elseif ($attribute = Util::getContextualAttributeFromDependency($parameter)) {
            $pendingDependencies[] = $container->resolveFromAttribute($attribute);
        }
        // If the parameter has a class type, resolve it from the container
        elseif (null !== ($className = Util::getParameterClassName($parameter))) {
            // Check if the parameter class is passed in the parameters
            if (Arr::keyExists($className, $parameters)) {
                $pendingDependencies[] = $parameters[$className];
                unset($parameters[$className]);
            } elseif ($parameter->isVariadic()) {
                // If the parameter is variadic, resolve multiple dependencies
                $variadicDependencies = $container->resolve($className);
                $pendingDependencies = Arr::merge($pendingDependencies, is_array($variadicDependencies)
                    ? $variadicDependencies
                    : [$variadicDependencies]);
            } else {
                // Resolve a single dependency from the container
                $pendingDependencies[] = $container->resolve($className);
            }
        }
        // If the parameter has a default value, use that as a fallback
        elseif ($parameter->isDefaultValueAvailable()) {
            $pendingDependencies[] = $parameter->getDefaultValue();
        }
        // If the parameter is required but no dependency can be found, throw an exception
        elseif (! $parameter->isOptional() && ! Arr::keyExists($paramName, $parameters)) {
            $message = "Unable to resolve dependency [{$parameter}] in class {$parameter->getDeclaringClass()->getName()}";

            throw new BindingResolutionException($message);
        }

        // Apply any "after resolving" callbacks to the resolved dependencies
        foreach ($pendingDependencies as $dependency) {
            $container->fireAfterResolvingAttributeCallbacks($parameter->getAttributes(), $dependency);
        }

        // Add the resolved dependencies to the list
        $dependencies = Arr::merge($dependencies, $pendingDependencies);
    }

    /**
     * Determine if the given string is in Class@method syntax.
     *
     * This method checks whether the given callback is in the class@method format, which
     * is commonly used to reference a method in a class.
     *
     * @param  mixed  $callback  The callback to check.
     *
     * @return bool  True if the callback is in the Class@method syntax, otherwise false.
     */
    protected static function isCallableWithAtSign($callback)
    {
        return is_string($callback) && str_contains($callback, '@');
    }
}
