<?php

declare(strict_types=1);

namespace Maginium\Framework\Serializer;

use Closure;
use Laravel\SerializableClosure\SerializableClosure;
use Laravel\SerializableClosure\SerializableClosureFactory;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Framework\Serializer\Interfaces\ClosureSerializerInterface;
use Maginium\Framework\Support\Facades\Serializer;

/**
 * ClosureSerializer.
 *
 * This class provides methods to serialize and unserialize closures
 * using the Laravel SerializableClosure library.
 */
class ClosureSerializer implements ClosureSerializerInterface
{
    /**
     * @var SerializableClosureFactory The factory for creating SerializableClosure instances.
     */
    private SerializableClosureFactory $factory;

    /**
     * ClosureSerializer constructor.
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
     * @return SerializableClosure The serializer instance.
     */
    public function make(Closure $closure): SerializableClosure
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
     * @return Closure The unserialized closure.
     */
    public function unserialize(string $string): Closure
    {
        // Unserialize the string back into a SerializableClosure
        $unserialized = Serializer::unserialize($string);

        // Check if it's a valid SerializableClosure
        if (! $unserialized instanceof SerializableClosure) {
            throw InvalidArgumentException::make('The provided string could not be unserialized into a valid closure.');
        }

        // Return the unserialized closure
        return $unserialized->getClosure();
    }
}
