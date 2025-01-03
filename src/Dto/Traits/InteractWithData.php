<?php

declare(strict_types=1);

namespace Maginium\Framework\Dto\Traits;

use Magento\Framework\Data\Collection;
use Magento\Framework\Model\AbstractModel;
use Maginium\Framework\Database\Interfaces\Data\ModelInterface;
use Maginium\Framework\Support\Arr;
use Maginium\Framework\Support\Reflection;

/**
 * Trait WithData.
 *
 * This trait provides functionality to retrieve a Data Transfer Object (DTO) instance based on the current model.
 * It allows easy conversion of the current object to a DTO using the associated data class.
 *
 * @property array $_originData
 */
trait InteractWithData
{
    /**
     * Return the original DTO data as an array, applying exclusions and inclusions.
     *
     * This method collects all properties from the DTO and respects any specified exclusions or inclusions
     * before returning the final transformed data.
     *
     * @return array The transformed data as an array.
     */
    public function toModel(): array
    {
        // Fetch the original data from the DTO
        $data = static::$_originData;

        // Process based on the type of the data
        if ($data instanceof ModelInterface) {
            return $data->toDataArray();
        }

        if ($data instanceof Collection) {
            return $this->processCollection($data);
        }

        if (is_array($data) && ! empty($data)) {
            return $this->processArray($data);
        }

        // Default: Return the data as an array with any exclusions/inclusions applied
        return static::make($data)->toArray();
    }

    /**
     * Process the Collection data and convert each item to an array.
     *
     * @param Collection $collection
     *
     * @return array
     */
    private function processCollection(Collection $collection): array
    {
        return Arr::each(fn($item) => $this->convertItemToArray($item), $collection->getItems());
    }

    /**
     * Process the data when it's an array of objects and convert each item to an array.
     *
     * @param array $data
     *
     * @return array
     */
    private function processArray(array $data): array
    {
        if ($this->areAllItemsObjects($data)) {
            return Arr::each(fn($item) => $this->convertItemToArray($item), $data);
        }

        return $data;
    }

    /**
     * Check if all items in the array are objects.
     *
     * @param array $data
     *
     * @return bool
     */
    private function areAllItemsObjects(array $data): bool
    {
        return Arr::reduce($data, fn($carry, $item) => $carry && is_object($item), true);
    }

    /**
     * Convert an item to an array based on its type (ModelInterface or default).
     *
     * @param mixed $item
     *
     * @return array
     */
    private function convertItemToArray($item): array
    {
        if ($item instanceof ModelInterface) {
            return $item->toDataArray();
        }

        // If the item is a subclass of AbstractModel, convert to data array
        if (Reflection::isSubclassOf($item, AbstractModel::class)) {
            return $item->toArray();
        }

        // Default: Convert to array
        return $item->toArray();
    }
}
