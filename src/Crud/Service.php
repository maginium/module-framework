<?php

declare(strict_types=1);

namespace Maginium\Framework\Crud;

use AllowDynamicProperties;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Maginium\Foundation\Exceptions\CouldNotDeleteException;
use Maginium\Foundation\Exceptions\CouldNotSaveException;
use Maginium\Foundation\Exceptions\Exception;
use Maginium\Foundation\Exceptions\InvalidArgumentException;
use Maginium\Foundation\Exceptions\LocalizedException;
use Maginium\Foundation\Exceptions\NoSuchEntityException;
use Maginium\Framework\Crud\Abstracts\AbstractService;
use Maginium\Framework\Crud\Interfaces\Repositories\RepositoryInterface;
use Maginium\Framework\Crud\Interfaces\Services\ServiceInterface;
use Maginium\Framework\Database\Interfaces\Data\ModelInterface;
use Maginium\Framework\Support\Facades\Log;

/**
 * Class Service.
 *
 * A generic service class for managing CRUD operations for models.
 * This class interacts with the repository layer to perform operations such as
 * retrieving, saving, deleting, and creating models.
 */
#[AllowDynamicProperties] // Allow dynamic properties for backward compatibility
class Service extends AbstractService implements ServiceInterface
{
    /**
     * Repository instance for interacting with the database.
     *
     * @var RepositoryInterface
     */
    protected RepositoryInterface $repository;

    /**
     * The name of the model being managed by this service.
     *
     * @var string
     */
    protected string $modelName;

    /**
     * Constructor for the Service class.
     * Initializes the repository and sets up logging and model naming.
     *
     * @param RepositoryInterface $repository The repository for the model.
     */
    public function __construct(
        RepositoryInterface $repository,
    ) {
        // Assign the repository instance to the class property
        $this->repository = $repository;

        // Set the class name for log context
        Log::setClassName(static::class);

        // Dynamically determine and set the model name using the repository
        $this->modelName = $repository->getEntityName();
    }

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
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        // Execute the search and return the populated search results
        return $this->repository->getList($searchCriteria);
    }

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
    public function get($id): ModelInterface
    {
        try {
            // Attempt to load the model by its ID from the repository
            $model = $this->repository->get($id);

            // Return the successfully loaded model
            return $model;
        } catch (NoSuchEntityException $e) {
            // If the model is not found, throw the exception
            throw $e;
        } catch (Exception $e) {
            // For other errors, throw a localized exception
            throw LocalizedException::make(__('Could not save the model.'));
        }
    }

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
    public function getBy($value, string $identifier = self::DEFAULT_IDENTIFIER): ModelInterface
    {
        try {
            // Filter the collection by the provided identifier field and value
            $model = $this->repository->where($identifier, $value); // Retrieve the first item that matches

            // Return the found model
            return $model;
        } catch (NoSuchEntityException $e) {
            // If no model is found, throw the exception
            throw $e;
        } catch (Exception $e) {
            // For other errors, throw a localized exception
            throw LocalizedException::make(__('Could not save the model.'));
        }
    }

    /**
     * Retrieve an model by its ID.
     *
     * This method loads an model using its unique ID. If no model is found, it returns false.
     *
     * @param int $id The ID of the model to load.
     *
     * @return ModelInterface|false The model if found, false otherwise.
     */
    public function getById(int $id): ModelInterface|false
    {
        try {
            // Filter the collection by the provided identifier field and value
            $model = $this->repository->find($id); // Retrieve the first item that matches

            // Return the found model
            return $model;
        } catch (NoSuchEntityException $e) {
            // If no model is found, throw the exception
            throw $e;
        } catch (Exception $e) {
            // For other errors, throw a localized exception
            throw LocalizedException::make(__('Could not save the model.'));
        }
    }

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
    public function save(array $data): ModelInterface
    {
        try {
            // Create a new model instance using the repository's factory
            $model = $this->repository->factory();

            // Set the data on the model instance
            $model->setData($data);

            // Save the model using the repository and return the result
            return $this->repository->save($model);
        } catch (CouldNotSaveException $e) {
            // If saving fails, rethrow the exception
            throw $e;
        } catch (Exception $e) {
            // Catch any other exceptions and throw a specific save exception
            throw CouldNotSaveException::make(__('Could not save the model.'));
        }
    }

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
    public function update($id, array $data): ModelInterface
    {
        try {
            // Load the existing model instance by ID
            $model = $this->repository->find($id);

            // Ensure the model exists
            if (! $model || ! $model->getId()) {
                throw NoSuchEntityException::make(__('Entity with ID %1 does not exist.', $id));
            }

            // Set the new data on the model instance
            $model->setData($data);

            // Save the updated model using the repository and return the result
            return $this->repository->save($model);
        } catch (NoSuchEntityException $e) {
            // If the model is not found, rethrow the exception
            throw $e;
        } catch (CouldNotSaveException $e) {
            // If the update fails, rethrow the exception
            throw $e;
        } catch (Exception $e) {
            // Catch any other exceptions and throw a specific update exception
            throw CouldNotSaveException::make(__('Could not update the model.'));
        }
    }

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
    public function upsert(array $data, array $uniqueBy, array $update): ModelInterface
    {
        try {
            // Validate the input to ensure that the data, uniqueBy, and update arrays are not empty
            if (empty($data)) {
                throw InvalidArgumentException::make(__('Data array cannot be empty.'));
            }

            // Ensure that the uniqueBy array is not empty, as it's essential for identifying unique records
            if (empty($uniqueBy)) {
                throw InvalidArgumentException::make(__('Unique by columns cannot be empty.'));
            }

            // Ensure that the update array is not empty, as it will be used to update the model if it exists
            if (empty($update)) {
                throw InvalidArgumentException::make(__('Update columns cannot be empty.'));
            }

            // Attempt to find an existing model based on the unique fields provided in $uniqueBy
            $existingEntity = $this->repository->getByUniqueFields($uniqueBy);

            if ($existingEntity) {
                // If an existing model is found, set the new data to the model (update it)
                $existingEntity->setData($update);

                // Save the updated model back to the repository (database)
                $this->repository->save($existingEntity);

                // Return the updated model (model) after saving
                return $existingEntity;
            }

            // If no existing model is found, create a new model using the provided $data
            $model = $this->repository->factory();

            // Set the data on the newly created model
            $model->setData($data);

            // Save the new model to the repository (database)
            $this->repository->save($model);

            // Return the newly inserted model (model)
            return $model;
        } catch (CouldNotSaveException $e) {
            // If the upsert operation fails due to a save error, rethrow the exception for further handling
            throw $e;
        } catch (Exception $e) {
            // Catch any other exceptions and throw a CouldNotSaveException with a relevant message
            throw CouldNotSaveException::make(__('Could not upsert the model.'));
        }
    }

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
    public function delete(ModelInterface $model): ModelInterface
    {
        try {
            // Retrieve the model before deletion to ensure it exists
            $beforeDelete = $this->get($model);

            // Attempt to delete the model from the database
            $this->repository->delete($model);

            // Return the model that was deleted
            return $beforeDelete;
        } catch (CouldNotDeleteException $e) {
            // If the delete operation fails, rethrow the exception
            throw $e;
        } catch (Exception $e) {
            // Catch general exceptions and throw a delete-specific exception
            throw Exception::make(__('Could not delete the model.'));
        }
    }

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
    public function deleteById(int $id): ModelInterface
    {
        try {
            // Retrieve the model to ensure it exists before deletion
            $beforeDelete = $this->getBy($id);

            // Delete the model by its ID
            $this->deleteById($id);

            // Return the model that was deleted
            return $beforeDelete;
        } catch (NoSuchEntityException $e) {
            // If the model is not found, throw the exception
            throw $e;
        } catch (Exception $e) {
            // Log the error if an exception occurs during deletion
            Log::error('Error deleting model by ID: ' . $e->getMessage());

            // Throw a general exception with a localized message
            throw Exception::make(__('Could not delete the model.'));
        }
    }

    /**
     * Get the repository instance.
     *
     * @return RepositoryInterface The repository instance.
     */
    public function getRepository(): RepositoryInterface
    {
        return $this->repository;
    }
}
