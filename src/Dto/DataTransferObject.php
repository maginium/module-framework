<?php

declare(strict_types=1);

namespace Maginium\Framework\Dto;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Magento\Framework\Data\Collection;
use Maginium\Foundation\Exceptions\InputException;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Foundation\Exceptions\LocalizedException;
use Maginium\Framework\Database\Interfaces\Data\ModelInterface;
use Maginium\Framework\Dto\Attributes\CastWith;
use Maginium\Framework\Dto\Attributes\MapInputName;
use Maginium\Framework\Dto\Attributes\MapTo;
use Maginium\Framework\Dto\Casters\DataTransferObjectCaster;
use Maginium\Framework\Dto\Exceptions\UnknownProperties;
use Maginium\Framework\Dto\Exceptions\UnknownPropertiesException;
use Maginium\Framework\Dto\Reflection\DataTransferObjectClass;
use Maginium\Framework\Dto\Reflection\DataTransferObjectProperty;
use Maginium\Framework\Dto\Traits\InteractWithData;
use Maginium\Framework\Enum\Enum;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Facades\Container;
use Maginium\Framework\Support\Facades\Json;
use Maginium\Framework\Support\Facades\Request;
use Maginium\Framework\Support\Reflection;
use Maginium\Framework\Support\Str;
use Maginium\Framework\Support\Validator;
use ReflectionClass;
use ReflectionEnum;
use ReflectionProperty;
use stdClass;

/**
 * Base class for data transfer objects (DTOs).
 *
 * This class provides functionality for mapping input values to class properties,
 * validation, and conversion of the DTO to an array format. It includes features like
 * including or excluding specific keys, handling unknown properties, and applying
 * custom mappers for property names.
 *
 * @template T
 */
#[CastWith(DataTransferObjectCaster::class)]
abstract class DataTransferObject implements Arrayable, Jsonable
{
    use InteractWithData;

    /**
     * @var array<string> The keys to include when converting the DTO to an array.
     */
    protected static array $_originData = [];

    /**
     * Setter/Getter underscore transformation cache.
     *
     * @var array
     */
    protected static $_underscoreCache = [];

    /**
     * @var array<string> The keys to exclude when converting the DTO to an array.
     */
    protected array $exceptKeys = [];

    /**
     * @var array<string> The keys to include when converting the DTO to an array.
     */
    protected array $onlyKeys = [];

    /**
     * Constructor to initialize the DTO with provided values.
     * Maps input values from the provided array and validates the properties.
     *
     * @param array $parameters Constructor arguments, typically an array of key-value pairs.
     *
     * @throws UnknownProperties Thrown when unknown properties are encountered in strict mode.
     */
    public function __construct(array $parameters = [])
    {
        // Initialize class reflection using the container
        // Replacing direct instantiation of DataTransferObjectClass with Container::make
        $class = Container::make(DataTransferObjectClass::class, ['dataTransferObject' => $this]);

        // Map provided values to DTO properties
        $this->mapValuesToProperties($parameters, $class);

        // Strict mode check for any unknown properties
        $this->checkForUnknownProperties($parameters, $class);

        // Perform validation (assumes the class has validation logic defined)
        $class->validate();
    }

    /**
     * Factory method to create a new instance of the DTO with provided parameters.
     * This method allows initializing the DTO with a specific set of parameters in an array.
     *
     * @param array $parameters An array of parameters to pass to the constructor. These are typically key-value pairs that map to the DTO properties.
     *
     * @return self A new instance of the DTO, initialized with the provided parameters.
     */
    public static function make(array $parameters = []): static
    {
        return new static($parameters);
    }

    /**
     * Factory method to create a new instance of the DTO from the provided payloads.
     * Initializes the DTO with the first payload if available, otherwise throws an exception.
     *
     * If the payload is an instance of ModelInterface, the toArray method will be called to convert it to an array.
     * If the payload is an instance of Collection, it will be converted to an array of items.
     * If the payload is an instance of Request, the content is decoded or fetched from the body.
     * If the payload is an array of objects, it will be processed accordingly.
     *
     * @param mixed ...$payloads One or more payloads to initialize the DTO.
     *
     * @throws InvalidArgumentException If no payload is provided.
     *
     * @return static A new instance of the DTO, initialized with the provided payload.
     */
    public static function from(mixed ...$payloads): static
    {
        // Ensure there's at least one payload provided
        if (empty($payloads)) {
            throw InvalidArgumentException::make(__('No payload provided to initialize the DTO.'));
        }

        // Get the first payload
        $data = $payloads[0];

        // Handle different types of data
        if ($data instanceof ModelInterface) {
            // Convert ModelInterface to an array using toArray
            $data = $data->toDataArray();
        } elseif ($data instanceof Collection) {
            // Convert Collection to an array of items
            $data = $data->getItems();
        } elseif ($data instanceof Request) {
            // Decode content or fetch from the body if it's a Request
            $data = Json::decode($data->getContent());
        }

        // Return a new instance of the DTO with the processed data
        static::$_originData = $data;

        // Assuming a constructor accepts the $data
        return static::make();
    }

    /**
     * Create an array of DataTransferObject instances from an array of parameters.
     *
     * Useful for handling an array of objects to be instantiated from array data.
     *
     * @param array $arrayOfParameters An array of parameters to create DTO instances.
     *
     * @return array An array of DataTransferObject instances.
     */
    public static function arrayOf(array $arrayOfParameters): array
    {
        return Arr::each(
            fn(mixed $parameters) => static::make($parameters), // Instantiate the DTO for each set of parameters
            $arrayOfParameters, // Map through each element of the array
        );
    }

    /**
     * Returns all public properties as an option array with their labels and types.
     *
     * @return array<string, array<string, string>> Array of properties with their labels and types.
     */
    public static function asOptionArray(): array
    {
        // Get all public properties of the class
        $properties = Reflection::getProperties(static::class, ReflectionProperty::IS_PUBLIC);

        // Build the option array
        $optionArray = [];

        foreach ($properties as $property) {
            $propertyName = $property->getName();

            // Get the property type
            $propertyType = (string)$property->getType();

            // Check if the property type is a class (i.e., a non-scalar type)
            if ($property->getType() && $property->getType()->getName() !== 'string' && Reflection::exists(Str::replace('?', '', $propertyType))) {
                $propertyType = 'array';  // If the type is a class, return 'array'
            }

            // Map to your desired format with name and type
            $optionArray[$propertyName] = [
                'name' => __(Str::replace('_', ' ', Str::studly($propertyName, true)))->render(),
                'type' => $propertyType ?: 'string', // Fallback to 'string' if no type is detected
            ];
        }

        return $optionArray;
    }

    /**
     * Return the object's properties as an array, with optional transformation via mappers.
     *
     * This method collects the object's public properties, applies transformations via
     * the `MapTo` and class-level `MapInputName` attributes, resolves enum values,
     * and returns the resulting array.
     *
     * @return array The transformed properties as an associative array.
     */
    public function all(): array
    {
        // Array to store property values
        $data = [];

        // Get reflection of the class
        $class = Container::make(ReflectionClass::class, ['objectOrClass' => static::class]);

        // Get the class-level mapper from MapInputName attribute
        $classMapper = $this->getClassMapper($class);

        // Get all public properties of the class
        $properties = $class->getProperties(ReflectionProperty::IS_PUBLIC);

        // Map the properties and apply transformations
        foreach ($properties as $property) {
            if ($property->isStatic()) {
                // Skip static properties, but we'll need to handle them differently
                continue;
            }

            // Resolve the mapped name for the property
            $name = $this->resolvePropertyName($property, $classMapper);

            // Preserve the mapped name for use in resolving enum values
            $mappedName = $name;

            // Get the actual property value
            $value = $property->getValue($this);

            // If the mapped name contains 'Enum', revert to the original property name
            if (str_contains($name, '\\') || str_contains($name, 'Enum')) {
                $name = $property->getName(); // Use original property name for enums
            }

            // Resolve the enum value using the mapped name and the property value
            $resolvedValue = $this->resolveEnumValue($mappedName, $value);

            // Set the resolved value in the data array using the final property name
            $data[$name] = $resolvedValue;
        }

        // Return the mapped data
        return $data;
    }

    /**
     * Return a clone of the current DTO with only the specified keys.
     *
     * @param string ...$keys Keys of the properties to include in the new DTO.
     *
     * @return static A new instance of the DTO with the selected keys.
     */
    public function only(string ...$keys): static
    {
        // Clone the current DTO instance
        $clone = clone $this;

        // Merge the current "only" keys with the new ones
        $clone->onlyKeys = Arr::merge($this->onlyKeys, $keys);

        // Return the cloned DTO with the specified keys
        return $clone;
    }

    /**
     * Return a clone of the current DTO excluding the specified keys.
     *
     * @param string ...$keys Keys of the properties to exclude from the new DTO.
     *
     * @return static A new instance of the DTO without the excluded keys.
     */
    public function except(string ...$keys): static
    {
        // Clone the current DTO instance
        $clone = clone $this;

        // Merge the current "except" keys with the new ones
        $clone->exceptKeys = Arr::merge($this->exceptKeys, $keys);

        // Return the cloned DTO without the specified keys
        return $clone;
    }

    /**
     * Return the original DTO data as an array with exclusions and inclusions.
     *
     * This method collects all properties and respects the exclusions and inclusions.
     *
     * @return array The final transformed data as an array.
     */
    public function toArray(): array
    {
        // Get all properties using the all() method
        $data = $this->all();

        // Filter properties based on "only" and "except" keys
        return Arr::only($data, $this->onlyKeys) // Apply the "only" filter
        // Apply the "except" filter
            + Arr::except($data, $this->exceptKeys);
    }

    /**
     * Converts the Dto to a JSON string representation.
     *
     * @return string A JSON string representation of the Dto.
     */
    public function toString(): string
    {
        return Json::encode($this->toArray());
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param int $options Options for JSON encoding.
     *
     * @return string JSON representation of the Dto.
     */
    public function toJson($options = 0)
    {
        return Json::encode($this->toArray(), $options);
    }

    /**
     * Recursively parse an array to convert DTO instances to arrays.
     *
     * @param array $array The array to recursively parse.
     *
     * @return array The parsed array with DTO instances converted to arrays.
     */
    protected function parseArray(array $array): array
    {
        return Arr::each(fn($item) => $item instanceof DataTransferObject ? $item->toArray() : $item, $array);
    }

    /**
     * Retrieve the model class name.
     *
     * This method returns the model class name associated with the DTO.
     *
     * @return class-string<ModelInterface> The model class name.
     */
    private function getModel(): string
    {
        // Return the model class name from the property
        return $this->model ?? '';
    }

    /**
     * Map the provided values to the properties of the DTO class.
     *
     * @param array $args The arguments to map to the DTO properties.
     * @param DataTransferObjectClass $class The DTO class reflection.
     */
    private function mapValuesToProperties(array $args, DataTransferObjectClass $class): void
    {
        // Loop through all the properties of the DTO
        /** @var DataTransferObjectProperty $property */
        foreach ($class->getProperties() as $property) {
            // Get the value before assignment
            $value = Arr::get($args, $property->name, $property->getDefaultValue() ?? null);

            // Ensure the value is of the correct type before assignment
            if ($property->getType() === 'bool' && ! Validator::isBool($value)) {
                // Create a more informative exception message
                throw InputException::make(__(
                    "Validation error: Property `%1` expects a boolean value. Provided value '%2' is invalid.",
                    $property->getName(),
                    $value,
                ));
            }

            // Assign the value or default to the property
            $property->setValue($value);

            // Remove the property from the arguments after assigning
            Arr::forget($args, [$property->name]);
        }
    }

    /**
     * Check for unknown properties and throw an exception if any are found in strict mode.
     *
     * @param array $args The arguments to check for unknown properties.
     * @param DataTransferObjectClass $class The DTO class reflection.
     *
     * @throws UnknownPropertiesException Thrown if unknown properties are found.
     */
    private function checkForUnknownProperties(array $args, DataTransferObjectClass $class): void
    {
        // Check if the class is in strict mode and if there are unknown properties
        if ($class->isStrict() && count($args)) {
            // Throw exception if unknown properties found in strict mode
            throw UnknownPropertiesException::make(static::class, Arr::keys($args));
        }
    }

    /**
     * Get the class-level mapper from the MapInputName attribute.
     *
     * @param ReflectionClass $class The class reflection.
     *
     * @return string|null The mapper class if defined, null otherwise.
     */
    private function getClassMapper(ReflectionClass $class): ?string
    {
        // Get the MapInputName attribute for the class
        $classAttributes = $class->getAttributes(MapInputName::class);

        // Return the mapper class if defined
        return count($classAttributes) ? $classAttributes[0]->newInstance()->mapperClass : null;
    }

    /**
     * Resolve the property name by checking the MapTo attribute and applying mappers.
     *
     * @param ReflectionProperty $property The property being mapped.
     * @param string|null $classMapper The class-level mapper, if any.
     *
     * @return string The resolved property name.
     */
    private function resolvePropertyName(ReflectionProperty $property, ?string $classMapper): string
    {
        // Resolve name using MapTo attribute or fallback to the property name
        // Get the MapTo attribute for the property
        $mapToAttribute = $property->getAttributes(MapTo::class);

        // Get name from MapTo attribute or use property name
        $name = count($mapToAttribute) ? $mapToAttribute[0]->newInstance()->name : $property->getName();

        // Apply the class-level mapper if defined
        if ($classMapper) {
            // Apply class-level mapping
            $name = (new $classMapper)->map($name);
        }

        // Apply property-specific mapper if necessary
        // Apply property-specific mapping or return the resolved name
        return $this->resolveMapper($property, $name) ?? $name;
    }

    /**
     * Apply property-specific mapper to resolve the final name.
     *
     * @param ReflectionProperty $property The property being mapped.
     * @param string $name The name to be transformed.
     *
     * @return string|null The final resolved name or null if no transformation is applied.
     */
    private function resolveMapper(ReflectionProperty $property, string $name): ?string
    {
        // Get the MapInputName attribute and apply the mapper if present

        // Get the MapInputName attribute for the property
        $attributes = $property->getAttributes(MapInputName::class);

        if (empty($attributes)) {
            // Return null if no MapInputName attribute is present
            return null;
        }

        // Get the mapper class and apply it
        // Get the attribute instance
        $attribute = $attributes[0]->newInstance();

        // Get the mapper class
        $mapperClass = $attribute->mapperClass;

        // Apply the mapper class to transform the name
        return (new $mapperClass)->map($name);
    }

    /**
     * Resolve the value of an enum property.
     *
     * This method checks if the provided property is associated with an enum type. It handles both:
     * - Native enums introduced in PHP 8.1.
     * - Custom enums that extend `Maginium\Framework\Enum\Enum`.
     *
     * Depending on the type of enum, the method retrieves the appropriate value:
     * - For native enums, it returns the enum case name (not the value).
     * - For custom enums, it retrieves the corresponding key or value using the `getValue()` method of the enum class.
     *
     * If the property is not an enum, it returns the original property value.
     *
     * @param string $mappedPropertyName The fully qualified name of the enum class or enum-like class.
     * @param mixed $propertyValue The value of the property to resolve.
     *
     * @return mixed The resolved enum value or the original property value if not an enum.
     */
    private function resolveEnumValue(string $mappedPropertyName, $propertyValue)
    {
        // If the class name does not contain '\\', it's not an enum, return the property value.
        if (! str_contains($mappedPropertyName, '\\') && ! str_contains($mappedPropertyName, 'Enum')) {
            return $propertyValue;
        }

        // Check if it's a subclass of Maginium\Framework\Enum\Enum (custom enum)
        if (Reflection::isSubclassOf($mappedPropertyName, Enum::class)) {
            // Retrieve the corresponding key or value from the custom enum
            $enumValue = $mappedPropertyName::getKey($propertyValue) ?? $mappedPropertyName::getValue((string)$propertyValue);

            return Str::lower($enumValue);  // Return the enum value in lowercase
        }

        // Check if it's a native enum (PHP 8.1+)
        if (Reflection::isEnum($mappedPropertyName)) {
            // For native enums, return the enum case name based on the value
            /** @var ReflectionEnum $enumClass */
            foreach ($enumClass->getCases() as $case) {
                if ($case->getValue()->value === $propertyValue) {
                    return Str::lower($case->getName());  // Return the case name in lowercase
                }
            }
        }

        // If it's neither a native enum nor an instance of a custom Enum class, return the original value
        return $propertyValue;
    }

    /**
     * Converts field names for setters and getters to underscore format.
     *
     * Converts camelCase property names to snake_case (underscore) format, ensuring consistency in method naming conventions.
     * Uses a cache to avoid redundant conversions, improving performance for repeated calls.
     *
     * Example: setMyField() -> my_field
     *
     * @param string $name The method name (e.g., "setMyField")
     *
     * @return string The converted name in snake_case (e.g., "my_field")
     */
    private function _underscore(string $name): string
    {
        // Check if the result is already cached
        if (isset(self::$_underscoreCache[$name])) {
            return self::$_underscoreCache[$name];
        }

        // Convert the property name from camelCase to snake_case
        $result = mb_strtolower(
            trim(
                preg_replace(
                    '/([A-Z]|[0-9]+)/',
                    '_$1',
                    lcfirst(
                        mb_substr($name, 3), // Remove the first 3 characters (e.g., 'set' or 'get')
                    ),
                ),
                '_', // Remove any leading/trailing underscores
            ),
        );

        // Cache the result for future use
        self::$_underscoreCache[$name] = $result;

        return $result;
    }

    /**
     * Magic method to handle dynamic getter, setter, unset, and has method calls.
     *
     * This method intercepts method calls like setFoo(), getFoo(), unsFoo(), and hasFoo(), and dynamically handles them.
     * It ensures that the correct property is accessed, modified, or checked based on the method called.
     *
     * @param string $method The name of the method being called (e.g., 'getMyField')
     * @param array $args The arguments passed to the method (if any)
     *
     * @throws LocalizedException If the method is invalid or not supported
     *
     * @return mixed The value returned by the method, or the property being set
     */
    public function __call(string $method, array $args)
    {
        // Determine the type of method by its first three characters (get, set, uns, has)
        switch (mb_substr($method, 0, 3)) {
            case 'get':
                // Handle getter: getMyField() -> $this->my_field
                // Convert the method name from camelCase to snake_case (e.g., getMyField -> my_field)
                $methodName = self::$_underscoreCache[$method] ?? $this->_underscore($method);

                // Return the value of the property, assuming it exists
                return $this->{$methodName};

            case 'set':
                // Handle setter: setMyField($value) -> $this->my_field = $value
                // Convert the method name from camelCase to snake_case (e.g., setMyField -> my_field)
                $methodName = self::$_underscoreCache[$method] ?? $this->_underscore($method);

                // Set the property value, using the first argument or null if no argument is provided
                return $this->{$methodName} = $args[0] ?? null;

            case 'uns':
                // Handle unset: unsMyField() -> unset $this->my_field
                // Convert the method name from camelCase to snake_case (e.g., unsMyField -> my_field)
                $methodName = self::$_underscoreCache[$method] ?? $this->_underscore($method);

                // Check if the property exists using Reflection
                if (! Reflection::hasProperty($this, $methodName)) {
                    // If the property does not exist, throw a localized exception
                    throw new LocalizedException(
                        __('Property "%1" not found on class "%2".', [$methodName, get_class($this)]),
                    );
                }

                // Retrieve the property using Reflection
                $property = Reflection::getProperty($this, $methodName);
                $propertyType = $property->getType(); // Get the property type

                // Assign a default empty value based on the property type
                if ($propertyType->allowsNull()) {
                    // For nullable types, set the property to null
                    return $this->{$methodName} = null;
                }

                // Default values for common types using match expression
                return $this->{$methodName} = match ($propertyType->getName()) {
                    'string' => '', // Default for string
                    'array' => [], // Default for array
                    'object' => new stdClass, // Default for object (stdClass)
                    default => null, // For other types, use null as the default value
                };

            case 'has':
                // Handle has: hasMyField() -> isset($this->my_field)
                // Convert the method name from camelCase to snake_case (e.g., hasMyField -> my_field)
                $methodName = self::$_underscoreCache[$method] ?? $this->_underscore($method);

                // Check if the property exists and is set (not null)
                return Reflection::hasProperty($this, $methodName) && isset($this->{$methodName});

            default:
                // If the method does not match any of the expected types (get, set, uns, has), throw an exception
                throw new LocalizedException(
                    __('Invalid method "%1::%2".', [get_class($this), $method]),
                );
        }
    }
}
