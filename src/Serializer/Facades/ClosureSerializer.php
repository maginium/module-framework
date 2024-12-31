<?php

declare(strict_types=1);

namespace Maginium\Framework\Serializer\Facades;

use Laravel\SerializableClosure\SerializableClosure;
use Maginium\Framework\Serializer\Interfaces\ClosureSerializerInterface;
use Maginium\Framework\Support\Facade;

/**
 * Serializer Facade.
 *
 * Provides a static interface to the serialization and deserialization methods defined in the ClosureSerializerInterface.
 *
 * @method static SerializableClosure make(callable $closure) Create a SerializableClosure instance for the given closure.
 * @method static ?string serialize(callable $closure) Serialize the given closure into a serialized string format.
 * @method static callable unserialize(string $string) Unserialize the given serialized string back into its original closure.
 * @method static \Closure getClosure() Get the original closure from the serialized closure.
 * @method static mixed __invoke() Delegate to the closure when this class is invoked as a method.
 *
 * @see ClosureSerializerInterface
 */
class ClosureSerializer extends Facade
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
        return ClosureSerializerInterface::class;
    }
}
