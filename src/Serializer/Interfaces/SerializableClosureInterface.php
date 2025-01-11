<?php

declare(strict_types=1);

namespace Maginium\Framework\Serializer\Interfaces;

use Closure;
use Laravel\SerializableClosure\SerializableClosure as BaseSerializableClosure;
use Maginium\Foundation\Exceptions\InvalidArgumentException;

/**
 * SerializableClosureInterface.
 *
 * This interface defines the methods required for a closure serializer,
 * ensuring that any implementing class provides the necessary functionalities
 * to serialize and unserialize closures.
 */
interface SerializableClosureInterface
{
    /**
     * Static method to create a SerializableClosure for the given closure.
     *
     * @param  Closure  $closure  Closure to be serialized.
     *
     * @throws InvalidArgumentException If the provided data is not a callable.
     *
     * @return BaseSerializableClosure The serializer instance.
     */
    public function make(Closure $closure): BaseSerializableClosure;

    /**
     * Serialize a closure into a string format.
     *
     * @param  Closure  $closure  Closure to be serialized.
     *
     * @throws InvalidArgumentException If the provided data is not a callable.
     *
     * @return string The serialized closure string.
     */
    public function serialize(Closure $closure): string;

    /**
     * Unserialize a string back into a closure.
     *
     * @param  string  $string  String to be unserialized.
     *
     * @throws InvalidArgumentException If the string cannot be unserialized.
     *
     * @return Closure|callable|mixed The unserialized closure.
     */
    public function unserialize(string $string): mixed;

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
    public function isSerializedClosure(string|Closure $value): bool;
}
