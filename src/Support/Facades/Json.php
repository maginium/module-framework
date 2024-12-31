<?php

declare(strict_types=1);

namespace Maginium\Framework\Support\Facades;

use Maginium\Framework\Serializer\Interfaces\JsonInterface;
use Maginium\Framework\Support\Facade;

/**
 * Json Facade.
 *
 * Provides a static interface to the serialization and deserialization methods defined in the JsonInterface.
 *
 * @method static ?string encode(mixed $data) Encode the given data into a JSON string format.
 * @method static mixed decode(string $string) Decode the given JSON string back into its original data format.
 * @method static bool isValid(string $json) Check if the given string is a valid JSON formatted string.
 * @method static bool isJson(string $string) Check if the string is a JSON formatted string.
 *
 * @see JsonInterface
 */
class Json extends Facade
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
        return JsonInterface::class;
    }
}
