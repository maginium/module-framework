<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Maginium\Framework\Serializer\Interfaces\SerializerInterface;
use Maginium\Framework\Support\Facade;

/**
 * Serializer Facade.
 *
 * Provides a static interface to the serialization and deserialization methods defined in the SerializerInterface.
 *
 * @method static ?string serialize(mixed $data) Serializer the given data into a serialized string format.
 * @method static mixed unserialize($string, bool $allowedClasses = false) Unserialize the given serialized string back into its original data format.
 *
 * @see SerializerInterface
 */
class Serializer extends Facade
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
        return SerializerInterface::class;
    }
}
