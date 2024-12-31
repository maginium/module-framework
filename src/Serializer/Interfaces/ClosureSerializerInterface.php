<?php

declare(strict_types=1);

namespace Maginium\Framework\Serializer\Interfaces;

use Closure;
use Laravel\SerializableClosure\SerializableClosure;
use Maginium\Foundation\Exceptions\InvalidArgumentException;

/**
 * ClosureSerializerInterface.
 *
 * This interface defines the methods required for a closure serializer,
 * ensuring that any implementing class provides the necessary functionalities
 * to serialize and unserialize closures.
 */
interface ClosureSerializerInterface
{
    /**
     * Static method to create a SerializableClosure for the given closure.
     *
     * @param  Closure  $closure  Closure to be serialized.
     *
     * @throws InvalidArgumentException If the provided data is not a callable.
     *
     * @return SerializableClosure The serializer instance.
     */
    public function make(Closure $closure): SerializableClosure;

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
     * @return Closure The unserialized closure.
     */
    public function unserialize(string $string): Closure;
}
