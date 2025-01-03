<?php

declare(strict_types=1);

namespace Maginium\Framework\Dto\Casters;

use ArrayAccess;
use Maginium\Foundation\Exceptions\LogicException;
use Maginium\Framework\Dto\Interfaces\CasterInterface;
use Traversable;

/**
 * Class ArrayCaster.
 *
 * This class implements the `CasterInterface` and is responsible for casting values into arrays or objects that implement
 * the `ArrayAccess` interface. It can handle a collection of items, transforming them into the specified item type.
 */
class ArrayCaster implements CasterInterface
{
    /**
     * @param array $types The types of destinations the value can be cast to. It expects either 'array' or a class implementing `ArrayAccess`.
     * @param string $itemType The type of the items in the array or `ArrayAccess` object.
     */
    public function __construct(
        private array $types,
        private string $itemType,
    ) {
    }

    /**
     * Cast the provided value into an array or an object that implements `ArrayAccess`.
     *
     * The method checks the provided value against the allowed types and performs the necessary casting. It handles the
     * transformation of the provided data into an array or an object implementing `ArrayAccess`, ensuring each item in the
     * array is of the correct type.
     *
     * @param mixed $value The value to be cast, typically an array or an object implementing `ArrayAccess`.
     *
     * @throws LogicException If the value cannot be cast into the specified types.
     *
     * @return array|ArrayAccess The casted result, which could either be an array or an object implementing `ArrayAccess`.
     */
    public function cast(mixed $value): array|ArrayAccess
    {
        // Iterate over the allowed types to determine the appropriate cast.
        foreach ($this->types as $type) {
            // Check if the type is 'array' and perform the transformation to an array.
            if ($type === 'array') {
                return $this->mapInto(
                    destination: [],
                    items: $value,
                );
            }

            // Check if the type is a subclass of ArrayAccess and perform the transformation.
            if (is_subclass_of($type, ArrayAccess::class)) {
                return $this->mapInto(
                    destination: new $type,
                    items: $value,
                );
            }
        }

        // Throw an exception if the type cannot be cast to either 'array' or a subclass of ArrayAccess.
        throw LogicException::make(
            'Caster [ArrayCaster] may only be used to cast arrays or objects that implement ArrayAccess.',
        );
    }

    /**
     * Map the items into the specified destination (either array or ArrayAccess).
     *
     * This method will iterate over the items and cast each one using the `castItem` method. It ensures that the destination
     * object or array is populated with the properly cast items.
     *
     * @param array|ArrayAccess $destination The destination where the items will be mapped. This can be an array or an object implementing `ArrayAccess`.
     * @param mixed $items The items that will be mapped into the destination.
     *
     * @throws LogicException If the destination is not traversable (for `ArrayAccess` objects).
     *
     * @return array|ArrayAccess The destination populated with the casted items.
     */
    private function mapInto(array|ArrayAccess $destination, mixed $items): array|ArrayAccess
    {
        // Ensure that if the destination is an instance of ArrayAccess, it must also be traversable.
        if ($destination instanceof ArrayAccess && ! is_subclass_of($destination, Traversable::class)) {
            throw LogicException::make(
                'Caster [ArrayCaster] may only be used to cast ArrayAccess objects that are traversable.',
            );
        }

        // Iterate over the items and cast each item, then assign it to the destination.
        foreach ($items as $key => $item) {
            $destination[$key] = $this->castItem($item);
        }

        return $destination;
    }

    /**
     * Cast a single item into the desired type.
     *
     * This method handles the transformation of individual items. If the item is already of the correct type, it will be
     * returned as is. If it is an array, it will instantiate a new object of the specified item type. Otherwise, a
     * `LogicException` is thrown.
     *
     * @param mixed $data The item to be cast.
     *
     * @throws LogicException If the item is neither an array nor an instance of the specified item type.
     *
     * @return mixed The casted item, which could either be an instance of the specified item type or an object created from an array.
     */
    private function castItem(mixed $data)
    {
        // If the item is already an instance of the expected item type, return it as is.
        if ($data instanceof $this->itemType) {
            return $data;
        }

        // If the item is an array, create a new instance of the expected item type using the array values.
        if (is_array($data)) {
            return new $this->itemType(...$data);
        }

        // Throw an exception if the item is neither an instance of the item type nor an array.
        throw LogicException::make(
            "Caster [ArrayCaster] each item must be an array or an instance of the specified item type [{$this->itemType}].",
        );
    }
}
