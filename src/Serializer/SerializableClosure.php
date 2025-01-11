<?php

declare(strict_types=1);

namespace Maginium\Framework\Serializer;

use Closure;
use Laravel\SerializableClosure\SerializableClosure as BaseSerializableClosure;
use Laravel\SerializableClosure\SerializableClosureFactory;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Framework\Serializer\Interfaces\SerializableClosureInterface;
use Maginium\Framework\Support\Facades\Serializer;
use Maginium\Framework\Support\Str;
use Maginium\Framework\Support\Validator;
use ReflectionMethod;

/**
 * SerializableClosure.
 *
 * This class provides methods to serialize and unserialize closures
 * using the Laravel SerializableClosure library.
 */
class SerializableClosure implements SerializableClosureInterface
{
    /**
     * @var SerializableClosureFactory The factory for creating SerializableClosure instances.
     */
    private SerializableClosureFactory $factory;

    /**
     * SerializableClosure constructor.
     *
     * @param  SerializableClosureFactory  $factory  The factory for creating SerializableClosure instances.
     */
    public function __construct(SerializableClosureFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Static method to create a SerializableClosure for the given closure.
     *
     * @param  Closure  $closure  Closure to be serialized.
     *
     * @throws InvalidArgumentException If the provided data is not a callable.
     *
     * @return BaseSerializableClosure The serializer instance.
     */
    public function make(Closure $closure): BaseSerializableClosure
    {
        if (! is_callable($closure)) {
            throw InvalidArgumentException::make('The provided argument is not a valid closure.');
        }

        // Use the factory to create a SerializableClosure instance
        return $this->factory->create(compact('closure'));
    }

    /**
     * Serialize a closure into a string format.
     *
     * @param  Closure  $closure  Closure to be serialized.
     *
     * @throws InvalidArgumentException If the provided data is not a callable.
     *
     * @return string The serialized closure string.
     */
    public function serialize(Closure $closure): string
    {
        if (! is_callable($closure)) {
            throw InvalidArgumentException::make('The provided argument is not a valid closure.');
        }

        // Create a SerializableClosure instance using the factory
        $serializableClosure = $this->make($closure);

        // Use PHP's serialize function to serialize the SerializableClosure instance
        return Serializer::serialize($serializableClosure);
    }

    /**
     * Unserialize a string back into a closure.
     *
     * @param  string  $string  String to be unserialized.
     *
     * @throws InvalidArgumentException If the string cannot be unserialized.
     *
     * @return Closure|callable|mixed The unserialized closure.
     */
    public function unserialize(string $string): mixed
    {
        // Unserialize the string back into a SerializableClosure
        $unserialized = Serializer::unserialize($string, true);

        // Check if it's a valid SerializableClosure
        if (! $unserialized instanceof BaseSerializableClosure) {
            throw InvalidArgumentException::make('The provided string could not be unserialized into a valid closure.');
        }

        // Return the unserialized closure
        return $unserialized->getClosure();
    }

    /**
     * Check if the given value is a serialized closure.
     *
     * This method checks if the value is a Closure instance or a serialized string.
     * If it's a Closure, it checks if the closure is of type `SerializableClosure`.
     * If it's a string, it checks if it contains the class name of `SerializableClosure`
     * to determine if it represents a serialized closure.
     *
     * @param string|Closure $value The value to check, which can be a string or Closure.
     *
     * @return bool Returns true if the value is a serialized closure, false otherwise.
     */
    public function isSerializedClosure(mixed $value): bool
    {
        // Check if the value is an instance of Closure
        if ($value instanceof Closure) {
            // Use reflection to get the return type of the closure's __invoke method
            $reflection = new ReflectionMethod($value, '__invoke');

            // Check if the return type of the closure matches the current class
            return $reflection->getReturnType()?->getName() === BaseSerializableClosure::class;
        }

        // If the value is a string, check if it contains the class name of SerializableClosure
        return Validator::isString($value) && Str::contains($value, BaseSerializableClosure::class);
    }
}
