<?php

declare(strict_types=1);

namespace Maginium\Framework\Serializer;

use Closure;
use Laravel\SerializableClosure\SerializableClosure as BaseSerializableClosure;
use Laravel\SerializableClosure\Serializers\Native;
use Laravel\SerializableClosure\Serializers\Signed;
use Laravel\SerializableClosure\Signers\Hmac;
use Laravel\SerializableClosure\UnsignedSerializableClosure;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Framework\Serializer\Interfaces\SerializableClosureInterface;
use Maginium\Framework\Support\Facades\Container;
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
class SerializableClosure extends BaseSerializableClosure implements SerializableClosureInterface
{
    /**
     * Creates a new serializable closure instance.
     *
     * @param  Closure  $closure
     *
     * @return void
     */
    public function __construct(?Closure $closure = null)
    {
        if ($closure) {
            parent::__construct($closure);
        }
    }

    /**
     * Create a new unsigned serializable closure instance.
     *
     * @param  Closure  $closure
     *
     * @return UnsignedSerializableClosure
     */
    public static function unsigned(Closure $closure): UnsignedSerializableClosure
    {
        return new UnsignedSerializableClosure($closure);
    }

    /**
     * Sets the serializable closure secret key.
     *
     * @param  string|null  $secret
     *
     * @return void
     */
    public static function setSecretKey($secret): void
    {
        Signed::$signer = $secret
            ? new Hmac($secret)
            : null;
    }

    /**
     * Sets the serializable closure secret key.
     *
     * @param  Closure|null  $transformer
     *
     * @return void
     */
    public static function transformUseVariablesUsing($transformer): void
    {
        Native::$transformUseVariables = $transformer;
    }

    /**
     * Sets the serializable closure secret key.
     *
     * @param  Closure|null  $resolver
     *
     * @return void
     */
    public static function resolveUseVariablesUsing($resolver): void
    {
        Native::$resolveUseVariables = $resolver;
    }

    /**
     * Static method to create a SerializableClosure for the given closure.
     *
     * @param  Closure  $closure  Closure to be serialized.
     *
     * @throws InvalidArgumentException If the provided data is not a callable.
     *
     * @return SerializableClosureInterface The serializer instance.
     */
    public function make(Closure $closure): SerializableClosureInterface
    {
        if (! is_callable($closure)) {
            throw InvalidArgumentException::make('The provided argument is not a valid closure.');
        }

        // Use the factory to create a SerializableClosure instance
        return Container::make(static::class, compact('closure'));
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
        if (! $closure instanceof SerializableClosureInterface) {
            $closure = $this->make($closure);
        }

        // Use PHP's serialize function to serialize the SerializableClosure instance
        return Serializer::serialize($closure);
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
        if (! $unserialized instanceof SerializableClosureInterface) {
            throw InvalidArgumentException::make('The provided string could not be unserialized into a valid closure.');
        }

        // Return the unserialized closure
        return $unserialized->getClosure();
    }

    /**
     * Gets the closure.
     *
     * @return callable
     */
    public function getClosure(): callable
    {
        return $this->serializable->getClosure();
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
            return $reflection->getReturnType()?->getName() === SerializableClosureInterface::class;
        }

        // If the value is a string, check if it contains the class name of SerializableClosure
        return Validator::isString($value) && Str::contains($value, static::class);
    }
}
