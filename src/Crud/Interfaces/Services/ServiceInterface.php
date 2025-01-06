<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud\Interfaces\Services;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Framework\Crud\Interfaces\Repositories\RepositoryInterface;
use Maginium\Framework\Database\Interfaces\Data\ModelInterface;

/**
 * Interface ServiceInterface.
 *
 * Defines the contract for service classes that handle model operations.
 */
interface ServiceInterface
{
    /**
     * Default identifier field name.
     *
     * This constant holds the default identifier field name, which is used in methods where the
     * identifier is not explicitly provided. The default is typically 'id'.
     *
     * @var string
     */
    public const DEFAULT_IDENTIFIER = 'id';

    /**
     * Retrieve a list of order payments matching the specified search criteria.
     *
     * This method retrieves a list of order payments that match the criteria provided
     * in the SearchCriteriaInterface object. It is useful for filtering and paginating
     * results based on specific conditions.
     *
     * @param SearchCriteriaInterface $searchCriteria The search criteria to filter results.
     *
     * @return SearchResultsInterface The search results containing the list of order payments.
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;

    /**
     * Get an model by its ID.
     *
     * This method attempts to load an model using its unique identifier (ID). If no
     * model with the specified ID exists, it throws an exception. This is typically used
     * when you need to retrieve a single model based on its unique ID.
     *
     * @param int $id The ID of the model to load.
     *
     * @throws NoSuchEntityException If the model with the given ID doesn't exist.
     * @throws LocalizedException If a general error occurs while retrieving the model.
     *
     * @return ModelInterface|false The model if found, false otherwise.
     */
    public function get($id): ModelInterface;

    /**
     * Get an model by a specific identifier.
     *
     * This method allows retrieval of an model using a custom identifier field, such
     * as 'email', 'sku', or any other identifier. If an model with the provided
     * identifier value is found, it returns the model; otherwise, it returns false.
     *
     * @param mixed $value The value of the identifier to search for.
     * @param string $identifier The name of the identifier field (e.g., 'email', 'sku').
     *
     * @throws NoSuchEntityException If the model with the given identifier doesn't exist.
     * @throws LocalizedException If a general error occurs while retrieving the model.
     *
     * @return ModelInterface The model if found, false otherwise.
     */
    public function getBy($value, string $identifier = self::DEFAULT_IDENTIFIER): ModelInterface;

    /**
     * Retrieve an model by its ID.
     *
     * This method loads an model using its unique ID. If no model is found, it returns false.
     *
     * @param int $id The ID of the model to load.
     *
     * @return ModelInterface|false The model if found, false otherwise.
     */
    public function getById(int $id): ModelInterface|false;

    /**
     * Save a new model to the database.
     *
     * This method creates a new model instance using the repository's factory,
     * sets the provided data, and saves the model to the database.
     *
     * @param array $data The data to populate the new model.
     *
     * @throws CouldNotSaveException If the save operation fails.
     *
     * @return ModelInterface The saved model.
     */
    public function save(array $data): ModelInterface;

    /**
     * Update an existing model in the database.
     *
     * This method loads an existing model instance using the provided ID,
     * sets the provided data, and updates the model in the database.
     *
     * @param int $id The ID of the model to update.
     * @param array $data The data to update the model with.
     *
     * @throws CouldNotSaveException If the update operation fails.
     * @throws NoSuchEntityException If the model with the given ID does not exist.
     *
     * @return ModelInterface The updated model.
     */
    public function update($id, array $data): ModelInterface;

    /**
     * Upsert (insert or update) an model in the database.
     *
     * This method attempts to insert a new model if it does not exist or update the
     * existing model if it already exists. It checks for an existing model based on
     * the provided unique keys and performs an insert or update operation accordingly.
     *
     * @param array $data The data to populate the new model or update the existing one.
     * @param array $uniqueBy The unique fields to check if the model already exists.
     * @param array $update The data to update the model with if it already exists.
     *
     * @throws CouldNotSaveException If the upsert operation fails.
     *
     * @return ModelInterface The result of the upsert operation.
     */
    public function upsert(array $data, array $uniqueBy, array $update): ModelInterface;

    /**
     * Delete an model from the database.
     *
     * This method deletes the provided model from the database using the model repository.
     * If the delete operation fails, an exception is thrown. It is useful for removing
     * models from the database when they are no longer needed.
     *
     * @param ModelInterface $model The model to be deleted.
     *
     * @throws Exception If the delete operation fails.
     *
     * @return ModelInterface The result of the delete operation.
     */
    public function delete(ModelInterface $model): ModelInterface;

    /**
     * Delete an model by its ID.
     *
     * This method retrieves an model by its ID and deletes it. If the model does not
     * exist, a NotFoundException is thrown. It is useful when you need to delete an
     * model by its unique identifier.
     *
     * @param int $id The ID of the model to be deleted.
     *
     * @throws NotFoundException If the model with the provided ID is not found.
     * @throws Exception If the delete operation fails.
     *
     * @return ModelInterface The result of the delete operation.
     */
    public function deleteById(int $id): ModelInterface;

    /**
     * Get the repository instance.
     *
     * @return RepositoryInterface The repository instance.
     */
    public function getRepository(): RepositoryInterface;
}
