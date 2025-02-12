<?php

declare(strict_types=1);

namespace Maginium\Framework\Serializer\Facades;

use Maginium\Framework\Serializer\Interfaces\SerializableClosureInterface;
use Maginium\Framework\Support\Facade;

/**
 * Serializer Facade.
 *
 * Provides a static interface to the serialization and deserialization methods defined in the SerializableClosureInterface.
 *
 * @method static SerializableClosureInterface make(callable $closure) Create a SerializableClosure instance for the given closure.
 * @method static ?string serialize(\Closure|SerializableClosureInterface $closure) Serialize the given closure into a serialized string format.
 * @method static SerializableClosureInterface unserialize(string $string) Unserialize the given serialized string back into its original closure.
 * @method static \Closure getClosure() Get the original closure from the serialized closure.
 * @method static mixed __invoke() Delegate to the closure when this class is invoked as a method.
 * @method static bool isSerializedClosure(string|Closure $value) Check if the given string is a serialized closure.
 *
 * @see SerializableClosureInterface
 */
class SerializableClosure extends Facade
{
    /**
     * Get the accessor for the facade.
     *
     * This method must be implemented by subclasses to return the accessor string
     * corresponding to the underlying service or class the facade represents.
     *
     * @return string The accessor for the facade.
     */
    protected static function getAccessor(): string
    {
        return SerializableClosureInterface::class;
    }
}
