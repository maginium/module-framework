<?php

declare(strict_types=1);

namespace Maginium\Framework\Enum;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Traits\Macroable;
use JsonSerializable;
use Maginium\Framework\Enum\Concerns\HasDynamicValues;
use Maginium\Framework\Enum\Exceptions\InvalidEnumKeyException;
use Maginium\Framework\Enum\Exceptions\InvalidEnumMemberException;
use Maginium\Framework\Enum\Interfaces\LocalizedEnum;
use Maginium\Framework\Enum\Traits\Descriptionable;
use Maginium\Framework\Enum\Traits\EnumComparison;
use Maginium\Framework\Enum\Traits\GetterAttributes;
use Maginium\Framework\Enum\Traits\Labelable;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Reflection;
use Maginium\Framework\Support\Str;
use Maginium\Framework\Support\Validator;
use ReflectionClass;
use Stringable;

/**
 * Class Enum.
 *
 * This abstract class provides a base for defining enums, offering reflection caching,
 * dynamic value handling, and support for Arrayable and JsonSerializable interfaces.
 * It also includes traits for macro capabilities, allowing dynamic method and static method
 * handling. Each enum member consists of a key, description, label, and a value.
 *
 * @template TValue The type of value held by the enum member.
 *
 * @implements Arrayable<array-key, mixed>
 */
abstract class Enum implements Arrayable, JsonSerializable, Stringable
{
    use Descriptionable;
    use EnumComparison;
    use GetterAttributes;
    use HasDynamicValues;
    use Labelable;
    use Macroable {
        __call as macroCall;
        __callStatic as macroCallStatic;
    }

    /**
     * Caches reflections of enum subclasses.
     *
     * @var array<class-string<static>, ReflectionClass<static>>
     */
    protected static array $reflectionCache = [];

    /**
     * The key of one of the enum members.
     */
    public string $key;

    /**
     * The value of one of the enum members.
     *
     * @var TValue
     */
    public $value;

    /**
     * Construct an Enum instance.
     *
     * @param  TValue  $enumValue  The value of the enum member.
     *
     * @throws InvalidEnumMemberException If the provided enum value is invalid.
     */
    public function __construct(mixed $enumValue)
    {
        // Check if the provided enum value is valid
        if (! static::hasValue($enumValue)) {
            // Throw an exception if the enum value is not valid
            throw new InvalidEnumMemberException($enumValue, static::class);
        }

        // Initialize instance properties
        $this->value = $enumValue;
        $this->key = static::getKey($enumValue);
        $this->label = static::getLabel($enumValue);
        $this->description = static::getDescription($enumValue);

        // Call the internal constructor method for further initialization
        $this->_construct();
    }

    /**
     * Return instances of all the contained values.
     *
     * This method creates instances of the enum class for all defined constants.
     *
     * @return static[] Array of enum instances keyed by their string representations.
     */
    public static function instances(): array
    {
        return Arr::each(
            static fn(mixed $constantValue): self => new static($constantValue),
            static::getConstants(),
        );
    }

    /**
     * Make a new instance from an enum value.
     *
     * @param  TValue  $enumValue  The value of the enum member.
     *
     * @return static Enum instance created from the enum value.
     */
    public static function fromValue(mixed $enumValue): static
    {
        // Return the enum instance if already an instance of the enum class
        if ($enumValue instanceof static) {
            return $enumValue;
        }

        // Otherwise, create a new instance using the provided enum value
        return new static($enumValue);
    }

    /**
     * Make an enum instance from a given key.
     *
     * @param  string  $key  Key of the enum member.
     *
     * @throws InvalidEnumKeyException If the provided key is not valid.
     *
     * @return static Enum instance created from the enum key.
     */
    public static function fromKey(string $key): static
    {
        // Check if the provided key exists in the enum class
        if (static::hasKey($key)) {
            // Retrieve the value associated with the key
            $enumValue = static::getValue($key);

            // Return a new enum instance created from the retrieved value
            return new static($enumValue);
        }

        // Throw an exception if the key is not valid
        throw new InvalidEnumKeyException($key, static::class);
    }

    /**
     * Attempt to instantiate a new Enum using the given key or value.
     *
     * This method tries to create an enum instance based on a key or value provided.
     *
     * @param  mixed  $enumKeyOrValue  Key or value used to instantiate the enum.
     *
     * @return static|null Enum instance if successful, null otherwise.
     */
    public static function coerce(mixed $enumKeyOrValue): ?static
    {
        if ($enumKeyOrValue === null) {
            return null;
        }

        // Return the instance directly if it's already an instance of this enum class
        if ($enumKeyOrValue instanceof static) {
            return $enumKeyOrValue;
        }

        // Try to create an instance using the provided enum value
        if (static::hasValue($enumKeyOrValue)) {
            return static::fromValue($enumKeyOrValue);
        }

        // Try to create an instance using the provided enum key
        if (Validator::isString($enumKeyOrValue) && static::hasKey($enumKeyOrValue)) {
            $enumValue = static::getValue($enumKeyOrValue);

            return new static($enumValue);
        }

        // Return null if neither key nor value could instantiate an enum instance
        return null;
    }

    /**
     * Return the enum as an array.
     *
     * @return array<string, mixed> Enum as an associative array (key => value).
     */
    public static function asArray(): array
    {
        // Return all constants of the enum as an associative array
        return static::getConstants();
    }

    /**
     * Get the enum as an array formatted for a select dropdown.
     *
     * This method prepares an associative array of enum values and descriptions,
     * formatted for use in select dropdowns. Each value is mapped to either its
     * label or description, with a fallback to a human-friendly version of the key.
     *
     * @param  bool  $withLocalization  Whether to include localization for the labels.
     *
     * @return array<string, string> Enum as an associative array (value => description).
     */
    public static function asSelectArray(bool $withLocalization = false): array
    {
        // Initialize an empty array to hold formatted select options.
        $selectArray = [];

        // Loop through each key-value pair in the enum's associative array.
        foreach (static::asArray() as $key => $value) {
            // Determine the label for the enum option
            $label = $withLocalization
                ? __($value)->render()  // If localization is requested, use the translated value
                : static::getLabel($value)  // Try to get a label
                    ?? static::getDescription($value)  // Fallback to description if label is not found
                    ?? __(Str::capital(Str::lower($key)))->render();  // Fallback to a formatted version of the key

            // Add the enum value and its corresponding label to the select array.
            $selectArray[$value] = $label;
        }

        // Return the completed array, which can be used to populate a dropdown.
        return $selectArray;
    }

    /**
     * Get all constants defined on the class excluding private constants.
     *
     * This method uses reflection to retrieve all constants, filtering out private ones.
     *
     * @return array<string, TValue> The list of constants excluding private ones.
     */
    protected static function getConstants(): array
    {
        // Get all constants defined on the enum class
        $constants = static::getReflection()->getConstants();

        // Filter out private constants using reflection
        $constants = Arr::filter($constants, function($name) {
            // Exclude constants defined as private
            $reflection = static::getReflection()->getReflectionConstant($name);

            return ! $reflection->isPrivate();
        }, ARRAY_FILTER_USE_KEY);

        // Merge with dynamically defined enum values
        return Arr::merge($constants, static::$enumValues);
    }

    /**
     * Determines whether the current class is localizable.
     *
     * This method checks if the class implements the `LocalizedEnum` interface,
     * indicating that it is intended to be used with localization.
     *
     * @return bool True if the class implements the LocalizedEnum interface, false otherwise.
     */
    protected static function isLocalizable(): bool
    {
        // Directly return the result of the interface check
        return Reflection::implements(static::class, LocalizedEnum::class);
    }

    /**
     * Returns a reflection of the enum subclass.
     *
     * @return ReflectionClass<static>
     */
    protected static function getReflection(): ReflectionClass
    {
        $class = static::class;

        // Cache and return reflection of the enum subclass
        return static::$reflectionCache[$class] ??= Reflection::getClass($class);
    }

    /**
     * Return a plain representation of the enum value.
     *
     * @return mixed Enum value.
     */
    public function toArray(): mixed
    {
        // Return the value of the enum instance
        return $this->value;
    }

    /**
     * Return a JSON-serializable representation of the enum.
     *
     * @return TValue
     */
    public function jsonSerialize(): mixed
    {
        // Return the enum value for JSON serialization
        return $this->value;
    }

    /**
     * Restores an enum instance exported by var_export().
     *
     * @param  array{value: TValue, key: string, description: string}  $enum  Array representation of the enum instance.
     *
     * @return static Restored enum instance.
     */
    public static function __set_state(array $enum): static
    {
        // Create a new instance using the provided enum value
        return new static($enum['value']);
    }

    /**
     * Attempt to instantiate an enum by calling the enum key as a static method.
     *
     * This function defers to the macroable __callStatic function if a macro is found using the static method called.
     *
     * @param  array<mixed>  $parameters  Parameters passed to the static method.
     *
     * @return mixed Enum instance or value corresponding to the method call.
     */
    public static function __callStatic(string $method, array $parameters): mixed
    {
        // Check if the method is a registered macro
        if (static::hasMacro($method)) {
            // Call the macro if found
            return static::macroCallStatic($method, $parameters);
        }

        // Otherwise, attempt to create an enum instance from the method name (key)
        return static::fromKey($method);
    }

    /**
     * Delegate magic method calls to macros or the static call.
     *
     * This method is invoked when a non-existent static method is called on the enum class.
     * It allows using the `Enum::KEY()` syntax to instantiate an enum instance.
     *
     * @param  string  $method  Method name being called.
     * @param  array<mixed>  $parameters  Parameters passed to the method.
     *
     * @return mixed Result of the method call (enum instance or value).
     */
    public function __call($method, $parameters): mixed
    {
        // Check if the method is a registered macro
        if (static::hasMacro($method)) {
            // Call the macro if found
            return $this->macroCall($method, $parameters);
        }

        // Otherwise, delegate to __callStatic to create an enum instance from the method name (key)
        return self::__callStatic($method, $parameters);
    }

    /**
     * Return a string representation of the enum value.
     *
     * @return string String representation of the enum value.
     */
    public function __toString(): string
    {
        return (string)$this->value;
    }
}
