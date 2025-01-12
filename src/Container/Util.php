<?php

declare(strict_types=1);

namespace Maginium\Framework\Container;

use Closure;
use Maginium\Framework\Container\Interfaces\ContextualAttributeInterface;
use ReflectionAttribute;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Utility class for container-related operations.
 *
 * This class contains static utility methods used for handling arrays, closures,
 * and reflection for parameter types and attributes in the container context.
 */
class Util
{
    /**
     * If the given value is not an array and not null, wrap it in one.
     *
     * This method ensures that any value passed into it is returned as an array,
     * unless it's already an array or null. This is a common utility in many frameworks
     * to handle single and multiple value cases uniformly.
     *
     * @param  mixed  $value  The value to wrap.
     *
     * @return array  An array containing the value, or an empty array if the value is null.
     */
    public static function arrayWrap($value)
    {
        // If value is null, return an empty array
        if ($value === null) {
            return [];
        }

        // If value is already an array, return it, otherwise wrap it in an array
        return is_array($value) ? $value : [$value];
    }

    /**
     * Return the default value of the given value, resolving closures if necessary.
     *
     * This method unwraps the value if it is a closure, calling the closure with
     * the provided arguments. If the value is not a closure, it returns the value itself.
     *
     * @param  mixed  $value  The value to potentially unwrap.
     * @param  mixed  ...$args  Additional arguments to pass to the closure if it is callable.
     *
     * @return mixed  The value or the result of the closure.
     */
    public static function unwrapIfClosure($value, ...$args)
    {
        // If the value is a closure, invoke it with the provided arguments
        return $value instanceof Closure ? $value(...$args) : $value;
    }

    /**
     * Get the class name of the given parameter's type, if possible.
     *
     * This method retrieves the type of a parameter and returns the class name if it's
     * a non-builtin class type. If the parameter is self or parent, it returns the
     * appropriate class name from the context.
     *
     * @param  ReflectionParameter  $parameter  The reflection parameter to inspect.
     *
     * @return string|null  The class name of the parameter type, or null if it is a built-in type.
     */
    public static function getParameterClassName($parameter)
    {
        // Retrieve the parameter's type
        $type = $parameter->getType();

        // If the type is not a named type or is a built-in type (e.g., string, int), return null
        if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return;
        }

        $name = $type->getName();

        // If the parameter is in the context of a class, resolve self or parent type
        if (null !== ($class = $parameter->getDeclaringClass())) {
            if ($name === 'self') {
                return $class->getName();  // Return the class name if 'self'
            }

            if ($name === 'parent' && $parent = $class->getParentClass()) {
                return $parent->getName();  // Return the parent's class name if 'parent'
            }
        }

        // Otherwise, return the class name directly
        return $name;
    }

    /**
     * Get a contextual attribute from a dependency.
     *
     * This method checks if the given reflection parameter has a contextual attribute
     * associated with it, which can be used for container-based resolution.
     *
     * @param  ReflectionParameter  $dependency  The reflection parameter to check for contextual attributes.
     *
     * @return ReflectionAttribute|null  The contextual attribute if found, otherwise null.
     */
    public static function getContextualAttributeFromDependency($dependency)
    {
        // Check if the parameter has attributes of type ContextualAttributeInterface
        return $dependency->getAttributes(ContextualAttributeInterface::class, ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;
    }
}
