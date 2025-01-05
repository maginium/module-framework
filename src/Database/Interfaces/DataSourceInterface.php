<?php

declare(strict_types=1);

namespace Maginium\Framework\Database\Interfaces;

use Maginium\Framework\Database\Interfaces\Data\ModelInterface;

/**
 * Interface for data patching sources.
 *
 * This interface defines the contract for classes that provide additional data to be merged
 * into a larger response, regardless of the model type. Implementing classes should be able
 * to add model-specific data to the response.
 *
 * @property string $key Key representing sales data in the response.
 * @property int $sortOrder Sort order for customer-related data.
 */
interface DataSourceInterface
{
    /**
     * Adds additional data to the provided model.
     *
     * This method retrieves extra data (e.g., order details, user info) specific to the provided
     * model and merges it into the larger response structure.
     *
     * @param ModelInterface $model The model object for which data is being retrieved.
     * @param int $storeId The store ID to contextualize the data.
     *
     * @return mixed The additional data related to the model.
     */
    public function addData(ModelInterface $model, int $storeId): mixed;
}
