<?php

declare(strict_types=1);

namespace Maginium\Framework\Support;

use Closure;
use Illuminate\Support\Reflector as BaseReflector;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Class Reflection.
 *
 * Utility class to validate and retrieve class properties and methods using PHP's ReflectionClass.
 * This class acts as a wrapper around the base Reflector functionality.
 */
class Reflection extends BaseReflector
{
    /**
     * Static cache for storing ReflectionClass instances.
     *
     * @var array<string, ReflectionClass>
     */
    private static array $cache = [];

    /**
     * Gets the ReflectionClass instance for a given class or object.
     *
     * This method returns a ReflectionClass object which provides detailed information
     * about a class, such as its properties, methods, constants, and more.
     * It can be used to inspect the structure of a class or its instance.
     *
     * @param string|object $classOrObject The name of the class (string) or an instance of the class (object).
     *
     * @return ReflectionClass The ReflectionClass instance associated with the provided class or object.
     */
    public static function getClass(string|object $classOrObject): ReflectionClass
    {
        return static::getReflectionClass($classOrObject);
    }

    /**
     * Gets the ReflectionFunction instance for a given function.
     *
     * This method returns a ReflectionFunction object which provides detailed information
     * about a function, such as its parameters, return type, and more.
     * It can be used to inspect the structure of a function.
     *
     * @param Closure|string $function The name of the function.
     *
     * @throws InvalidArgumentException if the function does not exist.
     *
     * @return ReflectionFunction The ReflectionFunction instance associated with the provided function name.
     */
    public static function getFunction(Closure|string $function): ReflectionFunction
    {
        if (Validator::isString($function) && ! function_exists($function)) {
            throw new InvalidArgumentException("Function '{$function}' does not exist.");
        }

        return new ReflectionFunction($function);
    }

    /**
     * Check if the class exists in the current environment.
     *
     * This method verifies whether the class is defined in the current PHP runtime environment.
     * It uses PHP's built-in `class_exists()` function to check if the class has been declared.
     *
     * @param string $class The name of the class to check for existence.
     *
     * @return bool True if the class exists, false if it does not.
     */
    public static function exists(string $class): bool
    {
        return class_exists($class);
    }

    /**
     * Check if a method exists in the given class or object.
     *
     * This method checks if a specified method exists within the given class or object.
     * It uses the `hasMethod()` method of the `ReflectionClass` to verify if the method is part of the class.
     *
     * @param string|object $classOrObject The class or object in which to check for the method.
     * @param string $method The name of the method to check.
     *
     * @return bool True if the method exists in the class, false if it does not.
     */
    public static function methodExists(string|object $classOrObject, string $method): bool
    {
        // Retrieve the ReflectionClass instance for the class or object
        $reflectionClass = static::getClass($classOrObject);

        // Check if the method exists in the class
        return $reflectionClass->hasMethod($method);
    }

    /**
     * Check if a property exists in the given class or object.
     *
     * This method checks if a specified property exists within the given class or object.
     * It uses the `hasProperty()` method of the `ReflectionClass` to check for the presence of the property.
     *
     * @param string|object $classOrObject The class or object in which to check for the property.
     * @param string $property The name of the property to check.
     *
     * @return bool True if the property exists in the class, false if it does not.
     */
    public static function propertyExists(string|object $classOrObject, string $property): bool
    {
        // Retrieve the ReflectionClass instance for the class or object
        $reflectionClass = static::getClass($classOrObject);

        // Check if the property exists in the class
        return $reflectionClass->hasProperty($property);
    }

    /**
     * Check if a constant exists in the given class or object.
     *
     * This method checks if a specified constant exists within the given class or object.
     * It uses the `hasConstant()` method of the `ReflectionClass` to check for the constant.
     *
     * @param string|object $classOrObject The class or object in which to check for the constant.
     * @param string $constant The name of the constant to check.
     *
     * @return bool True if the constant exists in the class, false if it does not.
     */
    public static function constantExists(string|object $classOrObject, string $constant): bool
    {
        // Retrieve the ReflectionClass instance for the class or object
        $reflectionClass = static::getClass($classOrObject);

        // Check if the constant exists in the class
        return $reflectionClass->hasConstant($constant);
    }

    /**
     * Check if the class has any attributes defined.
     *
     * This method checks whether the class has any attributes (annotations or metadata)
     * defined using PHP 8.0 attributes. It utilizes the `getAttributes()` method of `ReflectionClass`
     * to fetch all the attributes associated with the class and counts them.
     *
     * @param string|object $classOrObject The class or object to check.
     *
     * @return bool True if the class has attributes, false otherwise.
     */
    public static function hasAttributes(string|object $classOrObject): bool
    {
        // Retrieve the ReflectionClass instance for the class or object
        $reflectionClass = static::getClass($classOrObject);

        // Check if the class has any attributes (annotations)
        return count($reflectionClass->getAttributes()) > 0;
    }

    /**
     * Check if the class uses any traits.
     *
     * This method checks if the class utilizes any traits, which are code blocks
     * that can be reused in multiple classes. It uses the `getTraits()` method of `ReflectionClass`
     * to retrieve the traits used by the class.
     *
     * @param string|object $classOrObject The class or object to check.
     *
     * @return bool True if the class has traits, false otherwise.
     */
    public static function hasTraits(string|object $classOrObject): bool
    {
        // Retrieve the ReflectionClass instance for the class or object
        $reflectionClass = static::getClass($classOrObject);

        // Check if the class has any traits
        return count($reflectionClass->getTraits()) > 0;
    }

    /**
     * Gets the normalized class name using reflection.
     *
     * This method returns the normalized name of the class from an object or a class name.
     * The class name is normalized by removing unnecessary suffixes and trimming backslashes.
     *
     * @param string|object $classOrObject The name of the class or an instance of the class.
     *
     * @return string The normalized class name.
     */
    public static function getClassName(string|object $classOrObject): string
    {
        // Get the fully qualified class name using reflection
        $className = static::getClass($classOrObject)->getName();

        // Normalize the class name
        return static::normalizeClassName($className);
    }

    /**
     * Get the basename of a class name without the namespace, optionally removing a specified string from the beginning or end.
     *
     * This function extracts the class name from its fully qualified name,
     * normalizes it by removing unnecessary suffixes, then removes any specified string
     * and returns the resulting class name.
     *
     * @param string $class The fully qualified class name.
     * @param string|null $stringToRemove Optional string to remove from the class name.
     *
     * @return string The basename of the class, with the specified string removed if present.
     */
    public static function getClassBasename(string $class, ?string $stringToRemove = null): string
    {
        // Normalize the class name
        $class = static::normalizeClassName($class);

        // Replace backslashes with forward slashes for consistent path handling
        $class = Str::replace('\\', SP, $class);

        // Get the basename by extracting the last part after the last slash
        $basename = basename($class);

        // If a string to remove is provided, check for prefix and suffix removal
        if ($stringToRemove !== null) {
            // Check if the basename starts with the string to remove
            if (str_starts_with($basename, $stringToRemove)) {
                $basename = mb_substr($basename, mb_strlen($stringToRemove));
            }

            // Check if the basename ends with the string to remove
            if (mb_substr($basename, -mb_strlen($stringToRemove)) === $stringToRemove) {
                $basename = mb_substr($basename, 0, -mb_strlen($stringToRemove));
            }
        }

        // Remove any leading underscores resulting from prefix/suffix removal
        $basename = Str::ltrim($basename, '_');

        return $basename;
    }

    /**
     * Parses a PHP file and retrieves the normalized fully qualified class name with namespace.
     *
     * This method takes into account potential conflicts with docblocks by ensuring that only
     * the actual class declaration is matched. The returned class name is normalized.
     *
     * @param string $file_path The path to the PHP file.
     *
     * @return string|null The normalized fully qualified class name, or null if not found.
     */
    public static function getFullyQualifiedClassName(string $file_path): ?string
    {
        // Read the file contents
        $file_contents = file_get_contents($file_path);

        // Pattern to capture the namespace
        $namespace_pattern = '/namespace\s+([^\s;]+);/i';

        // Pattern to capture the class name, avoiding docblocks and ensuring it's the actual class declaration
        $class_pattern = '/(?:\/\*\*.*?\*\/\s*)?class\s+(\w+)/is';

        // Initialize namespace and class name
        $namespace = '';
        $class_name = '';

        // Find the namespace
        if (preg_match($namespace_pattern, $file_contents, $matches)) {
            $namespace = trim($matches[1]);
        }

        if (! $namespace) {
            return null;
        }

        // Find the class name
        if (preg_match($class_pattern, $file_contents, $matches)) {
            $class_name = trim($matches[1]);
        }

        // Combine the namespace and class name to get the fully qualified class name
        $fullyQualifiedClassName = rtrim($namespace, '\\') . '\\' . $class_name;

        // Normalize the fully qualified class name before returning
        return static::normalizeClassName($fullyQualifiedClassName);
    }

    /**
     * Gets the short name of the class using reflection.
     *
     * This method returns the short name of the class from an object or a class name.
     *
     * @param string|object $classOrObject The name of the class or an instance of the class.
     *
     * @return string The short name of the class.
     */
    public static function getClassShortName(string|object $classOrObject): string
    {
        // Get the fully qualified class name using reflection
        $className = static::getClass($classOrObject)->getShortName();

        // Normalize the class name
        return static::normalizeClassName($className);
    }

    /**
     * Gets the file name where the class is defined.
     *
     * @param string|object $classOrObject The name of the class or an instance to check.
     *
     * @return string The file name.
     */
    public static function getFileName(string|object $classOrObject): ?string
    {
        return static::getClass($classOrObject)->getFileName();
    }

    /**
     * Gets the start line of the class definition in the file.
     *
     * @param string|object $classOrObject The name of the class or an instance to check.
     *
     * @return int The start line number.
     */
    public static function getStartLine(string|object $classOrObject): int
    {
        return static::getClass($classOrObject)->getStartLine();
    }

    /**
     * Gets the end line of the class definition in the file.
     *
     * @param string|object $classOrObject The name of the class or an instance to check.
     *
     * @return int The end line number.
     */
    public static function getEndLine(string|object $classOrObject): int
    {
        return static::getClass($classOrObject)->getEndLine();
    }

    /**
     * Gets the doc comment of the class.
     *
     * @param string|object $classOrObject The name of the class or an instance to check.
     *
     * @return string|null The doc comment of the class, or null if not present.
     */
    public static function getDocComment(string|object $classOrObject): ?string
    {
        return static::getClass($classOrObject)->getDocComment();
    }

    /**
     * Check if the provided enum class is a native enum (BackedEnum or UnitEnum).
     *
     * @param string $enumClass The enum class name to check.
     *
     * @return bool True if the class is a native enum, false otherwise.
     */
    public static function isEnum(string $classOrObject): bool
    {
        // Check if the class is a native enum (unit or backed enum)
        return static::getClass($classOrObject)->isEnum();
    }

    /**
     * Checks if the given class is abstract.
     *
     * @param string|object $classOrObject The name of the class or an instance to check.
     *
     * @return bool True if the class is abstract, false otherwise.
     */
    public static function isAbstract(string|object $classOrObject): bool
    {
        return static::getClass($classOrObject)->isAbstract();
    }

    /**
     * Checks if the given class is final.
     *
     * @param string|object $classOrObject The name of the class or an instance to check.
     *
     * @return bool True if the class is final, false otherwise.
     */
    public static function isFinal(string|object $classOrObject): bool
    {
        return static::getClass($classOrObject)->isFinal();
    }

    /**
     * Checks if the given class is instantiable.
     *
     * @param string|object $classOrObject The name of the class or an instance to check.
     *
     * @return bool True if the class is instantiable, false otherwise.
     */
    public static function isInstantiable(string|object $classOrObject): bool
    {
        return static::getClass($classOrObject)->isInstantiable();
    }

    /**
     * Checks if the class is an anonymous class.
     *
     * @param string|object $classOrObject The name of the class or an instance to check.
     *
     * @return bool True if the class is anonymous, false otherwise.
     */
    public static function isAnonymous(string|object $classOrObject): bool
    {
        return static::getClass($classOrObject)->isAnonymous();
    }

    /**
     * Checks if the given class has a specific method.
     *
     * @param string|object $classOrObject The name of the class or an instance to check.
     * @param string $methodName The name of the method to check.
     *
     * @return bool True if the method exists, false otherwise.
     */
    public static function hasMethod(string|object $classOrObject, string $methodName): bool
    {
        return static::getClass($classOrObject)->hasMethod($methodName);
    }

    /**
     * Checks if the given class has a specific property.
     *
     * @param string|object $classOrObject The name of the class or an instance to check.
     * @param string $propertyName The name of the property to check.
     *
     * @return bool True if the property exists, false otherwise.
     */
    public static function hasProperty(string|object $classOrObject, string $propertyName): bool
    {
        return static::getClass($classOrObject)->hasProperty($propertyName);
    }

    /**
     * Checks if the given method is static.
     *
     * @param string|object $classOrObject The name of the class or an instance.
     * @param string $methodName The name of the method to check.
     *
     * @return bool True if the method is static, false otherwise.
     */
    public static function isMethodStatic(string|object $classOrObject, string $methodName): bool
    {
        return static::getClass($classOrObject)->getMethod($methodName)->isStatic();
    }

    /**
     * Checks if the given property is static.
     *
     * @param string|object $classOrObject The name of the class or an instance.
     * @param string $propertyName The name of the property to check.
     *
     * @return bool True if the property is static, false otherwise.
     */
    public static function isPropertyStatic(string|object $classOrObject, string $propertyName): bool
    {
        return static::getClass($classOrObject)->getProperty($propertyName)->isStatic();
    }

    /**
     * Checks if the given class is an interface.
     *
     * @param string|object $classOrObject The name of the class or an instance to check.
     *
     * @return bool True if the class is an interface, false otherwise.
     */
    public static function isInterface(string|object $classOrObject): bool
    {
        return static::getClass($classOrObject)->isInterface();
    }

    /**
     * Checks if the given class is a trait.
     *
     * @param string|object $classOrObject The name of the class or an instance to check.
     *
     * @return bool True if the class is a trait, false otherwise.
     */
    public static function isTrait(string|object $classOrObject): bool
    {
        return static::getClass($classOrObject)->isTrait();
    }

    /**
     * Gets a constant by name.
     *
     * @param string|object $classOrObject The name of the class or an instance to retrieve from.
     * @param string $name The name of the constant to retrieve.
     *
     * @return mixed The value of the constant, or null if not found or empty.
     */
    public static function getConstant(string|object $classOrObject, string $name): mixed
    {
        // Get the constant value using reflection
        $constant = static::getClass($classOrObject)->getConstant($name);

        // Return the constant value if it exists and is not empty, otherwise return null
        return ! Validator::isEmpty($constant) ? $constant : null;
    }

    /**
     * Gets all constants of the class.
     *
     * @param string|object $classOrObject The name of the class or an instance to retrieve from.
     * @param int|null $filter Optional filter to apply.
     *
     * @return array An associative array of constants.
     */
    public static function getConstants(string|object $classOrObject, ?int $filter = null): array
    {
        return static::getClass($classOrObject)->getConstants($filter);
    }

    /**
     * Gets the constructor of the class.
     *
     * @param string|object $classOrObject The name of the class or an instance to retrieve from.
     *
     * @return ReflectionMethod|null The constructor method, or null if it doesn't exist.
     */
    public static function getConstructor(string|object $classOrObject): ?ReflectionMethod
    {
        return static::getClass($classOrObject)->getConstructor();
    }

    /**
     * Gets the names of all interfaces implemented by the class.
     *
     * @param string|object $classOrObject The name of the class or an instance to retrieve from.
     *
     * @return array An array of interface names.
     */
    public static function getInterfaceNames(string|object $classOrObject): array
    {
        return static::getClass($classOrObject)->getInterfaceNames();
    }

    /**
     * Gets all interfaces implemented by the class.
     *
     * @param string|object $classOrObject The name of the class or an instance to retrieve from.
     *
     * @return array An array of ReflectionClass objects for the interfaces.
     */
    public static function getInterfaces(string|object $classOrObject): array
    {
        return static::getClass($classOrObject)->getInterfaces();
    }

    /**
     * Gets a method by name.
     *
     * @param string|object $classOrObject The name of the class or an instance to retrieve from.
     * @param string $name The name of the method to retrieve.
     *
     * @return ReflectionMethod The ReflectionMethod object.
     */
    public static function getMethod(string|object $classOrObject, string $name): ReflectionMethod
    {
        return static::getClass($classOrObject)->getMethod($name);
    }

    /**
     * Gets all methods of the class.
     *
     * @param string|object $classOrObject The name of the class or an instance to retrieve from.
     * @param int|null $filter Optional filter to apply.
     *
     * @return array An array of ReflectionMethod objects.
     */
    public static function getMethods(string|object $classOrObject, ?int $filter = null): array
    {
        return static::getClass($classOrObject)->getMethods($filter);
    }

    /**
     * Gets the namespace name of the class up to a specified number of levels.
     *
     * @param string|object $classOrObject The name of the class or an instance to retrieve from.
     * @param int $levels The number of levels of the namespace to return. If the levels exceed
     *                    the number of available levels, the deepest applicable level will be returned.
     *
     * @return string The namespace name up to the specified number of levels.
     */
    public static function getNamespaceName(string|object $classOrObject, int $levels = 1): string
    {
        // Create a reflection object for the class or instance.
        $reflection = static::getClass($classOrObject);

        // Get the full namespace of the class.
        $fullNamespace = $reflection->getNamespaceName();

        // Split the namespace into parts.
        $namespaceParts = explode('\\', $fullNamespace);

        // Ensure levels is not greater than the number of available namespace levels.
        $levels = min($levels, count($namespaceParts));

        // Return the namespace up to the specified number of levels.
        return implode('\\', Arr::slice($namespaceParts, 0, $levels));
    }

    /**
     * Gets the parent class of the class.
     *
     * @param string|object $classOrObject The name of the class or an instance to retrieve from.
     *
     * @return ReflectionClass|false The parent class, or null if it has none.
     */
    public static function getParentClass(string|object $classOrObject): ReflectionClass|false
    {
        return static::getClass($classOrObject)->getParentClass();
    }

    /**
     * Gets all properties of the class.
     *
     * @param string|object $classOrObject The name of the class or an instance to retrieve from.
     * @param int|null $filter Optional filter to apply.
     *
     * @return array An array of ReflectionProperty objects.
     */
    public static function getProperties(string|object $classOrObject, ?int $filter = null): array
    {
        return static::getClass($classOrObject)->getProperties($filter);
    }

    /**
     * Gets a property by name.
     *
     * @param string|object $classOrObject The name of the class or an instance to retrieve from.
     * @param string $name The name of the property to retrieve.
     *
     * @return ReflectionProperty|null The ReflectionProperty object.
     */
    public static function getProperty(string|object $classOrObject, string $name): ?ReflectionProperty
    {
        try {
            return static::getClass($classOrObject)->getProperty($name) ?? null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Gets a reflection constant by name.
     *
     * @param string|object $classOrObject The name of the class or an instance to retrieve from.
     * @param string $name The name of the constant to retrieve.
     *
     * @return ReflectionClassConstant The ReflectionClassConstant object.
     */
    public static function getReflectionConstant(string|object $classOrObject, string $name): ReflectionClassConstant
    {
        return static::getClass($classOrObject)->getReflectionConstant($name);
    }

    /**
     * Gets all reflection constants of the class.
     *
     * @param string|object $classOrObject The name of the class or an instance to retrieve from.
     * @param int|null $filter Optional filter to apply.
     *
     * @return array An array of ReflectionClassConstant objects.
     */
    public static function getReflectionConstants(string|object $classOrObject, ?int $filter = null): array
    {
        return static::getClass($classOrObject)->getReflectionConstants($filter);
    }

    /**
     * Gets the name of the class.
     *
     * @param string|object $classOrObject The name of the class or an instance of the class.
     *
     * @return string The name of the class.
     */
    public static function getName(string|object $classOrObject): string
    {
        return static::getClass($classOrObject)->getName();
    }

    /**
     * Gets the short name of the class.
     *
     * @param string|object $classOrObject The name of the class or an instance to retrieve from.
     *
     * @return string The short name of the class.
     */
    public static function getShortName(string|object $classOrObject): string
    {
        return static::getClass($classOrObject)->getShortName();
    }

    /**
     * Gets all properties of the class.
     *
     * @param string|object $classOrObject The name of the class or an instance to retrieve from.
     *
     * @return array An associative array of properties.
     */
    public static function getStaticProperties(string|object $classOrObject): array
    {
        return static::getClass($classOrObject)->getStaticProperties();
    }

    /**
     * Gets the value of a property.
     *
     * @param string|object $classOrObject The name of the class or an instance to retrieve from.
     * @param string $name The name of the property to retrieve.
     * @param mixed $def_value The default value if the property is not found.
     *
     * @return mixed The value of the property.
     */
    public static function getStaticProperty(string|object $classOrObject, string $name, mixed $def_value = null)
    {
        return static::getClass($classOrObject)->getStaticPropertyValue($name, $def_value);
    }

    /**
     * Gets the names of all traits used by the class, including nested traits, in a single method.
     *
     * This method uses a recursive approach to retrieve all traits, leveraging the Collection class for cleaner processing.
     *
     * @param string|object $classOrObject The name of the class or an instance to retrieve traits from.
     *
     * @return array An array of all trait names, including nested traits.
     */
    public static function getTraitNames(string|object $classOrObject): array
    {
        $reflection = static::getClass($classOrObject);

        return collect($reflection->getTraitNames()) // Start with the base traits.
            ->mapWithKeys(fn($trait) => [$trait => $trait]) // Map traits into a keyed array for uniqueness.
            ->flatMap(function($trait) {
                // Get sub-traits for the current trait.
                $subTraits = static::getClass($trait)->getTraitNames();

                return [$trait => $trait] + collect($subTraits) // Recursively process sub-traits.
                    ->mapWithKeys(fn($subTrait) => [$subTrait => $subTrait])
                    ->toArray();
            })
            ->keys() // Extract only the keys (trait names).
            ->all(); // Convert to a plain array.
    }

    /**
     * Gets all traits used by the class.
     *
     * @param string|object $classOrObject The name of the class or an instance to retrieve from.
     *
     * @return array An array of ReflectionClass objects for the traits.
     */
    public static function getTraits(string|object $classOrObject): array
    {
        return static::getClass($classOrObject)->getTraits();
    }

    /**
     * Check if a class or any of its parent classes uses a specific trait.
     *
     * This method checks whether a specified class (or any of its parent classes)
     * uses a particular trait. It returns true if the trait is found, or false otherwise.
     *
     * @param string|object $classOrObject The class instance or class name to check.
     * @param string $trait The trait name to check for.
     *
     * @return bool True if the class or any parent class uses the trait, false otherwise.
     */
    public static function hasTrait(string|object $classOrObject, string $trait): bool
    {
        // Ensure we are dealing with a valid class or object
        $className = Validator::isObject($classOrObject) ? get_class($classOrObject) : (string)$classOrObject;

        // Get traits of the current class
        $traits = static::getTraitNames($className);

        // Check if the current class has the specified trait
        if (Php::inArray($trait, $traits, true)) {
            return true;
        }

        // Check if the class has a parent class
        $parentClass = static::getParentClass($className);

        if ($parentClass !== false) {
            // Get traits of the parent class
            $parentTraits = $parentClass->getTraits();

            // Check if the parent class has the specified trait
            return static::hasTrait($parentClass->getName(), $trait);
        }

        // No trait found in the class or its parent
        return false;
    }

    /**
     * Checks if the class has a specific constant.
     *
     * @param string|object $classOrObject The name of the class or an instance to check.
     * @param string $name The name of the constant to check.
     *
     * @return bool True if the constant exists, false otherwise.
     */
    public static function hasConstant(string|object $classOrObject, string $name): bool
    {
        return static::getClass($classOrObject)->hasConstant($name);
    }

    /**
     * Checks if the class implements a specific interface.
     *
     * @param string|object $classOrObject The name of the class or an instance to check.
     * @param string $interface The name of the interface to check against.
     *
     * @return bool True if the class implements the interface, false otherwise.
     */
    public static function implements(string|object $classOrObject, string $interface): bool
    {
        return static::getClass($classOrObject)->implementsInterface($interface);
    }

    /**
     * Checks if the given class is a subclass of a specified class.
     *
     * @param string|object $classOrObject The name of the class or an instance to check.
     * @param string $className The name of the parent class to check against.
     *
     * @return bool True if the class is a subclass of the specified class, false otherwise.
     */
    public static function isSubclassOf(string|object $classOrObject, string $className): bool
    {
        return static::getClass($classOrObject)->isSubclassOf($className);
    }

    /**
     * Gets all properties declared in the class.
     *
     * This method retrieves all properties that are declared in the specified class.
     * It uses PHP's Reflection API to reflect on the class and get its properties.
     *
     * @param string|object $classOrObject The name of the class (string) or an instance of the class (object).
     *
     * @return array An array of ReflectionProperty objects, each representing a property declared in the class.
     */
    public static function getDeclaredProperties(string|object $classOrObject): array
    {
        // Obtain the ReflectionClass instance for the provided class name or object.
        $reflectionClass = static::getClass($classOrObject);

        // Get all properties declared in the class using ReflectionClass.
        $properties = $reflectionClass->getProperties();

        // Return the array of ReflectionProperty objects.
        return $properties;
    }

    /**
     * Gets all methods declared in the class.
     *
     * This method retrieves all methods that are declared in the specified class.
     * It uses PHP's Reflection API to reflect on the class and get its methods.
     *
     * @param string|object $classOrObject The name of the class (string) or an instance of the class (object).
     *
     * @return array An array of ReflectionMethod objects, each representing a method declared in the class.
     */
    public static function getDeclaredMethods(string|object $classOrObject): array
    {
        // Obtain the ReflectionClass instance for the provided class name or object.
        $reflectionClass = static::getClass($classOrObject);

        // Get all methods declared in the class using ReflectionClass.
        $methods = $reflectionClass->getMethods();

        // Return the array of ReflectionMethod objects.
        return $methods;
    }

    /**
     * Gets all constants declared in the class.
     *
     * This method retrieves all constants declared in the specified class.
     * Note: PHP does not have ReflectionClassConstant, so this returns the constants directly.
     *
     * @param string|object $classOrObject The name of the class (string) or an instance of the class (object).
     *
     * @return array An array of ReflectionClassConstant objects representing constants in the class.
     */
    public static function getDeclaredConstants(string|object $classOrObject): array
    {
        // Obtain the ReflectionClass instance for the provided class name or object.
        $reflectionClass = static::getClass($classOrObject);

        // Get all constants declared in the class using ReflectionClass.
        $constants = $reflectionClass->getConstants();

        // PHP does not provide ReflectionClassConstant, so this example assumes creating such objects.
        // Convert constants to ReflectionClassConstant objects (requires custom implementation if needed).
        return Arr::each(
            fn($name) => new ReflectionClassConstant($reflectionClass->getName(), $name), // Custom class needs to be implemented.
            Arr::keys($constants),
        );
    }

    /**
     * Gets all class constants declared in the class.
     *
     * This method retrieves all class constants and their values declared in the specified class.
     * It uses PHP's Reflection API to reflect on the class and get its constants as an associative array.
     *
     * @param string|object $classOrObject The name of the class (string) or an instance of the class (object).
     *
     * @return array An associative array where keys are constant names and values are their respective values.
     */
    public static function getDeclaredClassConstants(string|object $classOrObject): array
    {
        // Obtain the ReflectionClass instance for the provided class name or object.
        $reflectionClass = static::getClass($classOrObject);

        // Get all constants declared in the class using ReflectionClass.
        $constants = $reflectionClass->getConstants();

        // Return the associative array of constant names and their values.
        return $constants;
    }

    /**
     * Checks if the given class is a facade.
     *
     * This method returns true if the class is a facade. Assumes that facades follow a specific naming convention.
     *
     * @param string|object $classOrObject The name of the class or an instance of the class.
     *
     * @return bool True if the class is a facade, false otherwise.
     */
    public static function isFacade(string|object $classOrObject): bool
    {
        $className = static::getClassName($classOrObject);

        // Assuming facades are named with a "Facade" suffix
        return Str::endsWith($className, 'Facade');
    }

    /**
     * Checks if the given class is an interceptor.
     *
     * This method returns true if the class follows the naming convention for interceptors.
     *
     * @param string|object $classOrObject The name of the class or an instance of the class.
     *
     * @return bool True if the class is an interceptor, false otherwise.
     */
    public static function isInterceptor(string|object $classOrObject): bool
    {
        // Get the class name from the provided object or class name
        $className = static::getClassName($classOrObject);

        // Check if the class name ends with 'Interceptor'
        return Str::endsWith($className, 'Interceptor');
    }

    /**
     * Gets all attributes of the given class.
     *
     * This method retrieves all attributes defined on the class.
     *
     * @param string|object $classOrObject The name of the class or an instance of the class.
     * @param string|null $attributeName Optional name of a specific attribute to filter results.
     *
     * @return ReflectionAttribute[] An array of ReflectionAttribute instances.
     */
    public static function getAttributes(string|object $classOrObject, ?string $attributeName = null): array
    {
        // Get the ReflectionClass instance and retrieve attributes
        $attributes = static::getClass($classOrObject)->getAttributes($attributeName);

        // Return the attributes, filtering by name if specified
        return $attributeName ? Arr::filter($attributes, fn($attr) => $attr->getName() === $attributeName) : $attributes;
    }

    /**
     * Gets a specific attribute of the given class.
     *
     * This method retrieves a specific attribute by its name.
     *
     * @param string|object $classOrObject The name of the class or an instance of the class.
     * @param string $attributeName The name of the attribute to retrieve.
     *
     * @return ReflectionAttribute|null The ReflectionAttribute instance, or null if not found.
     */
    public static function getAttribute(string|object $classOrObject, string $attributeName): ?ReflectionAttribute
    {
        // Retrieve all attributes of the specified class or instance
        $attributes = static::getAttributes($classOrObject, $attributeName);

        // Return the first matching ReflectionAttribute instance or null if not found
        return ! Validator::isEmpty($attributes) ? $attributes[0] : null;
    }

    /**
     * Gets a ReflectionClass instance from the given class name or object.
     *
     * This method retrieves a ReflectionClass instance based on whether a class name (string) or an object is provided.
     * If the class is an interceptor, it removes the 'Interceptor' suffix from the class name before creating the ReflectionClass.
     *
     * @param string|object $classOrObject The name of the class (string) or an instance of the class (object).
     *
     * @throws InvalidArgumentException If the provided value is neither a class name nor an object.
     *
     * @return ReflectionClass The ReflectionClass instance for the provided class name or object.
     */
    private static function getReflectionClass(string|object $classOrObject): ReflectionClass
    {
        // Normalize class name
        $className = Validator::isObject($classOrObject) ? get_class($classOrObject) : $classOrObject;

        // Check if ReflectionClass is cached
        if (isset(self::$cache[$className])) {
            return self::$cache[$className];
        }

        // Check if the class is an interceptor and remove the 'Interceptor' suffix if necessary
        if (Str::endsWith($className, 'Interceptor')) {
            $className = Str::replaceLast('\Interceptor', '', $className);
        }

        // Otherwise, retrieve and cache the ReflectionClass instance
        $reflectionClass = new ReflectionClass($className);

        // Cache the ReflectionClass instance
        self::$cache[$className] = $reflectionClass;

        return $reflectionClass;
    }

    /**
     * Normalize the class name by removing suffixes and trimming backslashes.
     *
     * @param string $className The class name to normalize.
     *
     * @return string The normalized class name.
     */
    private static function normalizeClassName(string $className): string
    {
        // Remove any trailing "Interceptor", "Data", "Factory", or "Interface" suffixes
        $className = Php::pregReplace('/(Interceptor|Data|Factory|Interface)$/', '', $className);

        // Trim trailing backslashes
        $className = Str::rtrim($className, '\\');

        // Remove any remaining unwanted suffixes after trimming backslashes
        return Php::pregReplace('/(Interceptor|Data|Factory|Interface)$/', '', $className);
    }
}
